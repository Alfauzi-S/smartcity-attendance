<div class="card">
  <div class="idcard"><span class="av lg"><?= e(initials($user['name'])) ?></span><div><b><?= e($user['name']) ?></b><span>@<?= e($user['username']) ?> · Super Admin</span></div></div>
  <dl class="kv"><div><dt>Login terakhir</dt><dd><?= e(datetime_id($user['last_login_at'])) ?></dd></div><div><dt>Zona waktu sistem</dt><dd><?= e(config('timezone')) ?> (<?= e(config('timezone_label')) ?>)</dd></div></dl>
</div>
<form method="post" action="<?= e(url('admin/pengaturan/profil')) ?>" class="card">
  <?= csrf_field() ?>
  <div class="card-h"><h3>Profil</h3></div>
  <div class="fld"><label for="name">Nama tampilan</label><input class="inp" id="name" name="name" maxlength="120" required value="<?= e($user['name']) ?>"></div>
  <div><button class="btn btn-primary" type="submit">Simpan profil</button></div>
</form>
<?= \App\Core\View::partial('password_form', ['action' => 'admin/pengaturan/password', 'mustChange' => (int) $user['must_change_password'] === 1]) ?>
<form method="post" action="<?= e(url('keluar')) ?>" data-confirm="Keluar dari akun ini?"><?= csrf_field() ?><button class="btn btn-outline block" type="submit"><?= icon('logout', 18) ?>Keluar</button></form>
