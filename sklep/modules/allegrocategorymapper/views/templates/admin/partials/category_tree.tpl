{* /modules/allegrocategorymapper/views/templates/admin/partials/category_tree.tpl *}

{function name=renderNode}
<li class="acm-node">
    <div class="acm-node-row">
        {if $node.children|@count > 0}
            <button type="button" class="acm-toggle" aria-label="toggle"></button>
        {else}
            <span class="acm-bullet"></span>
        {/if}
        <label class="acm-check">
            <input type="checkbox" class="acm-chk" name="category_ids[]" value="{$node.id|intval}">
            <span class="acm-node-name">
                {$node.name|escape:'html'}
                {* Pokazujemy liczbę produktów tylko jeśli kategoria jest liściem (nie ma dzieci) *}
                {if $node.children|@count == 0 && $node.product_count > 0}
                    <span class="acm-product-count">({$node.product_count})</span>
                {/if}
            </span>
            
            {* --- POCZĄTEK ZMIAN --- *}
            {* Nowa, rozbudowana logika statusów *}
            {if $node.is_mapped}
                {* Ten status pozostaje bez zmian *}
                <span class="acm-status mapped">powiązana</span>
            {else}
                {* Jeśli kategoria nie jest powiązana, sprawdzamy czy ma produkty *}
                {if $node.product_count > 0}
                    {* Ma produkty, więc jest "do zrobienia" *}
                    <span class="acm-status todo">do zrobienia</span>
                {else}
                    {* Nie ma produktów, więc jest "zrobione" *}
                    <span class="acm-status done">zrobione</span>
                {/if}
            {/if}
            {* --- KONIEC ZMIAN --- *}

        </label>
    </div>
    {if $node.children|@count > 0}
        <ul class="acm-children">
            {foreach from=$node.children item=child}
                {call name=renderNode node=$child}
            {/foreach}
        </ul>
    {/if}
</li>
{/function}

<ul class="acm-tree">
    {foreach from=$categoriesTree item=top}
        {call name=renderNode node=$top}
    {/foreach}
</ul>