<?php
// =============================================================================
// includes/auth.php — Autentifikimi dhe autorizimi
// =============================================================================

/**
 * Kontrollo nëse perdoruesi është i kyçur
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Merr ID-në e perdoruesit aktual
 */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Merr rolin e perdoruesit aktual
 */
function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Merr të dhënat e perdoruesit aktual nga DB
 */
function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $user = db_row("SELECT * FROM users WHERE id = ? AND is_active = 1",
                       [current_user_id()]);
    }
    return $user;
}

/**
 * Kërkon autentifikim — redirect nëse jo i kyçur
 */
function require_auth(string $redirect = ''): void {
    if (!is_logged_in()) {
        $back = $redirect ?: (SITE_URL . '/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
        redirect($back);
    }
}

/**
 * Kërkon rol specifik
 */
function require_role(array|string $roles): void {
    $roles = (array)$roles;
    if (!is_logged_in()) {
        if (count($roles) === 1 && in_array('admin', $roles, true)) {
            redirect(SITE_URL . '/admin/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
        }
        require_auth();
    }
    if (!in_array(current_user_role(), $roles)) {
        redirect(SITE_URL . '/dashboard/index.php?err=noperm');
    }
}

/**
 * Kontrollo nëse perdoruesi ka rol
 */
function has_role(array|string $roles): bool {
    $roles = (array)$roles;
    return in_array(current_user_role(), $roles);
}

/**
 * Kyçja — validon kredencialet dhe krijon sesion
 * Kthen array me çelësat: success, message, user
 */
function login_user(string $email, string $password, bool $admin_only = false): array {
    $email = strtolower(trim($email));

    if (!is_valid_email($email)) {
        return ['success' => false, 'message' => 'Email i pavlefshëm.'];
    }

    $user = db_row(
        "SELECT id, email, password, first_name, last_name, role, is_active, email_verified, avatar
         FROM users WHERE email = ? LIMIT 1",
        [$email]
    );

    if (!$user) {
        record_login_attempt($email, false);
        return ['success' => false, 'message' => 'Email ose fjalëkalim i gabuar.'];
    }

    if ($admin_only && $user['role'] !== 'admin') {
        record_login_attempt($email, false);
        return ['success' => false, 'message' => 'Email ose fjalekalim i gabuar.'];
    }

    if (!$admin_only && $user['role'] === 'admin') {
        record_login_attempt($email, false);
        return ['success' => false, 'message' => 'Hyrja e administratorit kryhet nga faqja e dedikuar e adminit.'];
    }

    if (!$user['is_active']) {
        if ($user['role'] === 'agent') {
            return ['success' => false, 'message' => 'Llogaria juaj si agjent është në pritje të aprovimit nga administratori. Do të njoftoheni me email kur të aprovohet.'];
        }
        return ['success' => false, 'message' => 'Llogaria juaj është çaktivizuar. Kontaktoni administratorin.'];
    }

    if (!verify_password($password, $user['password'])) {
        record_login_attempt($email, false);
        log_activity(null, 'login_failed', "Email: {$email}", get_client_ip());
        return ['success' => false, 'message' => 'Email ose fjalëkalim i gabuar.'];
    }

    // Rigjeneroj session ID për siguri
    if ((int)$user['email_verified'] !== 1) {
        record_login_attempt($email, false);
        return ['success' => false, 'message' => 'Ju lutemi verifikoni email-in para se te hyni. Kontrolloni inbox/spam per linkun e verifikimit.'];
    }

    session_regenerate_id(true);

    $_SESSION['user_id']         = (int) $user['id'];
    $_SESSION['user_email']      = $user['email'];
    $_SESSION['user_name']       = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_first_name'] = $user['first_name'];
    $_SESSION['user_role']       = $user['role'];
    $_SESSION['user_avatar']     = $user['avatar'];
    $_SESSION['login_time']      = time();

    // Përditëso last_login
    db_query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    clear_failed_login_attempts($email);
    record_login_attempt($email, true);
    log_activity($user['id'], 'login', 'Login i suksesshëm', get_client_ip());

    return ['success' => true, 'message' => 'Mirë se erdhe!', 'user' => $user];
}

/**
 * Regjistrim i ri perdoruesi
 */
function register_user(array $data): array {
    $email      = strtolower(trim($data['email'] ?? ''));
    $password   = $data['password'] ?? '';
    $first_name = sanitize($data['first_name'] ?? '');
    $last_name  = sanitize($data['last_name'] ?? '');
    $phone      = preg_replace('/[\s\-\(\)]/', '', sanitize($data['phone'] ?? '')) ?? '';
    $gender     = in_array($data['gender'] ?? '', ['female', 'male', 'other', 'unspecified'], true)
                  ? $data['gender'] : 'unspecified';
    $role       = in_array($data['role'] ?? '', ['client', 'agent', 'owner'])
                  ? $data['role'] : 'client';

    // Validime
    if (!is_valid_email($email)) {
        return ['success' => false, 'message' => 'Email i pavlefshëm.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Fjalëkalimi duhet të ketë të paktën 8 karaktere.'];
    }
    if (strlen($first_name) < 2 || strlen($last_name) < 2) {
        return ['success' => false, 'message' => 'Emri dhe mbiemri janë të detyrueshëm.'];
    }

    if ($phone !== '' && !is_valid_phone($phone)) {
        return ['success' => false, 'message' => 'Numri i telefonit duhet te jete ne formatin +35567XXXXXXX, +35568XXXXXXX ose +35569XXXXXXX.'];
    }

    // Kontroll duplicate
    $exists = db_count("SELECT COUNT(*) FROM users WHERE email = ?", [$email]);
    if ($exists) {
        return ['success' => false, 'message' => 'Ky email është tashmë i regjistruar.'];
    }

    $hashed    = hash_password($password);
    $token     = generate_token();
    $is_active = ($role === 'agent') ? 0 : 1;

    db_query(
        "INSERT INTO users (email, password, first_name, last_name, phone, gender, role,
                            verification_token, is_active, email_verified, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())",
        [$email, $hashed, $first_name, $last_name, $phone, $gender, $role, $token, $is_active]
    );

    $user_id = (int) db_last_id();
    log_activity($user_id, 'register', "Regjistrim i ri: {$email}", get_client_ip());

    // Dërgo email konfirmimi
    $mail_sent = send_welcome_email($email, $first_name, $token);

    return [
        'success' => true,
        'message' => 'Llogaria u krijua me sukses!',
        'user_id' => $user_id,
        'mail_sent' => $mail_sent,
        'verify_url' => SITE_URL . '/verify-email.php?token=' . urlencode($token),
    ];
}

/**
 * Dalja nga sistemi
 */
function logout_user(): void {
    if (is_logged_in()) {
        log_activity(current_user_id(), 'logout', 'Logout', get_client_ip());
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Kontrollo nëse perdoruesi mund të modifikojë pronën
 */
function can_edit_property(int $property_id): bool {
    if (has_role('admin')) return true;
    $prop = db_row("SELECT owner_id, agent_id FROM properties WHERE id = ?", [$property_id]);
    if (!$prop) return false;
    $uid = current_user_id();
    return (int) $prop['owner_id'] === $uid || (int) $prop['agent_id'] === $uid;
}
