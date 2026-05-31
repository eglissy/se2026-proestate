<?php
ob_start();
// admin/users.php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/email.php';

require_role('admin');

// Change role
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer(); csrf_check();
    if (isset($_POST['change_role'])) {
        $uid_t = (int)($_POST['user_id'] ?? 0);
        $role  = sanitize($_POST['role'] ?? '');
        if (in_array($role, ['admin','agent','owner','client']) && $uid_t !== current_user_id()) {
            db_query("UPDATE users SET role=? WHERE id=?", [$role, $uid_t]);
            flash_success('Roli u ndryshua.');
        }
    }
    if (isset($_POST['approve_agent'])) {
        $uid_t = (int)($_POST['user_id'] ?? 0);
        $agent = db_row("SELECT id, role, is_active FROM users WHERE id=?", [$uid_t]);
        if ($agent && $agent['role'] === 'agent' && !(int) $agent['is_active']) {
            db_query("UPDATE users SET is_active=1 WHERE id=?", [$uid_t]);
            log_activity(current_user_id(), 'agent_approved', "Agjent ID: {$uid_t}", get_client_ip());
            flash_success('Agjenti u aprovua.');
        } else {
            flash_error('Ky agjent nuk eshte ne pritje aprovimi.');
        }
    }
    if (isset($_POST['reject_agent'])) {
        $uid_t = (int)($_POST['user_id'] ?? 0);
        $agent = db_row("SELECT id, role, is_active FROM users WHERE id=?", [$uid_t]);
        if ($agent && $agent['role'] === 'agent' && !(int) $agent['is_active']) {
            db_query("DELETE FROM users WHERE id=? AND role='agent' AND is_active=0", [$uid_t]);
            log_activity(current_user_id(), 'agent_rejected', "Agjent ID: {$uid_t}", get_client_ip());
            flash_success('Kerkesa e agjentit u refuzua.');
        } else {
            flash_error('Ky agjent nuk mund te refuzohet.');
        }
    }
    redirect(SITE_URL . '/admin/users.php');
}

$q      = sanitize($_GET['q'] ?? '');
$filter = in_array($_GET['role'] ?? '', ['admin','agent','owner','client']) ? $_GET['role'] : '';
$status = in_array($_GET['status'] ?? '', ['active','inactive','pending_agents']) ? $_GET['status'] : '';
$where  = "1=1";
$params = [];
if ($q) { $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)"; $params = array_merge($params, ["%{$q}%","%{$q}%","%{$q}%"]); }
if ($filter) { $where .= " AND u.role=?"; $params[] = $filter; }
if ($status === 'active') { $where .= " AND u.is_active=1"; }
if ($status === 'inactive') { $where .= " AND u.is_active=0"; }
if ($status === 'pending_agents') { $where .= " AND u.role='agent' AND u.is_active=0"; }

$pending_agents_count = db_count("SELECT COUNT(*) FROM users WHERE role='agent' AND is_active=0");

$total  = db_count("SELECT COUNT(*) FROM users u WHERE {$where}", $params);
$paging = paginate($total, 20, (int)($_GET['page']??1));
$users  = db_rows(
    "SELECT u.*, COUNT(DISTINCT p.id) as prop_count
     FROM users u
     LEFT JOIN properties p ON p.owner_id=u.id OR p.agent_id=u.id
     WHERE {$where}
     GROUP BY u.id ORDER BY u.created_at DESC
     LIMIT {$paging['per_page']} OFFSET {$paging['offset']}",
    $params
);

