<?php
/**
 * Bauplan für den Admin-Bereich: welche Inhalte bearbeitet werden können.
 *
 * Feldtypen: text, textarea, image, video, file, url, email, number, list.
 * Eine «list» hat eigene Felder (fields) und kann im Admin beliebig
 * erweitert, gekürzt und umsortiert werden (z. B. Team, Galerie).
 */

declare(strict_types=1);

$TEXTHILFE = 'Leerzeile = neuer Absatz. **Wort** = fett.';

return [
    'allgemein' => [
        'label' => 'Kontakt & Allgemein',
        'icon'  => '☎',
        'hint'  => 'Diese Angaben erscheinen in der Fusszeile, im Kopf und auf der Kontaktseite.',
        'fields' => [
            'name'                  => ['type' => 'text', 'label' => 'Name der Kita'],
            'firma'                 => ['type' => 'text', 'label' => 'Firmenname'],
            'strasse'               => ['type' => 'text', 'label' => 'Strasse'],
            'ort'                   => ['type' => 'text', 'label' => 'PLZ und Ort'],
            'telefon'               => ['type' => 'text', 'label' => 'Telefon'],
            'whatsapp'              => ['type' => 'text', 'label' => 'WhatsApp-Nummer', 'help' => 'Nur Ziffern mit Ländervorwahl, z. B. 41783159949. Leer lassen = kein WhatsApp-Knopf.'],
            'email'                 => ['type' => 'email', 'label' => 'E-Mail'],
            'instagram'             => ['type' => 'url', 'label' => 'Instagram-Link'],
            'oeffnungszeiten_tage'  => ['type' => 'text', 'label' => 'Öffnungstage'],
            'oeffnungszeiten_zeit'  => ['type' => 'text', 'label' => 'Öffnungszeit'],
            'parkieren'             => ['type' => 'text', 'label' => 'Hinweis Parkieren'],
            'maps_link'             => ['type' => 'url', 'label' => 'Link zu Google Maps'],
            'uid'                   => ['type' => 'text', 'label' => 'UID-Nummer'],
            'meta_beschreibung'     => ['type' => 'textarea', 'label' => 'Beschreibung für Google', 'help' => 'Ein bis zwei Sätze, die bei Google unter dem Titel erscheinen.'],
        ],
    ],
    'start' => [
        'label' => 'Startseite',
        'icon'  => '⌂',
        'fields' => [
            'hero_kicker' => ['type' => 'text', 'label' => 'Kleine Zeile über dem Titel'],
            'hero_titel'  => ['type' => 'text', 'label' => 'Grosser Titel', 'help' => 'Jeder Satz mit Punkt erscheint auf einer eigenen Zeile.'],
            'hero_text'   => ['type' => 'textarea', 'label' => 'Text unter dem Titel'],
            'hero_video'  => ['type' => 'video', 'label' => 'Video im Kopfbereich', 'help' => 'Kurzes Alltagsvideo (MP4, ohne Ton, max. 30 Sek.). Ohne Video wird das Bild gezeigt.'],
            'hero_bild'   => ['type' => 'image', 'label' => 'Bild im Kopfbereich'],
            'fakten' => ['type' => 'list', 'label' => 'Zahlen-Leiste', 'item' => 'Zahl', 'fields' => [
                'zahl' => ['type' => 'text', 'label' => 'Zahl / kurzer Wert'],
                'text' => ['type' => 'text', 'label' => 'Beschriftung'],
            ]],
            'werte_titel' => ['type' => 'text', 'label' => 'Titel «Werte»'],
            'werte' => ['type' => 'list', 'label' => 'Werte', 'item' => 'Wert', 'fields' => [
                'titel' => ['type' => 'text', 'label' => 'Titel'],
                'text'  => ['type' => 'textarea', 'label' => 'Text'],
            ]],
            'alltag_titel' => ['type' => 'text', 'label' => 'Titel «Alltag»'],
            'alltag_text'  => ['type' => 'textarea', 'label' => 'Text «Alltag»'],
            'alltag' => ['type' => 'list', 'label' => 'Alltags-Videos & Bilder', 'item' => 'Clip', 'help' => 'Hochformat-Videos (wie Instagram Reels) wirken hier am schönsten. Das Bild wird gezeigt, bis das Video lädt.', 'fields' => [
                'titel' => ['type' => 'text', 'label' => 'Titel'],
                'video' => ['type' => 'video', 'label' => 'Video (optional)'],
                'bild'  => ['type' => 'image', 'label' => 'Bild / Vorschaubild'],
            ]],
            'cta_titel' => ['type' => 'text', 'label' => 'Titel «Besichtigung»'],
            'cta_text'  => ['type' => 'textarea', 'label' => 'Text «Besichtigung»'],
        ],
    ],
    'ueber' => [
        'label' => 'Über uns & Räume',
        'icon'  => '♥',
        'fields' => [
            'titel' => ['type' => 'text', 'label' => 'Titel'],
            'intro' => ['type' => 'textarea', 'label' => 'Einleitung'],
            'text'  => ['type' => 'textarea', 'label' => 'Text', 'help' => $TEXTHILFE],
            'raeume_titel' => ['type' => 'text', 'label' => 'Titel «Räume»'],
            'raeume_text'  => ['type' => 'textarea', 'label' => 'Text «Räume»'],
            'raeume' => ['type' => 'list', 'label' => 'Räume', 'item' => 'Raum', 'fields' => [
                'titel' => ['type' => 'text', 'label' => 'Titel'],
                'text'  => ['type' => 'textarea', 'label' => 'Text'],
                'bild'  => ['type' => 'image', 'label' => 'Bild'],
            ]],
            'galerie_titel'   => ['type' => 'text', 'label' => 'Titel «Galerie»'],
            'galerie_hinweis' => ['type' => 'text', 'label' => 'Hinweis unter dem Galerie-Titel', 'help' => 'Leer lassen, wenn kein Hinweis nötig ist.'],
            'galerie' => ['type' => 'list', 'label' => 'Galerie-Bilder', 'item' => 'Bild', 'fields' => [
                'bild' => ['type' => 'image', 'label' => 'Bild'],
                'text' => ['type' => 'text', 'label' => 'Bildunterschrift'],
            ]],
            'partner_titel'   => ['type' => 'text', 'label' => 'Titel «Partnerschaft»'],
            'partner_text'    => ['type' => 'textarea', 'label' => 'Text «Partnerschaft»'],
            'partner_name'    => ['type' => 'text', 'label' => 'Name Partner'],
            'partner_adresse' => ['type' => 'text', 'label' => 'Adresse Partner'],
            'partner_link'    => ['type' => 'url', 'label' => 'Webseite Partner'],
            'partner_logo'    => ['type' => 'image', 'label' => 'Logo Partner'],
        ],
    ],
    'team' => [
        'label' => 'Team',
        'icon'  => '☺',
        'hint'  => 'Personen hinzufügen, entfernen oder mit den Pfeilen umsortieren. Ohne Foto wird ein farbiger Kreis mit Initiale gezeigt.',
        'fields' => [
            'titel' => ['type' => 'text', 'label' => 'Titel'],
            'intro' => ['type' => 'textarea', 'label' => 'Einleitung'],
            'mitglieder' => ['type' => 'list', 'label' => 'Teammitglieder', 'item' => 'Person', 'fields' => [
                'name'    => ['type' => 'text', 'label' => 'Name'],
                'rolle'   => ['type' => 'text', 'label' => 'Funktion'],
                'details' => ['type' => 'textarea', 'label' => 'Ausbildung / Details', 'help' => 'Eine Angabe pro Zeile.'],
                'foto'    => ['type' => 'image', 'label' => 'Foto'],
            ]],
        ],
    ],
    'angebot' => [
        'label' => 'Angebot & Ferien',
        'icon'  => '✿',
        'fields' => [
            'titel' => ['type' => 'text', 'label' => 'Titel'],
            'intro' => ['type' => 'textarea', 'label' => 'Einleitung'],
            'eingewoehnung_titel' => ['type' => 'text', 'label' => 'Titel «Eingewöhnung»'],
            'eingewoehnung_text'  => ['type' => 'textarea', 'label' => 'Text «Eingewöhnung»'],
            'phasen' => ['type' => 'list', 'label' => 'Phasen der Eingewöhnung', 'item' => 'Phase', 'fields' => [
                'titel' => ['type' => 'text', 'label' => 'Titel'],
                'text'  => ['type' => 'textarea', 'label' => 'Text'],
            ]],
            'extras' => ['type' => 'list', 'label' => 'Weitere Angebote', 'item' => 'Angebot', 'fields' => [
                'titel' => ['type' => 'text', 'label' => 'Titel'],
                'text'  => ['type' => 'textarea', 'label' => 'Text'],
            ]],
            'zeiten_titel'     => ['type' => 'text', 'label' => 'Titel «Öffnungszeiten»'],
            'zeiten_text'      => ['type' => 'textarea', 'label' => 'Text «Öffnungszeiten & Ferien»'],
            'ferien_pdf'       => ['type' => 'file', 'label' => 'PDF Feiertage & Betriebsferien', 'help' => 'Jedes Jahr das neue PDF hochladen.'],
            'ferien_pdf_titel' => ['type' => 'text', 'label' => 'Name des PDF-Links'],
        ],
    ],
    'paedagogik' => [
        'label' => 'Pädagogik',
        'icon'  => '❋',
        'fields' => [
            'titel' => ['type' => 'text', 'label' => 'Titel'],
            'intro' => ['type' => 'textarea', 'label' => 'Einleitung'],
            'grundsaetze' => ['type' => 'list', 'label' => 'Grundsätze', 'item' => 'Grundsatz', 'fields' => [
                'titel' => ['type' => 'text', 'label' => 'Titel'],
                'text'  => ['type' => 'textarea', 'label' => 'Text'],
            ]],
            'themen' => ['type' => 'list', 'label' => 'Themen mit Bild', 'item' => 'Thema', 'fields' => [
                'titel' => ['type' => 'text', 'label' => 'Titel'],
                'text'  => ['type' => 'textarea', 'label' => 'Text'],
                'bild'  => ['type' => 'image', 'label' => 'Bild'],
            ]],
        ],
    ],
    'tarife' => [
        'label' => 'Tarife',
        'icon'  => 'CHF',
        'hint'  => 'Die Monatspreise werden automatisch berechnet: Tagespreis × Anzahl Tage × Faktor.',
        'fields' => [
            'titel' => ['type' => 'text', 'label' => 'Titel'],
            'intro' => ['type' => 'textarea', 'label' => 'Einleitung'],
            'preis_saeugling' => ['type' => 'number', 'label' => 'Tagespreis Säuglinge (CHF)'],
            'label_saeugling' => ['type' => 'text', 'label' => 'Beschreibung Säuglinge'],
            'preis_kleinkind' => ['type' => 'number', 'label' => 'Tagespreis Kleinkinder (CHF)'],
            'label_kleinkind' => ['type' => 'text', 'label' => 'Beschreibung Kleinkinder'],
            'faktor'          => ['type' => 'number', 'label' => 'Faktor Wochen pro Monat', 'help' => 'Bisher 4.2 (z. B. 132 × 4.2 = 554.40 CHF für 1 Tag pro Woche).'],
            'empfehlung'      => ['type' => 'text', 'label' => 'Empfehlung'],
            'halbtage' => ['type' => 'list', 'label' => 'Halbtage', 'item' => 'Halbtag', 'fields' => [
                'titel'     => ['type' => 'text', 'label' => 'Titel'],
                'zeit'      => ['type' => 'text', 'label' => 'Zeit'],
                'saeugling' => ['type' => 'number', 'label' => 'Preis Säuglinge (CHF)'],
                'kleinkind' => ['type' => 'number', 'label' => 'Preis Kleinkinder (CHF)'],
            ]],
            'mindestbetreuung' => ['type' => 'text', 'label' => 'Hinweis Mindestbetreuung'],
            'vorteile' => ['type' => 'list', 'label' => 'Vorteile & Rabatte', 'item' => 'Vorteil', 'fields' => [
                'titel' => ['type' => 'text', 'label' => 'Titel'],
                'text'  => ['type' => 'textarea', 'label' => 'Text'],
            ]],
            'subvention_titel'  => ['type' => 'text', 'label' => 'Titel «Subventionen»'],
            'subvention_text'   => ['type' => 'textarea', 'label' => 'Text «Subventionen»'],
            'subvention_link'   => ['type' => 'url', 'label' => 'Link Kitarechner'],
            'subvention_button' => ['type' => 'text', 'label' => 'Beschriftung Knopf'],
        ],
    ],
    'jobs' => [
        'label' => 'Jobs',
        'icon'  => '★',
        'hint'  => 'Keine Stelle eingetragen = der Text «keine offenen Stellen» wird angezeigt.',
        'fields' => [
            'titel' => ['type' => 'text', 'label' => 'Titel'],
            'intro' => ['type' => 'textarea', 'label' => 'Einleitung'],
            'stellen' => ['type' => 'list', 'label' => 'Offene Stellen', 'item' => 'Stelle', 'fields' => [
                'titel'  => ['type' => 'text', 'label' => 'Stellenbezeichnung'],
                'pensum' => ['type' => 'text', 'label' => 'Pensum / Start', 'help' => 'z. B. 80–100 %, ab August 2027'],
                'text'   => ['type' => 'textarea', 'label' => 'Beschreibung'],
                'pdf'    => ['type' => 'file', 'label' => 'Inserat als PDF (optional)'],
            ]],
            'keine_stellen'   => ['type' => 'textarea', 'label' => 'Text, wenn keine Stelle offen ist'],
            'bewerbung_email' => ['type' => 'email', 'label' => 'E-Mail für Bewerbungen'],
        ],
    ],
    'kontakt' => [
        'label' => 'Kontakt & Anfrage',
        'icon'  => '✉',
        'fields' => [
            'titel' => ['type' => 'text', 'label' => 'Titel'],
            'intro' => ['type' => 'textarea', 'label' => 'Einleitung'],
            'schritte' => ['type' => 'list', 'label' => 'Schritte der Anmeldung', 'item' => 'Schritt', 'fields' => [
                'titel' => ['type' => 'text', 'label' => 'Titel'],
            ]],
            'formular_empfaenger' => ['type' => 'email', 'label' => 'Anfragen gehen an diese E-Mail'],
            'danke' => ['type' => 'textarea', 'label' => 'Danke-Text nach dem Absenden'],
        ],
    ],
    'rechtliches' => [
        'label' => 'Impressum & Datenschutz',
        'icon'  => '§',
        'fields' => [
            'impressum'   => ['type' => 'textarea', 'label' => 'Impressum', 'rows' => 14, 'help' => $TEXTHILFE],
            'datenschutz' => ['type' => 'textarea', 'label' => 'Datenschutzerklärung', 'rows' => 20, 'help' => $TEXTHILFE],
        ],
    ],
];
