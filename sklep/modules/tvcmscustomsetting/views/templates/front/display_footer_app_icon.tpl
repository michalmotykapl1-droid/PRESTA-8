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
    <div class="tvapp-logo-link-wrapper ">
        {if $show_fields['apple_app_link']}
        <div class='tvapp-logo-wrapper tvapp-logo-apple'>
            <a href='{$data.apple_link}' title='{l s="Apple App Link" mod="tvcmscustomsetting"}'>
                <img src='{$path}App-logo-1.png' alt='{l s="Apple App Link" mod="tvcmscustomsetting"}' height="31" width="110" class="tv-img-responsive">
                {* <svg aria-hidden="true" focusable="false" width="20px" height="20px" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 1024 1024">
                    <path d="M747.4 535.7c-.4-68.2 30.5-119.6 92.9-157.5c-34.9-50-87.7-77.5-157.3-82.8c-65.9-5.2-138 38.4-164.4 38.4c-27.9 0-91.7-36.6-141.9-36.6C273.1 298.8 163 379.8 163 544.6c0 48.7 8.9 99 26.7 150.8c23.8 68.2 109.6 235.3 199.1 232.6c46.8-1.1 79.9-33.2 140.8-33.2c59.1 0 89.7 33.2 141.9 33.2c90.3-1.3 167.9-153.2 190.5-221.6c-121.1-57.1-114.6-167.2-114.6-170.7zm-105.1-305c50.7-60.2 46.1-115 44.6-134.7c-44.8 2.6-96.6 30.5-126.1 64.8c-32.5 36.8-51.6 82.3-47.5 133.6c48.4 3.7 92.6-21.2 129-63.7z" fill="#222222" />
                    <rect x="0" y="0" width="1024" height="1024" fill="rgba(0, 0, 0, 0)" />
                </svg> *}
            </a>
        </div>
        {/if}
        {if $show_fields['google_app_link']}
        <div class='tvapp-logo-wrapper tvapp-logo-google'>
            <a href='{$data.google_link}' title='{l s="Google App Link" mod="tvcmscustomsetting"}'>
                <img src='{$path}App-logo-2.png' alt='{l s="Google App Link" mod="tvcmscustomsetting"}' height="31" width="110" class="tv-img-responsive">
                {* <svg aria-hidden="true" focusable="false" width="20px" height="20px" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                    <path d="M3 5.557l7.357-1.002l.004 7.097l-7.354.042L3 5.557zm7.354 6.913l.006 7.103l-7.354-1.011v-6.14l7.348.048zm.892-8.046L21.001 3v8.562l-9.755.077V4.424zm9.758 8.113l-.003 8.523l-9.755-1.378l-.014-7.161l9.772.016z" fill="#222222" />
                    <rect x="0" y="0" width="24" height="24" fill="rgba(0, 0, 0, 0)" />
                </svg> *}
            </a>
        </div>
        {/if}
        {if $show_fields['microsoft_app_link']}
        <div class='tvapp-logo-wrapper tvapp-logo-microsoft'>
            <a href='{$data.microsoft_link}' title='{l s="Microsoft App Link" mod="tvcmscustomsetting"}'>
                <img src='{$path}App-logo-3.png' alt='{l s="Microsoft App Link" mod="tvcmscustomsetting"}' height="31" width="110" class="tv-img-responsive">
                {* <svg aria-hidden="true" focusable="false" width="20px" height="20px" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 512 512">
                    <path d="M48 59.49v393a4.33 4.33 0 0 0 7.37 3.07L260 256L55.37 56.42A4.33 4.33 0 0 0 48 59.49z" fill="#222222" />
                    <path d="M345.8 174L89.22 32.64l-.16-.09c-4.42-2.4-8.62 3.58-5 7.06l201.13 192.32z" fill="#222222" />
                    <path d="M84.08 472.39c-3.64 3.48.56 9.46 5 7.06l.16-.09L345.8 338l-60.61-57.95z" fill="#222222" />
                    <path d="M449.38 231l-71.65-39.46L310.36 256l67.37 64.43L449.38 281c19.49-10.77 19.49-39.23 0-50z" fill="#222222" />
                    <rect x="0" y="0" width="512" height="512" fill="rgba(0, 0, 0, 0)" />
                </svg> *}
            </a>
        </div>
        {/if}
    </div>
    {/strip}