<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Event;
use App\Models\StatusHistory;
use App\Core\Migrator;
use App\Models\AuditLog;
use App\Services\Access;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = $this->manager();
        $event = $this->currentEvent($user);
        $this->render('super/dashboard', [
            'title' => t('Dashboard'),
            'active' => 'dashboard',
            'event' => $event,
            'events' => Access::events($user),
            'pendingMigrations' => $user['role'] === Auth::SUPER ? Migrator::pending() : [],
            'stats' => $event ? Event::stats((int) $event['id']) : null,
            'slots' => $event ? Event::checkinSlots($event) : [],
            'history' => $event ? StatusHistory::list((int) $event['id'], 'all', 4) : [],
        ]);
    }

    /** Jalankan migrasi aditif yang tertunda (khusus Super Admin). */
    public function migrate(): void
    {
        $user = Auth::require(Auth::SUPER);
        try {
            $ran = Migrator::run();
            AuditLog::add($user, 'system.migrate', null, 'migration', null, ['ran' => $ran]);
            flash('ok', 'Migrasi database selesai', $ran ? implode(', ', $ran) : t('Tidak ada migrasi baru.'));
        } catch (\Throwable $e) {
            error_log((string) $e);
            flash('bad', 'Migrasi gagal', t('Periksa log server. Data lama tidak diubah.'));
        }
        redirect('admin');
    }
}
