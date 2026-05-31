<?php
ob_start();
// agent.php - Profili publik i agjentit
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(SITE_URL . '/agents.php');

$agent = db_row(
    "SELECT u.*,
       COUNT(DISTINCT p.id) as prop_count,
       COUNT(DISTINCT p2.id) as sold_count,
       COALESCE(AVG(r.rating),0) as avg_rating,
       COUNT(DISTINCT r.id) as review_count
     FROM users u
     LEFT JOIN properties p  ON p.agent_id=u.id AND p.is_active=1 AND p.approval_status='approved'
     LEFT JOIN properties p2 ON p2.agent_id=u.id AND p2.status IN ('sold','rented')
     LEFT JOIN reviews r ON r.agent_id=u.id
     WHERE u.id=? AND u.role='agent' AND u.is_active=1
     GROUP BY u.id",
    [$id]
);
if (!$agent) { flash_error('Agjenti nuk u gjet.'); redirect(SITE_URL . '/agents.php'); }

$props = db_rows(
    "SELECT p.*, pi.filename AS primary_img FROM properties p
     LEFT JOIN property_images pi ON pi.property_id=p.id AND pi.is_primary=1
     WHERE p.agent_id=? AND p.is_active=1 AND p.approval_status='approved'
     ORDER BY p.is_featured DESC, p.created_at DESC LIMIT 6",
    [$id]
);

$reviews = db_rows(
    "SELECT r.*, u.first_name, u.last_name, u.avatar FROM reviews r
     JOIN users u ON u.id=r.reviewer_id WHERE r.agent_id=? ORDER BY r.created_at DESC",
    [$id]
);

// Handle review submission
$review_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    require_auth();
    check_referrer(); csrf_check();
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5) {
        $review_error = 'Zgjidh vlerësimin (1-5 yje).';
    } elseif (current_user_id() === $id) {
        $review_error = 'Nuk mund të vlerësoni veten tuaj.';
    } else {
        $existing = db_count("SELECT COUNT(*) FROM reviews WHERE reviewer_id=? AND agent_id=?", [current_user_id(), $id]);
        if ($existing) {
            db_query("UPDATE reviews SET rating=?, comment=? WHERE reviewer_id=? AND agent_id=?",
                     [$rating, $comment, current_user_id(), $id]);
        } else {
            db_query("INSERT INTO reviews (reviewer_id, agent_id, rating, comment) VALUES (?,?,?,?)",
                     [current_user_id(), $id, $rating, $comment]);
        }
        flash_success('Vlerësimi u ruajt! Faleminderit.');
        redirect(SITE_URL . '/agent.php?id=' . $id);
    }
}

$fav_ids = [];
if (is_logged_in()) {
    $fav_ids = array_column(db_rows("SELECT property_id FROM favorites WHERE user_id=?", [current_user_id()]), 'property_id');
}

$avg = round((float)$agent['avg_rating'], 1);
$page_title = e($agent['first_name'].' '.$agent['last_name']) . ' - Agjent ProEstate';
require __DIR__ . '/templates/header.php';
?>

