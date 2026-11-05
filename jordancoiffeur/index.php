<?php
declare(strict_types=1);
require __DIR__ . '/lib/layout.php';

$pdo = db();
$services = $pdo->query('SELECT * FROM services WHERE active=1 ORDER BY sort,id')->fetchAll();
$branches = $pdo->query('SELECT * FROM branches WHERE active=1 ORDER BY sort,id')->fetchAll();
$hours = $pdo->query('SELECT * FROM hours')->fetchAll();
$mainHours = hours_lines(array_filter($hours, fn($h) => (int)$h['branch_id'] === (int)($branches[0]['id'] ?? 0)));
$main = $branches[0] ?? null;

page_head('Jordan Barber Shop St. Gallen – Scharfer Schnitt. Starker Style.',
    'Jordan Coiffeur & Barbershop in St. Gallen: Haarschnitt, Fade, Bartpflege und Farbe an der St. Leonhardstrasse und Langgasse. Jetzt online buchen.',
    '', ['hero' => true]);
?>
<section class="hero" style="padding:0">
  <img class="poster" src="/assets/img/hero-poster.webp" alt="" width="1280" height="720" fetchpriority="high">
  <video autoplay muted loop playsinline preload="metadata" poster="/assets/img/hero-poster.webp" aria-hidden="true">
    <source src="/assets/video/hero-mobile.mp4" type="video/mp4" media="(max-width: 700px)">
    <source src="/assets/video/hero.mp4" type="video/mp4">
  </video>
  <div class="wrap on-dark">
    <p class="label">Jordan Barber Shop</p>
    <h1>Scharfer Schnitt.<br>Starker Style.</h1>
    <p>Haarschnitt, Fade, Bartpflege und Farbe – zweimal in St. Gallen.</p>
    <div class="hero-actions">
      <a class="btn gold" href="/buchen/">Jetzt buchen</a>
      <a class="btn outline-light" href="#preise">Preise</a>
    </div>
  </div>
  <button class="video-toggle" type="button" aria-label="Video anhalten"><svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="2" y="1" width="3.5" height="12"/><rect x="8.5" y="1" width="3.5" height="12"/></svg></button>
</section>

<div class="infobar">
  <div class="hours">
    <h3>Öffnungszeiten</h3>
    <?php foreach ($mainHours as [$d, $t]): ?><p><?= $d ?>&nbsp; <?= $t ?></p><?php endforeach; ?>
  </div>
  <div class="quote on-dark">
    <p class="label light" style="font-size:1rem">Kunden sagen</p>
    <blockquote>«Erster Besuch, exzellenter Haarschnitt und Bartpflege – bin sehr zufrieden und komme gerne wieder.»</blockquote>
    <cite>Sebastian</cite>
  </div>
  <a class="book" href="/buchen/"><span><?= icon('calendar', 22) ?></span>Jetzt buchen →</a>
</div>

<section id="ueber-uns">
  <div class="wrap split">
    <div class="about-photo reveal">
      <img src="/assets/img/barber.webp" alt="Kunde mit frischem Fade und gepflegtem Bart bei Jordan" width="1100" height="1838" loading="lazy">
    </div>
    <div class="reveal">
      <p class="label">Über uns</p>
      <h2>Willkommen bei Jordan</h2>
      <div class="ruled">
        <p>Bei Jordan Coiffeur &amp; Barbershop dreht sich alles um dich – deine Persönlichkeit, deinen Stil und deinen perfekten Look. Egal, ob du nach einem frischen Haarschnitt, einer schicken Farbe oder der besten Bartpflege der Stadt suchst, wir sind für dich da.</p>
        <p>Unser Team steckt jede Menge Herzblut in jeden Schnitt und jede Strähne. Klassisch oder aussergewöhnlich – wir kreieren mit dir deinen individuellen Stil.</p>
        <p style="margin:0">Wir freuen uns darauf, dich kennenzulernen!<br>Dein Team von Jordan Coiffeur &amp; Barbershop</p>
      </div>
      <img class="signature" src="/assets/img/signatur.png" alt="" width="190" height="114" loading="lazy">
    </div>
  </div>
</section>

