<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use App\Services\Access;
use App\Services\Validator;

/** Kelola akun Admin Absensi dan Admin Event, termasuk penugasan event (khusus Super Admin). */
final class AccountController extends Controller
{
    private const ROLES = [Auth::ADMIN, Auth::EVENT];

    public function index(): void
    {
        Auth::require(Auth::SUPER);
        $role = in_array(query('peran'), self::ROLES, true) ? query('peran') : '';
        $this->render('super/accounts/index', [
            'title' => t('Akun Admin'), 'active' => 'accounts', 'role' => $role,
            'admins' => User::admins($role ?: null),
            'migrated' => Assignment::available(),
        ]);
    }

    private function formView(array $d, array $errors): void
    {
        $this->render('super/accounts/form', [
            'title' => t('Buat Akun Admin'), 'active' => 'accounts', 'back' => 'admin/akun', 'd' => $d, 'errors' => $errors,
            'events' => Event::all(), 'migrated' => Assignment::available(),
        ]);
    }

    public function create(): void
    {
        Auth::require(Auth::SUPER);
        $role = query('peran') === Auth::EVENT ? Auth::EVENT : Auth::ADMIN;
        $this->formView(['name' => '', 'username' => '', 'password' => User::randomPassword(10), 'role' => $role,
            'event_id' => (string) (query('ev') ?: ($_SESSION['ev'] ?? '')), 'status' => 'Aktif'], []);
    }

    public function store(): void
    {
        $super = Auth::require(Auth::SUPER);
        $d = [
            'name' => input('name'),
            'username' => strtolower(input('username')),
            'password' => (string) ($_POST['password'] ?? ''),
            'role' => input('role', Auth::ADMIN),
            'event_id' => input('event_id'),
            'status' => input('status', 'Aktif'),
        ];
        $errors = Validator::account($d, true);
        if (!in_array($d['role'], self::ROLES, true)) {
            $errors['role'] = t('Pilih peran akun.');
        } elseif ($d['role'] === Auth::EVENT && !Assignment::available()) {
            $errors['role'] = t('Jalankan migrasi database terlebih dahulu (menu Dashboard).');
        }
        if ($errors) {
            http_response_code(422);
            $this->formView($d, $errors);
            return;
        }
        $isEvent = $d['role'] === Auth::EVENT;
        $id = User::create(['event_id' => $isEvent ? null : $d['event_id']] + $d + ['must_change_password' => 1]);
        AuditLog::add($super, 'account.create', null, 'user', $id, ['username' => $d['username'], 'role' => $d['role']]);
        if ($isEvent && $d['event_id']) {
            Assignment::save($id, (int) $d['event_id'], Access::DEFAULT_PERMS, (int) $super['id']);
            AuditLog::add($super, 'assignment.add', (int) $d['event_id'], 'user', $id, ['username' => $d['username'], 'perms' => Access::DEFAULT_PERMS]);
        }
        $_SESSION['new_credential'] = ['id' => $id, 'username' => $d['username'], 'password' => $d['password']];
        flash('ok', $isEvent ? 'Akun Admin Event dibuat' : 'Akun Admin Absensi dibuat', '@' . $d['username']);
        redirect('admin/akun/' . $id);
    }

    /** Hanya akun Admin Absensi / Admin Event. Akun Super Admin tidak dikelola dari sini. */
    private function findAdmin(string $id): array
    {
        $a = User::find((int) $id);
        if (!$a || !in_array($a['role'], self::ROLES, true)) {
            abort(404, 'Akun tidak ditemukan');
        }
        return $a;
    }

    public function show(string $id): void
    {
        Auth::require(Auth::SUPER);
        $a = $this->findAdmin($id);
        $cred = $_SESSION['new_credential'] ?? null;
        unset($_SESSION['new_credential']);
        $this->render('super/accounts/show', [
            'title' => t('Detail Akun Admin'),
            'active' => 'accounts',
            'back' => 'admin/akun',
            'a' => $a,
            'event' => $a['event_id'] ? Event::find((int) $a['event_id']) : null,
            'events' => Event::all(),
            'assignments' => $a['role'] === Auth::EVENT ? Assignment::forUser((int) $a['id']) : [],
            'logs' => AuditLog::list(null, null, 'assignment.', 200),
            'scans' => User::scanCount((int) $a['id']),
            'credential' => is_array($cred) && (int) $cred['id'] === (int) $a['id'] ? $cred : null,
        ]);
    }

