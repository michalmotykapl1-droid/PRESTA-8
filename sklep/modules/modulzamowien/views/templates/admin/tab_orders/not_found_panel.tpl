{* SEKCJA: BRAK W BAZIE (Nieznane EAN) - LIGHT STYLE *}
{if isset($list_not_found) && count($list_not_found) > 0}
    <div style="border: 1px solid #f5c6cb; border-radius: 6px; margin-bottom: 30px; overflow: hidden;">
        
        {* Nagłówek: Czerwony, ale lekki *}
        <div style="background-color: #fce8e6; padding: 12px 15px; color: #c0392b; display: flex; align-items: center; border-bottom: 1px solid #f5c6cb;">
            <i class="icon-remove-sign" style="font-size: 18px; margin-right: 8px;"></i> 
            <span style="font-weight: 700; font-size: 14px;">PRODUKTY NIEZNANE (BRAK EAN W BAZIE)</span>
            <span class="badge" style="background: #e74c3c; color: white; margin-left: 10px;">{count($list_not_found)}</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" style="margin:0; font-size: 13px;">
                <thead>
                    <tr style="background: #fff; color: #888;">
                        <th style="width:130px; border-bottom: 1px solid #eee;">EAN (z pliku)</th>
                        <th style="border-bottom: 1px solid #eee;">Nazwa (z pliku)</th>
                        <th style="text-align:center; width:100px; border-bottom: 1px solid #eee;">Szukana Ilość</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$list_not_found item=p}
                    <tr>
                        <td style="font-family:monospace; color:#d9534f;">{$p.ean}</td>
                        <td style="color: #555;">{$p.name}</td>
                        <td style="text-align:center; font-weight:bold; color:#d9534f;">{$p.qty_buy}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{/if}