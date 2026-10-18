<?php
declare(strict_types=1);

// Jalankan dari terminal server: php bin/migrate.php
// Aman dijalankan berulang kali: hanya migrasi yang belum tercatat yang dijalankan.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__) . '/app/bootstrap.php';

$pending = App\Core\Migrator::pending();
if (!$pending) {
    echo "Database sudah terbaru. Tidak ada migrasi yang perlu dijalankan.\n";
    exit(0);
}
echo "Migrasi tertunda:\n  - " . implode("\n  - ", $pending) . "\n";
if (!in_array('--yes', $argv, true)) {
    echo "Cadangkan database terlebih dahulu, lalu jalankan: php bin/migrate.php --yes\n";
    exit(1);
}
foreach (App\Core\Migrator::run() as $name) {
    echo "OK  {$name}\n";
}
echo "Selesai.\n";
