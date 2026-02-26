(function () {
  var CFG = window.AP_CORR || {};
  var API_URL = (CFG.apiUrl || '').toString();
  var BRIDGE = CFG.bridge || {};

  // Safety: jeśli Smarty/autoescape wstrzyknie &amp; do query string, naprawiamy po stronie JS.
  if (API_URL.indexOf('&amp;') !== -1) {
    API_URL = API_URL.replace(/&amp;/g, '&');
  }

  var state = {
    section: 'messages',
    filterBySection: {
      messages: 'msg_all',
      issues: 'iss_dispute_waiting'
    },
    search: '',
    selected: {
      messages: null,
      issues: null
    },

    paging: {
      threads: { offset: 0, total: 0, limit: 50, loading: false, hasMore: false },
      issues: { offset: 0, total: 0, limit: 50, loading: false, hasMore: false }
    },
    open: {
      thread: null,
      issue: null
    },
    detailBySection: {
      messages: false,
      issues: false
    },
    cacheBySection: {
      messages: [],
      issues: []
    }
  };


  // View mode: split (lista + podgląd) vs single (lista → pełny widok rozmowy)
  var VIEWMODE_KEY = 'ap_corr_viewmode_' + (BRIDGE.eid || '0');
  var viewMode = 'split';
  try {
    viewMode = localStorage.getItem(VIEWMODE_KEY) || 'split';
  } catch (e) {}
  if (viewMode !== 'split' && viewMode !== 'single') viewMode = 'split';

  var LABELS = {
    messages: {
      title: 'Wiadomości',
      filters: {
        msg_all: 'Wszystkie',
        msg_unread: 'Nieprzeczytane',
        msg_need_reply: 'Wymaga odpowiedzi',
        msg_order: 'Dot. zamówienia',
        msg_offer: 'Dot. oferty',
        msg_general: 'Ogólne',
        msg_attachments: 'Z załącznikami',
        msg_last24h: 'Ostatnie 24h',
        msg_last7d: 'Ostatnie 7 dni'
      }
    },
    issues: {
      title: 'Dyskusje / reklamacje',
      filters: {
        // (NOWE) – osobno dla dyskusji i reklamacji
        iss_dispute_all: 'Dyskusje: wszystkie',
        iss_dispute_new: 'Dyskusje: nowe',
        iss_dispute_waiting: 'Dyskusje: do odpowiedzi',
        iss_dispute_due_soon: 'Dyskusje: termin blisko',
        iss_dispute_ongoing: 'Dyskusje: w trakcie',
        iss_dispute_unresolved: 'Dyskusje: nierozwiązane',
        iss_dispute_closed: 'Dyskusje: zamknięte',

        iss_claim_all: 'Reklamacje: wszystkie',
        iss_claim_new: 'Reklamacje: nowe',
        iss_claim_waiting: 'Reklamacje: do odpowiedzi',
        iss_claim_due_soon: 'Reklamacje: termin blisko',
        iss_claim_submitted: 'Reklamacje: złożone',
        iss_claim_accepted: 'Reklamacje: zaakceptowane',
        iss_claim_rejected: 'Reklamacje: odrzucone',

        // kompatybilność (stare klucze – jeśli gdzieś zostały w state)
        iss_all: 'Wszystkie',
        iss_new: 'Nowe',
        iss_waiting_me: 'Do odpowiedzi',
        iss_due_soon: 'Termin blisko',

        // dodatkowe filtry dla reklamacji (Allegro)
        iss_expect_refund: 'Zwrot pieniędzy',
        iss_expect_partial_refund: 'Częściowy zwrot',
        iss_expect_exchange: 'Wymiana',
        iss_expect_repair: 'Naprawa',
        iss_return_required: 'Zwrot produktu wymagany',
        iss_right_warranty: 'Gwarancja',
        iss_right_complaint: 'Reklamacja'
      }
    }
  };

  function qs(sel) {
    return document.querySelector(sel);
  }

  function qsa(sel) {
    return Array.prototype.slice.call(document.querySelectorAll(sel));
  }

  function toInt(x, def) {
    var n = parseInt(x, 10);
    return isNaN(n) ? (def || 0) : n;
  }

  function showToast(type, msg) {
    msg = (msg || '').toString();
    if (!msg) return;

    var wrap = qs('.ap-toastwrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'ap-toastwrap';
      document.body.appendChild(wrap);
    }

    var el = document.createElement('div');
    el.className = 'ap-toast ap-toast--' + (type || 'info');
    el.textContent = msg;
    wrap.appendChild(el);

    setTimeout(function () {
      el.classList.add('ap-toast--hide');
      setTimeout(function () {
        if (el && el.parentNode) el.parentNode.removeChild(el);
      }, 220);
    }, 2600);
  }

  // === Row quick actions (menu) ===
  var rowMenuEl = null;
  var rowMenuCtx = null;

  function copyToClipboard(text) {
    var s = (text === undefined || text === null) ? '' : String(text);
    if (!s) return Promise.reject(new Error('Brak tekstu do skopiowania.'));
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(s);
    }
    return new Promise(function (resolve, reject) {
      try {
        var ta = document.createElement('textarea');
        ta.value = s;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        ta.style.top = '0';
        document.body.appendChild(ta);
        ta.select();
        var ok = document.execCommand('copy');
        document.body.removeChild(ta);
        if (ok) resolve();
        else reject(new Error('Nie udało się skopiować.'));
      } catch (e) {
        reject(e);
      }
    });
  }

  function ensureRowMenu() {
    if (rowMenuEl) return rowMenuEl;
    rowMenuEl = qs('#ap-rowMenu');
    if (!rowMenuEl) {
      rowMenuEl = document.createElement('div');
      rowMenuEl.id = 'ap-rowMenu';
      rowMenuEl.className = 'ap-menu';
      rowMenuEl.hidden = true;
      document.body.appendChild(rowMenuEl);
    }
    return rowMenuEl;
  }

  function closeRowMenu() {
    if (!rowMenuEl) return;
    rowMenuEl.hidden = true;
    rowMenuEl.innerHTML = '';
    rowMenuEl.removeAttribute('data-ap-key');
    rowMenuCtx = null;
  }

  function findThreadInCache(accountId, threadId) {
    var arr = state.cacheBySection.messages || [];
    for (var i = 0; i < arr.length; i++) {
      var it = arr[i];
      if (String(it.id_allegropro_account) === String(accountId) && String(it.thread_id) === String(threadId)) {
        return it;
      }
    }
    return null;
  }

  function openRowMenu(anchorBtn) {
    var menu = ensureRowMenu();
    var key = anchorBtn.getAttribute('data-ap-key') || '';
    if (!key) return;

    var parts = key.split(':');
    if (parts.length < 3 || parts[0] !== 'thread') return;
    var accountId = parts[1];
    var threadId = parts.slice(2).join(':');

    var it = findThreadInCache(accountId, threadId);

    rowMenuCtx = {
      key: key,
      accountId: accountId,
      threadId: threadId,
      item: it
    };

    // Build menu
    menu.innerHTML = '';
    menu.setAttribute('data-ap-key', key);

    var box = document.createElement('div');
    box.className = 'ap-menu__box';

    function addItem(label, action, disabled) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'ap-menu__item';
      b.textContent = label;
      b.setAttribute('data-ap-action', 'rowMenuItem');
      b.setAttribute('data-action', action);
      if (disabled) {
        b.disabled = true;
        b.classList.add('is-disabled');
      }
      box.appendChild(b);
    }

    addItem('Otwórz', 'open', false);

    if (it && String(it.read) === '0') {
      addItem('Oznacz jako przeczytane', 'markRead', false);
    }

    addItem('Kopiuj ID wątku', 'copyThreadId', false);

    if (it && it.checkout_form_id) {
      addItem('Kopiuj ID zamówienia', 'copyCheckout', false);
    }
    if (it && it.offer_id) {
      addItem('Kopiuj ID oferty', 'copyOffer', false);
    }

    menu.appendChild(box);
    menu.hidden = false;

    // Position near anchor
    var r = anchorBtn.getBoundingClientRect();
    var top = r.bottom + window.scrollY + 8;
    var left = r.left + window.scrollX;

    menu.style.position = 'absolute';
    menu.style.top = top + 'px';
    menu.style.left = left + 'px';

    // keep within viewport
    var mr = menu.getBoundingClientRect();
    var pad = 8;
    var maxLeft = window.scrollX + window.innerWidth - mr.width - pad;
    if (left > maxLeft) left = maxLeft;
    if (left < window.scrollX + pad) left = window.scrollX + pad;

    var maxTop = window.scrollY + window.innerHeight - mr.height - pad;
    if (top > maxTop) top = Math.max(window.scrollY + pad, r.top + window.scrollY - mr.height - 8);

    menu.style.top = top + 'px';
    menu.style.left = left + 'px';
  }

  function handleRowMenuAction(action) {
    var ctx = rowMenuCtx;
    if (!ctx) return;

    if (action === 'open') {
      closeRowMenu();
      // Mirror click on row
      state.selected.messages = ctx.key;
      qsa('[data-ap-list="threads"] [data-ap-key]').forEach(function (r) {
        r.classList.toggle('ap-row--active', r.getAttribute('data-ap-key') === ctx.key);
      });
      openDetail('messages');
      openThread(ctx.accountId, ctx.threadId);
      return;
    }

    if (action === 'markRead') {
      closeRowMenu();
      apiCall('thread.read', { account_id: ctx.accountId, thread_id: ctx.threadId }).then(function (res) {
        // Update cache item (so rerender keeps state)
        if (ctx.item) {
          ctx.item.read = 1;
        } else {
          var it2 = findThreadInCache(ctx.accountId, ctx.threadId);
          if (it2) it2.read = 1;
        }

        // Update row UI quickly (no refetch)
        var row = qs('[data-ap-list="threads"] [data-ap-key="' + ctx.key + '"]');
        if (row) {
          row.classList.remove('ap-row--unread');
          row.classList.remove('ap-ticketrow--unread');

          // Replace status pill text if present
          var pill = row.querySelector('.ap-pill.ap-pill--warn');
          if (pill && pill.textContent === 'Nieprzeczytane') {
            pill.textContent = 'Przeczytane';
            pill.classList.remove('ap-pill--warn');
            pill.classList.add('ap-pill--muted');
          }
        }

        showToast('success', 'Oznaczono jako przeczytane');
        refreshStats();
      }).catch(function (e) {
        showToast('error', 'Nie udało się oznaczyć jako przeczytane');
      });
      return;
    }

    if (action === 'copyThreadId') {
      copyToClipboard(ctx.threadId).then(function () {
        showToast('success', 'Skopiowano ID wątku');
      }).catch(function () {
        showToast('error', 'Nie udało się skopiować');
      });
      closeRowMenu();
      return;
    }

    if (action === 'copyCheckout') {
      var val = ctx.item && ctx.item.checkout_form_id ? ctx.item.checkout_form_id : '';
      copyToClipboard(val).then(function () {
        showToast('success', 'Skopiowano ID zamówienia');
      }).catch(function () {
        showToast('error', 'Nie udało się skopiować');
      });
      closeRowMenu();
      return;
    }

    if (action === 'copyOffer') {
      var v2 = ctx.item && ctx.item.offer_id ? ctx.item.offer_id : '';
      copyToClipboard(v2).then(function () {
        showToast('success', 'Skopiowano ID oferty');
      }).catch(function () {
        showToast('error', 'Nie udało się skopiować');
      });
      closeRowMenu();
      return;
    }

    closeRowMenu();
  }

  function apiCall(action, params) {
    params = params || {};
    if (!API_URL) {
      return Promise.reject(new Error('Brak apiUrl (ap_api_url).'));
    }

    var body = new URLSearchParams();
    body.set('action', action);
    body.set('eid', String(BRIDGE.eid || ''));
    body.set('ts', String(BRIDGE.ts || ''));
    body.set('ttl', String(BRIDGE.ttl || ''));
    body.set('sig', String(BRIDGE.sig || ''));

    Object.keys(params).forEach(function (k) {
      if (params[k] === undefined || params[k] === null) return;
      body.set(k, String(params[k]));
    });

    return fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: body.toString(),
      credentials: 'same-origin'
    })
      .then(function (r) {
        return r.text().then(function (t) {
          var json = null;
          try {
            json = JSON.parse(t);
          } catch (e) {
            var snippet = (t || '').toString().replace(/\s+/g, ' ').slice(0, 180);
            throw new Error('API zwróciło nieprawidłowy JSON (HTTP ' + r.status + '): ' + snippet);
          }
          return json;
        });
      })
      .then(function (json) {
        if (!json || json.ok !== true) {
          var err = (json && json.error && json.error.message) ? json.error.message : 'Błąd API';
          throw new Error(err);
        }
        return json;
      });
  }

  function setTheme() {
    var app = qs('#ap-app');
    if (!app) return;

    app.classList.toggle('ap-section-messages', state.section === 'messages');
    app.classList.toggle('ap-section-issues', state.section === 'issues');
  }

  function setHeader() {
    var titleEl = qs('[data-ap-header-title]');
    var subtitleEl = qs('[data-ap-header-subtitle]');

    var section = state.section;
    var filter = state.filterBySection[section];

    var sectionMeta = LABELS[section] || { title: section, filters: {} };
    var filterLabel = (sectionMeta.filters && sectionMeta.filters[filter]) ? sectionMeta.filters[filter] : filter;

    if (titleEl) titleEl.textContent = sectionMeta.title || section;
    if (subtitleEl) subtitleEl.textContent = 'Filtr: ' + filterLabel;
  }

  function setActiveSectionNav() {
    qsa('.ap-navitem[data-ap-section]').forEach(function (btn) {
      var section = btn.getAttribute('data-ap-section');
      var hasFilter = btn.hasAttribute('data-ap-filter');
      if (hasFilter) return;
      btn.classList.toggle('ap-navitem--active', section === state.section);
    });
  }

  function setActiveFilterButtons() {
    var section = state.section;
    var activeFilter = state.filterBySection[section];

    qsa('.ap-filter[data-ap-section][data-ap-filter]').forEach(function (btn) {
      var btnSection = btn.getAttribute('data-ap-section');
      var btnFilter = btn.getAttribute('data-ap-filter');
      btn.classList.toggle('ap-filter--active', btnSection === section && btnFilter === activeFilter);
    });
  }

  function toggleFilterGroups() {
    qsa('[data-ap-filter-group]').forEach(function (el) {
      var group = el.getAttribute('data-ap-filter-group');
      el.hidden = group !== state.section;
    });
  }

  function toggleViews() {
    qsa('.ap-view[data-ap-view]').forEach(function (v) {
      var isActive = v.getAttribute('data-ap-view') === state.section;
      v.classList.toggle('ap-view--active', isActive);
    });
  }

  function render() {
    setTheme();
    setActiveSectionNav();
    toggleFilterGroups();
    setActiveFilterButtons();
    toggleViews();
    setHeader();
    applyLayout();
  }


  function applyLayout() {
    var app = qs('#ap-app');
    if (!app) return;
    app.classList.toggle('ap-layout-single', viewMode === 'single');
    app.classList.toggle('ap-layout-split', viewMode !== 'single');

    var detailOpen = viewMode === 'single' && !!state.detailBySection[state.section];
    app.classList.toggle('ap-detail-open', detailOpen);
  }

  function openDetail(section) {
    if (viewMode !== 'single') return;
    state.detailBySection[section] = true;
    applyLayout();
  }

  function closeDetail(section) {
    state.detailBySection[section] = false;
    applyLayout();
  }

  function syncViewModeUi() {
    var opts = qsa('[data-ap-viewopt]');
    opts.forEach(function (btn) {
      var mode = btn.getAttribute('data-ap-viewopt');
      btn.classList.toggle('ap-viewopt--active', mode === viewMode);
      btn.setAttribute('aria-pressed', mode === viewMode ? 'true' : 'false');
    });
  }

  function setViewMode(mode) {
    if (mode !== 'split' && mode !== 'single') return;
    viewMode = mode;
    try {
      localStorage.setItem(VIEWMODE_KEY, mode);
    } catch (e) {}

    // Jeśli przełączasz na "single" i masz już otwarty wątek / zgłoszenie, pokaż go od razu.
    if (viewMode === 'single' && state.selected[state.section]) {
      state.detailBySection[state.section] = true;
    }
    if (viewMode !== 'single') {
      state.detailBySection[state.section] = false;
    }

    applyLayout();
    syncViewModeUi();
    rerenderCurrentList();
  }

  function rerenderCurrentList() {
    try {
      if (state.section === 'messages') {
        renderThreads(state.cacheBySection.messages || [], false);
      } else if (state.section === 'issues') {
        renderIssues(state.cacheBySection.issues || [], false);
      }
    } catch (e) {}
  }

  function getViewSettingsModal() {
    return qs('[data-ap-modal="viewSettings"]');
  }

  function openViewSettingsModal() {
    var modal = getViewSettingsModal();
    if (!modal) return;
    syncViewModeUi();
    modal.hidden = false;
  }

  function closeViewSettingsModal() {
    var modal = getViewSettingsModal();
    if (!modal) return;
    modal.hidden = true;
  }

  function getInitials(name) {
    if (!name) return 'U';
    var parts = String(name).trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'U';
    var first = parts[0];
    var last = parts.length > 1 ? parts[parts.length - 1] : '';
    var a = (first[0] || '').toUpperCase();
    var b = (last[0] || (first[1] || '')).toUpperCase();
    return (a + b) || 'U';
  }

  function syncUserInitials() {
    var nameEl = qs('[data-ap-user-name]');
    var initEl = qs('[data-ap-user-initials]');
    if (!nameEl || !initEl) return;
    initEl.textContent = getInitials(nameEl.textContent || '');
  }

  function fmtDate(dt) {
    if (!dt) return '';
    try {
      // Accept Date object OR string
      var d = (dt instanceof Date) ? dt : new Date(String(dt).replace(' ', 'T'));
      if (isNaN(d.getTime())) d = new Date(dt);
      if (isNaN(d.getTime())) return String(dt);
      return d.toLocaleString('pl-PL');
    } catch (e) {
      return String(dt);
    }
  }

    // Wersja „krótka” daty używana m.in. w listach. Przykład: "25.02 13:05"
  function fmtDateCompact(dt) {
    if (!dt) return '';
    try {
      var d = (dt instanceof Date) ? dt : new Date(dt);
      if (isNaN(d.getTime())) return String(dt);

      var dd = String(d.getDate()).padStart(2, '0');
      var mm = String(d.getMonth() + 1).padStart(2, '0');
      var hh = String(d.getHours()).padStart(2, '0');
      var mi = String(d.getMinutes()).padStart(2, '0');

      var now = new Date();
      // Jeśli ten sam rok, bez roku (bardziej kompaktowo)
      if (now.getFullYear() === d.getFullYear()) {
        return dd + '.' + mm + ' ' + hh + ':' + mi;
      }
      return dd + '.' + mm + '.' + d.getFullYear() + ' ' + hh + ':' + mi;
    } catch (e) {
      return String(dt);
    }
  }

  // Relatywny czas (np. "14 min temu", "3 h temu", "2 dni temu")
  function fmtRelativeTime(dt) {
    if (!dt) return '';
    try {
      var d = (dt instanceof Date) ? dt : new Date(dt);
      if (isNaN(d.getTime())) return fmtDateCompact(dt);

      var now = new Date();
      var diffMs = now.getTime() - d.getTime();
      if (diffMs < 0) diffMs = 0;

      var sec = Math.floor(diffMs / 1000);
      if (sec < 60) return sec + ' s temu';

      var min = Math.floor(sec / 60);
      if (min < 60) return min + ' min temu';

      var hrs = Math.floor(min / 60);
      if (hrs < 24) return hrs + ' h temu';

      var days = Math.floor(hrs / 24);
      if (days === 1) return '1 dzień temu';
      if (days < 7) return days + ' dni temu';

      return fmtDateCompact(d);
    } catch (e) {
      return fmtDateCompact(dt);
    }
  }

