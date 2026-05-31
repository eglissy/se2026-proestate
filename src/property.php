<?php
ob_start();
// property.php - Detajet e pronës
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(SITE_URL . '/properties.php');

$prop = db_row(
    "SELECT p.*,
       o.first_name AS owner_first, o.last_name AS owner_last, o.phone AS owner_phone,
       a.id AS ag_id, a.first_name AS ag_first, a.last_name AS ag_last,
       a.phone AS ag_phone, a.email AS ag_email, a.avatar AS ag_avatar,
       a.agency_name, a.bio AS ag_bio, a.license_number,
       COALESCE(AVG(r.rating),0) AS agent_rating,
       COUNT(DISTINCT r.id) AS agent_review_count
     FROM properties p
     LEFT JOIN users o ON o.id = p.owner_id
     LEFT JOIN users a ON a.id = p.agent_id
     LEFT JOIN reviews r ON r.agent_id = a.id
     WHERE p.id = ? AND p.is_active = 1 AND p.approval_status = 'approved'
     GROUP BY p.id",
    [$id]
);

if (!$prop) {
    flash_error('Prona nuk u gjet ose nuk është aktive.');
    redirect(SITE_URL . '/properties.php');
}

// Rrit numrin e shikimeve
db_query("UPDATE properties SET views = views + 1 WHERE id = ?", [$id]);

$images    = get_property_images($id);
$features  = db_rows("SELECT feature FROM property_features WHERE property_id = ?", [$id]);
$documents = db_rows(
    "SELECT * FROM property_documents WHERE property_id = ? ORDER BY created_at DESC",
    [$id]
);

$is_fav = false;
if (is_logged_in()) {
    $is_fav = (bool) db_count(
        "SELECT COUNT(*) FROM favorites WHERE user_id = ? AND property_id = ?",
        [current_user_id(), $id]
    );
}

// Prona të ngjashme
$similar = db_rows(
    "SELECT p.*, pi.filename AS primary_img FROM properties p
     LEFT JOIN property_images pi ON pi.property_id = p.id AND pi.is_primary = 1
     WHERE p.city = ? AND p.type = ? AND p.id != ? AND p.is_active = 1 AND p.approval_status='approved'
     ORDER BY p.created_at DESC LIMIT 3",
    [$prop['city'], $prop['type'], $id]
);

// Takimet e zëna për këtë pronë (për të bllokuar slots)
$booked_dates = db_rows(
    "SELECT scheduled_date, scheduled_time FROM appointments
     WHERE property_id = ? AND status IN ('pending','confirmed')",
    [$id]
);

// Pagesa PayPal: nuk ka POST handling këtu; procesimi bëhet nga api/paypal-capture-order.php
$appt_success = false;
$appt_error   = '';

$page_title       = $prop['title'] . ' - ProEstate';
$page_description = mb_substr(strip_tags($prop['description']), 0, 155);
$extra_head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
require __DIR__ . '/templates/header.php';
?>

<div class="breadcrumb-bar">
  <div class="container">
    <nav>
      <a href="<?= SITE_URL ?>">Faqja Kryesore</a> /
      <a href="<?= SITE_URL ?>/properties.php">Prona</a> /
      <a href="<?= SITE_URL ?>/properties.php?city=<?= urlencode($prop['city']) ?>"><?= e($prop['city']) ?></a> /
      <span><?= e(mb_substr($prop['title'],0,40)) ?>...</span>
    </nav>
  </div>
</div>

