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
                   {l s='MENU SKLEPU' mod='tvcmsmegamenu'}
                </li>
                {foreach from=$menus item=menu name=menus}
                {* <div class="active-title"></div> *}
                {if isset($menu.type) && $menu.type == 'CAT' && $menu.dropdown == 1}
                {$menu.sub_menu|escape:'quotes':'UTF-8' nofilter}
                {else}
                <li class="level-1 {$menu.class|escape:'html':'UTF-8'}{if count($menu.sub_menu) > 0}parent{/if}">
                    <a href="{$menu.link|escape:'html':'UTF-8'}" title="{$menu.title|escape:'html':'UTF-8'}">
                        {if $menu.type_icon == 0 && $menu.icon != ''}
                        <img class="img-icon" src="{$icon_path|escape:'html':'UTF-8'}{$menu.icon|escape:'html':'UTF-8'}" alt="{$menu.title|escape:'html':'UTF-8'}" {if $menu.width_icon && $menu.height_icon}width="{$menu.width_icon}" height="{$menu.height_icon}" {/if}/>
                        {elseif $menu.type_icon==1 && $menu.icon !='' } <i class="{$menu.icon|escape:'html':'UTF-8'}"></i>
                        {/if}
                        {if $menu.active_title == 1}<span>{$menu.title|escape:'html':'UTF-8'}</span>{/if}
                        {if $menu.subtitle != ''}
                        <span class="menu-subtitle" {if $menu.sub_title_stylesheet !='' }style="{$menu.sub_title_stylesheet}" {/if}>{$menu.subtitle|escape:'html':'UTF-8'}</span>{/if}</a><span class="icon-drop-mobile"></span>
                        {if isset($menu.sub_menu) && count($menu.sub_menu) > 0}
                        <div class="tv-sub-menu menu-dropdown col-xs-12 {$menu.width_sub|escape:'html':'UTF-8'} {$menu.align_sub|escape:'html':'UTF-8'}" {if $menu.background_img}style="background-image:url({$background_path}{$menu.background_img});" {/if}> {foreach from=$menu.sub_menu item=menu_row name=menu_row}<div class="tv-menu-row row {$menu_row.class|escape:'html':'UTF-8'}">
                            {if isset($menu_row.list_col) && count($menu_row.list_col) > 0}
                            {foreach from=$menu_row.list_col item= menu_col name=menu_col}
                            <div class="tv-menu-col col-xs-12 {$menu_col.width|escape:'html':'UTF-8'} {$menu_col.class|escape:'html':'UTF-8'} {if !empty($menu.type)}{$menu.type|escape:'quotes':'UTF-8'}{/if}">
                                {if count($menu_col.list_menu_item) > 0}
                                <ul class="ul-column tv-megamenu-slider-wrapper {if $menu_col.class == 'tv-mega-menu-slider'} owl-carousel{/if}">
                                    {foreach from=$menu_col.list_menu_item item= sub_menu_item name=sub_menu_item}
                                    <li class="{if $sub_menu_item.type_link != 4}tvmega-menu-link{/if} {if $menu_col.class == 'tv-mega-menu-slider'}item{/if} menu-item{if $sub_menu_item.type_item == 1} item-header{else} item-line{/if} {if $sub_menu_item.type_link == 4}product-block{/if}

{* Dynamic products block (type 5) *}
{if isset($sub_menu_item.type_link) && $sub_menu_item.type_link == 5 && isset($sub_menu_item.dynproducts)}
  {include file="module:tvcmsmegamenu/views/templates/hook/_dynproducts.tpl" dyn=$sub_menu_item.dynproducts}
{/if}

