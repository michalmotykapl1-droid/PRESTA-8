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
        Object.keys(params).forEach(function (k) { url.searchParams.set(k, params[k]); });
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
      if (e.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });


    /* ============================
     * Sync button: progress modal + throttled steps
     * ============================ */
    (function initSyncProgress() {
      var syncBtn = document.getElementById('alproSyncBtn');
      if (!syncBtn) return;

      var cancelled = false;

      function showModal() {
        cancelled = false;
        var m = document.getElementById('alproSyncModal');
        if (!m) return;
        // reset UI
        var bBar = document.getElementById('alproSyncBillingBar');
        var eBar = document.getElementById('alproSyncEnrichBar');
        var bTxt = document.getElementById('alproSyncBillingText');
        var eTxt = document.getElementById('alproSyncEnrichText');
        var log = document.getElementById('alproSyncLog');
        var closeBtn = document.getElementById('alproSyncClose');
        if (bBar) { bBar.style.width = '100%'; bBar.classList.add('progress-bar-striped','progress-bar-animated'); }
        if (eBar) { eBar.style.width = '0%'; }
        if (bTxt) bTxt.textContent = 'Start…';
        if (eTxt) eTxt.textContent = 'Oczekiwanie…';
        if (log) { log.style.display='none'; log.textContent=''; }
        if (closeBtn) closeBtn.style.display='none';

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
          window.jQuery(m).modal({backdrop:'static', keyboard:false, show:true});
        } else {
          m.style.display = 'block';
          m.classList.add('in');
        }
      }

      function appendLog(line) {
        var log = document.getElementById('alproSyncLog');
        if (!log) return;
        log.style.display = 'block';
        log.textContent += (line + "\n");
      }

      function hideModalFinish() {
        var closeBtn = document.getElementById('alproSyncClose');
        var cancelBtn = document.getElementById('alproSyncCancel');
        if (cancelBtn) cancelBtn.style.display='none';
        if (closeBtn) closeBtn.style.display='inline-block';
      }

      function getSelectedAccountId() {
        // z hidden inputs (po kliknięciu "Wybierz" strona już jest w kontekście wybranych kont)
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
        if (syncBtn.disabled) return;

        // tylko tryb billing
        if (cfg.mode !== 'billing') {
          alert('Synchronizacja działa w trybie "Księgowanie opłat". Przełącz tryb i spróbuj ponownie.');
          return;
        }

        var accountId = getSelectedAccountId();
        if (!accountId) {
          alert('Synchronizacja działa dla jednego konta naraz. Wybierz jedno konto i kliknij "Wybierz".');
          return;
        }

        showModal();
        syncBtn.disabled = true;

        var cancelBtn = document.getElementById('alproSyncCancel');
        if (cancelBtn) {
          cancelBtn.style.display='inline-block';
          cancelBtn.onclick = function(){
            cancelled = true;
            cancelBtn.disabled = true;
          };
          cancelBtn.disabled = false;
        }

        var bTxt = document.getElementById('alproSyncBillingText');
        var eTxt = document.getElementById('alproSyncEnrichText');
        var eBar = document.getElementById('alproSyncEnrichBar');

        var fetched = 0, inserted = 0, updated = 0;

        function stepBilling(offset) {
          if (cancelled) return finishCancelled();

          if (bTxt) bTxt.textContent = 'Pobieranie opłat… pobrano: ' + fetched + ', nowe: ' + inserted + ', aktualizacje: ' + updated;

          ajax('BillingSyncStep', {
            account_id: accountId,
            date_from: cfg.dateFrom,
            date_to: cfg.dateTo,
            offset: offset,
            limit: 100
          }).then(function(res){
            if (!res || !res.ok) {
              appendLog('Błąd billing sync: ' + (res && res.code ? ('HTTP ' + res.code) : ''));
              if (bTxt) bTxt.textContent = 'Błąd pobierania opłat.';
              syncBtn.disabled = false;
              hideModalFinish();
              return;
            }

            fetched += Number(res.got || 0);
            inserted += Number(res.inserted || 0);
            updated += Number(res.updated || 0);

            if (res.debug_tail && res.debug_tail.length) {
              for (var i=0;i<res.debug_tail.length;i++) appendLog(res.debug_tail[i]);
            }

            if (bTxt) bTxt.textContent = 'Pobieranie opłat… pobrano: ' + fetched + ', nowe: ' + inserted + ', aktualizacje: ' + updated;

            if (Number(res.done || 0) === 1) {
              // przejdź do etapu 1: enrichment
              startEnrich();
              return;
            }

            // throttle
            setTimeout(function(){ stepBilling(Number(res.next_offset || (offset+100))); }, 300);
          }).catch(function(err){
            var msg = (err && err.message) ? err.message : String(err);
            appendLog('Błąd billing sync: ' + msg);
            if (bTxt) bTxt.textContent = 'Błąd pobierania opłat.';
            syncBtn.disabled = false;
            hideModalFinish();
          });
        }

        function startEnrich() {
          if (cancelled) return finishCancelled();
          if (eTxt) eTxt.textContent = 'Sprawdzam braki…';

          ajax('EnrichMissingCount', {
            account_id: accountId,
            date_from: cfg.dateFrom,
            date_to: cfg.dateTo
          }).then(function(res){
            if (!res || !res.ok) {
              appendLog('Błąd: nie udało się policzyć braków.');
              if (eTxt) eTxt.textContent = 'Błąd liczenia braków.';
              syncBtn.disabled = false;
              hideModalFinish();
              return;
            }

            var missing = Number(res.missing || 0);
            if (!missing) {
              if (eTxt) eTxt.textContent = 'Brak brakujących danych — OK.';
              finishOk();
              return;
            }

            var processed = 0;
            var filled = 0;

            function stepEnrich(offset) {
              if (cancelled) return finishCancelled();

              if (eTxt) eTxt.textContent = 'Uzupełniam dane… ' + processed + '/' + missing;

              ajax('EnrichMissingStep', {
                account_id: accountId,
                date_from: cfg.dateFrom,
                date_to: cfg.dateTo,
                offset: offset,
                limit: 10
              }).then(function(r){
                if (!r || !r.ok) {
                  appendLog('Błąd uzupełniania danych.');
                  if (eTxt) eTxt.textContent = 'Błąd uzupełniania danych.';
                  syncBtn.disabled = false;
                  hideModalFinish();
                  return;
                }

                processed += Number(r.processed || 0);
                filled += Number(r.updated_orders || 0);

                if (r.errors && r.errors.length) {
                  for (var i=0;i<r.errors.length;i++) {
                    appendLog('ERR ' + (r.errors[i].id || '') + ' ' + (r.errors[i].code || '') + ' ' + (r.errors[i].error || ''));
                  }
                }

                var pct = missing ? Math.min(100, Math.round((processed / missing) * 100)) : 100;
                if (eBar) eBar.style.width = pct + '%';
                if (eTxt) eTxt.textContent = 'Uzupełniam dane… ' + processed + '/' + missing + ' (uzupełnione: ' + filled + ')';

                if (Number(r.done || 0) === 1) {
                  finishOk();
                  return;
                }

                setTimeout(function(){ stepEnrich(Number(r.next_offset || (offset+10))); }, 700);
          }).catch(function(err){
            var msg = (err && err.message) ? err.message : String(err);
            appendLog('Błąd uzupełniania: ' + msg);
                if (eTxt) eTxt.textContent = 'Błąd uzupełniania danych.';
                syncBtn.disabled = false;
                hideModalFinish();
              });
            }

            stepEnrich(0);
          }).catch(function(err){
            var msg = (err && err.message) ? err.message : String(err);
            appendLog('Błąd liczenia braków: ' + msg);
            if (eTxt) eTxt.textContent = 'Błąd liczenia braków.';
            syncBtn.disabled = false;
            hideModalFinish();
          });
        }

        function finishOk() {
          var bTxt = document.getElementById('alproSyncBillingText');
          var eTxt = document.getElementById('alproSyncEnrichText');
          if (bTxt) bTxt.textContent = 'Etap 1 zakończony: pobrano ' + fetched + ' wpisów (nowe: ' + inserted + ', aktualizacje: ' + updated + ').';
          if (eTxt && eTxt.textContent.indexOf('Błąd') === -1) eTxt.textContent = 'Etap 2 zakończony — gotowe.';
          hideModalFinish();

          // odśwież widok, żeby zobaczyć kwoty/login
          setTimeout(function(){ window.location.reload(); }, 900);
        }

        function finishCancelled() {
          appendLog('Anulowano przez użytkownika.');
          hideModalFinish();
          syncBtn.disabled = false;
        }

        stepBilling(0);
      }

      syncBtn.addEventListener('click', function (e) {
        // jeśli jest w formie submit, zatrzymaj default
        e.preventDefault();
        startWorkflow();
      });
    })();

  });
})();
