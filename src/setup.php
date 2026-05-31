<?php
// setup.php — Faqja e diagnostikës dhe instalimit
// Shko te: http://localhost/ProEstate/setup.php
// FShij ose riemëro këtë skedar pas instalimit të suksesshëm!

error_reporting(E_ALL);
ini_set('display_errors', 1);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$doc_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$self_dir = rtrim(str_replace('\\', '/', dirname(__FILE__)), '/');
$base     = ($doc_root && strpos($self_dir, $doc_root) === 0) ? substr($self_dir, strlen($doc_root)) : '';
$site_url = $protocol . '://' . $host . $base;

$checks = [];

// 1. PHP Version
$php_ok = version_compare(PHP_VERSION, '7.4.0', '>=');
$checks[] = ['PHP Version ≥ 7.4', $php_ok, PHP_VERSION];

// 2. PDO MySQL
$pdo_ok = extension_loaded('pdo_mysql');
$checks[] = ['PDO MySQL extension', $pdo_ok, $pdo_ok ? 'Aktive' : 'MOS E NGARKUAR'];

// 3. cURL
$curl_ok = extension_loaded('curl');
$checks[] = ['cURL extension', $curl_ok, $curl_ok ? 'Aktive' : 'MOS E NGARKUAR'];

// 4. GD (për imazhe)
$gd_ok = extension_loaded('gd');
$checks[] = ['GD (imazhe)', $gd_ok, $gd_ok ? 'Aktive' : 'Opsionale — mungon'];

// 5. Upload directories writable
$dirs = ['uploads/properties', 'uploads/documents', 'uploads/avatars'];
foreach ($dirs as $d) {
    $full = __DIR__ . '/' . $d;
    if (!is_dir($full)) @mkdir($full, 0755, true);
    $writable = is_writable($full);
    $checks[] = ["Direktoria writable: {$d}", $writable, $writable ? 'OK' : 'NUK MUND TË SHKRUHET — ndrysho lejet'];
}

// 6. Config file
$config_ok = file_exists(__DIR__ . '/config/config.php');
$checks[] = ['config/config.php ekziston', $config_ok, $config_ok ? 'OK' : 'MUNGON'];

// 7. Detected SITE_URL
$checks[] = ['SITE_URL i detektuar automatikisht', true, $site_url];

// 8. Test DB connection
$db_ok = false;
$db_msg = '';
$tables_ok = false;
$tables_msg = '';
$data_ok   = false;

// Load config for DB credentials
if ($config_ok) {
    require_once __DIR__ . '/config/config.php';
    $db_host = DB_HOST;
    $db_name = DB_NAME;
    $db_user = DB_USER;
    $db_pass = DB_PASS;

    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass,
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db_ok  = true;
        $db_msg = "Lidhur me {$db_user}@{$db_host}/{$db_name}";
        $checks[] = ['Lidhja me MySQL', true, $db_msg];

        // Check tables
        $req_tables = ['users','properties','property_images','property_documents',
                       'property_features','appointments','messages','favorites',
                       'reviews','activity_log','email_queue','payments'];
        $existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $missing  = array_diff($req_tables, $existing);

        if (empty($missing)) {
            $tables_ok  = true;
            $tables_msg = count($existing) . ' tabela të gjetura';
            $checks[]   = ['Tabelat e DB', true, $tables_msg];

            // Check data
            $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $prop_count = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
            $data_ok    = ($user_count > 0);
            $checks[]   = ['Të dhëna fillestare (seed)', $data_ok,
                           "Perdorues: {$user_count}, Prona: {$prop_count}" . ($data_ok ? '' : ' — KA NEVOJË PËR IMPORT')];
        } else {
            $tables_msg = 'Tabela që mungojnë: ' . implode(', ', $missing);
            $checks[]   = ['Tabelat e DB', false, $tables_msg . ' — IMPORTO database/proesta.sql'];
        }

    } catch (PDOException $e) {
        $db_msg  = $e->getMessage();
        $checks[] = ['Lidhja me MySQL', false, 'GABIM: ' . $db_msg . ' — kontrollo kredencialet në config/config.php'];
    }
}

