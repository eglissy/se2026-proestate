<?php
ob_start();
// admin/index.php - Admin Panel
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_role('admin');

$stats_full = [
    'users'       => db_count("SELECT COUNT(*) FROM users WHERE is_active=1"),
    'properties'  => db_count("SELECT COUNT(*) FROM properties WHERE is_active=1"),
    'appointments'=> db_count("SELECT COUNT(*) FROM appointments"),
    'messages'    => db_count("SELECT COUNT(*) FROM messages"),
    'agents'      => db_count("SELECT COUNT(*) FROM users WHERE role='agent' AND is_active=1"),
    'clients'     => db_count("SELECT COUNT(*) FROM users WHERE role='client' AND is_active=1"),
    'for_sale'    => db_count("SELECT COUNT(*) FROM properties WHERE status='for_sale' AND is_active=1"),
    'for_rent'    => db_count("SELECT COUNT(*) FROM properties WHERE status='for_rent' AND is_active=1"),
    'sold'        => db_count("SELECT COUNT(*) FROM properties WHERE status='sold'"),
    'pending_appt'=> db_count("SELECT COUNT(*) FROM appointments WHERE status='pending'"),
    'total_revenue'=> (float)(db_row("SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE status='completed'")['t'] ?? 0),
    'total_payments'=> db_count("SELECT COUNT(*) FROM payments WHERE status='completed'"),
    'pending_properties'=> db_count("SELECT COUNT(*) FROM properties WHERE approval_status='pending' AND is_active=1"),
    'pending_agents'=> db_count("SELECT COUNT(*) FROM users WHERE role='agent' AND is_active=0"),
];

$recent_users = db_rows(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 10"
);
$recent_props = db_rows(
    "SELECT p.*, pi.filename AS primary_img,
       u.first_name, u.last_name
     FROM properties p
     LEFT JOIN property_images pi ON pi.property_id=p.id AND pi.is_primary=1
     LEFT JOIN users u ON u.id=p.owner_id
     ORDER BY p.created_at DESC LIMIT 8"
);

// Statistikat mujore (6 muajt e fundit)
$monthly = db_rows(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as cnt
     FROM properties WHERE is_active=1
     GROUP BY month ORDER BY month DESC LIMIT 6"
);
$city_stats = db_rows(
    "SELECT city, COUNT(*) AS cnt
     FROM properties
     WHERE is_active=1 AND approval_status='approved'
     GROUP BY city ORDER BY cnt DESC LIMIT 8"
);
$deal_stats = db_rows(
    "SELECT status, COUNT(*) AS cnt
     FROM properties
     WHERE is_active=1
     GROUP BY status"
);

$page_title = 'Admin Panel - ProEstate';
require dirname(__DIR__) . '/templates/header.php';
?>

