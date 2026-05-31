<?php
ob_start();
// dashboard/my-properties.php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_role(['agent','owner','admin']);
$uid  = current_user_id();
$role = current_user_role();

// Fshi pronën
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    check_referrer(); csrf_check();
    $pid = (int)($_POST['prop_id'] ?? 0);
    if ($pid && (can_edit_property($pid) || has_role('admin'))) {
        db_query("UPDATE properties SET is_active=0 WHERE id=?", [$pid]);
        flash_success('Prona u fshi (çaktivizua).');
        log_activity($uid, 'property_deleted', "ID: {$pid}");
    }
    redirect(SITE_URL . '/dashboard/my-properties.php');
}

$where_sql = $role==='admin' ? "1=1" : ($role==='agent' ? "p.agent_id={$uid}" : "p.owner_id={$uid}");
$filter_status = in_array($_GET['status'] ?? '', ['for_sale','for_rent','sold','rented']) ? $_GET['status'] : '';
$filter_type   = in_array($_GET['type'] ?? '', ['apartment','house','villa','commercial','office','land','garage']) ? $_GET['type'] : '';
if ($filter_status) $where_sql .= " AND p.status=" . db()->quote($filter_status);
if ($filter_type)   $where_sql .= " AND p.type=" . db()->quote($filter_type);

$total  = db_count("SELECT COUNT(*) FROM properties p WHERE {$where_sql} AND p.is_active=1");
$paging = paginate($total, 10, (int)($_GET['page'] ?? 1));
$props  = db_rows(
    "SELECT p.*, pi.filename AS primary_img,
       COUNT(DISTINCT a.id) AS appt_count,
       COUNT(DISTINCT f.id) AS fav_count
     FROM properties p
     LEFT JOIN property_images pi ON pi.property_id=p.id AND pi.is_primary=1
     LEFT JOIN appointments a ON a.property_id=p.id AND a.status IN ('pending','confirmed')
     LEFT JOIN favorites f ON f.property_id=p.id
     WHERE {$where_sql} AND p.is_active=1
     GROUP BY p.id
     ORDER BY p.created_at DESC
     LIMIT {$paging['per_page']} OFFSET {$paging['offset']}"
);

$page_title = 'Pronat e Mia - ProEstate';
require dirname(__DIR__) . '/templates/header.php';
?>

<div class="dashboard">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <h1 class="dashboard__title">Pronat e Mia</h1>
        <p class="dashboard__subtitle"><?= $total ?> prona aktive gjithsej</p>
      </div>
      <a href="<?= SITE_URL ?>/dashboard/add-property.php" class="btn btn--primary">+ Shto Pronë të Re</a>
    </div>

    <!-- Filters -->
    <form method="GET" class="toolbar-form">
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">Çdo status</option>
        <option value="for_sale" <?= $filter_status==='for_sale'?'selected':'' ?>>Për Shitje</option>
        <option value="for_rent" <?= $filter_status==='for_rent'?'selected':'' ?>>Me Qira</option>
        <option value="sold"     <?= $filter_status==='sold'?'selected':'' ?>>Shitur</option>
        <option value="rented"   <?= $filter_status==='rented'?'selected':'' ?>>Me Qira (Zënë)</option>
      </select>
      <select name="type" class="form-control" onchange="this.form.submit()">
        <option value="">Çdo lloj</option>
        <?php foreach(['apartment'=>'Apartament','house'=>'Shtëpi','villa'=>'Vilë','commercial'=>'Komerciale','office'=>'Zyrë','land'=>'Truall'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= $filter_type===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($filter_status||$filter_type): ?>
      <a href="?" class="btn btn--sm btn--outline-navy">Pastro</a>
      <?php endif; ?>
    </form>

    <div class="data-table-wrap">
      <?php if (empty($props)): ?>
      <div class="empty-state">
        <h3>Nuk keni prona të listuara</h3>
        <a href="<?= SITE_URL ?>/dashboard/add-property.php" class="btn btn--primary" style="margin-top:16px;">Shto Pronën e Parë</a>
      </div>
      <?php else: ?>
      <table class="data-table">
        <thead><tr>
          <th>Prona</th><th>Lloji / Statusi</th><th>Çmimi</th>
          <th>Takime</th><th>Fav</th><th>Shikime</th><th>Veprime</th>
        </tr></thead>
        <tbody>
          <?php foreach ($props as $p):
            $pimg = $p['primary_img'] ? SITE_URL.'/uploads/properties/'.$p['primary_img'] : SITE_URL.'/assets/images/property-placeholder.svg';
          ?>
          <tr>
            <td style="min-width:240px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <img src="<?= e($pimg) ?>" class="prop-thumb">
                <div>
                  <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" style="font-weight:700;color:var(--navy);font-size:.9rem;"><?= e(mb_substr($p['title'],0,38)) ?></a>
                  <div style="font-size:.75rem;color:var(--gray-500);"><?= e($p['city']) ?><?= $p['neighborhood']?', '.e($p['neighborhood']):'' ?></div>
                  <?php if ($p['is_featured']): ?><span style="font-size:.7rem;color:var(--gold);">Premium</span><?php endif; ?>
                  <span style="display:block;font-size:.7rem;color:<?= $p['approval_status']==='approved'?'var(--green)':($p['approval_status']==='rejected'?'var(--red)':'var(--gold)') ?>;">
                    <?= $p['approval_status']==='approved'?'Aprovuar':($p['approval_status']==='rejected'?'Refuzuar':'Ne pritje') ?>
                  </span>
                </div>
              </div>
            </td>
            <td>
              <div><?= property_type_label($p['type']) ?></div>
              <span class="status-pill status-pill--<?= $p['status']==='for_sale'?'sale':'rent' ?>" style="margin-top:4px;"><?= property_status_label($p['status']) ?></span>
            </td>
            <td style="font-weight:700;color:var(--gold);"><?= format_price((float)$p['price'],$p['price_period']) ?></td>
            <td><?= $p['appt_count'] > 0 ? '<span style="background:var(--green-light);color:var(--green);padding:2px 8px;border-radius:12px;font-size:.8rem;font-weight:700;">'.$p['appt_count'].'</span>' : '<span style="color:var(--gray-400);">-</span>' ?></td>
            <td><?= $p['fav_count'] ?></td>
            <td><?= $p['views'] ?></td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" class="btn-icon" title="Shiko" style="background:var(--gray-100);">Sh</a>
                <a href="<?= SITE_URL ?>/dashboard/add-property.php?id=<?= $p['id'] ?>" class="btn-icon" title="Edito" style="background:var(--gold-pale);">Ed</a>
                <form method="POST" action="" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="delete_property" value="1">
                  <input type="hidden" name="prop_id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn-icon btn--danger" title="Fshi"
                          onclick="return confirm('Fshi pronën: <?= e(addslashes($p['title'])) ?>?')"
                          style="background:var(--red-light);color:var(--red);">Fsh</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?= render_pagination($paging, '?status='.$filter_status.'&type='.$filter_type) ?>
      <?php endif; ?>
    </div>
  </main>
</div>
<script>window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
