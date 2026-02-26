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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{if $dis_arr_result['status'] && $GetStatusService == 1}
    <div class='tvcms-left-testimonial tvcms-all-testimonial'>
        <div class='tvtestimonial'>

        	{include file='_partials/tvcms-left-column-title.tpl' status=$main_heading.title title=$main_heading.data.title}

            <div class="tvtestimonial-slider-button-wrapper">
                <div class="tvtestimonial-slider-inner">
                    <div class='tvtestimonial-content-box owl-theme owl-carousel'>
                        {foreach $dis_arr_result['data'] as $data}
                        <div class="item tvtestimonial-wrapper-info">
                            <div class="tvtestimonial-inner-content-box">
                                
                                <div class="tvtestimonial-img-content-block">
                                    <div class="tvtestimonial-img-block">
                                        <img src='{$dis_arr_result["path"]}{$data["image"]}'  alt="{$data['title']}" class="tv-img-responsive" loading="lazy"/>
                                    </div>
                                    <div class="tvtestimonial-title-des">
                                        <div class="tvtestimonial-title"><a href='{$data["link"]}'>{$data['title']}</a></div>
                                        <div class="tvtestimonial-designation">{$data['designation']}</div>
                                    </div>   
                                </div>
                                <div class='tvtestimonial-info-box'>
                                    <div class="tvtestimonial-dec">{$data['description']}</div>                                 
                                </div>
                               
                            </div>
                        </div>  
                        {/foreach}
                    </div>
                </div>  
            
                <div class='tvcms-testimonial-pagination-wrapper'>
                    <div class="tvcms-testimonial-pagination">
                        <div class="tvcms-testimonial-next-pre-btn">
                            <div class="tvtestimonial-prev tvcmsprev-btn"><i class='material-icons'>&#xe314;</i></div>
                            <div class="tvtestimonial-next tvcmsnext-btn"><i class='material-icons'>&#xe315;</i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{else if $dis_arr_result['status'] && $GetStatusService == 0 && $page.page_name == 'index'}
<div class='tvcms-left-testimonial tvcms-all-testimonial'>
        <div class='tvtestimonial'>

            {include file='_partials/tvcms-left-column-title.tpl' status=$main_heading.title title=$main_heading.data.title}

            <div class="tvtestimonial-slider-button-wrapper">
                <div class="tvtestimonial-slider-inner">
                    <div class='tvtestimonial-content-box owl-theme owl-carousel'>
                        {foreach $dis_arr_result['data'] as $data}
                        <div class="item tvtestimonial-wrapper-info">
                            <div class="tvtestimonial-inner-content-box">
                                
                                <div class="tvtestimonial-img-content-block">
                                    <div class="tvtestimonial-img-block">
                                        <img src='{$dis_arr_result["path"]}{$data["image"]}' width="50" height="50" alt="{$data['title']}" loading="lazy"/>
                                    </div>
                                    <div class="tvtestimonial-title-des">
                                        <div class="tvtestimonial-title"><a href='{$data["link"]}'>{$data['title']}</a></div>
                                        <div class="tvtestimonial-designation">{$data['designation']}</div>
                                    </div>   
                                </div>
                                <div class='tvtestimonial-info-box'>
                                    <div class="tvtestimonial-dec">{$data['description']}</div>                                 
                                </div>
                               
                            </div>
                        </div>  
                        {/foreach}
                    </div>
                </div>  
            
                <div class='tvcms-testimonial-pagination-wrapper'>
                    <div class="tvcms-testimonial-pagination">
                        <div class="tvcms-testimonial-next-pre-btn">
                            <div class="tvtestimonial-prev tvcmsprev-btn"><i class='material-icons'>&#xe314;</i></div>
                            <div class="tvtestimonial-next tvcmsnext-btn"><i class='material-icons'>&#xe315;</i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/if}
{/strip}