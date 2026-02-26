<div class="panel">
    <div class="panel-heading"><i class="icon-cogs"></i> MODUŁ ZAMÓWIEŃ</div>
    <div class="row">
        
        {* --- LEWA STRONA: WGRAJ DOSTAWĘ (KROK 1) --- *}
        <div class="col-md-6">
            <form action="{$surplus_form_action}" method="post" enctype="multipart/form-data">
                <div class="panel" style="background:#dff0d8; border: 1px solid #d6e9c6;">
                    <div class="panel-heading" style="background-color:#dff0d8; font-weight:bold; color:#3c763d;">
                        1. WGRAJ DOSTAWĘ (Pliki od hurtowni)
                    </div>
                    <div class="panel-body">
                        <label>Wybierz pliki CSV (możesz zaznaczyć kilka):</label>
                        <input type="file" name="confirmation_file[]" multiple class="form-control" style="margin-bottom:15px;" />
                        
                        <label>Zaznacz zrealizowane zamówienia (do odjęcia):</label>
                        <p class="help-block" style="font-size:11px; margin-top:0; margin-bottom:5px;">
                            Wybierz z listy zamówienia, które są zawarte w tym pliku dostawy.
                        </p>
                        
                        {* --- LISTA CHECKBOXÓW --- *}
                        <div style="height: 280px; overflow-y: scroll; border: 1px solid #ccc; background: #fff; padding: 10px; border-radius:4px;">
                            {assign var="yesterday_ts" value=$smarty.now-86400}
                            {assign var="yesterday_date" value=$yesterday_ts|date_format:"%Y-%m-%d"}
                            {assign var="current_date" value=""}
                            
                            {foreach from=$history_list item=h name=history_loop}
                                {if $smarty.foreach.history_loop.iteration > 20}{break}{/if}
                                {assign var="item_date" value=$h.date_add|date_format:"%Y-%m-%d"}
                                {assign var="is_bio" value=$h.supplier_name|lower|strpos:"bio" !== false}
                                {assign var="is_extra" value=$h.supplier_name|strstr:"[EXTRA]"}
                                {assign var="is_yesterday" value=($item_date == $yesterday_date)}
                                
                                {if $current_date != $item_date}
                                    <div style="background: #eee; padding: 5px; font-weight: bold; margin-top: 10px; margin-bottom: 5px; border-radius: 3px; font-size: 11px; color:#555;">
                                        <i class="icon-calendar"></i> DATA: {$item_date} {if $is_yesterday}<span class="label label-warning pull-right">WCZORAJ</span>{/if}
                                    </div>
                                    {assign var="current_date" value=$item_date}
                                {/if}

                                <div class="checkbox" style="margin: 2px 0; padding: 5px; border-bottom: 1px solid #f0f0f0;">
                                    <label style="width:100%; cursor:pointer;">
                                        <input type="checkbox" name="compare_history_id[]" value="{$h.id_history}">
                                        {if $is_extra}
                                            <i class="icon-warning-sign" style="color:red; margin-right:5px;"></i><b style="color:#d9534f; font-size:1.1em;">{$h.supplier_name}</b>
                                        {elseif $is_bio}
                                            <i class="icon-star" style="color:#ff9900;"></i> <b>{$h.supplier_name|truncate:30}</b>
                                        {else}
                                            {$h.supplier_name|truncate:30}
                                        {/if}
                                        <span class="pull-right text-muted" style="font-size:11px;">Godz: {$h.date_add|date_format:"%H:%M"} | Poz: {$h.items_count}</span>
                                    </label>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" name="submitUploadConfirmation" class="btn btn-success pull-right">
                            <i class="icon-upload"></i> WGRAJ I OBLICZ NADWYŻKĘ
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {* --- PRAWA STRONA: ANALIZA ZAMÓWIEŃ (KROK 2) --- *}
        <div class="col-md-6">
            <form action="{$current_url}&token={$token}" method="post" enctype="multipart/form-data">
                <div class="panel" style="background:#f9f9f9; border: 1px solid #ccc;">
                    <div class="panel-heading" style="font-weight:bold; color:#555;">
                        2. ANALIZA ZAMÓWIEŃ (Nowe zamówienia Klientów)
                    </div>
                    <div class="panel-body">
                        <div style="padding: 50px 0; text-align: center;">
                            <i class="icon-shopping-cart" style="font-size: 40px; color:#ddd; margin-bottom:10px;"></i>
                            <p>Wgraj plik z nowymi zamówieniami,<br>aby sprawdzić pokrycie w magazynie.</p>
                        </div>
                        <input type="file" name="order_file" class="form-control" />

                        {assign var="consume_pick_table" value=$consume_pick_table_default|default:1}
                        <div class="form-group" style="margin-top:15px; text-align:left;">
                            <label style="font-weight:bold; display:block; margin-bottom:5px;">
                                Pick Stół – ściągaj ze stołu po wgraniu?
                            </label>
                            <p class="help-block" style="margin-top:0; margin-bottom:10px; font-size:11px;">
                                Jeśli ustawisz <b>NIE</b>, analiza nie zmieni stanów Pick Stołu (Wirtualnego Magazynu) i nie doda zadań do zakładki 4.
                                To ustawienie jest przydatne do testów.
                            </p>

                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="consume_pick_table" id="consume_pick_table_on" value="1" {if $consume_pick_table == 1}checked="checked"{/if} />
                                <label for="consume_pick_table_on">TAK</label>
                                <input type="radio" name="consume_pick_table" id="consume_pick_table_off" value="0" {if $consume_pick_table == 0}checked="checked"{/if} />
                                <label for="consume_pick_table_off">NIE</label>
                                <a class="slide-button btn"></a>
                            </span>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" name="submitResetAnalysis" class="btn btn-danger" onclick="return confirm('Czy na pewno wyczyścić dane analizy? (Zakładki 1-3)');">
                            <i class="icon-trash"></i> RESET ANALIZY (TEST)
                        </button>

                        <button type="submit" name="submitUploadCsv" class="btn btn-primary pull-right">
                            <i class="icon-search"></i> ANALIZUJ ZAMÓWIENIA
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel" style="margin-top:20px;">
    <ul class="nav nav-tabs" id="myTab">
        {if isset($report_data)}
            <li class="active"><a href="#report" data-toggle="tab">1. RAPORT</a></li>
            <li><a href="#picking" data-toggle="tab" style="font-weight:bold; color:#d9534f;">2. MAGAZYN (Edycja)</a></li>
            <li><a href="#orders" data-toggle="tab" style="font-weight:bold; color:#007aff;">3. DO ZAMÓWIENIA</a></li>
            <li><a href="#picktable" data-toggle="tab" style="font-weight:bold; color:#ff9900;">4. PICK STÓŁ (Do zebrania)</a></li>
            <li><a href="#extra" data-toggle="tab" style="font-weight:bold; color:#9c27b0;"><i class="icon-plus"></i> EXTRA DODATKI</a></li>
        {/if}
        <li {if !isset($report_data)}class="active"{/if}><a href="#surplus" data-toggle="tab" style="font-weight:bold; color:#5cb85c;"><i class="icon-inbox"></i> WIRTUALNY MAGAZYN (DB)</a></li>
        <li><a href="#history" data-toggle="tab" style="color:#555;">5. HISTORIA</a></li>
        {if isset($report_data)}
            <li><a href="#mobile_monitor" data-toggle="tab" style="font-weight:bold; color:#fff; background-color:#333;"><i class="icon-mobile-phone"></i> 6. ZBIERANIE MOBILE</a></li>
        {/if}
        <li>
            <a href="#mobile_stock_qr" data-toggle="tab" style="font-weight:bold; color:#fff; background-color:#e65100; border-color:#e65100;">
                <i class="icon-download-alt"></i> 7. ZATOWAROWANIE MOBILE
            </a>
        </li>
    </ul>

    <div class="tab-content">
        {if isset($report_data)}
            <div class="tab-pane active" id="report">{include file='./tab_report.tpl' analysis_data=$report_data}</div>
            <div class="tab-pane" id="picking">{include file='./tab_picking.tpl' analysis_data=$report_data}</div>
            <div class="tab-pane" id="orders">{include file='./tab_orders.tpl'}</div>
            <div class="tab-pane" id="picktable">{include file='./tab_pick_table.tpl' analysis_data=$report_data}</div>
            <div class="tab-pane" id="extra">{include file='./tab_extra.tpl'}</div>
            <div class="tab-pane" id="mobile_monitor">{include file='./tab_mobile_monitor.tpl'}</div>
        {/if}
        <div class="tab-pane {if !isset($report_data)}active{/if}" id="surplus">{include file='./tab_surplus.tpl'}</div>
        <div class="tab-pane" id="history">{include file='./tab_history.tpl'}</div>
        <div class="tab-pane" id="mobile_stock_qr">{include file='./tab_mobile_stock_qr.tpl'}</div>
    </div>
