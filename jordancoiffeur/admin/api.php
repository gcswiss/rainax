<?php
/** Admin-API (nur mit Login + CSRF-Token). */
declare(strict_types=1);
require __DIR__ . '/../lib/auth.php';

if (!is_admin()) json_out(['error' => 'Nicht angemeldet'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_csrf($_SERVER['HTTP_X_CSRF'] ?? null)) json_out(['error' => 'Ungültige Anfrage'], 403);

$in = json_in();
$a = (string)($in['a'] ?? '');
$pdo = db();

function req_dt(array $in, string $k): string
{
    $v = (string)($in[$k] ?? '');
    if (!valid_dt($v)) throw new BookingError('Ungültige Zeitangabe.');
    return $v;
}

function staff_row(int $id): array
{
    $st = db()->prepare('SELECT * FROM staff WHERE id=?');
    $st->execute([$id]);
    $r = $st->fetch();
    if (!$r) throw new BookingError('Mitarbeiter nicht gefunden.');
    return $r;
}

try {
    switch ($a) {
        case 'bootstrap':
            // Datenschutz: Termine älter als 2 Jahre löschen
            $pdo->prepare('DELETE FROM bookings WHERE end < ?')->execute([date('Y-m-d', strtotime('-2 years'))]);
            json_out([
                'branches' => $pdo->query('SELECT * FROM branches ORDER BY sort,id')->fetchAll(),
                'hours' => $pdo->query('SELECT * FROM hours')->fetchAll(),
                'staff' => $pdo->query('SELECT * FROM staff ORDER BY sort,id')->fetchAll(),
                'services' => $pdo->query('SELECT * FROM services ORDER BY sort,id')->fetchAll(),
                'settings' => array_filter($pdo->query('SELECT key,value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR),
                    fn($k) => $k !== 'admin_hash', ARRAY_FILTER_USE_KEY),
            ]);

        case 'events': {
            $from = (string)($in['from'] ?? ''); $to = (string)($in['to'] ?? '');
            if (!valid_date($from) || !valid_date($to)) throw new BookingError('Ungültiger Zeitraum.');
            $toEx = date('Y-m-d', strtotime("$to +1 day"));
            $st = $pdo->prepare("SELECT id,branch_id,staff_id,service_id,service_name,start,end,name,phone,email,note,status,source,created_at
                                 FROM bookings WHERE start < ? AND end > ? AND status <> 'cancelled' ORDER BY start");
            $st->execute(["$toEx 00:00", "$from 00:00"]);
            $bookings = $st->fetchAll();
            $st = $pdo->prepare('SELECT * FROM blocks WHERE start < ? AND end > ? ORDER BY start');
            $st->execute(["$toEx 00:00", "$from 00:00"]);
            json_out(['bookings' => $bookings, 'blocks' => $st->fetchAll()]);
        }

        case 'list': {
            $q = trim((string)($in['q'] ?? ''));
            $sql = "SELECT b.*, s.name AS staff_name, br.name AS branch_name FROM bookings b JOIN staff s ON s.id=b.staff_id JOIN branches br ON br.id=b.branch_id";
            $args = [];
            if ($q !== '') {
                $sql .= ' WHERE (b.name LIKE ? OR b.phone LIKE ? OR b.email LIKE ?)';
                $args = ["%$q%", "%$q%", "%$q%"];
                $sql .= ' ORDER BY b.start DESC LIMIT 200';
            } else {
                $sql .= " WHERE b.start >= ? AND b.status <> 'cancelled' ORDER BY b.start LIMIT 300";
                $args = [date('Y-m-d') . ' 00:00'];
            }
            $st = $pdo->prepare($sql);
            $st->execute($args);
            json_out(['items' => $st->fetchAll()]);
        }

        case 'slots': {
            // freie Zeiten (für Admin ohne Vorlaufzeit), hilft beim Telefon-Termin
            $service = get_service((int)($in['service_id'] ?? 0));
            if (!$service || !valid_date((string)($in['date'] ?? ''))) throw new BookingError('Ungültige Anfrage.');
            $slots = free_slots((int)$in['branch_id'], (int)$service['duration'], $in['date'], !empty($in['staff_id']) ? (int)$in['staff_id'] : null, true);
            json_out(['times' => array_keys($slots)]);
        }

        case 'booking.save': {
            $id = (int)($in['id'] ?? 0);
            $staff = staff_row((int)($in['staff_id'] ?? 0));
            $start = req_dt($in, 'start');
            $end = req_dt($in, 'end');
            if ($end <= $start) throw new BookingError('Das Ende muss nach dem Beginn liegen.');
            $name = trim((string)($in['name'] ?? ''));
            if ($name === '') throw new BookingError('Bitte einen Namen eingeben.');
            $serviceId = !empty($in['service_id']) ? (int)$in['service_id'] : null;
            $service = $serviceId ? get_service($serviceId) : null;
            $serviceName = $service['name'] ?? trim((string)($in['service_name'] ?? 'Termin'));
            if (empty($in['force']) && !staff_is_free((int)$staff['id'], (int)$staff['branch_id'], $start, $end, $id ?: null)) {
                json_out(['conflict' => true, 'error' => "{$staff['name']} hat zu dieser Zeit schon einen Termin oder ist abwesend."], 409);
            }
            $email = trim((string)($in['email'] ?? '')) ?: null;
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new BookingError('Die E-Mail-Adresse ist ungültig.');
            $vals = [(int)$staff['branch_id'], (int)$staff['id'], $serviceId, $serviceName, $start, $end, $name,
                trim((string)($in['phone'] ?? '')) ?: null, $email, trim((string)($in['note'] ?? '')) ?: null];
            if ($id) {
                $pdo->prepare('UPDATE bookings SET branch_id=?,staff_id=?,service_id=?,service_name=?,start=?,end=?,name=?,phone=?,email=?,note=? WHERE id=?')
                    ->execute([...$vals, $id]);
            } else {
                $pdo->prepare("INSERT INTO bookings (branch_id,staff_id,service_id,service_name,start,end,name,phone,email,note,source,token,created_at)
                               VALUES (?,?,?,?,?,?,?,?,?,?,'admin',?,?)")
                    ->execute([...$vals, bin2hex(random_bytes(16)), now_str()]);
                $id = (int)$pdo->lastInsertId();
                if (!empty($in['notify']) && $email) send_booking_mails(array_merge(booking_by_id($id), ['source' => 'admin']), 'new');
            }
            json_out(['ok' => true, 'id' => $id]);
        }

        case 'booking.status': {
            $id = (int)($in['id'] ?? 0);
            $status = (string)($in['status'] ?? '');
            if (!in_array($status, ['confirmed', 'cancelled', 'noshow'], true)) throw new BookingError('Ungültiger Status.');
            if ($status === 'cancelled') {
                $b = booking_by_id($id);
                db()->prepare("UPDATE bookings SET status='cancelled', cancelled_at=? WHERE id=?")->execute([now_str(), $id]);
                if (!empty($in['notify']) && $b && $b['email']) send_booking_mails(array_merge($b, ['source' => 'admin']), 'cancel');
            } else {
                $pdo->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$status, $id]);
            }
            json_out(['ok' => true]);
        }

        case 'block.save': {
            $id = (int)($in['id'] ?? 0);
            $start = req_dt($in, 'start');
            $end = req_dt($in, 'end');
            if ($end <= $start) throw new BookingError('Das Ende muss nach dem Beginn liegen.');
            $staffId = !empty($in['staff_id']) ? (int)$in['staff_id'] : null;
            $branchId = !empty($in['branch_id']) ? (int)$in['branch_id'] : null;
            if ($staffId) $branchId = (int)staff_row($staffId)['branch_id'];
            $note = trim((string)($in['note'] ?? '')) ?: null;
            if ($id) {
                $pdo->prepare('UPDATE blocks SET branch_id=?,staff_id=?,start=?,end=?,note=? WHERE id=?')->execute([$branchId, $staffId, $start, $end, $note, $id]);
            } else {
                $pdo->prepare('INSERT INTO blocks (branch_id,staff_id,start,end,note) VALUES (?,?,?,?,?)')->execute([$branchId, $staffId, $start, $end, $note]);
                $id = (int)$pdo->lastInsertId();
            }
            json_out(['ok' => true, 'id' => $id]);
        }

        case 'block.delete':
            $pdo->prepare('DELETE FROM blocks WHERE id=?')->execute([(int)($in['id'] ?? 0)]);
            json_out(['ok' => true]);

        case 'services.save': {
            $pdo->beginTransaction();
            foreach ((array)($in['items'] ?? []) as $i => $s) {
                $name = trim((string)($s['name'] ?? ''));
                $dur = (int)($s['duration'] ?? 0);
                if ($name === '' || $dur < 5 || $dur > 600) throw new BookingError('Jede Dienstleistung braucht einen Namen und eine Dauer (5–600 Min.).');
                $vals = [$name, trim((string)($s['description'] ?? '')), $dur, trim((string)($s['price'] ?? '')), empty($s['active']) ? 0 : 1, $i + 1];
                if (!empty($s['id'])) $pdo->prepare('UPDATE services SET name=?,description=?,duration=?,price=?,active=?,sort=? WHERE id=?')->execute([...$vals, (int)$s['id']]);
                else $pdo->prepare('INSERT INTO services (name,description,duration,price,active,sort) VALUES (?,?,?,?,?,?)')->execute($vals);
            }
            $pdo->commit();
            json_out(['ok' => true]);
        }

        case 'staff.save': {
            $pdo->beginTransaction();
            foreach ((array)($in['items'] ?? []) as $i => $s) {
                $name = trim((string)($s['name'] ?? ''));
                if ($name === '') throw new BookingError('Jeder Mitarbeiter braucht einen Namen.');
                $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($s['color'] ?? '')) ? $s['color'] : '#f2a541';
                $days = preg_replace('/[^1-7]/', '', (string)($s['workdays'] ?? ''));
                $vals = [$name, (int)$s['branch_id'], $color, $days, empty($s['bookable']) ? 0 : 1, empty($s['active']) ? 0 : 1, $i + 1];
                if (!empty($s['id'])) $pdo->prepare('UPDATE staff SET name=?,branch_id=?,color=?,workdays=?,bookable=?,active=?,sort=? WHERE id=?')->execute([...$vals, (int)$s['id']]);
                else $pdo->prepare('INSERT INTO staff (name,branch_id,color,workdays,bookable,active,sort) VALUES (?,?,?,?,?,?,?)')->execute($vals);
            }
            $pdo->commit();
            json_out(['ok' => true]);
        }

        case 'branches.save': {
            $pdo->beginTransaction();
            foreach ((array)($in['items'] ?? []) as $b) {
                $id = (int)$b['id'];
                $pdo->prepare('UPDATE branches SET name=?,street=?,city=?,phone=?,active=? WHERE id=?')
                    ->execute([trim($b['name']), trim($b['street']), trim($b['city']), trim($b['phone']), empty($b['active']) ? 0 : 1, $id]);
                $pdo->prepare('DELETE FROM hours WHERE branch_id=?')->execute([$id]);
                foreach ((array)($b['hours'] ?? []) as $h) {
                    if (empty($h['open']) || empty($h['close'])) continue;
                    if (!preg_match('/^\d\d:\d\d$/', $h['open']) || !preg_match('/^\d\d:\d\d$/', $h['close']) || $h['close'] <= $h['open'])
                        throw new BookingError('Ungültige Öffnungszeit.');
                    $pdo->prepare('INSERT INTO hours VALUES (?,?,?,?)')->execute([$id, (int)$h['weekday'], $h['open'], $h['close']]);
                }
            }
            $pdo->commit();
            json_out(['ok' => true]);
        }

        case 'settings.save': {
            foreach (['slot_step' => [5, 60], 'lead_minutes' => [0, 2880], 'max_days' => [1, 365], 'cancel_hours' => [0, 168]] as $k => [$min, $max]) {
                if (isset($in[$k])) set_setting($k, (string)max($min, min($max, (int)$in[$k])));
            }
            if (isset($in['notify_salon'])) set_setting('notify_salon', $in['notify_salon'] ? '1' : '0');
            json_out(['ok' => true]);
        }

        case 'password': {
            if (!password_verify((string)($in['old'] ?? ''), (string)setting('admin_hash'))) throw new BookingError('Das aktuelle Passwort stimmt nicht.');
            set_admin_password((string)($in['new'] ?? ''));
            json_out(['ok' => true]);
        }
    }
    json_out(['error' => 'Unbekannte Aktion'], 404);
} catch (BookingError $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_out(['error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[jordan-admin] ' . $e);
    json_out(['error' => 'Technischer Fehler: ' . $e->getMessage()], 500);
}
