<?php
/**
 * RAINAX AG — Kontaktformular
 *
 * Nimmt das Formular von /kontakt/ entgegen, prüft die Eingaben und sendet
 * sie als E-Mail an EMPFAENGER. Antwortet mit JSON (Aufruf per JavaScript)
 * oder leitet zurück auf /kontakt/ (Aufruf ohne JavaScript).
 *
 * Voraussetzung beim Hoster: PHP 7.4 oder neuer und eine funktionierende
 * mail()-Funktion. ABSENDER muss eine Adresse der eigenen Domain sein,
 * sonst landen die Nachrichten oft im Spam (SPF/DMARC).
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Zurich');

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $text): int
    {
        return (int) preg_match_all('/./us', $text);
    }
}

// ---------------------------------------------------------------------------
// Einstellungen
// ---------------------------------------------------------------------------

const EMPFAENGER       = 'info@rainax.ch';
const ABSENDER         = 'webseite@rainax.ch';
const ABSENDER_NAME    = 'RAINAX Webseite';
const BETREFF          = 'Neue Anfrage über rainax.ch';
const MIN_SEKUNDEN     = 3;    // schneller ausgefüllt = vermutlich ein Bot
const MAX_PRO_STUNDE   = 5;    // Nachrichten pro IP-Adresse und Stunde
const THEMEN = [
    'Ich habe eine Idee',
    'Ich brauche eine App',
    'Ich interessiere mich für KI',
    'Etwas anderes',
];

// ---------------------------------------------------------------------------
// Antwort
// ---------------------------------------------------------------------------

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$wantsJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

/**
 * @param array<string, string> $fehler Feldname => Meldung
 */
function antworte(int $status, bool $ok, string $meldung, array $fehler = []): void
{
    global $wantsJson;

    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(
            ['ok' => $ok, 'message' => $meldung, 'errors' => (object) $fehler],
            JSON_UNESCAPED_UNICODE
        );
    } else {
        header('Location: /kontakt/?status=' . ($ok ? 'gesendet' : 'fehler') . '#formular', true, 303);
    }
    exit;
}

const DANKE = 'Danke für Ihre Nachricht. Wir melden uns in Kürze persönlich bei Ihnen.';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    antworte(405, false, 'Diese Adresse nimmt nur Formular-Nachrichten entgegen.');
}

// ---------------------------------------------------------------------------
// Schutz vor Spam
// ---------------------------------------------------------------------------

// Verstecktes Feld: Menschen sehen es nicht, Bots füllen es aus.
// Bots bekommen eine Erfolgsmeldung, damit sie nicht weiter probieren.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    antworte(200, true, DANKE);
}

// Zeitfalle: Das Formular setzt beim Laden einen Zeitstempel.
$geladen = (int) ($_POST['ts'] ?? 0);
if ($geladen > 0 && (time() - intdiv($geladen, 1000)) < MIN_SEKUNDEN) {
    antworte(200, true, DANKE);
}

// Begrenzung pro IP-Adresse (gespeichert als Hash, ohne die Adresse selbst)
function zuVieleAnfragen(): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unbekannt';
    $datei = sys_get_temp_dir() . '/rainax-kontakt-' . hash('sha256', $ip . __FILE__) . '.json';
    $jetzt = time();

    $zeiten = [];
    if (is_file($datei)) {
        $gelesen = json_decode((string) file_get_contents($datei), true);
        if (is_array($gelesen)) {
            $zeiten = array_values(array_filter($gelesen, static fn ($t) => is_int($t) && $t > $jetzt - 3600));
        }
    }
    if (count($zeiten) >= MAX_PRO_STUNDE) {
        return true;
    }
    $zeiten[] = $jetzt;
    @file_put_contents($datei, json_encode($zeiten), LOCK_EX);
    return false;
}

// ---------------------------------------------------------------------------
// Eingaben prüfen
// ---------------------------------------------------------------------------

