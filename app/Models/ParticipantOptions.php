<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Pilihan profesi dan program studi di formulir registrasi, dikelompokkan untuk <optgroup>.
 * Nilai lama (Mahasiswa S1, Informatika, dst.) tetap ada agar data peserta lama tetap valid.
 * "Lainnya" membuka kolom isian bebas; teks yang diisi disimpan sebagai nilainya.
 */
final class ParticipantOptions
{
    public const OTHER = 'Lainnya';
    public const PROFESSION_MAX = 60;
    public const FIELD_MAX = 120;

    public const PROFESSIONS = [
        'Pelajar & Mahasiswa' => [
            'Siswa SMA / SMK / MA', 'Mahasiswa D3 / D4', 'Mahasiswa S1', 'Mahasiswa S2/S3',
            'Mahasiswa Profesi (Koas, PPG, Ners, dll.)', 'Santri',
        ],
        'Pendidikan & Riset' => [
            'Dosen / Akademisi', 'Guru', 'Tenaga Kependidikan', 'Peneliti', 'Pelatih / Instruktur',
        ],
        'Pemerintahan & BUMN' => [
            'Aparatur Sipil Negara (PNS / PPPK)', 'TNI', 'Polri', 'Pegawai BUMN / BUMD', 'Perangkat Desa / Kelurahan',
        ],
        'Swasta & Profesional' => [
            'Karyawan Swasta', 'Praktisi / Profesional', 'Programmer / Software Engineer', 'Analis Data / AI',
            'Desainer / Kreator Konten', 'Insinyur / Teknisi', 'Arsitek / Perencana Kota', 'Tenaga Kesehatan',
            'Akuntan / Keuangan / Perbankan', 'Konsultan', 'Praktisi Hukum', 'Jurnalis / Media',
        ],
        'Wirausaha & Masyarakat' => [
            'Wiraswasta / Pengusaha', 'Pelaku UMKM', 'Pekerja Lepas (Freelancer)', 'Petani / Peternak / Nelayan',
            'Buruh / Pekerja Harian', 'Pekerja Sosial / NGO / Relawan', 'Tokoh Masyarakat / Tokoh Agama',
            'Ibu / Bapak Rumah Tangga', 'Pensiunan', 'Belum / Tidak Bekerja',
        ],
    ];

