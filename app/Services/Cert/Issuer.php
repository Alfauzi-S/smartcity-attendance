<?php
declare(strict_types=1);

namespace App\Services\Cert;

use App\Core\Database as DB;
use App\Models\AuditLog;
use App\Models\CertAsset;
use App\Models\CertIssuance;
use App\Models\CertNumbering;
use App\Models\CertTemplate;
use App\Models\CertVariable;
use App\Models\Participant;

/**
 * Alur penerbitan: validasi kesiapan → generate (nomor final + PDF snapshot) → review → publish.
 * Generate tidak sama dengan publish: sertifikat baru berstatus 'generated' dan tidak terlihat publik.
 */
final class Issuer
{
    public const ELIGIBILITY = [
        'hadir' => 'Hanya peserta berstatus Hadir (disarankan)',
        'tidak_absen' => 'Peserta terdaftar yang tidak ditetapkan Tidak Hadir',
        'semua' => 'Seluruh peserta terdaftar (hanya dapat diatur Super Admin)',
    ];

    /* ---------- pengaturan & kelayakan ---------- */

    public static function settings(int $eventId): array
    {
        return DB::one('SELECT * FROM cert_settings WHERE event_id = ?', [$eventId])
            ?? ['event_id' => $eventId, 'template_id' => null, 'eligibility' => 'hadir', 'require_approved_number' => 1, 'issue_date' => null];
    }

    public static function saveSettings(int $eventId, array $data, int $by): void
    {
        $data += ['updated_by' => $by, 'updated_at' => now()];
        if (DB::value('SELECT event_id FROM cert_settings WHERE event_id = ?', [$eventId])) {
            DB::update('cert_settings', $data, 'event_id = ?', [$eventId]);
        } else {
            DB::insert('cert_settings', $data + ['event_id' => $eventId]);
        }
    }

    public static function issueDate(array $event, array $settings): string
    {
        return $settings['issue_date'] ?: $event['event_date'];
    }

    /** @return string|null alasan tidak layak, null bila layak */
    public static function ineligibleReason(array $p, array $settings): ?string
    {
        return match ($settings['eligibility']) {
            'semua' => null,
            'tidak_absen' => $p['status'] === 'tidak' ? 'Ditetapkan Tidak Hadir' : null,
            default => $p['status'] === 'hadir' ? null : ($p['status'] === 'tidak' ? 'Ditetapkan Tidak Hadir' : 'Belum hadir / belum diverifikasi'),
        };
    }

    private static function eligibleWhere(array $settings): string
    {
        return match ($settings['eligibility']) {
            'semua' => '1 = 1',
            'tidak_absen' => "p.status <> 'tidak'",
            default => "p.status = 'hadir'",
        };
    }

    /** Ringkasan sebelum generate. */
    public static function summary(int $eventId, array $settings): array
    {
        $total = (int) DB::value('SELECT COUNT(*) FROM participants WHERE event_id = ?', [$eventId]);
        $eligible = (int) DB::value('SELECT COUNT(*) FROM participants p WHERE p.event_id = ? AND ' . self::eligibleWhere($settings), [$eventId]);
        $issued = (int) DB::value(
            "SELECT COUNT(*) FROM participants p WHERE p.event_id = ? AND " . self::eligibleWhere($settings) .
            " AND EXISTS (SELECT 1 FROM cert_issuances i WHERE i.participant_id = p.id AND i.status <> 'revoked')",
            [$eventId]
        );
        $reasons = [];
        foreach (DB::all('SELECT status, COUNT(*) AS n FROM participants p WHERE p.event_id = ? AND NOT (' . self::eligibleWhere($settings) . ') GROUP BY status', [$eventId]) as $r) {
            $reason = self::ineligibleReason(['status' => $r['status']], $settings) ?? '—';
            $reasons[$reason] = ($reasons[$reason] ?? 0) + (int) $r['n'];
        }
        return ['total' => $total, 'eligible' => $eligible, 'ineligible' => $total - $eligible, 'issued' => $issued, 'to_generate' => $eligible - $issued, 'reasons' => $reasons];
    }