// === Message rendering helpers (ładniejsza treść, dekodowanie entity, linki) ===
  function decodeEntities(str) {
    try {
      var txt = document.createElement('textarea');
      txt.innerHTML = String(str || '');
      return txt.value;
    } catch (e) {
      return String(str || '');
    }
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function linkifyEscapedText(escapedText) {
    var re = /(https?:\/\/[^\s<]+|www\.[^\s<]+)/gi;
    return String(escapedText || '').replace(re, function (m) {
      var full = m;
      var tail = '';
      // usuń końcowe znaki interpunkcyjne z URL
      while (/[\)\]\}.,;:!?]$/.test(full)) {
        tail = full.slice(-1) + tail;
        full = full.slice(0, -1);
      }
      var href = full;
      if (/^www\./i.test(href)) {
        href = 'http://' + href;
      }
      // full jest już escapowany (nie zawiera < >), href też jest bezpieczny w atrybucie
      return '<a class="ap-link" href="' + href + '" target="_blank" rel="noopener noreferrer">' + full + '</a>' + tail;
    });
  }

  function formatMessageHtml(rawText) {
    // 1) decode entities (&oacute; etc.)
    // 2) escape HTML (XSS)
    // 3) linkify (na escaped)
    // 4) zostawiamy \n – CSS pre-wrap zadba o łamanie
    var s = decodeEntities(rawText);
    s = s.replace(/\r\n/g, '\n');
    s = escapeHtml(s);
    s = linkifyEscapedText(s);
    return s;
  }

  function setBadge(key, value) {
    var el = qs('[data-ap-badge="' + key + '"]');
    if (!el) return;
    if (value === null || value === undefined) {
      el.textContent = '—';
      return;
    }
    el.textContent = String(value);
  }

  function refreshStats() {
    return apiCall('dashboard.stats', {})
      .then(function (res) {
        var badges = (res.data && res.data.badges) ? res.data.badges : {};
        Object.keys(badges).forEach(function (k) {
          setBadge(k, badges[k]);
        });
        // W razie braku kluczy ustaw "—" na tych, które są w DOM
        qsa('[data-ap-badge]').forEach(function (el) {
          var k = el.getAttribute('data-ap-badge');
          if (!(k in badges) && el.textContent.trim() === '') {
            el.textContent = '—';
          }
        });
      })
      .catch(function (e) {
        showToast('danger', 'Stats: ' + e.message);
      });
  }

  function clearList(kind) {
    var list = qs('[data-ap-list="' + kind + '"]');
    var empty = qs('[data-ap-empty="' + kind + '"]');
    var note = qs('[data-ap-note="' + kind + '"]');

    if (note) {
      note.hidden = true;
      note.textContent = '';
    }

    if (list) {
      list.innerHTML = '';
    }

    if (empty) {
      empty.hidden = true;
    }

    var more = qs('[data-ap-more="' + kind + '"]');
    if (more) {
      more.hidden = true;
      more.textContent = '';
    }
  }

  function setListMore(kind, mode) {
    var el = qs('[data-ap-more="' + kind + '"]');
    if (!el) return;
    if (mode === 'loading') {
      el.hidden = false;
      el.textContent = 'Ładowanie kolejnych…';
      return;
    }
    el.hidden = true;
    el.textContent = '';
  }

  function setListNote(kind, text) {
    var note = qs('[data-ap-note="' + kind + '"]');
    if (!note) return;
    if (!text) {
      note.hidden = true;
      note.textContent = '';
      return;
    }
    note.textContent = text;
    note.hidden = false;
  }

  function setListTotal(kind, total) {
    var el = qs('[data-ap-list-total="' + kind + '"]');
    if (el) el.textContent = String(toInt(total, 0));
  }

  function renderThreads(items, append) {
    var list = qs('[data-ap-list="threads"]');
    if (!list) return;

    var isSingle = (viewMode === 'single');
    list.classList.toggle('ap-list--tickets', isSingle);

    if (!append) {
      list.innerHTML = '';

      if (!items || !items.length) {
        var empty = document.createElement('div');
        empty.className = 'ap-empty';
        empty.textContent = 'Brak wiadomości dla wybranego filtra.';
        list.appendChild(empty);
        return;
      }

      if (isSingle) {
        var head = document.createElement('div');
        head.className = 'ap-tickethead';
        head.innerHTML = '<div>Klient</div><div>Wiadomość</div><div>Sentyment</div><div>Status</div><div>Ostatnia</div><div></div>';
        list.appendChild(head);
      }
    }

    if (!items || !items.length) return;

    // Find last date group if appending
    var currentDay = '';
    if (append) {
      var dgs = qsa('[data-ap-list="threads"] .ap-dategroup');
      if (dgs.length) currentDay = dgs[dgs.length - 1].getAttribute('data-day') || '';
    }

    function fmtKey(d) {
      if (!(d instanceof Date)) d = new Date(d);
      var y = d.getFullYear();
      var m = String(d.getMonth() + 1).padStart(2, '0');
      var day = String(d.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + day;
    }

    function dayKeyToNice(key) {
      if (!key) return 'Brak daty';
      var today = new Date();
      var yesterday = new Date();
      yesterday.setDate(yesterday.getDate() - 1);
      var tKey = fmtKey(today);
      var yKey = fmtKey(yesterday);
      if (key === tKey) return 'DZISIAJ';
      if (key === yKey) return 'WCZORAJ';
      return key.split('-').reverse().join('.');
    }

    function acctMark(label) {
      var s = (label || '').trim();
      if (!s) return '?';
      var parts = s.split(/\s+/);
      if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
      return s.slice(0, 2).toUpperCase();
    }

    function acctTitle(label, id) {
      var s = (label || '').trim() || ('Konto #' + id);
      return s + ' (#' + id + ')';
    }

    function acctText(label, id) {
      var s = (label || '').toString().trim();
      return s ? s : ('Konto #' + (id || ''));
    }

    function acctClass(id, label) {
      // Hashujemy (id + label), żeby różne konta dostawały różne kolory nawet jeśli ID się „zgrywa” modulo.
      var key = (id == null ? '' : String(id)) + '|' + (label == null ? '' : String(label));
      var h = 0;
      for (var i = 0; i < key.length; i++) {
        h = ((h << 5) - h + key.charCodeAt(i)) | 0;
      }
      var idx = (Math.abs(h) % 8) + 1;
      return 'ap-acct--c' + idx;
    }

    function normSentiment(code) {
  var c = (code || '').toString().toLowerCase();
  if (c === 'positive' || c === 'pos' || c === '+') return 'pos';
  if (c === 'negative' || c === 'neg' || c === '-') return 'neg';
  return 'neu';
}

function sentimentSvg(code) {
  var c = normSentiment(code);
  // Inline SVG (uses currentColor) – small, consistent icons instead of emoji.
  if (c === 'pos') {
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8" fill="none"/><circle cx="9" cy="10" r="1.2" fill="currentColor"/><circle cx="15" cy="10" r="1.2" fill="currentColor"/><path d="M8 15c1.2 1.4 2.6 2 4 2s2.8-.6 4-2" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/></svg>';
  }
  if (c === 'neg') {
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8" fill="none"/><circle cx="9" cy="10" r="1.2" fill="currentColor"/><circle cx="15" cy="10" r="1.2" fill="currentColor"/><path d="M8 17c1.2-1.4 2.6-2 4-2s2.8.6 4 2" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/></svg>';
  }
  return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8" fill="none"/><circle cx="9" cy="10" r="1.2" fill="currentColor"/><circle cx="15" cy="10" r="1.2" fill="currentColor"/><path d="M8 15h8" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round"/></svg>';
}

function createSentimentBadge(code, isSmall) {
  var c = normSentiment(code);
  var el = document.createElement('span');
  el.className = 'ap-sentimentbadge ap-sentimentbadge--' + c + (isSmall ? ' ap-sentimentbadge--sm' : '');
  el.setAttribute('title', 'Sentyment: ' + sentimentLabel(c));
  el.innerHTML = sentimentSvg(c);
  return el;
}

function sentimentLabel(code) {
      if (code === 'neg') return 'Negatywny';
      if (code === 'pos') return 'Pozytywny';
      return 'Neutralny';
    }

    function addPill(wrap, text, cls) {
      var p = document.createElement('span');
      p.className = 'ap-pill ' + (cls || '');
      p.textContent = text;
      wrap.appendChild(p);
    }

    items.forEach(function (it) {
      var key = 'thread:' + it.id_allegropro_account + ':' + it.thread_id;
      var selected = (state.selected.messages === key);
      var lastAt = it.last_message_at ? new Date(it.last_message_at) : null;

      var dk = it.last_message_at ? String(it.last_message_at).slice(0, 10) : '';
      if (!dk) dk = 'unknown';
      if (dk !== currentDay) {
        currentDay = dk;
        var group = document.createElement('div');
        group.className = 'ap-dategroup';
        group.setAttribute('data-day', dk);
        group.textContent = dayKeyToNice(dk === 'unknown' ? '' : dk);
        list.appendChild(group);
      }

      if (isSingle) {
        var tr = document.createElement('div');
        tr.className = 'ap-ticketrow' + (selected ? ' ap-row--active' : '') + (String(it.read) === '0' ? ' ap-ticketrow--unread' : '');
        tr.setAttribute('data-ap-key', key);

        tr.addEventListener('click', function (e) {
          if (e && e.target && e.target.closest && e.target.closest('[data-ap-action="rowMenu"]')) return;

          state.selected.messages = key;
          qsa('[data-ap-list="threads"] [data-ap-key]').forEach(function (r) {
            r.classList.toggle('ap-row--active', r.getAttribute('data-ap-key') === key);
          });
          openDetail('messages');
          openThread(it.id_allegropro_account, it.thread_id);
        });

        // Who
        var who = document.createElement('div');
        who.className = 'ap-ticketrow__who';

        var whoTxt = document.createElement('div');
        whoTxt.className = 'ap-ticketrow__whoTxt';

        var whoName = document.createElement('div');
        whoName.className = 'ap-ticketrow__login';
        whoName.textContent = it.interlocutor_login || '(brak loginu)';

        var whoSub = document.createElement('div');
        whoSub.className = 'ap-ticketrow__sub';
        // Konto Allegro jako czytelny tag (bez podwójnych inicjałów BP/BI)
        var acctTag = document.createElement('span');
        acctTag.className = 'ap-row__acctname ' + acctClass(it.id_allegropro_account, it.account_label);
        acctTag.title = acctTitle(it.account_label, it.id_allegropro_account);
        acctTag.textContent = acctText(it.account_label, it.id_allegropro_account);
        whoSub.appendChild(acctTag);

        if (it.interlocutor_email) {
          var email = document.createElement('span');
          email.className = 'ap-ticketrow__email';
          email.textContent = it.interlocutor_email;
          whoSub.appendChild(email);
        }
        if (it.thread_id) whoSub.title = 'ID wątku: ' + it.thread_id;

        whoTxt.appendChild(whoName);
        whoTxt.appendChild(whoSub);

        who.appendChild(whoTxt);

        // Message
        var msg = document.createElement('div');
        msg.className = 'ap-ticketrow__msg';

        var title = document.createElement('div');
        title.className = 'ap-ticketrow__title';
        var titleTxt = 'Chat z ' + (it.interlocutor_login || '');
        if (it.checkout_form_id) titleTxt = 'Zamówienie: ' + it.checkout_form_id;
        else if (it.offer_id) titleTxt = 'Oferta: ' + it.offer_id;
        if (!titleTxt.trim()) titleTxt = it.thread_id ? ('Wątek: ' + String(it.thread_id).slice(0, 8) + '…') : 'Wątek';
        title.textContent = titleTxt;

        var snippet = document.createElement('div');
        snippet.className = 'ap-ticketrow__snippet';
        snippet.textContent = (it.snippet || it.last_message_snippet || '').trim();

        msg.appendChild(title);
        if (snippet.textContent) msg.appendChild(snippet);

        // Tagi / kontekst (pod tytułem jak w systemach ticketowych)
        var tags = document.createElement('div');
        tags.className = 'ap-ticketrow__tags';
        if (it.checkout_form_id) addPill(tags, 'Zamówienie', 'ap-pill--info');
        if (it.offer_id) addPill(tags, 'Oferta', 'ap-pill--info');
        if (String(it.has_attachments) === '1') addPill(tags, 'Załączniki', 'ap-pill--muted');
        if (tags.childNodes.length) msg.appendChild(tags);

        // Sentiment
        var sent = document.createElement('div');
        var sCode = it.sentiment || 'neu';
        sent.className = 'ap-ticketrow__sentiment';
        sent.appendChild(createSentimentBadge(sCode, true));
// Status
        var st = document.createElement('div');
        st.className = 'ap-ticketrow__status';

        if (String(it.need_reply) === '1') addPill(st, 'Do odpowiedzi', 'ap-pill--danger');
        if (String(it.read) === '0') addPill(st, 'Nieprzeczytane', 'ap-pill--warn');
        if (st.childNodes.length === 0) addPill(st, 'Odpowiedzieliśmy', 'ap-pill--muted');

        // Last
        var last = document.createElement('div');
        last.className = 'ap-ticketrow__last';

        var when = document.createElement('div');
        when.className = 'ap-ticketrow__when';
        when.textContent = fmtRelativeTime(it.last_message_at);
        if (lastAt) when.title = fmtDateCompact(lastAt);

        var bar = document.createElement('div');
        bar.className = 'ap-agebar';
        var fill = document.createElement('div');
        fill.className = 'ap-agebar__fill';
        var pct = 0;
        if (lastAt) {
          var hours = (Date.now() - lastAt.getTime()) / 3600000;
          var frac = Math.max(0, Math.min(1, 1 - (Math.min(hours, 24) / 24)));
          pct = Math.round(frac * 100);
        }
        fill.style.width = pct + '%';
        bar.appendChild(fill);

        last.appendChild(when);
        last.appendChild(bar);

        // Actions
        var actions = document.createElement('div');
        actions.className = 'ap-ticketrow__actions';

        var kebab = document.createElement('button');
        kebab.type = 'button';
        kebab.className = 'ap-kebab';
        kebab.textContent = '⋯';
        kebab.title = 'Szybkie akcje';
        kebab.setAttribute('data-ap-action', 'rowMenu');
        kebab.setAttribute('data-ap-key', key);

        actions.appendChild(kebab);

        tr.appendChild(who);
        tr.appendChild(msg);
        tr.appendChild(sent);
        tr.appendChild(st);
        tr.appendChild(last);
        tr.appendChild(actions);

        list.appendChild(tr);
      } else {
        // Split (column) view – klasyczny układ (bez „ticketów”).
        var row = document.createElement('div');
        row.className = 'ap-row' + (selected ? ' ap-row--active' : '') + (String(it.read) === '0' ? ' ap-row--unread' : '');
        row.setAttribute('data-ap-key', key);

        row.addEventListener('click', function () {
          state.selected.messages = key;
          qsa('[data-ap-list="threads"] [data-ap-key]').forEach(function (r) {
            r.classList.toggle('ap-row--active', r.getAttribute('data-ap-key') === key);
          });
          openDetail('messages');
          openThread(it.id_allegropro_account, it.thread_id);
        });

        var left = document.createElement('div');
        left.className = 'ap-row__left';

        var main = document.createElement('div');
        main.className = 'ap-row__main';

        var top = document.createElement('div');
        top.className = 'ap-row__top';

        var title2 = document.createElement('div');
        title2.className = 'ap-row__title';
        title2.textContent = it.interlocutor_login || '(brak loginu)';

        var pills = document.createElement('div');
        pills.className = 'ap-row__pills';

        // Konto Allegro jako kolorowy tag (zawsze, bez inicjałów)
        var acct = document.createElement('span');
        acct.className = 'ap-row__acctname ' + acctClass(it.id_allegropro_account, it.account_label);
        acct.textContent = acctText(it.account_label, it.id_allegropro_account);
        acct.title = acctTitle(it.account_label, it.id_allegropro_account);
        pills.appendChild(acct);

        if (it.segment) {
          var seg = document.createElement('span');
          seg.className = 'ap-pill ap-pill--info';
          seg.textContent = it.segment_label || it.segment;
          pills.appendChild(seg);
        }

        top.appendChild(title2);
        top.appendChild(pills);

        var meta = document.createElement('div');
        meta.className = 'ap-row__meta';
        var lastAt2 = it.last_message_at || it.updated_at || it.created_at;
        var lastAtTxt = lastAt2 ? fmtDateCompact(lastAt2) : '';
        meta.textContent = 'Ostatnia: ' + (lastAtTxt || '-');
        meta.title = it.thread_id ? ('ID wątku: ' + it.thread_id) : '';

        main.appendChild(top);
        main.appendChild(meta);

        var snTxt = (it.last_message_snippet || it.snippet || '').trim();
        if (snTxt) {
          var snEl = document.createElement('div');
          snEl.className = 'ap-row__snippet';
          snEl.textContent = snTxt;
          main.appendChild(snEl);
        }

        left.appendChild(main);

        var right = document.createElement('div');
        right.className = 'ap-row__right';

        // Sentyment (ocena klienta / heurystyka)
        right.appendChild(createSentimentBadge(it.sentiment || 'neu', true));

        if (String(it.need_reply) === '1') {
          var nr = document.createElement('span');
          nr.className = 'ap-pill ap-pill--danger';
          nr.textContent = 'Wymaga odpowiedzi';
          right.appendChild(nr);
        }

        var st2 = document.createElement('span');
        st2.className = 'ap-pill ' + (String(it.read) === '0' ? 'ap-pill--warn' : 'ap-pill--muted');
        st2.textContent = (String(it.read) === '0' ? 'Nieprzeczytane' : 'Przeczytane');
        right.appendChild(st2);

        if (it.checkout_form_id) {
          var p1 = document.createElement('span');
          p1.className = 'ap-pill ap-pill--info';
          p1.textContent = 'Zamówienie';
          right.appendChild(p1);
        } else if (it.offer_id) {
          var p2 = document.createElement('span');
          p2.className = 'ap-pill ap-pill--info';
          p2.textContent = 'Oferta';
          right.appendChild(p2);
        } else {
          var p3 = document.createElement('span');
          p3.className = 'ap-pill ap-pill--muted';
          p3.textContent = 'Ogólne';
          right.appendChild(p3);
        }

        row.appendChild(left);
        row.appendChild(right);
        list.appendChild(row);
      }
    });
  }



  function setThreadUiState(mode) {
    // mode: empty | loading | view
    var empty = qs('[data-ap-preview-empty="messages"]');
    var loading = qs('[data-ap-preview-loading="messages"]');
    var view = qs('[data-ap-thread-view]');

    if (empty) empty.hidden = (mode !== 'empty');
    if (loading) loading.hidden = (mode !== 'loading');
    if (view) view.hidden = (mode !== 'view');
  }

  function renderThread(thread, messages) {
    var titleEl = qs('[data-ap-thread-title]');
    var metaEl = qs('[data-ap-thread-meta]');
    var chatEl = qs('[data-ap-chat="messages"]');
    var markBtn = qs('[data-ap-action="threadMarkRead"]');

    var login = (thread && thread.interlocutor_login) ? String(thread.interlocutor_login) : '';
    var threadId = (thread && thread.thread_id) ? String(thread.thread_id) : (state.open.thread ? state.open.thread.threadId : '');
    var lastAt = thread ? thread.last_message_at : null;
    var cfid = thread ? thread.checkout_form_id : null;
    var offerId = thread ? thread.offer_id : null;
    var isRead = thread ? (String(thread.read) === '1') : true;

    if (titleEl) titleEl.textContent = login ? login : ('Wiadomość ' + threadId);

    var metaParts = [];
    if (threadId) metaParts.push('ID: ' + threadId);
    if (lastAt) metaParts.push('Ostatnia: ' + fmtDate(lastAt));
    if (cfid) metaParts.push('Zamówienie: ' + cfid);
    if (offerId) metaParts.push('Oferta: ' + offerId);
    metaParts.push(isRead ? 'Przeczytane' : 'Nieprzeczytane');
    if (metaEl) metaEl.textContent = metaParts.join(' • ');

    if (markBtn) {
      markBtn.disabled = isRead;
    }

    if (chatEl) {
      chatEl.innerHTML = '';

      if (!messages || !messages.length) {
        var empty = document.createElement('div');
        empty.className = 'ap-empty';
        empty.textContent = 'Brak wiadomości w wątku (albo nie zostały jeszcze zsynchronizowane).';
        chatEl.appendChild(empty);
      } else {
        messages.forEach(function (m) {
          var isThem = String(m.author_is_interlocutor) === '1';
          var bubble = document.createElement('div');
          bubble.className = 'ap-bubble ' + (isThem ? 'ap-bubble--them' : 'ap-bubble--me');

          var meta = document.createElement('div');
          meta.className = 'ap-bubble__meta';

          var who = document.createElement('span');
          who.textContent = (m.author_login ? String(m.author_login) : (isThem ? 'Klient' : 'My'));

          var when = document.createElement('span');
          when.textContent = m.created_at_allegro ? fmtDateCompact(m.created_at_allegro) : '';

          meta.appendChild(who);
          meta.appendChild(when);

          var text = document.createElement('div');
          text.className = 'ap-bubble__text';
          text.innerHTML = formatMessageHtml(m.text || '');

          bubble.appendChild(meta);
          bubble.appendChild(text);

          if (String(m.has_attachments) === '1') {
            var att = document.createElement('div');
            att.className = 'ap-bubble__att';
            att.textContent = 'Załączniki: tak (obsłużymy w kolejnym etapie)';
            bubble.appendChild(att);
          }

          chatEl.appendChild(bubble);
        });

        // scroll na dół
        try {
          chatEl.scrollTop = chatEl.scrollHeight;
        } catch (e) {}
      }
    }
  }

  function openThread(accountId, threadId, login) {
    if (!accountId || !threadId) {
      return;
    }

    setThreadUiState('loading');

    return apiCall('thread.open', {
      account_id: accountId,
      thread_id: threadId
    })
      .then(function (res) {
        var data = res.data || {};
        var thread = data.thread || { thread_id: threadId, interlocutor_login: login || '' };
        var messages = data.messages || [];

        setThreadUiState('view');
        renderThread(thread, messages);

        // Auto mark-as-read po otwarciu (tylko jeśli był nieprzeczytany)
        if (thread && String(thread.read) === '0') {
          apiCall('thread.read', { account_id: accountId, thread_id: threadId })
            .then(function () {
              // odśwież badge/listę
              refreshStats();
              refreshList();
              // zablokuj przycisk
              var markBtn = qs('[data-ap-action="threadMarkRead"]');
              if (markBtn) markBtn.disabled = true;
            })
            .catch(function () {
              // nie blokujemy UI
            });
        }
      })
      .catch(function (e) {
        setThreadUiState('empty');
        showToast('danger', 'Wiadomość: ' + e.message);
      });
  }

  function renderIssues(items, append) {
    var list = qs('[data-ap-list="issues"]');
    var empty = qs('[data-ap-empty="issues"]');

    if (!list) return;

    // Helper: key dnia z "YYYY-MM-DD HH:MM:SS"
    function dayKey(mysqlDt) {
      if (!mysqlDt) return '';
      var s = mysqlDt.toString();
      if (s.length >= 10) return s.slice(0, 10);
      return s;
    }

    function fmtKey(d) {
      var y = d.getFullYear();
      var m = String(d.getMonth() + 1).padStart(2, '0');
      var da = String(d.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + da;
    }

    function dayLabel(key) {
      if (!key) return 'Brak daty';
      try {
        var parts = key.split('-');
        if (parts.length !== 3) return key;
        var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        if (isNaN(d.getTime())) return key;

        var now = new Date();
        var todayKey = fmtKey(now);

        var y = new Date(now.getTime());
        y.setDate(now.getDate() - 1);
        var yKey = fmtKey(y);

        if (key === todayKey) return 'Dzisiaj';
        if (key === yKey) return 'Wczoraj';

        return d.toLocaleDateString('pl-PL', { weekday: 'long', year: 'numeric', month: '2-digit', day: '2-digit' });
      } catch (e) {
        return key;
      }
    }

    function acctMark(label, accountId) {
      var t = (label || '').toString().trim();
      if (!t) return ('#' + accountId);

      var cleaned = t.replace(/[^0-9a-zA-Z]/g, '');
      if (!cleaned) cleaned = t;
      var m = cleaned.slice(0, 2);
      if (!m) return ('#' + accountId);
      return m.toUpperCase();
    }

    function acctTitle(label, accountId) {
      var t = (label || '').toString().trim();
      return t ? (t + ' (#' + accountId + ')') : ('Konto #' + accountId);
    }

    function acctClass(accountId, label) {
      // Hashujemy (id + label), żeby różne konta miały różne kolory.
      var key = (accountId == null ? '' : String(accountId)) + '|' + (label == null ? '' : String(label));
      var h = 0;
      for (var i = 0; i < key.length; i++) {
        h = ((h << 5) - h + key.charCodeAt(i)) | 0;
      }
      var idx = (Math.abs(h) % 8) + 1;
      return 'ap-acct--c' + idx;
    }

    function duePillClass(dueMysql) {
      if (!dueMysql) return 'ap-pill--muted';
      try {
        // parse "YYYY-MM-DD HH:MM:SS"
        var dt = dueMysql.toString().replace(' ', 'T');
        var dueMs = Date.parse(dt);
        if (isNaN(dueMs)) return 'ap-pill--danger';
        var now = Date.now();
        var diff = dueMs - now;
        if (diff <= 0) return 'ap-pill--danger';
        if (diff <= 48 * 3600 * 1000) return 'ap-pill--danger';
        if (diff <= 7 * 24 * 3600 * 1000) return 'ap-pill--warn';
        return 'ap-pill--muted';
      } catch (e) {
        return 'ap-pill--danger';
      }
    }

    // === Tłumaczenia (PL) dla issue ===
    function issueTypePl(type) {
      type = (type || '').toString();
      if (type === 'DISPUTE') return 'Dyskusja';
      if (type === 'CLAIM') return 'Reklamacja';
      return type;
    }

    function issueStatusPl(type, status) {
      status = (status || '').toString();

      // Dyskusje (DISPUTE)
      if (status === 'DISPUTE_ONGOING' || status === 'ONGOING') return 'W trakcie';
      if (status === 'DISPUTE_UNRESOLVED' || status === 'UNRESOLVED') return 'Nierozwiązana';
      if (status === 'DISPUTE_CLOSED' || status === 'CLOSED') return 'Zamknięta';

      // Reklamacje (CLAIM)
      if (status === 'CLAIM_SUBMITTED' || status === 'SUBMITTED') return 'Złożona';
      if (status === 'CLAIM_ACCEPTED' || status === 'ACCEPTED') return 'Zaakceptowana';
      if (status === 'CLAIM_REJECTED' || status === 'REJECTED') return 'Odrzucona';

      // fallback – jeśli Allegro doda nowe statusy / prefiksy
      if (status && type && status.indexOf(type + '_') === 0) {
        return status.replace(type + '_', '').replace(/_/g, ' ').toLowerCase();
      }
      return status;
    }

    function lastMsgStatusPl(s) {
      s = (s || '').toString();
      if (s === 'NEW') return 'Nowa wiadomość';
      if (s === 'BUYER_REPLIED') return 'Kupujący odpisał';
      if (s === 'SELLER_REPLIED') return 'Odpowiedzieliśmy';
      if (s === 'ALLEGRO_ADVISOR_REPLIED') return 'Doradca Allegro odpisał';
      return s ? s.replace(/_/g, ' ').toLowerCase() : '';
    }

    function lastMsgPillClass(s) {
      s = (s || '').toString();
      if (s === 'NEW') return 'ap-pill--danger';
      if (s === 'BUYER_REPLIED') return 'ap-pill--warn';
      if (s === 'ALLEGRO_ADVISOR_REPLIED') return 'ap-pill--info';
      if (s === 'SELLER_REPLIED') return 'ap-pill--muted';
      return 'ap-pill--muted';
    }

    function fmtDateCompact(dt) {
      if (!dt) return '';
      try {
        var d = new Date(String(dt).replace(' ', 'T'));
        if (isNaN(d.getTime())) return String(dt);
        return d.toLocaleString('pl-PL', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit'
        });
      } catch (e) {
        return String(dt);
      }
    }

    function fmtDueCompact(dueMysql) {
      if (!dueMysql) return '';
      try {
        var d = new Date(String(dueMysql).replace(' ', 'T'));
        if (isNaN(d.getTime())) return fmtDateCompact(dueMysql);

        var now = new Date();
        var todayKey = fmtKey(now);
        var key = fmtKey(d);

        var t = d.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });

        // jutro?
        var tomorrow = new Date(now.getTime());
        tomorrow.setDate(now.getDate() + 1);
        var tomKey = fmtKey(tomorrow);

        if (key === todayKey) return 'dzisiaj ' + t;
        if (key === tomKey) return 'jutro ' + t;

        // dd.mm HH:MM (bez roku, bo jest w listach + nagłówkach dnia)
        var dm = d.toLocaleDateString('pl-PL', { day: '2-digit', month: '2-digit' });
        return dm + ' ' + t;
      } catch (e) {
        return fmtDateCompact(dueMysql);
      }
    }


