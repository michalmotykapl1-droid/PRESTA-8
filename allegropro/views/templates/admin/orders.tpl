<div class="panel">
  <h3><i class="icon icon-shopping-cart"></i> Zamówienia (Baza danych modułu)</h3>

  <div class="row" style="margin-bottom:20px;">
      <div class="col-md-12">
          <div class="alert alert-info">
              <strong>Konsola Importu:</strong> Użyj przycisku poniżej, aby pobrać i przetworzyć zamówienia w trybie bezpiecznym (krok po kroku).
          </div>
          <button class="btn btn-primary btn-lg btn-block" id="btnOpenImportModal">
               <i class="icon icon-cloud-download"></i> OTWÓRZ KONSOLĘ IMPORTU / AKTUALIZACJI
          </button>
      </div>
  </div>

  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Data Allegro</th>
        <th>Status</th>
        <th>Dostawa</th>
        <th>Kupujący</th>
        <th>Kwota</th>
        <th>ID Zamówienia</th>
        <th class="text-center">Akcje</th>
      </tr>
    </thead>
    <tbody>
      {if empty($allegropro_orders)}
        <tr><td colspan="7" class="text-center text-muted" style="padding:20px;">Brak danych. Użyj konsoli importu.</td></tr>
      {else}
        {foreach from=$allegropro_orders item=o}
          <tr>
            <td>{$o.updated_at_allegro|escape:'htmlall':'UTF-8'}</td>
            <td><span class="label label-info">{$o.status|escape:'htmlall':'UTF-8'}</span></td>
            <td style="font-size:11px;">
                {if isset($o.shipping_method_name) && $o.shipping_method_name}<strong>{$o.shipping_method_name|truncate:40:'...'}</strong>{else}<span class="text-muted">-</span>{/if}
            </td>
            <td>
                <strong>{$o.buyer_login|escape:'htmlall':'UTF-8'}</strong><br>
                <small>{$o.buyer_email|escape:'htmlall':'UTF-8'}</small>
            </td>
            <td><strong>{$o.total_amount|string_format:"%.2f"} {$o.currency}</strong></td>
            <td><code style="font-size:10px;">{$o.checkout_form_id}</code></td>
            <td class="text-center">
                 <button class="btn btn-default btn-sm btn-details" data-id="{$o.checkout_form_id}">
                    <i class="icon icon-list"></i> Szczegóły
                </button>
            </td>
          </tr>
          <tr id="details-{$o.checkout_form_id}" style="display:none; background-color:#f9f9f9;">
            <td colspan="7">
                <div class="details-content" style="padding:10px 20px;">
                    <i class="icon icon-spinner icon-spin"></i> Ładowanie produktów...
                </div>
            </td>
          </tr>
        {/foreach}
      {/if}
    </tbody>
  </table>
</div>

<div id="importModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:#fff; width:800px; margin:50px auto; padding:20px; border-radius:5px; box-shadow:0 0 20px rgba(0,0,0,0.5);">
        
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">
            <i class="icon icon-refresh"></i> Konsola Importu Allegro
            <button type="button" class="close" onclick="$('#importModal').hide(); location.reload();">&times;</button>
        </h3>

        <div id="step_config">
            <div class="form-group">
                <label>Zakres operacji:</label>
                <select id="import_scope" class="form-control">
                    <option value="recent">Pobierz Ostatnie (Standard)</option>
                    <option value="history">Pobierz Historię (Rozszerzone)</option>
                </select>
            </div>
            <button class="btn btn-success btn-lg" id="btnStartProcess">
                ROZPOCZNIJ PROCES
            </button>
        </div>

        <div id="step_progress" style="display:none;">
            
            <div class="row" style="margin-bottom:10px;">
                <div class="col-md-4"><strong>Faza 1: Pobieranie z Allegro</strong></div>
                <div class="col-md-8"><span id="status_fetch" class="label label-default">Oczekuje...</span></div>
            </div>
            <div class="progress" style="height:15px; margin-bottom:15px;"><div id="bar_fetch" class="progress-bar progress-bar-info" style="width:0%;">0%</div></div>

            <div class="row" style="margin-bottom:10px;">
                <div class="col-md-4"><strong>Faza 2: Tworzenie Zamówień</strong></div>
                <div class="col-md-8"><span id="status_create" class="label label-default">Oczekuje...</span></div>
            </div>
            <div class="progress" style="height:15px; margin-bottom:15px;"><div id="bar_create" class="progress-bar progress-bar-warning" style="width:0%;">0%</div></div>

            <div class="row" style="margin-bottom:10px;">
                <div class="col-md-4"><strong>Faza 3: Aktualizacja Cen/Wysyłki</strong></div>
                <div class="col-md-8"><span id="status_fix" class="label label-default">Oczekuje...</span></div>
            </div>
            <div class="progress" style="height:15px; margin-bottom:15px;"><div id="bar_fix" class="progress-bar progress-bar-success" style="width:0%;">0%</div></div>

            <div style="background:#222; color:#0f0; font-family:monospace; height:250px; overflow-y:scroll; padding:10px; border:1px solid #ccc; font-size:11px;" id="console_logs">
                <div class="text-muted">Gotowy do pracy...</div>
            </div>

            <div style="margin-top:15px; text-align:right;">
                <button class="btn btn-default" id="btnCloseComplete" style="display:none;" onclick="location.reload()">ZAMKNIJ I ODŚWIEŻ</button>
            </div>
        </div>

    </div>
