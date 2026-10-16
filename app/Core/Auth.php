<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/** Autentikasi berbasis sesi dan pemeriksaan hak akses per peran. */
final class Auth
{
    public const SUPER = 'super';
    public const ADMIN = 'admin';

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        $id = (int) ($_SESSION['uid'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $user = User::find($id);
        if (!$user || $user['status'] !== 'Aktif') {
            unset($_SESSION['uid']);
            return null;
        }
        return self::$user = $user;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $user['id'];
        unset($_SESSION['ev']);
        self::$user = $user;
        self::$loaded = true;
        User::touchLogin((int) $user['id']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
    }

    public static function isSuper(): bool
    {
        return (self::user()['role'] ?? '') === self::SUPER;
    }

    /** Hentikan request bila pengguna belum login atau perannya tidak sesuai. */
    public static function require(string ...$roles): array
    {
        $user = self::user();
        if (!$user) {
            if (is_api_request()) {
                json_out(['ok' => false, 'state' => 'auth', 'message' => 'Sesi berakhir. Silakan login kembali.'], 401);
            }
            $_SESSION['next'] = Router::currentPath();
            flash('info', 'Silakan login terlebih dahulu');
            redirect('masuk');
        }
        if ($roles && !in_array($user['role'], $roles, true)) {
            if (is_api_request()) {
                json_out(['ok' => false, 'state' => 'forbidden', 'message' => 'Akun ini tidak memiliki akses.'], 403);
            }
            http_response_code(403);
            View::render('errors/forbidden', ['title' => 'Akses ditolak', 'active' => '', 'user' => $user], 'app');
            exit;
        }
        return $user;
    }
}
