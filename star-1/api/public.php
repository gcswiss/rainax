<?php
// Star 1 Coiffeur — öffentliche Schnittstelle für Webseite und Online-Buchung

declare(strict_types=1);
require __DIR__ . '/lib.php';

try {
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    switch ($action) {
        case 'info':
            $staff = array_map(fn($s) => [
                'id' => $s['id'], 'name' => $s['name'], 'role' => $s['role'], 'color' => $s['color'], 'days' => $s['days'],
            ], staff_list());
            $services = array_map(fn($s) => [
                'id' => $s['id'], 'category' => $s['category'], 'name' => $s['name'],
                'prices' => [$s['price1'], $s['price2'], $s['price3']],
            ], service_list());
            json_out([
                'ok' => true,
                'hours' => hours(),
                'staff' => $staff,
                'services' => $services,
                'slot_minutes' => (int)setting('slot_minutes', '30'),
                'horizon_days' => (int)setting('horizon_days', '60'),
                'today' => date('Y-m-d'),
            ]);

        case 'days':
            // Anzahl freier Zeiten pro Tag, für die Kalenderansicht
            $from = (string)($_GET['from'] ?? '');
            $to = (string)($_GET['to'] ?? '');
            if (!valid_date($from) || !valid_date($to) || $to < $from) {
                fail('Ungültiger Zeitraum.');
            }
            $d = new DateTime($from);
            $end = new DateTime($to);
            if ($d->diff($end)->days > 62) {
                fail('Zeitraum zu lang.');
            }
            $out = [];
            while ($d <= $end) {
                $key = $d->format('Y-m-d');
                $out[$key] = count(availability($key));
                $d->modify('+1 day');
            }
            json_out(['ok' => true, 'days' => $out]);

        case 'slots':
            $date = (string)($_GET['date'] ?? '');
            if (!valid_date($date)) {
                fail('Ungültiges Datum.');
            }
            json_out(['ok' => true, 'date' => $date, 'slots' => availability($date)]);

        case 'book':
            if ($method !== 'POST') {
                fail('Methode nicht erlaubt.', 405);
            }
            book();

        case 'contact':
            if ($method !== 'POST') {
                fail('Methode nicht erlaubt.', 405);
            }
            contact();

        default:
            fail('Unbekannte Aktion.', 404);
    }
} catch (Throwable $e) {
    error_log('[star1] ' . $e->getMessage());
    fail('Interner Fehler. Bitte rufen Sie uns an: ' . cfg('salon_phone'), 500);
}

