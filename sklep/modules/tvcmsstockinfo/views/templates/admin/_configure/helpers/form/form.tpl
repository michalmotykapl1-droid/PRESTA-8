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
{extends file="helpers/form/form.tpl"}

{block name="input"}
    {if $input.type == 'design'}
        <div class="row">
            {$levels = range(0, 100, 20)}
            {$isItemsDisplayable = true}
            {$hasMixedQty = false}
            {foreach $input.values as $key => $indicatorDesign}
                {if $key != 0 && $key % 4 == 0}</div><div class="row">{/if}
                <div class="col-md-3 text-center">
                    <input type="radio" class="well-input" id="{$input.name|escape:'html':'UTF-8'}_{$indicatorDesign|escape:'html':'UTF-8'}" name="{$input.name|escape:'html':'UTF-8'}" value="{$indicatorDesign|escape:'html':'UTF-8'}"
                            {if isset($fields_value[$input.name]) && $fields_value[$input.name] == $indicatorDesign} checked{/if}
                            {if $indicatorDesign == 'none' && isset($fields_value['ADVSI_SHOW_NR_OF_ITEMS']) && ! $fields_value['ADVSI_SHOW_NR_OF_ITEMS']} disabled{/if}>
                    <label class="well" for="{$input.name|escape:'html':'UTF-8'}_{$indicatorDesign|escape:'html':'UTF-8'}">
                        {if $indicatorDesign == 'bar'}
                            <div class="h2">{l s='Progress bar' mod='tvcmsstockinfo'}</div>
                            {foreach $levels as $stockLevel => $productItems}
                                {include file='../../../../front/indicators/bar.tpl'}
                            {/foreach}
                        {elseif $indicatorDesign == 'bar.sm'}
                            <div class="h2">{l s='Progress bar' mod='tvcmsstockinfo'} (sm)</div>
                            {foreach $levels as $stockLevel => $productItems}
                                {include file='../../../../front/indicators/bar.sm.tpl'}
                            {/foreach}
                         {elseif $indicatorDesign == 'bar2'}
                            <div class="h2">{l s='Progress bar' mod='tvcmsstockinfo'} (v2)</div>
                            {foreach $levels as $stockLevel => $productItems}
                                {include file='../../../../front/indicators/bar2.tpl'}
                            {/foreach}
                        {elseif $indicatorDesign == 'signal'}
                            <div class="h2">{l s='Phone signal' mod='tvcmsstockinfo'}</div>
                            {foreach $levels as $stockLevel => $productItems}
                                {include file='../../../../front/indicators/signal.tpl'}
                            {/foreach}
                        {elseif $indicatorDesign == 'battery'}
                            <div class="h2">{l s='Phone battery' mod='tvcmsstockinfo'}</div>
                            {foreach $levels as $stockLevel => $productItems}
                                {include file='../../../../front/indicators/battery.tpl'}
                            {/foreach}
                        {elseif $indicatorDesign == 'ring'}
                            <div class="h2">{l s='Ring' mod='tvcmsstockinfo'}</div>
                            {foreach $levels as $stockLevel => $productItems}
                                {include file='../../../../front/indicators/ring.tpl'}
                            {/foreach}
                        {elseif $indicatorDesign == 'pie'}
                            <div class="h2">{l s='Pie' mod='tvcmsstockinfo'}</div>
                            {foreach $levels as $stockLevel => $productItems}
                                {include file='../../../../front/indicators/pie.tpl'}
                            {/foreach}
                        {else}
                            <div class="h2">{l s='No design' mod='tvcmsstockinfo'}</div>
                            {foreach $levels as $stockLevel => $productItems}
                                {include file='../../../../front/indicators/none.tpl'}
                            {/foreach}
                        {/if}
                    </label>
                </div>
            {/foreach}
        </div>
        <script>
            $(function(){
                $('label[for=TV_DISPLAY_NR_OF_ITEMS_off], label[for=TV_DISPLAY_NR_OF_ITEMS_on]').click(function(e){
                    if($(this).attr('for') == 'TV_DISPLAY_NR_OF_ITEMS_off') {
                        $('#TV_INDICATOR_DESIGN_none').prop('disabled', true);
                    } else {
                        $('#TV_INDICATOR_DESIGN_none').prop('disabled', false);
                    }

                    if($('#TV_INDICATOR_DESIGN_none').is(':checked')) {
                        $('#TV_INDICATOR_DESIGN_bar').prop('checked', true);
                    }
                });
            });
        </script>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
{/strip}