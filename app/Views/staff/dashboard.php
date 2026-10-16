<?php use App\Core\View; ?>
<?php if (!$event): ?>
  <?= View::partial('no_event', ['heading' => 'Akun belum ditugaskan ke event', 'text' => 'Minta Super Admin menugaskan akun ini ke sebuah event agar Anda dapat memindai QR peserta.']) ?>
<?php else: ?>
  <div class="pagehead"><div><h2>Halo, <?= e(explode(' ', $user['name'])[0]) ?></h2><p>Anda telah memindai <?= (int) $myScans ?> peserta.</p></div></div>
  <?= View::partial('event_card', ['event' => $event]) ?>
  <?php if ($event['att_status'] !== 'open'): ?>
    <div class="banner <?= $event['att_status'] === 'closed' ? 'bad' : 'warn' ?>"><?= icon('clock') ?><div class="grow"><b><?= $event['att_status'] === 'closed' ? 'Absensi sudah ditutup' : 'Absensi belum dibuka' ?></b>Scan QR hanya bisa disimpan saat Super Admin membuka absensi.</div></div>
  <?php endif; ?>
  <a class="btn btn-primary lg block" href="<?= e(url('petugas/scan')) ?>"><?= icon('scan') ?>Mulai scan QR</a>
  <?= View::partial('stats', ['stats' => $stats]) ?>
  <div class="card">
    <div class="card-h"><h3>Progres kehadiran</h3><span class="sub"><?= (int) $stats['hadir'] ?> dari <?= (int) $stats['total'] ?></span></div>
    <?= View::partial('progress', ['stats' => $stats]) ?>
  </div>
  <div class="card">
    <div class="card-h"><h3>Check-in terbaru</h3><a class="btn btn-ghost sm" href="<?= e(url('petugas/peserta', ['st' => 'hadir'])) ?>">Lihat semua</a></div>
    <?php if (!$recent): ?>
      <p class="muted small">Belum ada peserta yang check-in.</p>
    <?php else: ?>
      <div class="list">
        <?php foreach ($recent as $p): ?>
          <a class="pc" href="<?= e(url('petugas/peserta/' . $p['id'])) ?>"><span class="av t"><?= e(initials($p['full_name'])) ?></span><span class="who"><b><?= e($p['full_name']) ?></b><span class="mono"><?= e($p['identity_number']) ?></span></span><span class="mono small"><?= e(time_only($p['checked_in_at'])) ?></span></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
