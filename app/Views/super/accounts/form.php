<?php $cls = static fn (string $k) => 'fld' . (isset($errors[$k]) ? ' is-err' : ''); ?>
<form method="post" action="<?= e(url('admin/akun/baru')) ?>" class="card" novalidate data-loading="<?= e(t('Membuat akun…')) ?>">
  <?= csrf_field() ?>
  <div class="card-h"><h3><?= e(t('Akun Admin Absensi baru')) ?></h3></div>
  <div class="g-form two">
    <div class="<?= $cls('name') ?>"><label for="name"><?= e(t('Nama petugas')) ?><span class="req">*</span></label><input class="inp" id="name" name="name" maxlength="120" required value="<?= e($d['name']) ?>"><?= field_error($errors, 'name') ?></div>
    <div class="<?= $cls('username') ?>"><label for="username"><?= e(t('Username')) ?><span class="req">*</span><span class="en"><?= e(t('Huruf kecil, angka, atau titik')) ?></span></label><input class="inp mono" id="username" name="username" maxlength="40" autocapitalize="none" spellcheck="false" required value="<?= e($d['username']) ?>" placeholder="petugas.c"><?= field_error($errors, 'username') ?></div>
    <div class="<?= $cls('password') ?>"><label for="password"><?= e(t('Password sementara')) ?><span class="req">*</span><span class="en"><?= e(t('Petugas wajib menggantinya saat login pertama')) ?></span></label><input class="inp mono" id="password" name="password" required value="<?= e($d['password']) ?>" autocomplete="off"><?= field_error($errors, 'password') ?></div>
    <div class="<?= $cls('event_id') ?>"><label for="event_id"><?= e(t('Ditugaskan ke event')) ?></label><?= \App\Core\View::partial('event_select', ['events' => $events, 'value' => $d['event_id']]) ?><?= field_error($errors, 'event_id') ?></div>
  </div>
  <input type="hidden" name="status" value="Aktif">
  <div class="banner info"><?= icon('info') ?><div class="grow"><?= e(t('Password sementara ditampilkan sekali setelah akun dibuat. Sampaikan langsung ke petugas, jangan lewat grup publik.')) ?></div></div>
  <div class="row" style="justify-content:flex-end"><a class="btn btn-ghost" href="<?= e(url('admin/akun')) ?>"><?= e(t('Batal')) ?></a><button class="btn btn-primary" type="submit"><?= e(t('Buat akun')) ?></button></div>
</form>