<div class="dashboard">
  <?php include dirname(__DIR__) . '/dashboard/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header">
      <h1 class="dashboard__title">Admin Panel</h1>
      <p class="dashboard__subtitle">Pasqyra e plotë e platformës ProEstate</p>
    </div>

    <div class="alert alert--warning" style="align-items:center;justify-content:space-between;">
      <span><strong><?= $stats_full['pending_agents'] ?></strong> agjentë në pritje për aprovim.</span>
      <a href="<?= SITE_URL ?>/admin/users.php?status=pending_agents" class="btn btn--sm btn--navy">Aprovo / Refuzo agjentë</a>
    </div>

    <!-- Main Stats Grid -->
    <div class="stats-grid">
      <?php $admin_stats = [
        ['PR','Prona Totale',     $stats_full['properties'],   'gold'],
        ['US','Perdorues',        $stats_full['users'],         'navy'],
        ['AG','Agjentë',          $stats_full['agents'],        'green'],
        ['AA','Agjentë në Pritje',$stats_full['pending_agents'], 'red'],
        ['KL','Klientë',          $stats_full['clients'],       'blue'],
        ['TK','Takime Totale',    $stats_full['appointments'],  'gold'],
        ['NP','Takime Priten',    $stats_full['pending_appt'],  'red'],
        ['AP','Prona per Aprovim',$stats_full['pending_properties'], 'red'],
        ['SH','Për Shitje',       $stats_full['for_sale'],      'navy'],
        ['QR','Me Qira',          $stats_full['for_rent'],      'green'],
        ['€','Të Ardhura PayPal', '€'.number_format($stats_full['total_revenue'],0), 'gold'],
        ['PP','Pagesa Totale',    $stats_full['total_payments'], 'green'],
      ]; foreach ($admin_stats as $s): ?>
      <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--<?= $s[3] ?>" style="font-size:1.2rem;"><?= $s[0] ?></div>
        <div>
          <div class="stat-card__value"><?= $s[2] ?></div>
          <div class="stat-card__label"><?= $s[1] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="dashboard-grid">

      <!-- Recent Users -->
      <div class="data-table-wrap">
        <div class="data-table-head">
          <h3>Perdorues të Fundit</h3>
          <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn--sm btn--outline-navy">Të gjithë</a>
        </div>
        <table class="data-table">
          <thead><tr><th>Perdoruesi</th><th>Roli</th><th>Data</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recent_users as $u): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <img src="<?= get_avatar_url($u['avatar']) ?>" style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
                  <div>
                    <div style="font-weight:600;font-size:.875rem;"><?= e($u['first_name'].' '.$u['last_name']) ?></div>
                    <div style="font-size:.72rem;color:var(--gray-500);"><?= e($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td><span class="role-badge"><?= role_label($u['role']) ?></span></td>
              <td style="font-size:.78rem;color:var(--gray-500);"><?= format_date($u['created_at']) ?></td>
              <td>
                <label style="cursor:pointer;display:flex;align-items:center;gap:4px;">
                  <input type="checkbox" class="toggle-active" data-id="<?= $u['id'] ?>"
                         <?= $u['is_active']?'checked':'' ?> <?= (int) $u['id']===current_user_id()?'disabled':'' ?>>
                  <span style="font-size:.78rem;"><?= $u['is_active']?'Aktiv':'Çaktiv' ?></span>
                </label>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Recent Properties -->
      <div class="data-table-wrap">
        <div class="data-table-head">
          <h3>Prona të Fundit</h3>
          <a href="<?= SITE_URL ?>/admin/properties.php" class="btn btn--sm btn--outline-navy">Të gjitha</a>
        </div>
        <table class="data-table">
          <thead><tr><th>Prona</th><th>Çmimi</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($recent_props as $p):
              $pimg = $p['primary_img'] ? SITE_URL.'/uploads/properties/'.$p['primary_img'] : SITE_URL.'/assets/images/property-placeholder.svg';
            ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <img src="<?= e($pimg) ?>" class="prop-thumb">
                  <div>
                    <div style="font-weight:600;font-size:.825rem;"><?= e(mb_substr($p['title'],0,28)) ?></div>
                    <div style="font-size:.72rem;color:var(--gray-500);"><?= e($p['city']) ?></div>
                  </div>
                </div>
              </td>
              <td style="font-weight:600;color:var(--gold);white-space:nowrap;"><?= format_price((float)$p['price'],$p['price_period']) ?></td>
              <td><span class="status-pill status-pill--<?= $p['status']==='for_sale'?'sale':'rent' ?>"><?= property_status_label($p['status']) ?></span></td>
              <td>
                <div style="display:flex;gap:4px;">
                  <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" class="btn-icon" style="background:var(--gray-100);" title="Shiko">Sh</a>
                  <a href="<?= SITE_URL ?>/dashboard/add-property.php?id=<?= $p['id'] ?>" class="btn-icon" style="background:var(--gold-pale);" title="Edito">Ed</a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Monthly Listings -->
    <div class="data-table-wrap" style="margin-top:24px;">
      <div class="data-table-head"><h3>Listimi Mujor i Pronave</h3></div>
      <div style="padding:20px;display:flex;align-items:flex-end;gap:12px;height:160px;">
        <?php
        $max = max(array_column($monthly, 'cnt') ?: [1]);
        $months_al = ['01'=>'Jan','02'=>'Shk','03'=>'Mar','04'=>'Pri','05'=>'Maj','06'=>'Qer','07'=>'Kor','08'=>'Gus','09'=>'Sht','10'=>'Tet','11'=>'Nën','12'=>'Dhj'];
        foreach (array_reverse($monthly) as $m):
            $h = max(20, round(($m['cnt'] / $max) * 100));
            $mo = explode('-', $m['month'])[1];
        ?>
        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;">
          <span style="font-size:.75rem;font-weight:700;color:var(--navy);"><?= $m['cnt'] ?></span>
          <div style="width:100%;height:<?= $h ?>px;background:var(--gold);border-radius:4px 4px 0 0;opacity:.85;"></div>
          <span style="font-size:.72rem;color:var(--gray-500);"><?= $months_al[$mo]??$mo ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="dashboard-grid" style="margin-top:24px;">
      <div class="data-table-wrap">
        <div class="data-table-head"><h3>Prona sipas Qytetit</h3></div>
        <table class="data-table">
          <thead><tr><th>Qyteti</th><th>Numri</th></tr></thead>
          <tbody>
            <?php foreach ($city_stats as $c): ?>
            <tr><td><?= e($c['city']) ?></td><td><strong><?= (int)$c['cnt'] ?></strong></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="data-table-wrap">
        <div class="data-table-head"><h3>Shitje / Qira</h3></div>
        <table class="data-table">
          <thead><tr><th>Statusi</th><th>Numri</th></tr></thead>
          <tbody>
            <?php foreach ($deal_stats as $d): ?>
            <tr><td><?= property_status_label($d['status']) ?></td><td><strong><?= (int)$d['cnt'] ?></strong></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<script>
window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
// Toggle user active
document.querySelectorAll('.toggle-active').forEach(function(chk) {
  chk.addEventListener('change', function() {
    $.post('<?= SITE_URL ?>/api/admin-actions.php', {
      action: 'toggle_user_active',
      id: this.dataset.id,
      value: this.checked ? 1 : 0,
      _proesta_csrf: window.CSRF_TOKEN
    }, function(r) {
      if (!r.success) alert(r.message);
    }, 'json');
  });
});
</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
