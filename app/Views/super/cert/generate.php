<?php
use App\Core\View;

$eid = (int) $event['id'];
$jobLabels = ['queued' => ['Menunggu', 'mute'], 'running' => ['Berjalan', 'info'], 'paused' => ['Dijeda', 'warn'], 'done' => ['Selesai', 'ok'], 'cancelled' => ['Dibatalkan', 'mute']];
?>
<?= View::fetch('super/cert/_tabs', compact('event', 'events', 'tab')) ?>
<div class="card">
  <div class="card-h"><h3><?= e(t('Laporan validasi')) ?></h3><span class="bdg <?= $ready['ok'] ? 'bdg-ok' : 'bdg-bad' ?>"><i></i><?= e($ready['ok'] ? t('Siap generate') : t('Belum lengkap')) ?></span></div>
  <?= View::fetch('super/cert/_checks', ['checks' => $ready['checks']]) ?>
</div>
<?= View::fetch('super/cert/_summary', ['summary' => $summary]) ?>
<form method="post" action="<?= e(url('admin/sertifikat/generate')) ?>" class="card" data-confirm="<?= e(t('Generate :n sertifikat sekarang? Nomor final dialokasikan dan dicatat permanen. Sertifikat belum terlihat publik sampai dipublikasikan.', ['n' => $summary['to_generate']])) ?>">
  <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $eid ?>"><input type="hidden" name="mode" value="all">
  <div class="card-h"><h3><?= e(t('Generate massal')) ?></h3></div>
  <p class="small muted"><?= e(t('Memproses semua peserta yang layak dan belum punya sertifikat, bertahap per :n peserta. Halaman progres boleh ditutup; proses dapat dilanjutkan tanpa menggandakan sertifikat.', ['n' => config('cert.batch_size', 10)])) ?></p>
  <div class="row">
    <button class="btn btn-primary" type="submit"<?= $ready['ok'] && $summary['to_generate'] > 0 ? '' : ' disabled' ?>><?= icon('award', 18) ?><?= e(t('Generate :n sertifikat', ['n' => $summary['to_generate']])) ?></button>
    <a class="btn btn-outline" href="<?= e(url('admin/sertifikat/kelayakan', ['ev' => $eid])) ?>"><?= e(t('Tinjau / pilih penerima')) ?></a>
  </div>
</form>
<div class="card">
  <div class="card-h"><h3><?= e(t('Riwayat proses generate')) ?></h3></div>
  <?php if (!$jobs): ?><p class="small muted"><?= e(t('Belum ada proses generate.')) ?></p><?php else: ?>
  <div class="tblw"><table class="tbl">
    <thead><tr><th>#</th><th><?= e(t('Status')) ?></th><th class="r"><?= e(t('Berhasil')) ?></th><th class="r"><?= e(t('Dilewati')) ?></th><th class="r"><?= e(t('Gagal')) ?></th><th class="r"><?= e(t('Total')) ?></th><th><?= e(t('Dimulai')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($jobs as $j): [$l, $tone] = $jobLabels[$j['status']] ?? [$j['status'], 'mute']; ?>
      <tr>
        <td><a class="nm" href="<?= e(url('admin/sertifikat/job/' . $j['id'])) ?>">#<?= (int) $j['id'] ?></a></td>
        <td><span class="bdg bdg-<?= $tone ?>"><i></i><?= e(t($l)) ?></span> <span class="small muted"><?= (int) $j['cursor_pos'] ?>/<?= (int) $j['total'] ?></span></td>
        <td class="r num"><?= (int) $j['success'] ?></td><td class="r num"><?= (int) $j['skipped'] ?></td><td class="r num"><?= (int) $j['failed'] ?></td><td class="r num"><?= (int) $j['total'] ?></td>
        <td class="small"><?= e(datetime_id($j['created_at'])) ?> · @<?= e($j['created_by_name'] ?? '—') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
