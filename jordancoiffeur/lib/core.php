<?php
/**
 * Jordan Coiffeur & Barbershop – Buchungssystem (Kern)
 * Datenbank (SQLite), Verfügbarkeiten, Buchungen, E-Mails.
 * Zeiten werden als Ortszeit Zürich im Format "YYYY-MM-DD HH:MM" gespeichert.
 */
declare(strict_types=1);

const JC_ROOT = __DIR__ . '/..';

function cfg(string $key, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $file = __DIR__ . '/config.php';
        $cfg = is_file($file) ? require $file : require __DIR__ . '/config.sample.php';
    }
    return $cfg[$key] ?? $default;
}

date_default_timezone_set(cfg('timezone', 'Europe/Zurich'));

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $dir = JC_ROOT . '/data';
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $file = getenv('JC_DB') ?: $dir . '/buchungen.sqlite';
    $fresh = !is_file($file);
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL; PRAGMA busy_timeout = 5000;');
    migrate($pdo, $fresh);
    return $pdo;
}

function migrate(PDO $pdo, bool $fresh): void
{
    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT);
    CREATE TABLE IF NOT EXISTS branches (
        id INTEGER PRIMARY KEY, name TEXT NOT NULL, street TEXT, city TEXT, phone TEXT,
        active INTEGER NOT NULL DEFAULT 1, sort INTEGER NOT NULL DEFAULT 0);
    CREATE TABLE IF NOT EXISTS hours (
        branch_id INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
        weekday INTEGER NOT NULL,           -- 1 = Montag … 7 = Sonntag
        open TEXT NOT NULL, close TEXT NOT NULL,
        PRIMARY KEY (branch_id, weekday));
    CREATE TABLE IF NOT EXISTS staff (
        id INTEGER PRIMARY KEY, name TEXT NOT NULL,
        branch_id INTEGER NOT NULL REFERENCES branches(id),
        color TEXT NOT NULL DEFAULT '#f2a541',
        workdays TEXT NOT NULL DEFAULT '123456', -- Wochentage, an denen gearbeitet wird
        bookable INTEGER NOT NULL DEFAULT 1,     -- online buchbar
        active INTEGER NOT NULL DEFAULT 1, sort INTEGER NOT NULL DEFAULT 0);
    CREATE TABLE IF NOT EXISTS services (
        id INTEGER PRIMARY KEY, name TEXT NOT NULL, description TEXT,
        duration INTEGER NOT NULL,          -- Minuten
        price TEXT NOT NULL,                -- Anzeige, z. B. "ab 32.-"
        active INTEGER NOT NULL DEFAULT 1, sort INTEGER NOT NULL DEFAULT 0);
    CREATE TABLE IF NOT EXISTS blocks (
        id INTEGER PRIMARY KEY,
        branch_id INTEGER REFERENCES branches(id) ON DELETE CASCADE, -- NULL = alle Filialen
        staff_id INTEGER REFERENCES staff(id) ON DELETE CASCADE,     -- NULL = ganze Filiale
        start TEXT NOT NULL, end TEXT NOT NULL, note TEXT);
    CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY,
        branch_id INTEGER NOT NULL REFERENCES branches(id),
        staff_id INTEGER NOT NULL REFERENCES staff(id),
        service_id INTEGER REFERENCES services(id),
        service_name TEXT NOT NULL, start TEXT NOT NULL, end TEXT NOT NULL,
        name TEXT NOT NULL, phone TEXT, email TEXT, note TEXT,
        status TEXT NOT NULL DEFAULT 'confirmed',  -- confirmed | cancelled | noshow
        source TEXT NOT NULL DEFAULT 'online',     -- online | admin
        token TEXT NOT NULL UNIQUE, created_at TEXT NOT NULL, cancelled_at TEXT);
    CREATE INDEX IF NOT EXISTS idx_bookings_staff_start ON bookings(staff_id, start);
    CREATE INDEX IF NOT EXISTS idx_blocks_start ON blocks(start);
    CREATE TABLE IF NOT EXISTS login_attempts (ip TEXT NOT NULL, at INTEGER NOT NULL);
    SQL);

    if (!$fresh && $pdo->query('SELECT COUNT(*) FROM branches')->fetchColumn() > 0) return;

    // Startdaten (von jordancoiffeur.ch übernommen). Im Admin anpassbar.
    $pdo->beginTransaction();
    $pdo->exec("INSERT INTO branches (id,name,street,city,phone,sort) VALUES
        (1,'St. Leonhardstrasse','St. Leonhardstr. 22','9000 St. Gallen','071 220 10 00',1),
        (2,'Langgasse','Langgasse 11','9008 St. Gallen','071 790 00 05',2)");
    $h = $pdo->prepare('INSERT INTO hours VALUES (?,?,?,?)');
    foreach ([1, 2] as $b) {
        for ($d = 1; $d <= 5; $d++) $h->execute([$b, $d, '09:00', '19:00']);
        $h->execute([$b, 6, '09:00', '17:00']);
    }
    $pdo->exec("INSERT INTO staff (name,branch_id,color,sort) VALUES
        ('Barber 1',1,'#f2a541',1),('Barber 2',1,'#5bb3a9',2),('Barber 3',2,'#e0675a',3)");
    $s = $pdo->prepare('INSERT INTO services (name,description,duration,price,sort) VALUES (?,?,?,?,?)');
    $services = [
        ['Haarschnitt', 'Präziser Schnitt und Styling – klassisch oder Skin Fade.', 30, 'ab 32.-'],
        ['Waschen, schneiden & föhnen', 'Haarwäsche, Schnitt und Föhnstyling.', 45, 'ab 38.-'],
        ['Bart', 'Bart trimmen und konturieren, Rasur mit Beratung und Hautanalyse.', 20, 'ab 20.-'],
        ['Augenbrauen', 'Augenbrauen in Form bringen.', 15, 'ab 15.-'],
        ['Kinder bis 12 Jahre', 'Haarschnitt für Kinder bis 12 Jahre.', 30, 'ab 25.-'],
        ['Haare färben', 'Farbe nach Wunsch.', 60, 'ab 35.-'],
        ['Bart färben', 'Bartfarbe auffrischen oder abdecken.', 30, 'ab 25.-'],
        ['Haare stylen', 'Styling für den perfekten Look.', 15, 'ab 15.-'],
        ['Gesichtsreinigung', 'Tiefenreinigung und Pflege fürs Gesicht.', 45, 'ab 58.-'],
    ];
    foreach ($services as $i => $row) $s->execute([...$row, $i + 1]);
    $set = $pdo->prepare('INSERT OR IGNORE INTO settings VALUES (?,?)');
    foreach ([
        'slot_step' => '15',      // Raster der Startzeiten in Minuten
        'lead_minutes' => '60',   // frühestens so viele Minuten im Voraus online buchbar
        'max_days' => '60',       // so viele Tage im Voraus buchbar
        'cancel_hours' => '12',   // bis so viele Stunden vorher online stornierbar
        'notify_salon' => '1',    // E-Mail an den Salon bei neuer Buchung
    ] as $k => $v) $set->execute([$k, $v]);
    $pdo->commit();
}

function setting(string $key, ?string $default = null): ?string
{
    $st = db()->prepare('SELECT value FROM settings WHERE key = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function set_setting(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings (key,value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
        ->execute([$key, $value]);
}

/* ---------- Zeit-Helfer ---------- */

function t2m(string $hhmm): int { [$h, $m] = array_map('intval', explode(':', $hhmm)); return $h * 60 + $m; }
function m2t(int $min): string { return sprintf('%02d:%02d', intdiv($min, 60), $min % 60); }
function valid_date(string $d): bool { $x = DateTime::createFromFormat('!Y-m-d', $d); return $x && $x->format('Y-m-d') === $d; }
function valid_dt(string $d): bool { $x = DateTime::createFromFormat('!Y-m-d H:i', $d); return $x && $x->format('Y-m-d H:i') === $d; }
function add_minutes(string $dt, int $min): string { return (new DateTime($dt))->modify("+{$min} minutes")->format('Y-m-d H:i'); }
function now_str(): string { return date('Y-m-d H:i'); }

/* ---------- Stammdaten ---------- */

function public_catalog(): array
{
    $pdo = db();
    $branches = $pdo->query('SELECT id,name,street,city,phone FROM branches WHERE active=1 ORDER BY sort,id')->fetchAll();
    $hours = $pdo->query('SELECT * FROM hours ORDER BY weekday')->fetchAll();
    foreach ($branches as &$b) {
        $b['hours'] = array_values(array_map(fn($h) => ['weekday' => (int)$h['weekday'], 'open' => $h['open'], 'close' => $h['close']],
            array_filter($hours, fn($h) => (int)$h['branch_id'] === (int)$b['id'])));
    }
    unset($b);
    return [
        'branches' => $branches,
        'staff' => $pdo->query('SELECT id,name,branch_id FROM staff WHERE active=1 AND bookable=1 ORDER BY sort,id')->fetchAll(),
        'services' => $pdo->query('SELECT id,name,description,duration,price FROM services WHERE active=1 ORDER BY sort,id')->fetchAll(),
        'max_days' => (int)setting('max_days', '60'),
    ];
}

/* ---------- Verfügbarkeit ---------- */

/** Belegte Intervalle (Minuten ab Tagesbeginn) je Mitarbeiter für einen Tag. */
function busy_map(string $date, int $branchId, ?int $ignoreBookingId = null): array
{
    $pdo = db();
    $from = "$date 00:00";
    $to = date('Y-m-d', strtotime("$date +1 day")) . ' 00:00';
    $busy = [];
    $clip = function (string $s, string $e) use ($date): array {
        $sm = substr($s, 0, 10) < $date ? 0 : t2m(substr($s, 11, 5));
        $em = substr($e, 0, 10) > $date ? 24 * 60 : t2m(substr($e, 11, 5));
        return [$sm, $em];
    };
    $st = $pdo->prepare("SELECT id, staff_id, start, end FROM bookings WHERE status='confirmed' AND start < ? AND end > ?");
    $st->execute([$to, $from]);
    foreach ($st as $r) {
        if ($ignoreBookingId && (int)$r['id'] === $ignoreBookingId) continue;
        $busy[(int)$r['staff_id']][] = $clip($r['start'], $r['end']);
    }
    $st = $pdo->prepare('SELECT staff_id, branch_id, start, end FROM blocks WHERE start < ? AND end > ?');
    $st->execute([$to, $from]);
    foreach ($st as $r) {
        if ($r['staff_id'] !== null) {
            $busy[(int)$r['staff_id']][] = $clip($r['start'], $r['end']);
        } elseif ($r['branch_id'] === null || (int)$r['branch_id'] === $branchId) {
            $busy['all'][] = $clip($r['start'], $r['end']);
        }
    }
    return $busy;
}

function staff_for_branch(int $branchId, bool $onlineOnly = true): array
{
    $sql = 'SELECT id,name,workdays FROM staff WHERE active=1 AND branch_id=?' . ($onlineOnly ? ' AND bookable=1' : '') . ' ORDER BY sort,id';
    $st = db()->prepare($sql);
    $st->execute([$branchId]);
    return $st->fetchAll();
}

function opening(int $branchId, string $date): ?array
{
    $st = db()->prepare('SELECT open, close FROM hours WHERE branch_id=? AND weekday=?');
    $st->execute([$branchId, (int)date('N', strtotime($date))]);
    $h = $st->fetch();
    return $h ? [t2m($h['open']), t2m($h['close'])] : null;
}

function overlaps(array $intervals, int $s, int $e): bool
{
    foreach ($intervals as [$a, $b]) if ($s < $b && $e > $a) return true;
    return false;
}

/**
 * Freie Startzeiten für einen Tag. Liefert ['09:00' => [staffId, ...], ...].
 * $staffId = null → beliebiger Mitarbeiter der Filiale.
 */
function free_slots(int $branchId, int $duration, string $date, ?int $staffId, bool $forAdmin = false): array
{
    $open = opening($branchId, $date);
    if (!$open) return [];
    [$o, $c] = $open;
    $step = max(5, (int)setting('slot_step', '15'));
    $earliest = $forAdmin ? null : (new DateTime())->modify('+' . (int)setting('lead_minutes', '60') . ' minutes')->format('Y-m-d H:i');
    if (!$forAdmin) {
        $last = (new DateTime('today'))->modify('+' . (int)setting('max_days', '60') . ' days')->format('Y-m-d');
        if ($date > $last || $date < date('Y-m-d')) return [];
    }
    $weekday = date('N', strtotime($date));
    $staff = array_filter(staff_for_branch($branchId, !$forAdmin), fn($s) =>
        ($staffId === null || (int)$s['id'] === $staffId) && str_contains($s['workdays'], $weekday));
    if (!$staff) return [];
    $busy = busy_map($date, $branchId);
    $all = $busy['all'] ?? [];
    $slots = [];
    for ($t = $o; $t + $duration <= $c; $t += $step) {
        if ($earliest && "$date " . m2t($t) < $earliest) continue;
        if (overlaps($all, $t, $t + $duration)) continue;
        foreach ($staff as $s) {
            if (!overlaps($busy[(int)$s['id']] ?? [], $t, $t + $duration)) $slots[m2t($t)][] = (int)$s['id'];
        }
    }
    return $slots;
}

/* ---------- Buchungen ---------- */

class BookingError extends RuntimeException {}

function get_service(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM services WHERE id=?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/**
 * Prüft, ob der Mitarbeiter im Zeitraum frei ist (Buchungen + Sperrzeiten).
 */
function staff_is_free(int $staffId, int $branchId, string $start, string $end, ?int $ignoreBookingId = null): bool
{
    $pdo = db();
    $st = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status='confirmed' AND staff_id=? AND start < ? AND end > ?" . ($ignoreBookingId ? ' AND id <> ' . (int)$ignoreBookingId : ''));
    $st->execute([$staffId, $end, $start]);
    if ($st->fetchColumn() > 0) return false;
    $st = $pdo->prepare('SELECT COUNT(*) FROM blocks WHERE start < ? AND end > ? AND (staff_id = ? OR (staff_id IS NULL AND (branch_id IS NULL OR branch_id = ?)))');
    $st->execute([$end, $start, $staffId, $branchId]);
    return $st->fetchColumn() == 0;
}

/**
 * Online-Buchung anlegen. Prüft alles nochmals innerhalb einer Schreibsperre,
 * damit zwei Kunden nie denselben Termin bekommen.
 */
function create_online_booking(array $in): array
{
    $branchId = (int)($in['branch_id'] ?? 0);
    $serviceId = (int)($in['service_id'] ?? 0);
    $staffId = !empty($in['staff_id']) ? (int)$in['staff_id'] : null;
    $date = (string)($in['date'] ?? '');
    $time = (string)($in['time'] ?? '');
    $name = trim((string)($in['name'] ?? ''));
    $phone = trim((string)($in['phone'] ?? ''));
    $email = trim((string)($in['email'] ?? ''));
    $note = trim((string)($in['note'] ?? ''));

    if (!valid_date($date) || !preg_match('/^\d\d:\d\d$/', $time)) throw new BookingError('Ungültiges Datum oder Zeit.');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) throw new BookingError('Bitte gib deinen Namen an.');
    if (!preg_match('/^[+0-9 ()\/.-]{7,25}$/', $phone)) throw new BookingError('Bitte gib eine gültige Telefonnummer an.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new BookingError('Die E-Mail-Adresse ist ungültig.');
    if (mb_strlen($note) > 500) throw new BookingError('Die Bemerkung ist zu lang.');
    $service = get_service($serviceId);
    if (!$service || !$service['active']) throw new BookingError('Diese Dienstleistung gibt es nicht.');

    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $slots = free_slots($branchId, (int)$service['duration'], $date, $staffId);
        $candidates = $slots[$time] ?? [];
        if (!$candidates) throw new BookingError('Dieser Termin ist leider nicht mehr frei. Bitte wähle eine andere Zeit.');
        // Bei «egal wer»: den Mitarbeiter mit den wenigsten Terminen an diesem Tag nehmen
        if ($staffId === null && count($candidates) > 1) {
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status='confirmed' AND staff_id=? AND start LIKE ?");
            usort($candidates, function ($a, $b) use ($cnt, $date) {
                $cnt->execute([$a, "$date%"]); $ca = (int)$cnt->fetchColumn();
                $cnt->execute([$b, "$date%"]); $cb = (int)$cnt->fetchColumn();
                return $ca <=> $cb;
            });
        }
        $chosen = $candidates[0];
        $start = "$date $time";
        $end = add_minutes($start, (int)$service['duration']);
        $token = bin2hex(random_bytes(16));
        $pdo->prepare('INSERT INTO bookings (branch_id,staff_id,service_id,service_name,start,end,name,phone,email,note,source,token,created_at)
                       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$branchId, $chosen, $serviceId, $service['name'], $start, $end, $name, $phone, $email ?: null, $note ?: null, 'online', $token, now_str()]);
        $id = (int)$pdo->lastInsertId();
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
    $booking = booking_by_id($id);
    send_booking_mails($booking, 'new');
    return $booking;
}

function booking_by_id(int $id): ?array
{
    $st = db()->prepare('SELECT b.*, s.name AS staff_name, br.name AS branch_name, br.street, br.city, br.phone AS branch_phone
        FROM bookings b JOIN staff s ON s.id=b.staff_id JOIN branches br ON br.id=b.branch_id WHERE b.id=?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function booking_by_token(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) return null;
    $st = db()->prepare('SELECT id FROM bookings WHERE token=?');
    $st->execute([$token]);
    $id = $st->fetchColumn();
    return $id ? booking_by_id((int)$id) : null;
}

function can_customer_cancel(array $b): bool
{
    if ($b['status'] !== 'confirmed') return false;
    $limit = (new DateTime())->modify('+' . (int)setting('cancel_hours', '12') . ' hours')->format('Y-m-d H:i');
    return $b['start'] > $limit;
}

function cancel_booking(int $id, bool $notify = true): void
{
    db()->prepare("UPDATE bookings SET status='cancelled', cancelled_at=? WHERE id=?")->execute([now_str(), $id]);
    if ($notify) send_booking_mails(booking_by_id($id), 'cancel');
}

/* ---------- E-Mails ---------- */

function fmt_date_de(string $dt): string
{
    $days = ['', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
    $ts = strtotime($dt);
    return $days[(int)date('N', $ts)] . ', ' . date('d.m.Y', $ts) . ' um ' . date('H:i', $ts) . ' Uhr';
}

function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $headers = [
        'From: ' . cfg('mail_from'),
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'MIME-Version: 1.0',
    ];
    if ($replyTo) $headers[] = 'Reply-To: ' . $replyTo;
    if (getenv('JC_NO_MAIL')) return true; // für Tests
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
}

function send_booking_mails(array $b, string $type): void
{
    $when = fmt_date_de($b['start']);
    $where = "{$b['street']}, {$b['city']}";
    $link = rtrim((string)cfg('site_url'), '/') . '/buchen/termin.php?t=' . $b['token'];
    $salon = (string)cfg('salon_email');

    if ($b['email']) {
        if ($type === 'new') {
            $body = "Hallo {$b['name']}\n\nVielen Dank für deine Buchung bei Jordan Coiffeur & Barbershop.\n\n"
                . "Dienstleistung: {$b['service_name']}\nTermin: $when\nBei: {$b['staff_name']}\nFiliale: $where\n\n"
                . "Termin ansehen oder absagen:\n$link\n\n"
                . "Kannst du nicht kommen? Bitte sag den Termin ab, damit jemand anderes ihn nutzen kann. "
                . "Kurzfristig erreichst du uns unter {$b['branch_phone']}.\n\nBis bald!\nDein Team von Jordan Coiffeur & Barbershop\n";
            send_mail($b['email'], "Terminbestätigung: $when", $body, $salon);
        } elseif ($type === 'cancel') {
            $body = "Hallo {$b['name']}\n\nDein Termin am $when ({$b['service_name']}) wurde abgesagt.\n\n"
                . "Neuen Termin buchen: " . rtrim((string)cfg('site_url'), '/') . "/buchen/\n\nDein Team von Jordan Coiffeur & Barbershop\n";
            send_mail($b['email'], "Termin abgesagt: $when", $body, $salon);
        }
    }
    if (setting('notify_salon', '1') === '1' && $salon && $b['source'] === 'online') {
        $title = $type === 'new' ? 'Neue Online-Buchung' : 'Termin abgesagt';
        $body = "$title\n\n{$b['service_name']}\n$when\nBei: {$b['staff_name']} ({$b['branch_name']})\n\n"
            . "Kunde: {$b['name']}\nTelefon: {$b['phone']}\nE-Mail: " . ($b['email'] ?: '–') . "\n"
            . ($b['note'] ? "Bemerkung: {$b['note']}\n" : '')
            . "\nKalender: " . rtrim((string)cfg('site_url'), '/') . "/admin/\n";
        send_mail($salon, "$title: {$b['name']}, $when", $body, $b['email'] ?: null);
    }
}

/* ---------- HTTP-Helfer ---------- */

function json_out($data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_in(): array
{
    $raw = file_get_contents('php://input');
    $d = json_decode($raw ?: '[]', true);
    return is_array($d) ? $d : [];
}

function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

/** Einfache Bremse gegen Missbrauch: max. $max Aktionen pro IP in $seconds. */
function rate_limited(string $bucket, int $max, int $seconds): bool
{
    $pdo = db();
    $key = $bucket . ':' . client_ip();
    $pdo->prepare('DELETE FROM login_attempts WHERE at < ?')->execute([time() - 86400]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip=? AND at > ?');
    $st->execute([$key, time() - $seconds]);
    if ($st->fetchColumn() >= $max) return true;
    $pdo->prepare('INSERT INTO login_attempts VALUES (?,?)')->execute([$key, time()]);
    return false;
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
