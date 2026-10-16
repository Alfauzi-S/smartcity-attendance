<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_PATH . '/helpers.php';

$configFile = ROOT_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo t('Konfigurasi belum dibuat.') . "\n" . t('Salin config/config.example.php menjadi config/config.php lalu isi data database.');
    exit;
}
$GLOBALS['__config'] = require $configFile;

date_default_timezone_set((string) config('timezone', 'Asia/Makassar'));

if (config('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
}

// Alamat dasar aplikasi (mendukung instalasi di subfolder)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
define('BASE_PATH', rtrim($scriptDir, '/'));
$configured = rtrim((string) config('base_url', ''), '/');
if ($configured !== '') {
    define('FULL_URL', $configured);
} else {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('FULL_URL', ($https ? 'https' : 'http') . '://' . $host . BASE_PATH);
}

// Sesi aman
$secure = str_starts_with(FULL_URL, 'https://');
session_name((string) config('session_name', 'sca_session'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => BASE_PATH === '' ? '/' : BASE_PATH . '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Bahasa tampilan: ?lang=id|en disimpan di sesi dan cookie (1 tahun)
$requestedLang = $_GET['lang'] ?? null;
if (is_string($requestedLang) && in_array($requestedLang, SUPPORTED_LANGS, true)) {
    $_SESSION['lang'] = $requestedLang;
    setcookie('sca_lang', $requestedLang, [
        'expires' => time() + 31536000,
        'path' => BASE_PATH === '' ? '/' : BASE_PATH . '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
$cookieLang = $_COOKIE['sca_lang'] ?? '';
$defaultLang = in_array(config('locale'), SUPPORTED_LANGS, true) ? config('locale') : 'id';
define('APP_LANG', $_SESSION['lang'] ?? (in_array($cookieLang, SUPPORTED_LANGS, true) ? $cookieLang : $defaultLang));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');

set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (is_api_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'state' => 'error', 'message' => t('Terjadi kesalahan di server.')]);
        return;
    }
    $message = config('debug') ? $e->getMessage() . "\n" . $e->getTraceAsString() : '';
    \App\Core\View::render('errors/error', [
        'title' => t('Terjadi kesalahan'),
        'code' => 500,
        'heading' => t('Terjadi kesalahan di server'),
        'message' => t('Coba muat ulang halaman. Jika masih terjadi, hubungi pengelola sistem.'),
        'debug' => $message,
    ], 'bare');
});
