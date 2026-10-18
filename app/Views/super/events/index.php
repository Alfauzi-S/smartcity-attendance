<div class="pagehead">
  <div><h2><?= e(t('Event')) ?></h2><p><?= e(t(':n event terdaftar', ['n' => count($events)])) ?></p></div>
  <?php if (is_super()): ?><a class="btn btn-primary" href="<?= e(url('admin/event/baru')) ?>"><?= icon('plus', 18) ?><?= e(t('Buat event')) ?></a><?php endif; ?>
</div>
<?php if (!$events): ?>
  <?= \App\Core\View::partial('no_event', ['heading' => t('Belum ada event'), 'text' => t('Buat event pertama untuk membuka registrasi peserta.'), 'cta' => ['admin/event/baru', t('Buat event')]]) ?>
<?php else: ?>
<div class="g-ev">
  <?php foreach ($events as $ev): ?>
    <a class="evc" href="<?= e(url('admin/event/' . $ev['id'])) ?>">
      <div class="row"><?= event_badge($ev['status']) ?><?= reg_badge($ev['reg_status']) ?></div>
      <h3><?= e($ev['name']) ?></h3>
      <div class="em">
        <span><?= icon('calendar', 16) ?><?= e(date_id($ev['event_date'], true)) ?> · <?= e($ev['start_time']) ?>–<?= e($ev['end_time']) ?></span>
        <span><?= icon('pin', 16) ?><?= e($ev['location']) ?></span>
      </div>
      <div class="ef"><span><b><?= (int) $ev['stats']['hadir'] ?></b> <?= e(t('hadir dari :total', ['total' => (int) $ev['stats']['total']])) ?></span><?= att_badge($ev['att_status']) ?></div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
