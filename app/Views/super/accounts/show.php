<?php
use App\Core\Auth;
use App\Services\Access;

$base = 'admin/akun/' . $a['id'];
$isEvent = $a['role'] === Auth::EVENT;
?>
<?php if ($credential): ?>
  <div class="card" style="border-color:#BFE6CC">
    <div class="card-h"><h3><?= e(t('Kredensial login')) ?></h3><span class="bdg bdg-warn"><?= e(t('Hanya tampil sekali')) ?></span></div>
    <div class="cred">
      <span><?= e(t('Username')) ?> <b class="mono" id="cred-u"><?= e($credential['username']) ?></b></span>
      <span><?= e(t('Password sementara')) ?> <b class="mono" id="cred-p"><?= e($credential['password']) ?></b></span>
      <span><?= e(t('Alamat login')) ?> <b class="mono"><?= e(full_url('masuk')) ?></b></span>
    </div>
    <textarea class="sr" id="cred-all" readonly><?= e(t('Login SmartCity Attendance')) ?>&#10;<?= e(full_url('masuk')) ?>&#10;<?= e(t('Username')) ?>: <?= e($credential['username']) ?>&#10;<?= e(t('Password sementara')) ?>: <?= e($credential['password']) ?></textarea>
    <div><button type="button" class="btn btn-outline sm" data-copy="#cred-all"><?= icon('copy', 16) ?><?= e(t('Salin kredensial')) ?></button></div>
  </div>
<?php endif; ?>
<div class="card">
  <div class="idcard"><span class="av lg t"><?= e(initials($a['name'])) ?></span><div><b><?= e($a['name']) ?></b><span class="mono">@<?= e($a['username']) ?></span></div></div>
  <div class="row"><span class="bdg <?= $isEvent ? 'bdg-navy' : 'bdg-teal' ?>"><?= e(Auth::roleLabel($a['role'])) ?></span><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><i></i><?= e(t($a['status'])) ?></span><?php if ((int) $a['must_change_password'] === 1): ?><span class="bdg bdg-warn"><?= e(t('Password sementara')) ?></span><?php endif; ?></div>
  <dl class="kv">
    <?php if (!$isEvent): ?><div><dt><?= e(t('Event')) ?></dt><dd><?= $event ? '<a href="' . e(url('admin/event/' . $event['id'])) . '">' . e($event['name']) . '</a>' : e(t('Belum ditugaskan')) ?></dd></div><?php endif; ?>
    <div><dt><?= e(t('Total scan')) ?></dt><dd><?= e(t(':n peserta', ['n' => (int) $scans])) ?></dd></div>
    <div><dt><?= e(t('Login terakhir')) ?></dt><dd><?= e(datetime_id($a['last_login_at'])) ?></dd></div>
    <div><dt><?= e(t('Dibuat')) ?></dt><dd><?= e(datetime_id($a['created_at'])) ?></dd></div>
  </dl>
</div>
<form method="post" action="<?= e(url($base . '/ubah')) ?>" class="card">
  <?= csrf_field() ?>
  <div class="card-h"><h3><?= e(t('Ubah akun')) ?></h3></div>
  <div class="g-form two">
    <div class="fld"><label for="name"><?= e(t('Nama')) ?></label><input class="inp" id="name" name="name" maxlength="120" required value="<?= e($a['name']) ?>"></div>
    <?php if (!$isEvent): ?><div class="fld"><label for="event_id"><?= e(t('Ditugaskan ke event')) ?></label><?= \App\Core\View::partial('event_select', ['events' => $events, 'value' => $a['event_id']]) ?></div><?php endif; ?>
  </div>
  <div><button class="btn btn-primary" type="submit"><?= e(t('Simpan')) ?></button></div>
