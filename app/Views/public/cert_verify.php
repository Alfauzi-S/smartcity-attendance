<div class="pad narrow stack" style="gap:16px">
  <h1 style="font-size:26px"><?= e(t('Verifikasi Sertifikat')) ?></h1>
  <?php if (!$i): ?>
    <div class="card"><div class="state s-warn"><span class="sic"><?= icon('alert', 30) ?></span><h3><?= e(t('Sertifikat tidak ditemukan')) ?></h3><p><?= e(t('Kode verifikasi ini tidak terdaftar sebagai sertifikat yang diterbitkan. Sertifikat mungkin palsu, belum dipublikasikan, atau tautannya salah.')) ?></p></div></div>
  <?php elseif ($i['status'] === 'revoked'): ?>
    <div class="card"><div class="state s-bad"><span class="sic"><?= icon('x', 30) ?></span><span class="bdg bdg-bad lg"><?= e(t('TIDAK VALID · DICABUT')) ?></span><h3><?= e(t('Sertifikat ini telah dicabut')) ?></h3><p><?= e(t('Dicabut pada :date. Sertifikat ini tidak lagi berlaku.', ['date' => date_id($i['revoked_at'])])) ?></p></div>
      <dl class="kv">
        <div><dt><?= e(t('Nomor sertifikat')) ?></dt><dd class="mono"><?= cert_no($i['cert_number']) ?></dd></div>
        <div><dt><?= e(t('Nama event')) ?></dt><dd><?= e($event['name'] ?? '—') ?></dd></div>
      </dl></div>
  <?php else: ?>
    <div class="card"><div class="state s-ok"><span class="sic"><?= icon('check', 30) ?></span><span class="bdg bdg-ok lg"><?= e(t('VALID')) ?></span><h3><?= e(t('Sertifikat asli dan berlaku')) ?></h3><p><?= e(t('Data di bawah sesuai catatan penerbitan resmi.')) ?></p></div>
      <dl class="kv">
        <div><dt><?= e(t('Nama penerima')) ?></dt><dd><b><?= e($i['full_name']) ?></b></dd></div>
        <div><dt><?= e(t('Nama event')) ?></dt><dd><?= e($event['name'] ?? '—') ?></dd></div>
        <div><dt><?= e(t('Tanggal event')) ?></dt><dd><?= e(date_id($event['event_date'] ?? null)) ?></dd></div>
        <div><dt><?= e(t('Nomor sertifikat')) ?></dt><dd class="mono"><?= cert_no($i['cert_number']) ?></dd></div>
        <div><dt><?= e(t('Tanggal terbit')) ?></dt><dd><?= e(date_id($i['issue_date'])) ?></dd></div>
      </dl></div>
  <?php endif; ?>
  <a class="btn btn-ghost" href="<?= e(url('sertifikat')) ?>"><?= icon('search', 18) ?><?= e(t('Cari sertifikat lain')) ?></a>
</div>
