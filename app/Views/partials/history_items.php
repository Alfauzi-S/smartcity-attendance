<?php if (!$items): ?>
  <p class="muted small"><?= e(t('Belum ada perubahan status manual.')) ?></p>
<?php else: ?>
<div class="hist">
  <?php foreach ($items as $h): ?>
    <div class="hi">
      <span class="hic"><?= icon('history', 18) ?></span>
      <div class="hb">
        <span class="t1"><a href="<?= e(url('admin/peserta/' . $h['participant_id'])) ?>" style="font-weight:600;color:var(--ink)"><?= e($h['full_name']) ?></a><?php if (!empty($h['identity_number'])): ?> <span class="mono small muted"><?= e($h['identity_number']) ?></span><?php endif; ?></span>
        <span class="t2"><?= status_badge($h['old_status']) ?><?= icon('arrow', 14) ?><?= status_badge($h['new_status']) ?></span>
        <?php if ($h['reason'] !== null && $h['reason'] !== ''): ?><q><?= e($h['reason']) ?></q><?php endif; ?>
        <span class="t3"><?= e(datetime_id($h['changed_at'])) ?> · <?= e(t('oleh :user', ['user' => $h['changed_by_username'] ? '@' . $h['changed_by_username'] : t('Super Admin')])) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
