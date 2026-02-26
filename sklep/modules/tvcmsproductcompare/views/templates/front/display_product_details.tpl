{**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2025 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{strip}
<div class="tvcompare-wrapper product_id_{$product.id_product}">
	<div class="product_id_{$product.id_product}">
		<button class="btn btn-unstyle tvcmsproduct-compare-btn" data-product-id='{$product.id_product}' data-comp-val='{if $prod_1 == $product.id_product || $prod_2 == $product.id_product || $prod_3 == $product.id_product || $prod_4 == $product.id_product}remove{else}add{/if}' data-toggle="tvtooltip" data-placement="top" data-html="true" data-original-title="Add to Wishlist" title="{l s='Add To Compare' mod='tvcmsproductcompare'}">
				<i class='material-icons remove {if $prod_1 == $product.id_product || $prod_2 == $product.id_product || $prod_3 == $product.id_product || $prod_4 == $product.id_product}{else}hide{/if}'>&#xe15c;</i>
				<i class='material-icons add {if $prod_1 == $product.id_product || $prod_2 == $product.id_product || $prod_3 == $product.id_product || $prod_4 == $product.id_product}hide{/if}'>&#xe043;</i>
			 {* <span>{l s='Add To Compare' mod='tvcmsproductcompare'}</span> *}
		</button>
	</div>
</div>
{/strip}