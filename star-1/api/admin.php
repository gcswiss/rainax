<?php
// Star 1 Coiffeur — Schnittstelle für die Verwaltung (nur mit Login)

declare(strict_types=1);
require __DIR__ . '/lib.php';

session_name('star1admin');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

try {
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Ohne Login erlaubt
    if ($action === 'login') {
        $method === 'POST' || fail('Methode nicht erlaubt.', 405);
        login();
    }
    if ($action === 'me') {
        if (empty($_SESSION['admin_id'])) {
            json_out(['ok' => true, 'user' => null]);
        }
        json_out(['ok' => true] + me());
    }

    // Ab hier: Login nötig
    if (empty($_SESSION['admin_id'])) {
        fail('Bitte melden Sie sich an.', 401);
    }
    if ($_SESSION['last'] ?? 0) {
        if (time() - $_SESSION['last'] > 8 * 3600) {
            session_destroy();
            fail('Sitzung abgelaufen. Bitte neu anmelden.', 401);
        }
    }
    $_SESSION['last'] = time();

    if ($method === 'POST') {
        $token = $_SERVER['HTTP_X_CSRF'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
            fail('Sicherheits-Token ungültig. Bitte Seite neu laden.', 403);
        }
    }

    // Solange das Startpasswort aktiv ist, nur Passwortwechsel zulassen
    if (!empty($_SESSION['must_change']) && !in_array($action, ['password', 'logout'], true)) {
        fail('Bitte ändern Sie zuerst das Passwort.', 403);
    }

    $in = body();
    switch ($action) {
        case 'logout':
            $_SESSION = [];
            session_destroy();
            json_out(['ok' => true]);

        case 'password':
            change_password($in);

        case 'overview':
            overview();

        case 'day':
            $date = (string)($_GET['date'] ?? '');
            valid_date($date) || fail('Ungültiges Datum.');
            day($date);

        case 'bookings':
            bookings_list();

        case 'export':
            export_csv();

        case 'booking_save':
            booking_save($in);

        case 'booking_status':
            $id = (int)($in['id'] ?? 0);
            $status = ($in['status'] ?? '') === 'cancelled' ? 'cancelled' : 'confirmed';
            try {
                db()->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$status, $id]);
            } catch (PDOException $e) {
                fail('Dieser Zeitpunkt ist inzwischen belegt – Termin kann nicht wiederhergestellt werden.', 409);
            }
            json_out(['ok' => true]);

        case 'booking_delete':
            db()->prepare('DELETE FROM bookings WHERE id = ?')->execute([(int)($in['id'] ?? 0)]);
            json_out(['ok' => true]);

        case 'staff':
            json_out(['ok' => true, 'staff' => staff_list(false)]);

        case 'staff_save':
            staff_save($in);

        case 'staff_delete':
            $id = (int)($in['id'] ?? 0);
            $st = db()->prepare('SELECT COUNT(*) FROM bookings WHERE staff_id = ?');
            $st->execute([$id]);
            if ((int)$st->fetchColumn() > 0) {
                db()->prepare('UPDATE staff SET active = 0 WHERE id = ?')->execute([$id]);
                json_out(['ok' => true, 'message' => 'Hat bereits Termine – wurde deaktiviert statt gelöscht.']);
            }
            db()->prepare('DELETE FROM staff WHERE id = ?')->execute([$id]);
            json_out(['ok' => true]);

        case 'services':
            json_out(['ok' => true, 'services' => service_list(false)]);

        case 'service_save':
            service_save($in);

        case 'service_delete':
            db()->prepare('DELETE FROM services WHERE id = ?')->execute([(int)($in['id'] ?? 0)]);
            json_out(['ok' => true]);

        case 'settings':
            json_out([
                'ok' => true,
                'hours' => hours(),
                'horizon_days' => (int)setting('horizon_days', '60'),
                'lead_minutes' => (int)setting('lead_minutes', '60'),
                'slot_minutes' => (int)setting('slot_minutes', '30'),
            ]);

        case 'settings_save':
            settings_save($in);

        case 'blocks':
            $st = db()->prepare('SELECT b.*, s.name AS staff_name FROM blocks b LEFT JOIN staff s ON s.id = b.staff_id
                WHERE b.date_to >= ? ORDER BY b.date_from, b.time_from');
            $st->execute([date('Y-m-d', strtotime('-30 days'))]);
            json_out(['ok' => true, 'blocks' => $st->fetchAll()]);

        case 'block_save':
            block_save($in);

        case 'block_delete':
            db()->prepare('DELETE FROM blocks WHERE id = ?')->execute([(int)($in['id'] ?? 0)]);
            json_out(['ok' => true]);

        default:
            fail('Unbekannte Aktion.', 404);
    }
} catch (Throwable $e) {
    error_log('[star1-admin] ' . $e->getMessage());
    fail('Interner Fehler. Details im Server-Log.', 500);
}

