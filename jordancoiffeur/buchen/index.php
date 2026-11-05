<?php
declare(strict_types=1);
require __DIR__ . '/../lib/layout.php';
page_head('Termin buchen | Jordan Coiffeur & Barbershop St. Gallen',
    'Online-Termin bei Jordan Coiffeur & Barbershop in St. Gallen buchen: Service, Barber und Zeit wählen.',
    '', ['css' => ['/assets/css/booking.css?v=1'], 'fab' => false]);
?>
<div class="wrap page-head" style="padding-bottom:8px">
  <p class="eyebrow">Online-Buchung</p>
  <h1 style="font-size:clamp(2.4rem,6vw,4rem)">Termin buchen</h1>
</div>
<section style="padding-top:16px">
  <div class="wrap booking" id="booking">
    <ol class="steps" aria-label="Schritte">
      <li data-step="1">Service</li><li data-step="2">Filiale</li><li data-step="3">Barber</li><li data-step="4">Zeit</li><li data-step="5">Daten</li>
    </ol>

    <div class="bk-main">
      <div class="panel" data-panel="1">
        <h2>Was dürfen wir machen?</h2>
        <div class="opts" id="opt-services"><p class="muted">Lade …</p></div>
      </div>

      <div class="panel" data-panel="2" hidden>
        <h2>Welche Filiale?</h2>
        <div class="opts" id="opt-branches"></div>
      </div>

      <div class="panel" data-panel="3" hidden>
        <h2>Bei wem?</h2>
        <div class="opts" id="opt-staff"></div>
      </div>

      <div class="panel" data-panel="4" hidden>
        <h2>Wann passt es dir?</h2>
        <div class="cal-wrap">
          <div class="cal">
            <div class="cal-head">
              <button type="button" class="icon-btn" id="cal-prev" aria-label="Vorheriger Monat">‹</button>
              <strong id="cal-title"></strong>
              <button type="button" class="icon-btn" id="cal-next" aria-label="Nächster Monat">›</button>
            </div>
            <div class="cal-grid" id="cal-grid" role="grid"></div>
          </div>
          <div class="times">
            <p class="times-title" id="times-title">Wähle zuerst einen Tag.</p>
            <div class="time-grid" id="time-grid"></div>
          </div>
        </div>
      </div>

      <div class="panel" data-panel="5" hidden>
        <h2>Deine Angaben</h2>
        <form id="bk-form" class="form" novalidate>
          <label>Vor- und Nachname*<input name="name" autocomplete="name" required maxlength="80"></label>
          <label>Telefon* <span class="muted">(für Rückfragen)</span><input name="phone" type="tel" autocomplete="tel" required placeholder="079 123 45 67"></label>
          <label>E-Mail <span class="muted">(für die Bestätigung)</span><input name="email" type="email" autocomplete="email"></label>
          <label>Bemerkung<textarea name="note" rows="3" maxlength="500" placeholder="z. B. Skin Fade, Bart nur trimmen …"></textarea></label>
          <label class="hp" aria-hidden="true">Webseite<input name="website" tabindex="-1" autocomplete="off"></label>
          <p class="small muted">Mit der Buchung akzeptierst du unsere <a href="/datenschutz/">Datenschutzerklärung</a>. Bitte sag ab, wenn du nicht kommen kannst.</p>
          <p class="form-error" id="form-error" role="alert" hidden></p>
          <button class="btn" type="submit" id="bk-submit">Verbindlich buchen</button>
        </form>
      </div>

      <div class="panel done" data-panel="6" hidden>
        <div class="check" aria-hidden="true">✓</div>
        <h2>Termin gebucht!</h2>
        <p id="done-text"></p>
        <p class="muted" id="done-mail"></p>
        <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-top:20px">
          <a class="btn ghost" id="done-manage" href="#">Termin ansehen / absagen</a>
          <a class="btn ghost" href="/">Zur Startseite</a>
        </div>
      </div>
    </div>

    <aside class="summary" id="summary" aria-live="polite">
      <h3>Dein Termin</h3>
      <dl>
        <dt>Service</dt><dd data-sum="service">–</dd>
        <dt>Filiale</dt><dd data-sum="branch">–</dd>
        <dt>Barber</dt><dd data-sum="staff">–</dd>
        <dt>Zeit</dt><dd data-sum="time">–</dd>
      </dl>
      <button type="button" class="btn ghost back" id="bk-back" hidden>‹ Zurück</button>
    </aside>
  </div>
</section>
<script src="/assets/js/booking.js?v=1" defer></script>
<?php page_foot();
