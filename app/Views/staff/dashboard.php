<?php use App\Core\View; ?>
<?php if (!$event): ?>
  <?= View::partial('no_event', ['heading' => t('Akun belum ditugaskan ke event'), 'text' => t('Minta Super Admin menugaskan akun ini ke sebuah event agar Anda dapat memindai QR peserta.')]) ?>
<?php else: ?>
  <div class="pagehead"><div><h2><?= e(t('Halo, :name', ['name' => explode(' ', $user['name'])[0]])) ?></h2><p><?= e(t('Anda telah memindai :n peserta.', ['n' => (int) $myScans])) ?></p></div></div>
  <?= View::partial('event_card', ['event' => $event]) ?>
  <?php if ($event['att_status'] !== 'open'): ?>
    <div class="banner <?= $event['att_status'] === 'closed' ? 'bad' : 'warn' ?>"><?= icon('clock') ?><div class="grow"><b><?= e($event['att_status'] === 'closed' ? t('Presensi sudah ditutup') : t('Presensi belum dibuka')) ?></b><?= e(t('Scan QR hanya bisa disimpan saat Super Admin membuka presensi.')) ?></div></div>
  <?php endif; ?>
  <a class="btn btn-primary lg block" href="<?= e(url('petugas/scan')) ?>"><?= icon('scan') ?><?= e(t('Mulai scan QR')) ?></a>
  <?= View::partial('stats', ['stats' => $stats]) ?>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Progres kehadiran')) ?></h3><span class="sub"><?= e(t(':n dari :total', ['n' => (int) $stats['hadir'], 'total' => (int) $stats['total']])) ?></span></div>
    <?= View::partial('progress', ['stats' => $stats]) ?>
  </div>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Check-in terbaru')) ?></h3><a class="btn btn-ghost sm" href="<?= e(url('petugas/peserta', ['st' => 'hadir'])) ?>"><?= e(t('Lihat semua')) ?></a></div>
    <?php if (!$recent): ?>
      <p class="muted small"><?= e(t('Belum ada peserta yang check-in.')) ?></p>
    <?php else: ?>
      <div class="list">
        <?php foreach ($recent as $p): ?>
          <a class="pc" href="<?= e(url('petugas/peserta/' . $p['id'])) ?>"><span class="av t"><?= e(initials($p['full_name'])) ?></span><span class="who"><b><?= e($p['full_name']) ?></b><span class="mono"><?= e($p['identity_number']) ?></span></span><span class="mono small"><?= e(time_only($p['checked_in_at'])) ?></span></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
