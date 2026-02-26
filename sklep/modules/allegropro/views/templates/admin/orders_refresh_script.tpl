<script>
(function($){
  var RefreshStoredOrders = {
    token: IMPORT_CFG.token,
    controller: 'AdminAllegroProOrders',
    total: 0,
    processed: 0,
    stats: null,
    running: false,

    resetStats: function() {
      this.stats = {
        stage0Checked: 0,
        stage0Reassigned: 0,
        stage0PrestaLinked: 0,
        stage0Unresolved: 0,
        stage0Ids: [],
        stage0PrestaLinkedIds: [],
        stage1Deleted: 0,
        stage1DeletedIds: [],
        ok: 0,
        fixed: 0,
        cancelled: 0,
        skipped: 0,
        stage2Changed: 0,
        stage2ChangedIds: [],
        errors: 0,
        errorIds: []
      };
    },

    log: function(msg, type) {
      var color = '#94a3b8';
      if (type === 'error') color = '#f87171';
      if (type === 'success') color = '#4ade80';
      if (type === 'warning') color = '#facc15';
      if (type === 'info') color = '#7dd3fc';

      var time = new Date().toLocaleTimeString();
      var line = $('<div/>').css('color', color).text('[' + time + '] ' + msg);
      var box = $('#refresh_orders_logs');
      box.append(line);
      box.scrollTop(box[0].scrollHeight);
    },

    setBar: function(selector, current, total) {
      var safeTotal = total > 0 ? total : 1;
      var percent = Math.round((current / safeTotal) * 100);
      if (percent < 0) percent = 0;
      if (percent > 100) percent = 100;
      $(selector).css('width', percent + '%').text(percent + '%');
    },

    setReport: function(html) {
      $('#refresh_orders_report').html(html);
    },

    setRunning: function(isRunning) {
      this.running = !!isRunning;
      var btn = $('#btnStartRefreshStoredOrders');
      if (this.running) {
        btn.prop('disabled', true).html('<i class="icon icon-refresh icon-spin"></i> Trwa aktualizacja...');
      } else {
        btn.prop('disabled', false).html('<i class="icon icon-play"></i> Aktualizuj pobrane zamówienia');
      }
    },

    setAccountSelectionLocked: function(locked) {
      var isLocked = !!locked;
      var select = $('#refresh_account_id');
      select.prop('disabled', isLocked);
      $('#refresh_account_lock_hint').toggle(isLocked);
    },

    getBatchSize: function() {
      var val = parseInt($('#refresh_batch_size').val(), 10);
      if (isNaN(val) || val < 1) val = 25;
      if (val > 200) val = 200;
      $('#refresh_batch_size').val(val);
      return val;
    },

    isOnlyLegacyMode: function() {
      return $('#refresh_only_legacy_reassign').is(':checked');
    },

    initUI: function() {
      this.total = 0;
      this.processed = 0;
      this.resetStats();
      this.setRunning(false);
      this.setAccountSelectionLocked(this.isOnlyLegacyMode());
      $('#refresh_runtime_section').hide();
    },

    start: function() {
      if (this.running) {
        return;
      }

      var onlyLegacyMode = this.isOnlyLegacyMode();
      var accountId = parseInt($('#refresh_account_id').val(), 10);
      if (!onlyLegacyMode && !accountId) {
        alert('Wybierz konto Allegro.');
        return;
      }

      this.total = 0;
      this.processed = 0;
      this.resetStats();
      $('#refresh_orders_logs').html('');

      $('#refresh_runtime_section').show();
      this.setRunning(true);

      $('#refresh_batch_status').removeClass('label-default label-success label-danger').addClass('label-warning').text('Etap 0/3: reasocjacja rekordów legacy...');
      $('#refresh_update_status').removeClass('label-default label-success label-danger').addClass('label-warning').text('Oczekiwanie na etap aktualizacji...');
      this.setBar('#refresh_batch_bar', 0, 100);
      this.setBar('#refresh_update_bar', 0, 100);

      this.setReport('<strong>Raport (w trakcie):</strong> start procesu 3-etapowego.');
      this.log(onlyLegacyMode
        ? 'Start aktualizacji w trybie legacy: skanowanie wszystkich aktywnych kont Allegro.'
        : ('Start aktualizacji dla konta ID ' + accountId + '.'), 'warning');

      this.runStage0Reassign(accountId);
    },

    runStage0Reassign: function(accountId) {
      this.log('ETAP 0/3: sprawdzam rekordy z nieużywanych ID kont i przypisuję je do aktywnego konta...', 'info');

      $.ajax({
        url: 'index.php?controller=' + this.controller + '&token=' + this.token + '&action=refresh_reassign_legacy_account_orders&ajax=1',
        type: 'POST',
        dataType: 'json',
        data: {
          id_allegropro_account: accountId,
          only_legacy_mode: this.isOnlyLegacyMode() ? 1 : 0
        },
        success: (res) => {
          if (!res || !res.success) {
            this.log('ETAP 0/3: błąd reasocjacji rekordów legacy.', 'error');
            this.setReport('<strong>Raport:</strong> przerwano na ETAPIE 0 (reasocjacja).');
            $('#refresh_batch_status').removeClass('label-warning').addClass('label-danger').text('Błąd ETAPU 0');
            this.setRunning(false);
            return;
          }

          this.stats.stage0Checked = parseInt(res.checked || 0, 10);
          this.stats.stage0Reassigned = parseInt(res.reassigned_count || 0, 10);
          this.stats.stage0PrestaLinked = parseInt(res.presta_linked_count || 0, 10);
          this.stats.stage0Unresolved = parseInt(res.unresolved_count || 0, 10);
          this.stats.stage0Ids = Array.isArray(res.reassigned_ids) ? res.reassigned_ids : [];
          this.stats.stage0PrestaLinkedIds = Array.isArray(res.presta_linked_ids) ? res.presta_linked_ids : [];

          this.log(
            'ETAP 0/3: sprawdzono ' + this.stats.stage0Checked
            + ', przypisano ponownie ' + this.stats.stage0Reassigned
            + ', nierozpoznane: ' + this.stats.stage0Unresolved
            + ', podpięto ID zamówienia Presta: ' + this.stats.stage0PrestaLinked + '.',
            this.stats.stage0Reassigned > 0 ? 'warning' : 'success'
          );

          if (this.stats.stage0PrestaLinkedIds.length) {
            this.log('ETAP 0/3: lista podpiętych zamówień Presta: ' + this.stats.stage0PrestaLinkedIds.join(', '), 'info');
          }

          this.setReport(
            '<strong>Raport (po ETAPIE 0):</strong> sprawdzono <strong>' + this.stats.stage0Checked + '</strong>, '
            + 'przypisano ponownie <strong>' + this.stats.stage0Reassigned + '</strong>, '
            + 'nierozpoznane <strong>' + this.stats.stage0Unresolved + '</strong>, '
            + 'uzupełniono ID zamówień Presta <strong>' + this.stats.stage0PrestaLinked + '</strong>.'
          );

          if (this.isOnlyLegacyMode()) {
            $('#refresh_batch_status').removeClass('label-warning label-danger').addClass('label-success').text('Tryb tylko legacy: zakończono ETAP 0');
            $('#refresh_update_status').removeClass('label-warning label-danger').addClass('label-success').text('Tryb tylko legacy: pominięto ETAP 1 i ETAP 2');
            this.setBar('#refresh_batch_bar', 100, 100);
            this.setBar('#refresh_update_bar', 100, 100);
            this.finishOnlyLegacy();
            return;
          }

          $('#refresh_batch_status').text('Etap 1/3: czyszczenie rekordów osieroconych...');
          this.runStage1Cleanup(accountId);
        },
        error: (xhr) => {
          var backendMsg = '';
          if (xhr && xhr.responseText) {
            try {
              var parsed = JSON.parse(xhr.responseText);
              backendMsg = parsed && parsed.message ? String(parsed.message) : '';
            } catch (e) {
              backendMsg = String(xhr.responseText).replace(/<[^>]*>/g, '').trim().slice(0, 240);
            }
          }

          this.log('ETAP 0/3: błąd połączenia podczas reasocjacji.' + (backendMsg ? ' Szczegóły: ' + backendMsg : ''), 'error');
          this.setReport('<strong>Raport:</strong> przerwano z powodu błędu połączenia w ETAPIE 0.' + (backendMsg ? ' <br><span style="color:#b91c1c;">' + backendMsg + '</span>' : ''));
          $('#refresh_batch_status').removeClass('label-warning').addClass('label-danger').text('Błąd połączenia ETAP 0');
          this.setRunning(false);
        }
      });
    },

    runStage1Cleanup: function(accountId) {
      this.log('ETAP 1/3: sprawdzam i usuwam rekordy z id_order_prestashop = 0 ...', 'info');

      $.ajax({
        url: 'index.php?controller=' + this.controller + '&token=' + this.token + '&action=refresh_cleanup_orphans&ajax=1',
        type: 'POST',
        dataType: 'json',
        data: {
          id_allegropro_account: accountId,
          only_legacy_mode: this.isOnlyLegacyMode() ? 1 : 0
        },
        success: (res) => {
          if (!res || !res.success) {
            this.log('ETAP 1/3: błąd czyszczenia rekordów osieroconych.', 'error');
            this.setReport('<strong>Raport:</strong> przerwano na ETAPIE 1 (cleanup).');
            $('#refresh_batch_status').removeClass('label-warning').addClass('label-danger').text('Błąd ETAPU 1');
            this.setRunning(false);
            return;
          }

          this.stats.stage1Deleted = parseInt(res.deleted_count || 0, 10);
          this.stats.stage1DeletedIds = Array.isArray(res.ids) ? res.ids : [];
          this.setBar('#refresh_batch_bar', 100, 100);
          this.log('ETAP 1/3: usunięto rekordów osieroconych: ' + this.stats.stage1Deleted + '.', this.stats.stage1Deleted > 0 ? 'warning' : 'success');

          $('#refresh_batch_status').removeClass('label-warning').addClass('label-success').text('Etap 1/3 zakończony');
          $('#refresh_update_status').removeClass('label-success label-danger label-default').addClass('label-warning').text('Etap 2/3: pobieranie partii...');
          this.setReport(
            '<strong>Raport (po ETAPIE 1):</strong> ETAP0 przypisał <strong>' + this.stats.stage0Reassigned + '</strong>, '
            + 'ETAP1 usunął <strong>' + this.stats.stage1Deleted + '</strong> rekordów z id_order_prestashop=0. '
            + 'Przechodzę do ETAPU 2 (aktualizacja istniejących zamówień).'
          );

          this.runStage2Refresh(accountId, 0);
        },
        error: (xhr) => {
          var backendMsg = '';
          if (xhr && xhr.responseText) {
            try {
              var parsed = JSON.parse(xhr.responseText);
              backendMsg = parsed && parsed.message ? String(parsed.message) : '';
            } catch (e) {
              backendMsg = String(xhr.responseText).replace(/<[^>]*>/g, '').trim().slice(0, 240);
            }
          }

          this.log('ETAP 1/3: błąd połączenia podczas cleanup.' + (backendMsg ? ' Szczegóły: ' + backendMsg : ''), 'error');
          this.setReport('<strong>Raport:</strong> przerwano z powodu błędu połączenia w ETAPIE 1.' + (backendMsg ? ' <br><span style="color:#b91c1c;">' + backendMsg + '</span>' : ''));
          $('#refresh_batch_status').removeClass('label-warning').addClass('label-danger').text('Błąd połączenia ETAP 1');
          this.setRunning(false);
        }
      });
    },

    runStage2Refresh: function(accountId, offset) {
      var batchSize = this.getBatchSize();

      $.ajax({
        url: 'index.php?controller=' + this.controller + '&token=' + this.token + '&action=refresh_get_batch&ajax=1',
        type: 'POST',
        dataType: 'json',
        data: {
          id_allegropro_account: accountId,
          limit: batchSize,
          offset: offset
        },
        success: (res) => {
          if (!res || !res.success) {
            this.log('ETAP 2/3: Nie udało się pobrać partii zamówień.', 'error');
            $('#refresh_update_status').removeClass('label-warning').addClass('label-danger').text('Błąd ETAPU 2');
            this.setRunning(false);
            this.setReport('<strong>Raport:</strong> przerwano na ETAPIE 2 (pobieranie partii).');
            return;
          }

          this.total = parseInt(res.total || 0, 10);
          this.setBar('#refresh_update_bar', Math.min(res.next_offset || 0, this.total), this.total || 1);
          $('#refresh_update_status').text('Etap 2/3: offset ' + (res.offset || 0) + ', rekordów: ' + (res.ids || []).length);

          if (!this.total) {
            this.log('ETAP 2/3: Brak zapisanych zamówień do aktualizacji po cleanup.', 'warning');
            $('#refresh_update_status').removeClass('label-warning').addClass('label-success').text('Etap 2/3: brak danych');
            this.setBar('#refresh_update_bar', 100, 100);
            this.finish();
            return;
          }

          var ids = res.ids || [];
          if (!ids.length) {
            this.finish();
            return;
          }

          this.log('ETAP 2/3: Przetwarzam partię: ' + ids.length + ' zamówień.', 'info');
          this.processIds(ids, 0, () => {
            if (res.has_more) {
              this.runStage2Refresh(accountId, res.next_offset || (offset + ids.length));
            } else {
              this.finish();
            }
          });
        },
        error: () => {
          this.log('ETAP 2/3: Błąd połączenia podczas pobierania partii.', 'error');
          $('#refresh_update_status').removeClass('label-warning').addClass('label-danger').text('Błąd połączenia ETAP 2');
          this.setReport('<strong>Raport:</strong> przerwano z powodu błędu połączenia (ETAP 2).');
          this.setRunning(false);
        }
      });
    },

    processIds: function(ids, idx, done) {
      if (idx >= ids.length) {
        done();
        return;
      }

      $('#refresh_update_status').text('Etap 2/3: aktualizacja... (' + (this.processed + 1) + '/' + this.total + ')');
      var cfId = ids[idx];

      $.ajax({
        url: 'index.php?controller=' + this.controller + '&token=' + this.token + '&action=import_process_single&ajax=1',
        type: 'POST',
        dataType: 'json',
        data: { checkout_form_id: cfId, step: 'fix' },
        success: (res) => {
          if (!res || !res.success) {
            this.stats.errors += 1;
            this.stats.errorIds.push(cfId);
            this.log('Błąd aktualizacji ' + cfId + (res && res.message ? ': ' + res.message : ''), 'error');
          } else {
            this.stats.ok += 1;
            var action = String(res.action || '');
            if (action === 'fixed') {
              this.stats.fixed += 1;
              this.stats.stage2Changed += 1;
              this.stats.stage2ChangedIds.push(cfId + ' [naprawione]');
            } else if (action === 'cancelled') {
              this.stats.cancelled += 1;
              this.stats.stage2Changed += 1;
              this.stats.stage2ChangedIds.push(cfId + ' [anulowane]');
            } else {
              this.stats.skipped += 1;
            }
          }

          this.processed += 1;
          this.setBar('#refresh_update_bar', this.processed, this.total || 1);

          this.setReport(
            '<strong>Raport (w trakcie):</strong> '
            + 'ETAP0 przypisano <strong>' + this.stats.stage0Reassigned + '</strong>, nierozpoznane <strong>' + this.stats.stage0Unresolved + '</strong>, '
            + 'ETAP1 usunięto <strong>' + this.stats.stage1Deleted + '</strong>, '
            + 'ETAP2 przetworzono <strong>' + this.processed + '/' + this.total + '</strong>, '
            + 'sukcesy <strong>' + this.stats.ok + '</strong>, '
            + 'błędy <strong>' + this.stats.errors + '</strong>.'
          );

          this.processIds(ids, idx + 1, done);
        },
        error: () => {
          this.stats.errors += 1;
          this.stats.errorIds.push(cfId);
          this.log('Błąd połączenia dla zamówienia ' + cfId + '.', 'error');
          this.processed += 1;
          this.setBar('#refresh_update_bar', this.processed, this.total || 1);
          this.processIds(ids, idx + 1, done);
        }
      });
    },


    finishOnlyLegacy: function() {
      var reassignedPreview = this.stats.stage0Ids.length
        ? '<br><span style="color:#1d4ed8;">Przypisane ponownie ID z ETAPU 0: ' + this.stats.stage0Ids.slice(0, 10).join(', ') + (this.stats.stage0Ids.length > 10 ? ' ...' : '') + '</span>'
        : '';

      var prestaLinkedPreview = this.stats.stage0PrestaLinkedIds.length
        ? '<br><span style="color:#0e7490;">Podpięte zamówienia Presta (ETAP 0): ' + this.stats.stage0PrestaLinkedIds.slice(0, 20).join(', ') + (this.stats.stage0PrestaLinkedIds.length > 20 ? ' ...' : '') + '</span>'
        : '';

      this.setReport(
        '<strong>Raport końcowy (tryb tylko legacy):</strong> '
        + '<br>ETAP 0 (reasocjacja legacy): sprawdzono <strong>' + this.stats.stage0Checked + '</strong>, przypisano ponownie <strong>' + this.stats.stage0Reassigned + '</strong>, nierozpoznane <strong>' + this.stats.stage0Unresolved + '</strong>, uzupełniono Presta ID <strong>' + this.stats.stage0PrestaLinked + '</strong>.'
        + '<br>ETAP 1 i ETAP 2 zostały pominięte zgodnie z zaznaczoną opcją.'
        + reassignedPreview
        + prestaLinkedPreview
      );

      this.log('Tryb tylko legacy zakończony. Sprawdzono: ' + this.stats.stage0Checked + ', przypisano: ' + this.stats.stage0Reassigned + ', nierozpoznane: ' + this.stats.stage0Unresolved + '.', 'success');
      this.setRunning(false);
    },

    finish: function() {
      $('#refresh_batch_status').removeClass('label-warning label-danger').addClass('label-success').text('Etap 1/3 zakończony');
      $('#refresh_update_status').removeClass('label-warning label-danger').addClass('label-success').text('Etap 2/3 zakończony');

      var errorsPreview = this.stats.errorIds.length
        ? '<br><span style="color:#b91c1c;">Błędne ID (ETAP 2): ' + this.stats.errorIds.slice(0, 10).join(', ') + (this.stats.errorIds.length > 10 ? ' ...' : '') + '</span>'
        : '';

      var removedPreview = this.stats.stage1DeletedIds.length
        ? '<br><span style="color:#92400e;">Usunięte ID z ETAPU 1: ' + this.stats.stage1DeletedIds.slice(0, 10).join(', ') + (this.stats.stage1DeletedIds.length > 10 ? ' ...' : '') + '</span>'
        : '';

      var reassignedPreview = this.stats.stage0Ids.length
        ? '<br><span style="color:#1d4ed8;">Przypisane ponownie ID z ETAPU 0: ' + this.stats.stage0Ids.slice(0, 10).join(', ') + (this.stats.stage0Ids.length > 10 ? ' ...' : '') + '</span>'
        : '';

      var prestaLinkedPreview = this.stats.stage0PrestaLinkedIds.length
        ? '<br><span style="color:#0e7490;">Podpięte zamówienia Presta (ETAP 0): ' + this.stats.stage0PrestaLinkedIds.slice(0, 20).join(', ') + (this.stats.stage0PrestaLinkedIds.length > 20 ? ' ...' : '') + '</span>'
        : '';

      var stage2ChangedPreview = this.stats.stage2ChangedIds.length
        ? '<br><span style="color:#166534;">Zmodyfikowane zamówienia (ETAP 2): ' + this.stats.stage2ChangedIds.slice(0, 20).join(', ') + (this.stats.stage2ChangedIds.length > 20 ? ' ...' : '') + '</span>'
        : '';

      this.setReport(
        '<strong>Raport końcowy (3 etapy):</strong> '
        + '<br>ETAP 0 (reasocjacja legacy): sprawdzono <strong>' + this.stats.stage0Checked + '</strong>, przypisano ponownie <strong>' + this.stats.stage0Reassigned + '</strong>, nierozpoznane <strong>' + this.stats.stage0Unresolved + '</strong>, uzupełniono Presta ID <strong>' + this.stats.stage0PrestaLinked + '</strong>.'
        + '<br>ETAP 1 (cleanup): usunięto rekordów z id_order_prestashop=0: <strong>' + this.stats.stage1Deleted + '</strong>.'
        + '<br>ETAP 2 (aktualizacja): przetworzono <strong>' + this.processed + '/' + this.total + '</strong>, '
        + 'sukcesy <strong>' + this.stats.ok + '</strong> '
        + '(naprawione: <strong>' + this.stats.fixed + '</strong>, anulowane: <strong>' + this.stats.cancelled + '</strong>, inne/pominięte: <strong>' + this.stats.skipped + '</strong>), '
        + 'zmienione rekordy <strong>' + this.stats.stage2Changed + '</strong>, '
        + 'błędy <strong>' + this.stats.errors + '</strong>.'
        + reassignedPreview
        + prestaLinkedPreview
        + removedPreview
        + stage2ChangedPreview
        + errorsPreview
      );

      this.log('Proces 3-etapowy zakończony. ETAP0 przypisano: ' + this.stats.stage0Reassigned + ' (nierozpoznane: ' + this.stats.stage0Unresolved + '), ETAP1 usunięto: ' + this.stats.stage1Deleted + ', ETAP2 przetworzono: ' + this.processed + ' / ' + this.total + '.', 'success');
      this.setRunning(false);
    }
  };

  $(document).ready(function(){
    RefreshStoredOrders.initUI();

    $('#refresh_only_legacy_reassign').on('change', function(){
      RefreshStoredOrders.setAccountSelectionLocked($(this).is(':checked'));
    });

    $('#btnStartRefreshStoredOrders').on('click', function(){
      RefreshStoredOrders.start();
    });
  });
})(jQuery);
</script>
