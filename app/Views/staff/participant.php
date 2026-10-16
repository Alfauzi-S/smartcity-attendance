<?php use App\Core\View; ?>
<?= View::partial('participant_info', ['p' => $p]) ?>
<?php if ($p['status'] === 'belum'): ?>
  <div class="banner warn"><?= icon('clock') ?><div class="grow"><b>Belum diverifikasi</b>Pindai QR peserta ini untuk mencatat kehadiran. Jika QR tidak tersedia, gunakan pencarian manual di halaman Scan.</div></div>
  <a class="btn btn-primary block" href="<?= e(url('petugas/scan')) ?>"><?= icon('scan') ?>Buka scanner</a>
<?php elseif ($p['status'] === 'tidak'): ?>
  <div class="banner lock"><?= icon('lock') ?><div class="grow"><b>Ditetapkan Tidak Hadir</b>Status ini ditetapkan Super Admin dan tidak dapat diubah lewat scan.</div></div>
<?php endif; ?>
