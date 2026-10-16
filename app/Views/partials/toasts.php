<div class="toasts" id="toasts" aria-live="polite">
<?php foreach (take_flash() as $f): $ic = ['ok' => 'check', 'bad' => 'alert', 'info' => 'info', 'warn' => 'alert'][$f['type']] ?? 'info'; ?>
  <div class="toast <?= e($f['type']) ?>" role="status">
    <span class="ti"><?= icon($ic, 16) ?></span>
    <div><b><?= e($f['title']) ?></b><?php if ($f['text'] !== ''): ?><span><?= e($f['text']) ?></span><?php endif; ?></div>
    <button type="button" data-dismiss aria-label="<?= e(t('Tutup')) ?>"><?= icon('x', 16) ?></button>
  </div>
<?php endforeach; ?>
</div>