</form>
<?php if ($isEvent): ?>
<div class="card" id="penugasan">
  <div class="card-h"><h3><?= e(t('Event yang ditugaskan')) ?></h3><span class="sub"><?= e(t(':n event', ['n' => count($assignments)])) ?></span></div>
  <p class="small muted"><?= e(t('Admin Event hanya dapat membuka event di bawah ini, dengan izin yang dicentang. Mencabut penugasan langsung menghentikan akses.')) ?></p>
  <?php if (!$assignments): ?><p class="muted small"><?= e(t('Belum ada event yang ditugaskan.')) ?></p><?php endif; ?>
  <?php foreach ($assignments as $as): ?>
    <form method="post" action="<?= e(url($base . '/penugasan')) ?>" class="card flat">
      <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= (int) $as['event_id'] ?>"><input type="hidden" name="mode" value="update">
      <div class="card-h"><h3 style="font-size:15px"><a href="<?= e(url('admin/event/' . $as['event_id'])) ?>"><?= e($as['event_name']) ?></a></h3><?= event_badge($as['event_status']) ?></div>
      <div class="g-form two">
        <?php foreach (Access::PERMS as $k => $label): ?>
          <label class="chk"><input type="checkbox" name="perms[]" value="<?= e($k) ?>"<?= in_array($k, $as['perms'], true) ? ' checked' : '' ?>><span><?= e(t($label)) ?></span></label>
        <?php endforeach; ?>
      </div>
      <div class="row"><button class="btn btn-primary sm" type="submit"><?= e(t('Simpan izin')) ?></button></div>
    </form>
    <div class="row" style="margin-top:-6px"><?= post_button($base . '/penugasan/' . $as['event_id'] . '/hapus', t('Cabut penugasan'), [], 'btn btn-danger sm', t('Cabut penugasan @:username dari event ":event"? Akses langsung berakhir.', ['username' => $a['username'], 'event' => $as['event_name']]), 'x') ?></div>
  <?php endforeach; ?>
  <?php $assigned = array_column($assignments, 'event_id'); $free = array_filter($events, static fn ($ev) => !in_array($ev['id'], $assigned)); ?>
  <?php if ($free): ?>
  <form method="post" action="<?= e(url($base . '/penugasan')) ?>" class="row">
    <?= csrf_field() ?><input type="hidden" name="mode" value="add">
    <select class="inp" name="event_id" aria-label="<?= e(t('Pilih event')) ?>" style="flex:1;min-width:200px">
      <?php foreach ($free as $ev): ?><option value="<?= (int) $ev['id'] ?>"><?= e($ev['name']) ?> · <?= e(date_id($ev['event_date'])) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline" type="submit"><?= icon('plus', 16) ?><?= e(t('Tugaskan ke event')) ?></button>
  </form>
  <p class="hint small"><?= e(t('Izin awal: semua izin operasional kecuali publikasi dan pencabutan sertifikat. Ubah setelah ditambahkan.')) ?></p>
  <?php endif; ?>
</div>
<?php $mine = array_filter($logs, static fn ($l) => (string) $l['object_id'] === (string) $a['id']); ?>
<?php if ($mine): ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Riwayat penugasan')) ?></h3></div>
  <div class="hist">
    <?php foreach (array_slice($mine, 0, 10) as $l): $m = json_decode((string) $l['meta'], true) ?: []; ?>
      <div class="hi"><span class="hic"><?= icon('shield', 16) ?></span><div class="hb">
        <span class="t1"><?= e(t(['assignment.add' => 'Ditugaskan ke event', 'assignment.update' => 'Izin diubah', 'assignment.remove' => 'Penugasan dicabut'][$l['action']] ?? $l['action'])) ?> · <?= e($l['event_name'] ?? ('#' . $l['event_id'])) ?></span>
        <?php if (!empty($m['added']) || !empty($m['removed'])): ?><span class="t2 small"><?= !empty($m['added']) ? '+ ' . e(implode(', ', $m['added'])) : '' ?> <?= !empty($m['removed']) ? '− ' . e(implode(', ', $m['removed'])) : '' ?></span><?php endif; ?>
        <span class="t3"><?= e(datetime_id($l['created_at'])) ?> · @<?= e($l['username']) ?></span>
      </div></div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Keamanan akun')) ?></h3></div>
  <div class="row">
    <?= post_button($base . '/reset', t('Buat password sementara baru'), [], 'btn btn-outline sm', t('Buat password sementara baru? Password lama langsung tidak berlaku.'), 'key') ?>
    <?= $a['status'] === 'Aktif'
        ? post_button($base . '/status', t('Nonaktifkan akun'), [], 'btn btn-danger sm', t('Nonaktifkan akun @:username? Petugas tidak bisa login sampai diaktifkan lagi.', ['username' => $a['username']]), 'power')
        : post_button($base . '/status', t('Aktifkan akun'), [], 'btn btn-primary sm', '', 'power') ?>
  </div>
  <p class="small muted"><?= e(t('Riwayat scan petugas tetap tersimpan meskipun akun dinonaktifkan.')) ?></p>
</div>
