<?php
// forgot-password.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

if (is_logged_in()) redirect(SITE_URL . '/dashboard/index.php');

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer(); csrf_check();
    if (!rate_limit('forgot_pwd', 3, 600)) {
        $error = 'Shumë kërkesa. Prisni 10 minuta.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!is_valid_email($email)) {
            $error = 'Email i pavlefshëm.';
        } else {
            $user = db_row("SELECT id, first_name FROM users WHERE email=? AND is_active=1", [$email]);
            if ($user) {
                $token   = generate_token(32);
                $expires = date('Y-m-d H:i:s', strtotime('+2 hours'));
                db_query("UPDATE users SET reset_token=?, reset_token_expires=? WHERE id=?",
                         [$token, $expires, $user['id']]);
                send_password_reset_email($email, $user['first_name'], $token);
                log_activity($user['id'], 'password_reset_request', $email, get_client_ip());
            }
            // Trego sukses gjithmonë (security: mos zbulo nëse email ekziston)
            $success = true;
        }
    }
}

$page_title = 'Harrova Fjalëkalimin - ProEstate';
?>
<!DOCTYPE html>
<html lang="sq">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="page-auth">
<div class="auth-page">
  <div class="auth-card">
  <div class="auth-card__inner">
    <div class="auth-logo"><a href="<?= SITE_URL ?>">Pro<strong>Estate</strong></a></div>
    <h2 class="auth-title">Harrova Fjalëkalimin</h2>
    <p class="auth-subtitle">Shkruani emailin tuaj dhe do t'ju dërgojmë udhëzime për rivendosje</p>

    <?php if ($success): ?>
    <div class="flash flash--success" style="position:static;max-width:none;margin-bottom:20px;">
      <span class="flash__icon">✓</span>
      <span>Nëse ky email ekziston, do të merrni një link rivendosjeje brenda pak minutash. Kontrolloni edhe folderin spam.</span>
    </div>
    <a href="<?= SITE_URL ?>/login.php" class="btn btn--navy btn--full">Kthehu te Hyrja</a>
    <?php else: ?>
    <?php if ($error): ?>
    <div class="flash flash--error" style="position:static;max-width:none;margin-bottom:20px;">
      <span class="flash__icon">X</span><span><?= e($error) ?></span>
    </div>
    <?php endif; ?>
    <form method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Email-i Juaj</label>
        <input type="email" name="email" class="form-control" placeholder="email@juaj.com" required autofocus
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn--primary btn--full btn--lg">Dërgo Linkun e Rivendosjes</button>
    </form>
    <div class="divider"></div>
    <p style="text-align:center;font-size:.875rem;">
      <a href="<?= SITE_URL ?>/login.php" style="color:var(--gold);">Kthehu te Hyrja</a>
    </p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
