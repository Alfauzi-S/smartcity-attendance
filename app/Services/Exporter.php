<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;
use App\Models\Participant;
use ZipArchive;

/** Ekspor data absensi ke CSV atau Excel (.xlsx). */
final class Exporter
{
    public const HEADERS = ['No', 'Nomor Identitas', 'Nama Lengkap', 'Profesi', 'WhatsApp / HP', 'Institusi', 'Program Studi', 'Status', 'Waktu Hadir', 'Metode', 'Kode Peserta', 'Terdaftar'];

    /** Judul kolom sesuai bahasa aktif. */
    public static function headers(): array
    {
        return array_map(static fn (string $h) => t($h), self::HEADERS);
    }

    public static function rows(int $eventId, string $status): array
    {
        $total = Participant::count($eventId, '', $status);
        $list = Participant::search($eventId, '', $status, max(1, $total), 0);
        $rows = [];
        foreach ($list as $i => $p) {
            $rows[] = [
                $i + 1,
                $p['identity_number'],
                $p['full_name'],
                t($p['profession']),
                $p['phone'],
                $p['institution'],
                t($p['field_of_study']),
                status_label($p['status']),
                $p['checked_in_at'] ? date('Y-m-d H:i', (int) strtotime($p['checked_in_at'])) : '',
                t(['scan' => 'Scan QR', 'cari' => 'Input manual petugas', 'manual' => 'Manual (Super Admin)'][$p['method'] ?? ''] ?? ''),
                $p['code'],
                date('Y-m-d H:i', (int) strtotime($p['created_at'])),
            ];
        }
        return $rows;
    }

    public static function csv(string $filename, array $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
        fputcsv($out, self::headers(), ',', '"', '\\');
        foreach ($rows as $r) {
            // Cegah formula injection di spreadsheet
            $r = array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $r);
            fputcsv($out, $r, ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    public static function xlsxAvailable(): bool
    {
        return class_exists(ZipArchive::class);
    }

    public static function xlsx(string $filename, string $sheetTitle, array $rows): never
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::x(mb_substr($sheetTitle, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf fontId="0"/><xf fontId="1" applyFont="1"/></cellXfs></styleSheet>');

        $widths = [6, 18, 32, 22, 16, 32, 26, 18, 18, 12, 14, 18];
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>';
        foreach ($widths as $i => $w) {
            $sheet .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $sheet .= '</cols><sheetData>';
        $all = array_merge([self::headers()], $rows);
        foreach ($all as $ri => $row) {
            $sheet .= '<row r="' . ($ri + 1) . '">';
            foreach (array_values($row) as $ci => $v) {
                $ref = self::col($ci) . ($ri + 1);
                $style = $ri === 0 ? ' s="1"' : '';
                if (is_int($v)) {
                    $sheet .= '<c r="' . $ref . '"' . $style . '><v>' . $v . '</v></c>';
                } else {
                    $sheet .= '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t>' . self::x((string) $v) . '</t></is></c>';
                }
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: no-store');
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    private static function col(int $i): string
    {
        $s = '';
        $i++;
        while ($i > 0) {
            $m = ($i - 1) % 26;
            $s = chr(65 + $m) . $s;
            $i = intdiv($i - 1, 26);
        }
        return $s;
    }

    private static function x(string $v): string
    {
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v) ?? '';
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
