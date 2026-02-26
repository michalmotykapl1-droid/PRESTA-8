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
<div class="featured-products clearfix mt-3">
  <h2 class="h2 products-section-title text-uppercase">
    
  </h2>
  <div class="tvcmsmain-title-wrapper clearfix">
      <div class="tvcms-main-title">
          <div class="tvmain-title">
              <h2>{l s='Best Sellers' d='Shop.Theme.Catalog'}</h2>
          </div>
      </div>
  </div>
  <div class="products">
      {foreach from=$products item="product"}
          {block name='product_miniature_item'}
              <article class="col-xs-12 col-sm-6 col-md-4 col-lg-3 {if !isset($tab_slider)}item{/if} {if !empty($double_row) && $double_row == true}tvtab-first-product{/if} product-miniature js-product-miniature {$col} tvall-product-wrapper-info-box" data-id-product="{$product.id_product}" data-id-product-attribute="{$product.id_product_attribute}" itemscope itemtype="http://schema.org/Product">
                  <div class="thumbnail-container">
                    {include file="catalog/_partials/miniatures/product-grid-view.tpl" class_name='grid' product=$product image_size='home_default'}
                  </div>
              </article>
          {/block}
      {/foreach}
  </div>
  {*<a class="all-product-link float-xs-left float-md-right h4" href="{$allBestSellers}">
    {l s='All best sellers' d='Shop.Theme.Catalog'}<i class="material-icons">&#xE315;</i>
  </a>*}
</div>
{/strip}