<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Models\User;
use App\Services\DemoSeeder;
use Throwable;

/**
 * Instalasi pertama lewat browser: membuat tabel dan akun Super Admin.
 * Otomatis terkunci (404) setelah Super Admin pertama dibuat.
 */
final class InstallController extends Controller
{
    private function guard(): void
    {
        try {
            if (DB::tableExists('users') && User::hasSuper()) {
                abort(404, 'Halaman tidak ditemukan');
            }
        } catch (Throwable $e) {
            // Koneksi gagal ditampilkan di form
        }
    }

    private function checks(): array
    {
        $dbOk = true;
        $dbError = '';
        try {
            DB::pdo();
        } catch (Throwable $e) {
            $dbOk = false;
            $dbError = $e->getMessage();
        }
        return [
            ['PHP 8.1 atau lebih baru', version_compare(PHP_VERSION, '8.1.0', '>='), t('Versi saat ini :version', ['version' => PHP_VERSION])],
            [t('Ekstensi PDO :driver', ['driver' => DB::driver()]), extension_loaded('pdo_' . DB::driver()), ''],
            ['Ekstensi mbstring', extension_loaded('mbstring'), ''],
            ['Ekstensi zip (untuk ekspor Excel)', class_exists(\ZipArchive::class), 'Opsional; tanpa ini ekspor tetap bisa CSV'],
            ['Folder storage dapat ditulis', is_writable(STORAGE_PATH), STORAGE_PATH],
            ['Koneksi database', $dbOk, $dbOk ? (string) config('db.driver') : $dbError],
        ];
    }

    public function form(): void
    {
        $this->guard();
        $this->render('install/form', ['title' => t('Instalasi'), 'checks' => $this->checks(), 'errors' => [], 'old' => ['name' => '', 'username' => 'superadmin', 'demo' => '']], 'bare');
    }

    public function install(): void
    {
        $this->guard();
        $old = ['name' => input('name'), 'username' => strtolower(input('username')), 'demo' => input('demo')];
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];
        if (mb_strlen($old['name']) < 3) {
            $errors['name'] = t('Nama minimal 3 karakter.');
        }
        if (!preg_match('/^[a-z0-9.]{4,40}$/', $old['username'])) {
            $errors['username'] = t('Gunakan 4–40 karakter: huruf kecil, angka, atau titik.');
        }
        if (strlen($password) < 10 || !preg_match('/\d/', $password) || !preg_match('/[a-z]/i', $password)) {
            $errors['password'] = t('Minimal 10 karakter dengan kombinasi huruf dan angka.');
        } elseif ($password !== (string) ($_POST['password_confirm'] ?? '')) {
            $errors['password'] = t('Konfirmasi password tidak sama.');
        }
        if (!$errors) {
            try {
                DB::runFile(ROOT_PATH . '/database/schema.' . (DB::driver() === 'sqlite' ? 'sqlite' : 'mysql') . '.sql');
                if (User::hasSuper()) {
                    abort(404, 'Halaman tidak ditemukan');
                }
                $id = User::create(['name' => $old['name'], 'username' => $old['username'], 'password' => $password, 'role' => Auth::SUPER, 'event_id' => null]);
                $super = User::find($id);
                $demo = $old['demo'] === '1' ? DemoSeeder::run($super) : [];
                Auth::login($super);
                $this->render('install/done', ['title' => t('Instalasi selesai'), 'demo' => $demo, 'username' => $old['username']], 'bare');
                return;
            } catch (Throwable $e) {
                error_log((string) $e);
                $errors['_form'] = t('Instalasi gagal: :error', ['error' => $e->getMessage()]);
            }
        }
        http_response_code(422);
        $this->render('install/form', ['title' => t('Instalasi'), 'checks' => $this->checks(), 'errors' => $errors, 'old' => $old], 'bare');
    }
}
