<?php
// Admin Event tidak dapat membuat event; tampilkan pesan penugasan sebagai gantinya.
if (!empty($cta) && $cta[0] === 'admin/event/baru' && !is_super()) {
    $heading = 'Belum ada event yang ditugaskan';
    $text = 'Minta Super Admin menugaskan akun ini ke event yang Anda kelola.';
    $cta = null;
}
?>
<div class="card"><div class="empty"><span class="eic"><?= icon('calendar', 26) ?></span>
  <h3><?= e(t($heading ?? 'Belum ada event')) ?></h3>
  <p><?= e(t($text ?? 'Buat event terlebih dahulu.')) ?></p>
  <?php if (!empty($cta)): ?><a class="btn btn-primary" href="<?= e(url($cta[0])) ?>"><?= e(t($cta[1])) ?></a><?php endif; ?>
</div></div>
