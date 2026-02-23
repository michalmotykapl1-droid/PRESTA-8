{* /modules/allegrocategorymapper/views/templates/admin/tree_view.tpl *}

{function name=renderTreeViewNode}
    <li>
        <span><i class="icon-folder-open"></i> {$node.name|escape:'html'}</span>
        {if !empty($node.children)}
            <ul style="padding-left: 20px;">
                {foreach from=$node.children item=child}
                    {call name=renderTreeViewNode node=$child}
                {/foreach}
            </ul>
        {/if}
    </li>
{/function}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-sitemap"></i> Podgląd drzewa kategorii utworzonego przez moduł
    </div>
    {if !empty($mappedTree)}
        <div class="panel-body">
            <p>Poniżej znajduje się struktura kategorii utworzona do tej pory w Twoim sklepie przez ten moduł. Pokazywane są tylko te gałęzie, w których znajduje się co najmniej jedna zmapowana kategoria.</p>
            <ul class="tree" style="list-style-type: none; padding-left: 10px;">
                {foreach from=$mappedTree item=topNode}
                    {call name=renderTreeViewNode node=$topNode}
                {/foreach}
            </ul>
        </div>
    {else}
        <div class="panel-body">
            <div class="alert alert-info">Drzewo jest puste. Żadne kategorie nie zostały jeszcze utworzone przez moduł.</div>
        </div>
    {/if}
</div>