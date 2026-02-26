{strip}
{if !$is_dieta_ajax}
<div class="dietaprodukty-wrapper">
  <div class="container">
    
    <div class="diet-bestsellers-header">
        <h2 class="diet-bestsellers-title">BESTSELLERY TWOJEJ DIETY</h2>
        <div class="diet-separator-line"></div>
        <p class="diet-bestsellers-desc">
            Odkryj produkty, które cieszą się największym zaufaniem.<br>
            Zobacz, co najczęściej wybierają osoby dbające o ten sam styl odżywiania co Ty.
        </p>
    </div>

    <div id="dieta-content-area" class="dieta-layout">
      
      <div class="dieta-sidebar">
        <ul class="dieta-nav">
          {foreach from=$diets_data item=diet key=k}
            <li class="dieta-nav-item {if $k == 'gluten'}active{/if}" data-target="{$diet.info.id_tab}">
              <span class="dieta-nav-icon"><i class="{$diet.info.icon}"></i></span>
              <span class="dieta-nav-text">{$diet.info.name}</span>
            </li>
          {/foreach}
        </ul>
      </div>

      <div class="dieta-content">
{/if}
        {foreach from=$diets_data item=diet key=k}
        <div class="dieta-tab-pane {if !$is_dieta_ajax && $k == 'gluten'}active{/if} {if !$diet.products}dieta-lazy-skeleton{/if}" id="{$diet.info.id_tab}">
          
          {* Kontener Scroll musi być zawsze obecny, aby JS mógł do niego wstawić treść *}
          <div class="diet-scroll-container">
              {if $diet.products}
                  <div class="diet-products-grid products">
                      {foreach from=$diet.products item=product}
                        <div class="product-miniature-wrapper tv-product-wrapper">
                             {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='tab_product' tab_slider=false} 
                        </div>
                      {/foreach}
                  </div>
              {else}
                  <div class="dieta-loading-overlay">
                      <div class="dieta-spinner"></div>
                      <span class="dieta-loading-text">Ładowanie produktów...</span>
                  </div>
              {/if}
          </div>

        </div>
        {/foreach}
{if !$is_dieta_ajax}
      </div>
    </div>
  </div>
</div>
{/if}
{/strip}