function book(): void
{
    $in = body();
    if (str_in($in, 'website') !== '') {
        json_out(['ok' => true, 'id' => 0]); // Spam-Falle: still ignorieren
    }
    if (too_many('book', 6, 3600)) {
        fail('Zu viele Buchungen in kurzer Zeit. Bitte rufen Sie uns an: ' . cfg('salon_phone'), 429);
    }

    $date = str_in($in, 'date', 10);
    $time = str_in($in, 'time', 5);
    $staffId = (int)($in['staff_id'] ?? 0);
    $serviceId = (int)($in['service_id'] ?? 0);
    $variant = str_in($in, 'variant', 10);
    $name = str_in($in, 'name', 100);
    $phone = str_in($in, 'phone', 40);
    $email = str_in($in, 'email', 150);
    $note = str_in($in, 'note', 500);

    if (!valid_date($date) || !valid_time($time)) {
        fail('Bitte wählen Sie Datum und Uhrzeit.');
    }
    if (mb_strlen($name) < 2) {
        fail('Bitte geben Sie Ihren Namen an.');
    }
    if (!preg_match('/^[+0-9 ()\/.-]{7,}$/', $phone)) {
        fail('Bitte geben Sie eine gültige Telefonnummer an.');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fail('Die E-Mail-Adresse ist ungültig.');
    }

    $service = null;
    foreach (service_list() as $s) {
        if ($s['id'] === $serviceId) {
            $service = $s;
        }
    }
    if (!$service) {
        fail('Bitte wählen Sie eine Dienstleistung.');
    }
    if (has_variants($service)) {
        if (price_for($service, $variant) === null) {
            fail('Bitte wählen Sie die Haarlänge.');
        }
    } else {
        $variant = '';
    }
    $price = price_for($service, $variant);

    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $free = availability($date)[$time] ?? [];
        if ($staffId === 0) {
            if (!$free) {
                throw new RuntimeException('taken');
            }
            // «Keine Präferenz»: die Person mit den wenigsten Terminen an diesem Tag
            $st = $pdo->prepare("SELECT staff_id, COUNT(*) c FROM bookings WHERE date = ? AND status = 'confirmed' GROUP BY staff_id");
            $st->execute([$date]);
            $load = array_column($st->fetchAll(), 'c', 'staff_id');
            usort($free, fn($a, $b) => ($load[$a] ?? 0) <=> ($load[$b] ?? 0));
            $staffId = $free[0];
        } elseif (!in_array($staffId, $free, true)) {
            throw new RuntimeException('taken');
        }

        $st = $pdo->prepare("INSERT INTO bookings(date, time, staff_id, service_id, service_name, variant, price, name, phone, email, note, status, source, ip, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'online', ?, ?)");
        $st->execute([
            $date, $time, $staffId, $service['id'], $service['category'] . ' · ' . $service['name'], $variant, $price,
            $name, $phone, $email, $note, client_ip(), date('Y-m-d H:i:s'),
        ]);
        $id = (int)$pdo->lastInsertId();
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        if ($e instanceof RuntimeException || ($e instanceof PDOException && str_contains($e->getMessage(), 'UNIQUE'))) {
            fail('Dieser Termin wurde soeben vergeben. Bitte wählen Sie eine andere Zeit.', 409);
        }
        throw $e;
    }
    note_attempt('book');

    $staffName = '';
    foreach (staff_list(false) as $s) {
        if ($s['id'] === $staffId) {
            $staffName = $s['name'];
        }
    }
    $serviceLabel = $service['category'] . ' · ' . $service['name'] . ($variant ? ' (' . ['kurz' => 'kurze Haare', 'mittel' => 'mittellange Haare', 'lang' => 'lange Haare'][$variant] . ')' : '');
    $summary = "Datum:         " . format_date_de($date) . "\n"
        . "Uhrzeit:       $time Uhr\n"
        . "Bei:           $staffName\n"
        . "Dienstleistung: $serviceLabel\n"
        . "Preis:         " . format_chf($price) . "\n";

    send_mail(
        $email,
        'Ihr Termin bei ' . cfg('salon_name') . ' – ' . format_date_de($date) . ", $time Uhr",
        "Guten Tag $name\n\nVielen Dank für Ihre Buchung. Wir freuen uns auf Sie!\n\n$summary\n"
        . "Termin verschieben oder absagen? Rufen Sie uns bitte an: " . cfg('salon_phone') . "\n\n"
        . cfg('salon_name') . "\n" . cfg('salon_address') . "\n"
    );
    send_mail(
        cfg('mail_salon'),
        "Neue Online-Buchung: " . format_date_de($date) . ", $time Uhr – $name",
        "Neue Buchung über die Webseite (#$id)\n\n$summary\nKunde:   $name\nTelefon: $phone\nE-Mail:  $email\nNotiz:   $note\n",
        $email
    );

    json_out([
        'ok' => true,
        'id' => $id,
        'date' => $date,
        'date_label' => format_date_de($date),
        'time' => $time,
        'staff' => $staffName,
        'service' => $serviceLabel,
        'price' => format_chf($price),
    ]);
}

function contact(): void
{
    $in = body();
    if (str_in($in, 'website') !== '') {
        json_out(['ok' => true]);
    }
    if (too_many('contact', 5, 3600)) {
        fail('Zu viele Nachrichten. Bitte versuchen Sie es später erneut.', 429);
    }
    $name = str_in($in, 'name', 100);
    $email = str_in($in, 'email', 150);
    $phone = str_in($in, 'phone', 40);
    $message = str_in($in, 'message', 3000);
    if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 5) {
        fail('Bitte füllen Sie Name, E-Mail und Nachricht aus.');
    }
    note_attempt('contact');
    send_mail(
        cfg('mail_salon'),
        "Kontaktanfrage von $name",
        "Name:    $name\nE-Mail:  $email\nTelefon: $phone\n\n$message\n",
        $email
    );
    json_out(['ok' => true]);
}