</div>

{* --- MODAL OSTRZEGAWCZY (DUPLIKAT) --- *}
<div id="duplicateFileModal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border: 3px solid #ff9900; box-shadow: 0 0 20px rgba(255, 153, 0, 0.3);">
            <div class="modal-header" style="background: #fff3e0; color: #e65100; border-bottom: 1px solid #ffe0b2;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" style="font-weight:bold;"><i class="icon-warning-sign icon-large"></i> UWAGA! DUPLIKAT PLIKU</h4>
            </div>
            <div class="modal-body" style="padding: 30px; text-align:center;">
                <i class="icon-copy" style="font-size: 60px; color: #ff9900; margin-bottom: 20px;"></i>
                <p style="font-size: 18px; font-weight: bold; color: #333;">Ten plik został już wgrany!</p>
                
                <p style="font-size: 14px; color: #666; margin-top: 10px;">
                    {if isset($duplicate_file_error)}{$duplicate_file_error}{else}System wykrył, że ten plik został już przetworzony w bieżącej sesji.{/if}
                </p>
                
                <div class="alert alert-warning" style="margin-top: 20px; text-align: left;">
                    <strong>Co to oznacza?</strong><br>
                    Produkty z tego pliku <u>NIE</u> zostały dodane ponownie do listy, aby uniknąć dublowania ilości do zebrania.
                </div>
            </div>
            <div class="modal-footer" style="text-align: center;">
                <button type="button" class="btn btn-warning btn-lg" data-dismiss="modal" style="width: 50%;">
                    <i class="icon-check"></i> ROZUMIEM, ZAMKNIJ
                </button>
            </div>
        </div>
    </div>
</div>

{* --- JS TRIGGER DLA MODALA --- *}
{if isset($duplicate_file_error)}
<script>
    $(document).ready(function() {
        $('#duplicateFileModal').modal('show');
    });
</script>
{/if}

<script>
    var ajax_picking_url = "{$ajax_picking_url nofilter}";
    var ajax_table_pick_url = "{$ajax_table_pick_url nofilter}";
    var ajax_refresh_url = "{$link->getAdminLink('AdminModulOrders')}&ajax=1&action=refreshOrders";
    var ajax_history_delete_url = "{$link->getAdminLink('AdminModulOrders')}&ajax=1&action=deleteHistory";
    var ajax_add_extra_url = "{$link->getAdminLink('AdminModulExtra')}&ajax=1";
    var ajax_check_state_url = "{$link->getAdminLink('AdminModulZamowien')}&ajax=1&action=checkPickingState";
    var ajax_init_mobile_url = "{$link->getAdminLink('AdminModulZamowien')}&ajax=1&action=initMobileSession";
</script>