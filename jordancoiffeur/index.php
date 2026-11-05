<?php
declare(strict_types=1);
require __DIR__ . '/lib/layout.php';

$pdo = db();
$services = $pdo->query('SELECT * FROM services WHERE active=1 ORDER BY sort,id')->fetchAll();
$branches = $pdo->query('SELECT * FROM branches WHERE active=1 ORDER BY sort,id')->fetchAll();
$hours = $pdo->query('SELECT * FROM hours')->fetchAll();
$hoursFor = fn($id) => hours_lines(array_filter($hours, fn($h) => (int)$h['branch_id'] === (int)$id));
$mainHours = $hoursFor($branches[0]['id'] ?? 0);

page_head('Jordan Coiffeur & Barbershop St. Gallen – Scharfer Schnitt. Starker Style.',
    'Barbershop in St. Gallen: Haarschnitt, Skin Fade, Bartpflege und Farbe an der St. Leonhardstrasse und Langgasse. Termin online buchen.');
?>
<section class="hero" style="padding:0">
  <picture class="hero-photo">
    <source media="(max-width: 700px)" srcset="/assets/img/barber-sm.webp">
    <img src="/assets/img/barber.webp" alt="Kunde mit frischem Fade und gepflegtem Bart bei Jordan" width="1100" height="1838" fetchpriority="high">
  </picture>
  <div class="wrap">
    <p class="eyebrow">Barbershop · St. Gallen</p>
    <h1>Scharfer Schnitt.<span>Starker Style.</span></h1>
    <p class="lead">Haarschnitt, Fade, Bartpflege und Farbe – mit Herzblut gemacht, an zwei Standorten in St. Gallen.</p>
    <div class="hero-actions">
      <a class="btn" href="/buchen/"><?= icon('calendar', 20) ?> Termin buchen</a>
      <a class="btn ghost" href="#preise">Preise ansehen</a>
    </div>
    <div class="hero-meta">
      <div><strong>Öffnungszeiten</strong><?php foreach ($mainHours as [$d, $t]): ?><?= $d ?> <?= $t ?><br><?php endforeach; ?></div>
      <div><strong>Telefon</strong><a href="tel:+41712201000">071 220 10 00</a></div>
      <div><strong>Google</strong>222 Bewertungen</div>
    </div>
  </div>
</section>

<section id="ueber-uns">
  <div class="wrap grid-2">
    <div class="reveal">
      <p class="eyebrow">Über uns</p>
      <h2>Willkommen bei Jordan</h2>
      <p>Bei Jordan Coiffeur &amp; Barbershop dreht sich alles um dich – deine Persönlichkeit, deinen Stil und deinen perfekten Look. Egal, ob du nach einem frischen Haarschnitt, einer schicken Farbe oder der besten Bartpflege der Stadt suchst, wir sind für dich da.</p>
      <p>Unser Team ist nicht nur talentiert, sondern steckt auch jede Menge Herzblut in jeden Schnitt und jede Strähne. Du willst einen klassischen Look? Kein Problem! Oder lieber etwas Aussergewöhnliches? Wir lieben es, kreativ zu sein und mit dir zusammen deinen individuellen Stil zu kreieren.</p>
      <p>Komm vorbei und entdecke, was es heisst, sich rundum wohlzufühlen. Wir freuen uns darauf, dich kennenzulernen!</p>
      <p class="signature">Dein Team von Jordan Coiffeur &amp; Barbershop</p>
    </div>
    <div class="about-card reveal">
      <img src="/assets/img/logo.webp" alt="Logo Jordan Coiffeur Barber" width="300" height="295" loading="lazy">
      <ul class="facts">
        <li><b>2</b><span>Standorte in St. Gallen</span></li>
        <li><b>6</b><span>Tage pro Woche offen</span></li>
        <li><b>222</b><span>Google-Bewertungen</span></li>
      </ul>
    </div>
  </div>
</section>

<section id="services" class="alt">
  <div class="wrap">
    <div class="section-head reveal">
      <p class="eyebrow">Services</p>
      <h2>Unsere Services für dich</h2>
    </div>
    <div class="services">
      <article class="service reveal">
        <div class="ico"><?= icon('scissors', 26) ?></div>
        <h3>Cut &amp; Style</h3>
        <p>Präzision im Schneiden und Stylen auf höchstem Niveau – unsere Stylisten nehmen sich Zeit für deinen Look, vom klassischen Schnitt bis zum Skin Fade.</p>
      </article>
      <article class="service reveal">
        <div class="ico"><?= icon('drop', 26) ?></div>
        <h3>Waschen mit Style</h3>
        <p>Für Männer, die stets einen scharfen Look bevorzugen: Haarwäsche, Schnitt und Föhnstyling, damit dein Fade perfekt sitzt.</p>
      </article>
      <article class="service reveal">
        <div class="ico"><?= icon('razor', 26) ?></div>
        <h3>Bart</h3>
        <p>Unsere Rasuren beginnen mit einer Beratung und Hautanalyse, um eventuelle Allergien oder Empfindlichkeiten zu erkennen.</p>
      </article>
    </div>
  </div>
