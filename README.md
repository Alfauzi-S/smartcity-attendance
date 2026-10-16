# SmartCity Attendance

Website registrasi event dan absensi berbasis QR Code dengan tema Smart City / Smart Campus.
Dibuat dengan PHP 8.1+ (OOP, tanpa framework), database MySQL/MariaDB atau SQLite.

## Peran pengguna

| Peran | Login | Hak akses |
|---|---|---|
| **Peserta** | Tidak | Melihat event, mengisi registrasi (6 data wajib), mendapat QR Code, mengunduh QR (PNG) |
| **Admin Absensi** | Ya | Memindai QR, menyimpan kehadiran, input/pencarian manual, melihat daftar peserta event yang ditugaskan |
| **Super Admin** | Ya | Semua fitur: event, akun Admin Absensi, peserta, ubah status Hadir/Tidak Hadir manual (dengan konfirmasi dan alasan), riwayat perubahan, laporan, ekspor CSV/Excel |

Status peserta:

- **Belum diverifikasi**: status awal setelah registrasi.
- **Hadir**: tercatat lewat scan QR, atau diubah manual oleh Super Admin.
- **Tidak Hadir**: ditetapkan Super Admin. Status ini final, sehingga QR peserta tidak bisa dipakai lagi.

## Kebutuhan server

- PHP **8.1 atau lebih baru** dengan ekstensi `pdo_mysql` (atau `pdo_sqlite`) dan `mbstring`
- Ekstensi `zip`: opsional, hanya untuk ekspor Excel (.xlsx). Tanpa ekstensi ini, ekspor CSV tetap tersedia.
- MySQL 5.7+ / MariaDB 10.3+ (atau SQLite 3 untuk uji coba)
- Apache dengan `mod_rewrite`, atau Nginx
- **HTTPS wajib** agar kamera scanner bisa dipakai. Browser hanya mengizinkan kamera di HTTPS atau `localhost`. Tanpa HTTPS, scanner otomatis beralih ke input kode dan pencarian manual.

## Cara deploy

### 1. Unggah file

Ekstrak ZIP di server, misalnya ke `/var/www/smartcity-attendance`.

**Disarankan:** arahkan *document root* domain ke folder `public/`.
Jika hosting tidak mengizinkannya (shared hosting / cPanel), letakkan seluruh folder di `public_html`. File `.htaccess` di root proyek akan meneruskan permintaan ke `public/` dan memblokir akses ke `app/`, `config/`, `database/`, dan `storage/`.

### 2. Buat database (MySQL/MariaDB)

```sql
CREATE DATABASE smartcity_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smartcity'@'localhost' IDENTIFIED BY 'password-kuat';
GRANT ALL PRIVILEGES ON smartcity_attendance.* TO 'smartcity'@'localhost';
```

Tabel tidak perlu dibuat manual karena installer di langkah 5 membuatnya otomatis.
Jika ingin membuatnya manual, impor `database/schema.mysql.sql`.

### 3. Atur konfigurasi

```bash
cp config/config.example.php config/config.php
```

Lalu ubah isi `config/config.php`:

- `db.driver`, `db.host`, `db.database`, `db.username`, `db.password`
- `timezone`: default `Asia/Makassar` (WITA)
- `debug`: **harus `false`** di server produksi
- `pretty_urls`: ubah ke `false` jika server tidak mendukung URL rewrite (alamat menjadi `/index.php/...`)
- `base_url`: kosongkan agar terdeteksi otomatis. Isi hanya jika aplikasi berada di balik reverse proxy.

Untuk uji coba cepat tanpa MySQL, pakai `'driver' => 'sqlite'`. File database dibuat di `storage/database.sqlite`.

### 4. Izin folder

Folder `storage/` harus bisa ditulis oleh web server:

```bash
chown -R www-data:www-data storage
chmod -R 775 storage
```

### 5. Jalankan installer

Buka `https://domain-anda/install` di browser:

1. Periksa hasil pengecekan server; semua item harus berstatus ✓.
2. Buat akun **Super Admin**.
3. Opsional: centang **data demo** (1 event, 156 peserta, 98 hadir, 2 Admin Absensi) untuk uji coba.

Setelah Super Admin pertama dibuat, halaman `/install` otomatis terkunci (404).

### 6. Mulai pakai

1. Masuk di `/masuk` sebagai Super Admin.
2. **Event → Buat event**, lalu ubah status event menjadi **Aktif** dan **Buka registrasi**.
3. Salin **tautan registrasi** dan bagikan ke peserta.
4. **Akun Admin → Buat akun** untuk petugas scan, lalu tugaskan ke event.
5. Pada hari-H, buka **Detail event → Buka absensi**. Petugas memindai QR di `/petugas/scan`.
6. Setelah selesai, **Tutup absensi**, tetapkan peserta yang tidak datang, lalu ekspor rekap di **Laporan**.

