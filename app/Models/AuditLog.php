<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/** Audit log tindakan penting. Tidak pernah menyimpan password, token, atau rahasia. */
final class AuditLog
{
    public const LABELS = [
        'access.denied' => 'Akses ditolak',
        'account.create' => 'Akun dibuat',
        'account.update' => 'Akun diubah',
        'account.status' => 'Status akun diubah',
        'account.reset_password' => 'Password sementara dibuat ulang',
        'assignment.add' => 'Penugasan event ditambahkan',
        'assignment.update' => 'Izin penugasan diubah',
        'assignment.remove' => 'Penugasan event dicabut',
        'event.create' => 'Event dibuat',
        'event.update' => 'Informasi event diubah',
        'event.setting' => 'Pengaturan event diubah',
        'event.delete' => 'Event dihapus',
        'participant.update' => 'Data peserta diubah',
        'participant.status' => 'Status kehadiran diubah manual',
        'report.export' => 'Laporan diekspor',
        'system.migrate' => 'Migrasi database dijalankan',
        'cert.template.create' => 'Template sertifikat dibuat',
        'cert.template.update' => 'Template sertifikat diubah',
        'cert.template.background' => 'Desain latar template diunggah',
        'cert.template.status' => 'Status template diubah',
        'cert.asset.upload' => 'Aset sertifikat diunggah',
        'cert.asset.approve' => 'Aset sertifikat disetujui',
        'cert.asset.archive' => 'Aset sertifikat diarsipkan',
        'cert.variable.create' => 'Variabel ditambahkan',
        'cert.variable.update' => 'Variabel diubah',
        'cert.variable.value' => 'Nilai variabel event diubah',
        'cert.numbering.create' => 'Pola nomor dibuat (draft)',
        'cert.numbering.approve' => 'Pola nomor disetujui',
        'cert.settings' => 'Pengaturan kelayakan diubah',
        'cert.generate' => 'Sertifikat di-generate',
        'cert.generate.failed' => 'Generate sertifikat gagal',
        'cert.job.create' => 'Generate massal dimulai',
        'cert.job.done' => 'Generate massal selesai',
        'cert.publish' => 'Sertifikat dipublikasikan',
        'cert.revoke' => 'Sertifikat dicabut',
        'cert.reissue' => 'Sertifikat diterbitkan ulang',
        'cert.download.zip' => 'ZIP sertifikat diunduh',
        'cert.public.download' => 'Sertifikat diunduh peserta',
        'cert.public.verify_failed' => 'Verifikasi unduhan publik gagal',
    ];

    public static function label(string $action): string
    {
        return t(self::LABELS[$action] ?? $action);
    }

    private const SECRET_KEYS = '/pass|token|secret|hash|csrf|cookie|session/i';

    public static function add(?array $user, string $action, ?int $eventId = null, ?string $objectType = null, mixed $objectId = null, array $meta = [], string $result = 'ok'): void
    {
        if (!DB::tableExists('audit_logs')) {
            return;
        }
        try {
            DB::insert('audit_logs', [
                'user_id' => $user ? (int) $user['id'] : null,
                'username' => $user['username'] ?? null,
                'role' => $user['role'] ?? null,
                'event_id' => $eventId,
                'action' => mb_substr($action, 0, 60),
                'object_type' => $objectType !== null ? mb_substr($objectType, 0, 40) : null,
                'object_id' => $objectId !== null ? mb_substr((string) $objectId, 0, 64) : null,
                'result' => mb_substr($result, 0, 20),
                'meta' => $meta ? json_encode(self::clean($meta), JSON_UNESCAPED_UNICODE) : null,
                'ip' => client_ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            error_log('audit log gagal: ' . $e->getMessage());
        }
    }

    private static function clean(array $meta): array
    {
        $out = [];
        foreach ($meta as $k => $v) {
            if (preg_match(self::SECRET_KEYS, (string) $k)) {
                continue;
            }
            $out[$k] = is_array($v) ? self::clean($v) : (is_string($v) ? mb_substr($v, 0, 500) : $v);
        }
        return $out;
    }

    /** @param int[]|null $eventIds null = semua (Super Admin) */
    private static function where(?array $eventIds, ?int $eventId, string $prefix): array
    {
        $w = ['1 = 1'];
        $p = [];
        if ($eventIds !== null) {
            $w[] = $eventIds ? 'event_id IN (' . implode(',', array_map('intval', $eventIds)) . ')' : '1 = 0';
        }
        if ($eventId) {
            $w[] = 'event_id = ?';
            $p[] = $eventId;
        }
        if ($prefix !== '') {
            $w[] = 'action LIKE ?';
            $p[] = $prefix . '%';
        }
        return [implode(' AND ', $w), $p];
    }

    public static function list(?array $eventIds, ?int $eventId = null, string $prefix = '', int $limit = 50, int $offset = 0): array
    {
        if (!DB::tableExists('audit_logs')) {
            return [];
        }
        [$w, $p] = self::where($eventIds, $eventId, $prefix);
        return DB::all('SELECT a.*, e.name AS event_name FROM audit_logs a LEFT JOIN events e ON e.id = a.event_id WHERE ' .
            str_replace('event_id', 'a.event_id', $w) . ' ORDER BY a.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $p);
    }

    public static function count(?array $eventIds, ?int $eventId = null, string $prefix = ''): int
    {
        if (!DB::tableExists('audit_logs')) {
            return 0;
        }
        [$w, $p] = self::where($eventIds, $eventId, $prefix);
        return (int) DB::value('SELECT COUNT(*) FROM audit_logs WHERE ' . $w, $p);
    }
}
