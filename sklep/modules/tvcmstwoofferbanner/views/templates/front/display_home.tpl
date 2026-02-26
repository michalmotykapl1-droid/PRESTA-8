{strip}
<div class="tvcmstwoofferbanners-one container-fluid">
    
    {* --- NAGŁÓWEK (STRUKTURA KOPIOWANA Z SUPLEMENTÓW) --- *}
    <div class="tv-allegro-header">
        <div class="tv-allegro-header-content">
            
            {* Tytuł *}
            <h2 class="tv-allegro-title-text">TOP KATEGORIE</h2>
            
            {* Osobny DIV na linię (fizyczny element, nie CSS) *}
            <div class="tv-allegro-line"></div>
            
        </div>
    </div>

    {* --- SLIDER KATEGORII --- *}
    <div class="tv-category-wrapper">
        <div id="tv-category-slider" class="owl-carousel owl-theme">
            {if isset($custom_categories) && $custom_categories}
                {foreach from=$custom_categories item=cat}
                    <div class="item">
                        <a href="{$cat.link}" class="tv-category-tile" title="{$cat.name}">
                            <div class="tv-tile-icon-wrapper">
                                <i class="{$cat.icon}"></i>
                            </div>
                            <div class="tv-tile-name">
                                {$cat.name}
                            </div>
                        </a>
                    </div>
                {/foreach}
            {/if}
        </div>
    </div>
</div>
{/strip}