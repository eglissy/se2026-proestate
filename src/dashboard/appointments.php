<?php
ob_start();
// dashboard/appointments.php - Menaxhimi i takimeve
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_auth();
$uid  = current_user_id();
$role = current_user_role();

// Update status (vetëm agent/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    check_referrer();
    csrf_check();

    $appt_id   = (int)($_POST['appt_id'] ?? 0);
    $new_status= sanitize($_POST['new_status'] ?? '');

    if (!in_array($new_status, ['pending','confirmed','cancelled','completed'])) {
        flash_error('Status i pavlefshëm.');
    } elseif (in_array($role, ['agent','admin'])) {
        $appt = db_row(
            "SELECT a.*, p.title AS prop_title, p.address, p.city,
               uc.email AS c_email, uc.first_name AS c_first, uc.last_name AS c_last, uc.phone AS c_phone,
               ua.first_name AS a_first, ua.last_name AS a_last, ua.phone AS a_phone
             FROM appointments a
             JOIN properties p ON a.property_id = p.id
             JOIN users uc ON uc.id = a.client_id
             LEFT JOIN users ua ON ua.id = a.agent_id
             WHERE a.id=?", [$appt_id]
        );

        if ($appt && ($role === 'admin' || (int) $appt['agent_id'] === $uid)) {
            db_query("UPDATE appointments SET status=?, notes=? WHERE id=?",
                [$new_status, sanitize($_POST['notes'] ?? ''), $appt_id]);

            // Dërgo email nëse konfirmohet
            if ($new_status === 'confirmed') {
                $client = ['email'=>$appt['c_email'],'first_name'=>$appt['c_first'],'last_name'=>$appt['c_last'],'phone'=>$appt['c_phone']];
                $agent  = ['first_name'=>$appt['a_first'],'last_name'=>$appt['a_last'],'phone'=>$appt['a_phone']];
                $prop   = ['title'=>$appt['prop_title'],'address'=>$appt['address'],'city'=>$appt['city']];
                send_appointment_email($appt, $prop, $client, $agent, 'confirmation');
            }

            flash_success('Statusi i takimit u ndryshua!');
            log_activity($uid, 'appointment_status_changed', "Takimi #{$appt_id}: {$new_status}");
        }
    } elseif ($role === 'client') {
        // Klienti mund të anulojë vetëm takimet e tij
        $appt = db_row("SELECT * FROM appointments WHERE id=? AND client_id=?", [$appt_id, $uid]);
        if ($appt && $new_status === 'cancelled') {
            db_query("UPDATE appointments SET status='cancelled' WHERE id=?", [$appt_id]);
            flash_success('Takimi u anulua.');
        }
    }
    redirect(SITE_URL . '/dashboard/appointments.php');
}

// Filtri
$allowed_statuses = ['pending','confirmed','cancelled','completed'];
$filter_status = sanitize($_GET['status'] ?? '');
$filter_status = in_array($filter_status, $allowed_statuses, true) ? $filter_status : '';
$filter_date   = sanitize($_GET['date'] ?? '');

// Build query sipas rolit
$where  = [];
$params = [];
if ($role === 'client') {
    $where[] = "a.client_id = ?"; $params[] = $uid;
} elseif ($role === 'agent') {
    $where[] = "a.agent_id = ?";  $params[] = $uid;
} elseif ($role === 'owner') {
    $where[] = "p.owner_id = ?";  $params[] = $uid;
}
// admin vep gjithë takimet

