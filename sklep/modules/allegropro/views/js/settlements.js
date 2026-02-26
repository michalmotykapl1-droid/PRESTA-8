/* AllegroPro Settlements - modal details + breakdown (no external libs) */
(function () {
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function fmtMoney(v) {
    var n = Number(v || 0);
    try {
      return n.toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' zł';
    } catch (e) {
      return n.toFixed(2) + ' zł';
    }
  }

  function fmtPct(v) {
    var n = Number(v || 0);
    try {
      return n.toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
    } catch (e) {
      return n.toFixed(2) + '%';
    }
  }

  function escHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  ready(function () {
    var cfgEl = document.getElementById('alpro-settlements');
    if (!cfgEl) return;

    var cfg = {
      ajaxUrl: cfgEl.getAttribute('data-ajax-url') || '',
      dateFrom: cfgEl.getAttribute('data-date-from') || '',
      dateTo: cfgEl.getAttribute('data-date-to') || '',
      mode: cfgEl.getAttribute('data-mode') || 'billing'
    };

    if (!cfg.ajaxUrl) return;

    // Multi-select: "Zaznacz wszystkie" konta
    
    function initAccountsMultiselect() {
      var ms = document.getElementById('alproAccountsMs');
      if (!ms) return;

      var btn = ms.querySelector('.alpro-ms__btn');
      var menu = ms.querySelector('.alpro-ms__menu');
      var hidden = ms.querySelector('.alpro-ms__hidden');
      var btnText = ms.querySelector('.alpro-ms__btnText');
      var checks = ms.querySelectorAll('input[type="checkbox"]');
      var search = ms.querySelector('.alpro-ms__search');
      var items = ms.querySelectorAll('.alpro-ms__item');

      function applySearch() {
        if (!search || !items) return;
        var q = (search.value || '').trim().toLowerCase();
        for (var i = 0; i < items.length; i++) {
          var it = items[i];
          var lbl = it.querySelector('.alpro-ms__label');
          var txt = (lbl ? lbl.textContent : it.textContent || '').toLowerCase();
          it.style.display = (!q || txt.indexOf(q) !== -1) ? '' : 'none';
        }
      }

      function syncHidden() {
        if (!hidden) return;
        hidden.innerHTML = '';
        var labels = [];
        for (var i = 0; i < checks.length; i++) {
          var cb = checks[i];
          if (cb.checked) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'id_allegropro_account[]';
            inp.value = cb.value;
            hidden.appendChild(inp);

            var labelEl = cb.parentNode && cb.parentNode.querySelector('.alpro-ms__label');
            if (labelEl) {
              labels.push(labelEl.textContent.trim());
            }
          }
        }

        var cnt = labels.length;
        var t = 'Wybierz konto';
        if (cnt === 1) t = labels[0];
        else if (cnt > 1) t = 'Wybrane: ' + cnt;
        if (btnText) btnText.textContent = t;
      }

      function openMenu() {
        if (!menu) return;
        menu.classList.add('open');
        if (btn) btn.setAttribute('aria-expanded', 'true');
        if (search) {
          try { search.focus(); } catch (e) {}
        }
      }
      function closeMenu() {
        if (!menu) return;
        menu.classList.remove('open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }
      function toggleMenu() {
        if (!menu) return;
        if (menu.classList.contains('open')) closeMenu();
        else openMenu();
      }

      if (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          toggleMenu();
        });
      }

      document.addEventListener('click', function (e) {
        if (!ms.contains(e.target)) closeMenu();
      });

      if (menu) {
        menu.addEventListener('click', function (e) {
          var a = e.target.closest && e.target.closest('a[data-act]');
          if (!a) return;
          e.preventDefault();
          var act = a.getAttribute('data-act');
          if (act === 'all') {
            for (var i = 0; i < checks.length; i++) checks[i].checked = true;
          } else if (act === 'none') {
            for (var i = 0; i < checks.length; i++) checks[i].checked = false;
          }
          syncHidden();
        });
      }

      if (search) {
        search.addEventListener('input', applySearch);
        // keep menu from closing when clicking in search
        search.addEventListener('click', function (e) { e.stopPropagation(); });
      }

      for (var i = 0; i < checks.length; i++) {
        checks[i].addEventListener('change', syncHidden);
      }

      var btnAll = document.getElementById('alproSelectAll');
      if (btnAll) {
        btnAll.addEventListener('click', function (e) {
          e.preventDefault();
          for (var i = 0; i < checks.length; i++) checks[i].checked = true;
          syncHidden();
          openMenu();
        });
      }


      syncHidden();
    }

    function initFeeTypesMultiselect() {
      var ms = document.getElementById('alproFeeTypesMs');
      if (!ms) return;

      var btn = ms.querySelector('.alpro-ms__btn');
      var menu = ms.querySelector('.alpro-ms__menu');
      var hidden = ms.querySelector('.alpro-ms__hidden');
      var btnText = ms.querySelector('.alpro-ms__btnText');
      var checks = ms.querySelectorAll('input[type="checkbox"]');
      var search = ms.querySelector('.alpro-ms__search');
      var items = ms.querySelectorAll('.alpro-ms__item');

      function applySearch() {
        if (!search || !items) return;
        var q = (search.value || '').trim().toLowerCase();
        for (var i = 0; i < items.length; i++) {
          var it = items[i];
          var lbl = it.querySelector('.alpro-ms__label');
          var txt = (lbl ? lbl.textContent : it.textContent || '').toLowerCase();
          it.style.display = (!q || txt.indexOf(q) !== -1) ? '' : 'none';
        }
      }

      function syncHidden() {
        if (!hidden) return;
        hidden.innerHTML = '';
        var labels = [];
        for (var i = 0; i < checks.length; i++) {
          var cb = checks[i];
          if (cb.checked) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'fee_type[]';
            inp.value = cb.value;
            hidden.appendChild(inp);

            var labelEl = cb.parentNode && cb.parentNode.querySelector('.alpro-ms__label');
            if (labelEl) labels.push(labelEl.textContent.trim());
          }
        }

        var cnt = labels.length;
        var t = 'Wybierz typy';
        if (cnt === 1) t = labels[0];
        else if (cnt > 1) t = 'Wybrane: ' + cnt;
        if (btnText) btnText.textContent = t;
      }

      function openMenu() {
        if (!menu) return;
        menu.classList.add('open');
        if (btn) btn.setAttribute('aria-expanded', 'true');
        if (search) {
          try { search.focus(); } catch (e) {}
        }
      }
      function closeMenu() {
        if (!menu) return;
        menu.classList.remove('open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }
      function toggleMenu() {
        if (!menu) return;
        if (menu.classList.contains('open')) closeMenu();
        else openMenu();
      }

      if (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          toggleMenu();
        });
      }

      document.addEventListener('click', function (e) {
        if (!ms.contains(e.target)) closeMenu();
      });

      if (menu) {
        menu.addEventListener('click', function (e) {
          var a = e.target.closest && e.target.closest('a[data-act]');
          if (!a) return;
          e.preventDefault();
          var act = a.getAttribute('data-act');
          if (act === 'all') {
            for (var i = 0; i < checks.length; i++) checks[i].checked = true;
          } else if (act === 'none') {
            for (var i = 0; i < checks.length; i++) checks[i].checked = false;
          }
          syncHidden();
        });
      }

      if (search) {
        search.addEventListener('input', applySearch);
        search.addEventListener('click', function (e) { e.stopPropagation(); });
      }

      for (var i = 0; i < checks.length; i++) {
        checks[i].addEventListener('change', syncHidden);
      }

      syncHidden();
    }

    function initFeeGroupFromUrl() {
      var sel = document.querySelector('select[name="fee_group"]');
      if (!sel) return;
      try {
        var p = new URLSearchParams(window.location.search || '');
        var v = p.get('fee_group');
        if (v !== null && v !== undefined && v !== '') {
          sel.value = v;
        }
      } catch (e) {}
    }

    initAccountsMultiselect();

    initFeeTypesMultiselect();
    initFeeGroupFromUrl();

    // Sub-tabs: Zamówienia / Do wyjaśnienia
    function initSubTabs() {
      var tabsWrap = document.getElementById('alproSubTabs');
      if (!tabsWrap) return;
      var links = tabsWrap.querySelectorAll('.js-alpro-subtab');
      var paneOrders = document.getElementById('alproTabOrders');
      var paneIssues = document.getElementById('alproTabIssues');
      var paneRaw = document.getElementById('alproTabRaw');
      var badgeOrders = document.getElementById('alproTabBadgeOrders');
      var badgeIssues = document.getElementById('alproTabBadgeIssues');
      var badgeRaw = document.getElementById('alproTabBadgeRaw');
      if (!paneOrders || !paneIssues || !links || !links.length) return;
      var key = 'alpro_settlements_subtab';

      function show(tab) {
        var isIssues = (tab === 'issues');
        var isOrders = (tab === 'orders');
        var isRaw = (tab === 'raw');
        if (isRaw && !paneRaw) { tab = 'issues'; isRaw = false; }
        // panes
        paneOrders.classList.toggle('is-active', isOrders);
        paneIssues.classList.toggle('is-active', isIssues);
        if (paneRaw) paneRaw.classList.toggle('is-active', isRaw);
        // links
        for (var i = 0; i < links.length; i++) {
          var a = links[i];
          var t = a.getAttribute('data-tab');
          a.classList.toggle('active', t === tab);
        }
        // badges
        if (badgeOrders) badgeOrders.style.display = isOrders ? '' : 'none';
        if (badgeIssues) badgeIssues.style.display = isIssues ? '' : 'none';
        if (badgeRaw) badgeRaw.style.display = isRaw ? '' : 'none';
        try { localStorage.setItem(key, tab); } catch (e) {}
        // auto-load RAW on first open
        if (isRaw) {
          try {
            setTimeout(function(){
              var c = document.getElementById('alproRawContent');
              var btn = document.getElementById('alproRawApply');
              if (c && !c.getAttribute('data-loaded') && btn) btn.click();
            }, 0);
          } catch (e) {}
        }
      }

      // initial
      var initial = 'orders';
      try {
        if (window.location.hash && window.location.hash.indexOf('alproTabIssues') !== -1) initial = 'issues';
        else if (window.location.hash && window.location.hash.indexOf('alproTabRaw') !== -1) initial = 'raw';
        else {
          var saved = localStorage.getItem(key);
          if (saved === 'issues' || saved === 'orders' || saved === 'raw') initial = saved;
        }
      } catch (e) {}
      show(initial);

      for (var j = 0; j < links.length; j++) {
        links[j].addEventListener('click', function (e) {
          e.preventDefault();
          var tab = this.getAttribute('data-tab') || 'orders';
          show(tab);
          // update hash for direct link
          try {
            if (tab === 'issues') window.location.hash = '#alproTabIssues';
            else if (tab === 'raw') window.location.hash = '#alproTabRaw';
            else window.location.hash = '#alproTabOrders';
          } catch (err) {}
        });
      }
    }

    function initIssuesAllToggle() {
      var cb = document.getElementById('alproIssuesAll');
      if (!cb) return;
      cb.addEventListener('change', function () {
        try {
          var u = new URL(window.location.href);
          if (cb.checked) u.searchParams.set('issues_all', '1');
          else u.searchParams.delete('issues_all');
          // restart pagination
          u.searchParams.set('issues_page', '1');
          u.hash = 'alproTabIssues';
          window.location.href = u.toString();
        } catch (e) {}
      });
    }


    
    function initIssuesPerPage() {
      var sel = document.getElementById('alproIssuesPerPage');
      if (!sel) return;
      sel.addEventListener('change', function () {
        try {
          var u = new URL(window.location.href);
          var v = sel.value || '50';
          u.searchParams.set('issues_per_page', v);
          u.searchParams.set('issues_page', '1');
          u.hash = 'alproTabIssues';
          window.location.href = u.toString();
        } catch (e) {}
      });
    }

function initIssuesRefundMode() {
      var sel = document.getElementById('alproIssuesRefundMode');
      if (!sel) return;
      sel.addEventListener('change', function () {
        try {
          var u = new URL(window.location.href);
          var v = sel.value || 'any';
          if (v === 'any') u.searchParams.delete('issues_refund');
          else u.searchParams.set('issues_refund', v);
          // restart pagination
          u.searchParams.set('issues_page', '1');
          u.hash = 'alproTabIssues';
          window.location.href = u.toString();
        } catch (e) {}
      });
    }

    initSubTabs();
    initIssuesAllToggle();
    initIssuesRefundMode();
    initIssuesPerPage();



    // Copy helper (ID zamówienia itd.)
    document.addEventListener('click', function (e) {
      var el = e.target && (e.target.closest ? e.target.closest('.js-alpro-copy') : null);
      if (!el) return;
      e.preventDefault();
      var text = el.getAttribute('data-copy') || '';
      if (!text) return;

      function flash() {
        el.classList.add('is-copied');
        window.setTimeout(function () { el.classList.remove('is-copied'); }, 900);
      }

      function fallback() {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (err) {}
        document.body.removeChild(ta);
        flash();
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(flash).catch(fallback);
      } else {
        fallback();
      }
    });


    var modal = document.getElementById('alproModal');
    var modalMeta = document.getElementById('alproModalMeta');
    var modalLoading = document.getElementById('alproModalLoading');
    var modalContent = document.getElementById('alproModalContent');

    // modal: unassigned billing
    var uModal = document.getElementById('alproUnassignedModal');
    var uMeta = document.getElementById('alproUnassignedMeta');
    var uLoading = document.getElementById('alproUnassignedLoading');
    var uContent = document.getElementById('alproUnassignedContent');

    // wykres kołowy na górze
    renderMainStructure();

    var lastFocus = null;

    function openModal() {
      lastFocus = document.activeElement;
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      modalContent.innerHTML = '';
      modalContent.style.display = 'none';
      modalLoading.style.display = 'flex';
      if (lastFocus && typeof lastFocus.focus === 'function') {
        try { lastFocus.focus(); } catch (e) {}
      }
    }

    // Unassigned modal
    var uLastFocus = null;

    function openUnassignedModal() {
      if (!uModal) return;
      uLastFocus = document.activeElement;
      uModal.classList.add('is-open');
      uModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeUnassignedModal() {
      if (!uModal) return;
      uModal.classList.remove('is-open');
      uModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (uContent) {
        uContent.innerHTML = '';
        uContent.style.display = 'none';
      }
      if (uLoading) {
        uLoading.style.display = 'flex';
      }
      if (uMeta) {
        uMeta.textContent = '';
      }
      if (uLastFocus && typeof uLastFocus.focus === 'function') {
        try { uLastFocus.focus(); } catch (e) {}
      }
    }

    function buildPieGradient(pie) {
      var colors = {
        commission: 'rgba(111,66,193,.65)',
        delivery: 'rgba(0,123,255,.55)',
        smart: 'rgba(255,193,7,.78)',
        promotion: 'rgba(220,53,69,.62)',
        other: 'rgba(108,117,125,.55)'
      };

      var start = 0;
      var parts = [];
      for (var i = 0; i < pie.length; i++) {
        var sl = pie[i];
        var share = Number(sl.share || 0);
        if (share <= 0) continue;
        var deg = (share / 100) * 360;
        var end = start + deg;
        var c = colors[sl.key] || 'rgba(0,0,0,.2)';
        parts.push(c + ' ' + start.toFixed(2) + 'deg ' + end.toFixed(2) + 'deg');
        start = end;
      }
      if (parts.length === 0) {
        return 'conic-gradient(rgba(0,0,0,.06) 0 360deg)';
      }
      // fill remainder (if rounding)
      if (start < 359.9) {
        parts.push('rgba(0,0,0,.06) ' + start.toFixed(2) + 'deg 360deg');
      }
      return 'conic-gradient(' + parts.join(',') + ')';
    }

    function catLabel(key) {
      var map = {
        commission: 'Prowizje',
        delivery: 'Dostawa',
        smart: 'Smart',
        promotion: 'Promocja',
        refunds: 'Rabaty/zwroty',
        other: 'Pozostałe'
      };
      return map[key] || key;
    }

    function pillClass(key) {
      var map = {
        commission: 'alpro-pill--commission',
        delivery: 'alpro-pill--delivery',
        smart: 'alpro-pill--smart',
        promotion: 'alpro-pill--promotion',
        refunds: 'alpro-pill--refunds',
        other: 'alpro-pill--other'
      };
      return map[key] || 'alpro-pill--other';
    }



    function renderMainStructure() {
      var el = document.getElementById('alproStructure');
      if (!el) return;
      var raw = el.getAttribute('data-structure') || '';
      if (!raw) {
        var lg = document.getElementById('alproLegend');
        if (lg) lg.innerHTML = '<div class="muted">Brak danych do wykresu.</div>';
        return;
      }

      var data = null;
      try { data = JSON.parse(raw); } catch (e) { data = null; }
      if (!data) {
        var lg2 = document.getElementById('alproLegend');
        if (lg2) lg2.innerHTML = '<div class="alert alert-warning">Nie udało się odczytać danych wykresu.</div>';
        return;
      }

      var slices = Array.isArray(data.slices) ? data.slices : [];
      var costsAbs = 0;
      for (var i = 0; i < slices.length; i++) {
        costsAbs += Number(slices[i].value || 0);
      }

      var pieEl = document.getElementById('alproPie');
      if (pieEl) {
        // build gradient using shares
        pieEl.style.background = buildPieGradient(slices);
      }

      var feesTotalEl = document.getElementById('alproFeesTotal');
      if (feesTotalEl) feesTotalEl.textContent = fmtMoney(Number(data.fees_total || 0));

      var refundsEl = document.getElementById('alproRefunds');
      if (refundsEl) refundsEl.textContent = fmtMoney(Number(data.refunds || 0));

      var feesRateEl = document.getElementById('alproFeesRate');
      if (feesRateEl) feesRateEl.textContent = fmtPct(Number(data.fees_rate_pct || 0));

      var pieTotalEl = document.getElementById('alproPieTotal');
      if (pieTotalEl) pieTotalEl.textContent = fmtMoney(-costsAbs);

      var pieSubEl = document.getElementById('alproPieSub');
      if (pieSubEl) {
        if (costsAbs > 0) {
          pieSubEl.textContent = fmtPct(Number(data.fees_rate_pct || 0)) + ' sprzedaży';
        } else {
          pieSubEl.textContent = 'brak kosztów do wykresu';
        }
      }

      var legend = document.getElementById('alproLegend');
      if (!legend) return;

      if (!slices.length) {
        legend.innerHTML = '<div class="muted">Brak kosztów opłat w wybranym okresie.</div>';
        return;
      }

      var html = '';
      html += '<div class="alpro-legend-title">Rozbicie kosztów</div>';
      html += '<div class="alpro-legend-sub">% opłat = udział w kosztach opłat • % sprzedaży = koszt w relacji do sprzedaży</div>';
      html += '<div class="alpro-legend-list">';

      for (var j = 0; j < slices.length; j++) {
        var sl = slices[j];
        var label = escHtml(sl.label || catLabel(sl.key));
        var amount = Number(sl.amount || 0);
        var share = Number(sl.share || 0);
        var pctSales = Number(sl.pct_sales || 0);

        html += '<div class="alpro-legend-row">'
          + '<span class="alpro-swatch alpro-swatch--' + escHtml(sl.key) + '"></span>'
          + '<div>'
            + '<div class="nm">' + label + '</div>'
            + '<div class="meta">'
              + '<span class="pill"><strong>' + fmtMoney(amount) + '</strong></span>'
              + '<span class="pill">% opłat: <strong>' + fmtPct(share) + '</strong></span>'
              + '<span class="pill">% sprzedaży: <strong>' + fmtPct(pctSales) + '</strong></span>'
            + '</div>'
          + '</div>'
        + '</div>';
      }

      // Rabaty/zwroty pokazujemy informacyjnie (nie są kosztem opłat)
      var refundsAmt = Number(data.refunds || 0);
      if (refundsAmt !== 0) {
        html += '<div class="alpro-legend-row is-note">'
          + '<span class="alpro-swatch alpro-swatch--refunds"></span>'
          + '<div>'
            + '<div class="nm">Rabaty / zwroty</div>'
            + '<div class="meta">'
              + '<span class="pill"><strong>' + fmtMoney(refundsAmt) + '</strong></span>'
              + '<span class="pill">korekty / rekompensaty</span>'
            + '</div>'
          + '</div>'
        + '</div>';
      }

      html += '</div>';
      legend.innerHTML = html;
    }

    function renderDetails(data) {
      var buyer = escHtml(data.buyer_login || '');
      var acc = escHtml(data.account_label || '');
      var id = escHtml(data.checkout_form_id || '');
      var statusRaw = String(data.order_status || '');

      function statusBadge(st) {
        st = String(st || '').toUpperCase();
        if (!st) return '';
        var cls = 'badge badge-secondary';
        var label = st;
        if (st === 'CANCELLED') { cls = 'badge badge-danger'; label = 'Anulowane'; }
        else if (st === 'FILLED_IN') { cls = 'badge badge-warning'; label = 'Nieopłacone'; }
        else if (st === 'READY_FOR_PROCESSING' || st === 'BOUGHT') { cls = 'badge badge-success'; label = 'Opłacone'; }
        return '<span class="' + cls + '" style="margin-left:6px;">' + escHtml(label) + '</span>';
      }

      modalMeta.innerHTML = (acc ? ('Konto: <strong>' + acc + '</strong> • ') : '')
        + 'ID: <span class="alpro-id">' + id + '</span>'
        + (statusRaw ? (' • Status: ' + statusBadge(statusRaw)) : '');

      var orderTotal = Number(data.order_total || 0);          // suma z dostawą
      var sales = Number(data.sales_amount || 0);             // sprzedaż bez dostawy
      var shipping = Number(data.shipping_amount || 0);

      if (!sales && orderTotal) {
        // fallback: jeśli backend nie podał, załóż że sprzedaż = suma
        sales = orderTotal;
      }

      var feesTotal = Number(data.fees_total || 0);           // netto (ujemne = koszt)
      var feesCharged = Number(data.fees_charged || 0);       // dodatnie (abs ujemnych)
      var feesRefunded = Number(data.fees_refunded || 0);     // dodatnie
      var feesPending = Number(data.fees_pending || 0);       // dodatnie

      var netAfter = Number(data.net_after_fees || (sales + feesTotal));

      var kpiHtml = '' +
        '<div class="alpro-modal-kpis">' +
          '<div class="kpi"><div class="label">Kupujący</div><div class="value">' + (buyer || '<span style="color:#6c757d;">brak</span>') + '</div><div class="sub">Login Allegro</div></div>' +
          '<div class="kpi"><div class="label">Sprzedaż (bez dostawy)</div><div class="value">' + fmtMoney(sales) + '</div><div class="sub">Suma z dostawą: <strong>' + fmtMoney(orderTotal) + '</strong></div></div>' +
          '<div class="kpi"><div class="label">Dostawa</div><div class="value">' + fmtMoney(shipping) + '</div><div class="sub">Koszt dostawy z zamówienia</div></div>' +
          '<div class="kpi"><div class="label">Opłaty pobrane</div><div class="value" style="color:#dc3545;">' + fmtMoney(-feesCharged) + '</div><div class="sub">Suma ujemnych opłat (wszystkie daty)</div></div>' +
          '<div class="kpi"><div class="label">Zwroty opłat</div><div class="value" style="color:#28a745;">' + fmtMoney(feesRefunded) + '</div><div class="sub">Suma dodatnich korekt (wszystkie daty)</div></div>' +
          '<div class="kpi"><div class="label">Do zwrotu (anul./nieopł.)</div><div class="value" style="color:' + (feesPending > 0.01 ? '#fd7e14' : '#6c757d') + ';">' + fmtMoney(feesPending) + '</div><div class="sub">Tylko dla anulowanych/nieopłaconych: jeśli &gt; 0, Allegro powinno zwrócić</div></div>' +
          '<div class="kpi"><div class="label">Opłaty netto (okres)</div><div class="value" style="color:' + (feesTotal < 0 ? '#dc3545' : '#28a745') + ';">' + fmtMoney(feesTotal) + '</div><div class="sub">Koszt / korekty w wybranym trybie</div></div>' +
          '<div class="kpi"><div class="label">Saldo po opłatach</div><div class="value" style="color:' + (netAfter < 0 ? '#dc3545' : '#28a745') + ';">' + fmtMoney(netAfter) + '</div><div class="sub">Sprzedaż + opłaty netto</div></div>' +
        '</div>';

      // breakdown table
      var cats = data.cats || {};
      var catsPct = data.cats_pct || {};

      var rows = ['commission','delivery','smart','promotion','other','refunds'];
      var breakdownRows = '';
      for (var i = 0; i < rows.length; i++) {
        var k = rows[i];
        var amt = Number(cats[k] || 0);
        var pct = Number(catsPct[k] || 0);
        breakdownRows += '<tr>' +
          '<td><span class="alpro-pill ' + pillClass(k) + '">' + escHtml(catLabel(k)) + '</span></td>' +
          '<td class="right" style="color:' + (amt < 0 ? '#dc3545' : (amt > 0 ? '#28a745' : '#6c757d')) + ';">' + fmtMoney(amt) + '</td>' +
          '<td class="right">' + fmtPct(pct) + '</td>' +
        '</tr>';
      }
      breakdownRows += '<tr>' +
        '<td><strong>Razem</strong></td>' +
        '<td class="right"><strong style="color:' + (feesTotal < 0 ? '#dc3545' : '#28a745') + ';">' + fmtMoney(feesTotal) + '</strong></td>' +
        '<td class="right"><strong>' + fmtPct(data.fees_rate_pct || 0) + '</strong></td>' +
      '</tr>';

      var breakdownHtml = '' +
        '<div class="alpro-panel">' +
          '<div class="hd"><div class="t">Podsumowanie opłat</div><div class="muted">kwota + udział w koszcie zamówienia</div></div>' +
          '<table class="alpro-breakdown-table">' + breakdownRows + '</table>' +
          '<div class="muted" style="margin-top:6px;">Saldo po opłatach: <strong style="color:' + (netAfter < 0 ? '#dc3545' : '#28a745') + ';">' + fmtMoney(netAfter) + '</strong></div>' +
        '</div>';

      // pie
      var pie = Array.isArray(data.pie) ? data.pie : [];
      var pieGradient = buildPieGradient(pie);
      var pieLegend = '';
      for (var j = 0; j < pie.length; j++) {
        var sl = pie[j];
        pieLegend += '<div class="it">' +
          '<span class="dot alpro-dot--' + escHtml(sl.key) + '"></span>' +
          '<span class="name">' + escHtml(sl.label || catLabel(sl.key)) + '</span>' +
          '<span class="val">' + fmtPct(sl.share || 0) + ' • ' + fmtMoney(sl.amount || 0) + '</span>' +
        '</div>';
      }
      // show refunds line if exists
      var refundsAmt = Number(cats.refunds || 0);
      if (refundsAmt !== 0) {
        pieLegend += '<div class="it">' +
          '<span class="dot alpro-dot--refunds"></span>' +
          '<span class="name">Rabaty/zwroty</span>' +
          '<span class="val">' + fmtMoney(refundsAmt) + '</span>' +
        '</div>';
      }

      var pieHtml = '' +
        '<div class="alpro-panel">' +
          '<div class="hd"><div class="t">Struktura kosztów</div><div class="muted">udział w kosztach (bez zwrotów)</div></div>' +
          '<div class="alpro-pie-wrap">' +
            '<div class="alpro-pie" style="background:' + pieGradient + '"></div>' +
            '<div class="alpro-legend">' + (pieLegend || '<div class="muted">Brak danych do wykresu</div>') + '</div>' +
          '</div>' +
        '</div>';

      // items table
      var items = Array.isArray(data.items) ? data.items : [];
      var itemRows = '';
      for (var k2 = 0; k2 < items.length; k2++) {
        var it = items[k2];
        var cat = it.category || 'other';
        var offer = it.offer_name ? escHtml(it.offer_name) : '<span style="color:#6c757d;">-</span>';
        itemRows += '<tr>' +
          '<td>' + escHtml(it.occurred_at || '') + '</td>' +
          '<td><span class="alpro-pill ' + pillClass(cat) + '">' + escHtml(catLabel(cat)) + '</span></td>' +
          '<td>' + escHtml(it.type_name || '') + '</td>' +
          '<td class="text-right" style="white-space:nowrap;color:' + (Number(it.value_amount||0) < 0 ? '#dc3545' : '#28a745') + ';">' + fmtMoney(it.value_amount || 0) + '</td>' +
          '<td>' + offer + '</td>' +
        '</tr>';
      }
      if (!itemRows) {
        var msg = (cfg.mode === 'orders')
          ? 'Brak wpisów opłat dla tego zamówienia.'
          : 'Brak wpisów opłat dla tego zamówienia w wybranym okresie księgowania.';
        itemRows = '<tr><td colspan="5" style="color:#6c757d;">' + escHtml(msg) + '</td></tr>';
      }

      var itemsHtml = '' +
        '<div class="alpro-panel">' +
          '<div class="hd"><div class="t">Operacje billing (zamówienie)</div><div class="muted">' + escHtml(items.length) + ' pozycji</div></div>' +
          '<div class="table-responsive">' +
            '<table class="table table-sm table-striped mb-0 alpro-items-table">' +
              '<thead><tr><th>Data</th><th>Kategoria</th><th>Typ operacji</th><th class="text-right">Kwota</th><th>Oferta</th></tr></thead>' +
              '<tbody>' + itemRows + '</tbody>' +
            '</table>' +
          '</div>' +
        '</div>';

      var html = kpiHtml +
        '<div class="alpro-modal-grid">' +
          breakdownHtml +
          pieHtml +
        '</div>' +
        itemsHtml;

      modalContent.innerHTML = html;
      modalLoading.style.display = 'none';
      modalContent.style.display = 'block';
    }

    function loadDetails(accountId, checkoutFormId) {
      modalLoading.style.display = 'flex';
      modalContent.style.display = 'none';
      modalContent.innerHTML = '';
      modalMeta.textContent = '';

      var params = {
        ajax: 1,
        action: 'OrderDetails',
        id_allegropro_account: accountId,
        checkoutFormId: checkoutFormId,
        date_from: cfg.dateFrom,
        date_to: cfg.dateTo,
        mode: cfg.mode
      };

      // keep fee filters (fee_group + fee_type[]) so modal matches current view
      try {
        var sp = new URLSearchParams(window.location.search);
        var fg = sp.get('fee_group') || '';
        if (fg) { params.fee_group = fg; }
        var ft = sp.getAll('fee_type[]');
        if (!ft || !ft.length) { ft = sp.getAll('fee_type'); }
        if (ft && ft.length) { params.fee_type = ft; }
      } catch (e) {}

      // use jQuery if available (Presta BO), else fallback
      if (window.jQuery) {
        window.jQuery.get(cfg.ajaxUrl, params)
          .done(function (resp) {
            if (typeof resp === 'string') {
              try { resp = JSON.parse(resp); } catch (e) {}
            }
            if (!resp || !resp.ok) {
              modalContent.innerHTML = '<div class="alert alert-danger">Nie udało się pobrać szczegółów. ' + escHtml(resp && resp.error ? resp.error : '') + '</div>';
              modalLoading.style.display = 'none';
              modalContent.style.display = 'block';
              return;
            }
            renderDetails(resp);
          })
          .fail(function () {
            modalContent.innerHTML = '<div class="alert alert-danger">Błąd AJAX — nie udało się pobrać danych.</div>';
            modalLoading.style.display = 'none';
            modalContent.style.display = 'block';
          });
      } else {
        var url = new URL(cfg.ajaxUrl, window.location.origin);
        Object.keys(params).forEach(function (k) {
          var v = params[k];
          if (Array.isArray(v)) {
            v.forEach(function (item) {
              // serialize arrays as fee_type[] etc (PHP)
              if (k === 'fee_type') {
                url.searchParams.append('fee_type[]', item);
              } else {
                url.searchParams.append(k + '[]', item);
              }
            });
          } else {
            url.searchParams.set(k, v);
          }
        });
        fetch(url.toString(), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (resp) {
            if (!resp || !resp.ok) {
              modalContent.innerHTML = '<div class="alert alert-danger">Nie udało się pobrać szczegółów.</div>';
              modalLoading.style.display = 'none';
              modalContent.style.display = 'block';
              return;
            }
            renderDetails(resp);
          })
          .catch(function () {
            modalContent.innerHTML = '<div class="alert alert-danger">Błąd AJAX — nie udało się pobrać danych.</div>';
            modalLoading.style.display = 'none';
            modalContent.style.display = 'block';
          });
      }
    }

    /* ============================
     * Unassigned billing entries (order_id NULL/empty)
     * ============================ */
    var uState = { page: 1, perPage: 50, q: '', sign: 'any' };

    function uniqPositiveInts(list) {
      var out = [];
      var seen = {};
      for (var i = 0; i < (list || []).length; i++) {
        var n = parseInt(list[i], 10);
        if (!isNaN(n) && n > 0 && !seen[n]) {
          seen[n] = 1;
          out.push(n);
        }
      }
      return out;
    }

    // W widoku Presta 8 (Symfony) parametry GET nie zawsze zawierają wybrane konta,
    // bo domyślne konto może zostać ustawione po stronie serwera (bez query string).
    // Dlatego czytamy kolejno: hidden inputs -> checked checkboxy -> URL.
    function getSelectedAccountIds() {
      var ids = [];

      // 1) Hidden inputs wygenerowane przez multiselect (najpewniejsze)
      var hiddenInputs = document.querySelectorAll('#alproAccountsMs .alpro-ms__hidden input[name="id_allegropro_account[]"]');
      if (hiddenInputs && hiddenInputs.length) {
        for (var i = 0; i < hiddenInputs.length; i++) {
          ids.push(hiddenInputs[i].value);
        }
        ids = uniqPositiveInts(ids);
        if (ids.length) return ids;
      }

      // 2) Checkboxy w menu multiselect (gdy hidden jeszcze nie zsynchronizowane)
      var checks = document.querySelectorAll('#alproAccountsMs input[type="checkbox"]:checked');
      if (checks && checks.length) {
        for (var j = 0; j < checks.length; j++) {
          ids.push(checks[j].value);
        }
        ids = uniqPositiveInts(ids);
        if (ids.length) return ids;
      }

      // 3) URL query string (gdy użytkownik kliknął „Wybierz”)
      try {
        var sp = new URLSearchParams(window.location.search);
        var arr = sp.getAll('id_allegropro_account[]');
        if (!arr || !arr.length) {
          var one = sp.get('id_allegropro_account');
          if (one) arr = [one];
        }
        if (arr && arr.length) {
          ids = uniqPositiveInts(arr);
          if (ids.length) return ids;
        }
      } catch (e) {}

      return [];
    }

    function appendFeeFiltersFromUrl(params) {
      try {
        var sp = new URLSearchParams(window.location.search);
        var fg = sp.get('fee_group') || '';
        if (fg) { params.fee_group = fg; }
        var ft = sp.getAll('fee_type[]');
        if (!ft || !ft.length) { ft = sp.getAll('fee_type'); }
        if (ft && ft.length) { params.fee_type = ft; }
      } catch (e) {}
      return params;
    }

    function renderUnassigned(data) {
      if (!uContent) return;

      var total = Number(data.total || 0);
      var page = Number(data.page || 1);
      var pages = Number(data.pages || 1);
      var perPage = Number(data.per_page || uState.perPage);
      var sumTotal = Number(data.sum_total || 0);
      var sumNeg = Number(data.sum_neg || 0);
      var sumPos = Number(data.sum_pos || 0);
      var items = data.items || [];

      uState.page = page;
      uState.perPage = perPage;

      if (uMeta) {
        uMeta.textContent = ' • ' + total + ' pozycji';
      }

      var toolbar = '' +
        '<div class="alpro-unassigned-toolbar">' +
          '<div class="form-group">' +
            '<label class="form-control-label">Szukaj</label>' +
            '<input type="text" class="form-control" id="alproUnQ" placeholder="typ / oferta / billing_entry_id" value="' + escHtml(uState.q) + '">' +
          '</div>' +
          '<div class="form-group">' +
            '<label class="form-control-label">Kwota</label>' +
            '<select class="form-control" id="alproUnSign">' +
              '<option value="any"' + (uState.sign==='any'?' selected':'') + '>Wszystkie</option>' +
              '<option value="neg"' + (uState.sign==='neg'?' selected':'') + '>Tylko koszty (ujemne)</option>' +
              '<option value="pos"' + (uState.sign==='pos'?' selected':'') + '>Tylko zwroty (dodatnie)</option>' +
            '</select>' +
          '</div>' +
          '<div class="form-group">' +
            '<label class="form-control-label">Na stronę</label>' +
            '<select class="form-control" id="alproUnPer">' +
              '<option value="25"' + (perPage===25?' selected':'') + '>25</option>' +
              '<option value="50"' + (perPage===50?' selected':'') + '>50</option>' +
              '<option value="100"' + (perPage===100?' selected':'') + '>100</option>' +
              '<option value="200"' + (perPage===200?' selected':'') + '>200</option>' +
            '</select>' +
          '</div>' +
          '<div class="form-group">' +
            '<button type="button" class="btn btn-outline-secondary" id="alproUnApply">Szukaj</button>' +
          '</div>' +
        '</div>';

      var summary = '' +
        '<div class="alpro-unassigned-summary">' +
          '<span class="alpro-chip alpro-chip--neutral">Saldo <strong>' + fmtMoney(sumTotal) + '</strong></span>' +
          '<span class="alpro-chip alpro-chip--neg">Koszty <strong>' + fmtMoney(sumNeg) + '</strong></span>' +
          '<span class="alpro-chip alpro-chip--pos">Zwroty <strong>' + fmtMoney(sumPos) + '</strong></span>' +
          '<span class="alpro-unassigned-summary__spacer"></span>' +
          '<span class="alpro-unassigned-page">Strona <strong>' + page + '</strong> / ' + pages + '</span>' +
        '</div>';

      var rows = '';
      for (var i = 0; i < items.length; i++) {
        var it = items[i] || {};
        var amt = Number(it.value_amount || 0);
        var acc = escHtml(it.account_label || ('#' + (it.id_allegropro_account || '')));
        var dt = escHtml(it.occurred_at || '');
        var type = escHtml(it.type_name || it.type_id || '-');
        var offer = escHtml(it.offer_name || it.offer_id || '-');
        var bid = escHtml(it.billing_entry_id || '');
        var amtCls = (amt < 0) ? 'is-neg' : ((amt > 0) ? 'is-pos' : 'is-zero');
        rows += '' +
          '<tr>' +
            '<td class="alpro-td--nowrap">' + acc + '</td>' +
            '<td class="alpro-td--nowrap">' + dt + '</td>' +
            '<td>' + type + '</td>' +
            '<td>' + offer + '</td>' +
            '<td class="text-right alpro-td--nowrap"><span class="alpro-amount ' + amtCls + '">' + fmtMoney(amt) + '</span></td>' +
            '<td class="alpro-td--nowrap">' +
              '<code class="alpro-code" title="' + bid + '">' + bid + '</code>' +
              (bid ? ' <a href="#" class="alpro-copy alpro-copybtn" data-copy="' + bid + '" title="Kopiuj"><i class="material-icons">content_copy</i></a>' : '') +
            '</td>' +
          '</tr>';
      }
      if (!rows) {
        rows = '<tr><td colspan="6" style="color:#6c757d">Brak nieprzypisanych operacji dla obecnych filtrów.</td></tr>';
      }

      var table = '' +
        '<div class="alpro-table-card">' +
          '<div class="table-responsive">' +
            '<table class="table table-sm table-hover mb-0 alpro-unassigned-table">' +
            '<thead><tr>' +
              '<th>Konto</th><th>Data</th><th>Typ operacji</th><th>Oferta</th><th class="text-right">Kwota</th><th>ID</th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
          '</table>' +
          '</div>' +
        '</div>';

      var prevDisabled = (page <= 1) ? ' disabled' : '';
      var nextDisabled = (page >= pages) ? ' disabled' : '';
      var pager = '' +
        '<div class="alpro-unassigned-pager">' +
          '<button type="button" class="btn btn-outline-secondary btn-sm alpro-pagebtn" title="Poprzednia strona" data-unpage="' + (page - 1) + '"' + prevDisabled + '>‹</button>' +
          '<button type="button" class="btn btn-outline-secondary btn-sm alpro-pagebtn" title="Następna strona" data-unpage="' + (page + 1) + '"' + nextDisabled + '>›</button>' +
        '</div>';

      uContent.innerHTML = toolbar + summary + table + pager;
      if (uLoading) uLoading.style.display = 'none';
      uContent.style.display = 'block';
    }

    function loadUnassigned(page) {
      if (!uLoading || !uContent) return;
      uLoading.style.display = 'flex';
      uContent.style.display = 'none';
      uContent.innerHTML = '';

      var accountIds = getSelectedAccountIds();
      var params = {
        ajax: 1,
        action: 'UnassignedList',
        date_from: cfg.dateFrom,
        date_to: cfg.dateTo,
        page: page || 1,
        per_page: uState.perPage,
        q: uState.q || '',
        sign: uState.sign || 'any'
      };
      params['id_allegropro_account[]'] = accountIds;

      appendFeeFiltersFromUrl(params);

      if (window.jQuery) {
        window.jQuery.get(cfg.ajaxUrl, params)
          .done(function (resp) {
            if (typeof resp === 'string') {
              try { resp = JSON.parse(resp); } catch (e) {}
            }
            if (!resp || !resp.ok || !resp.data) {
              uContent.innerHTML = '<div class="alert alert-danger">Nie udało się pobrać danych. ' + escHtml(resp && resp.error ? resp.error : '') + '</div>';
              uLoading.style.display = 'none';
              uContent.style.display = 'block';
              return;
            }
            renderUnassigned(resp.data);
          })
          .fail(function () {
            uContent.innerHTML = '<div class="alert alert-danger">Błąd AJAX — nie udało się pobrać danych.</div>';
            uLoading.style.display = 'none';
            uContent.style.display = 'block';
          });
      } else {
        var url = new URL(cfg.ajaxUrl, window.location.origin);
        Object.keys(params).forEach(function (k) {
          var v = params[k];
          if (Array.isArray(v)) {
            v.forEach(function (item) {
              if (k === 'fee_type') {
                url.searchParams.append('fee_type[]', item);
              } else if (k === 'id_allegropro_account[]') {
                url.searchParams.append('id_allegropro_account[]', item);
              } else {
                url.searchParams.append(k + '[]', item);
              }
            });
          } else {
            url.searchParams.set(k, v);
          }
        });
        fetch(url.toString(), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (resp) {
            if (!resp || !resp.ok || !resp.data) {
              uContent.innerHTML = '<div class="alert alert-danger">Nie udało się pobrać danych. ' + escHtml(resp && resp.error ? resp.error : '') + '</div>';
              uLoading.style.display = 'none';
              uContent.style.display = 'block';
              return;
            }
            renderUnassigned(resp.data);
          })
          .catch(function () {
            uContent.innerHTML = '<div class="alert alert-danger">Błąd AJAX — nie udało się pobrać danych.</div>';
            uLoading.style.display = 'none';
            uContent.style.display = 'block';
          });
      }
    }

    // Event delegation for details buttons
    document.addEventListener('click', function (e) {
      var target = e.target;
      if (!target) return;

      // close
      var close = target.getAttribute && target.getAttribute('data-alpro-close');
      if (close === '1') {
        e.preventDefault();
        closeModal();
        return;
      }

      // close unassigned
      var uClose = target.getAttribute && target.getAttribute('data-alpro-unassigned-close');
      if (uClose === '1') {
        e.preventDefault();
        closeUnassignedModal();
        return;
      }

      // open unassigned
      var uOpen = target.closest ? target.closest('#alproUnassignedOpen') : null;
      if (uOpen) {
        e.preventDefault();
        openUnassignedModal();
        loadUnassigned(1);
        return;
      }

      // unassigned apply
      if (target.id === 'alproUnApply') {
        e.preventDefault();
        var qEl = document.getElementById('alproUnQ');
        var sEl = document.getElementById('alproUnSign');
        var pEl = document.getElementById('alproUnPer');
        uState.q = qEl ? (qEl.value || '').trim() : '';
        uState.sign = sEl ? (sEl.value || 'any') : 'any';
        uState.perPage = pEl ? parseInt(pEl.value || '50', 10) : 50;
        if (!uState.perPage || isNaN(uState.perPage)) uState.perPage = 50;
        loadUnassigned(1);
        return;
      }

      // unassigned pager
      var unPage = target.getAttribute && target.getAttribute('data-unpage');
      if (unPage) {
        e.preventDefault();
        var n = parseInt(unPage, 10);
        if (!isNaN(n) && n >= 1) {
          loadUnassigned(n);
        }
        return;
      }

      // RAW apply
      if (target.id === 'alproRawApply') {
        e.preventDefault();
        var rq = document.getElementById('alproRawQ');
        var rs = document.getElementById('alproRawSign');
        var ra = document.getElementById('alproRawAssigned');
        var rpp = document.getElementById('alproRawPerPage');
        var rsb = document.getElementById('alproRawSortBy');
        var rsd = document.getElementById('alproRawSortDir');

        rawState.q = rq ? (rq.value || '').trim() : '';
        rawState.sign = rs ? (rs.value || 'any') : 'any';
        rawState.assigned = ra ? (ra.value || 'any') : 'any';
        rawState.perPage = rpp ? parseInt(rpp.value || '50', 10) : 50;
        rawState.sortBy = rsb ? (rsb.value || 'date') : 'date';
        rawState.sortDir = rsd ? (rsd.value || 'desc') : 'desc';
        if (!rawState.perPage || isNaN(rawState.perPage)) rawState.perPage = 50;
        loadRaw(1);
        return;
      }

      // RAW pager
      var rawPage = target.getAttribute && target.getAttribute('data-rawpage');
      if (rawPage) {
        e.preventDefault();
        var rn = parseInt(rawPage, 10);
        if (!isNaN(rn) && rn >= 1) {
          loadRaw(rn);
        }
        return;
      }

      // RAW copy
      var rawCopy = target.getAttribute && target.getAttribute('data-rawcopy');
      if (rawCopy) {
        e.preventDefault();
        var txt = String(rawCopy || '');
        if (!txt) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(txt).catch(function () {});
        } else {
          try {
            var ta = document.createElement('textarea');
            ta.value = txt;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
          } catch (err) {}
        }
        return;
      }

      // details
      var el = target.closest ? target.closest('.js-alpro-details') : null;
      if (el) {
        e.preventDefault();
        var checkout = el.getAttribute('data-checkout') || '';
        var accountId = el.getAttribute('data-account-id') || '';
        if (!checkout || !accountId) return;
        openModal();
        loadDetails(accountId, checkout);
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        if (modal.classList.contains('is-open')) {
          closeModal();
        }
        if (uModal && uModal.classList.contains('is-open')) {
          closeUnassignedModal();
        }
      }

      // enter on unassigned search
      if (e.key === 'Enter' && e.target && e.target.id === 'alproUnQ') {
        e.preventDefault();
        var btn = document.getElementById('alproUnApply');
        if (btn) { btn.click(); }
      }

      // enter on RAW search
      if (e.key === 'Enter' && e.target && e.target.id === 'alproRawQ') {
        e.preventDefault();
        var rb = document.getElementById('alproRawApply');
        if (rb) { rb.click(); }
      }
    });


    /* ============================
     * RAW billing operations (tab)
     * ============================ */

    var rawContent = document.getElementById('alproRawContent');
    var rawLoading = document.getElementById('alproRawLoading');
    var rawPager = document.getElementById('alproRawPager');
    var rawTotalEl = document.getElementById('alproRawTotal');
    var rawSumTotalEl = document.getElementById('alproRawSumTotal');
    var rawSumNegEl = document.getElementById('alproRawSumNeg');
    var rawSumPosEl = document.getElementById('alproRawSumPos');
    var rawExport = document.getElementById('alproRawExport');
    var rawExportSummary = document.getElementById('alproRawExportSummary');

    var rawState = {
      page: 1,
      perPage: 50,
      q: '',
      sign: 'any',
      assigned: 'any',
      sortBy: 'date',
      sortDir: 'desc',
      loadedOnce: false
    };

    function getDateFromTo() {
      var df = document.querySelector('input[name="date_from"]');
      var dt = document.querySelector('input[name="date_to"]');
      return {
        date_from: (df && df.value) ? df.value : (cfg.dateFrom || ''),
        date_to: (dt && dt.value) ? dt.value : (cfg.dateTo || '')
      };
    }

    function appendFeeFiltersFromPage(params) {
      // fee_group
      var fgSel = document.querySelector('select[name="fee_group"]');
      var fg = fgSel ? (fgSel.value || '') : '';
      if (fg) params.fee_group = fg;

      // fee_type[] (hidden inputs created by multiselect)
      var ft = [];
      var hidden = document.querySelectorAll('#alproFeeTypesMs .alpro-ms__hidden input[name="fee_type[]"]');
      if (hidden && hidden.length) {
        for (var i = 0; i < hidden.length; i++) {
          ft.push(hidden[i].value);
        }
      } else {
        // fallback: url
        try {
          var sp = new URLSearchParams(window.location.search);
          ft = sp.getAll('fee_type[]');
          if (!ft || !ft.length) ft = sp.getAll('fee_type');
        } catch (e) {}
      }
      if (ft && ft.length) {
        params['fee_type[]'] = ft;
      }
    }

    function buildRawExportUrlFor(el, variant) {
      if (!el) return;
      try {
        var u = new URL(window.location.href);
        u.hash = '';
        // reset
        u.searchParams.set('export_raw_billing', '1');

        // variant
        if (variant === 'summary') u.searchParams.set('export_raw_variant', 'summary');
        else u.searchParams.delete('export_raw_variant');

        // accounts
        var ids = getSelectedAccountIds();
        u.searchParams.delete('id_allegropro_account');
        u.searchParams.delete('id_allegropro_account[]');
        for (var i = 0; i < ids.length; i++) {
          u.searchParams.append('id_allegropro_account[]', String(ids[i]));
        }

        // dates
        var d = getDateFromTo();
        if (d.date_from) u.searchParams.set('date_from', d.date_from);
        if (d.date_to) u.searchParams.set('date_to', d.date_to);

        // global order_state
        var os = document.querySelector('select[name="order_state"]');
        if (os && os.value) u.searchParams.set('order_state', os.value);

        // fee filters
        var fgSel = document.querySelector('select[name="fee_group"]');
        if (fgSel) {
          if (fgSel.value) u.searchParams.set('fee_group', fgSel.value);
          else u.searchParams.delete('fee_group');
        }
        u.searchParams.delete('fee_type[]');
        var hidden = document.querySelectorAll('#alproFeeTypesMs .alpro-ms__hidden input[name="fee_type[]"]');
        if (hidden && hidden.length) {
          for (var j = 0; j < hidden.length; j++) {
            u.searchParams.append('fee_type[]', hidden[j].value);
          }
        }

        // raw filters
        u.searchParams.set('raw_q', rawState.q || '');
        u.searchParams.set('raw_sign', rawState.sign || 'any');
        u.searchParams.set('raw_assigned', rawState.assigned || 'any');
        u.searchParams.set('raw_sort_by', rawState.sortBy || 'date');
        u.searchParams.set('raw_sort_dir', rawState.sortDir || 'desc');

        el.href = u.toString();
      } catch (e) {
        el.href = '#';
      }
    }

    function buildRawExportUrl() {
      buildRawExportUrlFor(rawExport, 'raw');
      buildRawExportUrlFor(rawExportSummary, 'summary');
    }

    function renderRaw(resp) {
      if (!rawContent) return;
      var data = resp && resp.data ? resp.data : null;
      if (!data) {
        rawContent.innerHTML = '<div class="alert alert-danger">Nie udało się pobrać danych.</div>';
        if (rawLoading) rawLoading.style.display = 'none';
        return;
      }

      var total = Number(data.total || 0);
      var page = Number(data.page || 1);
      var pages = Number(data.pages || 1);

      if (rawTotalEl) rawTotalEl.textContent = String(total);
      if (rawSumTotalEl) rawSumTotalEl.textContent = fmtMoney(data.sum_total || 0);
      if (rawSumNegEl) rawSumNegEl.textContent = fmtMoney(data.sum_neg || 0);
      if (rawSumPosEl) rawSumPosEl.textContent = fmtMoney(data.sum_pos || 0);

      var adminOrdersLink = resp.admin_orders_link || '';

      var rows = '';
      var items = data.items || [];
      for (var i = 0; i < items.length; i++) {
        var r = items[i] || {};
        var amt = Number(r.value_amount || 0);
        var amtCls = amt < 0 ? 'neg' : (amt > 0 ? 'pos' : '');
        var offer = '';
        if (r.offer_name) {
          offer = '<div class="alpro-raw-offer__name">' + escHtml(r.offer_name) + '</div>';
        }
        if (r.offer_id) {
          offer += '<div class="alpro-raw-offer__id">' + escHtml(r.offer_id) + '</div>';
        }

        var orderCell = '';
        if (r.order_id) {
          orderCell = '<div class="alpro-raw-order__id">' + escHtml(r.order_id) + '</div>';
          if (r.order_status) {
            orderCell += '<div class="alpro-raw-order__st">' + escHtml(r.order_status) + '</div>';
          }
          var psId = parseInt(r.id_order_prestashop || 0, 10);
          if (psId > 0 && adminOrdersLink) {
            orderCell += '<a class="alpro-ext" href="' + escHtml(adminOrdersLink) + '&id_order=' + psId + '&vieworder=1" title="Otwórz zamówienie w PrestaShop" target="_blank" rel="noopener">↗</a>';
          }
        } else {
          orderCell = '<span class="muted">—</span>';
        }

        var idShort = (r.billing_entry_id || '').toString();
        var idDisp = idShort;
        if (idDisp.length > 14) idDisp = idDisp.substr(0, 10) + '…' + idDisp.substr(idDisp.length - 4);

        rows += '<tr>'
          + '<td>' + escHtml(r.occurred_at || '') + '</td>'
          + '<td>' + escHtml(r.account_label || ('#' + (r.id_allegropro_account || ''))) + '</td>'
          + '<td>' + escHtml(r.type_name || '') + '</td>'
          + '<td>' + (offer || '<span class="muted">—</span>') + '</td>'
          + '<td>' + orderCell + '</td>'
          + '<td class="text-right"><span class="alpro-amt ' + amtCls + '">' + escHtml(fmtMoney(amt)) + '</span></td>'
          + '<td class="alpro-raw-id"><code>' + escHtml(idDisp) + '</code> <button type="button" class="btn btn-link btn-xs alpro-copy" data-rawcopy="' + escHtml(r.billing_entry_id || '') + '" title="Kopiuj ID">⧉</button></td>'
          + '</tr>';
      }

      if (!rows) {
        rows = '<tr><td colspan="7" class="muted">Brak operacji dla obecnych filtrów.</td></tr>';
      }

      rawContent.innerHTML = ''
        + '<div class="alpro-table-card">'
        + '  <div class="table-responsive">'
        + '    <table class="table table-sm table-hover mb-0 alpro-raw-table">'
        + '      <thead><tr>'
        + '        <th>Data</th><th>Konto</th><th>Typ</th><th>Oferta</th><th>Zamówienie</th><th class="text-right">Kwota</th><th>ID</th>'
        + '      </tr></thead>'
        + '      <tbody>' + rows + '</tbody>'
        + '    </table>'
        + '  </div>'
        + '</div>';

      if (rawPager) {
        var prevDisabled = (page <= 1) ? ' disabled' : '';
        var nextDisabled = (page >= pages) ? ' disabled' : '';
        rawPager.innerHTML = ''
          + '<div class="alpro-raw-pager">'
          + '  <div class="alpro-raw-pager__meta">Strona <strong>' + page + '</strong> / ' + pages + '</div>'
          + '  <div class="alpro-raw-pager__btns">'
          + '    <button type="button" class="btn btn-outline-secondary btn-sm alpro-pagebtn" data-rawpage="' + (page - 1) + '"' + prevDisabled + '>‹</button>'
          + '    <button type="button" class="btn btn-outline-secondary btn-sm alpro-pagebtn" data-rawpage="' + (page + 1) + '"' + nextDisabled + '>›</button>'
          + '  </div>'
          + '</div>';
      }

      if (rawLoading) rawLoading.style.display = 'none';
      rawState.loadedOnce = true;
      try { if (rawContent) rawContent.setAttribute('data-loaded','1'); } catch(e) {}
      buildRawExportUrl();
    }

    function loadRaw(page) {
      if (!rawContent) return;
      rawState.page = page || 1;
      if (rawLoading) rawLoading.style.display = 'flex';
      rawContent.innerHTML = '';
      if (rawPager) rawPager.innerHTML = '';

      var ids = getSelectedAccountIds();
      var d = getDateFromTo();

      var params = {
        ajax: 1,
        action: 'BillingRawList',
        raw_page: rawState.page,
        raw_per_page: rawState.perPage,
        raw_q: rawState.q || '',
        raw_sign: rawState.sign || 'any',
        raw_assigned: rawState.assigned || 'any',
        raw_sort_by: rawState.sortBy || 'date',
        raw_sort_dir: rawState.sortDir || 'desc',
        date_from: d.date_from,
        date_to: d.date_to
      };
      params['id_allegropro_account[]'] = ids;

      // order_state (global)
      var os = document.querySelector('select[name="order_state"]');
      if (os && os.value) params.order_state = os.value;

      appendFeeFiltersFromPage(params);

      if (window.jQuery) {
        window.jQuery.get(cfg.ajaxUrl, params)
          .done(function (resp) {
            if (typeof resp === 'string') {
              try { resp = JSON.parse(resp); } catch (e) {}
            }
            if (!resp || !resp.ok) {
              rawContent.innerHTML = '<div class="alert alert-danger">Nie udało się pobrać danych. ' + escHtml(resp && resp.error ? resp.error : '') + '</div>';
              if (rawLoading) rawLoading.style.display = 'none';
              return;
            }
            renderRaw(resp);
          })
          .fail(function () {
            rawContent.innerHTML = '<div class="alert alert-danger">Błąd połączenia AJAX.</div>';
            if (rawLoading) rawLoading.style.display = 'none';
          });
      } else {
        // fallback fetch
        try {
          var qs = new URLSearchParams();
          Object.keys(params).forEach(function (k) {
            var v = params[k];
            if (Array.isArray(v)) {
              for (var i = 0; i < v.length; i++) qs.append(k, v[i]);
            } else if (v !== undefined && v !== null) {
              qs.append(k, String(v));
            }
          });
          fetch(cfg.ajaxUrl + (cfg.ajaxUrl.indexOf('?') === -1 ? '?' : '&') + qs.toString(), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
              if (!resp || !resp.ok) {
                rawContent.innerHTML = '<div class="alert alert-danger">Nie udało się pobrać danych.</div>';
                if (rawLoading) rawLoading.style.display = 'none';
                return;
              }
              renderRaw(resp);
            })
            .catch(function () {
              rawContent.innerHTML = '<div class="alert alert-danger">Błąd połączenia AJAX.</div>';
              if (rawLoading) rawLoading.style.display = 'none';
            });
        } catch (e) {
          rawContent.innerHTML = '<div class="alert alert-danger">Błąd przeglądarki.</div>';
          if (rawLoading) rawLoading.style.display = 'none';
        }
      }
    }

    // init placeholder
    if (rawContent) {
      rawContent.innerHTML = '<div class="muted" style="padding:10px 2px;">Kliknij <strong>Pokaż</strong>, aby wczytać operacje billing (RAW).</div>';
      buildRawExportUrl();
    }


    /* ============================
     * Sync button: progress modal + throttled steps
     * ============================ */
    (function initSyncProgress() {
      var syncBtn = document.getElementById('alproSyncBtn');
      if (!syncBtn) return;

      var cancelled = false;
      var workflowStarted = false;
      var overallPseudo = 0; // pseudo-progress for stage 1
      var logLines = [];
      var maxLogLines = 250;

      function el(id){ return document.getElementById(id); }

      function showModal() {
        var m = el('alproSyncModal');
        if (!m) return;

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
          window.jQuery(m).modal({backdrop:'static', keyboard:false, show:true});
        } else {
          m.style.display = 'block';
          m.classList.add('in');
        }
      }

      function setNow(text) {
        var now = el('alproSyncNowText');
        if (now) now.textContent = text;
        var sub = el('alproSyncHeaderSub');
        if (sub) sub.textContent = text;
      }

      function setOverall(pct, animate) {
        pct = Math.max(0, Math.min(100, Number(pct || 0)));
        var bar = el('alproSyncOverallBar');
        var pctEl = el('alproSyncOverallPct');
        if (pctEl) pctEl.textContent = Math.round(pct) + '%';
        if (!bar) return;

        bar.style.width = pct + '%';
        if (animate) {
          bar.classList.add('progress-bar-striped','progress-bar-animated');
        } else {
          bar.classList.remove('progress-bar-striped','progress-bar-animated');
        }
      }

      function showLogToggle() {
        var t = el('alproSyncToggleLog');
        if (t) t.style.display = 'inline-block';
      }

      function appendLog(line) {
        if (!line) return;
        logLines.push(String(line));
        if (logLines.length > maxLogLines) {
          logLines = logLines.slice(logLines.length - maxLogLines);
        }
        var log = el('alproSyncLog');
        if (log) log.textContent = logLines.join("\n");
        showLogToggle();
      }

      function enableModeInputs(enabled) {
        var modeInputs = document.querySelectorAll('input[name="alpro_sync_mode"]');
        for (var mi=0; mi<modeInputs.length; mi++) {
          modeInputs[mi].disabled = !enabled;
        }
      }

      function resetUi() {
        cancelled = false;
        workflowStarted = false;
        overallPseudo = 0;
        logLines = [];

        setOverall(0, false);
        setNow('Wybierz tryb i kliknij „Rozpocznij”.');

        enableModeInputs(true);

        var steps = el('alproSyncSteps');
        if (steps) steps.style.display = 'none';

        var modeBox = el('alproSyncModeBox');
        if (modeBox) modeBox.style.display = 'block';

        var summary = el('alproSyncSummary');
        if (summary) summary.style.display = 'none';
        var sumNote = el('alproSyncSummaryNote');
        if (sumNote) sumNote.textContent = '';

        var refreshBtn = el('alproSyncRefresh');
        if (refreshBtn) refreshBtn.style.display = 'none';

        var bTxt = el('alproSyncBillingText');
        var eTxt = el('alproSyncEnrichText');
        var eBar = el('alproSyncEnrichBar');
        var bBar = el('alproSyncBillingBar');

        if (bTxt) bTxt.textContent = 'Oczekiwanie…';
        if (eTxt) eTxt.textContent = 'Oczekiwanie…';
        if (eBar) eBar.style.width = '0%';
        if (bBar) {
          bBar.style.width = '0%';
          bBar.classList.remove('progress-bar-striped','progress-bar-animated','is-indeterminate');
        }

        var toggle = el('alproSyncToggleLog');
        var wrap = el('alproSyncLogWrap');
        var log = el('alproSyncLog');
        if (toggle) {
          toggle.style.display = 'none';
          toggle.textContent = 'Pokaż szczegóły techniczne';
          toggle.setAttribute('data-open', '0');
        }
        if (wrap) wrap.style.display = 'none';
        if (log) log.textContent = '';

        var dismissBtn = el('alproSyncDismiss');
        var startBtn = el('alproSyncStart');
        var cancelBtn = el('alproSyncCancel');
        var closeBtn = el('alproSyncClose');
        var headerClose = el('alproSyncHeaderClose');

        if (dismissBtn) dismissBtn.style.display = 'inline-block';
        if (startBtn) { startBtn.style.display = 'inline-block'; startBtn.disabled = false; }
        if (cancelBtn) { cancelBtn.style.display = 'none'; cancelBtn.disabled = false; }
        if (closeBtn) closeBtn.style.display = 'none';
        if (headerClose) headerClose.style.display = 'inline-block';

        syncBtn.disabled = false;
      }

      function setRunningUi() {
        workflowStarted = true;

        var steps = el('alproSyncSteps');
        if (steps) steps.style.display = 'block';

        var modeBox = el('alproSyncModeBox');
        if (modeBox) modeBox.style.display = 'none';

        var bBar = el('alproSyncBillingBar');
        if (bBar) {
          bBar.style.width = '0%';
          bBar.classList.add('is-indeterminate');
        }

        var dismissBtn = el('alproSyncDismiss');
        var startBtn = el('alproSyncStart');
        var cancelBtn = el('alproSyncCancel');
        var closeBtn = el('alproSyncClose');
        var headerClose = el('alproSyncHeaderClose');

        if (dismissBtn) dismissBtn.style.display = 'none';
        if (startBtn) startBtn.style.display = 'none';
        if (cancelBtn) cancelBtn.style.display = 'inline-block';
        if (closeBtn) closeBtn.style.display = 'none';
        if (headerClose) headerClose.style.display = 'none';

        enableModeInputs(false);
      }

      function setFinishedUi() {
        var dismissBtn = el('alproSyncDismiss');
        var startBtn = el('alproSyncStart');
        var cancelBtn = el('alproSyncCancel');
        var closeBtn = el('alproSyncClose');
        var refreshBtn = el('alproSyncRefresh');
        var headerClose = el('alproSyncHeaderClose');

        if (dismissBtn) dismissBtn.style.display = 'none';
        if (startBtn) startBtn.style.display = 'none';
        if (cancelBtn) cancelBtn.style.display = 'none';
        if (closeBtn) closeBtn.style.display = 'inline-block';
        if (refreshBtn) refreshBtn.style.display = 'none';
        if (headerClose) headerClose.style.display = 'inline-block';

        syncBtn.disabled = false;
      }

      function getSelectedAccountId() {
        var hidden = document.querySelectorAll('#alproAccountsMs .alpro-ms__hidden input[name="id_allegropro_account[]"]');
        var ids = [];
        for (var i=0;i<hidden.length;i++){
          var v = parseInt(hidden[i].value, 10);
          if (v>0) ids.push(v);
        }
        if (ids.length === 1) return ids[0];
        return 0;
      }

      function ajax(action, params) {
        params = params || {};
        params.ajax = 1;
        params.action = action;

        var url = cfg.ajaxUrl;
        var qs = [];
        for (var k in params) {
          if (!Object.prototype.hasOwnProperty.call(params, k)) continue;
          qs.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(params[k])));
        }
        url += (url.indexOf('?') >= 0 ? '&' : '?') + qs.join('&');

        return fetch(url, {credentials:'same-origin'})
          .then(function(r){
            return r.text().then(function(txt){
              try {
                return JSON.parse(txt);
              } catch (e) {
                var snip = (txt || '').slice(0, 240).replace(/\s+/g,' ').trim();
                var err = new Error('JSON.parse: ' + snip);
                err._http = r.status;
                throw err;
              }
            });
          });
      }

      function startWorkflow() {
        if (syncBtn.disabled) return false;

        if (cfg.mode !== 'billing') {
          alert('Synchronizacja działa w trybie "Księgowanie opłat". Przełącz tryb i spróbuj ponownie.');
          return false;
        }

        var accountId = getSelectedAccountId();
        if (!accountId) {
          alert('Synchronizacja działa dla jednego konta naraz. Wybierz jedno konto i kliknij "Wybierz".');
          return false;
        }

        setRunningUi();
        syncBtn.disabled = true;

        // odczytaj tryb
        var syncMode = 'inc';
        var modeEl = document.querySelector('input[name="alpro_sync_mode"]:checked');
        if (modeEl && modeEl.value) syncMode = String(modeEl.value);
        if (syncMode !== 'inc' && syncMode !== 'full') syncMode = 'inc';

        // przycisk anuluj
        var cancelBtn = el('alproSyncCancel');
        if (cancelBtn) {
          cancelBtn.disabled = false;
          cancelBtn.onclick = function(){
            cancelled = true;
            cancelBtn.disabled = true;
            setNow('Anulowanie…');
          };
        }

        var bTxt = el('alproSyncBillingText');
        var eTxt = el('alproSyncEnrichText');
        var eBar = el('alproSyncEnrichBar');

        var fetched = 0, inserted = 0, updated = 0;
        // etap 2 (enrich)
        var filled = 0;
        var missingTotal = 0;
        var processedTotal = 0;
        var skippedTotal = 0;
        var errorTotal = 0;
        var effectiveDateFrom = '';
        overallPseudo = 0;

        setOverall(5, true);
        setNow('Start synchronizacji…');

        function bumpStage1Progress() {
          overallPseudo = Math.min(45, overallPseudo + 1);
          setOverall(5 + overallPseudo, true);
        }

        function stepBilling(offset) {
          if (cancelled) return finishCancelled();

          bumpStage1Progress();

          var nowLine = 'Pobieranie opłat (billing-entries)… pobrano: ' + fetched + ', nowe: ' + inserted + ', aktualizacje: ' + updated;
          if (syncMode === 'full') nowLine += ' (pełna)';
          setNow(nowLine);
          if (bTxt) bTxt.textContent = nowLine;

          ajax('BillingSyncStep', {
            account_id: accountId,
            date_from: cfg.dateFrom,
            date_to: cfg.dateTo,
            sync_mode: syncMode,
            effective_date_from: effectiveDateFrom,
            offset: offset,
            limit: 100
          }).then(function(res){
            if (!res || !res.ok) {
              appendLog('Błąd billing sync: ' + (res && res.code ? ('HTTP ' + res.code) : ''));
              setNow('Błąd pobierania opłat.');
              if (bTxt) bTxt.textContent = 'Błąd pobierania opłat.';
              setOverall(100, false);
              setFinishedUi();
              return;
            }

            fetched += Number(res.got || 0);
            inserted += Number(res.inserted || 0);
            updated += Number(res.updated || 0);

            if (res.effective_date_from) effectiveDateFrom = String(res.effective_date_from);

            if (res.debug_tail && res.debug_tail.length) {
              for (var i=0;i<res.debug_tail.length;i++) appendLog(res.debug_tail[i]);
            }

            var t = 'Pobieranie opłat (billing-entries)… pobrano: ' + fetched + ', nowe: ' + inserted + ', aktualizacje: ' + updated;
            if (syncMode === 'inc' && effectiveDateFrom) t += ' (od: ' + effectiveDateFrom + ')';
            if (syncMode === 'full') t += ' (pełna)';
            setNow(t);
            if (bTxt) bTxt.textContent = t;

            if (Number(res.done || 0) === 1) {
              var bBarDone2 = el('alproSyncBillingBar');
              if (bBarDone2) {
                bBarDone2.classList.remove('is-indeterminate');
                bBarDone2.style.width = '100%';
              }
              setOverall(50, false);
              startEnrich();
              return;
            }

            setTimeout(function(){ stepBilling(Number(res.next_offset || (offset+100))); }, 280);
          }).catch(function(err){
            var msg = (err && err.message) ? err.message : String(err);
            appendLog('Błąd billing sync: ' + msg);
            setNow('Błąd pobierania opłat.');
            if (bTxt) bTxt.textContent = 'Błąd pobierania opłat.';
            setFinishedUi();
          });
        }

        function startEnrich() {
          if (cancelled) return finishCancelled();

          // reset liczniki etapu 2
          missingTotal = 0;
          processedTotal = 0;
          filled = 0;
          skippedTotal = 0;
          errorTotal = 0;

          if (eBar) {
            eBar.style.width = '0%';
            eBar.classList.add('is-indeterminate');
          }

          var countMsg = 'Krok 2/2: liczę braki w zamówieniach…';
          setNow(countMsg);
          if (eTxt) eTxt.textContent = countMsg;

          ajax('EnrichMissingCount', {
            account_id: accountId,
            date_from: cfg.dateFrom,
            date_to: cfg.dateTo
          }).then(function(res){
            if (!res || !res.ok) {
              appendLog('Błąd: nie udało się policzyć braków.');
              setNow('Błąd liczenia braków.');
              if (eTxt) eTxt.textContent = 'Błąd liczenia braków.';
              if (eBar) {
                eBar.classList.remove('is-indeterminate');
                eBar.style.width = '0%';
              }
              setFinishedUi();
              return;
            }

            missingTotal = Number(res.missing || 0);

            if (eBar) {
              eBar.classList.remove('is-indeterminate');
            }

            // Jeżeli nie ma braków — jasno to komunikujemy i wypełniamy pasek
            if (!missingTotal) {
              if (eBar) eBar.style.width = '100%';
              var ok0 = 'Etap 2 zakończony: brak brakujących danych (0).';
              setNow(ok0);
              if (eTxt) eTxt.textContent = ok0;
              finishOk();
              return;
            }

            // Start właściwego uzupełniania
            if (eBar) eBar.style.width = '1%';

            var startMsg = 'Etap 2: wykryto braki w ' + missingTotal + ' zamówieniach — rozpoczynam uzupełnianie…';
            setNow(startMsg);
            if (eTxt) eTxt.textContent = startMsg;

            // Cursor-based paging (stabilne — bez OFFSET na zmieniającym się zbiorze)
            var cursorLastAt = '';
            var cursorOrderId = '';
            var lastCursorKey = '';

            function renderLine() {
              var parts = [];
              parts.push('braki: ' + missingTotal);
              parts.push('sprawdzono: ' + processedTotal + '/' + missingTotal);
              parts.push('uzupełniono: ' + filled);
              if (skippedTotal) parts.push('pominięto: ' + skippedTotal);
              if (errorTotal) parts.push('błędy: ' + errorTotal);
              return 'Etap 2: ' + parts.join(' | ');
            }

            function stepEnrich() {
              if (cancelled) return finishCancelled();

              var l0 = renderLine();
              setNow(l0);
              if (eTxt) eTxt.textContent = l0;

              ajax('EnrichMissingStep', {
                account_id: accountId,
                date_from: cfg.dateFrom,
                date_to: cfg.dateTo,
                // offset pozostaje dla zgodności (ignorowany gdy jest kursor)
                offset: 0,
                limit: 10,
                cursor_last_at: cursorLastAt,
                cursor_order_id: cursorOrderId
              }).then(function(r){
                if (!r || !r.ok) {
                  appendLog('Błąd uzupełniania danych.');
                  setNow('Błąd uzupełniania danych.');
                  if (eTxt) eTxt.textContent = 'Błąd uzupełniania danych.';
                  if (eBar) eBar.style.width = '100%';
                  setFinishedUi();
                  return;
                }

                processedTotal += Number(r.processed || 0);
                filled += Number(r.updated_orders || 0);

                // update cursor (stabilne stronicowanie)
                if (r.next_cursor_last_at) {
                  cursorLastAt = String(r.next_cursor_last_at);
                  cursorOrderId = String(r.next_cursor_order_id || '');
                }
                var ck = cursorLastAt + '|' + cursorOrderId;
                if (ck && ck === lastCursorKey && Number(r.done || 0) !== 1) {
                  appendLog('WARN: kursor nie przesunął się — przerywam, aby uniknąć pętli.');
                  if (eBar) eBar.style.width = '100%';
                  finishOk();
                  return;
                }
                lastCursorKey = ck;

                if (r.errors && r.errors.length) {
                  for (var i=0;i<r.errors.length;i++) {
                    var er = r.errors[i] || {};
                    var code = Number(er.code || 0);
                    if (code === 404) skippedTotal++; else errorTotal++;
                    appendLog('ERR ' + (er.id || '') + ' ' + (er.code || '') + ' ' + (er.error || ''));
                  }
                }

                var pct = missingTotal ? Math.min(100, Math.round((processedTotal / missingTotal) * 100)) : 100;
                if (eBar) eBar.style.width = pct + '%';

                // overall: 50..100
                var overall = 50 + (pct * 0.5);
                setOverall(overall, false);

                var l1 = renderLine();
                setNow(l1);
                if (eTxt) eTxt.textContent = l1;

                // done
                if (Number(r.done || 0) === 1 || processedTotal >= missingTotal) {
                  if (eBar) eBar.style.width = '100%';
                  finishOk();
                  return;
                }

                setTimeout(function(){ stepEnrich(); }, 450);
              }).catch(function(err){
                var msg = (err && err.message) ? err.message : String(err);
                appendLog('Błąd uzupełniania: ' + msg);
                setNow('Błąd uzupełniania danych.');
                if (eTxt) eTxt.textContent = 'Błąd uzupełniania danych.';
                if (eBar) eBar.style.width = '100%';
                setFinishedUi();
              });
            }

            stepEnrich();
          }).catch(function(err){
            var msg = (err && err.message) ? err.message : String(err);
            appendLog('Błąd liczenia braków: ' + msg);
            setNow('Błąd liczenia braków.');
            if (eTxt) eTxt.textContent = 'Błąd liczenia braków.';
            if (eBar) {
              eBar.classList.remove('is-indeterminate');
              eBar.style.width = '0%';
            }
            setFinishedUi();
          });
        }
function finishOk() {
          // zakończ etap 1 (billing) jako 100%
          var bBarDone = el('alproSyncBillingBar');
          if (bBarDone) {
            bBarDone.classList.remove('is-indeterminate');
            bBarDone.style.width = '100%';
          }

          setOverall(100, false);

          var bTxt2 = el('alproSyncBillingText');
          var eTxt2 = el('alproSyncEnrichText');
          if (bTxt2) bTxt2.textContent = 'Etap 1 zakończony: pobrano ' + fetched + ' wpisów (nowe: ' + inserted + ', aktualizacje: ' + updated + ').';
          var eBarDone = el('alproSyncEnrichBar');
          if (eBarDone) {
            eBarDone.classList.remove('is-indeterminate');
            eBarDone.style.width = '100%';
          }

          if (eTxt2 && eTxt2.textContent.indexOf('Błąd') === -1) {
            if (!missingTotal) {
              eTxt2.textContent = 'Etap 2 zakończony: brak brakujących danych (0).';
            } else {
              var msg2 = 'Etap 2 zakończony: braki: ' + missingTotal + ', sprawdzono: ' + processedTotal + '/' + missingTotal + ', uzupełniono: ' + filled;
              if (skippedTotal) msg2 += ', pominięto: ' + skippedTotal;
              if (errorTotal) msg2 += ', błędy: ' + errorTotal;
              msg2 += '.';
              eTxt2.textContent = msg2;
            }
          }

          // podsumowanie
          var summary = el('alproSyncSummary');
          if (summary) summary.style.display = 'block';
          var sf = el('alproSumFetched'); if (sf) sf.textContent = String(fetched);
          var si = el('alproSumInserted'); if (si) si.textContent = String(inserted);
          var su = el('alproSumUpdated'); if (su) su.textContent = String(updated);
          var so = el('alproSumOrdersFilled'); if (so) so.textContent = String(filled || 0);

          var note = el('alproSyncSummaryNote');
          if (note) {
            var modeLabel = (syncMode === 'full') ? 'Pełna' : 'Szybka';
            var extra = (syncMode === 'inc' && effectiveDateFrom) ? (' (pobrano od: ' + effectiveDateFrom + ')') : '';
            note.textContent = 'Tryb: ' + modeLabel + extra + '.';
          }

          setNow('Zakończono — sprawdź podsumowanie poniżej.');

          setFinishedUi();

          var refreshBtn = el('alproSyncRefresh');
          if (refreshBtn) refreshBtn.style.display = 'inline-block';

          var closeBtn = el('alproSyncClose');
          if (closeBtn) closeBtn.style.display = 'inline-block';
        }



        function finishCancelled() {
          appendLog('Anulowano przez użytkownika.');
          setNow('Anulowano — możesz zamknąć okno.');

          var bBarDone = el('alproSyncBillingBar');
          if (bBarDone) bBarDone.classList.remove('is-indeterminate');

          setFinishedUi();
        }


        stepBilling(0);
        return true;
      }

      // toggle log
      var toggleBtn = el('alproSyncToggleLog');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', function(){
          var wrap = el('alproSyncLogWrap');
          var open = toggleBtn.getAttribute('data-open') === '1';
          if (!wrap) return;

          if (open) {
            wrap.style.display = 'none';
            toggleBtn.textContent = 'Pokaż szczegóły techniczne';
            toggleBtn.setAttribute('data-open', '0');
          } else {
            wrap.style.display = 'block';
            toggleBtn.textContent = 'Ukryj szczegóły techniczne';
            toggleBtn.setAttribute('data-open', '1');
          }
        });
      }

      
      // Refresh button (manual reload after completion)
      var refreshBtn = el('alproSyncRefresh');
      if (refreshBtn) {
        refreshBtn.addEventListener('click', function(e){
          e.preventDefault();
          window.location.reload();
        });
      }

// Start button inside modal
      var startBtn = el('alproSyncStart');
      if (startBtn) {
        startBtn.addEventListener('click', function(e){
          e.preventDefault();
          if (startBtn.disabled) return;
          startBtn.disabled = true;
          var startedOk = startWorkflow();
          if (startedOk === false) startBtn.disabled = false;
        });
      }

      // Open modal (setup) from main button
      syncBtn.addEventListener('click', function (e) {
        e.preventDefault();

        if (cfg.mode !== 'billing') {
          alert('Synchronizacja działa w trybie "Księgowanie opłat". Przełącz tryb i spróbuj ponownie.');
          return false;
        }

        showModal();
        resetUi();

        // po otwarciu ponownie pozwól kliknąć start
        var sb = el('alproSyncStart');
        if (sb) sb.disabled = false;
      });

      // jeśli ktoś zamknie modal w trakcie (np. brak jQuery), wyczyść stan
      var dismissBtn = el('alproSyncDismiss');
      if (dismissBtn) {
        dismissBtn.addEventListener('click', function(){
          if (!workflowStarted) {
            resetUi();
          }
        });
      }

    })();

  });
})();
