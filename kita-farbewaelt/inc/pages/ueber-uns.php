<?php
declare(strict_types=1);

$u = $c['ueber'] ?? [];
page_hero('Über uns', $u['titel'] ?? '', $u['intro'] ?? '', 'green');
?>
<section class="section">
    <div class="wrap split">
        <div class="split__text reveal">
            <div class="prose"><?= paragraphs($u['text'] ?? '') ?></div>
            <a class="btn btn--green" href="<?= e(url('kontakt/')) ?>">Zur Platzanfrage <?= icon('arrow') ?></a>
        </div>
        <div class="split__media reveal">
            <?php $first = $u['raeume'][0]['bild'] ?? ($u['galerie'][0]['bild'] ?? ''); ?>
            <?php if ($first !== ''): ?><div class="arch arch--tilt"><img src="<?= e(asset($first)) ?>" alt="" loading="lazy"></div><?php endif; ?>
            <?= dots('split__dots') ?>
        </div>
    </div>
</section>

<?php if (!empty($u['raeume'])): ?>
<section class="section section--cream" id="raeume">
    <div class="wrap">
        <?= section_head('Räume', $u['raeume_titel'] ?? '', $u['raeume_text'] ?? '') ?>
        <div class="rooms">
            <?php foreach ($u['raeume'] as $i => $r): ?>
                <article class="room tone-<?= $i % 4 ?> reveal" style="--d:<?= ($i % 2) * 100 ?>ms">
                    <?php if (!empty($r['bild'])): ?>
                        <div class="room__img"><img src="<?= e(asset($r['bild'])) ?>" alt="<?= e($r['titel'] ?? '') ?>" loading="lazy"></div>
                    <?php endif; ?>
                    <div class="room__body">
                        <h3><?= e($r['titel'] ?? '') ?></h3>
                        <?= paragraphs($r['text'] ?? '') ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($u['galerie'])): ?>
<section class="section" id="galerie">
    <div class="wrap">
        <?= section_head('Einblicke', $u['galerie_titel'] ?? '', $u['galerie_hinweis'] ?? '') ?>
        <div class="gallery" data-gallery>
            <?php foreach ($u['galerie'] as $i => $g): ?>
                <?php if (empty($g['bild'])) { continue; } ?>
                <a class="gallery__item reveal" href="<?= e(asset($g['bild'])) ?>" data-caption="<?= e($g['text'] ?? '') ?>" style="--d:<?= ($i % 4) * 70 ?>ms">
                    <img src="<?= e(asset($g['bild'])) ?>" alt="<?= e($g['text'] ?? '') ?>" loading="lazy">
                    <?php if (!empty($g['text'])): ?><span><?= e($g['text']) ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section section--sky" id="partnerschaft">
    <div class="wrap partner reveal">
        <div>
            <p class="kicker">Partnerschaft</p>
            <h2><?= line($u['partner_titel'] ?? '') ?></h2>
            <?= paragraphs($u['partner_text'] ?? '', 'lead') ?>
        </div>
        <a class="partner__card" href="<?= e($u['partner_link'] ?? '#') ?>" target="_blank" rel="noopener">
            <?php if (!empty($u['partner_logo'])): ?><img src="<?= e(asset($u['partner_logo'])) ?>" alt="<?= e($u['partner_name'] ?? '') ?>" loading="lazy"><?php endif; ?>
            <strong><?= e($u['partner_name'] ?? '') ?></strong>
            <span><?= e($u['partner_adresse'] ?? '') ?></span>
            <span class="teaser__more">Webseite besuchen <?= icon('arrow') ?></span>
        </a>
    </div>
</section>

<?php require __DIR__ . '/_cta.php'; ?>
