<?php
// Star 1 Coiffeur — gemeinsame Funktionen für das Buchungsportal

declare(strict_types=1);

$CONFIG = require __DIR__ . '/config.php';
date_default_timezone_set($CONFIG['timezone']);

const VARIANTS = ['kurz', 'mittel', 'lang'];
const WEEKDAYS = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];

function cfg(string $key)
{
    global $CONFIG;
    return $CONFIG[$key] ?? null;
}

// ---------------------------------------------------------------- Antworten

function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $message, int $status = 400): void
{
    json_out(['ok' => false, 'error' => $message], $status);
}

function body(): array
{
    static $data = null;
    if ($data === null) {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST ?: [];
        }
    }
    return $data;
}

function str_in(array $src, string $key, int $max = 200): string
{
    $v = $src[$key] ?? '';
    if (!is_scalar($v)) {
        return '';
    }
    $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)$v) ?? '');
    return mb_substr($v, 0, $max);
}

function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('!Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
}

function valid_time(string $t): bool
{
    return (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ---------------------------------------------------------------- Datenbank

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $path = cfg('db_path');
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $fresh = !file_exists($path);
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA journal_mode = WAL');
    migrate($pdo, $fresh);
    return $pdo;
}

function migrate(PDO $pdo, bool $fresh): void
{
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        key   TEXT PRIMARY KEY,
        value TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS staff (
        id     INTEGER PRIMARY KEY AUTOINCREMENT,
        name   TEXT NOT NULL,
        role   TEXT NOT NULL DEFAULT '',
        color  TEXT NOT NULL DEFAULT '#E3B341',
        days   TEXT NOT NULL DEFAULT '1,2,3,4,5,6',
        active INTEGER NOT NULL DEFAULT 1,
        sort   INTEGER NOT NULL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS services (
        id       INTEGER PRIMARY KEY AUTOINCREMENT,
        category TEXT NOT NULL,
        name     TEXT NOT NULL,
        price1   REAL,
        price2   REAL,
        price3   REAL,
        active   INTEGER NOT NULL DEFAULT 1,
        sort     INTEGER NOT NULL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS blocks (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        staff_id  INTEGER REFERENCES staff(id) ON DELETE CASCADE,
        date_from TEXT NOT NULL,
        date_to   TEXT NOT NULL,
        time_from TEXT,
        time_to   TEXT,
        reason    TEXT NOT NULL DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS bookings (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        date         TEXT NOT NULL,
        time         TEXT NOT NULL,
        staff_id     INTEGER NOT NULL REFERENCES staff(id),
        service_id   INTEGER REFERENCES services(id) ON DELETE SET NULL,
        service_name TEXT NOT NULL DEFAULT '',
        variant      TEXT NOT NULL DEFAULT '',
        price        REAL,
        name         TEXT NOT NULL,
        phone        TEXT NOT NULL DEFAULT '',
        email        TEXT NOT NULL DEFAULT '',
        note         TEXT NOT NULL DEFAULT '',
        status       TEXT NOT NULL DEFAULT 'confirmed',
        source       TEXT NOT NULL DEFAULT 'online',
        ip           TEXT NOT NULL DEFAULT '',
        created_at   TEXT NOT NULL
    );
    CREATE UNIQUE INDEX IF NOT EXISTS bookings_slot
        ON bookings(staff_id, date, time) WHERE status = 'confirmed';
    CREATE INDEX IF NOT EXISTS bookings_date ON bookings(date);
    CREATE TABLE IF NOT EXISTS admins (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        username    TEXT NOT NULL UNIQUE,
        pass_hash   TEXT NOT NULL,
        must_change INTEGER NOT NULL DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS attempts (
        kind TEXT NOT NULL,
        ip   TEXT NOT NULL,
        ts   INTEGER NOT NULL
    );
    ");

    if (!$fresh && (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
        return;
    }
    seed($pdo);
}

function seed(PDO $pdo): void
{
    $pdo->beginTransaction();

    $settings = [
        // Öffnungszeiten je Wochentag (0 = Sonntag)
        'hours' => json_encode([
            '0' => null,
            '1' => ['09:00', '19:00'],
            '2' => ['09:00', '19:00'],
            '3' => ['09:00', '19:00'],
            '4' => ['09:00', '19:00'],
            '5' => ['09:00', '19:00'],
            '6' => ['08:00', '17:00'],
        ]),
        'slot_minutes' => '30',
        'horizon_days' => '60',
        'lead_minutes' => '60',
    ];
    $st = $pdo->prepare('INSERT OR IGNORE INTO settings(key, value) VALUES (?, ?)');
    foreach ($settings as $k => $v) {
        $st->execute([$k, $v]);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM staff')->fetchColumn() === 0) {
        $st = $pdo->prepare('INSERT INTO staff(name, role, color, sort) VALUES (?, ?, ?, ?)');
        $st->execute(['Ahmed', 'Inhaber · Coiffeur', '#E3B341', 1]);
        $st->execute(['Mitarbeiter 2', 'Coiffeur', '#5B9BD5', 2]);
        $st->execute(['Mitarbeiterin 3', 'Coiffeuse', '#C77DBA', 3]);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 0) {
        $list = [
            ['Cut & Go', 'Damen', 38],
            ['Cut & Go', 'Herren', 32],

            ['Damen', 'Waschen, Schneiden, selber Föhnen', 44],
            ['Damen', 'Waschen, Föhnen', 38, 43, 53],
            ['Damen', 'Waschen, Glätten', 43, 48, 58],
            ['Damen', 'Nur Färben', 63, 73, 78],
            ['Damen', 'Nur Tönen', 38, 43, 58],
            ['Damen', 'Foliensträhnen Oberkopf', 63, 78, 88],
            ['Damen', 'Foliensträhnen ganzer Kopf', 88, 98, 135],
            ['Damen', 'Balayage / Ombré', null, 150, 180],
            ['Damen', 'Bleichen', 60, 75, 80],
            ['Damen', 'Ansatz färben', 48],
            ['Damen', 'Augenbrauen mit Faden zupfen', 20],
            ['Damen', 'Augenbrauen färben', 15],
            ['Damen', 'Wimpern färben', 15],

            ['Herren', 'Waschen, Schneiden, Frisieren', 38],
            ['Herren', 'Maschinen-Haarschnitt', 28],
            ['Herren', 'Nassrasur', 25],
            ['Herren', 'Bart schneiden', 20],
            ['Herren', 'Bart färben', 20],
            ['Herren', 'Nur Färben', 20],
            ['Herren', 'Nur Tönen', 38],
            ['Herren', 'Bleichen', 48],
            ['Herren', 'Augenbrauen mit Faden zupfen', 15],

            ['Kinder', 'Jungs bis 10 Jahre', 20],
            ['Kinder', 'Jungs bis 16 Jahre', 25],
            ['Kinder', 'Mädchen bis 6 Jahre', 20],
            ['Kinder', 'Mädchen bis 16 Jahre', 35],
        ];
        $st = $pdo->prepare('INSERT INTO services(category, name, price1, price2, price3, sort) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($list as $i => $s) {
            $st->execute([$s[0], $s[1], $s[2], $s[3] ?? null, $s[4] ?? null, $i + 1]);
        }
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
        $st = $pdo->prepare('INSERT INTO admins(username, pass_hash, must_change) VALUES (?, ?, 1)');
        $st->execute([cfg('admin_user'), password_hash(cfg('admin_initial_pass'), PASSWORD_DEFAULT)]);
    }

    $pdo->commit();
}

function setting(string $key, $default = null)
{
    $st = db()->prepare('SELECT value FROM settings WHERE key = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function set_setting(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings(key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
        ->execute([$key, $value]);
}

function hours(): array
{
    $h = json_decode((string)setting('hours', '{}'), true);
    return is_array($h) ? $h : [];
}

// ---------------------------------------------------------------- Missbrauchsschutz

function too_many(string $kind, int $max, int $seconds): bool
{
    $pdo = db();
    $pdo->prepare('DELETE FROM attempts WHERE ts < ?')->execute([time() - 86400]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM attempts WHERE kind = ? AND ip = ? AND ts > ?');
    $st->execute([$kind, client_ip(), time() - $seconds]);
    return (int)$st->fetchColumn() >= $max;
}

function note_attempt(string $kind): void
{
    db()->prepare('INSERT INTO attempts(kind, ip, ts) VALUES (?, ?, ?)')->execute([$kind, client_ip(), time()]);
}

// ---------------------------------------------------------------- Verfügbarkeit

function staff_list(bool $onlyActive = true): array
{
    $sql = 'SELECT * FROM staff' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort, id';
    $rows = db()->query($sql)->fetchAll();
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['active'] = (int)$r['active'];
        $r['sort'] = (int)$r['sort'];
        $r['days'] = array_values(array_filter(array_map('intval', explode(',', $r['days'])), fn($d) => $d >= 0 && $d <= 6));
    }
    return $rows;
}

function service_list(bool $onlyActive = true): array
{
    $sql = 'SELECT * FROM services' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort, id';
    $rows = db()->query($sql)->fetchAll();
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['active'] = (int)$r['active'];
        $r['sort'] = (int)$r['sort'];
        foreach (['price1', 'price2', 'price3'] as $p) {
            $r[$p] = $r[$p] === null ? null : (float)$r[$p];
        }
    }
    return $rows;
}

function time_to_min(string $t): int
{
    [$h, $m] = array_map('intval', explode(':', $t));
    return $h * 60 + $m;
}

function min_to_time(int $m): string
{
    return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
}

/** Alle Slot-Zeiten eines Tages laut Öffnungszeiten (ohne Rücksicht auf Buchungen). */
function day_slots(string $date): array
{
    $dow = (int)(new DateTime($date))->format('w');
    $h = hours()[(string)$dow] ?? null;
    if (!$h || !valid_time($h[0] ?? '') || !valid_time($h[1] ?? '')) {
        return [];
    }
    $step = max(5, (int)setting('slot_minutes', '30'));
    $out = [];
    for ($m = time_to_min($h[0]); $m + $step <= time_to_min($h[1]); $m += $step) {
        $out[] = min_to_time($m);
    }
    return $out;
}

function blocks_for(string $date): array
{
    $st = db()->prepare('SELECT * FROM blocks WHERE date_from <= ? AND date_to >= ?');
    $st->execute([$date, $date]);
    return $st->fetchAll();
}

function is_blocked(array $blocks, int $staffId, string $time, int $step): bool
{
    $s = time_to_min($time);
    $e = $s + $step;
    foreach ($blocks as $b) {
        if ($b['staff_id'] !== null && (int)$b['staff_id'] !== $staffId) {
            continue;
        }
        if (!$b['time_from'] || !$b['time_to']) {
            return true; // ganzer Tag
        }
        if ($s < time_to_min($b['time_to']) && $e > time_to_min($b['time_from'])) {
            return true;
        }
    }
    return false;
}

/**
 * Freie Mitarbeitende je Slot an einem Datum.
 * $public = true berücksichtigt Vorlaufzeit und Buchungshorizont.
 * Ergebnis: ['09:00' => [staffId, ...], ...] (nur Slots mit mindestens einer Person)
 */
function availability(string $date, bool $public = true): array
{
    $today = new DateTime('today');
    $d = new DateTime($date);
    if ($public) {
        $horizon = (clone $today)->modify('+' . (int)setting('horizon_days', '60') . ' days');
        if ($d < $today || $d > $horizon) {
            return [];
        }
    }
    $slots = day_slots($date);
    if (!$slots) {
        return [];
    }
    $dow = (int)$d->format('w');
    $step = max(5, (int)setting('slot_minutes', '30'));
    $staff = array_filter(staff_list(), fn($s) => in_array($dow, $s['days'], true));
    $blocks = blocks_for($date);

    $st = db()->prepare("SELECT staff_id, time FROM bookings WHERE date = ? AND status = 'confirmed'");
    $st->execute([$date]);
    $taken = [];
    foreach ($st->fetchAll() as $b) {
        $taken[$b['staff_id'] . '@' . $b['time']] = true;
    }

    $earliest = time() + (int)setting('lead_minutes', '60') * 60;
    $out = [];
    foreach ($slots as $t) {
        if ($public && strtotime("$date $t") < $earliest) {
            continue;
        }
        $free = [];
        foreach ($staff as $s) {
            if (isset($taken[$s['id'] . '@' . $t]) || is_blocked($blocks, $s['id'], $t, $step)) {
                continue;
            }
            $free[] = $s['id'];
        }
        if ($free) {
            $out[$t] = $free;
        }
    }
    return $out;
}

function price_for(array $service, string $variant): ?float
{
    $idx = array_search($variant, VARIANTS, true);
    $prices = [$service['price1'], $service['price2'], $service['price3']];
    $filled = array_filter($prices, fn($p) => $p !== null);
    if (count($filled) <= 1) {
        return $filled ? (float)array_values($filled)[0] : null;
    }
    if ($idx === false || $prices[$idx] === null) {
        return null;
    }
    return (float)$prices[$idx];
}

function has_variants(array $service): bool
{
    return count(array_filter([$service['price1'], $service['price2'], $service['price3']], fn($p) => $p !== null)) > 1;
}

// ---------------------------------------------------------------- E-Mail

function send_mail(string $to, string $subject, string $text, string $replyTo = ''): void
{
    if (!cfg('mail_enabled') || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $headers = [
        'From: ' . cfg('salon_name') . ' <' . cfg('mail_from') . '>',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $text, implode("\r\n", $headers));
}

function format_chf(?float $v): string
{
    if ($v === null) {
        return 'nach Aufwand';
    }
    return 'CHF ' . (floor($v) == $v ? number_format($v, 0, '.', "'") . '.–' : number_format($v, 2, '.', "'"));
}

function format_date_de(string $date): string
{
    $days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
    $d = new DateTime($date);
    return $days[(int)$d->format('w')] . ', ' . $d->format('d.m.Y');
}
