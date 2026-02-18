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
      accountId: cfgEl.getAttribute('data-account-id') || '',
      dateFrom: cfgEl.getAttribute('data-date-from') || '',
      dateTo: cfgEl.getAttribute('data-date-to') || ''
    };

    if (!cfg.ajaxUrl) return;

    var modal = document.getElementById('alproModal');
    var modalMeta = document.getElementById('alproModalMeta');
    var modalLoading = document.getElementById('alproModalLoading');
    var modalContent = document.getElementById('alproModalContent');

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

    function renderDetails(data) {
      var buyer = escHtml(data.buyer_login || '');
      var acc = escHtml(data.account_label || '');
      var id = escHtml(data.checkout_form_id || '');

      modalMeta.innerHTML = (acc ? ('Konto: <strong>' + acc + '</strong> • ') : '') + 'ID: <span class="alpro-id">' + id + '</span>';

      var orderTotal = Number(data.order_total || 0);
      var feesTotal = Number(data.fees_total || 0);
      var netAfter = Number(data.net_after_fees || (orderTotal + feesTotal));

      var kpiHtml = '' +
        '<div class="alpro-modal-kpis">' +
          '<div class="kpi"><div class="label">Kupujący</div><div class="value">' + (buyer || '<span style="color:#6c757d;">brak</span>') + '</div><div class="sub">Login Allegro</div></div>' +
          '<div class="kpi"><div class="label">Suma zamówienia</div><div class="value">' + fmtMoney(orderTotal) + '</div><div class="sub">Brutto</div></div>' +
          '<div class="kpi"><div class="label">Opłaty łącznie</div><div class="value" style="color:' + (feesTotal < 0 ? '#dc3545' : '#28a745') + ';">' + fmtMoney(feesTotal) + '</div><div class="sub">Koszt Allegro</div></div>' +
          '<div class="kpi"><div class="label">Koszt opłat</div><div class="value">' + fmtPct(data.fees_rate_pct || 0) + '</div><div class="sub">% wartości zamówienia</div></div>' +
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
        itemRows = '<tr><td colspan="5" style="color:#6c757d;">Brak wpisów opłat dla tego zamówienia w wybranym okresie.</td></tr>';
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

    function loadDetails(checkoutFormId) {
      modalLoading.style.display = 'flex';
      modalContent.style.display = 'none';
      modalContent.innerHTML = '';
      modalMeta.textContent = '';

      var params = {
        ajax: 1,
        action: 'OrderDetails',
        id_allegropro_account: cfg.accountId,
        checkoutFormId: checkoutFormId,
        date_from: cfg.dateFrom,
        date_to: cfg.dateTo
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
        if (!checkout) return;
        openModal();
        loadDetails(checkout);
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });
  });
})();
