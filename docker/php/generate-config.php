<?php
declare(strict_types=1);

/**
 * Membuat /var/www/html/config/config.php dari environment variable.
 * Dipanggil otomatis oleh entrypoint container "app" (docker-compose.yml).
 * Jangan mengubah config/config.php secara manual saat memakai Docker:
 * ubah nilai di file .env lalu jalankan ulang container.
 */

$appDir = getenv('APP_DIR') ?: '/var/www/html';
$configFile = $appDir . '/config/config.php';

$env = static fn (string $key, string $default = ''): string => (string) (getenv($key) !== false ? getenv($key) : $default);
$envBool = static fn (string $key, bool $default): bool => filter_var($env($key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
$envInt = static fn (string $key, int $default): int => (int) $env($key, (string) $default);

$config = [
    'app_name' => $env('APP_NAME', 'SmartCity Attendance'),

    // Kosongkan agar alamat terdeteksi otomatis (Host + X-Forwarded-Proto
    // diteruskan oleh Nginx proxy di depan aplikasi).
    'base_url' => $env('APP_BASE_URL'),

    'pretty_urls' => $envBool('APP_PRETTY_URLS', true),
    'locale' => $env('APP_LOCALE', 'id'),
    'timezone' => $env('APP_TIMEZONE', 'Asia/Makassar'),
    'timezone_label' => $env('APP_TIMEZONE_LABEL', 'WITA'),
    'debug' => $envBool('APP_DEBUG', false),

    'db' => [
        'driver' => $env('DB_DRIVER', 'mysql'),
        'host' => $env('DB_HOST', 'db'),
        'port' => (int) $env('DB_PORT', '3306'),
        'database' => $env('DB_NAME', 'smartcity_attendance'),
        'username' => $env('DB_USER', 'smartcity'),
        'password' => $env('DB_PASSWORD'),
        'charset' => 'utf8mb4',
        'sqlite_path' => $appDir . '/storage/database.sqlite',
    ],

    'session_name' => $env('APP_SESSION_NAME', 'sca_session'),
    'contact_phone' => $env('APP_CONTACT_PHONE'),

    // Sertifikat otomatis (lihat config/config.example.php untuk keterangan).
    'cert' => [
        'max_upload_mb' => $envInt('CERT_MAX_UPLOAD_MB', 8),
        'batch_size' => $envInt('CERT_BATCH_SIZE', 10),
        'search_per_10min' => $envInt('CERT_SEARCH_PER_10MIN', 30),
        'download_fail_per_15min' => $envInt('CERT_DOWNLOAD_FAIL_PER_15MIN', 8),
    ],
];

$out = "<?php\n"
    . "/**\n"
    . " * Dibuat otomatis oleh entrypoint Docker (docker/php/generate-config.php).\n"
    . " * Ubah konfigurasi lewat file .env, bukan lewat file ini.\n"
    . " */\n"
    . 'return ' . var_export($config, true) . ";\n";

if (file_put_contents($configFile, $out) === false) {
    fwrite(STDERR, "[entrypoint] gagal menulis {$configFile}\n");
    exit(1);
}

echo "[entrypoint] config/config.php dibuat dari environment\n";
