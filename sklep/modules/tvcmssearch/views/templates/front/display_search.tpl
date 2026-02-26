{*
  2007-2025 PrestaShop
  Academic Free License (AFL 3.0)
*}
{strip}
<div class="search-widget tvcmsheader-search"
     data-search-controller-url="{$search_controller_url|escape:'htmlall':'UTF-8'}">
  
  <div class="tvsearch-top-wrapper">
    <div class="tvsearch-header-display-wrappper tvsearch-header-display-full">
      
      {* FORMULARZ PREMIUM *}
      <form method="get" action="{$search_controller_url|escape:'htmlall':'UTF-8'}" class="premium-search-form">
        <input type="hidden" name="controller" value="search"/>
        
        {* LEWA STRONA: INPUT + SPINNER *}
        <div class="premium-input-group">
            <input type="text" name="s" class="tvcmssearch-words premium-input"
              value="{$smarty.get.s|default:''|escape:'htmlall':'UTF-8'}"
              placeholder="{l s='Wpisz czego szukasz...' mod='tvcmssearch'}"
              aria-label="{l s='Szukaj' mod='tvcmssearch'}"
              autocomplete="off"/>
            
            {* NOWY ELEMENT: SPINNER (Domyślnie ukryty) *}
            <div class="tvsearch-spinner"></div>
        </div>
       
        {* PRAWA STRONA: PRZYCISK *}
        <button type="submit" class="premium-search-btn" aria-label="{l s='Szukaj' mod='tvcmssearch'}">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="#ea7404" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M21 21L16.65 16.65" stroke="#ea7404" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

      </form>

      {* Wyniki AJAX *}
      <div class="tvsearch-result"></div>
    </div>
  </div>
</div>
{/strip}