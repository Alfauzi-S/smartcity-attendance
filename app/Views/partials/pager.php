<?php if ($pages > 1): $qs = $query ?? []; ?>
<nav class="pager" aria-label="<?= e(t('Halaman')) ?>">
  <span><?= e(t('Halaman :current dari :last · :total data', ['current' => (int) $page, 'last' => (int) $pages, 'total' => (int) $total])) ?></span>
  <div class="row">
    <?php if ($page > 1): ?><a class="btn btn-outline sm" href="<?= e(url($path, $qs + ['hal' => $page - 1])) ?>"><?= icon('back', 16) ?><?= e(t('Sebelumnya')) ?></a><?php endif; ?>
    <?php if ($page < $pages): ?><a class="btn btn-outline sm" href="<?= e(url($path, $qs + ['hal' => $page + 1])) ?>"><?= e(t('Berikutnya')) ?><?= icon('chev', 16) ?></a><?php endif; ?>
  </div>
</nav>
<?php elseif (isset($total)): ?>
<p class="small muted"><?= e(t(':total data ditampilkan', ['total' => (int) $total])) ?></p>
<?php endif; ?>
