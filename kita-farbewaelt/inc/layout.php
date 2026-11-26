<?php
/**
 * Kopf, Fusszeile und wiederkehrende Gestaltungselemente.
 */

declare(strict_types=1);

const NAV = [
    'ueber-uns'  => 'Über uns',
    'team'       => 'Team',
    'angebot'    => 'Angebot',
    'paedagogik' => 'Pädagogik',
    'tarife'     => 'Tarife',
    'jobs'       => 'Jobs',
];

/** Kleine Linien-Icons (24×24). */
function icon(string $name, string $class = 'icon'): string
{
    $paths = [
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'phone'   => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'mail'    => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 7 9 6 9-6"/>',
        'pin'     => '<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'car'     => '<path d="M5 16V11l2-5h10l2 5v5M5 16h14M5 16v2M19 16v2"/><circle cx="8" cy="13.5" r=".5"/><circle cx="16" cy="13.5" r=".5"/>',
        'insta'   => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/>',
        'whatsapp'=> '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 1a4 4 0 0 1-2-2l1-1-1-2z"/>',
        'heart'   => '<path d="M12 20s-7.5-4.6-7.5-10A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7.5 3c0 5.4-7.5 10-7.5 10z"/>',
        'moon'    => '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/>',
        'kids'    => '<circle cx="8" cy="6" r="2.5"/><circle cx="16.5" cy="8" r="2"/><path d="M4 21v-5a4 4 0 0 1 8 0v5M13 21v-4a3.5 3.5 0 0 1 7 0v4"/>',
        'percent' => '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>',
        'calc'    => '<rect x="5" y="3" width="14" height="18" rx="3"/><path d="M8 7h8M8 12h1M12 12h0M15.5 12h.5M8 16h1M12 16h0M15.5 16h.5"/>',
        'file'    => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'sun'     => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'leaf'    => '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19 13 11"/>',
        'spoon'   => '<path d="M8 3c2 0 3 2 3 4.5S10 11 8 11 5 10 5 7.5 6 3 8 3zM8 11v10M16 3v18M16 3c2 1 3 3 3 6h-3"/>',
        'play'    => '<path d="M8 5v14l11-7z"/>',
        'close'   => '<path d="M6 6l12 12M18 6 6 18"/>',
        'check'   => '<path d="m5 12 4.5 4.5L19 7"/>',
        'target'  => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/>',
        'star'    => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
    ];
    if ($name === 'heart-fill') {
        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 21.2c-.3 0-.6-.1-.8-.3C7.6 18 2.5 14 2.5 8.9 2.5 6 4.7 3.8 7.4 3.8c1.9 0 3.6 1 4.6 2.6 1-1.6 2.7-2.6 4.6-2.6 2.7 0 4.9 2.2 4.9 5.1 0 5.1-5.1 9.1-8.7 12-.2.2-.5.3-.8.3z"/></svg>';
    }
    $p = $paths[$name] ?? '';
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/** Boho-Regenbogen in den Logofarben. */
function rainbow(string $class = ''): string
{
    return '<svg class="rainbow ' . e($class) . '" viewBox="0 0 200 104" aria-hidden="true">'
        . '<path d="M8 104a92 92 0 0 1 184 0" fill="none" stroke="var(--brown)" stroke-width="16"/>'
        . '<path d="M28 104a72 72 0 0 1 144 0" fill="none" stroke="var(--ochre)" stroke-width="16"/>'
        . '<path d="M48 104a52 52 0 0 1 104 0" fill="none" stroke="var(--yellow)" stroke-width="16"/>'
        . '<path d="M68 104a32 32 0 0 1 64 0" fill="none" stroke="var(--green)" stroke-width="16"/>'
        . '</svg>';
}

/** Die vier Punkte aus dem Logo. */
function dots(string $class = ''): string
{
    return '<span class="dots ' . e($class) . '" aria-hidden="true"><i></i><i></i><i></i><i></i></span>';
}

function section_head(string $kicker, string $title, string $text = '', string $align = ''): string
{
    $out = '<header class="section-head ' . e($align) . ' reveal">';
    if ($kicker !== '') {
        $out .= '<p class="kicker">' . e($kicker) . '</p>';
    }
    $out .= '<h2>' . line($title) . '</h2>';
    if ($text !== '') {
        $out .= paragraphs($text, 'lead');
    }
    return $out . '</header>';
}

/** Kopfbereich für Unterseiten. */
function page_hero(string $kicker, string $title, string $intro = '', string $tone = 'green'): void
{
    ?>
    <section class="page-hero tone-<?= e($tone) ?>">
        <div class="wrap">
            <p class="kicker reveal"><?= e($kicker) ?></p>
            <h1 class="reveal"><?= line($title) ?></h1>
            <?php if ($intro !== ''): ?><div class="lead reveal"><?= paragraphs($intro) ?></div><?php endif; ?>
        </div>
        <?= rainbow('page-hero__rainbow') ?>
        <?= dots('page-hero__dots dots--bounce') ?>
    </section>
    <?php
}

function render_header(array $c, string $page, string $title): void
{
    $a       = $c['allgemein'] ?? [];
    $name    = $a['name'] ?? 'Kita Farbewält';
    $docTitle = $title !== '' ? $title . ' – ' . $name . ' Dietikon' : $name . ' – Kinderkrippe in Dietikon';
    ?>
<!doctype html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($docTitle) ?></title>
    <meta name="description" content="<?= e($a['meta_beschreibung'] ?? '') ?>">
    <meta name="theme-color" content="#FFFBF2">
    <meta property="og:title" content="<?= e($docTitle) ?>">
    <meta property="og:description" content="<?= e($a['meta_beschreibung'] ?? '') ?>">
    <meta property="og:image" content="<?= e(url('assets/img/logo.png')) ?>">
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(url('assets/img/apple-touch-icon.png')) ?>">
    <link rel="preload" href="<?= e(url('assets/fonts/fredoka.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(url('assets/fonts/nunito.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ChildCare',
        'name' => $name,
        'telephone' => $a['telefon'] ?? '',
        'email' => $a['email'] ?? '',
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => $a['strasse'] ?? '', 'addressLocality' => 'Dietikon', 'postalCode' => '8953', 'addressCountry' => 'CH'],
        'openingHours' => 'Mo-Fr 06:45-18:15',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="page-<?= e($page) ?>">
<a class="skip" href="#inhalt">Zum Inhalt springen</a>
<header class="site-header" data-header>
    <div class="wrap site-header__inner">
        <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($name) ?> – zur Startseite">
            <img src="<?= e(asset('assets/img/logo.png')) ?>" alt="<?= e($name) ?> Kinderkrippe" width="921" height="430">
        </a>
        <nav class="main-nav" id="menu" aria-label="Hauptnavigation">
            <ul>
                <?php foreach (NAV as $slug => $label): ?>
                    <li><a href="<?= e(url($slug . '/')) ?>"<?= $page === $slug ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn--sun main-nav__cta" href="<?= e(url('kontakt/')) ?>">Besichtigung buchen</a>
            <div class="main-nav__extra">
                <a href="<?= e(tel_link($a['telefon'] ?? '')) ?>"><?= icon('phone') ?> <?= e($a['telefon'] ?? '') ?></a>
                <a href="mailto:<?= e($a['email'] ?? '') ?>"><?= icon('mail') ?> <?= e($a['email'] ?? '') ?></a>
            </div>
            <?= rainbow('main-nav__rainbow') ?>
        </nav>
        <button class="menu-toggle" type="button" aria-controls="menu" aria-expanded="false" data-menu-toggle>
            <span class="menu-toggle__bars" aria-hidden="true"><i></i><i></i></span>
            <span class="sr-only">Menü</span>
        </button>
    </div>
</header>
<main id="inhalt">
    <?php
}

