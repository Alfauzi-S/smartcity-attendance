<section class="hero">
  <div class="in">
    <span class="bdg bdg-info" style="justify-self:start">Smart City · Smart Campus</span>
    <h1><?= e(t('Daftar event, simpan QR, hadir tanpa antre.')) ?></h1>
    <p><?= e(t('Pilih event di bawah untuk melihat jadwal dan melakukan registrasi. QR Code dikirim langsung di halaman setelah Anda mendaftar.')) ?></p>
    <div class="row"><a class="btn btn-ondark sm" href="<?= e(url('sertifikat')) ?>"><?= icon('award', 16) ?><?= e(t('Cari sertifikat Anda')) ?></a></div>
    <img class="art" src="<?= e(asset('img/city.svg')) ?>" alt="">
  </div>
</section>
<div class="pad">
  <h2 style="font-size:20px"><?= e(t('Event yang sedang dibuka')) ?></h2>
  <?php if (!$events): ?>
    <div class="card"><div class="empty"><span class="eic"><?= icon('calendar', 26) ?></span><h3><?= e(t('Belum ada event aktif')) ?></h3><p><?= e(t('Penyelenggara belum mempublikasikan event. Silakan cek kembali nanti.')) ?></p></div></div>
  <?php else: ?>
    <div class="g-ev">
      <?php foreach ($events as $ev): ?>
        <a class="evc" href="<?= e(url('e/' . $ev['slug'])) ?>">
          <div class="row"><?= reg_badge(\App\Models\Event::registrationState($ev)[0] ? 'Dibuka' : 'Ditutup') ?></div>
          <h3><?= e($ev['name']) ?></h3>
          <div class="em">
            <span><?= icon('calendar', 16) ?><?= e(date_id($ev['event_date'], true)) ?> · <?= e(event_time($ev)) ?> <?= e(config('timezone_label', 'WITA')) ?></span>
            <span><?= icon('pin', 16) ?><?= e($ev['location']) ?></span>
          </div>
          <div class="ef"><span class="muted"><?= e($ev['organizer']) ?></span><span class="row" style="gap:4px;color:var(--cyan-ink);font-weight:600"><?= e(t('Lihat event')) ?> <?= icon('arrow', 16) ?></span></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
