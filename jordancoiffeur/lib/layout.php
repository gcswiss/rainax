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
    $nav = ['/#ueber-uns' => 'Über uns', '/#services' => 'Services', '/#preise' => 'Preise', '/#standorte' => 'Standorte', '/jobs/' => 'Jobs'];
    ?><!doctype html>
<html lang="de-CH">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title>
<meta name="description" content="<?= h($description) ?>">
<meta name="theme-color" content="#0c0d21">
<meta property="og:title" content="<?= h($title) ?>">
<meta property="og:description" content="<?= h($description) ?>">
<meta property="og:image" content="<?= h(rtrim((string)cfg('site_url'), '/')) ?>/assets/img/barber.webp">
<meta property="og:locale" content="de_CH">
<link rel="icon" href="/assets/img/favicon-32.png" sizes="32x32">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<link rel="preload" href="/assets/fonts/oswald-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/site.css?v=1">
<?php foreach ($opts['css'] ?? [] as $css): ?><link rel="stylesheet" href="<?= h($css) ?>">
<?php endforeach; ?></head>
<body<?= $GLOBALS['jc_fab'] ? ' class="has-fab"' : '' ?>>
<a class="skip" href="#main">Zum Inhalt</a>
<header class="site-head">
  <div class="wrap">
    <a class="brand" href="/" aria-label="Jordan Coiffeur & Barbershop – Startseite">
      <img src="/assets/img/logo.webp" alt="" width="52" height="52">
      <span>Jordan<small>Coiffeur &amp; Barber</small></span>
    </a>
    <button class="menu-btn" aria-label="Menü" aria-expanded="false" aria-controls="nav"><?= icon('menu', 26) ?></button>
    <nav class="nav" id="nav" aria-label="Hauptnavigation">
      <?php foreach ($nav as $href => $label): ?>
        <a href="<?= $href ?>"<?= $current === $href ? ' aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
      <a class="btn" href="/buchen/">Termin buchen</a>
    </nav>
  </div>
</header>
<main id="main">
<?php
}

function page_foot(): void
{
    $branches = db()->query('SELECT * FROM branches WHERE active=1 ORDER BY sort,id')->fetchAll();
    $hours = db()->query('SELECT * FROM hours')->fetchAll();
    $h1 = array_filter($hours, fn($h) => (int)$h['branch_id'] === (int)($branches[0]['id'] ?? 0));
    ?>
</main>
<footer class="site-foot">
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <a class="brand" href="/"><img src="/assets/img/logo.webp" alt="" width="52" height="52"><span>Jordan<small>Coiffeur &amp; Barber</small></span></a>
        <p style="margin-top:16px">Scharfer Schnitt. Starker Style.<br>Wir unterstützen den FC St. Gallen.</p>
        <div class="social">
          <a href="https://www.instagram.com/jordan_coiffeur/" aria-label="Instagram" rel="noopener" target="_blank"><?= icon('instagram', 20) ?></a>
          <a href="https://www.facebook.com/profile.php?id=100042237135979" aria-label="Facebook" rel="noopener" target="_blank"><?= icon('facebook', 20) ?></a>
        </div>
      </div>
      <?php foreach ($branches as $b): ?>
      <div>
        <h4><?= h($b['name']) ?></h4>
        <ul>
          <li><?= h($b['street']) ?><br><?= h($b['city']) ?></li>
          <li><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', '+41' . ltrim($b['phone'], '0'))) ?>"><?= h($b['phone']) ?></a></li>
        </ul>
      </div>
      <?php endforeach; ?>
      <div>
        <h4>Öffnungszeiten</h4>
        <ul>
          <?php foreach (hours_lines($h1) as [$d, $t]): ?><li><?= $d ?>: <?= $t ?></li><?php endforeach; ?>
          <li><a href="mailto:kontakt@jordancoiffeur.ch">kontakt@jordancoiffeur.ch</a></li>
        </ul>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© <?= date('Y') ?> Saad Jordan Barbershop GmbH</span>
      <nav aria-label="Rechtliches"><a href="/impressum/">Impressum</a><a href="/datenschutz/">Datenschutz</a><a href="/admin/">Login</a></nav>
    </div>
  </div>
</footer>
<?php if ($GLOBALS['jc_fab'] ?? true): ?><a class="btn book-fab" href="/buchen/"><?= icon('calendar', 20) ?> Termin buchen</a><?php endif; ?>
<script src="/assets/js/site.js?v=1" defer></script>
</body>
</html>
<?php
}
