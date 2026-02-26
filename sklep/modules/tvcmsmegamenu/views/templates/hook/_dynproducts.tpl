
{* Clean, safe partial for dynamic products list *}
{if isset($tvdp) && isset($products) && $products|@count}
  {if $tvdp.layout == 'grid'}
    <ul class="ul-column tv-megamenu-slider-wrapper">
      {foreach from=$products item=p}
        <li class="tv-mega-product">
          <a href="{$p.url|escape:'htmlall':'UTF-8'}" class="tv-mega-product__link" title="{$p.name|escape:'htmlall':'UTF-8'}">
            <img src="{$p.cover.bySize.small_default.url|escape:'html':'UTF-8'}" alt="{$p.cover.legend|escape:'htmlall':'UTF-8'}" />
            <span class="tv-mega-product__name">{$p.name|escape:'htmlall':'UTF-8'}</span>
            {if $tvdp.show_price && isset($p.price)}<span class="tv-mega-product__price">{$p.price}</span>{/if}
            {if $tvdp.show_badge && $p.has_discount}<span class="tv-mega-product__badge">{l s='PROMO' d='Shop.Theme.Catalog'}</span>{/if}
          </a>
        </li>
      {/foreach}
    </ul>
  {else}
    <ul class="tv-mega-product-list">
      {foreach from=$products item=p}
        <li class="tv-mega-product-list__item">
          <a href="{$p.url|escape:'htmlall':'UTF-8'}" class="tv-mega-product-list__link" title="{$p.name|escape:'htmlall':'UTF-8'}">
            <img src="{$p.cover.bySize.small_default.url|escape:'html':'UTF-8'}" alt="{$p.cover.legend|escape:'htmlall':'UTF-8'}" />
            <span class="tv-mega-product-list__name">{$p.name|escape:'htmlall':'UTF-8'}</span>
            {if $tvdp.show_price && isset($p.price)}<span class="tv-mega-product-list__price">{$p.price}</span>{/if}
            {if $tvdp.show_badge && $p.has_discount}<span class="tv-mega-product-list__badge">{l s='PROMO' d='Shop.Theme.Catalog'}</span>{/if}
          </a>
        </li>
      {/foreach}
    </ul>
  {/if}
{else}
  <div class="tv-mega-empty">{l s='No products to display.' d='Modules.Tvcmsmegamenu.Admin'}</div>
{/if}
