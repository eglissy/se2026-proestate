<?php
// =============================================================================
// api/paypal-create-order.php — Krijon një PayPal Order
// Thirret nga JS PayPal SDK për të marrë order ID
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

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$prop_id = (int)($data['property_id'] ?? 0);
$date    = sanitize($data['date']     ?? '');
$time    = sanitize($data['time']     ?? '');
$notes   = sanitize($data['notes']   ?? '');

// Validime
if (!$prop_id || !$date || !$time) json_error('Të dhëna të pakompletuara.', 400);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
    json_error('Data ose ora është e pavlefshme.', 422);
}
$date_ts = strtotime($date);
if ($date_ts === false || $date_ts < strtotime('tomorrow')) {
    json_error('Zgjidhni një datë të ardhshme.', 422);
}
if ((int) date('w', $date_ts) === 0) {
    json_error('Të dielave nuk pranohen takime.', 422);
}
if (PAYPAL_CLIENT_ID === '' || PAYPAL_CLIENT_SECRET === '') {
    json_error('PayPal nuk është konfiguruar ende.', 503);
}

$prop = db_row("SELECT id, title, city, status FROM properties WHERE id=? AND is_active=1 AND approval_status='approved'", [$prop_id]);
if (!$prop) json_error('Prona nuk u gjet.', 404);
if (in_array($prop['status'], ['sold','rented'])) json_error('Kjo pronë nuk është e disponueshme.', 409);

// Kontroll konflikt takimi
$conflict = db_count(
    "SELECT COUNT(*) FROM appointments WHERE property_id=? AND scheduled_date=? AND scheduled_time=? AND status IN ('pending','confirmed')",
    [$prop_id, $date, $time.':00']
);
if ($conflict) json_error('Ky slot është i zënë. Zgjidhni orë tjetër.', 409);

// Merr PayPal Access Token
$access_token = get_paypal_access_token();
if (!$access_token) json_error('Gabim me PayPal. Provoni sërish.', 503);

$base_url = PAYPAL_MODE === 'live'
    ? 'https://api-m.paypal.com'
    : 'https://api-m.sandbox.paypal.com';

$amount   = number_format(PAYPAL_RESERVATION_FEE, 2, '.', '');
$prop_title = mb_substr($prop['title'], 0, 60);

// Ruaj info pagese fillestare në sesion (për capture)
$_SESSION['paypal_pending'] = [
    'property_id' => $prop_id,
    'date'        => $date,
    'time'        => $time,
    'notes'       => $notes,
    'amount'      => PAYPAL_RESERVATION_FEE,
    'user_id'     => current_user_id(),
    'created'     => time(),
];

// Krijo PayPal Order
$order_payload = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
        'reference_id'  => 'ProEstate-' . $prop_id . '-' . time(),
        'description'   => 'Tarifë Rezervimi: ' . $prop_title,
        'amount'        => [
            'currency_code' => PAYPAL_CURRENCY,
            'value'         => $amount,
            'breakdown'     => [
                'item_total' => ['currency_code' => PAYPAL_CURRENCY, 'value' => $amount],
            ],
        ],
        'items' => [[
            'name'        => 'Tarifë Rezervimi Takimi',
            'description' => 'Rezervim vizite: ' . $prop_title . ' · ' . $date . ' ' . $time,
            'quantity'    => '1',
            'unit_amount' => ['currency_code' => PAYPAL_CURRENCY, 'value' => $amount],
            'category'    => 'DIGITAL_GOODS',
        ]],
    ]],
    'application_context' => [
        'brand_name'          => SITE_NAME,
        'user_action'         => 'PAY_NOW',
        'shipping_preference' => 'NO_SHIPPING',
        'locale'              => 'sq-AL',
        'return_url'          => SITE_URL . '/payment-success.php',
        'cancel_url'          => SITE_URL . '/property.php?id=' . $prop_id,
    ],
];

$ch = curl_init($base_url . '/v2/checkout/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($order_payload),
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token,
        'PayPal-Request-Id: ' . uniqid('ProEstate-', true),
    ],
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$order = json_decode($response, true);
if ($http_code !== 201 || empty($order['id'])) {
    error_log("PayPal create-order error ({$http_code}): {$response}");
    json_error('PayPal nuk mund të procesojë kërkesën. Provoni sërish.', 503);
}

// Ruaj order_id në sesion
$_SESSION['paypal_pending']['order_id'] = $order['id'];

json_success(['order_id' => $order['id']]);


// ---- Helper: PayPal OAuth Token ----
function get_paypal_access_token(): ?string {
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
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Accept-Language: en_US'],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}
