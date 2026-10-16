<?php $tz = config('timezone_label', 'WITA'); ?>
<section class="hero">
  <div class="in">
    <div class="row"><?= reg_badge($event['reg_status']) ?><?php if ($event['status'] !== 'Aktif'): ?><?= event_badge($event['status']) ?><?php endif; ?></div>
    <h1><?= e($event['name']) ?></h1>
    <?php if ($event['description']): ?><p><?= nl2br(e($event['description'])) ?></p><?php endif; ?>
    <div class="meta">
      <div><?= icon('calendar', 18) ?><?= e(date_id($event['event_date'], true)) ?></div>
      <div><?= icon('clock', 18) ?><?= e($event['start_time']) ?>–<?= e($event['end_time']) ?> <?= e($tz) ?></div>
      <div><?= icon('pin', 18) ?><?= e($event['location']) ?></div>
      <div><?= icon('building', 18) ?><?= e($event['organizer']) ?></div>
    </div>
    <div class="ctas">
      <?php if ($open): ?>
        <a class="btn btn-accent lg" href="<?= e(url('e/' . $event['slug'] . '/daftar')) ?>"><?= icon('userplus') ?>Daftar sekarang</a>
      <?php endif; ?>
      <?php if ($myTicket): ?>
        <a class="btn btn-ondark lg" href="<?= e(url('t/' . $myTicket)) ?>"><?= icon('qr') ?>Lihat QR saya</a>
      <?php endif; ?>
    </div>
    <img class="art" src="<?= e(asset('img/city.svg')) ?>" alt="">
  </div>
</section>
<div class="pad">
  <?php if (!$open): ?>
    <div class="banner lock"><?= icon('lock') ?><div class="grow"><b>Registrasi tidak tersedia</b><?= e($closedMessage) ?></div></div>
  <?php endif; ?>
  <div class="stepsrow">
    <div><span class="n">1</span><span><b>Isi formulir</b><span>Enam data wajib: identitas, profesi, nama, WhatsApp, institusi, dan bidang studi.</span></span></div>
    <div><span class="n">2</span><span><b>Simpan QR Code</b><span>Unduh gambar QR atau simpan tautan tiket. QR bersifat pribadi.</span></span></div>
    <div><span class="n">3</span><span><b>Tunjukkan saat datang</b><span>Petugas memindai QR Anda dan status berubah menjadi Hadir.</span></span></div>
  </div>
  <div class="g-2 even">
    <div class="card">
      <div class="card-h"><h3>Jadwal</h3></div>
      <div class="sched">
        <?php if ($event['reg_start'] || $event['reg_end']): ?>
        <div><span class="mono">Registrasi</span><span><?= e(date_id($event['reg_start'])) ?> – <?= e(date_id($event['reg_end'])) ?></span></div>
        <?php endif; ?>
        <div><span class="mono"><?= e($event['att_start']) ?></span><span>Absensi QR dibuka di lokasi</span></div>
        <div><span class="mono"><?= e($event['start_time']) ?></span><span>Acara dimulai</span></div>
        <div><span class="mono"><?= e($event['att_end']) ?></span><span>Absensi QR ditutup</span></div>
        <div><span class="mono"><?= e($event['end_time']) ?></span><span>Acara selesai</span></div>
      </div>
    </div>
    <div class="card">
      <div class="card-h"><h3>Penting</h3></div>
      <div class="perm">
        <div><span class="y"><?= icon('check', 18) ?></span>Satu nomor identitas hanya bisa mendaftar satu kali.</div>
        <div><span class="y"><?= icon('check', 18) ?></span>Peserta tidak perlu membuat akun atau login.</div>
        <div><span class="y"><?= icon('check', 18) ?></span>Status "Belum diverifikasi" berubah menjadi Hadir setelah QR dipindai.</div>
        <div><span class="n"><?= icon('x', 18) ?></span>Jangan bagikan QR Anda; QR hanya dapat dipakai sekali.</div>
      </div>
    </div>
  </div>
  <?php if ($open): ?>
  <div class="sticky-cta only-narrow"><a class="btn btn-primary lg block" href="<?= e(url('e/' . $event['slug'] . '/daftar')) ?>">Daftar sekarang</a></div>
  <?php endif; ?>
</div>
