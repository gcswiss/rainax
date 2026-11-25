<?php
declare(strict_types=1);

$tm = $c['team'] ?? [];
page_hero('Team', $tm['titel'] ?? '', $tm['intro'] ?? '', 'ochre');
?>
<section class="section">
    <div class="wrap">
        <div class="team">
            <?php foreach (($tm['mitglieder'] ?? []) as $i => $m): ?>
                <article class="member tone-<?= $i % 4 ?> reveal" style="--d:<?= ($i % 4) * 80 ?>ms">
                    <div class="member__photo">
                        <?php if (!empty($m['foto'])): ?>
                            <img src="<?= e(asset($m['foto'])) ?>" alt="<?= e($m['name'] ?? '') ?>" loading="lazy">
                        <?php else: ?>
                            <span class="member__initials" aria-hidden="true"><?= e(initials($m['name'] ?? '')) ?></span>
                        <?php endif; ?>
                    </div>
                    <h2 class="member__name"><?= e($m['name'] ?? '') ?></h2>
                    <p class="member__role"><?= e($m['rolle'] ?? '') ?></p>
                    <?php if ($d = lines($m['details'] ?? '')): ?>
                        <ul class="member__details"><?php foreach ($d as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--cream">
    <div class="wrap join reveal">
        <?= icon('heart-fill', 'join__icon') ?>
        <h2>Möchtest du Teil unseres Teams werden?</h2>
        <a class="btn btn--green" href="<?= e(url('jobs/')) ?>">Offene Stellen <?= icon('arrow') ?></a>
    </div>
</section>
