<?php
declare(strict_types=1);

$an = $c['angebot'] ?? [];
$a  = $c['allgemein'] ?? [];
$extraIcons = ['file', 'heart', 'star', 'sun', 'leaf'];
page_hero('Angebot', $an['titel'] ?? '', $an['intro'] ?? '', 'sky');
?>
<section class="section">
    <div class="wrap">
        <?= section_head('Eingewöhnung', $an['eingewoehnung_titel'] ?? '', $an['eingewoehnung_text'] ?? '') ?>
        <ol class="timeline">
            <?php foreach (($an['phasen'] ?? []) as $i => $p): ?>
                <li class="timeline__step tone-<?= $i % 4 ?> reveal" style="--d:<?= $i * 90 ?>ms">
                    <span class="timeline__num"><?= $i + 1 ?></span>
                    <h3><?= e($p['titel'] ?? '') ?></h3>
                    <?= paragraphs($p['text'] ?? '') ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<?php if (!empty($an['extras'])): ?>
<section class="section section--cream">
    <div class="wrap">
        <?= section_head('Begleitung', 'Wir begleiten euer Kind') ?>
        <div class="cards3">
            <?php foreach ($an['extras'] as $i => $x): ?>
                <article class="card tone-<?= ($i + 1) % 4 ?> reveal" style="--d:<?= $i * 90 ?>ms">
                    <span class="value__icon"><?= icon($extraIcons[$i % 5]) ?></span>
                    <h3><?= e($x['titel'] ?? '') ?></h3>
                    <?= paragraphs($x['text'] ?? '') ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section" id="oeffnungszeiten">
    <div class="wrap hours reveal">
        <div class="hours__clock">
            <?= rainbow('hours__rainbow') ?>
            <p class="hours__days"><?= e($a['oeffnungszeiten_tage'] ?? '') ?></p>
            <p class="hours__time"><?= e($a['oeffnungszeiten_zeit'] ?? '') ?></p>
        </div>
        <div>
            <p class="kicker">Ferien</p>
            <h2><?= line($an['zeiten_titel'] ?? '') ?></h2>
            <?= paragraphs($an['zeiten_text'] ?? '', 'lead') ?>
            <?php if (!empty($an['ferien_pdf'])): ?>
                <a class="btn btn--ghost" href="<?= e(asset($an['ferien_pdf'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?> <?= e($an['ferien_pdf_titel'] ?: 'Ferienplan (PDF)') ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/_cta.php'; ?>
