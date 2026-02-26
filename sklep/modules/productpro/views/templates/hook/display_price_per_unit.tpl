{*
* 2007-2023 PrestaShop
*
* Szablon dla hooka wyświetlającego cenę za jednostkę oraz dane strukturalne.
*}
<div class="product-price-per-unit">
    {* Widoczny tekst dla użytkownika *}
    {if isset($price_per_unit_text) && $price_per_unit_text}
        {$price_per_unit_text|escape:'html':'UTF-8'}
    {/if}

    {* Niewidoczne dane strukturalne (schema.org) dla wyszukiwarek *}
    {if isset($schema_data) && $schema_data}
        <div itemprop="unitPriceSpecification" itemscope itemtype="http://schema.org/UnitPriceSpecification" class="hidden">
            <meta itemprop="price" content="{$schema_data.price|escape:'html':'UTF-8'}">
            <meta itemprop="priceCurrency" content="{$schema_data.currency|escape:'html':'UTF-8'}">
            <meta itemprop="unitCode" content="{$schema_data.unit_code|escape:'html':'UTF-8'}">
            <meta itemprop="billingIncrement" content="{$schema_data.billing_increment|escape:'html':'UTF-8'}">
        </div>
    {/if}
</div>