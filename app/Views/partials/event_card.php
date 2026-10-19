<div class="card">
  <div class="card-h"><div><span class="sec-t"><?= e(t('Event yang ditugaskan')) ?></span><h3 style="margin-top:6px"><?= e($event['name']) ?></h3></div><?= att_badge($event['att_status']) ?></div>
  <div class="row small muted">
    <span class="row" style="gap:6px"><?= icon('calendar', 16) ?><?= e(date_id($event['event_date'], true)) ?></span>
    <span class="row" style="gap:6px"><?= icon('clock', 16) ?><?= e(t('Presensi')) ?> <?= e($event['att_start']) ?>–<?= e($event['att_end']) ?> <?= e(config('timezone_label', 'WITA')) ?></span>
    <span class="row" style="gap:6px"><?= icon('pin', 16) ?><?= e($event['location']) ?></span>
  </div>
</div>
