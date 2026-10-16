<?php use App\Core\View; ?>
<?php if (!$event): ?>
  <?= View::partial('no_event', ['heading' => 'Selamat datang di SmartCity Attendance', 'text' => 'Mulai dengan membuat event pertama. Setelah itu bagikan tautan registrasi dan buat akun Admin Absensi.', 'cta' => ['admin/event/baru', 'Buat event']]) ?>
<?php else:
  $max = max(1, ...array_column($slots, 'n') ?: [1]);
?>
  <div class="pagehead">
    <div><h2><?= e($event['name']) ?></h2><p><?= e(date_id($event['event_date'], true)) ?> · <?= e($event['location']) ?></p></div>
    <div class="row"><?= event_badge($event['status']) ?><?= reg_badge($event['reg_status']) ?><?= att_badge($event['att_status']) ?></div>
  </div>
  <?= View::partial('event_picker', ['events' => $events, 'event' => $event, 'path' => 'admin']) ?>
  <?= View::partial('stats', ['stats' => $stats]) ?>
  <div class="g-2">
    <div class="card">
      <div class="card-h"><h3>Check-in per 15 menit</h3><span class="sub">Jam absensi <?= e($event['att_start']) ?>–<?= e($event['att_end']) ?></span></div>
      <?php if (!array_sum(array_column($slots, 'n'))): ?>
        <p class="muted small">Belum ada check-in lewat scan pada tanggal event.</p>
      <?php else: ?>
        <div class="chart-cols" role="img" aria-label="Grafik check-in per 15 menit">
          <?php foreach ($slots as $s): ?><div class="c"><em><?= $s['n'] ?: '' ?></em><i style="height:<?= round($s['n'] / $max * 100) ?>%"></i></div><?php endforeach; ?>
        </div>
        <div class="chart-x"><?php foreach ($slots as $i => $s): ?><span><?= $i % 2 === 0 ? e($s['label']) : '' ?></span><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-h"><h3>Progres kehadiran</h3><span class="sub"><?= e($stats['pct']) ?></span></div>
      <?= View::partial('progress', ['stats' => $stats]) ?>
      <div class="stack" style="gap:8px">
        <a class="btn btn-primary block" href="<?= e(url('petugas/scan')) ?>"><?= icon('scan', 18) ?>Buka scanner</a>
        <a class="btn btn-outline block" href="<?= e(url('admin/laporan')) ?>"><?= icon('download', 18) ?>Ekspor rekap</a>
        <a class="btn btn-ghost block" href="<?= e(url('admin/event/' . $event['id'])) ?>"><?= icon('sliders', 18) ?>Atur registrasi &amp; absensi</a>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-h"><h3>Perubahan status terbaru</h3><a class="btn btn-ghost sm" href="<?= e(url('admin/riwayat')) ?>">Lihat riwayat</a></div>
    <?= View::partial('history_items', ['items' => $history]) ?>
  </div>
<?php endif; ?>
