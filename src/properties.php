<?php
ob_start();
// =============================================================================
// properties.php - Lista e pronave me kërkim të avancuar
// =============================================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

// --- Parametrat e kërkimit ---
$status     = in_array($_GET['status'] ?? '', ['for_sale','for_rent','sold','rented']) ? $_GET['status'] : '';
$type       = in_array($_GET['type'] ?? '', ['apartment','house','villa','commercial','office','land','garage']) ? $_GET['type'] : '';
$city       = sanitize($_GET['city'] ?? '');
$q          = sanitize($_GET['q'] ?? '');
$min_price  = (float)($_GET['min_price'] ?? 0);
$max_price  = (float)($_GET['max_price'] ?? 0);
$min_rooms  = (int)($_GET['min_rooms'] ?? 0);
$min_bathrooms = (int)($_GET['min_bathrooms'] ?? 0);
$min_area   = (float)($_GET['min_area'] ?? 0);
$max_area   = (float)($_GET['max_area'] ?? 0);
$floor      = strlen($_GET['floor'] ?? '') ? (int)$_GET['floor'] : null;
$year_min   = (int)($_GET['year_min'] ?? 0);
$year_max   = (int)($_GET['year_max'] ?? 0);
$has_photos = isset($_GET['has_photos']) ? 1 : 0;
$verified   = isset($_GET['verified']) ? 1 : 0;
$near_me    = isset($_GET['near_me']) ? 1 : 0;
$user_lat   = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$user_lng   = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
$radius_km  = max(1, min(100, (int)($_GET['radius'] ?? 10)));
$is_featured= isset($_GET['is_featured']) ? 1 : null;
$sort       = in_array($_GET['sort'] ?? '', ['price_asc','price_desc','newest','oldest','area_asc','area_desc','popular','distance']) ? ($_GET['sort'] ?? '') : 'newest';
$per_page   = 9;
$page       = max(1, (int)($_GET['page'] ?? 1));

// --- Build WHERE clause ---
$where   = ["p.is_active = 1", "p.approval_status = 'approved'"];
$params  = [];

if ($status)     { $where[] = "p.status = ?";  $params[] = $status; }
if ($type)       { $where[] = "p.type = ?";    $params[] = $type; }
if ($city)       { $where[] = "p.city = ?";    $params[] = $city; }
if ($min_price)  { $where[] = "p.price >= ?";  $params[] = $min_price; }
if ($max_price)  { $where[] = "p.price <= ?";  $params[] = $max_price; }
if ($min_rooms)  { $where[] = "p.rooms >= ?";  $params[] = $min_rooms; }
if ($min_bathrooms) { $where[] = "p.bathrooms >= ?"; $params[] = $min_bathrooms; }
if ($min_area)   { $where[] = "p.area >= ?";   $params[] = $min_area; }
if ($max_area)   { $where[] = "p.area <= ?";   $params[] = $max_area; }
if ($floor !== null) { $where[] = "p.floor = ?"; $params[] = $floor; }
if ($year_min)   { $where[] = "p.year_built >= ?"; $params[] = $year_min; }
if ($year_max)   { $where[] = "p.year_built <= ?"; $params[] = $year_max; }
if ($has_photos) { $where[] = "EXISTS (SELECT 1 FROM property_images pix WHERE pix.property_id = p.id)"; }
if ($verified)   { $where[] = "p.is_verified = 1"; }
if ($is_featured){ $where[] = "p.is_featured = 1"; }
if ($near_me && $user_lat !== null && $user_lng !== null) {
    $where[] = "p.latitude IS NOT NULL AND p.longitude IS NOT NULL
                AND (6371 * ACOS(LEAST(1, GREATEST(-1,
                    COS(RADIANS(?)) * COS(RADIANS(p.latitude)) *
                    COS(RADIANS(p.longitude) - RADIANS(?)) +
                    SIN(RADIANS(?)) * SIN(RADIANS(p.latitude))
                )))) <= ?";
    array_push($params, $user_lat, $user_lng, $user_lat, $radius_km);
}
if ($q) {
    $where[]  = "MATCH(p.title, p.description, p.address, p.city, p.neighborhood) AGAINST(? IN BOOLEAN MODE)";
    $params[] = $q . '*';
}

