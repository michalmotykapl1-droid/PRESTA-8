{* NIESKOŃCZONE PRODUKTY - Układ Grid *}
<section class="np-section-wrapper" id="np-infinite-scroll" data-cat-id="{$np_id_category}">
    <div class="np-container">
        <div class="np-header">
            <h3 class="np-title">{$np_title|escape:'html':'UTF-8'}</h3>
            <div class="np-title-line"></div>
        </div>

        <div class="np-products-list" id="np-grid-container">
            {include file="module:nieskonczoneprodukty/views/templates/front/product_list.tpl" np_products=$np_products}
        </div>

        <div class="np-loader" id="np-loader">
            <div class="np-spinner"></div>
            <span style="color:#999; font-size:13px; text-transform:uppercase;">Wczytuję więcej...</span>
        </div>

        <div class="np-end-message" id="np-end-message">Wyświetlono wszystkie produkty.</div>
    </div>
</section>