/* =============================================================================
   ProEstate — main.js
   jQuery 3.7 + vanilla JS
   ============================================================================= */
'use strict';

$(function () {
  const siteUrl = (window.SITE_URL || '').replace(/\/$/, '');

  /* --- Navbar scroll effect --- */
  const $header = $('#site-header');
  $(window).on('scroll', function () {
    $header.toggleClass('scrolled', $(this).scrollTop() > 40);
  });

  /* --- Hamburger menu with morphing X --- */
  $('#hamburger').on('click', function () {
    const $menu = $('#nav-menu');
    const open  = $menu.hasClass('open');
    $menu.toggleClass('open');
    $(this).toggleClass('open');
    $(this).attr('aria-expanded', !open);
  });

  $('#nav-menu .nav-link').on('click', function () {
    $('#nav-menu').removeClass('open');
    $('#hamburger').removeClass('open').attr('aria-expanded', false);
  });

  /* Close menu on outside click */
  $(document).on('click', function (e) {
    if (!$(e.target).closest('.navbar').length) {
      $('#nav-menu').removeClass('open');
      $('#hamburger').removeClass('open').attr('aria-expanded', false);
    }
  });

  /* --- Dropdown --- */
  $('.dropdown__trigger').on('click', function (e) {
    e.stopPropagation();
    const $menu = $(this).siblings('.dropdown__menu');
    $menu.toggleClass('open');
  });
  $(document).on('click', function () {
    $('.dropdown__menu').removeClass('open');
  });

  /* --- Hero search tabs --- */
  $(document).on('click', '.search-tab', function () {
    const status = $(this).data('status');
    $('.search-tab').removeClass('active');
    $(this).addClass('active');
    $('input[name="status"]').val(status);
    // Update label dynamically if present
    const label = status === 'for_sale' ? 'Çmimi Maks (€)' : 'Qiraja Maks (€/muaj)';
    $('.price-label').text(label);
  });

  /* --- Active nav link highlight --- */
  const currentUrl = new URL(window.location.href);
  let activeMatched = false;
  $('.nav-link').removeClass('active');
  $('.nav-link').each(function () {
    const href = new URL($(this).attr('href'), window.location.href);
    if (href.pathname === currentUrl.pathname && href.search === currentUrl.search) {
      $(this).addClass('active');
      activeMatched = true;
    }
  });
  if (!activeMatched) {
    $('.nav-link').each(function () {
      const href = new URL($(this).attr('href'), window.location.href);
      if (href.pathname === currentUrl.pathname && !href.search) {
        $(this).addClass('active');
        return false;
      }
    });
  }

  /* --- Flash auto-dismiss --- */
  setTimeout(function () {
    $('.flash').fadeOut(400, function () { $(this).remove(); });
  }, 5000);

  /* --- ProEstate chatbot (preset answers, no API key needed) --- */
  initChatbot(siteUrl);

  /* --- Favorites toggle --- */
  window.toggleFav = function (e, propertyId) {
    e.preventDefault();
    e.stopPropagation();
    const $btn = $(e.currentTarget);
    $.ajax({
      url: siteUrl + '/api/favorites.php',
      method: 'POST',
      data: { property_id: propertyId, _proesta_csrf: window.CSRF_TOKEN || '' },
      dataType: 'json',
      success: function (res) {
        if (res.success) {
          $btn.toggleClass('active', res.added);
          $btn.attr('aria-pressed', res.added ? 'true' : 'false');
          if ($btn.find('svg').length) {
            $btn.find('svg').css('fill', res.added ? 'currentColor' : 'none');
          } else {
            $btn.html(res.added ? '♥' : '♡');
          }
          showToast(res.added ? 'Shtuar në preferuara' : 'Hequr nga preferuarat', 'info');
        } else {
          if (res.redirect) window.location.href = res.redirect;
          else showToast(res.message, 'error');
        }
      },
      error: function () { showToast('Gabim. Provoni sërish.', 'error'); }
    });
  };

  /* --- Mark fav buttons already active --- */
  if (window.USER_FAVS && window.USER_FAVS.length) {
    window.USER_FAVS.forEach(function (id) {
      $('[data-id="' + id + '"] .btn-fav').addClass('active').attr('aria-pressed', 'true');
    });
  }

  /* --- Lightweight client-side form feedback --- */
  $('form').on('submit', function () {
    const $form = $(this);
    let valid = true;

    $form.find('[required]').each(function () {
      const $field = $(this);
      const empty = !$field.val();
      $field.toggleClass('is-invalid', empty);
      if (empty) valid = false;
    });

    if (!valid) {
      showToast('Plotësoni fushat e detyrueshme.', 'warning');
      return false;
    }

    const $submit = $form.find('button[type="submit"]').first();
    if ($submit.length && !$submit.data('keep-enabled')) {
      $submit.data('original-text', $submit.html());
      $submit.prop('disabled', true).addClass('is-loading');
      setTimeout(function () {
        $submit.prop('disabled', false).removeClass('is-loading');
        if ($submit.data('original-text')) $submit.html($submit.data('original-text'));
      }, 4500);
    }
  });

  /* --- Image gallery lightbox (property detail) --- */
  $(document).on('click', '.prop-gallery__thumbs img', function () {
    const src = $(this).attr('src');
    $('.prop-gallery__main img').attr('src', src);
    $('.prop-gallery__thumbs img').removeClass('active');
    $(this).addClass('active');
  });

  /* --- Confirm delete --- */
  $(document).on('click', '.confirm-delete', function (e) {
    const msg = $(this).data('confirm') || 'Jeni i sigurt?';
    if (!confirm(msg)) e.preventDefault();
  });

  /* --- File upload dropzone --- */
  initDropzone();

  /* --- Price range slider (if present) --- */
  const $priceMin = $('#price_min');
  const $priceMax = $('#price_max');
  if ($priceMin.length && $priceMax.length) {
    $priceMin.on('input', function () {
      $('#price_min_display').text(parseInt($(this).val()).toLocaleString('sq-AL'));
    });
    $priceMax.on('input', function () {
      const v = parseInt($(this).val());
      $('#price_max_display').text(v >= 2000000 ? 'Pa limit' : v.toLocaleString('sq-AL'));
    });
  }

  /* --- Search form -- change URL params --- */
  $('#main-search-form').on('submit', function () {
    // Remove empty fields to keep URL clean
    $(this).find('input, select').each(function () {
      if (!$(this).val()) $(this).prop('disabled', true);
    });
  });

  /* --- Toast notification helper --- */
  window.showToast = function (message, type = 'info') {
    const icons  = { success: '✓', error: '✕', info: 'ℹ', warning: '⚠' };
    const $toast = $('<div class="flash flash--' + type + '">' +
      '<span class="flash__icon">' + (icons[type] || 'ℹ') + '</span>' +
      '<span>' + $('<span>').text(message).html() + '</span>' +
      '<button class="flash__close" onclick="this.parentElement.remove()">×</button>' +
      '</div>');
    $('#flash-container').append($toast);
    setTimeout(function () { $toast.fadeOut(400, function () { $(this).remove(); }); }, 4000);
  };

  /* --- Smooth anchor scrolling --- */
  $(document).on('click', 'a[href^="#"]', function (e) {
    const target = $($(this).attr('href'));
    if (target.length) {
      e.preventDefault();
      $('html,body').animate({ scrollTop: target.offset().top - 84 }, 400);
    }
  });

  /* --- Copy to clipboard --- */
  $(document).on('click', '.copy-btn', function () {
    const text = $(this).data('copy');
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function () {
        showToast('Kopjuar!', 'success');
      });
    }
  });

  /* --- Appointment form time validation --- */
  $('[name="scheduled_date"]').on('change', function () {
    const val = $(this).val();
    const day = new Date(val).getDay();
    if (day === 0) {
      showToast('Të dielave nuk pranohen takime. Zgjidhni ditë tjetër.', 'warning');
      $(this).val('');
    }
  });

  /* --- Review rating stars --- */
  $(document).on('click', '.stars--interactive .star', function () {
    const val = $(this).index() + 1;
    $(this).closest('.stars').find('.star').each(function (i) {
      $(this).toggleClass('star--filled', i < val);
    });
    $('input[name="rating"]').val(val);
  });

  /* --- Admin: toggle property active status --- */
  $(document).on('change', '.toggle-active', function () {
    const id  = $(this).data('id');
    const val = $(this).is(':checked') ? 1 : 0;
    $.post(siteUrl + '/api/admin-actions.php', {
      action: 'toggle_property_active', id: id, value: val,
      _proesta_csrf: window.CSRF_TOKEN || ''
    }, function (res) {
      if (res.success) showToast('Statusi u ndryshua.', 'success');
    }, 'json');
  });

});

