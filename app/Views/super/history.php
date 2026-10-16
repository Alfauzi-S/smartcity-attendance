<?php use App\Core\View; ?>
<?php if (!$event): ?>
  <?= View::partial('no_event', ['heading' => 'Belum ada event', 'text' => 'Riwayat muncul setelah ada perubahan status manual.']) ?>
<?php else: ?>
  <div class="pagehead"><div><h2>Riwayat perubahan status</h2><p><?= e($event['name']) ?> · <?= (int) $total ?> perubahan manual</p></div></div>
  <?= View::partial('event_picker', ['events' => $events, 'event' => $event, 'path' => 'admin/riwayat']) ?>
  <div class="chips">
    <?php foreach (['all' => 'Semua', 'hadir' => 'Menjadi Hadir', 'tidak' => 'Menjadi Tidak Hadir'] as $k => $label): ?>
      <a class="chip<?= $to === $k ? ' on' : '' ?>" href="<?= e(url('admin/riwayat', ['ke' => $k === 'all' ? '' : $k])) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="card"><?= View::partial('history_items', ['items' => $items]) ?></div>
  <?= View::partial('pager', ['page' => $page, 'pages' => $pages, 'total' => $total, 'path' => 'admin/riwayat', 'query' => ['ke' => $to === 'all' ? '' : $to]]) ?>
<?php endif; ?>
