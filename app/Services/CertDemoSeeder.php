<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\CertAsset;
use App\Models\CertNumbering;
use App\Models\CertTemplate;
use App\Models\CertVariable;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Services\Cert\Issuer;

/**
 * Data demo untuk peran Admin Event dan sertifikat (dipanggil setelah DemoSeeder).
 * Semua nilai resmi diberi label DEMO: logo dan latar digambar sebagai contoh netral, tidak ada tanda tangan
 * atau stempel, nilai variabel hanya diisi sebagai nilai khusus event demo (variabel global tetap kosong),
 * dan pola nomor diawali "DEMO" sehingga tidak bisa tertukar dengan nomor surat resmi.
 */
final class CertDemoSeeder
{
    private const INSTITUTIONS = ['Universitas Mahakam Raya', 'Politeknik Borneo Timur', 'Institut Teknologi Khatulistiwa', 'Universitas Samudra Cendekia'];
    private const PROFESSIONS = ['Mahasiswa S1', 'Mahasiswa S1', 'Mahasiswa S1', 'Dosen', 'Umum'];

    /**
     * @param int $mainEventId event demo utama dari DemoSeeder
     * @return array{account: array{name:string,username:string,password:string}|null, event: string, sample: array{name:string,check:string}|null}
     */
    public static function run(array $super, int $mainEventId): array
    {
        if (!DB::tableExists('cert_issuances')) {
            return ['account' => null, 'event' => '', 'sample' => null];
        }
        mt_srand(2027);
        $date = date('Y-m-d', strtotime('-7 days'));
        $eventId = Event::create([
            'name' => 'Seminar Nasional Kota Cerdas 2026',
            'description' => 'Seminar sehari tentang tata kelola data kota dan layanan publik digital. Event ini sudah selesai dan dipakai untuk contoh sertifikat. (Data demo)',
            'organizer' => 'Panitia Seminar Kota Cerdas',
            'location' => 'Auditorium Utama, Lt. 2',
            'event_date' => $date,
            'start_time' => '08:30',
            'end_time' => '12:30',
            'reg_start' => date('Y-m-d', strtotime('-30 days')),
            'reg_end' => date('Y-m-d', strtotime('-8 days')),
            'att_start' => '08:00',
            'att_end' => '10:00',
            'status' => 'Selesai',
            'reg_status' => 'Ditutup',
            'att_status' => 'closed',
        ]);
        $event = Event::find($eventId);

        // Peserta: 30 hadir, 4 tidak hadir, 6 belum diverifikasi; dua peserta sengaja bernama sama
        $names = ['Rahmat Hidayat', 'Rahmat Hidayat', 'Annisa Putri Lestari', 'Bima Satria Nugraha', 'Clara Wulandari', 'Dimas Arya Pratama',
            'Erlina Kusumawati', 'Fajar Ramadhan', 'Gita Permata Sari', 'Hendra Wijaya', 'Indah Maharani', 'Joko Santoso', 'Kartika Ayu Ningrum',
            'Lukman Hakim', 'Melati Anggraini', 'Nanda Saputra', 'Oktaviani Rahmawati', 'Prasetyo Adi', 'Qonita Zahra', 'Rizal Firmansyah',
            'Salsabila Nur’aini', 'Taufik Hidayatullah', 'Ulfa Khairunnisa', 'Vino Bastian Halim', 'Wulan Dari Utami', 'Yoga Pangestu',
            'Zahra Amelia', 'Ahmad Fauzan', 'Bella Safitri', 'Cahyo Nugroho', 'Desi Ratnasari', 'Eko Prasetyo', 'Fitri Handayani', 'Galang Mahendra',
            'Hana Pertiwi', 'Irfan Maulana', 'Jessica Tanoto', 'Kevin Pratama Siregar', 'Lia Amalia', 'Muhammad Rizky Ananda'];
        $statuses = array_merge(array_fill(0, 30, 'hadir'), array_fill(0, 4, 'tidak'), array_fill(0, 6, 'belum'));
        $present = [];
        $start = strtotime($date . ' 08:02');
        foreach ($names as $i => $name) {
            $p = Participant::create($eventId, [
                'identity_number' => '2609207' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'profession' => self::PROFESSIONS[$i % count(self::PROFESSIONS)],
                'full_name' => $name,
                'phone' => '0813' . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                'institution' => self::INSTITUTIONS[$i % count(self::INSTITUTIONS)],
                'field_of_study' => 'Informatika',
            ]);
            if ($statuses[$i] === 'hadir') {
                DB::update('participants', ['status' => 'hadir', 'checked_in_at' => date('Y-m-d H:i:s', $start + count($present) * 190), 'checked_in_by' => (int) $super['id'], 'method' => 'scan'], 'id = ?', [$p['id']]);
                $present[] = (int) $p['id'];
            } elseif ($statuses[$i] === 'tidak') {
                DB::update('participants', ['status' => 'tidak', 'checked_in_by' => (int) $super['id'], 'method' => 'manual'], 'id = ?', [$p['id']]);
                DB::insert('status_history', [
                    'participant_id' => $p['id'], 'old_status' => 'belum', 'new_status' => 'tidak', 'reason' => 'Konfirmasi tidak hadir via WhatsApp (data demo)',
                    'changed_by' => (int) $super['id'], 'changed_by_username' => $super['username'], 'changed_at' => $date . ' 10:15:00',
                ]);
            }
        }

        // Akun Admin Event demo: semua izin di event seminar, izin bawaan di event utama
        $account = null;
        if (!User::findByUsername('event.demo')) {
            $password = User::randomPassword(10);
            $uid = User::create(['name' => 'Admin Event Demo', 'username' => 'event.demo', 'password' => $password, 'role' => 'event', 'event_id' => null, 'status' => 'Aktif']);
            Assignment::save($uid, $eventId, array_keys(Access::PERMS), (int) $super['id']);
            Assignment::save($uid, $mainEventId, Access::DEFAULT_PERMS, (int) $super['id']);
            AuditLog::add($super, 'account.create', null, 'user', $uid, ['username' => 'event.demo', 'role' => 'event', 'demo' => true]);
            AuditLog::add($super, 'assignment.add', $eventId, 'user', $uid, ['username' => 'event.demo', 'perms' => array_keys(Access::PERMS)]);
            AuditLog::add($super, 'assignment.add', $mainEventId, 'user', $uid, ['username' => 'event.demo', 'perms' => Access::DEFAULT_PERMS]);
            $account = ['name' => 'Admin Event Demo', 'username' => 'event.demo', 'password' => $password];
        }

        // Aset dan desain latar contoh (bukan logo resmi, tanpa tanda tangan/stempel)
        $logo = self::logoImage();
        $logoId = CertAsset::create($eventId, 'logo', 'Logo DEMO (bukan logo resmi)', $logo, $super);
        $bg = self::backgroundImage();

        // Nilai variabel khusus event demo; variabel global tetap kosong untuk diisi data resmi
        $values = [
            'UNIVERSITY' => 'Universitas Demo Nusantara', 'FACULTY' => 'Fakultas Teknik (Demo)', 'FACULTY_CODE' => 'FT',
            'FACULTY_LETTER_CODE' => 'DEMO', 'STUDY_PROGRAM' => 'Informatika', 'STUDY_PROGRAM_CODE' => 'INF',
            'SIGNER_NAME' => 'Nama Penandatangan (Demo)', 'SIGNER_TITLE' => 'Ketua Panitia (Demo)', 'EVENT_CODE' => 'SNKC', 'EVENT_CATEGORY' => 'Seminar',
        ];
        foreach (CertVariable::globals() as $v) {
            if (isset($values[$v['var_key']])) {
                CertVariable::setOverride((int) $v['id'], $eventId, $values[$v['var_key']], (int) $super['id']);
            }
        }
        CertVariable::save([
            'var_key' => 'PERAN', 'label' => 'Peran peserta', 'var_type' => 'text', 'source' => 'manual', 'source_field' => '', 'choices' => '',
            'value' => 'Peserta', 'default_value' => '', 'required' => true, 'overridable' => false, 'description' => 'Contoh variabel kustom (data demo)', 'active' => true,
        ], 'event', $eventId, (int) $super['id']);

        $nid = CertNumbering::createDraft($eventId, [
            'pattern' => 'DEMO/{EVENT_CODE}/{SEQUENCE_PADDED}/{ROMAN_MONTH}/{YEAR}', 'seq_padding' => 3, 'seq_scope' => 'event', 'seq_reset' => 'never',
            'note' => 'Pola contoh untuk demo, bukan format resmi fakultas/TU',
        ], (int) $super['id']);
        CertNumbering::approve(CertNumbering::find($nid), (int) $super['id']);

        $elements = [];
        foreach (CertTemplate::academicPreset() as $el) {
            if ($el['type'] === 'image' && $el['slot'] !== 'logo' || in_array($el['id'], ['lg2', 'lg3', 'lg4'], true)) {
                continue; // tanda tangan & stempel tidak dibuat untuk demo; cukup satu logo contoh
            }
            if ($el['id'] === 'lg1') {
                $el = ['label' => 'Logo DEMO', 'asset_id' => $logoId, 'x' => 45.5, 'y' => 5, 'w' => 9, 'h' => 12] + $el;
            } elseif ($el['id'] === 'dsc') {
                $el['content'] = 'Atas partisipasinya sebagai {PERAN} dalam kegiatan {EVENT_NAME} yang diselenggarakan oleh {ORGANIZER_NAME} pada {EVENT_DATE} bertempat di {EVENT_LOCATION}.';
            } elseif ($el['id'] === 'snm') {
                $el['y'] = 84;
            }
            $elements[] = $el;
        }
        $elements[] = ['id' => 'demo', 'type' => 'text', 'content' => 'SERTIFIKAT DEMO · TIDAK BERLAKU SEBAGAI DOKUMEN RESMI', 'x' => 25, 'y' => 93, 'w' => 50,
            'font' => 'dejavusans', 'size' => 8, 'bold' => true, 'italic' => false, 'color' => '#B91C1C', 'align' => 'C', 'lh' => 1.2, 'ls' => 1, 'fit' => false];
        $tid = CertTemplate::create($eventId, 'Sertifikat Peserta (Demo)', false, (int) $super['id']);
        CertTemplate::update($tid, [
            'elements' => json_encode($elements, JSON_UNESCAPED_UNICODE),
            'background_path' => $bg['path'], 'background_type' => 'jpg', 'background_w' => $bg['w'], 'background_h' => $bg['h'],
        ], (int) $super['id']);
        $vid = CertTemplate::activate(CertTemplate::find($tid), (int) $super['id']);
        Issuer::saveSettings($eventId, ['eligibility' => 'hadir', 'issue_date' => $date, 'require_approved_number' => 1], (int) $super['id']);
        AuditLog::add($super, 'cert.template.status', $eventId, 'template', $tid, ['status' => 'Active', 'version_id' => $vid, 'demo' => true]);
        AuditLog::add($super, 'cert.numbering.approve', $eventId, 'numbering', $nid, ['demo' => true]);

        // Terbitkan: semua yang hadir dibuat, 3 dibiarkan Generated, 1 dicabut, 1 diterbitkan ulang
        $ready = Issuer::readiness($event);
        if (!$ready['ok']) {
            return ['account' => $account, 'event' => $event['name'], 'sample' => null];
        }
        $issued = [];
        foreach ($present as $pid) {
            $r = Issuer::issue($event, Participant::find($pid), $super, $ready);
            $issued[] = $r;
            AuditLog::add($super, 'cert.generate', $eventId, 'certificate', $r['id'], ['number' => $r['cert_number'], 'demo' => true]);
        }
        foreach (array_slice($issued, 0, count($issued) - 3) as $r) {
            Issuer::publish($r, $super);
        }
        AuditLog::add($super, 'cert.publish', $eventId, 'event', $eventId, ['count' => count($issued) - 3, 'demo' => true]);
        $rev = $issued[5];
        Issuer::revoke($rev, 'Contoh pencabutan: peserta meninggalkan acara sebelum sesi inti (data demo)', $super);
        AuditLog::add($super, 'cert.revoke', $eventId, 'certificate', $rev['id'], ['demo' => true]);
        $old = DB::one('SELECT * FROM cert_issuances WHERE id = ?', [(int) $issued[8]['id']]);
        $new = Issuer::issue($event, Participant::find((int) $old['participant_id']), $super, $ready, null, $old, 'Perbaikan penulisan nama (data demo)');
        Issuer::publish($new, $super);
        AuditLog::add($super, 'cert.reissue', $eventId, 'certificate', $new['id'], ['replaces' => (int) $old['id'], 'demo' => true]);

        $first = Participant::find($present[0]);
        return ['account' => $account, 'event' => $event['name'], 'sample' => ['name' => $first['full_name'], 'check' => substr((string) $first['identity_number'], -4)]];
    }

