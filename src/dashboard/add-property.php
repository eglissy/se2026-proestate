<?php
// dashboard/add-property.php - Shto ose edito prone
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_role(['agent','owner','admin']);

$edit_id = (int)($_GET['id'] ?? 0);
$prop    = null;
if ($edit_id) {
    $prop = db_row("SELECT * FROM properties WHERE id = ?", [$edit_id]);
    if (!$prop || !can_edit_property($edit_id)) {
        flash_error('Nuk keni leje të modifikoni këtë pronë.');
        redirect(SITE_URL . '/dashboard/my-properties.php');
    }
}

$errors = [];
$values = $prop ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer();
    csrf_check();
    $requires_approval = current_user_role() === 'owner';

    $values = [
        'title'        => sanitize($_POST['title'] ?? ''),
        'description'  => sanitize($_POST['description'] ?? ''),
        'type'         => sanitize($_POST['type'] ?? ''),
        'status'       => sanitize($_POST['status'] ?? ''),
        'price'        => (float)($_POST['price'] ?? 0),
        'price_period' => sanitize($_POST['price_period'] ?? 'total'),
        'area'         => (float)($_POST['area'] ?? 0) ?: null,
        'rooms'        => (int)($_POST['rooms'] ?? 0),
        'bathrooms'    => (int)($_POST['bathrooms'] ?? 0),
        'floor'        => strlen($_POST['floor'] ?? '') ? (int)$_POST['floor'] : null,
        'total_floors' => strlen($_POST['total_floors'] ?? '') ? (int)$_POST['total_floors'] : null,
        'year_built'   => (int)($_POST['year_built'] ?? 0) ?: null,
        'address'      => sanitize($_POST['address'] ?? ''),
        'city'         => sanitize($_POST['city'] ?? ''),
        'neighborhood' => sanitize($_POST['neighborhood'] ?? ''),
        'latitude'     => strlen($_POST['latitude'] ?? '') ? (float)$_POST['latitude'] : null,
        'longitude'    => strlen($_POST['longitude'] ?? '') ? (float)$_POST['longitude'] : null,
        'is_featured'  => isset($_POST['is_featured']) ? 1 : 0,
        'features'     => array_filter(array_map('trim', explode("\n", $_POST['features'] ?? ''))),
    ];

    // Validime
    if (strlen($values['title']) < 10) $errors['title'] = 'Titulli duhet të ketë të paktën 10 karaktere.';
    if (strlen($values['description']) < 30) $errors['description'] = 'Përshkrimi duhet të ketë të paktën 30 karaktere.';
    if (!in_array($values['type'], ['apartment','house','villa','commercial','office','land','garage'])) $errors['type'] = 'Zgjidh llojin.';
    if (!in_array($values['status'], ['for_sale','for_rent','sold','rented'])) $errors['status'] = 'Zgjidh statusin.';
    if ($values['price'] <= 0) $errors['price'] = 'Çmimi duhet të jetë pozitiv.';
    if (strlen($values['address']) < 5) $errors['address'] = 'Adresa është e detyrueshme.';
    if (strlen($values['city']) < 2) $errors['city'] = 'Qyteti është i detyrueshëm.';

    if (empty($errors)) {
        $data = [
            $values['title'], $values['description'], $values['type'], $values['status'],
            $values['price'], $values['price_period'], $values['area'], $values['rooms'],
            $values['bathrooms'], $values['floor'], $values['total_floors'], $values['year_built'],
            $values['address'], $values['city'], $values['neighborhood'],
            $values['latitude'], $values['longitude'], $values['is_featured'],
        ];

        if ($edit_id) {
            $approval_sql = $requires_approval ? ", approval_status='pending', is_verified=0, approved_at=NULL, approved_by=NULL" : '';
            db_query(
                "UPDATE properties SET
                   title=?, description=?, type=?, status=?, price=?, price_period=?,
                   area=?, rooms=?, bathrooms=?, floor=?, total_floors=?, year_built=?,
                   address=?, city=?, neighborhood=?, latitude=?, longitude=?, is_featured=? {$approval_sql}
                 WHERE id=?",
                array_merge($data, [$edit_id])
            );
            $new_id = $edit_id;
            // Përditëso features
            db_query("DELETE FROM property_features WHERE property_id=?", [$new_id]);
        } else {
            // Vendos owner_id
            $owner_id = current_user_role() === 'owner' ? current_user_id() : current_user_id();
            $agent_id = current_user_role() === 'agent' ? current_user_id() : null;
            $approval_status = $requires_approval ? 'pending' : 'approved';
            $is_verified = $requires_approval ? 0 : 1;
            db_query(
                "INSERT INTO properties
                   (title, description, type, status, price, price_period, area, rooms,
                    bathrooms, floor, total_floors, year_built, address, city, neighborhood,
                    latitude, longitude, is_featured, owner_id, agent_id, approval_status, is_verified)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                array_merge($data, [$owner_id, $agent_id, $approval_status, $is_verified])
            );
            $new_id = (int) db_last_id();
        }

        // Ruaj features
        foreach ($values['features'] as $feat) {
            if (strlen($feat) > 1) {
                db_query("INSERT INTO property_features (property_id, feature) VALUES (?,?)", [$new_id, $feat]);
            }
        }

        log_activity(current_user_id(), $edit_id ? 'property_updated' : 'property_created',
                     "ID: {$new_id}", get_client_ip());

        if ($requires_approval && $edit_id) {
            flash_success('Ndryshimet u ruajten dhe prona kaloi ne pritje aprovimi nga administratori.');
        } elseif ($requires_approval) {
            flash_success('Prona u dergua per aprovim. Mund te ngarkoni imazhet, por ajo shfaqet publikisht vetem pasi aprovohet nga administratori.');
        } elseif (current_user_role() === 'agent') {
            flash_success($edit_id ? 'Prona u perditesua dhe mbetet e publikuar.' : 'Prona u shtua dhe u publikua automatikisht. Tani mund te ngarkoni imazhet.');
        } else {
            flash_success($edit_id ? 'Prona u perditesua me sukses!' : 'Prona u shtua dhe u aprovua automatikisht.');
        }
        redirect(SITE_URL . '/dashboard/edit-property.php?id=' . $new_id . '&tab=images');
    }
}

