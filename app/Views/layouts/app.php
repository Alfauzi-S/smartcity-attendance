<?php
use App\Core\Auth;

$isSuper = ($user['role'] ?? '') === Auth::SUPER;
$active = $active ?? '';
if ($isSuper) {
    $nav = [
        ['dashboard', 'admin', 'grid', 'Dashboard'],
        ['events', 'admin/event', 'calendar', 'Event'],
        ['participants', 'admin/peserta', 'users', 'Peserta'],
        ['reports', 'admin/laporan', 'chart', 'Laporan'],
        ['history', 'admin/riwayat', 'history', 'Riwayat'],
        ['accounts', 'admin/akun', 'shield', 'Akun Admin'],
        ['scan', 'petugas/scan', 'scan', 'Scan QR'],
        ['settings', 'admin/pengaturan', 'sliders', 'Pengaturan'],
    ];
    $bottom = [['dashboard', 'admin', 'grid', 'Beranda'], ['events', 'admin/event', 'calendar', 'Event'], ['participants', 'admin/peserta', 'users', 'Peserta'], ['reports', 'admin/laporan', 'chart', 'Laporan']];
} else {
    $nav = [
        ['dashboard', 'petugas', 'home', 'Beranda'],
        ['scan', 'petugas/scan', 'scan', 'Scan QR'],
        ['participants', 'petugas/peserta', 'users', 'Peserta'],
        ['profile', 'petugas/profil', 'user', 'Profil'],
    ];
    $bottom = [['dashboard', 'petugas', 'home', 'Beranda'], ['scan', 'petugas/scan', 'scan', 'Scan'], ['participants', 'petugas/peserta', 'users', 'Peserta'], ['profile', 'petugas/profil', 'user', 'Profil']];
}
$moreActive = $isSuper && in_array($active, ['history', 'accounts', 'scan', 'settings'], true);
?>
<!doctype html>
<html lang="<?= e(lang()) ?>">
<head>
<?= \App\Core\View::partial('head', ['title' => $title ?? '']) ?>
</head>
<body>
<div class="app">
  <aside class="side" aria-label="<?= e(t('Navigasi utama')) ?>">
    <a class="sb-brand" href="<?= e(url($isSuper ? 'admin' : 'petugas')) ?>">
      <?= logo_mark(40) ?>
      <span class="t">SmartCity<small>Attendance</small></span>
    </a>
    <span class="role<?= $isSuper ? '' : ' adm' ?>"><?= e($isSuper ? 'Super Admin' : t('Admin Absensi')) ?></span>
    <?php foreach ($nav as [$key, $path, $ic, $label]): ?>
      <a class="nav-i<?= $active === $key ? ' on' : '' ?>" href="<?= e(url($path)) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e(t($label)) ?></span></a>
    <?php endforeach; ?>
    <div class="sp"></div>
    <form method="post" action="<?= e(url('keluar')) ?>"><?= csrf_field() ?>
      <button class="nav-i" type="submit"><?= icon('logout') ?><span><?= e(t('Keluar')) ?></span></button>
    </form>
    <?php if ($user): ?>
    <div class="me"><span class="av<?= $isSuper ? '' : ' t' ?>"><?= e(initials($user['name'])) ?></span><div><b><?= e($user['name']) ?></b><span>@<?= e($user['username']) ?></span></div></div>
    <?php endif; ?>
  </aside>
  <div class="col">
    <header class="tb">
      <?php if (!empty($back)): ?>
        <a class="iconbtn plain" href="<?= e(url($back)) ?>" aria-label="<?= e(t('Kembali')) ?>"><?= icon('back') ?></a>
      <?php else: ?>
        <span class="only-narrow"><?= logo_mark(34) ?></span>
      <?php endif; ?>
      <h1><?= e(t($title ?? '')) ?></h1>
      <?= \App\Core\View::partial('lang_switch') ?>
      <span class="rolechip<?= $isSuper ? '' : ' adm' ?> only-wide"><?= e($isSuper ? 'Super Admin' : t('Admin Absensi')) ?></span>
      <?php if ($user): ?><span class="av<?= $isSuper ? '' : ' t' ?> only-wide" title="<?= e($user['name']) ?>"><?= e(initials($user['name'])) ?></span><?php endif; ?>
      <?php if (!$isSuper): ?>
      <form method="post" action="<?= e(url('keluar')) ?>" class="only-narrow" data-confirm="<?= e(t('Keluar dari akun ini?')) ?>"><?= csrf_field() ?>
        <button class="iconbtn plain" type="submit" aria-label="<?= e(t('Keluar')) ?>"><?= icon('logout') ?></button>
      </form>
      <?php endif; ?>
    </header>
    <main class="main" id="main">
      <div class="pad"><?= $content ?></div>
    </main>
    <nav class="bnav" aria-label="<?= e(t('Navigasi bawah')) ?>">
      <?php foreach ($bottom as [$key, $path, $ic, $label]): ?>
        <a class="<?= $active === $key ? 'on' : '' ?>" href="<?= e(url($path)) ?>"><?= icon($ic) ?><span><?= e(t($label)) ?></span></a>
      <?php endforeach; ?>
      <?php if ($isSuper): ?>
        <button type="button" class="<?= $moreActive ? 'on' : '' ?>" data-open="more-menu"><?= icon('menu') ?><span><?= e(t('Lainnya')) ?></span></button>
      <?php endif; ?>
    </nav>
  </div>
</div>
<?php if ($isSuper): ?>
<div class="ov" id="more-menu" hidden role="dialog" aria-modal="true" aria-label="<?= e(t('Menu lainnya')) ?>">
  <div class="sheet">
    <div class="grab"></div>
    <div class="sh-h"><div><h3><?= e(t('Menu lainnya')) ?></h3><p><?= e($user['name'] ?? '') ?> · Super Admin</p></div><button type="button" class="iconbtn plain" data-close aria-label="<?= e(t('Tutup')) ?>"><?= icon('x') ?></button></div>
    <div class="menu-l">
      <a href="<?= e(url('petugas/scan')) ?>"><?= icon('scan') ?><?= e(t('Scan QR')) ?></a>
      <a href="<?= e(url('admin/riwayat')) ?>"><?= icon('history') ?><?= e(t('Riwayat Perubahan')) ?></a>
      <a href="<?= e(url('admin/akun')) ?>"><?= icon('shield') ?><?= e(t('Akun Admin Absensi')) ?></a>
      <a href="<?= e(url('admin/pengaturan')) ?>"><?= icon('sliders') ?><?= e(t('Pengaturan Akun')) ?></a>
      <form method="post" action="<?= e(url('keluar')) ?>"><?= csrf_field() ?><button type="submit" class="dng"><?= icon('logout') ?><?= e(t('Keluar')) ?></button></form>
    </div>
  </div>
</div>
<?php endif; ?>
<?= \App\Core\View::partial('confirm') ?>
<?= \App\Core\View::partial('toasts') ?>
<?php foreach (\App\Core\View::scripts() as $s): ?><script src="<?= e(asset($s)) ?>" defer></script>
<?php endforeach; ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
