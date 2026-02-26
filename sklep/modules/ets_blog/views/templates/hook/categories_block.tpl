{*
 * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
*}
{if $blockCategTree}
    {if isset($link_view_all)}
    <div class="block ets_block_categories {$ETS_BLOG_RTL_CLASS|escape:'html':'UTF-8'}">
        <h4 class="title_blog title_block">{l s='Blog categories' mod='ets_blog'}</h4>    
        <div class="content_block block_content">
    {/if}
            <ul class="tree">
                {foreach from=$blockCategTree[0].children item=child name=blockCategTree}
        			{if $smarty.foreach.blockCategTree.last}
        				{include file="$branche_tpl_path" node=$child last='true'}
        			{else}
        				{include file="$branche_tpl_path" node=$child}
        			{/if}
        		{/foreach}
            </ul>
    {if isset($link_view_all)}
            <div class="blog_view_all_button">
                <a class="blog_view_all" href="{$link_view_all|escape:'html':'UTF-8'}">{l s='View all categories' mod='ets_blog'}</a>
            </div>
        </div>    
    </div>
    {/if}
{/if}