</div>

<script>
var ImportManager = {
    token: '{$token|escape:'htmlall':'UTF-8'}',
    controller: 'AdminAllegroProOrders',
    
    log: function(msg, type='info') {
        var color = '#ccc';
        if(type==='error') color = '#ff5555';
        if(type==='success') color = '#55ff55';
        if(type==='warning') color = '#ffff55';
        
        var time = new Date().toLocaleTimeString();
        var html = '<div style="color:'+color+'">['+time+'] '+msg+'</div>';
        var box = $('#console_logs');
        box.append(html);
        box.scrollTop(box[0].scrollHeight);
    },

    start: function() {
        $('#step_config').hide();
        $('#step_progress').show();
        this.fetchOrdersFromAllegro();
    },

    // KROK 1: Pobierz JSON
    fetchOrdersFromAllegro: function() {
        var scope = $('#import_scope').val();
        this.log('>>> START FAZA 1: POBIERANIE', 'info');
        $('#status_fetch').removeClass('label-default').addClass('label-warning').text('Pobieranie...');
        $('#bar_fetch').css('width', '50%');

        $.ajax({
            url: 'index.php?controller='+this.controller+'&token='+this.token+'&action=import_fetch&ajax=1',
            type: 'POST',
            data: { scope: scope },
            dataType: 'json',
            success: (res) => {
                if(res.success) {
                    $('#bar_fetch').css('width', '100%').text('100%');
                    $('#status_fetch').removeClass('label-warning').addClass('label-success').text('OK ('+res.count+')');
                    this.log('Pobrano: ' + res.count + ' zamówień.', 'success');
                    this.getPendingOrders();
                } else {
                    this.log('Błąd: ' + res.message, 'error');
                }
            },
            error: (err) => this.log('Błąd połączenia (Faza 1)', 'error')
        });
    },

    // KROK 2: Lista ID
    getPendingOrders: function() {
        $.ajax({
            url: 'index.php?controller='+this.controller+'&token='+this.token+'&action=import_get_pending&ajax=1',
            type: 'POST',
            dataType: 'json',
            success: (res) => {
                if(res.success) {
                    var list = res.ids;
                    this.log('Znaleziono ' + list.length + ' do przetworzenia.', 'warning');
                    // START FAZY 2: TWORZENIE
                    this.processQueueCreate(list, 0);
                } else {
                    this.log('Błąd listy: ' + res.message, 'error');
                }
            }
        });
    },

    // KROK 3: Pętla Tworzenia (step=create)
    processQueueCreate: function(list, index) {
        var total = list.length;
        if (index === 0) {
            this.log('>>> START FAZA 2: TWORZENIE ZAMÓWIEŃ', 'info');
            $('#status_create').addClass('label-warning').text('Przetwarzanie...');
        }
        
        // --- POPRAWKA: Obliczanie procentów ---
        var percent = 0;
        if (total > 0) {
            percent = Math.round(((index) / total) * 100);
        }
        $('#bar_create').css('width', percent + '%').text(percent + '%');
        // --------------------------------------

        if (index >= total) {
            $('#bar_create').css('width', '100%').text('100%');
            $('#status_create').removeClass('label-warning').addClass('label-success').text('Zakończono');
            // PRZEJŚCIE DO FAZY 3
            this.processQueueFix(list, 0);
            return;
        }

        var cfId = list[index];

        $.ajax({
            url: 'index.php?controller='+this.controller+'&token='+this.token+'&action=import_process_single&ajax=1',
            type: 'POST',
            data: { checkout_form_id: cfId, step: 'create' }, // STEP CREATE
            dataType: 'json',
            success: (res) => {
                if(res.success) {
                    if(res.action == 'created') this.log('Utworzono ID: ' + res.id_order, 'success');
                    else if(res.action == 'skipped') this.log('Pominięto (istnieje): ' + res.id_order);
                } else {
                    this.log('Błąd ID ' + cfId + ': ' + res.message, 'error');
                }
                this.processQueueCreate(list, index + 1);
            },
            error: () => this.processQueueCreate(list, index + 1)
        });
    },

    // KROK 4: Pętla Naprawcza (step=fix)
    processQueueFix: function(list, index) {
        var total = list.length;
        if (index === 0) {
            this.log('>>> START FAZA 3: AKTUALIZACJA CEN (FIX)', 'info');
            $('#status_fix').addClass('label-warning').text('Aktualizowanie...');
        }

        // --- POPRAWKA: Obliczanie procentów ---
        var percent = 0;
        if (total > 0) {
            percent = Math.round(((index) / total) * 100);
        }
        $('#bar_fix').css('width', percent + '%').text(percent + '%');
        // --------------------------------------

        if (index >= total) {
            $('#bar_fix').css('width', '100%').text('100%');
            $('#status_fix').removeClass('label-warning').addClass('label-success').text('Zakończono');
            this.log('>>> CAŁY PROCES ZAKOŃCZONY!', 'success');
            $('#btnCloseComplete').show();
            return;
        }

        var cfId = list[index];

        $.ajax({
            url: 'index.php?controller='+this.controller+'&token='+this.token+'&action=import_process_single&ajax=1',
            type: 'POST',
            data: { checkout_form_id: cfId, step: 'fix' }, // STEP FIX
            dataType: 'json',
            success: (res) => {
                if(res.success) {
                    this.log('Zaktualizowano ID: ' + res.id_order, 'success');
                } else {
                    this.log('Błąd aktualizacji ' + cfId + ': ' + res.message, 'error');
                }
                this.processQueueFix(list, index + 1);
            },
            error: () => this.processQueueFix(list, index + 1)
        });
    }
};

