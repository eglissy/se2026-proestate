<?php
ob_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$page_title       = 'ProEstate - Prona të Paluajtshme në Shqipëri';
$page_description = 'Kërko apartamente, shtëpi dhe villa për shitje ose me qira në Tiranë, Durrës dhe gjithë Shqipërinë.';

$featured = db_rows(
    "SELECT p.*, pi.filename AS primary_img FROM properties p
     LEFT JOIN property_images pi ON pi.property_id=p.id AND pi.is_primary=1
     WHERE p.is_active=1 AND p.approval_status='approved' AND p.is_featured=1 ORDER BY p.created_at DESC LIMIT 6"
);
$latest = db_rows(
    "SELECT p.*, pi.filename AS primary_img FROM properties p
     LEFT JOIN property_images pi ON pi.property_id=p.id AND pi.is_primary=1
     WHERE p.is_active=1 AND p.approval_status='approved' ORDER BY p.created_at DESC LIMIT 3"
);
$agents = db_rows(
    "SELECT u.*, COUNT(DISTINCT p.id) as pc, COALESCE(AVG(r.rating),0) as avg_r, COUNT(DISTINCT r.id) as rc
     FROM users u
     LEFT JOIN properties p ON p.agent_id=u.id AND p.is_active=1 AND p.approval_status='approved'
     LEFT JOIN reviews r ON r.agent_id=u.id
     WHERE u.role='agent' AND u.is_active=1
     GROUP BY u.id ORDER BY avg_r DESC, pc DESC LIMIT 4"
);
$stats   = get_site_stats();
$cities  = db_rows("SELECT DISTINCT city FROM properties WHERE is_active=1 AND approval_status='approved' ORDER BY city");
$fav_ids = [];
if (is_logged_in()) {
    $fav_ids = array_column(db_rows("SELECT property_id FROM favorites WHERE user_id=?", [current_user_id()]), 'property_id');
}
require __DIR__ . '/templates/header.php';
?>