// Ngarko features ekzistuese nëse edit
$existing_features = '';
if ($edit_id) {
    $feats = db_rows("SELECT feature FROM property_features WHERE property_id=?", [$edit_id]);
    $existing_features = implode("\n", array_column($feats, 'feature'));
    $existing_images   = get_property_images($edit_id);
    $existing_docs     = db_rows("SELECT * FROM property_documents WHERE property_id=? ORDER BY created_at DESC", [$edit_id]);
}

$page_title = ($edit_id ? 'Edito Pronën' : 'Shto Pronë të Re') . ' - ProEstate';
$active_tab = sanitize($_GET['tab'] ?? 'details');
$active_tab = in_array($active_tab, ['details','images','documents'], true) ? $active_tab : 'details';
require dirname(__DIR__) . '/templates/header.php';
?>

<div class="dashboard">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header">
      <h1 class="dashboard__title"><?= $edit_id ? 'Edito Pronën' : 'Shto Pronë të Re' ?></h1>
      <p class="dashboard__subtitle"><?= $edit_id ? e($prop['title']) : 'Plotëso të dhënat e pronës' ?></p>
    </div>

    <!-- Tabs -->
    <div class="tabs-bar">
      <?php $tabs = ['details'=>'Detajet','images'=>'Imazhet','documents'=>'Dokumentet'];
      foreach ($tabs as $tk=>$tl): ?>
      <a href="?<?= $edit_id ? "id={$edit_id}&" : '' ?>tab=<?= $tk ?>"
         class="tabs-bar__link <?= $active_tab===$tk?'active':'' ?>">
        <?= $tl ?>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($active_tab === 'details'): ?>
    <!-- DETAILS TAB -->
    <div class="card card-body">
      <?php if (current_user_role() === 'owner'): ?>
      <div class="notice-pending" style="margin-bottom:18px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p>
          <strong>Prona duhet aprovuar nga administratori.</strong>
          Pas ruajtjes, prona qendron ne pritje dhe nuk shfaqet publikisht derisa admini ta aprovoje.
        </p>
      </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
      <div class="flash flash--error" style="position:static;max-width:none;margin-bottom:20px;">
        <span class="flash__icon">X</span>
        <span><?= implode('<br>', array_map('e', $errors)) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="form-row">
          <div class="form-group" style="grid-column:1/-1;">
            <label>Titulli i Pronës <span class="req">*</span></label>
            <input type="text" name="title" class="form-control <?= isset($errors['title'])?'is-invalid':'' ?>"
                   value="<?= e($values['title'] ?? '') ?>" placeholder="p.sh. Apartament Modern 2+1 në Bllok" required maxlength="300">
            <?php if (isset($errors['title'])): ?><div class="form-error"><?= e($errors['title']) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="form-group">
          <label>Përshkrim i Detajuar <span class="req">*</span></label>
          <textarea name="description" class="form-control <?= isset($errors['description'])?'is-invalid':'' ?>"
                    rows="6" placeholder="Përshkruani pronën në detaj: gjendja, kushtet, avantazhet, afërsia me shërbimet..." required><?= e($values['description'] ?? '') ?></textarea>
          <?php if (isset($errors['description'])): ?><div class="form-error"><?= e($errors['description']) ?></div><?php endif; ?>
        </div>

        <div class="form-row-3">
          <div class="form-group">
            <label>Lloji i Pronës <span class="req">*</span></label>
            <select name="type" class="form-control <?= isset($errors['type'])?'is-invalid':'' ?>" required>
              <option value="">Zgjidh llojin...</option>
              <?php foreach (['apartment'=>'Apartament','house'=>'Shtëpi','villa'=>'Vilë','commercial'=>'Komerciale','office'=>'Zyrë','land'=>'Truall','garage'=>'Garazh'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($values['type']??'')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['type'])): ?><div class="form-error"><?= e($errors['type']) ?></div><?php endif; ?>
          </div>

          <div class="form-group">
            <label>Statusi <span class="req">*</span></label>
            <select name="status" class="form-control <?= isset($errors['status'])?'is-invalid':'' ?>" required>
              <option value="">Zgjidh statusin...</option>
              <option value="for_sale" <?= ($values['status']??'')==='for_sale'?'selected':'' ?>>Për Shitje</option>
              <option value="for_rent" <?= ($values['status']??'')==='for_rent'?'selected':'' ?>>Me Qira</option>
              <option value="sold"     <?= ($values['status']??'')==='sold'?'selected':'' ?>>Shitur</option>
              <option value="rented"   <?= ($values['status']??'')==='rented'?'selected':'' ?>>Me Qira (Zënë)</option>
            </select>
          </div>

          <div class="form-group">
            <label>Çmimi (€) <span class="req">*</span></label>
            <input type="number" name="price" class="form-control <?= isset($errors['price'])?'is-invalid':'' ?>"
                   value="<?= $values['price'] ?? '' ?>" placeholder="p.sh. 85000" min="0" step="100" required>
            <?php if (isset($errors['price'])): ?><div class="form-error"><?= e($errors['price']) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="form-row-3">
          <div class="form-group">
            <label>Periudha e Çmimit</label>
            <select name="price_period" class="form-control">
              <option value="total"   <?= ($values['price_period']??'total')==='total'?'selected':'' ?>>Total (çmim i plotë)</option>
              <option value="monthly" <?= ($values['price_period']??'')==='monthly'?'selected':'' ?>>Mujor (qira/muaj)</option>
              <option value="yearly"  <?= ($values['price_period']??'')==='yearly'?'selected':'' ?>>Vjetor (qira/vit)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Sipërfaqja (m²)</label>
            <input type="number" name="area" class="form-control" value="<?= $values['area'] ?? '' ?>" placeholder="p.sh. 85" min="0" step="0.5">
          </div>
          <div class="form-group">
            <label>Dhoma Gjumi</label>
            <select name="rooms" class="form-control">
              <?php for ($i=0;$i<=10;$i++): ?>
              <option value="<?= $i ?>" <?= ($values['rooms']??0)==$i?'selected':'' ?>><?= $i === 0 ? 'Studio / 0' : $i ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <div class="form-row-3">
          <div class="form-group">
            <label>Banjo</label>
            <select name="bathrooms" class="form-control">
              <?php for ($i=0;$i<=6;$i++): ?>
              <option value="<?= $i ?>" <?= ($values['bathrooms']??0)==$i?'selected':'' ?>><?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Kati</label>
            <input type="number" name="floor" class="form-control" value="<?= $values['floor'] ?? '' ?>" placeholder="p.sh. 3" min="-2" max="100">
          </div>
          <div class="form-group">
            <label>Katet Totale</label>
            <input type="number" name="total_floors" class="form-control" value="<?= $values['total_floors'] ?? '' ?>" placeholder="p.sh. 8" min="1" max="100">
          </div>
        </div>

        <div class="form-group">
          <label>Lagjja / Zona</label>
          <input type="text" name="neighborhood" class="form-control" value="<?= e($values['neighborhood'] ?? '') ?>" placeholder="p.sh. Blloku, Kombinat...">
        </div>

        <div class="form-group">
          <label>Adresa <span class="req">*</span></label>
          <input type="text" name="address" class="form-control <?= isset($errors['address'])?'is-invalid':'' ?>"
                 value="<?= e($values['address'] ?? '') ?>" placeholder="Rruga dhe numri" required>
          <?php if (isset($errors['address'])): ?><div class="form-error"><?= e($errors['address']) ?></div><?php endif; ?>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Qyteti <span class="req">*</span></label>
            <input type="text" name="city" class="form-control <?= isset($errors['city'])?'is-invalid':'' ?>"
                   value="<?= e($values['city'] ?? '') ?>" placeholder="Tiranë" required>
            <?php if (isset($errors['city'])): ?><div class="form-error"><?= e($errors['city']) ?></div><?php endif; ?>
          </div>
          <div class="form-group">
            <label>Viti i Ndërtimit</label>
            <input type="number" name="year_built" class="form-control" value="<?= $values['year_built'] ?? '' ?>"
                   placeholder="2018" min="1900" max="<?= date('Y') ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Karakteristikat (një për rresht)</label>
          <textarea name="features" class="form-control" rows="5"
                    placeholder="Parking nëntokësor&#10;Ashensor&#10;Kondicioner&#10;Sistem alarmi..."><?= e($existing_features ?? '') ?></textarea>
          <div class="form-hint">Shkruani çdo karakteristikë në rresht të ri</div>
        </div>

        <?php if (has_role('admin')): ?>
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" name="is_featured" id="is_featured" value="1"
                   <?= ($values['is_featured'] ?? 0) ? 'checked' : '' ?>>
            <label for="is_featured">Shënoje si Pronë Premium (do të shfaqet në krye)</label>
          </div>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:12px;margin-top:8px;">
          <button type="submit" class="btn btn--primary btn--lg">
            <?= $edit_id ? 'Ruaj Ndryshimet' : 'Shto Pronën' ?>
          </button>
          <a href="<?= SITE_URL ?>/dashboard/my-properties.php" class="btn btn--outline-navy btn--lg">Anulo</a>
        </div>
      </form>
    </div>

    <?php elseif ($active_tab === 'images' && $edit_id): ?>
    <!-- IMAGES TAB -->
    <div class="card card-body">
      <h3 style="margin-bottom:6px;">Imazhet e Pronës</h3>
      <p style="color:var(--text-3);font-size:.875rem;margin-bottom:20px;">Ngarko deri në <?= MAX_IMAGES_PER_PROPERTY ?> imazhe. Formatet: JPG, PNG, WebP. Max: 10MB/imazh.</p>

      <div class="dropzone" id="img-dropzone">
        <input type="file" id="img-input" accept="image/jpeg,image/png,image/webp" multiple style="display:none;">
        <div class="dropzone__icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
        <p>Tërhiqni imazhet këtu ose <span>klikoni për t'i zgjedhur</span></p>
        <p style="font-size:.78rem;color:var(--text-3);margin-top:4px;">PNG, JPG, WebP · max 10MB</p>
      </div>
      <div class="file-preview" id="img-preview"></div>
      <button id="upload-imgs-btn" class="btn btn--primary" style="margin-top:16px;" disabled>
        Ngarko Imazhet
      </button>

      <!-- Ekzistueset -->
      <?php if (!empty($existing_images)): ?>
      <div class="divider"></div>
      <h4 style="margin-bottom:14px;">Imazhet Aktuale (<?= count($existing_images) ?>)</h4>
      <div style="display:flex;flex-wrap:wrap;gap:12px;">
        <?php foreach ($existing_images as $img): ?>
        <div data-img-wrap style="position:relative;width:130px;">
          <img src="<?= SITE_URL ?>/uploads/properties/<?= e($img['filename']) ?>"
               style="width:130px;height:100px;object-fit:cover;border-radius:var(--r);border:2px solid <?= $img['is_primary']?'var(--gold)':'var(--border)' ?>;"
               alt="imazh">
          <?php if ($img['is_primary']): ?><span style="position:absolute;top:4px;left:4px;background:var(--gold);color:#fff;font-size:.65rem;padding:2px 6px;border-radius:4px;font-weight:700;">Kryesor</span><?php endif; ?>
          <div style="display:flex;gap:4px;margin-top:4px;">
            <?php if (!$img['is_primary']): ?>
            <button onclick="setImagePrimary(<?= $img['id'] ?>, <?= $edit_id ?>)" class="btn btn--sm" style="flex:1;font-size:.7rem;background:var(--gold-bg);color:var(--gold);">Kryesor</button>
            <?php endif; ?>
            <button onclick="deleteImage(<?= $img['id'] ?>, this)" class="btn btn--sm btn--danger" style="flex:1;font-size:.7rem;">Fshi</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php elseif ($active_tab === 'documents' && $edit_id): ?>
    <!-- DOCUMENTS TAB -->
    <div class="card card-body">
      <h3 style="margin-bottom:6px;">Dokumentet e Pronës</h3>
      <p style="color:var(--text-3);font-size:.875rem;margin-bottom:20px;">Ngarko dokumentet ligjore: AMTP, certifikatë pronësie, kontratë etj. Formatet: PDF, DOC, DOCX.</p>

      <div class="dropzone" id="doc-dropzone">
        <input type="file" id="doc-input" accept=".pdf,.doc,.docx" style="display:none;">
        <div class="dropzone__icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <p>Tërhiqni dokumentin këtu ose <span>klikoni për ta zgjedhur</span></p>
        <p style="font-size:.78rem;color:var(--text-3);margin-top:4px;">PDF, DOC, DOCX · max 10MB</p>
      </div>
      <button id="upload-doc-btn" class="btn btn--primary" style="margin-top:16px;" disabled>
        Ngarko Dokumentin
      </button>

      <?php if (!empty($existing_docs)): ?>
      <div class="divider"></div>
      <h4 style="margin-bottom:14px;">Dokumentet Aktuale</h4>
      <div id="docs-list">
        <?php foreach ($existing_docs as $doc): ?>
        <div data-doc-wrap style="display:flex;align-items:center;justify-content:space-between;padding:12px;background:var(--bg);border-radius:var(--r);margin-bottom:8px;border:1px solid var(--border);">
          <div style="display:flex;align-items:center;gap:10px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="color:var(--text-3)"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <div>
              <div style="font-weight:600;font-size:.875rem;"><?= e($doc['original_name']) ?></div>
              <div style="font-size:.75rem;color:var(--text-3);"><?= human_filesize($doc['file_size']) ?> · <?= format_date($doc['created_at'], 'd/m/Y H:i') ?></div>
            </div>
          </div>
          <div style="display:flex;gap:8px;">
            <a href="<?= SITE_URL ?>/uploads/documents/<?= e($doc['filename']) ?>"
               download="<?= e($doc['original_name']) ?>" class="btn btn--sm btn--outline-navy">Shkarko</a>
            <button onclick="deleteDocument(<?= $doc['id'] ?>, this)" class="btn btn--sm btn--danger">Fshi</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<script>