<section id="services" style="padding-top:0">
  <div class="wrap services-grid">
    <div class="reveal">
      <p class="label">Services</p>
      <h2>Unsere Services für Sie.</h2>
    </div>
    <article class="service reveal">
      <img src="/assets/img/icon-schnitt.svg" alt="" width="70" height="70">
      <h3>Cut &amp; Style</h3>
      <p>Präzision im Schneiden und Stylen auf höchstem Niveau – vom klassischen Schnitt bis zum Skin Fade.</p>
    </article>
    <article class="service reveal">
      <img src="/assets/img/icon-foehn.svg" alt="" width="70" height="70">
      <h3>Haare Waschen mit Style</h3>
      <p>Für Männer, die stets einen scharfen Look bevorzugen. Damit dein Fade perfekt bleibt.</p>
    </article>
    <article class="service reveal">
      <img src="/assets/img/icon-rasur.svg" alt="" width="70" height="70">
      <h3>Bart</h3>
      <p>Unsere Rasuren beginnen mit einer Beratung und Hautanalyse, um Allergien oder Empfindlichkeiten zu erkennen.</p>
    </article>
  </div>
</section>

<div id="preise" class="price-banner on-dark">
  <div>
    <p class="label light">Preise</p>
    <h2>Preise, die sich lohnen!</h2>
  </div>
</div>
<div class="price-list">
  <div class="wrap">
    <ul class="prices">
      <?php foreach ($services as $s): ?>
      <li><span><?= h($s['name']) ?></span><span class="dots"></span><b><?= h($s['price']) ?></b></li>
      <?php endforeach; ?>
    </ul>
    <p class="center"><a class="btn outline-light" href="/buchen/">Jetzt buchen</a></p>
  </div>
</div>

<section id="kontakt">
  <div class="wrap">
    <div class="center reveal" style="margin-bottom:48px">
      <p class="label">Booking</p>
      <h2>Kontakt</h2>
    </div>
    <div class="cards-3">
      <div class="info-card reveal">
        <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M5 4 2.5 6.5M19 4l2.5 2.5"/></svg>
        <h3>Öffnungszeiten</h3>
        <?php foreach ($mainHours as [$d, $t]): ?><p><?= $d ?>&nbsp;&nbsp; <?= $t ?></p><?php endforeach; ?>
      </div>
      <div class="info-card reveal">
        <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/></svg>
        <h3>Telefon</h3>
        <?php foreach ($branches as $b): ?><p><a href="<?= h(tel_link($b['phone'])) ?>"><?= h($b['phone']) ?></a> · <?= h($b['name']) ?></p><?php endforeach; ?>
        <p><a href="mailto:kontakt@jordancoiffeur.ch">kontakt@jordancoiffeur.ch</a></p>
      </div>
      <div class="info-card reveal">
        <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
        <h3>Filialen</h3>
        <?php foreach ($branches as $b): ?><p><a href="https://www.google.com/maps/search/?api=1&amp;query=<?= urlencode('Jordan Coiffeur ' . $b['street'] . ' ' . $b['city']) ?>" target="_blank" rel="noopener"><?= h($b['street']) ?>, <?= h($b['city']) ?></a></p><?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="stats wrap" aria-label="Jordan in Zahlen">
  <div class="reveal"><b>15</b><span>Jahre Erfahrung</span></div>
  <div class="reveal"><b>2'500</b><span>Glückliche Kunden</span></div>
  <div class="reveal"><b>222</b><span>Google-Bewertungen</span></div>
</section>

<section style="padding-top:0" aria-label="Instagram">
  <div class="gallery">
    <?php for ($i = 1; $i <= 6; $i++): ?>
    <a href="https://www.instagram.com/jordan_coiffeur/" target="_blank" rel="noopener"><img src="/assets/img/insta-<?= $i ?>.webp" alt="Haarschnitt von Jordan auf Instagram" width="600" height="600" loading="lazy"></a>
    <?php endfor; ?>
  </div>
  <div class="gallery-cta"><a class="btn gold" href="https://www.instagram.com/jordan_coiffeur/" target="_blank" rel="noopener"><?= icon('instagram', 18) ?> Auf Instagram folgen</a></div>
</section>
<?php page_foot();
