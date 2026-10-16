<?php
use App\Core\View;

$regLink = full_url('e/' . $e['slug']);
$attNext = ['notyet' => ['open', 'Buka absensi', 'btn-primary'], 'open' => ['closed', 'Tutup absensi', 'btn-danger'], 'closed' => ['open', 'Buka kembali absensi', 'btn-outline']][$e['att_status']];
?>
<div class="pagehead">
  <div><h2><?= e($e['name']) ?></h2><p><?= e(date_id($e['event_date'], true)) ?> · <?= e($e['start_time']) ?>–<?= e($e['end_time']) ?> <?= e(config('timezone_label', 'WITA')) ?></p></div>
  <div class="row"><a class="btn btn-outline sm" href="<?= e(url('admin/event/' . $e['id'] . '/ubah')) ?>"><?= icon('edit', 16) ?>Ubah</a><a class="btn btn-outline sm" href="<?= e(url('admin/peserta', ['ev' => $e['id']])) ?>"><?= icon('users', 16) ?>Peserta</a></div>
</div>
<div class="row"><?= event_badge($e['status']) ?><?= reg_badge($e['reg_status']) ?><?= att_badge($e['att_status']) ?></div>
<?= View::partial('stats', ['stats' => $stats]) ?>
<div class="g-2 even">
  <div class="card">
    <div class="card-h"><h3>Tautan registrasi</h3></div>
    <div class="copyrow"><input class="inp" id="reg-link" value="<?= e($regLink) ?>" readonly aria-label="Tautan registrasi"><button type="button" class="btn btn-outline" data-copy="#reg-link"><?= icon('copy', 18) ?>Salin</button></div>
    <?php if ($e['status'] !== 'Aktif'): ?><p class="hint small" style="color:var(--warn-ink)">Event berstatus <?= e($e['status']) ?>. Ubah ke Aktif agar peserta dapat mendaftar.</p><?php endif; ?>
    <a class="btn btn-ghost sm" href="<?= e($regLink) ?>" target="_blank" rel="noopener"><?= icon('eye', 16) ?>Lihat halaman publik</a>
  </div>
  <div class="card">
    <div class="card-h"><h3>Kendali cepat</h3></div>
    <div class="kv">
      <div><span class="k">Registrasi</span><span class="v"><?= post_button('admin/event/' . $e['id'] . '/pengaturan', $e['reg_status'] === 'Dibuka' ? 'Tutup registrasi' : 'Buka registrasi', ['field' => 'reg_status', 'value' => $e['reg_status'] === 'Dibuka' ? 'Ditutup' : 'Dibuka'], 'btn ' . ($e['reg_status'] === 'Dibuka' ? 'btn-outline' : 'btn-primary') . ' sm', $e['reg_status'] === 'Dibuka' ? 'Tutup registrasi? Peserta baru tidak bisa mendaftar.' : '') ?></span></div>
      <div><span class="k">Absensi (scan QR)</span><span class="v"><?= post_button('admin/event/' . $e['id'] . '/pengaturan', $attNext[1], ['field' => 'att_status', 'value' => $attNext[0]], 'btn ' . $attNext[2] . ' sm', $attNext[0] === 'closed' ? 'Tutup absensi? Scan berikutnya tidak akan tersimpan.' : '') ?></span></div>
      <div><span class="k">Status event</span><span class="v">
        <form method="post" action="<?= e(url('admin/event/' . $e['id'] . '/pengaturan')) ?>" class="row" style="gap:6px;justify-content:flex-end"><?= csrf_field() ?><input type="hidden" name="field" value="status">
          <select class="inp" name="value" style="min-height:38px;width:auto;padding-top:6px;padding-bottom:6px" aria-label="Status event"><?php foreach (\App\Models\Event::STATUSES as $o): ?><option<?= selected($e['status'], $o) ?>><?= e($o) ?></option><?php endforeach; ?></select>
          <button class="btn btn-outline sm">Simpan</button></form></span></div>
    </div>
  </div>
</div>
<div class="g-2 even">
  <div class="card">
    <div class="card-h"><h3>Detail</h3></div>
    <dl class="kv">
      <div><dt>Penyelenggara</dt><dd><?= e($e['organizer']) ?></dd></div>
      <div><dt>Lokasi</dt><dd><?= e($e['location']) ?></dd></div>
      <div><dt>Periode registrasi</dt><dd><?= e(date_id($e['reg_start'])) ?> – <?= e(date_id($e['reg_end'])) ?></dd></div>
      <div><dt>Jam absensi</dt><dd class="mono"><?= e($e['att_start']) ?>–<?= e($e['att_end']) ?></dd></div>
    </dl>
    <?php if ($e['description']): ?><p class="small" style="color:var(--ink2)"><?= nl2br(e($e['description'])) ?></p><?php endif; ?>
  </div>
  <div class="card">
    <div class="card-h"><h3>Admin Absensi bertugas</h3><a class="btn btn-ghost sm" href="<?= e(url('admin/akun/baru', ['ev' => $e['id']])) ?>"><?= icon('userplus', 16) ?>Tambah</a></div>
    <?php if (!$admins): ?><p class="muted small">Belum ada Admin Absensi untuk event ini.</p><?php else: ?>
      <div class="list"><?php foreach ($admins as $a): ?>
        <a class="pc" href="<?= e(url('admin/akun/' . $a['id'])) ?>"><span class="av t"><?= e(initials($a['name'])) ?></span><span class="who"><b><?= e($a['name']) ?></b><span>@<?= e($a['username']) ?></span></span><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><?= e($a['status']) ?></span></a>
      <?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-h"><h3>Hapus event</h3></div>
  <p class="small muted">Hanya event tanpa peserta yang bisa dihapus. Event yang sudah berjalan sebaiknya diubah menjadi Ditutup atau Selesai.</p>
  <div><?= post_button('admin/event/' . $e['id'] . '/hapus', 'Hapus event', [], 'btn btn-danger sm', 'Hapus event "' . $e['name'] . '"? Tindakan ini tidak dapat dibatalkan.', 'trash') ?></div>
</div>
