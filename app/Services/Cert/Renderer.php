<?php
declare(strict_types=1);

namespace App\Services\Cert;

use App\Models\CertTemplate;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Membuat PDF sertifikat dari snapshot template + nilai variabel.
 * Teks digambar dengan MultiCell biasa (bukan HTML), jadi isi variabel tidak pernah ditafsirkan sebagai kode.
 * Font DejaVu mendukung karakter Indonesia dan Unicode. Font tambahan (Sora, Montserrat, dll.) dimuat dari
 * resources/fonts; bila teks memuat karakter yang tidak ada di font tersebut, elemen itu memakai DejaVu Sans.
 */
final class Renderer
{
    private const PT_MM = 0.352778;

    /**
     * @param array  $snap    hasil CertTemplate::snapshot()
     * @param array  $values  nilai placeholder (key => teks)
     * @param string $qrUrl   isi QR verifikasi (bukan QR absensi)
     * @return string isi file PDF
     */
    public static function pdf(array $snap, array $values, string $qrUrl, bool $preview = false, string $title = ''): string
    {
        @ini_set('memory_limit', '256M');
        $W = (float) $snap['width'];
        $H = (float) $snap['height'];
        $pdf = new Fpdi($W >= $H ? 'L' : 'P', 'mm', [$W, $H], true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetCreator('SmartCity Attendance');
        $pdf->SetTitle($title !== '' ? $title : 'Sertifikat');
        $pdf->setFontSubsetting(true);
        $pdf->AddPage($W >= $H ? 'L' : 'P', [$W, $H]);

        if (!empty($snap['background'])) {
            $file = Upload::abs($snap['background']['path']);
            if ($file === null) {
                throw new CertError(t('File desain latar template tidak ditemukan di server.'));
            }
            if ($snap['background']['type'] === 'pdf') {
                $pdf->setSourceFile($file);
                $tpl = $pdf->importPage(1);
                $pdf->useTemplate($tpl, 0, 0, $W, $H, false);
            } else {
                $pdf->Image($file, 0, 0, $W, $H, '', '', '', true, 300, '', false, false, 0, false, false, false);
            }
        }

        foreach ($snap['elements'] as $el) {
            $x = $W * $el['x'] / 100;
            $y = $H * $el['y'] / 100;
            if ($el['type'] === 'shape') {
                self::shape($pdf, $el, $x, $y, $W * $el['w'] / 100, $H * $el['h'] / 100);
            } elseif ($el['type'] === 'text') {
                self::text($pdf, $el, Placeholders::render((string) $el['content'], $values), $x, $y, $W * $el['w'] / 100);
            } elseif ($el['type'] === 'image') {
                $w = $W * $el['w'] / 100;
                $h = $H * $el['h'] / 100;
                $asset = $el['asset_id'] ? ($snap['assets'][$el['asset_id']] ?? null) : null;
                $file = $asset ? Upload::abs($asset['path']) : null;
                if ($file !== null) {
                    // 'CM': gambar dimuat utuh di dalam kotak dengan rasio aspek terjaga
                    $pdf->Image($file, $x, $y, $w, $h, '', '', '', false, 300, '', false, false, 0, 'CM', false, false);
                }
                if ($preview && ($file === null || !$asset['approved'])) {
                    $pdf->SetDrawColor(220, 38, 38);
                    $pdf->SetLineStyle(['width' => 0.3, 'dash' => '2,1.5', 'color' => [220, 38, 38]]);
                    $pdf->Rect($x, $y, $w, $h);
                    $pdf->SetLineStyle(['dash' => 0]);
                    $pdf->SetTextColor(220, 38, 38);
                    $pdf->SetFont('dejavusans', 'B', 6.5);
                    $label = $file === null ? '[' . ($el['label'] ?: $el['slot']) . ' belum diisi]' : '[belum disetujui]';
                    $pdf->MultiCell($w, 0, $label, 0, 'C', false, 1, $x, $y + $h / 2 - 2, true);
                }
            } else {
                $s = $W * $el['size'] / 100;
                $pdf->write2DBarcode($qrUrl, 'QRCODE,M', $x, $y, $s, $s, [
                    'border' => false, 'padding' => 1, 'fgcolor' => [0, 0, 0], 'bgcolor' => [255, 255, 255], 'module_width' => 1, 'module_height' => 1,
                ], 'N');
            }
        }

        if ($preview) {
            $pdf->SetAlpha(0.18);
            $pdf->SetTextColor(220, 38, 38);
            $pdf->SetFont('dejavusans', 'B', min($W, $H) / 2.6);
            $pdf->StartTransform();
            $pdf->Rotate(25, $W / 2, $H / 2);
            $pdf->MultiCell($W, 0, 'PRATINJAU', 0, 'C', false, 1, 0, $H / 2 - 30, true);
            $pdf->SetFont('dejavusans', 'B', min($W, $H) / 13);
            $pdf->MultiCell($W, 0, 'BUKAN SERTIFIKAT RESMI · NOMOR MASIH CONTOH', 0, 'C', false, 1, 0, $H / 2 + 14, true);
            $pdf->StopTransform();
            $pdf->SetAlpha(1);
        }
        return $pdf->Output('sertifikat.pdf', 'S');
    }

    /** @return int[] [r, g, b] */
    private static function rgb(string $hex): array
    {
        return array_map('intval', sscanf($hex, '#%02x%02x%02x'));
    }

    /** Daftarkan font dari resources/fonts (sekali per dokumen) dan kembalikan gaya yang benar-benar tersedia. */
    private static function useFont(Fpdi $pdf, string $font, bool $bold, bool $italic, bool $underline, float $size): void
    {
        $meta = CertTemplate::FONT_META[$font] ?? CertTemplate::FONT_META['dejavusans'];
        $style = ($bold && $meta[2] ? 'B' : '') . ($italic && $meta[3] ? 'I' : '');
        if ($meta[4]) {
            $file = ROOT_PATH . '/resources/fonts/' . $font . strtolower($style) . '.php';
            if (!is_file($file)) {
                throw new CertError(t('File font :font tidak ditemukan di server.', ['font' => $font]));
            }
            $pdf->AddFont($font, $style, $file);
        }
        $pdf->SetFont($font, $style . ($underline ? 'U' : ''), $size);
    }

    private static function text(Fpdi $pdf, array $el, string $text, float $x, float $y, float $w): void
    {
        if (!empty($el['upper'])) {
            $text = mb_strtoupper($text);
        }
        $font = isset(CertTemplate::FONT_META[$el['font']]) ? $el['font'] : 'dejavusans';
        $bold = (bool) $el['bold'];
        $italic = (bool) $el['italic'];
        $underline = !empty($el['underline']);
        $size = (float) $el['size'];
        $pdf->setFontSpacing((float) $el['ls'] * self::PT_MM);
        $pdf->setCellHeightRatio((float) $el['lh']);
        self::useFont($pdf, $font, $bold, $italic, $underline, $size);
        if (CertTemplate::FONT_META[$font][4]) {
            if (!$pdf->isCharDefined(0x27E8)) {
                $text = strtr($text, ['⟨' => '[', '⟩' => ']']); // penanda variabel kosong di pratinjau
            }
            foreach (mb_str_split(str_replace("\n", '', $text)) as $ch) {
                if (!$pdf->isCharDefined(mb_ord($ch))) {
                    $font = 'dejavusans'; // karakter tidak tersedia di font pilihan: pakai font Unicode lengkap
                    self::useFont($pdf, $font, $bold, $italic, $underline, $size);
                    break;
                }
            }
        }
        if ($el['fit'] && !str_contains($text, "\n")) {
            // Kecilkan font sampai teks muat dalam satu baris (mis. nama peserta yang panjang)
            while ($size > 6 && $pdf->GetStringWidth($text) > $w) {
                $size -= 0.5;
                $pdf->SetFontSize($size);
            }
        }
        [$r, $g, $b] = self::rgb($el['color']);
        $pdf->SetTextColor($r, $g, $b);
        $alpha = (float) ($el['opacity'] ?? 1);
        $rot = (float) ($el['rot'] ?? 0);
        if ($alpha < 1) {
            $pdf->SetAlpha($alpha);
        }
        if ($rot != 0) {
            $pdf->StartTransform();
            $pdf->Rotate(-$rot, $x, $y); // derajat searah jarum jam seperti di editor, poros di pojok kiri atas
        }
        $pdf->MultiCell($w, 0, $text, 0, $el['align'], false, 1, $x, $y, true, 0, false, true, 0, 'T', false);
        if ($rot != 0) {
            $pdf->StopTransform();
        }
        if ($alpha < 1) {
            $pdf->SetAlpha(1);
        }
        $pdf->setFontSpacing(0);
    }

    /** Bentuk dekoratif: isi warna/gradasi, garis tepi, transparansi, rotasi, dan cermin horizontal. */
    private static function shape(Fpdi $pdf, array $el, float $x, float $y, float $w, float $h): void
    {
        $fill = (string) $el['fill'];
        $fill2 = (string) $el['fill2'];
        $stroke = (string) $el['stroke'];
        $sw = (float) $el['sw'];
        $isLine = $el['shape'] === 'line';
        if ($isLine ? ($stroke === '' || $sw <= 0) : ($fill === '' && ($stroke === '' || $sw <= 0))) {
            return; // tidak ada yang digambar
        }
        $pdf->StartTransform();
        if ((float) $el['opacity'] < 1) {
            $pdf->SetAlpha((float) $el['opacity']);
        }
        if ((float) $el['rot'] != 0) {
            $pdf->Rotate(-(float) $el['rot'], $x + $w / 2, $y + ($isLine ? 0 : $h / 2));
        }
        if ($stroke !== '' && $sw > 0) {
            $pdf->SetLineStyle(['width' => $sw, 'cap' => 'butt', 'join' => 'miter', 'dash' => $el['dash'] ? ($sw * 3) . ',' . ($sw * 2) : 0, 'color' => self::rgb($stroke)]);
        }
        if ($isLine) {
            $pdf->Line($x, $y, $x + $w, $y);
        } else {
            $path = static function (string $style) use ($pdf, $el, $x, $y, $w, $h): void {
                if ($el['shape'] === 'rect') {
                    $r = min((float) $el['radius'], $w / 2, $h / 2);
                    $r > 0 ? $pdf->RoundedRect($x, $y, $w, $h, $r, '1111', $style) : $pdf->Rect($x, $y, $w, $h, $style);
                } elseif ($el['shape'] === 'ellipse') {
                    $pdf->Ellipse($x + $w / 2, $y + $h / 2, $w / 2, $h / 2, 0, 0, 360, $style);
                } else {
                    $pts = [];
                    foreach (array_chunk(CertTemplate::POLYGONS[$el['shape']], 2) as [$px, $py]) {
                        $pts[] = $x + $w * ($el['flip'] ? 100 - $px : $px) / 100;
                        $pts[] = $y + $h * $py / 100;
                    }
                    $pdf->Polygon($pts, $style);
                }
            };
            if ($fill !== '' && $fill2 !== '') {
                // Gradasi: potong area sesuai bentuk lalu isi gradasi linear
                $pdf->StartTransform();
                $path('CNZ');
                $coords = ['h' => [0, 0, 1, 0], 'v' => [0, 1, 0, 0], 'd' => [0, 1, 1, 0]][$el['grad']] ?? [0, 0, 1, 0];
                $pdf->LinearGradient($x, $y, $w, $h, self::rgb($fill), self::rgb($fill2), $coords);
                $pdf->StopTransform();
                if ($stroke !== '' && $sw > 0) {
                    $path('D');
                }
            } else {
                if ($fill !== '') {
                    $pdf->SetFillColor(...self::rgb($fill));
                }
                $path(($fill !== '' ? 'F' : '') . ($stroke !== '' && $sw > 0 ? 'D' : ''));
            }
        }
        $pdf->SetLineStyle(['width' => 0.2, 'dash' => 0, 'color' => [0, 0, 0]]);
        if ((float) $el['opacity'] < 1) {
            $pdf->SetAlpha(1);
        }
        $pdf->StopTransform();
    }
}