// 9. CSS file accessible
$css_exists = file_exists(__DIR__ . '/assets/css/style.css');
$checks[] = ['CSS ekziston', $css_exists, $css_exists ? 'OK — ' . round(filesize(__DIR__.'/assets/css/style.css')/1024) . 'KB' : 'MUNGON'];

$all_ok = !in_array(false, array_column($checks, 1));

?><!DOCTYPE html>
<html lang="sq">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ProEstate — Setup & Diagnostikë</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f2f5; color: #1a1a2e; }
    .wrap { max-width: 760px; margin: 40px auto; padding: 0 20px 60px; }
    .logo { font-size: 2rem; font-weight: 800; color: #0a1628; margin-bottom: 8px; }
    .logo span { color: #c8972a; }
    .subtitle { color: #6b7280; font-size: .95rem; margin-bottom: 32px; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
    .card-header { background: #0a1628; color: #fff; padding: 16px 24px; font-weight: 700; font-size: 1rem; }
    .check-row { display: flex; align-items: flex-start; gap: 14px; padding: 13px 24px; border-bottom: 1px solid #f0f2f5; }
    .check-row:last-child { border-bottom: none; }
    .icon { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
    .label { font-weight: 600; font-size: .9rem; margin-bottom: 2px; }
    .value { font-size: .82rem; color: #6b7280; font-family: monospace; }
    .value.fail { color: #dc2626; font-weight: 600; }
    .banner { padding: 20px 24px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; font-size: 1.05rem; }
    .banner.ok   { background: #dcfce7; color: #15803d; border: 2px solid #86efac; }
    .banner.fail { background: #fee2e2; color: #dc2626; border: 2px solid #fca5a5; }
    .steps { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 16px rgba(0,0,0,.08); margin-bottom: 24px; }
    .steps h3 { color: #0a1628; margin-bottom: 16px; font-size: 1.1rem; }
    .step { display: flex; gap: 14px; margin-bottom: 16px; align-items: flex-start; }
    .step-num { width: 28px; height: 28px; background: #0a1628; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; flex-shrink: 0; }
    .step-body { flex: 1; }
    .step-body strong { display: block; margin-bottom: 4px; color: #0a1628; }
    .step-body code { background: #f0f2f5; padding: 8px 12px; border-radius: 6px; display: block; font-size: .82rem; font-family: monospace; margin-top: 6px; white-space: pre-wrap; word-break: break-all; }
    .btn { display: inline-block; padding: 10px 24px; background: #c8972a; color: #fff; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: .9rem; margin-right: 10px; }
    .btn-outline { background: transparent; color: #0a1628; border: 2px solid #0a1628; }
    .warning { background: #fef3c7; border: 1px solid #fcd34d; color: #92400e; padding: 12px 16px; border-radius: 8px; font-size: .85rem; margin-top: 20px; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="logo">Pro<span>Estate</span></div>
  <p class="subtitle">Faqja e Instalimit dhe Diagnostikës</p>

  <?php if ($all_ok): ?>
  <div class="banner ok">✅ Gjithçka është OK! ProEstate është gati për t'u përdorur.</div>
  <?php else: ?>
  <div class="banner fail">⚠️ Ka probleme që duhen rregulluar. Shiko detajet më poshtë.</div>
  <?php endif; ?>

  <!-- Checks -->
  <div class="card">
    <div class="card-header">🔍 Kontrolli i Sistemit</div>
    <?php foreach ($checks as $check): ?>
    <div class="check-row">
      <span class="icon"><?= $check[1] ? '✅' : '❌' ?></span>
      <div>
        <div class="label"><?= htmlspecialchars($check[0]) ?></div>
        <div class="value <?= $check[1] ? '' : 'fail' ?>"><?= htmlspecialchars($check[2]) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Installation steps -->
  <?php if (!$db_ok || !$tables_ok || !$data_ok): ?>
  <div class="steps">
    <h3>📋 Hapat e Instalimit</h3>

    <div class="step">
      <div class="step-num">1</div>
      <div class="step-body">
        <strong>Konfigurim i Databazës</strong>
        Hap <code>config/config.php</code> dhe kontrollo/ndrysho:
        <code>define('DB_HOST', 'localhost');
define('DB_NAME', 'proesta');
define('DB_USER', 'root');       // emri i përdoruesit MySQL
define('DB_PASS', '');           // fjalëkalimi MySQL (bosh për XAMPP default)</code>
      </div>
    </div>

    <?php if ($db_ok && !$tables_ok): ?>
    <div class="step">
      <div class="step-num">2</div>
      <div class="step-body">
        <strong>Importo Bazën e të Dhënave</strong>
        Hap <strong>phpMyAdmin</strong> → zgjidh databazën <code>proesta</code> (krijo nëse nuk ekziston) → kliko <strong>Import</strong> → zgjidh skedarin:
        <code><?= htmlspecialchars($self_dir . '/database/proesta.sql') ?></code>
        <br>
        <strong>OSE</strong> nga terminali:
        <code>mysql -u root -p proesta < "<?= htmlspecialchars($self_dir) ?>/database/proesta.sql"</code>
      </div>
    </div>
    <?php elseif (!$db_ok): ?>
    <div class="step">
      <div class="step-num">2</div>
      <div class="step-body">
        <strong>Krijo Databazën</strong>
        Hap phpMyAdmin → kliko "New" → emërto <code>proesta</code> → charset: <code>utf8mb4_unicode_ci</code> → kliko "Create".
        <br>Pastaj importo skedarin: <code>database/proesta.sql</code>
      </div>
    </div>
    <?php endif; ?>

    <div class="step">
      <div class="step-num">3</div>
      <div class="step-body">
        <strong>Konfiguro PayPal (opsionale)</strong>
        Për të testuar pagesat, shko te <strong>developer.paypal.com</strong> → My Apps → krijo app Sandbox → vendos:
        <code>PAYPAL_CLIENT_ID=AaBbCc...
PAYPAL_CLIENT_SECRET=EeFfGg...</code>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- SITE_URL info -->
  <div class="steps">
    <h3>🌐 URL e Detektuar</h3>
    <div class="step">
      <div class="step-num">i</div>
      <div class="step-body">
        <strong>ProEstate është gjendet te:</strong>
        <code><?= htmlspecialchars($site_url) ?></code>
        Nëse kjo URL është e gabuar, hap <code>config/config.php</code>, fshij komentët dhe vendos manualisht:
        <code>define('SITE_URL', '<?= htmlspecialchars($site_url) ?>');</code>
      </div>
    </div>
  </div>

  <!-- Quick import button -->
  <?php if ($db_ok && !$tables_ok): ?>
  <div class="card" style="padding:24px;">
    <h3 style="margin-bottom:16px;">⚡ Import i Shpejtë i DB</h3>
    <form method="POST">
      <input type="hidden" name="do_import" value="1">
      <button type="submit" style="padding:12px 24px;background:#0a1628;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem;">
        📥 Importo database/proesta.sql Tani
      </button>
    </form>
  </div>

  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_import'])) {
      $sql = file_get_contents(__DIR__ . '/database/proesta.sql');
      try {
          // Execute SQL statements one by one
          $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
          // Split by ; but be careful with stored procedures
          $stmts = array_filter(array_map('trim', explode(";\n", $sql)));
          $ok = 0; $fail = 0;
          foreach ($stmts as $stmt) {
              if (empty($stmt) || substr($stmt, 0, 2) === '--') continue;
              try { $pdo->exec($stmt); $ok++; } catch (Exception $e) { $fail++; }
          }
          $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
          echo "<div class='banner ok' style='margin-top:16px;'>✅ Import i kryer! {$ok} deklarata. Rifresko faqen.</div>";
      } catch (Exception $e) {
          echo "<div class='banner fail' style='margin-top:16px;'>❌ " . htmlspecialchars($e->getMessage()) . "</div>";
      }
  }
  ?>
  <?php endif; ?>

  <!-- Links -->
  <div style="margin-top:24px;">
    <?php if ($all_ok): ?>
    <a href="<?= htmlspecialchars($site_url) ?>/index.php" class="btn">🏠 Hap ProEstate</a>
    <a href="<?= htmlspecialchars($site_url) ?>/login.php" class="btn btn-outline">🔑 Hyrje</a>
    <?php endif; ?>
  </div>

  <div class="warning">
    ⚠️ <strong>Siguri:</strong> Fshi ose riemëro skedarin <code>setup.php</code> pas instalimit të suksesshëm.
    Mos e lër të aksesueshëm në server të prodhimit.
  </div>
</div>
</body>
</html>
