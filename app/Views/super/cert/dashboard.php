<?php
use App\Core\View;
use App\Models\CertTemplate;

$eid = (int) $event['id'];
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<div class="g-stats">
  <div class="stat t-navy"><span class="k"><span class="dot"><?= icon('users', 16) ?></span><?= e(t('Memenuhi syarat')) ?></span><span class="v"><?= (int) $summary['eligible'] ?></span><span class="s"><?= e(t('dari :n peserta terdaftar', ['n' => $summary['total']])) ?></span></div>
  <div class="stat t-info"><span class="k"><span class="dot"><?= icon('file', 16) ?></span><?= e(t('Generated')) ?></span><span class="v"><?= (int) $stats['generated'] ?></span><span class="s"><?= e(t('Belum terlihat publik')) ?></span></div>
  <div class="stat t-ok"><span class="k"><span class="dot"><?= icon('check', 16) ?></span><?= e(t('Published')) ?></span><span class="v"><?= (int) $stats['published'] ?></span><span class="s"><?= e(t('Dapat dicari peserta')) ?></span></div>
  <div class="stat t-warn"><span class="k"><span class="dot"><?= icon('x', 16) ?></span><?= e(t('Revoked')) ?></span><span class="v"><?= (int) $stats['revoked'] ?></span><span class="s"><?= e(t('Dicabut, tidak valid')) ?></span></div>
</div>
<div class="g-2">
  <div class="card">
    <div class="card-h"><h3><?= e(t('Laporan validasi')) ?></h3><span class="bdg <?= $ready['ok'] ? 'bdg-ok' : 'bdg-bad' ?>"><i></i><?= e($ready['ok'] ? t('Siap generate') : t('Belum lengkap')) ?></span></div>
    <?= View::fetch('super/cert/_checks', ['checks' => $ready['checks']]) ?>
    <?php if (can('cert_manage', $eid)): ?>
    <div class="row"><a class="btn btn-primary sm" href="<?= e(url('admin/sertifikat/generate', ['ev' => $eid])) ?>"><?= icon('award', 16) ?><?= e(t('Ke halaman Generate')) ?></a><a class="btn btn-outline sm" href="<?= e(url('admin/sertifikat/variabel', ['ev' => $eid])) ?>"><?= e(t('Atur variabel')) ?></a></div>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Alur penerbitan')) ?></h3></div>
    <ol class="steps-l small">
      <li><?= e(t('Unggah aset resmi (logo, tanda tangan, stempel). Tanda tangan dan stempel disetujui Super Admin.')) ?></li>
      <li><?= e(t('Buat template, unggah desain, atur posisi elemen, lalu aktifkan.')) ?></li>
      <li><?= e(t('Isi variabel organisasi dan penandatangan sesuai data resmi.')) ?></li>
      <li><?= e(t('Buat pola nomor sesuai format dari fakultas/TU; Super Admin menyetujui.')) ?></li>
      <li><?= e(t('Atur kelayakan, tinjau penerima, lalu jalankan generate.')) ?></li>
      <li><?= e(t('Periksa hasil, lalu publikasikan. Peserta mencari di halaman Cari Sertifikat.')) ?></li>
    </ol>
    <div class="copyrow"><input class="inp" id="pub-link" value="<?= e(full_url('sertifikat')) ?>" readonly aria-label="<?= e(t('Tautan Cari Sertifikat')) ?>"><button type="button" class="btn btn-outline" data-copy="#pub-link"><?= icon('copy', 18) ?><?= e(t('Salin')) ?></button></div>
  </div>
</div>
<?php if ($templates): ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Template')) ?></h3></div>
  <div class="list">
    <?php foreach ($templates as $tp): ?>
      <a class="pc" href="<?= e(can('cert_manage', $eid) ? url('admin/sertifikat/template/' . $tp['id']) : '#') ?>"><span class="who"><b><?= e($tp['name']) ?></b><span><?= e(t('Versi :v · :n sertifikat terbit', ['v' => (int) $tp['version'], 'n' => (int) $tp['issued']])) ?></span></span><span class="bdg bdg-<?= CertTemplate::STATUSES[$tp['status']] ?>"><?= e(t($tp['status'])) ?></span></a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
