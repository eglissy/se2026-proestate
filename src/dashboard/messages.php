<?php
ob_start();
// dashboard/messages.php - Inbox dhe dërgim mesazhesh
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_auth();
$uid = current_user_id();

// Dërgo mesazh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    check_referrer();
    csrf_check();

    $to_id   = (int)($_POST['to_id'] ?? 0);
    $subject = sanitize($_POST['subject'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $prop_id = (int)($_POST['property_id'] ?? 0) ?: null;

    if ($to_id && strlen($content) >= 2 && $to_id !== $uid) {
        // Verifikojmë ekzistencën e marrësit
        $receiver = db_row("SELECT id, first_name, last_name, email FROM users WHERE id=? AND is_active=1", [$to_id]);
        if ($receiver) {
            db_query(
                "INSERT INTO messages (sender_id, receiver_id, property_id, subject, content) VALUES (?,?,?,?,?)",
                [$uid, $to_id, $prop_id, $subject, $content]
            );
            // Email njoftim
            $sender = current_user();
            send_message_notification($receiver, $sender['first_name'].' '.$sender['last_name'], $subject ?: $content);
            flash_success('Mesazhi u dërgua!');
            log_activity($uid, 'message_sent', "Te: #{$to_id}", get_client_ip());
        }
    } else {
        flash_error('Plotëso fushat e detyrueshme.');
    }
    redirect(SITE_URL . '/dashboard/messages.php');
}

// Shëno si lexuar
if (isset($_GET['read'])) {
    $mid = (int)$_GET['read'];
    db_query("UPDATE messages SET is_read=1 WHERE id=? AND receiver_id=?", [$mid, $uid]);
}

// Shëno të gjitha si lexuar
if (isset($_GET['read_all'])) {
    db_query("UPDATE messages SET is_read=1 WHERE receiver_id=?", [$uid]);
    flash_success('Të gjitha mesazhet u shënuan si lexuara.');
    redirect(SITE_URL . '/dashboard/messages.php');
}

$view = sanitize($_GET['view'] ?? 'inbox'); // inbox | sent

if ($view === 'sent') {
    $messages = db_rows(
        "SELECT m.*, u.first_name AS r_first, u.last_name AS r_last, u.avatar AS r_avatar,
           p.title AS prop_title
         FROM messages m
         JOIN users u ON u.id = m.receiver_id
         LEFT JOIN properties p ON p.id = m.property_id
         WHERE m.sender_id = ?
         ORDER BY m.created_at DESC",
        [$uid]
    );
} else {
    $messages = db_rows(
        "SELECT m.*, u.first_name AS s_first, u.last_name AS s_last, u.avatar AS s_avatar,
           p.title AS prop_title
         FROM messages m
         JOIN users u ON u.id = m.sender_id
         LEFT JOIN properties p ON p.id = m.property_id
         WHERE m.receiver_id = ?
         ORDER BY m.created_at DESC",
        [$uid]
    );
}

// Mesazhi i zgjedhur
$selected_id = (int)($_GET['id'] ?? 0);
$selected    = null;
if ($selected_id) {
    $selected = db_row(
        "SELECT m.*, 
           s.first_name AS s_first, s.last_name AS s_last, s.avatar AS s_avatar,
           r.first_name AS r_first, r.last_name AS r_last,
           p.title AS prop_title, p.id AS prop_id
         FROM messages m
         JOIN users s ON s.id = m.sender_id
         JOIN users r ON r.id = m.receiver_id
         LEFT JOIN properties p ON p.id = m.property_id
         WHERE m.id=? AND (m.sender_id=? OR m.receiver_id=?)",
        [$selected_id, $uid, $uid]
    );
    if ($selected && $selected['receiver_id'] == $uid && !$selected['is_read']) {
        db_query("UPDATE messages SET is_read=1 WHERE id=?", [$selected_id]);
    }
}

// Pre-populate: new message to agent
$new_to     = (int)($_GET['to'] ?? 0);
$new_prop   = (int)($_GET['prop'] ?? 0);
$new_to_user = $new_to ? db_row("SELECT id, first_name, last_name FROM users WHERE id=?", [$new_to]) : null;

// Lista perdoruesve për compose (agjentë dhe admin)
$agents = db_rows("SELECT id, first_name, last_name, role FROM users WHERE role IN ('agent','admin','owner') AND is_active=1 AND id != ? ORDER BY first_name", [$uid]);

$page_title = 'Mesazhet - ProEstate';
require dirname(__DIR__) . '/templates/header.php';
?>

<div class="dashboard">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <h1 class="dashboard__title">Mesazhet</h1>
        <p class="dashboard__subtitle">Komunikimi juaj me agjentë dhe klientë</p>
      </div>
      <div style="display:flex;gap:8px;">
        <a href="?read_all=1" class="btn btn--sm btn--outline-navy">Shëno të gjitha si lexuara</a>
      </div>
    </div>

    <div class="messages-dashboard-grid">

      <!-- LEFT: List + Compose -->
      <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- Compose form -->
        <div class="card card-body">
          <h4 style="margin-bottom:14px;color:var(--navy);">Mesazh i Ri</h4>
          <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="send_message" value="1">
            <div class="form-group">
              <label>Dërgo te</label>
              <select name="to_id" class="form-control" required>
                <option value="">Zgjidh perdoruesin...</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $new_to === (int) $a['id'] ? 'selected' : '' ?>>
                  <?= e($a['first_name'].' '.$a['last_name']) ?> (<?= role_label($a['role']) ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($new_prop): ?>
            <input type="hidden" name="property_id" value="<?= $new_prop ?>">
            <p style="font-size:.78rem;color:var(--gray-500);margin-bottom:8px;">Lidhur me pronën #<?= $new_prop ?></p>
            <?php endif; ?>
            <div class="form-group">
              <label>Subjekti</label>
              <input type="text" name="subject" class="form-control" placeholder="p.sh. Pyetje për pronën..." maxlength="200">
            </div>
            <div class="form-group">
              <label>Mesazhi <span class="req">*</span></label>
              <textarea name="content" class="form-control" rows="4" required placeholder="Shkruani mesazhin tuaj..." maxlength="2000"></textarea>
            </div>
            <button type="submit" class="btn btn--primary btn--full">Dërgo Mesazhin</button>
          </form>
        </div>

        <!-- Inbox / Sent tabs -->
        <div class="card" style="overflow:hidden;">
          <div style="display:flex;border-bottom:1px solid var(--gray-200);">
            <a href="?view=inbox" style="flex:1;text-align:center;padding:12px;font-size:.875rem;font-weight:600;
                color:<?= $view==='inbox'?'var(--gold)':'var(--gray-500)' ?>;
                border-bottom:2px solid <?= $view==='inbox'?'var(--gold)':'transparent' ?>;">
              Inbox
            </a>
            <a href="?view=sent" style="flex:1;text-align:center;padding:12px;font-size:.875rem;font-weight:600;
                color:<?= $view==='sent'?'var(--gold)':'var(--gray-500)' ?>;
                border-bottom:2px solid <?= $view==='sent'?'var(--gold)':'transparent' ?>;">
              Të Dërguara
            </a>
          </div>

          <?php if (empty($messages)): ?>
          <div style="padding:32px;text-align:center;color:var(--gray-500);">
            <p style="margin-top:8px;">Asnjë mesazh ende</p>
          </div>
          <?php else: ?>
          <div style="max-height:500px;overflow-y:auto;">
            <?php foreach ($messages as $m):
              $other_name  = $view==='inbox' ? e($m['s_first'].' '.$m['s_last']) : e($m['r_first'].' '.$m['r_last']);
              $other_avatar= $view==='inbox' ? ($m['s_avatar']??null) : ($m['r_avatar']??null);
              $is_active   = (int) $m['id'] === $selected_id;
              $unread_cls  = ($view==='inbox' && !$m['is_read']) ? 'font-weight:700;' : '';
            ?>
            <a href="?view=<?= $view ?>&id=<?= $m['id'] ?>"
               style="display:flex;align-items:flex-start;gap:10px;padding:12px 16px;
                      background:<?= $is_active?'var(--gold-pale)':'transparent' ?>;
                      border-bottom:1px solid var(--gray-100);<?= $unread_cls ?>
                      transition:background var(--transition);">
              <img src="<?= get_avatar_url($other_avatar) ?>"
                   style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;margin-top:2px;">
              <div style="min-width:0;">
                <div style="font-size:.875rem;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $other_name ?></div>
                <div style="font-size:.78rem;color:var(--gray-500);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e(mb_substr($m['subject']??$m['content'],0,38)) ?></div>
                <div style="font-size:.72rem;color:var(--gray-400);margin-top:2px;"><?= time_ago($m['created_at']) ?></div>
              </div>
              <?php if ($view==='inbox' && !$m['is_read']): ?>
              <span style="width:8px;height:8px;background:var(--gold);border-radius:50%;flex-shrink:0;margin-top:8px;margin-left:auto;"></span>
              <?php endif; ?>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT: Message detail -->
      <div class="card card-body" style="min-height:400px;">
        <?php if ($selected): ?>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--gray-200);">
          <div style="display:flex;align-items:center;gap:12px;">
            <img src="<?= get_avatar_url($selected['s_avatar']) ?>"
                 style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);">
            <div>
              <div style="font-weight:700;color:var(--navy);"><?= e($selected['s_first'].' '.$selected['s_last']) ?></div>
              <div style="font-size:.8rem;color:var(--gray-500);">Për: <?= e($selected['r_first'].' '.$selected['r_last']) ?></div>
            </div>
          </div>
          <div style="text-align:right;font-size:.78rem;color:var(--gray-400);"><?= format_date($selected['created_at'], 'd/m/Y H:i') ?></div>
        </div>

        <?php if ($selected['prop_title']): ?>
        <div style="background:var(--gray-50);padding:10px 14px;border-radius:var(--radius);margin-bottom:16px;font-size:.825rem;">
          Lidhur me: <a href="<?= SITE_URL ?>/property.php?id=<?= $selected['prop_id'] ?>" style="color:var(--gold);font-weight:600;"><?= e($selected['prop_title']) ?></a>
        </div>
        <?php endif; ?>

        <?php if ($selected['subject']): ?>
        <h3 style="font-size:1.1rem;margin-bottom:12px;"><?= e($selected['subject']) ?></h3>
        <?php endif; ?>

        <div style="line-height:1.75;color:var(--gray-700);white-space:pre-line;"><?= nl2br(e($selected['content'])) ?></div>

        <!-- Quick reply -->
        <?php
        $reply_to = (int) $selected['sender_id'] === $uid ? (int) $selected['receiver_id'] : (int) $selected['sender_id'];
        if ($reply_to !== $uid):
        ?>
        <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--gray-200);">
          <h4 style="margin-bottom:12px;font-size:.9rem;">Kthej Përgjigje</h4>
          <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="send_message" value="1">
            <input type="hidden" name="to_id" value="<?= $reply_to ?>">
            <input type="hidden" name="subject" value="Re: <?= e($selected['subject'] ?? '') ?>">
            <?php if ($selected['prop_id']): ?>
            <input type="hidden" name="property_id" value="<?= $selected['prop_id'] ?>">
            <?php endif; ?>
            <div class="form-group">
              <textarea name="content" class="form-control" rows="3" required placeholder="Shkruaj përgjigjen..."></textarea>
            </div>
            <button type="submit" class="btn btn--primary btn--sm">Dërgo Përgjigjen</button>
          </form>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:300px;color:var(--gray-400);">
          <p>Zgjidh një mesazh për ta lexuar</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script>window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
