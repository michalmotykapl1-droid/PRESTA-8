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
* versions in the future.
* If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2025 PrestaShop SA
    * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    {strip}
    {if $dis_arr_result['status']}
    <div class='container-fluid tvcmstabcategory-product-slider'>
        <div class='tvtabcategory-product-slider'>
            <div class='tvcmstabcategory-product-slider-main-title-wrapper'>
                {include file='_partials/tvcms-main-title.tpl' main_heading=$main_heading path=$dis_arr_result['path']}
            </div>
            {* <div class="tvtabcategory-product-pagination">
                <div class="tvtabcategory-product-next-pre-btn">
                    <div class="tvtabcategory-product-prev swiper-button-prev"><i class='material-icons'>&#xe314;</i></div>
                    <div class="tvtabcategory-product-next swiper-button-next"><i class='material-icons'>&#xe315;</i></div>
                </div>
            </div> *}
            <div class='tvtabcategory-tab-product'>
                <div class='tvtabcategory-all-tab tvtabcategory-product-desktop-view tvall-product-branner '>
                    {if $main_heading['main_product_title']}
                    <div class='tvtabcategory-tab-title'>
                        <i class='material-icons'>&#xe5c3;</i>
                        <span>{$main_heading['data']['product_title']}</span>
                    </div>
                    {/if}
                    <div class="tvtabcategory-product-sub-title-block">
                        <ul class="tvtabcategory-product-inner">
                            {foreach $dis_arr_result['data'] as $data}
                            <li class="tvtabcategory-product-li">
                                <a href="{$dis_arr_result['baseUrl']}" class="tvtabcategory-tab-sub-title" title='{$data["title"]}' data-category-id='{$data["id_category"]}' data-num-prod='{$data["num_of_prod"]}'>{$data['title']}</a>{*
                                <a class="tvtabcategory-tab-sub-title" title='{$data["lang_info"][$id_lang]["title"]}' data-category-id='{$data["id_category"]}' data-num-prod='{$data["num_of_prod"]}'>{$data['lang_info'][$id_lang]['title']}</a>
                                *}</li>
                            {/foreach}
                            <li class='tvtabcategory-show show-hide hide'>
                                <a href="{$dis_arr_result['baseUrl']}" class="tvtabcategory-tab-sub-title">{l s='Show Category' mod='tvcmscategoryproduct'}</a>
                                <i class='material-icons'>&#xe313;</i>
                            </li>
                            <li class='tvtabcategory-hide show-hide hide'>
                                <a href="{$dis_arr_result['baseUrl']}" class="tvtabcategory-tab-sub-title">{l s='Hide Category' mod='tvcmscategoryproduct'}</a>
                                <i class='material-icons'>&#xe316;</i>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class='tvtabcategory-all-product tvimage-true'>{*
                    Stat Ajax is Call *}<div class='tvtabcategory-all-product-wrapper'></div>{* End Ajax is Call
                *}</div>
            </div>
        </div>
    </div>
    {/if}
    {/strip}