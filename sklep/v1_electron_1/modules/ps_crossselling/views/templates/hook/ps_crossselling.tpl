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
<div class="tvcmscross-selling-product container-fluid">
    <div class='tvcross-selling-product-wrapper-box container'>
        <div class="tvcross-selling-product-all-box">
            <div class="tvcross-selling-product-content">
                <div class="tvall-block-box-shadows">
                    <div class="tvcross-selling-main-title-wrapper">
                        <div class='tvcmsmain-title-wrapper'>
                            <div class="tvcms-main-title">
                                <div class='tvmain-title'>
                                    <h2>{l s='Customers who bought this product also bought:' d='Shop.Theme.Catalog'}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                        
                    <div class="tvcross-selling-product">
                        <div class="products owl-theme owl-carousel tvcross-selling-product-wrapper tvproduct-wrapper-content-box">
                            {foreach $products as $product}
                                {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type="cross_selling_product" image_size='bought_default'}
                            {/foreach}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class='tvcross-selling-pagination-wrapper tv-pagination-wrapper'>
                <div class="tvfeature-pagination">
                    <div class="tvcmscross-selling-pagination">
                        <div class="tvcmscross-selling-next-pre-btn tvcms-next-pre-btn">
                            <div class="tvcmscross-selling-prev tvcmsprev-btn" data-parent="tvcmscross-selling-product">
                                <i class='material-icons'>&#xe314;</i>
                            </div>
                            <div class="tvcmscross-selling-next tvcmsnext-btn" data-parent="tvcmscross-selling-product">
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