    /** ID peserta layak yang belum punya sertifikat aktif. */
    public static function pendingIds(int $eventId, array $settings): array
    {
        return array_map('intval', array_column(DB::all(
            'SELECT p.id FROM participants p WHERE p.event_id = ? AND ' . self::eligibleWhere($settings) .
            " AND NOT EXISTS (SELECT 1 FROM cert_issuances i WHERE i.participant_id = p.id AND i.status <> 'revoked') ORDER BY p.full_name, p.id",
            [$eventId]
        ), 'id'));
    }

    /** Daftar peserta + kelayakan + sertifikat aktif (untuk ditinjau sebelum generate). */
    public static function recipients(int $eventId, array $settings, string $filter, string $q, int $limit, int $offset): array
    {
        $w = 'p.event_id = ?';
        $params = [$eventId];
        if ($filter === 'layak') {
            $w .= ' AND ' . self::eligibleWhere($settings);
        } elseif ($filter === 'tidak') {
            $w .= ' AND NOT (' . self::eligibleWhere($settings) . ')';
        } elseif ($filter === 'belum') {
            $w .= ' AND ' . self::eligibleWhere($settings) . " AND NOT EXISTS (SELECT 1 FROM cert_issuances x WHERE x.participant_id = p.id AND x.status <> 'revoked')";
        }
        if ($q !== '') {
            $w .= ' AND (p.full_name LIKE ? OR p.identity_number LIKE ?)';
            array_push($params, '%' . $q . '%', '%' . $q . '%');
        }
        $rows = DB::all(
            "SELECT p.*, (SELECT i.id FROM cert_issuances i WHERE i.participant_id = p.id AND i.status <> 'revoked' ORDER BY i.id DESC LIMIT 1) AS cert_id,
                    (SELECT i.status FROM cert_issuances i WHERE i.participant_id = p.id AND i.status <> 'revoked' ORDER BY i.id DESC LIMIT 1) AS cert_status
             FROM participants p WHERE {$w} ORDER BY p.full_name LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
        $count = (int) DB::value("SELECT COUNT(*) FROM participants p WHERE {$w}", $params);
        foreach ($rows as &$r) {
            $r['reason'] = self::ineligibleReason($r, $settings);
        }
        return [$rows, $count];
    }

    /* ---------- validasi kesiapan ---------- */

    /** Semua teks yang dipakai template + pola nomor. */
    private static function usedKeys(array $snap, ?array $numbering): array
    {
        $keys = [];
        foreach ($snap['elements'] as $el) {
            if ($el['type'] === 'text') {
                $keys = array_merge($keys, Placeholders::keys((string) $el['content']));
            }
        }
        if ($numbering) {
            $keys = array_merge($keys, Placeholders::keys((string) $numbering['pattern']));
        }
        return array_values(array_unique(array_diff($keys, CertNumbering::SEQ_KEYS)));
    }

    /** Masalah pada elemen gambar/QR template. @return array<int,array{0:string,1:string}> */
    public static function elementIssues(array $snap, int $eventId, bool $useSnapshotAssets): array
    {
        $issues = [];
        $count = ['signature' => 0, 'stamp' => 0];
        $hasQr = false;
        foreach ($snap['elements'] as $el) {
            if ($el['type'] === 'qr') {
                $hasQr = true;
            }
            if ($el['type'] !== 'image') {
                continue;
            }
            $label = $el['label'] ?: t(CertAsset::KINDS[$el['slot']] ?? 'Gambar');
            if (isset($count[$el['slot']])) {
                $count[$el['slot']]++;
            }
            if (!$el['asset_id']) {
                $issues[] = ['bad', t('Slot ":label" belum diisi aset resmi. Unggah aset atau hapus slot ini.', ['label' => $label])];
                continue;
            }
            $asset = $useSnapshotAssets ? ($snap['assets'][$el['asset_id']] ?? null) : CertAsset::find((int) $el['asset_id']);
            if (!$asset || (!$useSnapshotAssets && (!CertAsset::usableBy($asset, $eventId) || (int) $asset['archived']))) {
                $issues[] = ['bad', t('Aset untuk ":label" tidak tersedia atau sudah diarsipkan.', ['label' => $label])];
            } elseif (in_array($asset['kind'], CertAsset::NEEDS_APPROVAL, true) && !(int) $asset['approved']) {
                $issues[] = ['bad', t('Aset ":label" belum disetujui Super Admin.', ['label' => $label])];
            } elseif (!Upload::abs($useSnapshotAssets ? $asset['path'] : $asset['file_path'])) {
                $issues[] = ['bad', t('File aset ":label" tidak ditemukan di server.', ['label' => $label])];
            }
        }
        if ($count['signature'] > 1) {
            $issues[] = ['bad', t('Template memuat lebih dari satu tanda tangan pejabat.')];
        }
        if ($count['stamp'] > 1) {
            $issues[] = ['bad', t('Template memuat lebih dari satu stempel resmi.')];
        }
        if (!$hasQr) {
            $issues[] = ['warn', t('Template belum memuat QR verifikasi. Sertifikat tetap bisa diverifikasi lewat nomor, tetapi QR sangat disarankan.')];
        }
        if (empty($snap['background'])) {
            $issues[] = ['warn', t('Template belum memiliki desain latar. Sertifikat akan berlatar putih.')];
        } elseif (!Upload::abs($snap['background']['path'])) {
            $issues[] = ['bad', t('File desain latar template tidak ditemukan di server.')];
        }
        return $issues;
    }

    /**
     * Laporan kesiapan event untuk generate final.
     * @return array{ok:bool, checks:array, template:?array, version:?array, snap:?array, numbering:?array, settings:array, vars:array}
     */
    public static function readiness(array $event): array
    {
        $eventId = (int) $event['id'];
        $settings = self::settings($eventId);
        $checks = [];
        $template = CertTemplate::active($eventId);
        $version = $template ? CertTemplate::latestVersion((int) $template['id']) : null;
        $snap = $version ? json_decode($version['snapshot'], true) : null;
        if (!$template || !$snap) {
            $checks[] = ['bad', t('Belum ada template berstatus Active. Buat template, lengkapi, lalu aktifkan.')];
        } else {
            $checks[] = ['ok', t('Template aktif: :name (versi :v).', ['name' => $template['name'], 'v' => $version['version']])];
            foreach (self::elementIssues($snap, $eventId, true) as $issue) {
                $checks[] = $issue;
            }
        }
        $requireApproved = (int) $settings['require_approved_number'] === 1;
        $numbering = CertNumbering::forIssuing($eventId, $requireApproved);
        $latest = CertNumbering::latest($eventId);
        if (!$latest) {
            $checks[] = ['bad', t('Pola nomor sertifikat belum dibuat.')];
        } elseif (!$numbering) {
            $checks[] = ['bad', t('Pola nomor masih Draft dan belum disetujui Super Admin. Generate final diblokir karena persetujuan diwajibkan.')];
        } else {
            $checks[] = [$numbering['status'] === 'approved' ? 'ok' : 'warn', $numbering['status'] === 'approved'
                ? t('Pola nomor disetujui (versi :v): :p', ['v' => $numbering['version'], 'p' => $numbering['pattern']])
                : t('Pola nomor versi :v belum disetujui; persetujuan tidak diwajibkan untuk event ini.', ['v' => $numbering['version']])];
            if ((int) $latest['id'] !== (int) $numbering['id']) {
                $checks[] = ['warn', t('Ada pola nomor versi :v yang masih Draft. Generate memakai versi yang sudah disetujui.', ['v' => $latest['version']])];
            }
        }
        $vars = CertVariable::effective($eventId);
        if ($snap) {
            [, $errors] = Placeholders::resolve(self::usedKeys($snap, $numbering), $event, null, $vars, self::issueDate($event, $settings), [], true);
            foreach ($errors as $msg) {
                $checks[] = ['bad', $msg];
            }
        }
        $ok = !array_filter($checks, static fn ($c) => $c[0] === 'bad');
        return compact('ok', 'checks', 'template', 'version', 'snap', 'numbering', 'settings', 'vars');
    }

    /* ---------- pratinjau ---------- */

    /** PDF pratinjau berwatermark. Tidak mengambil nomor urut dan tidak menyimpan apa pun. */
    public static function preview(array $event, array $template, ?array $participant): array
    {
        $eventId = (int) $event['id'];
        $settings = self::settings($eventId);
        $snap = CertTemplate::snapshot($template);
        $numbering = CertNumbering::latest($eventId);
        $issueDate = self::issueDate($event, $settings);
        $sample = $participant ?? ['full_name' => 'Ayu Ṣalsabila Nur’aini, S.Kom.', 'identity_number' => '2309106000', 'institution' => 'Universitas Mulawarman',
            'field_of_study' => 'Informatika', 'profession' => 'Mahasiswa S1', 'code' => 'SCCONTOH1', 'status' => 'hadir'];
        $vars = CertVariable::effective($eventId);
        [$values, $errors] = Placeholders::resolve(self::usedKeys($snap, $numbering), $event, $sample, $vars, $issueDate, [], true);
        $values += Placeholders::dateTokens($issueDate);
        $values['CERTIFICATE_NUMBER'] = $numbering
            ? CertNumbering::format($numbering, CertNumbering::peek($numbering, $issueDate), $values) . ' (CONTOH)'
            : 'BELUM ADA POLA NOMOR (CONTOH)';
        $values['VERIFICATION_URL'] = full_url('v/pratinjau');
        $pdf = Renderer::pdf($snap, $values, full_url('v/pratinjau'), true, 'Pratinjau sertifikat');
        return [$pdf, $errors];
    }

    /* ---------- generate ---------- */

    /**
     * Terbitkan satu sertifikat. Nomor urut dialokasikan di transaksi yang sama dengan penyimpanan record,
     * sehingga gagal = tidak ada nomor terpakai dan tidak ada record setengah jadi.
     * @param array|null $replace penerbitan lama yang dicabut & digantikan (terbit ulang)
     */
    public static function issue(array $event, array $participant, array $user, array $ready, ?int $jobId = null, ?array $replace = null, string $reason = ''): array
    {
        $eventId = (int) $event['id'];
        if (!$ready['ok']) {
            throw new CertError(t('Konfigurasi sertifikat belum lengkap. Periksa laporan validasi.'));
        }
        if ((int) $participant['event_id'] !== $eventId) {
            throw new CertError(t('Peserta bukan bagian dari event ini.'));
        }
        if ($why = self::ineligibleReason($participant, $ready['settings'])) {
            throw new CertError(t('Tidak memenuhi syarat: :reason.', ['reason' => t($why)]));
        }
        $snap = $ready['snap'];
        $numbering = $ready['numbering'];
        $issueDate = self::issueDate($event, $ready['settings']);
        [$values, $errors] = Placeholders::resolve(self::usedKeys($snap, $numbering), $event, $participant, $ready['vars'], $issueDate, [], true);
        if ($errors) {
            throw new CertError(implode(' ', $errors));
        }
        $values += Placeholders::dateTokens($issueDate);
        CertNumbering::ensureSequence($numbering, $issueDate);

        $token = bin2hex(random_bytes(16));
        $rel = 'certificates/' . $eventId . '/' . $token . '.pdf';
        $abs = STORAGE_PATH . '/' . $rel;
        if (!is_dir(dirname($abs)) && !mkdir(dirname($abs), 0775, true) && !is_dir(dirname($abs))) {
            throw new CertError(t('Folder penyimpanan sertifikat tidak dapat dibuat. Periksa izin folder storage.'));
        }
        try {
            return DB::transaction(static function () use ($event, $participant, $user, $ready, $jobId, $replace, $reason, $numbering, $snap, $values, $issueDate, $token, $rel, $abs, $eventId): array {
                $seq = CertNumbering::allocate($numbering, $issueDate); // perintah tulis pertama: mengunci urutan
                $number = CertNumbering::format($numbering, $seq, $values);
                if ($number === '' || mb_strlen($number) > 190) {
                    throw new CertError(t('Nomor sertifikat hasil pola tidak valid.'));
                }
                if (DB::value('SELECT id FROM cert_issuances WHERE cert_number = ?', [$number])) {
                    throw new CertError(t('Nomor :n sudah dipakai sertifikat lain. Tambahkan {EVENT_CODE} atau {EVENT_ID} ke pola, atau gunakan urutan global.', ['n' => $number]));
                }
                if ($replace) {
                    $n = DB::run(
                        "UPDATE cert_issuances SET status = 'revoked', active_slot = NULL, revoked_by = ?, revoked_at = ?, revoke_reason = ? WHERE id = ? AND status <> 'revoked'",
                        [(int) $user['id'], now(), mb_substr($reason, 0, 255), (int) $replace['id']]
                    )->rowCount();
                    if ($n !== 1) {
                        throw new CertError(t('Sertifikat lama sudah berubah status. Muat ulang halaman.'));
                    }
                } elseif (DB::value("SELECT id FROM cert_issuances WHERE participant_id = ? AND status <> 'revoked'", [(int) $participant['id']])) {
                    throw new CertError(t('Peserta sudah memiliki sertifikat aktif.'));
                }
                $values['CERTIFICATE_NUMBER'] = $number;
                $values['VERIFICATION_URL'] = full_url('v/' . $token);
                $values['SEQUENCE'] = (string) $seq;
                $pdf = Renderer::pdf($snap, $values, full_url('v/' . $token), false, 'Sertifikat ' . $number);
                if (file_put_contents($abs, $pdf, LOCK_EX) === false) {
                    throw new CertError(t('File PDF gagal disimpan.'));
                }
                $id = DB::insert('cert_issuances', [
                    'event_id' => $eventId, 'participant_id' => (int) $participant['id'],
                    'template_version_id' => (int) $ready['version']['id'], 'numbering_id' => (int) $numbering['id'],
                    'sequence' => $seq, 'cert_number' => $number, 'token' => $token, 'status' => 'generated',
                    'active_slot' => (int) $participant['id'], 'file_path' => $rel, 'file_hash' => hash('sha256', $pdf),
                    'data_snapshot' => json_encode($values, JSON_UNESCAPED_UNICODE), 'issue_date' => $issueDate, 'job_id' => $jobId,
                    'replaces_id' => $replace ? (int) $replace['id'] : null, 'generated_by' => (int) $user['id'], 'generated_at' => now(),
                ]);
                return ['id' => $id, 'cert_number' => $number];
            });
        } catch (\Throwable $e) {
            if (is_file($abs)) {
                @unlink($abs);
            }
            if ($e instanceof \PDOException && (str_contains($e->getMessage(), 'active_slot') || str_contains($e->getMessage(), 'uq_cert_active'))) {
                throw new CertError(t('Sertifikat untuk peserta ini baru saja dibuat oleh proses lain.'));
            }
            if ($e instanceof \PDOException && (str_contains($e->getMessage(), 'cert_number') || str_contains($e->getMessage(), 'uq_cert_number'))) {
                throw new CertError(t('Nomor sertifikat bentrok dengan sertifikat lain. Coba lagi.'));
            }
            throw $e;
        }
    }

    /* ---------- job massal ---------- */

    public static function createJob(array $event, array $ids, array $ready, array $user): int
    {
        return DB::insert('cert_jobs', [
            'event_id' => (int) $event['id'], 'template_version_id' => (int) $ready['version']['id'], 'numbering_id' => (int) $ready['numbering']['id'],
            'status' => 'queued', 'participant_ids' => json_encode(array_values($ids)), 'total' => count($ids), 'cursor_pos' => 0,
            'success' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => '[]', 'created_by' => (int) $user['id'], 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public static function job(int $id): ?array
    {
        return DB::one('SELECT j.*, u.username AS created_by_name FROM cert_jobs j LEFT JOIN users u ON u.id = j.created_by WHERE j.id = ?', [$id]);
    }

    public static function jobs(int $eventId, int $limit = 10): array
    {
        return DB::all('SELECT j.*, u.username AS created_by_name FROM cert_jobs j LEFT JOIN users u ON u.id = j.created_by WHERE j.event_id = ? ORDER BY j.id DESC LIMIT ' . $limit, [$eventId]);
    }

    /**
     * Proses satu potong job. Kunci (locked_until) mencegah dua tab memproses job yang sama bersamaan;
     * peserta yang sudah punya sertifikat aktif dilewati sehingga retry tidak pernah menggandakan penerbitan.
     */
    public static function step(array $job, array $event, array $user): array
    {
        $now = date('Y-m-d H:i:s');
        $locked = DB::run(
            "UPDATE cert_jobs SET locked_until = ?, status = 'running', updated_at = ? WHERE id = ? AND status IN ('queued', 'running') AND (locked_until IS NULL OR locked_until < ?)",
            [date('Y-m-d H:i:s', time() + 90), $now, (int) $job['id'], $now]
        )->rowCount();
        if ($locked !== 1) {
            return ['state' => in_array($job['status'], ['done', 'cancelled'], true) ? $job['status'] : 'busy'];
        }
        $job = self::job((int) $job['id']);
        $ready = self::readiness($event);
        if (!$ready['ok'] || (int) $ready['version']['id'] !== (int) $job['template_version_id'] || (int) $ready['numbering']['id'] !== (int) $job['numbering_id']) {
            DB::update('cert_jobs', ['locked_until' => null, 'status' => 'paused', 'updated_at' => now()], 'id = ?', [(int) $job['id']]);
            return ['state' => 'paused', 'message' => t('Konfigurasi sertifikat berubah atau belum lengkap. Periksa laporan validasi, lalu mulai generate baru.')];
        }
        $ids = json_decode($job['participant_ids'], true) ?: [];
        $errors = json_decode((string) $job['errors'], true) ?: [];
        $cursor = (int) $job['cursor_pos'];
        $end = min(count($ids), $cursor + max(1, (int) config('cert.batch_size', 10)));
        $c = ['success' => (int) $job['success'], 'skipped' => (int) $job['skipped'], 'failed' => (int) $job['failed']];
        @set_time_limit(120);
        for (; $cursor < $end; $cursor++) {
            $pid = (int) $ids[$cursor];
            $p = Participant::find($pid);
            try {
                if (!$p || (int) $p['event_id'] !== (int) $event['id']) {
                    throw new CertError(t('Peserta tidak ditemukan di event ini.'));
                }
                if (CertIssuance::active($pid)) {
                    $c['skipped']++;
                } else {
                    $r = self::issue($event, $p, $user, $ready, (int) $job['id']);
                    $c['success']++;
                    AuditLog::add($user, 'cert.generate', (int) $event['id'], 'certificate', $r['id'], ['number' => $r['cert_number'], 'job' => (int) $job['id']]);
                }
            } catch (\Throwable $e) {
                $c['failed']++;
                $msg = $e instanceof CertError ? $e->getMessage() : t('Kesalahan server saat membuat PDF.');
                if (!$e instanceof CertError) {
                    error_log((string) $e);
                }
                if (count($errors) < 500) {
                    $errors[] = ['pid' => $pid, 'name' => $p['full_name'] ?? ('#' . $pid), 'message' => $msg];
                }
                AuditLog::add($user, 'cert.generate.failed', (int) $event['id'], 'participant', $pid, ['job' => (int) $job['id'], 'message' => $msg], 'failed');
            }
            DB::update('cert_jobs', $c + ['cursor_pos' => $cursor + 1, 'errors' => json_encode($errors, JSON_UNESCAPED_UNICODE), 'updated_at' => now()], 'id = ?', [(int) $job['id']]);
        }
        $done = $cursor >= count($ids);
        DB::update('cert_jobs', ['locked_until' => null, 'status' => $done ? 'done' : 'running', 'finished_at' => $done ? now() : null, 'updated_at' => now()], 'id = ?', [(int) $job['id']]);
        if ($done) {
            AuditLog::add($user, 'cert.job.done', (int) $event['id'], 'job', $job['id'], $c + ['total' => count($ids)]);
        }
        return ['state' => $done ? 'done' : 'running'] + $c + ['cursor' => $cursor, 'total' => count($ids), 'errors' => array_slice($errors, -20)];
    }

    /* ---------- publikasi, pencabutan, terbit ulang ---------- */

    /** @return string|null alasan publikasi diblokir */
    public static function publishBlock(array $i, array $settings): ?string
    {
        if ($i['status'] !== 'generated') {
            return t('Hanya sertifikat berstatus Generated yang bisa dipublikasikan.');
        }
        if ((int) $settings['require_approved_number'] === 1 && !in_array($i['numbering_status'], ['approved', 'retired'], true)) {
            return t('Nomor sertifikat memakai pola yang belum disetujui. Publikasi final diblokir.');
        }
        return null;
    }

    public static function publish(array $i, array $user): bool
    {
        return DB::run(
            "UPDATE cert_issuances SET status = 'published', published_by = ?, published_at = ? WHERE id = ? AND status = 'generated'",
            [(int) $user['id'], now(), (int) $i['id']]
        )->rowCount() === 1;
    }

    public static function revoke(array $i, string $reason, array $user): bool
    {
        return DB::run(
            "UPDATE cert_issuances SET status = 'revoked', active_slot = NULL, revoked_by = ?, revoked_at = ?, revoke_reason = ? WHERE id = ? AND status <> 'revoked'",
            [(int) $user['id'], now(), mb_substr($reason, 0, 255), (int) $i['id']]
        )->rowCount() === 1;
    }
}