function render_footer(array $c, string $page): void
{
    $a = $c['allgemein'] ?? [];
    ?>
</main>
<footer class="site-footer">
    <div class="site-footer__wave" aria-hidden="true">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none"><path d="M0 40c120-30 240-30 360 0s240 30 360 0 240-30 360 0 240 30 360 0v40H0z" fill="currentColor"/></svg>
    </div>
    <div class="site-footer__body">
        <div class="wrap site-footer__grid">
            <div class="site-footer__brand">
                <p class="site-footer__name"><?= e($a['name'] ?? '') ?></p>
                <p class="hand">Mit Herz. Mit euch gemeinsam.</p>
                <?= dots() ?>
                <div class="social">
                    <?php if (!empty($a['instagram'])): ?>
                        <a href="<?= e($a['instagram']) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icon('insta') ?></a>
                    <?php endif; ?>
                    <?php if (!empty($a['whatsapp'])): ?>
                        <a href="https://wa.me/<?= e(preg_replace('/\D+/', '', $a['whatsapp'])) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('whatsapp') ?></a>
                    <?php endif; ?>
                    <a href="mailto:<?= e($a['email'] ?? '') ?>" aria-label="E-Mail"><?= icon('mail') ?></a>
                </div>
            </div>
            <div>
                <h2 class="site-footer__title">Adresse</h2>
                <p><?= e($a['firma'] ?? '') ?><br><?= e($a['strasse'] ?? '') ?><br><?= e($a['ort'] ?? '') ?></p>
                <p class="site-footer__small"><?= icon('car') ?> <?= e($a['parkieren'] ?? '') ?></p>
                <?php if (!empty($a['maps_link'])): ?><p><a class="link" href="<?= e($a['maps_link']) ?>" target="_blank" rel="noopener">Route planen</a></p><?php endif; ?>
            </div>
            <div>
                <h2 class="site-footer__title">Öffnungszeiten</h2>
                <p><?= e($a['oeffnungszeiten_tage'] ?? '') ?><br><strong><?= e($a['oeffnungszeiten_zeit'] ?? '') ?></strong></p>
                <h2 class="site-footer__title">Kontakt</h2>
                <p><a href="<?= e(tel_link($a['telefon'] ?? '')) ?>"><?= e($a['telefon'] ?? '') ?></a><br><a href="mailto:<?= e($a['email'] ?? '') ?>"><?= e($a['email'] ?? '') ?></a></p>
            </div>
            <div class="site-footer__cta">
                <p class="site-footer__title">Neugierig geworden?</p>
                <a class="btn btn--sun" href="<?= e(url('kontakt/')) ?>">Besichtigung buchen <?= icon('arrow') ?></a>
            </div>
        </div>
        <div class="wrap site-footer__legal">
            <span>© <?= date('Y') ?> <?= e($a['firma'] ?? '') ?></span>
            <nav aria-label="Rechtliches">
                <a href="<?= e(url('impressum/')) ?>">Impressum</a>
                <a href="<?= e(url('datenschutz/')) ?>">Datenschutz</a>
                <a href="<?= e(url('admin/')) ?>" rel="nofollow">Login</a>
            </nav>
        </div>
    </div>
</footer>
<?php if (!empty($a['whatsapp'])): ?>
<a class="float-wa" href="https://wa.me/<?= e(preg_replace('/\D+/', '', $a['whatsapp'])) ?>" target="_blank" rel="noopener" aria-label="Per WhatsApp schreiben"><?= icon('whatsapp') ?></a>
<?php endif; ?>
<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
</body>
</html>
    <?php
}
