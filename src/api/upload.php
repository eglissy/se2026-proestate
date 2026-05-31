<?php
// =============================================================================
// api/upload.php — Upload sigurt imazhesh dhe dokumentesh
// =============================================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');
check_referrer();
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Metoda e gabuar.', 405);
csrf_check();

$type       = sanitize($_POST['type'] ?? ''); // 'property_image' | 'document' | 'avatar'
$prop_id    = (int)($_POST['property_id'] ?? 0);
$is_primary = (int)($_POST['is_primary'] ?? 0);

if (!in_array($type, ['property_image', 'document', 'avatar'])) {
    json_error('Lloj i pavlefshëm.', 400);
}
if (in_array($type, ['property_image', 'document']) && !$prop_id) {
    json_error('Prona është e pavlefshme.', 400);
}

// Verifiko leje pronë
if (in_array($type, ['property_image', 'document'])) {
    if (!can_edit_property($prop_id) && !has_role('admin')) {
        json_error('Nuk keni leje.', 403);
    }
}

$file_key = $type === 'avatar' ? 'avatar' : 'file';
if (empty($_FILES[$file_key])) json_error('Asnjë skedar nuk u ngarkua.', 400);

$file      = $_FILES[$file_key];
$orig_name = basename($file['name']);
$tmp_path  = $file['tmp_name'];
$file_size = $file['size'];
$mime      = '';

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($tmp_path)) {
    json_error('Ngarkimi deshtoi ose skedari nuk eshte i vlefshem.', 400);
}

$blocked_ext = ['php','php3','php4','php5','phtml','phar','pl','py','cgi','exe','bat','cmd','sh','js','html','svg'];
$name_parts = array_map('strtolower', explode('.', $orig_name));
foreach ($name_parts as $part) {
    if (in_array($part, $blocked_ext, true)) {
        json_error('Skedari ka prapashtese te rrezikshme.', 422);
    }
}

// Lexo MIME-in real (jo nga $_FILES)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $tmp_path);
finfo_close($finfo);

// Vërteto sipas tipit
if ($type === 'property_image' || $type === 'avatar') {
    if (!in_array($mime, ALLOWED_IMG_TYPES)) {
        json_error('Formati i imazhit nuk lejohet. Lejohen: JPEG, PNG, WebP, GIF.', 422);
    }
    $info = @getimagesize($tmp_path);
    if (!$info || empty($info[0]) || empty($info[1])) {
        json_error('Imazhi nuk mund te lexohet.', 422);
    }
    if (($info[0] * $info[1]) > 25000000) {
        json_error('Imazhi eshte shume i madh ne dimensione.', 422);
    }
} elseif ($type === 'document') {
    if (!in_array($mime, ALLOWED_DOC_TYPES)) {
        json_error('Formati i dokumentit nuk lejohet. Lejohen: PDF, DOC, DOCX.', 422);
    }
}

if ($file_size > MAX_FILE_SIZE) {
    json_error('Skedari është shumë i madh. Max: ' . human_filesize(MAX_FILE_SIZE), 422);
}
if ($type === 'property_image') {
    $image_count = db_count("SELECT COUNT(*) FROM property_images WHERE property_id = ?", [$prop_id]);
    if ($image_count >= MAX_IMAGES_PER_PROPERTY) {
        json_error('Keni arritur limitin prej ' . MAX_IMAGES_PER_PROPERTY . ' imazhesh për këtë pronë.', 422);
    }
}

// Gjenero emër unik
$ext_map = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
];
$safe_ext   = $ext_map[$mime] ?? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($orig_name, PATHINFO_EXTENSION)));
if ($safe_ext === '') {
    json_error('Skedari nuk ka prapashtesë të vlefshme.', 422);
}
$new_name   = uniqid('', true) . '_' . time() . '.' . $safe_ext;

// Vendos direktoria e destinacionit
switch ($type) {
    case 'property_image': $dir = UPLOAD_BASE_DIR . 'properties/'; break;
    case 'document':       $dir = UPLOAD_BASE_DIR . 'documents/';  break;
    case 'avatar':         $dir = UPLOAD_BASE_DIR . 'avatars/';    break;
}

if (!is_dir($dir)) mkdir($dir, 0755, true);

$dest = $dir . $new_name;

// Lëviz skedarin
if (!move_uploaded_file($tmp_path, $dest)) {
    json_error('Gabim gjatë ngarkimit. Provoni sërish.', 500);
}

// Rifresko imazhin (hiq metadata EXIF për siguri)
if ($type !== 'document' && function_exists('imagecreatefromjpeg')) {
    strip_exif($dest, $mime);
}

// Ruaj në DB
try {
    if ($type === 'property_image' && $prop_id) {
        // Nëse is_primary, hiq primary-n e vjetër
        if ($is_primary) {
            db_query("UPDATE property_images SET is_primary = 0 WHERE property_id = ?", [$prop_id]);
        }
        $count = db_count("SELECT COUNT(*) FROM property_images WHERE property_id = ?", [$prop_id]);
        db_query(
            "INSERT INTO property_images (property_id, filename, original_name, is_primary, sort_order)
             VALUES (?, ?, ?, ?, ?)",
            [$prop_id, $new_name, $orig_name, $is_primary ? 1 : ($count === 0 ? 1 : 0), $count]
        );
        $img_id = (int) db_last_id();
        json_success(['filename' => $new_name, 'image_id' => $img_id,
                      'url' => SITE_URL . '/uploads/properties/' . $new_name], 'Imazhi u ngarkua!');
    }

    if ($type === 'document' && $prop_id) {
        db_query(
            "INSERT INTO property_documents (property_id, filename, original_name, file_type, file_size, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$prop_id, $new_name, $orig_name, $mime, $file_size, current_user_id()]
        );
        $doc_id = (int) db_last_id();
        json_success(['filename' => $new_name, 'document_id' => $doc_id,
                      'original_name' => $orig_name, 'size' => human_filesize($file_size)], 'Dokumenti u ngarkua!');
    }

    if ($type === 'avatar') {
        $old = db_row("SELECT avatar FROM users WHERE id = ?", [current_user_id()]);
        if ($old && $old['avatar'] && file_exists(UPLOAD_BASE_DIR . 'avatars/' . $old['avatar'])) {
            @unlink(UPLOAD_BASE_DIR . 'avatars/' . $old['avatar']);
        }
        db_query("UPDATE users SET avatar = ? WHERE id = ?", [$new_name, current_user_id()]);
        $_SESSION['user_avatar'] = $new_name;
        json_success(['filename' => $new_name, 'url' => SITE_URL . '/uploads/avatars/' . $new_name], 'Avatari u ndryshua!');
    }
} catch (Exception $e) {
    @unlink($dest);
    json_error('Gabim i bazës së të dhënave.', 500);
}

/**
 * Hiq EXIF metadata nga imazhet
 */
function strip_exif(string $path, string $mime): void {
    try {
        switch ($mime) {
            case 'image/jpeg':
                $img = imagecreatefromjpeg($path);
                if ($img) { imagejpeg($img, $path, 90); imagedestroy($img); }
                break;
            case 'image/png':
                $img = imagecreatefrompng($path);
                if ($img) { imagesavealpha($img, true); imagepng($img, $path, 8); imagedestroy($img); }
                break;
        }
    } catch (Exception $e) { /* Vazhdo pa EXIF strip nëse dështon */ }
}
