{**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also avaitvle through the world-wide-web at this URL:
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
{if is_array($menus) && count($menus) > 0 }
    <div class="container_tv_megamenu">
        <div id="tv-menu-horizontal" class="tv-menu-horizontal clearfix">
            {$id_lang = Context::getContext()->language->id}
            <ul class="menu-content">
                <li class="tvmega-menu-title">
                    {l s='BIGBIO.PL' mod='tvcmsmegamenu'}
                </li>
                {foreach from=$menus item=menu name=menus}
                {if isset($menu.type) && $menu.type == 'CAT' && $menu.dropdown == 1}
                {$menu.sub_menu|escape:'quotes':'UTF-8' nofilter}
                {else}
                <li class="level-1 {$menu.class|escape:'html':'UTF-8'}{if count($menu.sub_menu) > 0} parent{/if}">
                    <a href="{$menu.link|escape:'html':'UTF-8'}">
                        {if $menu.type_icon == 0 && $menu.icon != ''}
                        <img class="img-icon" src="{$icon_path|escape:'html':'UTF-8'}{$menu.icon|escape:'html':'UTF-8'}" alt="{$menu.title|escape:'html':'UTF-8'}" {if $menu.width_icon && $menu.height_icon}width="{$menu.width_icon}" height="{$menu.height_icon}"{/if}/>
                        {elseif $menu.type_icon == 1 && $menu.icon != ''}
                        <i class="{$menu.icon|escape:'html':'UTF-8'}"></i>
                        {/if}
                        {if $menu.active_title == 1}<span>{$menu.title|escape:'html':'UTF-8'}</span>{/if}
                        {if $menu.subtitle != ''}
                        <span class="menu-subtitle" {if $menu.sub_title_stylesheet !='' }style="{$menu.sub_title_stylesheet}" {/if}> {$menu.subtitle|escape:'html':'UTF-8'}</span>{/if}</a><span class="icon-drop-mobile"></span>
                </li>
                {/if}
                {/foreach}
            </ul>
        </div>
    </div>
{/if}
{/strip}