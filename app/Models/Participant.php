<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Participant
{
    public const PROFESSIONS = ['Mahasiswa S1', 'Mahasiswa S2/S3', 'Dosen / Akademisi', 'Praktisi / Profesional', 'Lainnya'];
    public const FIELDS = ['Informatika', 'Sistem Informasi', 'Teknik Elektro', 'Perencanaan Wilayah dan Kota', 'Lainnya'];
    public const STATUSES = ['hadir', 'belum', 'tidak'];
    private const CODE_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function find(int $id): ?array
    {
        return DB::one(
            'SELECT p.*, u.name AS checked_in_by_name, u.role AS checked_in_by_role
             FROM participants p LEFT JOIN users u ON u.id = p.checked_in_by WHERE p.id = ?',
            [$id]
        );
    }

    public static function findByCode(string $code): ?array
    {
        return DB::one(
            'SELECT p.*, u.name AS checked_in_by_name, u.role AS checked_in_by_role
             FROM participants p LEFT JOIN users u ON u.id = p.checked_in_by WHERE p.code = ?',
            [$code]
        );
    }

    /** @return array{0:string,1:array} potongan WHERE dan parameternya */
    private static function filter(int $eventId, string $q, string $status): array
    {
        $where = 'p.event_id = ?';
        $params = [$eventId];
        if ($status === 'notyet') {
            $where .= " AND p.status <> 'hadir'";
        } elseif (in_array($status, self::STATUSES, true)) {
            $where .= ' AND p.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where .= ' AND (p.full_name LIKE ? OR p.identity_number LIKE ? OR p.code LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, strtoupper($like));
        }
        return [$where, $params];
    }

    public static function search(int $eventId, string $q = '', string $status = 'all', int $limit = 25, int $offset = 0): array
    {
        [$where, $params] = self::filter($eventId, $q, $status);
        return DB::all(
            'SELECT p.*, u.name AS checked_in_by_name FROM participants p
             LEFT JOIN users u ON u.id = p.checked_in_by
             WHERE ' . $where . ' ORDER BY p.full_name ASC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function count(int $eventId, string $q = '', string $status = 'all'): int
    {
        [$where, $params] = self::filter($eventId, $q, $status);
        return (int) DB::value('SELECT COUNT(*) FROM participants p WHERE ' . $where, $params);
    }

    public static function recentCheckins(int $eventId, int $limit = 5): array
    {
        return DB::all(
            "SELECT p.*, u.name AS checked_in_by_name FROM participants p
             LEFT JOIN users u ON u.id = p.checked_in_by
             WHERE p.event_id = ? AND p.status = 'hadir' ORDER BY p.checked_in_at DESC LIMIT " . $limit,
            [$eventId]
        );
    }

    public static function identityTaken(int $eventId, string $identity, int $exceptId = 0): bool
    {
        return (bool) DB::value(
            'SELECT id FROM participants WHERE event_id = ? AND identity_number = ? AND id <> ?',
            [$eventId, $identity, $exceptId]
        );
    }

    public static function create(int $eventId, array $data): array
    {
        $row = [
            'event_id' => $eventId,
            'code' => self::generateCode(),
            'identity_number' => $data['identity_number'],
            'profession' => $data['profession'],
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'institution' => $data['institution'],
            'field_of_study' => $data['field_of_study'],
            'status' => 'belum',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $row['id'] = DB::insert('participants', $row);
        return $row;
    }

    public static function updateData(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::update('participants', $data, 'id = ?', [$id]);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'SC';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::CODE_CHARS[random_int(0, strlen(self::CODE_CHARS) - 1)];
            }
        } while (DB::value('SELECT id FROM participants WHERE code = ?', [$code]));
        return $code;
    }

    /** Ambil kode peserta dari isi QR (bisa berupa URL tiket atau kode saja). */
    public static function extractCode(string $raw): ?string
    {
        $raw = strtoupper(trim($raw));
        if (preg_match('#/T/(SC[A-Z0-9]{8})(?:[/?\#]|$)#', $raw, $m)) {
            return $m[1];
        }
        $compact = preg_replace('/[^A-Z0-9]/', '', $raw) ?? '';
        return preg_match('/^SC[A-Z0-9]{8}$/', $compact) ? $compact : null;
    }

    public static function methodLabel(?string $method): string
    {
        return t(['scan' => 'Scan QR', 'cari' => 'Input manual petugas', 'manual' => 'Diubah manual oleh admin'][$method ?? ''] ?? '—');
    }
}
