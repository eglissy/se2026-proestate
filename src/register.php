<?php
ob_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';
 
if (is_logged_in()) redirect(SITE_URL . '/dashboard/index.php');
 
$errors = [];
$values = [];
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer();
    csrf_check();
 
    $values = [
        'first_name'=> sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'email'     => strtolower(trim($_POST['email'] ?? '')),
        'phone'     => preg_replace('/[\s\-\(\)]/', '', sanitize($_POST['phone'] ?? '')) ?? '',
        'gender'    => sanitize($_POST['gender'] ?? 'unspecified'),
        'role'      => sanitize($_POST['role'] ?? 'client'),
        'password'  => $_POST['password'] ?? '',
        'password2' => $_POST['password2'] ?? '',
    ];
 
    if (strlen($values['first_name']) < 2)       $errors['first_name'] = 'Emri duhet të jetë të paktën 2 karaktere.';
    if (strlen($values['last_name']) < 2)         $errors['last_name']  = 'Mbiemri duhet të jetë të paktën 2 karaktere.';
    if (!is_valid_email($values['email']))         $errors['email']      = 'Email i pavlefshëm.';
    if (strlen($values['password']) < 8)          $errors['password']   = 'Fjalëkalimi duhet të ketë të paktën 8 karaktere.';
    if ($values['password'] !== $values['password2']) $errors['password2'] = 'Fjalëkalimet nuk përputhen.';
    if (!isset($_POST['terms']))                  $errors['terms']      = 'Duhet të pranoni kushtet.';
 
    if ($values['phone'] !== '' && !is_valid_phone($values['phone'])) {
        $errors['phone'] = 'Numri duhet te jete ne formatin +35567XXXXXXX, +35568XXXXXXX ose +35569XXXXXXX.';
    }

    if (empty($errors)) {
        $result = register_user($values);
        if ($result['success']) {
            if (APP_DEBUG && empty($result['mail_sent']) && !empty($result['verify_url'])) {
                flash_warning('Email-i nuk u dergua nga XAMPP. Per testim lokal, perdorni linkun e verifikimit: ' . $result['verify_url']);
            }
            if ($values['role'] === 'agent') {
                flash_info('Kontrolloni email-in dhe verifikoni llogarine para hyrjes.');
                flash_success('Llogaria u krijua me sukses! Një administrator do ta rishikojë kërkesën tuaj brenda 24 orëve dhe do të njoftoheni me email kur të aprovohet.');
            } else {
                flash_success('Llogaria u krijua! Kontrolloni email-in per verifikim para hyrjes.');
            }
            redirect(SITE_URL . '/login.php');
        } else {
            $errors['general'] = $result['message'];
        }
    }
}
 
$page_title = 'Regjistrohu - ProEstate';
?>
<!DOCTYPE html>
<html lang="sq">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,500;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="page-auth">
<div id="flash-container"><?= flash_render() ?></div>
 