// === Step 17: lżejszy wygląd listy zgłoszeń (ikonki + tekst zamiast ciężkich "tabletek") ===
var SVG_CLOCK = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2"/></svg>';
var SVG_CHAT  = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 8h10M7 12h7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H10l-6 4v-4H7a3 3 0 0 1-3-3V6z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>';

function mkMiniIcon(svg) {
  var span = document.createElement('span');
  span.className = 'ap-miniico';
  span.setAttribute('aria-hidden', 'true');
  span.innerHTML = svg;
  return span;
}

function msgTone(s) {
  s = (s || '').toString();
  if (s === 'NEW') return 'danger';
  if (s === 'BUYER_REPLIED') return 'warn';
  if (s === 'ALLEGRO_ADVISOR_REPLIED') return 'info';
  return 'muted';
}

function dueTone(dueMysql) {
  if (!dueMysql) return 'muted';
  try {
    var dt = dueMysql.toString().replace(' ', 'T');
    var dueMs = Date.parse(dt);
    if (isNaN(dueMs)) return 'danger';
    var now = Date.now();
    var diff = dueMs - now;
    if (diff <= 0) return 'danger';
    if (diff <= 48 * 3600 * 1000) return 'danger';
    if (diff <= 7 * 24 * 3600 * 1000) return 'warn';
    return 'muted';
  } catch (e) {
    return 'danger';
  }
}

    if (!append) {
      list.innerHTML = '';
    }

    if (!items || !items.length) {
      if (!append) {
        if (empty) empty.hidden = false;
      }
      return;
    }

    // Ustalamy "ostatni dzień" już wyrenderowany w liście (żeby nie dublować nagłówków przy infinite scroll)
    var currentDay = null;
    try {
      var lastHeader = list.querySelector('.ap-dategroup:last-of-type');
      if (lastHeader && lastHeader.getAttribute) {
        currentDay = lastHeader.getAttribute('data-ap-day') || null;
      }
    } catch (e) {}

    items.forEach(function (it) {
      var accountId = toInt(it.id_allegropro_account, 0);
      var accountLabel = (it.account_label || '').toString();
      var issueId = (it.issue_id || '').toString();
      var buyer = (it.buyer_login || '').toString();
      var type = (it.type || '').toString();
      var status = (it.status || '').toString();
      var lastStatus = (it.last_message_status || '').toString();
      var lastAt = it.activity_at || it.last_message_at || it.updated_at_allegro || it.created_at_allegro;
      var due = it.status_due_date || it.decision_due_date;

      var key = String(accountId) + ':' + issueId;

      // Separator dnia
      var dKey = dayKey(lastAt);
      if (dKey !== currentDay) {
        var h = document.createElement('div');
        h.className = 'ap-dategroup';
        h.setAttribute('data-ap-day', dKey);
        h.textContent = dayLabel(dKey);
        list.appendChild(h);
        currentDay = dKey;
      }

      var row = document.createElement('div');
      row.className = 'ap-row' + (state.selected.issues === key ? ' ap-row--active' : '');
      row.setAttribute('data-ap-issue', issueId);
      row.setAttribute('data-ap-account', String(accountId));
      row.setAttribute('data-ap-key', key);

      var left = document.createElement('div');
      left.className = 'ap-row__left';

      var title = document.createElement('div');
      title.className = 'ap-row__title';

      var acct = document.createElement('span');
      acct.className = 'ap-acct ' + acctClass(accountId, accountLabel);
      acct.title = acctTitle(accountLabel, accountId);
      acct.textContent = acctMark(accountLabel, accountId);

      var titleText = document.createElement('span');
      titleText.className = 'ap-row__titletext';
      titleText.textContent = buyer ? buyer : ('Zgłoszenie ' + issueId);

      var acctName = document.createElement('span');
      // Konto jako tag (bez inicjałów BP/BI)
      acctName.className = 'ap-row__acctname ' + acctClass(accountId, accountLabel);
      acctName.title = acctTitle(accountLabel, accountId);
      acctName.textContent = accountLabel ? accountLabel : ('Konto #' + accountId);

      title.appendChild(titleText);
      title.appendChild(acctName);

      var sub = document.createElement('div');
      sub.className = 'ap-row__sub';

      // Czytelniejsza linia meta (bez długiego "Ostatnia:" i bez angielskich etykiet)
var timeTxt = '';
if (lastAt) {
  var sdt = String(lastAt);
  // MySQL: "YYYY-MM-DD HH:MM:SS" -> bierzemy HH:MM
  if (sdt.length >= 16) timeTxt = sdt.slice(11, 16);
  else timeTxt = fmtDateCompact(lastAt);
}

var idShort = issueId;
if (idShort && idShort.length > 14) {
  idShort = idShort.slice(0, 4) + '…' + idShort.slice(-4);
}

var parts = [];
if (timeTxt) parts.push(timeTxt);
if (type) parts.push(issueTypePl(type));
if (status) parts.push(issueStatusPl(type, status));
if (issueId) parts.push('ID: ' + idShort);
sub.textContent = parts.join(' • ');

// Pełny opis w tooltipie
try {
  sub.title = (lastAt ? ('Ostatnia: ' + fmtDateCompact(lastAt) + ' • ') : '') +
    (type ? (issueTypePl(type) + ' • ') : '') +
    (status ? ('Status: ' + issueStatusPl(type, status) + ' • ') : '') +
    (issueId ? ('ID: ' + issueId) : '');
} catch (e) {}

      left.appendChild(title);
      left.appendChild(sub);

      var right = document.createElement('div');
right.className = 'ap-row__right';

// Subtelnie: status ostatniej wiadomości (bez ciężkiej tabletki)
var lastLabel = lastMsgStatusPl(lastStatus);
if (lastLabel) {
  var metaLine = document.createElement('div');
  metaLine.className = 'ap-issmeta ap-issmeta--' + msgTone(lastStatus);
  metaLine.appendChild(mkMiniIcon(SVG_CHAT));

  var metaText = document.createElement('span');
  metaText.textContent = lastLabel;
  metaLine.appendChild(metaText);

  right.appendChild(metaLine);
}

// Termin: tekst + ikonka zegara (bez ciężkiej tabletki).
if (due) {
  var dueLine = document.createElement('div');
  dueLine.className = 'ap-issdue ap-issdue--' + dueTone(due);
  dueLine.appendChild(mkMiniIcon(SVG_CLOCK));

  var dueText = document.createElement('span');
  dueText.textContent = 'Termin: ' + fmtDueCompact(due);
  dueLine.appendChild(dueText);

  right.appendChild(dueLine);
}

      row.appendChild(left);
      row.appendChild(right);

      row.addEventListener('click', function () {
        state.selected.issues = key;
        state.open.issue = { accountId: accountId, issueId: issueId, buyer: buyer };
        openDetail('issues');

        // highlight
        qsa('[data-ap-list="issues"] [data-ap-key]').forEach(function (r) {
          r.classList.toggle('ap-row--active', r.getAttribute('data-ap-key') === key);
        });

        openIssue(accountId, issueId, buyer);
      });

      list.appendChild(row);
    });
  }


