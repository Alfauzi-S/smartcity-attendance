<?php $base = 'admin/akun/' . $a['id']; ?>
<?php if ($credential): ?>
  <div class="card" style="border-color:#BFE6CC">
    <div class="card-h"><h3><?= e(t('Kredensial login')) ?></h3><span class="bdg bdg-warn"><?= e(t('Hanya tampil sekali')) ?></span></div>
    <div class="cred">
      <span><?= e(t('Username')) ?> <b class="mono" id="cred-u"><?= e($credential['username']) ?></b></span>
      <span><?= e(t('Password sementara')) ?> <b class="mono" id="cred-p"><?= e($credential['password']) ?></b></span>
      <span><?= e(t('Alamat login')) ?> <b class="mono"><?= e(full_url('masuk')) ?></b></span>
    </div>
    <textarea class="sr" id="cred-all" readonly><?= e(t('Login SmartCity Attendance')) ?>&#10;<?= e(full_url('masuk')) ?>&#10;<?= e(t('Username')) ?>: <?= e($credential['username']) ?>&#10;<?= e(t('Password sementara')) ?>: <?= e($credential['password']) ?></textarea>
    <div><button type="button" class="btn btn-outline sm" data-copy="#cred-all"><?= icon('copy', 16) ?><?= e(t('Salin kredensial')) ?></button></div>
  </div>
<?php endif; ?>
<div class="card">
  <div class="idcard"><span class="av lg t"><?= e(initials($a['name'])) ?></span><div><b><?= e($a['name']) ?></b><span class="mono">@<?= e($a['username']) ?></span></div></div>
  <div class="row"><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><i></i><?= e(t($a['status'])) ?></span><?php if ((int) $a['must_change_password'] === 1): ?><span class="bdg bdg-warn"><?= e(t('Password sementara')) ?></span><?php endif; ?></div>
  <dl class="kv">
    <div><dt><?= e(t('Event')) ?></dt><dd><?= $event ? '<a href="' . e(url('admin/event/' . $event['id'])) . '">' . e($event['name']) . '</a>' : e(t('Belum ditugaskan')) ?></dd></div>
    <div><dt><?= e(t('Total scan')) ?></dt><dd><?= e(t(':n peserta', ['n' => (int) $scans])) ?></dd></div>
    <div><dt><?= e(t('Login terakhir')) ?></dt><dd><?= e(datetime_id($a['last_login_at'])) ?></dd></div>
    <div><dt><?= e(t('Dibuat')) ?></dt><dd><?= e(datetime_id($a['created_at'])) ?></dd></div>
  </dl>
</div>
<form method="post" action="<?= e(url($base . '/ubah')) ?>" class="card">
  <?= csrf_field() ?>
  <div class="card-h"><h3><?= e(t('Ubah akun')) ?></h3></div>
  <div class="g-form two">
    <div class="fld"><label for="name"><?= e(t('Nama petugas')) ?></label><input class="inp" id="name" name="name" maxlength="120" required value="<?= e($a['name']) ?>"></div>
    <div class="fld"><label for="event_id"><?= e(t('Ditugaskan ke event')) ?></label><?= \App\Core\View::partial('event_select', ['events' => $events, 'value' => $a['event_id']]) ?></div>
  </div>
  <div><button class="btn btn-primary" type="submit"><?= e(t('Simpan')) ?></button></div>
</form>
<div class="card">
  <div class="card-h"><h3><?= e(t('Keamanan akun')) ?></h3></div>
  <div class="row">
    <?= post_button($base . '/reset', t('Buat password sementara baru'), [], 'btn btn-outline sm', t('Buat password sementara baru? Password lama langsung tidak berlaku.'), 'key') ?>
    <?= $a['status'] === 'Aktif'
        ? post_button($base . '/status', t('Nonaktifkan akun'), [], 'btn btn-danger sm', t('Nonaktifkan akun @:username? Petugas tidak bisa login sampai diaktifkan lagi.', ['username' => $a['username']]), 'power')
        : post_button($base . '/status', t('Aktifkan akun'), [], 'btn btn-primary sm', '', 'power') ?>
  </div>
  <p class="small muted"><?= e(t('Riwayat scan petugas tetap tersimpan meskipun akun dinonaktifkan.')) ?></p>
</div>
