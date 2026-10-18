<?php
use App\Core\View;
use App\Models\CertVariable;
use App\Services\Cert\Placeholders;

$eid = (int) $event['id'];
$f = $old ?? ($edit ? [
    'id' => $edit['id'], 'scope' => $edit['scope'], 'var_key' => $edit['var_key'], 'label' => $edit['label'], 'var_type' => $edit['var_type'],
    'source' => $edit['source'], 'source_field' => $edit['source_field'], 'choices' => $edit['choices'], 'value' => $edit['value'],
    'default_value' => $edit['default_value'], 'required' => (bool) $edit['required'], 'overridable' => (bool) $edit['overridable'],
    'description' => $edit['description'], 'active' => (bool) $edit['active'],
] : ['id' => 0, 'scope' => 'event', 'var_key' => '', 'label' => '', 'var_type' => 'text', 'source' => 'manual', 'source_field' => '', 'choices' => '',
    'value' => '', 'default_value' => '', 'required' => true, 'overridable' => true, 'description' => '', 'active' => true]);
$isEdit = (int) ($f['id'] ?? 0) > 0;
$cls = static fn (string $k) => 'fld' . (isset($formErrors[$k]) ? ' is-err' : '');
$origin = ['global' => 'Nilai global', 'override' => 'Nilai khusus event', 'default' => 'Nilai default', 'event' => 'Variabel event'];
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<div class="banner info"><?= icon('info') ?><div class="grow"><?= e(t('Tulis variabel di template dengan format {KEY}. Variabel wajib yang kosong memblokir generate final. Isi kode surat dan nomor resmi hanya setelah dikonfirmasi fakultas/TU.')) ?></div></div>

