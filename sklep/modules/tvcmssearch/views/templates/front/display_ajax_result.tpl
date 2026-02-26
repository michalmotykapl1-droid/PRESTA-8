{**
* 2007-2025 PrestaShop
* Academic Free License (AFL 3.0)
* CLEAN VERSION - Mobile Categories on Top with SVG Icons
*}
{strip}
<div class="tvcmssearch-dropdown">
    <div class="tvsearch-dropdown-close-wrapper tvsearch-dropdown-close">
        <i class="material-icons">&#xe5cd;</i>
    </div>

    {* Główny kontener *}
    <div class="tvsearch-results-container">
        
        {* LEWA KOLUMNA: TYLKO KATEGORIE (DLA DESKTOP - ukrywana na mobile przez CSS) *}
        <div class="tvsearch-filter-column hidden-md-down">
            {if $showCategories && !empty($options.categories)}
                <h4>{l s='Kategorie' mod='tvcmssearch'}</h4>
                <ul class="tvsearch-category-list">
                    {foreach from=$options.categories item=category}
                        <li class="tvsearch-category-item">
                            <a href="#" class="tvsearch-category-link" data-id-category="{$category.id_category|escape:'htmlall':'UTF-8'}" data-category-url="{$category.url|escape:'htmlall':'UTF-8'}">
                                {$category.name|escape:'htmlall':'UTF-8'}
                                {if $showCatCount && isset($category.product_count)}
                                    <span class="tvsearch-product-count">({$category.product_count})</span>
                                {/if}
                            </a>
                        </li>
                    {/foreach}
                </ul>
            {/if}
        </div>

        {* PRAWA KOLUMNA: PRODUKTY *}
        <div class="tvsearch-products-column">
            
            {* --- NOWOŚĆ: SEKCJA KATEGORII DLA MOBILE (Top 5 + Specjalne SVG) --- *}
            {if $showCategories && !empty($options.categories)}
                <div class="tvsearch-mobile-categories-wrapper hidden-lg-up">
                    <div class="tvsearch-mobile-cat-header">
                        {l s='Najwięcej produktów w kategoriach:' mod='tvcmssearch'}
                    </div>
                    <div class="tvsearch-mobile-chips-scroll">
                        {* Logika: Najpierw 45 i 180, potem 5 najpopularniejszych *}
                        {assign var="counter_normal" value=0}
                        
                        {foreach from=$options.categories item=category}
                            {* Czy to kategoria specjalna? *}
                            {if $category.id_category == 45 || $category.id_category == 180}
                                <a href="#" class="tvsearch-category-link mobile-cat-chip special-chip-{$category.id_category}" data-id-category="{$category.id_category}" data-category-url="{$category.url}">
                                    
                                    {* --- IKONY SVG --- *}
                                    {if $category.id_category == 45}
                                        {* IKONA ETYKIETY (Dla Okazji) *}
                                        <svg class="chip-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
                                        </svg>
                                    {elseif $category.id_category == 180}
                                        {* IKONA KLEPSYDRY (Dla Krótkiej Daty) *}
                                        <svg class="chip-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                             <path d="M6 2v6h.01L6 8.01 10 12l-4 3.99L6 16v6h12v-6l-4-3.99L18 8.01 17.99 8H18V2H6zm10 14.5V20H8v-3.5l4-4 4 4z"/>
                                        </svg>
                                    {/if}
                                    {* ----------------- *}

                                    {$category.name} ({$category.product_count})
                                </a>
                            {* Jeśli nie, to czy mieścimy się w limicie 5? *}
                            {elseif $counter_normal < 5}
                                <a href="#" class="tvsearch-category-link mobile-cat-chip" data-id-category="{$category.id_category}" data-category-url="{$category.url}">
                                    {$category.name} ({$category.product_count})
                                </a>
                                {assign var="counter_normal" value=$counter_normal+1}
                            {/if}
                        {/foreach}
                    </div>
                </div>
            {/if}
            {* ----------------------------------------------------------- *}

            <div class="tvsearch-all-dropdown-wrapper">
                {if !empty($result_data.html)}
                    {$result_data.html nofilter}
                {else}
                    <p class="no-results">{l s='Nie znaleziono produktów.' mod='tvcmssearch'}</p>
                {/if}
            </div>
            
            <div class="tvsearch-more-search-wrapper">
                {if isset($result_data.total) && isset($products) && $result_data.total > $products|@count}
                    <button type="button" class="tvsearch-show-all-results-btn btn btn-primary">
                        <span>{l s='Więcej wyników' mod='tvcmssearch'}</span>
                    </button>
                {/if}
            </div>
        </div>
    </div>       
</div>
{/strip}