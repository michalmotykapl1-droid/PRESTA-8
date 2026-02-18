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
            <div class="col-md-9">
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

        </div>

        <div class="row mt-3">
            {* 3. WYSYŁKA - PANEL STEROWANIA *}
            <div class="col-12">
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
                            <div class="mb-2" style="font-size:12px; margin-top:8px;">
                                <label style="font-weight:normal; margin:0;">
                                    <input type="checkbox" id="label_download_debug"> Tryb debug pobierania etykiety (nie otwiera PDF, pokazuje szczegóły)
                                </label>
                            </div>
                            <div id="label_download_debug_box" class="alert alert-secondary" style="display:none; font-size:11px; white-space:pre-wrap; max-height:240px; overflow:auto;"></div>
                        {/if}
                        
                        {* BANER SMART (zawsze widoczny) *}
                        {assign var=isSmartOrder value=(isset($allegro_data.shipping.is_smart) && $allegro_data.shipping.is_smart)}
                        <div style="background-color:{if $isSmartOrder}#ffece5{else}#f7f7f7{/if}; border-left: 5px solid #ff5a00; padding: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="color:{if $isSmartOrder}#ff5a00{else}#555{/if};"><i class="material-icons" style="font-size:14px;vertical-align:-2px;">local_shipping</i> ALLEGRO SMART!</strong><br>
                                {if $isSmartOrder}
                                    <small>Wysyłka opłacona przez Allegro.</small>
                                {else}
                                    <small>To nie jest wysyłka Smart (standardowa wysyłka).</small>
                                {/if}
                            </div>
                            <div style="text-align:right; border: 1px solid {if $isSmartOrder}#f4d5c6{else}#e1e1e1{/if}; padding: 5px 8px; background: {if $isSmartOrder}#fff4ef{else}#ffffff{/if};">
                                <small style="display:block;color:#999;">POZOSTAŁO:</small>
                                <strong style="font-size:22px; color:{if $isSmartOrder}#ff5a00{else}#555{/if};">{$allegro_data.smart_left} / {$allegro_data.smart_limit}</strong>
                            </div>
                        </div>
                        
                        <p><strong>Metoda:</strong> {$allegro_data.shipping.method_name|default:'-'}</p>

                        {* FORMULARZ NADAWANIA *}
                        <div class="card border mb-3">
                            <div class="card-body p-2">
                                <label class="mb-1"><strong>Nadaj nową paczkę:</strong></label>
                                <div class="form-row mb-2">
                                    <div class="col-md-4 mb-2 mb-md-0">
                                        <select id="shipment_size_select" class="form-control">
                                            <option value="CUSTOM" selected>Własny gabaryt (waga)</option>
                                            <option value="A">Gabaryt A (Allegro)</option>
                                            <option value="B">Gabaryt B (Allegro)</option>
                                            <option value="C">Gabaryt C (Allegro)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-2 mb-md-0">
                                        <input type="text" id="shipment_weight_input" class="form-control" value="1.0" placeholder="Waga (kg)">
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-info btn-block" type="button" id="btnCreateShipment">Utwórz przesyłkę</button>
                                    </div>
                                </div>
                                <small class="form-text text-muted mb-2">Dla gabarytów A/B/C Allegro użyje stałych wymiarów z backendu. Przy "Własny gabaryt" używana jest tylko waga.</small>
                                <div class="form-check">
                                    <input type="checkbox" id="is_smart_shipment" class="form-check-input" {if $allegro_data.smart_left <= 0}disabled{/if}>
                                    <label for="is_smart_shipment" class="form-check-label">Użyj Allegro Smart! (jeśli dostępny)</label>
                                </div>
                            </div>
                        </div>

                        {* HISTORIA *}
                        <h5 style="font-size:13px;">Historia Przesyłek:</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped" style="font-size:11px; margin-bottom:0;">
                                <thead>
                                    <tr>
                                        <th>Status lokalny</th>
                                        <th>Status aktualny</th>
                                        <th>Typ</th>
                                        <th>Data</th>
                                        <th>Nr nadania</th>
                                        <th>Tracking</th>
                                        <th class="text-right">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                {foreach from=$allegro_data.shipments item=ship}
                                    <tr>
                                        <td>
                                            {assign var=ship_status value=$ship.status|default:''}
                                            {if $ship_status == 'CREATED' || $ship_status == 'PENDING'}<span class="badge badge-success">UTWORZONA</span>
                                            {elseif $ship_status == 'CANCELLED'}<span class="badge badge-secondary">ANULOWANA</span>
                                            {elseif $ship_status == 'NEW'}<span class="badge badge-info">W TOKU</span>
                                            {elseif $ship_status == 'IN_PROGRESS'}<span class="badge badge-info">PRZETWARZANIE</span>
                                            {elseif $ship_status == 'SENT'}<span class="badge badge-primary">NADANA</span>
                                            {elseif $ship_status == 'IN_TRANSIT'}<span class="badge badge-primary">W DRODZE</span>
                                            {elseif $ship_status == 'OUT_FOR_DELIVERY' || $ship_status == 'RELEASED_FOR_DELIVERY'}<span class="badge badge-primary">W DORĘCZENIU</span>
                                            {elseif $ship_status == 'READY_FOR_PICKUP' || $ship_status == 'AVAILABLE_FOR_PICKUP'}<span class="badge badge-primary">DO ODBIORU</span>
                                            {elseif $ship_status == 'DELIVERED'}<span class="badge badge-primary">DORĘCZONA</span>
                                            {elseif $ship_status == 'RETURNED_TO_SENDER' || $ship_status == 'RETURNED'}<span class="badge badge-secondary">ZWRÓCONA</span>
                                            {elseif $ship_status == 'LOST'}<span class="badge badge-danger">ZAGUBIONA</span>
                                            {elseif $ship_status == 'DELIVERY_FAILED' || $ship_status == 'UNDELIVERED'}<span class="badge badge-warning">NIEUDANA</span>
                                            {else}<span class="badge badge-warning">{$ship_status|escape:'htmlall':'UTF-8'}</span>{/if}

                                            <br><span class="text-muted" style="font-size:9px;">{$ship.origin_label|default:'POBRANA Z ALLEGRO'|escape:'htmlall':'UTF-8'}</span>
                                            {if $ship.is_smart}<br><span style="color:#ff5a00; font-weight:bold; font-size:9px;">SMART</span>{/if}
                                        </td>
                                        <td>
                                            {if $ship_status == 'CREATED' || $ship_status == 'PENDING'}Oczekuje na nadanie
                                            {elseif $ship_status == 'NEW' || $ship_status == 'IN_PROGRESS'}W trakcie tworzenia
                                            {elseif $ship_status == 'SENT' || $ship_status == 'IN_TRANSIT'}W drodze
                                            {elseif $ship_status == 'OUT_FOR_DELIVERY' || $ship_status == 'RELEASED_FOR_DELIVERY'}W doręczeniu
                                            {elseif $ship_status == 'READY_FOR_PICKUP' || $ship_status == 'AVAILABLE_FOR_PICKUP'}Do odbioru
                                            {elseif $ship_status == 'DELIVERED'}Dostarczona
                                            {elseif $ship_status == 'CANCELLED'}Anulowana
                                            {elseif $ship_status == 'RETURNED_TO_SENDER' || $ship_status == 'RETURNED'}Zwrócona
                                            {elseif $ship_status == 'LOST'}Zagubiona
                                            {elseif $ship_status == 'DELIVERY_FAILED' || $ship_status == 'UNDELIVERED'}Nieudana dostawa
                                            {else}{$ship_status|escape:'htmlall':'UTF-8'}{/if}
                                        {if $ship.status_changed_at}
                                                <br><span class="text-muted" style="font-size:9px;">{$ship.status_changed_at|date_format:"%d.%m.%Y (%H:%M)"}</span>
                                            {/if}
                                        </td>
                                        <td>{$ship.size_details}</td>
                                        <td>{$ship.created_at|date_format:"%d.%m.%Y (%H:%M)"}</td>
                                        <td>
                                            {if $ship.tracking_number}
                                                <code>{$ship.tracking_number|escape:'htmlall':'UTF-8'}</code>
                                            {else}
                                                <span class="text-muted">—</span>
                                            {/if}
                                        </td>
                                        <td>
                                            {if $ship.tracking_number}
                                                <button type="button"
                                                    class="btn btn-xs btn-default ap-track-btn"
                                                    title="Śledź przesyłkę"
                                                    data-number="{$ship.tracking_number|escape:'htmlall':'UTF-8'}"
                                                    data-carrier="{$ship.carrier_id|escape:'htmlall':'UTF-8'}"
                                                    data-url="https://allegro.pl/allegrodelivery/sledzenie-paczki?numer={$ship.tracking_number|escape:'url'}"
                                                ><i class="material-icons">search</i></button>
                                            {else}
                                                <span class="text-muted">—</span>
                                            {/if}
                                        </td>
                                        <td class="text-right">
                                            {if $ship.can_download_label && $ship.status != 'CANCELLED'}
                                                <button class="btn btn-xs btn-default btn-get-label" data-id="{$ship.shipment_id}" title="Pobierz Etykietę"><i class="material-icons">print</i></button>
                                            {/if}

                                            {if $ship.status == 'CREATED' || $ship.status == 'PENDING'}
                                                <button class="btn btn-xs btn-danger btn-cancel-shipment" data-id="{$ship.shipment_id}" title="Anuluj przesyłkę w Allegro"><i class="material-icons">cancel</i></button>
                                            {/if}
                                        </td>
                                    </tr>
                                {foreachelse}
                                    <tr><td colspan="6" class="text-center text-muted">Brak wygenerowanych etykiet.</td></tr>
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

            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=update_status&ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    msg.className = 'form-text text-success';
                    msg.innerText = data.message || 'Status zaktualizowany!';
                    setTimeout(() => location.reload(), 1000);
                } else {
                    msg.className = 'form-text text-danger';
                    msg.innerText = data.message || 'Błąd.';
                }
            })
            .catch(() => {
                msg.className = 'form-text text-danger';
                msg.innerText = 'Błąd połączenia.';
            })
            .finally(() => {
                btnStatus.disabled = false;
                btnStatus.innerText = 'Zaktualizuj';
            });
        });
    }

    // --- 2. Tworzenie Przesyłki ---
    var btnCreate = document.getElementById('btnCreateShipment');
    if(btnCreate){
        btnCreate.addEventListener('click', function(e){
            e.preventDefault();
            var weightInput = document.getElementById('shipment_weight_input');
            var sizeSelect = document.getElementById('shipment_size_select');
            var weight = weightInput ? weightInput.value : '';
            var sizeCode = sizeSelect ? sizeSelect.value : 'CUSTOM';
            var isSmart = document.getElementById('is_smart_shipment').checked;
            var dbgToggle = document.getElementById('sync_shipments_debug');
            var debugEnabled = dbgToggle ? dbgToggle.checked : false;
            var thisBtn = this;
            thisBtn.disabled = true;
            thisBtn.innerText = 'Tworzenie...';

            var formData = new FormData();
            formData.append('checkout_form_id', cfId);
            formData.append('id_allegropro_account', accId);
            formData.append('weight', weight);
            formData.append('size_code', sizeCode);
            formData.append('is_smart', isSmart ? 1 : 0);
            formData.append('debug', debugEnabled ? 1 : 0);

            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=create_shipment&ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                // Debug box (używamy tego samego panelu co dla "Odśwież przesyłki")
                if(debugEnabled){
                    var dbgBox = document.getElementById('ship_sync_debug');
                    if(dbgBox && Array.isArray(data.debug_lines) && data.debug_lines.length){
                        dbgBox.style.display = 'block';
                        dbgBox.innerText = data.debug_lines.join("\n");
                    }
                }

                alert(data.message || (data.success ? 'Sukces!' : 'Błąd.'));
                if(data.success){
                    setTimeout(() => location.reload(), 1500); 
                }
            })
            .catch(() => alert('Błąd połączenia.'))
            .finally(() => {
                thisBtn.disabled = false;
                thisBtn.innerText = 'Utwórz przesyłkę';
            });
        });
    }

    var sizeSelect = document.getElementById('shipment_size_select');
    var weightInput = document.getElementById('shipment_weight_input');
    if (sizeSelect && weightInput) {
        var updateWeightAvailability = function() {
            var isCustom = sizeSelect.value === 'CUSTOM';
            weightInput.disabled = !isCustom;
            if (!isCustom) {
                weightInput.classList.add('bg-light');
            } else {
                weightInput.classList.remove('bg-light');
            }
        };
        sizeSelect.addEventListener('change', updateWeightAvailability);
        updateWeightAvailability();
    }

    // --- 3. Anulowanie Przesyłki ---
    document.querySelectorAll('.btn-cancel-shipment').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            if(!confirm('Czy na pewno anulować tę przesyłkę w Allegro?')) return;
            
            var shipId = this.getAttribute('data-id');
            var thisBtn = this;
            thisBtn.disabled = true;

            var formData = new FormData();
            formData.append('shipment_id', shipId);
            formData.append('checkout_form_id', cfId);
            formData.append('id_allegropro_account', accId);

            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=cancel_shipment&ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message || (data.success ? 'Anulowano' : 'Błąd'));
                if(data.success) location.reload();
            })
            .catch(() => alert('Błąd połączenia.'))
            .finally(() => thisBtn.disabled = false);
        });
    });

    // --- 3b. Ręczne odświeżenie przesyłek z Allegro ---
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
                        if (debugEnabled) {
                            msg.innerText = 'Synchronizacja zakończona, zaktualizowano: ' + (d.synced || 0) + '. Tryb debug aktywny — widok NIE zostanie automatycznie odświeżony.';
                        } else {
                            msg.innerText = 'Synchronizacja zakończona, zaktualizowano: ' + (d.synced || 0) + '. Odświeżam widok...';
                        }
                    }
                    if (!debugEnabled) {
                        setTimeout(function(){ location.reload(); }, 800);
                    }
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
            var debugCheck = document.getElementById('label_download_debug');
            var debugEnabled = !!(debugCheck && debugCheck.checked);
            var debugBox = document.getElementById('label_download_debug_box');

            if (debugBox) {
                debugBox.style.display = 'none';
                debugBox.innerText = '';
            }

            var fd = new FormData();
            fd.append('shipment_id', shipId);
            fd.append('checkout_form_id', cfId);
            fd.append('id_allegropro_account', accId);

            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=get_label&ajax=1', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (!d.success || !d.url) {
                    alert('Błąd: ' + (d.message || 'Brak URL etykiety.'));
                    return;
                }

                if (!debugEnabled) {
                    window.open(d.url, '_blank');
                    return;
                }

                var debugUrl = d.url + '&debug=1&ajax=1';
                fetch(debugUrl)
                .then(function(r) { return r.json(); })
                .then(function(debugData) {
                    if (!debugBox) {
                        alert(JSON.stringify(debugData, null, 2));
                        return;
                    }

                    var lines = [];
                    lines.push('success=' + (debugData.success ? 'true' : 'false'));
                    lines.push('message=' + (debugData.message || ''));
                    if (debugData.http_code) {
                        lines.push('http_code=' + debugData.http_code);
                    }
                    if (Array.isArray(debugData.debug_lines) && debugData.debug_lines.length) {
                        lines.push('');
                        lines.push('--- debug_lines ---');
                        lines = lines.concat(debugData.debug_lines);
                    }
                    if (debugData.file_name) {
                        lines.push('');
                        lines.push('file_name=' + debugData.file_name);
                    }
                    if (debugData.file_size) {
                        lines.push('file_size=' + debugData.file_size);
                    }

                    debugBox.style.display = 'block';
                    debugBox.innerText = lines.join('\n');
                })
                .catch(function() {
                    if (debugBox) {
                        debugBox.style.display = 'block';
                        debugBox.innerText = 'Błąd połączenia podczas pobierania danych debug etykiety.';
                    } else {
                        alert('Błąd połączenia podczas pobierania danych debug etykiety.');
                    }
                });
            })
            .catch(function() {
                alert('Błąd połączenia podczas przygotowania URL etykiety.');
            });
        });
    });
});
</script>


