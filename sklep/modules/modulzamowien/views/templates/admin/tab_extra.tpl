{* Widok Zakładki: EXTRA DODATKI (Ręczne domawianie) *}
{* Wersja: 16.0 - Modal 1100px *}

<script type="text/javascript">
    var token_admin_extra = "{$token|escape:'html':'UTF-8'}";
    var ajax_add_extra_url = "{$link->getAdminLink('AdminModulExtra')|escape:'quotes':'UTF-8'}&ajax=1";
</script>

<style>
    .extra-img-wrapper { position: relative; width: 40px; height: 40px; margin: 0 auto; }
    .extra-product-thumb { width: 100%; height: 100%; object-fit: contain; border: 1px solid #e5e5e5; background: #fff; cursor: zoom-in; transition: transform 0.2s ease-in-out; position: relative; z-index: 1; border-radius: 3px; }
    .extra-product-thumb:hover { transform: scale(5.5); z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border: 1px solid #fff; }
    #extra_search_results table td { vertical-align: middle !important; }
    
    #warehouseGuardModal .modal-header { background: #f8d7da; color: #721c24; border-bottom: 1px solid #f5c6cb; border-radius: 5px 5px 0 0; }
    #warehouseGuardModal .modal-title { font-weight: bold; font-size: 1.2em; display: flex; align-items: center; }
    #warehouseGuardModal .modal-title i { margin-right: 10px; font-size: 1.4em; }
    .wms-location-list { list-style: none; padding: 0; margin: 15px 0; }
    .wms-location-item { background: #fff; border: 1px solid #ddd; padding: 10px 15px; margin-bottom: 8px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; font-size: 1.1em; border-left: 5px solid #0099cc; }
    .wms-loc-name { font-weight: bold; color: #333; }
    .wms-loc-qty { background: #e0f7fa; color: #006064; padding: 3px 10px; border-radius: 10px; font-weight: bold; }

    #alternativesModal .modal-header { background: #fff3cd; color: #856404; border-bottom: 1px solid #ffeeba; }
    #alternativesModal .modal-title { font-weight: bold; }
    
    /* MODAL NIEDOBORU */
    #partialStockModal .modal-header { background: #d9edf7; color: #31708f; border-bottom: 1px solid #bce8f1; }
    #partialStockModal .modal-body { text-align: center; padding: 20px; }
    .psm-big-number { font-size: 2em; font-weight: bold; color: #d9534f; }
</style>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-plus-sign"></i> RĘCZNE DOMAWIANIE (ZATOWAROWANIE)
    </div>
    <div class="panel-body">
        
        <div class="row" style="margin-bottom: 20px; background: #f4f8fb; padding: 15px; border-radius: 5px; border: 1px solid #dbe6ef;">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="extra_search_input" style="font-size: 1.2em; color: #007aff;">
                        <i class="icon-search"></i> Szukaj produktu (Nazwa lub EAN):
                    </label>
                    <div class="input-group">
                        <input type="text" id="extra_search_input" class="form-control input-lg" placeholder="Wpisz nazwę, EAN lub SKU..." style="font-size: 16px;">
                        <span class="input-group-btn">
                            <button class="btn btn-primary btn-lg" type="button" id="btn-extra-search">
                                <i class="icon-search"></i> SZUKAJ
                            </button>
                        </span>
                    </div>
                    <p class="help-block"><small>Wpisz min. 3 znaki. Produkty z magazynu (A_MAG) będą na górze i pokażą lokalizacje.</small></p>
                </div>
            </div>
        </div>

        <div id="extra_search_results" style="display:none; margin-bottom: 30px;">
            <table class="table table-bordered table-striped" style="table-layout: fixed;">
                <thead>
                    <tr style="background: #eee;">
                        <th style="width: 50px;" class="text-center">Foto</th>
                        <th style="width: auto;">Produkt</th>
                        <th style="width: 140px;">EAN</th>
                        <th style="width: 190px;">SKU / Partia</th>
                        <th class="text-center" style="width: 80px;">Cena</th>
                        <th class="text-center" style="width: 80px;">Stan</th>
                        <th class="text-center" style="width: 90px;">Ilość</th>
                        <th class="text-center" style="width: 110px;">Akcja</th>
                    </tr>
                </thead>
                <tbody id="extra_results_body"></tbody>
            </table>
        </div>

        <div class="alert alert-info">
            <i class="icon-info-sign"></i> Produkty dodane tutaj pojawią się w zakładce <b>3. DO ZAMÓWIENIA</b>. Dzięki temu po zapisaniu historii, system <span style="text-decoration: underline;">nie potraktuje ich jako nadwyżki</span> przy dostawie.
        </div>

        <h4>Twoja lista dodatkowa:</h4>
        <table class="table table-bordered" id="extra_items_table">
            <thead>
                <tr>
                    <th style="width: 130px;">EAN</th>
                    <th>Nazwa Produktu</th>
                    <th class="text-center" style="width: 100px;">Ilość</th>
                    <th class="text-center" style="width: 100px;">Akcja</th>
                </tr>
            </thead>
            <tbody>
                {if isset($extra_items) && count($extra_items) > 0}
                    {foreach from=$extra_items item=item}
                        <tr data-id="{$item.id_extra}">
                            <td>{$item.ean}</td>
                            <td>{$item.name}</td>
                            <td class="text-center" style="font-weight:bold; font-size:1.2em;">{$item.qty}</td>
                            <td class="text-center">
                                <button class="btn btn-danger btn-xs btn-remove-extra" data-db-id="{$item.id_extra}">
                                    <i class="icon-trash"></i> USUŃ
                                </button>
                            </td>
                        </tr>
                    {/foreach}
                {else}
                    <tr class="empty-row"><td colspan="4" class="text-center text-muted">Brak dodatkowych pozycji.</td></tr>
                {/if}
            </tbody>
        </table>
        
        <div style="margin-top: 15px; text-align: right;">
            <button type="button" class="btn btn-danger" id="btn-clear-extra">
                <i class="icon-trash"></i> WYCZYŚĆ LISTĘ (Przenieś do Historii)
            </button>
            <button type="button" class="btn btn-default" onclick="refreshExtraTable();">
                <i class="icon-refresh"></i> ODŚWIEŻ
            </button>
        </div>
    </div>
</div>

{* --- MODAL OSTRZEGAWCZY (Strażnik) --- *}
<div id="warehouseGuardModal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="icon-warning-sign"></i> UWAGA! PRODUKT NA STANIE</h4>
            </div>
            <div class="modal-body">
                <p style="font-size: 1.1em;">Ten produkt znajduje się już na magazynie WMS w następujących lokalizacjach:</p>
                <div id="warehouseGuardList"></div>
                <p class="text-danger" style="margin-top: 15px; font-weight: bold;">
                    Czy na pewno chcesz zamówić go w hurtowni (ignorując stan magazynowy)?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-lg" data-dismiss="modal" style="float:left;">
                    <i class="icon-arrow-left"></i> Anuluj (Biorę z półki)
                </button>
                <button type="button" class="btn btn-danger btn-lg" id="warehouseGuardConfirmBtn">
                    ZAMAWIAM W HURTOWNI <i class="icon-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{* --- MODAL: ALTERNATYWY PRODUKTÓW --- *}
<div id="alternativesModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="icon-lightbulb"></i> NIE ZNALEZIONO DOKŁADNEGO PRODUKTU...</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    Nie znaleźliśmy produktu pasującego idealnie do zapytania: <strong id="alt_original_query"></strong>.<br>
                    <div id="alt_source_name_box" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #faebcc; display:none;"></div>
                </div>
                
                <h4 style="margin-top:20px; font-weight:bold; color:#555;">ZAMIENNIKI (Podobna waga)</h4>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width:50px;">Foto</th>
                                <th>Nazwa Produktu</th>
                                <th>EAN</th>
                                <th class="text-center">Stan</th>
                                <th class="text-center" style="width:80px;">Cena</th> 
                                <th class="text-center" style="width:80px;">Ilość</th>
                                <th class="text-center" style="width:100px;">Akcja</th>
                            </tr>
                        </thead>
                        <tbody id="alternatives_list_body"></tbody>
                    </table>
                </div>

                <div id="smaller_weights_section" style="display:none;">
                    <h4 style="margin-top:30px; font-weight:bold; color:#007aff; border-bottom:2px solid #eee; padding-bottom:10px;">
                        <i class="icon-cubes"></i> ZŁÓŻ Z MNIEJSZYCH OPAKOWAŃ
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th style="width:50px;">Foto</th>
                                    <th>Nazwa Produktu</th>
                                    <th>EAN</th>
                                    <th class="text-center">Stan</th>
                                    <th class="text-center" style="width:80px;">Cena</th> 
                                    <th class="text-center" style="width:80px;">Ilość</th>
                                    <th class="text-center" style="width:100px;">Akcja</th>
                                </tr>
                            </thead>
                            <tbody id="smaller_weights_body"></tbody>
                        </table>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">ZAMKNIJ</button>
            </div>
        </div>
    </div>
</div>

{* --- MODAL: NIEDOBÓR / UZUPEŁNIANIE (ZWIĘKSZONO SZEROKOŚĆ DO 1100px) --- *}
<div id="partialStockModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document" style="width: 1100px; max-width:95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="icon-info-sign"></i> NIEWYSTARCZAJĄCY STAN MAGAZYNOWY</h4>
            </div>
            <div class="modal-body">
                <h4 id="psm_product_name" style="margin-top:0; color:#333;"></h4>
                <hr>
                <div class="row">
                    <div class="col-xs-4">
                        <div class="psm-big-number" style="color:green;" id="psm_available">0</div>
                        <small>NA STANIE</small>
                    </div>
                    <div class="col-xs-4">
                        <div class="psm-big-number" style="color:#555;" id="psm_needed">0</div>
                        <small>POTRZEBA</small>
                    </div>
                    <div class="col-xs-4">
                        <div class="psm-big-number" id="psm_missing">0</div>
                        <small>BRAKUJE</small>
                    </div>
                </div>
                
                <div id="psm_complementary_list_container"></div>
                
                <div id="psm_actions" style="margin-top:20px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">ANULUJ</button>
            </div>
        </div>
    </div>
</div>