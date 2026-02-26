{* PrestaShop 8.2.1 - ps_categorytree
   Keep active branch expanded after navigation.
   Place in: themes/YOUR_THEME/modules/ps_categorytree/views/templates/hook/ps_categorytree.tpl
   or modules/ps_categorytree/views/templates/hook/ps_categorytree.tpl
*}

{function name=categories nodes depth}
<ul class="category-sub-menu">
  {foreach from=$nodes item=node}
    <li data-depth="{$depth}" class="{if !empty($node.in_path)} is-in-path{/if}{if !empty($node.current)} current{/if}">
      <a class="category-name{if !empty($node.current)} current{/if}"
         href="{$node.link|escape:'html':'UTF-8'}"
         title="{$node.name|escape:'html':'UTF-8'}">
        {$node.name|escape:'html':'UTF-8'}
      </a>

      {if $node.children|count}
        <div class="navbar-toggler collapse-icons"
             data-toggle="collapse"
             data-target="#exCollapsingNavbar{$node.id|escape:'html':'UTF-8'}"
             aria-controls="exCollapsingNavbar{$node.id|escape:'html':'UTF-8'}"
             aria-expanded="{if !empty($node.in_path)}true{else}false{/if}"
             role="button">
          <i class="material-icons add">&#xE145;</i>
          <i class="material-icons remove">&#xE15B;</i>
        </div>

        <div id="exCollapsingNavbar{$node.id|escape:'html':'UTF-8'}"
             class="collapse{if !empty($node.in_path)} show{/if}"
             aria-expanded="{if !empty($node.in_path)}true{else}false{/if}">
          {categories nodes=$node.children depth=$depth+1}
        </div>
      {/if}
    </li>
  {/foreach}
</ul>
{/function}

{block name='category_tree'}
  <div id="block_categories" class="block-categories">
    <h4 class="block-title">{l s='Categories' d='Shop.Theme.Catalog'}</h4>
    <div class="block_content collapse show" id="block_categories_toggle">
      {if isset($categories) && $categories|count}
        {if isset($categories.children) && $categories.children|count}
          {categories nodes=$categories.children depth=0}
        {else}
          {categories nodes=[$categories] depth=0}
        {/if}
      {/if}
    </div>
  </div>

  {* JS fallback: expand ancestors of the current link on load (no localStorage) *}
  {literal}
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    var container = document.querySelector('#block_categories');
    if (!container) return;

    // Find current link either by ".current" class or by comparing URL path
    var currentLink = container.querySelector('a.current');
    if (!currentLink) {
      var links = container.querySelectorAll('a[href]');
      var path = location.pathname.replace(/\/+$/,''); // strip trailing slash
      for (var i=0;i<links.length;i++) {
        var u = document.createElement('a'); u.href = links[i].href;
        if (u.pathname.replace(/\/+$/,'') === path) { currentLink = links[i]; break; }
      }
    }
    if (!currentLink) return;

    // Walk up and open every parent collapse that wraps this item
    var li = currentLink.closest('li');
    while (li) {
      var parentCollapse = li.closest('div.collapse');
      if (!parentCollapse) break;
      parentCollapse.classList.add('show');
      var toggler = container.querySelector('[data-target="#'+parentCollapse.id+'"]');
      if (toggler) toggler.setAttribute('aria-expanded','true');
      li = parentCollapse.closest('li');
    }
  });
  </script>
  {/literal}
{/block}
