<?php
$eid = (int) $event['id'];
$manage = can('cert_manage', $eid);
$tabs = array_filter([
    'dashboard' => ['', 'Dashboard', true],
    'templates' => ['template', 'Template', $manage],
    'assets' => ['aset', 'Aset resmi', $manage],
    'variables' => ['variabel', 'Variabel', $manage],
    'numbering' => ['nomor', 'Nomor', $manage],
    'eligibility' => ['kelayakan', 'Kelayakan', $manage],
    'generate' => ['generate', 'Generate', $manage],
    'issued' => ['terbit', 'Sertifikat terbit', true],
    'history' => ['riwayat', 'Riwayat', true],
], static fn ($t) => $t[2]);
$path = 'admin/sertifikat' . ($tabs[$tab][0] ?? '' ? '/' . $tabs[$tab][0] : '');
?>
<div class="pagehead"><div><h2><?= e(t('Sertifikat')) ?></h2><p><?= e($event['name']) ?> · <?= e(date_id($event['event_date'])) ?></p></div></div>
<?= \App\Core\View::partial('event_picker', ['events' => $events, 'event' => $event, 'path' => $path]) ?>
<nav class="chips" aria-label="<?= e(t('Menu sertifikat')) ?>">
  <?php foreach ($tabs as $k => [$p, $label]): ?>
    <a class="chip<?= $tab === $k ? ' on' : '' ?>" href="<?= e(url('admin/sertifikat' . ($p !== '' ? '/' . $p : ''), ['ev' => $eid])) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= e(t($label)) ?></a>
  <?php endforeach; ?>
</nav>
