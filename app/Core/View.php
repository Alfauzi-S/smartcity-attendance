<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    private const ICONS = [
        'home' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z',
        'grid' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        'scan' => 'M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M4 12h16',
        'users' => 'M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 20v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
        'calendar' => 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
        'chart' => 'M4 20V11M10 20V5M16 20v-6M21 20H3',
        'history' => 'M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5M12 7v5l3 2',
        'shield' => 'M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z',
        'sliders' => 'M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1M15 4v4M9 10v4M17 16v4',
        'user' => 'M20 21v-1a5 5 0 0 0-5-5H9a5 5 0 0 0-5 5v1M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
        'logout' => 'M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M10 16l-4-4 4-4M6 12h10',
        'search' => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4',
        'check' => 'M5 12.5l4.5 4.5L19 7',
        'x' => 'M6 6l12 12M18 6 6 18',
        'alert' => 'M12 3 2 20h20zM12 10v4M12 17v.5',
        'info' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 11v5M12 8v.5',
        'download' => 'M12 4v11M7 10l5 5 5-5M5 20h14',
        'plus' => 'M12 5v14M5 12h14',
        'edit' => 'M4 20h4L19 9l-4-4L4 16zM14 6l4 4',
        'trash' => 'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
        'camoff' => 'M3 3l18 18M9 5h6l2 3h3a1 1 0 0 1 1 1v9M17 20H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1h3M14.5 14.5a3.5 3.5 0 0 1-5-5',
        'wifioff' => 'M3 3l18 18M8.5 16.5a5 5 0 0 1 7 0M5 13a10 10 0 0 1 5-2.7M19 13a10 10 0 0 0-2.4-1.8M2 9a15 15 0 0 1 4.3-2.6M22 9a15 15 0 0 0-9.5-3.9M12 20v.5',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
        'lock' => 'M6 11h12v10H6zM8 11V7a4 4 0 0 1 8 0v4',
        'link' => 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1',
        'copy' => 'M9 9h11v11H9zM5 15H4V4h11v1',
        'qr' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2zM14 18h2M18 14h2',
        'pin' => 'M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12zM12 11.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'back' => 'M15 18l-6-6 6-6',
        'chev' => 'M9 6l6 6-6 6',
        'refresh' => 'M20 12a8 8 0 1 1-2.3-5.7L20 8M20 3v5h-5',
        'sun' => 'M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.4 1.4M17.6 17.6 19 19M5 19l1.4-1.4M17.6 6.4 19 5',
        'building' => 'M4 21V5l8-2v18M12 9h8v12M7 8h2M7 12h2M7 16h2M15 13h2M15 17h2M2 21h20',
        'file' => 'M6 3h8l4 4v14H6zM14 3v4h4M9 13h6M9 17h6',
        'eye' => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
        'userplus' => 'M15 21v-1a5 5 0 0 0-5-5H7a5 5 0 0 0-5 5v1M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M16 11h6',
        'key' => 'M14.5 9.5a4.5 4.5 0 1 0-1.3 3.2L21 20.5M17.5 17l2-2M19.5 19l1.5-1.5',
        'power' => 'M12 3v9M6.3 6.3a8 8 0 1 0 11.4 0',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
        'globe' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18',
    ];

    /** @var string[] Skrip tambahan yang diminta template (dimuat di layout). */
    private static array $scripts = [];

    public static function script(string $path): void
    {
        if (!in_array($path, self::$scripts, true)) {
            self::$scripts[] = $path;
        }
    }

    public static function scripts(): array
    {
        return self::$scripts;
    }

    public static function icon(string $name, int $size = 20): string
    {
        $d = self::ICONS[$name] ?? '';
        return '<svg class="ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $d . '"/></svg>';
    }

    /** Render template di app/Views/{template}.php di dalam layout. */
    public static function render(string $template, array $data = [], string $layout = 'app'): void
    {
        $content = self::fetch($template, $data);
        echo self::fetch('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function fetch(string $template, array $data = []): string
    {
        $file = APP_PATH . '/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Template tidak ditemukan: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    public static function partial(string $name, array $data = []): string
    {
        return self::fetch('partials/' . $name, $data);
    }
}
