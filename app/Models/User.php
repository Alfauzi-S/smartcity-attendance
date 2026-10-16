<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class User
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return DB::one('SELECT * FROM users WHERE username = ?', [strtolower($username)]);
    }

    public static function hasSuper(): bool
    {
        return (bool) DB::value("SELECT id FROM users WHERE role = 'super' LIMIT 1");
    }

    /** Daftar akun Admin Absensi beserta event dan jumlah scan. */
    public static function admins(): array
    {
        return DB::all(
            "SELECT u.*, e.name AS event_name,
                (SELECT COUNT(*) FROM participants p WHERE p.checked_in_by = u.id AND p.method IN ('scan', 'cari')) AS scan_count
             FROM users u LEFT JOIN events e ON e.id = u.event_id
             WHERE u.role = 'admin' ORDER BY u.status ASC, u.name ASC"
        );
    }

    public static function scanCount(int $id): int
    {
        return (int) DB::value("SELECT COUNT(*) FROM participants WHERE checked_in_by = ? AND method IN ('scan', 'cari')", [$id]);
    }

    public static function create(array $data): int
    {
        return DB::insert('users', [
            'name' => $data['name'],
            'username' => strtolower($data['username']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'],
            'event_id' => $data['event_id'] ?: null,
            'status' => $data['status'] ?? 'Aktif',
            'must_change_password' => (int) ($data['must_change_password'] ?? 0),
            'created_at' => now(),
        ]);
    }

    public static function update(int $id, array $data): void
    {
        DB::update('users', $data, 'id = ?', [$id]);
    }

    public static function setPassword(int $id, string $password, bool $mustChange = false): void
    {
        DB::update('users', [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'must_change_password' => $mustChange ? 1 : 0,
        ], 'id = ?', [$id]);
    }

    public static function touchLogin(int $id): void
    {
        DB::update('users', ['last_login_at' => now()], 'id = ?', [$id]);
    }

    public static function randomPassword(int $length = 10): string
    {
        $chars = 'abcdefghjkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }
}
