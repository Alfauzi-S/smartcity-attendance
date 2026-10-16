<?php
declare(strict_types=1);

namespace App\Core;

/** Pembatas percobaan (login, registrasi) berbasis tabel rate_limits. */
final class RateLimiter
{
    public static function tooMany(string $key, int $max, int $windowSeconds): bool
    {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $count = (int) Database::value('SELECT COUNT(*) FROM rate_limits WHERE rl_key = ? AND created_at >= ?', [$key, $since]);
        return $count >= $max;
    }

    public static function hit(string $key): void
    {
        Database::insert('rate_limits', ['rl_key' => $key, 'created_at' => now()]);
        if (random_int(1, 50) === 1) {
            Database::run('DELETE FROM rate_limits WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
        }
    }

    public static function clear(string $key): void
    {
        Database::run('DELETE FROM rate_limits WHERE rl_key = ?', [$key]);
    }
}
