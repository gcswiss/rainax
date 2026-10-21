# Mood Up – Webseite (Mockup)

**Entwurf, nicht online.** Statische Seiten ohne Build-Schritt, alle Pfade relativ: Der Ordner kann direkt als
Wurzel von mood-up.ch hochgeladen oder lokal per Doppelklick auf `index.html` angesehen werden.

Stil «seriös, aber kreativ» (Richtung Uber und Cash App): Schwarz, eine Fläche in Mood-Up-Pink `#f7295c` mit schwarzer Schrift,
ein heller Abschnitt für Clubs, grosse Grotesk-Schrift (Inter Tight / Inter), echte App-Screenshots statt Effekte.

## Offline ansehen

Ordner öffnen und `index.html` doppelklicken. Alles läuft ohne Internet und ohne Server: Schriften liegen in
`assets/fonts/` (keine Verbindung zu Google), alle Links zeigen direkt auf `…/index.html`.

## Seiten

Gleiche Struktur wie die bestehende mood-up.ch: Navigation Home, Preise, FAQ, Über uns, Kontakt; im Footer «Links» mit AGB's, Impressum und Datenschutzerklärung.
Logo: `assets/logo.webp` (weiss) und `assets/logo-pink.webp` (Footer), Favicon aus dem Logo-Zeichen.

| Seite | Datei |
|---|---|
| Startseite | `index.html` |
| Preise | `preise/index.html` |
| FAQ | `faq/index.html` |
| Über uns | `ueber-uns/index.html` |
| Kontakt (mit Formular) | `kontakt/index.html` |
| AGB, Impressum, Datenschutz | `agb/`, `impressum/`, `datenschutz/` |

Gemeinsames Design und Verhalten: `assets/site.css`, `assets/site.js`. Kopfzeile, Footer und Unterseiten
werden mit `python3 tools/build.py` erzeugt; Änderungen an Navigation oder Footer dort vornehmen und neu ausführen.

## Startseite

1. Hero «Wo heute die Party ist.» mit zwei echten App-Screens
2. Pinke Markenfläche «Schluss mit endlosem Suchen.» (Live-Karte, Specials, Bar-Chat)
3. Scrollytelling: Das Handy bleibt stehen, die fünf Screens wechseln beim Scrollen
4. Für Clubs & Veranstalter (heller Abschnitt)
5. Gratis · Swiss made · Sicher
6. Fragen
7. Download (pinke Fläche)

Inhalte nur aus bestätigten Angaben: Texte der bestehenden mood-up.ch (über den Suchindex, die Seite selbst ist aus der
Arbeitsumgebung gesperrt), LinkedIn-Ankündigung der App (Smart Filter: Art der Location, Musik, Alter, Datum) und App-Screenshots.
Der Smart-Filter-Abschnitt ist ein interaktives Beispiel, keine echten Daten.

## Noch offen (im HTML mit `PLATZHALTER` markiert, auf der Seite gelb hervorgehoben)

- Preise und Pakete für Clubs (Seite Preise)
- Weitere Fragen der bestehenden FAQ-Seite
- Text «Über uns» abgleichen (Team bewusst weggelassen)
- Letzter Punkt bei Bars/Clubs: «Höhere Auslastung un…» war abgeschnitten, Rest des Satzes ergänzen
- Impressum (Betreiber, UID, vertretungsberechtigte Personen), Datenschutzerklärung und AGB: **nicht erfinden, von der bestehenden Seite übernehmen**
- Kontaktformular sendet über das E-Mail-Programm; für direktes Absenden einen Formular-Dienst anbinden

- Einverständnis der gezeigten Lokale (Santos Bar, Cinewil, Ralph's Bar) für Namen und Fotos
- Originallogo und App-Schrift
- Links zu App Store («Mood Up - Party Radar») und Google Play
- Impressum, Datenschutz, Social-Media-Links
- Texte mit der bestehenden mood-up.ch abgleichen
