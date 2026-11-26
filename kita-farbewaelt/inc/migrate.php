<?php
/**
 * Automatische Inhalts-Updates.
 *
 * Läuft einmalig beim ersten Seitenaufruf nach einem Update und passt die
 * bestehenden Inhalte (data/content.json) an, ohne Änderungen aus dem Admin
 * zu verlieren. Jede Stufe erhöht «_version».
 */

declare(strict_types=1);

const CONTENT_VERSION = 3;

function migrate_content(array $c): array
{
    $v = (int) ($c['_version'] ?? 1);

    if ($v < 2) {
        $c = migrate_to_2($c);
    }
    if ($v < 3) {
        $c = migrate_to_3($c);
    }

    $c['_version'] = CONTENT_VERSION;
    return $c;
}

/** In allen Texten ersetzen. */
function replace_everywhere(array $data, array $map): array
{
    array_walk_recursive($data, static function (&$value) use ($map): void {
        if (is_string($value)) {
            $value = strtr($value, $map);
        }
    });
    return $data;
}

/** Feedback-Runde 1 der Kundin (November 2026). */
function migrate_to_2(array $c): array
{
    // «&» ausschreiben, Telefonnummer korrigieren
    $c = replace_everywhere($c, [
        ' & '            => ' und ',
        '078 315 99 49'  => '078 315 19 49',
    ]);
    if (($c['allgemein']['whatsapp'] ?? '') === '41783159949') {
        $c['allgemein']['whatsapp'] = '41783151949';
    }

    // Startseite: neue Zahlen, Alltag ohne Bilder, Platz für Rezensionen
    $c['start']['fakten'] = [
        ['zahl' => '24', 'text' => 'Plätze pro Tag'],
        ['zahl' => '2', 'text' => 'familiäre Gruppen'],
        ['zahl' => '100 %', 'text' => 'glückliche Kinder'],
        ['zahl' => '15+', 'text' => 'Jahre Berufserfahrung'],
    ];
    unset($c['start']['alltag']);
    $c['start']['stimmen_titel'] ??= 'Das sagen unsere Eltern';
    $c['start']['stimmen'] ??= [];

    // Über uns: Titelbild/-video, Galerie entfernen, Rollenspiel-Bild korrigieren
    $u = $c['ueber'] ?? [];
    $u['titelbild'] ??= 'uploads/raum-gruppenraum.jpg';
    $u['titelvideo'] ??= '';
    unset($u['galerie'], $u['galerie_titel'], $u['galerie_hinweis']);
    $iSpielen = $iRolle = null;
    foreach (($u['raeume'] ?? []) as $i => $r) {
        if (($r['titel'] ?? '') === 'Spielen' && ($r['bild'] ?? '') === 'uploads/raum-gruppenraum.jpg') {
            $iSpielen = $i;
        }
        if (($r['titel'] ?? '') === 'Rollenspiel' && ($r['bild'] ?? '') === 'uploads/raum-gang.jpg') {
            $iRolle = $i;
        }
    }
    if ($iSpielen !== null && $iRolle !== null) {
        $u['raeume'][$iSpielen]['bild'] = 'uploads/raum-gang.jpg';
        $u['raeume'][$iRolle]['bild']   = 'uploads/raum-gruppenraum.jpg';
    }
    $c['ueber'] = $u;

    // Angebot: mehrere Ferien-PDFs
    $an   = $c['angebot'] ?? [];
    $pdfs = $an['ferien_pdfs'] ?? [];
    if (!$pdfs && !empty($an['ferien_pdf'])) {
        $pdfs[] = ['titel' => $an['ferien_pdf_titel'] ?: 'Feiertage und Betriebsferien', 'pdf' => $an['ferien_pdf']];
    }
    $neu = 'uploads/feiertage-betriebsferien-2027-2028.pdf';
    if (!in_array($neu, array_column($pdfs, 'pdf'), true)) {
        $pdfs[] = ['titel' => 'Feiertage und Betriebsferien 2027/2028', 'pdf' => $neu];
    }
    $an['ferien_pdfs'] = $pdfs;
    unset($an['ferien_pdf'], $an['ferien_pdf_titel']);
    $c['angebot'] = $an;

    // Pädagogik: Texte der bisherigen Webseite
    $c['paedagogik']['grundsaetze'] = [
        ['titel' => 'Unser Bild von eurem Kind', 'text' => 'Jedes Kind ist individuell, dies zeigt sich in der spezifischen Persönlichkeit wie auch in der körperlichen sowie geistigen Entwicklung. Euer Kind erfährt bei uns Akzeptanz, Wärme und Wertschätzung. Es darf jederzeit Freude und Trauer ausdrücken. Dabei erlebt es durch konstante und verlässliche Bezugspersonen Sicherheit und Geborgenheit.'],
        ['titel' => 'Unser Ziel für euer Kind', 'text' => 'Wir sind die Vorbilder für die Kinder. Dabei lassen wir sie partizipieren, unterstützen die Selbständigkeit, indem wir Raum und Zeit gewähren, hören den Kindern aktiv zu, verstärken sie positiv, respektieren ihre Gefühle und bieten ein anregendes Umfeld.'],
        ['titel' => 'Das Zusammenleben unter Kindern', 'text' => 'Das Miteinander in der Kita bietet verschiedenste Möglichkeiten, den Umgang und das Zusammenleben zu erleben und einzuüben. Wir bieten Begleitung bei schwierigen Gruppenprozessen, damit Unterschiedlichkeiten und Auseinandersetzungen als Chance genutzt und Ausgrenzungen verhindert werden.'],
    ];

    // Tarife: kein Halbtag ohne Mittagessen mehr, neue Überschrift, kürzerer Subventionstext
    $t = $c['tarife'] ?? [];
    $t['halbtage'] = array_values(array_filter($t['halbtage'] ?? [], static fn ($h) => stripos($h['titel'] ?? '', 'ohne Mittagessen') === false));
    $t['vorteile_titel'] = 'Das Plus für unsere Familien';
    $t['subvention_text'] = str_replace('unterstützt Familien mit Betreuungsgutscheinen.', 'unterstützt Familien.', $t['subvention_text'] ?? '');
    $c['tarife'] = $t;

    return $c;
}

