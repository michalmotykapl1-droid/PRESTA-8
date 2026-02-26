{* SEKCJA: BRAKI W HURTOWNIACH - CLEAN & MINIMAL *}
{if isset($list_no_stock_grouped) && count($list_no_stock_grouped) > 0}
    
    <div style="margin-bottom: 20px;">
        <h4 style="color:#e65100; font-weight:700; margin-bottom:15px; font-size: 15px; border-bottom: 2px solid #ffe0b2; padding-bottom: 8px;">
            <i class="icon-ban-circle"></i> BRAKI W HURTOWNIACH (Znany produkt, stan 0)
        </h4>
        
        {foreach from=$list_no_stock_grouped item=group}
            <div style="border: 1px solid #ffeeba; border-radius: 6px; margin-bottom: 15px; overflow: hidden; background: #fff;">
                
                <div style="background-color: #fff3cd; padding: 10px 15px; color: #856404; display: flex; align-items: center; border-bottom: 1px solid #ffeeba;">
                    <i class="icon-truck" style="margin-right: 8px; opacity: 0.7;"></i>
                    <span style="font-weight: 700; font-size: 14px;">{$group.supplier_name}</span>
                    <span style="background: #ffc107; color: #fff; border-radius: 10px; font-size: 11px; padding: 2px 8px; margin-left: 10px; font-weight: bold;">
                        {count($group.items)}
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" style="margin:0; font-size: 13px;">
                        <thead>
                            <tr style="color: #999;">
                                <th style="width:130px; border-bottom: 1px solid #f0f0f0;">EAN</th>
                                <th style="border-bottom: 1px solid #f0f0f0;">Produkt</th>
                                <th class="text-center" style="width:100px; border-bottom: 1px solid #f0f0f0;">Akcja</th>
                                <th style="text-align:center; width:100px; border-bottom: 1px solid #f0f0f0;">Chciałem kupić</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$group.items item=p}
                            {assign var=is_replaced value=false}
                            {if isset($replaced_map) && !empty($p.ean) && isset($replaced_map[$p.ean])}
                                {assign var=is_replaced value=true}
                            {/if}
                            {* ID WIERSZA *}
                            <tr id="row_fix_{$p.ean}" data-fix-ean="{$p.ean}"{if $is_replaced} style="background-color:#dff0d8;"{/if}>
                                <td style="font-family:monospace; color:#888;">{$p.ean}</td>
                                <td style="color: #444;">
                                    {if $p.name|strstr:"[EXTRA]"}
                                        <span style="color:#9c27b0; font-weight:bold; font-size:10px; border:1px solid #9c27b0; padding:1px 3px; border-radius:3px; margin-right:4px;">EXTRA</span>
                                        {$p.name|replace:'[EXTRA] ':''}
                                    {else}
                                        {$p.name}
                                    {/if}
                                </td>
                                
                                <td class="text-center action-cell" style="vertical-align: middle;">
                                    {* STATUS "WYMIENIONO" *}
                                    <div id="status_icon_{$p.ean}" class="fix-status-icon" data-fix-ean="{$p.ean}" style="{if $is_replaced}display:block;{else}display:none;{/if} margin-bottom: 4px;">
                                        <span class="label label-success" style="font-size: 10px; padding: 2px 6px;">
                                            <i class="icon-ok"></i> WYMIENIONO
                                        </span>
                                    </div>

                                    {* PRZYCISK SZUKANIA *}
                                    <button type="button" class="btn btn-default btn-xs btn-search-fix" data-fix-ean="{$p.ean}" {if $is_replaced}style="display:none;"{/if}
                                            onclick="searchInExtra('{$p.ean}', '{$p.name|strip_tags|escape:'javascript'}', '{$p.qty_buy|intval}'); return false;"
                                            title="Znajdź zamiennik">
                                        <i class="icon-search"></i> SZUKAJ
                                    </button>
                                </td>

                                <td style="text-align:center; font-weight:bold; color:#e65100;">
                                    {$p.qty_buy}
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/foreach}
    </div>
{/if}