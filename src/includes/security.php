<?php
// =============================================================================
// includes/security.php — Funksione sigurie
// =============================================================================

/**
 * Gjeneron CSRF token dhe e ruan në sesion
 */
function csrf_generate(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validon CSRF token nga forma
 */
function csrf_validate(?string $token): bool {
    if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Jep field hidden për forma
 */
function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME
         . '" value="' . htmlspecialchars(csrf_generate(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Kontrollon CSRF dhe ndalon nëse dështon
 */
function csrf_check(): void {
    $token = $_POST[CSRF_TOKEN_NAME] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!csrf_validate($token)) {
        http_response_code(403);
        json_error('Kërkesë e pavlefshme. Ridërgoni formularin.', 403);
    }
}

/**
 * Pastron output për XSS
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Kontrollon referrerin — lejon vetëm faqet e aplikacionit
 * Faqja kryesore (index.php) mund të aksesohet edhe nga jashtë
 */
function check_referrer(bool $allow_external = false): void {
    if ($allow_external) return;
    // Për API endpoints dhe dashboard, kontrollo referrerin
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($referer)) {
        $referer_host = parse_url($referer, PHP_URL_HOST);
        $site_host    = parse_url(SITE_URL, PHP_URL_HOST);
        if ($referer_host && $referer_host !== $site_host) {
            http_response_code(403);
            die('Akses i refuzuar.');
        }
    }
}

/**
 * Sanitizon hyrje tekst
 */
function sanitize(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Validon email
 */
function is_valid_email(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validon numrin e telefonit shqiptar
 */
function is_valid_phone(string $phone): bool {
    $normalized = preg_replace('/[\s\-\(\)]/', '', $phone) ?? '';
    return (bool) preg_match('/^\+3556[789]\d{7}$/', $normalized);
}

/**
 * Gjeneron token random të sigurt
 */
function generate_token(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Hash password
 */
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

/**
 * Verifiko password
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Merr IP-në reale të klientit
 */
function get_client_ip(): string {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                return trim($ip);
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Limito rate per IP (i thjeshtë, session-based)
 */
function rate_limit(string $key, int $max, int $window_seconds): bool {
    $sk = "rl_{$key}_" . get_client_ip();
    if (!isset($_SESSION[$sk])) {
        $_SESSION[$sk] = ['count' => 0, 'reset' => time() + $window_seconds];
    }
    if (time() > $_SESSION[$sk]['reset']) {
        $_SESSION[$sk] = ['count' => 0, 'reset' => time() + $window_seconds];
    }
    $_SESSION[$sk]['count']++;
    return $_SESSION[$sk]['count'] <= $max;
}

/**
 * Rate-limit login per email + IP ne DB: 5 tentativa, bllokim 10 minuta.
 */
function login_attempts_allowed(string $email, int $max = 5, int $lock_minutes = 10): array {
    $email = strtolower(trim($email));
    $ip = get_client_ip();
    try {
        $row = db_row(
            "SELECT COUNT(*) AS attempts, MIN(attempted_at) AS first_attempt
             FROM login_attempts
             WHERE email = ? AND ip_address = ? AND success = 0
               AND attempted_at >= (NOW() - INTERVAL ? MINUTE)",
            [$email, $ip, $lock_minutes]
        );
        $attempts = (int)($row['attempts'] ?? 0);
        if ($attempts >= $max) {
            $first = strtotime($row['first_attempt'] ?? 'now');
            $remaining = max(1, (int)ceil((($first + ($lock_minutes * 60)) - time()) / 60));
            return ['allowed' => false, 'remaining' => $remaining];
        }
    } catch (Throwable $e) {
        return ['allowed' => true, 'remaining' => 0];
    }
    return ['allowed' => true, 'remaining' => 0];
}

function record_login_attempt(string $email, bool $success): void {
    try {
        db_query(
            "INSERT INTO login_attempts (email, ip_address, success, user_agent, attempted_at)
             VALUES (?, ?, ?, ?, NOW())",
            [
                strtolower(trim($email)),
                get_client_ip(),
                $success ? 1 : 0,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]
        );
    } catch (Throwable $e) {
        // Mos ndalo login-in nese tabela nuk ekziston ende.
    }
}

function clear_failed_login_attempts(string $email): void {
    try {
        db_query(
            "DELETE FROM login_attempts WHERE email = ? AND ip_address = ? AND success = 0",
            [strtolower(trim($email)), get_client_ip()]
        );
    } catch (Throwable $e) {
        // Mos ndalo login-in nese pastrimi deshton.
    }
}

/**
 * Kthe JSON error dhe ndalo ekzekutimin
 */
function json_error(string $message, int $code = 400): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * Kthe JSON sukses
 */
function json_success(array $data = [], string $message = 'OK'): never {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => true, 'message' => $message], $data));
    exit;
}