/** Text bereinigen: Steuerzeichen entfernen, Leerraum kürzen. */
function feld(string $name, bool $mehrzeilig = false): string
{
    $wert = (string) ($_POST[$name] ?? '');
    $wert = $mehrzeilig
        ? preg_replace('/[^\P{C}\n\t]/u', '', str_replace("\r\n", "\n", $wert))
        : preg_replace('/\p{C}/u', '', $wert);
    return trim((string) $wert);
}

/** Zeilenumbrüche entfernen (schützt E-Mail-Kopfzeilen vor Manipulation). */
function einzeilig(string $wert): string
{
    return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', $wert));
}

$name      = einzeilig(feld('name'));
$email     = einzeilig(feld('email'));
$telefon   = einzeilig(feld('phone'));
$thema     = einzeilig(feld('topic'));
$nachricht = feld('message', true);

$fehler = [];

if ($name === '') {
    $fehler['name'] = 'Bitte geben Sie Ihren Namen an.';
} elseif (mb_strlen($name) > 120) {
    $fehler['name'] = 'Der Name ist zu lang.';
}

if ($email === '') {
    $fehler['email'] = 'Bitte geben Sie Ihre E-Mail-Adresse an.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 200) {
    $fehler['email'] = 'Bitte prüfen Sie Ihre E-Mail-Adresse.';
}

if ($telefon !== '' && !preg_match('/^[0-9+()\/.\s-]{6,30}$/', $telefon)) {
    $fehler['phone'] = 'Bitte prüfen Sie Ihre Telefonnummer.';
}

if (!in_array($thema, THEMEN, true)) {
    $fehler['topic'] = 'Bitte wählen Sie, worum es geht.';
}

if ($nachricht === '') {
    $fehler['message'] = 'Bitte schreiben Sie uns eine Nachricht.';
} elseif (mb_strlen($nachricht) > 5000) {
    $fehler['message'] = 'Die Nachricht ist zu lang (höchstens 5000 Zeichen).';
}

if ($fehler) {
    antworte(422, false, 'Bitte prüfen Sie die markierten Felder.', $fehler);
}

if (zuVieleAnfragen()) {
    antworte(429, false, 'Sie haben uns gerade mehrere Nachrichten gesendet. Bitte versuchen Sie es später noch einmal oder schreiben Sie an ' . EMPFAENGER . '.');
}

// ---------------------------------------------------------------------------
// E-Mail senden
// ---------------------------------------------------------------------------

function kodiere(string $text): string
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

$betreff = BETREFF . ': ' . $thema;

$text = implode("\n", [
    'Neue Anfrage über das Kontaktformular von rainax.ch',
    '',
    'Thema:    ' . $thema,
    'Name:     ' . $name,
    'E-Mail:   ' . $email,
    'Telefon:  ' . ($telefon !== '' ? $telefon : '–'),
    '',
    'Nachricht:',
    $nachricht,
    '',
    '—',
    'Gesendet am ' . date('d.m.Y \u\m H:i') . ' Uhr. Mit «Antworten» geht die E-Mail direkt an ' . $email . '.',
]);

$kopf = [
    'From: ' . kodiere(ABSENDER_NAME) . ' <' . ABSENDER . '>',
    'Reply-To: ' . kodiere($name) . ' <' . $email . '>',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'X-Mailer: RAINAX-Kontaktformular',
];

$gesendet = @mail(EMPFAENGER, kodiere($betreff), $text, implode("\r\n", $kopf), '-f' . ABSENDER);
if (!$gesendet) {
    // Manche Hoster erlauben den Parameter -f nicht: ohne ihn erneut versuchen.
    $gesendet = @mail(EMPFAENGER, kodiere($betreff), $text, implode("\r\n", $kopf));
}

if (!$gesendet) {
    error_log('RAINAX Kontaktformular: mail() fehlgeschlagen');
    antworte(500, false, 'Ihre Nachricht konnte leider nicht gesendet werden. Bitte schreiben Sie uns direkt an ' . EMPFAENGER . '.');
}

antworte(200, true, DANKE);
