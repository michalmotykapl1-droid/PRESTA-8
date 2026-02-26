{* SEKCJA: ZAMÓWIENIA NIEPEŁNE - LIGHT STYLE *}
{if isset($list_partial_orders) && count($list_partial_orders) > 0}
    <div style="border: 1px solid #bce8f1; border-radius: 6px; margin-bottom: 30px; overflow: hidden;">
        
        <div style="background-color: #eef9fb; padding: 12px 15px; color: #31708f; display: flex; align-items: center; border-bottom: 1px solid #bce8f1;">
            <i class="icon-adjust" style="font-size: 18px; margin-right: 8px;"></i> 
            <span style="font-weight: 700; font-size: 14px;">ZAMÓWIENIA NIEPEŁNE (ZA MAŁO TOWARU)</span>
            <span class="badge" style="background: #31b0d5; color: white; margin-left: 10px;">{count($list_partial_orders)}</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" style="margin:0; font-size: 13px;">
                <thead>
                    <tr style="background: #fff; color: #888;">
                        <th style="width:130px; border-bottom: 1px solid #eee;">EAN</th>
                        <th style="border-bottom: 1px solid #eee;">Produkt</th>
                        <th style="text-align:center; width:80px; border-bottom: 1px solid #eee;">Potrzeba</th>
                        <th style="text-align:center; width:80px; border-bottom: 1px solid #eee;">Dostępne</th>
                        <th style="text-align:center; width:80px; border-bottom: 1px solid #eee;">Brakuje</th>
                        <th style="border-bottom: 1px solid #eee;">Dostawca</th>
                        <th class="text-center" style="width:100px; border-bottom: 1px solid #eee;">Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$list_partial_orders item=p}
                    {assign var=is_replaced value=false}
                    {if isset($replaced_map) && !empty($p.ean) && isset($replaced_map[$p.ean])}
                        {assign var=is_replaced value=true}
                    {/if}
                    {* ID WIERSZA - WAŻNE DLA JS *}
                    <tr id="row_fix_{$p.ean}" data-fix-ean="{$p.ean}"{if $is_replaced} style="background-color:#dff0d8;"{/if}>
                        <td style="font-family:monospace; color:#666;">{$p.ean}</td>
                        <td>
                            {if $p.name|strstr:"[EXTRA]"}
                                <span style="color:#9c27b0; font-weight:bold; font-size:10px; border:1px solid #9c27b0; padding:1px 3px; border-radius:3px; margin-right:4px;">EXTRA</span>
                                {$p.name|replace:'[EXTRA] ':''}
                            {else}
                                {$p.name}
                            {/if}
                        </td>
                        <td style="text-align:center; color:#555;">{$p.qty_total}</td>
                        <td style="text-align:center; font-weight:bold; color:#2e7d32;">{$p.qty_buy}</td>
                        <td style="text-align:center; font-weight:bold; color:#d9534f;">-{$p.missing_qty}</td>
                        <td style="color:#31708f;">{$p.supplier nofilter}</td>
                        
                        <td class="text-center action-cell" style="vertical-align: middle;">
                            {* STATUS "WYMIENIONO" (Pobierany z bazy) *}
                            <div id="status_icon_{$p.ean}" class="fix-status-icon" data-fix-ean="{$p.ean}" style="{if $is_replaced}display:block;{else}display:none;{/if} margin-bottom: 4px;">
                                <span class="label label-success" style="font-size: 10px; padding: 2px 6px;">
                                    <i class="icon-ok"></i> WYMIENIONO
                                </span>
                            </div>
                            
                            {* PRZYCISK SZUKANIA *}
                            <button type="button" class="btn btn-default btn-xs btn-search-fix" data-fix-ean="{$p.ean}" {if $is_replaced}style="display:none;"{/if}
                                    onclick="searchInExtra('{$p.ean}', '{$p.name|strip_tags|escape:'javascript'}', '{$p.missing_qty|intval}'); return false;" 
                                    title="Znajdź zamiennik">
                                <i class="icon-search"></i> SZUKAJ
                            </button>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{/if}