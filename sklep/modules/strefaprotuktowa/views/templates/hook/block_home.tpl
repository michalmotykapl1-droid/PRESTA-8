{strip}
{if !$is_ajax_loading}
<div class="strefa-wrapper">
    <div class="container">
        <div class="strefa-header">
            <div class="strefa-accent">{$strefa_accent}</div>
            <h2 class="strefa-title">{$strefa_main_title}</h2>
            <div class="strefa-line"></div>
            <p class="strefa-desc">{$strefa_desc}</p>
        </div>

        <div id="strefa-content-area" class="strefa-layout-flex">
{/if}

            {* LEWA KOLUMNA *}
            <div class="strefa-left-col">
                {if $is_ajax_loading}
                    {foreach from=$strefa_tabs key=key item=tab name=tabs}
                        {assign var="hasDeal" value=(isset($strefa_deals.$key) && $strefa_deals.$key)}
                        <div class="strefa-deal-container {if $smarty.foreach.tabs.first}active{/if}" data-deal-target="strefa-tab-{$key}">
                            <div class="strefa-deal-box">
                                <div class="strefa-deal-top-bar">
                                    <div class="strefa-deal-badge"><i class="fa-solid fa-star"></i> HIT DNIA</div>
                                </div>
                                <div class="strefa-deal-desc-internal"><p>{$strefa_descriptions.$key}</p></div>
                                <div class="strefa-deal-content">
    {if $hasDeal}
        <div class="strefa-deal-item">
            {include file="catalog/_partials/miniatures/product.tpl" product=$strefa_deals.$key tv_product_type='deal_product'}
        </div>
    {else}
        {* Jeśli AJAX załadował dane, ale brak produktu -> Pokaż info zamiast spinnera *}
        <div class="strefa-empty-deal" style="text-align:center; padding: 20px; color: #999;">
            <i class="fa-solid fa-box-open" style="font-size: 30px; margin-bottom: 10px; display:block;"></i>
            <span>Oferta niedostępna</span>
        </div>
    {/if}
</div>
                            </div>
                        </div>
                    {/foreach}
                {else}
                    {* SKELETON INITIAL LEFT *}
                    <div class="strefa-deal-container active" data-deal-target="strefa-tab-featured">
                        <div class="strefa-deal-box">
                            <div class="strefa-deal-top-bar"><div class="strefa-deal-badge"><i class="fa-solid fa-star"></i> HIT DNIA</div></div>
                            <div class="strefa-deal-desc-internal"><p>{$strefa_descriptions.featured}</p></div>
                            <div class="strefa-deal-content"><div class="strefa-spinner"></div></div>
                        </div>
                    </div>
                {/if}
            </div>

            {* PRAWA KOLUMNA *}
            <div class="strefa-right-col">
                <div class="strefa-tabs-header">
                    <div class="strefa-tabs-buttons">
                        {foreach from=$strefa_tabs key=key item=tab name=tabs}
                            <div class="strefa-tab-btn {if $smarty.foreach.tabs.first}active{/if}" data-target="strefa-tab-{$key}">
                                <i class="{$tab.icon}"></i> <span>{$tab.title}</span>
                            </div>
                        {/foreach}
                    </div>
                    <div class="strefa-tabs-links">
                        {foreach from=$strefa_tabs key=key item=tab name=tabs}
                            <a href="{$tab.url}" class="strefa-more-link {if $smarty.foreach.tabs.first}active{/if}" data-link-target="strefa-tab-{$key}">ZOBACZ WSZYSTKIE <i class="fa-solid fa-arrow-right"></i></a>
                        {/foreach}
                    </div>
                </div>

                <div class="strefa-content">
                    {if $is_ajax_loading}
                        {foreach from=$strefa_tabs key=key item=tab name=tabs}
                            <div id="strefa-tab-{$key}" class="strefa-pane {if $smarty.foreach.tabs.first}active{/if}">
                                {if $tab.products}
                                    <div class="strefa-grid products">
                                        {foreach from=$tab.products item=product}
                                            <div class="strefa-item tv-product-wrapper">
                                                {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='tab_product'}
                                            </div>
                                        {/foreach}
                                    </div>
                                {else}
                                    {* ZAKŁADKA NIEZAŁADOWANA -> JEDEN LOADER *}
                                    <div class="strefa-loading-overlay strefa-lazy-skeleton">
                                        <div class="strefa-spinner"></div>
                                        <span class="strefa-loading-text">Ładowanie produktów...</span>
                                    </div>
                                {/if}
                            </div>
                        {/foreach}
                    {else}
                        {* INITIAL SKELETON -> JEDEN LOADER *}
                        <div class="strefa-loading-overlay">
                            <div class="strefa-spinner"></div>
                            <span class="strefa-loading-text">Ładowanie produktów...</span>
                        </div>
                    {/if}
                </div>
            </div>

{if !$is_ajax_loading}
        </div> </div>
</div>
{/if}
{/strip}