const PROP_ID    = <?= $edit_id ?>;
const CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
const UPLOAD_URL = '<?= SITE_URL ?>/api/upload.php';

/* IMAGE UPLOAD */
const imgDropzone = document.getElementById('img-dropzone');
const imgPreview  = document.getElementById('img-preview');
const uploadBtn   = document.getElementById('upload-imgs-btn');
let selectedFiles = [];

if (imgDropzone) {
  const imgInput = imgDropzone.querySelector('input');

  // Click to open file picker
  imgDropzone.addEventListener('click', () => imgInput.click());

  // File selected via picker
  imgInput.addEventListener('change', function () {
    selectedFiles = Array.from(this.files);
    renderPreviews();
    if (uploadBtn) uploadBtn.disabled = selectedFiles.length === 0;
  });

  // Drag and drop
  ['dragenter', 'dragover'].forEach(ev => imgDropzone.addEventListener(ev, e => {
    e.preventDefault(); imgDropzone.classList.add('drag-over');
  }));
  ['dragleave', 'drop'].forEach(ev => imgDropzone.addEventListener(ev, e => {
    e.preventDefault(); imgDropzone.classList.remove('drag-over');
  }));
  imgDropzone.addEventListener('drop', e => {
    const files = Array.from(e.dataTransfer.files).filter(f =>
      ['image/jpeg','image/png','image/webp'].includes(f.type));
    if (!files.length) return;
    selectedFiles = files;
    renderPreviews();
    if (uploadBtn) uploadBtn.disabled = false;
  });

  if (uploadBtn) {
    uploadBtn.addEventListener('click', async function () {
      if (!PROP_ID) { alert('Ruani pronën së pari para se të ngarkoni imazhe.'); return; }
      this.disabled = true;
      this.textContent = 'Duke ngarkuar...';
      for (let i = 0; i < selectedFiles.length; i++) {
        const fd = new FormData();
        fd.append('file', selectedFiles[i]);
        fd.append('type', 'property_image');
        fd.append('property_id', PROP_ID);
        fd.append('is_primary', i === 0 ? '1' : '0');
        fd.append('_proesta_csrf', CSRF_TOKEN);
        try {
          const res = await fetch(UPLOAD_URL, { method: 'POST', body: fd });
          const d   = await res.json();
          if (!d.success) { alert('Gabim: ' + d.message); }
        } catch (e) { alert('Gabim rrjeti. Provoni sërish.'); }
      }
      location.reload();
    });
  }
}

