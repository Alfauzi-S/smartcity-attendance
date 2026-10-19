<?php
declare(strict_types=1);

use App\Core\View;

/* ---------- bahasa ---------- */

const SUPPORTED_LANGS = ['id', 'en'];

function lang(): string
{
    return defined('APP_LANG') ? APP_LANG : 'id';
}

/**
 * Kamus terjemahan. Teks sumber (kunci) ditulis dalam bahasa Indonesia,
 * terjemahan ada di app/Lang/{bahasa}/*.php. Bahasa Indonesia tidak butuh kamus.
 */
function translations(): array
{
    static $cache = [];
    $l = lang();
    if (!isset($cache[$l])) {
        $cache[$l] = [];
        if ($l !== 'id') {
            foreach (glob(APP_PATH . '/Lang/' . $l . '/*.php') ?: [] as $file) {
                $cache[$l] += (array) require $file;
            }
        }
    }
    return $cache[$l];
}

/** Terjemahkan teks. Parameter ditulis :nama, mis. t('Halo, :name', ['name' => 'Budi']) */
function t(string $text, array $params = []): string
{
    $out = translations()[$text] ?? $text;
    foreach ($params as $k => $v) {
        $out = str_replace(':' . $k, (string) $v, $out);
    }
    return $out;
}

/** Kamus kecil untuk JavaScript (app/Lang/{bahasa}/js.php). */
function js_translations(): array
{
    $file = APP_PATH . '/Lang/' . lang() . '/js.php';
    return lang() !== 'id' && is_file($file) ? (array) require $file : [];
}

/** URL halaman saat ini dengan bahasa lain. */
function lang_url(string $to): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $q = $_GET;
    $q['lang'] = $to;
    return $path . '?' . http_build_query($q);
}

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL relatif aplikasi, mis. url('admin/peserta', ['q' => 'nadia']) */
function url(string $path = '', array $query = []): string
{
    $path = ltrim($path, '/');
    $prefix = BASE_PATH . (config('pretty_urls', true) ? '' : '/index.php');
    $u = $prefix . '/' . $path;
    $query = array_filter($query, static fn ($v) => $v !== null && $v !== '');
    if ($query) {
        $u .= '?' . http_build_query($query);
    }
    return $u;
}

/** URL lengkap (untuk QR Code dan tautan yang dibagikan) */
function full_url(string $path = ''): string
{
    $prefix = config('pretty_urls', true) ? '' : '/index.php';
    return FULL_URL . $prefix . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = PUBLIC_PATH . '/assets/' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return BASE_PATH . '/assets/' . $path . '?v=' . $version;
}

