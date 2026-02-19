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
                        {include file='./partials/shipment_form.tpl' allegro_data=$allegro_data}

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
                                            {if $ship_status == 'CREATED' || $ship_status == 'PENDING'}<span class="badge badge-secondary">UTWORZONA</span>
                                            {elseif $ship_status == 'CANCELLED'}<span class="badge badge-secondary">ANULOWANA</span>
                                            {elseif $ship_status == 'NEW'}<span class="badge badge-info">W PRZYGOTOWANIU</span>
                                            {elseif $ship_status == 'IN_PROGRESS'}<span class="badge badge-info">W PRZYGOTOWANIU</span>
                                            {elseif $ship_status == 'SENT' || $ship_status == 'IN_TRANSIT'}<span class="badge badge-primary">W DRODZE</span>
                                            {elseif $ship_status == 'OUT_FOR_DELIVERY' || $ship_status == 'RELEASED_FOR_DELIVERY'}<span class="badge badge-info">W DORĘCZENIU</span>
                                            {elseif $ship_status == 'READY_FOR_PICKUP' || $ship_status == 'AVAILABLE_FOR_PICKUP'}<span class="badge badge-warning">DO ODBIORU</span>
                                            {elseif $ship_status == 'DELIVERED'}<span class="badge badge-success">DOSTARCZONA</span>
                                            {elseif $ship_status == 'RETURNED_TO_SENDER' || $ship_status == 'RETURNED'}<span class="badge badge-secondary">ZWRÓCONA</span>
                                            {elseif $ship_status == 'LOST'}<span class="badge badge-danger">ZAGUBIONA</span>
                                            {elseif $ship_status == 'DELIVERY_FAILED' || $ship_status == 'UNDELIVERED'}<span class="badge badge-danger">PROBLEM</span>
                                            {else}<span class="badge badge-warning">{$ship_status|escape:'htmlall':'UTF-8'}</span>{/if}

                                            <br><span class="text-muted" style="font-size:9px;">{$ship.origin_label|default:'POBRANA Z ALLEGRO'|escape:'htmlall':'UTF-8'}</span>
                                            {if $ship.is_smart}<br><span style="color:#ff5a00; font-weight:bold; font-size:9px;">SMART</span>{/if}
                                        </td>
                                        <td>
                                            {if $ship_status == 'CREATED' || $ship_status == 'PENDING'}Oczekuje na nadanie
                                            {elseif $ship_status == 'NEW' || $ship_status == 'IN_PROGRESS'}W przygotowaniu
                                            {elseif $ship_status == 'SENT' || $ship_status == 'IN_TRANSIT'}W drodze
                                            {elseif $ship_status == 'OUT_FOR_DELIVERY' || $ship_status == 'RELEASED_FOR_DELIVERY'}W doręczeniu
                                            {elseif $ship_status == 'READY_FOR_PICKUP' || $ship_status == 'AVAILABLE_FOR_PICKUP'}Do odbioru
                                            {elseif $ship_status == 'DELIVERED'}Dostarczona
                                            {elseif $ship_status == 'CANCELLED'}Anulowana
                                            {elseif $ship_status == 'RETURNED_TO_SENDER' || $ship_status == 'RETURNED'}Zwrócona
                                            {elseif $ship_status == 'LOST'}Zagubiona
                                            {elseif $ship_status == 'DELIVERY_FAILED' || $ship_status == 'UNDELIVERED'}Problem z dostawą
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
                                                <button type="button" class="btn btn-xs btn-default ap-btn-track" data-number="{$ship.tracking_number|escape:'htmlall':'UTF-8'}" title="Pokaż historię śledzenia">
                                                    <i class="material-icons">search</i>
                                                </button>
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
                                    <tr><td colspan="7" class="text-center text-muted">Brak wygenerowanych etykiet.</td></tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>

                        {* MODAL: TRACKING *}
	                        <style>
	                        {literal}
	                            .ap-track-statusline{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:10px 0 6px;}
	                            .ap-track-badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-weight:600;font-size:12px;}
	                            .ap-track-meta{color:#6c757d;font-size:12px;}
	                            .ap-track-timeline{margin-top:10px;border-left:2px solid #e5e7eb;padding-left:14px;}
	                            .ap-track-item{position:relative;margin:10px 0;}
	                            .ap-track-dot{position:absolute;left:-22px;top:14px;width:10px;height:10px;border-radius:50%;background:#adb5bd;border:2px solid #fff;box-shadow:0 0 0 2px #e5e7eb;}
	                            .ap-track-card{padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;}
	                            .ap-track-date{font-size:12px;color:#6c757d;margin-bottom:2px;}
	                            .ap-track-text{font-size:13px;}
	                            .ap-track-item--current .ap-track-card{border-left:4px solid var(--ap-accent);background:var(--ap-bg);box-shadow:0 6px 18px rgba(0,0,0,0.06);}
	                            .ap-track-item--current .ap-track-text{font-weight:600;}
	                            .ap-track-item--current .ap-track-dot{width:12px;height:12px;left:-23px;box-shadow:0 0 0 3px rgba(0,0,0,0.05);background:var(--ap-accent);}
	                            .ap-sev-success{--ap-accent:#28a745;--ap-bg:#eaf7ee;}
	                            .ap-sev-info{--ap-accent:#17a2b8;--ap-bg:#e7f6fb;}
	                            .ap-sev-warning{--ap-accent:#ff5a00;--ap-bg:#fff3e6;}
	                            .ap-sev-danger{--ap-accent:#dc3545;--ap-bg:#fdecea;}
	                            .ap-sev-secondary{--ap-accent:#6c757d;--ap-bg:#f2f4f6;}
	                            .ap-track-badge.ap-sev-success{background:#eaf7ee;color:#1e7e34;}
	                            .ap-track-badge.ap-sev-info{background:#e7f6fb;color:#117a8b;}
	                            .ap-track-badge.ap-sev-warning{background:#fff3e6;color:#ff5a00;}
	                            .ap-track-badge.ap-sev-danger{background:#fdecea;color:#b21f2d;}
	                            .ap-track-badge.ap-sev-secondary{background:#f2f4f6;color:#495057;}
	                        {/literal}
	                        </style>

                        <div class="modal fade" id="apTrackingModal" tabindex="-1" role="dialog" aria-hidden="true">
                          <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title">Historia śledzenia przesyłki</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Zamknij"><span aria-hidden="true">&times;</span></button>
                              </div>
                              <div class="modal-body">
                                <div id="apTrackingAlert" class="alert" style="display:none;"></div>
                                <div id="apTrackingContent"></div>
                              </div>
                              <div class="modal-footer">
                                <a href="#" id="apTrackingOpenLink" target="_blank" class="btn btn-default">Otwórz stronę przewoźnika</a>
                                <button type="button" class="btn btn-primary" data-dismiss="modal">Zamknij</button>
                              </div>
                            </div>
                          </div>
                        </div>
                        {* PRODUKTY *}
                        <hr>
                        <h5 style="font-size:13px;">Produkty z zamówienia:</h5>
                        <table class="table table-sm table-bordered" style="font-size:11px;">
                            <thead>
                                <tr>
                                    <th style="width:50px;">ID</th>
                                    <th>Nazwa</th>
                                    <th style="width:80px;">EAN</th>
                                    <th style="width:80px;">Ilość</th>
                                    <th style="width:90px;">Waga [kg]</th>
                                </tr>
                            </thead>
                            <tbody>
                            {foreach from=$allegro_data.items item=item}
                                <tr>
                                    <td>{$item.id_product|intval}</td>
                                    <td>{$item.product_name|default:$item.name|default:'-'|escape:'htmlall':'UTF-8'}</td>
                                    <td>
                                        {if isset($item.ean) && $item.ean}
                                            <span class="badge badge-info" style="font-size:12px;">{$item.ean|escape:'htmlall':'UTF-8'}</span>
                                        {else}
                                            <span class="text-muted">brak</span>
                                        {/if}
                                    </td>
                                    <td>{$item.quantity|default:0|intval}</td>
                                    <td>
                                        {if isset($item.weight) && $item.weight && $item.weight > 0}
                                            {$item.weight|floatval}
                                        {else}
                                            <span class="text-muted">0</span>
                                        {/if}
                                    </td>
                                </tr>
                            {foreachelse}
                                <tr><td colspan="5" class="text-center text-muted">Brak pozycji.</td></tr>
                            {/foreach}
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var cfId = '{$allegro_data.order.checkout_form_id|escape:'javascript':'UTF-8'}';
    var accId = '{$allegro_data.order.id_allegropro_account|intval}';

    // --- 1. Statusy ---
    var btnStatus = document.getElementById('btnUpdateAllegroStatus');
    if(btnStatus){
        btnStatus.addEventListener('click', function(e){
            e.preventDefault();
            var select = document.getElementById('allegropro_status_select');
            var msg = document.getElementById('allegropro_status_msg');
            var newStatus = select.value;
            if(!newStatus){
                msg.className = 'form-text text-danger';
                msg.innerText = 'Wybierz status.';
                return;
            }

            btnStatus.disabled = true;
            btnStatus.innerText = 'Aktualizacja...';

            var formData = new FormData();
            formData.append('checkout_form_id', cfId);
            formData.append('id_allegropro_account', accId);
            formData.append('new_status', newStatus);

            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=update_status&ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                msg.className = data.success ? 'form-text text-success' : 'form-text text-danger';
                msg.innerText = data.message || (data.success ? 'OK' : 'Błąd');
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
            var weightSourceSelect = document.getElementById('shipment_weight_source');
            var weightSource = weightSourceSelect ? weightSourceSelect.value : 'MANUAL';
            var sizeCode = sizeSelect ? sizeSelect.value : 'CUSTOM';
            var lengthInput = document.getElementById('shipment_length_input');
            var widthInput = document.getElementById('shipment_width_input');
            var heightInput = document.getElementById('shipment_height_input');
            var dimensionSourceInput = document.getElementById('shipment_dimension_source');
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
            formData.append('weight_source', weightSource);
            formData.append('size_code', sizeCode);
            formData.append('length', lengthInput ? lengthInput.value : '');
            formData.append('width', widthInput ? widthInput.value : '');
            formData.append('height', heightInput ? heightInput.value : '');
            formData.append('dimension_source', dimensionSourceInput ? dimensionSourceInput.value : 'MANUAL');
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
                if (thisBtn.querySelector('.ap-create-main') || thisBtn.querySelector('.ap-create-sub')) {
                    thisBtn.innerHTML = '<span class="ap-create-main">Utwórz</span><span class="ap-create-sub">przesyłkę</span>';
                } else {
                    thisBtn.innerText = 'Utwórz przesyłkę';
                }
            });
        });
    }

    var sizeSelect = document.getElementById('shipment_size_select');
    var weightInput = document.getElementById('shipment_weight_input');
    var weightSourceInput = document.getElementById('shipment_weight_source');
    var configBtn = document.getElementById('weight_mode_config');
    var productsBtn = document.getElementById('weight_mode_products');
    if (weightInput && weightSourceInput && configBtn && productsBtn) {
        var weightDefaults = {
            MANUAL: String(weightInput.getAttribute('data-manual-default') || '1.0'),
            CONFIG: String(weightInput.getAttribute('data-config-weight') || '1.0'),
            PRODUCTS: String(weightInput.getAttribute('data-products-weight') || '')
        };

        var currentMode = 'CONFIG';
        var sourceWeights = {
            CONFIG: weightDefaults.CONFIG,
            PRODUCTS: weightDefaults.PRODUCTS || weightDefaults.MANUAL
        };

        var normalizeWeightValue = function(value, fallback) {
            var v = String(value || '').trim();
            if (!v) {
                return fallback;
            }

            var normalized = v.replace(',', '.');
            var asNumber = parseFloat(normalized);
            if (isNaN(asNumber) || asNumber <= 0) {
                return fallback;
            }

            return normalized;
        };
        var setActiveModeButton = function(mode) {
            var isConfig = mode === 'CONFIG';
            configBtn.className = 'btn btn-sm ' + (isConfig ? 'btn-primary' : 'btn-default');
            productsBtn.className = 'btn btn-sm ' + (isConfig ? 'btn-default' : 'btn-primary');
        };

        var switchWeightMode = function(nextMode) {
            sourceWeights[currentMode] = normalizeWeightValue(weightInput.value, sourceWeights[currentMode] || weightDefaults.MANUAL);

            currentMode = nextMode;
            weightSourceInput.value = nextMode;

            var resolvedWeight = sourceWeights[nextMode] || weightDefaults.MANUAL;
            resolvedWeight = normalizeWeightValue(resolvedWeight, weightDefaults.MANUAL);

            weightInput.value = resolvedWeight;
            weightInput.disabled = false;
            weightInput.classList.remove('bg-light');
            setActiveModeButton(nextMode);
        };

        configBtn.addEventListener('click', function() {
            switchWeightMode('CONFIG');
        });

        productsBtn.addEventListener('click', function() {
            switchWeightMode('PRODUCTS');
        });

        weightInput.addEventListener('input', function() {
            sourceWeights[currentMode] = normalizeWeightValue(weightInput.value, sourceWeights[currentMode] || weightDefaults.MANUAL);
        });

        switchWeightMode('CONFIG');
    }

    var dimensionsPanel = document.getElementById('shipment_dimensions_panel');
    var dimensionSourceInput = document.getElementById('shipment_dimension_source');
    var dimensionConfigBtn = document.getElementById('dimension_mode_config');
    var dimensionManualBtn = document.getElementById('dimension_mode_manual');
    var lengthInput = document.getElementById('shipment_length_input');
    var widthInput = document.getElementById('shipment_width_input');
    var heightInput = document.getElementById('shipment_height_input');

    var normalizeDimensionValue = function(value, fallback) {
        var parsed = parseInt(String(value || '').trim(), 10);
        if (isNaN(parsed) || parsed <= 0) {
            return String(fallback);
        }
        return String(parsed);
    };

    var applyDimensionMode = function(mode) {
        if (!dimensionSourceInput || !lengthInput || !widthInput || !heightInput || !dimensionConfigBtn || !dimensionManualBtn) {
            return;
        }

        var useConfig = mode === 'CONFIG';
        dimensionSourceInput.value = useConfig ? 'CONFIG' : 'MANUAL';

        [
            { input: lengthInput, key: 'length' },
            { input: widthInput, key: 'width' },
            { input: heightInput, key: 'height' }
        ].forEach(function(field) {
            var fallback = field.input.getAttribute('data-manual-default') || '10';
            var cfg = field.input.getAttribute('data-config-value') || fallback;
            var nextValue = useConfig ? cfg : field.input.value;
            field.input.value = normalizeDimensionValue(nextValue, fallback);
            field.input.disabled = useConfig;
            field.input.classList.toggle('bg-light', useConfig);
        });

        dimensionConfigBtn.className = 'btn btn-sm ' + (useConfig ? 'btn-primary' : 'btn-default');
        dimensionManualBtn.className = 'btn btn-sm ' + (useConfig ? 'btn-default' : 'btn-primary');
    };

    var refreshDimensionsPanelVisibility = function() {
        if (!dimensionsPanel || !sizeSelect) {
            return;
        }

        var initialCarrierMode = '{$allegro_data.carrier_mode|default:''|escape:'javascript':'UTF-8'}';
        var shouldShow = (initialCarrierMode === 'COURIER') || (sizeSelect.value === 'CUSTOM');
        dimensionsPanel.style.display = shouldShow ? 'block' : 'none';
    };

    if (dimensionConfigBtn && dimensionManualBtn) {
        dimensionConfigBtn.addEventListener('click', function() {
            applyDimensionMode('CONFIG');
        });

        dimensionManualBtn.addEventListener('click', function() {
            applyDimensionMode('MANUAL');
        });
    }

    if (sizeSelect) {
        sizeSelect.addEventListener('change', refreshDimensionsPanelVisibility);
    }

    refreshDimensionsPanelVisibility();
    applyDimensionMode('CONFIG');

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

    // --- 4. Synchronizacja przesyłek ---
    var btnSync = document.getElementById('btnSyncShipmentsNow');
    if(btnSync){
        btnSync.addEventListener('click', function(e){
            e.preventDefault();
            var thisBtn = this;
            var msg = document.getElementById('ship_sync_msg');
            var dbgToggle = document.getElementById('sync_shipments_debug');
            var debugEnabled = dbgToggle ? dbgToggle.checked : false;
            var dbgBox = document.getElementById('ship_sync_debug');
            if(dbgBox){
                dbgBox.style.display = 'none';
                dbgBox.innerText = '';
            }

            thisBtn.disabled = true;
            if(msg){
                msg.className = 'text-muted';
                msg.innerText = 'Synchronizacja przesyłek trwa...';
            }

            var formData = new FormData();
            formData.append('checkout_form_id', cfId);
            formData.append('id_allegropro_account', accId);
            formData.append('debug', debugEnabled ? 1 : 0);

            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=sync_shipments&ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(msg){
                    if(data.success){
                        if(data.skipped){
                            msg.innerText = 'Synchronizacja przesyłek: odświeżone niedawno (TTL).';
                        } else {
                            msg.innerText = 'Synchronizacja przesyłek: zaktualizowano ' + (data.synced || 0) + ' rekordów.';
                        }
                        msg.className = 'text-success';
                    } else {
                        msg.innerText = 'Synchronizacja przesyłek: ' + (data.message || 'błąd');
                        msg.className = 'text-danger';
                    }
                }

                if(debugEnabled && dbgBox && Array.isArray(data.debug_lines) && data.debug_lines.length){
                    dbgBox.style.display = 'block';
                    dbgBox.innerText = data.debug_lines.join("\n");
                }

                if(data.success){
                    setTimeout(() => location.reload(), 1200);
                }
            })
            .catch(() => {
                if(msg){
                    msg.innerText = 'Błąd połączenia podczas synchronizacji.';
                    msg.className = 'text-danger';
                }
            })
            .finally(() => {
                thisBtn.disabled = false;
            });
        });
    }

    // --- Pobieranie etykiety ---
    document.querySelectorAll('.btn-get-label').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            var shipId = this.getAttribute('data-id');
            var debugToggle = document.getElementById('label_download_debug');
            var debugEnabled = debugToggle ? debugToggle.checked : false;
            var debugBox = document.getElementById('label_download_debug_box');

            if(debugBox){
                debugBox.style.display = 'none';
                debugBox.innerText = '';
            }
            var formData = new FormData();
            formData.append('shipment_id', shipId);
            formData.append('checkout_form_id', cfId);
            formData.append('id_allegropro_account', accId);
            formData.append('debug', debugEnabled ? 1 : 0);

            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=getLabel&ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(!data.success || !data.url){
                    alert(data.message || 'Nie udało się przygotować pobierania etykiety.');
                    return;
                }

                if(!debugEnabled){
                    window.open(data.url, '_blank');
                    return;
                }

                fetch(data.url + '&debug=1')
                .then(function(resp) {
                    return resp.json();
                })
                .then(function(debugData) {
                    if(!debugBox){
                        return;
                    }

                    var lines = [];
                    lines.push('message=' + (debugData.message || 'brak'));
                    if (Array.isArray(debugData.debug_lines) && debugData.debug_lines.length) {
                        lines = lines.concat(debugData.debug_lines);
                    }
                    if (debugData.file_name) {
                        lines.push('file_name=' + debugData.file_name);
                    }
                    if (debugData.file_path) {
                        lines.push('file_path=' + debugData.file_path);
                    }
                    if (debugData.file_size) {
                        lines.push('file_size=' + debugData.file_size);
                    }
                    if (debugData.mime) {
                        lines.push('mime=' + debugData.mime);
                    }

                    if (!lines.length) {
                        lines.push('Brak danych debug.');
                    }

                    debugBox.style.display = 'block';
                    debugBox.innerText = lines.join("\n");
                })
                .catch(function() {
                    if(debugBox){
                        debugBox.style.display = 'block';
                        debugBox.innerText = 'Błąd podczas pobierania danych debug etykiety.';
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

    // --- 5. TRACKING (modal z historią) ---
    function apShowTrackingModal() {
        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery('#apTrackingModal').modal('show');
        } else {
            // fallback (gdyby bootstrap modal nie był dostępny)
            var el = document.getElementById('apTrackingModal');
            if (el) {
                el.style.display = 'block';
            }
        }
    }

    function apSetTrackingAlert(type, text) {
        var a = document.getElementById('apTrackingAlert');
        if (!a) return;
        a.className = 'alert alert-' + type;
        a.innerText = text;
        a.style.display = 'block';
    }

    function apHideTrackingAlert() {
        var a = document.getElementById('apTrackingAlert');
        if (!a) return;
        a.style.display = 'none';
        a.innerText = '';
        a.className = 'alert';
    }

    function apRenderTracking(data) {
        var box = document.getElementById('apTrackingContent');
        if (!box) return;
        box.innerHTML = '';

        var openLink = document.getElementById('apTrackingOpenLink');
        if (openLink && data.url) {
            openLink.href = data.url;
        }

        var current = data.current || null;
        var events = Array.isArray(data.events) ? data.events : [];

        var severity = (current && current.severity) ? current.severity : 'secondary';
        var shortPl = (current && current.short_pl) ? current.short_pl : '—';
        var labelPl = (current && current.label_pl) ? current.label_pl : '';
        var dt = (current && current.occurred_at_formatted) ? current.occurred_at_formatted : '';

        var header = document.createElement('div');
        header.className = 'ap-track-statusline';

        var badge = document.createElement('span');
        badge.className = 'ap-track-badge ap-sev-' + severity;
        badge.textContent = shortPl;
        header.appendChild(badge);

        var meta = document.createElement('div');
        meta.className = 'ap-track-meta';
        meta.textContent = 'Numer: ' + (data.number || '-') + (dt ? (' • Ostatnia aktualizacja: ' + dt) : '');
        header.appendChild(meta);

        box.appendChild(header);

        if (labelPl) {
            var statusLine = document.createElement('div');
            statusLine.style.margin = '6px 0 10px';
            statusLine.innerHTML = '<strong>Status:</strong> ' + labelPl;
            box.appendChild(statusLine);
        }

        if (!events.length) {
            var empty = document.createElement('div');
            empty.className = 'text-muted';
            empty.textContent = 'Brak szczegółowej historii trackingu dla tej przesyłki.';
            box.appendChild(empty);
            return;
        }

        var tl = document.createElement('div');
        tl.className = 'ap-track-timeline';

        events.forEach(function(ev, idx){
            var sev = ev.severity || 'secondary';
            var item = document.createElement('div');
            item.className = 'ap-track-item ap-sev-' + sev + (idx === 0 ? ' ap-track-item--current' : '');

            var dot = document.createElement('div');
            dot.className = 'ap-track-dot';
            item.appendChild(dot);

            var card = document.createElement('div');
            card.className = 'ap-track-card';

            var date = document.createElement('div');
            date.className = 'ap-track-date';
            date.textContent = ev.occurred_at_formatted || '';

            var text = document.createElement('div');
            text.className = 'ap-track-text';
            text.textContent = ev.label_pl || ev.status || '';

            card.appendChild(date);
            card.appendChild(text);
            item.appendChild(card);
            tl.appendChild(item);
        });

        box.appendChild(tl);
    }

    function apFetchTracking(number){
        apHideTrackingAlert();
        var box = document.getElementById('apTrackingContent');
        if (box) box.innerHTML = '<div class="text-muted">Pobieranie danych…</div>';
        var openLink = document.getElementById('apTrackingOpenLink');
        if (openLink) openLink.href = 'https://allegro.pl/allegrodelivery/sledzenie-paczki?numer=' + encodeURIComponent(number);
        apShowTrackingModal();

	        var url = 'index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=getTracking&ajax=1';
        var formData = new FormData();
        formData.append('tracking_number', number);
        formData.append('checkout_form_id', cfId);
        formData.append('id_allegropro_account', accId);

        fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (!d || !d.success) {
                apSetTrackingAlert('danger', (d && d.message) ? d.message : 'Nie udało się pobrać trackingu.');
                return;
            }
	            // Sukces — nie pokazujemy zielonego alertu, wyświetlamy od razu historię
	            apHideTrackingAlert();
            apRenderTracking(d);
        })
        .catch(() => {
            apSetTrackingAlert('danger', 'Błąd połączenia podczas pobierania trackingu.');
        });
    }

    document.querySelectorAll('.ap-btn-track').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            var num = this.getAttribute('data-number') || '';
            if (!num) return;
            apFetchTracking(num);
        });
    });
});
</script>