<div class="container">
  <div class="property-detail-layout">

    <!-- LEFT: Gallery + Info -->
    <div>
      <!-- Gallery -->
      <div class="prop-gallery">
        <div class="prop-gallery__main">
          <?php if (!empty($images)): ?>
          <img src="<?= SITE_URL ?>/uploads/properties/<?= e($images[0]['filename']) ?>"
               alt="<?= e($prop['title']) ?>" id="main-gallery-img">
          <?php else: ?>
          <img src="<?= SITE_URL ?>/assets/images/property-placeholder.svg" alt="Imazh mungon">
          <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="prop-gallery__thumbs">
          <?php foreach ($images as $i => $img): ?>
          <img src="<?= SITE_URL ?>/uploads/properties/<?= e($img['filename']) ?>"
               alt="Imazhi <?= $i+1 ?>"
               class="<?= $i===0?'active':'' ?>"
               onclick="document.getElementById('main-gallery-img').src=this.src;
                        document.querySelectorAll('.prop-gallery__thumbs img').forEach(x=>x.classList.remove('active'));
                        this.classList.add('active');">
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Header info -->
      <div class="property-title-row">
        <div>
          <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
            <span class="badge badge--<?= $prop['status']==='for_sale'?'sale':($prop['status']==='for_rent'?'rent':'other') ?>"
                  style="position:static;"><?= property_status_label($prop['status']) ?></span>
            <span class="meta-tag"><?= property_type_label($prop['type']) ?></span>
            <?php if ($prop['is_featured']): ?><span class="meta-tag" style="background:var(--gold-pale);color:var(--gold);">Premium</span><?php endif; ?>
          </div>
          <h1><?= e($prop['title']) ?></h1>
          <p style="color:var(--text-3);"><?= e($prop['address']) ?>, <?= e($prop['city']) ?><?= $prop['neighborhood']?', '.e($prop['neighborhood']):'' ?></p>
        </div>
        <div class="property-title-row__price">
          <div class="prop-price-badge"><?= format_price((float)$prop['price'], $prop['price_period']) ?></div>
          <button class="btn-fav <?= $is_fav?'active':'' ?>" data-id="<?= $id ?>"
                  onclick="toggleFav(event,<?= $id ?>)"
                  style="position:static;background:var(--gray-100);width:auto;height:auto;border-radius:var(--radius);padding:8px 16px;margin-top:8px;font-size:.875rem;">
            <?= $is_fav ? 'Në Preferuara' : 'Shto si Favorite' ?>
          </button>
        </div>
      </div>

      <!-- Meta grid -->
      <div class="prop-meta-grid">
        <?php if ($prop['area']): ?>
        <div class="prop-meta-item">
          <div class="prop-meta-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16"/><path d="M4 17h16"/><path d="M7 4v16"/><path d="M17 4v16"/></svg></div>
          <div class="prop-meta-value"><?= format_area($prop['area']) ?></div>
          <div class="prop-meta-label">Siperfaqja</div>
        </div>
        <?php endif; ?>
        <?php if ($prop['rooms']): ?>
        <div class="prop-meta-item">
          <div class="prop-meta-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11V7a2 2 0 0 1 2-2h5a3 3 0 0 1 3 3v3"/><path d="M13 11h6a2 2 0 0 1 2 2v6"/><path d="M3 19v-8h18"/><path d="M3 19h18"/></svg></div>
          <div class="prop-meta-value"><?= $prop['rooms'] ?></div>
          <div class="prop-meta-label">Dhoma gjumi</div>
        </div>
        <?php endif; ?>
        <?php if ($prop['bathrooms']): ?>
        <div class="prop-meta-item">
          <div class="prop-meta-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 10V5a3 3 0 0 1 6 0"/><path d="M4 11h16v4a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-4Z"/><path d="M8 21v-2"/><path d="M16 21v-2"/></svg></div>
          <div class="prop-meta-value"><?= $prop['bathrooms'] ?></div>
          <div class="prop-meta-label">Banjo</div>
        </div>
        <?php endif; ?>
        <?php if ($prop['floor'] !== null): ?>
        <div class="prop-meta-item">
          <div class="prop-meta-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16"/><path d="M6 20V4h12v16"/><path d="M9 8h1"/><path d="M14 8h1"/><path d="M9 12h1"/><path d="M14 12h1"/><path d="M9 16h1"/><path d="M14 16h1"/></svg></div>
          <div class="prop-meta-value"><?= $prop['floor'] ?>/<small><?= $prop['total_floors'] ?></small></div>
          <div class="prop-meta-label">Kati</div>
        </div>
        <?php endif; ?>
        <?php if ($prop['year_built']): ?>
        <div class="prop-meta-item">
          <div class="prop-meta-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg></div>
          <div class="prop-meta-value"><?= $prop['year_built'] ?></div>
          <div class="prop-meta-label">Viti i ndertimit</div>
        </div>
        <?php endif; ?>
        <div class="prop-meta-item">
          <div class="prop-meta-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></div>
          <div class="prop-meta-value"><?= $prop['views'] ?></div>
          <div class="prop-meta-label">Shikime</div>
        </div>
      </div>

      <!-- Description -->
      <div class="card card-body" style="margin-bottom:24px;">
        <h3 style="margin-bottom:12px;">Përshkrim</h3>
        <div style="color:var(--gray-700);line-height:1.75;white-space:pre-line;"><?= nl2br(e($prop['description'])) ?></div>
      </div>

      <?php if ($prop['latitude'] !== null && $prop['longitude'] !== null): ?>
      <div class="card card-body" style="margin-bottom:24px;">
        <div class="section-heading-row" style="margin-bottom:14px;">
          <div>
            <h3>Lokacioni</h3>
            <p class="text-sm text-muted"><?= e($prop['address']) ?>, <?= e($prop['city']) ?></p>
          </div>
          <a class="btn btn--sm btn--outline-navy" target="_blank"
             href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($prop['latitude'] . ',' . $prop['longitude']) ?>">
            Hap ne Google Maps
          </a>
        </div>
        <div id="property-map" class="property-map"></div>
      </div>
      <?php endif; ?>

      <!-- Features -->
      <?php if (!empty($features)): ?>
      <div class="card card-body" style="margin-bottom:24px;">
        <h3 style="margin-bottom:14px;">Karakteristikat</h3>
        <div class="prop-features">
          <?php foreach ($features as $f): ?>
          <span class="prop-feature"><?= e($f['feature']) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Documents -->
      <?php if (!empty($documents)): ?>
      <div class="card card-body" style="margin-bottom:24px;">
        <h3 style="margin-bottom:14px;">Dokumentet</h3>
        <?php foreach ($documents as $doc): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100);">
          <div style="display:flex;align-items:center;gap:10px;">
            <div>
              <div style="font-weight:600;font-size:.875rem;"><?= e($doc['original_name']) ?></div>
              <div style="font-size:.75rem;color:var(--gray-500);"><?= human_filesize($doc['file_size']) ?></div>
            </div>
          </div>
          <?php if (is_logged_in()): ?>
          <a href="<?= SITE_URL ?>/uploads/documents/<?= e($doc['filename']) ?>"
             download="<?= e($doc['original_name']) ?>" class="btn btn--sm btn--outline-navy">Shkarko</a>
          <?php else: ?>
          <a href="<?= SITE_URL ?>/login.php" class="btn btn--sm btn--outline-navy">Hyr</a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Booking + Agent -->
    <div class="property-sidebar">

      <!-- Booking Card with PayPal -->
      <div class="card card-body">
        <h3 style="margin-bottom:6px;">Rezervo Takim</h3>
        <p style="font-size:.78rem;color:var(--gray-500);margin-bottom:16px;">
          Tarifa e rezervimit: <strong style="color:var(--gold);">€<?php echo number_format(PAYPAL_RESERVATION_FEE,0); ?></strong>
          paguhet me PayPal dhe zbritet nga çmimi final
        </p>

        <?php if (!is_logged_in()): ?>
        <p style="text-align:center;color:var(--gray-600);font-size:.875rem;margin-bottom:16px;">
          Duhet të hyni për të rezervuar takim.
        </p>
        <a href="<?= SITE_URL ?>/login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
           class="btn btn--primary btn--full">Hyr për të Rezervuar</a>
        <?php elseif (in_array($prop['status'], ['sold','rented'])): ?>
        <p style="text-align:center;color:var(--gray-500);">Kjo pronë nuk është më e disponueshme.</p>
        <?php else: ?>

        <!-- Step 1: Choose date/time -->
        <div id="booking-step-1">
          <div class="form-group">
            <label>Data e Takimit</label>
            <input type="date" id="booking-date" class="form-control"
                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                   max="<?= date('Y-m-d', strtotime('+60 days')) ?>">
          </div>
          <div class="form-group">
            <label>Ora</label>
            <select id="booking-time" class="form-control">
              <option value="">Zgjidhni orën...</option>
              <?php for ($h=9;$h<=17;$h++): foreach(['00','30'] as $m): $slot=sprintf('%02d:%s',$h,$m); ?>
              <option value="<?= $slot ?>"><?= $slot ?></option>
              <?php endforeach; endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Shënime (opsionale)</label>
            <textarea id="booking-notes" class="form-control" rows="2"
                      placeholder="Çfarë doni të inspektoni?"></textarea>
          </div>
          <button id="proceed-to-pay" class="btn btn--primary btn--full" onclick="proceedToPayPal()">
            Vazhdo me Pagesën
          </button>
          <p id="booking-error" style="color:var(--red);font-size:.8rem;margin-top:8px;display:none;"></p>
        </div>

        <!-- Step 2: PayPal Buttons (shown after choosing date/time) -->
        <div id="booking-step-2" style="display:none;">
          <div class="paypal-summary" style="margin-bottom:14px;">
            <div class="paypal-summary__row">
              <span>Data</span><strong id="pay-sum-date">-</strong>
            </div>
            <div class="paypal-summary__row">
              <span>Ora</span><strong id="pay-sum-time">-</strong>
            </div>
            <div class="paypal-summary__total">
              <span>Tarifa Rezervimit</span>
              <span>€<?php echo number_format(PAYPAL_RESERVATION_FEE,0); ?></span>
            </div>
          </div>
          <div id="paypal-button-container"></div>
          <button onclick="resetBooking()" style="width:100%;margin-top:10px;background:none;border:none;color:var(--gray-500);font-size:.8rem;cursor:pointer;">Ndrysho datën/orën</button>
        </div>

        <!-- Step 3: Success -->
        <div id="booking-step-3" style="display:none;">
          <div style="text-align:center;padding:16px 0;">
            <div class="pay-success-mark">OK</div>
            <h4 style="color:var(--green);margin-bottom:6px;">Pagesa u Krye!</h4>
            <p style="font-size:.825rem;color:var(--gray-600);">Takimi u konfirmua. Keni marrë email.</p>
            <a href="<?= SITE_URL ?>/dashboard/appointments.php" class="btn btn--primary btn--sm" style="margin-top:12px;">
              Shiko Takimet
            </a>
          </div>
        </div>

        <!-- PayPal trust -->
        <div class="paypal-secure-note" style="margin-top:12px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Pagesa e sigurt me PayPal · Buyer Protection
        </div>
        <?php endif; ?>
      </div>

      <!-- Agent Card -->
      <?php if ($prop['ag_id']): ?>
      <div class="card card-body">
        <h3 style="margin-bottom:14px;">Agjenti</h3>
        <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:14px;">
          <img src="<?= get_avatar_url($prop['ag_avatar']) ?>" alt="<?= e($prop['ag_first']) ?>"
               style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);flex-shrink:0;">
          <div>
            <div style="font-weight:700;color:var(--navy);"><?= e($prop['ag_first'].' '.$prop['ag_last']) ?></div>
            <div style="font-size:.8rem;color:var(--gray-500);"><?= e($prop['agency_name'] ?? 'ProEstate') ?></div>
            <?= render_stars((float)$prop['agent_rating']) ?>
            <div style="font-size:.75rem;color:var(--gray-500);"><?= round((float)$prop['agent_rating'],1) ?>/5 · <?= $prop['agent_review_count'] ?> vlerësime</div>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;">
          <?php if ($prop['ag_phone']): ?>
          <a href="tel:<?= e($prop['ag_phone']) ?>" class="btn btn--outline-navy btn--sm">
            <?= e($prop['ag_phone']) ?>
          </a>
          <?php endif; ?>
          <a href="<?= SITE_URL ?>/agent.php?id=<?= $prop['ag_id'] ?>" class="btn btn--sm" style="border:1px solid var(--gray-200);color:var(--gray-700);">
            Shiko Profilin e Plotë
          </a>
        </div>
        <?php if (is_logged_in() && current_user_id() !== $prop['ag_id']): ?>
        <a href="<?= SITE_URL ?>/dashboard/messages.php?to=<?= $prop['ag_id'] ?>&prop=<?= $id ?>"
           class="btn btn--navy btn--full btn--sm">Dërgo Mesazh</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Share -->
      <div class="card card-body" style="text-align:center;">
        <p style="font-size:.825rem;color:var(--gray-600);margin-bottom:10px;">Ndaj këtë pronë</p>
        <div style="display:flex;justify-content:center;gap:10px;">
          <button class="copy-btn btn btn--sm btn--outline-navy" data-copy="<?= SITE_URL ?>/property.php?id=<?= $id ?>">Kopjo linkun</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Similar Properties -->
  <?php if (!empty($similar)): ?>
  <div style="margin-top:48px;">
    <h2 style="margin-bottom:24px;font-size:1.5rem;">Prona të Ngjashme</h2>
    <div class="properties-grid">
      <?php foreach ($similar as $s):
        $simg  = $s['primary_img'] ? SITE_URL.'/uploads/properties/'.$s['primary_img'] : SITE_URL.'/assets/images/property-placeholder.svg';
        $scode = $s['status']==='for_sale'?'sale':'rent';
      ?>
      <div class="property-card">
        <a href="<?= SITE_URL ?>/property.php?id=<?= $s['id'] ?>" class="property-card__img-wrap">
          <img src="<?= e($simg) ?>" alt="<?= e($s['title']) ?>" loading="lazy">
          <span class="badge badge--<?= $scode ?>"><?= property_status_label($s['status']) ?></span>
        </a>
        <div class="property-card__body">
          <div class="property-card__price"><?= format_price((float)$s['price'],$s['price_period']) ?></div>
          <h3 class="property-card__title"><a href="<?= SITE_URL ?>/property.php?id=<?= $s['id'] ?>"><?= e($s['title']) ?></a></h3>
          <div class="property-card__location"><?= e($s['city']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
window.USER_FAVS  = <?= json_encode($is_fav ? [$id] : []) ?>;
window.PROP_ID    = <?= (int)$id ?>;
window.SITE_URL   = '<?= SITE_URL ?>';
window.PROPERTY_LOCATION = <?= json_encode([
  'lat' => $prop['latitude'] !== null ? (float)$prop['latitude'] : null,
  'lng' => $prop['longitude'] !== null ? (float)$prop['longitude'] : null,
  'title' => $prop['title'],
  'address' => $prop['address'] . ', ' . $prop['city'],
  'price' => format_price((float)$prop['price'], $prop['price_period']),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php if (!in_array($prop['status'], ['sold','rented']) && is_logged_in()): ?>
<?php
// Kontrollo nëse CLIENT_ID është konfiguruar
$paypal_configured = (PAYPAL_CLIENT_ID !== '' && PAYPAL_CLIENT_SECRET !== '');
?>
<?php if ($paypal_configured): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars(PAYPAL_CLIENT_ID) ?>&currency=<?= PAYPAL_CURRENCY ?>&intent=capture&components=buttons"></script>
<script>
var ppRendered = false;
var bookDate = '', bookTime = '', bookNotes = '';

function proceedToPayPal() {
  bookDate  = document.getElementById('booking-date').value;
  bookTime  = document.getElementById('booking-time').value;
  bookNotes = (document.getElementById('booking-notes') || {}).value || '';
  var err = document.getElementById('booking-error');

  if (!bookDate) { err.textContent='Zgjidhni datën.'; err.style.display='block'; return; }
  if (!bookTime) { err.textContent='Zgjidhni orën.';  err.style.display='block'; return; }
  var d = new Date(bookDate + 'T00:00:00');
  if (d.getDay() === 0) { err.textContent='Të dielave nuk pranohen takime.'; err.style.display='block'; return; }
  if (d < new Date()) { err.textContent='Zgjidhni një datë në të ardhmen.'; err.style.display='block'; return; }
  err.style.display = 'none';

  document.getElementById('pay-sum-date').textContent = bookDate.split('-').reverse().join('/');
  document.getElementById('pay-sum-time').textContent = bookTime;
  document.getElementById('booking-step-1').style.display = 'none';
  document.getElementById('booking-step-2').style.display = 'block';

  if (!ppRendered) {
    ppRendered = true;
    if (typeof paypal === 'undefined') {
      document.getElementById('paypal-button-container').innerHTML =
        '<p style="color:var(--red);font-size:.85rem;text-align:center;">PayPal SDK nuk u ngarkua. Kontrolloni lidhjen e internetit.</p>';
      return;
    }
    renderPPButtons();
  }
}

function renderPPButtons() {
  paypal.Buttons({
    style: { layout:'vertical', color:'gold', shape:'rect', label:'pay', height:45 },

    createOrder: function() {
      return fetch(window.SITE_URL + '/api/paypal-create-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
        body: JSON.stringify({ property_id: window.PROP_ID, date: bookDate, time: bookTime, notes: bookNotes })
      })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d.success) { showPayErr(d.message || 'Gabim.'); throw new Error(d.message); }
        return d.order_id;
      });
    },

    onApprove: function(data) {
      document.getElementById('paypal-button-container').innerHTML =
        '<div style="text-align:center;padding:24px;">' +
        '<div style="width:32px;height:32px;border:3px solid #e5e7eb;border-top-color:#0a1628;border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 10px;"></div>' +
        '<p style="color:#6b7280;font-size:.85rem;">Duke konfirmuar...</p></div>';
      return fetch(window.SITE_URL + '/api/paypal-capture-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
        body: JSON.stringify({ order_id: data.orderID })
      })
      .then(function(r){ return r.json(); })
      .then(function(result){
        if (result.success) {
          document.getElementById('booking-step-2').style.display = 'none';
          document.getElementById('booking-step-3').style.display = 'block';
        } else {
          showPayErr(result.message || 'Gabim. Kontaktoni mbështetjen.');
        }
      })
      .catch(function(){ showPayErr('Gabim rrjeti. ID: ' + data.orderID); });
    },

    onCancel: function(){ resetBooking(); },
    onError:  function(e){ console.error(e); showPayErr('PayPal error. Provoni sërish.'); }
  }).render('#paypal-button-container');
}

