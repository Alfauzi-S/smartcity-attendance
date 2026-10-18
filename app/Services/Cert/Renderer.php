<?php
declare(strict_types=1);

namespace App\Services\Cert;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Membuat PDF sertifikat dari snapshot template + nilai variabel.
 * Teks digambar dengan MultiCell biasa (bukan HTML), jadi isi variabel tidak pernah ditafsirkan sebagai kode.
 * Font DejaVu mendukung karakter Indonesia dan Unicode.
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
            if ($el['type'] === 'text') {
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

    private static function text(Fpdi $pdf, array $el, string $text, float $x, float $y, float $w): void
    {
        $style = ($el['bold'] ? 'B' : '') . ($el['italic'] && $el['font'] !== 'dejavusanscondensed' ? 'I' : '');
        $size = (float) $el['size'];
        $pdf->setFontSpacing((float) $el['ls'] * self::PT_MM);
        $pdf->setCellHeightRatio((float) $el['lh']);
        $pdf->SetFont($el['font'], $style, $size);
        if ($el['fit'] && !str_contains($text, "\n")) {
            // Kecilkan font sampai teks muat dalam satu baris (mis. nama peserta yang panjang)
            while ($size > 6 && $pdf->GetStringWidth($text) > $w) {
                $size -= 0.5;
                $pdf->SetFont($el['font'], $style, $size);
            }
        }
        [$r, $g, $b] = sscanf($el['color'], '#%02x%02x%02x');
        $pdf->SetTextColor((int) $r, (int) $g, (int) $b);
        $pdf->MultiCell($w, 0, $text, 0, $el['align'], false, 1, $x, $y, true, 0, false, true, 0, 'T', false);
        $pdf->setFontSpacing(0);
    }
}
