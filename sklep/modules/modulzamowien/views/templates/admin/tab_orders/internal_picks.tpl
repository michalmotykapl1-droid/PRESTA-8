{* SEKCJA: DO ZEBRANIA Z MAGAZYNU (A_MAG) - CLEAN & SUBTLE *}
{if isset($internal_picks) && count($internal_picks) > 0}
    <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
        
        {* NAGŁÓWEK *}
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;">
            <div style="font-size: 16px; font-weight: 700; color: #444;">
                <i class="icon-inbox" style="color: #ff9800; margin-right: 8px;"></i> 
                DO ZEBRANIA Z MAGAZYNU (A_MAG)
            </div>
            <span style="background: #fff3e0; color: #e65100; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                POZYCJI: {count($internal_picks)}
            </span>
        </div>
        
        {* OPIS *}
        <div style="font-size: 13px; color: #777; margin-bottom: 20px; display:flex; align-items:center;">
            <i class="icon-info-sign" style="color:#aaa; margin-right:6px;"></i>
            Te produkty znajdują się fizycznie w magazynie. Pobierz je z półki i dołóż do zamówienia.
        </div>

        {* TABELA *}
        <div class="table-responsive">
            <table class="table table-hover" style="margin-bottom:0;">
                <thead style="background: #f9f9f9; color: #888; font-size: 11px; text-transform: uppercase;">
                    <tr>
                        <th style="padding: 10px; border:none;">Lokalizacja</th>
                        <th style="padding: 10px; border:none;">Produkt</th>
                        <th class="text-center" style="padding: 10px; border:none;">Zabierz</th>
                        <th class="text-center" style="padding: 10px; border:none;">Było</th>
                        <th class="text-center" style="padding: 10px; border:none;">Zostanie</th>
                    </tr>
                </thead>
                <tbody style="font-size: 13px;">
                    {foreach from=$internal_picks item=item}
                        {assign var="after_pick" value=$item.qty_stock_current - $item.qty}
                        <tr style="border-bottom: 1px solid #f5f5f5;">
                            <td style="vertical-align:middle; padding: 12px 10px;">
                                {if $item.name|regex_replace:"/.*Lok:\s*([A-Z0-9 \/]+).*/":"$1" neq $item.name}
                                    <span style="background:#f5f5f5; color:#333; padding:3px 6px; border-radius:3px; font-weight:bold; font-size:11px; border:1px solid #ddd;">
                                        {$item.name|regex_replace:"/.*Lok:\s*([A-Z0-9 \/]+).*/":"$1"}
                                    </span>
                                {else}
                                    <span style="color:#ccc;">-</span>
                                {/if}
                                <div style="font-size:10px; color:#999; margin-top:4px; font-family:monospace;">{$item.ean}</div>
                            </td>
                            
                            <td style="vertical-align:middle;">
                                <span style="font-weight:600; color:#444;">
                                    {$item.name|replace:'[EXTRA] ':''|regex_replace:"/Lok:.*/":""}
                                </span>
                                <div style="font-size:10px; color:#aaa; margin-top:2px;">SKU: {$item.sku}</div>
                            </td>
                            
                            <td class="text-center" style="vertical-align:middle;">
                                <span style="background: #fff3e0; color: #e65100; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 13px; border:1px solid #ffe0b2;">
                                    {$item.qty} szt.
                                </span>
                            </td>
                            
                            <td class="text-center" style="vertical-align:middle; color:#999;">
                                {$item.qty_stock_current}
                            </td>
                            
                            <td class="text-center" style="vertical-align:middle; font-weight:bold; {if $after_pick < 0}color:#d9534f;{else}color:#4caf50;{/if}">
                                {$after_pick}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{/if}