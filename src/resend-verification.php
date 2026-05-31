<?php
// resend-verification.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

if (is_logged_in()) redirect(SITE_URL . '/dashboard/index.php');

$success = false;
$error = '';
$debug_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer();
    csrf_check();

    if (!rate_limit('resend_verify', 3, 600)) {
        $error = 'Shume kerkesa. Prisni 10 minuta dhe provoni serish.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!is_valid_email($email)) {
            $error = 'Email i pavlefshem.';
        } else {
            $user = db_row(
                "SELECT id, first_name, email_verified, verification_token
                 FROM users WHERE email=? LIMIT 1",
                [$email]
            );

            if ($user && (int)$user['email_verified'] === 0) {
                $token = $user['verification_token'] ?: generate_token();
                if (!$user['verification_token']) {
                    db_query("UPDATE users SET verification_token=? WHERE id=?", [$token, $user['id']]);
                }

                $sent = send_welcome_email($email, $user['first_name'], $token);
                log_activity((int)$user['id'], 'verification_resend', $email, get_client_ip());

                if (!$sent && APP_DEBUG) {
                    $debug_link = SITE_URL . '/verify-email.php?token=' . urlencode($token);
                }
            }

            $success = true;
        }
    }
}

$page_title = 'Ridergo Verifikimin - ProEstate';
?>
<!DOCTYPE html>
<html lang="sq">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="page-auth">
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-card__inner">
      <div class="auth-logo"><a href="<?= SITE_URL ?>">Pro<strong>Estate</strong></a></div>
      <h2 class="auth-title">Ridergo Verifikimin</h2>
      <p class="auth-subtitle">Shkruani email-in dhe do t'ju dergojme linkun e verifikimit.</p>

      <?php if ($success): ?>
      <div class="flash flash--success" style="position:static;max-width:none;margin-bottom:20px;">
        <span class="flash__icon">OK</span>
        <span>Nese email-i ekziston dhe nuk eshte verifikuar, linku u dergua. Kontrolloni edhe spam.</span>
      </div>
      <?php if ($debug_link): ?>
      <div class="flash flash--warning" style="position:static;max-width:none;margin-bottom:20px;">
        <span class="flash__icon">!</span>
        <span>SMTP nuk dergoi email. Link testimi lokal: <a href="<?= e($debug_link) ?>"><?= e($debug_link) ?></a></span>
      </div>
      <?php endif; ?>
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
          <label>Email</label>
          <input type="email" name="email" class="form-control" placeholder="email@juaj.com" required autofocus
                 value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn--primary btn--full btn--lg">Ridergo Email-in</button>
      </form>
      <div class="divider"></div>
      <p style="text-align:center;font-size:.875rem;">
        <a href="<?= SITE_URL ?>/login.php" style="color:var(--gold);">Kthehu te Hyrja</a>
      </p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
