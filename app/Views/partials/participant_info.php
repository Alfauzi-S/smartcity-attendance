<?php use App\Models\Participant; ?>
<div class="card">
  <div class="idcard">
    <span class="av lg<?= $p['status'] === 'hadir' ? ' t' : '' ?>"><?= e(initials($p['full_name'])) ?></span>
    <div style="min-width:0"><b><?= e($p['full_name']) ?></b><span class="mono"><?= e($p['identity_number']) ?></span></div>
  </div>
  <div class="row"><?= status_badge($p['status'], true) ?><span class="code"><?= e($p['code']) ?></span></div>
  <dl class="kv">
    <div><dt><?= e(t('Profesi')) ?></dt><dd><?= e(t($p['profession'])) ?></dd></div>
    <div><dt><?= e(t('Institusi')) ?></dt><dd><?= e($p['institution']) ?></dd></div>
    <div><dt><?= e(t('Program studi')) ?></dt><dd><?= e(t($p['field_of_study'])) ?></dd></div>
    <?php if (!empty($showPhone)): ?><div><dt><?= e(t('WhatsApp / HP')) ?></dt><dd class="mono"><?= e($p['phone']) ?></dd></div><?php endif; ?>
    <div><dt><?= e(t('Terdaftar')) ?></dt><dd><?= e(datetime_id($p['created_at'])) ?></dd></div>
    <div><dt><?= e(t('Waktu hadir')) ?></dt><dd><?= $p['status'] === 'hadir' ? e(datetime_id($p['checked_in_at'])) : '—' ?></dd></div>
    <div><dt><?= e(t('Metode')) ?></dt><dd><?= e($p['status'] === 'belum' ? '—' : t(Participant::methodLabel($p['method']))) ?></dd></div>
    <?php if (!empty($p['checked_in_by_name'])): ?><div><dt><?= e($p['method'] === 'manual' ? t('Diubah oleh') : t('Dipindai oleh')) ?></dt><dd><?= e($p['checked_in_by_name']) ?></dd></div><?php endif; ?>
  </dl>
</div>
