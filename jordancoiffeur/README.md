# Jordan Coiffeur & Barbershop – neue Webseite mit Buchungssystem

Ersatz für die WordPress-Seite jordancoiffeur.ch. Die Inhalte (Texte, Preise, Öffnungszeiten, beide Filialen, Jobs, Logo, Foto) wurden von der alten Seite übernommen.
Ohne WordPress und ohne Plugins: PHP 8.1+ mit SQLite. Das läuft auf jedem Schweizer Webhosting, auf dem heute WordPress läuft (Hostpoint, Infomaniak, cyon usw.).
Es gibt keinen Build-Schritt, keine Datenbank-Einrichtung, keine Tracking-Cookies und keine externen Schriften oder Skripte.

## Aufbau

| Pfad | Inhalt |
|---|---|
| `index.php` | Startseite (Über uns, Services, Preise, Bewertungen, Standorte) |
| `buchen/` | Online-Buchung für Kunden (Service → Filiale → Barber → Datum/Zeit → Daten) |
| `buchen/termin.php?t=…` | Kunde sieht den eigenen Termin und kann ihn absagen (Link in der Bestätigungs-E-Mail) |
| `admin/` | **Admin-Bereich mit Kalender** (Login mit Passwort) |
| `api/` | Öffentliche Schnittstelle für die Buchung (JSON) |
| `jobs/`, `impressum/`, `datenschutz/` | Unterseiten |
| `lib/` | PHP-Logik (`core.php` für Verfügbarkeit und Buchungen, `auth.php`, `layout.php`). Vom Web aus gesperrt |
| `data/` | SQLite-Datenbank `buchungen.sqlite`, wird automatisch angelegt. Vom Web aus gesperrt |

Preise, Dienstleistungen und Öffnungszeiten auf der Startseite kommen direkt aus der Datenbank. Was im Admin geändert wird, ist also sofort auch auf der Webseite sichtbar.

## Admin-Bereich (`/admin/`)

- **Kalender**: Tagesansicht mit einer Spalte pro Barber, Wochenansicht (alle Barber oder einer). Zeigt Öffnungszeiten, die aktuelle Uhrzeit und alle Termine farbig nach Barber.
  - Klick auf eine freie Stelle legt einen neuen Termin an (z. B. für einen Telefonanruf). «Freie Zeiten anzeigen» hilft dabei.
  - Klick auf einen Termin öffnet ihn zum Bearbeiten, Absagen (optional mit E-Mail an den Kunden) oder zum Markieren als «Nicht erschienen».
  - Ein Termin lässt sich **per Drag & Drop verschieben**, auch zu einem anderen Barber.
  - Unter **«+ Abwesenheit»** werden Ferien, Krankheit, Pausen oder Feiertage eingetragen, für eine Person oder die ganze Filiale. In dieser Zeit ist keine Online-Buchung möglich.
  - Der Kalender lädt jede Minute neu, neue Online-Buchungen erscheinen also von selbst.
- **Termine**: Liste aller kommenden Termine und Suche nach Name, Telefon oder E-Mail.
- **Einstellungen**: Dienstleistungen (Name, Dauer, Preis, Reihenfolge), Mitarbeiter (Farbe, Filiale, Arbeitstage, online buchbar), Filialen und Öffnungszeiten, Buchungsregeln (Zeitraster, Vorlaufzeit, wie weit im Voraus buchbar, Frist für Online-Absagen) sowie das Passwort.

## Schutz vor Doppelbuchungen und Spam

- Jede Buchung wird unter einer Schreibsperre der Datenbank nochmals geprüft. Zwei Kunden können denselben Termin also nicht gleichzeitig bekommen.
- Wählt der Kunde «Egal», bekommt den Termin der freie Barber mit den wenigsten Terminen an diesem Tag.
- Ein Honeypot-Feld und eine Grenze pro IP (8 Buchungen pro Stunde) halten Spam-Bots fern.
- Admin-Login: Passwort-Hash, Sitzungs-Cookie (HttpOnly, SameSite=Strict), CSRF-Token und eine Sperre nach 10 Fehlversuchen in 15 Minuten.

## Installation beim Hoster

1. Den **Inhalt dieses Ordners** ins Web-Verzeichnis von jordancoiffeur.ch hochladen (nicht den Ordner selbst). WordPress vorher sichern und entfernen.
2. `lib/config.sample.php` als `lib/config.php` kopieren und anpassen:
   - `setup_key`: ein langer, geheimer Text, der nur für die Ersteinrichtung gebraucht wird
   - `salon_email`, `mail_from`: Adressen für die Buchungs-E-Mails
3. Der Ordner `data/` muss für PHP beschreibbar sein (meist automatisch so).
4. `https://jordancoiffeur.ch/admin/` aufrufen, den Einrichtungsschlüssel eingeben und das Admin-Passwort festlegen.
5. In **Einstellungen → Mitarbeiter** die echten Namen der Barber eintragen. Voreingestellt sind «Barber 1/2/3».
6. Eine Testbuchung machen und prüfen, ob die E-Mails ankommen. Der Versand läuft über PHPs `mail()`. Falls der Hoster das nicht unterstützt, muss SMTP ergänzt werden.

Alte WordPress-Adressen (`/buchung/`, `/barber/`, `/booking/`, `/prices/` …) werden per `.htaccess` umgeleitet.

**Backup:** Es reicht, die Datei `data/buchungen.sqlite` regelmässig zu sichern.

## Lokal testen

```bash
cd jordancoiffeur && php -S localhost:8000
```

Danach `http://localhost:8000` öffnen. Ohne Apache greifen die `.htaccess`-Regeln nicht, das ist für einen lokalen Test aber in Ordnung.

## Noch zu prüfen

- **Barber-Namen**: Sie werden auf der alten Seite dynamisch geladen und konnten deshalb nicht übernommen werden. Bitte im Admin eintragen.
- **Dauer der Dienstleistungen**: Die Werte sind Annahmen (Haarschnitt 30 Min., Bart 20 Min. …). Bitte im Admin anpassen.
- **Impressum**: UID-Nummer und vertretungsberechtigte Person ergänzen (Platzhalter in `impressum/index.php`).
- **Jobs-Seite**: Auf der alten Seite stand die Mail-Adresse `kontakt@jordancoiffeur.com`. Übernommen wurde `.ch`, weil die Seite sonst überall `.ch` verwendet.
- **Datenschutz**: Dort steht, dass die Daten bei einem Hoster in der Schweiz liegen. Bitte bestätigen.