// ---------------------------------------------------------------- Konto

function me(): array
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return [
        'user' => $_SESSION['admin_user'],
        'must_change' => !empty($_SESSION['must_change']),
        'csrf' => $_SESSION['csrf'],
    ];
}

function login(): void
{
    if (too_many('login', 8, 900)) {
        fail('Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.', 429);
    }
    $in = body();
    $user = str_in($in, 'username', 60);
    $pass = (string)($in['password'] ?? '');
    $st = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $st->execute([$user]);
    $row = $st->fetch();
    if (!$row || !password_verify($pass, $row['pass_hash'])) {
        note_attempt('login');
        usleep(400000);
        fail('Benutzername oder Passwort falsch.', 401);
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$row['id'];
    $_SESSION['admin_user'] = $row['username'];
    $_SESSION['must_change'] = (int)$row['must_change'] === 1;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['last'] = time();
    json_out(['ok' => true] + me());
}

function change_password(array $in): void
{
    $current = (string)($in['current'] ?? '');
    $new = (string)($in['new'] ?? '');
    $st = db()->prepare('SELECT pass_hash FROM admins WHERE id = ?');
    $st->execute([$_SESSION['admin_id']]);
    if (!password_verify($current, (string)$st->fetchColumn())) {
        fail('Das aktuelle Passwort stimmt nicht.');
    }
    if (mb_strlen($new) < 10) {
        fail('Das neue Passwort muss mindestens 10 Zeichen lang sein.');
    }
    if ($new === $current || $new === cfg('admin_initial_pass')) {
        fail('Bitte wählen Sie ein neues Passwort.');
    }
    db()->prepare('UPDATE admins SET pass_hash = ?, must_change = 0 WHERE id = ?')
        ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
    $_SESSION['must_change'] = false;
    json_out(['ok' => true] + me());
}

// ---------------------------------------------------------------- Termine

function overview(): void
{
    $pdo = db();
    $today = date('Y-m-d');
    $weekEnd = date('Y-m-d', strtotime('+6 days'));
    $count = function (string $sql, array $p) use ($pdo) {
        $st = $pdo->prepare($sql);
        $st->execute($p);
        return (int)$st->fetchColumn();
    };
    json_out([
        'ok' => true,
        'today' => $count("SELECT COUNT(*) FROM bookings WHERE date = ? AND status = 'confirmed'", [$today]),
        'week' => $count("SELECT COUNT(*) FROM bookings WHERE date BETWEEN ? AND ? AND status = 'confirmed'", [$today, $weekEnd]),
        'online_7d' => $count("SELECT COUNT(*) FROM bookings WHERE source = 'online' AND created_at >= ?", [date('Y-m-d H:i:s', strtotime('-7 days'))]),
        'revenue_today' => (float)$pdo->query("SELECT COALESCE(SUM(price), 0) FROM bookings WHERE date = '$today' AND status = 'confirmed'")->fetchColumn(),
    ]);
}

function day(string $date): void
{
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM bookings WHERE date = ? ORDER BY time');
    $st->execute([$date]);
    $bookings = $st->fetchAll();
    foreach ($bookings as &$b) {
        $b['id'] = (int)$b['id'];
        $b['staff_id'] = (int)$b['staff_id'];
        $b['price'] = $b['price'] === null ? null : (float)$b['price'];
    }
    unset($b);

    $dow = (int)(new DateTime($date))->format('w');
    $bookedStaff = array_column($bookings, 'staff_id');
    $staff = array_values(array_filter(
        staff_list(false),
        fn($s) => ($s['active'] && in_array($dow, $s['days'], true)) || in_array($s['id'], $bookedStaff, true)
    ));

    $slots = day_slots($date);
    $closed = !$slots;
    // Termine ausserhalb der Öffnungszeiten trotzdem anzeigen
    foreach ($bookings as $b) {
        if ($b['status'] === 'confirmed' && !in_array($b['time'], $slots, true)) {
            $slots[] = $b['time'];
        }
    }
    sort($slots);

    $step = max(5, (int)setting('slot_minutes', '30'));
    $blocks = blocks_for($date);
    $blocked = [];
    foreach ($staff as $s) {
        foreach ($slots as $t) {
            if (is_blocked($blocks, $s['id'], $t, $step)) {
                $blocked[] = $s['id'] . '@' . $t;
            }
        }
    }
    json_out([
        'ok' => true,
        'date' => $date,
        'date_label' => format_date_de($date),
        'closed' => $closed,
        'slots' => $slots,
        'staff' => $staff,
        'bookings' => $bookings,
        'blocked' => $blocked,
        'blocks' => $blocks,
    ]);
}

function bookings_query(): array
{
    $from = (string)($_GET['from'] ?? date('Y-m-d'));
    $to = (string)($_GET['to'] ?? date('Y-m-d', strtotime('+60 days')));
    $q = trim((string)($_GET['q'] ?? ''));
    $status = (string)($_GET['status'] ?? 'confirmed');
    if (!valid_date($from) || !valid_date($to)) {
        fail('Ungültiger Zeitraum.');
    }
    $sql = 'SELECT b.*, s.name AS staff_name FROM bookings b LEFT JOIN staff s ON s.id = b.staff_id WHERE b.date BETWEEN ? AND ?';
    $p = [$from, $to];
    if ($status === 'confirmed' || $status === 'cancelled') {
        $sql .= ' AND b.status = ?';
        $p[] = $status;
    }
    if ($q !== '') {
        $sql .= ' AND (b.name LIKE ? OR b.phone LIKE ? OR b.email LIKE ? OR b.service_name LIKE ?)';
        $like = '%' . $q . '%';
        array_push($p, $like, $like, $like, $like);
    }
    $sql .= ' ORDER BY b.date, b.time LIMIT 1000';
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

function bookings_list(): void
{
    json_out(['ok' => true, 'bookings' => bookings_query()]);
}

function export_csv(): void
{
    $rows = bookings_query();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="star1-termine-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Nr', 'Datum', 'Zeit', 'Mitarbeiter', 'Dienstleistung', 'Haarlänge', 'Preis', 'Name', 'Telefon', 'E-Mail', 'Notiz', 'Status', 'Quelle', 'Erstellt'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['date'], $r['time'], $r['staff_name'], $r['service_name'], $r['variant'], $r['price'],
            $r['name'], $r['phone'], $r['email'], $r['note'], $r['status'], $r['source'], $r['created_at'],
        ], ';');
    }
    exit;
}

