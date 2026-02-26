{**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{strip}
<div id="_desktop_cart" class="tvcms-header-cart">
    <div class="blockcart cart-preview {if $cart.products_count > 0}active{else}inactive{/if} tv-header-cart" data-refresh-url="{$refresh_url}">
        <div class="tvheader-cart-wrapper">
            <div class='tvheader-cart-btn-wrapper'>
                <a rel="nofollow" href="JavaScript:void(0);" data-url='{$cart_url}' title='{l s="Cart" d="Shop.Theme.Checkout"}'>
                    <div class="tvcart-icon-text-wrapper">
                        <div class="tv-cart-icon-main">
                            <div class="tv-cart-icon tvheader-right-icon">
                                <i class="material-icons shopping-cart">&#xe8f8;</i>
                            </div>
                        </div>
                        <div class="tv-cart-cart-inner">
                            {*<span class="tvcart-lable">{l s='Your Cart' d='Shop.Theme.Checkout'}</span>
                            <span class="tvcart-total-price">{$cart.totals.total_excluding_tax.value}</span>
                            <span class="tv-cart-title">{l s=' item' d='Shop.Theme.Checkout'}</span>*}
                            <span class="cart-products-count">{count($cart.products)}</span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="tvcmscart-show-dropdown">
                {if $cart.products_count > 0}
                <div class="tvcart-product-list">
                    <div class="tvcart-product-totle">
                        {l s='Your Cart: ' d='Shop.Theme.Checkout'} {count($cart.products)} {if count($cart.products) == 1}{l s='Item' d='Shop.Theme.Checkout'}{else}{l s='Items' d='Shop.Theme.Checkout'}{/if}
                    </div>

                    <div class="tvcart-product-content-box tvscroll-container">
                        {foreach from=$cart.products item=product}
                        <div class="tvcart-product-wrapper items">
                            <div class="tvcart-product-list-img">
                                <a href="{$product.url}" class="tvshoping-cart-dropdown-img-block">
                                    <img src="{$product.cover.bySize.cart_default.url}" width="{$product.cover.bySize.cart_default.width}" height="{$product.cover.bySize.cart_default.height}" loading="lazy">
                                </a>
                            </div>

                            <div class="tvcart-product-content">
                                <div class="tvcart-product-list-quentity">
                                    <div class="tvshoping-cart-dropdown-title">
                                        <a href="{$product.url}" class="">
                                            <span class="product-name">{$product.name}</span>
                                        </a>
                                    </div>

                                </div>
                                <div class="tvcart-product-list-price">
                                    <span class="product-quentity">{$product.quantity}</span>
                                    <span class="tvshopping-cart-quentity">X</span>
                                    <span class="product-price">{$product.price}</span>
                                </div>

                                <div class="tvcart-product-list-attribute">
                                    {foreach $product.attributes as $prod_attb=>$prod_val}
                                        <div class="tvcart-product-attr"><span>{$prod_attb}:</span> <span>{$prod_val}</span></div>
                                    {/foreach}
                                </div>
                                <div class="tvcart-product-remove">
                                    {$url = 'controller=cart&delete='|cat:$product.id_product}
                                    <a class="remove-from-cart tvcmsremove-from-cart"
                                        rel="nofollow"
                                        href                                = "{$product.remove_from_cart_url}"
                                        data-link-action                    = "delete-from-cart"
                                        data-id-product                     = "{$product.id_product|escape:'javascript'}"
                                        data-id-product-attribute           = "{$product.id_product_attribute|escape:'javascript'}"
                                        data-id-customization               = "{$product.id_customization|escape:'javascript'}"
                                        title="{l s='remove from cart' d='Shop.Theme.Actions'}"
                                    >
                                        <i class='material-icons'>&#xe872;</i>
                                    </a>
                                </div>

                                {if $product.customizations|count}
                                    <div class="customizations">
                                        <ul>
                                            {foreach from=$product.customizations item='customization'}
                                                <li>
                                                    <span class="product-quantity">{$customization.quantity}</span>
                                                    <a href="{$customization.remove_from_cart_url}" title="{l s='remove from cart' d='Shop.Theme.Actions'}" class="remove-from-cart" rel="nofollow">{l s='Remove' d='Shop.Theme.Actions'}</a>
                                                    <ul>
                                                        {foreach from=$customization.fields item='field'}
                                                            <li>
                                                                <span>{$field.label}</span>
                                                                {if $field.type == 'text'}
                                                                    <span>{$field.text nofilter}</span>
                                                                {else if $field.type == 'image'}
                                                                    <img src="{$field.image.small.url}" loading="lazy">
                                                                {/if}
                                                            </li>
                                                        {/foreach}
                                                    </ul>
                                                </li>
                                            {/foreach}
                                        </ul>
                                    </div>
                                {/if}
                            </div>
                        </div>
                        {/foreach}
                    </div>
                    <div class="tvcart-product-list-total-info">
                        <div class="tvcart-product-list-subtotal-prod">
                            <span class="tvshoping-cart-subtotal">{l s='Sub Total' d='Shop.Theme.Checkout'}</span>
                            <span class="tvcart-product-price">{$cart.subtotals.products.value}</span>
                        </div>
                        {* ZAKOMENTOWANE FRAGMENTY ZOSTAWIONE ZGODNIE Z ORYGINALNĄ STRUKTURĄ, ALE Z POPRAWKAMI W RAZIE ODKOMENTOWANIA *}
                        {if isset($cart.subtotals.shipping.value)}
                        <div class="tvcart-product-list-subtotal-shipping">
                            <span class="tvshoping-cart-shipping">{l s='Shipping' d='Shop.Theme.Checkout'}</span>
                            <span class="tvcart-product-price">{$cart.subtotals.shipping.value|default:''}</span>
                        </div>
                        {/if}
                        {if isset($cart.subtotals.tax.value)}
                        <div class="tvcart-product-list-subtotal-tax">
                            <span class="tvshoping-cart-tax">{$cart.subtotals.tax.label|default:''}</span>
                            <span class="tvcart-product-price">{$cart.subtotals.tax.value|default:''}</span>
                        </div>
                        {/if}
                        {if isset($cart.totals.total_excluding_tax.value)}
                        <div class="tvcart-product-list-subtotal-excluding-text">
                            <span class="ttshoping-cart-total">{l s='Total' d='Shop.Theme.Checkout'}</span>
                            <span class="tvcart-product-price">{$cart.totals.total_excluding_tax.value|default:''}</span>
                        </div>
                        {/if}
                    </div>
                </div>
                <div class="tvcart-product-list-btn-wrapper">
                    <div class="tvcart-product-list-viewcart">
                        <a href="{$cart_url}">{l s='View cart' d='Shop.Theme.Checkout'}</a>
                    </div>

                    <div class="tvcart-product-list-checkout">
                        <a href="javascript:void(0);" class="tvcart-product-list-checkout-link" >{l s='Proceed to checkout' d='Shop.Theme.Checkout'}</a>
                    </div>
                </div>
                {else}
                <div class="tvcart-no-product">
                    <div class='tvcart-no-product-label'>{l s='Twój koszyk jest pusty' d='Shop.Theme.Checkout'}</div>
                </div>
                {/if}
            </div>

        </div>
    </div>
</div>
{/strip}