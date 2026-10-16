<div class="pagehead">
  <div><h2>Akun Admin Absensi</h2><p>Admin Absensi hanya dapat memindai QR dan melihat peserta event yang ditugaskan.</p></div>
  <a class="btn btn-primary" href="<?= e(url('admin/akun/baru')) ?>"><?= icon('userplus', 18) ?>Buat akun</a>
</div>
<?php if (!$admins): ?>
  <div class="card"><div class="empty"><span class="eic"><?= icon('shield', 26) ?></span><h3>Belum ada akun Admin Absensi</h3><p>Buat akun untuk petugas yang akan memindai QR di lokasi.</p><a class="btn btn-primary" href="<?= e(url('admin/akun/baru')) ?>">Buat akun</a></div></div>
<?php else: ?>
  <div class="tblw only-wide">
    <table class="tbl">
      <thead><tr><th>Nama</th><th>Username</th><th>Event</th><th>Status</th><th class="r">Total scan</th><th>Login terakhir</th></tr></thead>
      <tbody>
      <?php foreach ($admins as $a): ?>
        <tr>
          <td><a class="nm" href="<?= e(url('admin/akun/' . $a['id'])) ?>"><?= e($a['name']) ?></a></td>
          <td class="mono">@<?= e($a['username']) ?></td>
          <td style="white-space:normal"><?= e($a['event_name'] ?? 'Belum ditugaskan') ?></td>
          <td><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><i></i><?= e($a['status']) ?></span><?php if ((int) $a['must_change_password'] === 1): ?> <span class="bdg bdg-warn">Password sementara</span><?php endif; ?></td>
          <td class="r num"><?= (int) $a['scan_count'] ?></td>
          <td><?= e(datetime_id($a['last_login_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="list only-narrow">
    <?php foreach ($admins as $a): ?>
      <a class="pc" href="<?= e(url('admin/akun/' . $a['id'])) ?>"><span class="av t"><?= e(initials($a['name'])) ?></span><span class="who"><b><?= e($a['name']) ?></b><span>@<?= e($a['username']) ?> · <?= (int) $a['scan_count'] ?> scan</span></span><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><?= e($a['status']) ?></span></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
