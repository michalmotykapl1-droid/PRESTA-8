{**
* 2007-2025 PrestaShop
* ...
*}
{strip}

{literal}
<style>
    /* 1. Wiersz produktu */
    .product-line-grid {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        padding: 25px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    /* 2. Prawa strona */
    .product-line-actions-container {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 20px;
        height: 100%;
    }

    /* 3. Ceny */
    .unit-price-display {
        font-size: 13px;
        color: #888;
        text-align: right;
        min-width: 80px;
    }
    .total-price-display {
        font-size: 16px;
        font-weight: 700;
        color: #222;
        min-width: 90px;
        text-align: right;
    }

    /* 4. STEPPER (KAPSUŁKA) - TYLKO CSS NA ORYGINALNYCH ELEMENTACH */
    .qty-stepper .input-group.bootstrap-touchspin {
        width: 110px !important;
        height: 36px !important;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        display: flex !important;
        flex-direction: row !important;
        align-items: stretch !important;
        justify-content: center !important;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: none !important;
        position: static !important;
    }

    /* Input (Środek) - order: 2 */
    .qty-stepper input.js-cart-line-product-quantity {
        order: 2 !important;
        width: 40px !important;
        flex-grow: 1 !important;
        border: none !important;
        border-left: 1px solid #f0f0f0 !important;
        border-right: 1px solid #f0f0f0 !important;
        background: transparent !important;
        text-align: center !important;
        font-weight: 600;
        font-size: 14px;
        color: #333;
        padding: 0 !important;
        margin: 0 !important;
        height: 100% !important;
        display: block !important;
        box-shadow: none !important;
    }

    /* Przyciski Presty (które są spanami lub divami) */
    .qty-stepper .input-group-btn-vertical,
    .qty-stepper .input-group-btn {
        width: auto !important;
        display: flex !important;
        flex-direction: row !important;
    }

    /* Przycisk MINUS (Lewa strona) - order: 1 */
    .qty-stepper .bootstrap-touchspin-down {
        order: 1 !important;
        background: #f9f9f9 !important;
        border: none !important;
        color: #555 !important;
        font-size: 18px !important;
        padding: 0 !important;
        width: 32px !important;
        height: 100% !important;
        cursor: pointer;
        display: flex !important;
        align-items: center;
        justify-content: center;
        position: static !important;
    }
    /* Znak minusa (gdyby ikonka zniknęła) */
    .qty-stepper .bootstrap-touchspin-down::after {
        content: '-' !important;
        font-family: Arial, sans-serif;
        font-weight: 400;
    }

    /* Przycisk PLUS (Prawa strona) - order: 3 */
    .qty-stepper .bootstrap-touchspin-up {
        order: 3 !important;
        background: #f9f9f9 !important;
        border: none !important;
        color: #555 !important;
        font-size: 18px !important;
        padding: 0 !important;
        width: 32px !important;
        height: 100% !important;
        cursor: pointer;
        display: flex !important;
        align-items: center;
        justify-content: center;
        position: static !important;
        margin-top: 0 !important;
    }
    /* Znak plusa */
    .qty-stepper .bootstrap-touchspin-up::after {
        content: '+' !important;
        font-family: Arial, sans-serif;
        font-weight: 400;
    }

    /* Ukrywamy oryginalne ikony wewnątrz przycisków, żeby nie było dubli */
    .qty-stepper i.material-icons,
    .qty-stepper .touchspin-up, 
    .qty-stepper .touchspin-down {
        display: none !important;
    }

    /* Hover */
    .qty-stepper .bootstrap-touchspin-up:hover,
    .qty-stepper .bootstrap-touchspin-down:hover {
        background-color: #eee !important;
        color: #000 !important;
    }

    /* 5. Ikona kosza */
    .remove-action {
        display: flex;
        align-items: center;
        margin-left: 10px;
    }
    .remove-from-cart-icon {
        color: #dcdcdc !important;
        font-size: 20px !important; 
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .remove-from-cart-icon:hover {
        color: #ff4c4c !important;
        transform: scale(1.1);
    }

    /* Mobile */
    @media (max-width: 767px) {
        .product-line-grid { align-items: flex-start; padding: 15px 0; }
        .product-line-actions-container {
            flex-wrap: wrap;
            justify-content: space-between;
            width: 100%;
            margin-top: 15px;
            gap: 10px;
        }
        .unit-price-display { display: none; }
        .total-price-display { font-size: 15px; order: 2; }
        .qty-stepper { order: 1; }
        .remove-action { position: absolute; top: 15px; right: 0; }
    }
</style>
{/literal}

<div class="product-line-grid">
    
    {* KOLUMNA 1: ZDJĘCIE *}
    <div class="col-md-2 col-xs-4">
        <span class="product-image media-middle">
             {if $product.cover.bySize.cart_default.url}
                 <img src="{$product.cover.bySize.cart_default.url}" alt="{$product.name|escape:'quotes'}" loading="lazy" style="max-width: 100%; height: auto; border-radius: 4px;">
             {else}
                 <img src="{$urls.no_picture_image.bySize.cart_default.url}" loading="lazy" style="max-width: 100%; height: auto; border-radius: 4px;">
             {/if}
        </span>
    </div>

    {* KOLUMNA 2: NAZWA I ATRYBUTY *}
    <div class="col-md-4 col-xs-8">
        <div class="product-line-info">
            <a href="{$product.url}" data-id_customization="{$product.id_customization|intval}" style="text-decoration:none; color:#222;">
                <h6 style="font-weight: 600; margin-bottom: 5px; line-height: 1.4;">{$product.name}</h6>
            </a>
        </div>
        {foreach from=$product.attributes key="attribute" item="value"}
            <div class="product-line-info text-muted" style="font-size: 12px; margin-bottom: 2px;">
                <span class="label">{$attribute}:</span>
                <span class="value">{$value}</span>
            </div>
        {/foreach}
        {if $product.customizations|count}
            {block name='cart_detailed_product_line_customization'}
                {foreach from=$product.customizations item="customization"}
                    <a href="#" data-toggle="modal" data-target="#product-customizations-modal-{$customization.id_customization}" style="font-size:12px; text-decoration:underline; color:#999;">
                        {l s='Product customization' d='Shop.Theme.Catalog'}
                    </a>
                    <div class="modal fade customization-modal" id="product-customizations-modal-{$customization.id_customization}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title">{l s='Product customization' d='Shop.Theme.Catalog'}</h4>
                                </div>
                                <div class="modal-body">
                                    {foreach from=$customization.fields item="field"}
                                        <div class="product-customization-line row">
                                            <div class="col-sm-3 col-xs-4 label">{$field.label}</div>
                                            <div class="col-sm-9 col-xs-8 value">
                                                {if $field.type == 'text'}{if (int)$field.id_module}{$field.text nofilter}{else}{$field.text}{/if}
                                                {elseif $field.type == 'image'}<img src="{$field.image.small.url}">{/if}
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}
            {/block}
        {/if}
    </div>

    {* KOLUMNA 3: AKCJE *}
    <div class="col-md-6 col-xs-12">
        <div class="product-line-actions-container">
            
            <div class="unit-price-display hidden-xs-down">
                {if $product.has_discount}
                    <div style="text-decoration: line-through; font-size: 11px; color: #bbb;">{$product.regular_price}</div>
                {/if}
                {$product.price}
            </div>

            {* --- STEPPER (STANDARD PRESTY - OSTYLOWANY) --- *}
            <div class="qty-stepper">
                {if isset($product.is_gift) && $product.is_gift}
                    <span class="gift-quantity">{$product.quantity}</span>
                {else}
                    <input
                        class="js-cart-line-product-quantity"
                        data-down-url="{$product.down_quantity_url}"
                        data-up-url="{$product.up_quantity_url}"
                        data-update-url="{$product.update_quantity_url}"
                        data-product-id="{$product.id_product}"
                        type="text"
                        value="{$product.quantity}"
                        name="product-quantity-spin"
                        min="{$product.minimal_quantity}"
                        max="{$product.available_quantity|default:$product.quantity_available|default:$product.stock_quantity|default:0|intval}"
                    />
                {/if}
            </div>

            <div class="total-price-display">
                {if isset($product.is_gift) && $product.is_gift}
                    <span class="gift" style="color: #ea7404;">{l s='Gift' d='Shop.Theme.Checkout'}</span>
                {else}
                    {$product.total}
                {/if}
            </div>

            <div class="remove-action">
                <a
                    class="remove-from-cart"
                    rel="nofollow"
                    href="{$product.remove_from_cart_url}"
                    data-link-action="delete-from-cart"
                    data-id-product="{$product.id_product|escape:'javascript'}"
                    data-id-product-attribute="{$product.id_product_attribute|escape:'javascript'}"
                    data-id-customization="{$product.id_customization|escape:'javascript'}"
                    title="{l s='remove from cart' d='Shop.Theme.Actions'}"
                >
                    <i class="material-icons remove-from-cart-icon">delete</i>
                </a>
            </div>

        </div>
    </div>

</div>
{/strip}