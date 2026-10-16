<section class="hero">
  <div class="in">
    <span class="bdg bdg-info" style="justify-self:start">Smart City · Smart Campus</span>
    <h1>Daftar event, simpan QR, hadir tanpa antre.</h1>
    <p>Pilih event di bawah untuk melihat jadwal dan melakukan registrasi. QR Code dikirim langsung di halaman setelah Anda mendaftar.</p>
    <img class="art" src="<?= e(asset('img/city.svg')) ?>" alt="">
  </div>
</section>
<div class="pad">
  <h2 style="font-size:20px">Event yang sedang dibuka</h2>
  <?php if (!$events): ?>
    <div class="card"><div class="empty"><span class="eic"><?= icon('calendar', 26) ?></span><h3>Belum ada event aktif</h3><p>Penyelenggara belum mempublikasikan event. Silakan cek kembali nanti.</p></div></div>
  <?php else: ?>
    <div class="g-ev">
      <?php foreach ($events as $ev): ?>
        <a class="evc" href="<?= e(url('e/' . $ev['slug'])) ?>">
          <div class="row"><?= reg_badge($ev['reg_status']) ?></div>
          <h3><?= e($ev['name']) ?></h3>
          <div class="em">
            <span><?= icon('calendar', 16) ?><?= e(date_id($ev['event_date'], true)) ?> · <?= e($ev['start_time']) ?>–<?= e($ev['end_time']) ?> <?= e(config('timezone_label', 'WITA')) ?></span>
            <span><?= icon('pin', 16) ?><?= e($ev['location']) ?></span>
          </div>
          <div class="ef"><span class="muted"><?= e($ev['organizer']) ?></span><span class="row" style="gap:4px;color:var(--cyan-ink);font-weight:600">Lihat event <?= icon('arrow', 16) ?></span></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
