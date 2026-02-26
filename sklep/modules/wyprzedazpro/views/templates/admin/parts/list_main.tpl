<div class="panel">
    <div class="panel-heading">
        <i class="icon-list"></i> {l s='Stan Magazynowy WMS (Fizyczny) vs Presta' mod='wyprzedazpro'}
        <span class="badge">{$sale_products_count}</span>
        <div class="btn-group pull-right">
            <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazPro&date_filter=all" class="btn btn-default btn-sm"><i class="icon-th-list"></i> {l s='Wszystkie' mod='wyprzedazpro'}</a>
            <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazPro&date_filter=short" class="btn btn-sm wyprzedaz-btn-short"><i class="icon-calendar"></i> {l s='Krótka data' mod='wyprzedazpro'}</a>
        </div>
    </div>
    
    {* KONTENER PRZEWIJANIA: max-height 600px *}
    <div class="table-responsive" style="max-height: 600px; overflow-y: auto; border: 1px solid #ddd;">
        <table class="table table-bordered table-striped" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">{l s='Nazwa' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;"><a href="{$current}&token={$token}&sort=reference&way={if $sort == 'reference' && $way == 'asc'}desc{else}asc{/if}">{l s='SKU WMS (A_MAG...)' mod='wyprzedazpro'}</a></th>
                    {* KOLUMNY STANU *}
                    <th style="position: sticky; top: 0; background-color:#e6faff; border-bottom: 2px solid #0099cc; text-align:center; z-index: 10;"><a href="{$current}&token={$token}&sort=wms&way={if $sort == 'wms' && $way == 'asc'}desc{else}asc{/if}">{l s='Stan WMS (Fizyczny)' mod='wyprzedazpro'}</a></th>
                    <th style="position: sticky; top: 0; background-color:#fff0f0; border-bottom: 2px solid #cc0000; text-align:center; z-index: 10;"><a href="{$current}&token={$token}&sort=quantity&way={if $sort == 'quantity' && $way == 'asc'}desc{else}asc{/if}">{l s='Stan Sklep (Presta)' mod='wyprzedazpro'}</a></th>
                    
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;"><a href="{$current}&token={$token}&sort=discount&way={if $sort == 'discount' && $way == 'asc'}desc{else}asc{/if}">{l s='Rabat' mod='wyprzedazpro'}</a></th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">{l s='Lok.' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;"><a href="{$current}&token={$token}&sort=expiry&way={if $sort == 'expiry' && $way == 'asc'}desc{else}asc{/if}">{l s='Ważność' mod='wyprzedazpro'}</a></th>
                    <th style="position: sticky; top: 0; background: #fff; z-index: 10;">{l s='Akcje' mod='wyprzedazpro'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$sale_products item=product}
                    {assign var="row_class" value=""}
                    {if $product.status == 'expired'}
                        {assign var="row_class" value="danger"}
                    {elseif $product.status == 'short_date'}
                        {assign var="row_class" value="warning"}
                    {/if}
                    {* Podświetlenie błędów synchronizacji *}
                    {if $product.quantity_wms != $product.quantity_presta}
                         {assign var="row_class" value="danger"} 
                    {/if}

                    <tr class="{$row_class}">
                        <td>
                            {$product.name|truncate:40:'...'}
                            {if isset($product.is_manual) && $product.is_manual == 1}
                                <span class="label label-info" style="background-color: #5bc0de; margin-left:5px;">
                                    <i class="icon-qrcode"></i> SKANER
                                </span>
                            {/if}
                        </td>
                        <td style="font-size:0.9em; font-family:monospace;">{$product.reference}</td>
                        
                        {* STAN WMS *}
                        <td style="background-color:#e6faff; font-weight:bold; text-align:center; font-size:1.2em; color:#0099cc;">{$product.quantity_wms}</td>
                        
                        {* STAN PRESTA *}
                        <td style="background-color:#fff0f0; font-weight:bold; text-align:center; color:#cc0000;">{$product.quantity_presta}</td>

                        <td>{if $product.reduction > 0}{($product.reduction * 100)|round:0}%{else}–{/if}</td>
                        <td>{$product.regal|default:'–'} / {$product.polka|default:'–'}</td>
                        <td>{if $product.expiry_date && $product.expiry_date != '0000-00-00'}{$product.expiry_date|date_format:"%d.%m.%Y"}{else}–{/if}</td>
                        <td><a href="{$product.product_url}" target="_blank" class="btn btn-sm btn-default"><i class="icon-eye"></i></a></td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="8" class="text-center">{l s='Brak produktów spełniających kryteria.' mod='wyprzedazpro'}</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>