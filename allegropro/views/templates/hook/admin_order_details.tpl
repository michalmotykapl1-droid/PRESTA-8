<div class="card mt-2" id="allegropro_order_details">
    {* Modal wykresów (Rozliczenia): używa settlements.css + settlements.js z modułu *}
    <link rel="stylesheet" href="{$smarty.const.__PS_BASE_URI__}modules/allegropro/views/css/settlements.css?v=1">
    <script defer src="{$smarty.const.__PS_BASE_URI__}modules/allegropro/views/js/settlements.js?v=1"></script>

    {* UI/UX: scoped styles for this block only (no global overrides) *}
    <style>
        {literal}
        #allegropro_order_details{border-radius:12px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,.06); border:1px solid rgba(0,0,0,.06); margin-bottom:18px;}
        #allegropro_order_details .card-header{background:#fff; border-bottom:1px solid rgba(0,0,0,.06);}
        #allegropro_order_details .card-header-title{display:flex; align-items:center; gap:8px; font-weight:700;}
        #allegropro_order_details .card-body{background:#fff;}
        #allegropro_order_details h4.text-muted{font-size:14px; font-weight:700; letter-spacing:.2px;}
        #allegropro_order_details .text-muted{color:#6c757d !important;}
        #allegropro_order_details .form-control[readonly]{background:#f7f9fb;}
        #allegropro_order_details .table{background:#fff;}
        #allegropro_order_details .table thead th{border-top:0; color:#516170; font-size:11px; letter-spacing:.4px; text-transform:uppercase;}
        #allegropro_order_details .table td, #allegropro_order_details .table th{vertical-align:middle;}
        #allegropro_order_details .table-responsive{border:1px solid rgba(0,0,0,.06); border-radius:10px; overflow:hidden;}
        #allegropro_order_details .ap-subcard{border:1px solid rgba(0,0,0,.06) !important; border-radius:12px;}
        #allegropro_order_details .ap-smart-banner{border-radius:12px;}
        #allegropro_order_details .ap-header{display:flex; align-items:flex-start; justify-content:space-between; gap:12px;}
        #allegropro_order_details .ap-header-sub{font-size:12px; margin-top:2px;}
        #allegropro_order_details .ap-pill{display:inline-flex; align-items:center; gap:8px; padding:7px 10px; border-radius:999px; border:1px solid rgba(0,0,0,.08); background:#f8fafc; font-weight:700; font-size:12px; white-space:nowrap;}
        #allegropro_order_details .ap-pill .material-icons{font-size:18px; color:#6c757d;}
#allegropro_order_details .ap-pill--status{background:#eef6ff; border-color:rgba(11,114,133,.25);}
#allegropro_order_details .ap-pill--status .material-icons{color:#0b7285;}
        #allegropro_order_details .ap-topcards{margin-top:6px;}
        #allegropro_order_details .ap-box{border:1px solid rgba(0,0,0,.06); border-radius:12px; background:#fff;}
        #allegropro_order_details .ap-box-title{display:flex; align-items:center; gap:8px; font-size:12px; font-weight:900; letter-spacing:.3px; text-transform:uppercase; color:#516170; margin-bottom:10px;}
        #allegropro_order_details .ap-box-title .material-icons{font-size:18px; color:#6c757d;}
        #allegropro_order_details .ap-kv{display:grid; grid-template-columns:150px 1fr; gap:6px 14px; align-items:center;}
        #allegropro_order_details .ap-k{color:#6c757d; font-size:12px;}
        #allegropro_order_details .ap-v{font-weight:700; font-size:13px;}
        #allegropro_order_details .ap-divider{height:1px; background:rgba(0,0,0,.06); margin:12px 0;}
        #allegropro_order_details .ap-help{font-size:12px; color:#6c757d;}
        #allegropro_order_details .ap-tabs{margin-top:14px; border-bottom:1px solid rgba(0,0,0,.06); display:flex; gap:6px;}
        #allegropro_order_details .ap-tabs .nav-link{display:flex; align-items:center; gap:6px; font-weight:800; color:#516170; border:0; border-bottom:2px solid transparent; padding:10px 14px;}
        #allegropro_order_details .ap-tabs .nav-link .material-icons{font-size:18px; color:#6c757d;}
        #allegropro_order_details .ap-tabs .nav-link.active{color:#0b7285; border-bottom-color:#0b7285; background:transparent;}
        #allegropro_order_details .ap-tab-content{padding-top:6px;}
	        #allegropro_order_details .ap-mini-box{border:1px solid rgba(0,0,0,.06); border-radius:12px; padding:12px; background:#f8fafc; height:100%;}
	        #allegropro_order_details .ap-mini-title{display:flex; align-items:center; gap:8px; font-size:12px; font-weight:900; letter-spacing:.3px; text-transform:uppercase; color:#516170; margin-bottom:10px;}
	        #allegropro_order_details .ap-mini-title .material-icons{font-size:18px; color:#6c757d;}
	        #allegropro_order_details .ap-mini-box .form-text{font-size:12px;}
	        #allegropro_order_details .ap-mini-box .btn{white-space:nowrap;}

        @media (max-width: 991px){
            #allegropro_order_details{box-shadow:none;}
        }
        {/literal}
    </style>
    <div class="card-header ap-header">
        <div class="ap-header-left">
            <div class="card-header-title">
                <i class="material-icons">shopping_cart</i>
                Allegro Pro — Szczegóły zamówienia
            </div>
            <div class="ap-header-sub text-muted">
                Checkout form ID: <code>{$allegro_data.order.checkout_form_id|escape:'htmlall':'UTF-8'}</code>
            </div>
        </div>
        <div class="ap-header-right">
            <span class="ap-pill" title="Konto Allegro">
                <i class="material-icons">account_circle</i>
                {$allegro_data.order.account_label|default:'Nieznane'|escape:'htmlall':'UTF-8'}
            </span>
        </div>
    </div>
    <div class="card-body">

	        <div class="row ap-topcards">
	            <div class="col-lg-4 mb-3 mb-lg-0">
                <div class="ap-box p-3">
                    <div class="ap-box-title"><i class="material-icons">person</i> Kupujący</div>
                    {assign var='ap_buyer_fullname' value=($allegro_data.buyer.firstname|default:'')|cat:' '|cat:($allegro_data.buyer.lastname|default:'')}
                    {assign var='ap_buyer_fullname' value=$ap_buyer_fullname|trim}
                    {if !$ap_buyer_fullname}
                        {assign var='ap_buyer_fullname' value=$allegro_data.shipping.addr_name|default:''|trim}
                    {/if}
                    <div class="row">
                        <div class="col-12 mb-2">
                            <div class="ap-k">Login</div>
                            <div class="ap-v">{$allegro_data.buyer.login|default:'-'|escape:'htmlall':'UTF-8'}</div>
                        </div>

                        {if $ap_buyer_fullname}
                            <div class="col-12 mb-2">
                                <div class="ap-k">Imię i nazwisko</div>
                                <div class="ap-v">{$ap_buyer_fullname|escape:'htmlall':'UTF-8'}</div>
                            </div>
                        {/if}

                        <div class="col-12">
                            <div class="ap-k">E-mail</div>
                            <div class="ap-v" style="word-break:break-word; overflow-wrap:anywhere; white-space:normal;">
                                {if isset($allegro_data.buyer.email) && $allegro_data.buyer.email}
                                    <a style="word-break:break-word; overflow-wrap:anywhere; white-space:normal; display:inline-block; max-width:100%;" href="mailto:{$allegro_data.buyer.email|escape:'htmlall':'UTF-8'}">{$allegro_data.buyer.email|escape:'htmlall':'UTF-8'}</a>
                                {else}
                                    -
                                {/if}
                            </div>
                        </div>
                    </div>
                    {assign var='ap_buyer_phone' value=$allegro_data.buyer.phone_number|default:$allegro_data.shipping.addr_phone|default:''}
                    {if $ap_buyer_phone}
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="ap-k">Telefon</div>
                            <div class="ap-v">
                                <a href="tel:{$ap_buyer_phone|escape:'htmlall':'UTF-8'}">{$ap_buyer_phone|escape:'htmlall':'UTF-8'}</a>
                            </div>
                        </div>
                    </div>
                    {/if}
                </div>
            </div>

	            <div class="col-lg-8 mb-3 mb-lg-0">
	                <div class="ap-box p-3">
	                    <div class="ap-box-title"><i class="material-icons">tune</i> Statusy</div>

