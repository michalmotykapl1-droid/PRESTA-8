<script type="text/javascript">
    var smartsupply_ajax_url = "{$ajax_picking_url}";
    var clear_collected_url = "{$ajax_clear_collected_url}";
    var modules_dir_path = "{$smarty.const._MODULE_DIR_}{$module_name}/";
</script>

<div class="panel" style="background:#ddd; margin-bottom:15px; padding:10px;">
    <div class="row">
        <div class="col-md-3">
            <label>Filtruj Regał:</label>
            <select id="filter_regal" class="form-control"><option value="">WSZYSTKIE</option></select>
        </div>
        <div class="col-md-3">
            <label>Filtruj Półka:</label>
             <input type="text" id="filter_polka" class="form-control" placeholder="Wpisz nr...">
        </div>
        <div class="col-md-6" style="text-align:right; padding-top:23px;">
            <button type="button" class="btn btn-primary" id="btn-collect-all" style="margin-right: 10px;">
                <i class="icon-check-sign"></i> ZBIERZ WSZYSTKO
            </button>
            <button type="button" class="btn btn-danger" id="btn-reset-all" style="margin-right: 20px;">
                 <i class="icon-refresh"></i> COFNIJ CAŁOŚĆ
            </button>
            <button type="button" class="btn btn-warning" id="btn-correct-stock" style="margin-right: 10px; font-weight:bold; border:2px solid #d58512;">
                <i class="icon-minus-sign"></i> ZERUJ BRAKI (KOREKTA)
            </button>
            <button type="button" class="btn btn-default" id="btn-open-clear-modal" style="font-weight:bold; background-color: #9c27b0; color: white; border-color: #7b1fa2;">
                <i class="icon-trash"></i> USUŃ ZEBRANE (KONIEC)
            </button>
            <span class="label label-info" style="display:block; margin-top:5px;">Sortowanie: A-Z / 1-9</span>
        </div>
    </div>
</div>

<div style="padding: 20px; background: #333; color: white; margin-top:5px; text-align:center; border-radius: 5px;">
    <label style="font-size: 18px;">SKANUJ KOD EAN:</label><br>
    <input type="text" id="scanner_input" class="form-control" style="width: 50%; margin: 10px auto; font-size: 24px; text-align: center;" placeholder="Kliknij tu i skanuj..." autofocus>
    <div id="scan_msg" style="margin-top:10px; font-weight:bold; height: 25px; font-size: 16px;"></div>
</div>

