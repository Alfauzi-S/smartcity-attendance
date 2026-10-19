<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Models\Event;
use App\Models\AuditLog;
use App\Services\Access;
use App\Services\Validator;

final class EventController extends Controller
{
    public function index(): void
    {
        $user = $this->manager();
        $events = Access::events($user);
        foreach ($events as &$e) {
            $e['stats'] = Event::stats((int) $e['id']);
        }
        unset($e);
        $this->render('super/events/index', ['title' => t('Event'), 'active' => 'events', 'events' => $events]);
    }

    private function formData(): array
    {
        $d = [];
        foreach (['name', 'description', 'organizer', 'location', 'event_date', 'start_time', 'end_time', 'att_start', 'att_end', 'status', 'reg_status'] as $k) {
            $d[$k] = input($k);
        }
        $d['reg_start'] = input('reg_start') !== '' ? input('reg_start') : null;
        $d['reg_end'] = input('reg_end') !== '' ? input('reg_end') : null;
        $d['description'] = mb_substr($d['description'], 0, 2000);
        if (Event::hasQuotaColumns()) {
            $d['quota'] = input('quota') !== '' ? input('quota') : null;
            $d['reg_end_time'] = input('reg_end_time') !== '' ? input('reg_end_time') : null;
        }
        return $d;
    }

    /** Kuota disimpan sebagai angka; jam tutup tanpa tanggal tutup tidak berarti apa-apa. */
    private static function normalize(array $d): array
    {
        if (array_key_exists('quota', $d)) {
            $d['quota'] = $d['quota'] === null ? null : (int) $d['quota'];
            $d['reg_end_time'] = $d['reg_end'] ? $d['reg_end_time'] : null;
        }
        return $d;
    }

    private function blank(): array
    {
        return ['name' => '', 'description' => '', 'organizer' => '', 'location' => '', 'event_date' => '', 'start_time' => '', 'end_time' => '',
            'reg_start' => '', 'reg_end' => '', 'reg_end_time' => '', 'quota' => '', 'att_start' => '', 'att_end' => '', 'status' => 'Draft', 'reg_status' => 'Ditutup'];
    }

    public function create(): void
    {
        Auth::require(Auth::SUPER);
        $this->render('super/events/form', ['title' => t('Buat Event'), 'active' => 'events', 'back' => 'admin/event', 'e' => $this->blank(), 'errors' => [], 'editId' => null]);
    }

    public function store(): void
    {
        Auth::require(Auth::SUPER);
        $d = $this->formData();
        $errors = Validator::event($d);
        if ($errors) {
            http_response_code(422);
            $this->render('super/events/form', ['title' => t('Buat Event'), 'active' => 'events', 'back' => 'admin/event', 'e' => $d, 'errors' => $errors, 'editId' => null]);
            return;
        }
        $id = Event::create(self::normalize($d));
        AuditLog::add(Auth::user(), 'event.create', $id, 'event', $id);
        flash('ok', 'Event berhasil dibuat', 'Bagikan tautan registrasi, lalu buat akun Admin Presensi.');
        redirect('admin/event/' . $id);
    }

    /** Event diambil dari database lalu dicek terhadap assignment pengguna (anti-IDOR). */
    private function findOr404(string $id, array $user, ?string $perm): array
    {
        return Access::requireEvent($user, (int) $id, $perm);
    }

    public function show(string $id): void
    {
        $user = $this->manager();
        $e = $this->findOr404($id, $user, null);
        $this->render('super/events/show', [
            'title' => t('Detail Event'),
            'active' => 'events',
            'back' => 'admin/event',
            'e' => $e,
            'stats' => Event::stats((int) $e['id']),
            'admins' => DB::all("SELECT * FROM users WHERE role = 'admin' AND event_id = ? ORDER BY name", [(int) $e['id']]),
            'eventAdmins' => DB::tableExists('event_admin_assignments') ? DB::all(
                'SELECT u.*, a.permissions FROM event_admin_assignments a JOIN users u ON u.id = a.user_id WHERE a.event_id = ? ORDER BY u.name',
                [(int) $e['id']]
            ) : [],
        ]);
    }

    public function edit(string $id): void
    {
        $user = $this->manager();
        $e = $this->findOr404($id, $user, 'event_edit');
        $this->render('super/events/form', ['title' => t('Ubah Event'), 'active' => 'events', 'back' => 'admin/event/' . $e['id'], 'e' => $e, 'errors' => [], 'editId' => (int) $e['id']]);
    }

    public function update(string $id): void
    {
        $user = $this->manager();
        $e = $this->findOr404($id, $user, 'event_edit');
        $d = $this->formData();
        $errors = Validator::event($d);
        if ($errors) {
            http_response_code(422);
            $this->render('super/events/form', ['title' => t('Ubah Event'), 'active' => 'events', 'back' => 'admin/event/' . $e['id'], 'e' => $d + $e, 'errors' => $errors, 'editId' => (int) $e['id']]);
            return;
        }
        Event::update((int) $e['id'], self::normalize($d));
        $changed = array_keys(array_filter($d, static fn ($v, $k) => (string) $v !== (string) ($e[$k] ?? ''), ARRAY_FILTER_USE_BOTH));
        AuditLog::add($user, 'event.update', (int) $e['id'], 'event', $e['id'], ['fields' => $changed]);
        flash('ok', 'Perubahan event disimpan');
        redirect('admin/event/' . $e['id']);
    }

    public function setting(string $id): void
    {
        $user = $this->manager();
        $e = $this->findOr404($id, $user, 'event_edit');
        $field = input('field');
        $value = input('value');
        if (Event::setSetting((int) $e['id'], $field, $value)) {
            AuditLog::add($user, 'event.setting', (int) $e['id'], 'event', $e['id'], ['field' => $field, 'old' => $e[$field] ?? null, 'new' => $value]);
            $messages = [
                'reg_status' => ['Dibuka' => 'Registrasi dibuka', 'Ditutup' => 'Registrasi ditutup'],
                'att_status' => ['open' => 'Presensi dibuka', 'notyet' => 'Presensi belum dibuka', 'closed' => 'Presensi ditutup'],
            ];
            $text = isset($messages[$field][$value])
                ? t($messages[$field][$value])
                : t('Status event diubah menjadi :status', ['status' => t($value)]);
            flash('ok', 'Pengaturan event diperbarui', $text);
        } else {
            flash('bad', 'Pengaturan tidak valid');
        }
        redirect('admin/event/' . $e['id']);
    }

    public function destroy(string $id): void
    {
        $user = Auth::require(Auth::SUPER);
        $e = $this->findOr404($id, $user, null);
        $hasCert = DB::tableExists('cert_templates') && (int) DB::value('SELECT COUNT(*) FROM cert_templates WHERE event_id = ?', [(int) $e['id']]) > 0;
        if (Event::participantCount((int) $e['id']) > 0 || $hasCert) {
            flash('bad', 'Event tidak dapat dihapus', 'Event ini sudah memiliki peserta. Ubah status menjadi Ditutup untuk mengarsipkan.');
            redirect('admin/event/' . $e['id']);
        }
        Event::delete((int) $e['id']);
        AuditLog::add($user, 'event.delete', null, 'event', $e['id'], ['name' => $e['name']]);
        unset($_SESSION['ev']);
        flash('ok', 'Event dihapus');
        redirect('admin/event');
    }
}
