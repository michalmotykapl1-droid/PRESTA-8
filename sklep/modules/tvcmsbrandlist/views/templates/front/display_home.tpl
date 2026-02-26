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
{* Tytuł jest wyświetlany ZAWSZE, o ile jest skonfigurowany. *}
{if $main_heading.main_status}
    <div class="container-fluid tvcmsbrandlist-slider">
        <div class='container tvbrandlist-slider'>
            <div class='tvcmsbrandlist-slider-main-title-wrapper'>
                {if $main_heading.data.short_desc || $main_heading.data.title}
                    <div class='tvmain-title-wrapper'>
                        <div class="tvmain-title tv-diet-heading-flex">
                            
                            {* AKCENT: TOP (czerwone tło) - Używamy stałego tekstu *}
                            <div class="tvcms-diet-heading-accent">TOP</div>
                            
                            {* GŁÓWNY TYTUŁ: PRODUCENCI (czarna czcionka) - Używamy stałego tekstu *}
                            <h2 class="tvcms-brand-main-title-simple">
                                PRODUCENCI
                            </h2>
                        </div>
                    </div>
                {/if}
            </div>
            
            {* START: KARUZELA (SLIDER) *}
            {if $dis_arr_result['status']}
                <div class="tvbrandlist-slider-block">
                    <div class='tvbrandlist-slider-inner tvbrandlist-slider-content-box owl-theme owl-carousel'>
                        {foreach $dis_arr_result['data'] as $data}
                        <div class="item tvbrandlist-slider-wrapper-info wow zoomIn tvall-block-box-shadows tv-text-only-item">
                            <a href="{$data['link']|escape:'htmlall':'UTF-8'}" class="tvbrand-link-only"> 
                                <div class="tvbrand-name">
                                    {$data['title']|escape:'htmlall':'UTF-8'}
                                </div>
                            </a>
                        </div>
                        {/foreach}
                    </div>
                </div>
                
                {* Przywracamy blok nawigacyjny dla karuzeli (strzałki) *}
                <div class='tvcms-brandlist-pagination-wrapper'>
                    <div class="tvcms-brandlist-next-pre-btn">
                        <div class="tvbrandlist-slider-prev tvcmsprev-btn"><i class='material-icons'>&#xe5cb;</i></div>
                        <div class="tvbrandlist-slider-next tvcmsnext-btn"><i class='material-icons'>&#xe5cc;</i></div>
                    </div>
                </div>
            {/if}
            
        </div>
    </div>
{/if}
{/strip}