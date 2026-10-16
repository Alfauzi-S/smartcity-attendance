<div class="card">
  <div class="state s-warn">
    <span class="sic"><?= icon('lock', 30) ?></span>
    <span class="bdg bdg-mute mono">Kode 403</span>
    <h3>Akses ditolak</h3>
    <p>Halaman ini hanya untuk Super Admin. Akun Admin Absensi hanya dapat memindai QR dan melihat daftar peserta event yang ditugaskan.</p>
    <div class="acts"><a class="btn btn-primary" href="<?= e(url(($user['role'] ?? '') === 'super' ? 'admin' : 'petugas')) ?>">Kembali ke beranda</a></div>
  </div>
</div>
