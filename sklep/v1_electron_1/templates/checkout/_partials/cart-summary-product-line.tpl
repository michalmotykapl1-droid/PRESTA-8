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
{block name='cart_summary_product_line'}
  <div class="media-left">
    <a href="{$product.url}" title="{$product.name}">
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
  <div class="media-body">
    <div class="tvproduct-name">
      <div class="product-title">
        <a href="{$product.url}" title="{$product.name}">
          <h6>{$product.name}</h6>
        </a>
      </div>
    </div>
    <span class="product-quantity">x{$product.quantity}</span>
    <span class="product-price float-xs-right">{$product.price}</span>
    {hook h='displayProductPriceBlock' product=$product type="unit_price"}
    {foreach from=$product.attributes key="attribute" item="value"}
        <div class="product-line-info product-line-info-secondary text-muted">
            <span class="label">{$attribute}:</span>
            <span class="value">{$value}</span>
        </div>
    {/foreach}
    <br/>
  </div>
{/block}
{/strip}