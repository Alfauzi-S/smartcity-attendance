<form method="post" action="<?= e(url($action)) ?>" class="card" data-loading="<?= e(t('Menyimpan…')) ?>">
  <?= csrf_field() ?>
  <div class="card-h"><h3><?= e(t('Ganti password')) ?></h3></div>
  <?php if (!empty($mustChange)): ?><div class="banner warn"><?= icon('key') ?><div class="grow"><b><?= e(t('Password sementara')) ?></b><?= e(t('Akun ini memakai password dari Super Admin. Ganti sekarang.')) ?></div></div><?php endif; ?>
  <div class="fld"><label for="current_password"><?= e(t('Password saat ini')) ?></label><input class="inp" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
  <div class="g-form two">
    <div class="fld"><label for="new_password"><?= e(t('Password baru')) ?></label><input class="inp" id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="10" required><p class="hint"><?= e(t('Minimal 10 karakter, kombinasi huruf dan angka.')) ?></p></div>
    <div class="fld"><label for="confirm_password"><?= e(t('Ulangi password baru')) ?></label><input class="inp" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="10" required></div>
  </div>
  <div><button type="submit" class="btn btn-primary"><?= e(t('Simpan password')) ?></button></div>
</form>
