<?php
use App\Models\Event;

$v = static fn (string $k) => e($e[$k] ?? '');
$cls = static fn (string $k) => 'fld' . (isset($errors[$k]) ? ' is-err' : '');
$action = $editId ? 'admin/event/' . $editId . '/ubah' : 'admin/event/baru';
?>
<?php if ($errors): ?><div class="banner bad" role="alert"><?= icon('alert') ?><div class="grow"><b><?= e(t('Periksa kembali :n isian', ['n' => count($errors)])) ?></b><?= e(t('Kolom yang ditandai merah belum sesuai.')) ?></div></div><?php endif; ?>
<form method="post" action="<?= e(url($action)) ?>" class="stack" novalidate data-loading="<?= e(t('Menyimpan event…')) ?>">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Informasi event')) ?></h3></div>
    <div class="<?= $cls('name') ?>"><label for="name"><?= e(t('Nama event')) ?><span class="req">*</span></label><input class="inp" id="name" name="name" maxlength="160" required value="<?= $v('name') ?>"><?= field_error($errors, 'name') ?></div>
    <div class="<?= $cls('description') ?>"><label for="description"><?= e(t('Deskripsi')) ?></label><textarea class="inp" id="description" name="description" maxlength="2000"><?= $v('description') ?></textarea></div>
    <div class="g-form two">
      <div class="<?= $cls('organizer') ?>"><label for="organizer"><?= e(t('Penyelenggara')) ?><span class="req">*</span></label><input class="inp" id="organizer" name="organizer" maxlength="160" required value="<?= $v('organizer') ?>"><?= field_error($errors, 'organizer') ?></div>
      <div class="<?= $cls('location') ?>"><label for="location"><?= e(t('Lokasi')) ?><span class="req">*</span></label><input class="inp" id="location" name="location" maxlength="200" required value="<?= $v('location') ?>"><?= field_error($errors, 'location') ?></div>
    </div>
  </div>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Jadwal')) ?></h3><span class="sub"><?= e(t('Zona waktu :tz', ['tz' => config('timezone_label', 'WITA')])) ?></span></div>
    <div class="g-form two">
      <div class="<?= $cls('event_date') ?>"><label for="event_date"><?= e(t('Tanggal event')) ?><span class="req">*</span></label><input class="inp" type="date" id="event_date" name="event_date" required value="<?= $v('event_date') ?>"><?= field_error($errors, 'event_date') ?></div>
      <div class="g-form two" style="gap:12px">
        <div class="<?= $cls('start_time') ?>"><label for="start_time"><?= e(t('Jam mulai')) ?><span class="req">*</span></label><input class="inp" type="time" id="start_time" name="start_time" required value="<?= $v('start_time') ?>"><?= field_error($errors, 'start_time') ?></div>
        <div class="<?= $cls('end_time') ?>"><label for="end_time"><?= e(t('Jam selesai')) ?></label><input class="inp" type="time" id="end_time" name="end_time" value="<?= $v('end_time') ?>"><?php if (!isset($errors['end_time'])): ?><p class="hint"><?= e(t('Kosongkan bila sampai selesai')) ?></p><?php endif; ?><?= field_error($errors, 'end_time') ?></div>
      </div>
      <div class="g-form two" style="gap:12px">
        <div class="<?= $cls('reg_start') ?>"><label for="reg_start"><?= e(t('Registrasi dibuka')) ?> <span class="muted" style="font-weight:500">(<?= e(t('opsional')) ?>)</span></label><input class="inp" type="date" id="reg_start" name="reg_start" value="<?= $v('reg_start') ?>"><?= field_error($errors, 'reg_start') ?></div>
        <div class="<?= $cls('reg_end') ?>"><label for="reg_end"><?= e(t('Registrasi ditutup')) ?> <span class="muted" style="font-weight:500">(<?= e(t('opsional')) ?>)</span></label><input class="inp" type="date" id="reg_end" name="reg_end" value="<?= $v('reg_end') ?>"><?= field_error($errors, 'reg_end') ?></div>
      </div>
      <div class="g-form two" style="gap:12px">
        <div class="<?= $cls('att_start') ?>"><label for="att_start"><?= e(t('Absensi mulai')) ?><span class="req">*</span></label><input class="inp" type="time" id="att_start" name="att_start" required value="<?= $v('att_start') ?>"><?= field_error($errors, 'att_start') ?></div>
        <div class="<?= $cls('att_end') ?>"><label for="att_end"><?= e(t('Absensi selesai')) ?><span class="req">*</span></label><input class="inp" type="time" id="att_end" name="att_end" required value="<?= $v('att_end') ?>"><?= field_error($errors, 'att_end') ?></div>
      </div>
      <?php if (Event::hasQuotaColumns()): ?>
      <div class="g-form two" style="gap:12px">
        <div class="<?= $cls('reg_end_time') ?>"><label for="reg_end_time"><?= e(t('Jam registrasi ditutup')) ?><span class="en"><?= e(t('Opsional, berlaku pada tanggal tutup')) ?></span></label><input class="inp" type="time" id="reg_end_time" name="reg_end_time" value="<?= $v('reg_end_time') ?>"><?= field_error($errors, 'reg_end_time') ?></div>
        <div class="<?= $cls('quota') ?>"><label for="quota"><?= e(t('Kuota peserta')) ?><span class="en"><?= e(t('Kosongkan bila tanpa batas')) ?></span></label><input class="inp" type="number" inputmode="numeric" min="1" max="100000" step="1" id="quota" name="quota" value="<?= $v('quota') ?>" placeholder="<?= e(t('Contoh: 140')) ?>"><?= field_error($errors, 'quota') ?></div>
      </div>
      <?php endif; ?>
    </div>
    <?php if (Event::hasQuotaColumns()): ?><p class="hint small muted"><?= e(t('Registrasi otomatis ditutup saat jumlah pendaftar mencapai kuota, atau saat tanggal dan jam tutup terlewati.')) ?></p><?php else: ?><p class="hint small" style="color:var(--warn-ink)"><?= e(t('Jalankan migrasi database untuk memakai kuota peserta dan jam tutup registrasi.')) ?></p><?php endif; ?>
    <p class="hint small muted"><?= e(t('Jam absensi dipakai sebagai informasi dan grafik. Scan hanya tersimpan saat absensi dibuka secara manual di halaman detail event.')) ?></p>
  </div>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Status')) ?></h3></div>
    <div class="g-form two">
      <div class="<?= $cls('status') ?>"><label for="status"><?= e(t('Status event')) ?></label><select class="inp" id="status" name="status"><?php foreach (Event::STATUSES as $o): ?><option value="<?= e($o) ?>"<?= selected($e['status'] ?? 'Draft', $o) ?>><?= e(t($o)) ?></option><?php endforeach; ?></select><p class="hint"><?= e(t('Draft tidak tampil untuk publik. Registrasi hanya bisa saat Aktif.')) ?></p><?= field_error($errors, 'status') ?></div>
      <div class="<?= $cls('reg_status') ?>"><label for="reg_status"><?= e(t('Registrasi')) ?></label><select class="inp" id="reg_status" name="reg_status"><?php foreach (Event::REG_STATUSES as $o): ?><option value="<?= e($o) ?>"<?= selected($e['reg_status'] ?? 'Ditutup', $o) ?>><?= e(t($o)) ?></option><?php endforeach; ?></select><?= field_error($errors, 'reg_status') ?></div>
    </div>
  </div>
  <div class="row" style="justify-content:flex-end">
    <a class="btn btn-ghost" href="<?= e(url($back)) ?>"><?= e(t('Batal')) ?></a>
    <button type="submit" class="btn btn-primary"><?= e($editId ? t('Simpan perubahan') : t('Buat event')) ?></button>
  </div>
</form>
