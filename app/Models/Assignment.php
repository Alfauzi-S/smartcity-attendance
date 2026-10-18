<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;
use App\Services\Access;

/** Penugasan Admin Event ke event (relasi banyak-ke-banyak) beserta izinnya. */
final class Assignment
{
    public static function available(): bool
    {
        return DB::tableExists('event_admin_assignments');
    }

    public static function forUser(int $userId): array
    {
        if (!self::available()) {
            return [];
        }
        $rows = DB::all(
            'SELECT a.*, e.name AS event_name, e.event_date, e.status AS event_status FROM event_admin_assignments a
             JOIN events e ON e.id = a.event_id WHERE a.user_id = ? ORDER BY e.event_date DESC',
            [$userId]
        );
        foreach ($rows as &$r) {
            $r['perms'] = Access::decode($r['permissions']);
        }
        return $rows;
    }

    public static function find(int $userId, int $eventId): ?array
    {
        return DB::one('SELECT * FROM event_admin_assignments WHERE user_id = ? AND event_id = ?', [$userId, $eventId]);
    }

    /** Tambah atau perbarui penugasan. @return array{0:string,1:string[]} [aksi, izin lama] */
    public static function save(int $userId, int $eventId, array $perms, int $by): array
    {
        $perms = array_values(array_intersect(array_keys(Access::PERMS), $perms));
        $json = json_encode($perms);
        $old = self::find($userId, $eventId);
        if ($old) {
            DB::update('event_admin_assignments', ['permissions' => $json, 'updated_at' => now()], 'id = ?', [(int) $old['id']]);
            Access::forget();
            return ['update', Access::decode($old['permissions'])];
        }
        DB::insert('event_admin_assignments', [
            'user_id' => $userId, 'event_id' => $eventId, 'permissions' => $json,
            'assigned_by' => $by, 'created_at' => now(), 'updated_at' => now(),
        ]);
        Access::forget();
        return ['add', []];
    }

    public static function remove(int $userId, int $eventId): bool
    {
        $n = DB::run('DELETE FROM event_admin_assignments WHERE user_id = ? AND event_id = ?', [$userId, $eventId])->rowCount();
        Access::forget();
        return $n > 0;
    }
}
