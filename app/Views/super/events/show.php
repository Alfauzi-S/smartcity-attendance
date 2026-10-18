<?php
use App\Core\View;

$regLink = full_url('e/' . $e['slug']);
$canEdit = can('event_edit', (int) $e['id']);
$attNext = ['notyet' => ['open', t('Buka absensi'), 'btn-primary'], 'open' => ['closed', t('Tutup absensi'), 'btn-danger'], 'closed' => ['open', t('Buka kembali absensi'), 'btn-outline']][$e['att_status']];
?>
<div class="pagehead">
  <div><h2><?= e($e['name']) ?></h2><p><?= e(date_id($e['event_date'], true)) ?> · <?= e($e['start_time']) ?>–<?= e($e['end_time']) ?> <?= e(config('timezone_label', 'WITA')) ?></p></div>
  <div class="row">
    <?php if ($canEdit): ?><a class="btn btn-outline sm" href="<?= e(url('admin/event/' . $e['id'] . '/ubah')) ?>"><?= icon('edit', 16) ?><?= e(t('Ubah')) ?></a><?php endif; ?>
    <?php if (can('participants', (int) $e['id'])): ?><a class="btn btn-outline sm" href="<?= e(url('admin/peserta', ['ev' => $e['id']])) ?>"><?= icon('users', 16) ?><?= e(t('Peserta')) ?></a><?php endif; ?>
    <?php if (can('cert_manage', (int) $e['id']) || can('cert_publish', (int) $e['id'])): ?><a class="btn btn-outline sm" href="<?= e(url('admin/sertifikat', ['ev' => $e['id']])) ?>"><?= icon('award', 16) ?><?= e(t('Sertifikat')) ?></a><?php endif; ?>
  </div>
</div>
<div class="row"><?= event_badge($e['status']) ?><?= reg_badge($e['reg_status']) ?><?= att_badge($e['att_status']) ?></div>
<?= View::partial('stats', ['stats' => $stats]) ?>
<div class="g-2 even">
  <div class="card">
    <div class="card-h"><h3><?= e(t('Tautan registrasi')) ?></h3></div>
    <div class="copyrow"><input class="inp" id="reg-link" value="<?= e($regLink) ?>" readonly aria-label="<?= e(t('Tautan registrasi')) ?>"><button type="button" class="btn btn-outline" data-copy="#reg-link"><?= icon('copy', 18) ?><?= e(t('Salin')) ?></button></div>
    <?php if ($e['status'] !== 'Aktif'): ?><p class="hint small" style="color:var(--warn-ink)"><?= e(t('Event berstatus :status. Ubah ke Aktif agar peserta dapat mendaftar.', ['status' => t($e['status'])])) ?></p><?php endif; ?>
    <a class="btn btn-ghost sm" href="<?= e($regLink) ?>" target="_blank" rel="noopener"><?= icon('eye', 16) ?><?= e(t('Lihat halaman publik')) ?></a>
  </div>
  <?php if ($canEdit): ?>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Kendali cepat')) ?></h3></div>
    <div class="kv">
      <div><span class="k"><?= e(t('Registrasi')) ?></span><span class="v"><?= post_button('admin/event/' . $e['id'] . '/pengaturan', $e['reg_status'] === 'Dibuka' ? t('Tutup registrasi') : t('Buka registrasi'), ['field' => 'reg_status', 'value' => $e['reg_status'] === 'Dibuka' ? 'Ditutup' : 'Dibuka'], 'btn ' . ($e['reg_status'] === 'Dibuka' ? 'btn-outline' : 'btn-primary') . ' sm', $e['reg_status'] === 'Dibuka' ? t('Tutup registrasi? Peserta baru tidak bisa mendaftar.') : '') ?></span></div>
      <div><span class="k"><?= e(t('Absensi (scan QR)')) ?></span><span class="v"><?= post_button('admin/event/' . $e['id'] . '/pengaturan', $attNext[1], ['field' => 'att_status', 'value' => $attNext[0]], 'btn ' . $attNext[2] . ' sm', $attNext[0] === 'closed' ? t('Tutup absensi? Scan berikutnya tidak akan tersimpan.') : '') ?></span></div>
      <div><span class="k"><?= e(t('Status event')) ?></span><span class="v">
        <form method="post" action="<?= e(url('admin/event/' . $e['id'] . '/pengaturan')) ?>" class="row" style="gap:6px;justify-content:flex-end"><?= csrf_field() ?><input type="hidden" name="field" value="status">
          <select class="inp" name="value" style="min-height:38px;width:auto;padding-top:6px;padding-bottom:6px" aria-label="<?= e(t('Status event')) ?>"><?php foreach (\App\Models\Event::STATUSES as $o): ?><option value="<?= e($o) ?>"<?= selected($e['status'], $o) ?>><?= e(t($o)) ?></option><?php endforeach; ?></select>
          <button class="btn btn-outline sm"><?= e(t('Simpan')) ?></button></form></span></div>
    </div>
  </div>
  <?php endif; ?>
