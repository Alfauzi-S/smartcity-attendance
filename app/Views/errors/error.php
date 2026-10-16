<?php $tone = in_array((int) $code, [403, 419], true) ? 's-warn' : ((int) $code >= 500 ? 's-bad' : 's-info'); ?>
<div class="bare">
  <div class="bare-c">
    <div class="card">
      <div class="state <?= $tone ?>">
        <span class="sic"><?= icon((int) $code === 404 ? 'search' : ((int) $code === 403 ? 'lock' : 'alert'), 30) ?></span>
        <span class="bdg bdg-mute mono"><?= e(t('Kode :code', ['code' => (int) $code])) ?></span>
        <h3><?= e($heading) ?></h3>
        <?php if ($message !== ''): ?><p><?= e($message) ?></p><?php endif; ?>
        <?php if (!empty($debug)): ?><pre class="small" style="text-align:left;white-space:pre-wrap;overflow:auto;max-height:280px;width:100%"><?= e($debug) ?></pre><?php endif; ?>
        <div class="acts">
          <a class="btn btn-primary" href="<?= e(url()) ?>"><?= e(t('Ke halaman utama')) ?></a>
          <button type="button" class="btn btn-outline" onclick="history.back()"><?= e(t('Kembali')) ?></button>
        </div>
      </div>
    </div>
  </div>
</div>
