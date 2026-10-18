<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Services\Access;

/** Audit log: Super Admin melihat semua; Admin Event hanya event yang ditugaskan dengan izin 'audit'. */
final class AuditController extends Controller
{
    private const PER_PAGE = 40;
    public const GROUPS = ['' => 'Semua', 'assignment.' => 'Penugasan', 'event.' => 'Event', 'participant.' => 'Peserta', 'cert.' => 'Sertifikat', 'access.' => 'Akses ditolak', 'account.' => 'Akun'];

    public function index(): void
    {
        $user = $this->manager();
        $ids = Access::eventIds($user, 'audit');
        if ($ids === []) {
            Access::deny($user, null, 'audit');
        }
        $eventId = query('ev') !== '' ? (int) query('ev') : null;
        if ($eventId !== null && $ids !== null && !in_array($eventId, $ids, true)) {
            Access::deny($user, $eventId, 'audit');
        }
        $group = array_key_exists(query('jenis'), self::GROUPS) ? query('jenis') : '';
        $page = $this->page();
        $total = AuditLog::count($ids, $eventId, $group);
        $this->render('super/audit', [
            'title' => t('Audit Log'),
            'active' => 'audit',
            'events' => Access::events($user, 'audit'),
            'eventId' => $eventId,
            'group' => $group,
            'items' => AuditLog::list($ids, $eventId, $group, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'page' => $page,
            'pages' => (int) ceil($total / self::PER_PAGE),
            'total' => $total,
        ]);
    }
}
