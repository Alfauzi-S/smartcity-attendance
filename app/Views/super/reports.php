<?php
use App\Core\View;
use App\Models\Participant;

$opts = ['all' => 'Semua peserta', 'hadir' => 'Hadir', 'belum' => 'Belum diverifikasi', 'tidak' => 'Tidak Hadir'];
?>
<?php if (!$event): ?>
  <?= View::partial('no_event', ['heading' => 'Belum ada event', 'text' => 'Laporan muncul setelah ada event dan peserta.', 'cta' => ['admin/event/baru', 'Buat event']]) ?>
<?php else: ?>
  <div class="pagehead"><div><h2>Laporan absensi</h2><p><?= e($event['name']) ?> · <?= e(date_id($event['event_date'], true)) ?></p></div></div>
  <?= View::partial('event_picker', ['events' => $events, 'event' => $event, 'path' => 'admin/laporan']) ?>
  <?= View::partial('stats', ['stats' => $stats]) ?>
  <div class="g-2">
    <div class="card">
      <div class="card-h"><h3>Ringkasan per status</h3></div>
      <div class="tblw">
        <table class="tbl">
          <thead><tr><th>Status</th><th class="r">Jumlah</th><th class="r">Persentase</th></tr></thead>
          <tbody>
            <?php foreach (['hadir', 'belum', 'tidak'] as $k): ?>
              <tr><td><?= status_badge($k) ?></td><td class="r num"><?= (int) $stats[$k] ?></td><td class="r num"><?= e(pct((int) $stats[$k], (int) $stats['total'])) ?></td></tr>
            <?php endforeach; ?>
            <tr><td><b>Total</b></td><td class="r num"><b><?= (int) $stats['total'] ?></b></td><td class="r num">100%</td></tr>
          </tbody>
        </table>
      </div>
      <?= View::partial('progress', ['stats' => $stats]) ?>
    </div>
    <form method="get" action="<?= e(url('admin/ekspor')) ?>" class="card">
      <div class="card-h"><h3>Ekspor rekap</h3></div>
      <input type="hidden" name="ev" value="<?= (int) $event['id'] ?>">
      <div class="fld"><label for="st">Data yang diekspor</label>
        <select class="inp" id="st" name="st" data-report-filter="<?= e(url('admin/laporan')) ?>"><?php foreach ($opts as $k => $label): ?><option value="<?= $k ?>"<?= selected($st, $k) ?>><?= e($label) ?></option><?php endforeach; ?></select>
        <p class="hint"><?= (int) $rowCount ?> baris akan diekspor.</p>
      </div>
      <div class="stack" style="gap:8px">
        <button class="btn btn-primary block" type="submit" name="format" value="xlsx"<?= $xlsx ? '' : ' disabled' ?>><?= icon('download', 18) ?>Unduh Excel (.xlsx)</button>
        <button class="btn btn-outline block" type="submit" name="format" value="csv"><?= icon('file', 18) ?>Unduh CSV</button>
      </div>
      <?php if (!$xlsx): ?><p class="hint small" style="color:var(--warn-ink)">Ekspor Excel butuh ekstensi PHP zip di server. CSV tetap bisa dibuka di Excel.</p><?php endif; ?>
      <p class="hint small muted">Kolom: No, Nomor Identitas, Nama Lengkap, Profesi, WhatsApp/HP, Institusi, Program Studi, Status, Waktu Hadir, Metode, Kode Peserta, Terdaftar.</p>
    </form>
  </div>
  <div class="card">
    <div class="card-h"><h3>Pratinjau</h3><span class="sub">8 baris pertama · <?= e($opts[$st] ?? 'Semua') ?></span></div>
    <?php if (!$preview): ?><p class="muted small">Tidak ada data untuk filter ini.</p><?php else: ?>
    <div class="tblw">
      <table class="tbl">
        <thead><tr><th>Nomor identitas</th><th>Nama lengkap</th><th>Program studi</th><th>Status</th><th>Waktu hadir</th><th>Metode</th></tr></thead>
        <tbody><?php foreach ($preview as $p): ?>
          <tr><td class="mono"><?= e($p['identity_number']) ?></td><td><?= e($p['full_name']) ?></td><td><?= e($p['field_of_study']) ?></td><td><?= status_badge($p['status']) ?></td><td class="mono"><?= $p['status'] === 'hadir' ? e(time_only($p['checked_in_at'])) : '—' ?></td><td><?= e($p['status'] === 'belum' ? '—' : Participant::methodLabel($p['method'])) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
