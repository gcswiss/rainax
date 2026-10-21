#!/usr/bin/env python3
"""Baut die Unterseiten der Mood-Up-Webseite aus gemeinsamen Bausteinen.

Aufruf (aus dem Ordner mood-up/):  python3 tools/build.py
Erzeugt bzw. aktualisiert Kopfzeile und Footer in index.html sowie alle Unterseiten.
Offene Inhalte sind mit PLATZHALTER markiert und gelb hervorgehoben.
"""
import pathlib, re

ROOT = pathlib.Path(__file__).resolve().parent.parent

NAV = [
    ("Die App", "#app", True),
    ("Für Clubs", "#clubs", True),
    ("Preise", "preise/", False),
    ("FAQ", "faq/", False),
    ("Über uns", "ueber-uns/", False),
    ("Kontakt", "kontakt/", False),
]

ICONS = """<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <symbol id="i-radar" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7.2" fill="none" stroke="#0a0a0c" stroke-width="2.6"/><circle cx="12" cy="12" r="2.6" fill="#0a0a0c"/></symbol>
    <symbol id="i-apple" viewBox="0 0 24 24"><path d="M16.37 12.6c-.02-2.2 1.8-3.26 1.88-3.31-1.02-1.5-2.62-1.7-3.19-1.72-1.36-.14-2.65.8-3.34.8-.69 0-1.75-.78-2.88-.76-1.48.02-2.85.86-3.61 2.19-1.54 2.67-.39 6.62 1.11 8.79.73 1.06 1.6 2.25 2.75 2.2 1.1-.04 1.52-.71 2.85-.71 1.33 0 1.7.71 2.87.69 1.19-.02 1.94-1.08 2.66-2.14.84-1.23 1.19-2.42 1.21-2.48-.03-.01-2.32-.89-2.34-3.53zM14.2 6.14c.61-.74 1.02-1.76.9-2.78-.88.04-1.94.58-2.57 1.32-.56.65-1.06 1.69-.93 2.69.98.08 1.98-.5 2.6-1.23z"/></symbol>
    <symbol id="i-play" viewBox="0 0 24 24"><path d="M3.6 2.2c-.2.2-.3.6-.3 1v17.6c0 .4.1.8.3 1l9.9-9.8-9.9-9.8zm10.6 10.5 2.8 2.8-11.9 6.8 9.1-9.6zm0-1.4L5.1 1.7 17 8.5l-2.8 2.8zm3.6-2.4 3.2 1.8c.9.5.9 1.4 0 1.9l-3.2 1.8-3-3 3-2.5z"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  </defs>
</svg>"""


def rel(depth: int, target: str, is_home_anchor: bool) -> str:
    base = "../" * depth if depth else ""
    if is_home_anchor:
        return (base or "./") + target if depth else target
    return base + target


def header(depth: int, current: str | None) -> str:
    home = ("../" * depth) or "./"
    base = "../" * depth
    items = []
    for label, target, anchor in NAV:
        href = rel(depth, target, anchor)
        cur = ' aria-current="page"' if current == target else ""
        items.append(f'<a href="{href}"{cur}>{label}</a>')
    nav = "\n      ".join(items)
    menu = "\n    ".join(items)
    dl = rel(depth, "#download", True)
    return f"""<header class="header" data-header>
  <div class="header__in">
    <a class="logo" href="{home}" aria-label="Mood Up – Startseite"><img class="logo__img" src="{base}assets/logo.webp" width="900" height="209" alt="Mood Up"></a>
    <nav class="nav" aria-label="Hauptmenü">
      {nav}
    </nav>
    <a class="btn btn--pink btn--sm header__cta" href="{dl}">App laden</a>
    <button class="burger" type="button" aria-controls="menu" aria-expanded="false" aria-label="Menü öffnen"><span></span><span></span></button>
  </div>
  <nav class="menu" id="menu" aria-label="Menü" hidden>
    {menu}
    <a class="btn btn--pink" href="{dl}">App laden</a>
  </nav>
</header>"""


