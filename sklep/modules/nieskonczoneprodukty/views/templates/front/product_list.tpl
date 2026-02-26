{foreach from=$np_products item="product"}
    <div class="np-item">
        {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type='tab_product' tab_slider=false}
    </div>
{/foreach}