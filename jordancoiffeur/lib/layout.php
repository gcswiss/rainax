<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

const DAYS_SHORT = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];

/** Öffnungszeiten zusammenfassen: "Mo – Fr 09:00 – 19:00", "Sa 09:00 – 17:00" */
function hours_lines(array $hours): array
{
    $byDay = [];
    foreach ($hours as $h) $byDay[(int)$h['weekday']] = "{$h['open']} – {$h['close']}";
    $out = []; $d = 1;
    while ($d <= 7) {
        if (!isset($byDay[$d])) { $d++; continue; }
        $e = $d;
        while (isset($byDay[$e + 1]) && $byDay[$e + 1] === $byDay[$d]) $e++;
        $out[] = [($e > $d ? DAYS_SHORT[$d] . ' – ' . DAYS_SHORT[$e] : DAYS_SHORT[$d]), $byDay[$d]];
        $d = $e + 1;
    }
    return $out;
}

function icon(string $name, int $size = 22): string
{
    $p = [
        'scissors' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4 8.12 15.88M14.47 14.48 20 20M8.12 8.12 12 12"/>',
        'razor' => '<path d="M3 17 14 6l4 4L7 21H3z"/><path d="m14 6 3-3 4 4-3 3"/>',
        'drop' => '<path d="M12 2.7s6 6.3 6 11.3a6 6 0 0 1-12 0c0-5 6-11.3 6-11.3z"/>',
        'pin' => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
    ][$name] ?? '';
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function page_head(string $title, string $description, string $current = '', array $opts = []): void
{
    $GLOBALS['jc_fab'] = $opts['fab'] ?? true;
    $classes = array_filter([$GLOBALS['jc_fab'] ? 'has-fab' : '', !empty($opts['hero']) ? 'has-hero' : '']);
    $nav = ['/#ueber-uns' => 'Über uns', '/#services' => 'Services', '/#preise' => 'Preise', '/#kontakt' => 'Kontakt', '/jobs/' => 'Jobs'];
    ?><!doctype html>
<html lang="de-CH">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title>
<meta name="description" content="<?= h($description) ?>">
<meta name="theme-color" content="#1d2a3a">
<meta property="og:title" content="<?= h($title) ?>">
<meta property="og:description" content="<?= h($description) ?>">
<meta property="og:image" content="<?= h(rtrim((string)cfg('site_url'), '/')) ?>/assets/img/hero-poster.webp">
<meta property="og:locale" content="de_CH">
<link rel="icon" href="/assets/img/favicon-32.png" sizes="32x32">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<link rel="preload" href="/assets/fonts/playfair-display-latin-900-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/site.css?v=2">
<?php foreach ($opts['css'] ?? [] as $css): ?><link rel="stylesheet" href="<?= h($css) ?>">
<?php endforeach; ?></head>
<body<?= $classes ? ' class="' . implode(' ', $classes) . '"' : '' ?>>
<a class="skip" href="#main">Zum Inhalt</a>
<header class="site-head">
  <div class="wrap">
    <a class="brand" href="/" aria-label="Jordan Coiffeur Barber – Startseite">
      <img class="dark" src="/assets/img/logo-dark.webp" alt="" width="64" height="65">
      <img class="light" src="/assets/img/logo.webp" alt="" width="64" height="65">
    </a>
    <button class="menu-btn" aria-label="Menü" aria-expanded="false" aria-controls="nav"><?= icon('menu', 26) ?></button>
    <nav class="nav" id="nav" aria-label="Hauptnavigation">
      <?php foreach ($nav as $href => $label): ?>
        <a href="<?= $href ?>"<?= $current === $href ? ' aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
      <a class="btn" href="/buchen/">Jetzt buchen</a>
    </nav>
  </div>
</header>
<main id="main">
<?php
}

function tel_link(string $phone): string { return 'tel:+41' . preg_replace('/\D/', '', ltrim($phone, '0')); }

function page_foot(): void
{
    $branches = db()->query('SELECT * FROM branches WHERE active=1 ORDER BY sort,id')->fetchAll();
    ?>
</main>
<footer class="site-foot">
  <div class="wrap">
    <img class="logo" src="/assets/img/logo.webp" alt="Jordan Coiffeur Barber" width="130" height="132" loading="lazy">
    <div class="foot-cols">
      <?php foreach ($branches as $b): ?>
      <p><?= h($b['street']) ?><br><?= h($b['city']) ?><br><a class="tel" href="<?= h(tel_link($b['phone'])) ?>"><?= h($b['phone']) ?></a></p>
      <?php endforeach; ?>
    </div>
    <p class="follow">Follow us</p>
    <div class="social">
      <a href="https://www.facebook.com/profile.php?id=100042237135979" aria-label="Facebook" rel="noopener" target="_blank"><?= icon('facebook', 18) ?></a>
      <a href="https://www.instagram.com/jordan_coiffeur/" aria-label="Instagram" rel="noopener" target="_blank"><?= icon('instagram', 18) ?></a>
      <a href="https://www.google.com/maps/search/?api=1&amp;query=Jordan+Coiffeur+St.+Leonhardstrasse+22+St.+Gallen" aria-label="Google Maps" rel="noopener" target="_blank"><?= icon('pin', 18) ?></a>
    </div>
    <p class="support">Wir unterstützen den FC St. Gallen</p>
    <div class="foot-bottom">
      <span>© <?= date('Y') ?> Saad Jordan Barbershop GmbH</span>
      <a href="/impressum/">Impressum</a><a href="/datenschutz/">Datenschutz</a><a href="/admin/">Login</a>
    </div>
  </div>
</footer>
<?php if ($GLOBALS['jc_fab'] ?? true): ?><a class="btn gold book-fab" href="/buchen/"><?= icon('calendar', 20) ?> Jetzt buchen</a><?php endif; ?>
<script src="/assets/js/site.js?v=2" defer></script>
</body>
</html>
<?php
}
