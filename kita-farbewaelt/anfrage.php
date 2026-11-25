<?php
/**
 * Kita Farbewält — Platzanfrage / Besichtigung
 *
 * Prüft das Formular von /kontakt/ und sendet es per E-Mail an die
 * Adresse, die im Admin unter «Kontakt und Anfrage» eingetragen ist.
 * Antwortet mit JSON (Aufruf per JavaScript) oder leitet zurück.
 *
 * Voraussetzung beim Hoster: funktionierende mail()-Funktion.
 * ABSENDER sollte eine Adresse der eigenen Domain sein (sonst Spam-Ordner).
 */

declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

const ABSENDER       = 'webseite@kita-farbewaelt.ch';
const MIN_SEKUNDEN   = 4;
const MAX_PRO_STUNDE = 5;

header('X-Content-Type-Options: nosniff');
$wantsJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

function antworte(bool $ok, string $meldung, int $status = 200): void
{
    global $wantsJson;
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok, 'message' => $meldung], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . url('kontakt/') . '?status=' . ($ok ? 'gesendet' : 'fehler') . '#formular', true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    antworte(false, 'Diese Adresse nimmt nur Formulare entgegen.', 405);
}

$c     = load_content();
$danke = $c['kontakt']['danke'] ?? 'Danke! Wir melden uns bald.';
$to    = $c['kontakt']['formular_empfaenger'] ?? ($c['allgemein']['email'] ?? '');

$feld = static function (string $name, int $max = 300): string {
    $v = $_POST[$name] ?? '';
    $v = is_string($v) ? trim(str_replace(["\r", "\0"], '', $v)) : '';
    return mb_substr($v, 0, $max);
};

// Spam-Schutz: Honigtopf-Feld und Mindestzeit -> still «Danke» sagen
if ($feld('website') !== '' || (time() - (int) $feld('t')) < MIN_SEKUNDEN) {
    antworte(true, $danke);
}

// Begrenzung pro IP-Adresse
$ipHash   = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . __DIR__);
$rateFile = DATA_DIR . '/anfragen-rate.json';
$rate     = is_file($rateFile) ? (json_decode((string) file_get_contents($rateFile), true) ?: []) : [];
$jetzt    = time();
foreach ($rate as $k => $zeiten) {
    $rate[$k] = array_values(array_filter((array) $zeiten, static fn ($t) => $t > $jetzt - 3600));
    if (!$rate[$k]) {
        unset($rate[$k]);
    }
}
if (count($rate[$ipHash] ?? []) >= MAX_PRO_STUNDE) {
    antworte(false, 'Es wurden bereits mehrere Anfragen gesendet. Bitte ruft uns direkt an.', 429);
}

$daten = [
    'Anliegen'          => $feld('anliegen', 40),
    'Vorname Kind'      => $feld('kind_vorname', 80),
    'Nachname Kind'     => $feld('kind_nachname', 80),
    'Geburtsdatum'      => $feld('geburtsdatum', 20),
    'Gewünschter Start' => $feld('beginn', 20),
    'Betreuungstage'    => implode(', ', array_intersect(['Mo', 'Di', 'Mi', 'Do', 'Fr'], (array) ($_POST['tage'] ?? []))),
    'Name Eltern'       => $feld('name', 120),
    'Telefon'           => $feld('telefon', 40),
    'E-Mail'            => $feld('email', 160),
    'Adresse'           => $feld('adresse', 200),
    'Nachricht'         => $feld('nachricht', 4000),
];

if ($daten['Vorname Kind'] === '' || $daten['Nachname Kind'] === '' || $daten['Name Eltern'] === ''
    || $daten['Telefon'] === '' || !filter_var($daten['E-Mail'], FILTER_VALIDATE_EMAIL) || empty($_POST['einwilligung'])) {
    antworte(false, 'Bitte füllt alle Pflichtfelder (*) korrekt aus.', 422);
}
if (!in_array($daten['Anliegen'], ['Platzanfrage', 'Besichtigung', 'Frage'], true)) {
    $daten['Anliegen'] = 'Platzanfrage';
}
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    antworte(false, 'Das Formular ist noch nicht eingerichtet. Bitte ruft uns an.', 500);
}

$text = "Neue Anfrage über die Webseite\n\n";
foreach ($daten as $label => $wert) {
    if ($wert !== '') {
        $text .= str_pad($label . ':', 20) . ($label === 'Nachricht' ? "\n" : '') . $wert . "\n";
    }
}
$text .= "\nGesendet am " . date('d.m.Y \u\m H:i') . " Uhr\n";

$betreff = '=?UTF-8?B?' . base64_encode($daten['Anliegen'] . ': ' . $daten['Vorname Kind'] . ' ' . $daten['Nachname Kind']) . '?=';
$header  = implode("\r\n", [
    'From: =?UTF-8?B?' . base64_encode('Webseite Kita Farbewält') . '?= <' . ABSENDER . '>',
    'Reply-To: ' . $daten['E-Mail'],
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
]);

$ok = @mail($to, $betreff, $text, $header, '-f' . ABSENDER);
if (!$ok) {
    antworte(false, 'Das hat leider nicht geklappt. Bitte ruft uns an oder schreibt eine E-Mail.', 500);
}

$rate[$ipHash][] = $jetzt;
@file_put_contents($rateFile, json_encode($rate), LOCK_EX);
antworte(true, $danke);
