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
<div class="tvcmssame-category-product container-fluid">
	<div class='tvsame-category-product-wrapper-box container'>
		<div class="tvsame-category-product-all-box">
			<div class="tvsame-category-product-content">
				<div class="tvall-block-box-shadows">
					<div class="tvsame-category-main-title-wrapper">
						<div class='tvcmsmain-title-wrapper'>
							<div class="tvcms-main-title">
								<div class='tvmain-title'>
									<h2>
										{if $products|@count == 1}
										  	{l s='%s other product in the same category:' sprintf=[$products|@count] d='Shop.Theme.Catalog'}
										{else}
										  	{l s='%s other products in the same category:' sprintf=[$products|@count] d='Shop.Theme.Catalog'}
										{/if}
									</h2>
								</div>
							</div>
						</div>
					</div>
						
					<div class="tvsame-category-product">
						<div class="products owl-theme owl-carousel tvsame-category-product-wrapper tvproduct-wrapper-content-box">
							{foreach $products as $product}
								{include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type="same_category_product"}
							{/foreach}
						</div>
					</div>
				</div>
			</div>
			
			<div class='tvsame-category-pagination-wrapper tv-pagination-wrapper'>
				<div class="tvfeature-pagination">
					<div class="tvcmssame-category-pagination">
						<div class="tvcmssame-category-next-pre-btn tvcms-next-pre-btn">
							<div class="tvcmssame-category-prev tvcmsprev-btn" data-parent="tvcmssame-category-product">
								<i class='material-icons'>&#xe314;</i>
							</div>
							<div class="tvcmssame-category-next tvcmsnext-btn" data-parent="tvcmssame-category-product">
								<i class='material-icons'>&#xe315;</i>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
{/strip}