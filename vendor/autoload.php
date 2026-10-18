<?php
/**
 * Autoload pustaka pihak ketiga yang disertakan (tanpa perlu menjalankan composer):
 * - tecnickcom/tcpdf 6.11.4 (LGPL-3.0-or-later) untuk membuat PDF sertifikat
 * - setasign/fpdi 2.6.9 (MIT) untuk memakai PDF sebagai latar sertifikat
 */
declare(strict_types=1);

if (!defined('K_PATH_FONTS')) {
    define('K_PATH_FONTS', __DIR__ . '/tecnickcom/tcpdf/fonts/');
}
if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
    define('K_TCPDF_EXTERNAL_CONFIG', true);
    define('K_PATH_CACHE', sys_get_temp_dir() . '/');
    define('K_TCPDF_CALLS_IN_HTML', false);
    define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
    define('PDF_PRODUCER', 'SmartCity Attendance');
}

spl_autoload_register(static function (string $class): void {
    if ($class === 'TCPDF') {
        require_once __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';
        return;
    }
    if ($class === 'TCPDF2DBarcode') {
        require_once __DIR__ . '/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
        return;
    }
    $prefix = 'setasign\\Fpdi\\';
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        $file = __DIR__ . '/setasign/fpdi/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
