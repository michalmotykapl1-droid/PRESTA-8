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
    {if $main_heading['main_status']}
    <div class='tvcmsmain-title-wrapper clearfix'>
        <div class="tvcms-main-title">
            {if $main_heading['main_sub_title']}
            <div class='tvmain-sub-title'>
                <h4>{$main_heading['data']['short_desc']}</h4>
            </div>
            {/if}
            {if $main_heading['main_title']}
            <div class='tvmain-title'>
                <h2>{$main_heading['data']['title']}</h2>
            </div>
            {/if}
            {if $main_heading['main_description']}
            <div class='tvmain-desc'>{$main_heading['data']['desc']}</div>
            {/if}
            {* {if $main_heading['main_image']}
            <div class=''><img src="{$path}{$main_heading['data']['image']}" /></div>
            {/if} *}
        </div>
    </div>
    {/if}
    {/strip}