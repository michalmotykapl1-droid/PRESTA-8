<script>
(function($){
  var RefreshStoredOrders = {
    token: IMPORT_CFG.token,
    controller: 'AdminAllegroProOrders',
    total: 0,
    processed: 0,

    log: function(msg, type) {
      var color = '#94a3b8';
      if (type === 'error') color = '#f87171';
      if (type === 'success') color = '#4ade80';
      if (type === 'warning') color = '#facc15';

      var time = new Date().toLocaleTimeString();
      var line = $('<div/>').css('color', color).text('[' + time + '] ' + msg);
      var box = $('#refresh_orders_logs');
      box.append(line);
      box.scrollTop(box[0].scrollHeight);
    },

    setBar: function(selector, current, total) {
      var percent = total > 0 ? Math.round((current / total) * 100) : 0;
      if (percent > 100) percent = 100;
      $(selector).css('width', percent + '%').text(percent + '%');
    },

    getBatchSize: function() {
      var val = parseInt($('#refresh_batch_size').val(), 10);
      if (isNaN(val) || val < 1) val = 25;
      if (val > 200) val = 200;
      $('#refresh_batch_size').val(val);
      return val;
    },

    start: function() {
      var accountId = parseInt($('#refresh_account_id').val(), 10);
      if (!accountId) {
        this.log('Wybierz konto Allegro.', 'error');
        return;
      }

      this.total = 0;
      this.processed = 0;
      $('#refresh_orders_logs').html('');
      $('#refresh_batch_status').removeClass('label-success label-danger').addClass('label-warning').text('Pobieranie listy...');
      $('#refresh_update_status').removeClass('label-success label-danger').addClass('label-warning').text('Oczekuje...');
      this.setBar('#refresh_batch_bar', 0, 100);
      this.setBar('#refresh_update_bar', 0, 100);
      this.log('Start aktualizacji dla konta ID ' + accountId + '.', 'warning');

      this.processBatch(accountId, 0);
    },

    processBatch: function(accountId, offset) {
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
            this.log('Nie udało się pobrać partii zamówień.', 'error');
            $('#refresh_batch_status').removeClass('label-warning').addClass('label-danger').text('Błąd');
            return;
          }

          this.total = parseInt(res.total || 0, 10);
          this.setBar('#refresh_batch_bar', Math.min(res.next_offset || 0, this.total), this.total || 1);
          $('#refresh_batch_status').text('Partia offset ' + (res.offset || 0) + ', rekordów: ' + (res.ids || []).length);

          if (!this.total) {
            this.log('Brak zapisanych zamówień do aktualizacji dla wybranego konta.', 'warning');
            $('#refresh_batch_status').removeClass('label-warning').addClass('label-success').text('Brak danych');
            $('#refresh_update_status').removeClass('label-warning').addClass('label-success').text('Zakończono');
            this.setBar('#refresh_batch_bar', 100, 100);
            this.setBar('#refresh_update_bar', 100, 100);
            return;
          }

          var ids = res.ids || [];
          if (!ids.length) {
            this.finish();
            return;
          }

          this.log('Przetwarzam partię: ' + ids.length + ' zamówień.', 'warning');
          this.processIds(ids, 0, () => {
            if (res.has_more) {
              this.processBatch(accountId, res.next_offset || (offset + ids.length));
            } else {
              this.finish();
            }
          });
        },
        error: () => {
          this.log('Błąd połączenia podczas pobierania partii.', 'error');
          $('#refresh_batch_status').removeClass('label-warning').addClass('label-danger').text('Błąd połączenia');
        }
      });
    },

    processIds: function(ids, idx, done) {
      if (idx >= ids.length) {
        done();
        return;
      }

      $('#refresh_update_status').text('Aktualizacja... (' + (this.processed + 1) + '/' + this.total + ')');
      var cfId = ids[idx];

      $.ajax({
        url: 'index.php?controller=' + this.controller + '&token=' + this.token + '&action=import_process_single&ajax=1',
        type: 'POST',
        dataType: 'json',
        data: { checkout_form_id: cfId, step: 'fix' },
        success: (res) => {
          if (!res || !res.success) {
            this.log('Błąd aktualizacji ' + cfId + (res && res.message ? ': ' + res.message : ''), 'error');
          }

          this.processed += 1;
          this.setBar('#refresh_update_bar', this.processed, this.total || 1);
          this.processIds(ids, idx + 1, done);
        },
        error: () => {
          this.log('Błąd połączenia dla zamówienia ' + cfId + '.', 'error');
          this.processed += 1;
          this.setBar('#refresh_update_bar', this.processed, this.total || 1);
          this.processIds(ids, idx + 1, done);
        }
      });
    },

    finish: function() {
      $('#refresh_batch_status').removeClass('label-warning label-danger').addClass('label-success').text('Zakończono');
      $('#refresh_update_status').removeClass('label-warning label-danger').addClass('label-success').text('Zakończono');
      this.setBar('#refresh_batch_bar', 100, 100);
      this.setBar('#refresh_update_bar', this.total, this.total || 1);
      this.log('Aktualizacja zakończona. Przetworzone: ' + this.processed + ' / ' + this.total + '.', 'success');
    }
  };

  $(document).ready(function(){
    $('#btnStartRefreshStoredOrders').on('click', function(){
      RefreshStoredOrders.start();
    });
  });
})(jQuery);
</script>
