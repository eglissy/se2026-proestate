<?php
// reset-password.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$token = sanitize($_GET['token'] ?? '');
$user  = null;
$error = '';

if ($token) {
    $user = db_row(
        "SELECT id, first_name, email FROM users
         WHERE reset_token=? AND reset_token_expires > NOW() AND is_active=1",
        [$token]
    );
}

if (!$user && $token) {
    $error = 'Ky link është i pavlefshëm ose ka skaduar. Kërkoni rivendosje të re.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    check_referrer(); csrf_check();
    $pwd  = $_POST['password'] ?? '';
    $pwd2 = $_POST['password2'] ?? '';

    if (strlen($pwd) < 8) {
        $error = 'Fjalëkalimi duhet të ketë të paktën 8 karaktere.';
    } elseif ($pwd !== $pwd2) {
        $error = 'Fjalëkalimet nuk përputhen.';
    } else {
        db_query(
            "UPDATE users SET password=?, reset_token=NULL, reset_token_expires=NULL WHERE id=?",
            [hash_password($pwd), $user['id']]
        );
        log_activity($user['id'], 'password_reset', 'Fjalëkalimi u rivendos.', get_client_ip());
        flash_success('Fjalëkalimi u ndryshua me sukses! Hyni me fjalëkalimin e ri.');
        redirect(SITE_URL . '/login.php');
    }
}

$page_title = 'Rivendos Fjalëkalimin - ProEstate';
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
<div id="flash-container"><?= flash_render() ?></div>
<div class="auth-page">
  <div class="auth-card">
  <div class="auth-card__inner">
    <div class="auth-logo"><a href="<?= SITE_URL ?>">Pro<strong>Estate</strong></a></div>
    <h2 class="auth-title">Rivendos Fjalëkalimin</h2>

    <?php if ($error && !$user): ?>
    <div class="flash flash--error" style="position:static;max-width:none;margin-bottom:20px;">
      <span class="flash__icon">X</span><span><?= e($error) ?></span>
    </div>
    <a href="<?= SITE_URL ?>/forgot-password.php" class="btn btn--primary btn--full">Kërko Link të Ri</a>
    <?php elseif ($user): ?>
    <p class="auth-subtitle">Vendosni fjalëkalimin e ri për llogarinë: <strong><?= e($user['email']) ?></strong></p>
    <?php if ($error): ?>
    <div class="flash flash--error" style="position:static;max-width:none;margin-bottom:16px;">
      <span class="flash__icon">X</span><span><?= e($error) ?></span>
    </div>
    <?php endif; ?>
    <form method="POST" action="">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Fjalëkalimi i Ri</label>
        <input type="password" name="password" class="form-control" placeholder="Min. 8 karaktere" required autofocus>
      </div>
      <div class="form-group">
        <label>Konfirmo Fjalëkalimin</label>
        <input type="password" name="password2" class="form-control" placeholder="Ripërsërit fjalëkalimin" required>
      </div>
      <button type="submit" class="btn btn--primary btn--full btn--lg">Ndrysho Fjalëkalimin</button>
    </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
