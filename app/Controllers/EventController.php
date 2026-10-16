<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Models\Event;
use App\Services\Validator;

final class EventController extends Controller
{
    public function index(): void
    {
        Auth::require(Auth::SUPER);
        $events = Event::all();
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
        return $d;
    }

    private function blank(): array
    {
        return ['name' => '', 'description' => '', 'organizer' => '', 'location' => '', 'event_date' => '', 'start_time' => '', 'end_time' => '',
            'reg_start' => '', 'reg_end' => '', 'att_start' => '', 'att_end' => '', 'status' => 'Draft', 'reg_status' => 'Ditutup'];
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
        $id = Event::create($d);
        flash('ok', 'Event berhasil dibuat', 'Bagikan tautan registrasi, lalu buat akun Admin Absensi.');
        redirect('admin/event/' . $id);
    }

    private function findOr404(string $id): array
    {
        $e = Event::find((int) $id);
        if (!$e) {
            abort(404, 'Event tidak ditemukan');
        }
        return $e;
    }

    public function show(string $id): void
    {
        Auth::require(Auth::SUPER);
        $e = $this->findOr404($id);
        $this->render('super/events/show', [
            'title' => t('Detail Event'),
            'active' => 'events',
            'back' => 'admin/event',
            'e' => $e,
            'stats' => Event::stats((int) $e['id']),
            'admins' => DB::all("SELECT * FROM users WHERE role = 'admin' AND event_id = ? ORDER BY name", [(int) $e['id']]),
        ]);
    }

    public function edit(string $id): void
    {
        Auth::require(Auth::SUPER);
        $e = $this->findOr404($id);
        $this->render('super/events/form', ['title' => t('Ubah Event'), 'active' => 'events', 'back' => 'admin/event/' . $e['id'], 'e' => $e, 'errors' => [], 'editId' => (int) $e['id']]);
    }

    public function update(string $id): void
    {
        Auth::require(Auth::SUPER);
        $e = $this->findOr404($id);
        $d = $this->formData();
        $errors = Validator::event($d);
        if ($errors) {
            http_response_code(422);
            $this->render('super/events/form', ['title' => t('Ubah Event'), 'active' => 'events', 'back' => 'admin/event/' . $e['id'], 'e' => $d + $e, 'errors' => $errors, 'editId' => (int) $e['id']]);
            return;
        }
        Event::update((int) $e['id'], $d);
        flash('ok', 'Perubahan event disimpan');
        redirect('admin/event/' . $e['id']);
    }

    public function setting(string $id): void
    {
        Auth::require(Auth::SUPER);
        $e = $this->findOr404($id);
        $field = input('field');
        $value = input('value');
        if (Event::setSetting((int) $e['id'], $field, $value)) {
            $messages = [
                'reg_status' => ['Dibuka' => 'Registrasi dibuka', 'Ditutup' => 'Registrasi ditutup'],
                'att_status' => ['open' => 'Absensi dibuka', 'notyet' => 'Absensi belum dibuka', 'closed' => 'Absensi ditutup'],
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
        Auth::require(Auth::SUPER);
        $e = $this->findOr404($id);
        if (Event::participantCount((int) $e['id']) > 0) {
            flash('bad', 'Event tidak dapat dihapus', 'Event ini sudah memiliki peserta. Ubah status menjadi Ditutup untuk mengarsipkan.');
            redirect('admin/event/' . $e['id']);
        }
        Event::delete((int) $e['id']);
        unset($_SESSION['ev']);
        flash('ok', 'Event dihapus');
        redirect('admin/event');
    }
}
