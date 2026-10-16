<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;

final class Validator
{
    /** Normalisasi nomor HP: 62xxx → 08xxx, hapus spasi dan tanda hubung. */
    public static function normalizePhone(string $phone): string
    {
        $p = preg_replace('/[^0-9+]/', '', $phone) ?? '';
        if (str_starts_with($p, '+62')) {
            $p = '0' . substr($p, 3);
        } elseif (str_starts_with($p, '62')) {
            $p = '0' . substr($p, 2);
        }
        return $p;
    }

    /** Validasi 6 kolom wajib registrasi. */
    public static function registration(array $d, int $eventId, int $exceptId = 0): array
    {
        $e = [];
        if ($d['identity_number'] === '') {
            $e['identity_number'] = 'Nomor identitas wajib diisi.';
        } elseif (!preg_match('/^\d{8,18}$/', $d['identity_number'])) {
            $e['identity_number'] = 'Gunakan 8–18 digit angka tanpa spasi.';
        } elseif (Participant::identityTaken($eventId, $d['identity_number'], $exceptId)) {
            $e['identity_number'] = $exceptId
                ? 'Nomor identitas ini sudah dipakai peserta lain di event yang sama.'
                : 'Nomor identitas ini sudah terdaftar di event ini. Buka QR dari tautan yang Anda simpan atau hubungi panitia.';
        }
        if (!in_array($d['profession'], Participant::PROFESSIONS, true)) {
            $e['profession'] = 'Pilih profesi atau pekerjaan.';
        }
        if (mb_strlen($d['full_name']) < 3) {
            $e['full_name'] = 'Tulis nama lengkap, minimal 3 huruf.';
        } elseif (mb_strlen($d['full_name']) > 160) {
            $e['full_name'] = 'Nama terlalu panjang (maksimal 160 karakter).';
        }
        if ($d['phone'] === '') {
            $e['phone'] = 'Nomor WhatsApp wajib diisi.';
        } elseif (!preg_match('/^08\d{8,12}$/', $d['phone'])) {
            $e['phone'] = 'Format nomor belum benar. Contoh: 081234567890.';
        }
        if ($d['institution'] === '') {
            $e['institution'] = 'Isi nama institusi atau organisasi.';
        } elseif (mb_strlen($d['institution']) > 160) {
            $e['institution'] = 'Maksimal 160 karakter.';
        }
        if (!in_array($d['field_of_study'], Participant::FIELDS, true)) {
            $e['field_of_study'] = 'Pilih program studi atau bidang studi.';
        }
        return $e;
    }

    public static function event(array $d): array
    {
        $e = [];
        $time = '/^([01]\d|2[0-3]):[0-5]\d$/';
        $date = '/^\d{4}-\d{2}-\d{2}$/';
        if ($d['name'] === '') {
            $e['name'] = 'Nama event wajib diisi.';
        }
        if ($d['organizer'] === '') {
            $e['organizer'] = 'Penyelenggara wajib diisi.';
        }
        if ($d['location'] === '') {
            $e['location'] = 'Lokasi wajib diisi.';
        }
        if (!preg_match($date, $d['event_date'])) {
            $e['event_date'] = 'Pilih tanggal event.';
        }
        foreach (['start_time' => 'jam mulai', 'end_time' => 'jam selesai', 'att_start' => 'waktu mulai absensi', 'att_end' => 'waktu selesai absensi'] as $k => $label) {
            if (!preg_match($time, $d[$k])) {
                $e[$k] = 'Isi ' . $label . ' (format JJ:MM).';
            }
        }
        if (!isset($e['end_time']) && !isset($e['start_time']) && $d['end_time'] <= $d['start_time']) {
            $e['end_time'] = 'Harus setelah jam mulai.';
        }
        if (!isset($e['att_end']) && !isset($e['att_start']) && $d['att_end'] <= $d['att_start']) {
            $e['att_end'] = 'Harus setelah waktu mulai absensi.';
        }
        foreach (['reg_start', 'reg_end'] as $k) {
            if ($d[$k] !== null && !preg_match($date, $d[$k])) {
                $e[$k] = 'Format tanggal tidak valid.';
            }
        }
        if ($d['reg_start'] && $d['reg_end'] && $d['reg_end'] < $d['reg_start']) {
            $e['reg_end'] = 'Tanggal tutup harus setelah tanggal buka.';
        }
        if ($d['reg_end'] && !isset($e['event_date']) && $d['reg_end'] > $d['event_date']) {
            $e['reg_end'] = 'Registrasi harus ditutup paling lambat pada hari event.';
        }
        if (!in_array($d['status'], Event::STATUSES, true)) {
            $e['status'] = 'Pilih status event.';
        }
        if (!in_array($d['reg_status'], Event::REG_STATUSES, true)) {
            $e['reg_status'] = 'Pilih status registrasi.';
        }
        return $e;
    }

    public static function account(array $d, bool $isNew = true): array
    {
        $e = [];
        if (mb_strlen($d['name']) < 3) {
            $e['name'] = 'Nama petugas wajib diisi.';
        }
        if ($isNew) {
            if (!preg_match('/^[a-z0-9.]{4,40}$/', $d['username'])) {
                $e['username'] = 'Gunakan 4–40 karakter: huruf kecil, angka, atau titik.';
            } elseif (User::findByUsername($d['username'])) {
                $e['username'] = 'Username sudah dipakai akun lain.';
            }
            if (strlen($d['password']) < 8) {
                $e['password'] = 'Password sementara minimal 8 karakter.';
            }
        }
        if ($d['event_id'] && !Event::find((int) $d['event_id'])) {
            $e['event_id'] = 'Event tidak ditemukan.';
        }
        if (!in_array($d['status'], ['Aktif', 'Nonaktif'], true)) {
            $e['status'] = 'Pilih status akun.';
        }
        return $e;
    }

    public static function newPassword(string $password, string $confirm): ?string
    {
        if (strlen($password) < 10) {
            return 'Password baru minimal 10 karakter.';
        }
        if (!preg_match('/\d/', $password) || !preg_match('/[a-z]/i', $password)) {
            return 'Gunakan kombinasi huruf dan angka.';
        }
        if ($password !== $confirm) {
            return 'Konfirmasi password tidak sama.';
        }
        return null;
    }
}