$where_sql = implode(' AND ', $where);

// --- Order ---
$order_map = [
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'newest'     => 'p.created_at DESC',
    'oldest'     => 'p.created_at ASC',
    'area_asc'   => 'p.area ASC',
    'area_desc'  => 'p.area DESC',
    'popular'    => 'p.views DESC',
    'distance'   => ($near_me && $user_lat !== null && $user_lng !== null) ? 'distance_km ASC' : 'p.created_at DESC',
];
$order_sql = $order_map[$sort] ?? 'p.created_at DESC';
$map_order_sql = $sort === 'distance' ? 'p.created_at DESC' : $order_sql;

// --- Count ---
$total  = db_count("SELECT COUNT(*) FROM properties p WHERE {$where_sql}", $params);
$paging = paginate($total, $per_page, $page);

// --- Fetch ---
$distance_select = '';
$fetch_params = $params;
if ($near_me && $user_lat !== null && $user_lng !== null) {
    $distance_select = ",
       (6371 * ACOS(LEAST(1, GREATEST(-1,
          COS(RADIANS(?)) * COS(RADIANS(p.latitude)) *
          COS(RADIANS(p.longitude) - RADIANS(?)) +
          SIN(RADIANS(?)) * SIN(RADIANS(p.latitude))
       )))) AS distance_km";
    $fetch_params = array_merge([$user_lat, $user_lng, $user_lat], $params);
}
$props  = db_rows(
    "SELECT p.*, pi.filename AS primary_img{$distance_select}
     FROM properties p
     LEFT JOIN property_images pi ON pi.property_id = p.id AND pi.is_primary = 1
     WHERE {$where_sql}
     ORDER BY p.is_featured DESC, {$order_sql}
     LIMIT {$paging['per_page']} OFFSET {$paging['offset']}",
    $fetch_params
);

$map_props = db_rows(
    "SELECT p.id, p.title, p.price, p.price_period, p.city, p.address, p.latitude, p.longitude,
            pi.filename AS primary_img
     FROM properties p
     LEFT JOIN property_images pi ON pi.property_id = p.id AND pi.is_primary = 1
     WHERE {$where_sql} AND p.latitude IS NOT NULL AND p.longitude IS NOT NULL
     ORDER BY p.is_featured DESC, {$map_order_sql}
     LIMIT 120",
    $params
);

// --- Cities for filter ---
$cities = db_rows("SELECT DISTINCT city FROM properties WHERE is_active=1 AND approval_status='approved' ORDER BY city");

// --- Favs ---
$fav_ids = [];
if (is_logged_in()) {
    $fav_ids = array_column(
        db_rows("SELECT property_id FROM favorites WHERE user_id = ?", [current_user_id()]),
        'property_id'
    );
}

$page_title = 'Prona ' . ($status ? '- ' . property_status_label($status) : '') . ($city ? " në {$city}" : '');
$page_description = 'Kërko apartamente, shtëpi, vila dhe prona komerciale për shitje dhe qiradhënie në Shqipëri.';

// URL base për pagination
$qp = $_GET;
unset($qp['page']);
$base_url = '?' . http_build_query($qp);
$extra_head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';

require __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <div class="container page-header__inner">
    <div class="page-header__eyebrow">Katalogu i Pronave</div>
    <h1>
      <?php if ($q): ?>Rezultate për "<?= e($q) ?>"
      <?php elseif ($status && $city): ?><?= property_status_label($status) ?> në <?= e($city) ?>
      <?php elseif ($status): ?>Prona <?= property_status_label($status) ?>
      <?php elseif ($city): ?>Prona në <?= e($city) ?>
      <?php else: ?>Të Gjitha Pronat<?php endif; ?>
    </h1>
    <p><?= $total ?> prona të gjetura. Përdorni filtrat për qytetin, çmimin, tipologjinë dhe statusin.</p>
  </div>
</header>