if ($filter_status) { $where[] = "a.status = ?"; $params[] = $filter_status; }
if ($filter_date)   { $where[] = "a.scheduled_date = ?"; $params[] = $filter_date; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$appointments = db_rows(
    "SELECT a.*,
       p.title AS prop_title, p.city, p.id AS prop_id,
       uc.first_name AS c_first, uc.last_name AS c_last, uc.phone AS c_phone, uc.email AS c_email,
       ua.first_name AS a_first, ua.last_name AS a_last, ua.phone AS a_phone
     FROM appointments a
     JOIN properties p ON a.property_id = p.id
     JOIN users uc ON uc.id = a.client_id
     LEFT JOIN users ua ON ua.id = a.agent_id
     {$where_sql}
     ORDER BY a.scheduled_date DESC, a.scheduled_time DESC",
    $params
);

$page_title = 'Takimet - ProEstate';
require dirname(__DIR__) . '/templates/header.php';
?>

<div class="dashboard">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header">
      <h1 class="dashboard__title">Takimet</h1>
      <p class="dashboard__subtitle"><?= count($appointments) ?> takime gjithsej</p>
    </div>

    <!-- Filters -->
    <form method="GET" action="" class="toolbar-form">
      <div class="form-group">
        <label>Statusi</label>
        <select name="status" class="form-control" onchange="this.form.submit()">
          <option value="">Të gjitha</option>
          <?php foreach(['pending'=>'Në Pritje','confirmed'=>'Konfirmuar','cancelled'=>'Anuluar','completed'=>'Kryer'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $filter_status===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Data</label>
        <input type="date" name="date" class="form-control" value="<?= e($filter_date) ?>" onchange="this.form.submit()">
      </div>
      <?php if ($filter_status || $filter_date): ?>
      <a href="?" class="btn btn--sm btn--outline-navy">Pastro</a>
      <?php endif; ?>
    </form>

    <div class="data-table-wrap">
      <?php if (empty($appointments)): ?>
      <div class="table-empty">
        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
          <rect x="3" y="4" width="18" height="18" rx="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/>
          <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <p>Asnjë takim i gjetur</p>
      </div>
      <?php else: ?>
      <table class="data-table">
        <thead><tr>
          <th>Prona</th>
          <th>Klienti</th>
          <th>Data & Ora</th>
          <th>Statusi</th>
          <th>Shënime</th>
          <?php if (in_array($role, ['agent','admin'])): ?><th>Veprime</th><?php endif; ?>
        </tr></thead>
        <tbody>
          <?php foreach ($appointments as $a): ?>
          <tr id="appt-<?= $a['id'] ?>">
            <td>
              <a href="<?= SITE_URL ?>/property.php?id=<?= $a['prop_id'] ?>" style="font-weight:600;color:var(--navy);"><?= e(mb_substr($a['prop_title'],0,30)) ?>...</a>
              <div style="font-size:.75rem;color:var(--gray-500);"><?= e($a['city']) ?></div>
            </td>
            <td>
              <div style="font-weight:600;"><?= e($a['c_first'].' '.$a['c_last']) ?></div>
              <div style="font-size:.75rem;color:var(--gray-500);"><?= e($a['c_phone'] ?? '') ?></div>
            </td>
            <td>
              <div style="font-weight:600;"><?= format_date($a['scheduled_date']) ?></div>
              <div style="font-size:.78rem;color:var(--gray-600);"><?= date('H:i', strtotime($a['scheduled_time'])) ?></div>
            </td>
            <td><span class="status-pill status-pill--<?= $a['status'] ?>"><?= appointment_status_label($a['status']) ?></span></td>
            <td style="max-width:160px;font-size:.8rem;color:var(--gray-600);">
              <?= e(mb_substr($a['client_notes']??$a['notes']??'-',0,60)) ?>
            </td>
            <?php if (in_array($role, ['agent','admin'])): ?>
            <td>
              <?php if ($a['status'] === 'pending'): ?>
              <form method="POST" action="" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="new_status" value="confirmed">
                <input type="hidden" name="notes" value="Konfirmuar nga agjenti.">
                <button type="submit" class="btn btn--sm" style="background:var(--green-light);color:var(--green);margin-bottom:4px;">Konfirmo</button>
              </form>
              <?php endif; ?>
              <?php if (in_array($a['status'], ['pending','confirmed'])): ?>
              <form method="POST" action="" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="new_status" value="cancelled">
                <input type="hidden" name="notes" value="">
                <button type="submit" class="btn btn--sm btn--danger" onclick="return confirm('Anulo takimin?')">Anulo</button>
              </form>
              <?php endif; ?>
              <?php if ($a['status'] === 'confirmed'): ?>
              <form method="POST" action="" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="new_status" value="completed">
                <input type="hidden" name="notes" value="">
                <button type="submit" class="btn btn--sm" style="background:var(--blue-light);color:var(--blue);">Krye</button>
              </form>
              <?php endif; ?>
            </td>
            <?php endif; ?>
            <?php if ($role === 'client' && in_array($a['status'], ['pending','confirmed'])): ?>
            <td>
              <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="new_status" value="cancelled">
                <button type="submit" class="btn btn--sm btn--danger" onclick="return confirm('Anulo takimin?')">Anulo</button>
              </form>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </main>
</div>
<script>window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