$(document).ready(function() {
    $('#btnOpenImportModal').click(function() { $('#importModal').show(); });
    $('#btnStartProcess').click(function() { ImportManager.start(); });
    
    // Logika szczegółów
    $('.btn-details').click(function(e) {
        e.preventDefault();
        var cfId = $(this).data('id');
        var row = $('#details-'+cfId);
        if(row.is(':visible')) row.hide();
        else {
            row.show();
            var url = '{$admin_link|escape:'javascript':'UTF-8'}&action=get_order_details&checkout_form_id=' + cfId;
            fetch(url).then(r=>r.json()).then(data=>{
                var html = '<table class="table" style="background:#fff;"><thead><tr><th>Nazwa</th><th>SKU</th><th>EAN</th><th>Ilość</th><th>Cena</th><th>Mapowanie</th></tr></thead><tbody>';
                if (data.length === 0) html += '<tr><td colspan="6" class="text-danger">BRAK DANYCH</td></tr>';
                else {
                    data.forEach(function(item) {
                        var matchInfo = item.id_product > 0 ? '<span class="label label-success">OK (ID: '+item.id_product+')</span>' : '<span class="label label-danger">BRAK</span>';
                        html += '<tr><td>' + item.name + '</td><td>' + (item.reference_number || '-') + '</td><td>' + (item.ean || '-') + '</td><td><strong>' + item.quantity + ' szt.</strong></td><td>' + item.price + '</td><td>' + matchInfo + '</td></tr>';
                    });
                }
                html += '</tbody></table>';
                row.find('.details-content').html(html);
            });
        }
    });
});
</script>