
{*
  Placeholder used in admin "Wiersze menu" list.
  Expected variables:
    - info.link (JSON string)
    - info.type == 5 indicates Dynamic products
*}
{if isset($info.type) && $info.type == 5}
  <div class="tvdp-list-preview" data-tvdp-config="{$info.link|escape:'htmlall':'UTF-8'}">
    <ul class="ul-column tv-megamenu-slider-wrapper"></ul>
  </div>
{/if}
