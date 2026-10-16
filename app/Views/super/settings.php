<div class="card">
  <div class="idcard"><span class="av lg"><?= e(initials($user['name'])) ?></span><div><b><?= e($user['name']) ?></b><span>@<?= e($user['username']) ?> · <?= e(t('Super Admin')) ?></span></div></div>
  <dl class="kv"><div><dt><?= e(t('Login terakhir')) ?></dt><dd><?= e(datetime_id($user['last_login_at'])) ?></dd></div><div><dt><?= e(t('Zona waktu sistem')) ?></dt><dd><?= e(config('timezone')) ?> (<?= e(config('timezone_label')) ?>)</dd></div></dl>
</div>
<form method="post" action="<?= e(url('admin/pengaturan/profil')) ?>" class="card">
  <?= csrf_field() ?>
  <div class="card-h"><h3><?= e(t('Profil')) ?></h3></div>
  <div class="fld"><label for="name"><?= e(t('Nama tampilan')) ?></label><input class="inp" id="name" name="name" maxlength="120" required value="<?= e($user['name']) ?>"></div>
  <div><button class="btn btn-primary" type="submit"><?= e(t('Simpan profil')) ?></button></div>
</form>
<?= \App\Core\View::partial('password_form', ['action' => 'admin/pengaturan/password', 'mustChange' => (int) $user['must_change_password'] === 1]) ?>
<form method="post" action="<?= e(url('keluar')) ?>" data-confirm="<?= e(t('Keluar dari akun ini?')) ?>"><?= csrf_field() ?><button class="btn btn-outline block" type="submit"><?= icon('logout', 18) ?><?= e(t('Keluar')) ?></button></form>
