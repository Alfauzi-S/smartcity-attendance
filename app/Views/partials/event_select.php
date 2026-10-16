<select class="inp" id="event_id" name="event_id">
  <option value="">Belum ditugaskan</option>
  <?php foreach ($events as $ev): ?><option value="<?= (int) $ev['id'] ?>"<?= selected($value, $ev['id']) ?>><?= e($ev['name']) ?> · <?= e(date_id($ev['event_date'])) ?></option><?php endforeach; ?>
</select>
