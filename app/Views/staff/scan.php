<?php
use App\Core\View;

View::script('vendor/html5-qrcode.min.js');
View::script('js/scanner.js');
$isSuper = $user['role'] === 'super';
?>
<?php if (!$event): ?>
  <?= View::partial('no_event', $isSuper ? ['heading' => 'Belum ada event', 'text' => 'Buat event terlebih dahulu.', 'cta' => ['admin/event/baru', 'Buat event']] : ['heading' => 'Akun belum ditugaskan ke event', 'text' => 'Minta Super Admin menugaskan akun ini ke sebuah event.']) ?>
<?php else: ?>
<div class="pagehead">
  <div><h2><?= e($event['name']) ?></h2><p>Absensi <?= e($event['att_start']) ?>–<?= e($event['att_end']) ?> <?= e(config('timezone_label', 'WITA')) ?> · <?= e(date_id($event['event_date'])) ?></p></div>
  <div class="row"><?= att_badge($event['att_status']) ?><?php if ($isSuper): ?><?= View::partial('event_picker', ['events' => $events, 'event' => $event, 'path' => 'petugas/scan']) ?><?php endif; ?></div>
</div>
<?php if ($event['att_status'] !== 'open'): ?>
  <div class="banner <?= $event['att_status'] === 'closed' ? 'bad' : 'warn' ?>"><?= icon('clock') ?><div class="grow"><b><?= $event['att_status'] === 'closed' ? 'Absensi sudah ditutup' : 'Absensi belum dibuka' ?></b>QR masih bisa dipindai untuk dicek, tetapi kehadiran tidak akan tersimpan<?= $isSuper ? '. Buka absensi di halaman detail event.' : ' sampai Super Admin membuka absensi.' ?></div></div>
<?php endif; ?>
<div class="scan-grid" id="scan-app"
     data-validate="<?= e(url('api/scan/validate')) ?>"
     data-save="<?= e(url('api/scan/save')) ?>"
     data-search="<?= e(url('api/scan/search')) ?>"
     data-event="<?= (int) $event['id'] ?>"
     data-login="<?= e(url('masuk')) ?>">
  <div class="stack">
    <div class="scanner" id="scanner">
      <div id="reader"></div>
      <div class="vf" id="vf"><i></i><i></i><i></i><i></i><span class="line"></span></div>
      <div class="cap" id="scan-cap">Arahkan kamera ke <b>QR Code peserta</b></div>
      <div class="over" id="scan-over"><div class="in"><span class="spin"></span><span id="scan-over-t">Menyalakan kamera…</span><p id="scan-over-p">Izinkan akses kamera jika browser meminta.</p></div></div>
    </div>
    <div class="row" style="justify-content:center">
      <button type="button" class="btn btn-outline sm" id="cam-switch" hidden><?= icon('refresh', 16) ?>Ganti kamera</button>
      <button type="button" class="btn btn-outline sm" id="cam-retry" hidden><?= icon('refresh', 16) ?>Coba nyalakan kamera</button>
    </div>
  </div>
  <div class="stack">
    <div class="card" id="scan-result" tabindex="-1" aria-live="polite">
      <div class="state s-info"><span class="sic"><?= icon('scan', 30) ?></span><h3>Siap memindai</h3><p>Hasil pemindaian muncul di sini. Kehadiran baru tersimpan setelah Anda menekan <b>Simpan Kehadiran</b>.</p></div>
    </div>
    <div class="card">
      <div class="card-h"><h3>Input manual</h3><span class="sub">Bila QR rusak atau kamera tidak tersedia</span></div>
      <form id="manual-code" class="copyrow" autocomplete="off">
        <input class="inp mono" name="code" placeholder="Kode QR, mis. SC7KX29QDA" aria-label="Kode QR peserta" maxlength="300" autocapitalize="characters">
        <button type="submit" class="btn btn-primary">Cek</button>
      </form>
      <div class="fld">
        <label for="manual-q">Cari nama atau nomor identitas</label>
        <div class="inp-ic"><?= icon('search', 18) ?><input class="inp" id="manual-q" type="search" placeholder="Ketik minimal 2 huruf" autocomplete="off"></div>
      </div>
      <div class="list" id="manual-results"></div>
    </div>
  </div>
</div>
<template id="tpl-icons">
  <span data-i="check"><?= icon('check', 30) ?></span><span data-i="alert"><?= icon('alert', 30) ?></span><span data-i="x"><?= icon('x', 30) ?></span>
  <span data-i="search"><?= icon('search', 30) ?></span><span data-i="clock"><?= icon('clock', 30) ?></span><span data-i="lock"><?= icon('lock', 30) ?></span>
  <span data-i="wifioff"><?= icon('wifioff', 30) ?></span><span data-i="camoff"><?= icon('camoff', 30) ?></span><span data-i="user"><?= icon('user', 30) ?></span><span data-i="scan"><?= icon('scan', 30) ?></span>
</template>
<?php endif; ?>
