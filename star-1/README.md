# Star 1 Coiffeur — Webseite mit Online-Buchung

Neue Webseite für Star 1 Coiffeur (Untere Bahnhofstrasse 19, 9500 Wil SG) mit Buchungsportal und Verwaltung.
Inhalte, Bilder, Logo und Video stammen von star-1.ch. Die Preise stammen von der Preistafel im Salon.

## Aufbau

| Pfad | Inhalt |
|---|---|
| `index.html` | Startseite (Hero-Video, Salon, Leistungen, Wieso wir, Galerie, Kundenstimmen, Preisliste, Promo-Video, Kontakt) |
| `buchen/` | Online-Buchung: **Datum → Uhrzeit → Coiffeur → Dienstleistung → Angaben** |
| `admin/` | Verwaltung mit Login |
| `datenschutz/` | Datenschutzerklärung |
| `api/public.php` | öffentliche Schnittstelle (Verfügbarkeit, Buchen, Kontakt) |
| `api/admin.php` | Verwaltungs-Schnittstelle (nur mit Login) |
| `api/config.php` | Einstellungen (Start-Passwort, E-Mail-Adressen) |
| `data/` | SQLite-Datenbank (wird automatisch angelegt, per `.htaccess` gesperrt) |

Keine Build-Schritte, keine externen Abhängigkeiten. Benötigt **PHP 8 mit pdo_sqlite** (bei Schweizer Hostern wie
Hostpoint, cyon oder Infomaniak Standard). Alle Pfade sind relativ — der Ordner kann direkt ins Web-Verzeichnis
der Domain star-1.ch kopiert werden. Der Ordner `data/` muss für PHP beschreibbar sein.

## Buchungsregeln

- Jede Buchung belegt **30 Minuten** bei einer Person (einstellbar in `settings.slot_minutes`).
- Die Kundschaft sieht nur Tage und Zeiten, an denen mindestens eine Person frei ist.
- «Keine Präferenz» weist den Termin der Person mit den wenigsten Terminen an diesem Tag zu.
- Doppelbuchungen sind in der Datenbank ausgeschlossen (eindeutiger Index pro Person, Datum, Zeit).
- Mindestvorlauf (Standard 60 Min.) und Buchungshorizont (Standard 60 Tage) sind in der Verwaltung einstellbar.
- Bei Damen-Leistungen mit drei Preisen wählt die Kundschaft die Haarlänge (kurz / mittel / lang).
- Bestätigung per E-Mail an die Kundschaft (wenn angegeben) und Kopie an `info@star-1.ch` (über PHP `mail()`).

## Verwaltung

Aufruf: `/admin/` — Start-Zugang: Benutzer `admin`, Passwort siehe `api/config.php`.
Beim ersten Login muss das Passwort geändert werden.

- **Tagesplan**: Raster Uhrzeit × Mitarbeiter, Termine anklicken (Details, bearbeiten, absagen, löschen),
  freie Felder anklicken für neue Termine (z. B. telefonische Buchungen), Kennzahlen.
- **Alle Termine**: Liste mit Zeitraum, Status, Suche und CSV-Export.
- **Mitarbeiter**: Name, Funktion, Farbe, Arbeitstage, aktiv/inaktiv.
- **Leistungen & Preise**: wirkt sofort auf Preisliste der Webseite und Buchung.
- **Öffnungszeiten**: pro Wochentag, Vorlauf und Horizont der Online-Buchung.
- **Abwesenheiten**: Ferien, Feiertage, Pausen — für eine Person oder den ganzen Salon, ganztags oder mit Uhrzeit.
- **Konto**: Passwort ändern.

Sicherheit: Passwort-Hash (`password_hash`), Sitzungs-Cookie `HttpOnly` + `SameSite=Strict`, CSRF-Token,
Sperre nach 8 Fehlversuchen (15 Min.), Spam-Falle und Limit für Online-Buchungen.

## Lokal testen

```bash
php -S localhost:8000
# → http://localhost:8000/
```
