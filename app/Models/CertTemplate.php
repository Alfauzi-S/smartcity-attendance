<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/**
 * Template sertifikat per event.
 * Status: Draft (boleh diedit) → Active (dipakai generate, tidak bisa diedit) → Archived.
 * Setiap aktivasi menyimpan snapshot versi (cert_template_versions) yang dirujuk sertifikat terbit,
 * sehingga perubahan template berikutnya tidak memengaruhi sertifikat lama.
 * Koordinat elemen disimpan dalam persen terhadap lebar/tinggi halaman.
 */
final class CertTemplate
{
    public const STATUSES = ['Draft' => 'mute', 'Active' => 'ok', 'Archived' => 'navy'];
    public const FORMATS = ['A4' => [210, 297], 'A5' => [148, 210], 'F4' => [215, 330], 'Letter' => [215.9, 279.4], 'Legal' => [215.9, 355.6], 'custom' => null];
    public const FONTS = [
        'dejavusans' => 'Sans (DejaVu Sans)', 'dejavuserif' => 'Serif (DejaVu Serif)', 'dejavusanscondensed' => 'Sans Condensed',
        'sora300' => 'Sora Light', 'sora' => 'Sora', 'sora600' => 'Sora SemiBold', 'sora800' => 'Sora ExtraBold',
        'montserrat' => 'Montserrat', 'cormorant' => 'Cormorant Garamond (serif klasik)',
        'greatvibes' => 'Great Vibes (tulisan tangan)', 'pinyonscript' => 'Pinyon Script (tulisan tangan)',
    ];
    /**
     * Detail font: [keluarga CSS editor, ketebalan CSS, punya varian tebal, punya varian miring, file di resources/fonts].
     * Font non-DejaVu berlisensi SIL OFL 1.1 (lihat resources/fonts/licenses).
     */
    public const FONT_META = [
        'dejavusans' => ['"DejaVu Sans", Verdana, sans-serif', 400, true, true, false],
        'dejavuserif' => ['Georgia, "DejaVu Serif", serif', 400, true, true, false],
        'dejavusanscondensed' => ['"DejaVu Sans Condensed", "Arial Narrow", sans-serif', 400, true, false, false],
        'sora300' => ['CertSora, sans-serif', 300, false, false, true],
        'sora' => ['CertSora, sans-serif', 400, true, false, true],
        'sora600' => ['CertSora, sans-serif', 600, false, false, true],
        'sora800' => ['CertSora, sans-serif', 800, false, false, true],
        'montserrat' => ['CertMontserrat, sans-serif', 400, true, true, true],
        'cormorant' => ['CertCormorant, serif', 500, true, true, true],
        'greatvibes' => ['CertGreatVibes, cursive', 400, false, false, true],
        'pinyonscript' => ['CertPinyon, cursive', 400, false, false, true],
    ];
    /** Bentuk dekoratif. Titik poligon dalam persen kotak elemen. */
    public const SHAPES = [
        'rect' => 'Persegi / pita', 'ellipse' => 'Lingkaran / elips', 'line' => 'Garis',
        'triangle' => 'Segitiga', 'rtriangle' => 'Segitiga siku', 'diamond' => 'Belah ketupat',
        'parallelogram' => 'Jajar genjang', 'trapezoid' => 'Trapesium', 'hexagon' => 'Segi enam',
    ];
    public const POLYGONS = [
        'triangle' => [50, 0, 100, 100, 0, 100],
        'rtriangle' => [0, 0, 100, 100, 0, 100],
        'diamond' => [50, 0, 100, 50, 50, 100, 0, 50],
        'parallelogram' => [25, 0, 100, 0, 75, 100, 0, 100],
        'trapezoid' => [20, 0, 80, 0, 100, 100, 0, 100],
        'hexagon' => [25, 0, 75, 0, 100, 50, 75, 100, 25, 100, 0, 50],
    ];
    public const MAX_ELEMENTS = 80;
    /** Batas tanda tangan pejabat per template (mis. Dekan, Koordinator Prodi, Ketua Panitia). */
    public const MAX_SIGNATURES = 3;

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM cert_templates WHERE id = ?', [$id]);
    }

    public static function ofEvent(int $eventId): array
    {
        return DB::all(
            "SELECT t.*, (SELECT COUNT(*) FROM cert_issuances i JOIN cert_template_versions v ON v.id = i.template_version_id WHERE v.template_id = t.id) AS issued
             FROM cert_templates t WHERE t.event_id = ? ORDER BY CASE t.status WHEN 'Active' THEN 0 WHEN 'Draft' THEN 1 ELSE 2 END, t.id DESC",
            [$eventId]
        );
    }

    public static function active(int $eventId): ?array
    {
        return DB::one("SELECT * FROM cert_templates WHERE event_id = ? AND status = 'Active' ORDER BY id DESC LIMIT 1", [$eventId]);
    }

    public static function latestVersion(int $templateId): ?array
    {
        return DB::one('SELECT * FROM cert_template_versions WHERE template_id = ? ORDER BY version DESC LIMIT 1', [$templateId]);
    }

    public static function version(int $versionId): ?array
    {
        return DB::one('SELECT * FROM cert_template_versions WHERE id = ?', [$versionId]);
    }

    /** @return array{0:float,1:float} [lebar, tinggi] mm */
    public static function size(array $t): array
    {
        $f = self::FORMATS[$t['page_format']] ?? null;
        if ($f === null) {
            return [(float) $t['width_mm'], (float) $t['height_mm']];
        }
        [$a, $b] = $f;
        return $t['orientation'] === 'L' ? [(float) $b, (float) $a] : [(float) $a, (float) $b];
    }

    public static function elements(array $t): array
    {
        $list = json_decode((string) $t['elements'], true);
        return is_array($list) ? $list : [];
    }

    /** @param bool|string $preset true/'akademik', 'modern', atau false/'kosong' */
    public static function create(int $eventId, string $name, bool|string $preset, int $by): int
    {
        $elements = match (true) {
            $preset === true, $preset === 'akademik' => self::academicPreset(),
            $preset === 'modern' => self::modernPreset(),
            default => [],
        };
        return DB::insert('cert_templates', [
            'event_id' => $eventId, 'name' => $name, 'status' => 'Draft', 'page_format' => 'A4', 'orientation' => 'L',
            'width_mm' => 297, 'height_mm' => 210, 'elements' => json_encode($elements, JSON_UNESCAPED_UNICODE),
            'version' => 0, 'created_by' => $by, 'updated_by' => $by, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Tata letak awal sertifikat akademik (A4 landscape). Slot logo, tanda tangan, dan stempel sengaja kosong:
     * administrator wajib mengisinya dengan aset resmi yang diunggah.
     */
    public static function academicPreset(): array
    {
        $text = static fn (string $id, string $content, float $y, float $size, array $o = []) => $o + [
            'id' => $id, 'type' => 'text', 'content' => $content, 'x' => 10, 'y' => $y, 'w' => 80, 'font' => 'dejavuserif',
            'size' => $size, 'bold' => false, 'italic' => false, 'color' => '#1F2937', 'align' => 'C', 'lh' => 1.25, 'ls' => 0, 'fit' => false,
        ];
        $slot = static fn (string $id, string $slot, string $label, float $x, float $y, float $w, float $h) => [
            'id' => $id, 'type' => 'image', 'slot' => $slot, 'label' => $label, 'asset_id' => null, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
        ];
        return [
            $slot('lg1', 'logo', 'Logo Kemendiktisaintek', 4, 4, 8, 11),
            $slot('lg2', 'logo', 'Logo Universitas Mulawarman', 13, 4, 8, 11),
            $slot('lg3', 'logo', 'Logo Fakultas Teknik', 79, 4, 8, 11),
            $slot('lg4', 'logo', 'Logo Informatika', 88, 4, 8, 11),
            $text('ttl', 'SERTIFIKAT', 18, 40, ['bold' => true, 'ls' => 3, 'color' => '#0B1220']),
            $text('num', 'Nomor: {CERTIFICATE_NUMBER}', 30, 12, ['font' => 'dejavusans']),
            $text('giv', 'Diberikan kepada', 37, 14, ['italic' => true]),
            $text('nam', '{PARTICIPANT_NAME}', 43, 28, ['bold' => true, 'fit' => true, 'color' => '#0B1220']),
            $text('dsc', 'Atas partisipasinya sebagai Peserta dalam kegiatan {EVENT_NAME} yang diselenggarakan oleh {ORGANIZER_NAME} pada {EVENT_DATE} bertempat di {EVENT_LOCATION}.', 55, 13, ['x' => 15, 'w' => 70, 'font' => 'dejavusans', 'lh' => 1.45]),
            $text('dat', '{ISSUE_DATE}', 70, 12, ['x' => 55, 'w' => 35, 'font' => 'dejavusans']),
            $text('stt', '{SIGNER_TITLE}', 74, 12, ['x' => 55, 'w' => 35, 'font' => 'dejavusans']),
            $slot('stp', 'stamp', 'Stempel resmi fakultas', 53, 77, 14, 17),
            $slot('sig', 'signature', 'Tanda tangan Dekan', 62, 78, 21, 11),
            $text('snm', '{SIGNER_NAME}', 89, 12, ['x' => 55, 'w' => 35, 'font' => 'dejavusans', 'bold' => true]),
            ['id' => 'qr', 'type' => 'qr', 'x' => 6, 'y' => 74, 'size' => 11],
            $text('qrt', 'Pindai untuk verifikasi', 93, 8, ['x' => 3, 'w' => 17, 'font' => 'dejavusans', 'color' => '#475569']),
        ];
    }

    /**
     * Tata letak modern gelap (navy-teal, font Sora) dengan tiga blok penanda tangan.
     * Slot logo dan tanda tangan kosong; nama, jabatan, NIP, dan tema diambil dari variabel yang diisi admin.
     */
    public static function modernPreset(): array
    {
        $text = static fn (string $id, string $content, float $x, float $y, float $w, string $font, float $size, string $color, array $o = []) => $o + [
            'id' => $id, 'type' => 'text', 'content' => $content, 'x' => $x, 'y' => $y, 'w' => $w, 'font' => $font, 'size' => $size,
            'bold' => false, 'italic' => false, 'underline' => false, 'upper' => false, 'color' => $color, 'opacity' => 1, 'rot' => 0,
            'align' => 'L', 'lh' => 1.25, 'ls' => 0, 'fit' => false,
        ];
        $shape = static fn (string $id, string $kind, float $x, float $y, float $w, float $h, string $fill, array $o = []) => $o + [
            'id' => $id, 'type' => 'shape', 'shape' => $kind, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'fill' => $fill, 'fill2' => '',
            'grad' => 'h', 'opacity' => 1, 'stroke' => '', 'sw' => 0, 'radius' => 0, 'rot' => 0, 'flip' => false, 'dash' => false,
        ];
        $slot = static fn (string $id, string $slot, string $label, float $x, float $y, float $w, float $h) => [
            'id' => $id, 'type' => 'image', 'slot' => $slot, 'label' => $label, 'asset_id' => null, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h,
        ];
        $navy = '#0B1F3A';
        $els = [
            $shape('bg', 'rect', 0, 0, 100, 100, '#0B1F3A', ['fill2' => '#123A5A', 'grad' => 'd', 'lock' => true]),
            $shape('gm1', 'parallelogram', 70, 0, 34, 46, '#0F766E', ['opacity' => 0.9]),
            $shape('gm2', 'rtriangle', 80, 18, 20, 52, '#14B8A6', ['opacity' => 0.75, 'flip' => true]),
            $shape('gm3', 'triangle', 66, 44, 22, 35, '#0D9488', ['opacity' => 0.55]),
            $shape('gm4', 'diamond', 88, 58, 10, 16, '#5EEAD4', ['opacity' => 0.35]),
            $shape('gm5', 'line', 5, 17, 14, 1, '', ['stroke' => '#2DD4BF', 'sw' => 1.2]),
            $slot('lg1', 'logo', 'Logo Universitas Mulawarman', 5, 5, 6, 9),
            $slot('lg2', 'logo', 'Logo Fakultas Teknik', 12, 5, 6, 9),
            $slot('lg3', 'logo', 'Logo Program Studi Informatika', 19, 5, 6, 9),
            $text('ttl', 'SERTIFIKAT', 5, 20, 60, 'sora800', 50, '#FFFFFF', ['ls' => 4]),
            $text('num', 'Nomor Sertifikat: {CERTIFICATE_NUMBER}', 5, 34, 60, 'sora', 11, '#B9C7D8'),
            $text('giv', 'DIBERIKAN KEPADA', 5, 41, 60, 'sora600', 12, '#5EEAD4', ['ls' => 2.5]),
            $text('nam', '{PARTICIPANT_NAME}', 5, 45, 62, 'greatvibes', 44, '#FFFFFF', ['fit' => true]),
            $shape('nml', 'line', 5, 59, 50, 1, '', ['stroke' => '#2DD4BF', 'sw' => 0.6]),
            $text('rol', 'Sebagai PESERTA', 5, 61, 60, 'sora600', 14, '#FFFFFF', ['ls' => 1]),
            $text('dsc', 'Pada kegiatan {EVENT_NAME} dengan tema “{EVENT_THEME}” yang diselenggarakan pada tanggal {EVENT_DATE}.', 5, 66.5, 60, 'sora', 11, '#DCE5F0', ['lh' => 1.45]),
            $shape('band', 'rect', 0, 79, 100, 21, '#F1F5F9', ['lock' => true]),
            $shape('bandl', 'rect', 0, 79, 100, 0.8, '#14B8A6'),
        ];
        foreach (['' => 4, '2' => 27, '3' => 50] as $n => $x) {
            $els[] = $text('st' . ($n ?: '1'), '{SIGNER' . $n . '_TITLE}', $x, 81.5, 21, 'sora600', 8.5, $navy, ['align' => 'C', 'lh' => 1.15]);
            $els[] = $slot('sg' . ($n ?: '1'), 'signature', 'Tanda tangan penanda tangan ' . ($n ?: '1'), $x + 4, 85, 13, 7);
            $els[] = $text('sn' . ($n ?: '1'), '{SIGNER' . $n . '_NAME}', $x, 92.3, 21, 'sora600', 8.5, $navy, ['align' => 'C', 'underline' => true]);
            $els[] = $text('si' . ($n ?: '1'), 'NIP. {SIGNER' . $n . '_ID}', $x, 95.8, 21, 'sora', 7.5, '#334155', ['align' => 'C']);
        }
        $els[] = ['id' => 'qr', 'type' => 'qr', 'x' => 85.5, 'y' => 81, 'size' => 8];
        $els[] = $text('qrt', 'Pindai untuk verifikasi', 79, 95.8, 20, 'sora', 7, '#334155', ['align' => 'C']);
        return $els;
    }

    private static function hex(mixed $v, string $def): string
    {
        return is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtoupper($v) : $def;
    }

    private static function num(mixed $v, float $min, float $max, float $def): float
    {
        return is_numeric($v) ? round(max($min, min($max, (float) $v)), 2) : $def;
    }

    /**
     * Bersihkan elemen kiriman editor. Hanya properti yang dikenal yang disimpan.
     * @return array{0:array,1:string[]} [elemen bersih, error]
     */
    public static function sanitize(mixed $input, int $eventId): array
    {
        if (!is_array($input)) {
            return [[], [t('Data elemen tidak valid.')]];
        }
        if (count($input) > self::MAX_ELEMENTS) {
            return [[], [t('Maksimal :n elemen per template.', ['n' => self::MAX_ELEMENTS])]];
        }
        $out = [];
        $errors = [];
        $ids = [];
        foreach (array_values($input) as $i => $el) {
            if (!is_array($el) || !in_array($el['type'] ?? '', ['text', 'image', 'qr', 'shape'], true)) {
                $errors[] = t('Elemen #:n tidak dikenal.', ['n' => $i + 1]);
                continue;
            }
            $id = is_string($el['id'] ?? null) && preg_match('/^[a-z0-9]{1,12}$/', $el['id']) && !isset($ids[$el['id']]) ? $el['id'] : 'e' . bin2hex(random_bytes(3));
            $ids[$id] = true;
            // Bentuk boleh keluar sedikit dari tepi halaman (efek bleed); elemen lain tetap di dalam halaman.
            $lo = $el['type'] === 'shape' ? -50 : 0;
            $base = ['id' => $id, 'type' => $el['type'], 'x' => self::num($el['x'] ?? 0, $lo, 100, 0), 'y' => self::num($el['y'] ?? 0, $lo, 100, 0)];
            if (!empty($el['lock'])) {
                $base['lock'] = true; // hanya penanda editor: elemen tidak ikut terseret di kanvas
            }
            if ($el['type'] === 'text') {
                $content = is_string($el['content'] ?? null) ? $el['content'] : '';
                $content = (string) preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/u', '', mb_substr(str_replace("\r", '', $content), 0, 600));
                $out[] = $base + [
                    'content' => $content,
                    'w' => self::num($el['w'] ?? 80, 1, 100, 80),
                    'font' => isset(self::FONTS[$el['font'] ?? '']) ? $el['font'] : 'dejavusans',
                    'size' => self::num($el['size'] ?? 14, 4, 120, 14),
                    'bold' => !empty($el['bold']),
                    'italic' => !empty($el['italic']),
                    'underline' => !empty($el['underline']),
                    'upper' => !empty($el['upper']),
                    'color' => self::hex($el['color'] ?? null, '#1F2937'),
                    'opacity' => self::num($el['opacity'] ?? 1, 0.05, 1, 1),
                    'rot' => self::num($el['rot'] ?? 0, -180, 180, 0),
                    'align' => in_array($el['align'] ?? '', ['L', 'C', 'R', 'J'], true) ? $el['align'] : 'C',
                    'lh' => self::num($el['lh'] ?? 1.25, 0.8, 3, 1.25),
                    'ls' => self::num($el['ls'] ?? 0, -1, 10, 0),
                    'fit' => !empty($el['fit']),
                ];
            } elseif ($el['type'] === 'image') {
                $assetId = isset($el['asset_id']) && is_numeric($el['asset_id']) && (int) $el['asset_id'] > 0 ? (int) $el['asset_id'] : null;
                if ($assetId !== null) {
                    $asset = CertAsset::find($assetId);
                    if (!$asset || !CertAsset::usableBy($asset, $eventId)) {
                        $errors[] = t('Aset pada elemen #:n tidak tersedia untuk event ini.', ['n' => $i + 1]);
                        $assetId = null;
                    }
                }
                $out[] = $base + [
                    'slot' => isset(CertAsset::KINDS[$el['slot'] ?? '']) ? $el['slot'] : 'image',
                    'label' => mb_substr(is_string($el['label'] ?? null) ? trim($el['label']) : '', 0, 60),
                    'asset_id' => $assetId,
                    'w' => self::num($el['w'] ?? 10, 1, 100, 10),
                    'h' => self::num($el['h'] ?? 10, 1, 100, 10),
                ];
            } elseif ($el['type'] === 'shape') {
                $out[] = $base + [
                    'shape' => isset(self::SHAPES[$el['shape'] ?? '']) ? $el['shape'] : 'rect',
                    'w' => self::num($el['w'] ?? 20, 0.2, 200, 20),
                    'h' => self::num($el['h'] ?? 10, 0.2, 200, 10),
                    'fill' => ($el['fill'] ?? '') === '' ? '' : self::hex($el['fill'], '#0F766E'),
                    'fill2' => ($el['fill2'] ?? '') === '' ? '' : self::hex($el['fill2'], ''),
                    'grad' => in_array($el['grad'] ?? '', ['h', 'v', 'd'], true) ? $el['grad'] : 'h',
                    'opacity' => self::num($el['opacity'] ?? 1, 0.05, 1, 1),
                    'stroke' => ($el['stroke'] ?? '') === '' ? '' : self::hex($el['stroke'], '#0B1220'),
                    'sw' => self::num($el['sw'] ?? 0, 0, 10, 0),
                    'radius' => self::num($el['radius'] ?? 0, 0, 50, 0),
                    'rot' => self::num($el['rot'] ?? 0, -180, 180, 0),
                    'flip' => !empty($el['flip']),
                    'dash' => !empty($el['dash']),
                ];
            } else {
                $out[] = $base + ['size' => self::num($el['size'] ?? 12, 3, 40, 12)];
            }
        }
        return [$out, $errors];
    }

    /** Snapshot lengkap untuk versi: halaman, latar, elemen, dan aset yang dipakai. */
    public static function snapshot(array $t): array
    {
        [$w, $h] = self::size($t);
        $elements = self::elements($t);
        $assets = [];
        foreach ($elements as $el) {
            if ($el['type'] === 'image' && $el['asset_id'] && ($a = CertAsset::find((int) $el['asset_id']))) {
                $assets[(int) $a['id']] = ['path' => $a['file_path'], 'kind' => $a['kind'], 'approved' => (int) $a['approved'], 'label' => $a['label']];
            }
        }
        return [
            'template_id' => (int) $t['id'], 'name' => $t['name'], 'width' => $w, 'height' => $h,
            'background' => $t['background_path'] ? ['path' => $t['background_path'], 'type' => $t['background_type']] : null,
            'elements' => $elements, 'assets' => $assets,
        ];
    }

    /** Aktifkan: simpan versi baru, arsipkan template Active lain di event yang sama. @return int id versi */
    public static function activate(array $t, int $by): int
    {
        return DB::transaction(static function () use ($t, $by): int {
            $version = (int) $t['version'] + 1;
            $vid = DB::insert('cert_template_versions', [
                'template_id' => (int) $t['id'], 'version' => $version,
                'snapshot' => json_encode(self::snapshot($t), JSON_UNESCAPED_UNICODE), 'created_by' => $by, 'created_at' => now(),
            ]);
            DB::run("UPDATE cert_templates SET status = 'Archived', updated_at = ? WHERE event_id = ? AND status = 'Active' AND id <> ?", [now(), (int) $t['event_id'], (int) $t['id']]);
            DB::update('cert_templates', ['status' => 'Active', 'version' => $version, 'updated_by' => $by, 'updated_at' => now()], 'id = ?', [(int) $t['id']]);
            return $vid;
        });
    }

    public static function setStatus(int $id, string $status, int $by): void
    {
        DB::update('cert_templates', ['status' => $status, 'updated_by' => $by, 'updated_at' => now()], 'id = ?', [$id]);
    }

    public static function update(int $id, array $data, int $by): void
    {
        DB::update('cert_templates', $data + ['updated_by' => $by, 'updated_at' => now()], 'id = ?', [$id]);
    }
}
