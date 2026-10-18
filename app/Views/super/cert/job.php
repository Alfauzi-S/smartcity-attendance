<?php
use App\Core\View;

View::script('js/cert-job.js');
$eid = (int) $event['id'];
$pct = $job['total'] ? (int) floor($job['cursor_pos'] / $job['total'] * 100) : 100;
$active = in_array($job['status'], ['queued', 'running'], true);
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<div class="card" id="job" data-step="<?= e(url('admin/sertifikat/job/' . $job['id'] . '/step')) ?>" data-active="<?= $active ? '1' : '0' ?>">
  <div class="card-h"><h3><?= e(t('Proses generate #:id', ['id' => $job['id']])) ?></h3><span class="bdg" id="job-state"><?= e(t(['queued' => 'Menunggu', 'running' => 'Berjalan', 'paused' => 'Dijeda', 'done' => 'Selesai'][$job['status']] ?? $job['status'])) ?></span></div>
  <div class="bar" aria-hidden="true"><i id="job-bar" style="width:<?= $pct ?>%"></i></div>
  <p class="small" id="job-progress" role="status" aria-live="polite"><?= e(t(':done dari :total diproses', ['done' => (int) $job['cursor_pos'], 'total' => (int) $job['total']])) ?></p>
  <div class="g-stats">
    <div class="stat t-ok"><span class="k"><?= e(t('Berhasil')) ?></span><span class="v" id="job-ok"><?= (int) $job['success'] ?></span></div>
    <div class="stat t-info"><span class="k"><?= e(t('Dilewati (sudah ada)')) ?></span><span class="v" id="job-skip"><?= (int) $job['skipped'] ?></span></div>
    <div class="stat t-warn"><span class="k"><?= e(t('Gagal')) ?></span><span class="v" id="job-fail"><?= (int) $job['failed'] ?></span></div>
    <div class="stat t-navy"><span class="k"><?= e(t('Total')) ?></span><span class="v"><?= (int) $job['total'] ?></span></div>
  </div>
  <div class="banner warn" id="job-msg"<?= $job['status'] === 'paused' ? '' : ' hidden' ?>><?= icon('alert') ?><div class="grow"><?= e(t('Proses dijeda karena konfigurasi berubah atau belum lengkap. Perbaiki lalu mulai generate baru; sertifikat yang sudah dibuat tidak akan digandakan.')) ?></div></div>
  <div class="row">
    <?php if ($active): ?>
      <form method="post" action="<?= e(url('admin/sertifikat/job/' . $job['id'] . '/step')) ?>" id="job-manual"><?= csrf_field() ?><button class="btn btn-outline sm" type="submit"><?= e(t('Lanjutkan proses')) ?></button></form>
    <?php endif; ?>
    <a class="btn btn-primary sm" id="job-done-link" href="<?= e(url('admin/sertifikat/terbit', ['ev' => $eid, 'st' => 'generated'])) ?>"<?= $job['status'] === 'done' ? '' : ' hidden' ?>><?= e(t('Periksa hasil')) ?></a>
    <?php if ((int) $job['failed'] > 0 && !$active): ?><?= post_button('admin/sertifikat/job/' . $job['id'] . '/ulang', t('Ulangi yang gagal'), [], 'btn btn-outline sm', '', 'refresh') ?><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-h"><h3><?= e(t('Laporan kesalahan')) ?></h3></div>
  <div id="job-errors">
    <?php if (!$errors): ?><p class="small muted" data-empty><?= e(t('Belum ada kesalahan.')) ?></p><?php endif; ?>
    <?php foreach ($errors as $er): ?><div class="ck ck-bad"><?= icon('x', 16) ?><span><b><?= e($er['name']) ?></b> — <?= e($er['message']) ?></span></div><?php endforeach; ?>
  </div>
</div>
