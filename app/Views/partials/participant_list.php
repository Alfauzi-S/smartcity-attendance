<?php
use App\Models\Participant;

$super = !empty($super);
?>
<?php if (!$list): ?>
  <div class="card"><div class="empty"><span class="eic"><?= icon('search', 26) ?></span>
    <h3><?= ($q ?? '') !== '' || ($st ?? 'all') !== 'all' ? 'Tidak ada peserta yang cocok' : 'Belum ada peserta' ?></h3>
    <p><?= ($q ?? '') !== '' ? 'Periksa ejaan nama atau nomor identitas, atau ubah filter status.' : 'Peserta muncul di sini setelah mendaftar lewat tautan registrasi.' ?></p>
  </div></div>
<?php else: ?>
  <div class="tblw only-wide">
    <table class="tbl">
      <thead><tr><th>Nama lengkap</th><th>No. identitas</th><th>Program studi</th><th>Institusi</th><th>Status</th><th>Waktu hadir</th><?php if ($super): ?><th class="r">Aksi</th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach ($list as $p): ?>
        <tr>
          <td><a class="nm" href="<?= e(url($base . $p['id'])) ?>"><?= e($p['full_name']) ?></a><div class="small muted"><?= e($p['profession']) ?></div></td>
          <td class="mono"><?= e($p['identity_number']) ?></td>
          <td><?= e($p['field_of_study']) ?></td>
          <td style="white-space:normal;min-width:160px"><?= e($p['institution']) ?></td>
          <td><?= status_badge($p['status']) ?></td>
          <td class="mono"><?= $p['status'] === 'hadir' ? e(time_only($p['checked_in_at'])) . '<div class="small muted" style="font-family:var(--f-body)">' . e(['scan' => 'Scan QR', 'cari' => 'Input manual'][$p['method']] ?? 'Manual') . '</div>' : '—' ?></td>
          <?php if ($super): ?><td class="r"><a class="btn btn-outline sm" href="<?= e(url($base . $p['id'], ['ubah' => 'status', 'dari' => 'list'])) ?>"><?= icon('edit', 16) ?>Ubah status</a></td><?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="list only-narrow">
    <?php foreach ($list as $p): ?>
      <a class="pc" href="<?= e(url($base . $p['id'])) ?>">
        <span class="av<?= $p['status'] === 'hadir' ? ' t' : '' ?>"><?= e(initials($p['full_name'])) ?></span>
        <span class="who"><b><?= e($p['full_name']) ?></b><span class="mono"><?= e($p['identity_number']) ?></span> <span>· <?= e($p['field_of_study']) ?></span></span>
        <span style="display:flex;flex-direction:column;align-items:flex-end;gap:4px"><?= status_badge($p['status']) ?><?php if ($p['status'] === 'hadir'): ?><span class="small muted mono"><?= e(time_only($p['checked_in_at'])) ?></span><?php endif; ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
