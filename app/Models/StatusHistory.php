<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/** Riwayat perubahan status presensi manual oleh Super Admin. */
final class StatusHistory
{
    public static function add(int $participantId, string $old, string $new, string $reason, array $by): void
    {
        DB::insert('status_history', [
            'participant_id' => $participantId,
            'old_status' => $old,
            'new_status' => $new,
            'reason' => $reason !== '' ? mb_substr($reason, 0, 255) : null,
            'changed_by' => (int) $by['id'],
            'changed_by_username' => $by['username'],
            'changed_at' => now(),
        ]);
    }

    public static function forParticipant(int $participantId): array
    {
        return DB::all(
            'SELECT h.*, p.full_name FROM status_history h JOIN participants p ON p.id = h.participant_id
             WHERE h.participant_id = ? ORDER BY h.changed_at DESC, h.id DESC',
            [$participantId]
        );
    }

    public static function list(int $eventId, string $to = 'all', int $limit = 50, int $offset = 0): array
    {
        $where = 'p.event_id = ?';
        $params = [$eventId];
        if (in_array($to, ['hadir', 'tidak'], true)) {
            $where .= ' AND h.new_status = ?';
            $params[] = $to;
        }
        return DB::all(
            'SELECT h.*, p.full_name, p.identity_number FROM status_history h
             JOIN participants p ON p.id = h.participant_id WHERE ' . $where .
            ' ORDER BY h.changed_at DESC, h.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function count(int $eventId, string $to = 'all'): int
    {
        $sql = 'SELECT COUNT(*) FROM status_history h JOIN participants p ON p.id = h.participant_id WHERE p.event_id = ?';
        $params = [$eventId];
        if (in_array($to, ['hadir', 'tidak'], true)) {
            $sql .= ' AND h.new_status = ?';
            $params[] = $to;
        }
        return (int) DB::value($sql, $params);
    }
}
