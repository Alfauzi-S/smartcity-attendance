<?php use App\Core\View; ?>
<div class="card">
  <div class="idcard"><span class="av lg t"><?= e(initials($user['name'])) ?></span><div><b><?= e($user['name']) ?></b><span>@<?= e($user['username']) ?> · Admin Absensi</span></div></div>
  <dl class="kv">
    <div><dt>Event</dt><dd><?= e($event['name'] ?? 'Belum ditugaskan') ?></dd></div>
    <div><dt>Total scan</dt><dd><?= (int) $scans ?> peserta</dd></div>
    <div><dt>Login terakhir</dt><dd><?= e(datetime_id($user['last_login_at'])) ?></dd></div>
  </dl>
</div>
<div class="card">
  <div class="card-h"><h3>Hak akses</h3></div>
  <div class="perm">
    <div><span class="y"><?= icon('check', 18) ?></span>Memindai QR dan menyimpan kehadiran</div>
    <div><span class="y"><?= icon('check', 18) ?></span>Melihat daftar peserta event yang ditugaskan</div>
    <div><span class="n"><?= icon('x', 18) ?></span>Mengubah status manual, data peserta, event, atau laporan</div>
  </div>
</div>
<?= View::partial('password_form', ['action' => 'petugas/profil/password', 'mustChange' => (int) $user['must_change_password'] === 1]) ?>
<form method="post" action="<?= e(url('keluar')) ?>" data-confirm="Keluar dari akun ini?"><?= csrf_field() ?><button class="btn btn-outline block" type="submit"><?= icon('logout', 18) ?>Keluar</button></form>