">
                                        {if $sub_menu_item.type_link == 4}
                                        {$id_lang = Context::getContext()->language->id}
                                        {$id_lang = Context::getContext()->language->id}
                                        {foreach from = $sub_menu_item.product item=product name=product}
                                        <div class="product-container clearfix">
                                            <div class="product-image-container">
                                                <a class="product_img_link" href="{$product.link|escape:'htmlall':'UTF-8'}" title="{$product.name|escape:'htmlall':'UTF-8'}">
                                                    <img class="replace-2x img-responsive" src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'home_default')|escape:'htmlall':'UTF-8'}" alt="{if !empty($product.legend)}{$product.legend|escape:'htmlall':'UTF-8'}{else}{$product.name|escape:'htmlall':'UTF-8'}{/if}" title="{if !empty($product.legend)}{$product.legend|escape:'htmlall':'UTF-8'}{else}{$product.name|escape:'htmlall':'UTF-8'}{/if}" width="{$width}" height="{$height}" loading="lazy" />
                                                </a>{* {if isset($product.on_sale) && $product.on_sale && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE}
                                                <a class="sale-box" href="{$product.link|escape:'htmlall':'UTF-8'}">
                                                    <span class="sale-label">{l s='Sale' mod='tvcmsmegamenu'}</span>
                                                </a>
                                                {/if} *}</div>
                                            <div class="product-description">
                                                {if $product.name|truncate:25:''|escape:'htmlall':'UTF-8'}
                                                <div class="tvproduct-name product-title">
                                                    <a href="{$product.link|escape:'htmlall':'UTF-8'}" title="{$product.name|escape:'htmlall':'UTF-8'}">
                                                        <h6>{$product.name|truncate:25:''|escape:'htmlall':'UTF-8'}</h6>
                                                    </a>
                                                </div>
                                                {/if}
                                                {if (!$PS_CATALOG_MODE AND ((isset($product.show_price) && $product.show_price) || (isset($product.available_for_order) && $product.available_for_order)))}
                                                <div class="product-price-and-shipping">
                                                    {if isset($product.show_price) && $product.show_price && !isset($restricted_country_mode)}
                                                    <span class="price product-price">
                                                        {Tools::displayPrice($product.price_tax_exc)|escape:'htmlall':'UTF-8'}
                                                    </span>
                                                    {if isset($product.specific_prices) && $product.specific_prices && isset($product.specific_prices.reduction) && $product.specific_prices.reduction > 0}
                                                    <span class="regular-price product-price">
                                                        {Tools::displayPrice($product.price_without_reduction)|escape:'htmlall':'UTF-8'}
                                                    </span>
                                                    {/if}
                                                    {if isset($product.specific_prices) && $product.specific_prices && isset($product.specific_prices.reduction) && $product.specific_prices.reduction > 0}
                                                    {if $product.specific_prices.reduction_type == 'percentage'}
                                                    <span class="price-percent-reduction discount-percentage discount-product tvproduct-discount-price">-{$product.specific_prices.reduction|escape:'htmlall':'UTF-8' * 100}%</span>
                                                    {/if}
                                                    {hook h="displayProductPriceBlock" product=$product type="old_price"}
                                                    {hook h="displayProductPriceBlock" id_product=$product.id_product type="old_price"}
                                                    {/if}
                                                    {hook h="displayProductPriceBlock" product=$product type="price"}
                                                    {hook h="displayProductPriceBlock" product=$product type="unit_price"}
                                                    {/if}
                                                </div>
                                                {/if}
                                            </div>
                                        </div>
                                        {/foreach}
                                        {else if $sub_menu_item.type_link == 3}
                                        {if $sub_menu_item.title|escape:'html':'UTF-8'}
                                        <a href="{$sub_menu_item.link|escape:'html':'UTF-8'}">{$sub_menu_item.title|escape:'html':'UTF-8'}</a>
                                        {/if}
                                        <div class="html-block">
                                            {$sub_menu_item.text|escape:'quotes':'UTF-8' nofilter}
                                        </div>
                                        
{else if $sub_menu_item.type_link == 6}
                                        <div class="tv-custom-menu-tpl">
                                            {if isset($sub_menu_item.link) && $sub_menu_item.link}
                                                {include file="module:tvcmsmegamenu/views/templates/front/custom_menus/{$sub_menu_item.link}"}
                                            {/if}
                                        </div>
{else}
                                        {if $sub_menu_item.title|escape:'html':'UTF-8'}
                                        <a href="{$sub_menu_item.link|escape:'html':'UTF-8'}">
                                            {$sub_menu_item.title|escape:'html':'UTF-8' nofilter}
                                        </a>
                                        {/if}
                                        {/if}
                                    </li>
                                    {/foreach}
                                </ul>{* {if $menu_col.class == 'tv-mega-menu-slider'}
                                <div class='tvcms-mega-menu-pagination-wrapper'>
                                    <div class="tvcms-mega-menu-pagination">
                                        <div class="tvcms-mega-menu-next-pre-btn">
                                            <div class="tvmega-menu-slider-prev tvcmsprev-btn"><i class='material-icons'>&#xe5c4;</i></div>
                                            <div class="tvmega-menu-slider-next tvcmsnext-btn"><i class='material-icons'>&#xe5c8;</i></div>
                                        </div>
                                    </div>
                                </div>
                                {/if}
                                *} {/if}
                            </div>
                            {/foreach}
                            {/if}
                        </div>
                        {/foreach}
        </div>
        {/if}
        </li>
        {/if}
        {/foreach}
        </ul>
    </div>
    </div>
    {/if}
    {/strip}