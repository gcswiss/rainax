<?php
/** Admin: Login, Ersteinrichtung und Kalender-Oberfläche. */
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
start_admin_session();

$error = null;
$needsSetup = !setting('admin_hash');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!check_csrf($_POST['csrf'] ?? null)) {
        $error = 'Sitzung abgelaufen. Bitte nochmals versuchen.';
    } elseif ($action === 'logout') {
        admin_logout();
        header('Location: ./');
        exit;
    } elseif ($action === 'setup' && $needsSetup) {
        $key = (string)cfg('setup_key', '');
        if ($key === '' || $key === 'BITTE-AENDERN') {
            $error = 'Bitte zuerst in lib/config.php einen eigenen «setup_key» eintragen.';
        } elseif (rate_limited('login', 10, 900)) {
            $error = 'Zu viele Versuche. Bitte 15 Minuten warten.';
        } elseif (!hash_equals($key, (string)($_POST['setup_key'] ?? ''))) {
            $error = 'Der Einrichtungsschlüssel stimmt nicht.';
        } elseif (($_POST['password'] ?? '') !== ($_POST['password2'] ?? '')) {
            $error = 'Die Passwörter stimmen nicht überein.';
        } else {
            try {
                set_admin_password((string)$_POST['password']);
                admin_login((string)$_POST['password']);
                header('Location: ./');
                exit;
            } catch (BookingError $e) { $error = $e->getMessage(); }
        }
    } elseif ($action === 'login') {
        if (rate_limited('login', 10, 900)) {
            $error = 'Zu viele Versuche. Bitte 15 Minuten warten.';
        } elseif (admin_login((string)($_POST['password'] ?? ''))) {
            header('Location: ./');
            exit;
        } else {
            usleep(400000);
            $error = 'Falsches Passwort.';
        }
    }
}
?><!doctype html>
<html lang="de-CH">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Kalender – Jordan Admin</title>
<link rel="icon" href="/assets/img/favicon-32.png" sizes="32x32">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<meta name="theme-color" content="#0c0d21">
<link rel="stylesheet" href="/assets/css/admin.css?v=2">
</head>
<body>
<?php if (!is_admin()): ?>
  <main class="login">
    <form method="post" class="login-card">
      <img src="/assets/img/logo.webp" alt="Jordan" width="96" height="94">
      <h1><?= $needsSetup ? 'Admin einrichten' : 'Admin-Login' ?></h1>
      <?php if ($error): ?><p class="err"><?= h($error) ?></p><?php endif; ?>
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
      <?php if ($needsSetup): ?>
        <input type="hidden" name="action" value="setup">
        <p class="hint">Lege einmalig das Admin-Passwort fest. Den Einrichtungsschlüssel findest du in <code>lib/config.php</code>.</p>
        <label>Einrichtungsschlüssel<input name="setup_key" type="password" required autocomplete="off"></label>
        <label>Neues Passwort (mind. 10 Zeichen)<input name="password" type="password" minlength="10" required autocomplete="new-password"></label>
        <label>Passwort wiederholen<input name="password2" type="password" minlength="10" required autocomplete="new-password"></label>
        <button class="b primary" type="submit">Speichern &amp; anmelden</button>
      <?php else: ?>
        <input type="hidden" name="action" value="login">
        <label>Passwort<input name="password" type="password" required autofocus autocomplete="current-password"></label>
        <button class="b primary" type="submit">Anmelden</button>
      <?php endif; ?>
      <a class="back" href="/">← Zur Webseite</a>
    </form>
  </main>
<?php else: ?>
  <div id="app" data-csrf="<?= h($_SESSION['csrf']) ?>">
    <header class="top">
      <a class="logo" href="/" title="Zur Webseite"><img src="/assets/img/logo.webp" alt="Jordan" width="36" height="35"></a>
      <nav class="tabs" role="tablist">
        <button role="tab" data-view="cal" aria-selected="true">Kalender</button>
        <button role="tab" data-view="list">Termine</button>
        <button role="tab" data-view="settings">Einstellungen</button>
      </nav>
      <form method="post" class="logout">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="logout">
        <button class="b ghost sm" type="submit">Abmelden</button>
      </form>
    </header>

    <section class="view" data-view="cal">
      <div class="toolbar">
        <div class="grp">
          <button class="b ghost sm" id="today">Heute</button>
          <button class="b ghost sm icon" id="prev" aria-label="Zurück">‹</button>
          <button class="b ghost sm icon" id="next" aria-label="Weiter">›</button>
          <input type="date" id="date-pick" class="sm" aria-label="Datum wählen">
          <h2 id="range-title"></h2>
        </div>
        <div class="grp">
          <select id="branch-sel" class="sm" aria-label="Filiale"></select>
          <div class="seg" role="group" aria-label="Ansicht">
            <button data-mode="day" aria-pressed="true">Tag</button>
            <button data-mode="week" aria-pressed="false">Woche</button>
          </div>
          <select id="staff-sel" class="sm" aria-label="Mitarbeiter (Wochenansicht)" hidden></select>
          <button class="b primary sm" id="new-booking">+ Termin</button>
          <button class="b ghost sm" id="new-block">+ Abwesenheit</button>
        </div>
      </div>
      <div class="cal" id="cal"></div>
      <p class="legend"><span class="lg online"></span>Online gebucht <span class="lg admin"></span>Im Salon eingetragen <span class="lg block"></span>Abwesend / gesperrt <span class="lg noshow"></span>Nicht erschienen</p>
    </section>

    <section class="view" data-view="list" hidden>
      <div class="toolbar">
        <div class="grp"><h2>Termine</h2></div>
        <div class="grp"><input type="search" id="search" class="sm" placeholder="Name, Telefon oder E-Mail suchen …" style="min-width:260px"></div>
      </div>
      <div id="list"></div>
    </section>

    <section class="view" data-view="settings" hidden>
      <div id="settings"></div>
    </section>
  </div>

  <dialog id="dlg"><form method="dialog" id="dlg-form"></form></dialog>
  <div id="toast" role="status" aria-live="polite"></div>
  <script src="/assets/js/admin.js?v=2" defer></script>
<?php endif; ?>
</body>
</html>
