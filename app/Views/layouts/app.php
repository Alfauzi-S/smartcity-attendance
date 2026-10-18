<?php
use App\Core\Auth;

$role = $user['role'] ?? '';
$isSuper = $role === Auth::SUPER;
$isManager = in_array($role, [Auth::SUPER, Auth::EVENT], true);
$roleCls = $isSuper ? '' : ($role === Auth::EVENT ? ' evt' : ' adm');
$active = $active ?? '';
if ($isManager) {
    // Menu Admin Event hanya menampilkan fitur yang izinnya dimiliki (keamanan tetap dicek di controller).
    $nav = array_values(array_filter([
        ['dashboard', 'admin', 'grid', 'Dashboard'],
        ['events', 'admin/event', 'calendar', 'Event'],
        can('participants') ? ['participants', 'admin/peserta', 'users', 'Peserta'] : null,
        can('reports') ? ['reports', 'admin/laporan', 'chart', 'Laporan'] : null,
        can('reports') ? ['history', 'admin/riwayat', 'history', 'Riwayat'] : null,
        can('cert_any') ? ['certificates', 'admin/sertifikat', 'award', 'Sertifikat'] : null,
        $isSuper ? ['accounts', 'admin/akun', 'shield', 'Akun Admin'] : null,
        can('audit') ? ['audit', 'admin/audit', 'file', 'Audit Log'] : null,
        can('attendance') ? ['scan', 'petugas/scan', 'scan', 'Scan QR'] : null,
        ['settings', 'admin/pengaturan', 'sliders', 'Pengaturan'],
    ]));
    $bottomKeys = ['dashboard', 'events', 'participants', 'certificates'];
    $bottom = array_values(array_filter($nav, static fn ($n) => in_array($n[0], $bottomKeys, true)));
    $bottom = array_map(static fn ($n) => $n[0] === 'dashboard' ? ['dashboard', 'admin', 'grid', 'Beranda'] : $n, $bottom);
    $more = array_values(array_filter($nav, static fn ($n) => !in_array($n[0], $bottomKeys, true)));
} else {
    $nav = [
        ['dashboard', 'petugas', 'home', 'Beranda'],
        ['scan', 'petugas/scan', 'scan', 'Scan QR'],
        ['participants', 'petugas/peserta', 'users', 'Peserta'],
        ['profile', 'petugas/profil', 'user', 'Profil'],
    ];
    $bottom = [['dashboard', 'petugas', 'home', 'Beranda'], ['scan', 'petugas/scan', 'scan', 'Scan'], ['participants', 'petugas/peserta', 'users', 'Peserta'], ['profile', 'petugas/profil', 'user', 'Profil']];
    $more = [];
}
$moreActive = $isManager && in_array($active, array_column($more, 0), true);
$roleName = Auth::roleLabel($role);
?>
<!doctype html>
<html lang="<?= e(lang()) ?>">
<head>
<?= \App\Core\View::partial('head', ['title' => $title ?? '']) ?>
</head>
<body>
<div class="app">
  <aside class="side" aria-label="<?= e(t('Navigasi utama')) ?>">
    <a class="sb-brand" href="<?= e(url($isManager ? 'admin' : 'petugas')) ?>">
      <?= logo_mark(40) ?>
      <span class="t">SmartCity<small>Attendance</small></span>
    </a>
    <span class="role<?= $roleCls ?>"><?= e($roleName) ?></span>
    <?php foreach ($nav as [$key, $path, $ic, $label]): ?>
      <a class="nav-i<?= $active === $key ? ' on' : '' ?>" href="<?= e(url($path)) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e(t($label)) ?></span></a>
    <?php endforeach; ?>
    <div class="sp"></div>
    <form method="post" action="<?= e(url('keluar')) ?>"><?= csrf_field() ?>
      <button class="nav-i" type="submit"><?= icon('logout') ?><span><?= e(t('Keluar')) ?></span></button>
    </form>
    <?php if ($user): ?>
    <div class="me"><span class="av<?= $isManager ? '' : ' t' ?>"><?= e(initials($user['name'])) ?></span><div><b><?= e($user['name']) ?></b><span>@<?= e($user['username']) ?></span></div></div>
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
      <span class="rolechip<?= $roleCls ?> only-wide"><?= e($roleName) ?></span>
      <?php if ($user): ?><span class="av<?= $isSuper ? '' : ' t' ?> only-wide" title="<?= e($user['name']) ?>"><?= e(initials($user['name'])) ?></span><?php endif; ?>
      <?php if (!$isManager): ?>
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
      <?php if ($isManager): ?>
        <button type="button" class="<?= $moreActive ? 'on' : '' ?>" data-open="more-menu"><?= icon('menu') ?><span><?= e(t('Lainnya')) ?></span></button>
      <?php endif; ?>
    </nav>
  </div>
</div>
<?php if ($isManager): ?>
<div class="ov" id="more-menu" hidden role="dialog" aria-modal="true" aria-label="<?= e(t('Menu lainnya')) ?>">
  <div class="sheet">
    <div class="grab"></div>
    <div class="sh-h"><div><h3><?= e(t('Menu lainnya')) ?></h3><p><?= e($user['name'] ?? '') ?> · <?= e($roleName) ?></p></div><button type="button" class="iconbtn plain" data-close aria-label="<?= e(t('Tutup')) ?>"><?= icon('x') ?></button></div>
    <div class="menu-l">
      <?php foreach ($more as [$key, $path, $ic, $label]): ?><a href="<?= e(url($path)) ?>"><?= icon($ic) ?><?= e(t($label)) ?></a><?php endforeach; ?>
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