<table class="table table-bordered" style="margin-top:15px;" id="table_picking">
    <thead>
        <tr style="background: #eee;">
            <th style="width: 100px; text-align:center;">REGAŁ</th>
            <th style="width: 80px; text-align:center;">PÓŁKA</th>
            <th style="width: 60px;">FOTO</th>
            <th>PRODUKT</th>
            <th style="width: 140px; text-align:center;">DODANO</th>
            <th style="width: 130px;">EAN</th>
            <th style="width: 100px; text-align:center;">POTRZEBA</th>
            <th style="width: 120px; text-align:center;">ZEBRANO</th>
            <th style="width: 100px; text-align:center;">AKCJA</th>
        </tr>
    </thead>
    <tbody id="picking_list">
        {foreach from=$picking_data item=row}
            {if $row.qty_stock > 0}
            
            {assign var="is_swapped" value=(isset($row.alt_sku) && $row.alt_sku)}
            {assign var="alts_json" value=$row.alternatives_json|json_decode:true}
            {assign var="has_options" value=false}
            {if $alts_json && count($alts_json) > 1}{assign var="has_options" value=true}{/if}

            <tr class="picking-row {if $row.is_collected}success{/if}" 
                data-ean="{$row.ean}" 
                data-sku="{$row.sku}" 
                data-needed="{$row.qty_stock_original|default:$row.qty_stock}"
                data-regal="{$row.regal}"
                data-polka="{$row.polka}"
                data-alternatives='{$row.alternatives_json|escape:'html':'UTF-8'}' {* WAŻNE: Atrybut dostępny zawsze *}
                {if $row.is_collected}style="background-color:#dff0d8;"{/if}
            >
                {* KOLUMNA 1: TYLKO REGAŁ *}
                <td class="loc-cell" style="text-align:center; vertical-align:middle; {if $is_swapped}background:#fff3e0; border-left: 5px solid #ff9800;{else}background:{if $has_options}#e8f5e9{else}#fff{/if};{/if}">
                    
                    {if $is_swapped}
                        <div style="font-size: 32px; font-weight:bold; color: #e65100;">
                            <span class="display-regal">{$row.alt_regal}</span>
                        </div>
                        <div style="font-size:10px; color:#999; text-decoration:line-through; margin-bottom:2px;">
                            {$row.regal} / {$row.polka}
                        </div>
                        <button type="button" class="btn btn-default btn-xs btn-reset-swap" style="width:100%; font-size:10px; padding:2px;">
                            <i class="icon-undo"></i> COFNIJ
                        </button>
                    {else}
                        <div style="font-size: 32px; font-weight:bold; color: #333;">
                            <span class="display-regal">{if $row.regal}{$row.regal}{else}-{/if}</span>
                        </div>
                        {if $has_options}
                            <button type="button" class="btn btn-default btn-xs btn-open-swap" style="margin-top:5px; width:100%; font-weight:bold; color:#2e7d32; border:1px solid #2e7d32; font-size:10px;">
                                <i class="icon-refresh"></i> ZMIEŃ
                            </button>
                        {/if}
                    {/if}
                </td>

                {* KOLUMNA 2: TYLKO PÓŁKA *}
                <td class="loc-polka-cell" style="font-size: 24px; font-weight:bold; color: #555; text-align:center; background:#f9f9f9; vertical-align:middle;">
                    {if $is_swapped}
                        <span class="display-polka" style="color:#e65100;">{$row.alt_polka}</span>
                    {else}
                        <span class="display-polka">{if $row.polka}{$row.polka}{else}-{/if}</span>
                    {/if}
                </td>

                <td>
                    {if $row.image_id}
                         <img src="{$link->getImageLink($row.link_rewrite, $row.image_id, 'small_default')}" width="50" style="border:1px solid #ccc;">
                    {/if}
                </td>
                <td style="vertical-align:middle;">
                    <span style="font-size: 14px; font-weight:bold;">{$row.name}</span><br>
                    <span style="font-size:10px; color:#999;">SKU: {$row.sku}</span>
                    {if $is_swapped}
                        <br><span class="label label-warning" style="font-size:9px;">LOKALIZACJA ZMIENIONA</span>
                    {/if}
                </td>
                <td style="vertical-align:middle; text-align:center; white-space: nowrap;">
                    {if isset($row.date_add)}
                        <span style="font-size: 11px; color: #666;">
                            <i class="icon-calendar" style="color:#aaa;"></i> {$row.date_add|date_format:"%d.%m"}
                        </span>
                        <span style="font-size: 11px; font-weight:bold; color: #333; margin-left: 5px;">
                            <i class="icon-time" style="color:#aaa;"></i> {$row.date_add|date_format:"%H:%M"}
                        </span>
                    {else}
                         <span style="color:#eee;">-</span>
                    {/if}
                </td>
                <td style="font-size: 12px; font-family: monospace; vertical-align:middle;">{$row.ean}</td>
                
                <td style="font-size: 24px; font-weight:bold; text-align:center; color:blue; vertical-align:middle;">
                    {$row.qty_stock_original|default:$row.qty_stock}
                </td>
                
                <td style="vertical-align:middle; text-align:center;">
                    {if $row.is_collected}
                        <span class="label label-success" style="font-size:14px; margin-right:5px;">ZEBRANO: {$row.user_picked_qty}</span>
                        <button class="btn btn-warning btn-xs btn-undo" style="margin-left:5px;"><i class="icon-undo"></i></button>
                    {else}
                        <input type="number" class="form-control val-collected" value="{if $row.user_picked_qty > 0}{$row.user_picked_qty}{else}0{/if}" min="0" max="{$row.qty_stock}" 
                                 style="text-align:center; font-size:20px; font-weight:bold; color:green; height: 40px;">
                    {/if}
                </td>
                
                <td style="vertical-align:middle; text-align:center;">
                    {if $row.is_collected}
                        <i class="icon-check" style="color:green; font-size:24px;"></i>
                    {else}
                        <button type="button" class="btn btn-success btn-confirm btn-lg btn-block" title="Zatwierdź ilość">OK</button>
                    {/if}
                </td>
            </tr>
            {/if}
        {/foreach}
    </tbody>
</table>

{* MODAL POTWIERDZENIA USUNIĘCIA *}
<div id="modal_clear_confirmation" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document" style="margin-top: 10%;">
        <div class="modal-content" style="border: 2px solid #d9534f; box-shadow: 0 5px 15px rgba(0,0,0,0.3); border-radius: 6px;">
            <div class="modal-header" style="background-color: #d9534f; color: white; padding: 15px; border-radius: 4px 4px 0 0;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" style="font-weight:bold; display:flex; align-items:center;">
                    <i class="icon-trash" style="font-size:1.2em; margin-right:10px;"></i> POTWIERDZENIE CZYSZCZENIA
                </h4>
            </div>
            <div class="modal-body" style="padding: 25px; text-align: center; font-size: 1.1em;">
                <i class="icon-warning-sign" style="font-size: 4em; color: #d9534f; margin-bottom: 15px;"></i>
                <p style="margin-bottom: 10px;">Czy na pewno chcesz usunąć <strong id="clear_count_val" style="color:#d9534f; font-size:1.3em;">0</strong> zakończonych pozycji?</p>
                <p class="text-muted" style="font-size: 0.9em;">
                    Te produkty znikną z listy.<br><strong>Stan magazynowy został już zdjęty.</strong><br>Tej operacji nie można cofnąć.
                </p>
            </div>
            <div class="modal-footer" style="background: #f9f9f9; padding: 15px; text-align: center;">
                <div class="row">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-default btn-lg btn-block" data-dismiss="modal">ANULUJ</button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" id="btn_confirm_clear_action" class="btn btn-danger btn-lg btn-block" style="font-weight:bold;">
                            <i class="icon-trash"></i> USUŃ TRWALE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="./modal_picking_alternatives.tpl"}

<script>
    if(typeof modules_dir_path !== 'undefined') {
        var script = document.createElement('script');
        script.src = modules_dir_path + "views/js/picking_alternatives.js?v=" + new Date().getTime();
        document.body.appendChild(script);
    }
</script>