<?php
declare(strict_types=1);

// Tambah data demo Admin Event dan sertifikat ke instalasi yang sudah ada (tanpa menghapus data apa pun).
// Jalankan dari terminal server: php bin/seed-demo.php --url=https://domain-anda --yes
// --url dipakai untuk alamat QR verifikasi di PDF demo (tidak perlu bila base_url sudah diisi di config).
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$url = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--url=')) {
        $url = rtrim(substr($arg, 6), '/');
    }
}
$parts = $url !== '' ? parse_url($url) : false;
if ($url !== '' && (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host']))) {
    echo "Alamat --url tidak valid. Contoh: --url=https://smartcity-unmul.web.id\n";
    exit(1);
}
$_SERVER['HTTP_HOST'] = $parts ? $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') : 'localhost';
$_SERVER['HTTPS'] = $parts && $parts['scheme'] === 'https' ? 'on' : 'off';
$_SERVER['SCRIPT_NAME'] = ($parts['path'] ?? '') . '/index.php';
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database as DB;
use App\Core\Migrator;
use App\Services\CertDemoSeeder;
use App\Services\DemoSeeder;

if (!Migrator::upToDate()) {
    echo "Migrasi database belum dijalankan. Jalankan dulu: php bin/migrate.php --yes\n";
    exit(1);
}
$super = DB::one("SELECT * FROM users WHERE role = 'super' ORDER BY id LIMIT 1");
if (!$super) {
    echo "Belum ada Super Admin. Selesaikan instalasi lewat /install terlebih dahulu.\n";
    exit(1);
}
if (DB::value("SELECT id FROM events WHERE name = 'Seminar Nasional Kota Cerdas 2026'")) {
    echo "Data demo sertifikat sudah ada. Tidak ada yang diubah.\n";
    exit(0);
}
echo "Alamat QR verifikasi: " . FULL_URL . "/v/...\n";
if (config('base_url', '') === '' && $url === '') {
    echo "Peringatan: tanpa --url atau base_url, QR di PDF demo mengarah ke localhost.\n";
}
if (!in_array('--yes', $argv, true)) {
    echo "Skrip ini menambah event demo, peserta demo, akun event.demo, dan sertifikat contoh. Data lama tidak diubah.\n";
    echo "Lanjutkan dengan: php bin/seed-demo.php --url=https://domain-anda --yes\n";
    exit(1);
}
$accounts = [];
$mainId = (int) DB::value('SELECT MIN(id) FROM events');
if ($mainId === 0) {
    $accounts = DemoSeeder::run($super);
    $mainId = (int) DB::value('SELECT MIN(id) FROM events');
}
$r = CertDemoSeeder::run($super, $mainId);
foreach ($accounts as $a) {
    echo "Admin Absensi : {$a['username']} / {$a['password']}\n";
}
if ($r['account']) {
    echo "Admin Event   : {$r['account']['username']} / {$r['account']['password']}\n";
}
echo "Event demo    : {$r['event']}\n";
if ($r['sample']) {
    echo "Coba /sertifikat dengan nama \"{$r['sample']['name']}\" dan 4 digit terakhir NIM {$r['sample']['check']}.\n";
}
echo "Selesai.\n";
