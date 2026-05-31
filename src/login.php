<?php
ob_start();
// login.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

if (is_logged_in()) redirect(SITE_URL . '/dashboard/index.php');

$error = '';
$next  = sanitize($_POST['next'] ?? ($_GET['next'] ?? ''));

function safe_login_redirect(string $next): string {
    if ($next === '') {
        return SITE_URL . '/dashboard/index.php';
    }
    $site_host = parse_url(SITE_URL, PHP_URL_HOST);
    $next_host = parse_url($next, PHP_URL_HOST);
    if ($next_host && $site_host && $next_host === $site_host) {
        return $next;
    }
    if ($next[0] === '/' && strpos($next, '//') !== 0) {
        return $next;
    }
    return SITE_URL . '/dashboard/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer();
    csrf_check();
    $email_for_limit = strtolower(trim($_POST['email'] ?? ''));
    $limit = login_attempts_allowed($email_for_limit, 5, 10);
    if (!$limit['allowed']) {
        $error = 'Shumë tentativa të pasuksesshme. Provoni sërish pas rreth ' . (int)$limit['remaining'] . ' minutash.';
    } else {
        $result = login_user($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) {
            flash_success('Mirë se u kthyet, ' . $result['user']['first_name'] . '!');
            redirect(safe_login_redirect($next));
        } else {
            $error = $result['message'];
        }
    }
}

$page_title = 'Hyrje - ProEstate';
$body_class = 'page-auth';
?>
<!DOCTYPE html>
<html lang="sq">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="page-auth">
<div id="flash-container"><?= flash_render() ?></div>

<div class="auth-page">
  <div class="auth-card">
  <div class="auth-card__inner">
    <div class="auth-logo"><a href="<?= SITE_URL ?>">Pro<strong>Estate</strong></a></div>
    <h2 class="auth-title">Mirë se u kthyet</h2>
    <p class="auth-subtitle">Hyni në llogarinë tuaj ProEstate</p>

    <?php if ($error): ?>
    <div class="flash flash--error" style="margin-bottom:20px;">
      <span class="flash__icon">X</span><span><?= e($error) ?></span>
    </div>
    <?php endif; ?>

    <p style="font-size:.82rem;color:var(--text-3);margin:-8px 0 18px;">
      Nuk ju erdhi email-i i verifikimit?
      <a href="<?= SITE_URL ?>/resend-verification.php" style="color:var(--gold);font-weight:600;">Ridergoje</a>
    </p>

    <form method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">

      <div class="form-group">
        <label for="email">Email <span class="req">*</span></label>
        <input type="email" id="email" name="email" class="form-control"
               value="<?= e($_POST['email'] ?? '') ?>"
               placeholder="email@juaj.com" required autofocus autocomplete="email">
      </div>

      <div class="form-group">
        <label for="password">Fjalëkalimi <span class="req">*</span></label>
        <div style="position:relative;">
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="••••••••" required autocomplete="current-password"
                 style="padding-right:44px;">
          <button type="button" onclick="togglePwd('password')"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--text-3);display:flex;align-items:center;background:none;border:none;cursor:pointer;">
            <svg id="pwd-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div class="form-check">
          <input type="checkbox" id="remember" name="remember" value="1">
          <label for="remember">Më mbaj mend</label>
        </div>
        <a href="<?= SITE_URL ?>/forgot-password.php" style="font-size:.825rem;color:var(--gold);">Harrova fjalëkalimin</a>
      </div>

      <button type="submit" class="btn btn--primary btn--full btn--lg">Hyr në Llogari</button>
    </form>

    <div class="divider"></div>
    <p style="text-align:center;font-size:.875rem;color:var(--text-2);">
      Nuk keni llogari?
      <a href="<?= SITE_URL ?>/register.php" style="color:var(--gold);font-weight:600;">Regjistrohu falas</a>
    </p>

  </div>
</div>

<script>
function togglePwd(id) {
  const f = document.getElementById(id);
  const isText = f.type === 'password';
  f.type = isText ? 'text' : 'password';
  const eye = document.getElementById('pwd-eye');
  if (eye) eye.style.opacity = isText ? '.4' : '1';
}
</script>
</body>
</html>