<div class="container listing-layout">

    <!-- SIDEBAR FILTERS -->
    <aside class="filters-panel">
      <h3>Filtro Pronat</h3>
      <form method="GET" action="">
        <!-- Kërkim text -->
        <div class="form-group">
          <label>Kërko me fjalë kyçe</label>
          <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="p.sh. apartament Bllok...">
        </div>
        <!-- Status -->
        <div class="form-group">
          <label>Statusi</label>
          <select name="status" class="form-control">
            <option value="">Të gjitha</option>
            <option value="for_sale" <?= $status==='for_sale'?'selected':'' ?>>Për Shitje</option>
            <option value="for_rent" <?= $status==='for_rent'?'selected':'' ?>>Me Qira</option>
          </select>
        </div>
        <!-- Lloji -->
        <div class="form-group">
          <label>Lloji i Pronës</label>
          <select name="type" class="form-control">
            <option value="">Të gjitha</option>
            <?php foreach (['apartment'=>'Apartament','house'=>'Shtëpi','villa'=>'Vilë','commercial'=>'Komerciale','office'=>'Zyrë','land'=>'Truall','garage'=>'Garazh'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $type===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Qyteti -->
        <div class="form-group">
          <label>Qyteti</label>
          <select name="city" class="form-control">
            <option value="">Kudo</option>
            <?php foreach ($cities as $c): ?>
            <option value="<?= e($c['city']) ?>" <?= $city===$c['city']?'selected':'' ?>><?= e($c['city']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Çmimi -->
        <div class="form-group">
          <label>Çmimi Min (€)</label>
          <input type="number" name="min_price" class="form-control" value="<?= $min_price ?: '' ?>" placeholder="0" min="0" step="5000">
        </div>
        <div class="form-group">
          <label>Çmimi Maks (€)</label>
          <input type="number" name="max_price" class="form-control" value="<?= $max_price ?: '' ?>" placeholder="Pa limit" min="0" step="5000">
        </div>
        <!-- Dhoma -->
        <div class="form-group">
          <label>Dhoma Minimale</label>
          <select name="min_rooms" class="form-control">
            <option value="">Çdo gjë</option>
            <?php for ($i=1;$i<=5;$i++): ?>
            <option value="<?= $i ?>" <?= $min_rooms===$i?'selected':'' ?>><?= $i ?>+</option>
            <?php endfor; ?>
          </select>
        </div>
        <!-- Sipërfaqja -->
        <div class="form-group">
          <label>Sipërfaqja Min (m²)</label>
          <input type="number" name="min_area" class="form-control" value="<?= $min_area ?: '' ?>" placeholder="0" min="0">
        </div>
        <div class="form-group">
          <label>Siperfaqja Maks (m2)</label>
          <input type="number" name="max_area" class="form-control" value="<?= $max_area ?: '' ?>" placeholder="Pa limit" min="0">
        </div>
        <div class="form-group">
          <label>Banjo Minimale</label>
          <select name="min_bathrooms" class="form-control">
            <option value="">Cdo gje</option>
            <?php for ($i=1;$i<=4;$i++): ?>
            <option value="<?= $i ?>" <?= $min_bathrooms===$i?'selected':'' ?>><?= $i ?>+</option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Kati</label>
          <input type="number" name="floor" class="form-control" value="<?= $floor !== null ? (int)$floor : '' ?>" placeholder="p.sh. 3">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Viti Min</label>
            <input type="number" name="year_min" class="form-control" value="<?= $year_min ?: '' ?>" min="1900" max="<?= date('Y') ?>">
          </div>
          <div class="form-group">
            <label>Viti Maks</label>
            <input type="number" name="year_max" class="form-control" value="<?= $year_max ?: '' ?>" min="1900" max="<?= date('Y') ?>">
          </div>
        </div>
        <!-- Premium -->
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" name="is_featured" id="is_featured" value="1" <?= $is_featured ? 'checked' : '' ?>>
            <label for="is_featured">Vetëm Premium</label>
          </div>
        </div>
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" name="has_photos" id="has_photos" value="1" <?= $has_photos ? 'checked' : '' ?>>
            <label for="has_photos">Vetem me foto</label>
          </div>
        </div>
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" name="verified" id="verified" value="1" <?= $verified ? 'checked' : '' ?>>
            <label for="verified">Vetem prona te verifikuara</label>
          </div>
        </div>
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" name="near_me" id="near_me" value="1" <?= $near_me ? 'checked' : '' ?>>
            <label for="near_me">Prona afer meje</label>
          </div>
          <input type="hidden" name="lat" id="filter-lat" value="<?= $user_lat !== null ? e((string)$user_lat) : '' ?>">
          <input type="hidden" name="lng" id="filter-lng" value="<?= $user_lng !== null ? e((string)$user_lng) : '' ?>">
        </div>
        <div class="form-group">
          <label>Rrezja</label>
          <select name="radius" class="form-control">
            <?php foreach ([3,5,10,20,50,100] as $r): ?>
            <option value="<?= $r ?>" <?= $radius_km===$r?'selected':'' ?>><?= $r ?> km</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-actions">
          <button type="submit" class="btn btn--navy btn--full">Apliko Filtrat</button>
          <a href="<?= SITE_URL ?>/properties.php" class="clear-link">Pastro filtrat</a>
        </div>
      </form>
    </aside>

    <!-- RESULTS -->
    <div>
      <!-- Sort bar -->
      <div class="listing-results-head">
        <span class="listing-results-count">
          Duke treguar <strong><?= count($props) ?></strong> nga <strong><?= $total ?></strong> prona
        </span>
        <form method="GET" action="" id="sort-form">
          <?php foreach ($qp as $k => $v): ?>
          <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endforeach; ?>
          <select name="sort" class="form-control" style="width:auto;padding:7px 32px 7px 12px;"
                  onchange="document.getElementById('sort-form').submit()">
            <option value="newest"     <?= $sort==='newest'?'selected':'' ?>>Më të reja</option>
            <option value="price_asc"  <?= $sort==='price_asc'?'selected':'' ?>>Çmimi: Ulët në Lartë</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Çmimi: Lartë në Ulët</option>
            <option value="area_asc"   <?= $sort==='area_asc'?'selected':'' ?>>Sipërfaqja</option>
          </select>
        </form>
      </div>

      <section class="listing-map-panel">
        <div class="listing-map-panel__head">
          <div>
            <h3>Harta e pronave</h3>
            <p><?= count($map_props) ?> lokacione me koordinata</p>
          </div>
          <button type="button" class="btn btn--sm btn--outline-navy" id="locate-me-btn">Prona afer meje</button>
        </div>
        <div id="properties-map" class="property-map property-map--listing"></div>
      </section>

      <?php if (empty($props)): ?>
      <div class="card card-body empty-state">
        <h3>Asnjë pronë nuk u gjet</h3>
        <p>Ndryshoni filtrat dhe provoni sërish.</p>
        <a href="<?= SITE_URL ?>/properties.php" class="btn btn--navy" style="margin-top:16px;">Pastro Filtrat</a>
      </div>
      <?php else: ?>
      <div class="properties-grid">
        <?php foreach ($props as $p):
          $img    = $p['primary_img']
            ? SITE_URL . '/uploads/properties/' . $p['primary_img']
            : SITE_URL . '/assets/images/property-placeholder.svg';
          $price  = format_price((float)$p['price'], $p['price_period']);
          $scode  = $p['status']==='for_sale' ? 'sale' : ($p['status']==='for_rent' ? 'rent' : 'other');
          $is_fav = in_array($p['id'], $fav_ids);
        ?>
        <div class="property-card" data-id="<?= $p['id'] ?>">
          <a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>" class="property-card__img-wrap">
            <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
            <span class="badge badge--<?= $scode ?>"><?= property_status_label($p['status']) ?></span>
            <?php if ($p['is_featured']): ?>
            <span class="badge" style="left:auto;right:12px;background:rgba(200,151,42,.9);color:#fff;">Premium</span>
            <?php endif; ?>
            <button class="btn-fav <?= $is_fav ? 'active' : '' ?>"
                    data-id="<?= $p['id'] ?>" onclick="toggleFav(event,<?= $p['id'] ?>)"><?= $is_fav ? '♥' : '♡' ?></button>
          </a>
          <div class="property-card__body">
            <div class="property-card__price"><?= $price ?></div>
            <h3 class="property-card__title"><a href="<?= SITE_URL ?>/property.php?id=<?= $p['id'] ?>"><?= e($p['title']) ?></a></h3>
            <div class="property-card__location"><?= e($p['city']) ?><?= $p['neighborhood'] ? ', '.e($p['neighborhood']) : '' ?></div>
            <div class="property-card__meta">
              <span class="meta-tag"><?= property_type_label($p['type']) ?></span>
              <?php if ($p['rooms']): ?><span><?= $p['rooms'] ?> dhoma</span><?php endif; ?>
              <?php if ($p['bathrooms']): ?><span><?= $p['bathrooms'] ?> banjo</span><?php endif; ?>
              <?php if ($p['area']): ?><span><?= format_area($p['area']) ?></span><?php endif; ?>
              <?php if (!empty($p['distance_km'])): ?><span><?= number_format((float)$p['distance_km'], 1) ?> km</span><?php endif; ?>
              <?php if (!empty($p['is_verified'])): ?><span>Verifikuar</span><?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?= render_pagination($paging, $base_url) ?>
      <?php endif; ?>
    </div>
</div>

<script>
window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
window.USER_FAVS  = <?= json_encode($fav_ids) ?>;
window.PROPERTY_MAP_DATA = <?= json_encode(array_map(function($p) {
    return [
        'id' => (int)$p['id'],
        'title' => $p['title'],
        'price' => format_price((float)$p['price'], $p['price_period']),
        'city' => $p['city'],
        'address' => $p['address'],
        'lat' => (float)$p['latitude'],
        'lng' => (float)$p['longitude'],
        'url' => SITE_URL . '/property.php?id=' . (int)$p['id'],
        'img' => $p['primary_img'] ? SITE_URL . '/uploads/properties/' . $p['primary_img'] : SITE_URL . '/assets/images/property-placeholder.svg',
    ];
}, $map_props), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php
$extra_js = <<<'HTML'
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
  const points = window.PROPERTY_MAP_DATA || [];
  const mapEl = document.getElementById('properties-map');
  if (mapEl && typeof L !== 'undefined') {
    const map = L.map(mapEl, { scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    const bounds = [];
    points.forEach(function (p) {
      if (!p.lat || !p.lng) return;
      bounds.push([p.lat, p.lng]);
      L.marker([p.lat, p.lng]).addTo(map).bindPopup(
        '<div class="map-popup"><img src="' + p.img + '" alt=""><strong>' + p.title + '</strong><span>' + p.price + '</span><small>' + p.city + '</small><a href="' + p.url + '">Shiko pronen</a></div>'
      );
    });
    if (bounds.length) map.fitBounds(bounds, { padding: [24, 24] });
    else map.setView([41.3275, 19.8187], 7);
  }

  const near = document.getElementById('near_me');
  const btn = document.getElementById('locate-me-btn');
  const form = near ? near.closest('form') : null;
  function locateAndSubmit() {
    if (!navigator.geolocation || !form) return alert('Geolocation nuk eshte i disponueshem.');
    navigator.geolocation.getCurrentPosition(function (pos) {
      document.getElementById('filter-lat').value = pos.coords.latitude.toFixed(7);
      document.getElementById('filter-lng').value = pos.coords.longitude.toFixed(7);
      near.checked = true;
      form.submit();
    }, function () {
      alert('Nuk mund te merret lokacioni. Lejoni aksesin ne browser dhe provoni serish.');
    }, { enableHighAccuracy: true, timeout: 8000 });
  }
  if (near) near.addEventListener('change', function () { if (this.checked) locateAndSubmit(); });
  if (btn) btn.addEventListener('click', locateAndSubmit);
})();
</script>
HTML;
?>
<?php require __DIR__ . '/templates/footer.php'; ?>
