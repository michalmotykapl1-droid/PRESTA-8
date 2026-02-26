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
    {block name='product_miniature_item'}
    <article class="tvleft-right-product-slider">
        <div class="thumbnail-container">
            <div class="tvproduct-wrapper">
                {block name='product_thumbnail'}
                <div class="tvproduct-image col-xl-4 col-lg-3 col-md-3 col-sm-3 col-xs-3">
                    {if $product.cover}
                    <a href="{$product.url}" class="thumbnail product-thumbnail">
                        <img class="tv-img-responsive" src="{$product.cover.bySize.side_product_default.url}" alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name}{/if}" height="{$product.cover.bySize.side_product_default.height}" width="{$product.cover.bySize.side_product_default.width}" loading="lazy" />
                        {if Configuration::get('TVCMSCUSTOMSETTING_HOVER_IMG')}
                        {if isset($product.images.0.bySize.side_product_default.url) && empty($product.images.0.cover)}
                        <img class="tvproduct-hover-img tv-img-responsive" src="{$product.images.0.bySize.side_product_default.url}" alt="{$product.name}" height="{$product.images.0.bySize.side_product_default.height}" width="{$product.images.0.bySize.side_product_default.width}" loading="lazy" />
                        {elseif isset($product.images.1.bySize.side_product_default.url) && empty($product.images.1.cover)}
                        <img class="tvproduct-hover-img tv-img-responsive" src="{$product.images.1.bySize.side_product_default.url}" alt="{$product.name}" height="{$product.images.1.bySize.side_product_default.height}" width="{$product.images.1.bySize.side_product_default.width}" loading="lazy" />
                        {/if}
                        {/if}
                    </a>
                    {else}
                    <a href="{$product.url}" class="thumbnail product-thumbnail">
                        <img class="tv-img-responsive" src="{$ImgDir}{$iso_code}-default-side_product_default2x.jpg" />
                    </a>
                    {/if}
                </div>
                {/block}
                <div class="product-description col-xl-8 col-lg-9 col-md-9 col-sm-9 col-xs-9">
                    {hook h='displayReviewProductList' product=$product}
                    {block name='product_name'}
                    <div class="tvproduct-name">
                        <div class="product-title">
                            <a href="{$product.url}">
                                <h6>{$product.name}</h6>
                            </a>
                        </div>
                    </div>
                    {/block}
                    <div class="tv-product-price">
                        <div class="tvproduct-name-price-wrapper">
                            {block name='product_price_and_shipping'}
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
                            {/block}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
    {/block}
    {/strip}