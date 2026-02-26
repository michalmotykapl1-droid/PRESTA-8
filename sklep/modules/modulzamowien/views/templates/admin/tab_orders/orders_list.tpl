{* SEKCJA: SZCZEGÓŁOWE TABELE ZAMÓWIEŃ - MODERN + BRUTTO *}
{foreach from=$orders_grouped key=supplierKey item=group}
    {assign var="supplierName" value=$group.supplier_name}
    {assign var="products" value=$group.items}
    {assign var="safeID" value=$supplierName|strip_tags|trim|md5}
    {assign var="is_error_group" value=false}
    {if $supplierName|strip_tags|strstr:"BRAK" || $supplierName|strip_tags|strstr:"WERYFIKACJI"}{assign var="is_error_group" value=true}{/if}

    <div class="panel" id="anchor_{$safeID}" style="border:none; box-shadow:0 1px 3px rgba(0,0,0,0.1); margin-bottom:40px;">
        <div class="panel-heading" style="background: {if $is_error_group}#f8d7da{else}#f8f9fa{/if}; color: #333; font-weight: bold; border-bottom: 1px solid #eee;">
            <i class="{if $is_error_group}icon-warning-sign{else}icon-truck{/if}" style="margin-right:5px;"></i> 
            {$supplierName|strip_tags} 
        </div>
        
        {if $is_error_group}
            <div class="alert alert-danger" style="margin:15px;">
                <b>Wymagana weryfikacja:</b> Produkty nie zostały automatycznie przypisane.
                {capture name=csv_content}Nazwa Produktu;EAN;Ilość
{foreach from=$products item=p}"{$p.name|replace:'[EXTRA] ':''|replace:'"':'""'}";"=""{$p.ean}""";{$p.qty_buy}
{/foreach}{/capture}
                <textarea id="csv_data_{$safeID}" style="display:none;">{$smarty.capture.csv_content}</textarea>
                <br>
                <button class="btn btn-danger btn-sm" onclick="downloadCSV('csv_data_{$safeID}', 'Braki_Weryfikacja.csv')">
                    <i class="icon-download-alt"></i> POBIERZ PLIK DO WERYFIKACJI
                </button>
            </div>
        {/if}

        <div class="table-responsive">
            <table class="table table-hover mz-table-clean">
                <thead>
                    <tr>
                        <th style="width: 130px;">EAN</th>
                        <th>Nazwa Produktu</th>
                        <th style="width: 80px; text-align: center;">ILOŚĆ</th>
                        <th style="width: 100px; text-align: right;">Cena Netto</th>
                        <th style="width: 80px; text-align: right;">VAT</th>
                        <th style="width: 100px; text-align: right;">Wartość Netto</th>
                        <th style="width: 300px;">Info</th>
                    </tr>
                </thead>
                <tbody>
                    {assign var="total_net" value=0}
                    {assign var="total_gross" value=0}
                    
                    {foreach from=$products item=p}
                        {assign var="row_net" value=$p.price * $p.qty_buy}
                        {assign var="row_gross" value=$p.price_gross * $p.qty_buy}
                        
                        {assign var="total_net" value=$total_net + $row_net}
                        {assign var="total_gross" value=$total_gross + $row_gross}
                        
                        {assign var="row_style" value=""}
                        {if isset($p.is_shortage) && $p.is_shortage}
                           {assign var="row_style" value="background-color:#fff3cd;"}
                        {/if}

                        <tr style="{$row_style}">
                            <td style="font-family: monospace; color:#666;">{$p.ean}</td>
                            <td>
                                {if $p.name|strstr:"[EXTRA]"}
                                     <span class="label label-primary" style="font-size:10px;">EXTRA</span>
                                    {$p.name|replace:'[EXTRA] ':''}
                                {else}
                                    {$p.name}
                                {/if}
                                {if isset($p.is_shortage) && $p.is_shortage}
                                     <span class="label label-warning">BRAK Z MAG</span>
                                {/if}
                            </td>
                            <td class="text-center">
                                <span style="font-size:16px; font-weight:bold; color:#2c3e50;">{$p.qty_buy}</span>
                            </td>
                            <td class="text-right text-muted">
                                {$p.price|string_format:"%.2f"} zł
                            </td>
                            <td class="text-right text-muted" style="font-size:11px;">
                                {if $p.tax_rate > 0}{$p.tax_rate|string_format:"%.0f"}%{else}0%{/if}
                            </td>
                            <td class="text-right" style="font-weight:bold;">
                                {if $row_net > 0}{$row_net|string_format:"%.2f"} zł{else}-{/if}
                            </td>
                            <td>
                                <small class="text-muted">{$p.supplier nofilter}</small>
                                {if isset($p.qty_stock) && $p.qty_stock > 0}
                                    <div class="mz-badge-wms"><i class="icon-building"></i> MAG: {$p.qty_stock}</div>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
                <tfoot>
                    <tr style="background: #f8f9fa;">
                        <td colspan="5" class="text-right" style="font-weight:bold; color:#555;">SUMA NETTO:</td>
                        <td class="text-right" style="font-weight:800; color:#007aff; font-size:1.1em;">{$total_net|string_format:"%.2f"} zł</td>
                        <td></td>
                    </tr>
                    <tr style="background: #eef2f5;">
                        <td colspan="5" class="text-right" style="font-weight:bold; color:#333;">SUMA BRUTTO:</td>
                        <td class="text-right" style="font-weight:800; color:#333; font-size:1.1em;">{$total_gross|string_format:"%.2f"} zł</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
{/foreach}