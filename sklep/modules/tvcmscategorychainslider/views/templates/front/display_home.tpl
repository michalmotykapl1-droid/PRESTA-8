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
    {if $dis_arr_result['status']}
    <div class='tvcmscategory-chain-slider container-fluid bottom-to-top hb-animate-element'>
        <div class='tvcategory-chain-slider container'>
            <div class='tvcategory-chain-slider-main-title-wrapper'>
                {include file='_partials/tvcms-main-title.tpl' main_heading=$main_heading path=$dis_arr_result['path']}
            </div>
            <div class="tvcategory-chain-slider-inner-info-box">
                <div class='tvcategory-chain-slider-content-box owl-theme owl-carousel'>
                    {foreach $dis_arr_result['data'] as $data}
                    <div class="item tvcategory-chain-slider-wrapper-info">
                        <div class="tvcategory-chain-slider-wrapper clearfix tvcategory-chain-slider-info-wrapper">
                            <div class="tvcategory-chin-img-block col-xl-12 col-lg-12 col-md-12 col-xs-12">
                                <img src="{$dis_arr_result['path']}{$data['image']}" alt="{$data['title']}" width="{$data['width']}" height="{$data['height']}" class="tv-img-responsive" loading="lazy"/>
                                <div class='tvcategory-chain-title'>
                                    <a href="{$link->getCategoryLink($data['id_category'])}" title="{$data['title']}">
                                        {$data['title']}
                                    </a>
                                </div>
                            </div>
                            <div class="tvcategory-chain-content-wrapper col-xl-12 col-lg-12 col-md-12 col-xs-12">
                                {if !empty($data.child_category)}
                                {$count = 1}
                                {$open_div = 'true'}
                                {foreach $data.child_category as $cat_info}
                                {if $count <= 6} <a href="{$link->getCategoryLink($cat_info['id_category'])}" title="{$cat_info['name']}" class="tvcategory-chain-slider-category">
                                    {$cat_info['name']}
                                    </a>
                                    {$count = $count + 1}
                                    {else}
                                    {break}
                                    {/if}
                                    {/foreach}{*
                                    <span class="tvcategory-chain-slider-all-link">
                                        <a href="{$link->getCategoryLink($data['id_category'])}" title="{$data['title']}">
                                            {l s='Show All' mod='tvcmscategorychainslider'}
                                            <i class='material-icons'>&#xe5c8;</i>
                                        </a>
                                    </span>
                                    *}{/if}
                            </div>
                        </div>
                    </div>
                    {/foreach}
                </div>
                <div class="tvcategory-chain-slider-pagination-wrapper">
                    <div class="tvcategory-chain-slider-next-pre-btn tvcms-next-pre-btn">
                        <div class="tvcategory-chain-slider-prev tvcmsprev-btn"><i class='material-icons'>&#xe314;</i></div>
                        <div class="tvcategory-chain-slider-next tvcmsnext-btn"><i class='material-icons'>&#xe315;</i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {/if}
    {/strip}