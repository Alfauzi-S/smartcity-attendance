<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Event;
use App\Models\User;
use App\Services\Validator;

/** Kelola akun Admin Absensi (khusus Super Admin). */
final class AccountController extends Controller
{
    public function index(): void
    {
        Auth::require(Auth::SUPER);
        $this->render('super/accounts/index', ['title' => t('Akun Admin'), 'active' => 'accounts', 'admins' => User::admins()]);
    }

    public function create(): void
    {
        Auth::require(Auth::SUPER);
        $d = ['name' => '', 'username' => '', 'password' => User::randomPassword(10), 'event_id' => (string) (query('ev') ?: ($_SESSION['ev'] ?? '')), 'status' => 'Aktif'];
        $this->render('super/accounts/form', ['title' => t('Buat Akun Admin'), 'active' => 'accounts', 'back' => 'admin/akun', 'd' => $d, 'errors' => [], 'events' => Event::all()]);
    }

    public function store(): void
    {
        Auth::require(Auth::SUPER);
        $d = [
            'name' => input('name'),
            'username' => strtolower(input('username')),
            'password' => (string) ($_POST['password'] ?? ''),
            'event_id' => input('event_id'),
            'status' => input('status', 'Aktif'),
        ];
        $errors = Validator::account($d, true);
        if ($errors) {
            http_response_code(422);
            $this->render('super/accounts/form', ['title' => t('Buat Akun Admin'), 'active' => 'accounts', 'back' => 'admin/akun', 'd' => $d, 'errors' => $errors, 'events' => Event::all()]);
            return;
        }
        $id = User::create($d + ['role' => Auth::ADMIN, 'must_change_password' => 1]);
        $_SESSION['new_credential'] = ['id' => $id, 'username' => $d['username'], 'password' => $d['password']];
        flash('ok', 'Akun Admin Absensi dibuat', '@' . $d['username']);
        redirect('admin/akun/' . $id);
    }

    private function findAdmin(string $id): array
    {
        $a = User::find((int) $id);
        if (!$a || $a['role'] !== Auth::ADMIN) {
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
            'scans' => User::scanCount((int) $a['id']),
            'credential' => is_array($cred) && (int) $cred['id'] === (int) $a['id'] ? $cred : null,
        ]);
    }

    public function update(string $id): void
    {
        Auth::require(Auth::SUPER);
        $a = $this->findAdmin($id);
        $d = ['name' => input('name'), 'username' => $a['username'], 'password' => '', 'event_id' => input('event_id'), 'status' => $a['status']];
        $errors = Validator::account($d, false);
        if ($errors) {
            flash('bad', 'Akun belum diperbarui', implode(' ', array_map(static fn ($m) => t((string) $m), $errors)));
        } else {
            User::update((int) $a['id'], ['name' => $d['name'], 'event_id' => $d['event_id'] ?: null]);
            flash('ok', 'Akun diperbarui');
        }
        redirect('admin/akun/' . $a['id']);
    }

    public function toggle(string $id): void
    {
        Auth::require(Auth::SUPER);
        $a = $this->findAdmin($id);
        $new = $a['status'] === 'Aktif' ? 'Nonaktif' : 'Aktif';
        User::update((int) $a['id'], ['status' => $new]);
        flash('ok', $new === 'Aktif' ? 'Akun diaktifkan' : 'Akun dinonaktifkan', '@' . $a['username']);
        redirect('admin/akun/' . $a['id']);
    }

    public function reset(string $id): void
    {
        Auth::require(Auth::SUPER);
        $a = $this->findAdmin($id);
        $password = User::randomPassword(10);
        User::setPassword((int) $a['id'], $password, true);
        $_SESSION['new_credential'] = ['id' => (int) $a['id'], 'username' => $a['username'], 'password' => $password];
        flash('ok', 'Password sementara baru dibuat', 'Sampaikan langsung ke petugas.');
        redirect('admin/akun/' . $a['id']);
    }
}
