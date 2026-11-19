<?php
declare(strict_types=1);

$s = $c['start'] ?? [];
$a = $c['allgemein'] ?? [];
$t = $c['tarife'] ?? [];
$teamMembers = $c['team']['mitglieder'] ?? [];
$titleLines = preg_split('/(?<=[.!?])\s+/u', trim($s['hero_titel'] ?? '')) ?: [];
$werteIcons = ['heart', 'kids', 'leaf', 'star'];
$minPreis = min(num($t['preis_kleinkind'] ?? '0'), num($t['preis_saeugling'] ?? '0'));
?>
<section class="hero">
    <div class="wrap hero__grid">
        <div class="hero__text">
            <p class="kicker reveal"><?= e($s['hero_kicker'] ?? '') ?></p>
            <h1 class="hero__title">
                <?php foreach ($titleLines as $i => $l): ?>
                    <span class="hero__line reveal" style="--d:<?= $i * 120 ?>ms"><?php if ($i === 1): ?><em><?= e($l) ?><?= squiggle() ?></em><?php else: ?><?= e($l) ?><?php endif; ?></span>
                <?php endforeach; ?>
            </h1>
            <div class="hero__lead reveal"><?= paragraphs($s['hero_text'] ?? '') ?></div>
            <div class="hero__actions reveal">
                <a class="btn btn--green" href="<?= e(url('kontakt/')) ?>">Besichtigung buchen <?= icon('arrow') ?></a>
                <a class="btn btn--ghost" href="<?= e(url('tarife/')) ?>">Tarife ansehen</a>
            </div>
        </div>
        <div class="hero__visual reveal">
            <?= rainbow('hero__rainbow') ?>
            <div class="arch hero__arch">
                <?php if (!empty($s['hero_video'])): ?>
                    <video autoplay muted loop playsinline preload="metadata" poster="<?= e(asset($s['hero_bild'] ?? '')) ?>">
                        <source src="<?= e(asset($s['hero_video'])) ?>" type="video/mp4">
                    </video>
                <?php elseif (!empty($s['hero_bild'])): ?>
                    <img src="<?= e(asset($s['hero_bild'])) ?>" alt="" fetchpriority="high">
                <?php endif; ?>
            </div>
            <div class="badge" aria-hidden="true">
                <svg viewBox="0 0 120 120"><defs><path id="kreis" d="M60 60m-44 0a44 44 0 1 1 88 0a44 44 0 1 1-88 0"/></defs><text><textPath href="#kreis" textLength="272" lengthAdjust="spacing">Kita Farbewält · Dietikon · mit Herz ·</textPath></text></svg>
                <?= icon('heart', 'badge__heart') ?>
            </div>
            <div class="sticker">
                <?= icon('clock') ?>
                <span><small><?= e($a['oeffnungszeiten_tage'] ?? '') ?></small><strong><?= e($a['oeffnungszeiten_zeit'] ?? '') ?></strong></span>
            </div>
            <?= dots('hero__dots') ?>
        </div>
    </div>
</section>

<?php if (!empty($s['fakten'])): ?>
<section class="facts" aria-label="Kurz und knapp">
    <div class="wrap facts__row">
        <?php foreach ($s['fakten'] as $i => $f): ?>
            <div class="fact reveal" style="--d:<?= $i * 80 ?>ms">
                <span class="fact__num"><?= e($f['zahl'] ?? '') ?></span>
                <span class="fact__text"><?= e($f['text'] ?? '') ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="wrap">
        <?= section_head('Unsere Haltung', $s['werte_titel'] ?? '') ?>
        <div class="values">
            <?php foreach (($s['werte'] ?? []) as $i => $w): ?>
                <article class="value tone-<?= $i % 4 ?> reveal" style="--d:<?= $i * 90 ?>ms">
                    <span class="value__icon"><?= icon($werteIcons[$i % 4]) ?></span>
                    <h3><?= e($w['titel'] ?? '') ?></h3>
                    <?= paragraphs($w['text'] ?? '') ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($s['alltag'])): ?>
<section class="section section--sky alltag">
    <div class="wrap">
        <?= section_head('Kita-Alltag', $s['alltag_titel'] ?? '', $s['alltag_text'] ?? '') ?>
    </div>
    <div class="reels" data-reels>
        <?php foreach ($s['alltag'] as $i => $r): ?>
            <figure class="reel reveal" style="--d:<?= $i * 90 ?>ms">
                <div class="reel__media">
                    <?php if (!empty($r['video'])): ?>
                        <video muted loop playsinline preload="none" poster="<?= e(asset($r['bild'] ?? '')) ?>" data-autoplay>
                            <source src="<?= e(asset($r['video'])) ?>" type="video/mp4">
                        </video>
                        <span class="reel__play"><?= icon('play') ?></span>
                    <?php elseif (!empty($r['bild'])): ?>
                        <img src="<?= e(asset($r['bild'])) ?>" alt="<?= e($r['titel'] ?? '') ?>" loading="lazy">
                    <?php endif; ?>
                </div>
                <figcaption><?= e($r['titel'] ?? '') ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($a['instagram'])): ?>
        <p class="center reveal"><a class="btn btn--ghost" href="<?= e($a['instagram']) ?>" target="_blank" rel="noopener"><?= icon('insta') ?> Mehr auf Instagram</a></p>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="section">
    <div class="wrap teasers">
        <a class="teaser teaser--price reveal" href="<?= e(url('tarife/')) ?>">
            <p class="kicker">Tarife</p>
            <p class="teaser__big"><small>ab CHF</small> <?= chf($minPreis, false) ?><small>/ Tag</small></p>
            <ul class="chips">
                <?php foreach (($t['vorteile'] ?? []) as $v): ?><li><?= e($v['titel'] ?? '') ?></li><?php endforeach; ?>
                <li>Subventionierte Plätze</li>
            </ul>
            <span class="teaser__more">Tarife & Rechner <?= icon('arrow') ?></span>
        </a>
        <a class="teaser teaser--concept reveal" href="<?= e(url('paedagogik/')) ?>" style="--d:100ms">
            <p class="kicker">Pädagogik</p>
            <h3>Jedes Kind ist einzigartig.</h3>
            <p>Akzeptanz, Wärme und Wertschätzung – kurz und bündig erklärt.</p>
            <span class="teaser__more">Unser Konzept <?= icon('arrow') ?></span>
            <?= rainbow('teaser__rainbow') ?>
        </a>
        <a class="teaser teaser--team reveal" href="<?= e(url('team/')) ?>" style="--d:200ms">
            <p class="kicker">Team</p>
            <h3>Das Herz unserer Kita</h3>
            <div class="avatars">
                <?php foreach (array_slice($teamMembers, 0, 5) as $i => $m): ?>
                    <?php if (!empty($m['foto'])): ?>
                        <img src="<?= e(asset($m['foto'])) ?>" alt="<?= e($m['name'] ?? '') ?>" loading="lazy">
                    <?php else: ?>
                        <span class="avatar tone-<?= $i % 4 ?>"><?= e(initials($m['name'] ?? '')) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (count($teamMembers) > 5): ?><span class="avatar avatar--more">+<?= count($teamMembers) - 5 ?></span><?php endif; ?>
            </div>
            <span class="teaser__more">Team kennenlernen <?= icon('arrow') ?></span>
        </a>
    </div>
</section>

<?php require __DIR__ . '/_cta.php'; ?>
