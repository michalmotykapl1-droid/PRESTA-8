{**
 * STREFA PRODUKTOWA - CUSTOM HTML GRID 5x2
 *}
<div class="strefa-custom-wrapper" id="strefa-menu-js-hook">

    {if isset($strefa_tabs)}
        {* ZAKŁADKI *}
        <div class="strefa-custom-tabs">
            {foreach from=$strefa_tabs key=key item=tab name=tabs}
                <div class="custom-tab-btn {if $smarty.foreach.tabs.first}active{/if}" 
                     data-target="custom-tab-{$key}"
                     onclick="switchCustomTab(this, 'custom-tab-{$key}')">
                    {$tab.title}
                </div>
            {/foreach}
        </div>

        {* CONTENT *}
        <div class="strefa-custom-content">
            {foreach from=$strefa_tabs key=key item=tab name=tabs}
                <div id="custom-tab-{$key}" class="custom-pane {if $smarty.foreach.tabs.first}active{/if}">
                    <div class="custom-grid">
                        {foreach from=$tab.products item=product name=prod_loop}
                            {if $smarty.foreach.prod_loop.iteration <= 10}
                                {* WŁASNY KAFELEK PRODUKTU (Lekki i czysty) *}
                                <div class="custom-card">
                                    <a href="{$product.url}" class="custom-img-box">
                                        <img src="{$product.cover.bySize.small_default.url}" alt="{$product.name}" loading="lazy">
                                    </a>
                                    <div class="custom-info-box">
                                        <a href="{$product.url}" class="custom-name">
                                            {$product.name|truncate:40:'...'}
                                        </a>
                                        <div class="custom-price">
                                            {$product.price}
                                        </div>
                                    </div>
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}
</div>

<script>
function switchCustomTab(element, targetId) {
    var container = document.getElementById('strefa-menu-js-hook');
    if(!container) return;
    container.querySelectorAll('.custom-tab-btn').forEach(b => b.classList.remove('active'));
    container.querySelectorAll('.custom-pane').forEach(p => p.classList.remove('active'));
    element.classList.add('active');
    var target = document.getElementById(targetId);
    if(target) target.classList.add('active');
}
</script>