</div>
<div class="g-2 even">
  <div class="card">
    <div class="card-h"><h3><?= e(t('Detail')) ?></h3></div>
    <dl class="kv">
      <div><dt><?= e(t('Penyelenggara')) ?></dt><dd><?= e($e['organizer']) ?></dd></div>
      <div><dt><?= e(t('Lokasi')) ?></dt><dd><?= e($e['location']) ?></dd></div>
      <div><dt><?= e(t('Periode registrasi')) ?></dt><dd><?= e(date_id($e['reg_start'])) ?> – <?= e(date_id($e['reg_end'])) ?></dd></div>
      <div><dt><?= e(t('Jam absensi')) ?></dt><dd class="mono"><?= e($e['att_start']) ?>–<?= e($e['att_end']) ?></dd></div>
    </dl>
    <?php if ($e['description']): ?><p class="small" style="color:var(--ink2)"><?= nl2br(e($e['description'])) ?></p><?php endif; ?>
  </div>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Admin Absensi bertugas')) ?></h3><?php if (is_super()): ?><a class="btn btn-ghost sm" href="<?= e(url('admin/akun/baru', ['ev' => $e['id']])) ?>"><?= icon('userplus', 16) ?><?= e(t('Tambah')) ?></a><?php endif; ?></div>
    <?php if (!$admins): ?><p class="muted small"><?= e(t('Belum ada Admin Absensi untuk event ini.')) ?></p><?php else: ?>
      <div class="list"><?php foreach ($admins as $a): ?>
        <a class="pc" href="<?= e(is_super() ? url('admin/akun/' . $a['id']) : '#') ?>"><span class="av t"><?= e(initials($a['name'])) ?></span><span class="who"><b><?= e($a['name']) ?></b><span>@<?= e($a['username']) ?></span></span><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><?= e(t($a['status'])) ?></span></a>
      <?php endforeach; ?></div>
    <?php endif; ?>
    <div class="card-h"><h3><?= e(t('Admin Event')) ?></h3><?php if (is_super()): ?><a class="btn btn-ghost sm" href="<?= e(url('admin/akun/baru', ['ev' => $e['id'], 'peran' => 'event'])) ?>"><?= icon('userplus', 16) ?><?= e(t('Tambah')) ?></a><?php endif; ?></div>
    <?php if (!$eventAdmins): ?><p class="muted small"><?= e(t('Belum ada Admin Event untuk event ini.')) ?></p><?php else: ?>
      <div class="list"><?php foreach ($eventAdmins as $a): ?>
        <a class="pc" href="<?= e(is_super() ? url('admin/akun/' . $a['id'], []) . '#penugasan' : '#') ?>"><span class="av"><?= e(initials($a['name'])) ?></span><span class="who"><b><?= e($a['name']) ?></b><span>@<?= e($a['username']) ?> · <?= e(t(':n izin', ['n' => count(\App\Services\Access::decode($a['permissions']))])) ?></span></span><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><?= e(t($a['status'])) ?></span></a>
      <?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>
<?php if (is_super()): ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Hapus event')) ?></h3></div>
  <p class="small muted"><?= e(t('Hanya event tanpa peserta yang bisa dihapus. Event yang sudah berjalan sebaiknya diubah menjadi Ditutup atau Selesai.')) ?></p>
  <div><?= post_button('admin/event/' . $e['id'] . '/hapus', t('Hapus event'), [], 'btn btn-danger sm', t('Hapus event ":name"? Tindakan ini tidak dapat dibatalkan.', ['name' => $e['name']]), 'trash') ?></div>
</div>
<?php endif; ?>
