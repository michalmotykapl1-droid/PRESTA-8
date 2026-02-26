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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2025 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{strip}
<div class="tvproduct-wishlist">
    <input type="hidden" class="wishlist_prod_id" value="{$id_product|escape:'htmlall':'UTF-8'}">
    {if isset($wishlists) && !empty($wishlists) && count($wishlists) > 1}
        <div class="buttons_bottom_block no-print panel-product-line panel-product-actions" data-toggle="tvtooltip" data-placement="top" data-html="true" title="{l s='Add To Wishlist' mod='tvcmswishlist'}">
            <div id="wishlist_button">
                {foreach $wishlists as $wishlist}
                    {if $wishlist.default == '1'}
                        <a class="wishlist_button_extra" onclick="WishlistCart('wishlist_block_list', 'add', '{$id_product|intval|escape:"htmlall":"UTF-8"}', $('#idCombination').val(), 1, {$wishlist.id_wishlist}); return false;">
                            <div class="panel-product-line panel-product-actions tvproduct-wishlist-icon">
                                <i class='material-icons'>&#xe87e;</i>
                                <span>{l s='Add To Wishlist' mod='tvcmswishlist'}</span>
                            </div>
                        </a>
                    {/if}
                {/foreach}

                {* <select id="idWishlist">
                    {foreach $wishlists as $wishlist}
                        <option value="{$wishlist.id_wishlist|escape:'htmlall':'UTF-8'}">{$wishlist.name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select> *}
            </div>
        </div> 
    {else}
        <a href="#" class="tvquick-view-popup-wishlist wishlist_button" onclick="WishlistCart('wishlist_block_list', 'add', '{$id_product|intval|escape:"htmlall":"UTF-8"}', $('#idCombination').val(), 1, 1); return false;" rel="nofollow" data-toggle="tvtooltip" data-placement="top" data-html="true" title="{l s='Add To Wishlist' mod='tvcmswishlist'}">
            <div class="panel-product-line panel-product-actions tvproduct-wishlist-icon">
                <i class='material-icons'>&#xe87e;</i>
                {* <span>{l s='Add To Wishlist' mod='tvcmswishlist'}</span> *}
            </div>
        </a>
    {/if}
</div>
{/strip}