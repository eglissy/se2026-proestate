<?php
ob_start();
// about.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$stats = get_site_stats();
$page_title = 'Rreth Nesh - ProEstate';
require __DIR__ . '/templates/header.php';
?>

<header class="page-header page-header--center">
  <div class="container page-header__inner">
    <div class="page-header__eyebrow">Rreth Nesh</div>
    <h1>Rreth ProEstate</h1>
    <p>ProEstate është një platformë për kërkimin, publikimin dhe menaxhimin e pronave në një mënyrë më të thjeshtë dhe më të organizuar.</p>
  </div>
</header>

<section class="section">
  <div class="container">
    <div class="split-grid" style="margin-bottom:72px;">
      <div>
        <span class="section__eyebrow">Kush jemi</span>
        <h2 style="margin:12px 0 20px;">Pse u krijua ProEstate</h2>
        <p class="section-lead">
          ProEstate u krijua si projekt studentor për ta bërë më të lehtë lidhjen mes klientëve, pronarëve dhe agjentëve. Qëllimi është që pronat, takimet dhe komunikimi të jenë në një vend, pa humbur kohë me mesazhe të shpërndara.
        </p>
        <p class="section-lead">
          Në platformë mund të kërkohen prona për shitje ose qira, të filtrohen rezultatet, të kontaktohen agjentët dhe të rezervohen takime për pronat që kanë interes.
        </p>
      </div>
      <div class="soft-panel" style="padding:28px;">
        <div class="metric-grid">
          <?php $about_stats = [
            [$stats['total_properties'],'Prona të Listuara'],
            [$stats['total_agents'],'Agjentë Aktivë'],
            [$stats['total_clients'],'Klientë të Regjistruar'],
            [$stats['sold_rented'],'Të mbyllura'],
          ]; foreach ($about_stats as $s): ?>
          <div class="metric-box">
            <div class="metric-box__value"><?= $s[0] ?></div>
            <div class="metric-box__label"><?= $s[1] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="section__header">
      <span class="section__eyebrow">Ekipi i Projektit</span>
      <h2>Zhvilluesit</h2>
    </div>
    <div class="team-grid">
      <?php $team = [
        'Eglis Haderaj',
        'Eriseld Memia',
        'Harilla Bica',
      ]; foreach ($team as $name): ?>
      <div class="card card-body team-card">
        <h4 style="margin:0;"><?= e($name) ?></h4>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--dark">
  <div class="container container--narrow" style="text-align:center;">
    <h2 style="color:var(--white);margin-bottom:14px;">Gati të Filloni?</h2>
    <p style="color:rgba(255,255,255,.65);margin-bottom:28px;">Regjistrohu falas dhe eksploro listimet aktive.</p>
    <div class="page-actions">
      <a href="<?= SITE_URL ?>/properties.php" class="btn btn--primary btn--lg">Shfleto Pronat</a>
      <a href="<?= SITE_URL ?>/contact.php" class="btn btn--outline btn--lg">Na Kontaktoni</a>
    </div>
  </div>
</section>

<script>window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';</script>
<?php require __DIR__ . '/templates/footer.php'; ?>