    public function update(string $id): void
    {
        $super = Auth::require(Auth::SUPER);
        $a = $this->findAdmin($id);
        $isEvent = $a['role'] === Auth::EVENT;
        $d = ['name' => input('name'), 'username' => $a['username'], 'password' => '', 'event_id' => $isEvent ? '' : input('event_id'), 'status' => $a['status']];
        $errors = Validator::account($d, false);
        if ($errors) {
            flash('bad', 'Akun belum diperbarui', implode(' ', array_map(static fn ($m) => t((string) $m), $errors)));
        } else {
            $data = ['name' => $d['name']];
            if (!$isEvent) {
                $data['event_id'] = $d['event_id'] ?: null;
            }
            User::update((int) $a['id'], $data);
            AuditLog::add($super, 'account.update', $isEvent ? null : ((int) $d['event_id'] ?: null), 'user', $a['id'], [
                'username' => $a['username'], 'old_event' => $a['event_id'], 'new_event' => $data['event_id'] ?? null,
            ]);
            flash('ok', 'Akun diperbarui');
        }
        redirect('admin/akun/' . $a['id']);
    }

    /** Tambah atau ubah penugasan event + izin untuk Admin Event. */
    public function assign(string $id): void
    {
        $super = Auth::require(Auth::SUPER);
        $a = $this->findAdmin($id);
        if ($a['role'] !== Auth::EVENT) {
            abort(404, 'Akun tidak ditemukan');
        }
        $event = Event::find((int) input('event_id'));
        if (!$event) {
            flash('bad', 'Penugasan gagal', 'Event tidak ditemukan.');
            redirect('admin/akun/' . $a['id']);
        }
        $perms = array_values(array_filter((array) ($_POST['perms'] ?? []), 'is_string'));
        if (input('mode') === 'add' && Assignment::find((int) $a['id'], (int) $event['id'])) {
            flash('bad', 'Penugasan gagal', 'Akun ini sudah ditugaskan ke event tersebut. Ubah izinnya di daftar penugasan.');
            redirect('admin/akun/' . $a['id']);
        }
        if (input('mode') === 'add' && !isset($_POST['perms'])) {
            $perms = Access::DEFAULT_PERMS;
        }
        [$action, $old] = Assignment::save((int) $a['id'], (int) $event['id'], $perms, (int) $super['id']);
        $new = Access::decode(json_encode($perms) ?: '[]');
        AuditLog::add($super, 'assignment.' . $action, (int) $event['id'], 'user', $a['id'], [
            'username' => $a['username'], 'added' => array_values(array_diff($new, $old)), 'removed' => array_values(array_diff($old, $new)),
        ]);
        flash('ok', $action === 'add' ? 'Penugasan ditambahkan' : 'Izin penugasan diperbarui', $event['name']);
        redirect('admin/akun/' . $a['id']);
    }

    public function unassign(string $id, string $eventId): void
    {
        $super = Auth::require(Auth::SUPER);
        $a = $this->findAdmin($id);
        if ($a['role'] === Auth::EVENT && Assignment::remove((int) $a['id'], (int) $eventId)) {
            AuditLog::add($super, 'assignment.remove', (int) $eventId, 'user', $a['id'], ['username' => $a['username']]);
            flash('ok', 'Penugasan dicabut', 'Akses ke event tersebut langsung berakhir.');
        } else {
            flash('bad', 'Penugasan tidak ditemukan');
        }
        redirect('admin/akun/' . $a['id']);
    }

    public function toggle(string $id): void
    {
        $super = Auth::require(Auth::SUPER);
        $a = $this->findAdmin($id);
        $new = $a['status'] === 'Aktif' ? 'Nonaktif' : 'Aktif';
        User::update((int) $a['id'], ['status' => $new]);
        AuditLog::add($super, 'account.status', null, 'user', $a['id'], ['username' => $a['username'], 'status' => $new]);
        flash('ok', $new === 'Aktif' ? 'Akun diaktifkan' : 'Akun dinonaktifkan', '@' . $a['username']);
        redirect('admin/akun/' . $a['id']);
    }

    public function reset(string $id): void
    {
        $super = Auth::require(Auth::SUPER);
        $a = $this->findAdmin($id);
        $password = User::randomPassword(10);
        User::setPassword((int) $a['id'], $password, true);
        AuditLog::add($super, 'account.reset_password', null, 'user', $a['id'], ['username' => $a['username']]);
        $_SESSION['new_credential'] = ['id' => (int) $a['id'], 'username' => $a['username'], 'password' => $password];
        flash('ok', 'Password sementara baru dibuat', 'Sampaikan langsung ke petugas.');
        redirect('admin/akun/' . $a['id']);
    }
}