/** Feedback-Runde 2: Texte «Was uns wichtig ist» von der bisherigen Webseite. */
function migrate_to_3(array $c): array
{
    $texte = [
        'Ernährung' => "Eine gesunde Ernährung ist entscheidend für das Wachstum, die Entwicklung und das Wohlbefinden.\n\nBei uns wird täglich frisch gekocht – mit viel Liebe und Sorgfalt bereiten wir ausgewogene Mahlzeiten zu. Wir legen grossen Wert auf eine gesunde Ernährung, die die Entwicklung der Kinder unterstützt und gleichzeitig Freude am Essen vermittelt.\n\nFür unsere Kleinsten bereiten wir liebevoll frische, pürierte Breie aus Gemüse und Früchten zu. Dazu gibt es abwechslungsreiches Fingerfood, das spielerisch die Selbständigkeit und den Entdeckerdrang der Kleinen unterstützt – so wird jede Mahlzeit zu einem kleinen Genuss- und Lernmoment.",
        'Natur und Umwelt' => "In der Natur finden Kinder viele wertvolle Entdeckungs- und Erfahrungsräume, die mit ihren vielfältigen Reizen die Sinne, die Körperwahrnehmung und den Verstand anregen.\n\nDas Entdecken und Erforschen der Natur fördert die kognitive Entwicklung. Kinder lernen über Pflanzen, Tiere, das Wetter bis hin zum richtigen Verhalten im Strassenverkehr.\n\nTägliche frische Luft ist fester Bestandteil unseres Alltags.",
        'Bewegung und Ruhe' => "**Ohne Bewegung keine Ruhe und ohne Ruhe keine Bewegung.**\n\nDer Schlaf ist eine der wichtigsten Ruhephasen. Im und während des Schlafs werden Erlebnisse der Wachphase verarbeitet und Wachstumshormone produziert, die für das körperliche Wachstum und die Entwicklung unerlässlich sind.",
    ];
    // Offene Stelle
    $stelle = 'FaBe Kind – Schwangerschaftsvertretung';
    if (!in_array($stelle, array_column($c['jobs']['stellen'] ?? [], 'titel'), true)) {
        $c['jobs']['stellen'][] = [
            'titel'  => $stelle,
            'pensum' => '100 % · ab Februar/März 2027',
            'text'   => "Wir suchen eine Fachfrau / einen Fachmann Betreuung Kind EFZ als Schwangerschaftsvertretung zu 100 %.\n\n**Mit Option auf eine Festanstellung zu 40–60 %** im Anschluss.\n\nStellenantritt ab Februar/März 2027.",
            'pdf'    => '',
        ];
    }

    foreach (($c['paedagogik']['themen'] ?? []) as $i => $th) {
        if (isset($texte[$th['titel'] ?? ''])) {
            $c['paedagogik']['themen'][$i]['text'] = $texte[$th['titel']];
        }
    }
    return $c;
}
