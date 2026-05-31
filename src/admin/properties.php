<?php
ob_start();
// admin/properties.php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_role('admin');

// Toggle featured
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer(); csrf_check();
    if (isset($_POST['toggle_featured'])) {
        $pid = (int)($_POST['prop_id'] ?? 0);
        $val = (int)($_POST['is_featured'] ?? 0);
        db_query("UPDATE properties SET is_featured=? WHERE id=?", [$val, $pid]);
        flash_success('Premium statusi u ndryshua.');
    }
    if (isset($_POST['toggle_active'])) {
        $pid = (int)($_POST['prop_id'] ?? 0);
        $val = (int)($_POST['is_active'] ?? 0);
        db_query("UPDATE properties SET is_active=? WHERE id=?", [$val, $pid]);
        flash_success('Statusi aktiv u ndryshua.');
    }
    if (isset($_POST['approve_property'])) {
        $pid = (int)($_POST['prop_id'] ?? 0);
        db_query("UPDATE properties SET approval_status='approved', is_verified=1, approved_at=NOW(), approved_by=? WHERE id=?", [current_user_id(), $pid]);
        log_activity(current_user_id(), 'property_approved', "ID: {$pid}", get_client_ip());
        flash_success('Prona u aprovua dhe u verifikua.');
    }
    if (isset($_POST['reject_property'])) {
        $pid = (int)($_POST['prop_id'] ?? 0);
        db_query("UPDATE properties SET approval_status='rejected', is_verified=0 WHERE id=?", [$pid]);
        log_activity(current_user_id(), 'property_rejected', "ID: {$pid}", get_client_ip());
        flash_success('Prona u refuzua.');
    }
    redirect(SITE_URL . '/admin/properties.php');
}

$q       = sanitize($_GET['q'] ?? '');
$fstatus = in_array($_GET['status'] ?? '', ['for_sale','for_rent','sold','rented']) ? $_GET['status'] : '';
$ftype   = in_array($_GET['type'] ?? '', ['apartment','house','villa','commercial','office','land','garage']) ? $_GET['type'] : '';
$approval= in_array($_GET['approval'] ?? '', ['pending','approved','rejected']) ? $_GET['approval'] : '';
$where   = "1=1";
$params  = [];
if ($q)       { $where .= " AND (p.title LIKE ? OR p.city LIKE ?)"; $params = array_merge($params, ["%{$q}%","%{$q}%"]); }
if ($fstatus) { $where .= " AND p.status=?";   $params[] = $fstatus; }
if ($ftype)   { $where .= " AND p.type=?";     $params[] = $ftype; }
if ($approval){ $where .= " AND p.approval_status=?"; $params[] = $approval; }

$pending_properties_count = db_count("SELECT COUNT(*) FROM properties WHERE approval_status='pending' AND is_active=1");
$total  = db_count("SELECT COUNT(*) FROM properties p WHERE {$where}", $params);
$paging = paginate($total, 15, (int)($_GET['page']??1));
$props  = db_rows(
    "SELECT p.*, pi.filename AS primary_img,
       ow.first_name AS o_first, ow.last_name AS o_last,
       ag.first_name AS a_first, ag.last_name AS a_last
     FROM properties p
     LEFT JOIN property_images pi ON pi.property_id=p.id AND pi.is_primary=1
     LEFT JOIN users ow ON ow.id=p.owner_id
     LEFT JOIN users ag ON ag.id=p.agent_id
     WHERE {$where}
     ORDER BY p.created_at DESC
     LIMIT {$paging['per_page']} OFFSET {$paging['offset']}",
    $params
);

