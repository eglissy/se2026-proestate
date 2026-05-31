<?php
ob_start();
// dashboard/profile.php - Profili i perdoruesit
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_auth();
$uid  = current_user_id();
$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer();
    csrf_check();

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $first_name   = sanitize($_POST['first_name'] ?? '');
        $last_name    = sanitize($_POST['last_name'] ?? '');
        $phone        = preg_replace('/[\s\-\(\)]/', '', sanitize($_POST['phone'] ?? '')) ?? '';
        $gender       = sanitize($_POST['gender'] ?? 'unspecified');
        $bio          = sanitize($_POST['bio'] ?? '');
        $city         = sanitize($_POST['city'] ?? '');
        $agency_name  = sanitize($_POST['agency_name'] ?? '');

        if (strlen($first_name) < 2) $errors['first_name'] = 'Emri i shkurtër.';
        if (strlen($last_name) < 2)  $errors['last_name']  = 'Mbiemri i shkurtër.';

        if (!in_array($gender, ['female','male','other','unspecified'], true)) $errors['gender'] = 'Zgjidhni gjinine.';
        if ($phone !== '' && !is_valid_phone($phone)) $errors['phone'] = 'Numri duhet te jete ne formatin +35567XXXXXXX, +35568XXXXXXX ose +35569XXXXXXX.';

        if (empty($errors)) {
            db_query(
                "UPDATE users SET first_name=?, last_name=?, phone=?, gender=?, bio=?, city=?, agency_name=? WHERE id=?",
                [$first_name, $last_name, $phone, $gender, $bio, $city, $agency_name, $uid]
            );
            $_SESSION['user_name']       = $first_name . ' ' . $last_name;
            $_SESSION['user_first_name'] = $first_name;
            flash_success('Profili u përditësua!');
            log_activity($uid, 'profile_updated', '', get_client_ip());
            redirect(SITE_URL . '/dashboard/profile.php');
        }
    }

    if ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pwd  = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!verify_password($current, $user['password'])) {
            $errors['current_password'] = 'Fjalëkalimi aktual është i gabuar.';
        } elseif (strlen($new_pwd) < 8) {
            $errors['new_password'] = 'Fjalëkalimi i ri duhet të ketë të paktën 8 karaktere.';
        } elseif ($new_pwd !== $confirm) {
            $errors['confirm_password'] = 'Fjalëkalimet nuk përputhen.';
        }

        if (empty($errors)) {
            db_query("UPDATE users SET password=? WHERE id=?", [hash_password($new_pwd), $uid]);
            flash_success('Fjalëkalimi u ndryshua me sukses!');
            log_activity($uid, 'password_changed', '', get_client_ip());
            redirect(SITE_URL . '/dashboard/profile.php');
        }
    }
}

// Rifresko user pas mundshëm update
$user = db_row("SELECT * FROM users WHERE id=?", [$uid]);

// Vlerësimet e agjentit
$reviews = [];
$avg_rating = 0;
if ($user['role'] === 'agent') {
    $reviews = db_rows(
        "SELECT r.*, u.first_name, u.last_name, u.avatar FROM reviews r
         JOIN users u ON u.id=r.reviewer_id WHERE r.agent_id=? ORDER BY r.created_at DESC",
        [$uid]
    );
    $avg = db_row("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE agent_id=?", [$uid]);
    $avg_rating = round((float)($avg['avg']??0), 1);
}

$page_title = 'Profili im - ProEstate';
require dirname(__DIR__) . '/templates/header.php';
?>

