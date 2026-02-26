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
 * versions in the future.
 * If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{strip}
    {* DODATKOWY STYL DLA EFEKTU NAJECHANIA I POPRAWKI KOSZYKA *}
    {literal}
    <style>
        /* Płynne przejście */
        .ttvcart-product-list-viewcart, 
        .ttvcart-product-list-checkout {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .ttvcart-product-list-viewcart:hover, 
        .ttvcart-product-list-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            opacity: 0.9;
            cursor: pointer;
        }

        /* USUNIĘCIE KRESEK POZIOMYCH W PODSUMOWANIU */
        .ttvcart-price-shipping-text,
        .ttvcart-product-label-value,
        .ttvcart-product-label-value.total,
        .ttvcart-product-label-value.tax {
            border: none !important;
            border-top: none !important;
            border-bottom: none !important;
            box-shadow: none !important;
        }
        .ttvcart-price-shipping-text .ttvcart-product-label-value,
        .ttvcart-price-shipping-text div {
            border: none !important;
            margin-bottom: 5px !important;
        }
        .ttvcart-price-shipping-text::before, .ttvcart-price-shipping-text::after,
        .ttvcart-product-label-value::before, .ttvcart-product-label-value::after {
            display: none !important;
            border: none !important;
        }

        /* --- STYL PASKA DARMOWEJ DOSTAWY (WERSJA KOMPAKTOWA) --- */
        .fs-cart-banner {
            background: #fdfdfd;
            padding: 12px 10px; /* Zmniejszony padding boczny = więcej miejsca na tekst */
            border-bottom: 1px solid #f0f0f0;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Flexbox: Ikonka i tekst w jednej linii, bez zawijania bloku */
        .fs-info-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #444;
            line-height: 1.3;
            flex-wrap: nowrap; /* WAŻNE: Zapobiega spadaniu ikonki */
        }
        
        .fs-icon {
            color: #ea7404;
            font-size: 20px !important;
            flex-shrink: 0; /* Ikonka nigdy się nie zmniejszy */
        }
        
        .fs-text-content {
            text-align: left; /* Lepiej wygląda przy dłuższym tekście */
            font-size: 12px;  /* Lekko mniejsza czcionka, żeby zmieścić całość */
        }

        .fs-highlight {
            color: #ea7404;
            font-weight: 700;
            font-size: 13px; /* Kwota wyróżniona */
        }
        
        .fs-progress-bg {
            background: #eee;
            height: 6px;
            width: 100%;
            margin: 0 auto;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .fs-progress-fill {
            background: #ea7404;
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
    </style>
    {/literal}

    <div id="_desktop_cart">
        <div class="blockcart cart-preview {if $cart.products_count > 0}active{else}inactive{/if} tv-header-cart" data-refresh-url="{$refresh_url}">
            <div class="tvheader-cart-wrapper {if Configuration::get('TVCMSCUSTOMSETTING_CART_VIEW') == 'pop-up'}tvheader-cart-wrapper-popup{/if}">
                <div class='tvheader-cart-btn-wrapper'>
                    <a rel="nofollow" href="JavaScript:void(0);" data-url='{$cart_url}' title='{l s="Cart" d="Shop.Theme.Checkout"}'>
                        <div class="tvcart-icon-text-wrapper">
                            <div class="tv-cart-icon tvheader-right-icon tv-cart-icon-main">
                                <svg version="1.1" id="Layer_1" x="0px" y="0px" width="36px" height="36.289px" viewBox="0 0 36 36.289" xml:space="preserve">
                                    <g>
                                        <path style="fill:#FFD741;" d="M6.266,11.058h28.609l-6.657,11.975H8.019C8.019,23.033,5.582,12.35,6.266,11.058"></path>
                                        <path d="M9.174,29.448c-0.29,0-0.525-0.235-0.525-0.523c0-0.291,0.236-0.528,0.525-0.528h22.059c1.223,0,2.217-0.995,2.217-2.218
                                c0-1.224-0.994-2.22-2.217-2.22H8.193c-0.257,0-0.475-0.183-0.517-0.435L4.537,5.223L0.25,0.992
                                c-0.101-0.098-0.157-0.229-0.16-0.37C0.088,0.483,0.142,0.351,0.241,0.248C0.339,0.146,0.473,0.09,0.615,0.09
                                c0.14,0,0.268,0.052,0.37,0.149l4.396,4.286c0.081,0.078,0.133,0.177,0.151,0.287l0.914,5.322h28.938
                                c0.188,0,0.361,0.101,0.454,0.264c0.095,0.163,0.094,0.364-0.001,0.526L28.88,22.907h2.354c1.803,0,3.27,1.468,3.27,3.271
                                c,1.803-1.467,3.27-3.27,3.27H9.174z M8.637,22.907h18.912l6.922-11.721H6.626L8.637,22.907z"></path>
                                        <ellipse style="fill:none;stroke:#000000;stroke-linejoin:round;stroke-miterlimit:10;" cx="15.269" cy="33.8" rx="1.934" ry="1.963"></ellipse>
                                        <ellipse style="fill:none;stroke:#000000;stroke-linejoin:round;stroke-miterlimit:10;" cx="25.147" cy="33.8" rx="1.934" ry="1.963"></ellipse>
                                    </g>
                                </svg>
                            </div>
                            <div class="tv-cart-cart-inner">
                                <span class="cart-products-count">{$cart.products_count}</span>
                            </div>
                        </div>
                    </a>
                </div>
                {if Configuration::get('TVCMSCUSTOMSETTING_CART_VIEW') == 'classic'}
                <div class="ttvcmscart-show-dropdown-right">
                    {if $cart.products_count > 0}
                    <div class="ttvcart-scroll-container">
                        <div class="ttvcart-close-title-count">
                            <button class="ttvclose-cart"></button>
                            <div class="ttvcart-top-title">
                                <h4>{l s='Shopping Cart' d='Shop.Theme.Checkout'}</h4>
                            </div>
                            <div class="ttvcart-counter">
                                <span class="ttvcart-products-count">{$cart.products_count}</span>
                            </div>
                        </div>

                        {* --- PASEK DARMOWEJ DOSTAWY --- *}
                        {capture name='bb_free_ship_cfg'}{Configuration::get('PS_SHIPPING_FREE_PRICE')}{/capture}
                        {assign var='free_shipping_threshold' value=$smarty.capture.bb_free_ship_cfg}
                        
                        {if $free_shipping_threshold > 0}
                            {assign var='current_total' value=$cart.subtotals.products.amount}
                            {math equation="x - y" x=$free_shipping_threshold y=$current_total assign="remaining"}
                            {if $remaining < 0}
                                {assign var='remaining' value=0}
                            {/if}
                            {math equation="(x * 100) / y" x=$current_total y=$free_shipping_threshold assign="percent"}
                            {if $percent > 100}
                                {assign var='percent' value=100}
                            {/if}
                            {if $percent < 0}
                                {assign var='percent' value=0}
                            {/if}

                            <div class="fs-cart-banner" data-free-shipping-threshold="{$free_shipping_threshold}">
                                <div class="fs-info-row">
                                    {* Ikonka *}
                                    <i class="material-icons fs-icon">&#xE558;</i>
                                    
                                    <div class="fs-text-content">
                                        {if $remaining > 0}
                                            Pozostało Ci tylko&nbsp;<span class="fs-highlight">{$remaining|number_format:2:',':' '} zł</span>&nbsp;do darmowej dostawy!
                                        {else}
                                            <span class="fs-highlight">
                                                Świetnie! Masz DARMOWĄ DOSTAWĘ!
                                            </span>
                                        {/if}
                                    </div>
                                </div>
                                <div class="fs-progress-bg">
                                    <div class="fs-progress-fill" style="width:{$percent|intval}%"></div>
                                </div>
                            </div>
                        {/if}
                        {* ----------------------------------------------------------- *}

                        <div class="ttvcart-product-content-box ttvscroll-container">
                            {foreach from=$cart.products item=product}
                            <div class="ttvcart-product-wrapper items">
                                <div class="tvcart-product-list-img">
                                    <a href="{$product.url}" class="tvshoping-cart-dropdown-img-block">
                                        {if $product.default_image}
                                            <img
                                              src="{$product.default_image.medium.url}"
                                              data-full-size-image-url="{$product.default_image.large.url}"
                                              title="{$product.default_image.legend}"
                                              alt="{$product.default_image.legend}"
                                              loading="lazy"
                                              class="product-image"
                                            >
                                        {else}
                                            <img
                                              src="{$urls.no_picture_image.bySize.medium_default.url}"
                                              loading="lazy"
                                              class="product-image"
                                            />
                                        {/if}
                                    </a>
                                </div>
                                <div class="tvcart-product-content">
                                    <div class="tvshoping-cart-dropdown-title">
                                        <a href="{$product.url}" class="">
                                            <span class="product-name">{$product.name}</span>
                                        </a>
                                    </div>
                                    <div class="tvcart-product-list-box">
                                        <span class="tvshopping-cart-qty">{l s='QTY :' d='Shop.Theme.Actions'}</span>
                                        <span class="product-qty">{$product.quantity}</span>
                                    </div>
                                    <span class="product-price">{$product.price}</span>
                                    {if $product.has_discount}
                                    <span class="regular-price">{$product.regular_price}</span>
                                    {/if}
                                    <div class="tvcart-product-remove">
                                        {$url = 'controller=cart&delete='|cat:$product.id_product}
                                        <a class="remove-from-cart tvcmsremove-from-cart" rel="nofollow" href="{$product.remove_from_cart_url}" data-link-action="delete-from-cart" data-id-product="{$product.id_product|escape:'javascript'}" data-id-product-attribute="{$product.id_product_attribute|escape:'javascript'}" data-id-customization="{$product.id_customization|escape:'javascript'}" title="{l s='remove from cart' d='Shop.Theme.Actions'}">
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
                    </div>
                    
                    {* --- SEKCJA PODSUMOWANIA (Z MODYFIKACJĄ RABATU) --- *}
                    <div class="ttvcart-price-shipping-text">
                        
                        {* 1. ZMIENNA NA KWOTĘ KODU RABATOWEGO *}
                        {assign var="voucher_amount_cached_mini" value=0}

                        {foreach from=$cart.subtotals item="subtotal"}
                            {if isset($subtotal.value) && $subtotal.type !== 'tax'}
                                
                                {* 2. UKRYWAMY DOMYŚLNY RABAT I POBIERAMY JEGO KWOTĘ *}
                                {if $subtotal.type === 'discount'}
                                    {if isset($subtotal.amount)}
                                        {assign var="voucher_amount_cached_mini" value=$subtotal.amount}
                                    {/if}
                                    {continue}
                                {/if}

                                <div class="ttvcart-product-label-value" id="tvcart-subtotal-{$subtotal.type}">
                                    <span class="ttvshoping-cart-label label{if 'products' === $subtotal.type} js-subtotal{/if}">
                                        {if 'products' == $subtotal.type}
                                            {l s='Sub Total' d='Shop.Theme.Checkout'}
                                        {else}
                                            {$subtotal.label|default:'Wysyłka'}
                                        {/if}
                                        {if $subtotal.type === 'shipping'}
                                            <small class="value">{hook h='displayCheckoutSubtotalDetails' subtotal=$subtotal}</small>
                                        {/if}
                                    </span>
                                    <span class="ttvcart-product-value">{$subtotal.value|default:''}</span>
                                </div>
                            {/if}
                        {/foreach}

                        {* 3. OBLICZANIE CAŁKOWITEGO RABATU (Produkty + Kod) *}
                        {assign var="product_savings_total_mini" value=0}
                        {foreach from=$cart.products item=product}
                            {if $product.has_discount}
                                {assign var="saving_per_unit" value=$product.regular_price_amount - $product.price_amount}
                                {assign var="saving_total" value=$saving_per_unit * $product.quantity}
                                {assign var="product_savings_total_mini" value=$product_savings_total_mini + $saving_total}
                            {/if}
                        {/foreach}
                        
                        {assign var="total_savings_combined_mini" value=$product_savings_total_mini + $voucher_amount_cached_mini}

                        {* 4. WYŚWIETLANIE RABATU (JEŚLI JEST > 0) *}
                        {if $total_savings_combined_mini > 0}
                            <div class="ttvcart-product-label-value">
                                <span class="ttvshoping-cart-label">Rabat</span>
                                <span class="ttvcart-product-value" style="color: #ea7404; font-weight: 600;">
                                    - {Tools::displayPrice($total_savings_combined_mini)}
                                </span>
                            </div>
                        {/if}

                        <div class="ttvcart-product-label-value total">
                            {* ZMIANA NAZWY NA DO ZAPŁATY *}
                            <span class="ttvshoping-cart-label">RAZEM Do zapłaty</span>
                            <span class="ttvcart-product-value">{$cart.totals.total.value}</span>
                        </div>
                        <div class="ttvcart-product-label-value tax">
                            <span class="ttvshoping-cart-label">w tym VAT</span>
                            <span class="ttvcart-product-value">{$cart.subtotals.tax.value|default:''}</span>
                        </div>
                    </div>
                    
                    <div class="ttvcart-product-list-btn-wrapper">
                        <button class="ttvcart-product-list-viewcart" style="background-color: #ffffff; border: 1px solid #333333;">
                            <a href="{$cart_url}" style="color: #333333;">
                                {l s='View Cart' d='Shop.Theme.Actions'}
                            </a>
                        </button>
                        <button class="ttvcart-product-list-checkout" style="background-color: #ef7c00; border: none;">
                            <a href="{$link->getPageLink('order', null, $language.id)}" style="color: white;">
                                {l s='CheckOut' d='Shop.Theme.Actions'}
                            </a>
                        </button>
                    </div>
                    {else}
                    <div class="ttvcart-no-product">
                        <div class="ttvcart-close-title-count tdclose-btn-wrap">
                            <button class="ttvclose-cart"></button>
                            <div class="ttvcart-top-title">
                                <h4>{l s='Shopping Cart' d='Shop.Theme.Checkout'}</h4>
                            </div>
                            <div class="ttvcart-counter">
                                <span class="ttvcart-products-count">{$cart.products_count}</span>
                            </div>
                        </div>
                    </div>
                    {/if}
                </div>
                {elseif Configuration::get('TVCMSCUSTOMSETTING_CART_VIEW') == 'pop-up'}
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
                                        <a class="remove-from-cart tvcmsremove-from-cart" rel="nofollow" href="{$product.remove_from_cart_url}" data-link-action="delete-from-cart" data-id-product="{$product.id_product|escape:'javascript'}" data-id-product-attribute="{$product.id_product_attribute|escape:'javascript'}" data-id-customization="{$product.id_customization|escape:'javascript'}" title="{l s='remove from cart' d='Shop.Theme.Actions'}">
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
                        </div>
                    </div>
                    <div class="tvcart-product-list-btn-wrapper">
                        <div class="tvcart-product-list-viewcart" style="background-color: #ffffff; border: 1px solid #333333;">
                            <a href="{$cart_url}" style="color:#333333;">{l s='View cart' d='Shop.Theme.Checkout'}</a>
                        </div>
                        <div class="tvcart-product-list-checkout" style="background-color: #ef7c00; border: none;">
                            <a href="javascript:void(0);" style="color:white;" class="tvcart-product-list-checkout-link">{l s='Proceed to checkout' d='Shop.Theme.Checkout'}</a>
                        </div>
                    </div>
                    {else}
                    <div class="tvcart-no-product">
                        <div class='tvcart-no-product-label'>{l s='Twój koszyk jest pusty' d='Shop.Theme.Checkout'}</div>
                    </div>
                    {/if}
                </div>
                {/if}
            </div>
        </div>
    </div>
{/strip}