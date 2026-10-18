<?php $icons = ['ok' => 'check', 'warn' => 'alert', 'bad' => 'x']; ?>
<div class="checks">
  <?php foreach ($checks as [$tone, $msg]): ?>
    <div class="ck ck-<?= e($tone) ?>"><?= icon($icons[$tone] ?? 'info', 16) ?><span><?= e($msg) ?></span></div>
  <?php endforeach; ?>
</div>
