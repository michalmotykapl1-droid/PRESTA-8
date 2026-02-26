<div class="panel">
    <div class="panel-heading">
        <i class="icon-list-alt"></i> {l s='Brak EAN w bazie' mod='wyprzedazpro'}
        <a href="{$current}&token={$token}&export_not_found=1" class="btn btn-default btn-sm pull-right"><i class="icon-download"></i> CSV</a>
    </div>
    
    {* KONTENER PRZEWIJANIA: max-height 400px *}
    <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd;">
        <table class="table table-bordered" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='EAN' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='Stan' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='Ważność' mod='wyprzedazpro'}</th>
                    <th style="position: sticky; top: 0; background: #f8f8f8; z-index: 10;">{l s='Akcje' mod='wyprzedazpro'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$not_found_products item=product}
                    <tr>
                        <td>{$product.ean|default:'–'}</td>
                        <td>{$product.quantity|default:'–'}</td>
                        <td>{if $product.expiry_date && $product.expiry_date != '0000-00-00'}{$product.expiry_date|date_format:"%d.%m.%Y"}{else}–{/if}</td>
                        <td><a href="{$link->getAdminLink('AdminProducts')|escape:'htmlall':'UTF-8'}&addproduct&ean13={$product.ean|escape:'htmlall':'UTF-8'}" class="btn btn-sm btn-primary"><i class="icon-plus"></i> Utwórz</a></td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="4" class="text-center">{l s='Pusto.' mod='wyprzedazpro'}</td></tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>