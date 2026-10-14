# Webseite RAINAX AG

Statische Webseite (HTML, CSS, JavaScript, ohne Build-Schritt), gebaut nach dem «Bauplan Webseite RAINAX AG» (13.10.2026).
Gestaltung: dunkle «Apple»-Stilreferenz mit RAINAX-Blau (`#2F6BFF`) als Akzent.

## Lokal ansehen

```bash
python3 -m http.server 8000
# → http://localhost:8000
```

Die Pfade sind absolut (`/assets/...`), die Seite muss also über einen Webserver laufen (nicht per Doppelklick).

## Aufbau

| Seite | Datei |
|---|---|
| Startseite | `index.html` |
| Was wir tun | `was-wir-tun/index.html` |
| Projekte | `projekte/index.html` |
| MoodUp | `projekte/moodup/index.html` |
| Über uns | `ueber-uns/index.html` |
| Team | `team/index.html` |
| Kontakt | `kontakt/index.html` |
| Impressum | `impressum/index.html` |
| Datenschutz | `datenschutz/index.html` |
| Fehlerseite | `404.html` |

`assets/css/style.css` enthält alle Design-Tokens, `assets/js/main.js` Menü, Scroll-Effekte, Videos und Formular.

## Hero-Video

Das Video im Hero ist ein YouTube-Video (`_Sl8diqCAFw`), das ohne Ton, ohne Bedienelemente und in Endlosschleife
als Hintergrund läuft (über `youtube-nocookie.com`). Video wechseln: `data-youtube-id` im Element `.video-bg` ändern.
Bei «Bewegung reduzieren» im Betriebssystem wird kein Video geladen. Unten rechts gibt es einen Pause-Knopf.

## Noch offen (Platzhalter)

Alle offenen Stellen sind im HTML mit `PLATZHALTER` kommentiert bzw. gelb markiert:

- Originallogo (weiss/dunkel) statt der Text-Wortmarke in Kopfzeile und Footer
- E-Mail, Telefonnummer, UID-Nummer
- Formular-Empfänger: `data-endpoint` im Kontaktformular (z. B. Formspree, Web3Forms)
- Bilder/Videos: Säulen, MoodUp-Screens, «KI für Immobilien», Team-Porträts, Seitenanfänge
- Links zu LinkedIn, Instagram und MoodUp / App Store
- Datenschutzerklärung (muss YouTube, OpenStreetMap und Google Fonts erwähnen)
