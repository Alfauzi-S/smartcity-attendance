<div class="login">
  <div class="art">
    <div class="row" style="position:relative"><?= logo_mark(40) ?><b style="font-family:var(--f-display);color:#fff">SmartCity Attendance</b></div>
    <div class="stack" style="position:relative">
      <h2><?= e(t('Absensi event yang cepat dan tercatat.')) ?></h2>
      <p><?= e(t('Masuk sebagai Admin Absensi untuk memindai QR peserta, atau sebagai Super Admin untuk mengelola event, akun, dan laporan.')) ?></p>
    </div>
    <img class="city" src="<?= e(asset('img/city.svg')) ?>" alt="">
  </div>
  <div class="form">
    <div class="only-narrow row"><?= logo_mark(40) ?><b style="font-family:var(--f-display)">SmartCity Attendance</b></div>
    <div><h2 style="font-size:26px"><?= e(t('Masuk panitia')) ?></h2><p class="muted"><?= e(t('Peserta tidak perlu login. Halaman ini khusus petugas dan Super Admin.')) ?></p></div>
    <?php if ($error !== ''): ?>
      <div class="banner bad" role="alert"><?= icon('alert') ?><div class="grow"><?= e(t($error)) ?></div></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('masuk')) ?>" class="stack" data-loading="<?= e(t('Memeriksa akun…')) ?>">
      <?= csrf_field() ?>
      <div class="fld"><label for="username"><?= e(t('Username')) ?></label>
        <div class="inp-ic"><?= icon('user', 18) ?><input class="inp" id="username" name="username" autocomplete="username" autocapitalize="none" spellcheck="false" required value="<?= e($username) ?>"></div></div>
      <div class="fld"><label for="password"><?= e(t('Password')) ?></label>
        <div class="inp-ic"><?= icon('lock', 18) ?><input class="inp" id="password" name="password" type="password" autocomplete="current-password" required></div></div>
      <label class="chk"><input type="checkbox" data-show-password="#password"><?= e(t('Tampilkan password')) ?></label>
      <button type="submit" class="btn btn-primary lg block"><?= e(t('Masuk')) ?></button>
    </form>
    <p class="small muted"><?= e(t('Lupa password? Hubungi Super Admin untuk membuat password sementara.')) ?></p>
    <a class="btn btn-ghost" href="<?= e(url()) ?>"><?= icon('back', 18) ?><?= e(t('Ke halaman event')) ?></a>
  </div>
</div>
