<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0B1220">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="base-path" content="<?= e(url()) ?>">
<title><?= e(($title ?? '') !== '' ? t($title) . ' · ' . config('app_name', 'SmartCity Attendance') : config('app_name', 'SmartCity Attendance')) ?></title>
<link rel="icon" href="<?= e(asset('img/icon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<script>
window.SCA_LANG = <?= json_encode(lang()) ?>;
window.SCA_T = <?= json_encode((object) js_translations(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
/* Terjemahan teks JavaScript: T('Halo, :name', {name: 'Budi'}) */
window.T = function (s, p) { var o = window.SCA_T[s] || s; for (var k in (p || {})) { o = o.split(':' + k).join(p[k]); } return o; };
</script>
