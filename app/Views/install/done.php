<div class="bare">
  <div class="bare-c">
    <div class="state s-ok"><span class="okmark"><?= icon('check', 38) ?></span><h3>Instalasi selesai</h3><p>Anda sudah masuk sebagai <b>@<?= e($username) ?></b>. Halaman instalasi kini terkunci.</p></div>
    <?php if ($demo): ?>
      <div class="card">
        <div class="card-h"><h3>Akun Admin Absensi demo</h3><span class="bdg bdg-warn">Catat sekarang</span></div>
        <?php foreach ($demo as $d): ?>
          <div class="cred"><span>Username <b class="mono"><?= e($d['username']) ?></b></span><span>Password <b class="mono"><?= e($d['password']) ?></b></span></div>
        <?php endforeach; ?>
        <p class="small muted">Password ini hanya ditampilkan sekali. Bisa dibuat ulang lewat menu Akun Admin.</p>
      </div>
    <?php endif; ?>
    <div class="banner warn"><?= icon('shield') ?><div class="grow"><b>Langkah keamanan</b>Pastikan situs memakai HTTPS (wajib untuk kamera scanner) dan setel <span class="mono">debug</span> ke <span class="mono">false</span> di config.</div></div>
    <a class="btn btn-primary lg block" href="<?= e(url('admin')) ?>">Buka dashboard</a>
  </div>
</div>
