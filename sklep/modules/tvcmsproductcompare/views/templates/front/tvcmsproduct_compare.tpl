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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{extends file='page.tpl'}
{block name='page_title'}
{l s='Product Compare' mod='tvcmsproductcompare'}
{/block}
{block name='page_content'}
<div class="products_block table-responsive">
    <table id="product_comparison" class="table table-bordered {if !empty($prod_1) || !empty($prod_2) || !empty($prod_3) || !empty($prod_4)}active{/if}">
        <tbody>
            <tr>
                <td class="tvcompare_extra_information">
                    <span>{l s='Features' mod='tvcmsproductcompare'}</span>
                </td>
                {if !empty($prod_1)}
                <td class="tvcmscomparison tvcms-compare-product-{$prod_1.prod->id}">
                    <div class="remove">
                        <button class="tvcmsproduct-compare-list" data-product-id='{$prod_1.prod->id}' data-comp-val='remove' title="{l s='Remove' mod='tvcmsproductcompare'}">
                            <i class='material-icons'>&#xe15d;</i>
                        </button>
                    </div>
                    <div class="thumbnail-container">
                        <a href="{$link->getProductLink($prod_1.prod->id)}" class="thumbnail product-thumbnail">
                            <img src="{$link->getImageLink($prod_1.prod->link_rewrite.$id_lang, $prod_1.all_img.0.id_image, 'home_default')}" alt="{$prod_1.prod->name.$id_lang}">
                            {if !empty($prod_1.all_img.1.id_image)}
                            <img class="tvcompare-hover-img" src="{$link->getImageLink($prod_1.prod->link_rewrite.$id_lang, $prod_1.all_img.1.id_image, 'home_default')}" alt="{$prod_1.prod->name.$id_lang}">
                            {/if}
                        </a>
                    </div>
                    <div class="product-description">
                        <div class="tvproduct-name">
                            <div class="product-title" itemprop="name">
                                <a href="{$link->getProductLink($prod_1.prod->id)}">
                                    <h6>{$prod_1.prod->name.$id_lang}</h6>
                                </a>
                            </div>
                        </div>
                        <div class="product-price-and-shipping">
                            <span itemprop="price" class="{if isset($prod_1.special_price) && !empty($prod_1.special_price)}price cancle{else}price cancle{/if}">{$prod_1.prod_price} </span>
                            {if isset($prod_1.special_price) && !empty($prod_1.special_price)}
                            <span class="regular-price">{$prod_1.special_price.discount_after_price}</span>
                            <span class="discount-percentage discount-product tvproduct-discount-price">{$prod_1.special_price.reduction}</span>
                            {/if}
                        </div>
                        <div class="product-desc">
                            {$prod_1.prod->description_short.$id_lang nofilter}
                        </div>
                        <div class="highlighted-informations no-variants">
                        </div>
                        {*<div class="tv-button-container">
                            <div class="product-add-to-cart">
                                <form action="{$urls.pages.cart}" method="post">
                                    <input type="hidden" name="id_product" value="{$prod_1.prod->id}">
                                    <input type="hidden" name="qty" value="1">
                                    <input type="hidden" name="token" value="{$static_token}">
                                    <button data-button-action="add-to-cart" class="tvall-inner-btn form-control-submit {if !$prod_1.prod->out_of_stock} disabled {/if}" data-toggle="tooltip" data-placement="top" title="{l s='Add to Cart' mod='tvcmsproductcompare'}">
                                         {if !$prod_1.prod->out_of_stock}
                                            <i class='material-icons block'>&#xe14b;</i>
                                            <span>{l s='Out of stock' d='Shop.Theme.Actions'}</span>
                                          {else}
                                            <i class="material-icons shopping-cart">&#xE547;</i>
                                            <span>{l s='Add to cart' d='Shop.Theme.Actions'}</span>
                                          {/if}
                                    </button>
                                </form>
                            </div>
                        </div>*}
                    </div>
                </td>
                {/if}

                {if !empty($prod_2)}
                <td class="tvcmscomparison tvcms-compare-product-{$prod_2.prod->id}">
                    <div class="remove">
                        <button class="tvcmsproduct-compare-list" data-product-id='{$prod_2.prod->id}' data-comp-val='remove' title="{l s='Remove' mod='tvcmsproductcompare'}">
                            <i class='material-icons'>&#xe15d;</i>
                        </button>
                    </div>
                    <div class="thumbnail-container">
                        <a href="{$link->getProductLink($prod_2.prod->id)}" class="thumbnail product-thumbnail">
                            <img src="{$link->getImageLink($prod_2.prod->link_rewrite.$id_lang, $prod_2.all_img.0.id_image, 'home_default')}" alt="{$prod_2.prod->name.$id_lang}">
                            {if !empty($prod_2.all_img.1.id_image)}
                            <img class="tvcompare-hover-img" src="{$link->getImageLink($prod_2.prod->link_rewrite.$id_lang, $prod_2.all_img.1.id_image, 'home_default')}" alt="{$prod_2.prod->name.$id_lang}">
                            {/if}
                        </a>
                    </div>
                    <div class="product-description">
                        <div class="tvproduct-name">
                            <div class="product-title" itemprop="name">
                                <a href="{$link->getProductLink($prod_2.prod->id)}">
                                    <h6>{$prod_2.prod->name.$id_lang}</h6>
                                </a>
                            </div>
                        </div>
                        <div class="product-price-and-shipping">
                            <span itemprop="price" class="{if isset($prod_2.special_price) && !empty($prod_2.special_price)}price cancle{else}price cancle{/if}">{$prod_2.prod_price} </span>
                            {if isset($prod_2.special_price) && !empty($prod_2.special_price)}
                            <span class="regular-price">{$prod_2.special_price.discount_after_price}</span>
                            <span class="discount-percentage discount-product tvproduct-discount-price">{$prod_2.special_price.reduction}</span>
                            {/if}
                        </div>
                        <div class="product-desc">
                            {$prod_2.prod->description_short.$id_lang nofilter}
                        </div>
                        <div class="highlighted-informations no-variants">
                        </div>
                    </div>
                </td>
                {/if}
                {if !empty($prod_3)}
                <td class="tvcmscomparison tvcms-compare-product-{$prod_3.prod->id}">
                    <div class="remove">
                        <button class="tvcmsproduct-compare-list" data-product-id='{$prod_3.prod->id}' data-comp-val='remove' title="{l s='Remove' mod='tvcmsproductcompare'}">
                            <i class='material-icons'>&#xe15d;</i>
                        </button>
                    </div>
                    <div class="thumbnail-container">
                        <a href="{$link->getProductLink($prod_3.prod->id)}" class="thumbnail product-thumbnail">
                            <img src="{$link->getImageLink($prod_3.prod->link_rewrite.$id_lang, $prod_3.all_img.0.id_image, 'home_default')}" alt="{$prod_3.prod->name.$id_lang}">
                            {if !empty($prod_3.all_img.1.id_image)}
                            <img class="tvcompare-hover-img" src="{$link->getImageLink($prod_3.prod->link_rewrite.$id_lang, $prod_3.all_img.1.id_image, 'home_default')}" alt="{$prod_3.prod->name.$id_lang}">
                            {/if}
                        </a>
                    </div>
                    <div class="product-description">
                        <div class="tvproduct-name">
                            <div class="product-title" itemprop="name">
                                <a href="{$link->getProductLink($prod_3.prod->id)}">
                                    <h6>{$prod_3.prod->name.$id_lang}</h6>
                                </a>
                            </div>
                        </div>
                        <div class="product-price-and-shipping">
                            <span itemprop="price" class="{if isset($prod_3.special_price) && !empty($prod_3.special_price)}price cancle{else}price cancle{/if}">{$prod_3.prod_price} </span>
                            {if isset($prod_3.special_price) && !empty($prod_3.special_price)}
                            <span class="regular-price">{$prod_3.special_price.discount_after_price}</span>
                            <span class="discount-percentage discount-product tvproduct-discount-price">{$prod_3.special_price.reduction}</span>
                            {/if}
                        </div>
                        <div class="product-desc">
                            {$prod_3.prod->description_short.$id_lang nofilter}
                        </div>
                        <div class="highlighted-informations no-variants">
                        </div>
                    </div>
                </td>
                {/if}
                {if !empty($prod_4)}
                <td class="tvcmscomparison tvcms-compare-product-{$prod_4.prod->id}">
                    <div class="remove">
                        <button class="tvcmsproduct-compare-list" data-product-id='{$prod_4.prod->id}' data-comp-val='remove' title="{l s='Remove' mod='tvcmsproductcompare'}">
                            <i class='material-icons'>&#xe15d;</i>
                        </button>
                    </div>
                    <div class="thumbnail-container">
                        <a href="{$link->getProductLink($prod_4.prod->id)}" class="thumbnail product-thumbnail">
                            <img src="{$link->getImageLink($prod_4.prod->link_rewrite.$id_lang, $prod_4.all_img.0.id_image, 'home_default')}" alt="{$prod_4.prod->name.$id_lang}">
                            {if !empty($prod_4.all_img.1.id_image)}
                            <img class="tvcompare-hover-img" src="{$link->getImageLink($prod_4.prod->link_rewrite.$id_lang, $prod_4.all_img.1.id_image, 'home_default')}" alt="{$prod_4.prod->name.$id_lang}">
                            {/if}
                        </a>
                    </div>
                    <div class="product-description">
                        <div class="tvproduct-name">
                            <div class="product-title" itemprop="name">
                                <a href="{$link->getProductLink($prod_4.prod->id)}">
                                    <h6>{$prod_4.prod->name.$id_lang}</h6>
                                </a>
                            </div>
                        </div>
                        <div class="product-price-and-shipping">
                            <span itemprop="price" class="{if isset($prod_4.special_price) && !empty($prod_4.special_price)}price cancle{else}price cancle{/if}">{$prod_4.prod_price} </span>
                            {if isset($prod_4.special_price) && !empty($prod_4.special_price)}
                            <span class="regular-price">{$prod_4.special_price.discount_after_price}</span>
                            <span class="discount-percentage discount-product tvproduct-discount-price">{$prod_4.special_price.reduction}</span>
                            {/if}
                        </div>
                        <div class="product-desc">
                            {$prod_4.prod->description_short.$id_lang nofilter}
                        </div>
                        <div class="highlighted-informations no-variants">
                        </div>
                    </div>
                </td> 
                {/if}
            </tr>
            {if !empty($all_feature)}
            {foreach $all_feature as $feature}
            <tr>
                <td class="comparison_feature_odd feature-name">
                    <strong>{$feature}</strong>
                </td>
                {if !empty($prod_1.prod_attr)}
                <td class="comparison_feature_odd comparison_infos tvcms-compare-product-{$prod_1.prod->id}">
                    {foreach $prod_1.prod_attr as $feature_1}
                    {if $feature_1.name == $feature}
                    {$feature_1.value}
                    {/if}
                    {/foreach}
                </td>
                {/if}
                {if !empty($prod_2.prod_attr)}
                <td class="comparison_feature_odd comparison_infos tvcms-compare-product-{$prod_2.prod->id}">
                    {foreach $prod_2.prod_attr as $feature_2}
                    {if $feature_2.name == $feature}
                    {$feature_2.value}
                    {/if}
                    {/foreach}
                </td>
                {/if}
                {if !empty($prod_3.prod_attr)}
                <td class="comparison_feature_odd comparison_infos tvcms-compare-product-{$prod_3.prod->id}">
                    {foreach $prod_3.prod_attr as $feature_3}
                    {if $feature_3.name == $feature}
                    {$feature_3.value}
                    {/if}
                    {/foreach}
                </td>
                {/if}
                {if !empty($prod_4.prod_attr)}
                <td class="comparison_feature_odd comparison_infos tvcms-compare-product-{$prod_4.prod->id}">
                    {foreach $prod_4.prod_attr as $feature_4}
                    {if $feature_4.name == $feature}
                    {$feature_4.value}
                    {/if}
                    {/foreach}
                </td>
                {/if}
            </tr>
            {/foreach}
            {/if}
        </tbody>
    </table>
    <div id="no_product_comparison" class="{if empty($prod_1) && empty($prod_2) && empty($prod_3) && empty($prod_4)}active{/if}">
        <div class="tvno-compare-product-dec">{l s='There are no products selected for comparison.' mod='tvcmsproductcompare'}</div>
    </div>
</div>
{/block}
{/strip}