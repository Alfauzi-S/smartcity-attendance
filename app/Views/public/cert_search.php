<?php use App\Controllers\CertPublicController; ?>
<div class="pad narrow stack" style="gap:16px">
  <div><h1 style="font-size:26px"><?= e(t('Cari Sertifikat')) ?></h1><p class="muted"><?= e(t('Pilih event, tulis nama lengkap Anda, lalu unduh sertifikat yang sudah diterbitkan panitia.')) ?></p></div>
  <?php if (!$events): ?>
    <div class="card"><div class="empty"><span class="eic"><?= icon('award', 26) ?></span><h3><?= e(t('Belum ada sertifikat yang diterbitkan')) ?></h3><p><?= e(t('Sertifikat akan tersedia di sini setelah panitia memublikasikannya.')) ?></p></div></div>
  <?php else: ?>
  <form method="get" action="<?= e(url('sertifikat')) ?>" class="card" role="search">
    <div class="fld"><label for="event"><?= e(t('Nama event')) ?></label>
      <select class="inp" id="event" name="event" required>
        <option value=""><?= e(t('Pilih event')) ?></option>
        <?php foreach ($events as $ev): ?><option value="<?= (int) $ev['id'] ?>"<?= selected($eventId, $ev['id']) ?>><?= e($ev['name']) ?> · <?= e(date_id($ev['event_date'])) ?></option><?php endforeach; ?>
      </select></div>
    <div class="fld"><label for="nama"><?= e(t('Nama lengkap')) ?></label><input class="inp" id="nama" name="nama" minlength="3" maxlength="120" required value="<?= e($name) ?>" autocomplete="name"></div>
    <?php if ($error): ?><div class="banner bad" role="alert"><?= icon('alert') ?><div class="grow"><?= e($error) ?></div></div><?php endif; ?>
    <button class="btn btn-primary block" type="submit"><?= icon('search', 18) ?><?= e(t('Cari sertifikat')) ?></button>
  </form>
  <?php endif; ?>

  <?php if ($tooMany): ?>
    <div class="banner warn" role="status"><?= icon('info') ?><div class="grow"><b><?= e(t('Terlalu banyak nama yang cocok')) ?></b><?= e(t('Tulis nama lengkap Anda agar hasil lebih spesifik.')) ?></div></div>
  <?php elseif (is_array($results) && !$results): ?>
    <div class="card"><div class="empty"><span class="eic"><?= icon('search', 26) ?></span><h3><?= e(t('Sertifikat tidak ditemukan')) ?></h3><p><?= e(t('Periksa ejaan nama atau pilih event lain. Bila Anda yakin terdaftar dan hadir, hubungi panitia: sertifikat mungkin belum dipublikasikan.')) ?></p></div></div>
  <?php elseif ($results): ?>
    <p class="small muted"><?= e(t(':n hasil ditemukan. Untuk melindungi data Anda, unduhan memerlukan konfirmasi.', ['n' => count($results)])) ?></p>
    <?php foreach ($results as $r): $err = is_array($flashError) && (int) $flashError[0] === (int) $r['id'] ? $flashError[1] : ''; ?>
      <form method="post" action="<?= e(url('sertifikat/unduh')) ?>" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="ref" value="<?= (int) $r['id'] ?>"><input type="hidden" name="event" value="<?= (int) $eventId ?>"><input type="hidden" name="nama" value="<?= e($name) ?>">
        <div class="idcard"><span class="av lg"><?= e(initials($r['full_name'])) ?></span><div><b><?= e($r['full_name']) ?></b><span><?= e(CertPublicController::maskInstitution((string) $r['institution'])) ?></span></div></div>
        <div class="fld<?= $err ? ' is-err' : '' ?>"><label for="c<?= (int) $r['id'] ?>"><?= e(t('Konfirmasi kepemilikan')) ?><span class="en"><?= e(t('4 karakter terakhir NIM/NIK, atau kode tiket QR (SC…)')) ?></span></label>
          <input class="inp mono" id="c<?= (int) $r['id'] ?>" name="check" maxlength="12" required autocomplete="off" autocapitalize="characters">
          <?php if ($err): ?><p class="msg"><?= icon('alert', 16) ?><span><?= e($err) ?></span></p><?php endif; ?></div>
        <button class="btn btn-primary" type="submit"><?= icon('download', 18) ?><?= e(t('Unduh sertifikat (PDF)')) ?></button>
      </form>
    <?php endforeach; ?>
  <?php elseif (is_array($flashError)): ?>
    <div class="banner bad" role="alert"><?= icon('alert') ?><div class="grow"><?= e($flashError[1]) ?></div></div>
  <?php endif; ?>
  <p class="small muted"><?= e(t('Keaslian sertifikat dapat diperiksa dengan memindai QR verifikasi yang tercetak pada sertifikat.')) ?></p>
</div>
