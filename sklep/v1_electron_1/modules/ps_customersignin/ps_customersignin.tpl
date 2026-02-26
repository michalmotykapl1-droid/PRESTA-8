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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
    <div id="tvcmsdesktop-user-info" class="tvcms-header-sign user-info tvheader-sign">
        {if $logged}
        <a class="logout tvhedaer-sign-btn" href="{$logout_url}" rel="nofollow">
            <i class="material-icons">&#xe879;</i>
            {l s='Sign out' d='Shop.Theme.Actions'}
        </a>
        {* <a class="account tvhedaer-sign-btn" href="{$my_account_url}" title="{l s='View my customer account' d='Shop.Theme.Customeraccount'}" rel="nofollow">
            <i class="material-icons hidden-md-up logged">&#xE7FF;</i>
            <span class="tvhedaer-sign-span">{$customerName}</span>
        </a> *}
        {else}
        <a href="{$my_account_url}" class="tvhedaer-sign-btn" title="{l s='Log into your customer account' d='Shop.Theme.Customeraccount'}" rel="nofollow">
            <i class="material-icons">&#xe7fd;</i>
            <span class="tvhedaer-sign-span">{l s='Sign in' d='Shop.Theme.Actions'}</span>
        </a>
        {/if}
    </div>
{/strip}