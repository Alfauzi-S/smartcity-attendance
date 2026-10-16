<?php $t = max(1, (int) $stats['total']); ?>
<div class="bar" role="img" aria-label="Hadir <?= (int) $stats['hadir'] ?>, belum diverifikasi <?= (int) $stats['belum'] ?>, tidak hadir <?= (int) $stats['tidak'] ?>">
  <i class="h" style="width:<?= round($stats['hadir'] / $t * 100, 2) ?>%"></i><i class="b" style="width:<?= round($stats['belum'] / $t * 100, 2) ?>%"></i><i class="t" style="width:<?= round($stats['tidak'] / $t * 100, 2) ?>%"></i>
</div>
<div class="legend">
  <span><i style="background:var(--teal)"></i>Hadir <?= (int) $stats['hadir'] ?></span>
  <span><i style="background:#F8C46A"></i>Belum diverifikasi <?= (int) $stats['belum'] ?></span>
  <span><i style="background:var(--error)"></i>Tidak Hadir <?= (int) $stats['tidak'] ?></span>
</div>
