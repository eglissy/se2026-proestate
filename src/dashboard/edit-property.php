<?php
// dashboard/edit-property.php - Redirect te add-property me id
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_auth();
$id  = (int)($_GET['id'] ?? 0);
$tab = sanitize($_GET['tab'] ?? 'details');
$tab = in_array($tab, ['details','images','documents'], true) ? $tab : 'details';
redirect(SITE_URL . '/dashboard/add-property.php?id=' . $id . '&tab=' . $tab);
