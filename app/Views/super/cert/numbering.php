<?php
use App\Core\View;
use App\Models\CertNumbering;

$eid = (int) $event['id'];
$f = $old ?? ['pattern' => '', 'seq_padding' => 4, 'seq_scope' => 'event', 'seq_reset' => 'never', 'note' => ''];
$cls = static fn (string $k) => 'fld' . (isset($formErrors[$k]) ? ' is-err' : '');
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<div class="banner warn"><?= icon('alert') ?><div class="grow"><b><?= e(t('Format resmi harus dari fakultas/TU')) ?></b><?= e(t('Sistem tidak mengarang nomor surat resmi. Pola baru selalu tersimpan sebagai Draft dan baru dipakai untuk sertifikat final setelah disetujui Super Admin. Contoh pola di bawah hanya ilustrasi.')) ?></div></div>
<div class="g-2">
  <form method="post" action="<?= e(url('admin/sertifikat/nomor/baru')) ?>" class="card" novalidate>
    <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>">
    <div class="card-h"><h3><?= e($versions ? t('Buat versi pola baru') : t('Buat pola nomor')) ?></h3></div>
    <div class="<?= $cls('pattern') ?>"><label for="pattern"><?= e(t('Pola nomor')) ?><span class="req">*</span></label><input class="inp mono" id="pattern" name="pattern" maxlength="190" value="<?= e($f['pattern']) ?>" placeholder="SERT/{EVENT_CODE}/{SEQUENCE_PADDED}/{ROMAN_MONTH}/{YEAR}"><?= field_error($formErrors, 'pattern') ?>
      <p class="hint"><?= e(t('Contoh ilustrasi: {FACULTY_CODE}/{STUDY_PROGRAM_CODE}/{EVENT_CODE}/{NUMBER}/{ROMAN_MONTH}/{YEAR}')) ?></p></div>
    <div class="g-form two">
      <div class="<?= $cls('seq_padding') ?>"><label for="seq_padding"><?= e(t('Panjang nomor urut')) ?></label><input class="inp mono" id="seq_padding" name="seq_padding" type="number" min="1" max="8" value="<?= (int) $f['seq_padding'] ?>"><?= field_error($formErrors, 'seq_padding') ?></div>
      <div class="<?= $cls('seq_scope') ?>"><label for="seq_scope"><?= e(t('Cakupan nomor urut')) ?></label><select class="inp" id="seq_scope" name="seq_scope"><?php foreach (CertNumbering::SCOPES as $k => $label): if ($k === 'global' && !is_super() && $f['seq_scope'] !== 'global') { continue; } ?><option value="<?= e($k) ?>"<?= selected($f['seq_scope'], $k) ?>><?= e(t($label)) ?></option><?php endforeach; ?></select><?= field_error($formErrors, 'seq_scope') ?></div>
      <div class="<?= $cls('seq_reset') ?>"><label for="seq_reset"><?= e(t('Reset nomor urut')) ?></label><select class="inp" id="seq_reset" name="seq_reset"><?php foreach (CertNumbering::RESETS as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($f['seq_reset'], $k) ?>><?= e(t($label)) ?></option><?php endforeach; ?></select><?= field_error($formErrors, 'seq_reset') ?></div>
      <div class="<?= $cls('note') ?>"><label for="note"><?= e(t('Catatan / dasar format')) ?></label><input class="inp" id="note" name="note" maxlength="255" value="<?= e($f['note'] ?? '') ?>" placeholder="<?= e(t('mis. Sesuai surat TU No. ...')) ?>"><?= field_error($formErrors, 'note') ?></div>
    </div>
    <p class="small muted"><?= e(t('Tanggal pada nomor memakai tanggal terbit dan zona waktu :tz. Nomor yang sudah terbit tidak berubah bila pola diganti, dan nomor yang dicabut tidak dipakai ulang.', ['tz' => config('timezone')])) ?></p>
    <div><button class="btn btn-primary" type="submit"><?= e(t('Simpan sebagai Draft')) ?></button></div>
  </form>
  <div class="card">
    <div class="card-h"><h3><?= e(t('Token yang tersedia')) ?></h3></div>
    <div class="vars-grid">
      <?php foreach (['SEQUENCE' => 'Nomor urut (1, 2, 3)', 'SEQUENCE_PADDED' => 'Nomor urut dengan nol (0001)', 'NUMBER' => 'Sama dengan SEQUENCE_PADDED', 'DAY' => 'Tanggal (17)', 'MONTH' => 'Bulan (10)', 'MONTH_NAME' => 'Nama bulan (Oktober)', 'ROMAN_MONTH' => 'Bulan Romawi (X)', 'YEAR' => 'Tahun (2026)', 'EVENT_ID' => 'ID event', 'EVENT_CODE' => 'Kode event (variabel)', 'FACULTY_CODE' => 'Kode fakultas (variabel)', 'STUDY_PROGRAM_CODE' => 'Kode prodi (variabel)', 'FACULTY_LETTER_CODE' => 'Kode surat resmi (variabel)'] as $k => $label): ?>
        <div><code>{<?= e($k) ?>}</code><span class="small muted"><?= e(t($label)) ?></span></div>
      <?php endforeach; ?>
    </div>
    <p class="small muted"><?= e(t('Semua variabel kustom juga dapat dipakai. Pola wajib memuat nomor urut.')) ?></p>
    <p class="small muted"><?= e(t('Persetujuan nomor diwajibkan untuk event ini: :v.', ['v' => (int) $settings['require_approved_number'] ? t('Ya') : t('Tidak')])) ?></p>
  </div>
</div>
<div class="card">
  <div class="card-h"><h3><?= e(t('Versi pola nomor')) ?></h3><span class="sub"><?= e(t(':n sertifikat sudah memakai nomor di event ini', ['n' => $used])) ?></span></div>
  <?php if (!$versions): ?><p class="small muted"><?= e(t('Belum ada pola nomor.')) ?></p><?php else: ?>
  <div class="tblw"><table class="tbl">
    <thead><tr><th><?= e(t('Versi')) ?></th><th><?= e(t('Pola')) ?></th><th><?= e(t('Contoh nomor berikutnya')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Dibuat')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($versions as $n): [$label, $tone] = CertNumbering::STATUSES[$n['status']]; ?>
      <tr>
        <td class="num">v<?= (int) $n['version'] ?></td>
        <td class="mono" style="white-space:normal"><?= e($n['pattern']) ?><br><span class="small muted"><?= e(t(CertNumbering::SCOPES[$n['seq_scope']])) ?> · <?= e(t(CertNumbering::RESETS[$n['seq_reset']])) ?> · <?= e(t(':n digit', ['n' => (int) $n['seq_padding']])) ?></span><?php if ($n['note']): ?><br><span class="small"><?= e($n['note']) ?></span><?php endif; ?></td>
        <td style="white-space:normal"><?php if (isset($examples[$n['id']])): [$ex, $errs] = $examples[$n['id']]; ?><span class="mono"><?= e($ex) ?></span> <span class="bdg bdg-warn"><?= e(t('CONTOH')) ?></span><?php foreach ($errs as $er): ?><br><span class="small" style="color:var(--bad-ink)"><?= e($er) ?></span><?php endforeach; ?><?php endif; ?></td>
        <td><span class="bdg bdg-<?= $tone ?>"><i></i><?= e(t($label)) ?></span><?php if ($n['approved_by_name']): ?><br><span class="small muted">@<?= e($n['approved_by_name']) ?> · <?= e(datetime_id($n['approved_at'])) ?></span><?php endif; ?></td>
        <td class="small"><?= e(datetime_id($n['created_at'])) ?><br>@<?= e($n['created_by_name'] ?? '—') ?></td>
        <td><?php if ($n['status'] === 'draft' && is_super()): ?><?= post_button('admin/sertifikat/nomor/' . $n['id'] . '/setujui', t('Setujui'), [], 'btn btn-primary sm', t('Setujui pola ":p" sebagai format nomor resmi event ini? Pastikan format sudah dikonfirmasi fakultas/TU.', ['p' => $n['pattern']]), 'check') ?><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
