{if $expired_products_count > 0}
    <div class="alert alert-warning">
        <i class="icon-warning-sign"></i> {l s='Na magazynie WMS znajduje się' mod='wyprzedazpro'} <strong>{$expired_products_count}</strong> {l s='produkt(ów) po terminie.' mod='wyprzedazpro'}
        <a href="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}&controller=AdminWyprzedazPro&date_filter=expired" class="btn btn-warning btn-sm pull-right">{l s='Pokaż' mod='wyprzedazpro'}</a>
        <div class="clearfix"></div>
    </div>
{/if}