    /** @return array{path:string,mime:string,w:int,h:int} */
    private static function logoImage(): array
    {
        $s = 400;
        $im = imagecreatetruecolor($s, $s);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagealphablending($im, true);
        $navy = imagecolorallocate($im, 11, 18, 32);
        $cyan = imagecolorallocate($im, 34, 211, 238);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledellipse($im, 200, 200, 380, 380, $navy);
        imagesetthickness($im, 10);
        imageellipse($im, 200, 200, 330, 330, $cyan);
        foreach ([[120, 230, 160], [175, 180, 225], [230, 140, 280]] as [$x, $top, $x2]) {
            imagefilledrectangle($im, $x, $top, $x2 - 10, 290, $cyan);
        }
        imagestring($im, 5, 165, 305, 'DEMO', $white);
        return self::save($im, 'assets', 'png', $s, $s);
    }

    /** @return array{path:string,mime:string,w:int,h:int} */
    private static function backgroundImage(): array
    {
        [$w, $h] = [2339, 1654]; // A4 landscape 200 dpi
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 251, 249, 243));
        $gold = imagecolorallocate($im, 176, 141, 62);
        $navy = imagecolorallocate($im, 11, 18, 32);
        $soft = imagecolorallocate($im, 236, 230, 214);
        imagefilledrectangle($im, 0, 0, $w, 70, $navy);
        imagefilledrectangle($im, 0, $h - 70, $w, $h, $navy);
        imagesetthickness($im, 6);
        imagerectangle($im, 110, 130, $w - 110, $h - 130, $gold);
        imagesetthickness($im, 2);
        imagerectangle($im, 135, 155, $w - 135, $h - 155, $gold);
        for ($i = 0; $i < 9; $i++) {
            imagefilledellipse($im, $w - 260 + $i * 4, 330, 260 - $i * 28, 260 - $i * 28, $i % 2 ? $soft : imagecolorallocate($im, 246, 242, 230));
        }
        return self::save($im, 'backgrounds', 'jpg', $w, $h);
    }

    /** @return array{path:string,mime:string,w:int,h:int} */
    private static function save(\GdImage $im, string $dir, string $ext, int $w, int $h): array
    {
        $rel = 'cert/' . $dir . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        $abs = STORAGE_PATH . '/' . $rel;
        if (!is_dir(dirname($abs))) {
            mkdir(dirname($abs), 0775, true);
        }
        $ext === 'png' ? imagepng($im, $abs, 6) : imagejpeg($im, $abs, 88);
        imagedestroy($im);
        return ['path' => $rel, 'mime' => $ext === 'png' ? 'image/png' : 'image/jpeg', 'w' => $w, 'h' => $h];
    }
}
