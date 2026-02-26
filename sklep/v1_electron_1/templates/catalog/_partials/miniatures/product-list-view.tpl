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
<div class="tvproduct-wrapper list">
    
    {block name='product_thumbnail'}
        <div class="tvproduct-image col-sm-4">
            {if $product.cover}
                <a href="{$product.url}" class="thumbnail product-thumbnail">
                    <img src="{$product.cover.bySize[$image_size]['url']}"
                         alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name}{/if}"
                         class="tvproduct-defult-img img-fluid w-100 d-block"
                         style="object-fit: contain; height: auto;"
                         loading="lazy">
                    {if Configuration::get('TVCMSCUSTOMSETTING_HOVER_IMG')}
                    {if isset($product.images.0.bySize[$image_size]['url']) && empty($product.images.0.cover)}
                        <img class="tvproduct-hover-img img-fluid w-100 d-block"
                             src="{$product.images.0.bySize[$image_size]['url']}"
                             alt="{$product.name}"
                             style="object-fit: contain; height: auto;"
                             loading="lazy">
                    {elseif isset($product.images.1.bySize[$image_size]['url']) && empty($product.images.1.cover)}
                        {if {Configuration::get('TVCMSCUSTOMSETTING_HOVER_IMG') != '0'}}
                            <img class="tvproduct-hover-img img-fluid w-100 d-block"
                                 src="{$product.images.1.bySize[$image_size]['url']}"
                                 alt="{$product.name}"
                                 style="object-fit: contain; height: auto;"
                                 loading="lazy">
                        {/if}
                    {/if}
                    {/if}
                </a>
            {else}
                <a href="{$product.url}" class="thumbnail product-thumbnail">
                    <img src="{$urls.no_picture_image.bySize[$image_size]['url']}"
                         class="img-fluid w-100 d-block"
                         style="object-fit: contain; height: auto;"
                         loading="lazy">
                </a>
            {/if}
            
            {block name='product_flags'}
                <ul class="tvproduct-flags tvproduct-online-new-wrapper">
                    {foreach from=$product.flags item=flag}
                    {if $flag.type == 'online-only' || $flag.type == 'new'}
                        <li class="product-flag {$flag.type}">{$flag.label}</li>
                    {/if}
                    {/foreach}
                </ul>

                <ul class="tvproduct-flags tvproduct-sale-pack-wrapper">
                    {foreach from=$product.flags item=flag}
                    {if $flag.type == 'on-sale' || $flag.type == 'pack'}
                        <li class="product-flag {$flag.type}">{$flag.label}</li>
                    {/if}
                    {/foreach}
                </ul>
                {* Dodajemy flagi na podstawie kategorii *}
{if isset($product.id_category_list)}
    {assign var=catlist value=$product.id_category_list}
{else}
    {assign var=catlist value=[$product.id_category_default]}
{/if}

<div class="badge-overlay">
    {if in_array(180, $catlist)}
        <span class="badge-custom badge-shortdate">KRÓTKA DATA</span>
    {/if}
    {if in_array(45, $catlist)}
        <span class="badge-custom badge-sale">WYPRZEDAŻ</span>
    {/if}
