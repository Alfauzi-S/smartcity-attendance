<?php $base = 'admin/akun/' . $a['id']; ?>
<?php if ($credential): ?>
  <div class="card" style="border-color:#BFE6CC">
    <div class="card-h"><h3>Kredensial login</h3><span class="bdg bdg-warn">Hanya tampil sekali</span></div>
    <div class="cred">
      <span>Username <b class="mono" id="cred-u"><?= e($credential['username']) ?></b></span>
      <span>Password sementara <b class="mono" id="cred-p"><?= e($credential['password']) ?></b></span>
      <span>Alamat login <b class="mono"><?= e(full_url('masuk')) ?></b></span>
    </div>
    <textarea class="sr" id="cred-all" readonly>Login SmartCity Attendance&#10;<?= e(full_url('masuk')) ?>&#10;Username: <?= e($credential['username']) ?>&#10;Password sementara: <?= e($credential['password']) ?></textarea>
    <div><button type="button" class="btn btn-outline sm" data-copy="#cred-all"><?= icon('copy', 16) ?>Salin kredensial</button></div>
  </div>
<?php endif; ?>
<div class="card">
  <div class="idcard"><span class="av lg t"><?= e(initials($a['name'])) ?></span><div><b><?= e($a['name']) ?></b><span class="mono">@<?= e($a['username']) ?></span></div></div>
  <div class="row"><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><i></i><?= e($a['status']) ?></span><?php if ((int) $a['must_change_password'] === 1): ?><span class="bdg bdg-warn">Password sementara</span><?php endif; ?></div>
  <dl class="kv">
    <div><dt>Event</dt><dd><?= $event ? '<a href="' . e(url('admin/event/' . $event['id'])) . '">' . e($event['name']) . '</a>' : 'Belum ditugaskan' ?></dd></div>
    <div><dt>Total scan</dt><dd><?= (int) $scans ?> peserta</dd></div>
    <div><dt>Login terakhir</dt><dd><?= e(datetime_id($a['last_login_at'])) ?></dd></div>
    <div><dt>Dibuat</dt><dd><?= e(datetime_id($a['created_at'])) ?></dd></div>
  </dl>
</div>
<form method="post" action="<?= e(url($base . '/ubah')) ?>" class="card">
  <?= csrf_field() ?>
  <div class="card-h"><h3>Ubah akun</h3></div>
  <div class="g-form two">
    <div class="fld"><label for="name">Nama petugas</label><input class="inp" id="name" name="name" maxlength="120" required value="<?= e($a['name']) ?>"></div>
    <div class="fld"><label for="event_id">Ditugaskan ke event</label><?= \App\Core\View::partial('event_select', ['events' => $events, 'value' => $a['event_id']]) ?></div>
  </div>
  <div><button class="btn btn-primary" type="submit">Simpan</button></div>
</form>
<div class="card">
  <div class="card-h"><h3>Keamanan akun</h3></div>
  <div class="row">
    <?= post_button($base . '/reset', 'Buat password sementara baru', [], 'btn btn-outline sm', 'Buat password sementara baru? Password lama langsung tidak berlaku.', 'key') ?>
    <?= $a['status'] === 'Aktif'
        ? post_button($base . '/status', 'Nonaktifkan akun', [], 'btn btn-danger sm', 'Nonaktifkan akun @' . $a['username'] . '? Petugas tidak bisa login sampai diaktifkan lagi.', 'power')
        : post_button($base . '/status', 'Aktifkan akun', [], 'btn btn-primary sm', '', 'power') ?>
  </div>
  <p class="small muted">Riwayat scan petugas tetap tersimpan meskipun akun dinonaktifkan.</p>
</div>
