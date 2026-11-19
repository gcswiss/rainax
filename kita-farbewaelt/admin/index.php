<?php
/**
 * Kita Farbewält — Admin-Bereich
 *
 * Texte, Bilder, Videos, Team, Jobs, Tarife und PDF bearbeiten.
 * Die Inhalte werden in data/content.json gespeichert; vor jedem Speichern
 * wird automatisch eine Sicherung in data/backups/ angelegt.
 */

declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';

const CONFIG_FILE   = DATA_DIR . '/config.php';
const ATTEMPTS_FILE = DATA_DIR . '/login-attempts.json';
const MAX_ATTEMPTS  = 6;
const LOCK_MINUTES  = 15;
const SESSION_IDLE  = 7200;

const UPLOAD_TYPES = [
    'image' => ['ext' => ['jpg', 'jpeg', 'png', 'webp', 'gif'], 'mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], 'max' => 25],
    'video' => ['ext' => ['mp4', 'm4v', 'mov', 'webm'], 'mime' => ['video/mp4', 'video/quicktime', 'video/webm', 'video/x-m4v', 'application/octet-stream'], 'max' => 300],
    'file'  => ['ext' => ['pdf'], 'mime' => ['application/pdf'], 'max' => 30],
];

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

start_session();

$schema = require ROOT . '/inc/schema.php';
$config = is_file(CONFIG_FILE) ? (require CONFIG_FILE) : [];

// ---------------------------------------------------------------------------
// Hilfsfunktionen
// ---------------------------------------------------------------------------

function admin_url(array $query = []): string
{
    return url('admin/') . ($query ? '?' . http_build_query($query) : '');
}

function redirect(array $query = []): never
{
    header('Location: ' . admin_url($query), true, 303);
    exit;
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = [$msg, $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function is_logged_in(): bool
{
    if (empty($_SESSION['admin'])) {
        return false;
    }
    if (time() - ($_SESSION['seen'] ?? 0) > SESSION_IDLE) {
        $_SESSION = [];
        return false;
    }
    $_SESSION['seen'] = time();
    return true;
}

function client_key(): string
{
    return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'farbewaelt');
}

function attempts(): array
{
    $a = is_file(ATTEMPTS_FILE) ? json_decode((string) file_get_contents(ATTEMPTS_FILE), true) : [];
    return is_array($a) ? $a : [];
}

function save_config(array $config): bool
{
    $php = "<?php\n// Automatisch erstellt – nicht von Hand bearbeiten.\nreturn " . var_export($config, true) . ";\n";
    return file_put_contents(CONFIG_FILE, $php, LOCK_EX) !== false;
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Eingaben anhand des Bauplans bereinigen – nur bekannte Felder werden gespeichert. */
function clean_value(array $def, mixed $value): mixed
{
    $type = $def['type'];
    if ($type === 'list') {
        $out = [];
        foreach (is_array($value) ? $value : [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $row = [];
            foreach ($def['fields'] as $k => $sub) {
                $row[$k] = clean_value($sub, $item[$k] ?? '');
            }
            if (implode('', array_map('strval', $row)) !== '') {
                $out[] = $row;
            }
        }
        return $out;
    }
    $v = is_string($value) ? $value : '';
    $v = str_replace("\r\n", "\n", $v);
    $v = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
    $v = mb_substr($type === 'textarea' ? $v : trim($v), 0, 20000);
    switch ($type) {
        case 'image':
        case 'video':
        case 'file':
            return preg_match('~^uploads/[A-Za-z0-9._-]+$~', $v) ? $v : '';
        case 'url':
            return ($v === '' || preg_match('~^(https?://|mailto:|tel:|/)~i', $v)) ? $v : 'https://' . $v;
        case 'email':
            return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : '';
        case 'number':
            $n = str_replace([',', "'", ' '], ['.', '', ''], $v);
            return is_numeric($n) ? $n : '';
        case 'textarea':
            return trim($v);
        default:
            return str_replace("\n", ' ', $v);
    }
}

function slugify(string $name): string
{
    $name = strtolower(strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'é' => 'e', 'è' => 'e', 'à' => 'a']));
    $name = trim((string) preg_replace('/[^a-z0-9]+/', '-', $name), '-');
    return substr($name !== '' ? $name : 'datei', 0, 40);
}

/** Bild verkleinern (max. 2000 px) und Handy-Metadaten (z. B. GPS) entfernen – falls GD verfügbar ist. */
function optimize_image(string $path, string $ext): void
{
    if (!function_exists('imagecreatefromstring') || $ext === 'gif') {
        return;
    }
    $data = @file_get_contents($path);
    $img  = $data !== false ? @imagecreatefromstring($data) : false;
    if (!$img) {
        return;
    }
    if (in_array($ext, ['jpg', 'jpeg'], true) && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $rot  = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? 0;
        if ($rot) {
            $img = imagerotate($img, $rot, 0) ?: $img;
        }
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $max = 2000;
    if ($w > $max || $h > $max) {
        $scale = $max / max($w, $h);
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $img = $dst;
    }
    match ($ext) {
        'png'   => imagepng($img, $path, 8),
        'webp'  => function_exists('imagewebp') ? imagewebp($img, $path, 82) : null,
        default => imagejpeg($img, $path, 82),
    };
}

// ---------------------------------------------------------------------------
// Aktionen
// ---------------------------------------------------------------------------

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Ersteinrichtung: Passwort festlegen, falls noch keines existiert
if (empty($config['password_hash'])) {
    if ($action === 'setup' && csrf_check($_POST['csrf'] ?? null)) {
        $pw = (string) ($_POST['password'] ?? '');
        if (mb_strlen($pw) < 10 || $pw !== ($_POST['password2'] ?? '')) {
            flash('Das Passwort muss mindestens 10 Zeichen haben und zweimal gleich sein.', 'err');
        } elseif (!empty($config['setup_code']) && !hash_equals((string) $config['setup_code'], (string) ($_POST['code'] ?? ''))) {
            flash('Der Einrichtungs-Code stimmt nicht.', 'err');
        } else {
            save_config(['password_hash' => password_hash($pw, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['seen']  = time();
            flash('Willkommen! Euer Passwort ist gespeichert.');
        }
        redirect();
    }
    $view = 'setup';
} elseif ($action === 'login') {
    $att  = attempts();
    $key  = client_key();
    $mine = $att[$key] ?? ['n' => 0, 't' => 0];
    if ($mine['n'] >= MAX_ATTEMPTS && time() - $mine['t'] < LOCK_MINUTES * 60) {
        flash('Zu viele Versuche. Bitte in ' . LOCK_MINUTES . ' Minuten erneut probieren.', 'err');
        redirect();
    }
    if (csrf_check($_POST['csrf'] ?? null) && password_verify((string) ($_POST['password'] ?? ''), $config['password_hash'])) {
        unset($att[$key]);
        file_put_contents(ATTEMPTS_FILE, json_encode($att), LOCK_EX);
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['seen']  = time();
        redirect();
    }
    $att[$key] = ['n' => ($mine['n'] >= MAX_ATTEMPTS ? 0 : $mine['n']) + 1, 't' => time()];
    file_put_contents(ATTEMPTS_FILE, json_encode($att), LOCK_EX);
    usleep(400000);
    flash('Das Passwort ist leider falsch.', 'err');
    redirect();
} elseif (!is_logged_in()) {
    $view = 'login';
} else {
    $view = 'edit';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_check($_POST['csrf'] ?? null)) {
        if ($action === 'upload') {
            json_out(['ok' => false, 'message' => 'Sitzung abgelaufen. Bitte Seite neu laden.'], 403);
        }
        flash('Die Sitzung ist abgelaufen. Bitte nochmals versuchen.', 'err');
        redirect();
    }

    switch ($action) {
        case 'logout':
            $_SESSION = [];
            session_destroy();
            header('Location: ' . admin_url(), true, 303);
            exit;

        case 'save':
            $key = (string) ($_POST['section'] ?? '');
            if (!isset($schema[$key])) {
                redirect();
            }
            $content = load_content();
            $input   = $_POST['c'] ?? [];
            $section = $content[$key] ?? [];
            foreach ($schema[$key]['fields'] as $field => $def) {
                $section[$field] = clean_value($def, $input[$field] ?? ($def['type'] === 'list' ? [] : ''));
            }
            $content[$key] = $section;
            save_content($content)
                ? flash('Gespeichert! Die Änderungen sind jetzt online.')
                : flash('Speichern fehlgeschlagen – bitte prüfen, ob der Ordner «data» beschreibbar ist.', 'err');
            redirect(['s' => $key]);

        case 'upload':
            $kind = (string) ($_POST['kind'] ?? '');
            $f    = $_FILES['file'] ?? null;
            if (!isset(UPLOAD_TYPES[$kind]) || !$f || is_array($f['name'])) {
                json_out(['ok' => false, 'message' => 'Ungültige Datei.'], 400);
            }
            if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
                json_out(['ok' => false, 'message' => 'Die Datei ist zu gross für den Server (max. ' . ini_get('upload_max_filesize') . ').'], 413);
            }
            if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                json_out(['ok' => false, 'message' => 'Hochladen fehlgeschlagen.'], 400);
            }
            $rule = UPLOAD_TYPES[$kind];
            $ext  = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
            if (!in_array($ext, $rule['ext'], true) || !in_array($mime, $rule['mime'], true)) {
                json_out(['ok' => false, 'message' => 'Dieser Dateityp ist nicht erlaubt. Erlaubt: ' . implode(', ', $rule['ext'])], 415);
            }
            if ($f['size'] > $rule['max'] * 1024 * 1024) {
                json_out(['ok' => false, 'message' => 'Die Datei ist zu gross (max. ' . $rule['max'] . ' MB).'], 413);
            }
            if ($kind === 'image' && @getimagesize($f['tmp_name']) === false) {
                json_out(['ok' => false, 'message' => 'Das Bild konnte nicht gelesen werden.'], 415);
            }
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            if (in_array($ext, ['mov', 'm4v'], true)) {
                $ext = 'mp4';
            }
            $name = slugify(pathinfo((string) $f['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $name)) {
                json_out(['ok' => false, 'message' => 'Datei konnte nicht gespeichert werden.'], 500);
            }
            @chmod(UPLOAD_DIR . '/' . $name, 0644);
            if ($kind === 'image') {
                optimize_image(UPLOAD_DIR . '/' . $name, $ext);
            }
            json_out(['ok' => true, 'path' => 'uploads/' . $name, 'url' => asset('uploads/' . $name)]);

        case 'password':
            $old = (string) ($_POST['old'] ?? '');
            $new = (string) ($_POST['new'] ?? '');
            if (!password_verify($old, $config['password_hash'])) {
                flash('Das aktuelle Passwort stimmt nicht.', 'err');
            } elseif (mb_strlen($new) < 10 || $new !== ($_POST['new2'] ?? '')) {
                flash('Das neue Passwort muss mindestens 10 Zeichen haben und zweimal gleich sein.', 'err');
            } else {
                $config['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
                save_config($config);
                flash('Passwort geändert.');
            }
            redirect(['s' => '_konto']);

        case 'restore':
            $file = basename((string) ($_POST['file'] ?? ''));
            $path = DATA_DIR . '/backups/' . $file;
            if (preg_match('/^content-[\da-f_-]+\.json$/', $file) && is_file($path) && is_array(json_decode((string) file_get_contents($path), true))) {
                save_content(json_decode((string) file_get_contents($path), true));
                flash('Die Sicherung vom ' . date('d.m.Y H:i', filemtime($path)) . ' wurde wiederhergestellt.');
            } else {
                flash('Sicherung nicht gefunden.', 'err');
            }
            redirect(['s' => '_konto']);
    }
}

// ---------------------------------------------------------------------------
// Formularfelder
// ---------------------------------------------------------------------------

function preview(string $type, string $value): string
{
    if ($value === '') {
        return '<span class="media__empty">Noch keine Datei</span>';
    }
    $src = e(asset($value));
    return match ($type) {
        'image' => '<img src="' . $src . '" alt="">',
        'video' => '<video src="' . $src . '" muted playsinline preload="metadata"></video>',
        default => '<a href="' . $src . '" target="_blank" rel="noopener" class="media__file">📄 ' . e(basename($value)) . '</a>',
    };
}

function render_field(string $name, array $def, mixed $value, string $key): void
{
    $type  = $def['type'];
    $id    = 'f_' . substr(md5($name), 0, 10);
    $label = e($def['label']);
    $help  = !empty($def['help']) ? '<small class="help">' . e($def['help']) . '</small>' : '';

    if ($type === 'list') {
        $items = is_array($value) ? $value : [];
        echo '<div class="list" data-list data-name="' . e($name) . '">';
        echo '<div class="list__head"><h3>' . $label . ' <span class="count" data-count>' . count($items) . '</span></h3>' . $help . '</div>';
        echo '<div class="list__items" data-items>';
        foreach (array_values($items) as $i => $item) {
            render_item($name . '[' . $i . ']', $def, (array) $item, $key);
        }
        echo '</div><template data-template>';
        render_item($name . '[__I__]', $def, [], $key);
        echo '</template>';
        echo '<button type="button" class="btn-add" data-add>+ ' . e($def['item'] ?? 'Eintrag') . ' hinzufügen</button>';
        echo '</div>';
        return;
    }

    $v = is_string($value) ? $value : '';
    echo '<div class="field field--' . e($type) . '">';
    echo '<label for="' . $id . '">' . $label . '</label>';
    switch ($type) {
        case 'textarea':
            $rows = $def['rows'] ?? max(3, min(10, (int) ceil(mb_strlen($v) / 70) + 1));
            echo '<textarea id="' . $id . '" name="' . e($name) . '" rows="' . (int) $rows . '">' . e($v) . '</textarea>';
            break;
        case 'image':
        case 'video':
        case 'file':
            $accept = ['image' => 'image/jpeg,image/png,image/webp,image/gif', 'video' => 'video/mp4,video/quicktime,video/webm', 'file' => 'application/pdf'][$type];
            echo '<div class="media" data-media data-kind="' . $type . '">';
            echo '<div class="media__preview" data-preview>' . preview($type, $v) . '</div>';
            echo '<div class="media__actions">';
            echo '<label class="btn-small btn-upload">' . ($type === 'image' ? 'Bild' : ($type === 'video' ? 'Video' : 'PDF')) . ' hochladen<input type="file" accept="' . $accept . '" data-file hidden></label>';
            echo '<button type="button" class="btn-small btn-ghost" data-clear' . ($v === '' ? ' hidden' : '') . '>Entfernen</button>';
            echo '<span class="media__status" data-status></span>';
            echo '</div>';
            echo '<input type="hidden" id="' . $id . '" name="' . e($name) . '" value="' . e($v) . '" data-value>';
            echo '</div>';
            break;
        default:
            $html = ['url' => 'url', 'email' => 'email', 'number' => 'text'][$type] ?? 'text';
            $mode = $type === 'number' ? ' inputmode="decimal"' : '';
            echo '<input id="' . $id . '" type="' . $html . '"' . $mode . ' name="' . e($name) . '" value="' . e($v) . '">';
    }
    echo $help . '</div>';
}

function render_item(string $name, array $def, array $item, string $key): void
{
    $first = array_key_first($def['fields']);
    $title = trim((string) ($item[$first] ?? '')) ?: ($def['item'] ?? 'Eintrag');
    $thumb = '';
    foreach ($def['fields'] as $k => $sub) {
        if ($sub['type'] === 'image' && !empty($item[$k])) {
            $thumb = '<img src="' . e(asset($item[$k])) . '" alt="">';
            break;
        }
    }
    echo '<details class="item" data-item' . (empty($item) ? ' open' : '') . '>';
    echo '<summary><span class="item__thumb">' . $thumb . '</span><span class="item__title" data-title>' . e($title) . '</span>';
    echo '<span class="item__tools"><button type="button" data-up title="Nach oben" aria-label="Nach oben">↑</button><button type="button" data-down title="Nach unten" aria-label="Nach unten">↓</button><button type="button" data-remove class="danger" title="Entfernen" aria-label="Entfernen">✕</button></span></summary>';
    echo '<div class="item__body">';
    foreach ($def['fields'] as $k => $sub) {
        render_field($name . '[' . $k . ']', $sub, $item[$k] ?? '', $key);
    }
    echo '</div></details>';
}

// ---------------------------------------------------------------------------
// Ausgabe
// ---------------------------------------------------------------------------

$flash   = flash();
$current = (string) ($_GET['s'] ?? 'start');
if ($current !== '_konto' && !isset($schema[$current])) {
    $current = 'start';
}
$content = load_content();
$previewLinks = ['allgemein' => 'kontakt/', 'start' => '', 'ueber' => 'ueber-uns/', 'team' => 'team/', 'angebot' => 'angebot/', 'paedagogik' => 'paedagogik/', 'tarife' => 'tarife/', 'jobs' => 'jobs/', 'kontakt' => 'kontakt/', 'rechtliches' => 'impressum/'];
?>
<!doctype html>
<html lang="de-CH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin – Kita Farbewält</title>
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('admin/admin.css')) ?>">
</head>
<body class="view-<?= e($view) ?>">

<?php if ($view === 'login' || $view === 'setup'): ?>
    <main class="login">
        <form method="post" class="login__card" action="<?= e(admin_url()) ?>">
            <img src="<?= e(asset('assets/img/logo.png')) ?>" alt="Kita Farbewält" class="login__logo">
            <?php if ($view === 'setup'): ?>
                <h1>Admin einrichten</h1>
                <p>Legt ein Passwort für den Admin-Bereich fest (mind. 10 Zeichen).</p>
            <?php else: ?>
                <h1>Willkommen zurück</h1>
            <?php endif; ?>
            <?php if ($flash): ?><p class="flash flash--<?= e($flash[1]) ?>"><?= e($flash[0]) ?></p><?php endif; ?>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $view === 'setup' ? 'setup' : 'login' ?>">
            <?php if ($view === 'setup' && !empty($config['setup_code'])): ?>
                <label>Einrichtungs-Code<input type="text" name="code" required autocomplete="off"></label>
            <?php endif; ?>
            <label>Passwort<input type="password" name="password" required autofocus autocomplete="<?= $view === 'setup' ? 'new-password' : 'current-password' ?>"></label>
            <?php if ($view === 'setup'): ?>
                <label>Passwort wiederholen<input type="password" name="password2" required autocomplete="new-password"></label>
            <?php endif; ?>
            <button class="btn" type="submit"><?= $view === 'setup' ? 'Speichern & loslegen' : 'Anmelden' ?></button>
            <a class="login__back" href="<?= e(url()) ?>">← zur Webseite</a>
        </form>
    </main>
<?php else: ?>
    <div class="app">
        <aside class="sidebar" data-sidebar>
            <a class="sidebar__brand" href="<?= e(admin_url()) ?>"><img src="<?= e(asset('assets/img/logo.png')) ?>" alt="Kita Farbewält"></a>
            <nav>
                <?php foreach ($schema as $k => $sec): ?>
                    <a href="<?= e(admin_url(['s' => $k])) ?>"<?= $k === $current ? ' aria-current="page"' : '' ?>><span class="ico"><?= e($sec['icon']) ?></span><?= e($sec['label']) ?></a>
                <?php endforeach; ?>
                <a href="<?= e(admin_url(['s' => '_konto'])) ?>"<?= $current === '_konto' ? ' aria-current="page"' : '' ?>><span class="ico">⚙</span>Passwort & Sicherungen</a>
            </nav>
            <div class="sidebar__foot">
                <a href="<?= e(url()) ?>" target="_blank" rel="noopener">Webseite ansehen ↗</a>
                <form method="post" action="<?= e(admin_url()) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <button type="submit" name="action" value="logout" class="linklike">Abmelden</button>
                </form>
            </div>
        </aside>

        <main class="main">
            <button class="sidebar-toggle" type="button" data-sidebar-toggle>☰ Menü</button>
            <?php if ($flash): ?><p class="flash flash--<?= e($flash[1]) ?>" data-flash><?= e($flash[0]) ?></p><?php endif; ?>

            <?php if ($current === '_konto'): ?>
                <header class="main__head"><h1>Passwort & Sicherungen</h1></header>
                <section class="panel">
                    <h2>Passwort ändern</h2>
                    <form method="post" action="<?= e(admin_url()) ?>" class="stack">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="password">
                        <div class="field"><label>Aktuelles Passwort</label><input type="password" name="old" required autocomplete="current-password"></div>
                        <div class="field"><label>Neues Passwort (mind. 10 Zeichen)</label><input type="password" name="new" required autocomplete="new-password"></div>
                        <div class="field"><label>Neues Passwort wiederholen</label><input type="password" name="new2" required autocomplete="new-password"></div>
                        <button class="btn" type="submit">Passwort speichern</button>
                    </form>
                </section>
                <section class="panel">
                    <h2>Sicherungen</h2>
                    <p class="help">Vor jedem Speichern wird automatisch eine Sicherung angelegt. Falls etwas schiefgeht, könnt ihr hier einen früheren Stand zurückholen.</p>
                    <?php $backups = glob(DATA_DIR . '/backups/content-*.json') ?: []; rsort($backups); ?>
                    <?php if (!$backups): ?><p>Noch keine Sicherungen vorhanden.</p><?php endif; ?>
                    <ul class="backups">
                        <?php foreach (array_slice($backups, 0, 15) as $b): ?>
                            <li>
                                <span><?= e(date('d.m.Y, H:i', filemtime($b))) ?> Uhr</span>
                                <form method="post" action="<?= e(admin_url()) ?>" onsubmit="return confirm('Diesen Stand wiederherstellen? Der aktuelle Stand wird vorher ebenfalls gesichert.')">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="file" value="<?= e(basename($b)) ?>">
                                    <button class="btn-small btn-ghost" type="submit">Wiederherstellen</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php else: $sec = $schema[$current]; ?>
                <form method="post" action="<?= e(admin_url()) ?>" class="editor" data-editor>
                    <header class="main__head">
                        <div>
                            <h1><?= e($sec['label']) ?></h1>
                            <?php if (!empty($sec['hint'])): ?><p class="hint"><?= e($sec['hint']) ?></p><?php endif; ?>
                        </div>
                        <a class="btn-small btn-ghost" href="<?= e(url($previewLinks[$current] ?? '')) ?>" target="_blank" rel="noopener">Seite ansehen ↗</a>
                    </header>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" data-csrf>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="section" value="<?= e($current) ?>">
                    <div class="panel">
                        <?php foreach ($sec['fields'] as $field => $def): ?>
                            <?php render_field('c[' . $field . ']', $def, $content[$current][$field] ?? '', $current); ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="savebar">
                        <span class="savebar__state" data-dirty-note>Alle Änderungen gespeichert</span>
                        <button class="btn" type="submit">Speichern</button>
                    </div>
                </form>
            <?php endif; ?>
        </main>
    </div>
    <script>window.ADMIN = { uploadUrl: <?= json_encode(admin_url(), JSON_UNESCAPED_SLASHES) ?>, maxUpload: <?= json_encode(ini_get('upload_max_filesize')) ?> };</script>
    <script src="<?= e(asset('admin/admin.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
