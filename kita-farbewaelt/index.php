<?php
/**
 * Kita Farbewält — Webseite
 * Alle Seiten laufen über diese Datei. Die Inhalte stehen in data/content.json
 * und werden im Admin-Bereich (/admin/) bearbeitet.
 */

declare(strict_types=1);

// Lokaler PHP-Testserver: vorhandene Dateien direkt ausliefern.
if (PHP_SAPI === 'cli-server') {
    if (preg_match('~^/(data|inc)(/|$)~', (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
        http_response_code(403);
        exit;
    }
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
    if (preg_match('~^/admin(/.*)?$~', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
        require __DIR__ . '/admin/index.php';
        exit;
    }
    if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/anfrage.php') {
        require __DIR__ . '/anfrage.php';
        exit;
    }
}

require __DIR__ . '/inc/bootstrap.php';

const PAGES = [
    ''            => ['start',       ''],
    'ueber-uns'   => ['ueber-uns',   'Über uns'],
    'team'        => ['team',        'Team'],
    'angebot'     => ['angebot',     'Angebot'],
    'paedagogik'  => ['paedagogik',  'Pädagogik'],
    'tarife'      => ['tarife',      'Tarife'],
    'jobs'        => ['jobs',        'Jobs'],
    'kontakt'     => ['kontakt',     'Kontakt'],
    'impressum'   => ['impressum',   'Impressum'],
    'datenschutz' => ['datenschutz', 'Datenschutz'],
];

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$base = base_url();
if (str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$slug = trim($path, '/');
if ($slug === 'index.php') {
    $slug = '';
}
// Fallback ohne mod_rewrite: index.php?seite=tarife
if ($slug === '' && isset($_GET['seite']) && is_string($_GET['seite'])) {
    $slug = trim($_GET['seite'], '/');
}

$c = load_content();

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (!isset(PAGES[$slug])) {
    http_response_code(404);
    $page  = '404';
    $title = 'Seite nicht gefunden';
} else {
    [$page, $title] = PAGES[$slug];
}

require __DIR__ . '/inc/layout.php';
render_header($c, $page, $title);
require __DIR__ . '/inc/pages/' . $page . '.php';
render_footer($c, $page);
