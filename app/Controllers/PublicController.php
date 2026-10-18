<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\RateLimiter;
use App\Models\Event;
use App\Models\Participant;
use App\Models\ParticipantOptions;
use App\Models\User;
use App\Services\Validator;
use PDOException;

final class PublicController extends Controller
{
    public function home(): void
    {
        if (!\App\Core\Database::tableExists('users') || !User::hasSuper()) {
            redirect('install');
        }
        $events = Event::publicList();
        if (count($events) === 1) {
            redirect('e/' . $events[0]['slug']);
        }
        $this->render('public/home', ['title' => t('Event'), 'events' => $events], 'public');
    }

    private function publicEvent(string $slug): array
    {
        $event = Event::findBySlug($slug);
        if (!$event || $event['status'] === 'Draft') {
            abort(404, 'Event tidak ditemukan', 'Tautan registrasi mungkin salah atau event belum dipublikasikan.');
        }
        return $event;
    }

    public function event(string $slug): void
    {
        $event = $this->publicEvent($slug);
        [$open, $closedMessage] = Event::registrationState($event);
        $this->render('public/event', [
            'title' => $event['name'],
            'event' => $event,
            'open' => $open,
            'closedMessage' => $closedMessage,
            'myTicket' => $this->myTicket((int) $event['id']),
        ], 'public');
    }

    public function registerForm(string $slug): void
    {
        $event = $this->publicEvent($slug);
        [$open, $msg] = Event::registrationState($event);
        if (!$open) {
            flash('bad', 'Registrasi tidak tersedia', $msg);
            redirect('e/' . $slug);
        }
        $this->render('public/register', ['title' => t('Registrasi'), 'event' => $event, 'old' => [], 'errors' => []], 'public');
    }

    public function register(string $slug): void
    {
        $event = $this->publicEvent($slug);
        [$open, $msg] = Event::registrationState($event);
        if (!$open) {
            flash('bad', 'Registrasi tidak tersedia', $msg);
            redirect('e/' . $slug);
        }
        if (input('website') !== '') { // honeypot anti-bot
            redirect('e/' . $slug);
        }
        $data = [
            'identity_number' => preg_replace('/\s+/', '', input('identity_number')) ?? '',
            'profession' => ParticipantOptions::pick(input('profession'), input('profession_other')),
            'full_name' => preg_replace('/\s+/', ' ', input('full_name')) ?? '',
            'phone' => Validator::normalizePhone(input('phone')),
            'institution' => input('institution'),
            'field_of_study' => ParticipantOptions::pick(input('field_of_study'), input('field_of_study_other')),
        ];
        $key = 'reg:' . client_ip();
        $errors = [];
        if (RateLimiter::tooMany($key, 30, 3600)) {
            $errors['_form'] = t('Terlalu banyak percobaan registrasi dari jaringan ini. Coba lagi dalam satu jam.');
        } else {
            $errors = Validator::registration($data, (int) $event['id']);
        }
        if (!$errors) {
            try {
                $p = Participant::create((int) $event['id'], $data);
            } catch (PDOException) {
                $errors['identity_number'] = t('Nomor identitas ini sudah terdaftar di event ini.');
            }
        }
        if ($errors) {
            http_response_code(422);
            $this->render('public/register', ['title' => t('Registrasi'), 'event' => $event, 'old' => $data, 'errors' => $errors], 'public');
            return;
        }
        RateLimiter::hit($key);
        $_SESSION['tickets'][(int) $event['id']] = $p['code'];
        redirect('t/' . $p['code'], ['baru' => '1']);
    }

    public function ticket(string $code): void
    {
        $p = Participant::findByCode(strtoupper($code));
        if (!$p) {
            abort(404, 'QR Code tidak ditemukan', 'Periksa kembali tautan QR Anda atau hubungi panitia.');
        }
        $event = Event::find((int) $p['event_id']);
        $this->render('public/ticket', [
            'title' => t('QR Code :name', ['name' => $p['full_name']]),
            'event' => $event,
            'p' => $p,
            'isNew' => query('baru') === '1' && ($_SESSION['tickets'][(int) $p['event_id']] ?? '') === $p['code'],
        ], 'public');
    }

    private function myTicket(int $eventId): ?string
    {
        $code = $_SESSION['tickets'][$eventId] ?? null;
        return is_string($code) ? $code : null;
    }
}
