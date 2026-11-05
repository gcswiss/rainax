<?php
declare(strict_types=1);
require __DIR__ . '/../lib/layout.php';
page_head('Jobs – Herrenfriseur gesucht | Jordan Coiffeur & Barbershop St. Gallen',
    'Wir suchen einen Herrenfriseur (m/w/d) für unseren Barbershop in St. Gallen.', '/jobs/');
?>
<div class="wrap page-head">
  <p class="eyebrow">Jobs bei Jordan</p>
  <h1 style="font-size:clamp(2.4rem,6vw,4.2rem)">Herrenfriseur (m/w/d) gesucht</h1>
</div>
<section style="padding-top:16px">
  <div class="wrap prose">
    <div class="card">
      <h2 style="margin-top:0">Deine Aufgaben</h2>
      <ul>
        <li>Beratung und Betreuung unserer Kunden</li>
        <li>Schneiden, Stylen und Rasieren von Herrenfrisuren und Bärten</li>
        <li>Pflege und Reinigung der Arbeitsmaterialien</li>
        <li>Sicherstellung eines sauberen und professionellen Arbeitsumfelds</li>
      </ul>
      <h2>Dein Profil</h2>
      <ul>
        <li>Abgeschlossene Ausbildung als Friseur/in oder vergleichbare Qualifikation</li>
        <li>Erfahrung im Herrenbereich von Vorteil, aber auch talentierte Einsteiger sind willkommen</li>
        <li>Leidenschaft für Trendfrisuren und moderne Techniken</li>
        <li>Freundliches Auftreten und Kundenorientierung</li>
        <li>Zuverlässigkeit und Teamfähigkeit</li>
      </ul>
      <h2>Wir bieten</h2>
      <ul>
        <li>Eine angenehme Arbeitsatmosphäre in einem modernen Salon</li>
        <li>Flexible Arbeitszeiten und faire Vergütung</li>
        <li>Möglichkeiten zur Weiterbildung und Entwicklung</li>
        <li>Ein tolles Team und eine Stammkundschaft</li>
      </ul>
      <p style="margin-top:28px">Wenn du denkst, dass du zu uns passt, freuen wir uns auf deine Bewerbung! Sende uns deine Unterlagen per E-Mail oder ruf uns direkt an. Wir freuen uns darauf, dich kennenzulernen!</p>
      <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:20px">
        <a class="btn" href="mailto:kontakt@jordancoiffeur.ch?subject=Bewerbung%20Herrenfriseur">Bewerbung senden</a>
        <a class="btn ghost" href="tel:+41763690745"><?= icon('phone', 18) ?> 076 369 07 45</a>
      </div>
    </div>
  </div>
</section>
<?php page_foot();
