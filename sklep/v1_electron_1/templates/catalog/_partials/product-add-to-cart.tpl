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
* versions in the future.
* If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
    <div class="product-add-to-cart">
        {if !$configuration.is_catalog}
        {block name='product_quantity'}
        <div class="product-quantity">
            <span class="control-label">{l s='Quantity : ' d='Shop.Theme.Catalog'}</span>
            <div class="qty">
                 <input type="text" name="qty" id="quantity_wanted" value="{$product.quantity_wanted}" class="input-group" min="{$product.minimal_quantity}" aria-label="{l s='Quantity' d='Shop.Theme.Actions'}">
            </div>
        </div>
        <div class='tvwishlist-compare-wrapper-page add tv-product-page-add-to-cart-wrapper'>
            <div class="tvcart-btn-model">
                {* --- PRZYCISK Z NOWĄ KLASĄ --- *}
                <button class="moj-btn-naprawiony add-to-cart {if !$product.add_to_cart_url} disabled {/if}" data-button-action="add-to-cart" type="submit" {if !$product.add_to_cart_url} disabled {/if}> 
                    {if !$product.add_to_cart_url} 
                        <i class='material-icons block'>&#xe14b;</i>
                        <span>{l s='Out of stock' d='Shop.Theme.Actions'}</span>
                    {else}
                        <i class="material-icons shopping-cart">&#xE547;</i>
                        <span>{l s='Add to cart' d='Shop.Theme.Actions'}</span>
                    {/if}
                </button>
                
                {* --- STYLE PRZYCISKU (ZMODYFIKOWANE - SZERSZY) --- *}
                <style>
                    .moj-btn-naprawiony {
                        /* Układ */
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        
                        /* ZMIANA: Minimalna szerokość 260px (będzie szerszy) */
                        min-width: 260px !important;
                        /* ZMIANA: Odrobinę wyższy (12px góra/dół) */
                        padding: 12px 20px !important;
                        
                        /* Kolor: Twój pomarańcz */
                        background-color: #ef7c00 !important;
                        color: #ffffff !important;
                        
                        /* Ramka i kształt */
                        border: none !important;
                        border-radius: 5px !important;
                        cursor: pointer !important;
                        
                        /* Font - ZMIANA: minimalnie większy tekst */
                        font-weight: bold !important;
                        text-transform: uppercase !important;
                        font-size: 15px !important;
                        letter-spacing: 0.5px !important;
                        
                        /* Wyłączenie starych cieni i teł */
                        background-image: none !important;
                        box-shadow: none !important;
                        outline: none !important;
                        
                        /* Płynne przejście */
                        transition: background-color 0.3s ease !important;
                    }

                    /* EFEKT NAJECHANIA (HOVER) - Rozjaśnienie */
                    .moj-btn-naprawiony:hover {
                        background-color: #ff9a35 !important;
                        color: #ffffff !important;
                        
                        /* ZAPEWNIENIE BRAKU RUCHU */
                        transform: none !important;
                        margin-top: 0 !important;
                    }
                    
                    /* Ikona w środku */
                    .moj-btn-naprawiony i {
                        color: #ffffff !important;
                        margin-right: 10px !important; /* Troszkę większy odstęp od tekstu */
                        font-size: 22px !important;    /* Troszkę większa ikona */
                    }

                    /* Stan nieaktywny (brak towaru) */
                    .moj-btn-naprawiony.disabled {
                        background-color: #ccc !important;
                        cursor: not-allowed !important;
                        min-width: auto !important; /* Przy braku towaru może być węższy */
                    }
                    
                    /* Zabezpieczenie na bardzo małe ekrany (mobilne) */
                    @media (max-width: 400px) {
                        .moj-btn-naprawiony {
                            min-width: 100% !important; /* Na małym telefonie na całą szerokość */
                        }
                    }
                </style>
                
                {* {if $page.page_name == 'product'}
                <button type="button" class="tvall-inner-btn tvclick-model" data-toggle="modal" data-target="#exampleModalCenter">
                    <i class="tvcustom-btn"></i>
                    <span>Buy in one click</span>
                </button>
                {/if} *}
            </div>
            <div class="tvproduct-wishlist-compare">
                {hook h='displayWishlistProductPage' product=$product}
                {hook h='displayProductCompareProductPage' product=$product}
            </div>
            <div class="tvproduct-stock-social">
                {hook h='displayProductPageStockIndicator' product=$product}
                {block name='product_additional_info'}
                {include file='catalog/_partials/product-additional-info.tpl'}
                {/block}
            </div>
        </div>
        {/block}
        {/if}
    </div>
{/strip}