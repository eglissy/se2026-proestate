<?php
ob_start();
// dashboard/favorites.php - Prona të preferuara
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_auth();
$uid = current_user_id();

$favs = db_rows(
    "SELECT p.*, pi.filename AS primary_img, f.created_at AS saved_at
     FROM favorites f
     JOIN properties p ON p.id = f.property_id
     LEFT JOIN property_images pi ON pi.property_id=p.id AND pi.is_primary=1
     WHERE f.user_id = ? AND p.is_active = 1 AND p.approval_status='approved'
     ORDER BY f.created_at DESC",
    [$uid]
);
$fav_ids = array_column($favs, 'id');

$page_title = 'Preferuarat - ProEstate';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="dashboard">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header">
      <h1 class="dashboard__title">Prona të Preferuara</h1>
      <p class="dashboard__subtitle"><?= count($favs) ?> prona të ruajtura</p>
    </div>

    <?php if (empty($favs)): ?>
    <div class="card card-body table-empty">
      <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21.2l8.9-8.8a5.5 5.5 0 0 0-.1-7.8z"/>
      </svg>
      <h3>Asnjë pronë e preferuar</h3>
      <p style="color:var(--gray-500);margin:8px 0 20px;">Shfleto pronat dhe klikoni ♡ për t'i ruajtur.</p>
      <a href="<?= SITE_URL ?>/properties.php" class="btn btn--primary">Shfleto Pronat</a>
    </div>
    <?php else: ?>
    <div class="properties-grid">
      <?php foreach ($favs as $p):
        $img   = $p['primary_img'] ? SITE_URL.'/uploads/properties/'.$p['primary_img'] : SITE_URL.'/assets/images/property-placeholder.svg';
        $price = format_price((float)$p['price'], $p['price_period']);
        $scode = $p['status']==='for_sale'?'sale':'rent';
      ?>
      <div class="property-card" data-id="<?= $p['id'] ?>">
        <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" class="property-card__img-wrap">
          <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
          <span class="badge badge--<?= $scode ?>"><?= property_status_label($p['status']) ?></span>
          <button class="btn-fav active" data-id="<?= $p['id'] ?>"
                  onclick="toggleFav(event,<?= $p['id'] ?>)">♥</button>
        </a>
        <div class="property-card__body">
          <div class="property-card__price"><?= $price ?></div>
          <h3 class="property-card__title">
            <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>"><?= e($p['title']) ?></a>
          </h3>
          <div class="property-card__location"><?= e($p['city']) ?></div>
          <div class="property-card__meta">
            <span class="meta-tag"><?= property_type_label($p['type']) ?></span>
            <?php if ($p['rooms']): ?><span><?= $p['rooms'] ?> dhoma</span><?php endif; ?>
            <?php if ($p['area']): ?><span><?= format_area($p['area']) ?></span><?php endif; ?>
          </div>
          <div style="font-size:.75rem;color:var(--gray-400);margin-top:8px;">
            Ruajtur: <?= time_ago($p['saved_at']) ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>
<script>
window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
window.USER_FAVS  = <?= json_encode($fav_ids) ?>;
// Remove card from grid when unfavorited
$(document).on('click', '.btn-fav', function() {
  const card = $(this).closest('.property-card');
  setTimeout(function() {
    if (!card.find('.btn-fav').hasClass('active')) card.fadeOut(300, function(){ $(this).remove(); });
  }, 500);
});
</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
