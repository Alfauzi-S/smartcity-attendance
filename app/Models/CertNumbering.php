<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;
use App\Services\Cert\Placeholders;

/**
 * Pola nomor sertifikat per event dengan versi.
 * - Pola baru selalu dibuat sebagai Draft; Super Admin menyetujui (approved). Versi approved sebelumnya menjadi retired.
 * - Nomor final disimpan permanen di cert_issuances; mengubah pola tidak mengubah nomor lama.
 * - Nomor urut diambil dari cert_sequences di dalam transaksi (aman untuk proses bersamaan) dan tidak pernah dipakai ulang.
 */
final class CertNumbering
{
    public const SEQ_KEYS = ['SEQUENCE', 'SEQUENCE_PADDED', 'NUMBER'];
    private const LITERAL_RE = '/^[A-Za-z0-9 .\/_\-()#:]*$/';
    public const SCOPES = ['event' => 'Per event (urutan sendiri untuk event ini)', 'global' => 'Global (satu urutan untuk semua event)'];
    public const RESETS = ['never' => 'Tidak pernah di-reset', 'yearly' => 'Di-reset setiap tahun (wajib memakai {YEAR})'];
    public const STATUSES = ['draft' => ['Draft · belum disetujui', 'warn'], 'approved' => ['Disetujui', 'ok'], 'retired' => ['Versi lama', 'mute']];

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM cert_numbering WHERE id = ?', [$id]);
    }

    public static function versions(int $eventId): array
    {
        return DB::all(
            'SELECT n.*, u.username AS created_by_name, a.username AS approved_by_name FROM cert_numbering n
             LEFT JOIN users u ON u.id = n.created_by LEFT JOIN users a ON a.id = n.approved_by
             WHERE n.event_id = ? ORDER BY n.version DESC',
            [$eventId]
        );
    }

    public static function approved(int $eventId): ?array
    {
        return DB::one("SELECT * FROM cert_numbering WHERE event_id = ? AND status = 'approved' ORDER BY version DESC LIMIT 1", [$eventId]);
    }

    public static function latest(int $eventId): ?array
    {
        return DB::one('SELECT * FROM cert_numbering WHERE event_id = ? ORDER BY version DESC LIMIT 1', [$eventId]);
    }

    /** Pola yang dipakai untuk generate: versi approved; bila persetujuan tidak diwajibkan, versi terbaru. */
    public static function forIssuing(int $eventId, bool $requireApproved): ?array
    {
        return $requireApproved ? self::approved($eventId) : (self::approved($eventId) ?? self::latest($eventId));
    }

    /** @param int|null $eventId bila diisi, placeholder juga dicek terhadap variabel yang berlaku di event itu */
    public static function validate(array $d, ?int $eventId = null): array
    {
        $e = [];
        $pattern = $d['pattern'];
        if ($pattern === '' || mb_strlen($pattern) > 190) {
            $e['pattern'] = t('Pola nomor wajib diisi (maks. 190 karakter).');
            return $e;
        }
        $keys = Placeholders::keys($pattern);
        if (!array_intersect(self::SEQ_KEYS, $keys)) {
            $e['pattern'] = t('Pola wajib memuat {SEQUENCE}, {SEQUENCE_PADDED}, atau {NUMBER} agar setiap nomor unik.');
        } elseif (in_array('CERTIFICATE_NUMBER', $keys, true) || in_array('VERIFICATION_URL', $keys, true)) {
            $e['pattern'] = t('Pola tidak boleh memuat {CERTIFICATE_NUMBER} atau {VERIFICATION_URL}.');
        } elseif (!preg_match(self::LITERAL_RE, (string) preg_replace(Placeholders::RE, '', $pattern))) {
            $e['pattern'] = t('Teks literal hanya boleh berisi huruf, angka, spasi, dan tanda . / _ - ( ) # :');
        } elseif (preg_match('/\{[^}]*\}/', (string) preg_replace(Placeholders::RE, '', $pattern))) {
            $e['pattern'] = t('Ada placeholder yang penulisannya tidak valid. Gunakan huruf kapital, mis. {YEAR}.');
        } elseif ($eventId !== null) {
            $vars = CertVariable::effective($eventId);
            $unknown = array_filter($keys, static fn ($k) => !isset(Placeholders::BUILTIN[$k]) && !isset(Placeholders::ISSUE_KEYS[$k]) && !isset($vars[$k]));
            if ($unknown) {
                $e['pattern'] = t('Variabel tidak dikenal: :list.', ['list' => '{' . implode('}, {', array_unique($unknown)) . '}']);
            }
        }
        if ((int) $d['seq_padding'] < 1 || (int) $d['seq_padding'] > 8) {
            $e['seq_padding'] = t('Panjang nomor urut 1–8 digit.');
        }
        if (!isset(self::SCOPES[$d['seq_scope']])) {
            $e['seq_scope'] = t('Pilih cakupan nomor urut.');
        }
        if (!isset(self::RESETS[$d['seq_reset']])) {
            $e['seq_reset'] = t('Pilih aturan reset.');
        } elseif ($d['seq_reset'] === 'yearly' && !in_array('YEAR', $keys, true)) {
            $e['seq_reset'] = t('Reset tahunan wajib memakai {YEAR} di pola agar nomor tidak bentrok.');
        }
        if (mb_strlen($d['note']) > 255) {
            $e['note'] = t('Catatan maksimal 255 karakter.');
        }
        return $e;
    }

    public static function createDraft(int $eventId, array $d, int $by): int
    {
        $version = (int) DB::value('SELECT COALESCE(MAX(version), 0) FROM cert_numbering WHERE event_id = ?', [$eventId]) + 1;
        return DB::insert('cert_numbering', [
            'event_id' => $eventId, 'version' => $version, 'pattern' => $d['pattern'], 'seq_padding' => (int) $d['seq_padding'],
            'seq_scope' => $d['seq_scope'], 'seq_reset' => $d['seq_reset'], 'status' => 'draft',
            'note' => $d['note'] !== '' ? $d['note'] : null, 'created_by' => $by, 'created_at' => now(),
        ]);
    }

    public static function approve(array $n, int $by): void
    {
        DB::transaction(static function () use ($n, $by): void {
            DB::run("UPDATE cert_numbering SET status = 'retired' WHERE event_id = ? AND status = 'approved'", [(int) $n['event_id']]);
            DB::update('cert_numbering', ['status' => 'approved', 'approved_by' => $by, 'approved_at' => now()], 'id = ?', [(int) $n['id']]);
        });
    }

    public static function scopeKey(array $n, string $issueDate): string
    {
        $key = $n['seq_scope'] === 'global' ? 'global' : 'event:' . (int) $n['event_id'];
        if ($n['seq_reset'] === 'yearly') {
            $key .= ':y' . date('Y', strtotime($issueDate) ?: time());
        }
        return $key;
    }

    /** Nomor urut berikutnya TANPA memakainya (untuk pratinjau). */
    public static function peek(array $n, string $issueDate): int
    {
        return (int) DB::value('SELECT seq_value FROM cert_sequences WHERE scope_key = ?', [self::scopeKey($n, $issueDate)]) + 1;
    }

    /** Pastikan baris urutan ada. Dipanggil SEBELUM transaksi penerbitan dimulai. */
    public static function ensureSequence(array $n, string $issueDate): void
    {
        $verb = DB::driver() === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';
        DB::run($verb . ' INTO cert_sequences (scope_key, seq_value) VALUES (?, 0)', [self::scopeKey($n, $issueDate)]);
    }

    /**
     * Ambil nomor urut berikutnya. WAJIB dipanggil di dalam transaksi dan sebagai perintah tulis pertama:
     * UPDATE mengunci baris urutan sampai commit sehingga dua proses tidak bisa mendapat nomor yang sama.
     * Bila transaksi dibatalkan, kenaikan urutan ikut dibatalkan (nomor itu memang belum pernah terbit).
     */
    public static function allocate(array $n, string $issueDate): int
    {
        $key = self::scopeKey($n, $issueDate);
        if (DB::run('UPDATE cert_sequences SET seq_value = seq_value + 1 WHERE scope_key = ?', [$key])->rowCount() !== 1) {
            throw new \RuntimeException('Baris urutan nomor belum disiapkan.');
        }
        return (int) DB::value('SELECT seq_value FROM cert_sequences WHERE scope_key = ?', [$key]);
    }

    /** Susun nomor dari pola. $values berisi nilai variabel lain yang dipakai pola. */
    public static function format(array $n, int $sequence, array $values): string
    {
        $padded = str_pad((string) $sequence, (int) $n['seq_padding'], '0', STR_PAD_LEFT);
        $values = ['SEQUENCE' => (string) $sequence, 'SEQUENCE_PADDED' => $padded, 'NUMBER' => $padded] + $values;
        return trim((string) preg_replace('/\s+/', ' ', Placeholders::render((string) $n['pattern'], $values)));
    }
}
