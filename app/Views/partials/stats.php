<div class="g-stats">
  <div class="stat t-navy"><span class="k"><span class="dot"><?= icon('users', 16) ?></span>Total peserta</span><span class="v"><?= (int) $stats['total'] ?></span><span class="s">Terdaftar di event ini</span></div>
  <div class="stat t-ok"><span class="k"><span class="dot"><?= icon('check', 16) ?></span>Hadir</span><span class="v"><?= (int) $stats['hadir'] ?></span><span class="s"><?= e($stats['pct']) ?> dari total</span></div>
  <div class="stat t-warn"><span class="k"><span class="dot"><?= icon('clock', 16) ?></span>Belum hadir</span><span class="v"><?= (int) $stats['notyet'] ?></span><span class="s"><?= (int) $stats['belum'] ?> belum diverifikasi · <?= (int) $stats['tidak'] ?> tidak hadir</span></div>
  <div class="stat t-info"><span class="k"><span class="dot"><?= icon('chart', 16) ?></span>Persentase</span><span class="v"><?= e($stats['pct']) ?></span><span class="s">Tingkat kehadiran</span></div>
</div>
