{if isset($type_variants) && $type_variants|@count}
<div class="product-variant-selector variant-tile productpro-types">
  <label class="variant-label" for="pp_type_toggle">
    {l s='Inne rodzaje:' d='Shop.Theme.Catalog'}
  </label>

  <div class="variant-control">
    <div class="pp-type-dropdown js-bb-type-dropdown">
      <button
        type="button"
        id="pp_type_toggle"
        class="btn btn-default btn-block pp-type-toggle js-bb-type-toggle"
        aria-haspopup="true"
        aria-expanded="false"
      >
        <span class="pp-type-current">
            {$currentName|escape:'html':'UTF-8'}
        </span>
      </button>

      <ul class="pp-type-list">
        {foreach from=$type_variants item=tv}
          <li class="pp-type-item{if $tv.is_sale} pp-type-item--sale{/if}">
            <a href="{$tv.link}" class="pp-type-link">
              
              {* 1. ZDJĘCIE (Czyste, bez napisów) *}
              {if isset($tv.image_url) && $tv.image_url}
                <span class="pp-type-thumb">
                   <img src="{$tv.image_url}"
                       alt="{$tv.display_name|escape:'html':'UTF-8'}"
                       loading="lazy" />
                </span>
              {/if}

              {* 2. NAZWA PRODUKTU *}
              <span class="pp-type-title">
                {$tv.display_name|escape:'html':'UTF-8'}
              </span>

              {* 3. CENA + ETYKIETA (Prawa strona) *}
              <span class="pp-type-price-block">
                
                {* Etykieta nad ceną *}
                {if $tv.is_sale}
                   <span class="pp-type-badge-pill">ŁAP OKAZJE</span>
                {/if}

                {* Ceny *}
                {if $tv.has_discount}
                  <span class="pp-type-price-new">{$tv.price}</span>
                  <span class="pp-type-price-old">{$tv.price_without_reduction}</span>
                {else}
                  <span class="pp-type-price-new">{$tv.price}</span>
                {/if}
              </span>
            </a>
          </li>
        {/foreach}
      </ul>
    </div>
  </div>
</div>
{/if}