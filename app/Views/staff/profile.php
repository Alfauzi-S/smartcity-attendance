<?php use App\Core\View; ?>
<div class="card">
  <div class="idcard"><span class="av lg t"><?= e(initials($user['name'])) ?></span><div><b><?= e($user['name']) ?></b><span>@<?= e($user['username']) ?> · <?= e(t('Admin Presensi')) ?></span></div></div>
  <dl class="kv">
    <div><dt><?= e(t('Event')) ?></dt><dd><?= e($event['name'] ?? t('Belum ditugaskan')) ?></dd></div>
    <div><dt><?= e(t('Total scan')) ?></dt><dd><?= e(t(':n peserta', ['n' => (int) $scans])) ?></dd></div>
    <div><dt><?= e(t('Login terakhir')) ?></dt><dd><?= e(datetime_id($user['last_login_at'])) ?></dd></div>
  </dl>
</div>
<div class="card">
  <div class="card-h"><h3><?= e(t('Hak akses')) ?></h3></div>
  <div class="perm">
    <div><span class="y"><?= icon('check', 18) ?></span><?= e(t('Memindai QR dan menyimpan kehadiran')) ?></div>
    <div><span class="y"><?= icon('check', 18) ?></span><?= e(t('Melihat daftar peserta event yang ditugaskan')) ?></div>
    <div><span class="n"><?= icon('x', 18) ?></span><?= e(t('Mengubah status manual, data peserta, event, atau laporan')) ?></div>
  </div>
</div>
<?= View::partial('password_form', ['action' => 'petugas/profil/password', 'mustChange' => (int) $user['must_change_password'] === 1]) ?>
<form method="post" action="<?= e(url('keluar')) ?>" data-confirm="<?= e(t('Keluar dari akun ini?')) ?>"><?= csrf_field() ?><button class="btn btn-outline block" type="submit"><?= icon('logout', 18) ?><?= e(t('Keluar')) ?></button></form>