function renderPreviews() {
  if (!imgPreview) return;
  imgPreview.innerHTML = '';
  selectedFiles.forEach(f => {
    const r = new FileReader();
    r.onload = ev => {
      const item = document.createElement('div');
      item.className = 'file-preview-item';
      item.innerHTML = `<img src="${ev.target.result}" alt="">`;
      imgPreview.appendChild(item);
    };
    r.readAsDataURL(f);
  });
}

function setImagePrimary(imgId, propId) {
  $.post('<?= SITE_URL ?>/api/admin-actions.php',
    { action:'set_primary_image', img_id:imgId, prop_id:propId, _proesta_csrf:CSRF_TOKEN },
    function(r){ if(r.success){ location.reload(); } else { alert(r.message); } }, 'json');
}

function deleteImage(imgId, btn) {
  if (!confirm('Fshi imazhin?')) return;
  $.post('<?= SITE_URL ?>/api/admin-actions.php',
    { action:'delete_image', img_id:imgId, _proesta_csrf:CSRF_TOKEN },
    function(r){ if(r.success){ btn.closest('[data-img-wrap]').remove(); } else { alert(r.message); } }, 'json');
}

/* DOCUMENT UPLOAD */
const docDropzone = document.getElementById('doc-dropzone');
const docBtn      = document.getElementById('upload-doc-btn');

