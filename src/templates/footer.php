<?php
// =============================================================================
// templates/footer.php - Footer global
// =============================================================================
?>
<footer class="site-footer">
  <div class="footer__top"></div>
  <div class="container">
    <div class="footer__grid">
      <div class="footer__brand">
        <a href="<?= SITE_URL ?>/index.php" class="footer__logo">
          Pro<strong>Estate</strong>
        </a>
        <p>Platformë për kërkim pronash, komunikim me agjentë dhe menaxhim takimesh në një vend.</p>
        <div class="footer__social">
          <a href="#" aria-label="Facebook">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
          </a>
          <a href="#" aria-label="Instagram">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
          </a>
          <a href="#" aria-label="LinkedIn">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
          </a>
        </div>
      </div>

      <div class="footer__col">
        <h4>Prona</h4>
        <ul>
          <li><a href="<?= SITE_URL ?>/properties.php?status=for_sale">Apartamente për Shitje</a></li>
          <li><a href="<?= SITE_URL ?>/properties.php?status=for_rent">Apartamente me Qira</a></li>
          <li><a href="<?= SITE_URL ?>/properties.php?type=villa">Vilat</a></li>
          <li><a href="<?= SITE_URL ?>/properties.php?type=commercial">Komerciale</a></li>
          <li><a href="<?= SITE_URL ?>/properties.php?type=land">Truallje</a></li>
          <li><a href="<?= SITE_URL ?>/properties.php?is_featured=1">Prona Premium</a></li>
        </ul>
      </div>

      <div class="footer__col">
        <h4>Shërbime</h4>
        <ul>
          <li><a href="<?= SITE_URL ?>/agents.php">Agjentët Tanë</a></li>
          <li><a href="<?= SITE_URL ?>/register.php?role=agent">Bëhu Agjent</a></li>
          <li><a href="<?= SITE_URL ?>/dashboard/add-property.php">Posto Pronën</a></li>
          <li><a href="<?= SITE_URL ?>/about.php">Rreth ProEstate</a></li>
          <li><a href="<?= SITE_URL ?>/contact.php">Na Kontaktoni</a></li>
        </ul>
      </div>

      <div class="footer__col">
        <h4>Kontakt</h4>
        <address>
          <p>Adresa: <?= SITE_ADDRESS ?></p>
          <p>Telefon: <a href="tel:<?= SITE_PHONE ?>"><?= SITE_PHONE ?></a></p>
          <p>Email: <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a></p>
          <p>Orari: E Hënë–E Shtunë, 09:00–18:00</p>
        </address>
      </div>
    </div>

    <div class="footer__bottom">
      <p>© <?= date('Y') ?> <?= SITE_NAME ?>. Të gjitha të drejtat e rezervuara.</p>
      <div class="footer__links">
        <a href="#">Politika e Privatësisë</a>
        <a href="#">Kushtet e Përdorimit</a>
        <a href="#">Cookies</a>
      </div>
    </div>
  </div>
</footer>

<div class="chatbot-widget" id="chatbot-widget" aria-live="polite">
  <button class="chatbot-toggle" id="chatbot-toggle" type="button" aria-label="Hap chatbot-in" aria-expanded="false">
    <span class="chatbot-toggle__mark">
      <img src="<?= SITE_URL ?>/assets/images/favicon.svg" alt="" width="32" height="32">
    </span>
    <span class="chatbot-toggle__copy">
      <span>Chat</span>
      <strong>ProEstate</strong>
    </span>
    <svg class="chatbot-toggle__icon chatbot-toggle__icon--close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M18 6 6 18M6 6l12 12"/>
    </svg>
  </button>

  <section class="chatbot-panel" id="chatbot-panel" aria-label="Asistenti ProEstate">
    <div class="chatbot-panel__head">
      <div class="chatbot-panel__brand">
        <img src="<?= SITE_URL ?>/assets/images/favicon.svg" alt="" width="38" height="38">
        <div>
        <span class="chatbot-panel__eyebrow">Asistenti i pronave</span>
        <h3>Si mund t'ju ndihmoj?</h3>
        </div>
      </div>
      <span class="chatbot-status"><span></span>Online</span>
    </div>

    <div class="chatbot-messages" id="chatbot-messages">
      <div class="chatbot-message chatbot-message--bot">
        Pershendetje! Jam asistenti AI i ProEstate. Shkruani pyetjen tuaj per pronat, agjentet, takimet, pagesat ose perdorimin e platformes.
      </div>
    </div>

    <div class="chatbot-suggestions" id="chatbot-suggestions" aria-label="Pyetje te shpejta">
      <button type="button" data-question="Si mund te kerkoj prona?">Kerko prona</button>
      <button type="button" data-question="Dua te blej nje apartament">Blerje</button>
      <button type="button" data-question="Dua prone me qira">Qira</button>
      <button type="button" data-question="Si caktoj takim me agjent?">Takim</button>
      <button type="button" data-question="Si postoj pronen time?">Posto prone</button>
      <button type="button" data-question="Si funksionon pagesa?">Pagesa</button>
    </div>

    <form class="chatbot-form" id="chatbot-form">
      <input id="chatbot-input" type="text" autocomplete="off" placeholder="Shkruani pyetjen..." aria-label="Shkruani pyetjen">
      <button type="submit" aria-label="Dergo pyetjen">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="m22 2-7 20-4-9-9-4Z"/>
          <path d="M22 2 11 13"/>
        </svg>
      </button>
    </form>
  </section>
</div>

<!-- Scripts -->
<script>
window.SITE_URL = window.SITE_URL || <?= json_encode(SITE_URL, JSON_UNESCAPED_SLASHES) ?>;
window.CSRF_TOKEN = window.CSRF_TOKEN || <?= json_encode(csrf_generate()) ?>;
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js?v=chatbot8"></script>
<?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
