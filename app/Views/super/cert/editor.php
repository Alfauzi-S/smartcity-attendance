<?php
use App\Core\View;
use App\Models\CertAsset;
use App\Models\CertTemplate;
use App\Services\Cert\Placeholders;

View::script('js/cert-editor.js');
$eid = (int) $event['id'];
$draft = $t['status'] === 'Draft';
$base = 'admin/sertifikat/template/' . $t['id'];
$catalog = [];
foreach (Placeholders::BUILTIN as $k => [$label]) {
    $catalog[] = ['key' => $k, 'label' => t($label), 'group' => t('Bawaan')];
}
foreach (Placeholders::ISSUE_KEYS as $k => $label) {
    $catalog[] = ['key' => $k, 'label' => t($label), 'group' => t('Penerbitan')];
}
foreach ($vars as $k => $v) {
    $catalog[] = ['key' => $k, 'label' => $v['label'], 'group' => t('Kustom')];
}
$data = [
    'w' => $w, 'h' => $h, 'readonly' => !$draft, 'elements' => $elements, 'assets' => $assets, 'bg' => $bgUrl,
    'kinds' => array_map('t', CertAsset::KINDS), 'fonts' => CertTemplate::FONTS, 'catalog' => $catalog,
];
$badIssues = array_filter($issues, static fn ($i) => $i[0] === 'bad');
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<div class="pagehead">
  <div><h2><?= e($t['name']) ?></h2><p><span class="bdg bdg-<?= CertTemplate::STATUSES[$t['status']] ?>"><?= e(t($t['status'])) ?></span> · <?= e(t('Versi :v', ['v' => (int) $t['version']])) ?> · <?= e((float) $w . ' × ' . (float) $h) ?> mm</p></div>
  <div class="row">
    <form method="get" action="<?= e(url($base . '/pratinjau')) ?>" target="_blank" class="row" style="gap:6px">
      <select class="inp" name="peserta" aria-label="<?= e(t('Data contoh')) ?>" style="width:auto;min-height:38px;padding-top:6px;padding-bottom:6px">
        <option value=""><?= e(t('Data contoh (fiktif)')) ?></option>
        <?php foreach ($participants as $pp): ?><option value="<?= (int) $pp['id'] ?>"><?= e($pp['full_name']) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-outline sm" type="submit"><?= icon('eye', 16) ?><?= e(t('Pratinjau PDF')) ?></button>
    </form>
    <?php if ($draft): ?>
      <?= post_button($base . '/status', t('Aktifkan template'), ['action' => 'activate'], 'btn btn-primary sm', t('Aktifkan template ini? Template akan dikunci dari perubahan dan dipakai untuk generate. Template Active lain di event ini diarsipkan.'), 'check') ?>
    <?php else: ?>
      <?= post_button($base . '/status', t('Kembalikan ke Draft'), ['action' => 'draft'], 'btn btn-outline sm', t('Kembalikan ke Draft untuk diedit? Generate diblokir sampai template diaktifkan lagi. Sertifikat yang sudah terbit tidak berubah.'), 'edit') ?>
    <?php endif; ?>
    <?php if ($t['status'] !== 'Archived'): ?><?= post_button($base . '/status', t('Arsipkan'), ['action' => 'archive'], 'btn btn-ghost sm', t('Arsipkan template ini? Riwayat tetap tersimpan.')) ?><?php endif; ?>
  </div>
</div>
<?php if (!$draft): ?><div class="banner lock"><?= icon('lock') ?><div class="grow"><b><?= e(t('Template terkunci')) ?></b><?= e(t('Template berstatus :status tidak dapat diedit. Kembalikan ke Draft untuk mengubah; versi yang sudah dipakai sertifikat tetap tersimpan.', ['status' => t($t['status'])])) ?></div></div><?php endif; ?>
<?php if ($issues || $varErrors): ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Pemeriksaan template')) ?></h3><span class="bdg <?= $badIssues || $varErrors ? 'bdg-bad' : 'bdg-warn' ?>"><?= e(t(':n catatan', ['n' => count($issues) + count($varErrors)])) ?></span></div>
  <?= View::fetch('super/cert/_checks', ['checks' => array_merge($issues, array_map(static fn ($m) => ['bad', $m], array_values($varErrors)))]) ?>
  <p class="small muted"><?= e(t('Variabel yang belum diisi tidak menghalangi aktivasi, tetapi generate final diblokir sampai semuanya valid.')) ?></p>
