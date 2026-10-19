<?php
use App\Core\View;
use App\Models\Participant;

$opts = ['all' => t('Semua peserta'), 'hadir' => t('Hadir'), 'belum' => t('Belum diverifikasi'), 'tidak' => t('Tidak Hadir')];
?>
<?php if (!$event): ?>
  <?= View::partial('no_event', ['heading' => t('Belum ada event'), 'text' => t('Laporan muncul setelah ada event dan peserta.'), 'cta' => ['admin/event/baru', t('Buat event')]]) ?>
<?php else: ?>
  <div class="pagehead"><div><h2><?= e(t('Laporan presensi')) ?></h2><p><?= e($event['name']) ?> · <?= e(date_id($event['event_date'], true)) ?></p></div></div>
  <?= View::partial('event_picker', ['events' => $events, 'event' => $event, 'path' => 'admin/laporan']) ?>
  <?= View::partial('stats', ['stats' => $stats]) ?>
  <div class="g-2">
    <div class="card">
      <div class="card-h"><h3><?= e(t('Ringkasan per status')) ?></h3></div>
      <div class="tblw">
        <table class="tbl">
          <thead><tr><th><?= e(t('Status')) ?></th><th class="r"><?= e(t('Jumlah')) ?></th><th class="r"><?= e(t('Persentase')) ?></th></tr></thead>
          <tbody>
            <?php foreach (['hadir', 'belum', 'tidak'] as $k): ?>
              <tr><td><?= status_badge($k) ?></td><td class="r num"><?= (int) $stats[$k] ?></td><td class="r num"><?= e(pct((int) $stats[$k], (int) $stats['total'])) ?></td></tr>
            <?php endforeach; ?>
            <tr><td><b><?= e(t('Total')) ?></b></td><td class="r num"><b><?= (int) $stats['total'] ?></b></td><td class="r num">100%</td></tr>
          </tbody>
        </table>
      </div>
      <?= View::partial('progress', ['stats' => $stats]) ?>
    </div>
    <form method="get" action="<?= e(url('admin/ekspor')) ?>" class="card">
      <div class="card-h"><h3><?= e(t('Ekspor rekap')) ?></h3></div>
      <input type="hidden" name="ev" value="<?= (int) $event['id'] ?>">
      <div class="fld"><label for="st"><?= e(t('Data yang diekspor')) ?></label>
        <select class="inp" id="st" name="st" data-report-filter="<?= e(url('admin/laporan')) ?>"><?php foreach ($opts as $k => $label): ?><option value="<?= $k ?>"<?= selected($st, $k) ?>><?= e($label) ?></option><?php endforeach; ?></select>
        <p class="hint"><?= e(t(':n baris akan diekspor.', ['n' => (int) $rowCount])) ?></p>
      </div>
      <div class="stack" style="gap:8px">
        <button class="btn btn-primary block" type="submit" name="format" value="xlsx"<?= $xlsx ? '' : ' disabled' ?>><?= icon('download', 18) ?><?= e(t('Unduh Excel (.xlsx)')) ?></button>
        <button class="btn btn-outline block" type="submit" name="format" value="csv"><?= icon('file', 18) ?><?= e(t('Unduh CSV')) ?></button>
      </div>
      <?php if (!$xlsx): ?><p class="hint small" style="color:var(--warn-ink)"><?= e(t('Ekspor Excel butuh ekstensi PHP zip di server. CSV tetap bisa dibuka di Excel.')) ?></p><?php endif; ?>
      <p class="hint small muted"><?= e(t('Kolom: No, Nomor Identitas, Nama Lengkap, Profesi, WhatsApp/HP, Institusi, Program Studi, Status, Waktu Hadir, Metode, Kode Peserta, Terdaftar.')) ?></p>
    </form>
  </div>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Pratinjau')) ?></h3><span class="sub"><?= e(t('8 baris pertama')) ?> · <?= e($opts[$st] ?? t('Semua')) ?></span></div>
    <?php if (!$preview): ?><p class="muted small"><?= e(t('Tidak ada data untuk filter ini.')) ?></p><?php else: ?>
    <div class="tblw">
      <table class="tbl">
        <thead><tr><th><?= e(t('Nomor identitas')) ?></th><th><?= e(t('Nama lengkap')) ?></th><th><?= e(t('Program studi')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Waktu hadir')) ?></th><th><?= e(t('Metode')) ?></th></tr></thead>
        <tbody><?php foreach ($preview as $p): ?>
          <tr><td class="mono"><?= e($p['identity_number']) ?></td><td><?= e($p['full_name']) ?></td><td><?= e(t($p['field_of_study'])) ?></td><td><?= status_badge($p['status']) ?></td><td class="mono"><?= $p['status'] === 'hadir' ? e(time_only($p['checked_in_at'])) : '—' ?></td><td><?= e($p['status'] === 'belum' ? '—' : t(Participant::methodLabel($p['method']))) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
