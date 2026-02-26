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
* @author    PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{if !empty($menus)}
<div class="tvcmsvertical-menu-wrapper-data">
<div class="tvcmsvertical-menu-wrapper">
<div id='tvcmsdesktop-vertical-menu'>
	<div class='tvcmsverticalmenu'>
	    <div id="tvverticalmenu" class="tvcmsvertical-menu">
            <div class="tvallcategories">
                <div class="tvallcategories-wrapper">
                    <div class="tvcategory-title-wrapper">
                        <div class="tvleft-right-title facet-label">
                            {* ZMIANA: Czysty tekst, bez ikony, CSS załatwi resztę *}
                            <span>TOP KATEGORIE</span>
                        </div>
                        {* Element toggle zostanie ukryty przez CSS *}
                        <div class="tvleft-right-title-toggle">
                            <i class='material-icons'>&#xe5d2;</i>
                        </div>
                    </div>
                </div>
            </div>

	        <ul class="menu-content tvverticalmenu-dropdown tv-dropdown tvleft-right-penal-all-block">
	            {foreach from=$menus item=menu name=menus}
	                {if isset($menu.type) && $menu.type == 'CAT' && $menu.dropdown == 1}
	                    {$menu.sub_menu|escape:'htmlall':'UTF-8' nofilter}
	                {else}
	                    <li class="level-1 {$menu.class|escape:'htmlall':'UTF-8'} {if count($menu.sub_menu) > 0} parent{/if}">
	                    	<div class='tv-vertical-menu-text-wrapper'>
		                        {$class = 'tvvertical-menu-all-text-block'}
		                        {if $menu.type_icon == 0 && $menu.icon != ''}
		                        <div class="tvvertical-menu-img-block">
		                            <img class="img-icon" src="{$icon_path|escape:'htmlall':'UTF-8'}{$menu.icon|escape:'htmlall':'UTF-8'}" alt="" loading="lazy"/>
		                            {$class = ''}
		                        </div>
		                        {elseif $menu.type_icon == 1 && $menu.icon != ''}
		                            <i class="icon-font {$menu.icon|escape:'htmlall':'UTF-8'}"></i>
		                            {$class = ''}
		                        {/if}
		                        <a href="{$menu.link|escape:'htmlall':'UTF-8'}" class='{$class}'>
		                           	<div class="tvvertical-menu-dropdown-icon1">{*<i class='material-icons'>&#xe3a5;</i>*}</div>
		                            <div class="tvvertical-menu-category">{$menu.title|escape:'htmlall':'UTF-8'}</div>
		                            {if $menu.subtitle != ''}
	                                	<div class="tvmenu-subtitle">{$menu.subtitle|escape:'htmlall':'UTF-8'}</div>
		                            {/if}
		                        </a>
	                            <span class="tv-vertical-menu-icon-wrapper">
	                                {if count($menu.sub_menu) > 0}
	                                	<i class="material-icons tvvertical-menu-dropdown-icon right">&#xe315;</i>
	                                	<i class='material-icons tvvertical-menu-dropdown-icon down'>&#xe5c5;</i>
	                                {/if}
	                            </span>
							</div>
		                    
	                        {if isset($menu.sub_menu) && count($menu.sub_menu) > 0}
	                            <div class="tvcmsvertical-sub-menu menu-dropdown {$menu.width_sub|escape:'htmlall':'UTF-8'}">
	                                {foreach from=$menu.sub_menu item= menu_row name=menu_row}
	                                    <div class="tvcmsvertical-menu-row row {$menu_row.class|escape:'htmlall':'UTF-8'}">
	                                        {if isset($menu_row.list_col) && count($menu_row.list_col) > 0}
	                                            {foreach from=$menu_row.list_col item=menu_col name=menu_col}
	                                                <div class="tvcmsvertical-menu-col col-xs-12 {$menu_col.width|escape:'htmlall':'UTF-8'} {$menu_col.class|escape:'htmlall':'UTF-8'}">
	                                                    {if count($menu_col.list_menu_item) > 0}
	                                                        <ul class="ul-column tv-verticalmenu-slider-wrapper {if $menu_col.class == 'tv-vertical-menu-slider'} owl-carousel{/if}">
	                                                        {$is_product = 0}
	                                                        {foreach from=$menu_col.list_menu_item item=sub_menu_item name=sub_menu_item}
	                                                            <li class="item menu-item tv-verticalmenu-slider">
	                                                                {if $sub_menu_item.type_link == 4}
	                                                                    {$id_lang = Context::getContext()->language->id}
	                                                                    {$id_lang = Context::getContext()->language->id}
	                                                                    {foreach from = $sub_menu_item.product item=product name=product}
	                                                                    <div class="product-container clearfix">
	                                                                        <div class="product-image-container">
	                                                                            <a class="product_img_link" href="{$product.link|escape:'htmlall':'UTF-8'}" title="{$product.name|escape:'htmlall':'UTF-8'}">
	                                                                                <img class="img-responsive" src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'home_default')|escape:'htmlall':'UTF-8'}" alt="{if !empty($product.legend)}{$product.legend|escape:'htmlall':'UTF-8'}{else}{$product.name|escape:'htmlall':'UTF-8'}{/if}" title="{if !empty($product.legend)}{$product.legend|escape:'htmlall':'UTF-8'}{else}{$product.name|escape:'htmlall':'UTF-8'}{/if}" width="{$width}" height="{$height}" loading="lazy"/>
	                                                                            </a>
	                                                                            {if isset($product.on_sale) && $product.on_sale && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE}
	                                                                                {* <a class="sale-box" href="{$product.link|escape:'htmlall':'UTF-8'}">
	                                                                                    <span class="sale-label">{l s='Sale' mod='tvcmsverticalmenu'}</span>
	                                                                                </a> *}
	                                                                            {/if}
	                                                                        </div>
	                                                                        <div class="product-description">
		                                                                        {if $product.name|truncate:25:''|escape:'htmlall':'UTF-8'}
		                                                                        <div class="tvproduct-name">
		                                                                        	<div class="product-title">
		                                                                            	<a  href="{$product.link|escape:'htmlall':'UTF-8'}" title="{$product.name|escape:'htmlall':'UTF-8'}" ><h6>
		                                                                                {$product.name|truncate:25:''|escape:'htmlall':'UTF-8'}
		                                                                            	</h6></a>
		                                                                            </div>
		                                                                        </div>
		                                                                        {/if}
		                                                                        {if (!$PS_CATALOG_MODE AND ((isset($product.show_price) && $product.show_price) || (isset($product.available_for_order) && $product.available_for_order)))}
		                                                                            <div class="product-price-and-shipping">
		                                                                                {if isset($product.show_price) && $product.show_price && !isset($restricted_country_mode)}
		                                                                                	<span class="price product-price">
		                                                                                      {Tools::displayPrice($product.price_tax_exc)|escape:'htmlall':'UTF-8'}
		                                                                                    </span>
																							{if isset($product.specific_prices) && $product.specific_prices && isset($product.specific_prices.reduction)  && $product.specific_prices.reduction > 0}
			                                                                                	<span class="regular-price product-price">
		                                                                                          {Tools::displayPrice($product.price_without_reduction)|escape:'htmlall':'UTF-8'}
			                                                                                    </span>
		                                                                                    {/if}
		                                                                                    {if isset($product.specific_prices) && $product.specific_prices && isset($product.specific_prices.reduction)  && $product.specific_prices.reduction > 0}

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
	                                                                    {$is_product = 1}

	                                                                    {/foreach}
	                                                                {else if $sub_menu_item.type_link == 3}
	                                                                    {if $sub_menu_item.title|escape:'htmlall':'UTF-8'}
	                                                                        <a href="{$sub_menu_item.link|escape:'htmlall':'UTF-8'}">{$sub_menu_item.title|escape:'htmlall':'UTF-8'}</a>
	                                                                    {/if}
	                                                                    {if $sub_menu_item.text|escape:'quotes':'UTF-8'}
	                                                                    <div class="htmlall-block">
	                                                                        {$sub_menu_item.text|escape:'quotes':'UTF-8'}
	                                                                    </div>
	                                                                    {/if}
	                                                                {else}
	                                                                    {if $sub_menu_item.type_item == 1} 
	                                                                        <h2>
	                                                                    {/if}
	                                                                    {if $sub_menu_item.title|escape:'htmlall':'UTF-8'}
	                                                                        <a href="{$sub_menu_item.link|escape:'htmlall':'UTF-8'}">{$sub_menu_item.title|escape:'htmlall':'UTF-8'}</a>
	                                                                    {/if}
	                                                                    {if $sub_menu_item.type_item == 1} 
	                                                                        </h2>
	                                                                    {/if}
	                                                                {/if}
	                                                            </li>
	                                                        {/foreach}

	                                                        </ul>
	                                                        {if $menu_col.class == 'tv-vertical-menu-slider'} 
	                                                        	<div class='tvcms-vertical-menu-pagination-wrapper'>
																<div class="tvcms-vertical-menu-pagination">
																	<div class="tvcms-vertical-menu-next-pre-btn">
																	  	<div class="tvvertical-menu-slider-prev tvcmsprev-btn"><i class='material-icons'>&#xe314;</i></div>
																	  	<div class="tvvertical-menu-slider-next tvcmsnext-btn"><i class='material-icons'>&#xe315;</i></div>
																	</div>
																</div>
														  	</div>
	                                                        {/if}
	                                                    {/if}
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
	                        
	              <BR><BR>
	  
	           </ul>
	    </div>
	</div>
</div>
</div>
</div>
{/if}
{/strip}