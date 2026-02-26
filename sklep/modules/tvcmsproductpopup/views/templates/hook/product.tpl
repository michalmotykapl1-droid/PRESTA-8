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
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2025 PrestaShop SA
    * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    {strip}
    <div class="tvproduct-wrapper bottom-to-top hb-animate-element">
        <span class="tvprodpopup-close">
            <i class="material-icons">&#xe5cd;</i>
        </span>
        {assign var="image_size" value="side_product_default"}
        {block name='product_thumbnail'}
        <div class="tvproduct-image">
            {if $product.cover}
            <a href="{$product.url}" class="thumbnail product-thumbnail">
                <img src="{$product.cover.bySize[$image_size]['url']}" alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name}{/if}" class="tvproduct-defult-img tv-img-responsive" height="{$product.cover.bySize[$image_size]['height']}" width="{$product.cover.bySize[$image_size]['width']}" loading="lazy">
                {if Configuration::get('TVCMSCUSTOMSETTING_HOVER_IMG')}
                {if isset($product.images.0.bySize[$image_size]['url']) && empty($product.images.0.cover)}
                <img class="tvproduct-hover-img tv-img-responsive" src="{$product.images.0.bySize[$image_size]['url']}" alt="{$product.name}" height="{$product.images.0.bySize[$image_size]['height']}" width="{$product.images.0.bySize[$image_size]['width']}" loading="lazy">
                {elseif isset($product.images.1.bySize[$image_size]['url']) && empty($product.images.1.cover)}
                <img class="tvproduct-hover-img tv-img-responsive" src="{$product.images.1.bySize[$image_size]['url']}" alt="{$product.name}" height="{$product.images.1.bySize[$image_size]['height']}" width="{$product.images.1.bySize[$image_size]['width']}" loading="lazy">
                {/if}
                {/if}
            </a>
            {else}
            <a href="{$product.url}" class="thumbnail product-thumbnail">
                <img src="{$urls.no_picture_image.bySize[$image_size]['url']}" height="{$urls.no_picture_image.bySize[$image_size]['height']}" width="{$urls.no_picture_image.bySize[$image_size]['width']}" loading="lazy">
            </a>
            {/if}
            {block name='product_flags'}
            <ul class="product-flags tvproduct-online-new-wrapper">
                {foreach from=$product.flags item=flag}
                <li class="product-flag {$flag.type}">{$flag.label}</li>
                {/foreach}
            </ul>
            {*<ul class="product-flags tvproduct-sale-pack-wrapper">
                {foreach from=$product.flags item=flag}
                {if $flag.type == 'on-sale' || $flag.type == 'pack'}
                {/if}
                {/foreach}
            </ul>*}
            {/block}
            {* <div class='tvproduct-hover-btn'>
                {hook h='displayWishlistBtnProductList' product=$product}
                {hook h='displayProductCompareProductList' product=$product}
                <div class="highlighted-informations{if !$product.main_variants} no-variants{/if} tvproduct-quick-btn">
                    {block name='quick_view'}
                    <a class="quick-view" href="#" data-link-action="quickview" data-toggle="tvtooltip" data-placement="left" data-html="true" title="{l s='Quick View' d='Shop.Theme.Actions'}" data-original-title="{l s='Quick View' d='Shop.Theme.Actions'}">
                        <div class="tvproduct-quick-icon">
                            <i class="material-icons search">&#xE8B6;</i>
                        </div>
                        <div class="tvproduct-quick-lable">
                            {l s='Quick View' d='Shop.Theme.Actions'}
                        </div>
                    </a>
                    {/block}
                </div>
                <div class="tvproduct-cart-btn">
                    <form action="{$urls.pages.cart}" method="post">
                        <input type="hidden" name="id_product" value="{$product.id_product}">
                        <input type="hidden" name="qty" value="1">
                        {if !empty($product.is_customizable) && count($product.customizations.fields)}
                        <input type="hidden" name="id_customization" value="{$product.id_customization}" id="product_customization_id">
                        {/if}
                        <input type="hidden" name="token" value="{$static_token}">
                        <button data-button-action="add-to-cart" type="submit" class="btn add-to-cart tvproduct-add-to-cart {if !$product.add_to_cart_url}tvproduct-out-of-stock disable {else} enabled{/if}" title="{if !$product.add_to_cart_url}{l s='Out Of Stock' d='Shop.Theme.Actions'}{else}{l s='Add To Cart' d='Shop.Theme.Actions'}{/if}" {if !$product.add_to_cart_url}disabled{/if} data-toggle="tvtooltip" data-placement="left" data-html="true" data-original-title="{if !$product.add_to_cart_url}{l s='Out Of Stock' d='Shop.Theme.Actions'}{else}{l s='Add To Cart' d='Shop.Theme.Actions'}{/if}">
                            <i class='material-icons'>&#xe8cc;</i>
                            <span class="tvproduct-add-to-cart-label">
                                {if !$product.add_to_cart_url}
                                {l s='Out Of Stock' d='Shop.Theme.Actions'}
                                {else}
                                {l s='Add To Cart' d='Shop.Theme.Actions'}{/if}
                            </span>
                        </button>
                    </form>
                </div>
            </div> *}
        </div>
        {/block}
        <div class="tvproduct-info-box-wrapper">
            {* Start Product Comment *}
            
            {* End Product Comment *}
            {*<div class="tvproduct-cat-name">{$product.category_name}</div>*}
