<?php
/**
 * Salin file ini menjadi config/config.php lalu sesuaikan nilainya.
 * File config.php tidak boleh diunggah ke repositori publik.
 */
return [
    'app_name' => 'SmartCity Attendance',

    // Kosongkan agar alamat terdeteksi otomatis.
    // Isi jika aplikasi berada di balik proxy, mis. 'https://absensi.kampus.ac.id'
    'base_url' => '',

    // true jika server mendukung URL rewrite (.htaccess / try_files Nginx).
    // false jika tidak: URL menjadi /index.php/e/slug-event
    'pretty_urls' => true,

    // Bahasa bawaan: 'id' (Indonesia) atau 'en' (English).
    // Pengunjung tetap bisa mengganti bahasa lewat tombol ID / EN.
    'locale' => 'id',

    // Zona waktu event. Samarinda/Balikpapan = Asia/Makassar (WITA)
    'timezone' => 'Asia/Makassar',
    'timezone_label' => 'WITA',

    // Tampilkan detail error. WAJIB false di server produksi.
    'debug' => false,

    'db' => [
        'driver'   => 'mysql',          // mysql | sqlite
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'smartcity_attendance',
        'username' => 'smartcity',
        'password' => 'ganti-dengan-password-database',
        'charset'  => 'utf8mb4',
        // Dipakai hanya jika driver = sqlite
        'sqlite_path' => __DIR__ . '/../storage/database.sqlite',
    ],

    'session_name' => 'sca_session',

    // Kontak panitia yang tampil di halaman event (opsional)
    'contact_phone' => '',

    // Sertifikat otomatis
    'cert' => [
        'max_upload_mb' => 8,        // batas ukuran desain/logo yang diunggah (juga cek upload_max_filesize PHP)
        'batch_size' => 10,          // jumlah sertifikat per langkah generate massal
        'search_per_10min' => 30,    // batas pencarian sertifikat publik per IP
        'download_fail_per_15min' => 8, // batas salah verifikasi unduhan per IP
    ],
];
