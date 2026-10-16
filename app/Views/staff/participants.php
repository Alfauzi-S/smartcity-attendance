<?php use App\Core\View; ?>
<?php if (!$event): ?>
  <?= View::partial('no_event', ['heading' => t('Akun belum ditugaskan ke event'), 'text' => t('Minta Super Admin menugaskan akun ini ke sebuah event.')]) ?>
<?php else: ?>
  <div class="pagehead"><div><h2><?= e(t('Daftar peserta')) ?></h2><p><?= e($event['name']) ?> · <?= e(t(':n hadir dari :total', ['n' => (int) $stats['hadir'], 'total' => (int) $stats['total']])) ?></p></div></div>
  <?= View::partial('filters', ['path' => 'petugas/peserta', 'q' => $q, 'st' => $st, 'stats' => $stats]) ?>
  <?= View::partial('participant_list', ['list' => $list, 'base' => 'petugas/peserta/', 'q' => $q, 'st' => $st]) ?>
  <?= View::partial('pager', ['page' => $page, 'pages' => $pages, 'total' => $total, 'path' => 'petugas/peserta', 'query' => ['q' => $q, 'st' => $st]]) ?>
  <p class="small muted"><?= icon('lock', 14) ?> <?= e(t('Admin Absensi hanya dapat melihat data. Perubahan status manual dilakukan oleh Super Admin.')) ?></p>
<?php endif; ?>
