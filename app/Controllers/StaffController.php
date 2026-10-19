<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Services\Access;

/** Halaman Admin Presensi. Super Admin juga boleh memakai scanner. */
final class StaffController extends Controller
{
    private const PER_PAGE = 25;

    public function dashboard(): void
    {
        $user = Auth::require(Auth::ADMIN, Auth::SUPER, Auth::EVENT);
        if ($user['role'] !== Auth::ADMIN) {
            redirect('admin');
        }
        $event = $this->currentEvent($user);
        $this->render('staff/dashboard', [
            'title' => t('Beranda'),
            'active' => 'dashboard',
            'event' => $event,
            'stats' => $event ? Event::stats((int) $event['id']) : null,
            'recent' => $event ? Participant::recentCheckins((int) $event['id']) : [],
            'myScans' => User::scanCount((int) $user['id']),
        ]);
    }

    public function scan(): void
    {
        $user = Auth::require(Auth::ADMIN, Auth::SUPER, Auth::EVENT);
        $event = $this->currentEvent($user, null, 'attendance');
        $this->render('staff/scan', [
            'title' => t('Scan QR'),
            'active' => 'scan',
            'event' => $event,
            'events' => $user['role'] !== Auth::ADMIN ? Access::events($user, 'attendance') : [],
        ]);
    }

    public function participants(): void
    {
        $user = Auth::require(Auth::ADMIN, Auth::SUPER, Auth::EVENT);
        if ($user['role'] === Auth::EVENT) {
            redirect('admin/peserta');
        }
        $event = $this->currentEvent($user);
        $q = query('q');
        $st = query('st', 'all');
        $page = $this->page();
        $total = $event ? Participant::count((int) $event['id'], $q, $st) : 0;
        $this->render('staff/participants', [
            'title' => t('Peserta'),
            'active' => 'participants',
            'event' => $event,
            'stats' => $event ? Event::stats((int) $event['id']) : null,
            'list' => $event ? Participant::search((int) $event['id'], $q, $st, self::PER_PAGE, ($page - 1) * self::PER_PAGE) : [],
            'total' => $total,
            'page' => $page,
            'pages' => (int) ceil($total / self::PER_PAGE),
            'q' => $q,
            'st' => $st,
        ]);
    }

    public function participant(string $id): void
    {
        $user = Auth::require(Auth::ADMIN, Auth::SUPER, Auth::EVENT);
        if ($user['role'] !== Auth::ADMIN) {
            redirect('admin/peserta/' . (int) $id);
        }
        $p = Participant::find((int) $id);
        if (!$p || (int) $p['event_id'] !== (int) $user['event_id']) {
            abort(404, 'Peserta tidak ditemukan');
        }
        $this->render('staff/participant', [
            'title' => t('Detail Peserta'),
            'active' => 'participants',
            'back' => 'petugas/peserta',
            'p' => $p,
            'event' => Event::find((int) $p['event_id']),
        ]);
    }

    public function profile(): void
    {
        $user = Auth::require(Auth::ADMIN, Auth::SUPER, Auth::EVENT);
        if ($user['role'] !== Auth::ADMIN) {
            redirect('admin/pengaturan');
        }
        $this->render('staff/profile', [
            'title' => t('Profil Akun'),
            'active' => 'profile',
            'event' => $this->currentEvent($user),
            'scans' => User::scanCount((int) $user['id']),
        ]);
    }

    public function password(): void
    {
        $user = Auth::require(Auth::ADMIN);
        $this->changeOwnPassword($user, 'petugas/profil');
    }
}
