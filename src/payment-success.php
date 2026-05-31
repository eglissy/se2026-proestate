<?php
// payment-success.php - Faqja e suksesit pas pagesës PayPal
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

require_auth();

// Kur PayPal ridrejton ketu pas miratimit manual (jo JS SDK - fallback)
// Normalisht JS SDK e kap approved-in direkt pa redirect
$payment = null;
$uid = current_user_id();

// Merr pagesën e fundit të suksesshme të këtij perdoruesi
$payment = db_row(
    "SELECT py.*, p.title AS prop_title, p.city, p.id AS prop_id,
       a.scheduled_date, a.scheduled_time, a.status AS appt_status
     FROM payments py
     JOIN properties p ON p.id = py.property_id
     LEFT JOIN appointments a ON a.id = py.appointment_id
     WHERE py.user_id = ? AND py.status = 'completed'
     ORDER BY py.paid_at DESC LIMIT 1",
    [$uid]
);

$page_title = 'Pagesa u Krye me Sukses - ProEstate';
require __DIR__ . '/templates/header.php';
?>

<section class="section">
  <div class="container container--narrow" style="text-align:center;max-width:600px;">

    <?php if ($payment): ?>
    <!-- SUCCESS -->
    <div style="margin-bottom:32px;">
      <div style="width:80px;height:80px;background:var(--green-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2.2rem;">✓</div>
      <h1 style="font-size:2rem;color:var(--navy);margin-bottom:8px;">Pagesa u Krye!</h1>
      <p style="color:var(--gray-500);font-size:1.05rem;">Rezervimi juaj u konfirmua me sukses. Faleminderit!</p>
    </div>

    <!-- Receipt Card -->
    <div class="card card-body" style="text-align:left;margin-bottom:24px;">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--gray-200);">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#009cde" stroke-width="2">
          <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
        </svg>
        <div>
          <div style="font-weight:700;color:var(--navy);">Faturë PayPal</div>
          <div style="font-size:.75rem;color:var(--gray-500);">Capture ID: <?= e($payment['paypal_capture_id']) ?></div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:10px;">
        <div style="display:flex;justify-content:space-between;font-size:.875rem;">
          <span style="color:var(--gray-600);">Prona</span>
          <strong><?= e($payment['prop_title']) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.875rem;">
          <span style="color:var(--gray-600);">Qyteti</span>
          <span><?= e($payment['city']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.875rem;">
          <span style="color:var(--gray-600);">Data e Takimit</span>
          <span><?= format_date($payment['scheduled_date']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.875rem;">
          <span style="color:var(--gray-600);">Ora</span>
          <span><?= date('H:i', strtotime($payment['scheduled_time'])) ?></span>
        </div>
        <div style="height:1px;background:var(--gray-200);margin:4px 0;"></div>
        <div style="display:flex;justify-content:space-between;font-size:1rem;font-weight:700;">
          <span style="color:var(--navy);">Shuma e Paguar</span>
          <span style="color:var(--gold);">€<?= number_format((float)$payment['amount'], 2) ?> <?= e($payment['currency']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.825rem;">
          <span style="color:var(--gray-500);">Paguar nga</span>
          <span><?= e($payment['payer_email']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.825rem;">
          <span style="color:var(--gray-500);">Data e pagesës</span>
          <span><?= format_date($payment['paid_at'], 'd/m/Y H:i') ?></span>
        </div>
      </div>
    </div>

    <div style="background:var(--gold-pale);border:1px solid #e6b84a;border-radius:var(--radius);padding:14px 18px;text-align:left;margin-bottom:24px;">
      <p style="font-size:.875rem;color:var(--navy);font-weight:600;margin-bottom:4px;">Çfarë ndodh tani?</p>
      <ul style="font-size:.825rem;color:var(--gray-700);padding-left:16px;margin:0;">
        <li>Agjenti do ju kontaktojë brenda 2 orëve</li>
        <li>Keni marrë email konfirmimi me detajet</li>
        <li>Tarifa e rezervimit zbritet nga çmimi final</li>
      </ul>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="<?= SITE_URL ?>/property.php?id=<?= $payment['prop_id'] ?>" class="btn btn--outline-navy">
        Shiko Pronën
      </a>
      <a href="<?= SITE_URL ?>/dashboard/appointments.php" class="btn btn--navy">
        Takimet e Mia
      </a>
    </div>

    <?php else: ?>
    <!-- No payment found -->
    <div style="margin:60px 0;">
      <h2>Asnjë pagesë e gjetur</h2>
      <p style="color:var(--gray-500);">Nuk u gjet asnjë pagesë aktive. Nëse keni kryer pagesë, kontrolloni emailin tuaj.</p>
      <a href="<?= SITE_URL ?>/dashboard/index.php" class="btn btn--navy" style="margin-top:20px;">Shko te Paneli</a>
    </div>
    <?php endif; ?>

  </div>
</section>

<script>window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';</script>
<?php require __DIR__ . '/templates/footer.php'; ?>
