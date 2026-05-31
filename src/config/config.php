<?php
// =============================================================================
// ProEstate - Platforma Web per Menaxhimin e Pronave
// config/config.php — Konfigurimi qendror
// =============================================================================

if (!function_exists('proesta_load_env_file')) {
    function proesta_load_env_file(string $path): void {
        if (!is_readable($path)) return;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");
            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
    proesta_load_env_file(dirname(__DIR__) . '/.env');
}

if (!function_exists('proesta_env')) {
    function proesta_env(string $key, string $default = ''): string {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

// --- Database ---
define('DB_HOST', proesta_env('DB_HOST', 'localhost:3308'));
define('DB_NAME', proesta_env('DB_NAME', 'proesta'));
define('DB_USER', proesta_env('DB_USER', 'root'));
define('DB_PASS', proesta_env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// --- Site ---
// SITE_URL — auto-detect basuar mbi rrugën fizike të skedarit
// Konfiguro manualisht vetëm nëse auto-detect nuk funksionon:
// define('SITE_URL', 'http://localhost/ProEstate');
if (!defined('SITE_URL')) {
    $__protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $__host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Rruga fizike e config.php është: /var/www/html/ProEstate/config/config.php
    // Document root: /var/www/html
    // Kështu base path: /ProEstate
    $__doc_root  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $__self_dir  = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    if ($__doc_root && strpos($__self_dir, $__doc_root) === 0) {
        $__base = substr($__self_dir, strlen($__doc_root));
    } else {
        // Fallback: nga SCRIPT_NAME
        $__base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
        // Hiq subdirektoritë e njohura
        foreach (['dashboard','admin','api','templates','includes','config'] as $__s) {
            if (substr($__base, -strlen($__s)-1) === '/' . $__s) {
                $__base = substr($__base, 0, -strlen($__s)-1);
            }
        }
    }
    define('SITE_URL', $__protocol . '://' . $__host . $__base);
    unset($__protocol, $__host, $__doc_root, $__self_dir, $__base, $__s);
}
define('SITE_NAME', 'ProEstate');
define('SITE_TAGLINE', 'Gjej Pronën e Ëndrrave');
define('SITE_EMAIL', 'info@proestate.al');
define('SITE_PHONE', '+355 69 123 4567');
define('SITE_ADDRESS', 'Rruga e Kavajes, Tiranë, Shqipëri');

define('PAYPAL_MODE', proesta_env('PAYPAL_MODE', 'sandbox'));
define('PAYPAL_CLIENT_ID', proesta_env('PAYPAL_CLIENT_ID', 'ASzh8pYFpXiCV79dswBAdbUJ9wOVUQQDI3g4pcXe0ZNqhLX-eigj3BVTPtJ01xokhdJvcoI6iAn4gHWg'));
define('PAYPAL_CLIENT_SECRET', proesta_env('PAYPAL_CLIENT_SECRET', 'EPsRsZfcvhMNP_k2lm7hdc7hRI4CaFwcWnPLFDgFrIlOaDbSOlvp77CA0l1pKuZikiQ4zd2JDOA3x6GR'));
define('PAYPAL_CURRENCY', proesta_env('PAYPAL_CURRENCY', 'EUR'));
define('PAYPAL_RESERVATION_FEE', 50.00);


// --- OpenAI Chatbot ---
// Mbaje API key vetem ne server (.env), asnjehere ne JavaScript/browser.
define('OPENAI_API_KEY',        proesta_env('OPENAI_API_KEY', ''));
define('OPENAI_MODEL',          proesta_env('OPENAI_MODEL', 'gpt-5.4-mini'));
define('OPENAI_ORG_ID',         proesta_env('OPENAI_ORG_ID', ''));
define('OPENAI_PROJECT_ID',     proesta_env('OPENAI_PROJECT_ID', ''));
define('OPENAI_CHATBOT_ENABLED', proesta_env('OPENAI_CHATBOT_ENABLED', '1') === '1');

// --- Upload ---
define('UPLOAD_BASE_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_IMAGES_PER_PROPERTY', 15);
define('ALLOWED_IMG_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_DOC_TYPES', ['application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// --- Email (konfiguro me kredencialet tuaja SMTP) ---
define('MAIL_HOST', proesta_env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int) proesta_env('MAIL_PORT', '587'));
define('MAIL_USER', proesta_env('MAIL_USER', ''));
define('MAIL_PASS', proesta_env('MAIL_PASS', ''));
define('MAIL_FROM', proesta_env('MAIL_FROM', MAIL_USER ?: 'noreply@proestate.al'));
define('MAIL_FROM_NAME', proesta_env('MAIL_FROM_NAME', 'ProEstate Platform'));

// --- Security ---
define('BCRYPT_COST', 12);
define('CSRF_TOKEN_NAME', '_proesta_csrf');
define('SESSION_LIFETIME', 3600);

// --- Environment ---
define('APP_ENV', proesta_env('APP_ENV', 'development')); // 'production' ne server real
define('APP_DEBUG', proesta_env('APP_DEBUG', APP_ENV === 'development' ? '1' : '0') === '1');

// --- Session ---
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    if (APP_ENV === 'production') {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_start();
}

// --- Timezone ---
date_default_timezone_set('Europe/Tirane');

// --- Error Handling ---
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