### Contoh konfigurasi Nginx

Lihat `nginx.conf.example`. Hal penting: `root` harus menunjuk ke folder `public/` dan `try_files $uri $uri/ /index.php?$query_string;`.

### Mencoba di komputer sendiri

```bash
cp config/config.example.php config/config.php   # ubah driver ke 'sqlite'
php -S localhost:8000 -t public public/index.php
```

Buka `http://localhost:8000/install`. Kamera berfungsi di `localhost` meskipun tanpa HTTPS.

## Keamanan yang sudah diterapkan

- Password disimpan dengan `password_hash` (bcrypt/argon sesuai PHP), dan sesi dibuat ulang setelah login.
- Token CSRF di semua form POST dan di API scanner (header `X-CSRF-Token`).
- Batas percobaan login (5 kali per 15 menit) dan batas registrasi (30 kali per jam per IP), ditambah honeypot anti-bot.
- Kontrol akses per peran: Admin Absensi mendapat 403 di halaman Super Admin dan hanya melihat event yang ditugaskan.
- Check-in atomik (`UPDATE ... WHERE status = 'belum'`), sehingga scan ganda atau dua petugas yang memindai bersamaan tidak membuat data dobel.
- Ubah status manual memakai transaksi dan tercatat di `status_history` (status lama dan baru, alasan, oleh siapa, kapan).
- Semua query memakai *prepared statement* (PDO), dan semua keluaran HTML di-escape.
- Cookie sesi `HttpOnly`, `SameSite=Lax`, dan `Secure` saat HTTPS. Header keamanan ikut dikirim: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, dan `Permissions-Policy` (kamera hanya untuk situs sendiri).
- Ekspor CSV dilindungi dari *formula injection*.
- Nomor WhatsApp tidak dikirim ke scanner dan hanya terlihat oleh Super Admin.
- Akun baru dan reset password memakai password sementara yang wajib diganti saat login pertama.

## Struktur folder

```
app/
  Core/          Router, Database (PDO), Auth, View, RateLimiter
  Models/        Event, Participant, User, StatusHistory
  Services/      AttendanceService (aturan absensi), Validator, Exporter (CSV/XLSX), DemoSeeder
  Controllers/   Public, Auth, Staff, Api, Dashboard, Event, Account, Participant, Report, Settings, Install
  Views/         layouts, partials, halaman publik, petugas, super admin
  bootstrap.php  autoload, konfigurasi, sesi, header keamanan
  helpers.php    fungsi bantu (url, e, csrf, format tanggal Indonesia, badge status)
config/          config.example.php → salin menjadi config.php
database/        schema.mysql.sql, schema.sqlite.sql
public/          index.php (front controller), assets (css, js, vendor, img)
storage/         database SQLite (opsional) dan log error
```

Konsep PBO yang dipakai:

- **Kelas dan namespace** `App\...` dengan autoload ala PSR-4
- **Pewarisan**: semua controller mewarisi `Controller` (render, event aktif, ganti password)
- **Enkapsulasi**: akses database hanya lewat kelas `Database` dan model
- **Pemisahan tanggung jawab**: model untuk data, service untuk aturan bisnis, controller untuk alur HTTP, view untuk tampilan

## Rute utama

| URL | Keterangan |
|---|---|
| `/e/{slug}` | Halaman event publik |
| `/e/{slug}/daftar` | Formulir registrasi |
| `/t/{kode}` | Tiket QR peserta (isi QR = alamat ini) |
| `/masuk` | Login Admin Absensi / Super Admin |
| `/petugas`, `/petugas/scan` | Beranda dan scanner Admin Absensi |
| `/admin` | Dashboard Super Admin |
| `/install` | Instalasi pertama (terkunci setelah selesai) |

## Pustaka pihak ketiga

Pustaka berikut sudah disertakan di `public/assets/vendor/`, sehingga tidak bergantung pada CDN:

- [html5-qrcode](https://github.com/mebjas/html5-qrcode) 2.3.8, Apache-2.0: scanner kamera
- [qrcodejs](https://github.com/davidshimjs/qrcodejs) 1.0.0, MIT: membuat gambar QR tiket

Font Sora dan Inter dimuat dari Google Fonts. Jika server tidak bisa mengakses internet, tampilan otomatis memakai font sistem.

## Kustomisasi cepat

- Pilihan **Profesi** dan **Program Studi**: konstanta `PROFESSIONS` dan `FIELDS` di `app/Models/Participant.php`
- Warna dan tampilan: variabel CSS di bagian atas `public/assets/css/app.css`
- Aturan validasi (panjang NIM, format nomor HP): `app/Services/Validator.php`
