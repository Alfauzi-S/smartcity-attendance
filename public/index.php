<?php
declare(strict_types=1);

// Server bawaan PHP (php -S): biarkan file statis dilayani langsung.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AccountController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EventController;
use App\Controllers\InstallController;
use App\Controllers\ParticipantController;
use App\Controllers\PublicController;
use App\Controllers\ReportController;
use App\Controllers\SettingsController;
use App\Controllers\StaffController;
use App\Core\Router;

$r = new Router();

// Instalasi pertama (otomatis nonaktif setelah Super Admin dibuat)
$r->get('/install', [InstallController::class, 'form']);
$r->post('/install', [InstallController::class, 'install']);

// Peserta (tanpa login)
$r->get('/', [PublicController::class, 'home']);
$r->get('/e/{slug}', [PublicController::class, 'event']);
$r->get('/e/{slug}/daftar', [PublicController::class, 'registerForm']);
$r->post('/e/{slug}/daftar', [PublicController::class, 'register']);
$r->get('/t/{code}', [PublicController::class, 'ticket']);

// Login
$r->get('/masuk', [AuthController::class, 'form']);
$r->post('/masuk', [AuthController::class, 'login']);
$r->post('/keluar', [AuthController::class, 'logout']);

// Admin Absensi
$r->get('/petugas', [StaffController::class, 'dashboard']);
$r->get('/petugas/scan', [StaffController::class, 'scan']);
$r->get('/petugas/peserta', [StaffController::class, 'participants']);
$r->get('/petugas/peserta/{id}', [StaffController::class, 'participant']);
$r->get('/petugas/profil', [StaffController::class, 'profile']);
$r->post('/petugas/profil/password', [StaffController::class, 'password']);
$r->post('/api/scan/validate', [ApiController::class, 'validate']);
$r->post('/api/scan/save', [ApiController::class, 'save']);
$r->post('/api/scan/search', [ApiController::class, 'search']);

// Super Admin
$r->get('/admin', [DashboardController::class, 'index']);
$r->get('/admin/event', [EventController::class, 'index']);
$r->get('/admin/event/baru', [EventController::class, 'create']);
$r->post('/admin/event/baru', [EventController::class, 'store']);
$r->get('/admin/event/{id}', [EventController::class, 'show']);
$r->get('/admin/event/{id}/ubah', [EventController::class, 'edit']);
$r->post('/admin/event/{id}/ubah', [EventController::class, 'update']);
$r->post('/admin/event/{id}/pengaturan', [EventController::class, 'setting']);
$r->post('/admin/event/{id}/hapus', [EventController::class, 'destroy']);
$r->get('/admin/akun', [AccountController::class, 'index']);
$r->get('/admin/akun/baru', [AccountController::class, 'create']);
$r->post('/admin/akun/baru', [AccountController::class, 'store']);
$r->get('/admin/akun/{id}', [AccountController::class, 'show']);
$r->post('/admin/akun/{id}/ubah', [AccountController::class, 'update']);
$r->post('/admin/akun/{id}/status', [AccountController::class, 'toggle']);
$r->post('/admin/akun/{id}/reset', [AccountController::class, 'reset']);
$r->get('/admin/peserta', [ParticipantController::class, 'index']);
$r->get('/admin/peserta/{id}', [ParticipantController::class, 'show']);
$r->post('/admin/peserta/{id}/ubah', [ParticipantController::class, 'update']);
$r->post('/admin/peserta/{id}/status', [ParticipantController::class, 'status']);
$r->get('/admin/riwayat', [ReportController::class, 'history']);
$r->get('/admin/laporan', [ReportController::class, 'index']);
$r->get('/admin/ekspor', [ReportController::class, 'export']);
$r->get('/admin/pengaturan', [SettingsController::class, 'index']);
$r->post('/admin/pengaturan/profil', [SettingsController::class, 'profile']);
$r->post('/admin/pengaturan/password', [SettingsController::class, 'password']);

$r->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', Router::currentPath());
