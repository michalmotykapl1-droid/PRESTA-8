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
<div class="images-container">
    {block name='product_cover'}
    <div class="product-cover col-sm-9">
        <div class="tvproduct-image-slider">
            {block name='product_flags'}
            <ul class="tvproduct-flags tvproduct-online-new-wrapper">
                {foreach from=$product.flags item=flag}
                {if $flag.type == 'online-only' || $flag.type == 'new'}
                <li class="product-flag {$flag.type}">{$flag.label}</li>
                {/if}
                {/foreach}
            </ul>
            <ul class="tvproduct-flags tvproduct-sale-pack-wrapper">
                {foreach from=$product.flags item=flag}
                {if $flag.type == 'on-sale' || $flag.type == 'pack'}
                <li class="product-flag {$flag.type}">{$flag.label}</li>
                {/if}
                {/foreach}
            </ul>
            {/block}
            {if $product.cover}
            <img class="js-qv-product-cover" src="{$product.default_image.bySize.large_default.url}" alt="{$product.cover.legend}" title="{$product.cover.legend}" itemprop="image">
            <div class="layer" data-toggle="modal" data-target="#product-modal">
                <i class='material-icons'>&#xe3c2;</i>
            </div>
            {else}
            <img src="{$urls.no_picture_image.bySize.large_default.url}">
            {/if}
        </div>
    </div>
    {/block}
    {block name='product_images'}
    <div class="tvvertical-slider col-sm-3">
        <ul class="product-images">
            {foreach from=$product.images item=image}
            <li class="tvcmsVerticalSlider item">
                <img class="thumb js-thumb {if $image.id_image == $product.cover.id_image} selected {/if}" data-image-medium-src="{$image.bySize.medium_default.url}" data-image-large-src="{$image.bySize.large_default.url}" src="{$image.bySize.home_default.url}" alt="{$image.legend}" title="{$image.legend}" itemprop="image">
            </li>
            {/foreach}
        </ul>
        <div class="arrows js-arrows">
            <i class="tvvertical-slider-next material-icons arrow-up js-arrow-up">&#xE316;</i>
            <i class="tvvertical-slider-pre material-icons arrow-down js-arrow-down">&#xE313;</i>
        </div>
    </div>
    {/block}
    {hook h='displayAfterProductThumbs' product=$product}
</div>
{/strip}