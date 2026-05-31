<?php
// api/admin-actions.php — Veprime AJAX për dashboard
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');
check_referrer();
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);
csrf_check();

$action = sanitize($_POST['action'] ?? '');

switch ($action) {
    case 'set_primary_image':
        $img_id  = (int)($_POST['img_id'] ?? 0);
        $prop_id = (int)($_POST['prop_id'] ?? 0);
        if (!$img_id || !$prop_id) json_error('Parametra të gabuar.');
        if (!can_edit_property($prop_id) && !has_role('admin')) json_error('Nuk keni leje.', 403);
        db_query("UPDATE property_images SET is_primary=0 WHERE property_id=?", [$prop_id]);
        db_query("UPDATE property_images SET is_primary=1 WHERE id=? AND property_id=?", [$img_id, $prop_id]);
        json_success([], 'Imazhi kryesor u ndryshua.');
        break;

    case 'delete_image':
        $img_id = (int)($_POST['img_id'] ?? 0);
        if (!$img_id) json_error('ID e gabuar.');
        $img = db_row("SELECT * FROM property_images WHERE id=?", [$img_id]);
        if (!$img) json_error('Imazhi nuk u gjet.');
        if (!can_edit_property($img['property_id']) && !has_role('admin')) json_error('Nuk keni leje.', 403);
        $file = UPLOAD_BASE_DIR . 'properties/' . $img['filename'];
        if (file_exists($file)) @unlink($file);
        db_query("DELETE FROM property_images WHERE id=?", [$img_id]);
        // Nëse ishte primary, cakto tjetrin si primary
        $next = db_row("SELECT id FROM property_images WHERE property_id=? ORDER BY sort_order ASC LIMIT 1", [$img['property_id']]);
        if ($next) db_query("UPDATE property_images SET is_primary=1 WHERE id=?", [$next['id']]);
        json_success([], 'Imazhi u fshi.');
        break;

    case 'delete_document':
        $doc_id = (int)($_POST['doc_id'] ?? 0);
        if (!$doc_id) json_error('ID e gabuar.');
        $doc = db_row("SELECT * FROM property_documents WHERE id=?", [$doc_id]);
        if (!$doc) json_error('Dokumenti nuk u gjet.');
        if (!can_edit_property($doc['property_id']) && !has_role('admin')) json_error('Nuk keni leje.', 403);
        $file = UPLOAD_BASE_DIR . 'documents/' . $doc['filename'];
        if (file_exists($file)) @unlink($file);
        db_query("DELETE FROM property_documents WHERE id=?", [$doc_id]);
        json_success([], 'Dokumenti u fshi.');
        break;

    case 'toggle_property_active':
        require_role('admin');
        $pid   = (int)($_POST['id'] ?? 0);
        $value = (int)($_POST['value'] ?? 0);
        db_query("UPDATE properties SET is_active=? WHERE id=?", [$value, $pid]);
        log_activity(current_user_id(), 'property_active_changed', "ID: {$pid}, active={$value}", get_client_ip());
        json_success([], 'Statusi u ndryshua.');
        break;

    case 'toggle_user_active':
        require_role('admin');
        $uid_t = (int)($_POST['id'] ?? 0);
        $value = (int)($_POST['value'] ?? 0);
        db_query("UPDATE users SET is_active=? WHERE id=?", [$value, $uid_t]);
        log_activity(current_user_id(), 'user_active_changed', "User ID: {$uid_t}, active={$value}", get_client_ip());
        json_success([], 'Statusi i perdoruesit u ndryshua.');
        break;

    case 'approve_property':
        require_role('admin');
        $pid = (int)($_POST['id'] ?? 0);
        if (!$pid) json_error('ID e gabuar.');
        db_query("UPDATE properties SET approval_status='approved', is_verified=1, approved_at=NOW(), approved_by=? WHERE id=?", [current_user_id(), $pid]);
        log_activity(current_user_id(), 'property_approved', "ID: {$pid}", get_client_ip());
        json_success([], 'Prona u aprovua.');
        break;

    case 'reject_property':
        require_role('admin');
        $pid = (int)($_POST['id'] ?? 0);
        if (!$pid) json_error('ID e gabuar.');
        db_query("UPDATE properties SET approval_status='rejected', is_verified=0 WHERE id=?", [$pid]);
        log_activity(current_user_id(), 'property_rejected', "ID: {$pid}", get_client_ip());
        json_success([], 'Prona u refuzua.');
        break;

    case 'approve_agent':
        require_role('admin');
        $uid_a = (int)($_POST['id'] ?? 0);
        if (!$uid_a) json_error('ID e gabuar.');
        $ag = db_row("SELECT id, email, first_name, role, is_active FROM users WHERE id=?", [$uid_a]);
        if (!$ag || $ag['role'] !== 'agent') json_error('Perdoruesi nuk është agjent.');
        if ($ag['is_active']) json_error('Agjenti është tashmë aktiv.');
        db_query("UPDATE users SET is_active=1 WHERE id=?", [$uid_a]);
        log_activity(current_user_id(), 'agent_approved', "Agjent ID: {$uid_a}", get_client_ip());
        json_success(['name' => $ag['first_name']], 'Agjenti u aprovua me sukses.');
        break;

    case 'reject_agent':
        require_role('admin');
        $uid_r = (int)($_POST['id'] ?? 0);
        if (!$uid_r) json_error('ID e gabuar.');
        $ag = db_row("SELECT role, is_active FROM users WHERE id=?", [$uid_r]);
        if (!$ag || $ag['role'] !== 'agent') json_error('Perdoruesi nuk është agjent.');
        if ($ag['is_active']) json_error('Nuk mund të refuzohet një agjent tashmë aktiv.');
        db_query("DELETE FROM users WHERE id=? AND role='agent' AND is_active=0", [$uid_r]);
        log_activity(current_user_id(), 'agent_rejected', "Agjent ID: {$uid_r}", get_client_ip());
        json_success([], 'Agjenti u refuzua dhe u fshi.');
        break;

    default:
        json_error('Veprim i panjohur.', 400);
}
