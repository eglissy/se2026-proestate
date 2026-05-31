<?php
ob_start();
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (is_logged_in()) {
    redirect(current_user_role() === 'admin' ? SITE_URL . '/admin/index.php' : SITE_URL . '/dashboard/index.php?err=noperm');
}

$error = '';
$next = sanitize($_POST['next'] ?? ($_GET['next'] ?? ''));

function safe_admin_login_redirect(string $next): string {
    if ($next === '') {
        return SITE_URL . '/admin/index.php';
    }

    $site_host = parse_url(SITE_URL, PHP_URL_HOST);
    $next_host = parse_url($next, PHP_URL_HOST);
    if ($next_host && $site_host && $next_host !== $site_host) {
        return SITE_URL . '/admin/index.php';
    }

    $path = parse_url($next, PHP_URL_PATH) ?: '';
    $base = parse_url(SITE_URL, PHP_URL_PATH) ?: '';
    $admin_prefix = rtrim($base, '/') . '/admin/';

    if (str_starts_with($path, $admin_prefix) || str_starts_with($path, '/admin/')) {
        return $next;
    }

    return SITE_URL . '/admin/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer();
    csrf_check();

    $email_for_limit = strtolower(trim($_POST['email'] ?? ''));
    $limit = login_attempts_allowed($email_for_limit, 5, 10);
    if (!$limit['allowed']) {
        $error = 'Shume tentativa te pasuksesshme. Provoni serish pas rreth ' . (int)$limit['remaining'] . ' minutash.';
    } else {
        $result = login_user($_POST['email'] ?? '', $_POST['password'] ?? '', true);
        if ($result['success']) {
            flash_success('Mire se u kthyet, ' . $result['user']['first_name'] . '!');
            redirect(safe_admin_login_redirect($next));
        }
        $error = $result['message'];
    }
}

$page_title = 'Hyrje Admin - ProEstate';
?>
<!DOCTYPE html>
<html lang="sq">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
      <div class="auth-logo"><a href="<?= SITE_URL ?>">Pro<strong>Estate</strong> Admin</a></div>
      <h2 class="auth-title">Hyrje Administratori</h2>
      <p class="auth-subtitle">Akses i dedikuar per panelin e administrimit</p>

      <?php if ($error): ?>
      <div class="flash flash--error" style="margin-bottom:20px;">
        <span class="flash__icon">X</span><span><?= e($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <div class="form-group">
          <label for="email">Email Admin <span class="req">*</span></label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 placeholder="email i administratorit" required autofocus autocomplete="email">
        </div>

        <div class="form-group">
          <label for="password">Fjalekalimi <span class="req">*</span></label>
          <div style="position:relative;">
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="********" required autocomplete="current-password"
                   style="padding-right:44px;">
            <button type="button" onclick="togglePwd('password')"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--text-3);display:flex;align-items:center;background:none;border:none;cursor:pointer;">
              <svg id="pwd-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn--primary btn--full btn--lg">Hyr ne Admin</button>
      </form>

      <div class="divider"></div>
      <p style="text-align:center;font-size:.875rem;color:var(--text-2);">
        Nuk jeni administrator?
        <a href="<?= SITE_URL ?>/login.php" style="color:var(--gold);font-weight:600;">Hyrje perdoruesi</a>
      </p>
    </div>
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
