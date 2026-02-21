/* AllegroPro Settlements - UX fix for "Nieprzypisane operacje" (billing-entry modal)
 * Loaded AFTER settlements.js.
 * - Improves modal layout (uses existing .alpro-modal-kpis styles)
 * - Makes raw_json readable (collapsible <details>)
 * - Sets modal title depending on what you open
 */
(function () {
  function escHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function fmtAmt(v, currency) {
    var n = Number(v || 0);
    var cur = (currency || '').toString().trim();
    try {
      var txt = n.toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      return txt + (cur ? (' ' + cur) : '');
    } catch (e) {
      return n.toFixed(2) + (cur ? (' ' + cur) : '');
    }
  }

  function setModalTitle(text) {
    var strong = document.querySelector('#alproModal .alpro-modal__title strong');
    if (strong) strong.textContent = text;
  }

  function openModalBare() {
    var modal = document.getElementById('alproModal');
    if (!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function setLoadingState() {
    var meta = document.getElementById('alproModalMeta');
    var loading = document.getElementById('alproModalLoading');
    var content = document.getElementById('alproModalContent');
    if (meta) meta.textContent = '';
    if (content) {
      content.style.display = 'none';
      content.innerHTML = '';
    }
    if (loading) loading.style.display = 'flex';
  }

  function showContent(html, metaText) {
    var meta = document.getElementById('alproModalMeta');
    var loading = document.getElementById('alproModalLoading');
    var content = document.getElementById('alproModalContent');

    if (meta) meta.textContent = metaText || '';
    if (loading) loading.style.display = 'none';
    if (content) {
      content.innerHTML = html;
      content.style.display = 'block';
    }
  }

  function renderBillingEntry(resp) {
    var e = resp && resp.entry ? resp.entry : null;
    if (!e) {
      showContent('<div class="alert alert-danger">Brak danych wpisu billing.</div>', '');
      return;
    }

    setModalTitle('Szczegóły operacji billing');

    var idTxt = e.billing_entry_id || ('#' + (e.id_allegropro_billing_entry || ''));
    var occurred = e.occurred_at || '';
    var typeName = e.type_name || '';
    var typeId = e.type_id || '';
    var offerName = e.offer_name ? e.offer_name : '-';
    var offerId = e.offer_id ? e.offer_id : '-';

    var amount = Number(e.value_amount || 0);
    var amountColor = (amount < 0) ? '#dc3545' : (amount > 0 ? '#28a745' : '#6c757d');

    // VAT / annotation
    var vatTxt = '-';
    if (e.tax_percentage !== null && e.tax_percentage !== undefined && String(e.tax_percentage) !== '') {
      try {
        vatTxt = Number(e.tax_percentage).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
      } catch (err) {
        vatTxt = String(e.tax_percentage) + '%';
      }
      if (e.tax_annotation) vatTxt += ' • ' + String(e.tax_annotation);
    } else if (e.tax_annotation) {
      vatTxt = String(e.tax_annotation);
    }

    // raw_json pretty
    var jsonTxt = String(e.raw_json || '');
    var pretty = '';
    try { pretty = JSON.stringify(JSON.parse(jsonTxt), null, 2); } catch (err) { pretty = jsonTxt; }

    var kpis = '' +
      '<div class="alpro-modal-kpis">' +
        '<div class="kpi"><div class="label">Data</div><div class="value">' + escHtml(occurred) + '</div><div class="sub">Księgowanie operacji</div></div>' +
        '<div class="kpi"><div class="label">Typ</div><div class="value">' + escHtml(typeName || '-') + '</div><div class="sub">' + escHtml(typeId || '') + '</div></div>' +
        '<div class="kpi"><div class="label">Kwota</div><div class="value" style="color:' + amountColor + ';">' + escHtml(fmtAmt(amount, e.value_currency)) + '</div><div class="sub">Wartość operacji</div></div>' +
        '<div class="kpi"><div class="label">VAT / adnotacja</div><div class="value">' + escHtml(vatTxt) + '</div><div class="sub">Z billing-entry</div></div>' +
        '<div class="kpi"><div class="label">Oferta</div><div class="value">' + escHtml(offerName) + '</div><div class="sub">offer_id: ' + escHtml(offerId) + '</div></div>' +
        '<div class="kpi"><div class="label">Balance</div><div class="value">' + escHtml(fmtAmt(e.balance_amount, e.balance_currency)) + '</div><div class="sub">Stan salda z Allegro</div></div>' +
      '</div>';

    var tech = '' +
      '<div class="alpro-panel">' +
        '<div class="hd">' +
          '<div class="t">Dane techniczne</div>' +
          '<div class="muted">billing_entry_id: <span class="alpro-id">' + escHtml(idTxt) + '</span></div>' +
        '</div>' +
        '<div class="table-responsive">' +
          '<table class="table table-sm mb-0">' +
            '<tbody>' +
              '<tr><td style="width:220px;color:#6c757d;">id_allegropro_billing_entry</td><td><strong>' + escHtml(e.id_allegropro_billing_entry || '') + '</strong></td></tr>' +
              '<tr><td style="color:#6c757d;">billing_entry_id</td><td>' + escHtml(e.billing_entry_id || '') + '</td></tr>' +
              '<tr><td style="color:#6c757d;">order_id</td><td>' + escHtml(e.order_id || '-') + '</td></tr>' +
              '<tr><td style="color:#6c757d;">offer_id</td><td>' + escHtml(offerId) + '</td></tr>' +
              '<tr><td style="color:#6c757d;">type_id</td><td>' + escHtml(typeId) + '</td></tr>' +
            '</tbody>' +
          '</table>' +
        '</div>' +
      '</div>';

    var json = '' +
      '<details class="alpro-panel" style="margin-top:12px;">' +
        '<summary style="cursor:pointer; font-weight:900;">raw_json (kliknij aby rozwinąć)</summary>' +
        '<pre style="margin-top:10px; max-height:320px; overflow:auto; background:#f6f8fa; color:#111; border:1px solid rgba(0,0,0,.08); border-radius:14px; padding:12px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,\\"Liberation Mono\\",\\"Courier New\\", monospace; font-size:12px; white-space:pre;">' + escHtml(pretty) + '</pre>' +
      '</details>';

    var html = kpis + tech + json;
    showContent(html, '\u2022 billing_entry: ' + idTxt);
  }

  function fetchBillingEntry(ajaxUrl, accountId, idEntry) {
    var params = {
      ajax: 1,
      action: 'BillingEntryDetails',
      id_allegropro_account: accountId,
      id_allegropro_billing_entry: idEntry
    };

    if (window.jQuery) {
      window.jQuery.get(ajaxUrl, params)
        .done(function (resp) {
          if (typeof resp === 'string') {
            try { resp = JSON.parse(resp); } catch (e) {}
          }
          if (!resp || !resp.ok) {
            showContent('<div class="alert alert-danger">Nie udało się pobrać wpisu billing. ' + escHtml(resp && resp.error ? resp.error : '') + '</div>', '');
            return;
          }
          renderBillingEntry(resp);
        })
        .fail(function () {
          showContent('<div class="alert alert-danger">Błąd AJAX — nie udało się pobrać danych.</div>', '');
        });
    } else {
      var url = new URL(ajaxUrl, window.location.origin);
      Object.keys(params).forEach(function (k) { url.searchParams.set(k, params[k]); });
      fetch(url.toString(), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
          if (!resp || !resp.ok) {
            showContent('<div class="alert alert-danger">Nie udało się pobrać wpisu billing.</div>', '');
            return;
          }
          renderBillingEntry(resp);
        })
        .catch(function () {
          showContent('<div class="alert alert-danger">Błąd AJAX — nie udało się pobrać danych.</div>', '');
        });
    }
  }

  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  // --- Unassigned (no order_id) quick filters + sorting (client-side, current page only)
  function parsePlAmount(txt) {
    var s = String(txt || '').trim();
    // keep sign
    s = s.replace(/\u00a0/g, ' ');
    // strip currency and other chars
    s = s.replace(/[^0-9,\.-\+]/g, '');
    // remove spaces
    s = s.replace(/\s+/g, '');
    // comma -> dot
    s = s.replace(',', '.');
    var n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  }

  function parseIsoLike(s) {
    var t = String(s || '').trim();
    if (!t) return 0;
    // 'YYYY-MM-DD HH:MM:SS' -> ISO
    t = t.replace(' ', 'T');
    var d = new Date(t);
    var ms = d && d.getTime ? d.getTime() : NaN;
    return isNaN(ms) ? 0 : ms;
  }

  function initUnassignedFilters() {
    var wrap = document.getElementById('alproIssuesUnassignedWrap');
    if (!wrap) return;
    if (wrap.getAttribute('data-unassigned-tools') === '1') return;

    var table = wrap.querySelector('table');
    if (!table || !table.tBodies || !table.tBodies[0]) return;
    var tbody = table.tBodies[0];
    var allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    var rows = allRows.filter(function (r) {
      return r.querySelector && r.querySelector('a.js-alpro-billing-details');
    });
    if (!rows.length) return;

    function getCell(r, idx) {
      // idx is 1-based
      var td = r.querySelector('td:nth-child(' + idx + ')');
      return td ? td : null;
    }

    // Build dataset
    var items = rows.map(function (r) {
      var tdDate = getCell(r, 2);
      var tdType = getCell(r, 3);
      var tdOffer = getCell(r, 4);
      var tdAmt = getCell(r, 5);

      var dateTxt = tdDate ? tdDate.textContent.trim() : '';
      var typeTxt = tdType ? tdType.textContent.trim() : '';
      var offerTxt = tdOffer ? tdOffer.textContent.trim() : '';
      var amtTxt = tdAmt ? tdAmt.textContent.trim() : '';

      // type_id is on 2nd line in small muted div
      var typeId = '';
      if (tdType) {
        var muted = tdType.querySelector('.alpro-muted');
        typeId = muted ? muted.textContent.trim() : '';
      }
      // type_name is first bold line
      var typeName = '';
      if (tdType) {
        var strong = tdType.querySelector('div');
        typeName = strong ? strong.textContent.trim() : typeTxt;
      }

      var amount = parsePlAmount(amtTxt);
      var dateMs = parseIsoLike(dateTxt);

      var searchText = (dateTxt + ' ' + typeTxt + ' ' + offerTxt + ' ' + amtTxt).toLowerCase();

      return {
        row: r,
        dateTxt: dateTxt,
        dateMs: dateMs,
        typeId: typeId,
        typeName: typeName,
        amount: amount,
        searchText: searchText
      };
    });

    // Build type options
    var typeMap = {};
    items.forEach(function (it) {
      var k = (it.typeId || '').trim();
      if (!k) return;
      if (!typeMap[k]) typeMap[k] = it.typeName || k;
    });
    var typeKeys = Object.keys(typeMap).sort(function (a, b) {
      return a.localeCompare(b);
    });

    // Insert toolbar
    var bar = document.createElement('div');
    bar.className = 'alpro-unassigned-tools';
    bar.style.display = 'flex';
    bar.style.flexWrap = 'wrap';
    bar.style.gap = '10px';
    bar.style.alignItems = 'center';
    bar.style.margin = '0 0 10px 0';

    bar.innerHTML = '' +
      '<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">' +
        '<input id="alproUnassignedSearch" class="form-control form-control-sm" style="min-width:240px;" placeholder="Szukaj w nieprzypisanych (typ/oferta/ID)…" />' +
        '<select id="alproUnassignedSign" class="form-control form-control-sm" style="width:auto; min-width:160px;">' +
          '<option value="all">Wszystkie kwoty</option>' +
          '<option value="neg">Tylko ujemne (koszty)</option>' +
          '<option value="pos">Tylko dodatnie (zwroty/korekty)</option>' +
        '</select>' +
        '<select id="alproUnassignedType" class="form-control form-control-sm" style="width:auto; min-width:220px;">' +
          '<option value="">Wszystkie typy</option>' +
        '</select>' +
        '<select id="alproUnassignedSort" class="form-control form-control-sm" style="width:auto; min-width:200px;">' +
          '<option value="date_desc">Sort: data (najnowsze)</option>' +
          '<option value="amount_desc">Sort: kwota (malejąco)</option>' +
          '<option value="amount_asc">Sort: kwota (rosnąco)</option>' +
        '</select>' +
        '<button id="alproUnassignedReset" class="btn btn-outline-secondary btn-sm" type="button">Reset</button>' +
      '</div>' +
      '<div id="alproUnassignedStat" class="alpro-muted" style="font-size:12px;"></div>';

    // place after the intro muted paragraph, before KPIs
    var intro = wrap.querySelector('.alpro-muted');
    if (intro && intro.parentNode) {
      intro.parentNode.insertBefore(bar, intro.nextSibling);
    } else {
      wrap.insertBefore(bar, wrap.firstChild);
    }

    // fill types
    var selType = bar.querySelector('#alproUnassignedType');
    typeKeys.forEach(function (k) {
      var opt = document.createElement('option');
      opt.value = k;
      opt.textContent = (typeMap[k] ? (typeMap[k] + ' (' + k + ')') : k);
      selType.appendChild(opt);
    });

    var inputQ = bar.querySelector('#alproUnassignedSearch');
    var selSign = bar.querySelector('#alproUnassignedSign');
    var selSort = bar.querySelector('#alproUnassignedSort');
    var stat = bar.querySelector('#alproUnassignedStat');

    function apply() {
      var q = (inputQ.value || '').trim().toLowerCase();
      var sign = selSign.value || 'all';
      var typeV = selType.value || '';
      var sortV = selSort.value || 'date_desc';

      var filtered = items.filter(function (it) {
        if (q && it.searchText.indexOf(q) === -1) return false;
        if (typeV && (it.typeId || '') !== typeV) return false;
        if (sign === 'neg' && !(it.amount < 0)) return false;
        if (sign === 'pos' && !(it.amount > 0)) return false;
        return true;
      });

      // sort
      filtered.sort(function (a, b) {
        if (sortV === 'amount_desc') return (b.amount - a.amount);
        if (sortV === 'amount_asc') return (a.amount - b.amount);
        // date_desc
        return (b.dateMs - a.dateMs);
      });

      // Update DOM ordering + visibility
      items.forEach(function (it) {
        it.row.style.display = 'none';
      });
      filtered.forEach(function (it) {
        it.row.style.display = '';
        tbody.appendChild(it.row);
      });

      // stats
      var sumNeg = 0, sumPos = 0, bal = 0;
      filtered.forEach(function (it) {
        if (it.amount < 0) sumNeg += it.amount;
        if (it.amount > 0) sumPos += it.amount;
        bal += it.amount;
      });

      function fmt(n) {
        try {
          return Number(n || 0).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } catch (e) {
          return (Number(n || 0)).toFixed(2);
        }
      }

      var shown = filtered.length;
      var total = items.length;
      stat.innerHTML = 'Pokazane: <strong>' + shown + '</strong> / ' + total +
        ' • Opłaty: <span style="color:#dc3545; font-weight:700;">' + fmt(sumNeg) + ' zł</span>' +
        ' • Zwroty: <span style="color:#28a745; font-weight:700;">+' + fmt(sumPos) + ' zł</span>' +
        ' • Saldo: <strong>' + fmt(bal) + ' zł</strong>';
    }

    function reset() {
      inputQ.value = '';
      selSign.value = 'all';
      selType.value = '';
      selSort.value = 'date_desc';
      apply();
    }

    bar.querySelector('#alproUnassignedReset').addEventListener('click', function () {
      reset();
    });
    inputQ.addEventListener('input', function () { apply(); });
    selSign.addEventListener('change', function () { apply(); });
    selType.addEventListener('change', function () { apply(); });
    selSort.addEventListener('change', function () { apply(); });

    wrap.setAttribute('data-unassigned-tools', '1');
    apply();
  }

  ready(function () {
    var cfgEl = document.getElementById('alpro-settlements');
    if (!cfgEl) return;
    var ajaxUrl = cfgEl.getAttribute('data-ajax-url') || '';
    if (!ajaxUrl) return;

    // 1) When opening normal order details, ensure correct title (but don't stop original handler).
    document.addEventListener('click', function (e) {
      var el = e.target && (e.target.closest ? e.target.closest('.js-alpro-details') : null);
      if (!el) return;
      setModalTitle('Szczegóły zamówienia');
    }, true);

    // 2) Override the unassigned billing-entry modal rendering.
    document.addEventListener('click', function (e) {
      var bel = e.target && (e.target.closest ? e.target.closest('.js-alpro-billing-details') : null);
      if (!bel) return;
      e.preventDefault();
      // stop original settlements.js handler
      if (e.stopImmediatePropagation) e.stopImmediatePropagation();
      e.stopPropagation();

      var beId = bel.getAttribute('data-billing-entry') || '';
      var beAcc = bel.getAttribute('data-account-id') || '';
      if (!beId || !beAcc) return;

      openModalBare();
      setLoadingState();
      fetchBillingEntry(ajaxUrl, beAcc, beId);
    }, true);

    // 3) Quick filters for unassigned operations table.
    initUnassignedFilters();
  });
})();