{assign var='ap_status_code' value=$allegro_data.allegro_fulfillment_status|default:''}
{assign var='ap_status_label' value=$allegro_data.allegro_fulfillment_label|default:''}
{if !$ap_status_label}
	{assign var='ap_status_label' value=$allegro_data.allegro_statuses[$ap_status_code]|default:$ap_status_code}
{/if}
{if !$ap_status_label}{assign var='ap_status_label' value='Brak danych'}{/if}

	                    <div class="row">
	                        <div class="col-md-6 mb-3 mb-md-0">
	                            <div class="ap-mini-box">
	                                <div class="ap-mini-title"><i class="material-icons">cloud</i> Allegro</div>

	                                <div class="ap-k mb-1">Aktualny status</div>
	                                <div class="ap-v mb-2" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
	                                    <span class="ap-pill ap-pill--status" id="apAllegroStatusPill" title="Aktualny status realizacji w Allegro (fulfillment.status)">
	                                        <i class="material-icons">cloud</i>
	                                        <span id="apAllegroStatusLabel">{$ap_status_label|escape:'htmlall':'UTF-8'}</span>
	                                    </span>
	                                    {if $ap_status_code}
	                                        <span class="text-muted" id="apAllegroStatusCode" style="font-weight:800; font-size:12px;">{$ap_status_code|escape:'htmlall':'UTF-8'}</span>
	                                    {/if}
	                                </div>

	                                <div class="form-group mb-0">
	                                    <label class="text-muted mb-1">Zmień status (Allegro)</label>
	                                    <div class="input-group">
	                                        <select id="allegropro_status_select" class="form-control custom-select">
	                                            <option value="">-- Wybierz nowy status --</option>
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
	                            </div>
	                        </div>

	                        <div class="col-md-6">
	                            <div class="ap-mini-box">
	                                <div class="ap-mini-title"><i class="material-icons">store</i> Sklep (PrestaShop)</div>

	                                <div class="ap-k mb-1">Aktualny status</div>
	                                <div class="ap-v mb-2" id="apShopStatusCurrent">{$allegro_data.ps_status_name|escape:'htmlall':'UTF-8'}</div>

	                                <div class="form-group mb-0">
	                                    <label class="text-muted mb-1">Zmień status (Sklep)</label>
	                                    <div class="input-group">
	                                        <select id="apShopStatusSelect" class="form-control custom-select">
                                            <option value="">-- Wybierz status --</option>
                                            {foreach from=$allegro_data.shop_states item=st}
                                                <option value="{$st.id_order_state|intval}" {if (int)$st.id_order_state == (int)$allegro_data.ps_status_id}selected{/if}>{$st.name|escape:'htmlall':'UTF-8'}</option>
                                            {/foreach}
                                        </select>
	                                        <div class="input-group-append">
	                                            <button class="btn btn-outline-primary" type="button" id="apBtnUpdateShopStatus">Ustaw</button>
	                                        </div>
	                                    </div>
	                                    <small class="form-text text-muted" id="apShopStatusMsg">Zmiana statusu wykona się tak samo jak w panelu zamówienia.</small>
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                </div>
	            </div>
        </div>

        <ul class="nav nav-tabs ap-tabs" id="apOrderTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="apTabShipmentsLink" data-toggle="tab" href="#apTabShipments" role="tab" aria-controls="apTabShipments" aria-selected="true">
                    <i class="material-icons">local_shipping</i> Wysyłka
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="apTabDocsLink" data-toggle="tab" href="#apTabDocs" role="tab" aria-controls="apTabDocs" aria-selected="false">
                    <i class="material-icons">description</i> Dokumenty
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="apTabSettlementsLink" data-toggle="tab" href="#apTabSettlements" role="tab" aria-controls="apTabSettlements" aria-selected="false">
                    <i class="material-icons">paid</i> Rozliczenia Allegro
                </a>
            </li>
        </ul>

        <div class="tab-content ap-tab-content" id="apOrderTabsContent">
            <div class="tab-pane fade show active" id="apTabShipments" role="tabpanel" aria-labelledby="apTabShipmentsLink">
