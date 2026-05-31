<?php
// api/favorites.php — Toggle favorite pronash
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');
check_referrer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Metoda e gabuar.', 405);
csrf_check();

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'redirect' => SITE_URL . '/login.php']);
    exit;
}

$prop_id = (int)($_POST['property_id'] ?? 0);
if (!$prop_id) json_error('Pronë e pavlefshme.', 400);

$exists = db_count(
    "SELECT COUNT(*) FROM favorites WHERE user_id = ? AND property_id = ?",
    [current_user_id(), $prop_id]
);

if ($exists) {
    db_query("DELETE FROM favorites WHERE user_id = ? AND property_id = ?",
             [current_user_id(), $prop_id]);
    json_success(['added' => false], 'Hequr nga preferuarat.');
} else {
    db_query("INSERT IGNORE INTO favorites (user_id, property_id) VALUES (?, ?)",
             [current_user_id(), $prop_id]);
    json_success(['added' => true], 'Shtuar në preferuara!');
}
