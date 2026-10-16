<!doctype html>
<html lang="id">
<head>
<?= \App\Core\View::partial('head', ['title' => $title ?? '']) ?>
</head>
<body>
<div class="public-wrap">
  <header class="pub-h">
    <?= logo_mark(34) ?>
    <a class="t" href="<?= e(url()) ?>">SmartCity Attendance<small>Registrasi &amp; absensi event</small></a>
    <a class="btn btn-ghost sm" href="<?= e(url('masuk')) ?>"><?= icon('lock', 16) ?><span>Masuk panitia</span></a>
  </header>
  <main class="public-main" id="main"><?= $content ?></main>
  <footer class="foot">© <?= date('Y') ?> <?= e(config('app_name', 'SmartCity Attendance')) ?><?php if (config('contact_phone')): ?> · Bantuan: <?= e(config('contact_phone')) ?><?php endif; ?></footer>
</div>
<?= \App\Core\View::partial('confirm') ?>
<?= \App\Core\View::partial('toasts') ?>
<?php foreach (\App\Core\View::scripts() as $s): ?><script src="<?= e(asset($s)) ?>" defer></script>
<?php endforeach; ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
