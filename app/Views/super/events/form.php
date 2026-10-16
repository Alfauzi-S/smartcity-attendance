<?php
use App\Models\Event;

$v = static fn (string $k) => e($e[$k] ?? '');
$cls = static fn (string $k) => 'fld' . (isset($errors[$k]) ? ' is-err' : '');
$action = $editId ? 'admin/event/' . $editId . '/ubah' : 'admin/event/baru';
?>
<?php if ($errors): ?><div class="banner bad" role="alert"><?= icon('alert') ?><div class="grow"><b>Periksa kembali <?= count($errors) ?> isian</b>Kolom yang ditandai merah belum sesuai.</div></div><?php endif; ?>
<form method="post" action="<?= e(url($action)) ?>" class="stack" novalidate data-loading="Menyimpan event…">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-h"><h3>Informasi event</h3></div>
    <div class="<?= $cls('name') ?>"><label for="name">Nama event<span class="req">*</span></label><input class="inp" id="name" name="name" maxlength="160" required value="<?= $v('name') ?>"><?= field_error($errors, 'name') ?></div>
    <div class="<?= $cls('description') ?>"><label for="description">Deskripsi</label><textarea class="inp" id="description" name="description" maxlength="2000"><?= $v('description') ?></textarea></div>
    <div class="g-form two">
      <div class="<?= $cls('organizer') ?>"><label for="organizer">Penyelenggara<span class="req">*</span></label><input class="inp" id="organizer" name="organizer" maxlength="160" required value="<?= $v('organizer') ?>"><?= field_error($errors, 'organizer') ?></div>
      <div class="<?= $cls('location') ?>"><label for="location">Lokasi<span class="req">*</span></label><input class="inp" id="location" name="location" maxlength="200" required value="<?= $v('location') ?>"><?= field_error($errors, 'location') ?></div>
    </div>
  </div>
  <div class="card">
    <div class="card-h"><h3>Jadwal</h3><span class="sub">Zona waktu <?= e(config('timezone_label', 'WITA')) ?></span></div>
    <div class="g-form two">
      <div class="<?= $cls('event_date') ?>"><label for="event_date">Tanggal event<span class="req">*</span></label><input class="inp" type="date" id="event_date" name="event_date" required value="<?= $v('event_date') ?>"><?= field_error($errors, 'event_date') ?></div>
      <div class="g-form two" style="gap:12px">
        <div class="<?= $cls('start_time') ?>"><label for="start_time">Jam mulai<span class="req">*</span></label><input class="inp" type="time" id="start_time" name="start_time" required value="<?= $v('start_time') ?>"><?= field_error($errors, 'start_time') ?></div>
        <div class="<?= $cls('end_time') ?>"><label for="end_time">Jam selesai<span class="req">*</span></label><input class="inp" type="time" id="end_time" name="end_time" required value="<?= $v('end_time') ?>"><?= field_error($errors, 'end_time') ?></div>
      </div>
      <div class="g-form two" style="gap:12px">
        <div class="<?= $cls('reg_start') ?>"><label for="reg_start">Registrasi dibuka<span class="en">Opsional</span></label><input class="inp" type="date" id="reg_start" name="reg_start" value="<?= $v('reg_start') ?>"><?= field_error($errors, 'reg_start') ?></div>
        <div class="<?= $cls('reg_end') ?>"><label for="reg_end">Registrasi ditutup<span class="en">Opsional</span></label><input class="inp" type="date" id="reg_end" name="reg_end" value="<?= $v('reg_end') ?>"><?= field_error($errors, 'reg_end') ?></div>
      </div>
      <div class="g-form two" style="gap:12px">
        <div class="<?= $cls('att_start') ?>"><label for="att_start">Absensi mulai<span class="req">*</span></label><input class="inp" type="time" id="att_start" name="att_start" required value="<?= $v('att_start') ?>"><?= field_error($errors, 'att_start') ?></div>
        <div class="<?= $cls('att_end') ?>"><label for="att_end">Absensi selesai<span class="req">*</span></label><input class="inp" type="time" id="att_end" name="att_end" required value="<?= $v('att_end') ?>"><?= field_error($errors, 'att_end') ?></div>
      </div>
    </div>
    <p class="hint small muted">Jam absensi dipakai sebagai informasi dan grafik. Scan hanya tersimpan saat absensi dibuka secara manual di halaman detail event.</p>
  </div>
  <div class="card">
    <div class="card-h"><h3>Status</h3></div>
    <div class="g-form two">
      <div class="<?= $cls('status') ?>"><label for="status">Status event</label><select class="inp" id="status" name="status"><?php foreach (Event::STATUSES as $o): ?><option<?= selected($e['status'] ?? 'Draft', $o) ?>><?= e($o) ?></option><?php endforeach; ?></select><p class="hint">Draft tidak tampil untuk publik. Registrasi hanya bisa saat Aktif.</p><?= field_error($errors, 'status') ?></div>
      <div class="<?= $cls('reg_status') ?>"><label for="reg_status">Registrasi</label><select class="inp" id="reg_status" name="reg_status"><?php foreach (Event::REG_STATUSES as $o): ?><option<?= selected($e['reg_status'] ?? 'Ditutup', $o) ?>><?= e($o) ?></option><?php endforeach; ?></select><?= field_error($errors, 'reg_status') ?></div>
    </div>
  </div>
  <div class="row" style="justify-content:flex-end">
    <a class="btn btn-ghost" href="<?= e(url($back)) ?>">Batal</a>
    <button type="submit" class="btn btn-primary"><?= $editId ? 'Simpan perubahan' : 'Buat event' ?></button>
  </div>
</form>
