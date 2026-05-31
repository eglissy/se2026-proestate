<?php
ob_start();
// dashboard/payments.php - Historia e pagesave
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_auth();
$uid  = current_user_id();
$role = current_user_role();

// Admin sheh të gjitha; klientët vetëm tonat
$where  = $role === 'admin' ? "1=1" : "py.user_id = {$uid}";
$total  = db_count("SELECT COUNT(*) FROM payments py WHERE {$where}");
$paging = paginate($total, 15, (int)($_GET['page']??1));

$payments = db_rows(
    "SELECT py.*,
       p.title AS prop_title, p.city,
       u.first_name, u.last_name,
       a.scheduled_date, a.scheduled_time
     FROM payments py
     JOIN properties p ON p.id = py.property_id
     JOIN users u ON u.id = py.user_id
     LEFT JOIN appointments a ON a.id = py.appointment_id
     WHERE {$where}
     ORDER BY py.paid_at DESC
     LIMIT {$paging['per_page']} OFFSET {$paging['offset']}",
);

// Statistikat
$total_earned = db_row(
    "SELECT SUM(amount) as total, COUNT(*) as cnt FROM payments py WHERE {$where} AND status='completed'"
);

$page_title = 'Pagesat - ProEstate';
require dirname(__DIR__) . '/templates/header.php';
?>

<div class="dashboard">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header">
      <h1 class="dashboard__title">Historia e Pagesave</h1>
      <p class="dashboard__subtitle"><?= $total ?> transaksione gjithsej</p>
    </div>

    <!-- Summary -->
    <div class="stats-grid" style="margin-bottom:24px;">
      <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--gold" style="font-size:.85rem;">EUR</div>
        <div>
          <div class="stat-card__value">€<?= number_format((float)($total_earned['total']??0), 0) ?></div>
          <div class="stat-card__label">Totali i Paguar</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--green" style="font-size:.85rem;">OK</div>
        <div>
          <div class="stat-card__value"><?= $total_earned['cnt'] ?? 0 ?></div>
          <div class="stat-card__label">Pagesa të Suksesshme</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--navy" style="font-size:.85rem;">PP</div>
        <div>
          <div class="stat-card__value">€<?= number_format(PAYPAL_RESERVATION_FEE, 0) ?></div>
          <div class="stat-card__label">Tarifë / Rezervim</div>
        </div>
      </div>
    </div>

    <div class="data-table-wrap">
      <?php if (empty($payments)): ?>
      <div class="table-empty">
        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
          <rect x="1" y="4" width="22" height="16" rx="2"/>
          <line x1="1" y1="10" x2="23" y2="10"/>
        </svg>
        <h3>Asnjë pagesë e gjetur</h3>
        <p>Pagesat tuaja PayPal do të shfaqen këtu.</p>
      </div>
      <?php else: ?>
      <table class="data-table">
        <thead><tr>
          <th>Prona</th>
          <?php if ($role==='admin'): ?><th>Klienti</th><?php endif; ?>
          <th>Data Takimit</th><th>Shuma</th>
          <th>Capture ID</th><th>Statusi</th><th>Paguar më</th>
        </tr></thead>
        <tbody>
          <?php foreach ($payments as $py): ?>
          <tr>
            <td>
              <a href="<?= SITE_URL ?>/property.php?id=<?= $py['property_id'] ?>" style="font-weight:600;color:var(--navy);font-size:.875rem;"><?= e(mb_substr($py['prop_title'],0,30)) ?></a>
              <div style="font-size:.75rem;color:var(--gray-500);"><?= e($py['city']) ?></div>
            </td>
            <?php if ($role==='admin'): ?>
            <td style="font-size:.825rem;"><?= e($py['first_name'].' '.$py['last_name']) ?><br>
              <span style="font-size:.72rem;color:var(--gray-400);"><?= e($py['payer_email']) ?></span>
            </td>
            <?php endif; ?>
            <td style="font-size:.825rem;">
              <?= $py['scheduled_date'] ? format_date($py['scheduled_date']) : '-' ?>
              <?php if ($py['scheduled_time']): ?>
              <div style="font-size:.72rem;color:var(--gray-500);"><?= date('H:i', strtotime($py['scheduled_time'])) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-weight:700;color:var(--gold);">€<?= number_format((float)$py['amount'], 2) ?></td>
            <td>
              <code style="font-size:.72rem;background:var(--gray-100);padding:2px 6px;border-radius:4px;color:var(--gray-600);">
                <?= e(mb_substr($py['paypal_capture_id'],0,18)) ?>...
              </code>
            </td>
            <td>
              <span class="payment-badge payment-badge--<?= $py['status'] ?>">
                <?php $slabels = ['completed'=>'Kryer','pending'=>'Pritje','refunded'=>'Rimbursuar','failed'=>'Dështoi'];
                echo $slabels[$py['status']] ?? $py['status']; ?>
              </span>
            </td>
            <td style="font-size:.78rem;color:var(--gray-500);white-space:nowrap;"><?= format_date($py['paid_at'], 'd/m/Y H:i') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?= render_pagination($paging, '?') ?>
      <?php endif; ?>
    </div>

    <!-- PayPal Info -->
    <div style="margin-top:24px;background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:20px;display:flex;align-items:flex-start;gap:16px;">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#009cde" stroke-width="1.5">
        <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
      </svg>
      <div>
        <h4 style="color:var(--navy);margin-bottom:4px;">Rreth Tarifës së Rezervimit</h4>
        <p style="font-size:.825rem;color:var(--gray-600);margin:0;">
          Tarifa e rezervimit (€<?= number_format(PAYPAL_RESERVATION_FEE, 0) ?>) paguhet me PayPal për të konfirmuar takimin e inspektimit.
          Kjo tarifë zbritet nga çmimi final nëse vendosni të blerë/merrni me qira pronën.
          Pagesat janë 100% të sigurta dhe të mbrojtuar nga PayPal Buyer Protection.
        </p>
      </div>
    </div>
  </main>
</div>
<script>window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
