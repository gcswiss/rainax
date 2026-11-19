# Webseite Kita Farbewält

Neue Webseite für die Kita Farbewält in Dietikon: Boho-Design in den Farben der Logo-Kreise,
mit eigenem, einfachem Admin-Bereich. Die Kita kann darin Texte, Fotos, Videos, Team, Jobs, Tarife
und das Ferien-PDF selbst bearbeiten, ohne Datenbank und ohne Programmierkenntnisse.

## Seiten

| Adresse | Inhalt |
|---|---|
| `/` | Startseite: Hero (Video oder Bild), Zahlen, Werte, Alltags-Reels, Teaser, Besichtigung |
| `/ueber-uns/` | Willkommen, Räume, Galerie, Partnerschaft Kita Witzchistli |
| `/team/` | Teammitglieder (ohne Foto: farbiger Kreis mit Initialen) |
| `/angebot/` | Eingewöhnung in 5 Phasen, Portfolio, Entwicklungsgespräche, Kindergarten-Vorbereitung, Öffnungszeiten & Ferien-PDF |
| `/paedagogik/` | Pädagogisches Konzept, kurz und bündig |
| `/tarife/` | Tarifrechner, Monatstarife (automatisch berechnet), Halbtage, Date Night, Geschwisterrabatt, Abwesenheit, subventionierte Plätze mit Link zum Kitarechner Dietikon |
| `/jobs/` | Offene Stellen oder «keine offenen Stellen» mit Spontanbewerbung |
| `/kontakt/` | Anmeldeschritte, Formular für Platzanfrage / Besichtigung, Kontaktdaten |
| `/impressum/`, `/datenschutz/` | Rechtliches |
| `/admin/` | Admin-Bereich (Login) |

## Admin-Bereich (für die Kita)

1. `https://www.kita-farbewaelt.ch/admin/` öffnen und mit dem Passwort anmelden.
2. Links den Bereich wählen (z. B. «Team»), Texte ändern, Bilder mit «Bild hochladen» ersetzen.
3. Listen (Team, Galerie, Räume, Jobs …): «+ hinzufügen», mit ↑ ↓ umsortieren, mit ✕ entfernen.
4. Unten rechts **Speichern**: die Änderungen sind sofort online.

- Vor jedem Speichern wird automatisch eine Sicherung angelegt. Unter «Passwort & Sicherungen»
  kann ein früherer Stand mit einem Klick zurückgeholt werden.
- Hochgeladene Fotos werden automatisch verkleinert (max. 2000 px). Handy-Metadaten wie der GPS-Standort werden dabei entfernt.
- Videos: MP4, kurz (10–30 Sek.), ohne Ton, für die Alltags-Reels am besten im Hochformat.
- Texte: Leerzeile = neuer Absatz, `**Wort**` = fett.

## Technik

- PHP 8.1 oder neuer, Apache mit `mod_rewrite` (Standard bei Hostpoint, cyon, Infomaniak usw.)
- Keine Datenbank: Inhalte in `data/content.json`, Passwort-Hash in `data/config.php`
- Schriften lokal eingebunden (Fraunces, Nunito, Caveat), keine Cookies, kein Tracking, keine Google-Dienste
- `data/` und `inc/` sind per `.htaccess` gesperrt; in `uploads/` werden keine Skripte ausgeführt
- Login mit Sperre nach 6 Fehlversuchen (15 Min.), CSRF-Schutz, sichere Session-Cookies
- Formular: PHP `mail()` mit Spam-Schutz (Honigtopf, Mindestzeit, max. 5 Anfragen pro Stunde und IP)

### Aufschalten

1. Den **Inhalt** des Ordners `kita-farbewaelt/` per FTP/SFTP ins Hauptverzeichnis der Domain laden.
2. Die Ordner `data/`, `data/backups/` und `uploads/` müssen für PHP beschreibbar sein (meist automatisch so).
3. In `anfrage.php` die Konstante `ABSENDER` auf eine existierende Adresse der Domain setzen
   (z. B. `webseite@kita-farbewaelt.ch`), sonst landen Anfragen im Spam.
4. SSL beim Hoster aktivieren. Die `.htaccess` leitet automatisch auf HTTPS um.
5. Beim ersten Login das Passwort unter «Passwort & Sicherungen» ändern.

Für Videos muss der Hoster grosse Uploads erlauben. `.htaccess` und `.user.ini` setzen 256 MB,
falls der Hoster das zulässt. Sonst Videos vorher verkleinern (z. B. mit HandBrake, 720p).

Ohne `mod_rewrite` funktionieren die Seiten auch über `index.php?seite=tarife`.

### Lokal testen

```bash
cd kita-farbewaelt
php -S 127.0.0.1:8080 index.php
# → http://127.0.0.1:8080  und  http://127.0.0.1:8080/admin/
```

### Neues Passwort setzen (falls vergessen)

`data/config.php` per FTP löschen und `/admin/` aufrufen. Danach kann ein neues Passwort festgelegt werden.
Achtung: Bis dahin kann das jeder tun, der die Seite aufruft, also sofort erledigen.
