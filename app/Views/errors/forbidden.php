<?php use App\Core\Auth; ?>
<div class="card">
  <div class="state s-warn">
    <span class="sic"><?= icon('lock', 30) ?></span>
    <span class="bdg bdg-mute mono"><?= e(t('Kode :code', ['code' => 403])) ?></span>
    <h3><?= e(t('Akses ditolak')) ?></h3>
    <?php if (($user['role'] ?? '') === Auth::EVENT): ?>
      <p><?= e(t('Akun Admin Event hanya dapat membuka event yang ditugaskan, sesuai izin yang diberikan Super Admin.')) ?></p>
    <?php else: ?>
      <p><?= e(t('Halaman ini hanya untuk Super Admin. Akun Admin Absensi hanya dapat memindai QR dan melihat daftar peserta event yang ditugaskan.')) ?></p>
    <?php endif; ?>
    <div class="acts"><a class="btn btn-primary" href="<?= e(url(in_array($user['role'] ?? '', [Auth::SUPER, Auth::EVENT], true) ? 'admin' : 'petugas')) ?>"><?= e(t('Kembali ke beranda')) ?></a></div>
  </div>
</div>