<section class="hero">
  <div class="hero__bg"></div>
  <div class="hero__overlay"></div>
  <div class="hero__inner">
    <div class="container">
      <div class="hero__layout">

        <!-- LEFT: headline + stats -->
        <div class="hero__content">
          <div class="hero__eyebrow">Prona të paluajtshme · Shqipëri</div>
          <h1>Mbi <?= $stats['total_properties'] ?> Prona.<br><em>Njëra është e jotja.</em></h1>
          <p class="hero__desc">
            Tiranë, Durrës, Vlorë, Shkodër — prona me çmime reale,
            agjentë të licencuar dhe rezervim takimi direkt online.
          </p>
          <div class="hero__actions">
            <a href="<?= SITE_URL ?>/properties.php?status=for_sale" class="btn btn--primary btn--lg">Bli Pronë</a>
            <a href="<?= SITE_URL ?>/properties.php?status=for_rent" class="btn btn--outline btn--lg">Me Qira</a>
          </div>
          <div class="hero__stats">
            <div class="hero-stat">
              <div class="hero-stat__value"><?= $stats['total_properties'] ?></div>
              <div class="hero-stat__label">Prona aktive</div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat__value"><?= $stats['total_agents'] ?></div>
              <div class="hero-stat__label">Agjentë</div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat__value"><?= $stats['sold_rented'] ?></div>
              <div class="hero-stat__label">Të mbyllura</div>
            </div>
          </div>
        </div>

        <!-- RIGHT: glass search panel -->
        <div class="hero__search-panel">
          <div class="search-panel__label">Kërko Pronë</div>
          <div class="search-tabs">
            <button class="search-tab active" data-status="for_sale">Blerje</button>
            <button class="search-tab" data-status="for_rent">Qiradhënie</button>
            <button class="search-tab" data-status="">Të gjitha</button>
          </div>
          <form action="<?= SITE_URL ?>/properties.php" method="GET" id="main-search-form">
            <input type="hidden" name="status" value="for_sale">
            <div class="search-fields-grid">
              <div class="search-field">
                <label>Lloji</label>
                <select name="type">
                  <option value="">Të gjitha</option>
                  <option value="apartment">Apartament</option>
                  <option value="house">Shtëpi</option>
                  <option value="villa">Vilë</option>
                  <option value="commercial">Komerciale</option>
                  <option value="office">Zyrë</option>
                  <option value="land">Truall</option>
                </select>
              </div>
              <div class="search-field">
                <label>Qyteti</label>
                <select name="city">
                  <option value="">Kudo</option>
                  <?php foreach ($cities as $c): ?>
                  <option value="<?= e($c['city']) ?>"><?= e($c['city']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="search-field">
              <label class="price-label">Çmimi Maksimal (€)</label>
              <input type="number" name="max_price" placeholder="p.sh. 120 000" min="0" step="5000">
            </div>
            <div class="search-field">
              <label>Dhoma minimale</label>
              <select name="min_rooms">
                <option value="">Çdo gjë</option>
                <option value="1">1+</option>
                <option value="2">2+</option>
                <option value="3">3+</option>
                <option value="4">4+</option>
              </select>
            </div>
            <button type="submit" class="btn btn--primary btn--full" style="margin-top:6px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              Kërko Pronë
            </button>
          </form>
          <?php
            $hero_preview = $featured[0] ?? ($latest[0] ?? null);
            if ($hero_preview):
              $hero_img = !empty($hero_preview['primary_img'])
                ? SITE_URL . '/uploads/properties/' . $hero_preview['primary_img']
                : SITE_URL . '/assets/images/property-placeholder.svg';
          ?>
          <div class="hero__property-preview">
            <a href="<?= SITE_URL ?>/property.php?id=<?= (int)$hero_preview['id'] ?>" class="hero-preview-card">
              <img src="<?= e($hero_img) ?>" alt="<?= e($hero_preview['title']) ?>" loading="lazy">
              <span>
                <span class="hero-preview-card__label">Listim i rekomanduar</span>
                <span class="hero-preview-card__title"><?= e($hero_preview['title']) ?></span>
                <span class="hero-preview-card__meta"><?= e($hero_preview['city']) ?> · <?= format_price((float)$hero_preview['price'], $hero_preview['price_period']) ?></span>
              </span>
            </a>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</section>


<?php if (!empty($featured)): ?>
<section class="section section--alt">
  <div class="container">
    <div class="section-heading-row">
      <div><div class="section__eyebrow">Të Rekomanduara</div><h2 style="margin:0;">Prona Premium</h2></div>
      <a href="<?= SITE_URL ?>/properties.php?is_featured=1" class="btn btn--outline-navy btn--sm">Shiko të gjitha</a>
    </div>
    <div class="properties-grid">
      <?php foreach ($featured as $p):
        $img   = $p['primary_img'] ? SITE_URL.'/uploads/properties/'.$p['primary_img'] : SITE_URL.'/assets/images/property-placeholder.svg';
        $scode = $p['status']==='for_sale'?'sale':($p['status']==='for_rent'?'rent':'other');
        $is_fav= in_array($p['id'], $fav_ids);
      ?>
      <div class="property-card" data-id="<?= $p['id'] ?>">
        <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" class="property-card__img-wrap">
          <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy" onerror="this.src='<?= SITE_URL ?>/assets/images/property-placeholder.svg'">
          <span class="badge badge--<?= $scode ?>"><?= property_status_label($p['status']) ?></span>
          <button class="btn-fav <?= $is_fav?'active':'' ?>" data-id="<?= $p['id'] ?>" onclick="toggleFav(event,<?= $p['id'] ?>)"><?= $is_fav?'♥':'♡' ?></button>
        </a>
        <div class="property-card__body">
          <div class="property-card__price"><?= format_price((float)$p['price'],$p['price_period']) ?></div>
          <h3 class="property-card__title"><a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>"><?= e($p['title']) ?></a></h3>
          <div class="property-card__location">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= e($p['city']) ?><?= $p['neighborhood']?', '.e($p['neighborhood']):'' ?>
          </div>
          <div class="property-card__meta">
            <span class="meta-tag"><?= property_type_label($p['type']) ?></span>
            <?php if ($p['rooms']): ?><span><?= $p['rooms'] ?> dhoma</span><?php endif; ?>
            <?php if ($p['area']): ?><span><?= format_area((float)$p['area']) ?></span><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>


<section class="section">
  <div class="container">
    <div class="section-heading-row">
      <div><div class="section__eyebrow">Sapo Shtuar</div><h2 style="margin:0;">Listimet e Fundit</h2></div>
      <a href="<?= SITE_URL ?>/properties.php" class="btn btn--outline-navy btn--sm">Shiko të gjitha</a>
    </div>
    <div class="properties-grid" style="grid-template-columns:repeat(auto-fill,minmax(340px,1fr));">
      <?php foreach ($latest as $p):
        $img   = $p['primary_img'] ? SITE_URL.'/uploads/properties/'.$p['primary_img'] : SITE_URL.'/assets/images/property-placeholder.svg';
        $scode = $p['status']==='for_sale'?'sale':'rent';
        $is_fav= in_array($p['id'], $fav_ids);
      ?>
      <div class="property-card" data-id="<?= $p['id'] ?>">
        <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" class="property-card__img-wrap">
          <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
          <span class="badge badge--<?= $scode ?>"><?= property_status_label($p['status']) ?></span>
          <button class="btn-fav <?= $is_fav?'active':'' ?>" data-id="<?= $p['id'] ?>" onclick="toggleFav(event,<?= $p['id'] ?>)"><?= $is_fav?'♥':'♡' ?></button>
        </a>
        <div class="property-card__body">
          <div class="property-card__price"><?= format_price((float)$p['price'],$p['price_period']) ?></div>
          <h3 class="property-card__title"><a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>"><?= e($p['title']) ?></a></h3>
          <div class="property-card__location">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= e($p['city']) ?><?= $p['neighborhood']?', '.e($p['neighborhood']):'' ?>
          </div>
          <div class="property-card__meta">
            <span class="meta-tag"><?= property_type_label($p['type']) ?></span>
            <?php if ($p['rooms']): ?><span><?= $p['rooms'] ?> dhoma</span><?php endif; ?>
            <?php if ($p['area']): ?><span><?= format_area((float)$p['area']) ?></span><?php endif; ?>
            <span style="margin-left:auto;color:var(--text-3);font-size:.73rem;"><?= time_ago($p['created_at']) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--alt">
  <div class="container">
    <div class="section-heading-row">
      <div><div class="section__eyebrow">Ekipi</div><h2 style="margin:0;">Agjentët Tanë</h2></div>
      <a href="<?= SITE_URL ?>/agents.php" class="btn btn--outline-navy btn--sm">Të gjithë</a>
    </div>
    <div class="agents-grid">
      <?php foreach ($agents as $a): $avg = round((float)$a['avg_r'],1); ?>
      <div class="agent-card">
        <img src="<?= get_avatar_url($a['avatar']) ?>" alt="<?= e($a['first_name']) ?>" class="agent-card__avatar">
        <div class="agent-card__name"><?= e($a['first_name'].' '.$a['last_name']) ?></div>
        <div class="agent-card__agency"><?= e($a['agency_name'] ?? 'ProEstate') ?></div>
        <?= render_stars($avg) ?>
        <div style="font-size:.74rem;color:var(--text-3);margin-top:2px;"><?= $avg>0 ? $avg.'/5 · '.$a['rc'].' vlerësime' : 'Pa vlerësime' ?></div>
        <div class="agent-card__stats">
          <div class="agent-stat"><div class="agent-stat__n"><?= $a['pc'] ?></div><div class="agent-stat__l">Prona</div></div>
          <div class="agent-stat"><div class="agent-stat__n"><?= e($a['city']??'-') ?></div><div class="agent-stat__l">Qyteti</div></div>
        </div>
        <a href="<?= SITE_URL ?>/agent.php?id=<?= $a['id'] ?>" class="btn btn--outline-navy btn--sm btn--full">Shiko Profilin</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="split-grid">
      <div>
        <div class="section__eyebrow">Si Funksionon</div>
        <h2 style="margin:10px 0 14px;">Tre Hapa.<br>Pa Komplikime.</h2>
        <p class="section-lead" style="margin-bottom:12px;">
          Kërko nga telefoni ose kompjuteri. Kontakto agjentin direkt.
          Rezervo vizitën me pagesë PayPal — €50 që zbriten nga çmimi final.
        </p>
        <p class="section-lead" style="margin-bottom:22px;">
          Çdo listim është i verifikuar. Çdo agjent ka licencë.
          Çdo takim konfirmohet brenda 2 orëve.
        </p>
        <div class="steps-list">
          <?php $steps = [
            ['1','Kërko dhe filtro','Sipas qytetit, çmimit dhe tipologjisë.'],
            ['2','Rezervo vizitën','Zgjidhni datën, paguani €50 me PayPal.'],
            ['3','Vizito dhe vendos','Agjenti ju pret në pronë. Kontrata nënshkruhet.'],
          ]; foreach ($steps as $s): ?>
          <div class="step-item">
            <div class="step-item__number"><?= $s[0] ?></div>
            <div>
              <div class="step-item__title"><?= $s[1] ?></div>
              <div class="step-item__text"><?= $s[2] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="feature-mosaic">
        <?php $feats = [
          ['<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>','Pagesa PayPal','Pagesa regjistrohet në historikun e llogarisë'],
          ['<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>','Profile agjentësh','Vlerësime, kontakte dhe prona të lidhura'],
          ['<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>','Ndihmë Ligjore','Kontrata dhe AMTP me noterë'],
          ['<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>','Gjithmonë Aktiv','Akses 24/7 nga çdo pajisje'],
        ]; foreach ($feats as $f): ?>
        <div class="feature-tile">
          <div class="feature-tile__icon"><?= $f[0] ?></div>
          <div class="feature-tile__title"><?= $f[1] ?></div>
          <div class="feature-tile__text"><?= $f[2] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section section--dark" style="padding:64px 0;">
  <div class="container container--narrow" style="text-align:center;">
    <p style="font-size:.72rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.28);margin-bottom:16px;">ProEstate · Shqipëri</p>
    <h2 style="color:var(--white);margin-bottom:13px;font-size:clamp(1.7rem,3.5vw,2.6rem);">Prona juaj e ardhshme<br><em style="color:var(--gold);">ju pret.</em></h2>
    <p style="color:rgba(255,255,255,.44);margin-bottom:28px;max-width:400px;margin-left:auto;margin-right:auto;font-size:.92rem;line-height:1.72;">Shfletoni listimet, ruani të preferuarat dhe kontaktoni agjentin direkt.</p>
    <div style="display:flex;gap:11px;justify-content:center;flex-wrap:wrap;">
      <a href="<?= SITE_URL ?>/properties.php" class="btn btn--primary btn--lg">Shfletoni Pronat</a>
      <?php if (!is_logged_in()): ?>
      <a href="<?= SITE_URL ?>/register.php" class="btn btn--outline btn--lg">Krijoni Llogari</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
window.USER_FAVS  = <?= json_encode($fav_ids) ?>;
</script>
<?php require __DIR__ . '/templates/footer.php'; ?>
