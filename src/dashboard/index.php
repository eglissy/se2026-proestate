<?php
ob_start();
// dashboard/index.php - Paneli kryesor
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_auth();
$user = current_user();
$uid  = current_user_id();
$role = current_user_role();

// Stats per rol
if ($role === 'admin') {
    $stats = [
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>','label'=>'Prona Totale',       'value'=> db_count("SELECT COUNT(*) FROM properties WHERE is_active=1"),         'color'=>'gold'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>','label'=>'Perdorues Totalë',    'value'=> db_count("SELECT COUNT(*) FROM users WHERE is_active=1"),              'color'=>'navy'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>','label'=>'Takime Sot',           'value'=> db_count("SELECT COUNT(*) FROM appointments WHERE scheduled_date=CURDATE()"), 'color'=>'green'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>','label'=>'Mesazhe të Palexuara', 'value'=> db_count("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0",[$uid]), 'color'=>'red'],
    ];
} elseif ($role === 'agent') {
    $stats = [
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>','label'=>'Pronat e Mia',    'value'=> db_count("SELECT COUNT(*) FROM properties WHERE agent_id=? AND is_active=1",[$uid]), 'color'=>'gold'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>','label'=>'Takime Aktive',   'value'=> db_count("SELECT COUNT(*) FROM appointments WHERE agent_id=? AND status IN ('pending','confirmed')",[$uid]), 'color'=>'navy'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>','label'=>'Vlerësimet',        'value'=> db_count("SELECT COUNT(*) FROM reviews WHERE agent_id=?",[$uid]),    'color'=>'green'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>','label'=>'Mesazhe të reja',  'value'=> db_count("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0",[$uid]), 'color'=>'red'],
    ];
} elseif ($role === 'owner') {
    $stats = [
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>','label'=>'Pronat e Mia',       'value'=> db_count("SELECT COUNT(*) FROM properties WHERE owner_id=? AND is_active=1",[$uid]), 'color'=>'gold'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>','label'=>'Takime Prona',        'value'=> db_count("SELECT COUNT(*) FROM appointments a JOIN properties p ON a.property_id=p.id WHERE p.owner_id=? AND a.status IN ('pending','confirmed')",[$uid]),'color'=>'navy'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>','label'=>'Prona Preferuara',    'value'=> db_count("SELECT COUNT(*) FROM favorites WHERE user_id=?",[$uid]),   'color'=>'green'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>','label'=>'Mesazhe të reja',     'value'=> db_count("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0",[$uid]),'color'=>'red'],
    ];
} else {
    $stats = [
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>','label'=>'Preferuarat',          'value'=> db_count("SELECT COUNT(*) FROM favorites WHERE user_id=?",[$uid]),    'color'=>'gold'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>','label'=>'Takimet e Mia',        'value'=> db_count("SELECT COUNT(*) FROM appointments WHERE client_id=?",[$uid]),'color'=>'navy'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>','label'=>'Pagesa Kryera',         'value'=> db_count("SELECT COUNT(*) FROM payments WHERE user_id=? AND status='completed'",[$uid]),'color'=>'green'],
        ['icon'=>'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>','label'=>'Mesazhe të reja',      'value'=> db_count("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0",[$uid]),'color'=>'red'],
    ];
}

// Takimet e ardhshme
if ($role === 'client') {
    $appointments = db_rows(
        "SELECT a.*, p.title AS prop_title, p.city,
           u.first_name AS agent_first, u.last_name AS agent_last
         FROM appointments a
         JOIN properties p ON a.property_id = p.id
         LEFT JOIN users u ON a.agent_id = u.id
         WHERE a.client_id = ? AND a.scheduled_date >= CURDATE()
         ORDER BY a.scheduled_date, a.scheduled_time LIMIT 5",
        [$uid]
    );
} elseif ($role === 'agent') {
    $appointments = db_rows(
        "SELECT a.*, p.title AS prop_title, p.city,
           u.first_name AS client_first, u.last_name AS client_last, u.phone AS client_phone
         FROM appointments a
         JOIN properties p ON a.property_id = p.id
         JOIN users u ON a.client_id = u.id
         WHERE a.agent_id = ? AND a.scheduled_date >= CURDATE()
         ORDER BY a.scheduled_date, a.scheduled_time LIMIT 5",
        [$uid]
    );
} elseif ($role === 'owner') {
    $appointments = db_rows(
        "SELECT a.*, p.title AS prop_title, p.city,
           uc.first_name AS client_first, uc.last_name AS client_last
         FROM appointments a
         JOIN properties p ON a.property_id = p.id
         JOIN users uc ON a.client_id = uc.id
         WHERE p.owner_id = ? AND a.scheduled_date >= CURDATE()
         ORDER BY a.scheduled_date, a.scheduled_time LIMIT 5",
        [$uid]
    );
} else {
    $appointments = [];
}

