{strip}
{if !$is_suple_ajax}
<div class="suple-wrapper">
    <div class="container">
        
        <div class="suple-header">
            <div class="suple-header-content">
                <h2 class="suple-title">{$suple_main_title}</h2>
                <div class="suple-line"></div>
                <p class="suple-desc">{$suple_desc}</p>
            </div>
            <div class="suple-btn-container">
                <a href="{$suple_all_link}" class="suple-btn-all">
                    ZOBACZ WSZYSTKIE <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <div id="suple-content-area" class="suple-layout-flex">
            <div class="suple-nav-col">
                <div class="suple-menu">
                    {foreach from=$suple_tabs key=key item=tab name=tabs}
                        <div class="suple-menu-item {if $smarty.foreach.tabs.first}active{/if}" 
                             data-target="suple-tab-{$key}">
                            <div class="suple-icon"><i class="{$tab.icon}"></i></div>
                            <div class="suple-text-wrap">
                                <span class="suple-label">{$tab.title}</span>
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>

            <div class="suple-products-col">
{/if}
                {foreach from=$suple_tabs key=key item=tab name=tabs}
                    <div id="suple-tab-{$key}" 
                         class="suple-pane {if !$is_suple_ajax && $smarty.foreach.tabs.first}active{/if}">
                        
                        {if $tab.products}
                            {* MAMY PRODUKTY *}
                            <div class="suple-grid products">
                                {foreach from=$tab.products item=product}
                                    <div class="suple-item tvproduct-wrapper">
                                        {include file="catalog/_partials/miniatures/product.tpl" 
                                                 product=$product 
                                                 tv_product_type='suple_product'}
                                    </div>
                                {/foreach}
                            </div>
                        {else}
                            {* BRAK PRODUKTÓW -> POKAŻ LOADER *}
                            <div class="suple-loading-overlay suple-lazy-skeleton">
                                <div class="suple-spinner"></div>
                                <span class="suple-loading-text">Ładowanie produktów...</span>
                            </div>
                        {/if}
                    </div>
                {/foreach}
{if !$is_suple_ajax}
            </div>
        </div>
    </div>
</div>
{/if}
{/strip}