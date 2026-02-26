{**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 * ... (nagłówek licencyjny bez zmian) ...
 *}
{strip}
{block name='category_miniature_item'}
  <div class="category-miniature col-md-3 col-sm-4 col-xs-6">
    <a href="{$category.url}">
      <div class="thumbnail-container">
        {* START: POPRAWIONY WARUNEK IF/ELSE *}
        {* Sprawdzamy, czy obrazek istnieje i czy NIE jest placeholderem 'pl-default-small_default.jpg' *}
        {if !empty($category.image) && $category.image.medium.url && !strstr($category.image.medium.url, 'pl-default-small_default.jpg')}
          {* Jeśli jest obrazek - wyświetlamy go *}
          <img 
            src="{$category.image.medium.url}" 
            alt="{if !empty($category.image.legend)}{$category.image.legend}{else}{$category.name}{/if}" 
            height="{$category.image.medium.height}" 
            width="{$category.image.medium.width}" 
            loading="lazy" 
          />
        {else}
          {* Jeśli NIE MA obrazka - wyświetlamy kafelek tekstowy *}
          <div class="category-text-tile"> 
            <span class="category-name">{$category.name}</span>
          </div>
        {/if}
        {* KONIEC: POPRAWIONY WARUNEK IF/ELSE *}
      </div>
      
      {* ZMIANA: Ukrywamy domyślną nazwę i opis pod miniaturą, jeśli jest kafelek tekstowy *}
      {if empty($category.image) || strstr($category.image.medium.url, 'pl-default-small_default.jpg')}
        {* Nic nie wyświetlamy, bo nazwa jest w kafelku *}
      {else}
        {* Jeśli jest obrazek, wyświetlamy nazwę pod nim *}
        <h3 class="h3 category-title">
          {$category.name}
        </h3>
        {* Można dodać opis, jeśli potrzebujesz *}
        {* <div class="category-description">{$category.description nofilter}</div> *}
      {/if}
    </a>
  </div>
{/block}
{/strip}