// Pronat e fundit (për owner/agent)
$my_props = [];
if (in_array($role, ['agent','owner','admin'])) {
    $where_prop = $role === 'agent' ? "agent_id = {$uid}" : ($role === 'owner' ? "owner_id = {$uid}" : "1=1");
    $my_props = db_rows(
        "SELECT p.*, pi.filename AS primary_img FROM properties p
         LEFT JOIN property_images pi ON pi.property_id=p.id AND pi.is_primary=1
         WHERE {$where_prop} AND p.is_active=1
         ORDER BY p.created_at DESC LIMIT 5"
    );
}

// Mesazhet e fundit
$recent_msgs = db_rows(
    "SELECT m.*, u.first_name, u.last_name, u.avatar FROM messages m
     JOIN users u ON u.id = m.sender_id
     WHERE m.receiver_id = ? ORDER BY m.created_at DESC LIMIT 5",
    [$uid]
);

$page_title = 'Paneli im - ProEstate';
require __DIR__ . '/../templates/header.php';
?>

<div class="dashboard">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header">
      <h1 class="dashboard__title">Mirë se erdhët, <?= e($user['first_name']) ?>!</h1>
      <p class="dashboard__subtitle"><?= format_date(date('Y-m-d'), 'l, d F Y') ?> · <?= role_label($role) ?></p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <?php foreach ($stats as $s): ?>
      <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--<?= $s['color'] ?>"><?= $s['icon'] ?></div>
        <div>
          <div class="stat-card__value"><?= $s['value'] ?></div>
          <div class="stat-card__label"><?= $s['label'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="dashboard-grid">

      <!-- Takimet -->
      <div class="data-table-wrap">
        <div class="data-table-head">
          <h3>Takimet e Ardhshme</h3>
          <a href="<?= SITE_URL ?>/dashboard/appointments.php" class="btn btn--sm btn--outline-navy">Shiko të gjitha</a>
        </div>
        <?php if (empty($appointments)): ?>
        <div class="table-empty">
          <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <p>Asnjë takim i ardhshëm</p>
        </div>
        <?php else: ?>
        <table class="data-table">
          <thead><tr>
            <th>Prona</th><th>Data</th><th>Ora</th><th>Statusi</th>
          </tr></thead>
          <tbody>
            <?php foreach ($appointments as $a): ?>
            <tr>
              <td>
                <a href="<?= SITE_URL ?>/property.php?id=<?= $a['property_id'] ?>" style="font-weight:600;color:var(--dark);"><?= e(mb_substr($a['prop_title'],0,30)) ?>...</a>
                <div style="font-size:.75rem;color:var(--text-3);"><?= e($a['city']) ?></div>
              </td>
              <td><?= format_date($a['scheduled_date']) ?></td>
              <td><?= date('H:i', strtotime($a['scheduled_time'])) ?></td>
              <td><span class="status-pill status-pill--<?= $a['status'] ?>"><?= appointment_status_label($a['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <!-- Mesazhet -->
      <div class="data-table-wrap">
        <div class="data-table-head">
          <h3>Mesazhe të Reja</h3>
          <a href="<?= SITE_URL ?>/dashboard/messages.php" class="btn btn--sm btn--outline-navy">Shiko të gjitha</a>
        </div>
        <?php if (empty($recent_msgs)): ?>
        <div class="table-empty">
          <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          <p>Asnjë mesazh i ri</p>
        </div>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>Nga</th><th>Mesazhi</th><th>Koha</th></tr></thead>
          <tbody>
            <?php foreach ($recent_msgs as $m): ?>
            <tr <?= !$m['is_read']?'style="font-weight:700;"':'' ?>>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <img src="<?= get_avatar_url($m['avatar']) ?>" style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
                  <span><?= e($m['first_name'].' '.$m['last_name']) ?></span>
                </div>
              </td>
              <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <a href="<?= SITE_URL ?>/dashboard/messages.php#msg-<?= $m['id'] ?>"><?= e(mb_substr($m['subject']??$m['content'],0,40)) ?></a>
              </td>
              <td style="font-size:.78rem;color:var(--text-3);white-space:nowrap;"><?= time_ago($m['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Pending Agents (admin only) -->
    <?php if ($role === 'admin'):
      $pending_agents = db_rows(
        "SELECT id, first_name, last_name, email, phone, created_at
         FROM users WHERE role='agent' AND is_active=0 ORDER BY created_at DESC"
      );
      if (!empty($pending_agents)): ?>
    <div class="data-table-wrap dashboard-section" style="border-left:3px solid var(--gold);">
      <div class="data-table-head">
        <h3 style="display:flex;align-items:center;gap:8px;">
          Agjentë në Pritje të Aprovimit
          <span style="background:var(--gold);color:var(--dark);font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:var(--r-full);"><?= count($pending_agents) ?></span>
        </h3>
        <p style="font-size:.81rem;color:var(--text-3);margin:0;">Rishikoni dhe aprovoni ose refuzoni kërkesat e reja për agjentë.</p>
      </div>
      <table class="data-table">
        <thead><tr><th>Emri</th><th>Email</th><th>Telefon</th><th>Regjistruar</th><th>Veprime</th></tr></thead>
        <tbody>
          <?php foreach ($pending_agents as $pa): ?>
          <tr id="agent-row-<?= $pa['id'] ?>">
            <td style="font-weight:600;"><?= e($pa['first_name'].' '.$pa['last_name']) ?></td>
            <td><?= e($pa['email']) ?></td>
            <td><?= e($pa['phone'] ?: '-') ?></td>
            <td style="font-size:.8rem;color:var(--text-3);"><?= time_ago($pa['created_at']) ?></td>
            <td>
              <div style="display:flex;gap:6px;">
                <button onclick="approveAgent(<?= $pa['id'] ?>, this)"
                        class="btn btn--sm btn--primary" style="font-size:.78rem;">
                  Aprovo
                </button>
                <button onclick="rejectAgent(<?= $pa['id'] ?>, this)"
                        class="btn btn--sm btn--danger" style="font-size:.78rem;">
                  Refuzo
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; endif; ?>
    <?php if (!empty($my_props)): ?>
    <div class="data-table-wrap dashboard-section">
      <div class="data-table-head">
        <h3>Pronat e Mia</h3>
        <a href="<?= SITE_URL ?>/dashboard/my-properties.php" class="btn btn--sm btn--outline-navy">Shiko të gjitha</a>
      </div>
      <table class="data-table">
        <thead><tr><th>Prona</th><th>Lloji</th><th>Çmimi</th><th>Statusi</th><th>Shikime</th><th>Veprime</th></tr></thead>
        <tbody>
          <?php foreach ($my_props as $p):
            $pimg = $p['primary_img'] ? SITE_URL.'/uploads/properties/'.$p['primary_img'] : SITE_URL.'/assets/images/property-placeholder.svg';
          ?>
          <tr>
            <td style="min-width:200px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <img src="<?= e($pimg) ?>" class="prop-thumb" alt="">
                <div>
                  <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" style="font-weight:600;color:var(--dark);"><?= e(mb_substr($p['title'],0,35)) ?></a>
                  <div style="font-size:.75rem;color:var(--text-3);"><?= e($p['city']) ?></div>
                </div>
              </div>
            </td>
            <td><?= property_type_label($p['type']) ?></td>
            <td><?= format_price((float)$p['price'],$p['price_period']) ?></td>
            <td><span class="status-pill status-pill--<?= $p['status']==='for_sale'?'sale':'rent' ?>"><?= property_status_label($p['status']) ?></span></td>
            <td><?= $p['views'] ?></td>
            <td>
              <div style="display:flex;gap:6px;">
                <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" class="btn-action btn-action--view" title="Shiko">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
                <a href="<?= SITE_URL ?>/dashboard/edit-property.php?id=<?= $p['id'] ?>" class="btn-action btn-action--edit" title="Edito">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>

<script>
window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
const ADMIN_URL   = '<?= SITE_URL ?>/api/admin-actions.php';

function postAdminAction(data) {
  data._proesta_csrf = window.CSRF_TOKEN || '';
  return fetch(ADMIN_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams(data)
  }).then(function(response) {
    return response.json().catch(function() {
      return { success: false, message: 'Përgjigje e pavlefshme nga serveri.' };
    });
  });
}

function approveAgent(id, btn) {
  if (!confirm('Aprovo këtë agjent?')) return;
  btn.disabled = true; btn.textContent = 'Duke punuar...';
  postAdminAction({ action:'approve_agent', id:id }).then(function(r) {
    if (r.success) {
      const row = document.getElementById('agent-row-' + id);
      if (row) { row.style.opacity = '.4'; setTimeout(function() { window.location.reload(); }, 450); }
    } else {
      alert(r.message || 'Veprimi deshtoi.');
      btn.disabled = false; btn.textContent = 'Aprovo';
    }
  }).catch(function() {
    alert('Gabim rrjeti.');
    btn.disabled = false; btn.textContent = 'Aprovo';
  });
}

function rejectAgent(id, btn) {
  if (!confirm('Refuzo dhe fshi këtë kërkesë?')) return;
  btn.disabled = true; btn.textContent = 'Duke punuar...';
  postAdminAction({ action:'reject_agent', id:id }).then(function(r) {
    if (r.success) {
      const row = document.getElementById('agent-row-' + id);
      if (row) { row.style.opacity = '.4'; setTimeout(function() { window.location.reload(); }, 450); }
    } else {
      alert(r.message || 'Veprimi deshtoi.');
      btn.disabled = false; btn.textContent = 'Refuzo';
    }
  }).catch(function() {
    alert('Gabim rrjeti.');
    btn.disabled = false; btn.textContent = 'Refuzo';
  });
}
</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
