<form method="post" action="<?= e(url($action)) ?>" class="card" data-loading="Menyimpan…">
  <?= csrf_field() ?>
  <div class="card-h"><h3>Ganti password</h3></div>
  <?php if (!empty($mustChange)): ?><div class="banner warn"><?= icon('key') ?><div class="grow"><b>Password sementara</b>Akun ini memakai password dari Super Admin. Ganti sekarang.</div></div><?php endif; ?>
  <div class="fld"><label for="current_password">Password saat ini</label><input class="inp" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
  <div class="g-form two">
    <div class="fld"><label for="new_password">Password baru</label><input class="inp" id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="10" required><p class="hint">Minimal 10 karakter, kombinasi huruf dan angka.</p></div>
    <div class="fld"><label for="confirm_password">Ulangi password baru</label><input class="inp" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="10" required></div>
  </div>
  <div><button type="submit" class="btn btn-primary">Simpan password</button></div>
</form>