$page_title = 'Perdoruesit - Admin ProEstate';
require dirname(__DIR__) . '/templates/header.php';
?>
<div class="dashboard">
  <?php include dirname(__DIR__) . '/dashboard/sidebar.php'; ?>
  <main class="dashboard__content">
    <div class="dashboard__header">
      <div>
        <h1 class="dashboard__title">Menaxhimi i Perdoruesve</h1>
        <p class="dashboard__subtitle"><?= $total ?> perdorues gjithsej</p>
      </div>
      <a href="<?= SITE_URL ?>/admin/users.php?status=pending_agents" class="btn btn--navy btn--sm">
        Aprovo / Refuzo agjente
      </a>
    </div>

    <?php if ($pending_agents_count > 0): ?>
    <div class="alert alert--warning" style="align-items:center;justify-content:space-between;">
      <span><strong><?= $pending_agents_count ?></strong> agjent<?= $pending_agents_count === 1 ? '' : 'e' ?> ne pritje per aprovim.</span>
      <a href="<?= SITE_URL ?>/admin/users.php?status=pending_agents" class="btn btn--sm btn--navy">Shiko kerkesat</a>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="GET" class="toolbar-form">
      <input type="text" name="q" class="form-control" value="<?= e($q) ?>"
             placeholder="Kërko emër, email...">
      <select name="role" class="form-control" onchange="this.form.submit()">
        <option value="">Të gjitha rolet</option>
        <?php foreach(['admin'=>'Admin','agent'=>'Agjent','owner'=>'Pronar','client'=>'Klient'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= $filter===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="form-control" onchange="this.form.submit()">
        <option value="">Te gjitha statuset</option>
        <option value="active" <?= $status==='active'?'selected':'' ?>>Aktiv</option>
        <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Jo aktiv</option>
        <option value="pending_agents" <?= $status==='pending_agents'?'selected':'' ?>>Agjente ne pritje</option>
      </select>
      <button type="submit" class="btn btn--navy btn--sm">Kërko</button>
      <?php if ($q||$filter||$status): ?><a href="?" class="btn btn--sm btn--outline-navy">Pastro</a><?php endif; ?>
    </form>

    <div class="data-table-wrap">
      <table class="data-table">
        <thead><tr>
          <th>Perdoruesi</th><th>Roli</th><th>Qyteti</th>
          <th>Prona</th><th>Regjistruar</th><th>Email</th><th>Status</th><th>Veprime</th>
        </tr></thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr id="user-row-<?= $u['id'] ?>">
            <td colspan="8" style="text-align:center;color:var(--gray-500);padding:28px;">
              <?= $status === 'pending_agents' ? 'Nuk ka agjente ne pritje per aprovim.' : 'Nuk u gjet asnje perdorues.' ?>
            </td>
          </tr>
          <?php endif; ?>
          <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <img src="<?= get_avatar_url($u['avatar']) ?>"
                     style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--gray-200);">
                <div>
                  <div style="font-weight:700;font-size:.875rem;"><?= e($u['first_name'].' '.$u['last_name']) ?></div>
                  <div style="font-size:.72rem;color:var(--gray-500);"><?= e($u['email']) ?></div>
                  <div style="font-size:.7rem;color:var(--gray-400);">Gjinia: <?= e(gender_label($u['gender'] ?? 'unspecified')) ?></div>
                </div>
              </div>
            </td>
            <td>
              <?php if ((int) $u['id'] !== current_user_id()): ?>
              <form method="POST" action="" style="display:inline-flex;gap:4px;align-items:center;">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <input type="hidden" name="change_role" value="1">
                <select name="role" class="form-control" style="padding:4px 8px;font-size:.78rem;width:120px;"
                        onchange="this.form.submit()">
                  <?php foreach(['admin','agent','owner','client'] as $r): ?>
                  <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= role_label($r) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <?php else: ?>
              <span class="role-badge"><?= role_label($u['role']) ?></span>
              <?php endif; ?>
            </td>
            <td style="font-size:.825rem;"><?= e($u['city']??'-') ?></td>
            <td><?= $u['prop_count'] ?></td>
            <td style="font-size:.78rem;color:var(--gray-500);"><?= format_date($u['created_at']) ?></td>
            <td>
              <?php if ($u['email_verified']): ?>
              <span style="color:var(--green);font-size:.78rem;">✓ Verifikuar</span>
              <?php else: ?>
              <span style="color:var(--gray-400);font-size:.78rem;">○ Pa verif.</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u['role'] === 'agent' && !(int) $u['is_active']): ?>
              <span class="status-pill status-pill--pending">Në pritje</span>
              <?php elseif ((int) $u['id'] !== current_user_id()): ?>
              <label style="cursor:pointer;display:flex;align-items:center;gap:4px;white-space:nowrap;">
                <input type="checkbox" class="toggle-user-active" data-id="<?= $u['id'] ?>"
                       <?= $u['is_active']?'checked':'' ?>>
                <span style="font-size:.78rem;"><?= $u['is_active']?'Aktiv':'Çaktiv' ?></span>
              </label>
              <?php else: ?>
              <span style="font-size:.78rem;color:var(--gray-400);">-</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:4px;flex-wrap:wrap;">
                <?php if ($u['role'] === 'agent' && !(int) $u['is_active']): ?>
                <button type="button" data-agent-action="approve_agent" data-id="<?= $u['id'] ?>"
                        class="btn btn--sm btn--primary js-agent-action" style="font-size:.72rem;padding:6px 10px;">
                  Aprovo
                </button>
                <button type="button" data-agent-action="reject_agent" data-id="<?= $u['id'] ?>"
                        class="btn btn--sm btn--danger js-agent-action" style="font-size:.72rem;padding:6px 10px;">
                  Refuzo
                </button>
                <?php endif; ?>
                <?php if (in_array($u['role'],['agent'])): ?>
                <a href="<?= SITE_URL ?>/agent.php?id=<?= $u['id'] ?>" class="btn-icon" style="background:var(--gray-100);" title="Profili">Pr</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/dashboard/messages.php?to=<?= $u['id'] ?>" class="btn-icon" style="background:var(--gold-pale);" title="Mesazh">Ms</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?= render_pagination($paging, '?q='.urlencode($q).'&role='.$filter.'&status='.$status) ?>
    </div>
  </main>
