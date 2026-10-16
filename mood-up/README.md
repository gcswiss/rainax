# Webseite MoodUp (mood-up.ch)

Eigenständige Produktseite für die App MoodUp, unabhängig vom RAINAX-Design.
Statisch (HTML, CSS, JavaScript, ohne Build-Schritt), alle Pfade relativ: Der Ordnerinhalt kann
direkt als Wurzel von mood-up.ch hochgeladen werden.

Gestaltung «Nacht in der Stadt»: tiefes Schwarz-Violett, Neon-Verlauf Pink → Violett → Amber,
drei Stimmungsfarben (Chillig Türkis, Gesellig Amber, Party Pink). Schriften: Bricolage Grotesque und Inter (Google Fonts).

## Lokal ansehen

```bash
cd mood-up && python3 -m http.server 8000
# → http://localhost:8000
```

## Aufbau

| Datei | Inhalt |
|---|---|
| `index.html` | ganze Seite: Hero mit Phone, So geht's, Funktionen, Gäste/Betriebe, Geschichte, Fragen, Download |
| `style.css` | Design-Tokens und Layout |
| `main.js` | Menü, Live-Map, Stimmungsfilter, Tabs, Scroll-Effekte |
| `favicon.svg` | Platzhalter-Icon im MoodUp-Verlauf |

## Bewegung

Alles ist abgeschaltet, wenn «Bewegung reduzieren» eingestellt ist.

- Hero: Die Live-Map im Phone wechselt alle paar Sekunden das ausgewählte Lokal; das Phone kippt zur Maus
- Funktionen: Stimmungsfilter zum Ausprobieren, die Karte blendet passende Lokale ein und andere aus
- Aussage: Die Wörter leuchten beim Lesen nacheinander auf
- Laufband mit Musikstilen und Stimmungen

Die Lokale auf den Karten sind Beispiele (Typen wie «Rooftop-Bar», «Techno-Club»), keine echten Betriebe.

## Noch offen (Platzhalter)

Alle offenen Stellen sind im HTML mit `PLATZHALTER` kommentiert bzw. gelb markiert:

- Originallogo statt Wortmarke und Platzhalter-Icon
- Links zum App Store und zu Google Play (je zweimal: Hero und Download)
- E-Mail-Adresse (Footer und Button «Betrieb anmelden»)
- Links zu Instagram / TikTok
- Impressum und Datenschutz
- `og:image` für Vorschauen in sozialen Netzwerken
- optional: echte App-Screenshots statt der gezeichneten Live-Map
