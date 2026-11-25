<?php
declare(strict_types=1);

$t  = $c['tarife'] ?? [];
$ps = num($t['preis_saeugling'] ?? '0');
$pk = num($t['preis_kleinkind'] ?? '0');
$f  = num($t['faktor'] ?? '4.2') ?: 4.2;
$vIcons = ['moon', 'kids', 'percent', 'star'];
page_hero('Tarife', $t['titel'] ?? '', $t['intro'] ?? '', 'green');
?>
<section class="section">
    <div class="wrap pricing">
        <div class="calc reveal" data-calc data-saeugling="<?= e((string) $ps) ?>" data-kleinkind="<?= e((string) $pk) ?>" data-faktor="<?= e((string) $f) ?>">
            <div class="calc__head">
                <?= icon('calc') ?>
                <h2>Tarifrechner</h2>
            </div>
            <fieldset class="segmented">
                <legend>Alter des Kindes</legend>
                <label><input type="radio" name="alter" value="kleinkind" checked><span>Kleinkind<small>ab 19 Monate</small></span></label>
                <label><input type="radio" name="alter" value="saeugling"><span>Säugling<small>bis 18 Monate</small></span></label>
            </fieldset>
            <fieldset class="days">
                <legend>Ganze Tage pro Woche</legend>
                <div class="days__row">
                    <?php for ($d = 1; $d <= 5; $d++): ?>
                        <label><input type="radio" name="tage" value="<?= $d ?>"<?= $d === 2 ? ' checked' : '' ?>><span><?= $d ?></span></label>
                    <?php endfor; ?>
                </div>
            </fieldset>
            <label class="toggle"><input type="checkbox" name="geschwister"><span class="toggle__ui"></span> Geschwisterrabatt (10 %)</label>
            <div class="calc__result" aria-live="polite">
                <span class="calc__label">pro Monat</span>
                <span class="calc__price">CHF <strong data-out-month><?= chf($pk * 2 * $f) ?></strong></span>
                <span class="calc__day" data-out-day>CHF <?= chf($pk, false) ?> pro Tag</span>
            </div>
            <p class="calc__note"><?= e($t['empfehlung'] ?? '') ?></p>
        </div>

        <div class="price-table reveal" style="--d:120ms">
            <table>
                <caption class="sr-only">Monatstarife nach Anzahl Tagen</caption>
                <thead>
                    <tr><th scope="col">Tage / Woche</th><th scope="col">Säuglinge<small><?= e($t['label_saeugling'] ?? '') ?></small></th><th scope="col">Kleinkinder<small><?= e($t['label_kleinkind'] ?? '') ?></small></th></tr>
                </thead>
                <tbody>
                    <?php for ($d = 1; $d <= 5; $d++): ?>
                        <tr><th scope="row"><?= $d ?> <?= $d === 1 ? 'Tag' : 'Tage' ?></th><td>CHF <?= chf($ps * $d * $f) ?></td><td>CHF <?= chf($pk * $d * $f) ?></td></tr>
                    <?php endfor; ?>
                    <tr class="price-table__extra"><th scope="row">Zusatztag</th><td>CHF <?= chf($ps, false) ?></td><td>CHF <?= chf($pk, false) ?></td></tr>
                </tbody>
            </table>
            <p class="price-table__note">Monatstarif = Tagespreis × Tage × <?= e(rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.')) ?> Wochen</p>

            <?php if (!empty($t['halbtage'])): ?>
            <h3 class="price-table__title">Halbtage</h3>
            <table>
                <tbody>
                    <?php foreach ($t['halbtage'] as $h): ?>
                        <tr><th scope="row"><?= e($h['titel'] ?? '') ?><small><?= e($h['zeit'] ?? '') ?></small></th><td>CHF <?= chf(num($h['saeugling'] ?? '0'), false) ?></td><td>CHF <?= chf(num($h['kleinkind'] ?? '0'), false) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="price-table__note"><?= e($t['mindestbetreuung'] ?? '') ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="wrap">
        <div class="subsidy reveal">
            <div class="subsidy__icon"><?= icon('percent') ?></div>
            <div>
                <p class="kicker">Stadt Dietikon</p>
                <h2><?= line($t['subvention_titel'] ?? '') ?></h2>
                <?= paragraphs($t['subvention_text'] ?? '') ?>
            </div>
            <?php if (!empty($t['subvention_link'])): ?>
                <a class="btn btn--sun" href="<?= e($t['subvention_link']) ?>" target="_blank" rel="noopener"><?= e($t['subvention_button'] ?: 'Zum Rechner') ?> <?= icon('arrow') ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!empty($t['vorteile'])): ?>
<section class="section section--cream">
    <div class="wrap">
        <?= section_head('Extras', $t['vorteile_titel'] ?? 'Das Plus für unsere Familien') ?>
        <div class="cards3">
            <?php foreach ($t['vorteile'] as $i => $v): ?>
                <article class="card perk tone-<?= $i % 4 ?> reveal" style="--d:<?= $i * 90 ?>ms">
                    <span class="value__icon"><?= icon($vIcons[$i % 4]) ?></span>
                    <h3><?= e($v['titel'] ?? '') ?></h3>
                    <?= paragraphs($v['text'] ?? '') ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/_cta.php'; ?>
