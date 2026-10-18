<?php
declare(strict_types=1);

namespace App\Services\Cert;

/**
 * Validasi dan penyimpanan file unggahan sertifikat.
 * - Isi file diperiksa (finfo + getimagesize / header %PDF + parser FPDI), bukan hanya ekstensi.
 * - Gambar di-encode ulang dengan GD (menghapus metadata dan konten tersembunyi).
 * - Disimpan di storage/ (tidak bisa diakses langsung dari web) dengan nama acak.
 */
final class Upload
{
    public const MAX_PIXELS = 16_000_000;
    public const MAX_SIDE = 6000;

    public static function maxBytes(): int
    {
        return (int) round((float) config('cert.max_upload_mb', 8) * 1024 * 1024);
    }

    /** @return array{0:string,1:int} [tmp path, ukuran] */
    private static function check(?array $file): array
    {
        if (!$file || !isset($file['error']) || is_array($file['error'])) {
            throw new CertError(t('Pilih file yang akan diunggah.'));
        }
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new CertError(t('File terlalu besar. Maksimal :mb MB.', ['mb' => config('cert.max_upload_mb', 8)]));
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new CertError(t('File gagal diunggah. Coba lagi.'));
        }
        $size = (int) filesize($file['tmp_name']);
        if ($size <= 0 || $size > self::maxBytes()) {
            throw new CertError(t('File terlalu besar. Maksimal :mb MB.', ['mb' => config('cert.max_upload_mb', 8)]));
        }
        return [$file['tmp_name'], $size];
    }

    private static function mime(string $path): string
    {
        return (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
    }

    private static function target(string $dir, string $ext): array
    {
        $rel = 'cert/' . $dir . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        $abs = STORAGE_PATH . '/' . $rel;
        if (!is_dir(dirname($abs)) && !mkdir(dirname($abs), 0775, true) && !is_dir(dirname($abs))) {
            throw new CertError(t('Folder penyimpanan tidak dapat dibuat. Periksa izin folder storage.'));
        }
        return [$rel, $abs];
    }

    /** Gambar PNG/JPEG. @return array{path:string,mime:string,w:int,h:int} */
    public static function image(?array $file, string $dir): array
    {
        if (!extension_loaded('gd')) {
            throw new CertError(t('Ekstensi PHP GD belum aktif di server, gambar tidak dapat diproses.'));
        }
        [$tmp] = self::check($file);
        $mime = self::mime($tmp);
        $info = @getimagesize($tmp);
        if (!in_array($mime, ['image/png', 'image/jpeg'], true) || !$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw new CertError(t('Format tidak didukung. Gunakan PNG atau JPG.'));
        }
        [$w, $h] = $info;
        if ($w < 20 || $h < 20 || $w > self::MAX_SIDE || $h > self::MAX_SIDE || $w * $h > self::MAX_PIXELS) {
            throw new CertError(t('Ukuran gambar harus 20–:max piksel per sisi dan maksimal 16 megapiksel.', ['max' => self::MAX_SIDE]));
        }
        @ini_set('memory_limit', '256M');
        $img = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($tmp) : @imagecreatefromjpeg($tmp);
        if (!$img) {
            throw new CertError(t('File gambar rusak atau tidak dapat dibaca.'));
        }
        $png = $info[2] === IMAGETYPE_PNG;
        [$rel, $abs] = self::target($dir, $png ? 'png' : 'jpg');
        if ($png) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $ok = imagepng($img, $abs, 6);
        } else {
            $ok = imagejpeg($img, $abs, 92);
        }
        imagedestroy($img);
        if (!$ok) {
            throw new CertError(t('Gambar gagal disimpan.'));
        }
        return ['path' => $rel, 'mime' => $png ? 'image/png' : 'image/jpeg', 'w' => $w, 'h' => $h];
    }

    /** PDF satu halaman (halaman pertama dipakai sebagai latar). @return array{path:string,mime:string,w:int,h:int} ukuran dalam 0,1 mm */
    public static function pdf(?array $file, string $dir): array
    {
        [$tmp] = self::check($file);
        $head = (string) file_get_contents($tmp, false, null, 0, 5);
        if (self::mime($tmp) !== 'application/pdf' || $head !== '%PDF-') {
            throw new CertError(t('File bukan PDF yang valid.'));
        }
        try {
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('L', 'mm', 'A4');
            $pages = $pdf->setSourceFile($tmp);
            $tpl = $pdf->importPage(1);
            $size = $pdf->getTemplateSize($tpl);
        } catch (\Throwable $e) {
            throw new CertError(t('PDF ini tidak dapat diproses (mungkin terenkripsi atau memakai kompresi yang tidak didukung). Ekspor desain sebagai PNG/JPG resolusi tinggi atau PDF versi 1.4.'));
        }
        if ($pages < 1) {
            throw new CertError(t('PDF tidak memiliki halaman.'));
        }
        [$rel, $abs] = self::target($dir, 'pdf');
        if (!copy($tmp, $abs)) {
            throw new CertError(t('File gagal disimpan.'));
        }
        return ['path' => $rel, 'mime' => 'application/pdf', 'w' => (int) round($size['width'] * 10), 'h' => (int) round($size['height'] * 10)];
    }

    /** Path absolut aman untuk file di storage/cert (mencegah path traversal). */
    public static function abs(?string $rel): ?string
    {
        if (!$rel || !preg_match('#^cert/[a-z_]+/[a-f0-9]{32}\.(png|jpg|pdf)$#', $rel)) {
            return null;
        }
        $abs = STORAGE_PATH . '/' . $rel;
        return is_file($abs) ? $abs : null;
    }
}
