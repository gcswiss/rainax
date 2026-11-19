<?php
declare(strict_types=1);

$cta = $c['start'] ?? [];
$k   = $c['kontakt'] ?? [];
?>
<section class="cta">
    <div class="wrap">
        <div class="cta__box reveal">
            <?= rainbow('cta__rainbow') ?>
            <div class="cta__text">
                <h2><?= line($cta['cta_titel'] ?? '') ?></h2>
                <?= paragraphs($cta['cta_text'] ?? '') ?>
                <a class="btn btn--sun" href="<?= e(url('kontakt/')) ?>">Besichtigung buchen <?= icon('arrow') ?></a>
            </div>
            <?php if (!empty($k['schritte'])): ?>
            <ol class="steps steps--light">
                <?php foreach ($k['schritte'] as $i => $st): ?>
                    <li><span><?= $i + 1 ?></span><?= e($st['titel'] ?? '') ?></li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
        </div>
    </div>
</section>
