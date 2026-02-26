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
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2025 PrestaShop SA
    * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    {strip}
    {if isset($product_list) && !empty($product_list)}
    <div class='tvtabcategory-all-product-content-box'>
        <div class='tvtabcategory-all-product-slider owl-theme owl-carousel'>
            {foreach $product_list as $product}
            {include file="catalog/_partials/miniatures/category-product-slider.tpl" product=$product type="category_product_slider"}
            {/foreach}
        </div>
    </div>
    <div class="tvtabcategory-product-pagination">
        <div class="tvtabcategory-product-next-pre-btn">
            <div class="tvtabcategory-product-prev swiper-button-prev tvcmsprev-btn"><i class='material-icons'>&#xe314;</i></div>
            <div class="tvtabcategory-product-next swiper-button-next tvcmsnext-btn"><i class='material-icons'>&#xe315;</i></div>
        </div>
    </div>
    {else}
    <div class="tvtabcategory-not-found">
        <div>{l s='No Data Found' mod='tvcmscategoryproduct'}</div>
    </div>
    {/if}
    {/strip}