<style>
    /* AllegroPro tracking modal */
    #apTrackingModal .ap-track-date { white-space: nowrap; color: #6c757d; font-size: 12px; }
    #apTrackingModal .ap-track-desc { font-size: 13px; }
    #apTrackingModal tr.ap-track-latest { font-weight: 700; }
    #apTrackingModal tr.ap-track-latest td { position: relative; }
    #apTrackingModal tr.ap-track-latest td:first-child { border-left: 4px solid #17a2b8; }
    #apTrackingModal tr.ap-track-latest.ap-track-done td:first-child { border-left-color: #28a745; }
    #apTrackingModal tr.ap-track-latest.ap-track-bad td:first-child { border-left-color: #dc3545; }
    #apTrackingModal tr.ap-track-latest.ap-track-pickup td:first-child { border-left-color: #6f42c1; }
    #apTrackingModal tr.ap-track-latest { background: #f8f9fa; }
    #apTrackingModal tr.ap-track-latest.ap-track-done { background: #eaf7ee; }
    #apTrackingModal tr.ap-track-latest.ap-track-bad { background: #fdecea; }
    #apTrackingModal tr.ap-track-latest.ap-track-pickup { background: #f2effb; }
</style>

<div class="modal fade" id="apTrackingModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><i class="material-icons" style="vertical-align:middle;">local_shipping</i> Śledzenie przesyłki</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="apTrackAlert" class="alert alert-info" style="margin-bottom:12px;">Ładowanie…</div>

        <div id="apTrackMeta" style="margin-bottom:10px;">
          <div><strong>Numer:</strong> <code id="apTrackNumber">—</code></div>
          <div><strong>Status:</strong> <span id="apTrackCurrent">—</span></div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm" style="font-size:13px;">
            <thead>
              <tr>
                <th style="width:180px;">Data</th>
                <th>Zdarzenie</th>
              </tr>
            </thead>
            <tbody id="apTrackTbody">
              <tr><td colspan="2" class="text-muted">—</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn btn-primary" id="apTrackOpen" target="_blank" rel="noopener">Otwórz śledzenie w Allegro</a>
        <button type="button" class="btn btn-default" data-dismiss="modal">Zamknij</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
    function apIsPolishText(s){
        if (!s) return false;
        // prosta heurystyka: polskie znaki lub słowo "Przesyłka"
        return /[ąćęłńóśżź]/i.test(s) || /przesyłk/i.test(s);
    }

    function apCodeToPlShort(code){
        var map = {
            'PENDING': 'Oczekuje na nadanie',
            'IN_TRANSIT': 'W drodze',
            'RELEASED_FOR_DELIVERY': 'W doręczeniu',
            'OUT_FOR_DELIVERY': 'W doręczeniu',
            'AVAILABLE_FOR_PICKUP': 'Do odbioru',
            'READY_FOR_PICKUP': 'Do odbioru',
            'NOTICE_LEFT': 'Awizo',
            'ISSUE': 'Problem z przesyłką',
            'DELIVERED': 'Dostarczona',
            'RETURNED': 'Zwrócona',
            'RETURNED_TO_SENDER': 'Zwrócona',
            'SENT': 'Nadana',
            'LOST': 'Zagubiona',
            'DELIVERY_FAILED': 'Nieudana dostawa',
            'UNDELIVERED': 'Nieudana dostawa'
        };
        return map[code] || code || '—';
    }

    function apCodeToPlLong(code){
        var map = {
            'PENDING': 'Przesyłka oczekuje na nadanie',
            'SENT': 'Przesyłka została nadana',
            'IN_TRANSIT': 'Przesyłka jest w drodze',
            'RELEASED_FOR_DELIVERY': 'Przesyłka została wydana do doręczenia',
            'OUT_FOR_DELIVERY': 'Przesyłka została wydana do doręczenia',
            'AVAILABLE_FOR_PICKUP': 'Przesyłka czeka na odbiór',
            'READY_FOR_PICKUP': 'Przesyłka czeka na odbiór',
            'DELIVERED': 'Przesyłka została doręczona',
            'RETURNED_TO_SENDER': 'Przesyłka została zwrócona do nadawcy',
            'RETURNED': 'Przesyłka została zwrócona',
            'ISSUE': 'Wystąpił problem z przesyłką',
            'LOST': 'Przesyłka została uznana za zagubioną',
            'DELIVERY_FAILED': 'Doręczenie przesyłki nie powiodło się',
            'UNDELIVERED': 'Doręczenie przesyłki nie powiodło się'
        };
        return map[code] || apCodeToPlShort(code);
    }

    function apDescToPl(desc, code){
        if (!desc) return '';
        desc = String(desc).trim();
        if (!desc) return '';
        if (apIsPolishText(desc)) return desc;

        var d = desc.toLowerCase();

        var map = [
            [/parcel has been delivered/i, 'Przesyłka została doręczona'],
            [/parcel has been released for delivery/i, 'Przesyłka została wydana do doręczenia'],
            [/parcel has been accepted at the destination center/i, 'Przesyłka została przyjęta w oddziale doręczającym'],
            [/parcel has been accepted at the delivery center/i, 'Przesyłka została przyjęta w oddziale doręczającym'],
            [/parcel has been accepted at the branch/i, 'Przesyłka została przyjęta w oddziale'],
            [/parcel has been picked up by the courier/i, 'Przesyłka została odebrana przez kuriera'],
            [/parcel has been prepared by the sender/i, 'Przesyłka została przygotowana przez nadawcę'],
            [/parcel has been prepared by sender/i, 'Przesyłka została przygotowana przez nadawcę'],
            [/parcel is in transit/i, 'Przesyłka jest w drodze'],
            [/parcel is ready for pickup/i, 'Przesyłka czeka na odbiór'],
            [/parcel is available for pickup/i, 'Przesyłka czeka na odbiór'],
            [/parcel has been returned to sender/i, 'Przesyłka została zwrócona do nadawcy'],
            [/parcel has been returned/i, 'Przesyłka została zwrócona'],
            [/parcel has been handed over to the carrier/i, 'Przesyłka została przekazana przewoźnikowi'],
            [/parcel has been accepted/i, 'Przesyłka została przyjęta'],
        ];

        for (var i = 0; i < map.length; i++){
            if (map[i][0].test(desc)) return map[i][1];
        }

        // fallback: dłuższy opis z kodu
        return apCodeToPlLong(code || '');
    }

    function apLatestClass(code){
        code = (code || '').toUpperCase();
        if (code === 'DELIVERED') return 'ap-track-done';
        if (code === 'READY_FOR_PICKUP' || code === 'AVAILABLE_FOR_PICKUP') return 'ap-track-pickup';
        if (code === 'ISSUE' || code === 'LOST' || code === 'DELIVERY_FAILED' || code === 'UNDELIVERED') return 'ap-track-bad';
        return '';
    }

    function apEventText(code, desc){
        var pl = apDescToPl(desc || '', code || '');
        if (pl) return pl;
        return apCodeToPlLong(code || '');
    }

    function apFmt(dtStr){
        try {
            var d = new Date(dtStr);
            if (isNaN(d.getTime())) return dtStr || '';
            return d.toLocaleString('pl-PL');
        } catch(e){
            return dtStr || '';
        }
    }

    function apSetAlert(type, msg){
        var el = document.getElementById('apTrackAlert');
        if (!el) return;
        el.className = 'alert alert-' + (type || 'info');
        el.textContent = msg || '';
    }

    function apRenderStatuses(statuses){
        var tbody = document.getElementById('apTrackTbody');
        if (!tbody) return;
        tbody.innerHTML = '';

        var list = Array.isArray(statuses) ? statuses.slice() : [];
        if (!list.length){
            var tr0 = document.createElement('tr');
            var td0 = document.createElement('td');
            td0.colSpan = 2;
            td0.className = 'text-muted';
            td0.textContent = 'Brak zdarzeń trackingu.';
            tr0.appendChild(td0);
            tbody.appendChild(tr0);
            return;
        }

        // sort: najnowsze na górze (jak w Allegro Delivery)
        list.sort(function(a, b){
            var ta = (a && a.occurredAt) ? String(a.occurredAt) : '';
            var tb = (b && b.occurredAt) ? String(b.occurredAt) : '';
            return tb.localeCompare(ta);
        });

        list.forEach(function(st, idx){
            var tr = document.createElement('tr');
            if (idx === 0) {
                var cls = apLatestClass(st.code || '');
                tr.className = 'ap-track-latest' + (cls ? (' ' + cls) : '');
            }

            var tdDate = document.createElement('td');
            tdDate.className = 'ap-track-date';
            tdDate.textContent = apFmt(st.occurredAt || '');

            var tdDesc = document.createElement('td');
            tdDesc.className = 'ap-track-desc';
            tdDesc.textContent = apEventText(st.code || '', st.description || '');

            tr.appendChild(tdDate);
            tr.appendChild(tdDesc);
            tbody.appendChild(tr);
        });

        // current status line (najnowszy)
        var latest = list[0];
        var curText = apEventText(latest.code || '', latest.description || '');
        var curEl = document.getElementById('apTrackCurrent');
        if (curEl) curEl.textContent = curText + ' (' + apFmt(latest.occurredAt || '') + ')';
    }

    document.addEventListener('click', function(ev){
        var btn = ev.target && ev.target.closest ? ev.target.closest('.ap-track-btn') : null;
        if (!btn) return;

        ev.preventDefault();

        var number = (btn.getAttribute('data-number') || '').trim();
        var carrier = (btn.getAttribute('data-carrier') || '').trim().toUpperCase();
        var openUrl = (btn.getAttribute('data-url') || ('https://allegro.pl/allegrodelivery/sledzenie-paczki?numer=' + encodeURIComponent(number)));

        document.getElementById('apTrackNumber').textContent = number || '—';
        document.getElementById('apTrackOpen').setAttribute('href', openUrl);

        apSetAlert('info', 'Ładowanie historii…');
        document.getElementById('apTrackCurrent').textContent = '—';
        document.getElementById('apTrackTbody').innerHTML = '<tr><td colspan="2" class="text-muted">Ładowanie…</td></tr>';

        // pokaż modal (fallback: nowa karta)
        if (typeof $ === 'undefined' || !$('#apTrackingModal').modal) {
            window.open(openUrl, '_blank');
            return;
        }
        $('#apTrackingModal').modal('show');

        var accId = {$allegro_data.order.id_allegropro_account|intval};

        var endpoint = 'index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=get_tracking&ajax=1';
        var fd = new FormData();
        fd.append('id_allegropro_account', accId);
        fd.append('tracking_number', number);
        fd.append('carrier_id', carrier);

        fetch(endpoint, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(function(json){
            if (!json || !json.success){
                apSetAlert('danger', (json && json.message) ? json.message : 'Nie udało się pobrać trackingu.');
                return;
            }
            if (json.message) {
                apSetAlert('info', json.message);
            } else {
                apSetAlert('success', 'Pobrano historię trackingu.');
            }
            apRenderStatuses(json.statuses || []);
        })
        .catch(function(err){
            apSetAlert('danger', 'Błąd połączenia: ' + (err && err.message ? err.message : err));
        });
    });
})();
</script>