def footer(depth: int) -> str:
    b = "../" * depth
    home = b or "./"
    a = lambda t: rel(depth, t, True)
    return f"""<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        <a class="logo logo--footer" href="{home}" aria-label="Mood Up – Startseite"><img class="logo__img" src="{b}assets/logo.webp" width="900" height="209" alt="Mood Up"></a>
        <p style="margin-top:16px">Find your vibe! Dein Radar für Partys in deiner Nähe. Swiss made.</p>
      </div>
      <div>
        <h3>App</h3>
        <ul><li><a href="{a('#download')}">App Download</a></li><li><a href="{a('#app')}">So funktioniert's</a></li><li><a href="{a('#filter')}">Smart Filter</a></li><li><a href="{a('#clubs')}">Für Clubs</a></li></ul>
      </div>
      <div>
        <h3>Mood Up</h3>
        <ul><li><a href="{home}">Home</a></li><li><a href="{b}preise/">Preise</a></li><li><a href="{b}faq/">FAQ</a></li><li><a href="{b}ueber-uns/">Über uns</a></li><li><a href="{b}kontakt/">Kontakt</a></li></ul>
      </div>
      <div>
        <h3>Links</h3>
        <ul><li><a href="{b}agb/">AGB's</a></li><li><a href="{b}impressum/">Impressum</a></li><li><a href="{b}datenschutz/">Datenschutzerklärung</a></li></ul>
      </div>
      <div>
        <h3>Kontakt</h3>
        <address>Sirnacherstrasse 5<br>9500 Wil SG<br><a href="tel:+41715236505">+41 71 523 65 05</a><br><a href="mailto:info@mood-up.ch">info@mood-up.ch</a></address>
      </div>
    </div>
    <div class="footer__bottom">
      <p>© <span data-year>2026</span> Mood Up · Mood Up folgt den strengen Schweizer Datenschutzgesetzen.</p>
      <span class="draft">Entwurf · nicht online</span>
    </div>
    <img class="wordmark" src="{b}assets/logo-pink.webp" width="900" height="209" alt="" aria-hidden="true">
  </div>
</footer>"""


def head(depth: int, title: str, desc: str) -> str:
    b = "../" * depth
    return f"""<!doctype html>
<html lang="de-CH">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{title}</title>
<meta name="description" content="{desc}">
<meta name="theme-color" content="#0a0a0c">
<meta name="robots" content="noindex">
<!-- ENTWURF / MOCKUP: nicht online. Offene Stellen sind mit PLATZHALTER markiert. -->
<link rel="icon" href="{b}assets/favicon.png" type="image/png">
<link rel="apple-touch-icon" href="{b}assets/apple-touch-icon.png">
<link rel="stylesheet" href="{b}assets/fonts.css">
<link rel="stylesheet" href="{b}assets/site.css">
<script src="{b}assets/site.js" defer></script>
</head>"""


LINK_RE = re.compile(r'href="((?:\.\./)*)([a-z-]+/)?(#[a-z-]*)?"')


def offline_links(html: str) -> str:
    """Macht Links explizit (…/index.html), damit die Seite auch per Doppelklick ohne Server funktioniert."""
    def fix(m):
        up, folder, anchor = m.group(1), m.group(2) or "", m.group(3) or ""
        if not up and not folder:
            return m.group(0)  # reiner Anker auf derselben Seite
        return f'href="{up}{folder}index.html{anchor}"'
    html = LINK_RE.sub(fix, html)
    return html.replace('href="./"', 'href="index.html"')


def page(slug: str, title: str, desc: str, body: str) -> None:
    html = f"""{head(1, title, desc)}
<body>
<a class="skip" href="#main">Zum Inhalt springen</a>
{ICONS}
{header(1, slug + "/")}
<main id="main">
{body}
</main>
{footer(1)}
</body>
</html>
"""
    out = ROOT / slug / "index.html"
    out.parent.mkdir(exist_ok=True)
    out.write_text(offline_links(html), encoding="utf-8")


def ph(text: str) -> str:
    return f'<span class="placeholder">{text}</span>'


def hero(eyebrow: str, title: str, lead: str) -> str:
    return f"""<section class="page-hero">
  <div class="container">
    <span class="eyebrow reveal">{eyebrow}</span>
    <h1 class="reveal" style="--d:60ms">{title}</h1>
    <p class="page-hero__lead reveal" style="--d:120ms">{lead}</p>
  </div>
</section>"""


STORES = """<div class="stores">
  <!-- PLATZHALTER: Store-Links -->
  <a class="store" href="#" rel="noopener" aria-label="Mood Up im App Store laden"><svg aria-hidden="true"><use href="#i-apple"/></svg><span><small>Laden im</small>App Store</span></a>
  <a class="store" href="#" rel="noopener" aria-label="Mood Up bei Google Play laden"><svg aria-hidden="true"><use href="#i-play"/></svg><span><small>Jetzt bei</small>Google Play</span></a>
</div>"""

