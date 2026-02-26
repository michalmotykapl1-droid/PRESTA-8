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
{if $posts}
    <div class="block ets_block_latest {$ETS_BLOG_RTL_CLASS|escape:'html':'UTF-8'} {if isset($page) && $page}page_{$page|escape:'html':'UTF-8'}{else}page_blog{/if} ets_block_slider">
        <h4 class="title_blog title_block">{l s='Latest posts' mod='ets_blog'}</h4>
        {assign var='product_row' value=4}
        <div class="block_content row">
            <ul class="owl-rtl {if count($posts)>1}owl-carousel{/if}">
                {foreach from=$posts item='post'}
                    <li {if $page=='home'}class="col-xs-12 col-sm-4 col-lg-{12/$product_row|intval}"{/if}> 
                        {if $post.thumb}
                            <a class="ets_item_img" href="{$post.link|escape:'html':'UTF-8'}">
                                <img src="{$post.thumb|escape:'html':'UTF-8'}" alt="{$post.title|escape:'html':'UTF-8'}" title="{$post.title|escape:'html':'UTF-8'}" />
                            </a>
                        {/if}
                        <div class="ets-blog-latest-post-content">
                            <a class="ets_title_block" href="{$post.link|escape:'html':'UTF-8'}">{$post.title|escape:'html':'UTF-8'}</a>
                            <div class="ets-blog-sidear-post-meta">
                                {if isset($post.categories) && $post.categories}
                                    <div class="ets-blog-categories">
                                        {assign var='ik' value=0}
                                        {assign var='totalCat' value=count($post.categories)}                        
                                        <div class="be-categories">
                                            <span class="be-label">{l s='Posted in' mod='ets_blog'}: </span>
                                            {foreach from=$post.categories item='cat'}
                                                {assign var='ik' value=$ik+1}                                        
                                                <a href="{$cat.link|escape:'html':'UTF-8'}">{ucfirst($cat.title)|escape:'html':'UTF-8'}</a>{if $ik < $totalCat}<span class="comma">, </span>{/if}
                                            {/foreach}
                                        </div>
                                    </div>
                                {/if}
                                <span class="post-date">{dateFormat date=$post.date_add full=0}</span>
                            </div>
                            {if $allowComments || $show_views || $allow_like} 
                                <div class="ets-blog-latest-toolbar">
                                    {if $show_views}
                                        <span class="ets-blog-latest-toolbar-views">{$post.click_number|intval} {if $post.click_number!=1}<span>{l s='views' mod='ets_blog'}</span>{else}<span>{l s='view' mod='ets_blog'}</span>{/if}</span> 
                                    {/if}   
                                    {if $allowComments && $post.comments_num>0}
                                        <span class="ets-blog-latest-toolbar-comments">{$post.comments_num|intval} {if $post.comments_num!=1}<span>
                                        {l s='comments' mod='ets_blog'}</span>{else}<span>{l s='comment' mod='ets_blog'}</span>{/if}</span> 
                                    {/if}                                 
                                    {if $allow_like}
                                        <span title="{if $post.liked}{l s='Liked' mod='ets_blog'}{else}{l s='Like this post' mod='ets_blog'}{/if}" class="ets-blog-like-span ets-blog-like-span-{$post.id_post|intval} {if $post.liked}active{/if}"  data-id-post="{$post.id_post|intval}">                        
                                            <span class="ben_{$post.id_post|intval}">{$post.likes|intval}</span>
                                            <span class="blog-post-like-text blog-post-like-text-{$post.id_post|intval}">{l s='Liked' mod='ets_blog'}</span>
                                            
                                        </span> 
                                    {/if}
                                </div>
                            {/if}                         
                            {if $post.short_description}
                                <div class="blog_description">
                                    <p>
                                        {$post.short_description|strip_tags:'UTF-8'|truncate:120:'...'|escape:'html':'UTF-8'}
                                    </p>
                                </div>
                            {elseif $post.description}
                                <div class="blog_description">
                                    <p>
                                        {$post.description|strip_tags:'UTF-8'|truncate:120:'...'|escape:'html':'UTF-8'}
                                    </p>                               
                                </div>
                            {/if}
                            <a class="read_more" href="{$post.link|escape:'html':'UTF-8'}">{if $ets_blog_text_Readmore}{$ets_blog_text_Readmore|escape:'html':'UTF-8'}{else}{l s='Read More' mod='ets_blog'}{/if}</a>
                        </div>
                    </li>
                {/foreach}
            </ul>
            {if $page!='home'}
                <div class="blog_view_all_button">
                    <a href="{$latest_link|escape:'html':'UTF-8'}" class="view_all_link">{l s='View all latest posts' mod='ets_blog'}</a>
                </div>
            {/if}
        </div>
        <div class="clear"></div>
    </div>
{/if}