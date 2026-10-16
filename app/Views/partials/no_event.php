<div class="card"><div class="empty"><span class="eic"><?= icon('calendar', 26) ?></span>
  <h3><?= e($heading ?? 'Belum ada event') ?></h3>
  <p><?= e($text ?? 'Buat event terlebih dahulu.') ?></p>
  <?php if (!empty($cta)): ?><a class="btn btn-primary" href="<?= e(url($cta[0])) ?>"><?= e($cta[1]) ?></a><?php endif; ?>
</div></div>
