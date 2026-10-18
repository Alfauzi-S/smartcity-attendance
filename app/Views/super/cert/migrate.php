<div class="card"><div class="empty"><span class="eic"><?= icon('refresh', 26) ?></span>
  <h3><?= e(t('Fitur sertifikat belum aktif')) ?></h3>
  <p><?= e($isSuper ? t('Jalankan migrasi database dari Dashboard untuk membuat tabel sertifikat. Data lama tidak diubah.') : t('Minta Super Admin menjalankan pembaruan database terlebih dahulu.')) ?></p>
  <?php if ($isSuper): ?><a class="btn btn-primary" href="<?= e(url('admin')) ?>"><?= e(t('Buka Dashboard')) ?></a><?php endif; ?>
</div></div>