function setIssueUiState(mode) {
    // mode: empty | loading | view
    var empty = qs('[data-ap-preview-empty="issues"]');
    var loading = qs('[data-ap-preview-loading="issues"]');
    var view = qs('[data-ap-issue-view]');

    if (empty) empty.hidden = (mode !== 'empty');
    if (loading) loading.hidden = (mode !== 'loading');
    if (view) view.hidden = (mode !== 'view');
  }

  function renderIssue(issue, chat) {
    var titleEl = qs('[data-ap-issue-title]');
    var metaEl = qs('[data-ap-issue-meta]');
    var chatEl = qs('[data-ap-chat="issues"]');

    var issueId = (issue && issue.issue_id) ? String(issue.issue_id) : (state.open.issue ? state.open.issue.issueId : '');
    var buyer = (issue && issue.buyer_login) ? String(issue.buyer_login) : (state.open.issue ? (state.open.issue.buyer || '') : '');
    var type = issue ? (issue.type || '') : '';
    var status = issue ? (issue.status || '') : '';
    var orderId = issue ? (issue.checkout_form_id || '') : '';
    var due = issue ? (issue.status_due_date || issue.decision_due_date || '') : '';
    var right = issue ? (issue.right_type || '') : '';

    function issueTypePl(type) {
      type = (type || '').toString();
      if (type === 'DISPUTE') return 'Dyskusja';
      if (type === 'CLAIM') return 'Reklamacja';
      return type;
    }

    function issueStatusPl(type, status) {
      status = (status || '').toString();

      // Dyskusje (DISPUTE)
      if (status === 'DISPUTE_ONGOING' || status === 'ONGOING') return 'W trakcie';
      if (status === 'DISPUTE_UNRESOLVED' || status === 'UNRESOLVED') return 'Nierozwiązana';
      if (status === 'DISPUTE_CLOSED' || status === 'CLOSED') return 'Zamknięta';

      // Reklamacje (CLAIM)
      if (status === 'CLAIM_SUBMITTED' || status === 'SUBMITTED') return 'Złożona';
      if (status === 'CLAIM_ACCEPTED' || status === 'ACCEPTED') return 'Zaakceptowana';
      if (status === 'CLAIM_REJECTED' || status === 'REJECTED') return 'Odrzucona';

      // fallback – jeśli Allegro doda nowe statusy / prefiksy
      if (status && type && status.indexOf(type + '_') === 0) {
        return status.replace(type + '_', '').replace(/_/g, ' ').toLowerCase();
      }
      return status;
    }

    function rolePl(role) {
      role = (role || '').toString();
      if (role === 'BUYER' || role === 'CUSTOMER') return 'Kupujący';
      if (role === 'SELLER') return 'My';
      if (role === 'ALLEGRO_ADVISOR') return 'Doradca Allegro';
      if (role === 'ALLEGRO') return 'Allegro';
      return role;
    }

    function fmtDateCompact(dt) {
      if (!dt) return '';
      try {
        var d = new Date(String(dt).replace(' ', 'T'));
        if (isNaN(d.getTime())) return String(dt);
        return d.toLocaleString('pl-PL', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit'
        });
      } catch (e) {
        return String(dt);
      }
    }

    if (titleEl) titleEl.textContent = buyer ? buyer : ('Zgłoszenie ' + issueId);

    var metaParts = [];
    if (issueId) metaParts.push('ID: ' + issueId);
    if (type) metaParts.push('Typ: ' + issueTypePl(type));
    if (status) metaParts.push('Status: ' + issueStatusPl(type, status));
    if (orderId) metaParts.push('Zamówienie: ' + orderId);
    if (right) metaParts.push('Podstawa: ' + right);
    if (due) metaParts.push('Termin: ' + fmtDateCompact(due));
    if (metaEl) metaEl.textContent = metaParts.join(' • ');

    if (chatEl) {
      chatEl.innerHTML = '';

      if (!chat || !chat.length) {
        var empty = document.createElement('div');
        empty.className = 'ap-empty';
        empty.textContent = 'Brak wiadomości w zgłoszeniu (albo nie zostały jeszcze zsynchronizowane).';
        chatEl.appendChild(empty);
      } else {
        chat.forEach(function (m) {
          var role = (m.author_role || '').toString();
          var login = (m.author_login || '').toString();

          var isBuyer = (role === 'BUYER' || role === 'CUSTOMER');
          var isAdvisor = (role === 'ALLEGRO_ADVISOR');

          var bubble = document.createElement('div');
          bubble.className = 'ap-bubble ' + (isAdvisor ? 'ap-bubble--advisor' : (isBuyer ? 'ap-bubble--them' : 'ap-bubble--me'));

          var meta = document.createElement('div');
          meta.className = 'ap-bubble__meta';

          var who = document.createElement('span');
          if (login) {
            who.textContent = login + (role ? (' (' + rolePl(role) + ')') : '');
          } else {
            who.textContent = role ? rolePl(role) : (isBuyer ? 'Kupujący' : 'My');
          }

          var when = document.createElement('span');
          when.textContent = m.created_at_allegro ? fmtDate(m.created_at_allegro) : '';

          meta.appendChild(who);
          meta.appendChild(when);

          var text = document.createElement('div');
          text.className = 'ap-bubble__text';
          text.innerHTML = formatMessageHtml(m.text || '');

          bubble.appendChild(meta);
          bubble.appendChild(text);

          if (String(m.has_attachments) === '1') {
            var att = document.createElement('div');
            att.className = 'ap-bubble__att';
            att.textContent = 'Załączniki: tak (dopniemy pobieranie/preview w kolejnym etapie)';
            bubble.appendChild(att);
          }

          chatEl.appendChild(bubble);
        });

        try {
          chatEl.scrollTop = chatEl.scrollHeight;
        } catch (e) {}
      }
    }
  }

  function openIssue(accountId, issueId, buyer) {
    if (!accountId || !issueId) {
      return;
    }

    setIssueUiState('loading');

    return apiCall('issue.open', {
      account_id: accountId,
      issue_id: issueId
    })
      .then(function (res) {
        var data = res.data || {};
        var issue = data.issue || { issue_id: issueId, buyer_login: buyer || '' };
        var chat = data.chat || [];

        setIssueUiState('view');
        renderIssue(issue, chat);
      })
      .catch(function (e) {
        setIssueUiState('empty');
        showToast('danger', 'Zgłoszenie: ' + e.message);
      });
  }

  function refreshList(reset) {
    if (reset === undefined) reset = true;

    var section = state.section;
    var filter = state.filterBySection[section];

    function fetch(kind, action, renderer) {
      var paging = state.paging[kind];
      if (!paging) return Promise.resolve();
      if (paging.loading) return Promise.resolve();

      var offset = reset ? 0 : paging.offset;
      var limit = paging.limit;

      if (reset) {
        paging.offset = 0;
        paging.total = 0;
        paging.hasMore = false;
        clearList(kind);

        var listEl = qs('[data-ap-list="' + kind + '"]');
        if (listEl) listEl.scrollTop = 0;
      }

      paging.loading = true;
      setListMore(kind, 'loading');

      return apiCall(action, {
        filter: filter,
        q: state.search,
        limit: limit,
        offset: offset
      })
        .then(function (res) {
          var data = res.data || {};
          var meta = res.meta || {};
          var items = data.items || [];

          // Cache for instant view-mode re-render (switching split/full without refetch)
          if (kind === 'threads') {
            state.cacheBySection.messages = reset ? items.slice() : (state.cacheBySection.messages || []).concat(items);
          } else if (kind === 'issues') {
            state.cacheBySection.issues = reset ? items.slice() : (state.cacheBySection.issues || []).concat(items);
          }


          paging.total = toInt(data.total, 0);
          paging.offset = offset + items.length;
          paging.hasMore = paging.offset < paging.total;

          setListTotal(kind, paging.total);
          setListNote(kind, meta.note || '');
          renderer(items, !reset);
        })
        .catch(function (e) {
          showToast('danger', (kind === 'threads' ? 'Lista wiadomości' : 'Lista zgłoszeń') + ': ' + e.message);
        })
        .finally(function () {
          paging.loading = false;
          setListMore(kind, '');
        });
    }

    if (section === 'messages') {
      return fetch('threads', 'threads.list', renderThreads);
    }
    if (section === 'issues') {
      return fetch('issues', 'issues.list', renderIssues);
    }
  }

  function setSection(section) {
    if (!section) return;
    if (state.section === section) return;
    state.section = section;

    if (!state.filterBySection[section]) {
      state.filterBySection[section] = section === 'issues' ? 'iss_dispute_waiting' : 'msg_all';
    }

    render();
    refreshList(true);
  }

  function setFilter(section, filter) {
    if (!section || !filter) return;
    state.section = section;
    state.filterBySection[section] = filter;

    render();
    refreshList(true);
  }

  function syncCurrent() {
    var btn = qs('[data-ap-action="sync"]');
    if (btn) btn.classList.add('is-loading');

    var action = (state.section === 'issues') ? 'issues.sync' : 'threads.sync';

    return apiCall(action, {})
      .then(function (res) {
        var d = res.data || {};
        var fetched = toInt(d.fetched, 0);
        var upserted = toInt(d.upserted, 0);
        showToast('success', 'Synchronizacja OK: pobrano ' + fetched + ', zapisano ' + upserted + '.');

        if (d.errors && d.errors.length) {
          showToast('warning', 'Uwaga: część kont zwróciła błąd (sprawdź tokeny).');
          console.warn('sync errors', d.errors);
        }
      })
      .catch(function (e) {
        showToast('danger', 'Synchronizacja: ' + e.message);
      })
      .finally(function () {
        if (btn) btn.classList.remove('is-loading');
        refreshStats();
        refreshList();
      });
  }




  // === Segregacja (pola pochodne) dla już pobranych wątków ===
  // Używane do przygotowania filtrów od razu po synchronizacji listy (bez klikania w wątki).
  var _enrichRunning = false;

  function enrichThreads(batches, opts) {
    opts = opts || {};
    var force = !!opts.force;
    var limitParam = opts.limit ? toInt(opts.limit, 0) : 0;

    batches = toInt(batches, 1);
    if (batches < 1) batches = 1;
    if (batches > 20) batches = 20;

    if (_enrichRunning) {
      return Promise.resolve();
    }
    _enrichRunning = true;

    var btn = qs('[data-ap-action="enrich"]');
    if (btn) btn.classList.add('is-loading');

    function one(rem) {
      var params = {};
      if (force) params.force = 1;
      if (limitParam > 0) params.limit = limitParam;

      return apiCall('threads.enrich', params)
        .then(function (res) {
          var d = (res && res.data) ? res.data : {};
          var processed = toInt(d.processed_threads, 0);
          var pending = toInt(d.pending, 0);

          showToast('info', 'Segregacja: przetworzono ' + processed + ' • pozostało ' + pending);

          // Odświeżamy badge + listę, żeby było widać efekt segregacji
          refreshStats();
          refreshList();

          if (pending > 0 && rem > 1) {
            return one(rem - 1);
          }
        });
    }

    return one(batches)
      .catch(function (e) {
        showToast('danger', 'Segregacja: ' + e.message);
      })
      .finally(function () {
        _enrichRunning = false;
        if (btn) btn.classList.remove('is-loading');
      });
  }

  // === Pełna re-segregacja (reset + progress bar) ===
  var _resegRunning = false;
  var _resegCancel = false;
  var _resegModal = null;

  function ensureResegModal() {
    if (_resegModal && _resegModal.root) return _resegModal;

    // CSS (wstrzykujemy raz, żeby nie ruszać plików CSS)
    if (!document.getElementById('ap-reseg-style')) {
      var st = document.createElement('style');
      st.id = 'ap-reseg-style';
      st.textContent =
        '.ap-modal{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:18px}' +
        '.ap-modal[hidden]{display:none!important}' +
        '.ap-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.35);backdrop-filter:blur(2px)}' +
        '.ap-modal__dialog{position:relative;z-index:1;width:min(640px,95vw);background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden}' +
        '.ap-modal__head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #eee}' +
        '.ap-modal__title{font-weight:800;font-size:16px}' +
        '.ap-modal__close{background:none;border:0;cursor:pointer;font-size:22px;line-height:1;color:#666}' +
        '.ap-modal__body{padding:14px 16px}' +
        '.ap-modal__meta{display:flex;gap:12px;justify-content:space-between;flex-wrap:wrap;margin-top:10px;font-size:13px;color:#555}' +
        '.ap-progress{height:10px;background:#f1f3f5;border-radius:999px;overflow:hidden}' +
        '.ap-progress__bar{height:100%;width:0%;background:linear-gradient(90deg,#ff5a00,#ff8a3d);border-radius:999px;transition:width .2s}' +
        '.ap-modal__small{font-size:12px;color:#777;margin-top:8px}' +
        '.ap-modal__foot{display:flex;gap:10px;justify-content:flex-end;padding:12px 16px;border-top:1px solid #eee;background:#fafafa}' +
        '.ap-modal__log{margin-top:10px;max-height:160px;overflow:auto;font-size:12px;background:#fcfcfc;border:1px solid #eee;border-radius:12px;padding:10px;color:#333}';
      document.head.appendChild(st);
    }

    var root = document.createElement('div');
    root.className = 'ap-modal';
    root.hidden = true;
    root.innerHTML =
      '<div class="ap-modal__backdrop" data-ap-reseg-hide></div>' +
      '<div class="ap-modal__dialog" role="dialog" aria-modal="true" aria-label="Segregacja wątków">' +
        '<div class="ap-modal__head">' +
          '<div class="ap-modal__title">Segregacja: przelicz wszystkie wątki</div>' +
          '<button type="button" class="ap-modal__close" title="Ukryj" data-ap-reseg-hide>&times;</button>' +
        '</div>' +
        '<div class="ap-modal__body">' +
          '<div class="ap-progress"><div class="ap-progress__bar" data-ap-reseg-bar></div></div>' +
          '<div class="ap-modal__meta">' +
            '<div><strong data-ap-reseg-pct>0%</strong> • <span data-ap-reseg-count>0 / 0</span></div>' +
            '<div>Pozostało: <strong data-ap-reseg-left>0</strong></div>' +
            '<div>Zakres: <strong data-ap-reseg-months>—</strong></div>' +
          '</div>' +
          '<div class="ap-modal__small" data-ap-reseg-status>Przygotowanie…</div>' +
          '<div class="ap-modal__log" data-ap-reseg-log hidden></div>' +
        '</div>' +
        '<div class="ap-modal__foot">' +
          '<button type="button" class="ap-btn ap-btn--ghost" data-ap-reseg-stop>Zatrzymaj</button>' +
          '<button type="button" class="ap-btn" data-ap-reseg-hide>Ukryj</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(root);

    var modal = {
      root: root,
      bar: root.querySelector('[data-ap-reseg-bar]'),
      pct: root.querySelector('[data-ap-reseg-pct]'),
      count: root.querySelector('[data-ap-reseg-count]'),
      left: root.querySelector('[data-ap-reseg-left]'),
      months: root.querySelector('[data-ap-reseg-months]'),
      status: root.querySelector('[data-ap-reseg-status]'),
      log: root.querySelector('[data-ap-reseg-log]'),
      stopBtn: root.querySelector('[data-ap-reseg-stop]')
    };

    qsa('[data-ap-reseg-hide]').forEach(function (el) {
      el.addEventListener('click', function () {
        // Ukryj – nie przerywamy pracy, tylko chowamy okno
        root.hidden = true;
        if (_resegRunning) {
          showToast('info', 'Segregacja działa w tle…');
        }
      });
    });

    if (modal.stopBtn) {
      modal.stopBtn.addEventListener('click', function () {
        _resegCancel = true;
        modal.stopBtn.disabled = true;
        modal.stopBtn.textContent = 'Zatrzymuję…';
      });
    }

    _resegModal = modal;
    return modal;
  }

  function showResegModal() {
    var m = ensureResegModal();
    m.root.hidden = false;
  }

  function setResegProgress(processed, total, pending, months, status, logLine) {
    var m = ensureResegModal();
    processed = toInt(processed, 0);
    total = toInt(total, 0);
    pending = toInt(pending, 0);

    var pct = 0;
    if (total > 0) {
      pct = Math.round((processed / total) * 100);
      if (pct < 0) pct = 0;
      if (pct > 100) pct = 100;
    }

    if (m.bar) m.bar.style.width = pct + '%';
    if (m.pct) m.pct.textContent = pct + '%';
    if (m.count) m.count.textContent = processed + ' / ' + total;
    if (m.left) m.left.textContent = pending;
    if (m.months) m.months.textContent = months ? (months + ' mies.') : '—';
    if (m.status) m.status.textContent = status || '';

    if (logLine) {
      if (m.log) {
        m.log.hidden = false;
        var p = document.createElement('div');
        p.textContent = logLine;
        m.log.appendChild(p);
        // scroll to bottom
        m.log.scrollTop = m.log.scrollHeight;
      }
    }
  }

  /**
   * Pełna re-segregacja:
   * 1) threads.reseg.start => reset derived fields (w ramach ustawionych miesięcy) + zwraca total
   * 2) pętla threads.enrich => batch po batchu aż pending=0
   */
  function startFullReseg() {
    if (_resegRunning) {
      showResegModal();
      return;
    }

    if (!confirm('Uruchomić pełną segregację wszystkich pobranych wątków?\n\nTo przeliczy filtry („Wymaga odpowiedzi”, „Dot. zamówienia/oferty”, „Załączniki”) od nowa w ramach ustawionego zakresu miesięcy.')) {
      return;
    }

    _resegRunning = true;
    _resegCancel = false;

    var btn = qs('[data-ap-action="enrich"]');
    if (btn) btn.classList.add('is-loading');

    showResegModal();
    setResegProgress(0, 0, 0, null, 'Resetuję pola i przygotowuję kolejkę…');

    var totalAll = 0;
    var months = null;
    var batchNo = 0;

    apiCall('threads.reseg.start', {})
      .then(function (res) {
        var d = (res && res.data) ? res.data : {};
        totalAll = toInt(d.total, 0);
        months = toInt(d.months, 0) || null;
        var pending = toInt(d.pending, totalAll);

        setResegProgress(0, totalAll, pending, months, 'Start: ' + totalAll + ' wątków do przeliczenia.');

        if (totalAll <= 0) {
          showToast('info', 'Brak wątków do segregacji w tym zakresie.');
          _resegRunning = false;
          if (btn) btn.classList.remove('is-loading');
          refreshStats();
          refreshList();
          return;
        }

        var batchLimit = 80; // UI: jedna paczka na request (czas i rate-limit)
        function step() {
          if (_resegCancel) {
            setResegProgress(totalAll - pending, totalAll, pending, months, 'Zatrzymano (możesz uruchomić ponownie).');
            return Promise.resolve();
          }

          return apiCall('threads.enrich', { limit: batchLimit })
            .then(function (r2) {
              var d2 = (r2 && r2.data) ? r2.data : {};
              pending = toInt(d2.pending, 0);
              var processed = totalAll - pending;
              batchNo++;

              setResegProgress(processed, totalAll, pending, months, 'Przetwarzanie… (batch ' + batchNo + ')');

              // odświeżamy UI co 2 batch’e, żeby nie mielić listy bez sensu
              if (batchNo % 2 === 0) {
                refreshStats();
                refreshList();
              } else {
                refreshStats();
              }

              if (pending > 0) {
                return new Promise(function (resolve) { setTimeout(resolve, 180); }).then(step);
              }

              setResegProgress(totalAll, totalAll, 0, months, 'Gotowe ✅');
              showToast('success', 'Segregacja zakończona: ' + totalAll + ' wątków.');
              refreshStats();
              refreshList();
            });
        }

        return step();
      })
      .catch(function (e) {
        setResegProgress(0, totalAll, totalAll, months, 'Błąd: ' + e.message, 'ERROR: ' + e.message);
        showToast('danger', 'Segregacja: ' + e.message);
      })
      .finally(function () {
        _resegRunning = false;
        _resegCancel = false;

        var m = ensureResegModal();
        if (m.stopBtn) {
          m.stopBtn.disabled = false;
          m.stopBtn.textContent = 'Zatrzymaj';
        }

        if (btn) btn.classList.remove('is-loading');
      });
  }

