<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/** Catatan penerbitan sertifikat. Record lama tidak pernah dihapus; pencabutan dan terbit ulang menambah histori. */
final class CertIssuance
{
    public const STATUSES = ['generated' => ['Generated', 'info'], 'published' => ['Published', 'ok'], 'revoked' => ['Revoked', 'bad']];

    public static function badge(string $status): string
    {
        [$label, $tone] = self::STATUSES[$status] ?? [$status, 'mute'];
        return '<span class="bdg bdg-' . $tone . '"><i></i>' . e(t($label)) . '</span>';
    }

    private const SELECT = 'SELECT i.*, p.full_name, p.institution, p.identity_number, p.code AS participant_code, p.status AS attendance,
        n.version AS numbering_version, n.status AS numbering_status, v.version AS template_version, g.username AS generated_by_name,
        pb.username AS published_by_name, rv.username AS revoked_by_name
        FROM cert_issuances i JOIN participants p ON p.id = i.participant_id
        JOIN cert_numbering n ON n.id = i.numbering_id JOIN cert_template_versions v ON v.id = i.template_version_id
        LEFT JOIN users g ON g.id = i.generated_by LEFT JOIN users pb ON pb.id = i.published_by LEFT JOIN users rv ON rv.id = i.revoked_by';

    public static function find(int $id): ?array
    {
        return DB::one(self::SELECT . ' WHERE i.id = ?', [$id]);
    }

    public static function findByToken(string $token): ?array
    {
        return DB::one(self::SELECT . ' WHERE i.token = ?', [$token]);
    }

    public static function active(int $participantId): ?array
    {
        return DB::one("SELECT * FROM cert_issuances WHERE participant_id = ? AND status <> 'revoked' ORDER BY id DESC LIMIT 1", [$participantId]);
    }

    /** @return array{0:string,1:array} */
    private static function filter(int $eventId, string $status, string $q): array
    {
        $w = 'i.event_id = ?';
        $p = [$eventId];
        if (isset(self::STATUSES[$status])) {
            $w .= ' AND i.status = ?';
            $p[] = $status;
        }
        if ($q !== '') {
            $w .= ' AND (p.full_name LIKE ? OR i.cert_number LIKE ?)';
            array_push($p, '%' . $q . '%', '%' . $q . '%');
        }
        return [$w, $p];
    }

    public static function list(int $eventId, string $status = '', string $q = '', int $limit = 25, int $offset = 0): array
    {
        [$w, $p] = self::filter($eventId, $status, $q);
        return DB::all(self::SELECT . ' WHERE ' . $w . ' ORDER BY i.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $p);
    }

    public static function count(int $eventId, string $status = '', string $q = ''): int
    {
        [$w, $p] = self::filter($eventId, $status, $q);
        return (int) DB::value('SELECT COUNT(*) FROM cert_issuances i JOIN participants p ON p.id = i.participant_id WHERE ' . $w, $p);
    }

    public static function stats(int $eventId): array
    {
        $s = ['generated' => 0, 'published' => 0, 'revoked' => 0];
        foreach (DB::all('SELECT status, COUNT(*) AS n FROM cert_issuances WHERE event_id = ? GROUP BY status', [$eventId]) as $r) {
            $s[$r['status']] = (int) $r['n'];
        }
        return $s;
    }

    /** Semua penerbitan untuk satu peserta (rantai terbit ulang). */
    public static function history(int $participantId): array
    {
        return DB::all(self::SELECT . ' WHERE i.participant_id = ? ORDER BY i.id DESC', [$participantId]);
    }

    public static function abs(array $i): ?string
    {
        if (!preg_match('#^certificates/\d+/[a-f0-9]{32}\.pdf$#', (string) $i['file_path'])) {
            return null;
        }
        $file = STORAGE_PATH . '/' . $i['file_path'];
        return is_file($file) ? $file : null;
    }

    /** Kirim file PDF yang tersimpan (snapshot final, bukan dibuat ulang). */
    public static function stream(array $i, string $disposition = 'attachment'): never
    {
        $file = self::abs($i);
        if ($file === null || !hash_equals((string) $i['file_hash'], (string) hash_file('sha256', $file))) {
            abort(410, 'File sertifikat tidak tersedia', 'File tidak ditemukan atau tidak cocok dengan catatan penerbitan. Hubungi panitia.');
        }
        $name = 'Sertifikat-' . trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $i['full_name']) ?: 'peserta'), '-') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }
}
