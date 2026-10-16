<!doctype html>
<html lang="id">
<head>
<?= \App\Core\View::partial('head', ['title' => $title ?? '']) ?>
</head>
<body>
<?= $content ?>
<?= \App\Core\View::partial('confirm') ?>
<?= \App\Core\View::partial('toasts') ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
