{**
 * FRESH PRODUKTY – WIDOK (Full Lazy Load)
 *}
{if !$is_fresh_ajax}
<div class="freshprodukty-section">
  <div class="container">
    
    <div class="fresh-prod-header">
        <h3 class="fresh-prod-title">
            {if isset($fresh_title)}{$fresh_title}{else}Strefa Fresh{/if}
        </h3>
        <div class="fresh-prod-line"></div>
        <p class="fresh-prod-desc">
            Przekonaj się, jak smakuje prawdziwa natura. Wyselekcjonowane warzywa, nabiał i mięsa, które zachwycają jakością i certyfikowanym pochodzeniem.
        </p>
    </div>

    {* KONTENER AJAX *}
    <div id="fresh-content-area" class="fresh-lazy-skeleton">
{/if}

        {if isset($fresh_products) && $fresh_products && $fresh_products|count > 0}
            <div class="fresh-hero-layout">
                
                {* 1. HERO *}
                {$hero_product = $fresh_products[0]}
                <div class="fresh-hero-column">
                    <div class="fresh-hero-intro">
                        <h4 class="intro-title"><i class="fa-solid fa-heart-pulse"></i> SMAK I ZDROWIE</h4>
                        <p class="intro-text">
                            Produkty ekologiczne to więcej witamin i minerałów bez zbędnej chemii. Wybierając BIO, inwestujesz w naturalną odporność i lepsze samopoczucie swojej rodziny.
                        </p>
                    </div>
                    <div class="fresh-hero-card-wrapper">
                         <div class="hero-badge"><i class="fa-solid fa-star"></i> PRODUKT DNIA</div>
                        {include file="catalog/_partials/miniatures/product.tpl" product=$hero_product tv_product_type='tab_product'}
                    </div>
                </div>

                {* 2. GRID *}
                <div class="fresh-grid-column">
                    <div class="fresh-dense-grid products">
                        {foreach from=$fresh_products item=product key=k}
                            {if $k > 0}
                                <div class="fresh-grid-item tv-product-wrapper">
                                    {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='tab_product'}
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            </div>
        {else}
            {* LOADER (Gdy brak produktów / Start) *}
            <div class="fresh-loading-overlay">
                <div class="fresh-spinner"></div>
                <span class="fresh-loading-text">Ładowanie produktów...</span>
            </div>
        {/if}

{if !$is_fresh_ajax}
    </div>
  </div>
</div>
{/if}