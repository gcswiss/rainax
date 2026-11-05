#!/usr/bin/env python3
"""Baut die Unterseiten der Mood-Up-Webseite aus gemeinsamen Bausteinen.

Aufruf (aus dem Ordner mood-up/):  python3 tools/build.py
Erzeugt bzw. aktualisiert Kopfzeile und Footer in index.html sowie alle Unterseiten.
Offene Inhalte sind mit PLATZHALTER markiert und gelb hervorgehoben.
"""
import html as htmllib
import pathlib, posixpath, re

from i18n import LANG_NAMES, LANGS, T, UI

ROOT = pathlib.Path(__file__).resolve().parent.parent

NAV = [
    ("Die App", "#app", True),
    ("Für Clubs", "#clubs", True),
    ("Preise", "preise/", False),
    ("FAQ", "faq/", False),
    ("Über uns", "ueber-uns/", False),
    ("Kontakt", "kontakt/", False),
]

SOCIAL = [
    ("Instagram", "https://www.instagram.com/moodup.ch",
     '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>'),
    ("TikTok", "https://www.tiktok.com/@moodup.ch",
     '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.4 2.6 2.2 4.4 5 4.7"/>'),
    ("Facebook", "https://www.facebook.com/profile.php?id=61587538247145",
     '<path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V10H6.5v3.5H9V21h3.5v-7.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15z"/>'),
    ("LinkedIn", "https://www.linkedin.com/company/moodup-ch",
     '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M7.5 10.5V17M7.5 7.2v.1M11 17v-6.5M11 13.5c0-2 1.3-3 2.8-3s2.7 1 2.7 3V17"/>'),
]


def social_links(cls: str = "social") -> str:
    items = "".join(
        f'<a class="{cls}__a" href="{url}" target="_blank" rel="noopener" aria-label="Mood Up auf {name}"><svg viewBox="0 0 24 24" aria-hidden="true">{path}</svg><span>{name}</span></a>'
        for name, url, path in SOCIAL)
    return f'<div class="{cls}">{items}</div>'


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


def header(depth: int, current: str | None, extra: str = "") -> str:
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
    </nav>{extra or "<!--LANGSELECT-->"}
    <a class="btn btn--pink btn--sm header__cta" href="{dl}">App laden</a>
    <button class="burger" type="button" aria-controls="menu" aria-expanded="false" aria-label="Menü öffnen" data-open="Menü öffnen" data-close="Menü schliessen"><span></span><span></span></button>
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
        {social_links()}
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
        <ul><li><a href="{b}agb/">AGB's</a></li><li><a href="{b}impressum/">Impressum</a></li><li><a href="{b}datenschutz/">Datenschutzerklärung</a></li><li><a href="{b}konto-loeschen/">Konto löschen</a></li></ul>
      </div>
      <div>
        <h3>Kontakt</h3>
        <address>Sirnacherstrasse 5<br>9500 Wil SG<br><a href="tel:+41715236881">+41 71 523 68 81</a><br><a href="mailto:info@mood-up.ch">info@mood-up.ch</a></address>
      </div>
    </div>
    <div class="footer__bottom">
      <p>© <span data-year>2026</span> Rainax AG. Alle Rechte vorbehalten. · Mood Up folgt den strengen Schweizer Datenschutzgesetzen.</p>
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
<meta property="og:type" content="website">
<meta property="og:site_name" content="Mood Up">
<meta property="og:title" content="{title}">
<meta property="og:description" content="{desc}">
<meta property="og:locale" content="de_CH">
<meta property="og:image" content="https://www.mood-up.ch/assets/og-image.png">
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


def page(slug: str, title: str, desc: str, body: str, header_extra: str = "") -> None:
    html = f"""{head(1, title, desc)}
<body>
<a class="skip" href="#main">Zum Inhalt springen</a>
{ICONS}
{header(1, slug + "/", header_extra)}
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
  <a class="store" href="https://apps.apple.com/ch/app/mood-up-party-radar/id6748701886" target="_blank" rel="noopener" aria-label="Mood Up im App Store laden"><svg aria-hidden="true"><use href="#i-apple"/></svg><span><small>Laden im</small>App Store</span></a>
  <a class="store" href="https://play.google.com/store/apps/details?id=com.mycompany.moodup" target="_blank" rel="noopener" aria-label="Mood Up bei Google Play laden"><svg aria-hidden="true"><use href="#i-play"/></svg><span><small>Jetzt bei</small>Google Play</span></a>
</div>"""

