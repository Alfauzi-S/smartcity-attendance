<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Models\User;

final class AuthController extends Controller
{
    public static function homeFor(array $user): string
    {
        return in_array($user['role'], [Auth::SUPER, Auth::EVENT], true) ? 'admin' : 'petugas';
    }

    public function form(): void
    {
        if ($user = Auth::user()) {
            redirect(self::homeFor($user));
        }
        $this->render('auth/login', ['title' => t('Masuk'), 'username' => '', 'error' => ''], 'bare');
    }

    public function login(): void
    {
        $username = strtolower(input('username'));
        $password = (string) ($_POST['password'] ?? '');
        $key = 'login:' . client_ip() . ':' . $username;
        $error = '';

        if ($username === '' || $password === '') {
            $error = t('Username dan password wajib diisi.');
        } elseif (RateLimiter::tooMany($key, 5, 900)) {
            $error = t('Terlalu banyak percobaan gagal. Tunggu 15 menit lalu coba lagi.');
        } else {
            $user = User::findByUsername($username);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                RateLimiter::hit($key);
                $error = t('Username atau password salah.');
            } elseif ($user['status'] !== 'Aktif') {
                $error = t('Akun ini dinonaktifkan oleh Super Admin. Hubungi Super Admin untuk mengaktifkan kembali.');
            } else {
                RateLimiter::clear($key);
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    User::setPassword((int) $user['id'], $password, (bool) $user['must_change_password']);
                }
                $next = $_SESSION['next'] ?? '';
                Auth::login($user);
                if ((int) $user['must_change_password'] === 1) {
                    flash('info', 'Ganti password sementara Anda', 'Akun ini memakai password dari Super Admin. Buat password baru sekarang.');
                    redirect(self::homeFor($user) === 'admin' ? 'admin/pengaturan' : 'petugas/profil');
                }
                $home = self::homeFor($user);
                $target = is_string($next) && str_starts_with(ltrim($next, '/'), $home) ? $next : $home;
                redirect($target);
            }
        }
        http_response_code(422);
        $this->render('auth/login', ['title' => t('Masuk'), 'username' => $username, 'error' => $error], 'bare');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('info', 'Anda telah keluar');
        redirect('masuk');
    }
}
