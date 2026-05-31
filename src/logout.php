<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

check_referrer();
logout_user();
flash_success('U çkyçët me sukses. Shihemi sërish!');
redirect(SITE_URL . '/index.php');
