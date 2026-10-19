<?php use App\Core\View; ?>
<?php if (!empty($pendingMigrations)): ?>
  <div class="banner warn" role="status"><?= icon('alert') ?><div class="grow"><b><?= e(t('Pembaruan database tersedia')) ?></b><?= e(t('Migrasi aditif untuk role Admin Event dan sertifikat belum dijalankan (:list). Data lama tidak diubah atau dihapus. Cadangkan database terlebih dahulu.', ['list' => implode(', ', $pendingMigrations)])) ?></div>
    <?= post_button('admin/migrasi', t('Jalankan migrasi'), [], 'btn btn-primary sm', t('Jalankan migrasi database sekarang? Pastikan database sudah dicadangkan.'), 'refresh') ?></div>
<?php endif; ?>
<?php if (!$event): ?>
  <?= View::partial('no_event', ['heading' => t('Selamat datang di SmartCity Attendance'), 'text' => t('Mulai dengan membuat event pertama. Setelah itu bagikan tautan registrasi dan buat akun Admin Presensi.'), 'cta' => ['admin/event/baru', t('Buat event')]]) ?>
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
      <div class="card-h"><h3><?= e(t('Check-in per 15 menit')) ?></h3><span class="sub"><?= e(t('Jam presensi :start–:end', ['start' => $event['att_start'], 'end' => $event['att_end']])) ?></span></div>
      <?php if (!array_sum(array_column($slots, 'n'))): ?>
        <p class="muted small"><?= e(t('Belum ada check-in lewat scan pada tanggal event.')) ?></p>
      <?php else: ?>
        <div class="chart-cols" role="img" aria-label="<?= e(t('Grafik check-in per 15 menit')) ?>">
          <?php foreach ($slots as $s): ?><div class="c"><em><?= $s['n'] ?: '' ?></em><i style="height:<?= round($s['n'] / $max * 100) ?>%"></i></div><?php endforeach; ?>
        </div>
        <div class="chart-x"><?php foreach ($slots as $i => $s): ?><span><?= $i % 2 === 0 ? e($s['label']) : '' ?></span><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-h"><h3><?= e(t('Progres kehadiran')) ?></h3><span class="sub"><?= e($stats['pct']) ?></span></div>
      <?= View::partial('progress', ['stats' => $stats]) ?>
      <div class="stack" style="gap:8px">
        <a class="btn btn-primary block" href="<?= e(url('petugas/scan')) ?>"><?= icon('scan', 18) ?><?= e(t('Buka scanner')) ?></a>
        <a class="btn btn-outline block" href="<?= e(url('admin/laporan')) ?>"><?= icon('download', 18) ?><?= e(t('Ekspor rekap')) ?></a>
        <a class="btn btn-ghost block" href="<?= e(url('admin/event/' . $event['id'])) ?>"><?= icon('sliders', 18) ?><?= e(t('Atur registrasi & presensi')) ?></a>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Perubahan status terbaru')) ?></h3><a class="btn btn-ghost sm" href="<?= e(url('admin/riwayat')) ?>"><?= e(t('Lihat riwayat')) ?></a></div>
    <?= View::partial('history_items', ['items' => $history]) ?>
  </div>
<?php endif; ?>