<div class="row pt-3">
            {* 3. WYSYŁKA - PANEL STEROWANIA *}
            <div class="col-12">
                <div class="card bg-light border-0 ap-subcard">
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
                        <div class="ap-smart-banner" style="background-color:{if $isSmartOrder}#fff2ec{else}#f7f8fa{/if}; border: 1px solid {if $isSmartOrder}rgba(255,90,0,.25){else}rgba(0,0,0,.06){/if}; padding: 12px 12px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; gap:12px;">
                            <div>
                                <strong style="color:{if $isSmartOrder}#ff5a00{else}#555{/if};"><i class="material-icons" style="font-size:14px;vertical-align:-2px;">local_shipping</i> ALLEGRO SMART!</strong><br>
                                {if $isSmartOrder}
                                    <small>Wysyłka opłacona przez Allegro.</small>
                                {else}
                                    <small>To nie jest wysyłka Smart (standardowa wysyłka).</small>
                                {/if}
                            </div>
                            <div style="text-align:right; border: 1px solid {if $isSmartOrder}rgba(255,90,0,.25){else}rgba(0,0,0,.08){/if}; padding: 6px 10px; background: #ffffff; border-radius:10px; min-width:120px;">
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

            <div class="tab-pane fade" id="apTabDocs" role="tabpanel" aria-labelledby="apTabDocsLink">
                <div class="pt-3">
                    {include file='./partials/documents_panel.tpl' allegro_data=$allegro_data}
                </div>
            </div>

            <div class="tab-pane fade" id="apTabSettlements" role="tabpanel" aria-labelledby="apTabSettlementsLink">
                {include file='./partials/settlements_panel.tpl' allegro_data=$allegro_data}
            </div>
        </div>

    </div>
</div>

