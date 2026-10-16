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
<html lang="id">
<head>
<?= \App\Core\View::partial('head', ['title' => $title ?? '']) ?>
</head>
<body>
<div class="app">
  <aside class="side" aria-label="Navigasi utama">
    <a class="sb-brand" href="<?= e(url($isSuper ? 'admin' : 'petugas')) ?>">
      <?= logo_mark(40) ?>
      <span class="t">SmartCity<small>Attendance</small></span>
    </a>
    <span class="role<?= $isSuper ? '' : ' adm' ?>"><?= $isSuper ? 'Super Admin' : 'Admin Absensi' ?></span>
    <?php foreach ($nav as [$key, $path, $ic, $label]): ?>
      <a class="nav-i<?= $active === $key ? ' on' : '' ?>" href="<?= e(url($path)) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
    <div class="sp"></div>
    <form method="post" action="<?= e(url('keluar')) ?>"><?= csrf_field() ?>
      <button class="nav-i" type="submit"><?= icon('logout') ?><span>Keluar</span></button>
    </form>
    <?php if ($user): ?>
    <div class="me"><span class="av<?= $isSuper ? '' : ' t' ?>"><?= e(initials($user['name'])) ?></span><div><b><?= e($user['name']) ?></b><span>@<?= e($user['username']) ?></span></div></div>
    <?php endif; ?>
  </aside>
  <div class="col">
    <header class="tb">
      <?php if (!empty($back)): ?>
        <a class="iconbtn plain" href="<?= e(url($back)) ?>" aria-label="Kembali"><?= icon('back') ?></a>
      <?php else: ?>
        <span class="only-narrow"><?= logo_mark(34) ?></span>
      <?php endif; ?>
      <h1><?= e($title ?? '') ?></h1>
      <span class="rolechip<?= $isSuper ? '' : ' adm' ?> only-wide"><?= $isSuper ? 'Super Admin' : 'Admin Absensi' ?></span>
      <?php if ($user): ?><span class="av<?= $isSuper ? '' : ' t' ?> only-wide" title="<?= e($user['name']) ?>"><?= e(initials($user['name'])) ?></span><?php endif; ?>
      <?php if (!$isSuper): ?>
      <form method="post" action="<?= e(url('keluar')) ?>" class="only-narrow" data-confirm="Keluar dari akun ini?"><?= csrf_field() ?>
        <button class="iconbtn plain" type="submit" aria-label="Keluar"><?= icon('logout') ?></button>
      </form>
      <?php endif; ?>
    </header>
    <main class="main" id="main">
      <div class="pad"><?= $content ?></div>
    </main>
    <nav class="bnav" aria-label="Navigasi bawah">
      <?php foreach ($bottom as $i => [$key, $path, $ic, $label]):
        $fab = !$isSuper && $key === 'scan'; ?>
        <a class="<?= $active === $key ? 'on' : '' ?><?= $fab ? ' fab' : '' ?>" href="<?= e(url($path)) ?>"><?= icon($ic) ?><span><?= e($label) ?></span></a>
      <?php endforeach; ?>
      <?php if ($isSuper): ?>
        <button type="button" class="<?= $moreActive ? 'on' : '' ?>" data-open="more-menu"><?= icon('menu') ?><span>Lainnya</span></button>
      <?php endif; ?>
    </nav>
  </div>
</div>
<?php if ($isSuper): ?>
<div class="ov" id="more-menu" hidden role="dialog" aria-modal="true" aria-label="Menu lainnya">
  <div class="sheet">
    <div class="grab"></div>
    <div class="sh-h"><div><h3>Menu lainnya</h3><p><?= e($user['name'] ?? '') ?> · Super Admin</p></div><button type="button" class="iconbtn plain" data-close aria-label="Tutup"><?= icon('x') ?></button></div>
    <div class="menu-l">
      <a href="<?= e(url('petugas/scan')) ?>"><?= icon('scan') ?>Scan QR</a>
      <a href="<?= e(url('admin/riwayat')) ?>"><?= icon('history') ?>Riwayat Perubahan</a>
      <a href="<?= e(url('admin/akun')) ?>"><?= icon('shield') ?>Akun Admin Absensi</a>
      <a href="<?= e(url('admin/pengaturan')) ?>"><?= icon('sliders') ?>Pengaturan Akun</a>
      <form method="post" action="<?= e(url('keluar')) ?>"><?= csrf_field() ?><button type="submit" class="dng"><?= icon('logout') ?>Keluar</button></form>
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
