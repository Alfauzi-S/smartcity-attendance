<?php
use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;

$eid = (int) $event['id'];
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<?php if ($changed): ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Pencabutan dan penerbitan ulang')) ?></h3></div>
  <div class="tblw"><table class="tbl">
    <thead><tr><th><?= e(t('Peserta')) ?></th><th><?= e(t('Nomor dicabut')) ?></th><th><?= e(t('Alasan')) ?></th><th><?= e(t('Pengganti')) ?></th><th><?= e(t('Waktu')) ?></th></tr></thead>
    <tbody><?php foreach ($changed as $c): ?>
      <tr><td><?= e($c['full_name']) ?></td><td class="mono"><a href="<?= e(url('admin/sertifikat/' . $c['id'])) ?>"><?= e($c['cert_number']) ?></a></td><td style="white-space:normal"><?= e($c['revoke_reason']) ?></td>
        <td class="mono"><?= $c['new_id'] ? '<a href="' . e(url('admin/sertifikat/' . $c['new_id'])) . '">' . e($c['new_number']) . '</a>' : '—' ?></td><td class="small"><?= e(datetime_id($c['revoked_at'])) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div>
<?php endif; ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Audit log sertifikat')) ?></h3><?php if (can('audit', $eid)): ?><a class="btn btn-ghost sm" href="<?= e(url('admin/audit', ['ev' => $eid, 'jenis' => 'cert.'])) ?>"><?= e(t('Buka Audit Log')) ?></a><?php endif; ?></div>
  <?php if (!$items): ?><p class="small muted"><?= e(t('Belum ada catatan.')) ?></p><?php else: ?>
  <div class="hist">
    <?php foreach ($items as $l): $m = json_decode((string) $l['meta'], true) ?: []; ?>
      <div class="hi"><span class="hic"><?= icon($l['result'] === 'ok' ? 'award' : 'alert', 16) ?></span><div class="hb">
        <span class="t1"><?= e(AuditLog::label($l['action'])) ?><?= isset($m['number']) ? ' · ' . e($m['number']) : '' ?></span>
        <?php if (isset($m['message']) || isset($m['reason'])): ?><span class="t2 small"><?= e($m['message'] ?? $m['reason']) ?></span><?php endif; ?>
        <span class="t3"><?= e(datetime_id($l['created_at'])) ?> · <?= $l['username'] ? '@' . e($l['username']) . ' (' . e(Auth::roleLabel($l['role'])) . ')' : e(t('Publik')) ?></span>
      </div></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?= View::partial('pager', ['page' => $pg, 'pages' => $pages, 'total' => $total, 'path' => 'admin/sertifikat/riwayat', 'query' => ['ev' => $eid]]) ?>
