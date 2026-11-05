<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('jc_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
    // Nach 12 Stunden Inaktivität abmelden
    if (!empty($_SESSION['admin']) && (time() - ($_SESSION['seen'] ?? 0)) > 12 * 3600) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['seen'] = time();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function is_admin(): bool { start_admin_session(); return !empty($_SESSION['admin']); }

function admin_login(string $password): bool
{
    $hash = setting('admin_hash');
    if (!$hash || !password_verify($password, $hash)) return false;
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return true;
}

function admin_logout(): void
{
    start_admin_session();
    $_SESSION = [];
    session_destroy();
}

function set_admin_password(string $pw): void
{
    if (mb_strlen($pw) < 10) throw new BookingError('Das Passwort muss mindestens 10 Zeichen lang sein.');
    set_setting('admin_hash', password_hash($pw, PASSWORD_DEFAULT));
}

function check_csrf(?string $token): bool
{
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}
