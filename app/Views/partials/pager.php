<?php if ($pages > 1): $qs = $query ?? []; ?>
<nav class="pager" aria-label="Halaman">
  <span>Halaman <?= (int) $page ?> dari <?= (int) $pages ?> · <?= (int) $total ?> data</span>
  <div class="row">
    <?php if ($page > 1): ?><a class="btn btn-outline sm" href="<?= e(url($path, $qs + ['hal' => $page - 1])) ?>"><?= icon('back', 16) ?>Sebelumnya</a><?php endif; ?>
    <?php if ($page < $pages): ?><a class="btn btn-outline sm" href="<?= e(url($path, $qs + ['hal' => $page + 1])) ?>">Berikutnya<?= icon('chev', 16) ?></a><?php endif; ?>
  </div>
</nav>
<?php elseif (isset($total)): ?>
<p class="small muted"><?= (int) $total ?> data ditampilkan</p>
<?php endif; ?>
