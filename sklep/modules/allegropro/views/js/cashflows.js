// AllegroPro Cashflows
// Chunked sync z paskiem postępu (modal)

(function ($) {
  'use strict';

  function setProgress(percent) {
    percent = Math.max(0, Math.min(100, parseInt(percent || 0, 10)));
    var $bar = $('#alproSyncProgress');
    $bar.css('width', percent + '%');
    $bar.text(percent + '%');
  }

  function setText(main, sub) {
    $('#alproSyncText').text(main || '');
    $('#alproSyncSub').text(sub || '');
  }

  function openModal() {
    setProgress(0);
    setText('Start…', '');
    $('#alproSyncClose').prop('disabled', true);
    try {
      $('#alproSyncModal').modal({ backdrop: 'static', keyboard: false });
    } catch (e) {
      // fallback: jeśli modal nie działa, nie blokuj
    }
  }

  function closeModal() {
    try {
      $('#alproSyncModal').modal('hide');
    } catch (e) {}
  }

  function postChunk(ajaxUrl, formData, state, onDone) {
    var data = $.extend({}, formData, { state: state || '' });
    $.ajax({
      url: ajaxUrl,
      method: 'POST',
      dataType: 'json',
      data: data,
      timeout: 120000
    })
      .done(function (resp) {
        if (!resp || !resp.ok) {
          var err = (resp && resp.error) ? resp.error : 'Nieznany błąd synchronizacji.';
          if (resp && resp.http) {
            err += ' (HTTP ' + resp.http + ')';
          }
          // Spróbuj wyciągnąć "detail" z RFC7807 / problem+json
          if (resp && resp.raw) {
            try {
              var j = JSON.parse(resp.raw);
              if (j && (j.title || j.detail)) {
                err += ' · ' + (j.title ? (j.title + ': ') : '') + (j.detail || '');
              } else {
                err += ' · ' + String(resp.raw).substring(0, 200);
              }
            } catch (e2) {
              err += ' · ' + String(resp.raw).substring(0, 200);
            }
          }
          setText('Błąd synchronizacji', err);
          $('#alproSyncClose').prop('disabled', false);
          setProgress(0);
          return;
        }

        setProgress(resp.percent || 0);
        var fetched = parseInt(resp.fetched || 0, 10);
        var stored = parseInt(resp.stored || 0, 10);
        var unchanged = fetched - stored;
        if (unchanged < 0) unchanged = 0;
        var main = 'Pobrano z API (operacje): ' + fetched +
          ' · nowe/uzupełnione: ' + stored +
          (unchanged ? (' · bez zmian: ' + unchanged) : '') +
          ' · strony API: ' + (resp.pages || 0);

        var sub = '';
        if (resp.chunk && (resp.chunk.range_from || resp.chunk.range_to)) {
          sub = 'Zakres operacji (occurredAt): ' + (resp.chunk.range_from || '') + ' → ' + (resp.chunk.range_to || '') +
            ' · zakres #' + ((resp.range_index || 0) + 1) + '/' + (resp.range_total || 0);
          if (resp.chunk.totalCount && resp.chunk.totalCount > 0) {
            sub += ' · operacje: ' + Math.min(resp.chunk.offset || 0, resp.chunk.totalCount) + '/' + resp.chunk.totalCount + ' (totalCount z API)';
          }
        }
        setText(main, sub);

        if (resp.done) {
          setProgress(100);
          var fetchedDone = parseInt(resp.fetched || 0, 10);
          var storedDone = parseInt(resp.stored || 0, 10);
          var unchangedDone = fetchedDone - storedDone;
          if (unchangedDone < 0) unchangedDone = 0;
          var doneTxt = 'Zakończono. Pobrano z API (operacje): ' + fetchedDone +
            ' · nowe/uzupełnione: ' + storedDone +
            (unchangedDone ? (' · bez zmian: ' + unchangedDone) : '') +
            ' · strony API: ' + (resp.pages || 0);
          if (resp.filled_days) {
            doneTxt += ' · uzupełnione dni: ' + resp.filled_days;
          }
          if (resp.chunk && resp.chunk.totalCount && resp.chunk.totalCount > 0) {
            doneTxt += ' · potwierdzenie zakresu: ' + Math.min(resp.chunk.offset || 0, resp.chunk.totalCount) + '/' + resp.chunk.totalCount;
          }
          setText(doneTxt, 'Odświeżam widok…');
          $('#alproSyncClose').prop('disabled', false);
          // odśwież widok po zakończeniu
          window.setTimeout(function () {
            onDone && onDone();
          }, 600);
          return;
        }

        // kolejna partia
        window.setTimeout(function () {
          postChunk(ajaxUrl, formData, resp.state || '', onDone);
        }, 120);
      })
      .fail(function (xhr, statusText) {
        var msg = 'Błąd połączenia: ' + (statusText || '');
        if (xhr && xhr.responseText) {
          msg += ' · ' + xhr.responseText.substring(0, 200);
        }
        setText('Błąd synchronizacji', msg);
        $('#alproSyncClose').prop('disabled', false);
        setProgress(0);
      });
  }


  function enrichMissingOrders(countUrl, stepUrl, params, onDone) {
    if (!countUrl || !stepUrl) {
      onDone && onDone();
      return;
    }

    // Używamy tego samego modala co synchronizacja – pokazujemy postęp uzupełniania danych zamówień.
    setProgress(0);
    setText('Uzupełniam dane zamówień z Allegro…', 'Sprawdzam zamówienia do uzupełnienia…');
    $('#alproSyncClose').prop('disabled', true);

    function showError(prefix, resp, xhr) {
      var err = prefix + ': ';
      if (resp && resp.error) {
        err += resp.error;
      } else if (xhr && xhr.responseText) {
        err += String(xhr.responseText).substring(0, 250);
      } else {
        err += 'Nieznany błąd.';
      }
      if (resp && resp.http) {
        err += ' (HTTP ' + resp.http + ')';
      }
      setText('Błąd', err);
      setProgress(0);
      $('#alproSyncClose').prop('disabled', false);
    }

    // 1) Policz braki
    $.ajax({
      url: countUrl,
      method: 'POST',
      dataType: 'json',
      data: params,
      timeout: 120000
    })
      .done(function (cnt) {
        if (!cnt || !cnt.ok) {
          showError('Błąd podczas przygotowania listy zamówień', cnt);
          return;
        }

        var totalMissing = parseInt(cnt.missing || 0, 10);
        var throttled = parseInt(cnt.throttled || 0, 10);

        if (totalMissing <= 0) {
          setProgress(100);
          setText('Brak danych do uzupełnienia', throttled > 0 ? ('Pomijam odroczone: ' + throttled) : '');
          $('#alproSyncClose').prop('disabled', false);
          window.setTimeout(function () {
            onDone && onDone();
          }, 300);
          return;
        }

        var processedTotal = 0;
        var updatedTotal = 0;
        var notFoundTotal = 0;
        var errorsTotal = 0;

        // Cursor-based od "dalekiej przyszłości", żeby pierwsza strona nie pominęła rekordów.
        var cursorLastAt = '2099-12-31 23:59:59';
        var cursorOrderId = 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz';

        function doStep() {
          var payload = $.extend({}, params, {
            limit: 10,
            offset: 0,
            cursor_last_at: cursorLastAt,
            cursor_order_id: cursorOrderId
          });

          $.ajax({
            url: stepUrl,
            method: 'POST',
            dataType: 'json',
            data: payload,
            timeout: 120000
          })
            .done(function (resp) {
              if (!resp || !resp.ok) {
                showError('Błąd uzupełniania danych zamówień', resp);
                return;
              }

              var processed = parseInt(resp.processed || 0, 10);
              var updated = parseInt(resp.updated_orders || 0, 10);
              var notFound = parseInt(resp.not_found || 0, 10);
              var errs = parseInt(resp.errors_count || ((resp.errors && resp.errors.length) ? resp.errors.length : 0), 10);

              processedTotal += processed;
              updatedTotal += updated;
              notFoundTotal += notFound;
              errorsTotal += errs;

              var pct = Math.round((processedTotal / totalMissing) * 100);
              pct = Math.max(0, Math.min(99, pct));
              setProgress(pct);

              var main = 'Aktualizacja danych zamówień: ' + processedTotal + '/' + totalMissing +
                ' · zaktualizowane: ' + updatedTotal +
                ' · nie znaleziono w Allegro: ' + notFoundTotal +
                ' · błędy: ' + errorsTotal;
              var sub = throttled > 0 ? ('Pomijam odroczone: ' + throttled) : '';
              setText(main, sub);

              // aktualizuj cursor
              if (resp.next_cursor_last_at) {
                cursorLastAt = resp.next_cursor_last_at;
              }
              if (resp.next_cursor_order_id) {
                cursorOrderId = resp.next_cursor_order_id;
              }

              if (resp.done) {
                setProgress(100);
                setText('Zakończono aktualizację danych. Zaktualizowane: ' + updatedTotal + ' · nie znaleziono: ' + notFoundTotal + ' · błędy: ' + errorsTotal, 'Odświeżam widok…');
                $('#alproSyncClose').prop('disabled', false);
                window.setTimeout(function () {
                  onDone && onDone();
                }, 600);
                return;
              }

              // kolejny krok
              window.setTimeout(doStep, 120);
            })
            .fail(function (xhr, statusText) {
              showError('Błąd połączenia (' + (statusText || '') + ')', null, xhr);
            });
        }

        // start
        doStep();
      })
      .fail(function (xhr, statusText) {
        showError('Błąd połączenia (' + (statusText || '') + ')', null, xhr);
      });
  }


  $(document).on('click', '.alpro-sync-btn', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var ajaxUrl = $btn.data('ajax-url');
    if (!ajaxUrl) {
      return;
    }

    var $form = $btn.closest('form');
    var formData = {
      id_allegropro_account: $form.find('[name="id_allegropro_account"]').val() || '',
      date_from: $form.find('[name="date_from"]').val() || '',
      date_to: $form.find('[name="date_to"]').val() || '',
      sync_mode: $form.find('[name="sync_mode"]').val() || 'fill'
    };

    // UI lock
    $btn.prop('disabled', true);
    openModal();

    var afterEnrich = parseInt($btn.data('after-enrich') || 0, 10) === 1;
    var enrichCountUrl = $btn.data('enrich-count-url') || '';
    var enrichStepUrl = $btn.data('enrich-step-url') || '';

    postChunk(ajaxUrl, formData, '', function () {
      // Po synchronizacji (BILLING) uzupełnij dane zamówień z Allegro, żeby zniknęło "Brak danych".
      if (afterEnrich && enrichCountUrl && enrichStepUrl) {
        // modal jest już otwarty – kontynuujemy w nim progres
        $('#alproSyncClose').prop('disabled', true);
        enrichMissingOrders(enrichCountUrl, enrichStepUrl, {
          account_id: formData.id_allegropro_account,
          date_from: formData.date_from,
          date_to: formData.date_to
        }, function () {
          $btn.prop('disabled', false);
          try { closeModal(); } catch (e2) {}
          window.location.reload();
        });
        return;
      }

      // unlock + reload
      $btn.prop('disabled', false);
      try { closeModal(); } catch (e2) {}
      window.location.reload();
    });
  });


  $(document).on('click', '.alpro-enrich-btn', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var countUrl = $btn.data('enrich-count-url') || '';
    var stepUrl = $btn.data('enrich-step-url') || '';
    if (!countUrl || !stepUrl) return;

    var $form = $btn.closest('form');
    var params = {
      account_id: $form.find('[name="id_allegropro_account"]').val() || '',
      date_from: $form.find('[name="date_from"]').val() || '',
      date_to: $form.find('[name="date_to"]').val() || ''
    };

    $btn.prop('disabled', true);
    openModal();

    enrichMissingOrders(countUrl, stepUrl, params, function () {
      $btn.prop('disabled', false);
      try { closeModal(); } catch (e2) {}
      window.location.reload();
    });
  });

  // Rozwijanie payment_id pod transakcją (w tym samym widoku, pod wierszem)
  $(document).on('click', '.alpro-toggle-payments', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var target = $btn.data('target');
    if (!target) return;
    var $row = $(target);
    if ($row.length === 0) return;

    var isOpen = $row.is(':visible');
    if (isOpen) {
      $row.hide();
      $btn.find('.material-icons').text('expand_more');
    } else {
      $row.show();
      $btn.find('.material-icons').text('expand_less');
    }
  });



  // BILLING: Szczegóły opłat/zwrotów opłat dla checkoutFormId (lazy-load)
  function alproEscHtml(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function alproFmtMoney(n, currency) {
    var num = parseFloat(n || 0);
    if (isNaN(num)) num = 0;
    var cur = currency || 'PLN';
    return (Math.round(num * 100) / 100).toFixed(2) + ' ' + cur;
  }

  function alproRenderBillingDetails(resp) {
    var entries = (resp && resp.entries) ? resp.entries : [];
    var html = '';
    html += '<div class="alpro-details-summary">';
    html += '  <div>';
    html += '    <div class="alpro-details-title">Szczegóły opłat i zwrotów opłat (Allegro)</div>';
    html += '    <div class="alpro-details-subtitle">checkoutFormId: ' + alproEscHtml(resp.order_id || '') + '</div>';
    html += '  </div>';
    html += '  <div class="alpro-details-kpis">';
    html += '    <div class="alpro-details-kpi"><strong>Opłaty:</strong> ' + alproFmtMoney(resp.fees_abs || 0, 'PLN') + '</div>';
    html += '    <div class="alpro-details-kpi"><strong>Zwroty opłat:</strong> ' + alproFmtMoney(resp.refunds_pos || 0, 'PLN') + '</div>';
    html += '    <div class="alpro-details-kpi"><strong>Saldo:</strong> ' + alproFmtMoney(resp.net || 0, 'PLN') + '</div>';
    html += '  </div>';
    html += '</div>';

    html += '<div class="alpro-details-note">Kwoty ujemne = opłata pobrana przez Allegro. Kwoty dodatnie = zwrot opłaty (Allegro oddało). To rozliczenia Allegro ↔ Ty (nie zwroty dla klienta). VAT pokazuje stawkę podatku dla tej opłaty na fakturze od Allegro.</div>';

    html += '<div class="table-responsive">';
    html += '<table class="table table-sm mb-0 alpro-details-table">';
    html += '  <thead><tr>';
    html += '    <th>Data</th>';
    html += '    <th>Typ</th>';
    html += '    <th>Oferta / identyfikator</th>';
    html += '    <th style="text-align:right;" title="Kwota operacji billingowej z Allegro (value.amount) – obciąża/uznaje Twoje saldo w Allegro.">Kwota (z Allegro)</th>';
    html += '    <th style="text-align:right;" title="Stawka VAT dla tej opłaty (tax.percentage/tax.annotation).">VAT (stawka)</th>';
    html += '  </tr></thead>';
    html += '  <tbody>';

    if (!entries || entries.length === 0) {
      html += '<tr><td colspan="5" class="text-muted" style="padding:10px 12px;">Brak wpisów billing-entries dla tego zamówienia w wybranym zakresie.</td></tr>';
    } else {
      for (var i = 0; i < entries.length; i++) {
        var e = entries[i] || {};
        var date = alproEscHtml(e.occurred_at || '');
        var type = alproEscHtml(e.type_name || e.type_id || '');
        var offerName = (e.offer_name || '') ? alproEscHtml(e.offer_name) : '';
        var offerId = (e.offer_id || '') ? alproEscHtml(e.offer_id) : '';
        var offerHtml = offerName ? offerName : (offerId ? ('Oferta: ' + offerId) : '-');
        if (offerName && offerId) {
          offerHtml += '<div class="text-muted" style="font-size:11px; margin-top:2px;">' + offerId + '</div>';
        }

        var amt = parseFloat(e.amount || 0);
        if (isNaN(amt)) amt = 0;
        var cls = (amt < 0) ? 'alpro-amt-neg' : ((amt > 0) ? 'alpro-amt-pos' : 'alpro-amt-zero');
        var amtTxt = alproFmtMoney(amt, e.currency || 'PLN');

        var vat = '-';
        if (e.tax_percentage !== null && e.tax_percentage !== undefined && e.tax_percentage !== '') {
          var v = parseFloat(e.tax_percentage);
          if (!isNaN(v)) {
            vat = (Math.round(v * 100) / 100).toFixed(2) + '%';
          }
        }
        var vatExtra = '';
        if (e.tax_annotation) {
          vatExtra = '<div class="text-muted" style="font-size:11px; margin-top:2px;">' + alproEscHtml(e.tax_annotation) + '</div>';
        }

        var ids = [];
        if (e.billing_entry_id) ids.push('billing: ' + e.billing_entry_id);
        if (e.payment_id) ids.push('payment: ' + e.payment_id);
        var idsHtml = ids.length ? ('<div class="text-muted" style="font-size:11px; margin-top:2px;">' + alproEscHtml(ids.join(' · ')) + '</div>') : '';

        html += '<tr>';
        html += '  <td>' + date + '</td>';
        html += '  <td>' + type + idsHtml + '</td>';
        html += '  <td>' + offerHtml + '</td>';
        html += '  <td style="text-align:right;" class="' + cls + '">' + amtTxt + '</td>';
        html += '  <td style="text-align:right;">' + vat + vatExtra + '</td>';
        html += '</tr>';
      }
    }

    html += '  </tbody>';
    html += '</table>';
    html += '</div>';

    return html;
  }

  $(document).on('click', '.alpro-billing-details-toggle', function (e) {
    e.preventDefault();

    var $btn = $(this);
    var orderId = $btn.data('order-id') || '';
    if (!orderId) return;

    var $page = $btn.closest('.alpro-billing');
    var detailsUrl = $page.data('details-url') || '';
    if (!detailsUrl) return;

    var $tr = $btn.closest('tr');
    if ($tr.length === 0) return;

    // Zamknij inne otwarte szczegóły (żeby nie robić bałaganu na ekranie)
    $('.alpro-billing-details-row').not($tr.next('.alpro-billing-details-row')).remove();
    $('.alpro-billing-details-toggle .material-icons').text('expand_more');

    var $next = $tr.next('.alpro-billing-details-row');
    if ($next.length) {
      // toggle existing
      if ($next.is(':visible')) {
        $next.hide();
        $btn.find('.material-icons').text('expand_more');
      } else {
        $next.show();
        $btn.find('.material-icons').text('expand_less');
      }
      return;
    }

    var colspan = $tr.children('td').length || 1;
    var $detailsRow = $('<tr class="alpro-billing-details-row"><td colspan="' + colspan + '"><div class="alpro-billing-details-box"><div class="text-muted">Ładuję szczegóły…</div></div></td></tr>');
    $tr.after($detailsRow);
    $btn.find('.material-icons').text('expand_less');

    // parametry z formularza filtrów (konto / zakres dat)
    var $form = $page.find('form.alpro-filters-form').first();
    var accountId = $form.find('[name="id_allegropro_account"]').val() || '';
    var dateFrom = $form.find('[name="date_from"]').val() || '';
    var dateTo = $form.find('[name="date_to"]').val() || '';

    $.ajax({
      url: detailsUrl,
      method: 'POST',
      dataType: 'json',
      data: {
        id_allegropro_account: accountId,
        order_id: orderId,
        date_from: dateFrom,
        date_to: dateTo
      },
      timeout: 120000
    })
      .done(function (resp) {
        if (!resp || !resp.ok) {
          var err = (resp && resp.error) ? resp.error : 'Nie udało się pobrać szczegółów.';
          $detailsRow.find('.alpro-billing-details-box').html('<div class="alert alert-warning" style="margin:0;">' + alproEscHtml(err) + '</div>');
          return;
        }
        $detailsRow.find('.alpro-billing-details-box').html(alproRenderBillingDetails(resp));
      })
      .fail(function (xhr, statusText) {
        var msg = 'Błąd połączenia: ' + (statusText || '');
        if (xhr && xhr.responseText) {
          msg += ' · ' + String(xhr.responseText).substring(0, 200);
        }
        $detailsRow.find('.alpro-billing-details-box').html('<div class="alert alert-warning" style="margin:0;">' + alproEscHtml(msg) + '</div>');
      });
  });


  // =============================
  // Rozliczenie: szybki podgląd problemów (filtry) + szczegóły payment-operations
  // =============================

  function alproFmt2(n) {
    var x = parseFloat(n);
    if (isNaN(x)) x = 0;
    return x.toFixed(2);
  }

  function alproApplyReconFilter(filter) {
    // Podgląd problemów
    var $preview = $('#alproReconIssuesPreview');
    if ($preview.length) {
      var $rows = $preview.find('tbody tr[data-issue]');
      if (filter === 'all') {
        $rows.show();
      } else if (filter === 'issues') {
        $rows.each(function () {
          var $r = $(this);
          $r.toggle(($r.data('issue') || 0) == 1);
        });
      } else {
        $rows.each(function () {
          var $r = $(this);
          $r.toggle(String($r.data('issue-type') || '') === String(filter));
        });
      }
    }

    // Główna tabela – chowamy również rozwinięte wiersze payment_id
    var $main = $('#alproReconMainTable');
    if ($main.length) {
      $main.find('tr.alpro-payments-row').hide();
      $main.find('tbody tr[data-issue]').each(function () {
        var $r = $(this);
        if (filter === 'all') {
          $r.show();
        } else if (filter === 'issues') {
          $r.toggle(($r.data('issue') || 0) == 1);
        } else {
          $r.toggle(String($r.data('issue-type') || '') === String(filter));
        }
      });
    }
  }

  $(document).on('click', '.alpro-recon-filter', function (e) {
    e.preventDefault();
    var filter = $(this).data('filter') || 'all';

    // podświetlenie aktywnego filtra
    $('.alpro-recon-filter').removeClass('alpro-filter-active');
    $(this).addClass('alpro-filter-active');

    alproApplyReconFilter(String(filter));
  });

  function alproRenderReconPayOps(resp) {
    var rows = (resp && resp.rows) ? resp.rows : [];
    var sums = (resp && resp.sums) ? resp.sums : {};
    var cur = (rows[0] && rows[0].currency) ? rows[0].currency : 'PLN';

    var html = '';

    html += '<div style="margin-bottom:8px;">';
    html += '  <strong>Podsumowanie</strong>';
    html += '  <div class="text-muted" style="font-size:12px;">Cashflow = W (WAITING) + A (AVAILABLE); Saldo = Cashflow − Opłaty + Zwroty opłat</div>';
    html += '</div>';

    html += '<div class="alpro-payops-summary">';
    html += '  <div><span class="text-muted">W:</span> ' + alproFmt2(sums.cashflow_waiting) + ' ' + alproEscHtml(cur) + '</div>';
    html += '  <div><span class="text-muted">A:</span> ' + alproFmt2(sums.cashflow_available) + ' ' + alproEscHtml(cur) + '</div>';
    html += '  <div><span class="text-muted">Opłaty:</span> <span class="text-danger">' + alproFmt2(sums.fee_deduction) + '</span> ' + alproEscHtml(cur) + '</div>';
    html += '  <div><span class="text-muted">Zwroty opłat:</span> <span class="text-success">' + alproFmt2(sums.fee_refund) + '</span> ' + alproEscHtml(cur) + '</div>';
    html += '  <div><span class="text-muted">Saldo:</span> <strong>' + alproFmt2(sums.net) + '</strong> ' + alproEscHtml(cur) + '</div>';
    html += '</div>';

    html += '<div class="table-responsive" style="margin-top:10px;">';
    html += '<table class="table table-sm">';
    html += '  <thead><tr>';
    html += '    <th>occurred_at</th>';
    html += '    <th>group</th>';
    html += '    <th>type</th>';
    html += '    <th>wallet</th>';
    html += '    <th style="text-align:right;">kwota</th>';
    html += '  </tr></thead>';
    html += '  <tbody>';

    if (!rows.length) {
      html += '<tr><td colspan="5" class="text-muted">Brak operacji w cache dla tego payment_id.</td></tr>';
    } else {
      for (var i = 0; i < rows.length; i++) {
        var r = rows[i] || {};
        var amt = parseFloat(r.amount);
        if (isNaN(amt)) amt = 0;

        var cls = '';
        if (amt < 0) cls = 'text-danger';
        else if (amt > 0) cls = 'text-success';

        html += '<tr>';
        html += '  <td>' + alproEscHtml(String(r.occurred_at || '')) + '</td>';
        html += '  <td><code>' + alproEscHtml(String(r.op_group || '')) + '</code></td>';
        html += '  <td><code>' + alproEscHtml(String(r.op_type || '')) + '</code></td>';
        html += '  <td><code>' + alproEscHtml(String(r.wallet_type || '')) + '</code></td>';
        html += '  <td style="text-align:right;"><span class="' + cls + '">' + alproFmt2(amt) + '</span> ' + alproEscHtml(String(r.currency || cur)) + '</td>';
        html += '</tr>';
      }
    }

    html += '  </tbody>';
    html += '</table>';
    html += '</div>';

    html += '<div class="text-muted" style="font-size:12px; margin-top:6px;">';
    html += 'Wskazówka: jeśli widzisz opłatę i zwrot tej samej kwoty – to korekta/rabat i koszt netto wynosi 0.';
    html += '</div>';

    return html;
  }

  $(document).on('click', '.alpro-toggle-payops', function (e) {
    e.preventDefault();

    var $a = $(this);
    var paymentId = $a.data('payment-id') || '';
    var targetSel = $a.data('target') || '';
    var ajaxUrl = $a.data('ajax-url') || '';

    if (!paymentId || !targetSel || !ajaxUrl) return;

    var $row = $(String(targetSel));
    if (!$row.length) return;

    // toggle
    if ($row.is(':visible')) {
      $row.hide();
      return;
    }
    $row.show();

    if ($row.data('loaded') == 1) {
      return;
    }

    var $box = $row.find('.alpro-payops-box');
    if (!$box.length) {
      $box = $row;
    }

    $box.html('<div class="text-muted" style="font-size:12px;">Ładuję operacje…</div>');

    // konto bierzemy z formularza filtrów (jeśli istnieje) albo z pierwszego selecta
    var accountId = '';
    var $form = $('form.alpro-filters-form').first();
    if ($form.length) {
      accountId = $form.find('[name="id_allegropro_account"]').val() || '';
    }
    if (!accountId) {
      accountId = $('[name="id_allegropro_account"]').first().val() || '';
    }

    $.ajax({
      url: ajaxUrl,
      method: 'POST',
      dataType: 'json',
      data: {
        id_allegropro_account: accountId,
        payment_id: paymentId
      },
      timeout: 120000
    })
      .done(function (resp) {
        if (!resp || !resp.ok) {
          var err = (resp && resp.error) ? resp.error : 'Nie udało się pobrać operacji.';
          $box.html('<div class="alert alert-warning" style="margin:0;">' + alproEscHtml(err) + '</div>');
          return;
        }
        $row.data('loaded', 1);
        $box.html(alproRenderReconPayOps(resp));
      })
      .fail(function (xhr, statusText) {
        var msg = 'Błąd połączenia: ' + (statusText || '');
        if (xhr && xhr.responseText) {
          msg += ' · ' + String(xhr.responseText).substring(0, 200);
        }
        $box.html('<div class="alert alert-warning" style="margin:0;">' + alproEscHtml(msg) + '</div>');
      });
  });


  // Rozliczenia: rozwiń szczegóły okna (opłaty / inne operacje)
  $(document).on('click', '.alpro-pc-toggle', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var target = $btn.data('target');
    if (!target) return;
    var $row = $('#' + target);
    if (!$row.length) return;

    var isOpen = $row.is(':visible');
    if (isOpen) {
      $row.hide();
      $btn.removeClass('is-open');
      $btn.find('i.material-icons').text('unfold_more');
      $btn.find('span').text('Rozwiń');
    } else {
      $row.show();
      $btn.addClass('is-open');
      $btn.find('i.material-icons').text('unfold_less');
      $btn.find('span').text('Zwiń');
    }
  });


})(window.jQuery);