$page_title = 'Pronat - Admin ProEstate';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="dashboard">
  <?php include dirname(__DIR__) . '/dashboard/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <h1 class="dashboard__title">Të Gjitha Pronat</h1>
        <p class="dashboard__subtitle"><?= $total ?> prona gjithsej</p>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= SITE_URL ?>/admin/properties.php?approval=pending" class="btn btn--navy">
          Pronat ne pritje<?= $pending_properties_count ? ' ('.$pending_properties_count.')' : '' ?>
        </a>
        <a href="<?= SITE_URL ?>/dashboard/add-property.php" class="btn btn--primary">+ Shto Prone</a>
      </div>
    </div>

    <form method="GET" class="toolbar-form">
      <input type="text" name="q" class="form-control" value="<?= e($q) ?>"
             placeholder="Kërko titull, qytet...">
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">Çdo status</option>
        <option value="for_sale" <?= $fstatus==='for_sale'?'selected':'' ?>>Për Shitje</option>
        <option value="for_rent" <?= $fstatus==='for_rent'?'selected':'' ?>>Me Qira</option>
        <option value="sold"     <?= $fstatus==='sold'?'selected':'' ?>>Shitur</option>
        <option value="rented"   <?= $fstatus==='rented'?'selected':'' ?>>Zënë</option>
      </select>
      <select name="type" class="form-control" onchange="this.form.submit()">
        <option value="">Çdo lloj</option>
        <?php foreach(['apartment'=>'Apartament','house'=>'Shtëpi','villa'=>'Vilë','commercial'=>'Komerciale','office'=>'Zyrë','land'=>'Truall'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= $ftype===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <select name="approval" class="form-control" onchange="this.form.submit()">
        <option value="">Cdo aprovim</option>
        <option value="pending" <?= $approval==='pending'?'selected':'' ?>>Ne pritje</option>
        <option value="approved" <?= $approval==='approved'?'selected':'' ?>>Aprovuar</option>
        <option value="rejected" <?= $approval==='rejected'?'selected':'' ?>>Refuzuar</option>
      </select>
      <button type="submit" class="btn btn--navy btn--sm">Kerko</button>
      <?php if ($q||$fstatus||$ftype||$approval): ?><a href="?" class="btn btn--sm btn--outline-navy">Pastro</a><?php endif; ?>
    </form>

    <div class="data-table-wrap">
      <table class="data-table">
        <thead><tr>
          <th>Prona</th><th>Lloji/Statusi</th><th>Çmimi</th>
          <th>Pronari</th><th>Agjenti</th><th>Premium</th><th>Aktive</th><th>Veprime</th>
        </tr></thead>
        <tbody>
          <?php foreach ($props as $p):
            $pimg = $p['primary_img'] ? SITE_URL.'/uploads/properties/'.$p['primary_img'] : SITE_URL.'/assets/images/property-placeholder.svg';
          ?>
          <tr>
            <td style="min-width:220px;">
              <div style="display:flex;align-items:center;gap:8px;">
                <img src="<?= e($pimg) ?>" class="prop-thumb">
                <div>
                  <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" style="font-weight:700;font-size:.825rem;color:var(--navy);"><?= e(mb_substr($p['title'],0,32)) ?></a>
                  <div style="font-size:.72rem;color:var(--gray-500);"><?= e($p['city']) ?></div>
                  <div style="font-size:.7rem;color:<?= $p['approval_status']==='approved'?'var(--green)':($p['approval_status']==='rejected'?'var(--red)':'var(--gold)') ?>;">
                    <?= $p['approval_status']==='approved'?'Aprovuar':($p['approval_status']==='rejected'?'Refuzuar':'Ne pritje') ?>
                    <?= $p['is_verified'] ? ' · Verifikuar' : '' ?>
                  </div>
                </div>
              </div>
            </td>
            <td>
              <div style="font-size:.78rem;"><?= property_type_label($p['type']) ?></div>
              <span class="status-pill status-pill--<?= $p['status']==='for_sale'?'sale':'rent' ?>" style="margin-top:2px;"><?= property_status_label($p['status']) ?></span>
            </td>
            <td style="font-weight:700;color:var(--gold);font-size:.875rem;"><?= format_price((float)$p['price'],$p['price_period']) ?></td>
            <td style="font-size:.825rem;"><?= e($p['o_first'].' '.$p['o_last']) ?></td>
            <td style="font-size:.825rem;"><?= !empty($p['a_first']) ? e($p['a_first'].' '.$p['a_last']) : '<span style="color:var(--gray-400);">-</span>' ?></td>
            <td>
              <form method="POST" action="" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="toggle_featured" value="1">
                <input type="hidden" name="prop_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="is_featured" value="<?= $p['is_featured'] ? 0 : 1 ?>">
                <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.1rem;"
                        title="<?= $p['is_featured']?'Hiq Premium':'Bëje Premium' ?>">
                  <?= $p['is_featured'] ? 'Premium' : 'Jo' ?>
                </button>
              </form>
            </td>
            <td>
              <form method="POST" action="" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="toggle_active" value="1">
                <input type="hidden" name="prop_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="is_active" value="<?= $p['is_active'] ? 0 : 1 ?>">
                <button type="submit" class="btn btn--sm" style="font-size:.72rem;background:<?= $p['is_active']?'var(--green-light)':'var(--red-light)' ?>;color:<?= $p['is_active']?'var(--green)':'var(--red)' ?>;">
                  <?= $p['is_active'] ? 'Aktive' : 'Çaktive' ?>
                </button>
              </form>
            </td>
            <td>
              <div style="display:flex;gap:4px;">
                <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" class="btn-icon" style="background:var(--gray-100);" title="Shiko">Sh</a>
                <a href="<?= SITE_URL ?>/dashboard/add-property.php?id=<?= $p['id'] ?>" class="btn-icon" style="background:var(--gold-pale);" title="Edito">Ed</a>
                <?php if ($p['approval_status'] !== 'approved'): ?>
                <form method="POST" action="" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="approve_property" value="1">
                  <input type="hidden" name="prop_id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn-icon" style="background:var(--green-light);color:var(--green);" title="Aprovo">Ap</button>
                </form>
                <?php endif; ?>
                <?php if ($p['approval_status'] !== 'rejected'): ?>
                <form method="POST" action="" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="reject_property" value="1">
                  <input type="hidden" name="prop_id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn-icon" style="background:var(--red-light);color:var(--red);" title="Refuzo">Rf</button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?= render_pagination($paging, '?q='.urlencode($q).'&status='.$fstatus.'&type='.$ftype.'&approval='.$approval) ?>
    </div>
  </main>
</div>
<script>window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
