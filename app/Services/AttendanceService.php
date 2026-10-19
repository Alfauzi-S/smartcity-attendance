<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;
use App\Models\Participant;
use App\Models\StatusHistory;

/**
 * Aturan inti presensi:
 * - QR terbaca belum berarti hadir; harus divalidasi lalu disimpan.
 * - Scan hanya mengubah "belum" menjadi "hadir" (update atomik, tidak ada catatan ganda).
 * - Status "tidak" yang ditetapkan Super Admin tidak bisa ditimpa oleh scan.
 * - Perubahan manual hanya oleh Super Admin dan selalu dicatat di riwayat.
 */
final class AttendanceService
{
    /** @return array{state:string, participant?:array, message?:string} */
    public static function validate(array $event, string $raw): array
    {
        $code = Participant::extractCode($raw);
        if ($code === null) {
            return ['state' => 'invalid'];
        }
        $p = Participant::findByCode($code);
        if (!$p || (int) $p['event_id'] !== (int) $event['id']) {
            return ['state' => 'notfound', 'code' => $code];
        }
        if ($event['att_status'] === 'notyet') {
            return ['state' => 'notyet'];
        }
        if ($event['att_status'] === 'closed') {
            return ['state' => 'closed'];
        }
        if ($p['status'] === 'hadir') {
            return ['state' => 'dup', 'participant' => $p];
        }
        if ($p['status'] === 'tidak') {
            return ['state' => 'locked', 'participant' => $p];
        }
        return ['state' => 'valid', 'participant' => $p];
    }

    /**
     * Simpan kehadiran oleh petugas.
     * $method: 'scan' (kamera) atau 'cari' (kode diketik / pencarian nama).
     */
    public static function checkIn(array $event, int $participantId, array $user, string $method = 'scan'): array
    {
        $method = $method === 'cari' ? 'cari' : 'scan';
        if ($event['att_status'] !== 'open') {
            return ['state' => $event['att_status'] === 'closed' ? 'closed' : 'notyet'];
        }
        $changed = DB::run(
            "UPDATE participants SET status = 'hadir', checked_in_at = ?, checked_in_by = ?, method = ?, updated_at = ?
             WHERE id = ? AND event_id = ? AND status = 'belum'",
            [now(), (int) $user['id'], $method, now(), $participantId, (int) $event['id']]
        )->rowCount();

        $p = Participant::find($participantId);
        if (!$p || (int) $p['event_id'] !== (int) $event['id']) {
            return ['state' => 'notfound'];
        }
        if ($changed === 1) {
            return ['state' => 'success', 'participant' => $p];
        }
        return ['state' => $p['status'] === 'hadir' ? 'dup' : 'locked', 'participant' => $p];
    }

    /**
     * Ubah status manual (khusus Super Admin).
     * @return array{ok:bool, message:string, old?:string}
     */
    public static function changeStatus(int $participantId, string $new, string $reason, array $super): array
    {
        if (!in_array($new, ['hadir', 'tidak'], true)) {
            return ['ok' => false, 'message' => t('Pilih status Hadir atau Tidak Hadir.')];
        }
        return DB::transaction(static function () use ($participantId, $new, $reason, $super): array {
            $p = DB::one('SELECT id, status FROM participants WHERE id = ?', [$participantId]);
            if (!$p) {
                return ['ok' => false, 'message' => t('Peserta tidak ditemukan.')];
            }
            $old = $p['status'];
            if ($old === $new) {
                return ['ok' => false, 'message' => t('Status peserta sudah :status.', ['status' => status_label($new)])];
            }
            $data = $new === 'hadir'
                ? ['status' => 'hadir', 'checked_in_at' => now(), 'checked_in_by' => (int) $super['id'], 'method' => 'manual', 'updated_at' => now()]
                : ['status' => 'tidak', 'checked_in_at' => null, 'checked_in_by' => (int) $super['id'], 'method' => 'manual', 'updated_at' => now()];
            $n = DB::update('participants', $data, 'id = ? AND status = ?', [$participantId, $old]);
            if ($n !== 1) {
                return ['ok' => false, 'message' => t('Status baru saja diubah oleh proses lain. Muat ulang lalu coba lagi.')];
            }
            StatusHistory::add($participantId, $old, $new, $reason, $super);
            return ['ok' => true, 'message' => status_label($old) . ' → ' . status_label($new), 'old' => $old];
        });
    }

    /** Data peserta yang aman dikirim ke scanner (tanpa nomor HP). */
    public static function publicParticipant(array $p): array
    {
        return [
            'id' => (int) $p['id'],
            'name' => $p['full_name'],
            'identity' => $p['identity_number'],
            'field' => t($p['field_of_study']),
            'institution' => $p['institution'],
            'code' => $p['code'],
            'status' => $p['status'],
            'status_label' => status_label($p['status']),
            'checked_in_at' => $p['checked_in_at'] ? time_only($p['checked_in_at']) : null,
            'checked_in_by' => $p['checked_in_by_name'] ?? null,
        ];
    }
}
