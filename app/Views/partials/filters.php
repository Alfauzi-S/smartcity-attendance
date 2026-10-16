<?php
$opts = ['all' => 'Semua', 'hadir' => 'Hadir', 'belum' => 'Belum diverifikasi', 'tidak' => 'Tidak Hadir'];
?>
<form method="get" action="<?= e(url($path)) ?>" class="filters" role="search">
  <div class="inp-ic search"><?= icon('search', 18) ?><input class="inp" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Cari nama, nomor identitas, atau kode QR')) ?>" aria-label="<?= e(t('Cari peserta')) ?>"></div>
  <div class="frow">
    <select class="inp" name="st" aria-label="<?= e(t('Filter status')) ?>" data-autosubmit-field>
      <?php foreach ($opts as $k => $label): ?><option value="<?= $k ?>"<?= selected($st, $k) ?>><?= e(t($label)) ?><?= isset($stats) && $k !== 'all' ? ' (' . (int) $stats[$k] . ')' : '' ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><?= e(t('Cari')) ?></button>
    <?php if ($q !== '' || $st !== 'all'): ?><a class="btn btn-ghost" href="<?= e(url($path)) ?>"><?= e(t('Reset')) ?></a><?php endif; ?>
  </div>
</form>
