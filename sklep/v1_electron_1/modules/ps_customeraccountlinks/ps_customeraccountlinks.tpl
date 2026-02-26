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
    <div id="block_myaccount_infos" class="col-xl-2 col-lg-2 col-md-12">
        <div class="tvfooter-title-wrapper" data-target="#footer_sub_menu_myaccount" data-toggle="collapse">
            <span class="tvfooter-title">{l s='Your account' d='Shop.Theme.Customeraccount'}</span>
            <span class="float-xs-right tvfooter-toggle-icon-wrapper navbar-toggler collapse-icons tvfooter-toggle-icon">
                <i class="material-icons add">&#xE313;</i>
                <i class="material-icons remove">&#xE316;</i>
            </span>
        </div>
        <ul id="footer_sub_menu_myaccount" class="collapse account-list footer_account_list">
            {foreach from=$my_account_urls item=my_account_url}
            <li>
                <a href="{$my_account_url.url}" title="{$my_account_url.title}" rel="nofollow">
                    {$my_account_url.title}
                </a>
            </li>
            {/foreach}
            {hook h='displayMyAccountBlock'}
        </ul>
    </div>
    {/strip}