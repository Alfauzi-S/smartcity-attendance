<?php $other = lang() === 'en' ? 'id' : 'en'; ?>
<a class="btn btn-ghost sm lang-sw" href="<?= e(lang_url($other)) ?>" hreflang="<?= $other ?>" lang="<?= $other ?>" title="<?= $other === 'en' ? 'Switch to English' : 'Ganti ke Bahasa Indonesia' ?>"><?= icon('globe', 16) ?><span><?= strtoupper($other) ?></span></a>
