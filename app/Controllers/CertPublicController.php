<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database as DB;
use App\Core\RateLimiter;
use App\Models\AuditLog;
use App\Models\CertIssuance;
use App\Models\Event;

/**
 * Halaman publik tanpa login: cari sertifikat, unduh, dan verifikasi QR.
 * - Hanya sertifikat berstatus Published yang dapat dicari dan diunduh.
 * - Hasil pencarian dibatasi dan tidak memuat nomor identitas, nomor HP, atau kode tiket.
 * - Unduhan butuh konfirmasi 4 karakter terakhir nomor identitas atau kode tiket (pencegah tebak nama).
 */
final class CertPublicController extends Controller
{
    private const MAX_RESULTS = 10;

    /** Event yang memiliki sertifikat terpublikasi. */
    private function events(): array
    {
        if (!DB::tableExists('cert_issuances')) {
            return [];
        }
        return DB::all("SELECT e.id, e.name, e.event_date, e.slug FROM events e WHERE EXISTS (SELECT 1 FROM cert_issuances i WHERE i.event_id = e.id AND i.status = 'published') ORDER BY e.event_date DESC");
    }

    public function search(): void
    {
        $events = $this->events();
        $eventId = (int) query('event');
        $name = trim((string) preg_replace('/\s+/', ' ', mb_substr(query('nama'), 0, 120)));
        $event = null;
        $results = null;
        $error = '';
        $tooMany = false;
        if ($eventId || $name !== '') {
            $event = $eventId ? (array_values(array_filter($events, static fn ($e) => (int) $e['id'] === $eventId))[0] ?? null) : null;
            $key = 'certsearch:' . client_ip();
            if (!$event) {
                $error = t('Pilih event terlebih dahulu.');
            } elseif (mb_strlen($name) < 3) {
                $error = t('Tulis nama minimal 3 huruf.');
            } elseif (RateLimiter::tooMany($key, (int) config('cert.search_per_10min', 30), 600)) {
                http_response_code(429);
                $error = t('Terlalu banyak pencarian. Tunggu beberapa menit lalu coba lagi.');
            } else {
                RateLimiter::hit($key);
                // '!' sebagai karakter escape karena backslash diperlakukan berbeda oleh MySQL dan SQLite
                $like = '%' . strtr($name, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
                $rows = DB::all(
                    "SELECT i.id, p.full_name, p.institution FROM cert_issuances i JOIN participants p ON p.id = i.participant_id
                     WHERE i.event_id = ? AND i.status = 'published' AND p.full_name LIKE ? ESCAPE '!' ORDER BY p.full_name LIMIT " . (self::MAX_RESULTS + 1),
                    [$eventId, $like]
                );
                $tooMany = count($rows) > self::MAX_RESULTS;
                $results = $tooMany ? [] : $rows;
            }
        }
        $this->render('public/cert_search', [
            'title' => t('Cari Sertifikat'), 'events' => $events, 'event' => $event, 'eventId' => $eventId, 'name' => $name,
            'results' => $results, 'error' => $error, 'tooMany' => $tooMany, 'flashError' => $_SESSION['cert_dl_error'] ?? null,
        ], 'public');
        unset($_SESSION['cert_dl_error']);
    }

    /** Samarkan institusi: tampilkan cukup untuk membedakan nama yang sama tanpa membuka data lengkap. */
    public static function maskInstitution(string $s): string
    {
        $words = preg_split('/\s+/', trim($s)) ?: [];
        return implode(' ', array_map(static fn ($w) => mb_strlen($w) <= 3 ? $w : mb_substr($w, 0, 3) . str_repeat('•', min(5, mb_strlen($w) - 3)), $words));
    }

    public function download(): void
    {
        $id = (int) input('ref');
        $eventId = (int) input('event');
        $check = strtoupper(preg_replace('/\s+/', '', input('check')) ?? '');
        $ipKey = 'certdl:' . client_ip();
        $refKey = 'certdl-ref:' . $id;
        $back = ['event' => $eventId, 'nama' => input('nama')];
        if (RateLimiter::tooMany($ipKey, (int) config('cert.download_fail_per_15min', 8), 900) || RateLimiter::tooMany($refKey, 10, 3600)) {
            $_SESSION['cert_dl_error'] = [$id, t('Terlalu banyak percobaan. Tunggu 15 menit lalu coba lagi.')];
            redirect('sertifikat', $back);
        }
        $i = $id > 0 ? CertIssuance::find($id) : null;
        // Sertifikat harus Published dan milik event yang dipilih; selain itu diperlakukan sama dengan "tidak ditemukan"
        $valid = $i && $i['status'] === 'published' && (int) $i['event_id'] === $eventId;
        $ok = false;
        if ($valid && strlen($check) >= 4) {
            $identity = strtoupper((string) $i['identity_number']);
            $ok = hash_equals(substr($identity, -4), substr($check, -4)) && strlen($check) === 4
                || hash_equals(strtoupper((string) $i['participant_code']), $check);
        }
        if (!$ok) {
            RateLimiter::hit($ipKey);
            RateLimiter::hit($refKey);
            AuditLog::add(null, 'cert.public.verify_failed', $valid ? (int) $i['event_id'] : null, 'certificate', $id ?: null, [], 'denied');
            $_SESSION['cert_dl_error'] = [$id, t('Data konfirmasi tidak cocok. Masukkan 4 karakter terakhir nomor identitas (NIM/NIK) atau kode tiket QR Anda.')];
            redirect('sertifikat', $back);
        }
        AuditLog::add(null, 'cert.public.download', (int) $i['event_id'], 'certificate', $i['id']);
        CertIssuance::stream($i);
    }

    public function verify(string $token): void
    {
        $key = 'certverify:' . client_ip();
        if (RateLimiter::tooMany($key, 60, 600)) {
            abort(429, 'Terlalu banyak permintaan', 'Tunggu beberapa menit lalu coba lagi.');
        }
        RateLimiter::hit($key);
        $i = preg_match('/^[a-f0-9]{32}$/', $token) && DB::tableExists('cert_issuances') ? CertIssuance::findByToken($token) : null;
        // Sertifikat yang belum pernah dipublikasikan tidak boleh tampil sebagai sertifikat apa pun bagi publik
        if ($i && $i['published_at'] === null) {
            $i = null;
        }
        $this->render('public/cert_verify', [
            'title' => t('Verifikasi Sertifikat'),
            'i' => $i,
            'event' => $i ? Event::find((int) $i['event_id']) : null,
        ], 'public');
    }
}
