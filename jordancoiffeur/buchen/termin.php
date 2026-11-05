<?php
/** Kunde: eigenen Termin ansehen und absagen (Link aus der Bestätigungs-E-Mail). */
declare(strict_types=1);
require __DIR__ . '/../lib/layout.php';

header('X-Robots-Tag: noindex');
$token = (string)($_GET['t'] ?? $_POST['t'] ?? '');
$b = booking_by_token($token);
$msg = null;

if ($b && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (can_customer_cancel($b)) {
        cancel_booking((int)$b['id']);
        $b = booking_by_token($token);
        $msg = 'Dein Termin wurde abgesagt. Danke, dass du Bescheid gegeben hast!';
    } else {
        $msg = 'Online kann dieser Termin nicht mehr abgesagt werden. Bitte ruf uns an.';
    }
}

page_head('Dein Termin | Jordan Coiffeur & Barbershop', 'Termin ansehen oder absagen.', '', ['css' => ['/assets/css/booking.css?v=2']]);
?>
<div class="wrap page-head" style="padding-bottom:8px"><p class="label">Dein Termin</p></div>
<section style="padding-top:8px">
  <div class="wrap" style="max-width:640px">
    <div class="panel">
    <?php if (!$b): ?>
      <h2>Termin nicht gefunden</h2>
      <p>Der Link ist ungültig. Bitte ruf uns an: <a href="tel:+41712201000">071 220 10 00</a>.</p>
    <?php else: ?>
      <?php if ($msg): ?><p class="form-error" style="background:var(--paper);border-color:var(--gold);color:var(--ink)"><?= h($msg) ?></p><?php endif; ?>
      <h2><?= h($b['service_name']) ?></h2>
      <dl class="tdetails">
        <dt>Wann</dt><dd><?= h(fmt_date_de($b['start'])) ?></dd>
        <dt>Barber</dt><dd><?= h($b['staff_name']) ?></dd>
        <dt>Filiale</dt><dd><?= h($b['street']) ?>, <?= h($b['city']) ?></dd>
        <dt>Name</dt><dd><?= h($b['name']) ?></dd>
        <dt>Status</dt><dd><?= $b['status'] === 'confirmed' ? 'Bestätigt' : ($b['status'] === 'cancelled' ? 'Abgesagt' : 'Vergangen') ?></dd>
      </dl>
      <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:28px">
      <?php if (can_customer_cancel($b)): ?>
        <form method="post" onsubmit="return confirm('Termin wirklich absagen?')">
          <input type="hidden" name="t" value="<?= h($token) ?>"><input type="hidden" name="action" value="cancel">
          <button class="btn outline" type="submit">Termin absagen</button>
        </form>
      <?php elseif ($b['status'] === 'confirmed'): ?>
        <p class="muted">Kurzfristige Absagen bitte telefonisch: <a href="tel:+41<?= h(preg_replace('/\D/', '', ltrim($b['branch_phone'], '0'))) ?>"><?= h($b['branch_phone']) ?></a></p>
      <?php endif; ?>
        <a class="btn" href="/buchen/">Neuen Termin buchen</a>
      </div>
    <?php endif; ?>
    </div>
  </div>
</section>
<?php page_foot();
