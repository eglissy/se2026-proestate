<?php
// =============================================================================
// includes/email.php - Funksionet e Email-it
// =============================================================================

/**
 * Dërgo email HTML
 */
function send_email(string $to_email, string $to_name, string $subject, string $html_body): bool {
    $from_email = MAIL_FROM;
    $from_name  = MAIL_FROM_NAME;
    $boundary   = md5(uniqid());

    // Krijo headers profesionale
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "X-Mailer: ProEstate Platform\r\n";

    $full_html = email_layout($subject, $html_body);

    try {
        if (MAIL_USER !== '' && MAIL_PASS !== '') {
            $result = smtp_send_email($to_email, $to_name, $subject, $full_html, $headers);
        } else {
            $result = mail(
                "{$to_name} <{$to_email}>",
                '=?UTF-8?B?' . base64_encode($subject) . '?=',
                $full_html,
                $headers
            );
        }

        // Log në DB
        db_query(
            "INSERT INTO email_queue (to_email, to_name, subject, body, status, sent_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$to_email, $to_name, $subject, $html_body, $result ? 'sent' : 'failed']
        );

        return (bool)$result;
    } catch (Exception $e) {
        return false;
    }
}

function smtp_send_email(string $to_email, string $to_name, string $subject, string $html_body, string $headers): bool {
    $host = MAIL_HOST;
    $port = (int) MAIL_PORT;
    $timeout = 15;
    $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout);
    if (!$socket) return false;

    $read = function () use ($socket): string {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function (string $command) use ($socket, $read): string {
        fwrite($socket, $command . "\r\n");
        return $read();
    };
    $ok = function (string $response, array $codes): bool {
        return in_array((int) substr($response, 0, 3), $codes, true);
    };

    if (!$ok($read(), [220])) { fclose($socket); return false; }
    if (!$ok($cmd('EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), [250])) { fclose($socket); return false; }
    if ($port === 587) {
        if (!$ok($cmd('STARTTLS'), [220])) { fclose($socket); return false; }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($socket); return false; }
        if (!$ok($cmd('EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), [250])) { fclose($socket); return false; }
    }
    if (!$ok($cmd('AUTH LOGIN'), [334])) { fclose($socket); return false; }
    if (!$ok($cmd(base64_encode(MAIL_USER)), [334])) { fclose($socket); return false; }
    if (!$ok($cmd(base64_encode(MAIL_PASS)), [235])) { fclose($socket); return false; }

    $from = MAIL_FROM ?: MAIL_USER;
    if (!$ok($cmd('MAIL FROM:<' . $from . '>'), [250])) { fclose($socket); return false; }
    if (!$ok($cmd('RCPT TO:<' . $to_email . '>'), [250, 251])) { fclose($socket); return false; }
    if (!$ok($cmd('DATA'), [354])) { fclose($socket); return false; }

    $message  = "To: =?UTF-8?B?" . base64_encode($to_name) . "?= <{$to_email}>\r\n";
    $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $message .= $headers . "\r\n" . $html_body . "\r\n.";
    if (!$ok($cmd($message), [250])) { fclose($socket); return false; }
    $cmd('QUIT');
    fclose($socket);
    return true;
}

/**
 * Layout HTML bazë për emailet
 */
function email_layout(string $subject, string $content): string {
    $site_name = SITE_NAME;
    $site_url  = SITE_URL;
    $year      = date('Y');
    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>{$subject}</title>
    </head>
    <body style="margin:0;padding:0;background:#f5f7fa;font-family:Georgia,serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fa;padding:30px 0;">
        <tr><td align="center">
          <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
            <!-- Header -->
            <tr>
              <td style="background:#0a1628;padding:32px 40px;text-align:center;">
                <h1 style="margin:0;color:#c8972a;font-size:28px;letter-spacing:2px;">{$site_name}</h1>
                <p style="margin:4px 0 0;color:#8a9bb8;font-size:12px;letter-spacing:3px;text-transform:uppercase;">Platforma e Pronave të Paluajtshme</p>
              </td>
            </tr>
            <!-- Body -->
            <tr>
              <td style="padding:40px;color:#333;font-size:16px;line-height:1.7;">
                {$content}
              </td>
            </tr>
            <!-- Footer -->
            <tr>
              <td style="background:#f5f7fa;padding:24px 40px;text-align:center;border-top:1px solid #e5e7eb;">
                <p style="margin:0;color:#6b7280;font-size:13px;">
                  © {$year} {$site_name} · <a href="{$site_url}" style="color:#c8972a;text-decoration:none;">proestate.al</a>
                </p>
                <p style="margin:8px 0 0;color:#9ca3af;font-size:12px;">Ky email u dërgua automatikisht. Mos i përgjigjeni drejtpërsëdrejti.</p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}

/**
 * Email mirëpritjeje pas regjistrimit
 */
function send_welcome_email(string $email, string $first_name, string $token): bool {
    $verify_url = SITE_URL . '/verify-email.php?token=' . urlencode($token);
    $name = htmlspecialchars($first_name);
    $content = <<<HTML
    <h2 style="color:#0a1628;margin-top:0;">Mirë se vini, {$name}!</h2>
    <p>Faleminderit që u regjistruat në <strong>ProEstate</strong>, platformën shqiptare për prona të paluajtshme.</p>
    <p>Llogaria juaj është krijuar me sukses. Mund të filloni të kërkoni prona, të rezervoni takime dhe shumë më tepër.</p>
    <div style="text-align:center;margin:32px 0;">
      <a href="{$verify_url}" style="display:inline-block;background:#c8972a;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:600;letter-spacing:1px;">Verifiko Email-in</a>
    </div>
    <p style="color:#6b7280;font-size:14px;">Nëse nuk keni krijuar llogari, injoroni këtë email.</p>
    HTML;
    return send_email($email, $first_name, 'Mirë se vini në ProEstate!', $content);
}

/**
 * Email konfirmimi takimi
 */
function send_appointment_email(array $appointment, array $property, array $client, array $agent, string $type = 'confirmation'): bool {
    $date   = format_date($appointment['scheduled_date']);
    $time   = date('H:i', strtotime($appointment['scheduled_time']));
    $prop_t = htmlspecialchars($property['title']);
    $addr   = htmlspecialchars($property['address'] . ', ' . $property['city']);
    $aname  = htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']);
    $aphone = htmlspecialchars($agent['phone'] ?? '');

    if ($type === 'confirmation') {
        $content = <<<HTML
        <h2 style="color:#0a1628;margin-top:0;">Takimi juaj është Konfirmuar</h2>
        <p>Takimi për inspektimin e pronës u konfirmua me sukses.</p>
        <div style="background:#f8f9fa;border-left:4px solid #c8972a;padding:20px;border-radius:0 8px 8px 0;margin:24px 0;">
          <p style="margin:0 0 8px;"><strong>Prona:</strong> {$prop_t}</p>
          <p style="margin:0 0 8px;"><strong>Adresa:</strong> {$addr}</p>
          <p style="margin:0 0 8px;"><strong>Data:</strong> {$date}</p>
          <p style="margin:0 0 8px;"><strong>Ora:</strong> {$time}</p>
          <p style="margin:0;"><strong>Agjenti:</strong> {$aname} · {$aphone}</p>
        </div>
        <p>Nëse keni pyetje, kontaktoni agjentin drejtpërsëdrejti ose na shkruani.</p>
        HTML;
        return send_email($client['email'], $client['first_name'],
                          'Takimi juaj u konfirmua - ProEstate', $content);
    }

    if ($type === 'new_request') {
        $cname  = htmlspecialchars($client['first_name'] . ' ' . $client['last_name']);
        $cphone = htmlspecialchars($client['phone'] ?? '');
        $content = <<<HTML
        <h2 style="color:#0a1628;margin-top:0;">Kërkesë e Re Takimi</h2>
        <p>Keni një kërkesë të re takimi:</p>
        <div style="background:#f8f9fa;border-left:4px solid #c8972a;padding:20px;border-radius:0 8px 8px 0;margin:24px 0;">
          <p style="margin:0 0 8px;"><strong>Prona:</strong> {$prop_t}</p>
          <p style="margin:0 0 8px;"><strong>Data:</strong> {$date} në {$time}</p>
          <p style="margin:0 0 8px;"><strong>Klienti:</strong> {$cname}</p>
          <p style="margin:0;"><strong>Telefoni:</strong> {$cphone}</p>
        </div>
        <div style="text-align:center;margin:24px 0;">
          <a href="{$_SERVER['SCRIPT_FILENAME']}" style="display:inline-block;background:#0a1628;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;">Menaxho Takimin</a>
        </div>
        HTML;
        return send_email($agent['email'], $agent['first_name'],
                          'Kërkesë e re takimi - ProEstate', $content);
    }

    return false;
}

/**
 * Email njoftimi mesazhi i ri
 */
function send_message_notification(array $receiver, string $sender_name, string $subject): bool {
    $sname = htmlspecialchars($sender_name);
    $subj  = htmlspecialchars($subject);
    $inbox = SITE_URL . '/dashboard/messages.php';
    $content = <<<HTML
    <h2 style="color:#0a1628;margin-top:0;">Mesazh i Ri</h2>
    <p><strong>{$sname}</strong> ju ka dërguar një mesazh të ri:</p>
    <p style="background:#f8f9fa;padding:16px;border-radius:8px;font-style:italic;">"{$subj}"</p>
    <div style="text-align:center;margin:24px 0;">
      <a href="{$inbox}" style="display:inline-block;background:#c8972a;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;">Lexo Mesazhin</a>
    </div>
    HTML;
    return send_email($receiver['email'], $receiver['first_name'],
                      "Mesazh i ri nga {$sname} - ProEstate", $content);
}

/**
 * Email reset fjalëkalimi
 */
function send_password_reset_email(string $email, string $first_name, string $token): bool {
    $reset_url = SITE_URL . '/reset-password.php?token=' . urlencode($token);
    $name      = htmlspecialchars($first_name);
    $content   = <<<HTML
    <h2 style="color:#0a1628;margin-top:0;">Rivendosni Fjalëkalimin</h2>
    <p>Kemi marrë një kërkesë për rivendosjen e fjalëkalimit të llogarisë suaj <strong>ProEstate</strong>.</p>
    <div style="text-align:center;margin:32px 0;">
      <a href="{$reset_url}" style="display:inline-block;background:#c8972a;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:600;letter-spacing:1px;">Rivendos Fjalëkalimin</a>
    </div>
    <p style="color:#6b7280;font-size:14px;">Ky link është i vlefshëm për 2 orë. Nëse nuk e keni kërkuar, injoroni këtë email.</p>
    HTML;
    return send_email($email, $first_name, 'Rivendosni fjalëkalimin - ProEstate', $content);
}

/**
 * Email konfirmimi pagese PayPal
 */
function send_payment_confirmation_email(array $client, array $prop, string $date, string $time, float $amount, string $capture_id): bool {
    $name     = htmlspecialchars($client['first_name']);
    $prop_t   = htmlspecialchars($prop['title']);
    $fmt_date = date('d/m/Y', strtotime($date));
    $fmt_time = date('H:i', strtotime($time));
    $fmt_amt  = '€' . number_format($amount, 2);
    $dashboard= SITE_URL . '/dashboard/payments.php';

    $content = <<<HTML
    <h2 style="color:#0a1628;margin-top:0;">Pagesa u Konfirmua</h2>
    <p>Pershendetje <strong>{$name}</strong>, takimi juaj u konfirmua me sukses nëpërmjet PayPal.</p>
    <div style="background:#f8f9fa;border-left:4px solid #c8972a;padding:20px;border-radius:0 8px 8px 0;margin:24px 0;">
      <p style="margin:0 0 8px;"><strong>Prona:</strong> {$prop_t}</p>
      <p style="margin:0 0 8px;"><strong>Data:</strong> {$fmt_date}</p>
      <p style="margin:0 0 8px;"><strong>Ora:</strong> {$fmt_time}</p>
      <p style="margin:0 0 8px;"><strong>Shuma paguar:</strong> {$fmt_amt} EUR</p>
      <p style="margin:0;"><strong>Capture ID:</strong> <code>{$capture_id}</code></p>
    </div>
    <p style="color:#6b7280;font-size:14px;">Tarifa e rezervimit zbritet nga çmimi final nëse vendosni të blerë/merrni me qira pronën.</p>
    <div style="text-align:center;margin:24px 0;">
      <a href="{$dashboard}" style="display:inline-block;background:#c8972a;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;">Shiko Pagesën</a>
    </div>
HTML;
    return send_email($client['email'], $client['first_name'],
                      'Konfirmim Pagese PayPal - ProEstate', $content);
}
