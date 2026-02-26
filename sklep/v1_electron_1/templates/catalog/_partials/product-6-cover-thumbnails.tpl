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
{block name='page_content_container'}
    <div class="page-contents product-6" id="content">
        {block name='page_content'}
        <div class="images-container">
            {block name='product_images'}
            <div class="tvvertical-slider col-sm-12">
                <div class="product-images">
                    {foreach from=$product.images item=image}
                        <div class="tvcmsVerticalSlider item">
                            <picture>
                                <source srcset="{$image.bySize.pd4_def.url}" media="(max-width: 768px)">
                                <img src="{$image.bySize.pd4_def.url}" class="{if $image.id_image == $product.cover.id_image} selected {/if}" data-image-medium-src="{$image.bySize.pd4_def.url}" data-image-large-src="{$image.bySize.large_default.url}" alt="{$product.name}" title="{$product.name}" height="{$product.default_image.bySize.pd4_def.height}" width="{$product.default_image.bySize.pd4_def.width}" itemprop="image" loading="lazy">
                            </picture>
                        </div>
                    {/foreach}
                </div>
                <div class="arrows js-arrows">
                    <i class="tvvertical-slider-next material-icons arrow-up js-arrow-up">
                        <span>keyboard_arrow_right</span>
                    </i>
                    <i class="tvvertical-slider-pre material-icons arrow-down js-arrow-down">
                        <span>keyboard_arrow_left</span>
                    </i>
                </div>
            </div>
            {/block}
        </div>
        {/block}
    </div>
{/block}
{/strip}