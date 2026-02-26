{**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http:opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http:www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2025 PrestaShop SA
    * @license http:opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    {strip}
    <div class='tvcmsapp-logo container-fluid'>
        <div class='tvapp-logo container'>
            <div class="tvapp-logo-content-box row">
                <div class="tvapp-logo-content-wrapper col-lg-8 col-md-8 col-sm-12 col-xs-12">
                    <div class="tvapp-logo-img-content-wrapper">
                        <div class="tvapp-logo-content-inner">
                            {if $show_fields['app_title']}
                            <div class='tvdekstop-footer-all-title-wrapper'>
                                <div class='tvfooter-title'><span>{$data.link_title}</span></div>
                            </div>
                            {/if}
                            {if $show_fields['app_sub_title']}
                            <div class='tvdekstop-footer-all-sub-title-wrapper'>
                                <div class='tvfooter-subtitle'><span>{$data.link_sub_title}</span></div>
                            </div>
                            {/if}
                            {if $show_fields['app_desc']}
                            <div class='tvdekstop-footer-all-desc-wrapper tvfooter-desc'>
                                <span>{$data.link_desc}</span>
                            </div>
                            {/if}
                        </div>
                        {if $show_fields['app_main_image']}
                        <div class='tvapp-logo-image'>
                            <img src='{$path}{$data.link_image}' alt="{$data.link_title}" height="275" width="250" class="tv-img-responsive" loading="lazy"/>
                        </div>
                        {/if}
                    </div>
                </div>
                <div class="tvapp-logo-link-wrapper col-lg-4 col-md-4 col-sm-12 col-xs-12">
                    {if $show_fields['apple_app_link']}
                    <div class='tvapp-logo-wrapper tvapp-logo-apple'>
                        <a href='{$data.apple_link}' title='{l s="Apple App Link" mod="tvcmscustomsetting"}'>
                            <img src='{$path}App-logo-1.png' alt='{l s="Apple App Link" mod="tvcmscustomsetting"}' height="43" width="150" class="tv-img-responsive" loading="lazy"/>
                        </a>
                    </div>
                    {/if}
                    {if $show_fields['google_app_link']}
                    <div class='tvapp-logo-wrapper tvapp-logo-google'>
                        <a href='{$data.google_link}' title='{l s="Google App Link" mod="tvcmscustomsetting"}'>
                            <img src='{$path}App-logo-2.png' alt='{l s="Google App Link" mod="tvcmscustomsetting"}' height="43" width="150" class="tv-img-responsive" loading="lazy"/>
                        </a>
                    </div>
                    {/if}
                    {if $show_fields['microsoft_app_link']}
                    <div class='tvapp-logo-wrapper tvapp-logo-microsoft'>
                        <a href='{$data.microsoft_link}' title='{l s="Microsoft App Link" mod="tvcmscustomsetting"}'>
                            <img src='{$path}App-logo-3.png' alt='{l s="Microsoft App Link" mod="tvcmscustomsetting"}' height="43" width="150" class="tv-img-responsive" loading="lazy"/>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
    {/strip}