<div class="breadcrumb-bar">
  <div class="container">
    <nav>
      <a href="<?= SITE_URL ?>">Faqja Kryesore</a> /
      <a href="<?= SITE_URL ?>/agents.php">Agjentët</a> /
      <span><?= e($agent['first_name'].' '.$agent['last_name']) ?></span>
    </nav>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="agent-profile-layout">

      <!-- LEFT: Agent Card -->
      <div class="agent-profile-aside">
        <div class="card card-body agent-profile-card">
          <img src="<?= get_avatar_url($agent['avatar']) ?>"
               alt="<?= e($agent['first_name']) ?>"
               class="agent-profile-card__avatar">
          <h1 class="agent-profile-card__name"><?= e($agent['first_name'].' '.$agent['last_name']) ?></h1>
          <p class="agent-profile-card__agency"><?= e($agent['agency_name'] ?? 'ProEstate Realty') ?></p>
          <?php if ($agent['license_number']): ?>
          <p class="agent-profile-card__meta">Licencë: <?= e($agent['license_number']) ?></p>
          <?php endif; ?>

          <div style="margin:14px 0;"><?= render_stars($avg) ?></div>
          <p style="font-size:.875rem;color:var(--gray-600);">
            <strong><?= $avg ?>/5</strong> · <?= $agent['review_count'] ?> vlerësime
          </p>

          <div class="agent-profile-stats">
            <div class="agent-profile-stat">
              <div class="agent-profile-stat__value"><?= $agent['prop_count'] ?></div>
              <div class="agent-profile-stat__label">Prona Aktive</div>
            </div>
            <div class="agent-profile-stat">
              <div class="agent-profile-stat__value"><?= $agent['sold_count'] ?></div>
              <div class="agent-profile-stat__label">Të Shitura</div>
            </div>
          </div>

          <?php if ($agent['city']): ?>
          <p style="color:var(--text-3);font-size:.825rem;"><?= e($agent['city']) ?></p>
          <?php endif; ?>
          <?php if ($agent['phone']): ?>
          <a href="tel:<?= e($agent['phone']) ?>" class="btn btn--outline-navy btn--sm btn--full" style="margin-top:12px;">
            <?= e($agent['phone']) ?>
          </a>
          <?php endif; ?>
          <?php if (is_logged_in() && current_user_id() !== $id): ?>
          <a href="<?= SITE_URL ?>/dashboard/messages.php?to=<?= $id ?>"
             class="btn btn--navy btn--sm btn--full" style="margin-top:8px;">Dërgo Mesazh</a>
          <?php elseif (!is_logged_in()): ?>
          <a href="<?= SITE_URL ?>/login.php" class="btn btn--primary btn--sm btn--full" style="margin-top:12px;">
            Hyr për të Kontaktuar
          </a>
          <?php endif; ?>
        </div>

        <?php if ($agent['bio']): ?>
        <div class="card card-body">
          <h4 style="margin-bottom:10px;">Rreth Meje</h4>
          <p style="color:var(--gray-600);font-size:.875rem;line-height:1.7;"><?= nl2br(e($agent['bio'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Review Form -->
        <?php if (is_logged_in() && current_user_id() !== $id): ?>
        <div class="card card-body">
          <h4 style="margin-bottom:14px;">Lini Vlerësimin Tuaj</h4>
          <?php if ($review_error): ?>
          <div class="flash flash--error" style="position:static;max-width:none;margin-bottom:12px;">
            <span><?= e($review_error) ?></span>
          </div>
          <?php endif; ?>
          <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="submit_review" value="1">
            <div class="form-group">
              <label>Vlerësimi</label>
              <div class="stars stars--interactive" style="font-size:1.6rem;cursor:pointer;margin-bottom:8px;">
                <?php for ($i=1;$i<=5;$i++): ?>
                <span class="star" style="cursor:pointer;" onclick="setRating(<?=$i?>)">★</span>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="rating-input" value="0">
            </div>
            <div class="form-group">
              <label>Komenti (opsional)</label>
              <textarea name="comment" class="form-control" rows="3"
                        placeholder="Ndani përvojën tuaj me këtë agjent..."></textarea>
            </div>
            <button type="submit" class="btn btn--primary btn--full btn--sm">Dërgo Vlerësimin</button>
          </form>
        </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: Properties + Reviews -->
      <div>
        <!-- Properties -->
        <?php if (!empty($props)): ?>
        <h2 style="font-size:1.4rem;margin-bottom:20px;">Pronat e <?= e($agent['first_name']) ?></h2>
        <div class="properties-grid" style="margin-bottom:40px;">
          <?php foreach ($props as $p):
            $img   = $p['primary_img'] ? SITE_URL.'/uploads/properties/'.$p['primary_img'] : SITE_URL.'/assets/images/property-placeholder.svg';
            $scode = $p['status']==='for_sale'?'sale':'rent';
            $is_fav= in_array($p['id'], $fav_ids);
          ?>
          <div class="property-card" data-id="<?= $p['id'] ?>">
            <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" class="property-card__img-wrap">
              <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
              <span class="badge badge--<?= $scode ?>"><?= property_status_label($p['status']) ?></span>
              <button class="btn-fav <?= $is_fav?'active':'' ?>" data-id="<?= $p['id'] ?>"
                      onclick="toggleFav(event,<?= $p['id'] ?>)"><?= $is_fav?'♥':'♡' ?></button>
            </a>
            <div class="property-card__body">
              <div class="property-card__price"><?= format_price((float)$p['price'],$p['price_period']) ?></div>
              <h3 class="property-card__title">
                <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>"><?= e($p['title']) ?></a>
              </h3>
              <div class="property-card__location"><?= e($p['city']) ?></div>
              <div class="property-card__meta">
                <span class="meta-tag"><?= property_type_label($p['type']) ?></span>
                <?php if ($p['rooms']): ?><span><?= $p['rooms'] ?> dhoma</span><?php endif; ?>
                <?php if ($p['area']): ?><span><?= format_area($p['area']) ?></span><?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Reviews -->
        <h2 style="font-size:1.4rem;margin-bottom:20px;">Vlerësimet (<?= count($reviews) ?>)</h2>
        <?php if (empty($reviews)): ?>
        <div class="card card-body empty-state">
          <p>Ky agjent nuk ka vlerësime ende. Bëhuni i pari!</p>
        </div>
        <?php else: ?>
        <div class="review-list">
          <?php foreach ($reviews as $rev): ?>
          <div class="card card-body">
            <div class="review-item__head">
              <div class="review-item__user">
                <img src="<?= get_avatar_url($rev['avatar']) ?>"
                     class="review-item__avatar" alt="<?= e($rev['first_name']) ?>">
                <div>
                  <div style="font-weight:700;color:var(--navy);"><?= e($rev['first_name'].' '.$rev['last_name']) ?></div>
                  <div style="font-size:.75rem;color:var(--gray-400);"><?= time_ago($rev['created_at']) ?></div>
                </div>
              </div>
              <?= render_stars($rev['rating']) ?>
            </div>
            <?php if ($rev['comment']): ?>
            <p style="color:var(--gray-600);font-style:italic;font-size:.9rem;line-height:1.65;">
              "<?= nl2br(e($rev['comment'])) ?>"
            </p>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>
window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
window.USER_FAVS  = <?= json_encode($fav_ids) ?>;

function setRating(val) {
  document.getElementById('rating-input').value = val;
  document.querySelectorAll('.stars--interactive .star').forEach(function(s, i) {
    s.classList.toggle('star--filled', i < val);
  });
}
</script>
<?php require __DIR__ . '/templates/footer.php'; ?>
