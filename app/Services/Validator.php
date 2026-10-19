<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\Participant;
use App\Models\ParticipantOptions;
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
            $e['identity_number'] = t('Nomor identitas wajib diisi.');
        } elseif (!preg_match('/^\d{8,18}$/', $d['identity_number'])) {
            $e['identity_number'] = t('Gunakan 8–18 digit angka tanpa spasi.');
        } elseif (Participant::identityTaken($eventId, $d['identity_number'], $exceptId)) {
            $e['identity_number'] = $exceptId
                ? t('Nomor identitas ini sudah dipakai peserta lain di event yang sama.')
                : t('Nomor identitas ini sudah terdaftar di event ini. Buka QR dari tautan yang Anda simpan atau hubungi panitia.');
        }
        if (!ParticipantOptions::valid($d['profession'], ParticipantOptions::professions(), ParticipantOptions::PROFESSION_MAX)) {
            $e['profession'] = t('Pilih profesi atau pekerjaan.');
        }
        if (mb_strlen($d['full_name']) < 3) {
            $e['full_name'] = t('Tulis nama lengkap, minimal 3 huruf.');
        } elseif (mb_strlen($d['full_name']) > 160) {
            $e['full_name'] = t('Nama terlalu panjang (maksimal 160 karakter).');
        }
        if ($d['phone'] === '') {
            $e['phone'] = t('Nomor WhatsApp wajib diisi.');
        } elseif (!preg_match('/^08\d{8,12}$/', $d['phone'])) {
            $e['phone'] = t('Format nomor belum benar. Contoh: 081234567890.');
        }
        if ($d['institution'] === '') {
            $e['institution'] = t('Isi nama institusi atau organisasi.');
        } elseif (mb_strlen($d['institution']) > 160) {
            $e['institution'] = t('Maksimal 160 karakter.');
        }
        if (!ParticipantOptions::valid($d['field_of_study'], ParticipantOptions::fields(), ParticipantOptions::FIELD_MAX)) {
            $e['field_of_study'] = t('Pilih program studi atau bidang studi.');
        }
        return $e;
    }

    public static function event(array $d): array
    {
        $e = [];
        $time = '/^([01]\d|2[0-3]):[0-5]\d$/';
        $date = '/^\d{4}-\d{2}-\d{2}$/';
        if ($d['name'] === '') {
            $e['name'] = t('Nama event wajib diisi.');
        }
        if ($d['organizer'] === '') {
            $e['organizer'] = t('Penyelenggara wajib diisi.');
        }
        if ($d['location'] === '') {
            $e['location'] = t('Lokasi wajib diisi.');
        }
        if (!preg_match($date, $d['event_date'])) {
            $e['event_date'] = t('Pilih tanggal event.');
        }
        foreach (['start_time' => 'jam mulai', 'end_time' => 'jam selesai', 'att_start' => 'waktu mulai presensi', 'att_end' => 'waktu selesai presensi'] as $k => $label) {
            if ($k === 'end_time' && $d[$k] === '') {
                continue; // jam selesai boleh kosong: acara "sampai selesai"
            }
            if (!preg_match($time, $d[$k])) {
                $e[$k] = t('Isi :label (format JJ:MM).', ['label' => t($label)]);
            }
        }
        if ($d['end_time'] !== '' && !isset($e['end_time']) && !isset($e['start_time']) && $d['end_time'] <= $d['start_time']) {
            $e['end_time'] = t('Harus setelah jam mulai.');
        }
        if (!isset($e['att_end']) && !isset($e['att_start']) && $d['att_end'] <= $d['att_start']) {
            $e['att_end'] = t('Harus setelah waktu mulai presensi.');
        }
        foreach (['reg_start', 'reg_end'] as $k) {
            if ($d[$k] !== null && !preg_match($date, $d[$k])) {
                $e[$k] = t('Format tanggal tidak valid.');
            }
        }
        if ($d['reg_start'] && $d['reg_end'] && $d['reg_end'] < $d['reg_start']) {
            $e['reg_end'] = t('Tanggal tutup harus setelah tanggal buka.');
        }
        if ($d['reg_end'] && !isset($e['event_date']) && $d['reg_end'] > $d['event_date']) {
            $e['reg_end'] = t('Registrasi harus ditutup paling lambat pada hari event.');
        }
        if (array_key_exists('quota', $d) && $d['quota'] !== null && (!ctype_digit($d['quota']) || (int) $d['quota'] < 1 || (int) $d['quota'] > 100000)) {
            $e['quota'] = t('Kuota berupa angka 1–100000, atau kosongkan bila tanpa batas.');
        }
        if (!empty($d['reg_end_time'])) {
            if (!preg_match($time, $d['reg_end_time'])) {
                $e['reg_end_time'] = t('Format jam tidak valid (JJ:MM).');
            } elseif (!$d['reg_end']) {
                $e['reg_end_time'] = t('Isi tanggal registrasi ditutup terlebih dahulu.');
            }
        }
        if (!in_array($d['status'], Event::STATUSES, true)) {
            $e['status'] = t('Pilih status event.');
        }
        if (!in_array($d['reg_status'], Event::REG_STATUSES, true)) {
            $e['reg_status'] = t('Pilih status registrasi.');
        }
        return $e;
    }

    public static function account(array $d, bool $isNew = true): array
    {
        $e = [];
        if (mb_strlen($d['name']) < 3) {
            $e['name'] = t('Nama petugas wajib diisi.');
        }
        if ($isNew) {
            if (!preg_match('/^[a-z0-9.]{4,40}$/', $d['username'])) {
                $e['username'] = t('Gunakan 4–40 karakter: huruf kecil, angka, atau titik.');
            } elseif (User::findByUsername($d['username'])) {
                $e['username'] = t('Username sudah dipakai akun lain.');
            }
            if (strlen($d['password']) < 8) {
                $e['password'] = t('Password sementara minimal 8 karakter.');
            }
        }
        if ($d['event_id'] && !Event::find((int) $d['event_id'])) {
            $e['event_id'] = t('Event tidak ditemukan.');
        }
        if (!in_array($d['status'], ['Aktif', 'Nonaktif'], true)) {
            $e['status'] = t('Pilih status akun.');
        }
        return $e;
    }

    public static function newPassword(string $password, string $confirm): ?string
    {
        if (strlen($password) < 10) {
            return t('Password baru minimal 10 karakter.');
        }
        if (!preg_match('/\d/', $password) || !preg_match('/[a-z]/i', $password)) {
            return t('Gunakan kombinasi huruf dan angka.');
        }
        if ($password !== $confirm) {
            return t('Konfirmasi password tidak sama.');
        }
        return null;
    }
}
