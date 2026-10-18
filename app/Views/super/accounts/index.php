<?php use App\Core\Auth; ?>
<div class="pagehead">
  <div><h2><?= e(t('Akun Admin')) ?></h2><p><?= e(t('Admin Absensi memindai QR di lokasi. Admin Event mengelola event yang ditugaskan, termasuk sertifikat, sesuai izin yang Anda berikan.')) ?></p></div>
  <div class="row">
    <a class="btn btn-outline" href="<?= e(url('admin/akun/baru', ['peran' => Auth::ADMIN])) ?>"><?= icon('userplus', 18) ?><?= e(t('Admin Absensi')) ?></a>
    <?php if ($migrated): ?><a class="btn btn-primary" href="<?= e(url('admin/akun/baru', ['peran' => Auth::EVENT])) ?>"><?= icon('userplus', 18) ?><?= e(t('Admin Event')) ?></a><?php endif; ?>
  </div>
</div>
<div class="chips">
  <?php foreach (['' => 'Semua', Auth::EVENT => 'Admin Event', Auth::ADMIN => 'Admin Absensi'] as $k => $label): ?>
    <a class="chip<?= $role === $k ? ' on' : '' ?>" href="<?= e(url('admin/akun', ['peran' => $k])) ?>"><?= e(t($label)) ?></a>
  <?php endforeach; ?>
</div>
<?php if (!$admins): ?>
  <div class="card"><div class="empty"><span class="eic"><?= icon('shield', 26) ?></span><h3><?= e(t('Belum ada akun admin')) ?></h3><p><?= e(t('Buat akun untuk petugas yang akan memindai QR di lokasi atau untuk pengelola event.')) ?></p></div></div>
<?php else: ?>
  <div class="tblw only-wide">
    <table class="tbl">
      <thead><tr><th><?= e(t('Nama')) ?></th><th><?= e(t('Username')) ?></th><th><?= e(t('Peran')) ?></th><th><?= e(t('Event')) ?></th><th><?= e(t('Status')) ?></th><th class="r"><?= e(t('Total scan')) ?></th><th><?= e(t('Login terakhir')) ?></th></tr></thead>
      <tbody>
      <?php foreach ($admins as $a): $isEv = $a['role'] === Auth::EVENT; ?>
        <tr>
          <td><a class="nm" href="<?= e(url('admin/akun/' . $a['id'])) ?>"><?= e($a['name']) ?></a></td>
          <td class="mono">@<?= e($a['username']) ?></td>
          <td><span class="bdg <?= $isEv ? 'bdg-navy' : 'bdg-teal' ?>"><?= e(Auth::roleLabel($a['role'])) ?></span></td>
          <td style="white-space:normal"><?= e($isEv ? t(':n event ditugaskan', ['n' => (int) $a['assignment_count']]) : ($a['event_name'] ?? t('Belum ditugaskan'))) ?></td>
          <td><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><i></i><?= e(t($a['status'])) ?></span><?php if ((int) $a['must_change_password'] === 1): ?> <span class="bdg bdg-warn"><?= e(t('Password sementara')) ?></span><?php endif; ?></td>
          <td class="r num"><?= (int) $a['scan_count'] ?></td>
          <td><?= e(datetime_id($a['last_login_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="list only-narrow">
    <?php foreach ($admins as $a): ?>
      <a class="pc" href="<?= e(url('admin/akun/' . $a['id'])) ?>"><span class="av t"><?= e(initials($a['name'])) ?></span><span class="who"><b><?= e($a['name']) ?></b><span>@<?= e($a['username']) ?> · <?= e(Auth::roleLabel($a['role'])) ?></span></span><span class="bdg <?= $a['status'] === 'Aktif' ? 'bdg-ok' : 'bdg-mute' ?>"><?= e(t($a['status'])) ?></span></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
