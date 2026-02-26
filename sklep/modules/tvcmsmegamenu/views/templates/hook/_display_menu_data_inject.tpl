{* Add after each item header (type==5) to carry JSON for JS labelling *}
{if isset($menuitem.type_link) && $menuitem.type_link==5 && isset($menuitem.link) && $menuitem.link}
  <span class="tvdp-meta" data-tvdp-json="{$menuitem.link|escape:'html':'UTF-8'}"></span>
{/if}