function booking_save(array $in): void
{
    $id = (int)($in['id'] ?? 0);
    $date = str_in($in, 'date', 10);
    $time = str_in($in, 'time', 5);
    $staffId = (int)($in['staff_id'] ?? 0);
    $serviceId = (int)($in['service_id'] ?? 0);
    $variant = str_in($in, 'variant', 10);
    $name = str_in($in, 'name', 100);
    if (!valid_date($date) || !valid_time($time)) {
        fail('Bitte Datum und Uhrzeit angeben.');
    }
    if ($name === '') {
        fail('Bitte einen Namen angeben.');
    }
    $staffIds = array_column(staff_list(false), 'id');
    if (!in_array($staffId, $staffIds, true)) {
        fail('Bitte eine Mitarbeiterin / einen Mitarbeiter wählen.');
    }
    $service = null;
    foreach (service_list(false) as $s) {
        if ($s['id'] === $serviceId) {
            $service = $s;
        }
    }
    $serviceName = $service ? $service['category'] . ' · ' . $service['name'] : str_in($in, 'service_name', 150);
    if ($service && !has_variants($service)) {
        $variant = '';
    }
    $price = isset($in['price']) && $in['price'] !== '' && $in['price'] !== null
        ? (float)$in['price']
        : ($service ? price_for($service, $variant) : null);

    $fields = [
        $date, $time, $staffId, $service['id'] ?? null, $serviceName, $variant, $price, $name,
        str_in($in, 'phone', 40), str_in($in, 'email', 150), str_in($in, 'note', 500),
    ];
    try {
        if ($id) {
            $fields[] = $id;
            db()->prepare('UPDATE bookings SET date=?, time=?, staff_id=?, service_id=?, service_name=?, variant=?, price=?, name=?, phone=?, email=?, note=? WHERE id=?')
                ->execute($fields);
        } else {
            array_push($fields, date('Y-m-d H:i:s'));
            db()->prepare("INSERT INTO bookings(date, time, staff_id, service_id, service_name, variant, price, name, phone, email, note, status, source, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'admin', ?)")->execute($fields);
            $id = (int)db()->lastInsertId();
        }
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'UNIQUE')) {
            fail('Zu dieser Zeit hat die gewählte Person bereits einen Termin.', 409);
        }
        throw $e;
    }
    json_out(['ok' => true, 'id' => $id]);
}

