{**
* STREFA OKAZJI - DANE PRODUKTOWE (AJAX)
*}
{strip}
<div class="special-dual-layout">
            
    {* --- LEWA STRONA: WYPRZEDAŻ --- *}
    <div class="special-col col-sale">
        <div class="special-col-header">
            <div class="header-content">
                <h3>
                    <i class="fa-solid fa-tags icon-orange"></i> 
                    CODZIENNIE NOWE OKAZJE
                </h3>
                <p class="stock-info">Ponad <strong>1000 produktów</strong> w obniżonych cenach. <strong>Codziennie</strong> nowa pula okazji – sprawdzaj nas regularnie!</p>
            </div>
            <div class="sales-timer-pill">
                <i class="fa-solid fa-stopwatch"></i> NASTĘPNE ZA: <span class="daily-countdown">...</span>
            </div>
        </div>
        
        <div class="special-grid-sale products">
            {if isset($special_sale_products) && $special_sale_products}
                {foreach from=$special_sale_products item=product}
                    <div class="special-item tv-product-wrapper">
                        {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='tab_product'}
                    </div>
                {/foreach}
            {else}
                <div class="alert alert-warning">Brak produktów w strefie okazji.</div>
            {/if}
        </div>

        <div class="special-col-footer">
            <a href="{$link_sale}" class="btn-clean-orange">
                ZOBACZ WSZYSTKIE OKAZJE <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    {* --- PRAWA STRONA: KRÓTKA DATA --- *}
    <div class="special-col col-short">
        <div class="special-col-header header-short">
            <h3>
                <i class="fa-solid fa-hourglass-half icon-red"></i> 
                LAST MINUTE
            </h3>
            <p>Uratuj produkt i zapłać grosze</p>
        </div>
        
        <div class="special-grid-short products">
            {if isset($special_short_products) && $special_short_products}
                {foreach from=$special_short_products item=product}
                    <div class="special-item tv-product-wrapper">
                        {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='tab_product'}
                    </div>
                {/foreach}
            {else}
                <div class="alert alert-warning">Brak produktów.</div>
            {/if}
        </div>

        <div class="special-col-footer">
            <a href="{$link_short}" class="btn-clean-red">
                ZOBACZ WIĘCEJ <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

</div>
{/strip}