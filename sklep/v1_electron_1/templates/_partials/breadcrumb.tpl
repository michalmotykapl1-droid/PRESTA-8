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
    <nav data-depth="{$breadcrumb.count}" class="breadcrumb">
        <ol itemscope itemtype="http://schema.org/BreadcrumbList">
            {block name='breadcrumb'}
            {foreach from=$breadcrumb.links item=path name=breadcrumb}
                {block name='breadcrumb_item'}
                <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                    <a itemprop="item" href="{$path.url}">
                        <span itemprop="name">{$path.title}</span>
                    </a>
                    <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
                </li>
                {/block}
            {/foreach}
            {if $page.page_name == 'module-tvcmsattrsearch-productsearch'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='Product Search' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'module-tvcmswishlist-mywishlist'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='My Wishlist' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'module-tvcmsproductcompare-productcomparelist'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='Product Compare' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'cart'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{$page.page_name}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'my-account'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='My account' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'identity'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='Identity' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'addresses'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='Address' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'history'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s= 'Order history' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'order-slip'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='Order slip' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'authentication'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='Sign in' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'order-confirmation'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='Your Order Is Confirmed' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'stores'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='Stores' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {else if $page.page_name == 'new-products'}
            <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="{$path.url}">
                    <span itemprop="name">{l s='New products' d='Shop.Theme.Action'}</span>
                </a>
                <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            </li>
            {/if}
            {/block}
        </ol>
    </nav>
{/strip}