function initChatbot(siteUrl) {
  const $widget = $('#chatbot-widget');
  if (!$widget.length) return;

  const $toggle = $('#chatbot-toggle');
  const $panel = $('#chatbot-panel');
  const $messages = $('#chatbot-messages');
  const $form = $('#chatbot-form');
  const $input = $('#chatbot-input');
  let chatHistory = [];
  let waitingForBot = false;

  const answers = [
    {
      keys: ['profesionale', 'me profesionale', 'shpallja', 'pershkrim', 'foto', 'informacioni duhet te vendos'],
      text: 'Për një shpallje më profesionale, vendosni titull të qartë, lokacionin, çmimin, sipërfaqen, numrin e dhomave, përshkrim real të gjendjes së pronës dhe foto të pastra nga ambientet kryesore. Shtoni edhe avantazhe si ashensor, ballkon, parkim, afërsi me shkolla/transport dhe dokumentacion nëse është gati.',
      actions: [
        { label: 'Posto prone', url: siteUrl + '/dashboard/add-property.php' },
        { label: 'Shiko pronat', url: siteUrl + '/properties.php' }
      ]
    },
    {
      keys: ['proestate', 'platforma', 'platforme', 'website', 'faqja', 'projekti', 'sistemi', 'funksionon', 'cfare ofron', 'sherbime'],
      text: 'ProEstate eshte platforme per kerkimin, publikimin dhe menaxhimin e pronave. Perdoruesit mund te shohin prona per shitje ose qira, te perdorin filtra, te kontaktojne agjente, te caktojne takime dhe te kryejne pagesa rezervimi me PayPal.',
      actions: [
        { label: 'Shiko pronat', url: siteUrl + '/properties.php' },
        { label: 'Agjentet', url: siteUrl + '/agents.php' }
      ]
    },
    {
      keys: ['postoj', 'shtoj', 'publikoj', 'pronen time', 'apartamentin tim', 'jam pronar', 'shpallje'],
      text: 'Per te publikuar pronen tuaj, krijoni llogari ose hyni ne panel, pastaj hapni formularin "Posto Pronen". Aty plotesoni te dhenat, cmimin, lokacionin, pershkrimin dhe ngarkoni fotot.',
      actions: [
        { label: 'Regjistrohu', url: siteUrl + '/register.php' },
        { label: 'Posto prone', url: siteUrl + '/dashboard/add-property.php' }
      ]
    },
    {
      keys: ['kerko', 'prona', 'apartament', 'shtepi', 'villa', 'komerciale', 'truall'],
      text: 'Mund te kerkoni prona sipas statusit, lokacionit, tipit dhe cmimit. Faqja e pronave ka filtra per te ngushtuar rezultatet.',
      actions: [
        { label: 'Shiko pronat', url: siteUrl + '/properties.php' },
        { label: 'Prona premium', url: siteUrl + '/properties.php?is_featured=1' }
      ]
    },
    {
      keys: ['blej', 'blerje', 'shitje', 'sale', 'shes'],
      text: 'Per blerje, hapni listen e pronave per shitje dhe perdorni filtrat per qytetin, tipin e prones dhe buxhetin.',
      actions: [
        { label: 'Prona per shitje', url: siteUrl + '/properties.php?status=for_sale' }
      ]
    },
    {
      keys: ['qira', 'rent', 'rentoj', 'marr me qira'],
      text: 'Per qira, mund te shihni apartamente dhe prona te tjera sipas cmimit mujor, lokacionit dhe karakteristikave.',
      actions: [
        { label: 'Prona me qira', url: siteUrl + '/properties.php?status=for_rent' }
      ]
    },
    {
      keys: ['agjent', 'agjente', 'agent', 'kontakt'],
      text: 'Agjentet mund t\'ju ndihmojne me vizita, pyetje rreth prones dhe negociim. Zgjidhni nje agjent nga lista per detajet e kontaktit.',
      actions: [
        { label: 'Shiko agjentet', url: siteUrl + '/agents.php' },
        { label: 'Na kontaktoni', url: siteUrl + '/contact.php' }
      ]
    },
    {
      keys: ['rezervo takim', 'rezervosh takim', 'rezervim takimi', 'takim manualisht', 'me rezervo', 'caktoj takim', 'vizite prone'],
      text: 'Nuk mund ta rezervoj takimin direkt nga chat-i, por mund t\'ju udhezoj. Hapni faqen e prones qe ju intereson, zgjidhni daten dhe oren e vizites, pastaj vazhdoni me pagesen e rezervimit me PayPal. Pas konfirmimit, takimi shfaqet ne panelin tuaj.',
      actions: [
        { label: 'Kerko prone', url: siteUrl + '/properties.php' },
        { label: 'Takimet', url: siteUrl + '/dashboard/appointments.php' }
      ]
    },
    {
      keys: ['takim', 'vizite', 'appointment', 'rezervo', 'caktoj'],
      text: 'Takimet caktohen nga faqja e prones ose permes agjentit. Zgjidhni pronen qe ju intereson dhe dergoni kerkesen per takim.',
      actions: [
        { label: 'Kerko prone', url: siteUrl + '/properties.php' },
        { label: 'Agjentet', url: siteUrl + '/agents.php' }
      ]
    },
    {
      keys: ['pagese', 'pagesa', 'paypal', 'abonim', 'premium'],
      text: 'Pagesat perdoren per sherbime premium ose publikime te vecanta. Pas pageses, sistemi ju kthen ne faqen e konfirmimit.',
      actions: [
        { label: 'Paneli im', url: siteUrl + '/dashboard/payments.php' }
      ]
    },
    {
      keys: ['llogari', 'regjistrohem', 'login', 'hyrje', 'fjalekalim', 'password'],
      text: 'Mund te krijoni llogari te re, te hyni ne llogarine ekzistuese ose te rikuperoni fjalekalimin nga faqet e hyrjes.',
      actions: [
        { label: 'Hyrje', url: siteUrl + '/login.php' },
        { label: 'Regjistrohu', url: siteUrl + '/register.php' }
      ]
    }
  ];

  function setOpen(open) {
    $widget.toggleClass('is-open', open);
    $toggle.attr('aria-expanded', open ? 'true' : 'false');
    try { localStorage.setItem('proestate_chatbot_open', open ? '1' : '0'); } catch (e) {}
    if (open) setTimeout(function () { $input.trigger('focus'); }, 120);
  }

  function normalize(text) {
    return String(text || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function getFallbackAnswer(question) {
    const clean = normalize(question);
    const matched = answers.find(function (answer) {
      return answer.keys.some(function (key) { return clean.includes(normalize(key)); });
    });

    return matched || {
      text: 'Jam ketu per t\'ju ndihmuar me ProEstate: prona, agjente, kerkime, filtra, takime, pagesa, llogari, admin ose statistika te platformes. Mund ta formuloni pyetjen pak me konkretisht dhe do t\'ju orientoj.',
      actions: [
        { label: 'Pronat', url: siteUrl + '/properties.php' },
        { label: 'Kontakt', url: siteUrl + '/contact.php' }
      ]
    };
  }

  function appendMessage(type, text, actions) {
    const $message = $('<div>').addClass('chatbot-message chatbot-message--' + type).text(cleanBotText(type, text));
    if (actions && actions.length) {
      const $actions = $('<div>').addClass('chatbot-message__actions');
      actions.forEach(function (action) {
        $('<a>').attr('href', action.url).text(action.label).appendTo($actions);
      });
      $message.append($actions);
    }
    $messages.append($message);
    $messages.scrollTop($messages.prop('scrollHeight'));
    return $message;
  }

  function cleanBotText(type, text) {
    if (type !== 'bot') return text;
    return String(text || '')
      .replace(/\*\*(.*?)\*\*/g, '$1')
      .replace(/`/g, '')
      .replace(/\/properties\.php\?status=for_sale/g, 'faqen e pronave per shitje')
      .replace(/\/properties\.php\?status=for_rent/g, 'faqen e pronave me qira')
      .replace(/\/properties\.php/g, 'faqen e pronave')
      .replace(/\/dashboard\/add-property\.php/g, 'formularin "Posto Pronen" ne panel')
      .replace(/\/dashboard\/appointments\.php/g, 'takimet ne panelin tuaj')
      .replace(/\/dashboard\/payments\.php/g, 'historikun e pagesave ne panel')
      .replace(/\/dashboard\/?/g, 'panelin tuaj')
      .replace(/\/contact\.php/g, 'faqen e kontaktit')
      .replace(/\/agents\.php/g, 'faqen e agjenteve')
      .replace(/\/login\.php/g, 'faqen e hyrjes')
      .replace(/\/register\.php/g, 'faqen e regjistrimit')
      .replace(/(?<!^)\s+-\s+/g, '\n- ');
  }

  function remember(role, text) {
    chatHistory.push({ role: role, text: text });
    if (chatHistory.length > 10) chatHistory = chatHistory.slice(-10);
  }

  function submitQuestion(question) {
    const text = $.trim(question);
    if (!text || waitingForBot) return;

    appendMessage('user', text);
    remember('user', text);
    $input.val('');

    const fallback = getFallbackAnswer(text);
    const $typing = appendMessage('bot', 'Duke menduar...');
    $typing.addClass('chatbot-message--typing');
    waitingForBot = true;
    $input.prop('disabled', true);

    $.ajax({
      url: siteUrl + '/api/chatbot.php',
      method: 'POST',
      dataType: 'json',
      data: {
        message: text,
        history: JSON.stringify(chatHistory.slice(-8)),
        _proesta_csrf: window.CSRF_TOKEN || ''
      },
      success: function (res) {
        const reply = res && res.success && res.reply ? res.reply : fallback.text;
        const actions = res && res.success && res.actions ? res.actions : fallback.actions;
        $typing.remove();
        appendMessage('bot', reply, actions);
        remember('assistant', reply);
      },
      error: function () {
        $typing.remove();
        appendMessage('bot', fallback.text, fallback.actions);
        remember('assistant', fallback.text);
      },
      complete: function () {
        waitingForBot = false;
        $input.prop('disabled', false).trigger('focus');
      }
    });
  }

  $toggle.on('click', function () {
    setOpen(!$widget.hasClass('is-open'));
  });

  $('#chatbot-suggestions').on('click', 'button', function () {
    submitQuestion($(this).data('question') || $(this).text());
  });

  $form.on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    submitQuestion($input.val());
    return false;
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $widget.hasClass('is-open')) setOpen(false);
  });

  try {
    setOpen(localStorage.getItem('proestate_chatbot_open') === '1');
  } catch (e) {}
}

/* --- Dropzone init --- */
function initDropzone() {
  const $dz = $('.dropzone');
  if (!$dz.length) return;

  $dz.on('click', function () {
    $(this).find('input[type="file"]').click();
  }).on('dragover dragenter', function (e) {
    e.preventDefault();
    $(this).addClass('dragover');
  }).on('dragleave drop', function (e) {
    $(this).removeClass('dragover');
    if (e.type === 'drop') {
      e.preventDefault();
      handleFiles(e.originalEvent.dataTransfer.files, $(this));
    }
  });

  $dz.find('input[type="file"]').on('change', function () {
    handleFiles(this.files, $(this).closest('.dropzone'));
  });
}

function handleFiles(files, $dz) {
  const $preview = $dz.siblings('.file-preview');
  $preview.empty();
  Array.from(files).forEach(function (file) {
    if (!file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = function (e) {
      $preview.append(
        '<div class="file-preview-item">' +
        '<img src="' + e.target.result + '" alt="preview">' +
        '<button class="remove-file" type="button">×</button>' +
        '</div>'
      );
    };
    reader.readAsDataURL(file);
  });
}

/* --- Sticky sidebar active link --- */
document.addEventListener('DOMContentLoaded', function () {
  const links = document.querySelectorAll('.sidebar__link');
  const path  = window.location.pathname;
  links.forEach(function (link) {
    try {
      if (new URL(link.href, window.location.href).pathname === path) {
        link.classList.add('active');
      }
    } catch (e) {}
  });
});

/* =============================================================
   SCROLL REVEAL — IntersectionObserver, GPU-safe
   Only animates elements explicitly marked with .reveal,
   plus section headers. Does NOT auto-animate every card.
============================================================= */
(function () {
  if (!window.IntersectionObserver) return;

  /* Only elements explicitly needing reveal + section headers */
  const SELECTORS = ['.reveal', '.section__header', '.cta-banner__inner'].join(',');

  const io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      /* Stagger siblings only within the same parent */
      const siblings = el.parentElement
        ? Array.from(el.parentElement.querySelectorAll(SELECTORS))
        : [el];
      const idx = siblings.indexOf(el);
      el.style.transitionDelay = Math.min(idx % 4, 3) * 70 + 'ms';
      el.classList.add('visible');
      io.unobserve(el);
    });
  }, { threshold: 0.06, rootMargin: '0px 0px -24px 0px' });

  function observe() {
    document.querySelectorAll(SELECTORS).forEach(function (el) {
      if (!el.classList.contains('visible')) io.observe(el);
    });
  }

  document.addEventListener('DOMContentLoaded', observe);
  observe();
})();

/* =============================================================
   BUTTON TACTILE PRESS — scale(0.98) on :active via JS
   Keeps all interactive elements feeling physical
============================================================= */
document.addEventListener('mousedown', function (e) {
  const btn = e.target.closest('.btn, .btn-icon, .nav-icon-btn');
  if (btn && !btn.closest('.btn-fav')) {
    btn.style.transition = 'transform .08s ease';
    btn.style.transform  = 'scale(.985)';
  }
});
document.addEventListener('mouseup', function (e) {
  const btn = e.target.closest('.btn, .btn-icon, .nav-icon-btn');
  if (btn) {
    btn.style.transform = '';
    setTimeout(function () { btn.style.transition = ''; }, 200);
  }
});
