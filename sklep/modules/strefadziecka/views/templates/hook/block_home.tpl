{**
 * STREFA DZIECKA – WIDOK (Full Lazy Load)
 *}
{strip}
{if !$is_child_ajax}
<div class="dziecko-wrapper">
    <div class="container">
        
        <div class="dziecko-header">
            <div class="dziecko-header-content">
                <h2 class="dziecko-main-title">{$dziecko_main_title}</h2>
                <div class="dziecko-line"></div>
                <p class="dziecko-main-desc">{$dziecko_main_desc}</p>
            </div>
            <div class="dziecko-btn-container">
                <a href="{$link_all}" class="dziecko-btn-all">
                    ZOBACZ WSZYSTKIE <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        {* ID DLA JS *}
        <div id="dziecko-content-area" class="dziecko-layout">
            
            <div class="dziecko-info-col">
                <div class="dziecko-info-box">
                    <div class="dziecko-bg-icon"><i class="fa-solid fa-baby"></i></div>
                    <div class="dziecko-box-content">
                        <span class="dziecko-badge">{$box_accent}</span>
                        <h3 class="dziecko-box-title">{$box_title}</h3>
                        <p class="dziecko-box-desc">{$box_desc}</p>
                    </div>
                </div>
            </div>

            <div class="dziecko-right-col">
                
                <div class="dziecko-cats-row">
                    <a href="{$link_food}" class="dziecko-cat-tile">
                        <div class="cat-icon"><i class="fa-solid fa-apple-whole"></i></div>
                        <div class="cat-info">
                            <span class="cat-name">Zdrowa Żywność</span>
                            <span class="cat-sub">Kaszki, słoiczki, przekąski</span>
                        </div>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                    <a href="{$link_care}" class="dziecko-cat-tile">
                        <div class="cat-icon"><i class="fa-solid fa-pump-soap"></i></div>
                        <div class="cat-info">
                            <span class="cat-name">Pielęgnacja i Higiena</span>
                            <span class="cat-sub">Naturalne kosmetyki, pieluchy</span>
                        </div>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                </div>

                <div class="dziecko-products-row">
{/if}
                    {* GRID Z LOADEREM *}
                    <div class="dziecko-grid {if !$dziecko_products}dziecko-lazy-skeleton{/if}">
                        {if $dziecko_products}
                            {foreach from=$dziecko_products item=product}
                                <div class="dziecko-item tvproduct-wrapper">
                                    {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='dziecko_product'}
                                </div>
                            {/foreach}
                        {else}
                            {* LOADER *}
                            <div class="dziecko-loading-overlay">
                                <div class="dziecko-spinner"></div>
                                <span class="dziecko-loading-text">Ładowanie produktów...</span>
                            </div>
                        {/if}
                    </div>
{if !$is_child_ajax}
                </div>
            </div>
        </div>
    </div>
</div>
{/if}
{/strip}