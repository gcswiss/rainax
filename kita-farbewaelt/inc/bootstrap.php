<?php
/**
 * Gemeinsame Grundlage für Webseite und Admin-Bereich.
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Zurich');
mb_internal_encoding('UTF-8');

const ROOT        = __DIR__ . '/..';
const DATA_DIR    = ROOT . '/data';
const CONTENT     = DATA_DIR . '/content.json';
const UPLOAD_DIR  = ROOT . '/uploads';
const MAX_BACKUPS = 40;

/** Basis-URL der Webseite (funktioniert im Hauptverzeichnis und in Unterordnern). */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
        $root    = realpath(ROOT) ?: ROOT;
        $base    = ($docRoot !== '' && str_starts_with($root, $docRoot))
            ? str_replace('\\', '/', substr($root, strlen($docRoot)))
            : '';
        $base = rtrim($base, '/') . '/';
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_url() . ltrim($path, '/');
}

/** Adresse einer hochgeladenen Datei oder eines Assets, mit Versionsnummer gegen alten Browser-Cache. */
function asset(string $path): string
{
    if ($path === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    $file = ROOT . '/' . ltrim($path, '/');
    $v    = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $v;
}

function load_content(): array
{
    $json = is_file(CONTENT) ? file_get_contents(CONTENT) : '{}';
    $data = json_decode((string) $json, true);
    return is_array($data) ? $data : [];
}

function save_content(array $data): bool
{
    if (!is_dir(DATA_DIR . '/backups')) {
        @mkdir(DATA_DIR . '/backups', 0750, true);
    }
    if (is_file(CONTENT)) {
        @copy(CONTENT, DATA_DIR . '/backups/content-' . date('Y-m-d_H-i-s') . '-' . bin2hex(random_bytes(2)) . '.json');
        $backups = glob(DATA_DIR . '/backups/content-*.json') ?: [];
        sort($backups);
        while (count($backups) > MAX_BACKUPS) {
            @unlink(array_shift($backups));
        }
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tmp  = CONTENT . '.tmp';
    if ($json === false || file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, CONTENT);
}

/** HTML-sicher ausgeben. */
function e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** **fett** in <strong> umwandeln (nach dem Escapen). */
function inline_format(string $escaped): string
{
    return (string) preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);
}

/** Text mit Leerzeilen in Absätze verwandeln, einfache Zeilenumbrüche bleiben erhalten. */
function paragraphs(?string $text, string $class = ''): string
{
    $text = trim(str_replace("\r\n", "\n", (string) $text));
    if ($text === '') {
        return '';
    }
    $attr = $class !== '' ? ' class="' . e($class) . '"' : '';
    $out  = '';
    foreach (preg_split("/\n{2,}/", $text) as $block) {
        $out .= '<p' . $attr . '>' . nl2br(inline_format(e(trim($block))), false) . '</p>';
    }
    return $out;
}

/** Einzeiliger Text mit **fett**. */
function line(?string $text): string
{
    return inline_format(e($text));
}

function lines(?string $text): array
{
    return array_values(array_filter(array_map('trim', explode("\n", (string) $text)), 'strlen'));
}

/** Betrag im Schweizer Format: 1'108.80 */
function chf(float $value, bool $decimals = true): string
{
    return number_format($value, $decimals ? 2 : 0, '.', "'");
}

function num(?string $value): float
{
    return (float) str_replace([',', "'", ' '], ['.', '', ''], (string) $value);
}

function tel_link(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (str_starts_with($digits, '0')) {
        $digits = '41' . substr($digits, 1);
    }
    return 'tel:+' . $digits;
}

/** Initialen für Team-Mitglieder ohne Foto. */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '?', 0, 1);
    $last  = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('farbewaelt');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_url(),
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
