<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/** Aset gambar resmi: logo, tanda tangan, stempel. Tanda tangan dan stempel wajib disetujui Super Admin. */
final class CertAsset
{
    public const KINDS = ['logo' => 'Logo', 'signature' => 'Tanda tangan', 'stamp' => 'Stempel', 'image' => 'Gambar lain'];
    public const NEEDS_APPROVAL = ['signature', 'stamp'];

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM cert_assets WHERE id = ?', [$id]);
    }

    /** Aset yang boleh dipakai template event ini: aset global + aset event ini. */
    public static function usable(int $eventId, bool $withArchived = false): array
    {
        return DB::all(
            'SELECT a.*, u.username AS uploaded_by_name FROM cert_assets a LEFT JOIN users u ON u.id = a.uploaded_by
             WHERE (a.event_id IS NULL OR a.event_id = ?)' . ($withArchived ? '' : ' AND a.archived = 0') . ' ORDER BY a.kind, a.id DESC',
            [$eventId]
        );
    }

    public static function create(?int $eventId, string $kind, string $label, array $file, array $user): int
    {
        $auto = !in_array($kind, self::NEEDS_APPROVAL, true) || $user['role'] === 'super';
        return DB::insert('cert_assets', [
            'event_id' => $eventId, 'kind' => $kind, 'label' => $label, 'file_path' => $file['path'], 'mime' => $file['mime'],
            'width_px' => $file['w'], 'height_px' => $file['h'], 'approved' => $auto ? 1 : 0,
            'approved_by' => $auto ? (int) $user['id'] : null, 'approved_at' => $auto ? now() : null,
            'archived' => 0, 'uploaded_by' => (int) $user['id'], 'created_at' => now(),
        ]);
    }

    public static function usableBy(array $asset, int $eventId): bool
    {
        return $asset['event_id'] === null || (int) $asset['event_id'] === $eventId;
    }
}
