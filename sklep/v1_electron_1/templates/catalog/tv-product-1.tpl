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
<div class="tvprduct-image-info-wrapper clearfix row product-1" data-product-layout="1">
    {hook h='displayProductTabVideo'}
    <div class="col-md-6 tv-product-page-image">
        {block name='product_cover_thumbnails'}
            {include file='catalog/_partials/product-cover-thumbnails.tpl'}
        {/block}
    </div>
    <div class="col-md-6 tv-product-page-content">
        
        {* ========================================================== *}
        {* 1. IKONY DIET (TOP RIGHT)                                *}
        {* ========================================================== *}
        {assign var="features_for_badges" value=$product.features}
        {assign var="dietBadges_direct" value=[]}

        {assign var="featureMappings_direct" value=[
            'Dieta: Wegańska'                      => ['color' => '#8CC63F', 'letters' => 'VEGE', 'tooltip' => 'Wegańska'],
            'Dieta: Wegetariańska'                 => ['color' => '#808000', 'letters' => 'VEG',  'tooltip' => 'Wegetariańska'],
            'Dieta: Bez glutenu'                   => ['color' => '#FFA500', 'letters' => 'GF',   'tooltip' => 'Bez glutenu'],
            'Dieta: Keto / Low-Carb'               => ['color' => '#800080', 'letters' => 'KETO', 'tooltip' => 'Low-Carb / Keto'],
            'Certyfikat: BIO'                      => ['color' => '#ADFF2F', 'letters' => 'BIO',  'tooltip' => 'Certyfikat BIO'],
            'Bez: Laktozy'                         => ['color' => '#87CEEB', 'letters' => 'NL',   'tooltip' => 'Bez laktozy'],
            'Bez: Cukru'                           => ['color' => '#8B4513', 'letters' => 'NS',   'tooltip' => 'Bez cukru'],
            'Dieta: Niski Indeks Glikemiczny'      => ['color' => '#4CAF50', 'letters' => 'IG',   'tooltip' => 'Niski indeks glikemiczny']
        ]}

        {if !empty($features_for_badges)}
            {foreach from=$features_for_badges item=feature}
                {if is_array($feature) && isset($feature.name) && isset($feature.value)}
                    {assign var="featureValueLower" value=$feature.value|lower}
                    {if isset($featureMappings_direct[$feature.name]) && in_array($featureValueLower, ['tak', 'yes', '1', 'true'])}
                        {assign var="dietBadges_direct" value=$dietBadges_direct|array_merge:[ $featureMappings_direct[$feature.name] ]}
                    {/if}
                {/if}
            {/foreach}
        {/if}

        {if !empty($dietBadges_direct)}
            <div class="dietamamyto-icons-top-wrapper" style="display: flex; justify-content: flex-end; width: 100%; margin-bottom: 5px;">
                <div class="diet-badges-container">
                    {foreach from=$dietBadges_direct item=badge}
                        <span class="diet-badge" style="background-color: {$badge.color}; margin-left: 5px;" title="{$badge.tooltip|escape:'html':'UTF-8'}">
                            {$badge.letters}
                        </span>
                    {/foreach}
                </div>
            </div>
        {/if}

        {* TYTUŁ PRODUKTU (FULL WIDTH) *}
        <div class="tvproduct-title-brandimage" itemprop="itemReviewed" itemscope itemtype="http://schema.org/Thing" style="display: block !important; width: 100% !important; clear: both !important;">
            {block name='page_header_container'}
                {block name='page_header'}
                    <h1 class="h1" itemprop="name" style="width: 100% !important; display: block !important; max-width: 100% !important; margin: 0 0 5px 0 !important; padding: 0 !important;">{block name='page_title'}{$product.name}{/block}</h1>
                {/block}
            {/block}
            <div class="tvcms-product-brand-logo" style="display: block; float: right;">
                {if isset($manufacturer_image_url)}
                <a href="{$product_brand_url}" class="tvproduct-brand">
                    <img src="{$manufacturer_image_url}" alt="{$product_manufacturer->name}" title="{$product_manufacturer->name}" height="75px" width="170px" loading="lazy">
                </a>
                {/if}
            </div>
            <div style="clear: both;"></div>
        </div>

        {* Start Product Comment *}
        {hook h='displayReviewProductList' product=$product}
        {* End Product Comment *}

        {block name='product_prices'}
            {include file='catalog/_partials/product-prices.tpl'}
        {/block}

        {block name='product_availability'}
            {if $product.show_availability && $product.availability_message}
            <span id="product-availability">
                {if $product.availability == 'available'}
                    <i class="material-icons rtl-no-flip product-available">&#xE5CA;</i>
                {elseif $product.availability == 'last_remaining_items'}
                    <i class="material-icons product-last-items">&#xE002;</i>
                {else}
                    <i class="material-icons product-unavailable">&#xE14B;</i>
                {/if}
                {$product.availability_message}
            </span>
            {/if}
        {/block}

        {block name='product_description_short'}
            {if $product.description_short }
                <div id="product-description-short-{$product.id}" itemscope itemprop="description" class="tvproduct-page-decs">{$product.description_short nofilter}</div>
            {/if}
        {/block}

        {hook h='displayPricePerUnit' product=$product}
        {hook h='displayProductProWeightSelector' product=$product}
        {hook h='displayProductFlavorSelector' product=$product}

        {if !empty($product.specific_prices.from) && !empty($product.specific_prices.to) && $product.specific_prices.from != '0000-00-00 00:00:00' && $product.specific_prices.to != '0000-00-00 00:00:00'}
            {include file='catalog/_partials/miniatures/product-timer.tpl' timer=$product.specific_prices.to}
        {/if}

        <div class="product-information tvproduct-special-desc">
            {if $product.is_customizable && count($product.customizations.fields)}
                {block name='product_customization'}
                    {include file="catalog/_partials/product-customization.tpl" customizations=$product.customizations}
                {/block}
            {/if}
            <div class="product-actions">
                {block name='product_buy'}
                    <form action="{$urls.pages.cart}" method="post" id="add-to-cart-or-refresh">
                        <input type="hidden" name="token" value="{$static_token}">
                        <input type="hidden" name="id_product" value="{$product.id}" id="product_page_product_id">
                        <input type="hidden" name="id_customization" value="{$product.id_customization}" id="product_customization_id">
                        
                        {* --- BLOK WARIANTÓW --- *}
                        {block name='product_variants'}
                            {include file='catalog/_partials/product-variants.tpl'}
                        {/block}
                        
                        {block name='product_pack'}
                            {if $packItems}
                                <div class="product-pack">
                                    <p class="h4">{l s='This pack contains' d='Shop.Theme.Catalog'}</p>
                                    {foreach from=$packItems item="product_pack"}
                                        {block name='product_miniature'}
                                            {include file='catalog/_partials/miniatures/pack-product.tpl' product=$product_pack}
                                        {/block}
                                    {/foreach}
                                </div>
                            {/if}
                        {/block}

                        {block name='product_discounts'}
                            {include file='catalog/_partials/product-discounts.tpl'}
                        {/block}

                        {* --- PRZYCISK DODAJ DO KOSZYKA --- *}
                        {block name='product_add_to_cart'}
                            {include file='catalog/_partials/product-add-to-cart.tpl'}
                        {/block}

                        {* ========================================================== *}
                        {* PRZENIESIONE ELEMENTY (POD PRZYCISKIEM KOSZYKA)          *}
                        {* ========================================================== *}
                        
                        {* 1. MODUŁ LOJALNOŚCIOWY *}
                        <div style="margin-top: 15px;">
                            {hook h='displayCustomLoyalty' product=$product}
                        </div>

                        {* 2. PASKI DOSTAWY + LICZNIK WYSYŁKI *}
                        {assign var="fs_threshold" value=Configuration::get('PS_SHIPPING_FREE_PRICE')}
                        {assign var="context" value=Context::getContext()}
                        {if isset($context->currency->conversion_rate) && $context->currency->conversion_rate}
                            {assign var="fs_threshold" value=$fs_threshold * $context->currency->conversion_rate}
                        {/if}
                        
                        {assign var="cart_total" value=$cart.subtotals.products.amount}
                        {assign var="fs_missing" value=$fs_threshold - $cart_total}
                        {if $fs_threshold > 0 && $fs_missing > 0}
                             {assign var="fs_percent" value=($cart_total / $fs_threshold) * 100}
                        {else}
                            {assign var="fs_percent" value=100}
                            {assign var="fs_missing" value=0}
                        {/if}
                        
                        {assign var="current_hour" value=$smarty.now|date_format:"%H"}
                        {assign var="current_day" value=$smarty.now|date_format:"%u"}
                        {assign var="cutoff_hour" value=10}
                        {if $current_day == 1} {if $current_hour >= $cutoff_hour} {assign var="add_days" value=3} {else} {assign var="add_days" value=2} {/if}
                        {elseif $current_day == 2} {if $current_hour >= $cutoff_hour} {assign var="add_days" value=3} {else} {assign var="add_days" value=2} {/if}
                        {elseif $current_day == 3} {if $current_hour >= $cutoff_hour} {assign var="add_days" value=5} {else} {assign var="add_days" value=2} {/if}
                        {elseif $current_day == 4} {if $current_hour >= $cutoff_hour} {assign var="add_days" value=5} {else} {assign var="add_days" value=4} {/if}
                        {elseif $current_day == 5} {if $current_hour >= $cutoff_hour} {assign var="add_days" value=5} {else} {assign var="add_days" value=4} {/if}
                        {elseif $current_day == 6} {assign var="add_days" value=3}
                        {elseif $current_day == 7} {assign var="add_days" value=2}
                        {/if}
                        {assign var="delivery_ts" value=$smarty.now + ($add_days * 86400)}

                        <div class="product-info-badges">
                            {if $fs_threshold > 0}
                                <div class="fs-product-banner">
                                     <div class="fs-product-info">
                                         <i class="material-icons fs-product-icon">&#xE558;</i>
                                         {if $fs_missing > 0}
                                             <span>Pozostało Ci tylko <span class="fs-product-highlight">{$fs_missing|string_format:"%.2f"} zł</span> do darmowej dostawy!</span>
                                         {else}
                                             <span class="fs-product-highlight">Świetnie! Masz DARMOWĄ DOSTAWĘ!</span>
                                         {/if}
                                     </div>
                                     <div class="fs-product-progress-bg">
                                          <div class="fs-product-progress-fill" style="width: {$fs_percent}%;"></div>
                                     </div>
                                </div>
                            {/if}
                            
                            <div class="delivery-timer-banner">
                                <i class="material-icons dt-icon">&#xE8B5;</i>
                                <div class="dt-text">
                                    {if $current_hour < $cutoff_hour}
                                        Zamów dzisiaj do godziny <strong>{$cutoff_hour}:00</strong>, a przewidywana dostawa: <br>
                                    {else}
                                        Zamów do godziny <strong>{$cutoff_hour}:00</strong> następnego dnia, a przewidywana dostawa: <br>
                                    {/if}
                                    <strong style="color: #ea7404;">{$delivery_ts|date_format:"%A, %e %B"|replace:'Monday':'Poniedziałek'|replace:'Tuesday':'Wtorek'|replace:'Wednesday':'Środa'|replace:'Thursday':'Czwartek'|replace:'Friday':'Piątek'|replace:'Saturday':'Sobota'|replace:'Sunday':'Niedziela'|replace:'January':'stycznia'|replace:'February':'lutego'|replace:'March':'marca'|replace:'April':'kwietnia'|replace:'May':'maja'|replace:'June':'czerwca'|replace:'July':'lipca'|replace:'August':'sierpnia'|replace:'September':'września'|replace:'October':'października'|replace:'November':'listopada'|replace:'December':'grudnia'}</strong>
                                </div>
                            </div>
                        </div>

                        {hook h='displayPromoNotification' product=$product}
                        {* ========================================================== *}
                 
                        {hook h='displayCustomtab'}
                    
                        {block name='product_refresh'}{/block}
                    </form>
                {/block}
            </div>
        </div>

        {hook h='displayBBProductPro'}
        
        {* BLOK REASSURANCE - WYŁĄCZONY *}
        {* {block name='hook_display_reassurance'}{hook h='displayReassurance'}{/block} *}

    </div>
