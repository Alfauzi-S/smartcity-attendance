<?php
use App\Core\Auth;

$cls = static fn (string $k) => 'fld' . (isset($errors[$k]) ? ' is-err' : '');
$isEvent = $d['role'] === Auth::EVENT;
?>
<form method="post" action="<?= e(url('admin/akun/baru')) ?>" class="card" novalidate data-loading="<?= e(t('Membuat akun…')) ?>">
  <?= csrf_field() ?>
  <div class="card-h"><h3><?= e($isEvent ? t('Akun Admin Event baru') : t('Akun Admin Absensi baru')) ?></h3></div>
  <div class="<?= $cls('role') ?>">
    <span class="lbl"><?= e(t('Peran')) ?><span class="req">*</span></span>
    <div class="opts">
      <label class="opt"><input type="radio" name="role" value="<?= Auth::ADMIN ?>"<?= checked($d['role'], Auth::ADMIN) ?>><span class="rd"></span><span class="ot"><b><?= e(t('Admin Absensi')) ?></b><span><?= e(t('Memindai QR dan melihat peserta satu event.')) ?></span></span></label>
      <?php if ($migrated): ?><label class="opt"><input type="radio" name="role" value="<?= Auth::EVENT ?>"<?= checked($d['role'], Auth::EVENT) ?>><span class="rd"></span><span class="ot"><b><?= e(t('Admin Event')) ?></b><span><?= e(t('Mengelola event yang ditugaskan: peserta, laporan, dan sertifikat sesuai izin.')) ?></span></span></label><?php endif; ?>
    </div>
    <?= field_error($errors, 'role') ?>
  </div>
  <div class="g-form two">
    <div class="<?= $cls('name') ?>"><label for="name"><?= e(t('Nama')) ?><span class="req">*</span></label><input class="inp" id="name" name="name" maxlength="120" required value="<?= e($d['name']) ?>"><?= field_error($errors, 'name') ?></div>
    <div class="<?= $cls('username') ?>"><label for="username"><?= e(t('Username')) ?><span class="req">*</span><span class="en"><?= e(t('Huruf kecil, angka, atau titik')) ?></span></label><input class="inp mono" id="username" name="username" maxlength="40" autocapitalize="none" spellcheck="false" required value="<?= e($d['username']) ?>" placeholder="petugas.c"><?= field_error($errors, 'username') ?></div>
    <div class="<?= $cls('password') ?>"><label for="password"><?= e(t('Password sementara')) ?><span class="req">*</span><span class="en"><?= e(t('Wajib diganti saat login pertama')) ?></span></label><input class="inp mono" id="password" name="password" required value="<?= e($d['password']) ?>" autocomplete="off"><?= field_error($errors, 'password') ?></div>
    <div class="<?= $cls('event_id') ?>"><label for="event_id"><?= e(t('Ditugaskan ke event')) ?><span class="en"><?= e(t('Admin Event dapat ditambah event lain setelah akun dibuat')) ?></span></label><?= \App\Core\View::partial('event_select', ['events' => $events, 'value' => $d['event_id']]) ?><?= field_error($errors, 'event_id') ?></div>
  </div>
  <input type="hidden" name="status" value="Aktif">
  <div class="banner info"><?= icon('info') ?><div class="grow"><?= e(t('Password sementara ditampilkan sekali setelah akun dibuat. Sampaikan langsung ke petugas, jangan lewat grup publik.')) ?></div></div>
  <div class="row" style="justify-content:flex-end"><a class="btn btn-ghost" href="<?= e(url('admin/akun')) ?>"><?= e(t('Batal')) ?></a><button class="btn btn-primary" type="submit"><?= e(t('Buat akun')) ?></button></div>
</form>
