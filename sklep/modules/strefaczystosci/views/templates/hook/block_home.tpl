{**
 * STREFA CZYSTOŚCI – WIDOK (Loader + Grid)
 *}
{strip}
{if !$is_czystosc_ajax}
<div class="czystosc-wrapper">
    <div class="container">
        
        <div class="czystosc-header">
            <div class="czystosc-header-content">
                <h2 class="czystosc-main-title">{$czystosc_main_title}</h2>
                <div class="czystosc-line"></div>
                <p class="czystosc-main-desc">{$czystosc_main_desc}</p>
            </div>
            <div class="czystosc-btn-container">
                <a href="{$czystosc_all_link}" class="czystosc-btn-all">
                    ZOBACZ WSZYSTKIE  <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        {* ID KONIECZNE DO JS i CACHE *}
        <div id="czystosc-content-area" class="czystosc-layout">
            
            <div class="czystosc-info-col">
                <div class="czystosc-info-box">
                    <div class="czystosc-bg-icon"><i class="fa-solid fa-leaf"></i></div>
                    <div class="czystosc-box-content">
                        <span class="czystosc-badge">{$box_accent}</span>
                        <h3 class="czystosc-box-title">{$box_title}</h3>
                        <p class="czystosc-box-desc">{$box_desc}</p>
                    </div>
                </div>
            </div>

            <div class="czystosc-grid-col">
{/if}
                {* KLASA 'czystosc-lazy-skeleton' MÓWI JS ŻE TRZEBA POBRAĆ DANE *}
                <div class="czystosc-grid {if !$czystosc_products}czystosc-lazy-skeleton{/if}">
                    {if $czystosc_products}
                        {* DANE ZAŁADOWANE *}
                        {foreach from=$czystosc_products item=product}
                            <div class="czystosc-item tvproduct-wrapper">
                                {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='czystosc_product'}
                            </div>
                        {/foreach}
                    {else}
                        {* BRAK DANYCH = LOADER *}
                        <div class="czystosc-loading-overlay">
                            <div class="czystosc-spinner"></div>
                            <span class="czystosc-loading-text">Ładowanie produktów...</span>
                        </div>
                    {/if}
                </div>
{if !$is_czystosc_ajax}
            </div>
        </div>
    </div>
</div>
{/if}
{/strip}