<div class="bare">
  <div class="bare-c">
    <div class="state s-ok"><span class="okmark"><?= icon('check', 38) ?></span><h3><?= e(t('Instalasi selesai')) ?></h3><p><?= e(t('Anda sudah masuk sebagai')) ?> <b>@<?= e($username) ?></b>. <?= e(t('Halaman instalasi kini terkunci.')) ?></p></div>
    <?php if ($demo): ?>
      <div class="card">
        <div class="card-h"><h3><?= e(t('Akun Admin Presensi demo')) ?></h3><span class="bdg bdg-warn"><?= e(t('Catat sekarang')) ?></span></div>
        <?php foreach ($demo as $d): ?>
          <div class="cred"><span><?= e(t('Username')) ?> <b class="mono"><?= e($d['username']) ?></b></span><span><?= e(t('Password')) ?> <b class="mono"><?= e($d['password']) ?></b></span></div>
        <?php endforeach; ?>
        <p class="small muted"><?= e(t('Password ini hanya ditampilkan sekali. Bisa dibuat ulang lewat menu Akun Admin.')) ?></p>
      </div>
    <?php endif; ?>
    <?php if (!empty($certDemo['account']) || !empty($certDemo['sample'])): ?>
      <div class="card">
        <div class="card-h"><h3><?= e(t('Demo Admin Event dan sertifikat')) ?></h3><span class="bdg bdg-warn"><?= e(t('Catat sekarang')) ?></span></div>
        <?php if ($certDemo['account']): ?>
          <div class="cred"><span><?= e(t('Username')) ?> <b class="mono"><?= e($certDemo['account']['username']) ?></b></span><span><?= e(t('Password')) ?> <b class="mono"><?= e($certDemo['account']['password']) ?></b></span></div>
        <?php endif; ?>
        <p class="small muted"><?= e(t('Admin Event ditugaskan ke dua event demo. Event ":event" sudah berisi template, pola nomor demo, dan sertifikat yang terbit.', ['event' => $certDemo['event']])) ?></p>
        <?php if ($certDemo['sample']): ?>
          <p class="small"><?= e(t('Coba halaman publik Cari Sertifikat dengan nama :name dan 4 digit terakhir NIM :check.', ['name' => $certDemo['sample']['name'], 'check' => $certDemo['sample']['check']])) ?></p>
        <?php endif; ?>
      </div>
    <?php elseif (!empty($certDemo['error'])): ?>
      <div class="banner warn"><?= icon('alert') ?><div class="grow"><?= e(t('Data demo sertifikat tidak dapat dibuat. Periksa ekstensi GD dan log error; data demo presensi tetap terpasang.')) ?></div></div>
    <?php endif; ?>
    <div class="banner warn"><?= icon('shield') ?><div class="grow"><b><?= e(t('Langkah keamanan')) ?></b><?= e(t('Pastikan situs memakai HTTPS (wajib untuk kamera scanner) dan setel')) ?> <span class="mono">debug</span> <?= e(t('ke')) ?> <span class="mono">false</span> <?= e(t('di config.')) ?></div></div>
    <a class="btn btn-primary lg block" href="<?= e(url('admin')) ?>"><?= e(t('Buka dashboard')) ?></a>
  </div>
</div>