</div>
<script>
window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';
const ADMIN_ACTIONS_URL = '<?= SITE_URL ?>/api/admin-actions.php';

function postAdminAction(data) {
  data._proesta_csrf = window.CSRF_TOKEN || '';
  return fetch(ADMIN_ACTIONS_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams(data)
  }).then(function(response) {
    return response.json().catch(function() {
      return { success: false, message: 'Përgjigje e pavlefshme nga serveri.' };
    });
  });
}

function notifyAdminUser(message, type) {
  if (typeof showToast === 'function') {
    showToast(message, type || 'success');
  } else {
    alert(message);
  }
}

document.querySelectorAll('.toggle-user-active').forEach(function(chk) {
  chk.addEventListener('change', function() {
    postAdminAction({
      action:'toggle_user_active', id:this.dataset.id,
      value:this.checked?1:0
    }).then(function(r) {
      if (!r.success) alert(r.message || 'Veprimi deshtoi.');
    });
  });
});

document.querySelectorAll('.js-agent-action').forEach(function(btn) {
  btn.addEventListener('click', function() {
    const action = this.dataset.agentAction;
    const id = this.dataset.id;
    if (action === 'reject_agent' && !confirm('Refuzo dhe fshi kete kerkese agjenti?')) return;

    const row = document.getElementById('user-row-' + id);
    const buttons = row ? row.querySelectorAll('.js-agent-action') : [this];
    buttons.forEach(function(b) { b.disabled = true; });
    const originalText = this.textContent;
    this.textContent = 'Duke punuar...';

    postAdminAction({ action: action, id: id }).then(function(r) {
      if (!r.success) {
        alert(r.message || 'Veprimi deshtoi.');
        buttons.forEach(function(b) { b.disabled = false; });
        btn.textContent = originalText;
        return;
      }
      notifyAdminUser(r.message || 'Veprimi u krye.', 'success');
      if (row) {
        row.style.opacity = '.45';
        setTimeout(function() { window.location.reload(); }, 450);
      } else {
        window.location.reload();
      }
    }).catch(function() {
      alert('Gabim rrjeti. Provoni perseri.');
      buttons.forEach(function(b) { b.disabled = false; });
      btn.textContent = originalText;
    });
  });
});
</script>
<?php require dirname(__DIR__) . '/templates/footer.php'; ?>
