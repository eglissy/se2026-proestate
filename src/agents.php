<?php
ob_start();
// agents.php - Lista e agjentëve
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$agents = db_rows(
    "SELECT u.*,
       COUNT(DISTINCT p.id) as prop_count,
       COUNT(DISTINCT p2.id) as sold_count,
       COALESCE(AVG(r.rating),0) as avg_rating,
       COUNT(DISTINCT r.id) as review_count
     FROM users u
     LEFT JOIN properties p  ON p.agent_id=u.id AND p.is_active=1 AND p.approval_status='approved'
     LEFT JOIN properties p2 ON p2.agent_id=u.id AND p2.status IN ('sold','rented')
     LEFT JOIN reviews r ON r.agent_id=u.id
     WHERE u.role='agent' AND u.is_active=1
     GROUP BY u.id
     ORDER BY avg_rating DESC, prop_count DESC"
);

$page_title = 'Agjentët - ProEstate';
$page_description = 'Gjej agjentin e duhur imobiliar. Agjentë të certifikuar me eksperiencë në Shqipëri.';
require __DIR__ . '/templates/header.php';
?>

<header class="page-header page-header--center">
  <div class="container page-header__inner">
    <div class="page-header__eyebrow">Ekipi Ynë</div>
    <h1>Agjentët Ekspertë</h1>
    <p>Profesionistë të certifikuar me njohuri të tregut imobiliar shqiptar, listime aktive dhe vlerësime nga klientët.</p>
  </div>
</header>

<section class="section">
  <div class="container">
    <div class="agent-directory-grid">
      <?php foreach ($agents as $a):
        $avg = round((float)$a['avg_rating'], 1);
      ?>
      <div class="agent-card">
        <img src="<?= get_avatar_url($a['avatar']) ?>" alt="<?= e($a['first_name']) ?>" class="agent-card__avatar">
        <div class="agent-card__name"><?= e($a['first_name'].' '.$a['last_name']) ?></div>
        <div class="agent-card__agency"><?= e($a['agency_name'] ?? 'ProEstate Realty') ?></div>
        <?php if ($a['license_number']): ?>
        <div class="agent-card__license">Licencë: <?= e($a['license_number']) ?></div>
        <?php endif; ?>
        <?= render_stars($avg) ?>
        <div style="font-size:.78rem;color:var(--text-3);margin:4px 0;"><?= $avg > 0 ? $avg.'/5 · '.$a['review_count'].' vlerësime' : 'Pa vlerësime ende' ?></div>

        <div class="agent-card__stats">
          <div class="agent-stat"><div class="agent-stat__n"><?= $a['prop_count'] ?></div><div class="agent-stat__l">Prona Aktive</div></div>
          <div class="agent-stat"><div class="agent-stat__n"><?= $a['sold_count'] ?></div><div class="agent-stat__l">Të Shitura</div></div>
          <div class="agent-stat"><div class="agent-stat__n"><?= e($a['city'] ?? '-') ?></div><div class="agent-stat__l">Qyteti</div></div>
        </div>

        <?php if ($a['bio']): ?>
        <p class="agent-card__bio">"<?= e(mb_substr($a['bio'],0,120)) ?>..."</p>
        <?php endif; ?>

        <div class="agent-card__actions">
          <a href="<?= SITE_URL ?>/agent.php?id=<?= $a['id'] ?>" class="btn btn--outline-navy btn--sm">Profili</a>
          <?php if (is_logged_in() && current_user_id() !== $a['id']): ?>
          <a href="<?= SITE_URL ?>/dashboard/messages.php?to=<?= $a['id'] ?>" class="btn btn--navy btn--sm">Mesazh</a>
          <?php elseif (!is_logged_in()): ?>
          <a href="tel:<?= e($a['phone'] ?? '') ?>" class="btn btn--navy btn--sm">Kontakt</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($agents)): ?>
    <div class="empty-state">
      <p>Asnjë agjent aktiv momentalisht.</p>
    </div>
    <?php endif; ?>

    <div class="page-cta">
      <h2>Jeni Agjent Imobiliar?</h2>
      <p>Bashkohuni me ekipin e ProEstate dhe listoni pronat tuaja te klientë të mundshëm.</p>
      <a href="<?= SITE_URL ?>/register.php?role=agent" class="btn btn--primary btn--lg">Regjistrohu si Agjent</a>
    </div>
  </div>
</section>

<script>window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';</script>
<?php require __DIR__ . '/templates/footer.php'; ?>
