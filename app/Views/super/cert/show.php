<?php
use App\Core\View;
use App\Models\CertIssuance;
use App\Services\Cert\Issuer;

$eid = (int) $event['id'];
$block = Issuer::publishBlock($i, $settings);
$verifyUrl = full_url('v/' . $i['token']);
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<div class="card">
  <div class="card-h"><h3><?= e($i['full_name']) ?></h3><?= CertIssuance::badge($i['status']) ?></div>
  <dl class="kv">
    <div><dt><?= e(t('Nomor sertifikat')) ?></dt><dd class="mono"><?= e($i['cert_number']) ?></dd></div>
    <div><dt><?= e(t('Tanggal terbit')) ?></dt><dd><?= e(date_id($i['issue_date'])) ?></dd></div>
    <div><dt><?= e(t('Versi template / pola nomor')) ?></dt><dd><?= e(t('Template v:t · Pola v:n (:s)', ['t' => $i['template_version'], 'n' => $i['numbering_version'], 's' => t(\App\Models\CertNumbering::STATUSES[$i['numbering_status']][0])])) ?></dd></div>
    <div><dt><?= e(t('Di-generate')) ?></dt><dd><?= e(datetime_id($i['generated_at'])) ?> · @<?= e($i['generated_by_name'] ?? '—') ?></dd></div>
    <?php if ($i['published_at']): ?><div><dt><?= e(t('Dipublikasikan')) ?></dt><dd><?= e(datetime_id($i['published_at'])) ?> · @<?= e($i['published_by_name'] ?? '—') ?></dd></div><?php endif; ?>
    <?php if ($i['revoked_at']): ?><div><dt><?= e(t('Dicabut')) ?></dt><dd><?= e(datetime_id($i['revoked_at'])) ?> · @<?= e($i['revoked_by_name'] ?? '—') ?><br><q><?= e($i['revoke_reason']) ?></q></dd></div><?php endif; ?>
    <div><dt><?= e(t('Halaman verifikasi')) ?></dt><dd style="word-break:break-all"><?= $i['status'] === 'generated' ? e(t('Aktif setelah dipublikasikan')) : '<a href="' . e($verifyUrl) . '" target="_blank" rel="noopener">' . e($verifyUrl) . '</a>' ?></dd></div>
    <div><dt><?= e(t('Sidik file (SHA-256)')) ?></dt><dd class="mono small" style="word-break:break-all"><?= e($i['file_hash']) ?></dd></div>
  </dl>
  <div class="row">
    <a class="btn btn-outline sm" href="<?= e(url('admin/sertifikat/' . $i['id'] . '/unduh', ['lihat' => '1'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 16) ?><?= e(t('Lihat PDF')) ?></a>
    <a class="btn btn-outline sm" href="<?= e(url('admin/sertifikat/' . $i['id'] . '/unduh')) ?>"><?= icon('download', 16) ?><?= e(t('Unduh PDF')) ?></a>
    <?php if ($i['status'] === 'generated' && can('cert_publish', $eid)): ?>
      <?= $block ? '<span class="bdg bdg-warn">' . e($block) . '</span>' : post_button('admin/sertifikat/' . $i['id'] . '/publikasi', t('Publikasikan'), [], 'btn btn-primary sm', t('Publikasikan sertifikat ini? Peserta dapat mencarinya dan mengunduhnya.'), 'check') ?>
    <?php endif; ?>
  </div>
</div>
<?php if ($i['status'] !== 'revoked' && can('cert_revoke', $eid)): ?>
<div class="g-2 even">
  <form method="post" action="<?= e(url('admin/sertifikat/' . $i['id'] . '/cabut')) ?>" class="card" data-confirm="<?= e(t('Cabut sertifikat :n? Sertifikat tidak dapat diunduh lagi dan verifikasi menampilkan TIDAK VALID. Nomor tidak akan dipakai ulang.', ['n' => $i['cert_number']])) ?>">
    <?= csrf_field() ?>
    <div class="card-h"><h3><?= e(t('Cabut sertifikat')) ?></h3></div>
    <div class="fld"><label for="r1"><?= e(t('Alasan pencabutan')) ?><span class="req">*</span></label><textarea class="inp" id="r1" name="reason" rows="2" maxlength="255" required minlength="5"></textarea></div>
    <div><button class="btn btn-danger sm" type="submit"><?= icon('x', 16) ?><?= e(t('Cabut')) ?></button></div>
  </form>
  <?php if (can('cert_manage', $eid)): ?>
  <form method="post" action="<?= e(url('admin/sertifikat/' . $i['id'] . '/terbit-ulang')) ?>" class="card" data-confirm="<?= e(t('Terbitkan ulang? Sertifikat :n dicabut dan sertifikat baru dengan nomor baru dibuat dari template aktif saat ini.', ['n' => $i['cert_number']])) ?>">
    <?= csrf_field() ?>
    <div class="card-h"><h3><?= e(t('Terbitkan ulang')) ?></h3></div>
    <p class="small muted"><?= e(t('Gunakan bila ada kesalahan data (mis. nama salah ketik setelah diperbaiki). Record lama tetap tersimpan sebagai Revoked.')) ?></p>
    <div class="fld"><label for="r2"><?= e(t('Alasan penerbitan ulang')) ?><span class="req">*</span></label><textarea class="inp" id="r2" name="reason" rows="2" maxlength="255" required minlength="5"></textarea></div>
    <div><button class="btn btn-outline sm" type="submit"><?= icon('refresh', 16) ?><?= e(t('Terbitkan ulang')) ?></button></div>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Riwayat penerbitan peserta ini')) ?></h3></div>
  <div class="tblw"><table class="tbl">
    <thead><tr><th><?= e(t('Nomor')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Dibuat')) ?></th><th><?= e(t('Keterangan')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($history as $hh): ?>
      <tr<?= (int) $hh['id'] === (int) $i['id'] ? ' style="background:var(--cyan-bg)"' : '' ?>>
        <td class="mono"><a href="<?= e(url('admin/sertifikat/' . $hh['id'])) ?>"><?= e($hh['cert_number']) ?></a></td>
        <td><?= CertIssuance::badge($hh['status']) ?></td>
        <td class="small"><?= e(datetime_id($hh['generated_at'])) ?></td>
        <td class="small" style="white-space:normal"><?= $hh['replaces_id'] ? e(t('Menggantikan penerbitan #:id', ['id' => $hh['replaces_id']])) . '<br>' : '' ?><?= $hh['revoke_reason'] ? e(t('Dicabut: :r', ['r' => $hh['revoke_reason']])) : '' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
