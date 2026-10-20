<?php
use App\Core\View;
use App\Models\Participant;
use App\Models\ParticipantOptions;
use App\Services\Access;

$cls = static fn (string $k) => 'fld' . (isset($errors[$k]) ? ' is-err' : '');
$base = 'admin/peserta/' . $p['id'];
$preset = in_array($openStatus, ['hadir', 'tidak'], true) ? $openStatus : '';
?>
<?php if ($errors): ?><div class="banner bad" role="alert"><?= icon('alert') ?><div class="grow"><b><?= e(t('Data belum disimpan')) ?></b><?= e(t('Periksa kolom yang ditandai merah.')) ?></div></div><?php endif; ?>
<?= View::partial('participant_info', ['p' => $p, 'showPhone' => true]) ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Status kehadiran')) ?></h3><?= status_badge($p['status']) ?></div>
  <p class="small muted"><?= e(t('Ubah status secara manual bila peserta hadir tetapi QR tidak bisa dipindai, atau untuk menetapkan Tidak Hadir setelah presensi ditutup. Setiap perubahan tercatat di riwayat.')) ?></p>
  <div class="row">
    <?php if ($canStatus): ?><button type="button" class="btn btn-primary" data-open="status-dialog"><?= icon('edit', 18) ?><?= e(t('Ubah status')) ?></button><?php endif; ?>
    <a class="btn btn-ghost" href="<?= e(url($base, ['ubah' => 'data'])) ?>#edit"><?= icon('user', 18) ?><?= e(t('Ubah data peserta')) ?></a>
  </div>
</div>

<?php if ($editing): ?>
<form method="post" action="<?= e(url($base . '/ubah')) ?>" class="card" id="edit" novalidate data-loading="<?= e(t('Menyimpan…')) ?>">
  <?= csrf_field() ?>
  <div class="card-h"><h3><?= e(t('Ubah data peserta')) ?></h3></div>
  <div class="g-form two">
    <div class="<?= $cls('identity_number') ?>"><label for="identity_number"><?= e(t('Nomor Identitas')) ?></label><input class="inp mono" id="identity_number" name="identity_number" inputmode="numeric" maxlength="18" value="<?= e($p['identity_number']) ?>"><?= field_error($errors, 'identity_number') ?></div>
    <div class="<?= $cls('profession') ?>"><label for="profession"><?= e(t('Profesi / Pekerjaan')) ?></label><?= \App\Core\View::partial('option_select', ['name' => 'profession', 'groups' => ParticipantOptions::PROFESSIONS, 'value' => $p['profession'], 'otherLabel' => t('Tulis profesi atau pekerjaan'), 'max' => ParticipantOptions::PROFESSION_MAX]) ?><?= field_error($errors, 'profession') ?></div>
    <div class="<?= $cls('full_name') ?>"><label for="full_name"><?= e(t('Nama Lengkap beserta Gelar')) ?></label><input class="inp" id="full_name" name="full_name" maxlength="160" value="<?= e($p['full_name']) ?>"><?= field_error($errors, 'full_name') ?></div>
    <div class="<?= $cls('phone') ?>"><label for="phone"><?= e(t('Nomor WhatsApp / HP')) ?></label><input class="inp mono" id="phone" name="phone" type="tel" maxlength="16" value="<?= e($p['phone']) ?>"><?= field_error($errors, 'phone') ?></div>
    <div class="<?= $cls('institution') ?>"><label for="institution"><?= e(t('Institusi / Organisasi / Universitas')) ?></label><input class="inp" id="institution" name="institution" maxlength="160" value="<?= e($p['institution']) ?>"><?= field_error($errors, 'institution') ?></div>
    <div class="<?= $cls('field_of_study') ?>"><label for="field_of_study"><?= e(t('Program Studi / Bidang Studi')) ?></label><?= \App\Core\View::partial('option_select', ['name' => 'field_of_study', 'groups' => ParticipantOptions::FIELDS, 'value' => $p['field_of_study'], 'otherLabel' => t('Tulis program studi atau bidang studi'), 'max' => ParticipantOptions::FIELD_MAX]) ?><?= field_error($errors, 'field_of_study') ?></div>
  </div>
  <div class="row" style="justify-content:flex-end"><a class="btn btn-ghost" href="<?= e(url($base)) ?>"><?= e(t('Batal')) ?></a><button class="btn btn-primary" type="submit"><?= e(t('Simpan data')) ?></button></div>
