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
<li style="list-style: none; position: relative;">
    <input style="margin: 2px 7px 0 0; float: left;" type="checkbox" {if in_array($node.id_category,$disabled_categories)}disabled="disabled"{else}{if in_array($node.id_category, $selected_categories)} checked="checked"{/if}{/if} value="{$node.id_category|escape:'html':'UTF-8'}" name="{$name|escape:'html':'UTF-8'}[]" id="ybc_input_blog_category_{$node.id_category|escape:'html':'UTF-8'}" /><label for="ybc_input_blog_category_{$node.id_category|intval}">{if isset($node.thumb_link)}{$node.thumb_link nofilter}&nbsp;{/if}{$node.title|escape:'html':'UTF-8'}</label>
    {if $node.children|@count > 0}
            <span class="category-blog-parent">click</span>
        		<ul class="children">
        		{foreach from=$node.children item=child name=categoryTreeBranch}
                     
        			{if $smarty.foreach.categoryTreeBranch.last}
        				{include file="$branche_tpl_path_input" node=$child last='true'}
        			{else}
        				{include file="$branche_tpl_path_input" node=$child last='false'}
        			{/if}
        		{/foreach}
    		</ul>
   	{/if}
    <input type="radio" name="id_category_default" class="category_default" value="{$node.id_category|intval}"{if $node.id_category==$id_category_default} checked="checked"{/if} />   
</li>
                       