<div class="dashboard">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header">
      <h1 class="dashboard__title">Profili im</h1>
    </div>

    <div class="profile-layout">

      <!-- Avatar card -->
      <div style="display:flex;flex-direction:column;gap:16px;">
        <div class="card card-body" style="text-align:center;">
          <img src="<?= get_avatar_url($user['avatar']) ?>" id="avatar-preview"
               style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin:0 auto 16px;border:3px solid var(--gold);"
               alt="Avatar">
          <h3 style="font-size:1.1rem;"><?= e($user['first_name'].' '.$user['last_name']) ?></h3>
          <p style="color:var(--gray-500);font-size:.825rem;margin:4px 0 16px;"><?= role_label($user['role']) ?></p>

          <!-- Upload avatar -->
          <div>
            <label for="avatar-input" class="btn btn--outline-navy btn--sm" style="cursor:pointer;">
              Ndrysho Foton
            </label>
            <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/webp" style="display:none;">
          </div>

          <?php if ($user['role'] === 'agent'): ?>
          <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--gray-100);">
            <?= render_stars($avg_rating) ?>
            <p style="font-size:.8rem;color:var(--gray-500);margin-top:4px;"><?= $avg_rating ?>/5 · <?= count($reviews) ?> vlerësime</p>
          </div>
          <?php endif; ?>

          <?php if ($user['email_verified']): ?>
          <div style="margin-top:12px;display:flex;align-items:center;justify-content:center;gap:6px;font-size:.78rem;color:var(--green);">
            ✓ Email i Verifikuar
          </div>
          <?php endif; ?>
        </div>

        <div class="card card-body">
          <h4 style="margin-bottom:10px;font-size:.875rem;color:var(--gray-700);">Informacion</h4>
          <div style="font-size:.825rem;color:var(--gray-600);display:flex;flex-direction:column;gap:6px;">
            <div>Email: <?= e($user['email']) ?></div>
            <?php if ($user['phone']): ?><div>Telefon: <?= e($user['phone']) ?></div><?php endif; ?>
            <div>Gjinia: <?= e(gender_label($user['gender'] ?? 'unspecified')) ?></div>
            <?php if ($user['city']): ?><div>Qytet: <?= e($user['city']) ?></div><?php endif; ?>
            <?php if ($user['agency_name']): ?><div>Agjenci: <?= e($user['agency_name']) ?></div><?php endif; ?>
            <?php if ($user['license_number']): ?><div>Licencë: <?= e($user['license_number']) ?></div><?php endif; ?>
            <div>Regjistruar: <?= format_date($user['created_at']) ?></div>
          </div>
        </div>
      </div>

      <!-- Forms -->
      <div style="display:flex;flex-direction:column;gap:20px;">

        <!-- Update profile -->
        <div class="card card-body">
          <h3 style="margin-bottom:20px;">Edito Profilin</h3>
          <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="form-row">
              <div class="form-group">
                <label>Emri <span class="req">*</span></label>
                <input type="text" name="first_name" class="form-control <?= isset($errors['first_name'])?'is-invalid':'' ?>"
                       value="<?= e($user['first_name']) ?>" required>
                <?php if (isset($errors['first_name'])): ?><div class="form-error"><?= e($errors['first_name']) ?></div><?php endif; ?>
              </div>
              <div class="form-group">
                <label>Mbiemri <span class="req">*</span></label>
                <input type="text" name="last_name" class="form-control <?= isset($errors['last_name'])?'is-invalid':'' ?>"
                       value="<?= e($user['last_name']) ?>" required>
                <?php if (isset($errors['last_name'])): ?><div class="form-error"><?= e($errors['last_name']) ?></div><?php endif; ?>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Telefoni</label>
                <input type="tel" name="phone" class="form-control <?= isset($errors['phone'])?'is-invalid':'' ?>"
                       value="<?= e($user['phone'] ?? '') ?>" placeholder="+355691234567"
                       pattern="^\+3556[789][0-9]{7}$" maxlength="13" inputmode="tel"
                       title="Lejohen vetem numra ne formatin +35567XXXXXXX, +35568XXXXXXX ose +35569XXXXXXX">
                <?php if (isset($errors['phone'])): ?><div class="form-error"><?= e($errors['phone']) ?></div><?php endif; ?>
              </div>
              <div class="form-group">
                <label>Gjinia</label>
                <select name="gender" class="form-control <?= isset($errors['gender'])?'is-invalid':'' ?>">
                  <?php foreach (['unspecified'=>'Nuk preferoj ta them','female'=>'Femer','male'=>'Mashkull','other'=>'Tjeter'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= ($user['gender'] ?? 'unspecified') === $v ? 'selected' : '' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($errors['gender'])): ?><div class="form-error"><?= e($errors['gender']) ?></div><?php endif; ?>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Qyteti</label>
                <input type="text" name="city" class="form-control" value="<?= e($user['city'] ?? '') ?>">
              </div>
              <div class="form-group"></div>
            </div>

            <?php if (in_array($user['role'], ['agent','owner'])): ?>
            <div class="form-group">
              <label>Emri i Agjencisë</label>
              <input type="text" name="agency_name" class="form-control" value="<?= e($user['agency_name'] ?? '') ?>">
            </div>
            <?php endif; ?>

            <div class="form-group">
              <label>Bio / Rreth Meje</label>
              <textarea name="bio" class="form-control" rows="4" placeholder="Përshkruani veten, eksperiencën dhe specializimin tuaj..."><?= e($user['bio'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn--primary">Ruaj Profilin</button>
          </form>
        </div>

        <!-- Change password -->
        <div class="card card-body">
          <h3 style="margin-bottom:20px;">Ndrysho Fjalëkalimin</h3>
          <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
              <label>Fjalëkalimi Aktual</label>
              <input type="password" name="current_password" class="form-control <?= isset($errors['current_password'])?'is-invalid':'' ?>"
                     placeholder="••••••••" required>
              <?php if (isset($errors['current_password'])): ?><div class="form-error"><?= e($errors['current_password']) ?></div><?php endif; ?>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Fjalëkalimi i Ri</label>
                <input type="password" name="new_password" class="form-control <?= isset($errors['new_password'])?'is-invalid':'' ?>"
                       placeholder="Min. 8 karaktere" required>
                <?php if (isset($errors['new_password'])): ?><div class="form-error"><?= e($errors['new_password']) ?></div><?php endif; ?>
              </div>
              <div class="form-group">
                <label>Konfirmo Fjalëkalimin</label>
                <input type="password" name="confirm_password" class="form-control <?= isset($errors['confirm_password'])?'is-invalid':'' ?>"
                       placeholder="Ripërsërit fjalëkalimin" required>
                <?php if (isset($errors['confirm_password'])): ?><div class="form-error"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
              </div>
            </div>
            <button type="submit" class="btn btn--navy">Ndrysho Fjalëkalimin</button>
          </form>
        </div>

        <!-- Reviews (for agents) -->
        <?php if (!empty($reviews)): ?>
        <div class="card card-body">
          <h3 style="margin-bottom:16px;">Vlerësimet e Mia</h3>
          <?php foreach ($reviews as $rev): ?>
          <div style="padding:14px 0;border-bottom:1px solid var(--gray-100);">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
              <img src="<?= get_avatar_url($rev['avatar']) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
              <div>
                <strong><?= e($rev['first_name'].' '.$rev['last_name']) ?></strong>
                <div style="font-size:.75rem;color:var(--gray-400);"><?= time_ago($rev['created_at']) ?></div>
              </div>
              <?= render_stars($rev['rating']) ?>
            </div>
            <?php if ($rev['comment']): ?>
            <p style="color:var(--gray-600);font-size:.875rem;font-style:italic;">"<?= e($rev['comment']) ?>"</p>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script>
const CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
const avatarInput = document.getElementById('avatar-input');
const avatarPreview = document.getElementById('avatar-preview');

avatarInput.addEventListener('change', async function () {
  const file = this.files[0];
  if (!file) return;

  // Preview immediately
  const reader = new FileReader();
  reader.onload = e => avatarPreview.src = e.target.result;
  reader.readAsDataURL(file);

  // Upload
  const fd = new FormData();
  fd.append('avatar', file);
  fd.append('type', 'avatar');
  fd.append('_proesta_csrf', CSRF_TOKEN);

  try {
    const res  = await fetch('<?= SITE_URL ?>/api/upload.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) {
      avatarPreview.src = data.url + '?t=' + Date.now();
      // Update header avatar
      document.querySelectorAll('.avatar-sm').forEach(img => img.src = data.url);
    } else {
      alert('Gabim: ' + data.message);
    }
  } catch(e) {
    alert('Gabim rrjeti.');
  }
});
</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