// ---------------------------------------------------------------- Stammdaten

function staff_save(array $in): void
{
    $id = (int)($in['id'] ?? 0);
    $name = str_in($in, 'name', 60);
    $name !== '' || fail('Bitte einen Namen angeben.');
    $days = array_values(array_unique(array_filter(array_map('intval', (array)($in['days'] ?? [])), fn($d) => $d >= 0 && $d <= 6)));
    sort($days);
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($in['color'] ?? '')) ? $in['color'] : '#E3B341';
    $f = [$name, str_in($in, 'role', 60), $color, implode(',', $days), empty($in['active']) ? 0 : 1, (int)($in['sort'] ?? 0)];
    if ($id) {
        $f[] = $id;
        db()->prepare('UPDATE staff SET name=?, role=?, color=?, days=?, active=?, sort=? WHERE id=?')->execute($f);
    } else {
        db()->prepare('INSERT INTO staff(name, role, color, days, active, sort) VALUES (?, ?, ?, ?, ?, ?)')->execute($f);
    }
    json_out(['ok' => true]);
}

function service_save(array $in): void
{
    $id = (int)($in['id'] ?? 0);
    $name = str_in($in, 'name', 120);
    $cat = str_in($in, 'category', 60);
    ($name !== '' && $cat !== '') || fail('Bitte Kategorie und Bezeichnung angeben.');
    $p = fn($k) => (isset($in[$k]) && $in[$k] !== '' && $in[$k] !== null) ? round((float)$in[$k], 2) : null;
    $f = [$cat, $name, $p('price1'), $p('price2'), $p('price3'), empty($in['active']) ? 0 : 1, (int)($in['sort'] ?? 0)];
    if ($id) {
        $f[] = $id;
        db()->prepare('UPDATE services SET category=?, name=?, price1=?, price2=?, price3=?, active=?, sort=? WHERE id=?')->execute($f);
    } else {
        db()->prepare('INSERT INTO services(category, name, price1, price2, price3, active, sort) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute($f);
    }
    json_out(['ok' => true]);
}

function settings_save(array $in): void
{
    $hours = [];
    foreach (range(0, 6) as $d) {
        $h = $in['hours'][(string)$d] ?? null;
        if (is_array($h) && valid_time((string)($h[0] ?? '')) && valid_time((string)($h[1] ?? '')) && $h[0] < $h[1]) {
            $hours[(string)$d] = [$h[0], $h[1]];
        } else {
            $hours[(string)$d] = null;
        }
    }
    set_setting('hours', json_encode($hours));
    set_setting('horizon_days', (string)max(1, min(365, (int)($in['horizon_days'] ?? 60))));
    set_setting('lead_minutes', (string)max(0, min(10080, (int)($in['lead_minutes'] ?? 60))));
    json_out(['ok' => true]);
}

function block_save(array $in): void
{
    $from = str_in($in, 'date_from', 10);
    $to = str_in($in, 'date_to', 10) ?: $from;
    (valid_date($from) && valid_date($to) && $from <= $to) || fail('Bitte einen gültigen Zeitraum angeben.');
    $tf = str_in($in, 'time_from', 5);
    $tt = str_in($in, 'time_to', 5);
    if ($tf !== '' || $tt !== '') {
        (valid_time($tf) && valid_time($tt) && $tf < $tt) || fail('Bitte eine gültige Uhrzeit (von–bis) angeben oder leer lassen für ganztags.');
    } else {
        $tf = $tt = null;
    }
    $staffId = (int)($in['staff_id'] ?? 0) ?: null;
    db()->prepare('INSERT INTO blocks(staff_id, date_from, date_to, time_from, time_to, reason) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$staffId, $from, $to, $tf, $tt, str_in($in, 'reason', 120)]);
    json_out(['ok' => true]);
}