var _autoSyncRunning = false;

  function autoSyncOnEnter() {
    if (_autoSyncRunning) {
      return Promise.resolve();
    }

    // Prosty throttle (żeby przy szybkim odświeżaniu strony nie walić API co sekundę)
    try {
      var last = sessionStorage.getItem('ap_corr_autosync_ts');
      var now = Date.now();
      if (last && (now - parseInt(last, 10) < 60000)) {
        return Promise.resolve();
      }
      sessionStorage.setItem('ap_corr_autosync_ts', String(now));
    } catch (e) {}

    _autoSyncRunning = true;

    var btn = qs('[data-ap-action="sync"]');
    if (btn) btn.classList.add('is-loading');

    return apiCall('auto.sync', { scope: 'all' })
      .then(function (res) {
        var d = (res && res.data) ? res.data : {};
        var t = d.threads || null;
        var i = d.issues || null;

        var parts = [];
        if (t) parts.push('Wiadomości: pobrano ' + toInt(t.fetched, 0) + ', zapisano ' + toInt(t.upserted, 0));
        if (i) parts.push('Dyskusje: pobrano ' + toInt(i.fetched, 0) + ', zapisano ' + toInt(i.upserted, 0));

        if (parts.length) {
          showToast('info', 'Auto-sync: ' + parts.join(' • '));
        }

        // Jeśli po auto-sync zostały wątki bez przeliczonych pól pochodnych (pending>0),
        // dociągamy jeszcze 1–2 batch’e w tle (żeby filtry były gotowe od razu, bez klikania w wątki).
        try {
          var pendingEnrich = (t && t.enrich) ? toInt(t.enrich.pending, 0) : 0;
          if (pendingEnrich > 0) {
            setTimeout(function () {
              enrichThreads(2);
            }, 400);
          }
        } catch (e) {}

        var hasErrors = false;
        if (t && t.errors && t.errors.length) hasErrors = true;
        if (i && i.errors && i.errors.length) hasErrors = true;

        if (hasErrors) {
          showToast('warning', 'Auto-sync: część kont zwróciła błąd (sprawdź tokeny).');
          console.warn('auto.sync errors', { threads: t && t.errors, issues: i && i.errors });
        }
      })
      .catch(function (e) {
        showToast('danger', 'Auto-sync: ' + e.message);
      })
      .finally(function () {
        _autoSyncRunning = false;
        if (btn) btn.classList.remove('is-loading');

        // Po auto-sync odświeżamy badge i listę (żeby było widać nowe rekordy)
        refreshStats();
        refreshList();
      });
  }

  // Events
  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('button[data-ap-section]') : null;

    // Row quick actions menu (ellipsis on row)
    if (rowMenuEl && !rowMenuEl.hidden) {
      var insideMenu = false;
      if (e.target) {
        insideMenu = rowMenuEl.contains(e.target) || (e.target.closest && e.target.closest('[data-ap-action="rowMenu"]'));
      }
      if (!insideMenu) {
        closeRowMenu();
      }
    }

    var rowMenuBtn = e.target && e.target.closest ? e.target.closest('[data-ap-action="rowMenu"]') : null;
    if (rowMenuBtn) {
      e.preventDefault();
      openRowMenu(rowMenuBtn);
      return;
    }

    var rowMenuItem = e.target && e.target.closest ? e.target.closest('[data-ap-action="rowMenuItem"]') : null;
    if (rowMenuItem) {
      e.preventDefault();
      var act = rowMenuItem.getAttribute('data-action') || '';
      handleRowMenuAction(act);
      return;
    }


    // Widok (ustawienia + nawigacja w "single")
    var backThreadsBtn = e.target && e.target.closest ? e.target.closest('[data-ap-action="backToThreads"]') : null;
    if (backThreadsBtn) {
      e.preventDefault();
      closeDetail('messages');
      return;
    }
    var backIssuesBtn = e.target && e.target.closest ? e.target.closest('[data-ap-action="backToIssues"]') : null;
    if (backIssuesBtn) {
      e.preventDefault();
      closeDetail('issues');
      return;
    }

    var openViewBtn = e.target && e.target.closest ? e.target.closest('[data-ap-action="openViewSettings"]') : null;
    if (openViewBtn) {
      e.preventDefault();
      openViewSettingsModal();
      return;
    }

    var modalCloseBtn = e.target && e.target.closest ? e.target.closest('[data-ap-modal-close]') : null;
    if (modalCloseBtn) {
      e.preventDefault();
      closeViewSettingsModal();
      return;
    }

    var viewOptBtn = e.target && e.target.closest ? e.target.closest('[data-ap-viewopt]') : null;
    if (viewOptBtn) {
      e.preventDefault();
      var mode = viewOptBtn.getAttribute('data-ap-viewopt');
      setViewMode(mode);
      closeViewSettingsModal();
      showToast('success', 'Widok: ' + (mode === 'single' ? 'pełny' : 'kolumnowy'));
      return;
    }

    if (btn) {
      var section = btn.getAttribute('data-ap-section');
      var filter = btn.getAttribute('data-ap-filter');

      if (!filter) {
        setSection(section);
        return;
      }

      setFilter(section, filter);
      return;
    }

    var syncBtn = e.target && e.target.closest ? e.target.closest('[data-ap-action="sync"]') : null;
    if (syncBtn) {
      syncCurrent();
      return;
    }

    // Ręczna segregacja: pełny przebieg z progress barem (reset + batch do zera)
    var enrichBtn = e.target && e.target.closest ? e.target.closest('[data-ap-action="enrich"]') : null;
    if (enrichBtn) {
      startFullReseg();
      return;
    }

    var sendBtn = e.target && e.target.closest ? e.target.closest('[data-ap-action="threadSend"]') : null;
    if (sendBtn) {
      var ctx = state.open.thread;
      var input = qs('[data-ap-compose-text]');
      var text = input ? (input.value || '').toString().trim() : '';
      if (!ctx || !ctx.accountId || !ctx.threadId) {
        showToast('warning', 'Najpierw wybierz wiadomość.');
        return;
      }
      if (!text) {
        showToast('warning', 'Wpisz treść wiadomości.');
        return;
      }

      sendBtn.classList.add('is-loading');
      sendBtn.disabled = true;
      if (input) input.disabled = true;

      apiCall('thread.send', {
        account_id: ctx.accountId,
        thread_id: ctx.threadId,
        text: text
      })
        .then(function () {
          if (input) input.value = '';
          showToast('success', 'Wiadomość wysłana.');
          return openThread(ctx.accountId, ctx.threadId, ctx.login || '');
        })
        .catch(function (err) {
          showToast('danger', 'Wyślij: ' + err.message);
        })
        .finally(function () {
          sendBtn.classList.remove('is-loading');
          sendBtn.disabled = false;
          if (input) input.disabled = false;
          refreshStats();
          refreshList();
        });
      return;
    }

    var markBtn = e.target && e.target.closest ? e.target.closest('[data-ap-action="threadMarkRead"]') : null;
    if (markBtn) {
      var ctx2 = state.open.thread;
      if (!ctx2 || !ctx2.accountId || !ctx2.threadId) {
        return;
      }
      markBtn.classList.add('is-loading');
      markBtn.disabled = true;

      apiCall('thread.read', {
        account_id: ctx2.accountId,
        thread_id: ctx2.threadId
      })
        .then(function () {
          showToast('success', 'Oznaczono jako przeczytane.');
        })
        .catch(function (err) {
          markBtn.disabled = false;
          showToast('danger', 'Oznacz przeczytane: ' + err.message);
        })
        .finally(function () {
          markBtn.classList.remove('is-loading');
          refreshStats();
          refreshList();
        });
      return;
    }

    var issueSendBtn = e.target && e.target.closest ? e.target.closest('[data-ap-action="issueSend"]') : null;
    if (issueSendBtn) {
      var ctx3 = state.open.issue;
      var input2 = qs('[data-ap-compose-issue-text]');
      var text2 = input2 ? (input2.value || '').toString().trim() : '';
      if (!ctx3 || !ctx3.accountId || !ctx3.issueId) {
        showToast('warning', 'Najpierw wybierz zgłoszenie.');
        return;
      }
      if (!text2) {
        showToast('warning', 'Wpisz treść wiadomości.');
        return;
      }

      issueSendBtn.classList.add('is-loading');
      issueSendBtn.disabled = true;
      if (input2) input2.disabled = true;

      apiCall('issue.send', {
        account_id: ctx3.accountId,
        issue_id: ctx3.issueId,
        text: text2
      })
        .then(function () {
          if (input2) input2.value = '';
          showToast('success', 'Wiadomość wysłana.');
          return openIssue(ctx3.accountId, ctx3.issueId, ctx3.buyer || '');
        })
        .catch(function (err) {
          showToast('danger', 'Wyślij: ' + err.message);
        })
        .finally(function () {
          issueSendBtn.classList.remove('is-loading');
          issueSendBtn.disabled = false;
          if (input2) input2.disabled = false;
          refreshStats();
          refreshList();
        });
      return;
    }
  });

  // Search
  var searchEl = qs('[data-ap-search]');
  if (searchEl) {
    var t = null;
    searchEl.addEventListener('input', function () {
      state.search = (searchEl.value || '').toString();
      if (t) clearTimeout(t);
      t = setTimeout(function () {
        refreshList(true);
      }, 250);
    });
  }

  // Infinite scroll (lista wątków / zgłoszeń)
  var threadsList = qs('[data-ap-list="threads"]');
  if (threadsList) {
    threadsList.addEventListener('scroll', function () {
      if (state.section !== 'messages') return;
      var p = state.paging.threads;
      if (!p || p.loading || !p.hasMore) return;
      if (threadsList.scrollTop + threadsList.clientHeight >= threadsList.scrollHeight - 40) {
        refreshList(false);
      }
    });
  }
  var issuesList = qs('[data-ap-list="issues"]');
  if (issuesList) {
    issuesList.addEventListener('scroll', function () {
      if (state.section !== 'issues') return;
      var p2 = state.paging.issues;
      if (!p2 || p2.loading || !p2.hasMore) return;
      if (issuesList.scrollTop + issuesList.clientHeight >= issuesList.scrollHeight - 40) {
        refreshList(false);
      }
    });
  }

  // Initial render + load stats/list (od razu pokazujemy to, co jest w DB)
  syncUserInitials();
  syncViewModeUi();
  render();
  refreshStats().then(function () {
    refreshList(true);
  });

  // Auto-sync przy wejściu (delta) – uzupełnia tylko brakujące/nowe rekordy
  autoSyncOnEnter();
})();