</div>

{* --- CSS & JS --- *}
<style>
    /* ======================================================== */
    /* CAŁKOWITE WYŁĄCZENIE POWIĘKSZANIA ZDJĘĆ (MODAL/ZOOM)     */
    /* ======================================================== */
    .tv-product-page-image .layer,
    .product-cover .layer,
    div[data-toggle="modal"] {
        display: none !important;
        pointer-events: none !important;
    }
    
    .tv-product-page-image img,
    .product-cover img,
    .tvproduct-image-slider img {
        cursor: default !important;
    }

    /* ======================================================== */
    /* RESZTA STYLI (Desktop, Mobile, Layout)                   */
    /* ======================================================== */
    .dietamamyto-icons-top-wrapper {
        display: flex; 
        justify-content: flex-end; 
        width: 100%;
        margin-bottom: 5px;
    }
    .tvproduct-title-brandimage {
        display: block !important; 
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
    }
    .tvproduct-title-brandimage h1.h1 {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important; 
        float: none !important;
        clear: both !important;
        margin-right: 0 !important;
    }
    .tvcms-product-brand-logo {
        display: block !important;
        float: right !important; 
        width: auto !important;
        margin-top: 5px !important;
    }

    .product-info-badges { 
        margin: 15px 0;
        background: #fff;
        border: 1px solid #f1f1f1; 
        border-radius: 6px; 
    }
    .fs-product-banner { padding: 10px 15px;
        border-bottom: 1px solid #f9f9f9; text-align: center; }
    .fs-product-info { font-size: 13px; color: #444; margin-bottom: 5px; display: flex;
        align-items: center; justify-content: center; gap: 8px; }
    .fs-product-icon { color: #ea7404; font-size: 18px; }
    .fs-product-highlight { color: #ea7404; font-weight: 700; }
    .fs-product-progress-bg { background: #eee; height: 5px;
        width: 100%; border-radius: 3px; overflow: hidden; }
    .fs-product-progress-fill { background: #ea7404; height: 100%; border-radius: 3px;
        transition: width 0.5s ease; }
    
    .delivery-timer-banner { padding: 10px 15px;
        background: #fdfdfd; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #555; }
    .dt-icon { font-size: 20px; color: #ea7404; }
    .dt-text strong { color: #333; }
    
    .product-price .current-price .price, .product-price .current-price { color: #ef7c00 !important; }

    /* Sticky Header Cleanup */
    .tvfooter-product-sticky-bottom { padding-top: 15px !important; padding-bottom: 15px !important; height: auto !important; }
    .tvfooter-product-sticky-bottom .bb-promo-container-v2,
    .tvfooter-product-sticky-bottom .product-info-badges,
    .tvfooter-product-sticky-bottom .loyalty-box,
    .tvfooter-product-sticky-bottom #loyalty_product { display: none !important; }

    .tvfooter-product-sticky-bottom .tvflex-items { display: flex !important; justify-content: space-between !important; align-items: center !important; width: 100% !important; padding: 0 10px !important; }
    .tvfooter-product-sticky-bottom .tvproduct-image-title-price { display: flex !important; align-items: center !important; gap: 15px !important; padding: 5px 0 !important; flex: 1 1 auto !important; justify-content: flex-start !important; margin-right: 20px !important; }
    .tvfooter-product-sticky-bottom .product-actions { display: flex !important; align-items: center !important; justify-content: flex-end !important; gap: 15px !important; margin: 0 !important; flex: 0 0 auto !important; }
    .tvfooter-product-sticky-bottom h1.h1 { font-size: 16px !important; line-height: 1.2 !important; margin: 0 0 4px 0 !important; padding: 0 !important; font-weight: 700 !important; color: #222 !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; max-width: 650px !important; text-transform: none !important; }
    .tvfooter-product-sticky-bottom .product-image { display: block !important; flex: 0 0 auto !important; }
    .tvfooter-product-sticky-bottom .product-image img { height: 50px !important; width: auto !important; border-radius: 4px !important; border: 1px solid #e0e0e0 !important; padding: 2px !important; background: #fff !important; object-fit: contain !important; }
    .tvfooter-product-sticky-bottom .tvtitle-price { display: flex !important; flex-direction: column !important; justify-content: center !important; align-items: flex-start !important; max-width: 100% !important; }
    .tvfooter-product-sticky-bottom .product-price { display: flex !important; align-items: center !important; flex-wrap: nowrap !important; gap: 8px !important; margin: 0 !important; }
    .tvfooter-product-sticky-bottom .current-price, .tvfooter-product-sticky-bottom .current-price .price { font-size: 16px !important; font-weight: 700 !important; color: #ea7404 !important; margin: 0 !important; }
    .tvfooter-product-sticky-bottom .regular-price { font-size: 11px !important; color: #999 !important; text-decoration: line-through !important; font-weight: 400 !important; margin: 0 !important; }
    .tvfooter-product-sticky-bottom .discount-percentage, .tvfooter-product-sticky-bottom .discount-amount { font-size: 10px !important; padding: 2px 5px !important; background: #fff0e6 !important; color: #ea7404 !important; border: 1px solid #ea7404 !important; border-radius: 10px !important; font-weight: 700 !important; margin: 0 !important; }
    .tvfooter-product-sticky-bottom .tax-shipping-delivery-label { display: none !important; }
    .tvfooter-product-sticky-bottom .product-quantities { display: flex !important; align-items: center !important; margin-right: 15px !important; margin-bottom: 0 !important; }
    .tvfooter-product-sticky-bottom .control-label { display: block !important; margin-bottom: 0 !important; margin-right: 10px !important; padding-top: 0 !important; font-weight: 600 !important; }
    .tvfooter-product-sticky-bottom .qty .input-group { display: flex !important; align-items: center !important; width: auto !important; }
    .tvfooter-product-sticky-bottom #quantity_wanted { height: 40px !important; width: 45px !important; text-align: center !important; border: 1px solid #dcdcdc !important; border-radius: 4px !important; margin-right: 5px !important; background: #fff !important; color: #333 !important; font-weight: 600 !important; }
    
    /* POPRAWKA KOLEJNOŚCI PRZYCISKÓW W STICKY FOOTERZE */
    .tvfooter-product-sticky-bottom .input-group-btn-vertical { 
        display: flex !important; 
        flex-direction: row-reverse !important; /* Zamiana kolejności: Minus, potem Plus */
        width: auto !important; 
        gap: 5px !important; 
    }
    
    .tvfooter-product-sticky-bottom .input-group-btn-vertical .btn { display: flex !important; align-items: center !important; justify-content: center !important; width: 35px !important; height: 40px !important; background: #fff !important; border: 1px solid #dcdcdc !important; border-radius: 4px !important; padding: 0 !important; margin: 0 !important; }
    .tvfooter-product-sticky-bottom .input-group-btn-vertical .btn:hover { background: #f9f9f9 !important; border-color: #bbb !important; }

    @media (max-width: 767px) {
        .product-prices, .product-price, .current-price, .tax-shipping-delivery-label,
        .tv-product-page-content .product-prices {
            display: flex !important; flex-direction: column !important; align-items: flex-end !important;
            text-align: right !important; width: 100% !important; justify-content: flex-end !important;
        }
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var $ = jQuery; 
});
</script>
{/strip}