if (docDropzone) {
  const docInput = docDropzone.querySelector('input');

  // Click to open file picker
  docDropzone.addEventListener('click', () => docInput.click());

  docInput.addEventListener('change', function () {
    if (docBtn) docBtn.disabled = this.files.length === 0;
  });

  // Drag and drop
  ['dragenter','dragover'].forEach(ev => docDropzone.addEventListener(ev, e => {
    e.preventDefault(); docDropzone.classList.add('drag-over');
  }));
  ['dragleave','drop'].forEach(ev => docDropzone.addEventListener(ev, e => {
    e.preventDefault(); docDropzone.classList.remove('drag-over');
  }));
  docDropzone.addEventListener('drop', e => {
    const allowed = ['application/pdf','application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const files = Array.from(e.dataTransfer.files).filter(f => allowed.includes(f.type));
    if (!files.length) { alert('Vetëm PDF, DOC, DOCX lejohen.'); return; }
    // Assign to input via DataTransfer
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    docInput.files = dt.files;
    if (docBtn) docBtn.disabled = false;
  });

  if (docBtn) {
    docBtn.addEventListener('click', async function () {
      if (!PROP_ID) { alert('Ruani pronën së pari.'); return; }
      const file = docInput.files[0];
      if (!file) return;
      this.disabled = true;
      this.textContent = 'Duke ngarkuar...';
      const fd = new FormData();
      fd.append('file', file);
      fd.append('type', 'document');
      fd.append('property_id', PROP_ID);
      fd.append('_proesta_csrf', CSRF_TOKEN);
      try {
        const res = await fetch(UPLOAD_URL, { method: 'POST', body: fd });
        const d   = await res.json();
        if (d.success) { location.reload(); }
        else { alert('Gabim: ' + d.message); this.disabled = false; this.textContent = 'Ngarko Dokumentin'; }
      } catch (e) { alert('Gabim rrjeti.'); this.disabled = false; }
    });
  }
}

function deleteDocument(docId, btn) {
  if (!confirm('Fshi dokumentin?')) return;
  $.post('<?= SITE_URL ?>/api/admin-actions.php',
    { action:'delete_document', doc_id:docId, _proesta_csrf:CSRF_TOKEN },
    function(r){ if(r.success){ btn.closest('[data-doc-wrap]').remove(); } else { alert(r.message); } }, 'json');
}
</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
