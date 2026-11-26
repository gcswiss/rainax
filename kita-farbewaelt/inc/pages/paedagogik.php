<?php
declare(strict_types=1);

$p = $c['paedagogik'] ?? [];
$themeIcons = ['spoon', 'leaf', 'moon'];
$grundIcons = ['heart', 'target', 'kids'];
page_hero('Pädagogik', $p['titel'] ?? '', $p['intro'] ?? '', 'yellow');
?>
<section class="section">
    <div class="wrap">
        <div class="cards3">
            <?php foreach (($p['grundsaetze'] ?? []) as $i => $g): ?>
                <article class="value tone-<?= $i % 4 ?> reveal" style="--d:<?= $i * 100 ?>ms">
                    <span class="value__icon"><?= icon($grundIcons[$i % 3]) ?></span>
                    <h2 class="value__title"><?= e($g['titel'] ?? '') ?></h2>
                    <?= paragraphs($g['text'] ?? '') ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($p['themen'])): ?>
<section class="section section--cream">
    <div class="wrap">
        <?= section_head('Im Alltag', 'Was uns wichtig ist') ?>
        <div class="themes">
            <?php foreach ($p['themen'] as $i => $th): ?>
                <article class="theme reveal" style="--d:<?= $i * 100 ?>ms">
                    <?php if (!empty($th['bild'])): ?><div class="arch theme__img"><img src="<?= e(asset($th['bild'])) ?>" alt="" loading="lazy"></div><?php endif; ?>
                    <span class="theme__icon tone-<?= $i % 4 ?>"><?= icon($themeIcons[$i % 3]) ?></span>
                    <h3><?= e($th['titel'] ?? '') ?></h3>
                    <?= paragraphs($th['text'] ?? '') ?>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="center reveal"><a class="btn btn--green" href="<?= e(url('kontakt/')) ?>">Mehr erfahren bei einer Besichtigung <?= icon('arrow') ?></a></p>
    </div>
</section>
<?php endif; ?>
