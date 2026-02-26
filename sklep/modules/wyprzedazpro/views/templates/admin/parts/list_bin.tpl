<div class="panel">
    <div class="panel-heading">
        <i class="icon-trash"></i> {l s='Lokalizacja KOSZ - Pełna Lista' mod='wyprzedazpro'}
        <span class="badge">{$bin_products_count}</span>
        
        <div class="pull-right" style="margin-top: -4px;">
            {* 1. PRZYCISK: TYLKO WMS (Istniejący) *}
            <a href="{$link->getAdminLink('AdminWyprzedazPro')|escape:'html':'UTF-8'}&delete_all_bin=1" 
               class="btn btn-default btn-sm"
               style="margin-right: 5px;"
               title="Usuwa tylko wpisy z tej tabeli, produkty w sklepie zostają"
               onclick="return confirm('Czy usunąć wpisy WMS z KOSZA? \nProdukty w sklepie pozostaną nienaruszone.');">
                <i class="icon-eraser"></i> {l s='CZYŚĆ TYLKO WMS' mod='wyprzedazpro'}
            </a>

            {* 2. PRZYCISK: WMS + PRESTA (Nowy - Hard Delete) *}
            <a href="{$link->getAdminLink('AdminWyprzedazPro')|escape:'html':'UTF-8'}&delete_all_bin_full=1" 
               class="btn btn-danger btn-sm"
               title="Usuwa wpisy WMS ORAZ fizycznie kasuje produkty ze sklepu!"
               onclick="return confirm('ALARM!\n\nCzy na pewno chcesz usunąć WSZYSTKIE produkty z lokalizacji KOSZ?\n\nTo usunie je FIZYCZNIE z bazy sklepu PrestaShop!\nTej operacji nie można cofnąć.');">
                <i class="icon-trash"></i> {l s='USUŃ KOMPLETNIE (WMS + SKLEP)' mod='wyprzedazpro'}
            </a>
        </div>
    </div>
    
    <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd;">
        <table class="table table-bordered table-striped" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">{l s='Nazwa Produktu' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">{l s='EAN' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">{l s='SKU WMS' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #e6faff; border-bottom: 2px solid #0099cc; z-index: 10; text-align:center;">{l s='Stan WMS' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">{l s='Ważność' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">{l s='Lokalizacja' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">{l s='Akcje' mod='wyprzedazpro'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$bin_products item=product}
                    <tr>
                        <td>
                            <strong>{$product.name|default:'(Brak nazwy)'|truncate:60:'...'}</strong>
                        </td>
                        
                        {* Wyświetla EAN z WMS jeśli produkt usunięty ze sklepu *}
                        <td style="font-family:monospace;">{$product.display_ean}</td>
                        
                        <td style="font-family:monospace; font-size: 0.9em;">{$product.reference}</td>
                        
                        <td style="text-align:center; font-weight:bold; font-size:1.2em; {if $product.quantity_wms > 0}background-color:#e6faff; color:#0099cc;{else}background-color:#f5f5f5; color:#999;{/if}">
                            {$product.quantity_wms}
                        </td>
                        
                        <td>
                            {if $product.expiry_date && $product.expiry_date != '0000-00-00'}
                                {$product.expiry_date|date_format:"%d.%m.%Y"}
                            {else}
                                -
                            {/if}
                        </td>
                        
                        <td>
                            <span class="label label-danger">{$product.regal}</span> / {$product.polka}
                        </td>
                        
                        <td>
                            <div class="btn-group">
                                {if $product.product_url && $product.product_url != '#'}
                                <a href="{$product.product_url}" target="_blank" class="btn btn-default btn-sm" title="Edytuj">
                                    <i class="icon-pencil"></i>
                                </a>
                                {/if}
                                
                                {* 1. USUŃ TYLKO Z WMS *}
                                <a href="{$link->getAdminLink('AdminWyprzedazPro')|escape:'html':'UTF-8'}&delete_bin_item=1&id_product={$product.delete_id}" 
                                   class="btn btn-default btn-sm" 
                                   title="Usuń tylko z listy WMS"
                                   onclick="return confirm('Usunąć tylko wpis WMS? Produkt w sklepie zostanie.');">
                                    <i class="icon-eraser"></i>
                                </a>

                                {* 2. USUŃ Z WMS I SKLEPU (HARD) *}
                                <a href="{$link->getAdminLink('AdminWyprzedazPro')|escape:'html':'UTF-8'}&delete_bin_item_full=1&id_product={$product.delete_id}" 
                                   class="btn btn-danger btn-sm" 
                                   title="Usuń produkt całkowicie ze sklepu!"
                                   onclick="return confirm('UWAGA! Czy na pewno usunąć ten produkt fizycznie ze sklepu?');">
                                    <i class="icon-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="7" class="text-center text-muted">{l s='Brak produktów w lokalizacji KOSZ.' mod='wyprzedazpro'}</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>