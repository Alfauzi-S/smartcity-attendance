<?php
use App\Core\View;
use App\Models\CertAsset;

$eid = (int) $event['id'];
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<div class="banner info"><?= icon('info') ?><div class="grow"><b><?= e(t('Gunakan aset resmi')) ?></b><?= e(t('Unggah file logo, tanda tangan, dan stempel resmi dari pihak berwenang. Sistem tidak menggambar ulang logo dan tidak membuat tanda tangan atau stempel. Tanda tangan dan stempel baru dapat dipakai di sertifikat final setelah disetujui Super Admin.')) ?></div></div>
<form method="post" action="<?= e(url('admin/sertifikat/aset/unggah')) ?>" enctype="multipart/form-data" class="card" data-loading="<?= e(t('Mengunggah…')) ?>">
  <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>">
  <div class="card-h"><h3><?= e(t('Unggah aset')) ?></h3><span class="sub"><?= e(t('PNG (disarankan, latar transparan) atau JPG · maks. :mb MB', ['mb' => config('cert.max_upload_mb', 8)])) ?></span></div>
  <div class="g-form two">
    <div class="fld"><label for="kind"><?= e(t('Jenis')) ?></label><select class="inp" id="kind" name="kind"><?php foreach (CertAsset::KINDS as $k => $label): ?><option value="<?= e($k) ?>"><?= e(t($label)) ?></option><?php endforeach; ?></select></div>
    <div class="fld"><label for="label"><?= e(t('Nama aset')) ?><span class="req">*</span></label><input class="inp" id="label" name="label" maxlength="120" required placeholder="<?= e(t('Logo Universitas Mulawarman')) ?>"></div>
    <div class="fld"><label for="file"><?= e(t('File')) ?><span class="req">*</span></label><input class="inp" id="file" name="file" type="file" accept="image/png,image/jpeg" required></div>
    <?php if (is_super()): ?>
    <div class="fld"><label for="scope"><?= e(t('Berlaku untuk')) ?></label><select class="inp" id="scope" name="scope"><option value="event"><?= e(t('Event ini saja')) ?></option><option value="global"><?= e(t('Semua event (aset global)')) ?></option></select></div>
    <?php endif; ?>
  </div>
  <div><button class="btn btn-primary" type="submit"><?= icon('upload', 18) ?><?= e(t('Unggah')) ?></button></div>
</form>
<div class="row"><a class="chip<?= query('arsip') === '1' ? '' : ' on' ?>" href="<?= e(url('admin/sertifikat/aset', ['ev' => $eid])) ?>"><?= e(t('Aktif')) ?></a><a class="chip<?= query('arsip') === '1' ? ' on' : '' ?>" href="<?= e(url('admin/sertifikat/aset', ['ev' => $eid, 'arsip' => '1'])) ?>"><?= e(t('Termasuk arsip')) ?></a></div>
<?php if (!$assets): ?>
  <div class="card"><div class="empty"><span class="eic"><?= icon('image', 26) ?></span><h3><?= e(t('Belum ada aset')) ?></h3><p><?= e(t('Unggah logo resmi, tanda tangan, dan stempel untuk dipakai di template.')) ?></p></div></div>
<?php else: ?>
  <div class="asset-grid">
    <?php foreach ($assets as $a): $needs = in_array($a['kind'], CertAsset::NEEDS_APPROVAL, true); ?>
      <div class="card asset<?= (int) $a['archived'] ? ' arch' : '' ?>">
        <div class="thumb"><img src="<?= e(url('admin/sertifikat/aset/' . $a['id'] . '/gambar')) ?>" alt="<?= e($a['label']) ?>" loading="lazy"></div>
        <b><?= e($a['label']) ?></b>
        <div class="row" style="gap:6px">
          <span class="bdg bdg-mute"><?= e(t(CertAsset::KINDS[$a['kind']])) ?></span>
          <span class="bdg <?= $a['event_id'] === null ? 'bdg-navy' : 'bdg-teal' ?>"><?= e($a['event_id'] === null ? t('Global') : t('Event ini')) ?></span>
          <?php if ($needs): ?><span class="bdg <?= (int) $a['approved'] ? 'bdg-ok' : 'bdg-warn' ?>"><i></i><?= e((int) $a['approved'] ? t('Disetujui') : t('Menunggu persetujuan')) ?></span><?php endif; ?>
          <?php if ((int) $a['archived']): ?><span class="bdg bdg-mute"><?= e(t('Diarsipkan')) ?></span><?php endif; ?>
        </div>
        <span class="small muted"><?= e((int) $a['width_px'] . '×' . (int) $a['height_px'] . ' px · @' . ($a['uploaded_by_name'] ?? '—')) ?></span>
        <div class="row" style="gap:6px">
          <?php if ($needs && !(int) $a['approved'] && is_super()): ?><?= post_button('admin/sertifikat/aset/' . $a['id'] . '/setujui', t('Setujui'), ['ev' => $eid], 'btn btn-primary sm', t('Setujui aset ":label" sebagai aset resmi yang sah?', ['label' => $a['label']]), 'check') ?><?php endif; ?>
          <?php if ($a['event_id'] !== null || is_super()): ?><?= post_button('admin/sertifikat/aset/' . $a['id'] . '/arsip', (int) $a['archived'] ? t('Pulihkan') : t('Arsipkan'), ['ev' => $eid], 'btn btn-outline sm', (int) $a['archived'] ? '' : t('Arsipkan aset ini? Template yang memakainya harus diganti asetnya.')) ?><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