<script>
(function() {
  function apInitAllegroProOrderDetails() {
    {literal}
    // UI: move this block below the two-column layout to use full page width (without overlapping left column)
    (function apMoveToFullWidth(){
      try {
        var root = document.getElementById('allegropro_order_details');
        if (!root || root.getAttribute('data-ap-wide') === '1') return;
        var rightCol = root.closest('.right-column');
        if (!rightCol) return;
        var mainRow = rightCol.closest('.row');
        if (!mainRow || !mainRow.parentNode) return;

        var wideRow = document.createElement('div');
        wideRow.className = 'row';
        wideRow.setAttribute('data-ap-wide-row', '1');
        var wideCol = document.createElement('div');
        wideCol.className = 'col-12';
        wideRow.appendChild(wideCol);
        wideCol.appendChild(root);
        mainRow.parentNode.insertBefore(wideRow, mainRow.nextSibling);
        root.setAttribute('data-ap-wide', '1');
      } catch (e) {}
    })();
        // UI: also move the Payments block below AllegroPro to full width (align with this section)
    (function apMovePaymentsToFullWidth(){
      try {
        var wideRow = document.querySelector('[data-ap-wide-row="1"]');
        if (!wideRow) return;
        var wideCol = wideRow.querySelector('.col-12');
        if (!wideCol) return;

        var payments = document.querySelector('#orderPayments, #orderPaymentsPanel, #order-payments, #order_payments, [data-role="order-payments"], .order-payments, .js-order-payments');
        if (!payments) {
          var cards = document.querySelectorAll('.card');
          for (var i = 0; i < cards.length; i++) {
            var h = cards[i].querySelector('.card-header');
            var txt = (h ? h.textContent : '') || '';
            if (/Płatnoś|Payments/i.test(txt)) { payments = cards[i]; break; }
          }
        }
        if (!payments || payments.getAttribute('data-ap-wide') === '1') return;

        payments.style.marginTop = '18px';
        // keep visuals consistent with AllegroPro block
        if (!payments.style.borderRadius) payments.style.borderRadius = '12px';
        if (!payments.style.overflow) payments.style.overflow = 'hidden';

        wideCol.appendChild(payments);
        payments.setAttribute('data-ap-wide', '1');
      } catch (e) {}
    })();

{/literal}

    var cfId = '{$allegro_data.order.checkout_form_id|escape:'javascript':'UTF-8'}';
    var accId = '{$allegro_data.order.id_allegropro_account|intval}';
    var cfRev = '{$allegro_data.allegro_revision|default:''|escape:'javascript':'UTF-8'}';

    // --- 0. Persist aktywnej zakładki (hash / sessionStorage) ---
    (function apTabsPersist(){
      try {
        var tabs = document.querySelectorAll('#apOrderTabs a[data-toggle="tab"]');
        if (!tabs || !tabs.length) return;
        tabs.forEach(function(a){
          a.addEventListener('click', function(){
            var h = a.getAttribute('href') || '';
            if (h && h.charAt(0) === '#') {
              try { sessionStorage.setItem('ap_order_tab', h); } catch(e) {}
              try { history.replaceState(null, '', h); } catch(e) { window.location.hash = h; }
            }
          });
        });

        var desired = window.location.hash || '';
        if (!desired) {
          try { desired = sessionStorage.getItem('ap_order_tab') || ''; } catch(e) {}
        }
        if (desired && document.querySelector('#apOrderTabs a[href="' + desired + '"]')) {
          try { $('#apOrderTabs a[href="' + desired + '"]').tab('show'); } catch(e) {}
        }
      } catch (e) {}
    })();

    // --- 0a. Fallback tabs (gdy bootstrap/jQuery tab() nie działa lub zniknie klasa active) ---
    (function apTabsFallback(){
      try {
        var links = Array.prototype.slice.call(document.querySelectorAll('#apOrderTabs a[href^="#"]'));
        var panes = Array.prototype.slice.call(document.querySelectorAll('#apOrderTabsContent .tab-pane'));
        if (!links.length || !panes.length) return;

        function show(hash){
          if (!hash || hash.charAt(0) !== '#') return;
          links.forEach(function(a){
            var h = a.getAttribute('href') || '';
            var on = (h === hash);
            a.classList.toggle('active', on);
            a.setAttribute('aria-selected', on ? 'true' : 'false');
          });
          panes.forEach(function(p){
            var on = ('#' + p.id) === hash;
            p.classList.toggle('active', on);
            p.classList.toggle('show', on);
          });
        }

        // jeśli żadna zakładka nie jest aktywna (np. po konflikcie JS) — ustaw domyślną
        var anyActive = panes.some(function(p){ return p.classList.contains('active') || p.classList.contains('show'); });
        if (!anyActive) {
          var desired = window.location.hash || '';
          if (!desired) { try { desired = sessionStorage.getItem('ap_order_tab') || ''; } catch(e) {} }
          if (!desired || !document.querySelector('#apOrderTabs a[href="' + desired + '"]')) {
            desired = links[0].getAttribute('href') || '#apTabShipments';
          }
          show(desired);
        }

        // przełączanie bez zależności od bootstrap tab()
        links.forEach(function(a){
          a.addEventListener('click', function(ev){
            // jeśli bootstrap tab() działa — zostawiamy mu sterowanie
            try { if (window.jQuery && jQuery.fn && jQuery.fn.tab) return; } catch(e) {}
            ev.preventDefault();
            var h = a.getAttribute('href') || '';
            show(h);
            if (h && h.charAt(0) === '#') {
              try { sessionStorage.setItem('ap_order_tab', h); } catch(e) {}
              try { history.replaceState(null, '', h); } catch(e) { window.location.hash = h; }
            }
          }, true);
        });
      } catch (e) {}
    })();

    // --- 0b. Rozliczenia Allegro: filtry + ręczne pobranie billing-entries ---
    (function apInitSettlementsTab(){
      var panel = document.getElementById('apSettlementsPanel');
      if (!panel) return;

      var rangeSel = document.getElementById('apBillingRange');
      var rangeHint = document.getElementById('apBillingRangeHint');
      var forceCb = document.getElementById('apBillingForce');
      var btn = document.getElementById('apBillingSyncBtn');
      var msg = document.getElementById('apBillingSyncMsg');

      var viewSel = document.getElementById('apBillingView');
      var catSel = document.getElementById('apBillingCat');
      var table = document.getElementById('apBillingTable');

      function getRange(){
        var v = (rangeSel && rangeSel.value) ? rangeSel.value : 'narrow';
        var from = panel.getAttribute('data-range-narrow-from') || '';
        var to = panel.getAttribute('data-range-narrow-to') || '';
        if (v === 'wide') {
          from = panel.getAttribute('data-range-wide-from') || from;
          to = panel.getAttribute('data-range-wide-to') || to;
        }
        return { value: v, from: from, to: to };
      }

      function setRangeHint(){
        if (!rangeHint) return;
        var r = getRange();
        rangeHint.textContent = r.from && r.to ? ('Zakres: ' + r.from + ' → ' + r.to) : '';
      }
      if (rangeSel) rangeSel.addEventListener('change', setRangeHint);
      setRangeHint();

      function applyBillingFilters(){
        if (!table) return;
        var v = viewSel ? (viewSel.value || 'fees') : 'fees';
        var cat = catSel ? (catSel.value || '') : '';
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function(tr){
          // puste wiersze (brak danych) pomijamy
          if (!tr.getAttribute('data-amt')) return;
          var amt = parseFloat(tr.getAttribute('data-amt') || '0');
          var rowCat = tr.getAttribute('data-cat') || '';

          var ok = true;
          if (v === 'fees') {
            // pokazuj tylko opłaty/zwroty (czyli wartości != 0)
            ok = ok && (Math.abs(amt) > 0.00001);
          }
          if (cat) {
            ok = ok && (rowCat === cat);
          }
          tr.style.display = ok ? '' : 'none';
        });
      }
      if (viewSel) viewSel.addEventListener('change', applyBillingFilters);
      if (catSel) catSel.addEventListener('change', applyBillingFilters);
      applyBillingFilters();

      function setMsg(text, isError){
        if (!msg) return;
        msg.className = 'form-text ' + (isError ? 'text-danger' : 'text-muted');
        msg.textContent = text || '';
      }

      function setBtnLoading(isLoading){
        if (!btn) return;
        var icon = btn.querySelector('.ap-sync-icon');
        var txt = btn.querySelector('.ap-sync-text');
        var label = btn.getAttribute('data-ap-label');
        if (!label) {
          label = (txt ? txt.textContent : (btn.textContent || 'Pobierz z Allegro'));
          try { btn.setAttribute('data-ap-label', label); } catch(e) {}
        }
        if (isLoading) {
          btn.disabled = true;
          if (icon) icon.classList.add('ap-spin');
          if (txt) { txt.textContent = 'Pobieram…'; } else { btn.textContent = 'Pobieram…'; }
        } else {
          btn.disabled = false;
          if (icon) icon.classList.remove('ap-spin');
          if (txt) { txt.textContent = label; } else { btn.textContent = label; }
        }
      }

      if (btn) {
        btn.addEventListener('click', function(e){
          e.preventDefault();
          var r = getRange();
          var accountId = panel.getAttribute('data-account-id') || '';
          var cf = panel.getAttribute('data-checkout-form-id') || '';
          var force = forceCb && forceCb.checked ? 1 : 0;

          if (!accountId || !cf || !r.from || !r.to) {
            setMsg('Brak parametrów do pobrania (konto/checkoutFormId/zakres).', true);
            return;
          }

          setBtnLoading(true);
          setMsg('Pobieranie billing-entries z Allegro…', false);

          var url = 'index.php?controller=AdminAllegroProSettlements&token={getAdminToken tab='AdminAllegroProSettlements'}&action=syncOrderBilling&ajax=1';
          var fd = new FormData();
          fd.append('id_allegropro_account', accountId);
          fd.append('checkout_form_id', cf);
          fd.append('date_from', r.from);
          fd.append('date_to', r.to);
          fd.append('force_update', force ? '1' : '0');

          fetch(url, { method: 'POST', body: fd })
            .then(function(resp){ return resp.json(); })
            .then(function(d){
              if (!d || !d.ok) {
                setMsg((d && d.error) ? d.error : 'Nie udało się pobrać billing-entries.', true);
                setBtnLoading(false);
                return;
              }
              setMsg('Synchronizacja zakończona — pobrano ' + (d.total||0) + ' (nowe: ' + (d.inserted||0) + ', zaktualizowane: ' + (d.updated||0) + '). Odświeżam widok…', false);
              try { sessionStorage.setItem('ap_order_tab', '#apTabSettlements'); } catch(e) {}
              setTimeout(function(){ window.location.reload(); }, 700);
            })
            .catch(function(){
              setMsg('Błąd połączenia podczas pobierania billing-entries.', true);
              setBtnLoading(false);
            });
        });
      }
    })();

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
            if (typeof cfRev !== 'undefined' && cfRev) {
                formData.append('checkout_form_revision', cfRev);
            }

            fetch('index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=update_status&ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                msg.className = data.success ? 'form-text text-success' : 'form-text text-danger';
                msg.innerText = data.message || (data.success ? 'OK' : 'Błąd');

                // odśwież widoczny status w panelu (bez reloadu)
                if (data && data.success) {
                    var lbl = document.getElementById('apAllegroStatusLabel');
                    var code = document.getElementById('apAllegroStatusCode');
                    if (lbl && data.current_status_label) {
                        lbl.innerText = data.current_status_label;
                    }
                    if (code && data.current_status) {
                        code.innerText = data.current_status;
                    }
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

	        // --- 1b. Zmiana statusu w sklepie (PrestaShop) ---
    (function apInitShopStatusBox(){
        var boxSelect = document.getElementById('apShopStatusSelect');
        var boxBtn = document.getElementById('apBtnUpdateShopStatus');
        var boxMsg = document.getElementById('apShopStatusMsg');
        var boxCur = document.getElementById('apShopStatusCurrent');
        if (!boxSelect || !boxBtn) return;

        var psOrderId = parseInt('{$allegro_data.order.id_order_prestashop|intval}', 10) || 0;
        if (!psOrderId) {
            if (boxMsg) {
                boxMsg.className = 'form-text text-danger';
                boxMsg.textContent = 'Brak ID zamówienia w PrestaShop.';
            }
            boxSelect.disabled = true;
            boxBtn.disabled = true;
            return;
        }

        boxSelect.disabled = false;
        boxBtn.disabled = false;

        boxBtn.addEventListener('click', function(e){
            e.preventDefault();
            var newStateId = (boxSelect.value || '').toString();
            if (!newStateId) {
                if (boxMsg) {
                    boxMsg.className = 'form-text text-danger';
                    boxMsg.textContent = 'Wybierz status.';
                }
                return;
            }

            boxBtn.disabled = true;
            var oldBtnText = boxBtn.textContent;
            boxBtn.textContent = 'Ustawiam…';
            if (boxMsg) {
                boxMsg.className = 'form-text text-muted';
                boxMsg.textContent = 'Zmieniam status w sklepie...';
            }

            var url = 'index.php?controller=AdminAllegroProOrders&token={getAdminToken tab="AdminAllegroProOrders"}&action=set_shop_status&ajax=1';
            var fd = new FormData();
            fd.append('id_order_prestashop', psOrderId);
            fd.append('id_order_state', newStateId);

            fetch(url, { method: 'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.success) {
                    if (boxMsg) {
                        boxMsg.className = 'form-text text-danger';
                        boxMsg.textContent = (d && d.message) ? d.message : 'Nie udało się zmienić statusu.';
                    }
                    return;
                }
                if (boxCur && d.current_status_name) {
                    boxCur.textContent = d.current_status_name;
                } else if (boxCur) {
                    // fallback: aktualna nazwa z opcji select
                    try {
                        var opt = boxSelect.options[boxSelect.selectedIndex];
                        if (opt) boxCur.textContent = opt.text;
                    } catch (e) {}
                }
                if (boxMsg) {
                    boxMsg.className = 'form-text text-success';
                    boxMsg.textContent = d.message || 'Status w sklepie zaktualizowany.';
                }
            })
            .catch(function(){
                if (boxMsg) {
                    boxMsg.className = 'form-text text-danger';
                    boxMsg.textContent = 'Błąd połączenia.';
                }
            })
            .finally(function(){
                boxBtn.disabled = false;
                boxBtn.textContent = oldBtnText || 'Ustaw';
            });
        });
    })();;

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


    // --- 4c. Dokumenty sprzedażowe z Allegro ---
    // Uwaga: Allegro API często zwraca metadane faktury, ale NIE udostępnia PDF do pobrania (brak direct_url/links).
    // Dlatego przy braku linku nie pokazujemy "Pobierz", tylko przycisk "PDF niedostępny" + modal z wyjaśnieniem.

    var initialOrderDocuments = {$allegro_data.documents_cache|default:[]|@json_encode nofilter};

    function apDocEsc(v) {
        return String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function apDocHumanStatus(raw) {
        var s = String(raw || '').toUpperCase();
        if (!s) return '—';
        if (s === 'ACCEPTED') return 'Zweryfikowana';
        if (s === 'REJECTED') return 'Odrzucona';
        if (s === 'PENDING' || s === 'IN_PROGRESS' || s === 'WAITING') return 'W trakcie weryfikacji';
        if (s === 'CREATED' || s === 'NEW') return 'Utworzona';
        return String(raw || '—');
    }

    function apDocFormatDate(raw) {
        if (!raw) return '—';
        try {
            var d = new Date(String(raw));
            if (isNaN(d.getTime())) return String(raw);
            var dd = String(d.getDate()).padStart(2, '0');
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var yy = d.getFullYear();
            var hh = String(d.getHours()).padStart(2, '0');
            var mi = String(d.getMinutes()).padStart(2, '0');
            return dd + '.' + mm + '.' + yy + ' ' + hh + ':' + mi;
        } catch (e) {
            return String(raw);
        }
    }

    function apDocBuildDownloadUrl(doc) {
        if (doc && doc.download_url) {
            return String(doc.download_url);
        }

        var p = [];
        p.push('controller=AdminAllegroProOrders');
        p.push('token={getAdminToken tab='AdminAllegroProOrders'}');
        p.push('ajax=1');
        p.push('action=downloadOrderDocumentFile');
        p.push('checkout_form_id=' + encodeURIComponent(cfId || ''));
        p.push('id_allegropro_account=' + encodeURIComponent(accId || ''));
        p.push('document_id=' + encodeURIComponent((doc && doc.id) ? doc.id : ''));
        p.push('document_type=' + encodeURIComponent((doc && doc.type) ? doc.type : 'Dokument'));
        p.push('document_number=' + encodeURIComponent((doc && doc.number) ? doc.number : ''));
        p.push('direct_url=' + encodeURIComponent((doc && doc.direct_url) ? doc.direct_url : ''));
        return 'index.php?' + p.join('&');
    }

    function apEnsureDocInfoModal() {
        if (document.getElementById('apDocInfoModal')) {
            return;
        }
        var html = ''
            + '<div class="modal fade" id="apDocInfoModal" tabindex="-1" role="dialog" aria-hidden="true">'
            + '  <div class="modal-dialog modal-md" role="document">'
            + '    <div class="modal-content" style="border-radius:14px; overflow:hidden;">'
            + '      <div class="modal-header" style="background:#f8f9fa;">'
            + '        <h5 class="modal-title" id="apDocInfoModalTitle" style="margin:0; font-weight:700;">Nie można pobrać PDF z Allegro</h5>'
            + '        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
            + '      </div>'
            + '      <div class="modal-body" id="apDocInfoModalBody" style="font-size:13px; line-height:1.45;"></div>'
            + '      <div class="modal-footer" style="background:#fff;">'
            + '        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Zamknij</button>'
            + '      </div>'
            + '    </div>'
            + '  </div>'
            + '</div>';
        document.body.insertAdjacentHTML('beforeend', html);
    }

    function apShowDocInfoModal(docType, docNumber, extraLine) {
        apEnsureDocInfoModal();

        var titleEl = document.getElementById('apDocInfoModalTitle');
        var bodyEl = document.getElementById('apDocInfoModalBody');

        if (titleEl) {
            titleEl.innerText = 'Nie można pobrać PDF z Allegro';
        }

        var lines = [];
        lines.push('<div style="margin-bottom:8px;"><strong>' + apDocEsc(docType || 'Dokument') + '</strong>'
            + (docNumber ? (' <span class="text-muted">(' + apDocEsc(docNumber) + ')</span>') : '')
            + '</div>');

        if (extraLine) {
            lines.push('<div class="text-muted" style="margin-bottom:10px;">' + apDocEsc(extraLine) + '</div>');
        }

        lines.push('<div style="margin-bottom:8px;">Allegro API zwraca metadane dokumentu, ale nie udostępnia linku do pobrania PDF (brak <code>direct_url</code>/<code>links</code>).</div>');
        lines.push('<div style="margin-top:10px;"><strong>Co możesz zrobić:</strong></div>');
        lines.push('<ul style="margin:8px 0 0 18px;">'
            + '<li>Dodaj link do faktury/e-paragonu przez <code>POST /order/&lt;orderId&gt;/billing-documents/links</code> (wtedy pojawi się w polu <code>links</code>).</li>'
            + '<li>Pobierz fakturę z systemu, w którym ją wystawiłeś (Fakturownia/ERP/księgowość).</li>'
            + '</ul>');

        if (bodyEl) {
            bodyEl.innerHTML = lines.join('');
        }

        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery('#apDocInfoModal').modal('show');
        } else {
            var el = document.getElementById('apDocInfoModal');
            if (el) { el.style.display = 'block'; }
        }
    }

    function apRenderOrderDocuments(docs) {
        var listBox = document.getElementById('order_documents_list');
        if (!listBox) {
            return;
        }

        if (!Array.isArray(docs) || !docs.length) {
            listBox.innerHTML = '<div class="text-muted" style="font-size:12px;">Brak dokumentów do wyświetlenia.</div>';
            return;
        }

        var header = '<div class="table-responsive"><table class="table table-sm table-striped" style="font-size:12px; margin-bottom:0;">'
            + '<thead><tr><th>Typ</th><th>Numer</th><th>Status</th><th>Data</th><th class="text-right">Akcja</th></tr></thead><tbody>';
        var footer = '</tbody></table></div>';

        var rows = [];
        docs.forEach(function(doc){
            var type = doc.type || 'Dokument';
            var number = doc.number || '—';
            var status = apDocHumanStatus(doc.status || '');
            var issued = apDocFormatDate(doc.issued_at || '');
            var canDownload = !!(doc.can_download || doc.direct_url);
            var url = apDocBuildDownloadUrl(doc);

            var actionHtml = '';
            if (canDownload) {
                actionHtml = '<a class="btn btn-xs btn-default ap-doc-download" href="' + apDocEsc(url) + '"'
                    + ' data-doc-type="' + apDocEsc(type) + '" data-doc-number="' + apDocEsc(number) + '" target="_blank">Pobierz</a>';
            } else {
                actionHtml = '<button type="button" class="btn btn-xs btn-outline-secondary ap-doc-unavailable"'
                    + ' data-doc-type="' + apDocEsc(type) + '" data-doc-number="' + apDocEsc(number) + '">'
                    + '<i class="material-icons" style="font-size:16px; vertical-align:middle; margin-right:4px;">info</i>'
                    + 'PDF niedostępny</button>';
            }

            rows.push('<tr>'
                + '<td>' + apDocEsc(type) + '</td>'
                + '<td>' + apDocEsc(number) + '</td>'
                + '<td>' + apDocEsc(status) + '</td>'
                + '<td>' + apDocEsc(issued) + '</td>'
                + '<td class="text-right">' + actionHtml + '</td>'
                + '</tr>');
        });

        listBox.innerHTML = header + rows.join('') + footer;
    }

    function apSafeParseJson(text) {
        try {
            return JSON.parse(text);
        } catch (e) {
            return null;
        }
    }

    // Globalna funkcja (używana też w auto-fetch).
    window.apFetchOrderDocuments = function(opts) {
        opts = opts || {};
        var silent = !!opts.silent;
        var keepList = !!opts.keepList;

        var msgBox = document.getElementById('order_documents_msg');
        var listBox = document.getElementById('order_documents_list');
        var debugToggle = document.getElementById('order_documents_debug');
        var debugBox = document.getElementById('order_documents_debug_box');
        var debugEnabled = !!(debugToggle && debugToggle.checked);

        if (!keepList && listBox) {
            listBox.innerHTML = '';
        }
        if (debugBox && !keepList) {
            debugBox.style.display = 'none';
            debugBox.innerText = '';
        }

        if (msgBox && !silent) {
            msgBox.className = 'text-muted';
            msgBox.innerText = 'Pobieranie listy dokumentów z Allegro...';
        }

        var fd = new FormData();
        fd.append('checkout_form_id', cfId);
        fd.append('id_allegropro_account', accId);
        fd.append('debug', debugEnabled ? 1 : 0);

        var url = 'index.php?controller=AdminAllegroProOrders&token={getAdminToken tab='AdminAllegroProOrders'}&action=getOrderDocuments&ajax=1';

        var handlePayload = function(data, rawText) {
            if (debugEnabled && debugBox && data && Array.isArray(data.debug_lines) && data.debug_lines.length) {
                debugBox.style.display = 'block';
                debugBox.innerText = data.debug_lines.join("\n");
            } else if (debugEnabled && debugBox && rawText && (!data || !data.debug_lines)) {
                debugBox.style.display = 'block';
                debugBox.innerText = rawText;
            }

            if (!data || !data.success || !Array.isArray(data.documents)) {
                if (msgBox && !silent) {
                    msgBox.className = 'text-danger';
                    msgBox.innerText = (data && data.message) ? data.message : 'Nie udało się pobrać dokumentów.';
                }
                return;
            }

            if (msgBox) {
                msgBox.className = 'text-success';
                msgBox.innerText = data.message || ('Pobrano listę dokumentów: ' + data.documents.length);
            }

            apRenderOrderDocuments(data.documents);
        };

        // fetch (z bezpiecznym parse) + fallback na jQuery
        if (window.fetch) {
            var fetchOpts = { method: 'POST', body: fd, credentials: 'same-origin' };
            return fetch(url, fetchOpts)
                .then(function(res){
                    return res.text().then(function(t){
                        var data = apSafeParseJson(t);
                        handlePayload(data, t);
                    });
                })
                .catch(function(err){
                    if (msgBox && !silent) {
                        msgBox.className = 'text-danger';
                        msgBox.innerText = 'Błąd połączenia podczas pobierania dokumentów.';
                    }
                    if (debugEnabled && debugBox) {
                        debugBox.style.display = 'block';
                        debugBox.innerText = String(err || '');
                    }
                });
        }

        if (window.jQuery) {
            return window.jQuery.ajax({
                url: url,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false
            }).done(function(respText){
                var data = (typeof respText === 'string') ? apSafeParseJson(respText) : respText;
                handlePayload(data, (typeof respText === 'string') ? respText : '');
            }).fail(function(xhr){
                if (msgBox && !silent) {
                    msgBox.className = 'text-danger';
                    msgBox.innerText = 'Błąd połączenia podczas pobierania dokumentów.';
                }
                if (debugEnabled && debugBox) {
                    debugBox.style.display = 'block';
                    debugBox.innerText = (xhr && xhr.responseText) ? xhr.responseText : '';
                }
            });
        }
    };

    var orderDocsMsgBox = document.getElementById('order_documents_msg');
    if (Array.isArray(initialOrderDocuments) && initialOrderDocuments.length) {
        apRenderOrderDocuments(initialOrderDocuments);
        if (orderDocsMsgBox) {
            orderDocsMsgBox.className = 'text-info';
            orderDocsMsgBox.innerText = 'Załadowano z zapisanej listy dokumentów: ' + initialOrderDocuments.length;
        }
    }

    // Obsługa klików w tabeli (download / debug / modal)
    var orderDocsListBox = document.getElementById('order_documents_list');
    if (orderDocsListBox && !orderDocsListBox.getAttribute('data-ap-docs-bound')) {
        orderDocsListBox.setAttribute('data-ap-docs-bound', '1');

        orderDocsListBox.addEventListener('click', function(e){
            var target = e.target;

            var unavailableBtn = target ? target.closest('button.ap-doc-unavailable') : null;
            if (unavailableBtn) {
                e.preventDefault();
                apShowDocInfoModal(unavailableBtn.getAttribute('data-doc-type') || 'Dokument', unavailableBtn.getAttribute('data-doc-number') || '', '');
                return;
            }

            var link = target ? target.closest('a.ap-doc-download') : null;
            if (!link) {
                return;
            }

            var debugToggle = document.getElementById('order_documents_debug');
            var debugEnabled = !!(debugToggle && debugToggle.checked);
            if (!debugEnabled) {
                return; // normalne pobieranie w nowej karcie
            }

            e.preventDefault();

            var debugBox = document.getElementById('order_documents_debug_box');
            var msgBox = document.getElementById('order_documents_msg');
            var baseUrl = link.getAttribute('href') || '';
            var debugUrl = baseUrl;

            if (debugUrl.indexOf('debug=') === -1) {
                debugUrl += (debugUrl.indexOf('?') !== -1 ? '&' : '?') + 'debug=1';
            }

            if (msgBox) {
                msgBox.className = 'text-warning';
                msgBox.innerText = 'Tryb debug pobierania dokumentu: ' + (link.getAttribute('data-doc-type') || 'Dokument') + ' ' + (link.getAttribute('data-doc-number') || '');
            }
            if (debugBox) {
                debugBox.style.display = 'block';
                debugBox.innerText = 'Pobieranie danych debug dokumentu...';
            }

            // debug endpoint zwraca JSON
            if (window.fetch) {
                fetch(debugUrl, { credentials: 'same-origin' })
                    .then(function(resp){ return resp.text(); })
                    .then(function(t){
                        var data = apSafeParseJson(t);
                        if (!data) {
                            if (debugBox) { debugBox.innerText = t; }
                            return;
                        }
                        var lines = [];
                        lines.push('success=' + (!!data.success ? '1' : '0'));
                        lines.push('message=' + (data.message || 'brak'));
                        if (typeof data.http_code !== 'undefined') { lines.push('http_code=' + data.http_code); }
                        if (data.file_name) { lines.push('file_name=' + data.file_name); }
                        if (data.document) {
                            lines.push('document.checkout_form_id=' + (data.document.checkout_form_id || ''));
                            lines.push('document.document_id=' + (data.document.document_id || ''));
                            lines.push('document.document_type=' + (data.document.document_type || ''));
                            lines.push('document.document_number=' + (data.document.document_number || ''));
                            lines.push('document.direct_url=' + (data.document.direct_url || ''));
                        }
                        if (Array.isArray(data.diagnosis) && data.diagnosis.length) {
                            lines.push('--- DIAGNOSIS ---');
                            data.diagnosis.forEach(function(item, idx){ lines.push((idx + 1) + '. ' + item); });
                        }
                        if (Array.isArray(data.attempts) && data.attempts.length) {
                            lines.push('--- ATTEMPTS ---');
                            data.attempts.forEach(function(at, idx){
                                lines.push('#' + (idx + 1)
                                    + ' type=' + (at.type || '')
                                    + ' target=' + (at.target || '')
                                    + ' http=' + (typeof at.http_code !== 'undefined' ? at.http_code : '')
                                    + ' ok=' + (at.ok ? '1' : '0')
                                    + ' bytes=' + (typeof at.bytes !== 'undefined' ? at.bytes : '')
                                    + (at.error_code ? (' error_code=' + at.error_code) : '')
                                    + (at.error_user_message ? (' error_user_message=' + at.error_user_message) : ''));
                            });
                        }
                        if (Array.isArray(data.debug_lines) && data.debug_lines.length) {
                            lines.push('--- DEBUG LINES ---');
                            lines = lines.concat(data.debug_lines);
                        }
                        if (debugBox) { debugBox.innerText = lines.join("\n"); }
                    })
                    .catch(function(err){
                        if (debugBox) { debugBox.innerText = String(err || ''); }
                    });
            }
        });
    }

    // Klik przycisku ręcznego pobrania listy
    var btnFetchDocs = document.getElementById('btnFetchOrderDocuments');
    if (btnFetchDocs && !btnFetchDocs.getAttribute('data-ap-docs-bound')) {
        btnFetchDocs.setAttribute('data-ap-docs-bound', '1');
        btnFetchDocs.addEventListener('click', function(e){
            e.preventDefault();
            btnFetchDocs.disabled = true;

            var p = window.apFetchOrderDocuments({ silent: false, keepList: false });
            if (p && typeof p.finally === 'function') {
                p.finally(function(){ btnFetchDocs.disabled = false; });
            } else {
                setTimeout(function(){ btnFetchDocs.disabled = false; }, 1200);
            }
        });
    }

    // AUTO: po wejściu w zamówienie – pobierz listę z Allegro
    setTimeout(function(){
        if (typeof window.apFetchOrderDocuments === 'function') {
            window.apFetchOrderDocuments({ silent: true, keepList: true });
        }
    }, 250);

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

{literal}
    // --- Tracking: tłumaczenie EN -> PL (fallback po stronie UI, działa też dla danych z cache) ---
    function apTrTrackLabel(input) {
        var t = (input || '').toString().trim();
        if (!t) return '—';
        var up = t.toUpperCase();
        var codeMap = { 
            'DELIVERED': 'Dostarczona',
            'OUT_FOR_DELIVERY': 'W doręczeniu',
            'READY_FOR_PICKUP': 'Gotowa do odbioru',
            'AVAILABLE_FOR_PICKUP': 'Gotowa do odbioru',
            'PLACED_IN_PICKUP_PARCEL_LOCKER': 'Umieszczono w paczkomacie (gotowa do odbioru)',
            'IN_TRANSIT': 'W drodze',
            'ACCEPTED': 'Przyjęto w oddziale',
            'ACCEPTED_AT_INPOST_DELIVERY_CENTER': 'Przyjęto w oddziale InPost',
            'PREPARED_BY_SENDER': 'Przygotowana przez nadawcę',
            'CREATED': 'Utworzona',
            'RETURNED': 'Zwrócona',
            'CANCELLED': 'Anulowana',
            'FAILED': 'Nieudana próba doręczenia'
        };
        if (codeMap[up]) return codeMap[up];

        var low = t.toLowerCase();
        var phraseMap = { 
            'delivered': 'Dostarczona',
            'placed in pick-up parcel locker': 'Umieszczono w paczkomacie (gotowa do odbioru)',
            'ready for pickup': 'Gotowa do odbioru',
            'available for pickup': 'Gotowa do odbioru',
            'handed over for delivery': 'Przekazano do doręczenia',
            'out for delivery': 'W doręczeniu',
            'accepted at inpost delivery center': 'Przyjęto w oddziale InPost',
            'accepted at delivery center': 'Przyjęto w oddziale',
            'in transit': 'W drodze',
            'prepared by sender': 'Przygotowana przez nadawcę',
            'prepared by the sender': 'Przygotowana przez nadawcę',
            'shipment created': 'Utworzona',
            'returned to sender': 'Zwrócona do nadawcy',
            'delivery failed': 'Nieudana próba doręczenia'
        };
        for (var needle in phraseMap) {
            if (!Object.prototype.hasOwnProperty.call(phraseMap, needle)) continue;
            if (low.indexOf(needle) !== -1) return phraseMap[needle];
        }
        return t; // jeśli brak mapowania — pokaż oryginał
    }

    function apTrTrackSeverity(code, labelPl) {
        var c = (code || '').toString().toUpperCase();
        var l = (labelPl || '').toString().toLowerCase();
        if (c.indexOf('DELIVER') !== -1 || l.indexOf('dostarcz') !== -1) return 'success';
        if (/FAILED|ERROR|EXCEPTION|LOST|DAMAGED|RETURN|CANCEL/.test(c) || l.indexOf('nieudan') !== -1 || l.indexOf('zwro') !== -1) return 'danger';
        if (/OUT_FOR_DELIVERY|READY|PICKUP|COLLECT|AWAIT|WAIT/.test(c) || l.indexOf('odbior') !== -1 || l.indexOf('doręcze') !== -1 || l.indexOf('dorecze') !== -1) return 'warning';
        if (c) return 'info';
        return 'secondary';
    }

    function apTrTrackShort(labelPl) {
        var l = (labelPl || '').toString();
        var low = l.toLowerCase();
        if (low.indexOf('paczkomac') !== -1 || low.indexOf('odbioru') !== -1) return 'Do odbioru';
        if (low.indexOf('w doręczeniu') !== -1 || low.indexOf('w doreczeniu') !== -1) return 'W doręczeniu';
        if (low.indexOf('w drodze') !== -1) return 'W drodze';
        if (low.indexOf('dostarcz') !== -1) return 'Dostarczona';
        if (low.indexOf('przyję') !== -1 || low.indexOf('przyje') !== -1) return 'Przyjęta';
        if (low.indexOf('przygotowan') !== -1) return 'Przygot.';
        if (l.length > 24) return l.slice(0, 23) + '…';
        return l || '—';
    }
{/literal}

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

        // Weź cokolwiek mamy (PL z backendu lub EN z cache) i przetłumacz po stronie UI
        var currentBase = (current && (current.label_pl || current.label || current.status)) ? (current.label_pl || current.label || current.status) : '';
        var labelPl = apTrTrackLabel(currentBase);
        var shortPl = apTrTrackShort(labelPl);
        var dt = (current && current.occurred_at_formatted) ? current.occurred_at_formatted : '';
        var severity = apTrTrackSeverity((current && current.status) ? current.status : '', labelPl);
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
            var base = (ev && (ev.label_pl || ev.label || ev.status)) ? (ev.label_pl || ev.label || ev.status) : '';
            var labelEvPl = apTrTrackLabel(base);
            var sev = apTrTrackSeverity(ev.status || '', labelEvPl);
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
            text.textContent = labelEvPl || base || '';

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
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', apInitAllegroProOrderDetails);
  } else {
    apInitAllegroProOrderDetails();
  }
})();
</script>
