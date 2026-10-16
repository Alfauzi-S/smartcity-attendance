<?php
use App\Models\Participant;

$v = static fn (string $k) => e($old[$k] ?? '');
$cls = static fn (string $k) => 'fld' . (isset($errors[$k]) ? ' is-err' : '');
$aria = static fn (string $k) => isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : '';
?>
<div class="pad narrow">
  <div class="pagehead">
    <div><a class="btn btn-ghost sm" href="<?= e(url('e/' . $event['slug'])) ?>" style="margin-left:-12px"><?= icon('back', 18) ?><?= e(t('Detail event')) ?></a>
      <h2><?= e(t('Formulir registrasi')) ?></h2>
      <p><?= e($event['name']) ?> · <?= e(date_id($event['event_date'], true)) ?></p>
    </div>
  </div>
  <?php if (!empty($errors['_form'])): ?>
    <div class="banner bad"><?= icon('alert') ?><div class="grow"><b><?= e(t('Registrasi gagal')) ?></b><?= e($errors['_form']) ?></div></div>
  <?php elseif ($errors): ?>
    <div class="banner bad" role="alert"><?= icon('alert') ?><div class="grow"><b><?= e(t('Periksa kembali :count isian', ['count' => count($errors)])) ?></b><?= e(t('Kolom yang ditandai merah belum sesuai.')) ?></div></div>
  <?php endif; ?>
  <form method="post" action="<?= e(url('e/' . $event['slug'] . '/daftar')) ?>" class="card" novalidate data-loading="<?= e(t('Menyimpan registrasi…')) ?>">
    <?= csrf_field() ?>
    <div class="sr" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <p class="small muted"><?= e(t('Semua kolom wajib diisi')) ?> <span style="color:var(--error)">*</span></p>
    <div class="g-form">
      <div class="<?= $cls('identity_number') ?>">
        <label for="identity_number"><?= e(t('Nomor Identitas')) ?><span class="req">*</span><span class="en"><?= e(t('NIM / NIP / NIK, hanya angka')) ?></span></label>
        <input class="inp mono" id="identity_number" name="identity_number" inputmode="numeric" autocomplete="off" maxlength="18" required value="<?= $v('identity_number') ?>" placeholder="<?= e(t('Contoh: 2609106001')) ?>"<?= $aria('identity_number') ?>>
        <?= field_error($errors, 'identity_number') ?>
      </div>
      <div class="<?= $cls('profession') ?>">
        <label for="profession"><?= e(t('Profesi / Pekerjaan')) ?><span class="req">*</span></label>
        <select class="inp" id="profession" name="profession" required<?= $aria('profession') ?>>
          <option value=""><?= e(t('Pilih profesi')) ?></option>
          <?php foreach (Participant::PROFESSIONS as $o): ?><option value="<?= e($o) ?>"<?= selected($old['profession'] ?? '', $o) ?>><?= e(t($o)) ?></option><?php endforeach; ?>
        </select>
        <?= field_error($errors, 'profession') ?>
      </div>
      <div class="<?= $cls('full_name') ?>">
        <label for="full_name"><?= e(t('Nama Lengkap beserta Gelar')) ?><span class="req">*</span><span class="en"><?= e(t('Ditulis seperti pada sertifikat')) ?></span></label>
        <input class="inp" id="full_name" name="full_name" autocomplete="name" maxlength="160" required value="<?= $v('full_name') ?>" placeholder="<?= e(t('Contoh: Nadia Putri Ramadhani, S.Kom.')) ?>"<?= $aria('full_name') ?>>
        <?= field_error($errors, 'full_name') ?>
      </div>
      <div class="<?= $cls('phone') ?>">
        <label for="phone"><?= e(t('Nomor WhatsApp / HP')) ?><span class="req">*</span></label>
        <input class="inp mono" id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="16" required value="<?= $v('phone') ?>" placeholder="08xxxxxxxxxx"<?= $aria('phone') ?>>
        <?php if (!isset($errors['phone'])): ?><p class="hint"><?= e(t('Hanya dilihat panitia untuk informasi event.')) ?></p><?php endif; ?>
        <?= field_error($errors, 'phone') ?>
      </div>
      <div class="<?= $cls('institution') ?>">
        <label for="institution"><?= e(t('Institusi / Organisasi / Universitas')) ?><span class="req">*</span></label>
        <input class="inp" id="institution" name="institution" autocomplete="organization" maxlength="160" required value="<?= $v('institution') ?>" placeholder="<?= e(t('Contoh: Universitas Mulawarman')) ?>"<?= $aria('institution') ?>>
        <?= field_error($errors, 'institution') ?>
      </div>
      <div class="<?= $cls('field_of_study') ?>">
        <label for="field_of_study"><?= e(t('Program Studi / Bidang Studi')) ?><span class="req">*</span></label>
        <select class="inp" id="field_of_study" name="field_of_study" required<?= $aria('field_of_study') ?>>
          <option value=""><?= e(t('Pilih program studi')) ?></option>
          <?php foreach (Participant::FIELDS as $o): ?><option value="<?= e($o) ?>"<?= selected($old['field_of_study'] ?? '', $o) ?>><?= e(t($o)) ?></option><?php endforeach; ?>
        </select>
        <?= field_error($errors, 'field_of_study') ?>
      </div>
    </div>
    <div class="banner info"><?= icon('info') ?><div class="grow"><?= e(t('Setelah dikirim, QR Code langsung tampil. Simpan baik-baik karena QR hanya berlaku untuk satu kali absensi.')) ?></div></div>
    <button type="submit" class="btn btn-primary lg block"><?= e(t('Kirim registrasi')) ?></button>
  </form>
</div>
