<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\Event;

/**
 * Hak akses per event (deny-by-default).
 * - Super Admin: semua event, semua izin.
 * - Admin Event: hanya event di tabel event_admin_assignments, dengan izin yang dicentang Super Admin.
 * - Admin Absensi: hanya izin 'attendance' pada event users.event_id.
 * Semua pemeriksaan memakai data database, bukan event_id/participant_id kiriman browser.
 */
final class Access
{
    /** Izin granular yang dapat diberikan ke Admin Event. */
    public const PERMS = [
        'event_edit' => 'Mengubah informasi dan status event',
        'participants' => 'Melihat dan mengoreksi data peserta',
        'status' => 'Mengubah status kehadiran manual',
        'attendance' => 'Memindai QR / memproses absensi',
        'reports' => 'Melihat laporan, riwayat, dan mengekspor',
        'cert_manage' => 'Mengelola template, variabel, nomor, dan generate sertifikat',
        'cert_publish' => 'Memublikasikan sertifikat',
        'cert_revoke' => 'Mencabut dan menerbitkan ulang sertifikat',
        'audit' => 'Melihat audit log event',
    ];

    /** Kebijakan awal: semua izin operasional, kecuali publikasi dan pencabutan sertifikat. */
    public const DEFAULT_PERMS = ['event_edit', 'participants', 'status', 'attendance', 'reports', 'cert_manage', 'audit'];

    /** Pseudo-izin: salah satu izin sertifikat (untuk membuka menu Sertifikat). */
    public const CERT_ANY = 'cert_any';

    /** @var array<int, array<int, string[]>> */
    private static array $cache = [];

    /** @return array<int, string[]> event_id => izin */
    public static function assignments(int $userId): array
    {
        if (isset(self::$cache[$userId])) {
            return self::$cache[$userId];
        }
        $out = [];
        if (DB::tableExists('event_admin_assignments')) {
            foreach (DB::all('SELECT event_id, permissions FROM event_admin_assignments WHERE user_id = ?', [$userId]) as $row) {
                $out[(int) $row['event_id']] = self::decode($row['permissions']);
            }
        }
        return self::$cache[$userId] = $out;
    }

    public static function decode(?string $json): array
    {
        $list = json_decode((string) $json, true);
        return is_array($list) ? array_values(array_intersect(array_keys(self::PERMS), $list)) : [];
    }

    public static function forget(): void
    {
        self::$cache = [];
    }

    /** @return int[]|null null = semua event (Super Admin) */
    public static function eventIds(array $user, ?string $perm = null): ?array
    {
        return match ($user['role']) {
            Auth::SUPER => null,
            Auth::EVENT => array_keys(array_filter(
                self::assignments((int) $user['id']),
                static fn (array $perms) => $perm === null
                    || ($perm === self::CERT_ANY ? (bool) array_intersect(['cert_manage', 'cert_publish', 'cert_revoke'], $perms) : in_array($perm, $perms, true))
            )),
            Auth::ADMIN => ($perm === null || $perm === 'attendance') && $user['event_id'] ? [(int) $user['event_id']] : [],
            default => [],
        };
    }

    /** $perm null = cukup ditugaskan ke event (izin apa pun). */
    public static function can(array $user, int $eventId, ?string $perm): bool
    {
        if ($eventId <= 0) {
            return false;
        }
        $ids = self::eventIds($user, $perm);
        return $ids === null || in_array($eventId, $ids, true);
    }

    /** Apakah pengguna punya izin ini di minimal satu event (untuk menampilkan menu). */
    public static function any(array $user, string $perm): bool
    {
        $ids = self::eventIds($user, $perm);
        return $ids === null || $ids !== [];
    }

    /** Event yang boleh dipakai pengguna, terbaru dulu. */
    public static function events(array $user, ?string $perm = null): array
    {
        $ids = self::eventIds($user, $perm);
        if ($ids === null) {
            return Event::all();
        }
        if (!$ids) {
            return [];
        }
        return DB::all(
            'SELECT * FROM events WHERE id IN (' . implode(',', array_map('intval', $ids)) . ') ORDER BY event_date DESC, id DESC'
        );
    }

    /** Ambil event bila diizinkan; tolak (403 + audit log) bila tidak. */
    public static function requireEvent(array $user, int $eventId, ?string $perm): array
    {
        if (!self::can($user, $eventId, $perm)) {
            self::deny($user, $eventId, $perm);
        }
        $event = Event::find($eventId);
        if (!$event) {
            abort(404, 'Event tidak ditemukan');
        }
        return $event;
    }

    public static function deny(array $user, ?int $eventId, ?string $perm): never
    {
        AuditLog::add($user, 'access.denied', $eventId ?: null, 'permission', $perm ?? 'event', ['path' => \App\Core\Router::currentPath()], 'denied');
        if (is_api_request()) {
            json_out(['ok' => false, 'state' => 'forbidden', 'message' => t('Akun ini tidak memiliki akses.')], 403);
        }
        http_response_code(403);
        View::render('errors/forbidden', ['title' => t('Akses ditolak'), 'active' => '', 'user' => $user], 'app');
        exit;
    }
}
