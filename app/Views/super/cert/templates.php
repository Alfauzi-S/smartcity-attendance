<?php
use App\Core\View;
use App\Models\CertTemplate;

$eid = (int) $event['id'];
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<form method="post" action="<?= e(url('admin/sertifikat/template/baru')) ?>" class="card">
  <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>">
  <div class="card-h"><h3><?= e(t('Template baru')) ?></h3></div>
  <div class="g-form two">
    <div class="fld"><label for="tname"><?= e(t('Nama template')) ?><span class="req">*</span></label><input class="inp" id="tname" name="name" maxlength="120" required placeholder="<?= e(t('Sertifikat Peserta')) ?>"></div>
    <div class="fld"><label for="preset"><?= e(t('Tata letak awal')) ?></label>
      <select class="inp" id="preset" name="preset">
        <option value="akademik"><?= e(t('Sertifikat akademik (A4 landscape, slot logo, tanda tangan, stempel, QR)')) ?></option>
        <option value="modern"><?= e(t('Modern gelap (Sora, navy-teal, 3 logo, 3 penanda tangan, QR)')) ?></option>
        <option value="kosong"><?= e(t('Kosong')) ?></option>
      </select>
    </div>
  </div>
  <div><button class="btn btn-primary" type="submit"><?= icon('plus', 18) ?><?= e(t('Buat template')) ?></button></div>
</form>
<?php if (!$templates): ?>
  <div class="card"><div class="empty"><span class="eic"><?= icon('award', 26) ?></span><h3><?= e(t('Belum ada template')) ?></h3><p><?= e(t('Buat template pertama untuk event ini.')) ?></p></div></div>
<?php else: ?>
  <div class="tblw">
    <table class="tbl">
      <thead><tr><th><?= e(t('Nama')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Ukuran')) ?></th><th><?= e(t('Desain')) ?></th><th class="r"><?= e(t('Versi')) ?></th><th class="r"><?= e(t('Terbit')) ?></th><th><?= e(t('Diubah')) ?></th></tr></thead>
      <tbody>
      <?php foreach ($templates as $tp): [$w, $h] = CertTemplate::size($tp); ?>
        <tr>
          <td><a class="nm" href="<?= e(url('admin/sertifikat/template/' . $tp['id'])) ?>"><?= e($tp['name']) ?></a></td>
          <td><span class="bdg bdg-<?= CertTemplate::STATUSES[$tp['status']] ?>"><?= e(t($tp['status'])) ?></span></td>
          <td class="mono"><?= e($tp['page_format'] === 'custom' ? t('Kustom') : $tp['page_format']) ?> · <?= e((float) $w . '×' . (float) $h) ?> mm</td>
          <td><?= e($tp['background_type'] ? strtoupper($tp['background_type']) : t('Belum ada')) ?></td>
          <td class="r num"><?= (int) $tp['version'] ?></td>
          <td class="r num"><?= (int) $tp['issued'] ?></td>
          <td><?= e(datetime_id($tp['updated_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
