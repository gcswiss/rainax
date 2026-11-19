<?php
declare(strict_types=1);

$k = $c['kontakt'] ?? [];
$a = $c['allgemein'] ?? [];
$status = $_GET['status'] ?? '';
page_hero('Kontakt', $k['titel'] ?? '', $k['intro'] ?? '', 'sky');
?>
<section class="section">
    <div class="wrap">
        <?php if (!empty($k['schritte'])): ?>
            <ol class="steps reveal">
                <?php foreach ($k['schritte'] as $i => $st): ?>
                    <li><span><?= $i + 1 ?></span><?= e($st['titel'] ?? '') ?></li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <div class="contact">
            <form class="form reveal" id="formular" action="<?= e(url('anfrage.php')) ?>" method="post" data-form novalidate>
                <h2>Anfrage senden</h2>
                <?php if ($status === 'gesendet'): ?>
                    <p class="form__msg form__msg--ok"><?= e($k['danke'] ?? '') ?></p>
                <?php elseif ($status === 'fehler'): ?>
                    <p class="form__msg form__msg--err">Das hat leider nicht geklappt. Bitte prüft die Angaben oder ruft uns an.</p>
                <?php endif; ?>
                <div class="form__msg" data-form-msg hidden></div>

                <fieldset class="segmented segmented--wide">
                    <legend>Ich möchte …</legend>
                    <label><input type="radio" name="anliegen" value="Platzanfrage" checked><span>einen Platz anfragen</span></label>
                    <label><input type="radio" name="anliegen" value="Besichtigung"><span>eine Besichtigung</span></label>
                    <label><input type="radio" name="anliegen" value="Frage"><span>etwas fragen</span></label>
                </fieldset>

                <p class="form__group">Kind</p>
                <div class="form__row">
                    <label class="field"><span>Vorname Kind *</span><input name="kind_vorname" required autocomplete="off"></label>
                    <label class="field"><span>Nachname Kind *</span><input name="kind_nachname" required autocomplete="off"></label>
                </div>
                <div class="form__row">
                    <label class="field"><span>Geburtsdatum (oder Termin)</span><input name="geburtsdatum" type="date"></label>
                    <label class="field"><span>Gewünschter Betreuungsbeginn</span><input name="beginn" type="month"></label>
                </div>
                <fieldset class="weekdays">
                    <legend>Gewünschte Betreuungstage</legend>
                    <?php foreach (['Mo', 'Di', 'Mi', 'Do', 'Fr'] as $tag): ?>
                        <label><input type="checkbox" name="tage[]" value="<?= $tag ?>"><span><?= $tag ?></span></label>
                    <?php endforeach; ?>
                </fieldset>

                <p class="form__group">Eltern</p>
                <div class="form__row">
                    <label class="field"><span>Vor- und Nachname *</span><input name="name" required autocomplete="name"></label>
                    <label class="field"><span>Telefon *</span><input name="telefon" type="tel" required autocomplete="tel"></label>
                </div>
                <div class="form__row">
                    <label class="field"><span>E-Mail *</span><input name="email" type="email" required autocomplete="email"></label>
                    <label class="field"><span>Adresse</span><input name="adresse" autocomplete="street-address"></label>
                </div>
                <label class="field"><span>Nachricht</span><textarea name="nachricht" rows="4"></textarea></label>

                <div class="hp" aria-hidden="true"><label>Bitte leer lassen<input name="website" tabindex="-1" autocomplete="off"></label></div>
                <input type="hidden" name="t" value="<?= time() ?>">

                <label class="consent"><input type="checkbox" name="einwilligung" required> <span>Ich bin einverstanden, dass meine Angaben zur Bearbeitung der Anfrage verwendet werden (<a href="<?= e(url('datenschutz/')) ?>">Datenschutz</a>). *</span></label>
                <button class="btn btn--green" type="submit">Anfrage senden <?= icon('arrow') ?></button>
            </form>

            <aside class="contact__info reveal" style="--d:120ms">
                <div class="info-card">
                    <h2>So erreicht ihr uns</h2>
                    <ul class="info-list">
                        <li><?= icon('phone') ?><a href="<?= e(tel_link($a['telefon'] ?? '')) ?>"><?= e($a['telefon'] ?? '') ?></a></li>
                        <li><?= icon('mail') ?><a href="mailto:<?= e($a['email'] ?? '') ?>"><?= e($a['email'] ?? '') ?></a></li>
                        <?php if (!empty($a['whatsapp'])): ?><li><?= icon('whatsapp') ?><a href="https://wa.me/<?= e(preg_replace('/\D+/', '', $a['whatsapp'])) ?>" target="_blank" rel="noopener">WhatsApp schreiben</a></li><?php endif; ?>
                        <li><?= icon('pin') ?><span><?= e($a['strasse'] ?? '') ?>, <?= e($a['ort'] ?? '') ?></span></li>
                        <li><?= icon('clock') ?><span><?= e($a['oeffnungszeiten_tage'] ?? '') ?>, <?= e($a['oeffnungszeiten_zeit'] ?? '') ?></span></li>
                        <li><?= icon('car') ?><span><?= e($a['parkieren'] ?? '') ?></span></li>
                    </ul>
                </div>
                <a class="map" href="<?= e($a['maps_link'] ?? '#') ?>" target="_blank" rel="noopener">
                    <svg viewBox="0 0 400 260" aria-hidden="true" class="map__art">
                        <rect width="400" height="260" fill="var(--sky)"/>
                        <path d="M-10 190C80 150 140 210 230 170S360 120 420 140" stroke="#fff" stroke-width="22" fill="none"/>
                        <path d="M120-10 160 280M300-10 250 280M-10 70h420" stroke="#fff" stroke-width="12" fill="none"/>
                        <circle cx="60" cy="40" r="26" fill="#cfe0c3"/><circle cx="350" cy="220" r="34" fill="#cfe0c3"/><circle cx="330" cy="40" r="18" fill="#cfe0c3"/>
                    </svg>
                    <span class="map__pin"><?= icon('pin') ?></span>
                    <span class="map__label">Route planen <?= icon('arrow') ?></span>
                </a>
            </aside>
        </div>
    </div>
</section>