<div class="tvproduct-bought-info">
        WŁAŚNIE KTOŚ KUPIŁ:
    </div>
            <div class="tvproduct-name product-title">
                <a href="{$product.url}">
                    <h6>{$product.name}</h6>
                </a>
            </div>
            {if !empty($product.specific_prices.from) && !empty($product.specific_prices.to) && $product.specific_prices.from != '0000-00-00 00:00:00' && $product.specific_prices.to != '0000-00-00 00:00:00'}
            {include file='catalog/_partials/miniatures/product-timer.tpl' timer=$product.specific_prices.to}
            {/if}
            {block name='product_price_and_shipping'}
            
            {/block}
            {if $product.main_variants}
            <div class="tv-product-price-info-box">
                <div class='tvcmsstock-indicator-wraper'>
                    {hook h='displayProductListStockIndicator' product=$product}
                </div>
                <div class='tvproduct-color-wrapper'>
                    {if $product.main_variants}
                    {block name='product_variants'}
                    {assign var="isMore" value=4}
                    {assign var="colorCount" value=0}
                    {foreach from=$product.main_variants item=color_info}
                    {if $isMore == $colorCount && $isMore < count($product.main_variants)} <a href="javascript:void(0)" class='tvcmsmorecolor-icon' title="{l s='Product Color' d='Shop.Theme.Catalog'}">
                        {(count($product.main_variants)-4)}
                        <i class='material-icons'>&#xe145</i>
                        </a>
                        <span class="tvcmsmorecolor">
                            {/if}
                            {$colorCount = $colorCount+1}
                            <a href="{$color_info.url}" class='tvporoduct-color-box' style='{if $color_info.html_color_code != ""}background-color: {$color_info.html_color_code};{else}background-image: url({$color_info.texture});{/if}' title="{l s='Product Color' d='Shop.Theme.Catalog'}">
                            </a>
                            {/foreach}
                            {if $isMore < $colorCount} <a href="javascript:void(0)" class='tvcmslesscolor-icon'>
                                <i class='material-icons'>&#xe15b</i>
                                </a>
                        </span>
                        {/if}
                        {/block}
                        {/if}
                </div>
            </div>
            {/if}

        </div>
<div class="tv-product-price">
                {if $product.show_price}
                <div class="product-price-and-shipping">
                    <span class="price">{$product.price}</span>
                    {if $product.has_discount}
                    <span class="regular-price">{$product.regular_price}</span>
                    {/if}
                    {* {if $product.has_discount}
                    {hook h='displayProductPriceBlock' product=$product type="old_price"}
                    <span class="sr-only">{l s='Regular price' d='Shop.Theme.Catalog'}</span>
                    {if $product.discount_type === 'percentage'}
                    <span class="discount-percentage discount-product tvproduct-discount-price">
                        {$product.discount_percentage}
                        {l s=' off' d='Shop.Theme.Catalog'}
                    </span>
                    {elseif $product.discount_type === 'amount'}
                    <span class="discount-amount discount-product tvproduct-discount-price">
                        {$product.discount_amount_to_display}
                        {l s=' off' d='Shop.Theme.Catalog'}
                    </span>
                    {/if}
                    {/if}
                    {hook h='displayProductPriceBlock' product=$product type="before_price"}
                    <span class="sr-only">{l s='Price' d='Shop.Theme.Catalog'}</span> *}
                    {hook h='displayProductPriceBlock' product=$product type='unit_price'}
                    {hook h='displayProductPriceBlock' product=$product type='weight'}
                </div>
                {/if}
            </div>
    </div>

    {/strip}