FAQ_ITEMS = [
    ("Was ist Mood Up?", "Mood Up ist dein Radar für Partys in der Nähe. Die interaktive Karte zeigt dir live die beliebtesten Partys, Bars und Clubs in deiner Umgebung, ohne endloses Suchen."),
    ("Kostet Mood Up etwas?", "Nein, für Nutzerinnen und Nutzer ist Mood Up gratis."),
    ("Gibt es Mood Up für iPhone und Android?", "Ja. Im App Store findest du die App als «Mood Up - Party Radar», ausserdem gibt es sie für Android."),
    ("Wonach kann ich filtern?", "Mit dem Smart Filter wählst du aus vielen Kategorien, zum Beispiel Art der Location, Musik, Alter und Datum."),
    ("Kann ich im Voraus planen?", "Ja. Wähle heute, morgen oder ein Datum im Kalender und plane deinen Abend schon jetzt."),
    ("Kann ich die Stimmung vorher sehen?", "Ja. Du checkst Stimmung und Musikstil, bevor du losgehst."),
    ("Wie funktioniert der Bar-Chat?", "Bist du in einer Location eingecheckt, kannst du im Bar-Chat direkt mit ihr schreiben."),
    ("Wie kommt meine Location auf Mood Up?", 'Schreib uns an <a href="mailto:info@mood-up.ch">info@mood-up.ch</a>. Danach erfasst du deine Events schnell und unkompliziert selbst und erscheinst auf der Live-Party-Map.'),
    ("Was passiert mit meinen Daten?", 'Mood Up hält sich an das strenge Schweizer Datenschutzgesetz. Details findest du in der <a href="../datenschutz/">Datenschutzerklärung</a>.'),
]


def faq_list(items, prefix="") -> str:
    out = []
    for q, a in items:
        a = a.replace('href="../', f'href="{prefix}')
        out.append(f'<details class="reveal"><summary>{q}<i aria-hidden="true"></i></summary><p>{a}</p></details>')
    return "\n        ".join(out)