</div>


            {/block}

        </div>
    {/block}

    <div class="tvproductdescription-button-wrapper col-sm-8">
        <div class="product-description">
            {block name='product_name'}
                <div class="tvproduct-name">
                    <div class="product-title">
                        <a href="{$product.url}"><h6>{$product.name}</h6></a>
                    </div>
                    <div class="tvproduct-cat-name">{$product.category_name}</div>
                </div>    
                {if $product.description_short }
                <div class='tv-product-desc'>
                    {$product.description_short|truncate:400|strip_tags:true}
                </div>
                {/if}
            {/block}
            {* Start Product Comment *}
                {hook h='displayReviewProductList' product=$product}
            {* End Product Comment *}
            {block name='product_price_and_shipping'}
                <div class="tv-product-price">
                    <div class="tvproduct-name-price-wrapper">
                        {if $product.show_price}
                            <div class="product-price-and-shipping">
                                <span class="price">{$product.price}</span>
                                {if $product.has_discount}
                                    <span class="regular-price">{$product.regular_price}</span>
                                {/if}
                                {if $product.has_discount}
                                    {hook h='displayProductPriceBlock' product=$product type="old_price"}
                                    <span class="sr-only">{l s='Regular price' d='Shop.Theme.Catalog'}</span>
                                    
                                     {if $product.discount_type === 'percentage'}
                                         <span class="discount-percentage discount-product tvproduct-discount-price">{$product.discount_percentage}{l s=' off' d='Shop.Theme.Catalog'}</span>
                                    {elseif $product.discount_type === 'amount'}
                                         <span class="discount-amount discount-product tvproduct-discount-price">{$product.discount_amount_to_display} {l s=' off' d='Shop.Theme.Catalog'}</span>
                                    {/if}    
                                {/if}
                                {hook h='displayProductPriceBlock' product=$product type="before_price"}
                                <span class="sr-only">{l s='Price' d='Shop.Theme.Catalog'}</span>

                                {hook h='displayProductPriceBlock' product=$product type='unit_price'}
                                {hook h='displayProductPriceBlock' product=$product type='weight'}
                            </div>
                        {/if}
                    </div>
                </div>
            {/block}

            <div class="Product-Comment-Stock clearfix">
                {* Start Product Stock Indicator *}
                {hook h='displayProductListStockIndicator' product=$product}
                {* End Product Stock Indicator *}            
            </div>

            {if Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_COLOR') == '1'}
                {block name='product_variants'}
                    {if $product.main_variants}
                        <div class="tvproduct-color">
                             {foreach from=$product.main_variants item=color_info}
                            <div class='tvproduct-color-wrapper'>
                                 <a href="{$color_info.url}">
                                    <div class="tvproduct-color-box-border">
                                         <div class='tvporoduct-color-box' style='{if $color_info.html_color_code != ""}background-color: {$color_info.html_color_code};{else}background-image: url({$color_info.texture});{/if}'></div>
                                    </div>
                                </a>
                            </div>
                             {/foreach}
                        </div>
                    {/if}
                {/block}
            {/if}

            {if !empty($product.specific_prices.from) && !empty($product.specific_prices.to) && $product.specific_prices.from != '0000-00-00 00:00:00' && $product.specific_prices.to != '0000-00-00 00:00:00'}
                     {include file='catalog/_partials/miniatures/product-timer.tpl' timer=$product.specific_prices.to}
            {/if}
            
        </div>
        <div class='tvall-product-btn-list'>
            <div class="{* highlighted-informations{if !$product.main_variants} no-variants{/if} *} tvproduct-quick-btn">
                {block name='quick_view'}
                    <a class="quick-view" href="#" data-link-action="quickview" data-toggle="tvtooltip" data-placement="top" data-html="true" title="{l s='Quick View' d='Shop.Theme.Actions'}" data-original-title="{l s='Quick View' d='Shop.Theme.Actions'}">
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
                    <button data-button-action="add-to-cart" type="submit" class="btn add-to-cart tvproduct-add-to-cart {if !$product.add_to_cart_url}tvproduct-out-of-stock disable{/if}" title="{if !$product.add_to_cart_url}{l s='Out Of Stock' d='Shop.Theme.Actions'}{else}{l s='Add To Cart' d='Shop.Theme.Actions'}{/if}" {if !$product.add_to_cart_url}disabled{/if} data-toggle="tvtooltip" data-placement="top" data-html="true" data-original-title="{if !$product.add_to_cart_url}{l s='Out Of Stock' d='Shop.Theme.Actions'}{else}{l s='Add To Cart' d='Shop.Theme.Actions'}{/if}">
                        <i class='material-icons add-cart'>&#xe8cc;</i>
                        <i class='material-icons out-of-stock hide'>&#xe611;</i>
                        <span class="tvproduct-add-to-cart-label">{if !$product.add_to_cart_url}{l s='Out Of Stock' d='Shop.Theme.Actions'}{else}{l s='Add To Cart' d='Shop.Theme.Actions'}{/if} </span>
                    </button>
                </form>
            </div>
            {hook h='displayProductCompareProductList' product=$product}
            {hook h='displayWishlistBtnProductList' product=$product}
            
        </div>
    </div>
</div>
{/strip}