FAQ_ITEMS = [
    ("Was ist Mood Up?", "Mood Up ist dein Radar für Partys in der Nähe. Die interaktive Karte zeigt dir live die beliebtesten Partys, Bars und Clubs in deiner Umgebung, ohne endloses Suchen."),
    ("Kostet Mood Up etwas?", "Nein, für Nutzerinnen und Nutzer ist Mood Up gratis."),
    ("Gibt es Mood Up für iPhone und Android?", "Ja. Im App Store findest du die App als «Mood Up - Party Radar», ausserdem gibt es sie für Android."),
    ("Wonach kann ich filtern?", "Mit dem Smart Filter wählst du aus vielen Kategorien, zum Beispiel Art der Location, Musik, Alter und Datum."),
    ("Was kostet Mood Up für Bars und Clubs?", "Der Einstieg ist gratis: Location auf der Karte, Party-Erstellung, Chat-Funktion für Users und Followers sammeln. Mit Pro für CHF 49.90 pro Monat kommen Statistik (Views und Klicks), Followers anschreiben und Storys posten dazu."),
    ("Kann ich im Voraus planen?", "Ja. Wähle heute, morgen oder ein Datum im Kalender und plane deinen Abend schon jetzt."),
    ("Kann ich die Stimmung vorher sehen?", "Ja. Du checkst Stimmung und Musikstil, bevor du losgehst."),
    ("Wie funktioniert der Gäste-Chat?", "Bist du vor Ort, trittst du dem Gäste-Chat der Location bei und schreibst direkt mit."),
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
    page("preise", "Preise | Mood Up", "Für Users immer gratis. Für Bars und Clubs: Gratis-Eintrag oder Pro für CHF 49.90.",
         hero("Preise", "Einfach starten.<br>Mehr mit <em>Pro.</em>", "Für Users ist Mood Up immer gratis. Bars und Clubs starten kostenlos auf der Karte und holen mit Pro noch mehr aus ihrer Location heraus.") + """
<section class="section section--tight">
  <div class="container">
    <div class="free-users reveal">
      <span class="plan__tag">Für Users</span>
      <p><b>Immer gratis.</b> Live-Map, Smart Filter, Chat und Lieblingslocations folgen kosten nichts.</p>
      <a class="btn btn--pink btn--sm" href="../#download">App laden <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
    <h2 class="plans__title reveal">Für Bars &amp; Clubs</h2>
    <div class="plans">
      <article class="plan reveal">
        <span class="plan__tag">Gratis</span>
        <h3 class="plan__price">CHF 0</h3>
        <p class="plan__sub">Alles, um gefunden zu werden.</p>
        <ul class="ticks"><li>Location auf der Karte</li><li>Party-Erstellung</li><li>Chat-Funktion für Users</li><li>Followers sammeln</li></ul>
        <a class="btn btn--pink" href="../kontakt/">Gratis starten <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
      </article>
      <article class="plan plan--pink reveal" style="--d:80ms">
        <span class="plan__tag">Pro</span>
        <h3 class="plan__price">CHF 49.90<small> / Monat</small></h3>
        <p class="plan__sub">Alles aus Gratis, dazu:</p>
        <ul class="ticks"><li>Statistik: Views und Klicks</li><li>Followers anschreiben</li><li>Storys posten</li></ul>
        <a class="btn btn--ink" href="../kontakt/">Pro anfragen <svg aria-hidden="true"><use href="#i-arrow"/></svg></a>
      </article>
    </div>
    <p class="note reveal">Fragen zu den Angeboten? <a href="../kontakt/">Schreib uns</a>.</p>
  </div>
</section>""")

    # FAQ
    page("faq", "FAQ | Mood Up", "Häufige Fragen zu Mood Up, dem Radar für Partys in der Nähe.",
         hero("FAQ", "Gut zu wissen.", "Die wichtigsten Antworten rund um Mood Up. Deine Frage ist nicht dabei? Schreib uns.") + f"""
<section class="section section--tight">
  <div class="container faq-page">
    <div class="faq__list">
        {faq_list(FAQ_ITEMS, "../")}
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
    page("kontakt", "Kontakt | Mood Up", "Kontakt zu Mood Up: Sirnacherstrasse 5, 9500 Wil SG, +41 71 523 68 81, info@mood-up.ch.",
         hero("Kontakt", "Fragen?<br>Schreibe <em>uns.</em>", "Ob Gast, Club oder Veranstalter: Wir freuen uns auf deine Nachricht.") + """
<section class="section section--tight">
  <div class="container contact">
    <div class="contact__cards">
      <a class="ccard reveal" href="mailto:info@mood-up.ch"><span>E-Mail</span><b>info@mood-up.ch</b></a>
      <a class="ccard reveal" style="--d:60ms" href="tel:+41715236881"><span>Telefon</span><b>+41 71 523 68 81</b></a>
      <div class="ccard reveal" style="--d:120ms"><span>Adresse</span><b>Sirnacherstrasse 5<br>9500 Wil SG</b></div>
      <div class="ccard ccard--social reveal" style="--d:180ms"><span>Folge uns</span>""" + social_links("social") + """</div>
    </div>
    <form class="form reveal" data-mailto="info@mood-up.ch" data-subject="Anfrage über mood-up.ch" data-l-name="Name" data-l-mail="E-Mail" data-l-who="Ich bin" novalidate>
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
      <p class="form__note">Öffnet dein E-Mail-Programm mit der fertigen Nachricht.</p>
    </form>
  </div>
</section>""")

    # Rechtliches – Texte müssen von der bestehenden Seite übernommen werden
    legal = {
        "impressum": ("Impressum", """<h2>Kontaktadresse</h2>
<p>Rainax AG<br>Sirnacherstrasse 5<br>9500 Wil SG<br>Schweiz</p>
<p>Telefon: <a href="tel:+41715236881">+41 71 523 68 81</a><br>E-Mail: <a href="mailto:info@mood-up.ch">info@mood-up.ch</a></p>
<h2>Verantwortliche Personen</h2>
<p>Massimo Nardone<br>George Chamoun</p>
<h2>Unternehmens-Identifikationsnummer (UID)</h2>
<p>CHE-442.927.494</p>
<h2>Haftungsausschluss</h2>
<p>Die Rainax AG übernimmt keine Gewähr für die Richtigkeit, Vollständigkeit und Aktualität der bereitgestellten Informationen.</p>
<p>Für Inhalte externer Links sind ausschliesslich deren Betreiber verantwortlich.</p>
<h2>Urheberrecht</h2>
<p>Alle Texte, Bilder, Grafiken und das Design dieser Website und App unterliegen dem Urheberrecht der Rainax AG oder Dritter.</p>
<p>Die Vervielfältigung oder Verwendung dieser Inhalte ist ohne ausdrückliche schriftliche Genehmigung nicht gestattet.</p>
<h2>Anwendbares Recht</h2>
<p>Es gilt ausschliesslich schweizerisches Recht.</p>
<p>Gerichtsstand ist St. Gallen (Schweiz).</p>
<p class="prose__copy">© 2026 Rainax AG. Alle Rechte vorbehalten.</p>"""),
        "datenschutz": ("Datenschutzerklärung", """<h2>1. Verantwortliche Stelle</h2>
<p>Rainax AG<br>Sirnacherstrasse 5<br>9500 Wil, Schweiz<br>E-Mail: <a href="mailto:info@rainax.ch">info@rainax.ch</a></p>
<p>Die Rainax AG ist Betreiberin der App Mood Up und verantwortlich für die Verarbeitung personenbezogener Daten gemäss dem schweizerischen Datenschutzgesetz (DSG) und der EU-Datenschutz-Grundverordnung (DSGVO).</p>
<h2>2. Allgemeines zur Datenverarbeitung</h2>
<p>Der Schutz Ihrer persönlichen Daten ist uns wichtig. Wir verarbeiten personenbezogene Daten nur, soweit dies für den Betrieb unserer App, der Website und der angebotenen Dienstleistungen erforderlich ist.</p>
<p>Wir erheben und speichern personenbezogene Daten grundsätzlich nur, soweit dies gesetzlich erlaubt oder Sie ausdrücklich eingewilligt haben.</p>
<h2>3. Nutzung der App „Mood Up“</h2>
<h3>3.1 Keine Registrierung</h3>
<p>Die Nutzung der App ist ohne Anmeldung möglich. Es werden keine Konten erstellt und keine personenbezogenen Daten zentral gespeichert.</p>
<h3>3.2 Standortdaten</h3>
<p>Für die Hauptfunktion der App (Anzeige von Partys und Events in der Nähe) wird Ihr aktueller Standort verwendet.</p>
<p>Diese Standortdaten werden ausschliesslich lokal auf Ihrem Gerät verarbeitet und nicht an unsere Server übermittelt oder dort gespeichert.</p>
<h3>3.3 Nutzungsdaten</h3>
<p>Beim Zugriff auf die App können automatisch technische Informationen übermittelt werden (z. B. Gerätetyp, Betriebssystem, App-Version).</p>
<p>Diese Daten werden anonymisiert ausgewertet, um Stabilität, Sicherheit und Performance der App zu gewährleisten.</p>
<h2>4. Datenverarbeitung bei Club-Abonnements</h2>
<h3>4.1 Registrierung und Vertrag</h3>
<p>Clubinhaber schliessen über die Website oder innerhalb der App ein monatliches Abo ab. Dabei werden zur Vertragsabwicklung folgende Daten erhoben:</p>
<ul><li>Name oder Firmenname</li><li>E-Mail-Adresse</li><li>Rechnungs- und Zahlungsdaten (über Stripe)</li></ul>
<h3>4.2 Zahlungsabwicklung über Stripe</h3>
<p>Die Bezahlung erfolgt über den Zahlungsdienstleister Stripe Payments Europe Ltd., Irland.</p>
<p>Stripe verarbeitet personenbezogene Daten gemäss den geltenden Datenschutzvorschriften.</p>
<p>Weitere Informationen: <a href="https://stripe.com/privacy" rel="noopener">https://stripe.com/privacy</a></p>
<p>Die Rainax AG erhält keinen Zugriff auf Kreditkarten- oder Kontoinformationen.</p>
<h2>5. Zweck der Datenverarbeitung</h2>
<p>Die Verarbeitung erfolgt zu folgenden Zwecken:</p>
<ul><li>Bereitstellung und Verbesserung der App</li><li>Anzeige von Events und Standorten</li><li>Verwaltung und Abrechnung von Club-Abonnements</li><li>Kommunikation mit Kunden und Support</li></ul>
<h2>6. Weitergabe von Daten</h2>
<p>Wir geben personenbezogene Daten grundsätzlich nicht an Dritte weiter, ausser:</p>
<ul><li>wenn dies zur Vertragserfüllung notwendig ist (z. B. Stripe-Zahlungsabwicklung),</li><li>wenn eine gesetzliche Verpflichtung besteht,</li><li>oder Sie ausdrücklich eingewilligt haben.</li></ul>
<h2>7. Datensicherheit</h2>
<p>Wir treffen angemessene technische und organisatorische Massnahmen, um Ihre Daten gegen Verlust, Missbrauch, unbefugten Zugriff oder Offenlegung zu schützen.</p>
<p>Unsere Server und Datenverarbeitungssysteme befinden sich ausschliesslich in der Schweiz oder der EU.</p>
<h2>8. Speicherdauer</h2>
<p>Personenbezogene Daten werden nur so lange gespeichert, wie es für die jeweiligen Zwecke notwendig ist oder gesetzliche Aufbewahrungspflichten bestehen.</p>
<p>Daten aus Club-Abos werden nach Kündigung und Ablauf gesetzlicher Fristen gelöscht.</p>
<h2>9. Rechte der betroffenen Personen</h2>
<p>Sie haben gemäss DSG und DSGVO folgende Rechte:</p>
<ul><li>Auskunft: Welche Daten über Sie gespeichert sind</li><li>Berichtigung: Falsche oder unvollständige Daten korrigieren lassen</li><li>Löschung: Daten löschen lassen, sofern keine gesetzliche Pflicht besteht</li><li>Einschränkung der Verarbeitung</li><li>Datenübertragbarkeit</li><li>Widerspruch gegen bestimmte Verarbeitungen</li></ul>
<p>Anfragen richten Sie bitte an: <a href="mailto:info@rainax.ch">info@rainax.ch</a></p>
<h2>10. Nutzung von Cookies und Analyse (Website)</h2>
<p>Unsere Website kann Cookies oder ähnliche Technologien verwenden, um die Benutzerfreundlichkeit zu verbessern.</p>
<p>Sie können die Verwendung von Cookies in den Einstellungen Ihres Browsers deaktivieren.</p>
<p>Wir verzichten auf Tracking-Tools wie Google Analytics innerhalb der App.</p>
<h2>11. Internationale Datenübermittlung</h2>
<p>Da die Rainax AG ihren Sitz in der Schweiz hat, kann es bei internationalen Nutzern zu grenzüberschreitenden Datenübermittlungen kommen.</p>
<p>Diese erfolgen ausschliesslich in Länder mit angemessenem Datenschutzniveau gemäss DSG und DSGVO (z. B. EU/EWR).</p>
<h2>12. Änderungen dieser Datenschutzerklärung</h2>
<p>Die Rainax AG kann diese Datenschutzerklärung bei Bedarf anpassen.</p>
<p>Die jeweils aktuelle Version ist in der App oder auf der Website abrufbar unter:<br><a href="https://www.moodup.app/datenschutz" rel="noopener">https://www.moodup.app/datenschutz</a></p>
<h2>13. Kontakt</h2>
<p>Bei Fragen oder Anliegen zum Datenschutz wenden Sie sich bitte an:</p>
<p>Rainax AG<br>Sirnacherstrasse 5<br>9500 Wil, Schweiz<br>E-Mail: <a href="mailto:info@rainax.ch">info@rainax.ch</a></p>
<p class="prose__copy">© 2026 Rainax AG. Alle Rechte vorbehalten.</p>"""),
        "agb": ("AGB", """<h2>1. Anbieter</h2>
<p>Rainax AG<br>Sirnacherstrasse 5<br>9500 Wil, Schweiz<br>E-Mail: <a href="mailto:info@rainax.ch">info@rainax.ch</a></p>
<h2>2. Geltungsbereich</h2>
<p>Diese Allgemeinen Geschäftsbedingungen (AGB) regeln die Nutzung der mobilen Applikation Mood Up („App“) sowie aller damit verbundenen Leistungen der Rainax AG („Anbieter“).</p>
<p>Sie gelten sowohl für private Nutzer („User“) als auch für registrierte Veranstalter oder Clubs („Clubinhaber“).</p>
<p>Mit dem Herunterladen oder Verwenden der App erklärt sich der Nutzer mit diesen AGB einverstanden.</p>
<h2>3. Leistungsbeschreibung</h2>
<p>Mood Up ist eine App, die in Echtzeit Partys, Events und Veranstaltungen in der Umgebung anzeigt.</p>
<p>Für Nutzer: Die Nutzung der App ist kostenlos, es ist keine Registrierung erforderlich.</p>
<p>Für Clubinhaber: Die Location kann kostenlos auf der Karte eingetragen werden, und Partys können kostenlos erstellt werden. Mit dem kostenpflichtigen Pro-Abo stehen zusätzliche Funktionen zur Verfügung.</p>
<p>Der Anbieter behält sich das Recht vor, Funktionen oder Inhalte jederzeit zu erweitern, zu ändern oder einzuschränken.</p>
<h2>4. Nutzung der App</h2>
<p>Die Nutzung der App setzt eine aktive Internetverbindung und die Freigabe des Standortes voraus.</p>
<p>Standortdaten werden ausschliesslich lokal auf dem Gerät verarbeitet und nicht an den Anbieter übermittelt.</p>
<p>Nutzer dürfen die App nur für private Zwecke verwenden und keine automatisierten Systeme oder Bots einsetzen.</p>
<h2>5. Angebote und Abonnement für Clubinhaber</h2>
<p>Clubinhaber können Mood Up kostenlos nutzen oder ein monatliches Pro-Abo abschliessen.</p>
<ul><li>Gratis: Location auf der Karte, Party-Erstellung, Chat-Funktion für Users, Followers sammeln</li><li>Pro: CHF 49.90 pro Monat, zusätzlich Statistik (Views und Klicks), Followers anschreiben, Storys posten</li><li>Zahlungsart Pro: Kreditkarte über Stripe (automatische monatliche Verlängerung)</li><li>Laufzeit Pro: 1 Monat, kündbar bis zum Ende der laufenden Periode</li></ul>
<p>Der Anbieter behält sich vor, Preise oder Konditionen nach vorheriger Ankündigung anzupassen.</p>
<h2>6. Inhalte von Clubs</h2>
<p>Für alle von Clubinhabern veröffentlichten Inhalte (Texte, Bilder, Logos, Eventbeschreibungen etc.) sind ausschliesslich die jeweiligen Clubinhaber verantwortlich.</p>
<p>Es dürfen nur Inhalte veröffentlicht werden, die keine Rechte Dritter verletzen und keine diskriminierenden, pornografischen, gewaltverherrlichenden oder illegalen Inhalte enthalten.</p>
<p>Der Anbieter ist berechtigt, Inhalte zu prüfen und bei Verstössen zu löschen oder zu sperren.</p>
<h2>7. Haftung</h2>
<p>Der Anbieter übernimmt keine Gewähr für die Richtigkeit, Vollständigkeit oder Aktualität der in der App angezeigten Events.</p>
<p>Die Teilnahme an Veranstaltungen erfolgt auf eigenes Risiko.</p>
<p>Der Anbieter haftet nur für Schäden, die auf Vorsatz oder grobe Fahrlässigkeit zurückzuführen sind. Eine Haftung für indirekte Schäden, entgangenen Gewinn oder Datenverluste wird ausgeschlossen, soweit gesetzlich zulässig.</p>
<h2>8. Datenschutz</h2>
<p>Die Verarbeitung personenbezogener Daten erfolgt gemäss der Datenschutzerklärung der Rainax AG.</p>
<p>Diese ist unter folgender Adresse einsehbar:<br><a href="https://www.moodup.app/datenschutz" rel="noopener">https://www.moodup.app/datenschutz</a></p>
<h2>9. Geistiges Eigentum</h2>
<p>Alle Inhalte, Marken, Designs und Logos der App sind Eigentum der Rainax AG oder entsprechend lizenziert.</p>
<p>Jegliche Vervielfältigung, Bearbeitung oder Weiterverbreitung ohne schriftliche Zustimmung ist untersagt, soweit keine gesetzlichen Ausnahmen gelten.</p>
<h2>10. Vertragsänderungen und Beendigung</h2>
<p>Der Anbieter kann diese AGB jederzeit anpassen. Änderungen werden in der App oder auf der Website veröffentlicht. Änderungen bestehender Vertragsverhältnisse erfolgen unter Beachtung der anwendbaren gesetzlichen Bestimmungen und allfälliger vertraglicher Vereinbarungen.</p>
<p>Club-Abos können vom Clubinhaber jederzeit über das Stripe-Konto oder per E-Mail an <a href="mailto:info@rainax.ch">info@rainax.ch</a> gekündigt werden. Die Kündigung wird zum Ende der laufenden Abrechnungsperiode wirksam, sofern keine abweichende Vereinbarung besteht.</p>
<h2>11. Anwendbares Recht und Gerichtsstand</h2>
<p>Es gilt ausschliesslich das schweizerische Recht unter Ausschluss des UN-Kaufrechts, soweit gesetzlich zulässig.</p>
<p>Gerichtsstand ist St. Gallen, Schweiz, soweit keine zwingenden gesetzlichen Gerichtsstände entgegenstehen.</p>
<h2>12. Kontakt</h2>
<p>Bei Fragen oder Support wenden Sie sich bitte an:</p>
<p>Rainax AG<br>Sirnacherstrasse 5<br>9500 Wil, Schweiz<br>E-Mail: <a href="mailto:info@rainax.ch">info@rainax.ch</a><br>Website: <a href="https://www.mood-up.ch" rel="noopener">https://www.mood-up.ch</a></p>
<p class="prose__copy">© 2026 Rainax AG. Alle Rechte vorbehalten.</p>"""),
    }
    for slug, (title, body) in legal.items():
        lead = {"impressum": "Angaben gemäss Schweizer Recht.",
                "datenschutz": "für die App „Mood Up“ und die Website mood-up.ch · Stand: Oktober 2026",
                "agb": "Allgemeine Geschäftsbedingungen für die App „Mood Up“ der Rainax AG · Stand: Oktober 2026"}.get(slug, "Stand: " + ph("[Datum]"))
        marker = "" if slug in ("impressum", "datenschutz", "agb") else "\n    <!-- PLATZHALTER: Rechtstext nicht erfinden, sondern von der bestehenden Seite übernehmen -->"
        page(slug, f"{title} | Mood Up", f"{title} von Mood Up.",
             hero("Rechtliches", title, lead) + f"""
<section class="section section--tight">
  <div class="container prose">{marker}
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



# ---------------------------------------------------------------------------
# Konto löschen (DE, EN, IT, FR) – eine URL, Sprache per Umschalter oder Browser
# ---------------------------------------------------------------------------
DELETE_T = {
    "de": dict(
        name="Deutsch", eyebrow="Konto", title="Konto löschen",
        lead="Du möchtest dein Mood-Up-Konto löschen? Das geht direkt in der App oder über das Formular auf dieser Seite.",
        app_h="In der App löschen", app_hint="Am schnellsten, wenn die App noch installiert ist:",
        steps=["Öffne die Mood-Up-App und melde dich an.", "Tippe unten rechts auf <b>Profil</b>.", "Scrolle ganz nach unten und tippe auf <b>Konto löschen</b>.", "Bestätige die Löschung."],
        form_h="Ohne App löschen", form_hint="Du hast die App nicht mehr? Schick uns eine Lösch-Anfrage. Wir prüfen sie und bestätigen dir die Löschung per E-Mail.",
        l_mail="E-Mail-Adresse deines Kontos", l_name="Name im Konto (optional)", l_msg="Nachricht (optional)",
        l_confirm="Ich möchte mein Mood-Up-Konto und die zugehörigen Daten endgültig löschen lassen.",
        submit="Lösch-Anfrage senden", note="Öffnet dein E-Mail-Programm mit der fertigen Anfrage an info@mood-up.ch. Bitte sende sie von der E-Mail-Adresse deines Kontos.",
        e_mail="Bitte gib eine gültige E-Mail-Adresse ein.", e_confirm="Bitte bestätige, dass du dein Konto löschen möchtest.",
        subject="Kontolöschung Mood Up", body_intro="Bitte löscht mein Mood-Up-Konto und die zugehörigen Daten.",
        data_h="Was wird gelöscht?",
        data_del=["Dein Konto mit Name, E-Mail-Adresse und Profilangaben", "Gespeicherter Ort bzw. Adresse", "Gefolgte Locations und deine Einstellungen", "Deine Chat-Nachrichten"],
        data_keep_h="Was bleibt?",
        data_keep="Daten, die wir aus gesetzlichen Gründen aufbewahren müssen (z. B. Rechnungen von Club-Abos), werden erst nach Ablauf der gesetzlichen Fristen gelöscht.",
        clubs_h="Für Bars &amp; Clubs", clubs="Ein laufendes Pro-Abo kündigst du über dein Stripe-Konto oder per E-Mail. Die Kündigung gilt auf Ende der laufenden Periode.",
        more='Mehr dazu in der <a href="../datenschutz/">Datenschutzerklärung</a>. Fragen? <a href="mailto:info@mood-up.ch">info@mood-up.ch</a>',
        lbl_mail="E-Mail", lbl_name="Name", lbl_msg="Nachricht"),
    "en": dict(
        name="English", eyebrow="Account", title="Delete account",
        lead="Want to delete your Mood Up account? You can do it directly in the app or with the form on this page.",
        app_h="Delete in the app", app_hint="The quickest way if the app is still installed:",
        steps=["Open the Mood Up app and sign in.", "Tap <b>Profile</b> at the bottom right.", "Scroll all the way down and tap <b>Delete account</b>.", "Confirm the deletion."],
        form_h="Delete without the app", form_hint="Don't have the app anymore? Send us a deletion request. We will review it and confirm the deletion by email.",
        l_mail="Email address of your account", l_name="Name on the account (optional)", l_msg="Message (optional)",
        l_confirm="I want my Mood Up account and the related data to be deleted permanently.",
        submit="Send deletion request", note="Opens your email app with the request to info@mood-up.ch ready to send. Please send it from your account's email address.",
        e_mail="Please enter a valid email address.", e_confirm="Please confirm that you want to delete your account.",
        subject="Mood Up account deletion", body_intro="Please delete my Mood Up account and the related data.",
        data_h="What is deleted?",
        data_del=["Your account with name, email address and profile details", "Saved location or address", "Followed locations and your settings", "Your chat messages"],
        data_keep_h="What is kept?",
        data_keep="Data we must keep for legal reasons (e.g. invoices for club subscriptions) is deleted once the statutory retention periods have expired.",
        clubs_h="For bars &amp; clubs", clubs="Cancel an active Pro subscription via your Stripe account or by email. The cancellation takes effect at the end of the current billing period.",
        more='More details in our <a href="../datenschutz/">privacy policy</a> (German). Questions? <a href="mailto:info@mood-up.ch">info@mood-up.ch</a>',
        lbl_mail="Email", lbl_name="Name", lbl_msg="Message"),
    "it": dict(
        name="Italiano", eyebrow="Account", title="Eliminare l'account",
        lead="Vuoi eliminare il tuo account Mood Up? Puoi farlo direttamente nell'app oppure con il modulo su questa pagina.",
        app_h="Eliminare nell'app", app_hint="Il modo più rapido se l'app è ancora installata:",
        steps=["Apri l'app Mood Up ed effettua l'accesso.", "Tocca <b>Profilo</b> in basso a destra.", "Scorri fino in fondo e tocca <b>Elimina account</b>.", "Conferma l'eliminazione."],
        form_h="Eliminare senza l'app", form_hint="Non hai più l'app? Inviaci una richiesta di eliminazione. La verificheremo e ti confermeremo l'eliminazione via e-mail.",
        l_mail="Indirizzo e-mail del tuo account", l_name="Nome nell'account (facoltativo)", l_msg="Messaggio (facoltativo)",
        l_confirm="Desidero che il mio account Mood Up e i dati collegati vengano eliminati definitivamente.",
        submit="Invia richiesta di eliminazione", note="Apre il tuo programma e-mail con la richiesta già pronta per info@mood-up.ch. Inviala dall'indirizzo e-mail del tuo account.",
        e_mail="Inserisci un indirizzo e-mail valido.", e_confirm="Conferma che desideri eliminare il tuo account.",
        subject="Eliminazione account Mood Up", body_intro="Vi prego di eliminare il mio account Mood Up e i dati collegati.",
        data_h="Cosa viene eliminato?",
        data_del=["Il tuo account con nome, indirizzo e-mail e dati del profilo", "Luogo o indirizzo salvato", "Locali seguiti e le tue impostazioni", "I tuoi messaggi in chat"],
        data_keep_h="Cosa viene conservato?",
        data_keep="I dati che dobbiamo conservare per motivi di legge (ad es. le fatture degli abbonamenti dei club) vengono eliminati alla scadenza dei termini di conservazione previsti dalla legge.",
        clubs_h="Per bar e club", clubs="Disdici un abbonamento Pro attivo tramite il tuo account Stripe o via e-mail. La disdetta ha effetto alla fine del periodo in corso.",
        more='Maggiori dettagli nell\'<a href="../datenschutz/">informativa sulla privacy</a> (in tedesco). Domande? <a href="mailto:info@mood-up.ch">info@mood-up.ch</a>',
        lbl_mail="E-mail", lbl_name="Nome", lbl_msg="Messaggio"),
    "fr": dict(
        name="Français", eyebrow="Compte", title="Supprimer le compte",
        lead="Tu souhaites supprimer ton compte Mood Up ? Tu peux le faire directement dans l'app ou avec le formulaire de cette page.",
        app_h="Supprimer dans l'app", app_hint="Le plus rapide si l'app est encore installée :",
        steps=["Ouvre l'app Mood Up et connecte-toi.", "Touche <b>Profil</b> en bas à droite.", "Fais défiler tout en bas et touche <b>Supprimer le compte</b>.", "Confirme la suppression."],
        form_h="Supprimer sans l'app", form_hint="Tu n'as plus l'app ? Envoie-nous une demande de suppression. Nous la vérifions et te confirmons la suppression par e-mail.",
        l_mail="Adresse e-mail de ton compte", l_name="Nom du compte (facultatif)", l_msg="Message (facultatif)",
        l_confirm="Je souhaite que mon compte Mood Up et les données associées soient supprimés définitivement.",
        submit="Envoyer la demande de suppression", note="Ouvre ton programme e-mail avec la demande prête à envoyer à info@mood-up.ch. Envoie-la depuis l'adresse e-mail de ton compte.",
        e_mail="Merci d'indiquer une adresse e-mail valide.", e_confirm="Merci de confirmer que tu souhaites supprimer ton compte.",
        subject="Suppression du compte Mood Up", body_intro="Merci de supprimer mon compte Mood Up et les données associées.",
        data_h="Qu'est-ce qui est supprimé ?",
        data_del=["Ton compte avec nom, adresse e-mail et informations de profil", "Lieu ou adresse enregistrés", "Lieux suivis et tes réglages", "Tes messages de chat"],
        data_keep_h="Qu'est-ce qui est conservé ?",
        data_keep="Les données que nous devons conserver pour des raisons légales (p. ex. les factures des abonnements des clubs) sont supprimées à l'expiration des délais légaux de conservation.",
        clubs_h="Pour les bars et clubs", clubs="Résilie un abonnement Pro en cours via ton compte Stripe ou par e-mail. La résiliation prend effet à la fin de la période en cours.",
        more='Plus de détails dans la <a href="../datenschutz/">déclaration de confidentialité</a> (en allemand). Des questions ? <a href="mailto:info@mood-up.ch">info@mood-up.ch</a>',
        lbl_mail="E-mail", lbl_name="Nom", lbl_msg="Message"),
}


def build_delete_page() -> None:
    options = "".join(f'<option value="{k}" lang="{k}">{v["name"]}</option>' for k, v in DELETE_T.items())
    lang_select = f'''
    <label class="lang-select">
      <span class="sr-only">Sprache / Language / Lingua / Langue</span>
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
      <select data-lang-select>{options}</select>
      <svg class="lang-select__chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
    </label>'''
    blocks = []
    for k, t in DELETE_T.items():
        steps = "".join(f"<li><span>{x}</span></li>" for x in t["steps"])
        dels = "".join(f"<li>{x}</li>" for x in t["data_del"])
        hidden = "" if k == "de" else " hidden"
        blocks.append(f'''<div class="del" lang="{k}" data-lang="{k}"{hidden}>
  <section class="page-hero page-hero--del">
    <div class="container">
      <span class="eyebrow">{t["eyebrow"]}</span>
      <h1>{t["title"]}</h1>
      <p class="page-hero__lead">{t["lead"]}</p>
    </div>
  </section>
  <section class="section section--tight">
    <div class="container del__grid">
      <div class="del__col">
        <article class="del__card">
          <span class="del__num">1</span>
          <h2>{t["app_h"]}</h2>
          <p>{t["app_hint"]}</p>
          <ol class="del__steps">{steps}</ol>
        </article>
        <article class="del__card del__card--info">
          <h2>{t["data_h"]}</h2>
          <ul class="ticks">{dels}</ul>
          <h3>{t["data_keep_h"]}</h3>
          <p>{t["data_keep"]}</p>
          <h3>{t["clubs_h"]}</h3>
          <p>{t["clubs"]}</p>
          <p class="del__more">{t["more"]}</p>
        </article>
      </div>
      <form class="form del__form" data-delete novalidate
            data-subject="{t["subject"]}" data-intro="{t["body_intro"]}"
            data-l-mail="{t["lbl_mail"]}" data-l-name="{t["lbl_name"]}" data-l-msg="{t["lbl_msg"]}">
        <span class="del__num del__num--dark">2</span>
        <h2>{t["form_h"]}</h2>
        <p class="form__note">{t["form_hint"]}</p>
        <div class="field"><label for="d-mail-{k}">{t["l_mail"]}</label><input id="d-mail-{k}" name="email" type="email" autocomplete="email" required><p class="err" id="d-e-mail-{k}" hidden>{t["e_mail"]}</p></div>
        <div class="field"><label for="d-name-{k}">{t["l_name"]}</label><input id="d-name-{k}" name="name" autocomplete="name"></div>
        <div class="field"><label for="d-msg-{k}">{t["l_msg"]}</label><textarea id="d-msg-{k}" name="message" rows="3"></textarea></div>
        <label class="check"><input type="checkbox" id="d-ok-{k}" name="confirm"><span>{t["l_confirm"]}</span></label>
        <p class="err" id="d-e-ok-{k}" hidden>{t["e_confirm"]}</p>
        <button class="btn btn--danger" type="submit">{t["submit"]} <svg aria-hidden="true"><use href="#i-arrow"/></svg></button>
        <p class="form__note">{t["note"]}</p>
      </form>
    </div>
  </section>
</div>''')
    body = "\n".join(blocks)
    page("konto-loeschen", "Konto löschen | Mood Up",
         "Mood-Up-Konto löschen: in der App oder per Lösch-Anfrage. Delete account · Eliminare l'account · Supprimer le compte.", body, lang_select)




# ---------------------------------------------------------------------------
# Mehrsprachigkeit: /en/, /it/, /fr/ aus den deutschen Seiten erzeugen
# ---------------------------------------------------------------------------
SITE = "https://www.mood-up.ch/"
TRANSLATED = ["index.html", "preise/index.html", "faq/index.html", "ueber-uns/index.html", "kontakt/index.html",
              "impressum/index.html", "datenschutz/index.html", "agb/index.html"]
LEGAL = {"impressum/index.html", "datenschutz/index.html", "agb/index.html"}
ATTR_RE = re.compile(r'\b(alt|aria-label|title|placeholder|content|value|data-subject|data-l-name|data-l-mail|data-l-who|data-open|data-close)="([^"]*)"')
URL_RE = re.compile(r'\b(href|src)="([^"]+)"')


def _norm(t: str) -> str:
    return " ".join(htmllib.unescape(t).split())


def translate_html(src: str, lang: str) -> str:
    """Ersetzt ganze Textabschnitte und Attributwerte anhand der Tabelle in i18n.py (Rechtstext bleibt Deutsch)."""
    keep = []
    def protect(m):
        keep.append(m.group(0)); return f"<\x00{len(keep) - 1}\x00>"
    src = re.sub(r'<(script|style)\b.*?</\1>|<svg\b.*?</svg>|<div class="container prose">.*?</section>', protect, src, flags=re.S)

    def seg(m):
        raw = m.group(1); key = _norm(raw)
        if key not in T:
            return m.group(0)
        lead = raw[:len(raw) - len(raw.lstrip())]; trail = raw[len(raw.rstrip()):]
        return ">" + lead + htmllib.escape(T[key][lang], quote=False) + trail + "<"
    src = re.sub(r'>([^<]+)<', seg, src)

    def attr(m):
        key = htmllib.unescape(m.group(2))
        if key not in T:
            return m.group(0)
        return f'{m.group(1)}="{htmllib.escape(T[key][lang])}"'
    src = ATTR_RE.sub(attr, src)
    return re.sub(r"<\x00(\d+)\x00>", lambda m: keep[int(m.group(1))], src)


def page_path(lang: str, page: str) -> str:
    return page if lang == "de" else f"{lang}/{page}"


def relocate(src: str, page: str, lang: str) -> str:
    """Passt relative Links an den neuen Ort /<lang>/... an; übersetzte Seiten zeigen in dieselbe Sprache."""
    src_dir = posixpath.dirname(page); dst_dir = posixpath.dirname(page_path(lang, page))
    def fix(m):
        attr, url = m.group(1), m.group(2)
        if re.match(r'(https?:|mailto:|tel:|#|data:)', url):
            return m.group(0)
        path, _, anchor = url.partition("#")
        path, _, query = path.partition("?")
        target = posixpath.normpath(posixpath.join(src_dir, path))
        if target in TRANSLATED:
            target = page_path(lang, target)
        elif target == "konto-loeschen/index.html":
            query = f"lang={lang}"
        new = posixpath.relpath(target, dst_dir or ".")
        return f'{attr}="{new}{"?" + query if query else ""}{"#" + anchor if anchor else ""}"'
    return URL_RE.sub(fix, src)


def public_url(lang: str, page: str) -> str:
    return SITE + page_path(lang, page).replace("index.html", "")


def head_links(page: str) -> str:
    alt = "".join(f'<link rel="alternate" hreflang="{UI[l]["html_lang"].split("-")[0]}" href="{public_url(l, page)}">\n' for l in ("de",) + LANGS)
    return alt + f'<link rel="alternate" hreflang="x-default" href="{public_url("de", page)}">\n'


def lang_select(page: str, lang: str) -> str:
    here = posixpath.dirname(page_path(lang, page))
    opts = "".join(
        f'<option value="{posixpath.relpath(page_path(l, page), here or ".")}" lang="{l}"{" selected" if l == lang else ""}>{LANG_NAMES[l]}</option>'
        for l in ("de",) + LANGS)
    return f"""
    <label class="lang-select">
      <span class="sr-only">{UI[lang]["lang_label"]}</span>
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
      <select data-lang-nav data-lang-current="{lang}">{opts}</select>
      <svg class="lang-select__chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
    </label>"""


def finish(src: str, page: str, lang: str) -> str:
    src = src.replace("<!--LANGSELECT-->", lang_select(page, lang))
    src = re.sub(r'<link rel="canonical"[^>]*>\n?|<link rel="alternate" hreflang[^>]*>\n?', "", src)
    src = src.replace("</head>", f'<link rel="canonical" href="{public_url(lang, page)}">\n' + head_links(page) + "</head>", 1)
    src = re.sub(r'<html lang="[^"]*">', f'<html lang="{UI[lang]["html_lang"]}">', src, count=1)
    src = re.sub(r'<meta property="og:locale" content="[^"]*">', f'<meta property="og:locale" content="{UI[lang]["og_locale"]}">', src)
    if lang != "de" and page in LEGAL:
        src = src.replace('<div class="container prose">', f'<div class="container prose">\n    <p class="legal-note" lang="{lang}">{UI[lang]["legal_note"]}</p>', 1)
    return src


def build_languages() -> None:
    for page in TRANSLATED:
        de_src = (ROOT / page).read_text(encoding="utf-8")
        (ROOT / page).write_text(finish(de_src, page, "de"), encoding="utf-8")
        for lang in LANGS:
            out = ROOT / page_path(lang, page)
            out.parent.mkdir(parents=True, exist_ok=True)
            html = relocate(translate_html(de_src, lang), page, lang)
            out.write_text(finish(html, page, lang), encoding="utf-8")
    # Konto löschen: eigene Sprachwahl auf der Seite, nur die Kopfzeile bekommt kein Navigations-Dropdown
    k = ROOT / "konto-loeschen/index.html"
    k.write_text(k.read_text(encoding="utf-8").replace("<!--LANGSELECT-->", ""), encoding="utf-8")


if __name__ == "__main__":
    update_home()
    build_pages()
    build_delete_page()
    build_languages()
    print("ok")
