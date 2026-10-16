<?php $t = max(1, (int) $stats['total']); ?>
<div class="bar" role="img" aria-label="<?= e(t('Hadir :present, belum diverifikasi :unverified, tidak hadir :absent', ['present' => (int) $stats['hadir'], 'unverified' => (int) $stats['belum'], 'absent' => (int) $stats['tidak']])) ?>">
  <i class="h" style="width:<?= round($stats['hadir'] / $t * 100, 2) ?>%"></i><i class="b" style="width:<?= round($stats['belum'] / $t * 100, 2) ?>%"></i><i class="t" style="width:<?= round($stats['tidak'] / $t * 100, 2) ?>%"></i>
</div>
<div class="legend">
  <span><i style="background:var(--teal)"></i><?= e(t('Hadir')) ?> <?= (int) $stats['hadir'] ?></span>
  <span><i style="background:#F8C46A"></i><?= e(t('Belum diverifikasi')) ?> <?= (int) $stats['belum'] ?></span>
  <span><i style="background:var(--error)"></i><?= e(t('Tidak Hadir')) ?> <?= (int) $stats['tidak'] ?></span>
</div>