function resetBooking() {
  document.getElementById('booking-step-2').style.display = 'none';
  document.getElementById('booking-step-1').style.display = 'block';
  ppRendered = false;
  document.getElementById('paypal-button-container').innerHTML = '';
}

function showPayErr(msg) {
  document.getElementById('paypal-button-container').innerHTML =
    '<div style="background:#fee2e2;color:#dc2626;padding:12px 14px;border-radius:8px;font-size:.83rem;margin-top:8px;">' + msg + '</div>';
}
</script>
<?php else: ?>
<script>
function proceedToPayPal() {
  alert('PayPal nuk është konfiguruar ende.\nVendos PAYPAL_CLIENT_ID dhe PAYPAL_CLIENT_SECRET si environment variables.');
}
</script>
<?php endif; ?>
<?php endif; ?>

<?php
$extra_js = <<<'HTML'
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
  const loc = window.PROPERTY_LOCATION || {};
  const el = document.getElementById('property-map');
  if (!el || typeof L === 'undefined' || !loc.lat || !loc.lng) return;
  const map = L.map(el, { scrollWheelZoom: false }).setView([loc.lat, loc.lng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);
  L.marker([loc.lat, loc.lng]).addTo(map).bindPopup(
    '<strong>' + loc.title + '</strong><br><span>' + loc.price + '</span><br><small>' + loc.address + '</small>'
  ).openPopup();
})();
</script>
HTML;
?>
<?php require __DIR__ . '/templates/footer.php'; ?>