    public const FIELDS = [
        'Komputer & Teknologi Informasi' => [
            'Informatika', 'Teknik Informatika', 'Ilmu Komputer', 'Sistem Informasi', 'Teknologi Informasi',
            'Rekayasa Perangkat Lunak', 'Sains Data', 'Kecerdasan Artifisial', 'Keamanan Siber', 'Teknik Komputer',
            'Manajemen Informatika', 'Bisnis Digital',
        ],
        'Teknik & Perencanaan' => [
            'Teknik Elektro', 'Teknik Telekomunikasi', 'Teknik Mesin', 'Teknik Mekatronika', 'Teknik Sipil',
            'Teknik Industri', 'Teknik Kimia', 'Teknik Lingkungan', 'Teknik Pertambangan', 'Teknik Perminyakan',
            'Teknik Geologi', 'Teknik Geomatika / Geodesi', 'Teknik Fisika', 'Teknik Biomedis',
            'Teknik Material / Metalurgi', 'Teknik Perkapalan / Kelautan', 'Arsitektur', 'Perencanaan Wilayah dan Kota',
        ],
        'Matematika & Ilmu Pengetahuan Alam' => [
            'Matematika', 'Statistika', 'Aktuaria', 'Fisika', 'Kimia', 'Biologi', 'Geofisika', 'Geografi',
            'Ilmu Lingkungan', 'Meteorologi / Klimatologi',
        ],
        'Kesehatan' => [
            'Kedokteran', 'Kedokteran Gigi', 'Keperawatan', 'Kebidanan', 'Farmasi', 'Kesehatan Masyarakat', 'Gizi',
            'Fisioterapi', 'Teknologi Laboratorium Medis', 'Rekam Medis dan Informasi Kesehatan', 'Kedokteran Hewan',
        ],
        'Pertanian, Kehutanan & Kelautan' => [
            'Agroteknologi / Agronomi', 'Agribisnis', 'Peternakan', 'Kehutanan', 'Teknologi Pangan',
            'Teknologi Hasil Pertanian', 'Teknik Pertanian dan Biosistem', 'Perikanan / Budidaya Perairan', 'Ilmu Kelautan',
        ],
        'Ekonomi & Bisnis' => [
            'Manajemen', 'Akuntansi', 'Ekonomi Pembangunan', 'Ekonomi Syariah', 'Perbankan dan Keuangan',
            'Administrasi Bisnis', 'Kewirausahaan', 'Pariwisata / Perhotelan', 'Logistik dan Rantai Pasok',
        ],
        'Sosial, Politik & Hukum' => [
            'Ilmu Hukum', 'Ilmu Komunikasi', 'Administrasi Publik', 'Ilmu Pemerintahan', 'Hubungan Internasional',
            'Ilmu Politik', 'Sosiologi', 'Antropologi', 'Kesejahteraan Sosial', 'Psikologi', 'Ilmu Perpustakaan',
            'Kriminologi',
        ],
        'Pendidikan & Keguruan' => [
            'Pendidikan Guru Sekolah Dasar (PGSD)', 'Pendidikan Guru PAUD', 'Pendidikan Matematika',
            'Pendidikan Bahasa Indonesia', 'Pendidikan Bahasa Inggris', 'Pendidikan Fisika', 'Pendidikan Kimia',
            'Pendidikan Biologi', 'Pendidikan Ekonomi', 'Pendidikan Pancasila dan Kewarganegaraan', 'Pendidikan Sejarah',
            'Pendidikan Geografi', 'Pendidikan Jasmani dan Olahraga', 'Pendidikan Teknologi Informasi',
            'Pendidikan Teknik / Vokasi', 'Bimbingan dan Konseling', 'Teknologi Pendidikan', 'Manajemen Pendidikan',
        ],
        'Bahasa, Seni & Budaya' => [
            'Sastra Indonesia', 'Sastra Inggris', 'Sastra Daerah', 'Bahasa dan Sastra Asing Lainnya', 'Ilmu Sejarah',
            'Arkeologi', 'Filsafat', 'Desain Komunikasi Visual', 'Desain Interior', 'Desain Produk', 'Seni Rupa',
            'Seni Musik', 'Seni Tari / Seni Pertunjukan', 'Film dan Televisi',
        ],
        'Agama' => [
            'Pendidikan Agama Islam', 'Hukum Keluarga Islam / Syariah', 'Komunikasi dan Penyiaran Islam',
            'Ilmu Al-Qur\'an dan Tafsir', 'Studi Agama-Agama', 'Teologi', 'Pendidikan Agama Kristen / Katolik',
            'Pendidikan Agama Hindu / Buddha',
        ],
        'Sekolah Menengah & Umum' => [
            'SMA / MA (IPA)', 'SMA / MA (IPS / Bahasa)', 'SMK', 'Umum / Tidak Berlatar Akademik',
        ],
    ];

    /** @return list<string> */
    public static function professions(): array
    {
        return [...array_merge(...array_values(self::PROFESSIONS)), self::OTHER];
    }

    /** @return list<string> */
    public static function fields(): array
    {
        return [...array_merge(...array_values(self::FIELDS)), self::OTHER];
    }

    /** Nilai yang disimpan: pilihan dari daftar, atau teks isian bila "Lainnya" dipilih dan diisi. */
    public static function pick(string $selected, string $other): string
    {
        $other = trim(preg_replace('/\s+/u', ' ', $other) ?? '');
        return $selected === self::OTHER && $other !== '' ? $other : $selected;
    }

    /** Pilihan dari daftar, atau teks isian bebas yang wajar (huruf, angka, tanda baca umum). */
    public static function valid(string $value, array $list, int $max): bool
    {
        if (in_array($value, $list, true)) {
            return true;
        }
        return $value !== '' && mb_strlen($value) >= 2 && mb_strlen($value) <= $max
            && preg_match('/^[\p{L}\p{N}][\p{L}\p{N} .,&()\/\'+-]*$/u', $value) === 1;
    }
}
