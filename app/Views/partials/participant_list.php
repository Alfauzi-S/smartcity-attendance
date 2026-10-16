<?php
use App\Models\Participant;

$super = !empty($super);
?>
<?php if (!$list): ?>
  <div class="card"><div class="empty"><span class="eic"><?= icon('search', 26) ?></span>
    <h3><?= e(($q ?? '') !== '' || ($st ?? 'all') !== 'all' ? t('Tidak ada peserta yang cocok') : t('Belum ada peserta')) ?></h3>
    <p><?= e(($q ?? '') !== '' ? t('Periksa ejaan nama atau nomor identitas, atau ubah filter status.') : t('Peserta muncul di sini setelah mendaftar lewat tautan registrasi.')) ?></p>
  </div></div>
<?php else: ?>
  <div class="tblw only-wide">
    <table class="tbl">
      <thead><tr><th><?= e(t('Nama lengkap')) ?></th><th><?= e(t('No. identitas')) ?></th><th><?= e(t('Program studi')) ?></th><th><?= e(t('Institusi')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Waktu hadir')) ?></th><?php if ($super): ?><th class="r"><?= e(t('Aksi')) ?></th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach ($list as $p): ?>
        <tr>
          <td><a class="nm" href="<?= e(url($base . $p['id'])) ?>"><?= e($p['full_name']) ?></a><div class="small muted"><?= e(t($p['profession'])) ?></div></td>
          <td class="mono"><?= e($p['identity_number']) ?></td>
          <td><?= e(t($p['field_of_study'])) ?></td>
          <td style="white-space:normal;min-width:160px"><?= e($p['institution']) ?></td>
          <td><?= status_badge($p['status']) ?></td>
          <td class="mono"><?= $p['status'] === 'hadir' ? e(time_only($p['checked_in_at'])) . '<div class="small muted" style="font-family:var(--f-body)">' . e(['scan' => t('Scan QR'), 'cari' => t('Input manual')][$p['method']] ?? t('Manual')) . '</div>' : '—' ?></td>
          <?php if ($super): ?><td class="r"><a class="btn btn-outline sm" href="<?= e(url($base . $p['id'], ['ubah' => 'status', 'dari' => 'list'])) ?>"><?= icon('edit', 16) ?><?= e(t('Ubah status')) ?></a></td><?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="list only-narrow">
    <?php foreach ($list as $p): ?>
      <a class="pc" href="<?= e(url($base . $p['id'])) ?>">
        <span class="av<?= $p['status'] === 'hadir' ? ' t' : '' ?>"><?= e(initials($p['full_name'])) ?></span>
        <span class="who"><b><?= e($p['full_name']) ?></b><span class="mono"><?= e($p['identity_number']) ?></span> <span>· <?= e(t($p['field_of_study'])) ?></span></span>
        <span style="display:flex;flex-direction:column;align-items:flex-end;gap:4px"><?= status_badge($p['status']) ?><?php if ($p['status'] === 'hadir'): ?><span class="small muted mono"><?= e(time_only($p['checked_in_at'])) ?></span><?php endif; ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
