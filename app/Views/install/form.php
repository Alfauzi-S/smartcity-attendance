<?php
$ready = !in_array(false, array_map(static fn ($c) => $c[1] || str_starts_with($c[0], 'Ekstensi zip'), $checks), true);
$cls = static fn (string $k) => 'fld' . (isset($errors[$k]) ? ' is-err' : '');
?>
<div class="bare">
  <div class="bare-c">
    <div class="row"><?= logo_mark(40) ?><div><b style="font-family:var(--f-display)">SmartCity Attendance</b><div class="small muted">Instalasi pertama</div></div></div>
    <div class="card">
      <div class="card-h"><h3>Pemeriksaan server</h3></div>
      <?php foreach ($checks as [$label, $ok, $note]): ?>
        <div class="check <?= $ok ? 'y' : 'n' ?>"><?= icon($ok ? 'check' : 'x', 18) ?><div><?= e($label) ?><?php if ($note !== ''): ?><small><?= e($note) ?></small><?php endif; ?></div></div>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($errors['_form'])): ?><div class="banner bad"><?= icon('alert') ?><div class="grow"><b>Instalasi gagal</b><?= e($errors['_form']) ?></div></div><?php endif; ?>
    <?php if (!$ready): ?>
      <div class="banner warn"><?= icon('alert') ?><div class="grow"><b>Perbaiki dulu item bertanda ×</b>Ubah <span class="mono">config/config.php</span> atau pengaturan server, lalu muat ulang halaman ini.</div></div>
    <?php else: ?>
    <form method="post" action="<?= e(url('install')) ?>" class="card" novalidate data-loading="Memasang…">
      <?= csrf_field() ?>
      <div class="card-h"><h3>Akun Super Admin</h3></div>
      <div class="<?= $cls('name') ?>"><label for="name">Nama</label><input class="inp" id="name" name="name" required value="<?= e($old['name']) ?>"><?= field_error($errors, 'name') ?></div>
      <div class="<?= $cls('username') ?>"><label for="username">Username</label><input class="inp mono" id="username" name="username" required autocapitalize="none" value="<?= e($old['username']) ?>"><?= field_error($errors, 'username') ?></div>
      <div class="<?= $cls('password') ?>"><label for="password">Password<span class="en">Minimal 10 karakter, kombinasi huruf dan angka</span></label><input class="inp" id="password" name="password" type="password" autocomplete="new-password" required><?= field_error($errors, 'password') ?></div>
      <div class="fld"><label for="password_confirm">Ulangi password</label><input class="inp" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required></div>
      <label class="chk"><input type="checkbox" name="demo" value="1"<?= checked($old['demo'], '1') ?>>Isi data demo (1 event, 156 peserta, 2 Admin Absensi)</label>
      <p class="hint small muted">Data demo cocok untuk uji coba. Untuk produksi, biarkan tidak dicentang.</p>
      <button class="btn btn-primary lg block" type="submit">Pasang sekarang</button>
    </form>
    <?php endif; ?>
  </div>
</div>
