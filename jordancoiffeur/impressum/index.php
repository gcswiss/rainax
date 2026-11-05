<?php
declare(strict_types=1);
require __DIR__ . '/../lib/layout.php';
page_head('Impressum | Jordan Coiffeur & Barbershop', 'Impressum von Jordan Coiffeur & Barbershop, St. Gallen.');
?>
<div class="wrap page-head"><h1 style="font-size:clamp(2.4rem,6vw,4rem)">Impressum</h1></div>
<section style="padding-top:16px">
  <div class="wrap prose">
    <h2 style="margin-top:0">Betreiberin</h2>
    <p>Saad Jordan Barbershop GmbH<br>St. Leonhardstr. 22<br>9000 St. Gallen<br>Schweiz</p>
    <p>Telefon: <a href="tel:+41712201000">071 220 10 00</a><br>E-Mail: <a href="mailto:kontakt@jordancoiffeur.ch">kontakt@jordancoiffeur.ch</a></p>
    <!-- TODO: UID-Nummer aus dem Handelsregister ergänzen (z. B. CHE-123.456.789) und vertretungsberechtigte Person -->
    <h2>Haftungsausschluss</h2>
    <p>Wir prüfen die Inhalte dieser Webseite sorgfältig. Für Richtigkeit, Vollständigkeit und Aktualität übernehmen wir keine Gewähr. Preise sind Richtpreise («ab»); der Endpreis richtet sich nach Aufwand.</p>
    <h2>Urheberrecht</h2>
    <p>Texte, Bilder und Logo auf dieser Webseite gehören der Saad Jordan Barbershop GmbH. Eine Verwendung ist nur mit schriftlicher Erlaubnis gestattet.</p>
  </div>
</section>
<?php page_foot();