<div class="card">
  <div class="card-h"><h3><?= e(t('Variabel organisasi & global')) ?></h3><span class="sub"><?= e(is_super() ? t('Dikelola Super Admin; dapat diganti per event bila diizinkan') : t('Nilai global dari Super Admin; isi kolom kanan untuk mengganti khusus event ini')) ?></span></div>
  <div class="tblw">
    <table class="tbl">
      <thead><tr><th><?= e(t('Variabel')) ?></th><th><?= e(t('Tipe / sumber')) ?></th><th><?= e(t('Nilai global')) ?></th><th><?= e(t('Nilai untuk event ini')) ?></th><th><?= e(t('Status')) ?></th><?php if (is_super()): ?><th></th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach ($globals as $v): $k = $v['var_key']; $eff = $effective[$k] ?? null; ?>
        <tr<?= (int) $v['active'] ? '' : ' class="muted"' ?>>
          <td style="white-space:normal"><code>{<?= e($k) ?>}</code><br><span class="small"><?= e($v['label']) ?><?= (int) $v['required'] ? '' : ' · ' . e(t('opsional')) ?></span><?php if ($v['description']): ?><br><span class="small muted"><?= e($v['description']) ?></span><?php endif; ?></td>
          <td class="small"><?= e(t(CertVariable::TYPES[$v['var_type']] ?? $v['var_type'])) ?><br><span class="muted"><?= e(t(CertVariable::SOURCES[$v['source']] ?? $v['source'])) ?><?= $v['source_field'] ? ' · ' . e($v['source_field']) : '' ?></span></td>
          <td style="white-space:normal"><?= $v['source'] === 'manual' ? e((string) $v['value'] !== '' ? $v['value'] : '—') : '<span class="muted small">' . e(t('otomatis')) . '</span>' ?></td>
          <td style="white-space:normal;min-width:220px">
            <?php if ($v['source'] === 'manual' && (int) $v['overridable'] && (int) $v['active']): ?>
              <form method="post" action="<?= e(url('admin/sertifikat/variabel/' . $v['id'] . '/nilai')) ?>" class="row" style="gap:6px">
                <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>">
                <?php if ($v['var_type'] === 'choice'): ?>
                  <select class="inp" name="value" style="flex:1"><option value=""><?= e(t('— pakai nilai global —')) ?></option><?php foreach (CertVariable::choices($v) as $c): ?><option<?= selected($overrides[(int) $v['id']] ?? '', $c) ?>><?= e($c) ?></option><?php endforeach; ?></select>
                <?php else: ?>
                  <input class="inp" name="value" type="<?= $v['var_type'] === 'date' ? 'date' : 'text' ?>" maxlength="500" value="<?= e($overrides[(int) $v['id']] ?? '') ?>" placeholder="<?= e(t('pakai nilai global')) ?>" style="flex:1;min-width:120px">
                <?php endif; ?>
                <button class="btn btn-outline sm" type="submit"><?= e(t('Simpan')) ?></button>
              </form>
            <?php else: ?><span class="muted small"><?= e((int) $v['active'] ? t('Tidak dapat diganti per event') : t('Nonaktif')) ?></span><?php endif; ?>
          </td>
          <td><?php if (!(int) $v['active']): ?><span class="bdg bdg-mute"><?= e(t('Nonaktif')) ?></span><?php elseif (isset($varErrors[$k])): ?><span class="bdg bdg-bad"><?= e(t('Belum diisi')) ?></span><?php elseif ($eff): ?><span class="bdg bdg-ok" title="<?= e($eff['resolved_manual']) ?>"><?= e(t($origin[$eff['origin']] ?? 'OK')) ?></span><?php endif; ?></td>
          <?php if (is_super()): ?><td><a class="btn btn-ghost sm" href="<?= e(url('admin/sertifikat/variabel', ['ev' => $eid, 'ubah' => $v['id']])) ?>#form-var"><?= icon('edit', 16) ?></a></td><?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-h"><h3><?= e(t('Variabel khusus event ini')) ?></h3></div>
  <?php if (!$eventVars): ?><p class="small muted"><?= e(t('Belum ada. Tambahkan lewat formulir di bawah, mis. {EVENT_THEME} atau {SKS}.')) ?></p><?php else: ?>
  <div class="tblw"><table class="tbl">
    <thead><tr><th><?= e(t('Variabel')) ?></th><th><?= e(t('Tipe / sumber')) ?></th><th><?= e(t('Nilai')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($eventVars as $v): $k = $v['var_key']; ?>
      <tr>
        <td style="white-space:normal"><code>{<?= e($k) ?>}</code><br><span class="small"><?= e($v['label']) ?><?= (int) $v['required'] ? '' : ' · ' . e(t('opsional')) ?></span></td>
        <td class="small"><?= e(t(CertVariable::TYPES[$v['var_type']] ?? $v['var_type'])) ?><br><span class="muted"><?= e(t(CertVariable::SOURCES[$v['source']] ?? $v['source'])) ?><?= $v['source_field'] ? ' · ' . e($v['source_field']) : '' ?></span></td>
        <td style="white-space:normal"><?= e($v['source'] === 'manual' ? ((string) $v['value'] !== '' ? $v['value'] : ((string) $v['default_value'] !== '' ? $v['default_value'] . ' (' . t('default') . ')' : '—')) : t('otomatis')) ?></td>
        <td><?php if (!(int) $v['active']): ?><span class="bdg bdg-mute"><?= e(t('Nonaktif')) ?></span><?php elseif (isset($varErrors[$k])): ?><span class="bdg bdg-bad"><?= e(t('Belum diisi')) ?></span><?php else: ?><span class="bdg bdg-ok"><?= e(t('Aktif')) ?></span><?php endif; ?></td>
        <td><a class="btn btn-ghost sm" href="<?= e(url('admin/sertifikat/variabel', ['ev' => $eid, 'ubah' => $v['id']])) ?>#form-var"><?= icon('edit', 16) ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>

<form method="post" action="<?= e(url('admin/sertifikat/variabel/simpan')) ?>" class="card" id="form-var" novalidate>
  <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>"><input type="hidden" name="id" value="<?= (int) ($f['id'] ?? 0) ?>">
  <div class="card-h"><h3><?= e($isEdit ? t('Ubah variabel {:key}', ['key' => $f['var_key']]) : t('Tambah variabel')) ?></h3><?php if ($isEdit): ?><a class="btn btn-ghost sm" href="<?= e(url('admin/sertifikat/variabel', ['ev' => $eid])) ?>"><?= e(t('Batal')) ?></a><?php endif; ?></div>
  <div class="g-form two">
    <div class="<?= $cls('var_key') ?>"><label for="var_key"><?= e(t('Key')) ?><span class="req">*</span><span class="en"><?= e(t('HURUF_KAPITAL, mis. FACULTY_LETTER_CODE')) ?></span></label><input class="inp mono" id="var_key" name="var_key" maxlength="40" value="<?= e($f['var_key']) ?>"<?= $isEdit ? ' readonly' : '' ?> autocapitalize="characters"><?= field_error($formErrors, 'var_key') ?></div>
    <div class="<?= $cls('label') ?>"><label for="vlabel"><?= e(t('Label')) ?><span class="req">*</span></label><input class="inp" id="vlabel" name="label" maxlength="120" value="<?= e($f['label']) ?>"><?= field_error($formErrors, 'label') ?></div>
    <?php if (!$isEdit && is_super()): ?>
    <div class="fld"><label for="vscope"><?= e(t('Cakupan')) ?></label><select class="inp" id="vscope" name="scope"><option value="event"<?= selected($f['scope'], 'event') ?>><?= e(t('Event ini')) ?></option><option value="global"<?= selected($f['scope'], 'global') ?>><?= e(t('Global (semua event)')) ?></option></select></div>
    <?php endif; ?>
    <div class="<?= $cls('var_type') ?>"><label for="vtype"><?= e(t('Tipe data')) ?></label><select class="inp" id="vtype" name="var_type"><?php foreach (CertVariable::TYPES as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($f['var_type'], $k) ?>><?= e(t($label)) ?></option><?php endforeach; ?></select><?= field_error($formErrors, 'var_type') ?></div>
    <div class="<?= $cls('source') ?>"><label for="vsource"><?= e(t('Sumber nilai')) ?></label><select class="inp" id="vsource" name="source"><?php foreach (CertVariable::SOURCES as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($f['source'], $k) ?>><?= e(t($label)) ?></option><?php endforeach; ?></select><?= field_error($formErrors, 'source') ?></div>
    <div class="<?= $cls('source_field') ?>"><label for="vfield"><?= e(t('Kolom / generator')) ?><span class="en"><?= e(t('Untuk sumber data peserta, data event, atau generator')) ?></span></label>
      <select class="inp" id="vfield" name="source_field">
        <option value="">—</option>
        <optgroup label="<?= e(t('Data peserta')) ?>"><?php foreach (CertVariable::PARTICIPANT_FIELDS as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($f['source'] === 'participant_field' ? $f['source_field'] : '', $k) ?>><?= e(t($label)) ?></option><?php endforeach; ?></optgroup>
        <optgroup label="<?= e(t('Data event')) ?>"><?php foreach (CertVariable::EVENT_FIELDS as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($f['source'] === 'event_field' ? $f['source_field'] : '', $k) ?>><?= e(t($label)) ?></option><?php endforeach; ?></optgroup>
        <optgroup label="<?= e(t('Generator sistem')) ?>"><?php foreach (CertVariable::GENERATORS as $k => $label): ?><option value="<?= e($k) ?>"<?= selected($f['source'] === 'generator' ? $f['source_field'] : '', $k) ?>><?= e(t($label)) ?></option><?php endforeach; ?></optgroup>
      </select><?= field_error($formErrors, 'source_field') ?></div>
    <div class="<?= $cls('value') ?>"><label for="vvalue"><?= e(t('Nilai')) ?><span class="en"><?= e(t('Untuk sumber input manual')) ?></span></label><input class="inp" id="vvalue" name="value" maxlength="500" value="<?= e($f['value']) ?>"><?= field_error($formErrors, 'value') ?></div>
    <div class="<?= $cls('default_value') ?>"><label for="vdef"><?= e(t('Nilai default')) ?><span class="en"><?= e(t('Opsional')) ?></span></label><input class="inp" id="vdef" name="default_value" maxlength="500" value="<?= e($f['default_value']) ?>"><?= field_error($formErrors, 'default_value') ?></div>
    <div class="<?= $cls('choices') ?>"><label for="vchoices"><?= e(t('Pilihan')) ?><span class="en"><?= e(t('Untuk tipe Pilihan, satu per baris')) ?></span></label><textarea class="inp" id="vchoices" name="choices" rows="3"><?= e($f['choices']) ?></textarea><?= field_error($formErrors, 'choices') ?></div>
    <div class="<?= $cls('description') ?>"><label for="vdesc"><?= e(t('Keterangan penggunaan')) ?></label><input class="inp" id="vdesc" name="description" maxlength="255" value="<?= e($f['description']) ?>"><?= field_error($formErrors, 'description') ?></div>
  </div>
  <div class="row">
    <label class="chk"><input type="checkbox" name="required" value="1"<?= $f['required'] ? ' checked' : '' ?>><span><?= e(t('Wajib diisi')) ?></span></label>
    <?php if (is_super() && ($f['scope'] ?? 'event') === 'global' || is_super() && !$isEdit): ?><label class="chk"><input type="checkbox" name="overridable" value="1"<?= $f['overridable'] ? ' checked' : '' ?>><span><?= e(t('Boleh diganti per event (khusus variabel global)')) ?></span></label><?php endif; ?>
    <label class="chk"><input type="checkbox" name="active" value="1"<?= $f['active'] ? ' checked' : '' ?>><span><?= e(t('Aktif')) ?></span></label>
  </div>
  <div><button class="btn btn-primary" type="submit"><?= e($isEdit ? t('Simpan perubahan') : t('Tambah variabel')) ?></button></div>
</form>

<div class="card">
  <div class="card-h"><h3><?= e(t('Variabel bawaan')) ?></h3><span class="sub"><?= e(t('Diisi otomatis oleh sistem')) ?></span></div>
  <div class="vars-grid">
    <?php foreach (Placeholders::BUILTIN as $k => [$label]): ?><div><code>{<?= e($k) ?>}</code><span class="small muted"><?= e(t($label)) ?></span></div><?php endforeach; ?>
    <?php foreach (Placeholders::ISSUE_KEYS as $k => $label): ?><div><code>{<?= e($k) ?>}</code><span class="small muted"><?= e(t($label)) ?></span></div><?php endforeach; ?>
  </div>
  <p class="small muted"><?= e(t('Data kelas (PARTICIPANT_CLASS) belum tersedia di formulir registrasi; buat variabel kustom bila event membutuhkannya.')) ?></p>
</div>
