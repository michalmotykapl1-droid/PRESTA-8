<div class="card mt-2" id="allegropro_order_details">
    <div class="card-header">
        <h3 class="card-header-title">
            <i class="material-icons">shopping_cart</i>
            Allegro Pro - Szczegóły zamówienia ({$allegro_data.order.checkout_form_id})
        </h3>
    </div>
    <div class="card-body">
        
        <div class="row">
            {* 1. Statusy i Konto *}
            <div class="col-md-3">
                <div class="form-group mb-2">
                    <label class="text-muted">Status zamówienia (Sklep):</label>
                    <input type="text" class="form-control" value="{$allegro_data.ps_status_name|escape:'htmlall':'UTF-8'}" readonly style="background:#f8f9fa; font-weight:bold;">
                </div>
                <div class="form-group mb-3">
                    <label class="text-muted">Zmień status na Allegro:</label>
                    <div class="input-group">
                        <select id="allegropro_status_select" class="form-control custom-select">
                            <option value="">-- Wybierz akcję --</option>
                            {foreach from=$allegro_data.allegro_statuses key=k item=v}
                                <option value="{$k}">{$v}</option>
                            {/foreach}
                        </select>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="btnUpdateAllegroStatus">Zaktualizuj</button>
                        </div>
                    </div>
                    <small class="form-text text-muted" id="allegropro_status_msg"></small>
                </div>
                <p class="mb-1 text-muted">Konto Allegro:</p>
                <strong>{$allegro_data.order.account_label|default:'Nieznane'}</strong>
            </div>

            {* 2. Kupujący *}
            <div class="col-md-3">
                <h4 class="text-muted mb-2">Kupujący</h4>
                <div class="row mb-1">
                    <div class="col-4 text-muted">Login:</div>
                    <div class="col-8"><strong>{$allegro_data.buyer.login}</strong></div>
                </div>
                <div class="row mb-1">
                    <div class="col-4 text-muted">E-mail:</div>
                    <div class="col-8"><a href="mailto:{$allegro_data.buyer.email}">{$allegro_data.buyer.email}</a></div>
                </div>
                {if $allegro_data.invoice}
                    <div class="mt-2 alert alert-info p-2">
                        <strong>FAKTURA:</strong><br>
                        {$allegro_data.invoice.company_name}<br>
                        NIP: {$allegro_data.invoice.tax_id}
                    </div>
                {/if}
            </div>

            {* 3. WYSYŁKA - PANEL STEROWANIA *}
            <div class="col-md-6">
                <div class="card bg-light border-0">
                    <div class="card-body p-3">
                        <h4 class="text-muted mb-3">Zarządzanie Wysyłką</h4>
                        {if isset($allegro_data.shipments_sync)}
                            <div class="d-flex align-items-center justify-content-between mb-2" style="gap:8px;">
                                <div class="text-muted" style="font-size:11px;" id="ship_sync_msg">
                                    {if $allegro_data.shipments_sync.skipped}
                                        Synchronizacja przesyłek: odświeżone niedawno (TTL).
                                    {elseif $allegro_data.shipments_sync.ok}
                                        Synchronizacja przesyłek: zaktualizowano {$allegro_data.shipments_sync.synced|intval} rekordów.
                                    {else}
                                        Synchronizacja przesyłek: brak aktualizacji (użyto danych lokalnych).
                                    {/if}
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSyncShipmentsNow" title="Wymuś aktualizację przesyłek i numerów nadania z Allegro">
                                    <i class="material-icons" style="font-size:16px; vertical-align:middle;">refresh</i>
                                    Odśwież przesyłki
                                </button>
                            </div>
                            <div class="mb-2" style="font-size:12px;">
                                <label style="font-weight:normal; margin:0;">
                                    <input type="checkbox" id="sync_shipments_debug"> Tryb debug (pokaż szczegóły odpowiedzi API po odświeżeniu)
                                </label>
                            </div>
                            <div id="ship_sync_debug" class="alert alert-secondary" style="display:none; font-size:11px; white-space:pre-wrap; max-height:220px; overflow:auto;"></div>
                        {/if}
                        
                        {* BANER SMART *}
                        {if isset($allegro_data.shipping.is_smart) && $allegro_data.shipping.is_smart}
                             <div style="background-color: #ffece5; border-left: 5px solid #ff5a00; padding: 10px; margin-bottom: 15px; display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center;">
                                    <i class="material-icons" style="color: #ff5a00; font-size: 28px; margin-right: 12px;">local_shipping</i>
                                    <div>
                                        <strong style="color: #222; font-size: 14px; display:block;">ALLEGRO SMART!</strong>
                                        <span style="font-size:11px; color:#666;">Wysyłka opłacona przez Allegro.</span>
                                    </div>
                                </div>
                                <div style="text-align:right; background:white; padding:5px 10px; border-radius:4px; border:1px solid #ffdec2;">
                                    <div style="font-size:10px; color:#888; text-transform:uppercase;">Pozostało:</div>
                                    <div style="font-size:18px; font-weight:bold; color:{if $allegro_data.smart_left > 0}#28a745{else}#dc3545{/if};">
                                        {$allegro_data.smart_left} <span style="font-size:12px; color:#aaa;">/ {$allegro_data.smart_limit}</span>
                                    </div>
                                </div>
                            </div>
                        {/if}

                        <div class="mb-2"><strong>Metoda:</strong> {$allegro_data.shipping.method_name}</div>
                        {if $allegro_data.shipping.pickup_point_id}
                            <div class="mb-3 text-muted" style="font-size:12px;">
                                Punkt: <strong>{$allegro_data.shipping.pickup_point_id}</strong>
                            </div>
                        {/if}

                        {* KREATOR PRZESYŁKI *}
                        <div class="shipment-creator mb-3" style="border:1px solid #ddd; padding:10px; background:#fff; border-radius:4px;">
                            <h5 style="margin-top:0; font-size:13px; color:#555;">Nadaj nową paczkę:</h5>
                            <div class="row">
                                {if $allegro_data.carrier_mode == 'BOX'}
                                    <div class="col-sm-4"><button class="btn btn-outline-primary btn-block btn-create-shipment" data-size="A">Gabaryt <strong>A</strong></button></div>
                                    <div class="col-sm-4"><button class="btn btn-outline-primary btn-block btn-create-shipment" data-size="B">Gabaryt <strong>B</strong></button></div>
                                    <div class="col-sm-4"><button class="btn btn-outline-primary btn-block btn-create-shipment" data-size="C">Gabaryt <strong>C</strong></button></div>
                                {else}
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="courier_weight" placeholder="Waga (kg)" value="1.0">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary btn-create-shipment" data-mode="courier">Nadaj Kurierem</button>
                                            </div>
                                        </div>
                                    </div>
                                {/if}
                            </div>
                            {if isset($allegro_data.shipping.is_smart) && $allegro_data.shipping.is_smart}
                            <div class="mt-2">
                                <label style="font-weight:normal; font-size:12px;">
                                    <input type="checkbox" id="use_smart" {if $allegro_data.smart_left > 0}checked{/if}> Użyj Allegro Smart! (jeśli dostępny)
                                </label>
                            </div>
                            {/if}
                            <div id="creator_msg" class="mt-2 text-info" style="font-size:12px;"></div>
                        </div>

                        {* HISTORIA *}
                        <h5 style="font-size:13px;">Historia Przesyłek:</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped" style="font-size:11px; margin-bottom:0;">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Typ</th>
                                        <th>Data</th>
                                        <th>Nr nadania</th>
                                        <th class="text-right">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                {foreach from=$allegro_data.shipments item=ship}
                                    <tr>
                                        <td>
                                            {if $ship.status == 'CREATED'}<span class="badge badge-success">UTWORZONA</span>
                                            {elseif $ship.status == 'CANCELLED'}<span class="badge badge-secondary">ANULOWANA</span>
                                            {elseif $ship.status == 'NEW'}<span class="badge badge-info">W TOKU...</span>
                                            {else}<span class="badge badge-warning">{$ship.status}</span>{/if}
                                            
                                            {if $ship.is_smart}<br><span style="color:#ff5a00; font-weight:bold; font-size:9px;">SMART</span>{/if}
                                        </td>
                                        <td>{$ship.size_details}</td>
                                        <td>{$ship.created_at|date_format:"%H:%M %d-%m"}</td>
                                        <td>
                                            {if $ship.tracking_number}
                                                <code>{$ship.tracking_number|escape:'htmlall':'UTF-8'}</code>
                                            {else}
                                                <span class="text-muted">—</span>
                                            {/if}
                                        </td>
                                        <td class="text-right">
                                            {if $ship.status == 'CREATED'}
                                                <button class="btn btn-xs btn-default btn-get-label" data-id="{$ship.shipment_id}" title="Pobierz Etykietę"><i class="material-icons">print</i></button>
                                                <button class="btn btn-xs btn-danger btn-cancel-shipment" data-id="{$ship.shipment_id}" title="Anuluj przesyłkę w Allegro"><i class="material-icons">cancel</i></button>
                                            {/if}
                                        </td>
                                    </tr>
                                {foreachelse}
                                    <tr><td colspan="5" class="text-center text-muted">Brak wygenerowanych etykiet.</td></tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <hr>

        <h4 class="text-muted mb-3">Lista ofert (Allegro)</h4>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr class="bg-light">
                        <th>ID Oferty</th>
                        <th>Nazwa na aukcji</th>
                        <th>SKU / Sygnatura</th>
                        <th>EAN</th> 
                        <th class="text-center">Ilość</th>
                        <th class="text-right">Cena brutto</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$allegro_data.items item=item}
                    <tr>
                        <td>
                            <a href="https://allegro.pl/oferta/{$item.offer_id}" target="_blank">{$item.offer_id}</a>
                        </td>
                        <td>{$item.name}</td>
                        <td>{$item.reference_number|default:'-'}</td>
                        <td>
                            {if $item.ean}
                                <span class="badge badge-info" style="font-size:12px;">{$item.ean}</span>
                            {else}
                                <span class="text-muted">-</span>
                            {/if}
                        </td>
                        <td class="text-center"><strong>{$item.quantity}</strong></td>
                        <td class="text-right">{$item.price} zł</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var cfId = '{$allegro_data.order.checkout_form_id}';
    var accId = '{$allegro_data.order.id_allegropro_account}';

    // --- 1. Zmiana Statusu ---
    var btnStatus = document.getElementById('btnUpdateAllegroStatus');
    if(btnStatus){
        btnStatus.addEventListener('click', function(e) {
            e.preventDefault();
            var select = document.getElementById('allegropro_status_select');
            var msg = document.getElementById('allegropro_status_msg');
            var status = select.value;
            
            if (!status) { alert('Wybierz status z listy.'); return; }

            btnStatus.disabled = true;
            btnStatus.innerText = 'Wysyłanie...';
            msg.innerText = '';
            
            var formData = new FormData();
            formData.append('checkout_form_id', cfId);
            formData.append('id_allegropro_account', accId);
            formData.append('new_status', status);

            var url = 'index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=update_allegro_status&ajax=1';

            fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                btnStatus.disabled = false;
                btnStatus.innerText = 'Zaktualizuj';
                if (d.success) {
                    msg.innerText = 'Status zmieniony!';
                    msg.className = 'form-text text-success';
                } else {
                    msg.innerText = 'Błąd: ' + (d.message || 'Error');
                    msg.className = 'form-text text-danger';
                }
            }).catch(e => { console.error(e); btnStatus.disabled = false; });
        });
    }

    // --- 2. TWORZENIE PRZESYŁKI (A/B/C/Kurier) ---
    var creatorMsg = document.getElementById('creator_msg');
    
    document.querySelectorAll('.btn-create-shipment').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var size = this.getAttribute('data-size');
            var mode = this.getAttribute('data-mode');
            var weight = 1.0;
            
            if (mode === 'courier') {
                var wInput = document.getElementById('courier_weight');
                if(wInput) weight = wInput.value;
            }
            
            // Obsługa checkboxa Smart
            var isSmart = 0;
            var smartCheck = document.getElementById('use_smart');
            if (smartCheck && smartCheck.checked) isSmart = 1;

            if(!confirm('Czy na pewno utworzyć etykietę?')) return;

            creatorMsg.innerText = 'Przetwarzanie... proszę czekać.';
            creatorMsg.className = 'mt-2 text-info';

            var fd = new FormData();
            fd.append('checkout_form_id', cfId);
            fd.append('id_allegropro_account', accId);
            fd.append('is_smart', isSmart);
            if(size) fd.append('size_code', size);
            if(weight) fd.append('weight', weight);

            var url = 'index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=create_shipment&ajax=1';

            // POPRAWKA: Dodano spacje w obiekcie { method:... }
            fetch(url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    creatorMsg.innerText = 'Sukces! Odświeżam...';
                    creatorMsg.className = 'mt-2 text-success';
                    location.reload();
                } else {
                    creatorMsg.innerText = 'Błąd: ' + d.message;
                    creatorMsg.className = 'mt-2 text-danger';
                }
            })
            .catch(e => {
                creatorMsg.innerText = 'Błąd połączenia.';
                creatorMsg.className = 'mt-2 text-danger';
            });
        });
    });

    // --- 3. ANULOWANIE ---
    document.querySelectorAll('.btn-cancel-shipment').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if(!confirm('Czy na pewno ANULOWAĆ tę przesyłkę w Allegro? Spowoduje to zwolnienie slotu Smart.')) return;
            
            var shipId = this.getAttribute('data-id');
            var fd = new FormData();
            fd.append('shipment_id', shipId);
            fd.append('id_allegropro_account', accId);

            // POPRAWKA: Dodano spacje w obiekcie { method:... }
            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=cancel_shipment&ajax=1', { method: 'POST', body: fd })
            .then(r=>r.json()).then(d=>{
                if(d.success) location.reload();
                else alert('Błąd anulowania: '+d.message);
            });
        });
    });


    // --- 5. RĘCZNY SYNC PRZESYŁEK ---
    var btnSync = document.getElementById('btnSyncShipmentsNow');
    if (btnSync) {
        btnSync.addEventListener('click', function(e) {
            e.preventDefault();

            var msg = document.getElementById('ship_sync_msg');
            var debugCheck = document.getElementById('sync_shipments_debug');
            var debugBox = document.getElementById('ship_sync_debug');
            var debugEnabled = !!(debugCheck && debugCheck.checked);

            btnSync.disabled = true;
            if (msg) {
                msg.className = 'text-info';
                msg.innerText = 'Trwa synchronizacja przesyłek z Allegro...';
            }
            if (debugBox) {
                debugBox.style.display = 'none';
                debugBox.innerText = '';
            }

            var fd = new FormData();
            fd.append('checkout_form_id', cfId);
            fd.append('id_allegropro_account', accId);
            fd.append('debug', debugEnabled ? '1' : '0');

            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=sync_shipments&ajax=1', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                btnSync.disabled = false;

                if (debugEnabled && debugBox) {
                    var lines = Array.isArray(d.debug_lines) ? d.debug_lines : [];
                    if (!lines.length) {
                        lines = ['Brak dodatkowych danych debug.'];
                    }
                    debugBox.style.display = 'block';
                    debugBox.innerText = lines.join('\n');
                }

                if (d.success) {
                    if (msg) {
                        msg.className = 'text-success';
                        msg.innerText = 'Synchronizacja zakończona, zaktualizowano: ' + (d.synced || 0) + (debugEnabled ? '. Tryb debug aktywny.' : '') + '. Odświeżam widok...';
                    }
                    setTimeout(function(){ location.reload(); }, debugEnabled ? 1500 : 800);
                } else {
                    if (msg) {
                        msg.className = 'text-danger';
                        msg.innerText = 'Błąd synchronizacji: ' + (d.message || 'Nieznany błąd');
                    }
                }
            })
            .catch(function() {
                btnSync.disabled = false;
                if (msg) {
                    msg.className = 'text-danger';
                    msg.innerText = 'Błąd połączenia podczas synchronizacji.';
                }
                if (debugEnabled && debugBox) {
                    debugBox.style.display = 'block';
                    debugBox.innerText = 'Błąd połączenia - brak danych debug z serwera.';
                }
            });
        });
    }

    // --- 4. DRUKOWANIE (Pobieranie PDF) ---
    document.querySelectorAll('.btn-get-label').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var shipId = this.getAttribute('data-id');
            var fd = new FormData();
            fd.append('shipment_id', shipId);
            fd.append('checkout_form_id', cfId);
            fd.append('id_allegropro_account', accId);

            // POPRAWKA: Dodano spacje w obiekcie { method:... }
            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=get_label&ajax=1', { method: 'POST', body: fd })
            .then(r=>r.json()).then(d=>{
                if(d.success) window.open(d.url, '_blank');
                else alert('Błąd: '+d.message);
            });
        });
    });
});
</script>
