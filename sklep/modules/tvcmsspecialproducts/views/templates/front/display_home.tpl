{**
* STREFA OKAZJI - SZKIELET (LOADER)
*}
{strip}
<div class="special-products-wrapper">
    <div class="container">
        
        {* --- NAGŁÓWEK --- *}
        <div class="special-header-section">
            <div class="special-header-accent">ŁAP OKAZJE</div>
            <h2 class="special-header-title">{l s='STREFA OKAZJI' mod='tvcmsspecialproducts'}</h2>
            <div class="special-header-line"></div>
            <p class="special-header-desc">
                Poluj na prawdziwe perełki! Twoje ulubione produkty Premium i BIO w cenach, które pokochasz.<br>
                Bądź sprytny – kupuj pełnowartościowe towary za ułamek ich wartości, zanim znikną z półki.
            </p>
        </div>

        {* --- MIEJSCE NA TREŚĆ (TUTAJ WSTRZYKUJEMY display_home-data.tpl) --- *}
        <div id="special-content-area" class="special-lazy-skeleton">
            
            {* LOADER (WIDOCZNY NA START) *}
            <div class="special-loading-overlay">
                <div class="special-spinner"></div>
                <span class="special-loading-text">Ładowanie okazji...</span>
            </div>

        </div>

    </div>
</div>
{/strip}