function redirect(string $path, array $query = []): never
{
    header('Location: ' . url($path, $query), true, 303);
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function query(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function is_api_request(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    return str_contains($path, '/api/');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        if (is_api_request()) {
            json_out(['ok' => false, 'state' => 'expired', 'message' => t('Sesi kedaluwarsa. Muat ulang halaman.')], 419);
        }
        abort(419, t('Sesi formulir kedaluwarsa'), t('Muat ulang halaman lalu kirim formulir sekali lagi.'));
    }
}

/** Judul dan teks otomatis diterjemahkan; teks dinamis sebaiknya sudah lewat t() dengan parameter. */
function flash(string $type, string $title, string $text = ''): void
{
    $title = t($title);
    $text = $text === '' ? '' : t($text);
    $_SESSION['_flash'][] = ['type' => $type, 'title' => $title, 'text' => $text];
}

function take_flash(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $items;
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function abort(int $code, string $heading, string $message = ''): never
{
    http_response_code($code);
    View::render('errors/error', [
        'title' => t($heading),
        'code' => $code,
        'heading' => t($heading),
        'message' => $message === '' ? '' : t($message),
    ], 'bare');
    exit;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/* ---------- format tampilan ---------- */

const STATUS_LABELS = [
    'hadir' => ['Hadir', 'ok'],
    'belum' => ['Belum diverifikasi', 'warn'],
    'tidak' => ['Tidak Hadir', 'bad'],
];

function status_label(string $status): string
{
    return t(STATUS_LABELS[$status][0] ?? $status);
}

function status_badge(string $status, bool $large = false): string
{
    [$label, $tone] = STATUS_LABELS[$status] ?? [$status, 'mute'];
    return '<span class="bdg bdg-' . $tone . ($large ? ' lg' : '') . '"><i></i>' . e(t($label)) . '</span>';
}

function event_badge(string $status): string
{
    $tone = ['Draft' => 'mute', 'Aktif' => 'teal', 'Selesai' => 'navy', 'Ditutup' => 'bad'][$status] ?? 'mute';
    return '<span class="bdg bdg-' . $tone . '">' . e(t($status)) . '</span>';
}

/** Jam acara, misalnya "08:00–15:00" atau "08:00–selesai" bila jam selesai dikosongkan. */
function event_time(array $e): string
{
    return $e['start_time'] . '–' . ($e['end_time'] !== '' ? $e['end_time'] : t('selesai'));
}

/** Periode registrasi untuk ditampilkan, lengkap dengan jam tutup bila ada. */
function reg_period(array $e): string
{
    $end = date_id($e['reg_end'] ?? null);
    if (!empty($e['reg_end']) && !empty($e['reg_end_time'])) {
        $end .= ' ' . t('pukul :time', ['time' => $e['reg_end_time']]);
    }
    return date_id($e['reg_start'] ?? null) . ' – ' . $end;
}

function reg_badge(string $status): string
{
    $label = $status === 'Dibuka' ? 'Registrasi dibuka' : 'Registrasi ditutup';
    return '<span class="bdg ' . ($status === 'Dibuka' ? 'bdg-info' : 'bdg-mute') . '">' . e(t($label)) . '</span>';
}

const ATT_LABELS = [
    'notyet' => ['Absensi belum dibuka', 'mute'],
    'open' => ['Absensi dibuka', 'ok'],
    'closed' => ['Absensi ditutup', 'bad'],
];

function att_badge(string $status): string
{
    [$label, $tone] = ATT_LABELS[$status] ?? [$status, 'mute'];
    return '<span class="bdg bdg-' . $tone . '"><i></i>' . e(t($label)) . '</span>';
}

const MONTHS_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const DAYS_ID = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const MONTHS_EN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const DAYS_EN = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function date_id(?string $date, bool $withDay = false): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '—';
    }
    $en = lang() === 'en';
    $out = (int) date('j', $ts) . ' ' . ($en ? MONTHS_EN : MONTHS_ID)[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    return $withDay ? ($en ? DAYS_EN : DAYS_ID)[(int) date('w', $ts)] . ', ' . $out : $out;
}

function datetime_id(?string $dt): string
{
    if (!$dt) {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts === false ? '—' : date_id($dt) . ', ' . date('H:i', $ts) . ' ' . config('timezone_label', 'WITA');
}

function time_only(?string $dt): string
{
    if (!$dt) {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts === false ? '—' : date('H:i', $ts);
}

function pct(int $part, int $total): string
{
    if ($total <= 0) {
        return '0%';
    }
    $value = number_format($part / $total * 100, 1);
    return (lang() === 'en' ? $value : str_replace('.', ',', $value)) . '%';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out ?: '?';
}

/** Untuk menampilkan menu/tombol saja. Keamanan tetap diperiksa di controller. */
function can(string $perm, ?int $eventId = null): bool
{
    $user = \App\Core\Auth::user();
    if (!$user) {
        return false;
    }
    if ($perm === 'cert_any' && $eventId === null) {
        return \App\Services\Access::any($user, \App\Services\Access::CERT_ANY);
    }
    return $eventId === null ? \App\Services\Access::any($user, $perm) : \App\Services\Access::can($user, $eventId, $perm);
}

function is_super(): bool
{
    return \App\Core\Auth::isSuper();
}

function icon(string $name, int $size = 20): string
{
    return View::icon($name, $size);
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' checked' : '';
}

function logo_mark(int $size = 34): string
{
    $s = (int) round($size * 0.6);
    return '<span class="logo" style="width:' . $size . 'px;height:' . $size . 'px"><svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke-linecap="round" aria-hidden="true"><path d="M4 20V11l4-3v12M10 20V6l5-3v17M17 20v-9l3 2v7" stroke="#35D5F2" stroke-width="1.8" stroke-linejoin="round"/><circle cx="15" cy="3" r="1.6" fill="#23C7A5"/></svg></span>';
}

/** Tombol POST kecil dengan konfirmasi opsional (CSRF otomatis). */
function post_button(string $path, string $label, array $fields = [], string $class = 'btn btn-outline sm', string $confirm = '', string $icon = ''): string
{
    $html = '<form method="post" action="' . e(url($path)) . '" class="inline"' . ($confirm !== '' ? ' data-confirm="' . e(t($confirm)) . '"' : '') . '>' . csrf_field();
    foreach ($fields as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html . '<button type="submit" class="' . e($class) . '">' . ($icon !== '' ? icon($icon, 18) : '') . e(t($label)) . '</button></form>';
}

function field_error(array $errors, string $key): string
{
    if (empty($errors[$key])) {
        return '';
    }
    return '<p class="msg" id="err-' . e($key) . '">' . icon('alert', 16) . '<span>' . e(t($errors[$key])) . '</span></p>';
}

/** Nomor sertifikat yang sudah di-escape, dengan titik patah baris setelah "/" agar rapi di layar sempit. */
function cert_no(string $number): string
{
    return str_replace('/', '/<wbr>', e($number));
}
