<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;

/**
 * Data demo fiktif: 1 event aktif hari ini, 156 peserta (98 hadir, 8 tidak hadir),
 * dan 2 akun Admin Absensi. Hanya untuk uji coba; jangan dipakai di event sungguhan.
 */
final class DemoSeeder
{
    private const FIRST = ['Aditya', 'Alya', 'Arief', 'Bagas', 'Bunga', 'Cahya', 'Daffa', 'Dewi', 'Elang', 'Farah', 'Fikri', 'Gilang', 'Hana', 'Ilham', 'Intan', 'Jihan', 'Kevin', 'Laras', 'Luthfi', 'Maya', 'Naufal', 'Nisa', 'Oki', 'Putri', 'Qori', 'Rafi', 'Rania', 'Sekar', 'Tegar', 'Tiara', 'Umar', 'Vina', 'Wahyu', 'Yasmin', 'Zaki', 'Anisa', 'Bayu', 'Citra', 'Galih', 'Keisha'];
    private const MIDDLE = ['Ayu', 'Dwi', 'Eka', 'Kurnia', 'Mahesa', 'Nur', 'Putra', 'Sari', 'Tri', 'Wulan', 'Adi', 'Rizky', 'Dian', 'Indah', 'Bagus', 'Lintang'];
    private const LAST = ['Pratama', 'Wijaya', 'Saputra', 'Lestari', 'Kusuma', 'Hidayat', 'Permata', 'Nugroho', 'Anggraini', 'Ramadhan', 'Santoso', 'Maharani', 'Halim', 'Setiawan', 'Wardani', 'Purnama', 'Siregar', 'Pangestu', 'Utami', 'Rahmawati'];
    private const REASONS = ['Izin tertulis: mewakili kampus di lomba robotik', 'Sakit, surat keterangan dokter diterima panitia', 'Izin keluarga, dikonfirmasi dosen wali', 'Bentrok jadwal praktikum susulan', 'Konfirmasi tidak hadir via WhatsApp', 'Izin tertulis: magang di luar kota', 'Sakit, konfirmasi orang tua', 'Izin dari ketua prodi'];

    /** @return array<int, array{username:string,password:string,name:string}> akun admin demo */
    public static function run(array $super): array
    {
        mt_srand(2026);
        $eventId = Event::create([
            'name' => 'Smart City Day — Informatika 2026',
            'description' => 'Hari pameran proyek kota pintar mahasiswa Informatika angkatan 2026: demo IoT kampus, dashboard transportasi, dan sesi berbagi dengan praktisi. (Data demo)',
            'organizer' => 'Himpunan Mahasiswa Informatika',
            'location' => 'Gedung Inovasi Digital, Aula Lt. 3',
            'event_date' => today(),
            'start_time' => '08:00',
            'end_time' => '15:00',
            'reg_start' => date('Y-m-d', strtotime('-14 days')),
            'reg_end' => today(),
            'att_start' => '07:00',
            'att_end' => '10:00',
            'status' => 'Aktif',
            'reg_status' => 'Dibuka',
            'att_status' => 'open',
        ]);

        $accounts = [];
        $adminIds = [];
        foreach ([['Petugas Absensi A', 'petugas.a'], ['Petugas Absensi B', 'petugas.b']] as [$name, $username]) {
            if (User::findByUsername($username)) {
                continue;
            }
            $password = User::randomPassword(10);
            $adminIds[] = User::create(['name' => $name, 'username' => $username, 'password' => $password, 'role' => 'admin', 'event_id' => $eventId, 'status' => 'Aktif']);
            $accounts[] = ['name' => $name, 'username' => $username, 'password' => $password];
        }
        $adminIds = $adminIds ?: [(int) $super['id']];

        DB::transaction(static function () use ($eventId, $adminIds, $super): void {
            $used = [];
            $statuses = array_merge(array_fill(0, 98, 'hadir'), array_fill(0, 8, 'tidak'), array_fill(0, 50, 'belum'));
            shuffle($statuses);
            $hadirIndex = 0;
            $tidakIndex = 0;
            $start = strtotime(today() . ' 07:03');
            for ($i = 1; $i <= 156; $i++) {
                do {
                    $name = self::FIRST[mt_rand(0, count(self::FIRST) - 1)] . ' '
                        . (mt_rand(0, 99) < 55 ? self::MIDDLE[mt_rand(0, count(self::MIDDLE) - 1)] . ' ' : '')
                        . self::LAST[mt_rand(0, count(self::LAST) - 1)];
                } while (isset($used[$name]));
                $used[$name] = true;
                $p = Participant::create($eventId, [
                    'identity_number' => '2609106' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'profession' => 'Mahasiswa S1',
                    'full_name' => $name,
                    'phone' => '08' . ['12', '13', '21', '52', '57', '77', '81', '95'][mt_rand(0, 7)] . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'institution' => 'Universitas Mahakam Raya',
                    'field_of_study' => 'Informatika',
                ]);
                $status = $statuses[$i - 1];
                if ($status === 'hadir') {
                    $at = date('Y-m-d H:i:s', $start + (int) round($hadirIndex * (126 * 60 / 98)));
                    DB::update('participants', ['status' => 'hadir', 'checked_in_at' => $at, 'checked_in_by' => $adminIds[$hadirIndex % count($adminIds)], 'method' => 'scan'], 'id = ?', [$p['id']]);
                    $hadirIndex++;
                } elseif ($status === 'tidak') {
                    DB::update('participants', ['status' => 'tidak', 'checked_in_by' => (int) $super['id'], 'method' => 'manual'], 'id = ?', [$p['id']]);
                    DB::insert('status_history', [
                        'participant_id' => $p['id'],
                        'old_status' => 'belum',
                        'new_status' => 'tidak',
                        'reason' => self::REASONS[$tidakIndex % count(self::REASONS)],
                        'changed_by' => (int) $super['id'],
                        'changed_by_username' => $super['username'],
                        'changed_at' => date('Y-m-d H:i:s', strtotime(today() . ' 08:06') + $tidakIndex * 420),
                    ]);
                    $tidakIndex++;
                }
            }
        });
        return $accounts;
    }
}
