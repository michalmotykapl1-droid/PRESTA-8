<div class="bb-promo-container-v2 js-bb-promo-box" 
     data-promo-id="{$promo_id}" 
     data-promo-total="{$promo_total}" 
     data-in-cart="{$promo_in_cart}">
    
    {* --- LEWA STRONA (Ikona) --- *}
    <div class="bb-promo-header">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#ea7404" class="bb-promo-svg-icon">
            <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 
5.5 6.33 7 5.5 7z"/>
            <path d="M0 0h24v24H0z" fill="none"/>
        </svg>
        <div class="bb-promo-title-text">ŁAP<br>OKAZJE</div>
    </div>

    {* --- ŚRODEK --- *}
    <div class="bb-promo-content-v2">
        {* 1. Link z dynamicznym tekstem *}
        <div class="bb-row-top">
             <a href="{$promo_url}" class="bb-promo-link-v2" target="_blank" title="Kliknij i sprawdź tańszą wersję">
                {if isset($promo_percentage) && $promo_percentage > 0}
                    Kup ten produkt <strong>{$promo_percentage}% taniej</strong> w strefie okazji!
                {else}
                    Nie przepłacaj! Sprawdź tańszy egzemplarz &raquo;
                {/if}
            </a>
        </div>
        
        {* 2. Info *}
        <div class="bb-row-middle">
            {if $promo_date}
                <span class="bb-date-info">Data ważności: <strong>{$promo_date}</strong></span>
                <span class="bb-sep">•</span>
            {/if}
            <span class="bb-stock-info-v2">
                Dostępne: <strong><span class="js-bb-qty-left">{$promo_left}</span> szt.</strong>
            </span>
        </div>

        {* 3. Statusy *}
        <div class="bb-row-statuses">
            <span class="bb-status-badge bb-status-green js-bb-in-cart-msg" style="{if $promo_in_cart > 0}display:inline-flex{else}display:none !important;{/if}">
                W koszyku: <span class="js-bb-qty-current" style="margin-left:3px">{$promo_in_cart}</span>
            </span>
            
            <span class="bb-status-badge bb-status-limit js-bb-full-msg" style="{if $promo_left <= 0}display:inline-flex{else}display:none !important;{/if}">
                Osiągnięto limit
            </span>
        </div>
    </div>

    {* --- PRAWA STRONA --- *}
    <div class="bb-promo-action-v2">
        <div class="bb-promo-price-v2">
            {$promo_price}
        </div>
        
        <div class="bb-qty-form-wrapper">
            <input type="hidden" class="js-bb-token" value="{$static_token}">
            <input type="hidden" class="js-bb-id-product" value="{$promo_id}">
            
            {* SELEKTOR ILOŚCI (Kolejność: Input, Minus, Plus) *}
            <div class="bb-qty-selector js-bb-qty-selector" style="{if $promo_left <= 0}display:none !important;{/if}">
                
                {* 1. Input *}
                <input type="number" 
                       value="1" 
                       min="1" 
                       max="{$promo_left}" 
                       class="bb-qty-input js-bb-promo-qty-input"
                       readonly
                 >
                 
                {* 2. Minus *}
                <button type="button" class="bb-qty-btn bb-btn-minus">-</button>
                
                {* 3. Plus *}
                <button type="button" class="bb-qty-btn bb-btn-plus">+</button>
            </div>
            
            {* PRZYCISK KOSZYKA (AJAX) *}
            <button class="btn btn-primary bb-promo-btn-v2 js-bb-promo-add-btn {if $promo_left <= 0}hidden-btn{/if}" 
                    type="button" 
                    title="Dodaj do koszyka"
                    {if $promo_left <= 0}disabled{/if}>
                <i class="material-icons shopping-cart">&#xE854;</i>
            </button>
        </div>
    </div>
</div>