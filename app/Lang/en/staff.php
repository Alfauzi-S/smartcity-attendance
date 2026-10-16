<?php
return [
    // Judul halaman
    'Beranda' => 'Home',
    'Scan QR' => 'Scan QR',
    'Peserta' => 'Participants',
    'Detail Peserta' => 'Participant Details',
    'Profil Akun' => 'Account Profile',

    // Beranda petugas
    'Akun belum ditugaskan ke event' => 'Account not yet assigned to an event',
    'Minta Super Admin menugaskan akun ini ke sebuah event agar Anda dapat memindai QR peserta.' => 'Ask a Super Admin to assign this account to an event so you can scan participant QR codes.',
    'Minta Super Admin menugaskan akun ini ke sebuah event.' => 'Ask a Super Admin to assign this account to an event.',
    'Halo, :name' => 'Hi, :name',
    'Anda telah memindai :n peserta.' => 'You have scanned :n participant(s).',
    'Absensi sudah ditutup' => 'Attendance is closed',
    'Absensi belum dibuka' => 'Attendance is not open yet',
    'Scan QR hanya bisa disimpan saat Super Admin membuka absensi.' => 'QR scans can only be saved while a Super Admin has attendance open.',
    'Mulai scan QR' => 'Start scanning QR',
    'Progres kehadiran' => 'Attendance progress',
    ':n dari :total' => ':n of :total',
    'Check-in terbaru' => 'Latest check-ins',
    'Lihat semua' => 'View all',
    'Belum ada peserta yang check-in.' => 'No participants have checked in yet.',

    // Daftar & detail peserta
    'Daftar peserta' => 'Participant list',
    ':n hadir dari :total' => ':n present of :total',
    'Admin Absensi hanya dapat melihat data. Perubahan status manual dilakukan oleh Super Admin.' => 'Attendance Admins can only view data. Manual status changes are made by a Super Admin.',
    'Belum diverifikasi' => 'Not yet verified',
    'Pindai QR peserta ini untuk mencatat kehadiran. Jika QR tidak tersedia, gunakan pencarian manual di halaman Scan.' => "Scan this participant's QR to record attendance. If the QR isn't available, use the manual search on the Scan page.",
    'Buka scanner' => 'Open scanner',
    'Ditetapkan Tidak Hadir' => 'Marked Absent',
    'Status ini ditetapkan Super Admin dan tidak dapat diubah lewat scan.' => 'This status was set by a Super Admin and cannot be changed by scanning.',

    // Profil
    'Admin Absensi' => 'Attendance Admin',
    'Event' => 'Event',
    'Belum ditugaskan' => 'Not assigned yet',
    'Total scan' => 'Total scans',
    ':n peserta' => ':n participant(s)',
    'Login terakhir' => 'Last login',
    'Hak akses' => 'Permissions',
    'Memindai QR dan menyimpan kehadiran' => 'Scan QR codes and save attendance',
    'Melihat daftar peserta event yang ditugaskan' => 'View the participant list of the assigned event',
    'Mengubah status manual, data peserta, event, atau laporan' => 'Change statuses manually, or edit participant data, events, or reports',
    'Keluar dari akun ini?' => 'Sign out of this account?',
    'Keluar' => 'Sign out',

    // Halaman scan
    'Belum ada event' => 'No events yet',
    'Buat event terlebih dahulu.' => 'Create an event first.',
    'Buat event' => 'Create event',
    'Absensi :start–:end :tz' => 'Attendance :start–:end :tz',
    'QR masih bisa dipindai untuk dicek, tetapi kehadiran tidak akan tersimpan. Buka absensi di halaman detail event.' => 'QR codes can still be scanned for checking, but attendance will not be saved. Open attendance on the event detail page.',
    'QR masih bisa dipindai untuk dicek, tetapi kehadiran tidak akan tersimpan sampai Super Admin membuka absensi.' => 'QR codes can still be scanned for checking, but attendance will not be saved until a Super Admin opens attendance.',
    'Arahkan kamera ke <b>QR Code peserta</b>' => "Point the camera at the <b>participant's QR code</b>",
    'Menyalakan kamera…' => 'Starting camera…',
    'Izinkan akses kamera jika browser meminta.' => 'Allow camera access if the browser asks.',
    'Ganti kamera' => 'Switch camera',
    'Coba nyalakan kamera' => 'Try starting camera',
    'Siap memindai' => 'Ready to scan',
    'Hasil pemindaian muncul di sini. Kehadiran baru tersimpan setelah Anda menekan <b>Simpan Kehadiran</b>.' => 'Scan results appear here. Attendance is only saved after you press <b>Save Attendance</b>.',
    'Input manual' => 'Manual entry',
    'Bila QR rusak atau kamera tidak tersedia' => "If the QR is damaged or the camera isn't available",
    'Kode QR, mis. SC7KX29QDA' => 'QR code, e.g. SC7KX29QDA',
    'Kode QR peserta' => 'Participant QR code',
    'Cek' => 'Check',
    'Cari nama atau nomor identitas' => 'Search name or ID number',
    'Ketik minimal 2 huruf' => 'Type at least 2 characters',

    // Pesan API / layanan
    'Akun ini belum ditugaskan ke event.' => 'This account has not been assigned to an event.',
    'Pilih status Hadir atau Tidak Hadir.' => 'Choose Present or Absent.',
    'Peserta tidak ditemukan.' => 'Participant not found.',
    'Peserta tidak ditemukan' => 'Participant not found',
    'Status peserta sudah :status.' => 'Participant status is already :status.',
    'Status baru saja diubah oleh proses lain. Muat ulang lalu coba lagi.' => 'The status was just changed by another process. Reload and try again.',

    // Metode check-in
    'Input manual petugas' => 'Manual entry by staff',
    'Diubah manual oleh Super Admin' => 'Changed manually by Super Admin',
];
