<?php $ticketUrl = full_url('t/' . $p['code']); ?>
<div class="pad narrow">
  <?php if ($isNew): ?>
    <div class="state s-ok" style="padding-bottom:0">
      <span class="okmark"><?= icon('check', 38) ?></span>
      <h3><?= e(t('Registrasi berhasil')) ?></h3>
      <p><?= e(t('Simpan QR Code di bawah. Tunjukkan kepada petugas saat datang ke lokasi.')) ?></p>
    </div>
  <?php endif; ?>
  <div class="qrcard">
    <span class="tc"><?= e(t('QR Code Kehadiran')) ?></span>
    <div class="qrbox" style="width:min(100%,260px)"><div class="qr" id="ticket-qr" data-qr="<?= e($ticketUrl) ?>" role="img" aria-label="<?= e(t('QR Code :code', ['code' => $p['code']])) ?>"></div></div>
    <span class="code"><?= e($p['code']) ?></span>
    <div class="idcard" style="flex-direction:column;text-align:center;gap:4px">
      <b><?= e($p['full_name']) ?></b>
      <span class="mono"><?= e($p['identity_number']) ?> · <?= e(t($p['field_of_study'])) ?></span>
      <span><?= e($p['institution']) ?></span>
    </div>
    <?= status_badge($p['status'], true) ?>
    <?php if ($p['status'] === 'hadir'): ?><p class="small muted"><?= e(t('Tercatat hadir :time', ['time' => datetime_id($p['checked_in_at'])])) ?></p><?php endif; ?>
  </div>
  <div class="stack">
    <button type="button" class="btn btn-primary lg block" data-qr-download="ticket-qr" data-name="<?= e($p['full_name']) ?>" data-code="<?= e($p['code']) ?>" data-event="<?= e($event['name']) ?>"><?= icon('download') ?><?= e(t('Unduh QR Code (PNG)')) ?></button>
    <div class="copyrow">
      <input class="inp" id="ticket-link" value="<?= e($ticketUrl) ?>" readonly aria-label="<?= e(t('Tautan tiket')) ?>">
      <button type="button" class="btn btn-outline" data-copy="#ticket-link"><?= icon('copy', 18) ?><?= e(t('Salin')) ?></button>
    </div>
    <p class="hint small muted"><?= e(t('Tautan ini menampilkan QR Anda kapan saja. Jangan bagikan ke orang lain.')) ?></p>
  </div>
  <div class="card">
    <div class="card-h"><h3><?= e($event['name']) ?></h3></div>
    <dl class="kv">
      <div><dt><?= e(t('Tanggal')) ?></dt><dd><?= e(date_id($event['event_date'], true)) ?></dd></div>
      <div><dt><?= e(t('Presensi dibuka')) ?></dt><dd class="mono"><?= e($event['att_start']) ?>–<?= e($event['att_end']) ?> <?= e(config('timezone_label', 'WITA')) ?></dd></div>
      <div><dt><?= e(t('Lokasi')) ?></dt><dd><?= e($event['location']) ?></dd></div>
    </dl>
  </div>
  <a class="btn btn-ghost block" href="<?= e(url('e/' . $event['slug'])) ?>"><?= e(t('Kembali ke halaman event')) ?></a>
</div>
<?php \App\Core\View::script('vendor/qrcode.min.js'); ?>