</div>
<?php endif; ?>

<div class="ed">
  <div class="ed-main">
    <div class="card">
      <?php if ($t['background_type'] === 'pdf'): ?>
        <div class="banner info"><?= icon('info') ?><div class="grow"><?= e(t('Desain latar berupa PDF tidak dapat ditampilkan di editor browser. Atur posisi di sini, lalu periksa hasil akhirnya dengan Pratinjau PDF.')) ?></div></div>
      <?php endif; ?>
      <div class="cv-wrap"><div class="cv" id="cv" style="aspect-ratio:<?= e((float) $w . ' / ' . (float) $h) ?>" aria-label="<?= e(t('Kanvas sertifikat')) ?>"></div></div>
      <p class="small muted"><?= e(t('Seret elemen untuk memindahkan. Posisi disimpan dalam persen sehingga tetap sama di PDF berapa pun ukuran layar.')) ?></p>
      <?php if ($draft): ?>
      <div class="row ed-add">
        <button type="button" class="btn btn-outline sm" data-add="text"><?= icon('plus', 16) ?><?= e(t('Teks')) ?></button>
        <button type="button" class="btn btn-outline sm" data-add="logo"><?= icon('plus', 16) ?><?= e(t('Logo')) ?></button>
        <button type="button" class="btn btn-outline sm" data-add="signature"><?= icon('plus', 16) ?><?= e(t('Tanda tangan')) ?></button>
        <button type="button" class="btn btn-outline sm" data-add="stamp"><?= icon('plus', 16) ?><?= e(t('Stempel')) ?></button>
        <button type="button" class="btn btn-outline sm" data-add="image"><?= icon('plus', 16) ?><?= e(t('Gambar')) ?></button>
        <button type="button" class="btn btn-outline sm" data-add="qr"><?= icon('plus', 16) ?><?= e(t('QR verifikasi')) ?></button>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($draft): ?>
    <form method="post" action="<?= e(url($base . '/latar')) ?>" enctype="multipart/form-data" class="card" data-loading="<?= e(t('Mengunggah…')) ?>">
      <?= csrf_field() ?>
      <div class="card-h"><h3><?= e(t('Desain latar')) ?></h3><span class="sub"><?= e($t['background_type'] ? strtoupper($t['background_type']) . ' · ' . ($t['background_type'] === 'pdf' ? ((int) $t['background_w'] / 10) . '×' . ((int) $t['background_h'] / 10) . ' mm' : (int) $t['background_w'] . '×' . (int) $t['background_h'] . ' px') : t('Belum ada')) ?></span></div>
      <div class="row"><input class="inp" type="file" name="background" accept="image/png,image/jpeg,application/pdf" required style="flex:1;min-width:220px"><button class="btn btn-outline" type="submit"><?= icon('upload', 18) ?><?= e(t('Unggah desain')) ?></button></div>
      <p class="hint small"><?= e(t('PNG/JPG (disarankan ≥ 3508×2480 px untuk A4 300 dpi) atau PDF halaman pertama. Maks. :mb MB.', ['mb' => config('cert.max_upload_mb', 8)])) ?></p>
      <?php if ($t['background_w'] && $t['background_h'] && abs(($t['background_w'] / $t['background_h']) - ($w / $h)) > 0.02): ?>
        <div class="banner warn"><?= icon('alert') ?><div class="grow"><b><?= e(t('Rasio desain berbeda dengan halaman')) ?></b><?= e(t('Desain akan direntangkan mengikuti ukuran halaman. Samakan ukuran halaman dengan desain agar rasio terjaga.')) ?></div></div>
      <?php endif; ?>
    </form>
    <?php if ($t['background_w'] && $t['background_h'] && abs(($t['background_w'] / $t['background_h']) - ($w / $h)) > 0.02): ?>
      <div><?= post_button($base . '/ukuran', t('Samakan ukuran halaman dengan desain'), [], 'btn btn-outline sm') ?></div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <form method="post" action="<?= e(url($base . '/simpan')) ?>" class="ed-side" id="ed-form">
    <?= csrf_field() ?>
    <input type="hidden" name="elements" id="ed-elements" value="<?= e(json_encode($elements, JSON_UNESCAPED_UNICODE)) ?>">
    <div class="card">
      <div class="card-h"><h3><?= e(t('Halaman')) ?></h3></div>
      <div class="fld"><label for="ed-name"><?= e(t('Nama template')) ?></label><input class="inp" id="ed-name" name="name" maxlength="120" value="<?= e($t['name']) ?>"<?= $draft ? '' : ' disabled' ?>></div>
      <div class="g-form two">
        <div class="fld"><label for="ed-format"><?= e(t('Ukuran')) ?></label><select class="inp" id="ed-format" name="page_format"<?= $draft ? '' : ' disabled' ?>><?php foreach (CertTemplate::FORMATS as $k => $v): ?><option value="<?= e($k) ?>"<?= selected($t['page_format'], $k) ?>><?= e($k === 'custom' ? t('Kustom') : $k) ?></option><?php endforeach; ?></select></div>
        <div class="fld"><label for="ed-orient"><?= e(t('Orientasi')) ?></label><select class="inp" id="ed-orient" name="orientation"<?= $draft ? '' : ' disabled' ?>><option value="L"<?= selected($t['orientation'], 'L') ?>><?= e(t('Landscape')) ?></option><option value="P"<?= selected($t['orientation'], 'P') ?>><?= e(t('Portrait')) ?></option></select></div>
        <div class="fld" data-custom><label for="ed-w"><?= e(t('Lebar (mm)')) ?></label><input class="inp mono" id="ed-w" name="width_mm" inputmode="decimal" value="<?= e((float) $t['width_mm']) ?>"<?= $draft ? '' : ' disabled' ?>></div>
        <div class="fld" data-custom><label for="ed-h"><?= e(t('Tinggi (mm)')) ?></label><input class="inp mono" id="ed-h" name="height_mm" inputmode="decimal" value="<?= e((float) $t['height_mm']) ?>"<?= $draft ? '' : ' disabled' ?>></div>
      </div>
      <p class="hint small"><?= e(t('Perubahan ukuran halaman tampil di kanvas setelah disimpan.')) ?></p>
    </div>
    <div class="card">
      <div class="card-h"><h3><?= e(t('Elemen')) ?></h3><span class="sub" id="ed-count"></span></div>
      <div class="ed-list" id="ed-list"></div>
    </div>
    <div class="card" id="ed-props" hidden>
      <div class="card-h"><h3 id="ed-props-title"><?= e(t('Properti')) ?></h3></div>
      <div id="ed-props-body" class="stack" style="gap:12px"></div>
    </div>
    <div class="card" id="ed-vars" hidden>
      <div class="card-h"><h3><?= e(t('Sisipkan variabel')) ?></h3></div>
      <input class="inp" type="search" id="ed-var-q" placeholder="<?= e(t('Cari variabel')) ?>" aria-label="<?= e(t('Cari variabel')) ?>">
      <div class="ed-vlist" id="ed-vlist"></div>
      <a class="small" href="<?= e(url('admin/sertifikat/variabel', ['ev' => $eid])) ?>"><?= e(t('Kelola variabel kustom')) ?></a>
    </div>
    <?php if ($draft): ?><div class="ed-save"><button class="btn btn-primary block" type="submit"><?= icon('check', 18) ?><?= e(t('Simpan template')) ?></button></div><?php endif; ?>
  </form>
</div>
<script type="application/json" id="cert-data"><?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
