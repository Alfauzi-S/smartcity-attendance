<?php
// Select berkelompok + kolom "Lainnya" (diisi sendiri). Nilai di luar daftar dianggap isian "Lainnya".
use App\Models\ParticipantOptions as Opt;

$flat = array_merge(...array_values($groups));
$value = (string) ($value ?? '');
$custom = $value !== '' && $value !== Opt::OTHER && !in_array($value, $flat, true);
$sel = $custom ? Opt::OTHER : $value;
$otherId = $name . '_other';
?>
<select class="inp" id="<?= e($name) ?>" name="<?= e($name) ?>" data-other="<?= e($otherId) ?>"<?= !empty($required) ? ' required' : '' ?><?= $aria ?? '' ?>>
  <?php if (!empty($placeholder)): ?><option value=""><?= e($placeholder) ?></option><?php endif; ?>
  <?php foreach ($groups as $label => $items): ?>
  <optgroup label="<?= e(t($label)) ?>">
    <?php foreach ($items as $o): ?><option value="<?= e($o) ?>"<?= selected($sel, $o) ?>><?= e(t($o)) ?></option><?php endforeach; ?>
  </optgroup>
  <?php endforeach; ?>
  <option value="<?= e(Opt::OTHER) ?>"<?= selected($sel, Opt::OTHER) ?>><?= e(t('Lainnya (tulis sendiri)')) ?></option>
</select>
<div class="other-fld" id="<?= e($otherId) ?>_wrap"<?= $sel === Opt::OTHER ? '' : ' hidden' ?>>
  <label class="sr" for="<?= e($otherId) ?>"><?= e($otherLabel) ?></label>
  <input class="inp" id="<?= e($otherId) ?>" name="<?= e($otherId) ?>" maxlength="<?= (int) $max ?>" value="<?= $custom ? e($value) : '' ?>" placeholder="<?= e($otherLabel) ?>">
</div>
