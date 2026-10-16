<?php
use App\Core\View;
use App\Models\Participant;

$cls = static fn (string $k) => 'fld' . (isset($errors[$k]) ? ' is-err' : '');
$base = 'admin/peserta/' . $p['id'];
$preset = in_array($openStatus, ['hadir', 'tidak'], true) ? $openStatus : '';
?>
<?php if ($errors): ?><div class="banner bad" role="alert"><?= icon('alert') ?><div class="grow"><b>Data belum disimpan</b>Periksa kolom yang ditandai merah.</div></div><?php endif; ?>
<?= View::partial('participant_info', ['p' => $p, 'showPhone' => true]) ?>
<div class="card">
  <div class="card-h"><h3>Status kehadiran</h3><?= status_badge($p['status']) ?></div>
  <p class="small muted">Ubah status secara manual bila peserta hadir tetapi QR tidak bisa dipindai, atau untuk menetapkan Tidak Hadir setelah absensi ditutup. Setiap perubahan tercatat di riwayat.</p>
  <div class="row">
    <button type="button" class="btn btn-primary" data-open="status-dialog"><?= icon('edit', 18) ?>Ubah status</button>
    <a class="btn btn-ghost" href="<?= e(url($base, ['ubah' => 'data'])) ?>#edit"><?= icon('user', 18) ?>Ubah data peserta</a>
  </div>
</div>

<?php if ($editing): ?>
<form method="post" action="<?= e(url($base . '/ubah')) ?>" class="card" id="edit" novalidate data-loading="Menyimpan…">
  <?= csrf_field() ?>
  <div class="card-h"><h3>Ubah data peserta</h3></div>
  <div class="g-form two">
    <div class="<?= $cls('identity_number') ?>"><label for="identity_number">Nomor Identitas</label><input class="inp mono" id="identity_number" name="identity_number" inputmode="numeric" maxlength="18" value="<?= e($p['identity_number']) ?>"><?= field_error($errors, 'identity_number') ?></div>
    <div class="<?= $cls('profession') ?>"><label for="profession">Profesi / Pekerjaan</label><select class="inp" id="profession" name="profession"><?php foreach (Participant::PROFESSIONS as $o): ?><option<?= selected($p['profession'], $o) ?>><?= e($o) ?></option><?php endforeach; ?></select><?= field_error($errors, 'profession') ?></div>
    <div class="<?= $cls('full_name') ?>"><label for="full_name">Nama Lengkap beserta Gelar</label><input class="inp" id="full_name" name="full_name" maxlength="160" value="<?= e($p['full_name']) ?>"><?= field_error($errors, 'full_name') ?></div>
    <div class="<?= $cls('phone') ?>"><label for="phone">Nomor WhatsApp / HP</label><input class="inp mono" id="phone" name="phone" type="tel" maxlength="16" value="<?= e($p['phone']) ?>"><?= field_error($errors, 'phone') ?></div>
    <div class="<?= $cls('institution') ?>"><label for="institution">Institusi / Organisasi / Universitas</label><input class="inp" id="institution" name="institution" maxlength="160" value="<?= e($p['institution']) ?>"><?= field_error($errors, 'institution') ?></div>
    <div class="<?= $cls('field_of_study') ?>"><label for="field_of_study">Program Studi / Bidang Studi</label><select class="inp" id="field_of_study" name="field_of_study"><?php foreach (Participant::FIELDS as $o): ?><option<?= selected($p['field_of_study'], $o) ?>><?= e($o) ?></option><?php endforeach; ?></select><?= field_error($errors, 'field_of_study') ?></div>
  </div>
  <div class="row" style="justify-content:flex-end"><a class="btn btn-ghost" href="<?= e(url($base)) ?>">Batal</a><button class="btn btn-primary" type="submit">Simpan data</button></div>
</form>
<?php endif; ?>

<div class="card">
  <div class="card-h"><h3>Riwayat perubahan status</h3></div>
  <?= View::partial('history_items', ['items' => $history]) ?>
</div>

<div class="ov" id="status-dialog" role="dialog" aria-modal="true" aria-labelledby="sd-title"<?= $openStatus === '' ? ' hidden' : '' ?>>
  <form method="post" action="<?= e(url($base . '/status')) ?>" class="sheet" data-status-form data-current="<?= e($p['status']) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e(query('dari') === 'list' ? 'list' : '') ?>">
    <div class="grab"></div>
    <div data-step="1" class="stack" style="gap:16px">
      <div class="sh-h"><div><h3 id="sd-title">Ubah status kehadiran</h3><p><?= e($p['full_name']) ?> · <span class="mono"><?= e($p['identity_number']) ?></span></p></div><button type="button" class="iconbtn plain" data-close aria-label="Tutup"><?= icon('x') ?></button></div>
      <div class="opts" role="radiogroup" aria-label="Status baru">
        <?php foreach (['hadir' => ['Hadir', 'Peserta datang; dicatat sebagai manual'], 'tidak' => ['Tidak Hadir', 'Status final, QR tidak bisa dipakai']] as $k => [$label, $desc]):
          $isCur = $p['status'] === $k; ?>
          <label class="opt<?= $isCur ? ' dis' : '' ?>">
            <input type="radio" name="new_status" value="<?= $k ?>"<?= $isCur ? ' disabled' : '' ?><?= $preset === $k && !$isCur ? ' checked' : '' ?> required>
            <span class="rd"></span><span class="ot"><b><?= $label ?></b><span><?= $isCur ? 'Status saat ini' : $desc ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="fld"><label for="reason">Alasan perubahan<span class="en">Wajib diisi, tercatat di riwayat</span></label><textarea class="inp" id="reason" name="reason" maxlength="255" required placeholder="Contoh: QR rusak, identitas dicek dengan KTM"></textarea></div>
      <div class="acts"><button type="button" class="btn btn-outline" data-close>Batal</button><button type="button" class="btn btn-primary" data-next>Lanjut</button></div>
    </div>
    <div data-step="2" class="stack" style="gap:16px" hidden>
      <div class="sh-h"><div><h3>Konfirmasi perubahan</h3><p>Perubahan ini tercatat atas nama <?= e($user['name']) ?>.</p></div></div>
      <div class="fromto"><?= status_badge($p['status'], true) ?><?= icon('arrow') ?><span data-to></span></div>
      <p class="small" style="color:var(--ink2)">Alasan: <q data-reason></q></p>
      <div class="acts"><button type="button" class="btn btn-outline" data-prev>Kembali</button><button type="submit" class="btn btn-primary">Ya, ubah status</button></div>
    </div>
  </form>
</div>
<template id="tpl-badges"><span data-b="hadir"><?= status_badge('hadir', true) ?></span><span data-b="tidak"><?= status_badge('tidak', true) ?></span></template>
