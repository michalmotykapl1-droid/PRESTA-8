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
{$col = ''}
{if empty($tv_product_type)}
	{$col = 'col-xl-3 col-lg-4 col-md-6 col-sm-6 col-xs-12 tv-grid-list-wrapper-info-box'}
{/if}


{block name='product_miniature_item'}
<article class="{if !isset($tab_slider)}item{/if} {if !empty($double_row) && $double_row == true}tvtab-first-product{/if} product-miniature js-product-miniature {$col} tvall-product-wrapper-info-box" data-id-product="{$product.id_product}" data-id-product-attribute="{$product.id_product_attribute}" itemscope itemtype="http://schema.org/Product">
	<div class="thumbnail-container">
		{if !empty($image_size) && $image_size == 'bought_default'}
			{* Start Grid View *}	
			{include file='catalog/_partials/miniatures/product-grid-view.tpl' class_name='grid'  product=$product image_size='bought_default'}
			{* End Grid View *}
		{else}
			{* Start Grid View *}	
			{include file='catalog/_partials/miniatures/product-grid-view.tpl' class_name='grid'  product=$product image_size='home_default'}
			{* End Grid View *}
		{/if}

		{if empty($tv_product_type)}
			{* Start Grid-2 View *}
			{include file='catalog/_partials/miniatures/product-grid-view2.tpl' class_name='grid-2' product=$product image_size='medium_default'}
			{* End Grid-2 View *}

			{* Start List View *}
			{include file='catalog/_partials/miniatures/product-list-view.tpl' product=$product image_size='home_default'}
			{* End List View *}

			{* Start Catelog View *}
			{include file='catalog/_partials/miniatures/product-catelog-view.tpl' product=$product image_size='home_default'}
			{* End Catelog View *}
		{/if}
	</div>
</article>
{/block}
{/strip}