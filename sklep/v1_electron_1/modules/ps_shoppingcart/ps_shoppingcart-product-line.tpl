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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *} 
{strip}
<div class="tvcart-product-list-img">
    <a href="{$product.url}" class="tvshoping-cart-dropdown-img-block">
        <img src="{$product.cover.bySize.cart_default.url}">
    </a>
</div>

<div class="tvcart-product-content">
    <div class="tvcart-product-list-quentity">
        <div class="tvshoping-cart-dropdown-title">
            <a href="{$product.url}" class="">
                <span class="product-name">{$product.name}</span>
            </a>
        </div>

    </div>
    <div class="tvcart-product-list-price">
        <span class="product-quentity">{$product.quantity}</span>
        <span class="tvshopping-cart-quentity">X</span>
        <span class="product-price">{$product.price}</span>
    </div>
    
    <div class="tvcart-product-list-attribute">
        {foreach $product.attributes as $prod_attb=>$prod_val}
            <div class="tvcart-product-attr"><span>{$prod_attb}:</span> <span>{$prod_val}</span></div>
        {/foreach}
    </div>
     <div class="tvcart-product-remove">
            {$url = 'controller=cart&delete='|cat:$product.id_product}
            <a class="remove-from-cart tvcmsremove-from-cart"
                rel="nofollow"
                href                        = "{$product.remove_from_cart_url}"
                data-link-action            = "delete-from-cart"
                data-id-product             = "{$product.id_product|escape:'javascript'}"
                data-id-product-attribute   = "{$product.id_product_attribute|escape:'javascript'}"
                data-id-customization       = "{$product.id_customization|escape:'javascript'}"
                title="{l s='remove from cart' d='Shop.Theme.Actions'}"
            >
                <i class='material-icons'>&#xe872;</i>
            </a>
        </div>
   
    {if $product.customizations|count}
        <div class="customizations">
            <ul>
                {foreach from=$product.customizations item='customization'}
                    <li>
                        <span class="product-quantity">{$customization.quantity}</span>
                        <a href="{$customization.remove_from_cart_url}" title="{l s='remove from cart' d='Shop.Theme.Actions'}" class="remove-from-cart" rel="nofollow">{l s='Remove' d='Shop.Theme.Actions'}</a>
                        <ul>
                            {foreach from=$customization.fields item='field'}
                                <li>
                                    <span>{$field.label}</span>
                                    {if $field.type == 'text'}
                                        <span>{$field.text nofilter}</span>
                                    {else if $field.type == 'image'}
                                        <img src="{$field.image.small.url}">
                                    {/if}
                                </li>
                            {/foreach}
                        </ul>
                    </li>
                {/foreach}
            </ul>
        </div>
    {/if}
</div>
{/strip}