<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Event;
use App\Models\Participant;
use App\Models\StatusHistory;
use App\Models\AuditLog;
use App\Services\Access;
use App\Services\Exporter;

final class ReportController extends Controller
{
    public function index(): void
    {
        $user = $this->manager();
        $event = $this->currentEvent($user, null, 'reports');
        $st = query('st', 'all');
        $this->render('super/reports', [
            'title' => t('Laporan Absensi'),
            'active' => 'reports',
            'event' => $event,
            'events' => Access::events($user, 'reports'),
            'stats' => $event ? Event::stats((int) $event['id']) : null,
            'st' => $st,
            'rowCount' => $event ? Participant::count((int) $event['id'], '', $st) : 0,
            'preview' => $event ? Participant::search((int) $event['id'], '', $st, 8) : [],
            'xlsx' => Exporter::xlsxAvailable(),
        ]);
    }

    public function export(): void
    {
        $user = $this->manager();
        $event = $this->currentEvent($user, null, 'reports');
        if (!$event) {
            flash('bad', 'Belum ada event untuk diekspor');
            redirect('admin/laporan');
        }
        $st = query('st', 'all');
        $format = query('format', 'csv');
        $rows = Exporter::rows((int) $event['id'], $st);
        AuditLog::add($user, 'report.export', (int) $event['id'], 'report', null, ['format' => $format, 'filter' => $st, 'rows' => max(0, count($rows) - 1)]);
        $base = 'rekap-absensi_' . $event['slug'] . '_' . date('Ymd-Hi');
        if ($format === 'xlsx') {
            if (!Exporter::xlsxAvailable()) {
                flash('bad', 'Ekspor Excel tidak tersedia', 'Ekstensi PHP zip belum aktif di server. Gunakan CSV atau aktifkan ekstensi zip.');
                redirect('admin/laporan');
            }
            Exporter::xlsx($base . '.xlsx', t('Absensi'), $rows);
        }
        Exporter::csv($base . '.csv', $rows);
    }

    public function history(): void
    {
        $user = $this->manager();
        $event = $this->currentEvent($user, null, 'reports');
        $to = query('ke', 'all');
        $page = $this->page();
        $per = 30;
        $total = $event ? StatusHistory::count((int) $event['id'], $to) : 0;
        $this->render('super/history', [
            'title' => t('Riwayat Perubahan'),
            'active' => 'history',
            'event' => $event,
            'events' => Access::events($user, 'reports'),
            'items' => $event ? StatusHistory::list((int) $event['id'], $to, $per, ($page - 1) * $per) : [],
            'to' => $to,
            'page' => $page,
            'pages' => (int) ceil($total / $per),
            'total' => $total,
        ]);
    }
}
