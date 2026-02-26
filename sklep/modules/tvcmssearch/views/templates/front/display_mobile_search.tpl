{**
* 2007-2025 PrestaShop
* Academic Free License (AFL 3.0)
*}
{strip}
<div id="tvcmssearch-mobile" class="search-widget tvcmsheader-search" data-search-controller-url="{$search_controller_url|escape:'htmlall':'UTF-8'}" data-ajax-url="{$link->getModuleLink('tvcmssearch','ajax')|escape:'htmlall':'UTF-8'}">
	<div class="tvsearch-top-wrapper">
		
		<div class="tvsearch-header-display-wrappper">
			
			{* IKONA ZAMKNIĘCIA (Ukryta) *}
			<div class="tvsearch-close hidden-md-up" style="display:none;">
				<i class='material-icons'>&#xe5cd;</i>
			</div>

			<form method="get" action="{$search_controller_url|escape:'htmlall':'UTF-8'}" class="mobile-search-form">
				<input type="hidden" name="controller" value="search" />
				
				{* KONTENER: INPUT + PRZYCISK WEWNĄTRZ *}
				<div class="mobile-search-inner">
					
					{* POLE TEKSTOWE *}
					<input type="text" 
						   name="s" 
						   class='tvcmssearch-words mobile-search-input' 
						   placeholder="{l s='Szukaj...' mod='tvcmssearch'}" 
						   aria-label="{l s='Search' mod='tvcmssearch'}" 
						   autocomplete="off"/>
					
					{* PRZYCISK SZUKAJ (TA SAMA IKONA CO NA DESKTOPIE) *}
					<button type="submit" class="mobile-search-btn">
                        {* Dokładnie ta sama ikona SVG co w display_search.tpl *}
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="#ea7404" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 21L16.65 16.65" stroke="#ea7404" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
					</button>

				</div>
			</form>
		</div>
	</div>
</div>
{/strip}