</form>
<?php endif; ?>

<div class="card">
  <div class="card-h"><h3><?= e(t('Riwayat perubahan status')) ?></h3></div>
  <?= View::partial('history_items', ['items' => $history]) ?>
</div>

<?php if ($canStatus): ?>
<div class="ov" id="status-dialog" role="dialog" aria-modal="true" aria-labelledby="sd-title"<?= $openStatus === '' ? ' hidden' : '' ?>>
  <form method="post" action="<?= e(url($base . '/status')) ?>" class="sheet" data-status-form data-current="<?= e($p['status']) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e(query('dari') === 'list' ? 'list' : '') ?>">
    <div class="grab"></div>
    <div data-step="1" class="stack" style="gap:16px">
      <div class="sh-h"><div><h3 id="sd-title"><?= e(t('Ubah status kehadiran')) ?></h3><p><?= e($p['full_name']) ?> · <span class="mono"><?= e($p['identity_number']) ?></span></p></div><button type="button" class="iconbtn plain" data-close aria-label="<?= e(t('Tutup')) ?>"><?= icon('x') ?></button></div>
      <div class="opts" role="radiogroup" aria-label="<?= e(t('Status baru')) ?>">
        <?php foreach (['hadir' => [t('Hadir'), t('Peserta datang; dicatat sebagai manual')], 'tidak' => [t('Tidak Hadir'), t('Status final, QR tidak bisa dipakai')]] as $k => [$label, $desc]):
          $isCur = $p['status'] === $k; ?>
          <label class="opt<?= $isCur ? ' dis' : '' ?>">
            <input type="radio" name="new_status" value="<?= $k ?>"<?= $isCur ? ' disabled' : '' ?><?= $preset === $k && !$isCur ? ' checked' : '' ?> required>
            <span class="rd"></span><span class="ot"><b><?= e($label) ?></b><span><?= e($isCur ? t('Status saat ini') : $desc) ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="fld"><label for="reason"><?= e(t('Alasan perubahan')) ?><span class="en"><?= e(t('Wajib diisi, tercatat di riwayat')) ?></span></label><textarea class="inp" id="reason" name="reason" maxlength="255" required placeholder="<?= e(t('Contoh: QR rusak, identitas dicek dengan KTM')) ?>"></textarea></div>
      <div class="acts"><button type="button" class="btn btn-outline" data-close><?= e(t('Batal')) ?></button><button type="button" class="btn btn-primary" data-next><?= e(t('Lanjut')) ?></button></div>
    </div>
    <div data-step="2" class="stack" style="gap:16px" hidden>
      <div class="sh-h"><div><h3><?= e(t('Konfirmasi perubahan')) ?></h3><p><?= e(t('Perubahan ini tercatat atas nama :name.', ['name' => $user['name']])) ?></p></div></div>
      <div class="fromto"><?= status_badge($p['status'], true) ?><?= icon('arrow') ?><span data-to></span></div>
      <p class="small" style="color:var(--ink2)"><?= e(t('Alasan:')) ?> <q data-reason></q></p>
      <div class="acts"><button type="button" class="btn btn-outline" data-prev><?= e(t('Kembali')) ?></button><button type="submit" class="btn btn-primary"><?= e(t('Ya, ubah status')) ?></button></div>
    </div>
  </form>
</div>
<?php endif; ?>

<?php if (Access::can($user, (int) $p['event_id'], 'participants')): ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Hapus peserta')) ?></h3></div>
  <?php if (!empty($hasCert)): ?>
    <p class="small muted"><?= e(t('Peserta ini sudah memiliki sertifikat sehingga tidak dapat dihapus. Data sertifikat disimpan untuk keperluan verifikasi.')) ?></p>
  <?php else: ?>
    <p class="small muted"><?= e(t('Gunakan untuk peserta yang salah mendaftar atau duplikat. Data peserta, tiket QR, dan riwayat statusnya akan dihapus permanen; audit log tetap tersimpan.')) ?></p>
    <div><?= post_button('admin/peserta/' . $p['id'] . '/hapus', t('Hapus peserta'), [], 'btn btn-danger sm', t('Hapus peserta ":name"? Data peserta, tiket QR, dan riwayat statusnya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.', ['name' => $p['full_name']]), 'trash') ?></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<template id="tpl-badges"><span data-b="hadir"><?= status_badge('hadir', true) ?></span><span data-b="tidak"><?= status_badge('tidak', true) ?></span></template>
