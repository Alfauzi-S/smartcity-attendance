<?php
use App\Core\View;
use App\Models\CertIssuance;

$eid = (int) $event['id'];
$canPublish = can('cert_publish', $eid);
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<div class="chips">
  <?php foreach (['' => t('Semua') . ' (' . array_sum($stats) . ')', 'generated' => t('Generated') . ' (' . $stats['generated'] . ')', 'published' => t('Published') . ' (' . $stats['published'] . ')', 'revoked' => t('Revoked') . ' (' . $stats['revoked'] . ')'] as $k => $label): ?>
    <a class="chip<?= $status === $k ? ' on' : '' ?>" href="<?= e(url('admin/sertifikat/terbit', ['ev' => $eid, 'st' => $k, 'q' => $q])) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>
<form method="get" action="<?= e(url('admin/sertifikat/terbit')) ?>" class="filters" role="search">
  <input type="hidden" name="ev" value="<?= $eid ?>"><input type="hidden" name="st" value="<?= e($status) ?>">
  <div class="inp-ic search"><?= icon('search', 18) ?><input class="inp" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Cari nama atau nomor sertifikat')) ?>" aria-label="<?= e(t('Cari sertifikat')) ?>"></div>
  <div class="frow"><button class="btn btn-primary" type="submit"><?= e(t('Cari')) ?></button>
    <a class="btn btn-outline" href="<?= e(url('admin/sertifikat/zip', ['ev' => $eid, 'st' => in_array($status, ['generated', 'published'], true) ? $status : ''])) ?>"><?= icon('download', 16) ?><?= e(t('Unduh ZIP')) ?></a></div>
</form>
<?php if ($canPublish && $stats['generated'] > 0): ?>
  <div class="banner info"><?= icon('info') ?><div class="grow"><b><?= e(t(':n sertifikat menunggu publikasi', ['n' => $stats['generated']])) ?></b><?= e(t('Periksa beberapa PDF terlebih dahulu. Sertifikat Generated belum dapat dicari atau diunduh peserta.')) ?></div>
    <?= post_button('admin/sertifikat/terbit/publikasi', t('Publikasikan semua'), ['event_id' => $eid, 'mode' => 'all'], 'btn btn-primary sm', t('Publikasikan :n sertifikat? Peserta akan dapat mencari dan mengunduhnya.', ['n' => $stats['generated']]), 'check') ?></div>
<?php endif; ?>
<?php if ($canPublish && (int) $settings['require_approved_number'] === 1): ?><p class="small muted"><?= e(t('Publikasi hanya untuk sertifikat dengan pola nomor yang sudah disetujui.')) ?></p><?php endif; ?>
<?php if (!$items): ?>
  <div class="card"><div class="empty"><span class="eic"><?= icon('award', 26) ?></span><h3><?= e(t('Belum ada sertifikat')) ?></h3><p><?= e(t('Sertifikat muncul di sini setelah proses generate.')) ?></p></div></div>
<?php else: ?>
<form method="post" action="<?= e(url('admin/sertifikat/terbit/publikasi')) ?>" id="sel-form" data-confirm="<?= e(t('Publikasikan sertifikat yang dipilih?')) ?>">
  <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>"><input type="hidden" name="mode" value="selected">
  <div class="tblw"><table class="tbl">
    <thead><tr><th><input type="checkbox" data-check-all aria-label="<?= e(t('Pilih semua')) ?>" style="width:18px;height:18px"></th><th><?= e(t('Peserta')) ?></th><th><?= e(t('Nomor sertifikat')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Dibuat')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
      <tr>
        <td><?php if ($i['status'] !== 'revoked'): ?><input type="checkbox" name="ids[]" value="<?= (int) $i['id'] ?>" aria-label="<?= e($i['full_name']) ?>" style="width:18px;height:18px"><?php endif; ?></td>
        <td style="white-space:normal"><a class="nm" href="<?= e(url('admin/sertifikat/' . $i['id'])) ?>"><?= e($i['full_name']) ?></a><br><span class="small muted"><?= e($i['institution']) ?></span></td>
        <td class="mono" style="white-space:normal"><?= cert_no($i['cert_number']) ?><?php if ($i['numbering_status'] === 'draft'): ?> <span class="bdg bdg-warn"><?= e(t('Pola belum disetujui')) ?></span><?php endif; ?></td>
        <td><?= CertIssuance::badge($i['status']) ?><?php if ($i['replaces_id']): ?> <span class="bdg bdg-mute"><?= e(t('Terbit ulang')) ?></span><?php endif; ?></td>
        <td class="small"><?= e(datetime_id($i['generated_at'])) ?></td>
        <td class="r"><a class="btn btn-ghost sm" href="<?= e(url('admin/sertifikat/' . $i['id'] . '/unduh', ['lihat' => '1'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 16) ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="row">
    <?php if ($canPublish): ?><button class="btn btn-primary sm" type="submit"><?= icon('check', 16) ?><?= e(t('Publikasikan yang dipilih')) ?></button><?php endif; ?>
    <button class="btn btn-outline sm" type="button" data-zip="<?= e(url('admin/sertifikat/zip', ['ev' => $eid])) ?>"><?= icon('download', 16) ?><?= e(t('ZIP yang dipilih')) ?></button>
  </div>
</form>
<script>
document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-zip]'); if (!b) return;
  var ids = Array.prototype.map.call(document.querySelectorAll('#sel-form input[name="ids[]"]:checked'), function (c) { return c.value; });
  if (!ids.length) { window.scaToast && window.scaToast('bad', window.T('Pilih sertifikat terlebih dahulu')); return; }
  location.href = b.getAttribute('data-zip') + '&ids=' + ids.join(',');
});
</script>
<?php endif; ?>
<?= View::partial('pager', ['page' => $pg, 'pages' => $pages, 'total' => $total, 'path' => 'admin/sertifikat/terbit', 'query' => ['ev' => $eid, 'st' => $status, 'q' => $q]]) ?>
