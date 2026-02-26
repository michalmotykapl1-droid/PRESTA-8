<div class="panel">
    <div class="panel-heading">
        <i class="icon-random"></i> {l s='Rozbicie Partii (Ten sam EAN i Data w wielu miejscach)' mod='wyprzedazpro'}
        <span class="badge">{$duplicate_products_count}</span>
    </div>
    
    {* KONTENER PRZEWIJANIA *}
    <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd;">
        <table class="table table-bordered table-striped" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">Nazwa Produktu</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">EAN</th>
                    <th style="position: sticky; top: 0; background: #e6faff; border-bottom: 2px solid #0099cc; z-index: 10; text-align:center;">Stan WMS (Suma)</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">Wszystkie Lokalizacje (Ilość)</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">Ważność</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$duplicated_products item=product}
                    <tr>
                        <td>
                            <strong>{$product.name|default:'(Brak nazwy w bazie)'}</strong>
                        </td>
                        <td style="font-family:monospace;">{$product.ean}</td>
                        
                        <td style="background-color:#e6faff; font-weight:bold; text-align:center; color:#0099cc; font-size:1.2em;">
                            {$product.total_quantity}
                        </td>
                        
                        <td style="font-size:0.9em; color:#555;">
                            <i class="icon-map-marker"></i> {$product.locations nofilter}
                        </td>
                        
                        <td>
                            {if $product.expiry_date && $product.expiry_date != '0000-00-00'}
                                {$product.expiry_date|date_format:"%d.%m.%Y"}
                            {else}
                                -
                            {/if}
                        </td>
                        
                        <td>
                            {if $product.product_url}
                            <a href="{$product.product_url}" target="_blank" class="btn btn-default btn-sm" title="Edytuj produkt">
                                <i class="icon-pencil"></i>
                            </a>
                            {/if}
                        </td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="6" class="text-center text-muted">{l s='Brak rozbitych partii.' mod='wyprzedazpro'}</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>