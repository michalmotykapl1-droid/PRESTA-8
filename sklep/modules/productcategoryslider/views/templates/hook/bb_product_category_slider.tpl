{strip}
{* --- MODUŁ: PRODUCT CATEGORY SLIDER (Mobile Friendly) --- *}

{if !$is_pcslider_ajax}
    {* --- LOADING --- *}
    <section class="pcslider-section">
        <div class="container">
            <div id="pcslider-content-area" 
                 class="pcslider-lazy-skeleton"
                 data-pid="{$current_pid}" 
                 data-cid="{$current_cid}">
                 <div style="text-align:center; padding: 50px; color:#999;">Ładowanie produktów...</div>
            </div>
        </div>
    </section>
{else}
    {* --- TREŚĆ --- *}
    {if isset($pcslider_products) && $pcslider_products}
        <div class="pcslider-container">
            
            {* 1. NAGŁÓWEK (Tytuł + Linia) *}
            <div class="pcslider-top-bar">
                <div class="pcslider-title-wrapper">
                    <h3 class="pcslider-title">{$pcslider_title}</h3>
                    <div class="pcslider-line"></div>
                </div>
            </div>

            {* 2. SLIDER PRODUKTÓW *}
            <div class="pcslider-slider-wrapper">
                <div class="pcslider-slider" id="pcSlider">
                    {foreach from=$pcslider_products item="product"}
                        <div class="pcslider-item">
                            {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='tab_product' tab_slider=false}
                        </div>
                    {/foreach}
                </div>
            </div>

            {* 3. STRZAŁKI NAWIGACJI *}
            {* Na Desktopie: CSS przenosi je na górę w prawo. *}
            {* Na Mobile: Zostają tutaj, pod produktami. *}
            <div class="pcslider-arrows-wrapper">
                <button class="pc-arrow pc-prev" aria-label="Poprzednie"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="pc-arrow pc-next" aria-label="Następne"><i class="fa-solid fa-chevron-right"></i></button>
            </div>

        </div>
    {/if}
{/if}
{/strip}