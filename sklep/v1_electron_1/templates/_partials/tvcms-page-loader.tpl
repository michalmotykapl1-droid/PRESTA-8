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
{if Configuration::get('TVCMSCUSTOMSETTING_PAGE_LOADER')}
{block name='page_loading'}
<div class="tvcms-loading-overlay">
        <div class="tvcms-loading-inner">
            <img class="logo img-responsive" src="{$shop.logo}" alt="{$shop.name}"  height="51" width="205"/>
            <img src="{$urls.img_url}themevolty/tv_loading.gif" alt="" height="101" width="114"/>
      		</div>
        </div>
{/block}
{/if}
{/strip}