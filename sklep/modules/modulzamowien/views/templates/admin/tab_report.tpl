<div class="table-responsive" style="margin-top:15px;">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Produkt</th>
                <th>EAN</th>
                <th>Lokalizacja</th>
                <th style="background:#eef; text-align:center;">ZAMÓWIONO</th>
                <th style="color:green;">Z MAGAZYNU</th>
                <th style="color:red;">DO KUPIENIA</th>
                <th>Dostawca / Status</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$analysis_data item=row}

                {* Bezpieczne pobranie pól (PHP 8+ / Smarty compiled templates: brak klucza = Warning) *}
                {assign var="supplierStr" value=""}
                {if isset($row.supplier)}{assign var="supplierStr" value=$row.supplier}{/if}

                {assign var="rowClass" value=""}
                {if $supplierStr|strstr:"BRAK W BAZIE"}
                    {assign var="rowClass" value="danger"}
                {elseif $supplierStr|strstr:"BRAK NA RYNKU"}
                    {assign var="rowClass" value="warning"}
                {/if}

                <tr class="{$rowClass}">
                    <td>
                        {if isset($row.name) && $row.name}
                            <strong>{$row.name}</strong>
                        {else}
                            <strong>-</strong>
                        {/if}

                        {if isset($row.match_type) && $row.match_type == 'name'}
                            <br><span class="label label-warning" style="color:black; font-size:10px;">(Dopasowano po Nazwie)</span>
                        {/if}
                    </td>

                    <td>
                        {if isset($row.ean) && $row.ean}
                            {$row.ean}
                        {else}
                            <span style="color:#ccc;">-</span>
                        {/if}
                    </td>

                    <td>
                        {if isset($row.location) && $row.location}
                            <span class="label label-info">{$row.location}</span>
                        {else}
                            <span style="color:#ccc;">-</span>
                        {/if}
                    </td>

                    <td style="background:#eef; font-weight:bold; font-size:1.1em; text-align:center;">
                        {if isset($row.qty_total)}{$row.qty_total}{else}0{/if}
                    </td>

                    <td style="color:green; font-weight:bold; font-size:1.1em;">
                        {if isset($row.qty_stock) && $row.qty_stock > 0}{$row.qty_stock}{else}-{/if}
                    </td>

                    <td style="color:red; font-weight:bold; font-size:1.1em;">
                        {if isset($row.qty_buy) && $row.qty_buy > 0}{$row.qty_buy}{else}-{/if}
                    </td>

                    <td>
                        {if $supplierStr|strstr:"BRAK W BAZIE"}
                            <span class="label label-danger" style="font-size:12px;">!!! BRAK W BAZIE !!!</span>
                        {elseif $supplierStr|strstr:"BRAK NA RYNKU"}
                            <span class="label label-warning" style="font-size:12px; color:#111;">BRAK NA RYNKU</span>
                        {elseif $supplierStr}
                            {$supplierStr nofilter}
                        {else}
                            <span style="color:#ccc;">-</span>
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
