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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{strip}
<div class="tv-indicator {block name='tv-class'}tv-{$indicatorDesign|escape:'html':'UTF-8'}{/block}{if $useProgressiveColors} tv-colors{/if}">
    <div class="tv-outer" data-toggle="tvtooltip" data-placement="top" data-html="true" {if isset($stockLevelStatus)}title="<div class='text-center'>{l s='Stock status' mod='tvcmsstockinfo'}: <b>{$stockLevelStatus|escape:'html':'UTF-8'}</b></div>"{/if} >
        {block name='tv-inner'}
            <div class="tv-inner tv-lvl-{$stockLevel|escape:'html':'UTF-8'}">{block name='tv-inner-content'}{/block}</div>
        {/block}
    </div>
    {block name='tv-items'}
        {if $isItemsDisplayable}
            <div class="tv-items">
                {if ! $hasMixedQty}
                    {if $productItems < 0}0{else}{$productItems|escape:'html':'UTF-8'}{/if}
                    {l s='items' mod='tvcmsstockinfo'}
                {else}
                    {l s='Mixed items' mod='tvcmsstockinfo'}
                {/if}
            </div>
        {/if}
    {/block}
</div>
{/strip}