<div class="auth-page">
  <div class="auth-card" style="max-width:540px;">
  <div class="auth-card__inner">
    <div class="auth-logo"><a href="<?= SITE_URL ?>">Pro<strong>Estate</strong></a></div>
    <h2 class="auth-title">Krijo Llogari Falas</h2>
    <p class="auth-subtitle">Krijoni profilin për të ruajtur prona, dërguar mesazhe dhe rezervuar takime</p>
 
    <?php if (!empty($errors['general'])): ?>
    <div class="flash flash--error" style="margin-bottom:20px;">
      <span class="flash__icon">X</span><span><?= e($errors['general']) ?></span>
    </div>
    <?php endif; ?>

    <div class="notice-pending" style="background:var(--blue-bg);color:var(--blue);border-color:#bfcffd;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <p>
        <strong>Vendosni një email real.</strong>
        Pas regjistrimit do të dërgohet një link verifikimi në email. Pa verifikim nuk mund të hyni në llogari.
      </p>
    </div>
 
    <form method="POST" action="">
      <?= csrf_field() ?>
 
      <!-- Roli -->
      <div class="form-group">
        <label>Roli juaj <span class="req">*</span></label>
        <div class="role-selector">
          <?php $roles = [
            ['client','Klient','Kërkoj pronë për të blerë ose marrë me qira',
             '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
            ['agent','Agjent','Jam agjent imobiliar me licencë',
             '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>'],
            ['owner','Pronar','Kam pronë për të shitur ose dhënë me qira',
             '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
          ]; foreach ($roles as $r): ?>
          <div class="role-option">
            <input type="radio" name="role" id="role_<?= $r[0] ?>" value="<?= $r[0] ?>"
                   <?= ($values['role'] ?? 'client') === $r[0] ? 'checked' : '' ?>>
            <label for="role_<?= $r[0] ?>">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $r[3] ?></svg>
              <?= $r[1] ?>
              <?php if ($r[0] === 'agent'): ?>
              <span style="display:block;font-size:.68rem;color:var(--gold);font-weight:600;margin-top:2px;">Kërkon aprovim admin</span>
              <?php endif; ?>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
 
      <!-- Agent notice - shown only when agent is selected -->
      <div id="agent-notice" class="notice-pending" style="display:none;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p>
          <strong>Llogaria e agjentit kërkon aprovim</strong>
          Pas regjistrimit, llogaria juaj shkon në pritje. Një administrator do ta rishikojë dhe aprovoje brenda 24 orëve. Nuk do të keni akses deri sa të aprovohet.
        </p>
      </div>
 
      <div class="form-row">
        <div class="form-group">
          <label for="first_name">Emri <span class="req">*</span></label>
          <input type="text" id="first_name" name="first_name" class="form-control <?= isset($errors['first_name'])?'is-invalid':'' ?>"
                 value="<?= e($values['first_name'] ?? '') ?>" placeholder="Klajdi" required autocomplete="given-name">
          <?php if (isset($errors['first_name'])): ?><div class="form-error"><?= e($errors['first_name']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label for="last_name">Mbiemri <span class="req">*</span></label>
          <input type="text" id="last_name" name="last_name" class="form-control <?= isset($errors['last_name'])?'is-invalid':'' ?>"
                 value="<?= e($values['last_name'] ?? '') ?>" placeholder="Prifti" required autocomplete="family-name">
          <?php if (isset($errors['last_name'])): ?><div class="form-error"><?= e($errors['last_name']) ?></div><?php endif; ?>
        </div>
      </div>
 
      <div class="form-group">
        <label for="gender">Gjinia</label>
        <select id="gender" name="gender" class="form-control">
          <?php foreach (['unspecified'=>'Nuk preferoj ta them','female'=>'Femer','male'=>'Mashkull','other'=>'Tjeter'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= ($values['gender'] ?? 'unspecified') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['gender'])): ?><div class="form-error"><?= e($errors['gender']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label for="email">Email <span class="req">*</span></label>
        <input type="email" id="email" name="email" class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>"
               value="<?= e($values['email'] ?? '') ?>" placeholder="email@juaj.com" required autocomplete="email">
        <small style="display:block;margin-top:6px;color:var(--text-3);font-size:.76rem;">
          Përdorni një email që e hapni, sepse aty do të merrni linkun e verifikimit.
        </small>
        <?php if (isset($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>
 
      <div class="form-group">
        <label for="phone">Numri i Telefonit</label>
        <input type="tel" id="phone" name="phone" class="form-control"
               value="<?= e($values['phone'] ?? '') ?>" placeholder="+355691234567"
               pattern="^\+3556[789][0-9]{7}$" maxlength="13" inputmode="tel"
               title="Lejohen vetem numra ne formatin +35567XXXXXXX, +35568XXXXXXX ose +35569XXXXXXX"
               autocomplete="tel">
        <small style="display:block;margin-top:6px;color:var(--text-3);font-size:.76rem;">
          Formati i lejuar: +35567XXXXXXX, +35568XXXXXXX ose +35569XXXXXXX.
        </small>
        <?php if (isset($errors['phone'])): ?><div class="form-error"><?= e($errors['phone']) ?></div><?php endif; ?>
      </div>
 
      <div class="form-row">
        <div class="form-group">
          <label for="password">Fjalëkalimi <span class="req">*</span></label>
          <input type="password" id="password" name="password" class="form-control <?= isset($errors['password'])?'is-invalid':'' ?>"
                 placeholder="Min. 8 karaktere" required autocomplete="new-password">
          <?php if (isset($errors['password'])): ?><div class="form-error"><?= e($errors['password']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label for="password2">Konfirmo Fjalëkalimin <span class="req">*</span></label>
          <input type="password" id="password2" name="password2" class="form-control <?= isset($errors['password2'])?'is-invalid':'' ?>"
                 placeholder="Përsërit fjalëkalimin" required autocomplete="new-password">
          <?php if (isset($errors['password2'])): ?><div class="form-error"><?= e($errors['password2']) ?></div><?php endif; ?>
        </div>
      </div>
 
      <!-- Password strength -->
      <div id="pwd-strength" style="height:4px;background:var(--border);border-radius:2px;margin:-12px 0 16px;overflow:hidden;">
        <div id="pwd-bar" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:2px;"></div>
      </div>
 
      <div class="form-group">
        <div class="form-check">
          <input type="checkbox" id="terms" name="terms" required>
          <label for="terms">
            Pajtohem me <a href="#" style="color:var(--gold);">Kushtet e Shërbimit</a> dhe
            <a href="#" style="color:var(--gold);">Politikën e Privatësisë</a> <span class="req">*</span>
          </label>
        </div>
        <?php if (isset($errors['terms'])): ?><div class="form-error"><?= e($errors['terms']) ?></div><?php endif; ?>
      </div>
 
      <button type="submit" class="btn btn--primary btn--full btn--lg">Krijo Llogarinë</button>
    </form>
 
    <div class="divider"></div>
    <p style="text-align:center;font-size:.875rem;color:var(--text-2);">
      Keni llogari?
      <a href="<?= SITE_URL ?>/login.php" style="color:var(--gold);font-weight:600;">Hyni këtu</a>
    </p>
  </div>
</div>
 
<script>
// Show agent pending notice when agent role is selected
document.querySelectorAll('input[name="role"]').forEach(r => {
  r.addEventListener('change', function () {
    const notice = document.getElementById('agent-notice');
    if (notice) notice.style.display = this.value === 'agent' ? 'flex' : 'none';
  });
});
// Run on load in case agent is pre-selected (e.g. validation error)
const preSelected = document.querySelector('input[name="role"]:checked');
if (preSelected && preSelected.value === 'agent') {
  const notice = document.getElementById('agent-notice');
  if (notice) notice.style.display = 'flex';
}
 
const pwdInput = document.getElementById('password');
const pwdBar   = document.getElementById('pwd-bar');
pwdInput.addEventListener('input', function () {
  const v = this.value;
  let score = 0;
  if (v.length >= 8)         score++;
  if (/[A-Z]/.test(v))       score++;
  if (/[0-9]/.test(v))       score++;
  if (/[^A-Za-z0-9]/.test(v))score++;
  const colors = ['#ef4444','#f97316','#eab308','#22c55e'];
  const widths  = ['25%','50%','75%','100%'];
  pwdBar.style.width      = score ? widths[score-1] : '0';
  pwdBar.style.background = score ? colors[score-1] : '';
});
</script>
</body>
</html>
