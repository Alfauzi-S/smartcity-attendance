<?php
use App\Core\View;
use App\Services\Cert\Issuer;

$eid = (int) $event['id'];
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<form method="post" action="<?= e(url('admin/sertifikat/kelayakan')) ?>" class="card">
  <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>">
  <div class="card-h"><h3><?= e(t('Kebijakan kelayakan')) ?></h3></div>
  <div class="opts">
    <?php foreach (Issuer::ELIGIBILITY as $k => $label): $dis = $k === 'semua' && !is_super() && $settings['eligibility'] !== 'semua'; ?>
      <label class="opt<?= $dis ? ' dis' : '' ?>"><input type="radio" name="eligibility" value="<?= e($k) ?>"<?= checked($settings['eligibility'], $k) ?><?= $dis ? ' disabled' : '' ?>><span class="rd"></span><span class="ot"><b><?= e(t($label)) ?></b></span></label>
    <?php endforeach; ?>
  </div>
  <p class="small muted"><?= e(t('Event memakai satu sesi absensi, sehingga persentase kehadiran minimum belum dapat dihitung. Peserta berstatus Tidak Hadir tidak pernah dimasukkan otomatis kecuali mode seluruh peserta dipilih Super Admin.')) ?></p>
  <div class="g-form two">
    <div class="fld"><label for="issue_date"><?= e(t('Tanggal terbit')) ?><span class="en"><?= e(t('Kosongkan untuk memakai tanggal event')) ?></span></label><input class="inp" id="issue_date" name="issue_date" type="date" value="<?= e($settings['issue_date'] ?? '') ?>"></div>
    <?php if (is_super()): ?>
    <div class="fld"><span class="lbl"><?= e(t('Persetujuan nomor')) ?></span><label class="chk"><input type="checkbox" name="require_approved_number" value="1"<?= (int) $settings['require_approved_number'] ? ' checked' : '' ?>><span><?= e(t('Wajibkan pola nomor disetujui sebelum generate dan publikasi final')) ?></span></label></div>
    <?php endif; ?>
  </div>
  <div><button class="btn btn-primary" type="submit"><?= e(t('Simpan kebijakan')) ?></button></div>
</form>
<?= View::fetch('super/cert/_summary', ['summary' => $summary]) ?>
<form method="get" action="<?= e(url('admin/sertifikat/kelayakan')) ?>" class="filters" role="search">
  <input type="hidden" name="ev" value="<?= $eid ?>">
  <div class="inp-ic search"><?= icon('search', 18) ?><input class="inp" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Cari nama atau nomor identitas')) ?>" aria-label="<?= e(t('Cari peserta')) ?>"></div>
  <div class="frow">
    <select class="inp" name="f" aria-label="<?= e(t('Filter')) ?>" data-autosubmit-field>
      <?php foreach (['belum' => 'Layak, belum punya sertifikat', 'layak' => 'Semua yang layak', 'tidak' => 'Tidak memenuhi syarat', 'semua' => 'Semua peserta'] as $k => $label): ?><option value="<?= $k ?>"<?= selected($filter, $k) ?>><?= e(t($label)) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit"><?= e(t('Cari')) ?></button>
  </div>
</form>
<form method="post" action="<?= e(url('admin/sertifikat/generate')) ?>" class="card" id="pick-form" data-confirm="<?= e(t('Generate sertifikat untuk peserta yang dipilih? Nomor final akan dialokasikan.')) ?>">
  <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>"><input type="hidden" name="mode" value="selected">
  <div class="card-h"><h3><?= e(t('Daftar penerima')) ?></h3><span class="sub"><?= e(t(':n peserta', ['n' => $count])) ?></span></div>
  <?php if (!$rows): ?><p class="small muted"><?= e(t('Tidak ada peserta pada filter ini.')) ?></p><?php else: ?>
  <div class="tblw"><table class="tbl">
    <thead><tr><th><label class="chk" style="min-height:0"><input type="checkbox" data-check-all aria-label="<?= e(t('Pilih semua')) ?>"></label></th><th><?= e(t('Nama')) ?></th><th><?= e(t('Kehadiran')) ?></th><th><?= e(t('Kelayakan')) ?></th><th><?= e(t('Sertifikat')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $canPick = !$r['reason'] && !$r['cert_id']; ?>
      <tr>
        <td><?php if ($canPick): ?><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" aria-label="<?= e($r['full_name']) ?>" style="width:18px;height:18px"><?php endif; ?></td>
        <td style="white-space:normal"><b><?= e($r['full_name']) ?></b><br><span class="small muted mono"><?= e($r['identity_number']) ?></span></td>
        <td><?= status_badge($r['status']) ?></td>
        <td><?= $r['reason'] ? '<span class="bdg bdg-bad">' . e(t($r['reason'])) . '</span>' : '<span class="bdg bdg-ok">' . e(t('Layak')) . '</span>' ?></td>
        <td><?= $r['cert_id'] ? '<a href="' . e(url('admin/sertifikat/' . $r['cert_id'])) . '">' . \App\Models\CertIssuance::badge($r['cert_status']) . '</a>' : '<span class="muted small">—</span>' ?></td>
        <td class="r"><?php if ($canPick && $ready): ?><button class="btn btn-outline sm" type="submit" form="one-<?= (int) $r['id'] ?>"><?= e(t('Generate')) ?></button><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="row"><button class="btn btn-primary" type="submit"<?= $ready ? '' : ' disabled' ?>><?= icon('award', 18) ?><?= e(t('Generate yang dipilih')) ?></button><?php if (!$ready): ?><span class="small" style="color:var(--bad-ink)"><?= e(t('Konfigurasi belum lengkap. Lihat Dashboard Sertifikat.')) ?></span><?php endif; ?></div>
  <?php endif; ?>
</form>
<?php foreach ($rows as $r): if (!$r['reason'] && !$r['cert_id'] && $ready): ?>
  <form method="post" action="<?= e(url('admin/sertifikat/peserta/' . $r['id'] . '/generate')) ?>" id="one-<?= (int) $r['id'] ?>" hidden data-confirm="<?= e(t('Generate sertifikat untuk :name?', ['name' => $r['full_name']])) ?>"><?= csrf_field() ?></form>
<?php endif; endforeach; ?>
<?= View::partial('pager', ['page' => $pg, 'pages' => $pages, 'total' => $count, 'path' => 'admin/sertifikat/kelayakan', 'query' => ['ev' => $eid, 'f' => $filter, 'q' => $q]]) ?>
