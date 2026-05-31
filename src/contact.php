<?php
ob_start();
// contact.php - Faqja e kontaktit
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_referrer();
    csrf_check();
    if (!rate_limit('contact', 5, 600)) {
        $errors[] = 'Shumë tentativa. Provoni pas 10 minutash.';
    } else {
        $name    = sanitize($_POST['name'] ?? '');
        $email   = strtolower(trim($_POST['email'] ?? ''));
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if (strlen($name) < 2)    $errors[] = 'Emri i shkurtër.';
        if (!is_valid_email($email)) $errors[] = 'Email i pavlefshëm.';
        if (strlen($message) < 10) $errors[] = 'Mesazhi i shkurtër.';

        if (empty($errors)) {
            $content = "
              <h3>Mesazh i ri nga Kontakti i Website</h3>
              <p><strong>Emri:</strong> " . htmlspecialchars($name) . "</p>
              <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
              <p><strong>Subjekti:</strong> " . htmlspecialchars($subject) . "</p>
              <p><strong>Mesazhi:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
            ";
            send_email(SITE_EMAIL, SITE_NAME, "Mesazh i ri: " . ($subject ?: 'Kontakt'), $content);
            log_activity(null, 'contact_form', "Nga: {$email}");
            $success = true;
        }
    }
}

$page_title = 'Kontakt - ProEstate';
require __DIR__ . '/templates/header.php';
?>

<header class="page-header page-header--center">
  <div class="container page-header__inner">
    <div class="page-header__eyebrow">Na Shkruani</div>
    <h1>Na Kontaktoni</h1>
    <p>Dërgoni pyetjen tuaj për prona, agjentë ose probleme teknike. Përgjigjja ruhet në workflow-in e email-it të projektit.</p>
  </div>
</header>

<section class="section">
  <div class="container">
    <div class="contact-grid">

      <!-- Contact Info -->
      <div>
        <h2 style="margin-bottom:24px;">Informacioni i Kontaktit</h2>

        <?php $infos = [
          ['A', 'Adresa', SITE_ADDRESS],
          ['T', 'Telefoni', SITE_PHONE],
          ['E', 'Email', SITE_EMAIL],
          ['O', 'Oraret', "E Hënë - E Shtunë\n09:00 - 18:00"],
        ]; foreach ($infos as $info): ?>
        <div class="contact-item">
          <div class="contact-item__icon"><?= e($info[0]) ?></div>
          <div>
            <div class="contact-item__title"><?= e($info[1]) ?></div>
            <div class="contact-item__text"><?= e($info[2]) ?></div>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="map-card">
          <div>
            <p style="font-size:.875rem;">Rruga e Kavajës, Tiranë</p>
            <a href="https://maps.google.com/?q=Tirana+Albania" target="_blank" class="btn btn--sm btn--outline-navy" style="margin-top:8px;">Hap në Google Maps</a>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="card card-body">
        <h3 style="margin-bottom:20px;">Dërgoni Mesazhin</h3>

        <?php if ($success): ?>
        <div class="flash flash--success" style="position:static;max-width:none;margin-bottom:20px;">
          <span class="flash__icon">✓</span>
          <span>Mesazhi u dërgua me sukses! Do t'ju kontaktojmë brenda 24 orëve.</span>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="flash flash--error" style="position:static;max-width:none;margin-bottom:20px;">
          <span class="flash__icon">X</span>
          <span><?= implode('<br>', array_map('e', $errors)) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="">
          <?= csrf_field() ?>
          <div class="form-row">
            <div class="form-group">
              <label>Emri Juaj <span class="req">*</span></label>
              <input type="text" name="name" class="form-control"
                     value="<?= e($_POST['name'] ?? (is_logged_in() ? current_user()['first_name'].' '.current_user()['last_name'] : '')) ?>"
                     placeholder="Emri Mbiemri" required>
            </div>
            <div class="form-group">
              <label>Email <span class="req">*</span></label>
              <input type="email" name="email" class="form-control"
                     value="<?= e($_POST['email'] ?? (is_logged_in() ? current_user()['email'] : '')) ?>"
                     placeholder="email@juaj.com" required>
            </div>
          </div>

          <div class="form-group">
            <label>Subjekti</label>
            <select name="subject" class="form-control">
              <option value="">Zgjidh subjektin...</option>
              <option>Informacion për pronë</option>
              <option>Bashkëpunim si agjent</option>
              <option>Problem teknik</option>
              <option>Sugjerim</option>
              <option>Tjetër</option>
            </select>
          </div>

          <div class="form-group">
            <label>Mesazhi <span class="req">*</span></label>
            <textarea name="message" class="form-control" rows="6" required
                      placeholder="Shkruani mesazhin tuaj këtu..."><?= e($_POST['message'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn--primary btn--full btn--lg">
            Dërgo Mesazhin
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>window.CSRF_TOKEN = '<?= e(csrf_generate()) ?>';</script>
<?php require __DIR__ . '/templates/footer.php'; ?>
