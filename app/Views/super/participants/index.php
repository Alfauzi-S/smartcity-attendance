<?php use App\Core\View; ?>
<?php if (!$event): ?>
  <?= View::partial('no_event', ['heading' => 'Belum ada event', 'text' => 'Buat event terlebih dahulu.', 'cta' => ['admin/event/baru', 'Buat event']]) ?>
<?php else: ?>
  <div class="pagehead">
    <div><h2>Peserta</h2><p><?= e($event['name']) ?> · <?= (int) $stats['hadir'] ?> hadir dari <?= (int) $stats['total'] ?> (<?= e($stats['pct']) ?>)</p></div>
    <a class="btn btn-outline sm" href="<?= e(url('admin/laporan')) ?>"><?= icon('download', 16) ?>Ekspor</a>
  </div>
  <?= View::partial('event_picker', ['events' => $events, 'event' => $event, 'path' => 'admin/peserta']) ?>
  <?= View::partial('filters', ['path' => 'admin/peserta', 'q' => $q, 'st' => $st, 'stats' => $stats]) ?>
  <?= View::partial('participant_list', ['list' => $list, 'base' => 'admin/peserta/', 'q' => $q, 'st' => $st, 'super' => true]) ?>
  <?= View::partial('pager', ['page' => $page, 'pages' => $pages, 'total' => $total, 'path' => 'admin/peserta', 'query' => ['q' => $q, 'st' => $st]]) ?>
<?php endif; ?>
