<?php
/**
 * Öffentliche Buchungs-API
 *   GET  ?a=catalog                                   → Filialen, Mitarbeiter, Dienstleistungen
 *   GET  ?a=days&branch=1&service=2&staff=&month=2026-11 → Tage mit freien Terminen
 *   GET  ?a=slots&branch=1&service=2&staff=&date=2026-11-12 → freie Zeiten
 *   POST ?a=book  {branch_id, service_id, staff_id|null, date, time, name, phone, email, note, website}
 */
declare(strict_types=1);
require __DIR__ . '/../lib/core.php';

try {
    $a = $_GET['a'] ?? '';
    if ($a === 'catalog') json_out(public_catalog());

    if ($a === 'days' || $a === 'slots') {
        $branch = (int)($_GET['branch'] ?? 0);
        $service = get_service((int)($_GET['service'] ?? 0));
        $staff = !empty($_GET['staff']) ? (int)$_GET['staff'] : null;
        if (!$service || !$service['active'] || !$branch) json_out(['error' => 'Ungültige Anfrage'], 400);
        $dur = (int)$service['duration'];

        if ($a === 'slots') {
            $date = (string)($_GET['date'] ?? '');
            if (!valid_date($date)) json_out(['error' => 'Ungültiges Datum'], 400);
            json_out(['date' => $date, 'times' => array_keys(free_slots($branch, $dur, $date, $staff))]);
        }

        $month = (string)($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) json_out(['error' => 'Ungültiger Monat'], 400);
        $days = [];
        $d = new DateTime("$month-01");
        $endM = (clone $d)->modify('last day of this month')->format('Y-m-d');
        $today = date('Y-m-d');
        for (; $d->format('Y-m-d') <= $endM; $d->modify('+1 day')) {
            $ds = $d->format('Y-m-d');
            if ($ds < $today) continue;
            if (free_slots($branch, $dur, $ds, $staff)) $days[] = $ds;
        }
        json_out(['month' => $month, 'days' => $days]);
    }

    if ($a === 'book' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = json_in();
        if (!empty($in['website'])) json_out(['error' => 'Fehler'], 400); // Honeypot gegen Spam-Bots
        if (rate_limited('book', 8, 3600)) json_out(['error' => 'Zu viele Buchungen in kurzer Zeit. Bitte ruf uns an.'], 429);
        $b = create_online_booking($in);
        json_out(['ok' => true, 'booking' => [
            'service' => $b['service_name'], 'start' => $b['start'], 'when' => fmt_date_de($b['start']),
            'staff' => $b['staff_name'], 'branch' => $b['branch_name'], 'address' => "{$b['street']}, {$b['city']}",
            'manage_url' => '/buchen/termin.php?t=' . $b['token'], 'email_sent' => (bool)$b['email'],
        ]]);
    }

    json_out(['error' => 'Unbekannte Aktion'], 404);
} catch (BookingError $e) {
    json_out(['error' => $e->getMessage()], 409);
} catch (Throwable $e) {
    error_log('[jordan-buchung] ' . $e);
    json_out(['error' => 'Technischer Fehler. Bitte ruf uns an: 071 220 10 00'], 500);
}
