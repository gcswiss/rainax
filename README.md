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

Das Hintergrundvideo wird selbst gehostet (kein YouTube): ohne Ton, ohne Bedienelemente, in Endlosschleife.
Die Seite erwartet diese Dateien in `assets/video/`:

| Datei | Zweck |
|---|---|
| `hero.webm` | VP9, kleiner (Chrome, Firefox, Edge) |
| `hero.mp4` | H.264 (Safari, iPhone, alle übrigen) |
| `hero-poster.jpg` | Standbild, bis das Video läuft |

Erstellen aus der Originaldatei (benötigt [ffmpeg](https://ffmpeg.org)):

```bash
tools/encode-video.sh original.mp4          # → assets/video/hero.*
tools/encode-video.sh code.mp4 was-wir-tun  # weiteres Video unter eigenem Namen
```

Das Skript verkleinert auf höchstens 1920 px Breite, entfernt die Tonspur und optimiert für schnelles Laden.
Ziel: zusammen unter ca. 8 MB; ein kurzer Ausschnitt von 10–20 Sekunden reicht für eine Schleife.

Verhalten: Das Video wird erst geladen, wenn es in die Nähe des Bildschirms kommt, pausiert ausserhalb
des Bildschirms und kann unten rechts angehalten werden. Bei «Bewegung reduzieren» im Betriebssystem
wird kein Video geladen. Fehlen die Dateien, bleibt der dunkle Hintergrund stehen.
Dasselbe Video läuft auch im Schlussbild der Startseite.

Das aktuelle Video («Planet Earth Spinning in Space») wurde vor dem Aufbereiten zu einer nahtlosen
Schleife geschnitten: Die letzten 1,5 Sekunden blenden weich in den Anfang über, damit beim Neustart
kein Sprung sichtbar ist.

## Bewegung

Ruhig und präzise, nichts blinkt oder springt. Alles ist abgeschaltet, wenn «Bewegung reduzieren» eingestellt ist;
Hover-Effekte gibt es nur mit Maus.

- Hero: Titel erscheint Wort für Wort aus der Unschärfe; die Erde reagiert mit Tiefe auf die Maus;
  beim Scrollen fliegt man in die Erde hinein, der Text tritt zurück
- Einleitung: Die Wörter leuchten beim Lesen nacheinander auf
- Kacheln: Lichtkegel folgt der Maus, leichtes 3D-Kippen
- Buttons: ziehen sich leicht zur Maus hin (magnetisch)
- MoodUp: Handy kippt Richtung Maus, Punkte auf der Live-Map pulsieren wie Echtzeit-Signale
- Prozess und Zeitleiste: Die Verbindungslinie zeichnet sich selbst, Punkte leuchten nacheinander auf
- Schlussbild: Die Erde zoomt beim Scrollen langsam heraus

## Noch offen (Platzhalter)

Alle offenen Stellen sind im HTML mit `PLATZHALTER` kommentiert bzw. gelb markiert:

- Originallogo (weiss/dunkel) statt der Text-Wortmarke in Kopfzeile und Footer
- E-Mail, Telefonnummer, UID-Nummer
- Formular-Empfänger: `data-endpoint` im Kontaktformular (z. B. Formspree, Web3Forms)
- Bilder/Videos: Säulen, MoodUp-Screens, «KI für Immobilien», Team-Porträts, Seitenanfänge
- Links zu LinkedIn, Instagram und MoodUp / App Store
- Datenschutzerklärung (muss OpenStreetMap und Google Fonts erwähnen)
