<div class="g-stats">
  <div class="stat t-navy"><span class="k"><span class="dot"><?= icon('users', 16) ?></span><?= e(t('Total peserta')) ?></span><span class="v"><?= (int) $stats['total'] ?></span><span class="s"><?= e(t('Terdaftar di event ini')) ?></span></div>
  <div class="stat t-ok"><span class="k"><span class="dot"><?= icon('check', 16) ?></span><?= e(t('Hadir')) ?></span><span class="v"><?= (int) $stats['hadir'] ?></span><span class="s"><?= e(t(':pct dari total', ['pct' => $stats['pct']])) ?></span></div>
  <div class="stat t-warn"><span class="k"><span class="dot"><?= icon('clock', 16) ?></span><?= e(t('Belum hadir')) ?></span><span class="v"><?= (int) $stats['notyet'] ?></span><span class="s"><?= e(t(':unverified belum diverifikasi · :absent tidak hadir', ['unverified' => (int) $stats['belum'], 'absent' => (int) $stats['tidak']])) ?></span></div>
  <div class="stat t-info"><span class="k"><span class="dot"><?= icon('chart', 16) ?></span><?= e(t('Persentase')) ?></span><span class="v"><?= e($stats['pct']) ?></span><span class="s"><?= e(t('Tingkat kehadiran')) ?></span></div>
</div>