def build_pages() -> None:
    # Preise
    page("preise", "Preise | Mood Up", "Mood Up ist für Nutzerinnen und Nutzer gratis. Angebote für Clubs und Veranstalter.",
         hero("Preise", "Für Users gratis.<br>Für Clubs <em>sichtbar.</em>", "Ausgehen mit Mood Up kostet nichts. Für Clubs und Veranstalter gibt es Angebote, mit denen ihr auf der Live-Party-Map gefunden werdet.") + f"""
<section class="section">
  <div class="container plans">
    <article class="plan reveal">
      <span class="plan__tag">Für Users</span>
      <h2 class="plan__price">Gratis</h2>
      <p class="plan__sub">Die richtigen Orte. Zur richtigen Zeit.</p>
      <ul class="ticks"><li>Live-Map mit personalisierten Empfehlungen</li><li>Filter nach Musikstil, Alter &amp; Stimmung</li><li>Events in der Nähe entdecken</li><li>Lieblingslocations folgen</li><li>Exklusive Inhalte erhalten</li></ul>
      <a class="btn btn--pink" href="../#download">App laden <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </article>
    <article class="plan plan--pink reveal" style="--d:80ms">
      <span class="plan__tag">Für Bars, Clubs &amp; Veranstalter</span>
      <h2 class="plan__price">{ph("[PREIS]")}</h2>
      <!-- PLATZHALTER: Preise und Pakete von der bestehenden Seite mood-up.ch/Preise übernehmen -->
      <p class="plan__sub">{ph("[Paketname / Laufzeit von der bestehenden Seite]")}</p>
      <ul class="ticks"><li>Eigene Profilseite mit Bildern, Videos &amp; Infos</li><li>Follower aufbauen und langfristig binden</li><li>Direkte Push-Nachrichten an Follower</li><li>Radius-Push für lokale Zielgruppen</li><li>Gezielte Ansprache nach Musikstil, Eventtyp &amp; Alter</li><li>Höhere Auslastung</li></ul>
      <a class="btn btn--ink" href="../kontakt/">Location anmelden <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </article>
  </div>
  <p class="container note reveal">Fragen zu den Angeboten? <a href="../kontakt/">Schreib uns</a>.</p>
</section>""")

    # FAQ
    page("faq", "FAQ | Mood Up", "Häufige Fragen zu Mood Up, dem Radar für Partys in der Nähe.",
         hero("FAQ", "Gut zu wissen.", "Die wichtigsten Antworten rund um Mood Up. Deine Frage ist nicht dabei? Schreib uns.") + f"""
<section class="section section--tight">
  <div class="container faq-page">
    <div class="faq__list">
        {faq_list(FAQ_ITEMS, "../")}
        <!-- PLATZHALTER: weitere Fragen von der bestehenden Seite mood-up.ch/FAQ übernehmen -->
    </div>
    <aside class="ask reveal">
      <h2>Fragen?<br>Schreibe uns.</h2>
      <p>Wir antworten so schnell wie möglich.</p>
      <a class="btn btn--pink" href="mailto:info@mood-up.ch">info@mood-up.ch</a>
    </aside>
  </div>
</section>""")

    # Über uns
    page("ueber-uns", "Über uns | Mood Up", "Mood Up ist in Wil SG entstanden: aus Gesprächen mit der Gastronomie, gebaut für die ganze Schweiz.",
         hero("Über uns", "Aus Wil.<br>Für jede <em>Partynacht.</em>", "Mood Up ist aus Gesprächen mit der Gastronomie entstanden. Gäste suchen Erlebnisse, die zu ihnen passen, Bars und Clubs kämpfen um Sichtbarkeit. Wir bringen beide zusammen.") + f"""
<section class="section">
  <div class="container about">
    <div class="about__story reveal">
      <h2>Unsere Geschichte</h2>
      <p>Die Idee zu Mood Up kam 2023 im direkten Austausch mit Bars und Clubs. Statt auf Google, Maps und Instagram zu suchen, sollte ein Blick genügen: Wo ist heute was los, und passt es zu mir?</p>
      <p>Von der ersten Idee über Design und Entwicklung bis zum Marktstart ist die App in Wil SG entstanden. Gestartet in Wil, gebaut für die ganze Schweiz.</p>
      <!-- PLATZHALTER: Text der bestehenden Seite mood-up.ch/Über uns abgleichen bzw. übernehmen -->
    </div>
    <ol class="timeline reveal" style="--d:80ms">
      <li><b>2023</b><span>Aus Gesprächen mit der Gastronomie entsteht die Idee zu Mood Up.</span></li>
      <li><b>Heute</b><span>Mood Up zeigt live, wo die Partys sind, und bringt Gäste und Locations zusammen.</span></li>
      <li><b>Morgen</b><span>Gebaut für die ganze Schweiz.</span></li>
    </ol>
  </div>
</section>
<section class="values">
  <div class="container values__grid">
    <div class="reveal"><b>Gratis.</b><span>Für Nutzerinnen und Nutzer kostet Mood Up nichts.</span></div>
    <div class="reveal" style="--d:60ms"><b>Swiss made.</b><span>Entwickelt in Wil SG.</span></div>
    <div class="reveal" style="--d:120ms"><b>Sicher.</b><span>Wir halten uns an das strenge Schweizer Datenschutzgesetz.</span></div>
  </div>
</section>
<section class="section">
  <div class="container">
    <span class="eyebrow reveal">Für die Zukunft</span>
    <h2 class="future__title reveal">Ein vernetztes Nachtleben.</h2>
    <div class="future">
      <div class="reveal"><i>01</i><p>Digitale Infrastruktur für Menschen, Betriebe und Veranstalter</p></div>
      <div class="reveal" style="--d:60ms"><i>02</i><p>Skalierbares Potenzial in der Schweiz und darüber hinaus</p></div>
      <div class="reveal" style="--d:120ms"><i>03</i><p>Innovation, die reale Begegnungen fördert</p></div>
      <div class="reveal" style="--d:180ms"><i>04</i><p>Ein wachsender Markt mit internationalen Möglichkeiten</p></div>
    </div>
  </div>
</section>""")

    # Kontakt
    page("kontakt", "Kontakt | Mood Up", "Kontakt zu Mood Up: Sirnacherstrasse 5, 9500 Wil SG, +41 71 523 65 05, info@mood-up.ch.",
         hero("Kontakt", "Fragen?<br>Schreibe <em>uns.</em>", "Ob Gast, Club oder Veranstalter: Wir freuen uns auf deine Nachricht.") + """
<section class="section section--tight">
  <div class="container contact">
    <div class="contact__cards">
      <a class="ccard reveal" href="mailto:info@mood-up.ch"><span>E-Mail</span><b>info@mood-up.ch</b></a>
      <a class="ccard reveal" style="--d:60ms" href="tel:+41715236505"><span>Telefon</span><b>+41 71 523 65 05</b></a>
      <div class="ccard reveal" style="--d:120ms"><span>Adresse</span><b>Sirnacherstrasse 5<br>9500 Wil SG</b></div>
    </div>
    <form class="form reveal" data-mailto="info@mood-up.ch" novalidate>
      <fieldset class="seg">
        <legend>Ich bin</legend>
        <label><input type="radio" name="who" value="Gast" checked><span>Gast</span></label>
        <label><input type="radio" name="who" value="Club / Bar"><span>Club / Bar</span></label>
        <label><input type="radio" name="who" value="Veranstalter"><span>Veranstalter</span></label>
      </fieldset>
      <div class="field"><label for="f-name">Name</label><input id="f-name" name="name" autocomplete="name" required><p class="err" id="e-name" hidden>Bitte gib deinen Namen ein.</p></div>
      <div class="field"><label for="f-mail">E-Mail</label><input id="f-mail" name="email" type="email" autocomplete="email" required><p class="err" id="e-mail" hidden>Bitte gib eine gültige E-Mail-Adresse ein.</p></div>
      <div class="field"><label for="f-msg">Nachricht</label><textarea id="f-msg" name="message" rows="5" required></textarea><p class="err" id="e-msg" hidden>Bitte schreib uns eine Nachricht.</p></div>
      <button class="btn btn--pink" type="submit">Nachricht senden <svg aria-hidden="true"><use href="#i-arrow"/></svg></button>
      <p class="form__note">Öffnet dein E-Mail-Programm mit der fertigen Nachricht.
        <!-- PLATZHALTER: für echtes Absenden ohne E-Mail-Programm einen Formular-Dienst anbinden --></p>
    </form>
  </div>
</section>""")

    # Rechtliches – Texte müssen von der bestehenden Seite übernommen werden
    legal = {
        "impressum": ("Impressum", f"""<h2>Kontaktadresse</h2>
<p>{ph("[Firmenname / Betreiber]")}<br>Sirnacherstrasse 5<br>9500 Wil SG<br>Schweiz</p>
<p>Telefon: <a href="tel:+41715236505">+41 71 523 65 05</a><br>E-Mail: <a href="mailto:info@mood-up.ch">info@mood-up.ch</a></p>
<h2>Vertretungsberechtigte Personen</h2><p>{ph("[Namen]")}</p>
<h2>Handelsregister</h2><p>{ph("[UID / Handelsregister-Nummer]")}</p>
<h2>Haftungsausschluss, Urheberrechte</h2><p>{ph("[Text von der bestehenden Seite mood-up.ch/Impressum übernehmen]")}</p>"""),
        "datenschutz": ("Datenschutzerklärung", f"""<p>Mood Up hält sich an das strenge Schweizer Datenschutzgesetz.</p>
<p>{ph("[Vollständige Datenschutzerklärung von der bestehenden Seite mood-up.ch übernehmen. ]")}</p>"""),
        "agb": ("AGB", f"""<p>{ph("[Allgemeine Geschäftsbedingungen von der bestehenden Seite mood-up.ch übernehmen]")}</p>"""),
    }
    for slug, (title, body) in legal.items():
        page(slug, f"{title} | Mood Up", f"{title} von Mood Up.",
             hero("Rechtliches", title, "Stand: " + ph("[Datum]")) + f"""
<section class="section section--tight">
  <div class="container prose">
    <!-- PLATZHALTER: Rechtstext nicht erfinden, sondern von der bestehenden Seite übernehmen -->
    {body}
  </div>
</section>""")


def update_home() -> None:
    p = ROOT / "index.html"
    s = p.read_text(encoding="utf-8")
    s = re.sub(r"<header class=\"header\" data-header>.*?</header>", lambda m: header(0, None), s, count=1, flags=re.S)
    s = re.sub(r"<footer class=\"footer\">.*?</footer>", lambda m: footer(0), s, count=1, flags=re.S)
    s = s.replace('<link rel="preconnect" href="https://fonts.googleapis.com">\n', "")
    s = s.replace('<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>\n', "")
    s = re.sub(r'<link rel="stylesheet" href="https://fonts.googleapis.com[^"]*">', '<link rel="stylesheet" href="assets/fonts.css">', s)
    p.write_text(offline_links(s), encoding="utf-8")


if __name__ == "__main__":
    update_home()
    build_pages()
    print("ok")
