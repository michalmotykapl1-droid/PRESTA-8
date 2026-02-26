{function name="categories_mobile" nodes=[] depth=0}
  {if $nodes|count}
    <ul class="mobile-cat-tree-ul {if $depth == 0}root-level{else}sub-level{/if}" 
        data-depth="{$depth}">
      {foreach from=$nodes item=node}
        <li class="mobile-cat-item cat-id-{$node.id} {if !empty($node.current)}current-active{/if} {if !empty($node.in_path)}in-path{/if}">
            <div class="mobile-cat-header">
                <a href="{$node.link}" class="mobile-cat-link category-id-{$node.id}">
                    {$node.name}
                </a>
                {if $node.children|count}
                    <span class="mobile-cat-toggler" 
                          data-menu-target="#mob-cat-{$node.id}" 
                          aria-expanded="{if !empty($node.in_path)}true{else}false{/if}">
                        <i class="material-icons add">add</i>
                        <i class="material-icons remove">remove</i>
                    </span>
                {/if}
            </div>

            {if $node.children|count}
                <div id="mob-cat-{$node.id}" class="mobile-cat-children {if !empty($node.in_path)}expanded{/if}">
                    {categories_mobile nodes=$node.children depth=$depth+1}
                </div>
            {/if}
        </li>
    {/foreach}
    </ul>
  {/if}
{/function}

<div class="block-categories-mobile-wrapper">
    {* ZMIANA: Zamiast "KATEGORIE" jest teraz przyjemne "Menu" *}
    <div class="mobile-cat-title">Menu</div>
    
    {if isset($categories) && $categories.children|count}
        {categories_mobile nodes=$categories.children depth=0}
    {else}
        <p class="no-cats">{l s='No categories' d='Shop.Theme.Catalog'}</p>
    {/if}
</div>