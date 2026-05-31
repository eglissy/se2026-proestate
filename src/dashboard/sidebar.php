<?php
// dashboard/sidebar.php - Sidebar i dashboard-it
$role = current_user_role();
$user = current_user();
$uid  = current_user_id();
$unread = db_count("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0", [$uid]);
$pending_agents_nav = $role === 'admin'
    ? db_count("SELECT COUNT(*) FROM users WHERE role='agent' AND is_active=0")
    : 0;
$pending_properties_nav = $role === 'admin'
    ? db_count("SELECT COUNT(*) FROM properties WHERE approval_status='pending' AND is_active=1")
    : 0;
?>
<aside class="sidebar">
  <div class="sidebar__user">
    <img src="<?= get_avatar_url($user['avatar'] ?? null) ?>"
         alt="<?= e($user['first_name']) ?>" class="sidebar__avatar">
    <div>
      <div class="sidebar__user-name"><?= e($user['first_name'].' '.$user['last_name']) ?></div>
      <div class="sidebar__user-role"><?= role_label($role) ?></div>
    </div>
  </div>

  <nav class="sidebar__nav">
    <div class="sidebar__section-title">Kryesore</div>

    <a href="<?= SITE_URL ?>/dashboard/index.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
      </svg>
      Paneli
    </a>

    <a href="<?= SITE_URL ?>/dashboard/profile.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      Profili im
    </a>

    <a href="<?= SITE_URL ?>/dashboard/messages.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
      </svg>
      Mesazhet
      <?php if ($unread > 0): ?>
      <span class="badge-count-inline" style="margin-left:auto;"><?= $unread ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= SITE_URL ?>/dashboard/appointments.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
        <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
      </svg>
      Takimet
    </a>

    <a href="<?= SITE_URL ?>/dashboard/favorites.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
      </svg>
      Preferuarat
    </a>

    <a href="<?= SITE_URL ?>/dashboard/payments.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
      </svg>
      Pagesat PayPal
    </a>

    <?php if (in_array($role, ['agent','owner','admin'])): ?>
    <div class="sidebar__section-title" style="margin-top:8px;">Prona</div>

    <a href="<?= SITE_URL ?>/dashboard/my-properties.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Pronat e Mia
    </a>

    <a href="<?= SITE_URL ?>/dashboard/add-property.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/>
        <line x1="8" y1="12" x2="16" y2="12"/>
      </svg>
      Shto Pronë të Re
    </a>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
    <div class="sidebar__section-title" style="margin-top:8px;">Admin</div>
    <a href="<?= SITE_URL ?>/admin/index.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
      </svg>
      Admin Panel
    </a>
    <a href="<?= SITE_URL ?>/admin/users.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
      </svg>
      Perdoruesit
    </a>
    <a href="<?= SITE_URL ?>/admin/users.php?status=pending_agents" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="m17 11 2 2 4-4"/>
      </svg>
      Agjente per aprovim
      <?php if ($pending_agents_nav > 0): ?>
      <span class="badge-count-inline" style="margin-left:auto;"><?= $pending_agents_nav ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= SITE_URL ?>/admin/properties.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
      </svg>
      Të gjitha Pronat
    </a>
    <a href="<?= SITE_URL ?>/admin/properties.php?approval=pending" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        <path d="m9 12 2 2 4-4"/>
      </svg>
      Prona per aprovim
      <?php if ($pending_properties_nav > 0): ?>
      <span class="badge-count-inline" style="margin-left:auto;"><?= $pending_properties_nav ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <div class="sidebar__section-title" style="margin-top:8px;">Tjetër</div>
    <a href="<?= SITE_URL ?>/index.php" class="sidebar__link">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
      </svg>
      Kthehu në Site
    </a>
    <a href="<?= SITE_URL ?>/logout.php" class="sidebar__link" style="color:rgba(255,100,100,.7);">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Dil
    </a>
  </nav>
</aside>
