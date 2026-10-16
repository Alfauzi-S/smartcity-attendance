<!doctype html>
<html lang="<?= e(lang()) ?>">
<head>
<?= \App\Core\View::partial('head', ['title' => $title ?? '']) ?>
</head>
<body>
<div class="lang-float"><?= \App\Core\View::partial('lang_switch') ?></div>
<?= $content ?>
<?= \App\Core\View::partial('confirm') ?>
<?= \App\Core\View::partial('toasts') ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