</section>

<section id="preise">
  <div class="wrap">
    <div class="section-head reveal">
      <p class="eyebrow">Preise</p>
      <h2>Preise, die sich lohnen</h2>
      <p class="muted">Alle Preise in CHF. Der Endpreis hängt von Haarlänge und Aufwand ab.</p>
    </div>
    <div class="prices reveal">
      <?php foreach ($services as $s): ?>
      <div class="price">
        <span><?= h($s['name']) ?><small><?= (int)$s['duration'] ?> Min.</small></span>
        <span class="dots"></span>
        <b><?= h($s['price']) ?></b>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="prices-cta">
      <a class="btn" href="/buchen/"><?= icon('calendar', 20) ?> Jetzt buchen</a>
      <span class="muted">Online in einer Minute – oder ruf uns an.</span>
    </div>
  </div>
</section>

<section class="alt">
  <div class="wrap">
    <div class="section-head reveal">
      <p class="eyebrow">Kunden sagen</p>
      <h2>Was unsere Kunden sagen</h2>
      <div class="rating-badge"><b>222</b> Bewertungen auf Google</div>
    </div>
    <div class="reviews">
      <figure class="review reveal">
        <blockquote>«Erster Besuch, exzellenter Haarschnitt und Bartpflege – bin sehr zufrieden und komme gerne wieder.»</blockquote>
        <figcaption>Sebastian</figcaption>
      </figure>
      <figure class="review reveal">
        <blockquote>«Schnelle und freundliche Bedienung, perfektes Ergebnis.»</blockquote>
        <figcaption>Ben Zürcher · Google</figcaption>
      </figure>
      <figure class="review reveal">
        <blockquote>«Sehr guter Service. Gute Fades. Alles super.»</blockquote>
        <figcaption>Timon Nowak · Google</figcaption>
      </figure>
    </div>
    <p style="margin-top:28px"><a class="btn ghost" href="https://admin.trustindex.io/api/googleWriteReview?place-id=ChIJK7hmpAYfm0cR3FVLJC3p7o4" target="_blank" rel="noopener">Bewertung schreiben</a></p>
  </div>
</section>

<section id="standorte">
  <div class="wrap">
    <div class="section-head reveal">
      <p class="eyebrow">Standorte</p>
      <h2>Zweimal in St. Gallen</h2>
    </div>
    <div class="locations">
      <?php foreach ($branches as $i => $b): $tel = '+41' . preg_replace('/\D/', '', ltrim($b['phone'], '0')); ?>
      <article class="location reveal">
        <span class="tag"><?= $i === 0 ? 'Hauptfiliale' : 'Filiale' ?></span>
        <h3><?= h($b['name']) ?></h3>
        <dl>
          <dt>Adresse</dt><dd><?= h($b['street']) ?>, <?= h($b['city']) ?></dd>
          <dt>Telefon</dt><dd><a href="tel:<?= h($tel) ?>"><?= h($b['phone']) ?></a></dd>
          <?php foreach ($hoursFor($b['id']) as [$d, $t]): ?><dt><?= $d ?></dt><dd><?= $t ?></dd><?php endforeach; ?>
        </dl>
        <div class="links">
          <a class="btn" href="/buchen/?filiale=<?= (int)$b['id'] ?>">Hier buchen</a>
          <a class="btn ghost" href="https://www.google.com/maps/search/?api=1&amp;query=<?= urlencode('Jordan Coiffeur ' . $b['street'] . ' ' . $b['city']) ?>" target="_blank" rel="noopener"><?= icon('pin', 18) ?> Route</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section style="padding-top:0">
  <div class="wrap">
    <div class="cta-band reveal">
      <div>
        <h2>Bereit für deinen neuen Look?</h2>
        <p style="margin:0">Wähle Service, Barber und Zeit – fertig.</p>
      </div>
      <a class="btn" href="/buchen/"><?= icon('calendar', 20) ?> Termin buchen</a>
    </div>
  </div>
</section>
<?php page_foot();
