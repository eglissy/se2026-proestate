<?php
// =============================================================================
// api/paypal-capture-order.php — Kap pagesën dhe krijon takimin
// Thirret pas miratimit të pagesës nga PayPal JS SDK
// =============================================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

header('Content-Type: application/json');
check_referrer();
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Metoda e gabuar.', 405);
csrf_check();

$raw      = file_get_contents('php://input');
$data     = json_decode($raw, true);
$order_id = sanitize($data['order_id'] ?? '');

if (!$order_id) json_error('Order ID mungon.', 400);

// Verifiko sesionin
$pending = $_SESSION['paypal_pending'] ?? null;
if (!$pending || ($pending['order_id'] ?? '') !== $order_id) {
    json_error('Sesion i pavlefshëm ose i skaduar.', 403);
}
if ((int) $pending['user_id'] !== current_user_id()) {
    json_error('Autorizim i refuzuar.', 403);
}
// Kontrollo timeout sesionit (15 min)
if (time() - ($pending['created'] ?? 0) > 900) {
    unset($_SESSION['paypal_pending']);
    json_error('Sesioni i pagesës ka skaduar. Filloni sërish.', 408);
}

// Merr PayPal Access Token
if (PAYPAL_CLIENT_ID === '' || PAYPAL_CLIENT_SECRET === '') {
    json_error('PayPal nuk është konfiguruar ende.', 503);
}
$access_token = get_paypal_access_token_capture();
if (!$access_token) json_error('Gabim me PayPal.', 503);

$base_url = PAYPAL_MODE === 'live'
    ? 'https://api-m.paypal.com'
    : 'https://api-m.sandbox.paypal.com';

// Capture Payment
$ch = curl_init("{$base_url}/v2/checkout/orders/{$order_id}/capture");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => '{}',
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token,
    ],
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$capture = json_decode($response, true);

if ($http_code !== 201 || ($capture['status'] ?? '') !== 'COMPLETED') {
    error_log("PayPal capture error ({$http_code}): {$response}");
    json_error('Pagesa nuk u konfirmua nga PayPal. Provoni sërish.', 402);
}

// Ekstrakto të dhëna capture
$capture_id  = $capture['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';
$amount_val  = $capture['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? '0';
$currency    = $capture['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'] ?? PAYPAL_CURRENCY;
$payer_email = $capture['payer']['email_address'] ?? '';
$payer_name  = ($capture['payer']['name']['given_name'] ?? '') . ' ' . ($capture['payer']['name']['surname'] ?? '');

// === Krijo takimin në DB ===
$prop_id  = $pending['property_id'];
$date     = $pending['date'];
$time_str = $pending['time'] . ':00';
$notes    = $pending['notes'];
$uid      = current_user_id();

// Verifikë ende nuk ka konflikt
$conflict = db_count(
    "SELECT COUNT(*) FROM appointments WHERE property_id=? AND scheduled_date=? AND scheduled_time=? AND status IN ('pending','confirmed')",
    [$prop_id, $date, $time_str]
);
if ($conflict) {
    // Kthejme rimbursim (prodhim) — këtu logojmë vetëm
    error_log("PayPal: slot u zu ndërkohë. OrderID: {$order_id}. CaptureID: {$capture_id}");
    json_error('Fatkeqësisht ky slot u zu ndërkohë. Do kontaktoheni për rimbursim.', 409);
}

// Merr agent_id
$prop = db_row("SELECT agent_id, title, address, city FROM properties WHERE id=?", [$prop_id]);

db_query(
    "INSERT INTO appointments (property_id, client_id, agent_id, scheduled_date, scheduled_time, status, client_notes)
     VALUES (?, ?, ?, ?, ?, 'confirmed', ?)",
    [$prop_id, $uid, $prop['agent_id'], $date, $time_str, $notes]
);
$appt_id = (int) db_last_id();

// === Regjistro pagesën në DB ===
db_query(
    "INSERT INTO payments
       (appointment_id, user_id, property_id, paypal_order_id, paypal_capture_id,
        payer_email, payer_name, amount, currency, status, paid_at)
     VALUES (?,?,?,?,?,?,?,?,?,'completed',NOW())",
    [$appt_id, $uid, $prop_id, $order_id, $capture_id,
     $payer_email, trim($payer_name), $amount_val, $currency]
);
$payment_id = (int) db_last_id();

// Pastro sesionin
unset($_SESSION['paypal_pending']);

// Dërgo emaile konfirmimi
$client = current_user();
if ($prop['agent_id']) {
    $agent = db_row("SELECT * FROM users WHERE id=?", [$prop['agent_id']]);
    if ($agent) {
        send_appointment_email(
            ['scheduled_date' => $date, 'scheduled_time' => $time_str],
            $prop, $client, $agent, 'confirmation'
        );
    }
}

// Email klientit me detajet pagesës
send_payment_confirmation_email($client, $prop, $date, $time_str, (float)$amount_val, $capture_id);

log_activity($uid, 'payment_completed',
    "Pronë #{$prop_id} · Order: {$order_id} · Capture: {$capture_id}", get_client_ip());

json_success([
    'appointment_id' => $appt_id,
    'payment_id'     => $payment_id,
    'capture_id'     => $capture_id,
    'amount'         => $amount_val,
    'currency'       => $currency,
    'message'        => 'Pagesa u krye dhe takimi u konfirmua!',
]);


// ---- Helper: PayPal OAuth Token (për capture) ----
function get_paypal_access_token_capture(): ?string {
    $base_url = PAYPAL_MODE === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';

    $ch = curl_init($base_url . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERPWD        => PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $data['access_token'] ?? null;
}
