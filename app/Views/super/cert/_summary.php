<div class="g-stats">
  <div class="stat t-navy"><span class="k"><span class="dot"><?= icon('users', 16) ?></span><?= e(t('Peserta terdaftar')) ?></span><span class="v"><?= (int) $summary['total'] ?></span></div>
  <div class="stat t-ok"><span class="k"><span class="dot"><?= icon('check', 16) ?></span><?= e(t('Memenuhi syarat')) ?></span><span class="v"><?= (int) $summary['eligible'] ?></span><span class="s"><?= e(t(':n sudah punya sertifikat', ['n' => $summary['issued']])) ?></span></div>
  <div class="stat t-warn"><span class="k"><span class="dot"><?= icon('x', 16) ?></span><?= e(t('Tidak memenuhi syarat')) ?></span><span class="v"><?= (int) $summary['ineligible'] ?></span><span class="s"><?php foreach ($summary['reasons'] as $r => $n): ?><?= e(t($r)) ?>: <?= (int) $n ?><br><?php endforeach; ?></span></div>
  <div class="stat t-info"><span class="k"><span class="dot"><?= icon('award', 16) ?></span><?= e(t('Akan dibuat')) ?></span><span class="v"><?= (int) $summary['to_generate'] ?></span><span class="s"><?= e(t('Layak dan belum punya sertifikat')) ?></span></div>
</div>
