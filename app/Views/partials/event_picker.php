<?php if (!empty($events) && count($events) > 1): ?>
<form method="get" action="<?= e(url($path)) ?>" class="ev-pick" data-autosubmit>
  <label for="ev" class="small muted">Event</label>
  <select class="inp" name="ev" id="ev">
    <?php foreach ($events as $ev): ?><option value="<?= (int) $ev['id'] ?>"<?= selected($event['id'] ?? '', $ev['id']) ?>><?= e($ev['name']) ?> · <?= e(date_id($ev['event_date'])) ?></option><?php endforeach; ?>
  </select>
  <noscript><button class="btn btn-outline sm">Pilih</button></noscript>
</form>
<?php endif; ?>
