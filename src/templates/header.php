<?php
// =============================================================================
// templates/header.php - Header global me navbar
// $page_title, $page_description kalojnë nga çdo faqe
// =============================================================================
if (!defined('SITE_NAME')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

$page_title       = $page_title ?? SITE_NAME;
$page_description = $page_description ?? 'ProEstate — platformë për shitje, blerje, qiradhënie dhe menaxhim takimesh për prona të paluajtshme.';
$full_title       = ($page_title !== SITE_NAME) ? "{$page_title} - " . SITE_NAME : SITE_NAME;

$unread_msgs = 0;
if (is_logged_in()) {
    $unread_msgs = db_count(
        "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0",
        [current_user_id()]
    );
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($full_title) ?></title>
  <meta name="description" content="<?= e($page_description) ?>">
  <meta name="robots" content="index, follow">
  <meta property="og:title" content="<?= e($full_title) ?>">
  <meta property="og:description" content="<?= e($page_description) ?>">
  <meta property="og:type" content="website">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,500;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=chatbot7">
  <?php if (isset($extra_head)) echo $extra_head; ?>
  <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/images/favicon.svg">
</head>
<body class="<?= $body_class ?? '' ?>">

<!-- Flash Messages -->
<div id="flash-container" aria-live="polite">
  <?= flash_render() ?>
</div>

<!-- Navigation -->
<header class="site-header" id="site-header">
  <nav class="navbar container">
    <a href="<?= SITE_URL ?>/index.php" class="navbar__brand">
      <span class="brand-logo">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" width="32" height="32">
          <path d="M18 3L3 14v19h10V22h10v11h10V14L18 3z" fill="currentColor" opacity=".15"/>
          <path d="M18 3L3 14v19h10V22h10v11h10V14L18 3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
          <rect x="14" y="22" width="8" height="10" rx="1" fill="currentColor" opacity=".4"/>
        </svg>
      </span>
      <span class="brand-text">Pro<strong>Estate</strong></span>
    </a>

    <ul class="navbar__menu" id="nav-menu">
      <li><a href="<?= SITE_URL ?>/index.php" class="nav-link">Faqja Kryesore</a></li>
      <li><a href="<?= SITE_URL ?>/properties.php" class="nav-link">Prona</a></li>
      <li><a href="<?= SITE_URL ?>/properties.php?status=for_sale" class="nav-link">Shitje</a></li>
      <li><a href="<?= SITE_URL ?>/properties.php?status=for_rent" class="nav-link">Qiradhënie</a></li>
      <li><a href="<?= SITE_URL ?>/agents.php" class="nav-link">Agjentë</a></li>
      <li><a href="<?= SITE_URL ?>/about.php" class="nav-link">Rreth Nesh</a></li>
      <li><a href="<?= SITE_URL ?>/contact.php" class="nav-link">Kontakt</a></li>
    </ul>

    <div class="navbar__actions">
      <?php if (is_logged_in()): ?>
  <?php
    $user = current_user() ?? [];
    $first_name = $user['first_name'] ?? 'User';
    $last_name  = $user['last_name'] ?? '';
    $role       = $user['role'] ?? 'client';
    $avatar     = $user['avatar'] ?? null;
    $full_name  = trim($first_name . ' ' . $last_name);
  ?>
  <a href="<?= SITE_URL ?>/dashboard/messages.php" class="nav-icon-btn" title="Mesazhet">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
    <?php if ($unread_msgs > 0): ?>
      <span class="badge-count"><?= $unread_msgs > 9 ? '9+' : $unread_msgs ?></span>
    <?php endif; ?>
  </a>
  <div class="dropdown">
    <button class="dropdown__trigger">
      <img src="<?= get_avatar_url($avatar) ?>" alt="<?= e($first_name) ?>" class="avatar-sm">
      <span><?= e($first_name) ?></span>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="dropdown__menu">
      <div class="dropdown__header">
        <strong><?= e($full_name ?: 'User') ?></strong>
        <span class="role-badge"><?= role_label($role) ?></span>
      </div>
            <a href="<?= SITE_URL ?>/dashboard/index.php" class="dropdown__item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
              Paneli im
            </a>
            <a href="<?= SITE_URL ?>/dashboard/my-properties.php" class="dropdown__item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
              Pronat e Mia
            </a>
            <a href="<?= SITE_URL ?>/dashboard/favorites.php" class="dropdown__item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
              Preferuarat
            </a>
            <a href="<?= SITE_URL ?>/dashboard/profile.php" class="dropdown__item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Profili im
            </a>
            <?php if (has_role('admin')): ?>
            <div class="dropdown__divider"></div>
            <a href="<?= SITE_URL ?>/admin/index.php" class="dropdown__item dropdown__item--admin">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Admin Panel
            </a>
            <?php endif; ?>
            <div class="dropdown__divider"></div>
            <a href="<?= SITE_URL ?>/logout.php" class="dropdown__item dropdown__item--danger">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Dil
            </a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="btn btn--outline btn--sm">Hyrje</a>
        <a href="<?= SITE_URL ?>/register.php" class="btn btn--primary btn--sm">Regjistrohu</a>
      <?php endif; ?>
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
</header>
