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
<div class="ets_blog_layout_{$blog_layout|escape:'html':'UTF-8'} ets-blog-wrapper ets-blog-wrapper-blog-list{if isset($blog_config.ETS_BLOG_AUTO_LOAD) &&$blog_config.ETS_BLOG_AUTO_LOAD} loadmore{/if}">
    {if $blog_categories}
        <h2 class="page-heading product-listing">{l s='All categories' mod='ets_blog'}</h2>
        <ul class="ets-blog-list row">
            {assign var='first_post' value=true}
            {foreach from=$blog_categories item='category'}
                <li class="list_category_item">                         
                    <div class="post-wrapper">
                        {if $first_post && ($blog_layout == 'large_list' || $blog_layout == 'large_grid')}
                            {if $category.thumb}
                                <a class="ets_item_img" href="{$category.link|escape:'html':'UTF-8'}">
                                    <img title="{$category.title|escape:'html':'UTF-8'}" src="{$category.thumb|escape:'html':'UTF-8'}" alt="{$category.title|escape:'html':'UTF-8'}"  />
                                    
                                </a> 
                            {elseif $category.image}
                                <a class="ets_item_img" href="{$category.link|escape:'html':'UTF-8'}">
                                    <img title="{$category.title|escape:'html':'UTF-8'}" src="{$category.image|escape:'html':'UTF-8'}" alt="{$category.title|escape:'html':'UTF-8'}"  />
                                    
                                </a> 
                             {/if}                              
                            {assign var='first_post' value=false}
                        {else}
                            {if $category.thumb}
                                <a class="ets_item_img" href="{$category.link|escape:'html':'UTF-8'}">
                                    <img title="{$category.title|escape:'html':'UTF-8'}" src="{$category.thumb|escape:'html':'UTF-8'}" alt="{$category.title|escape:'html':'UTF-8'}"  />

                                </a> 
                            {elseif $category.image}
                                <a class="ets_item_img" href="{$category.link|escape:'html':'UTF-8'}">
                                    <img title="{$category.title|escape:'html':'UTF-8'}" src="{$category.image|escape:'html':'UTF-8'}" alt="{$category.title|escape:'html':'UTF-8'}" />
                                </a>
                            {/if}
                        {/if}
                        <div class="ets-blog-wrapper-content">
                             <div class="ets-blog-wrapper-content-main">
                                <a class="ets_title_block" href="{$category.link|escape:'html':'UTF-8'}">
                                    {$category.title|escape:'html':'UTF-8'}&nbsp;({$category.count_posts|intval})
                                </a>
                                
                                {if $category.sub_categogires}
                                    <div class="sub_category">
                                        <ul>
                                            {foreach from=$category.sub_categogires item='sub_category'}
                                                <li>
                                                    <a href="{$sub_category.link|escape:'html':'UTF-8'}">{$sub_category.title|escape:'html':'UTF-8'}</a>
                                                </li>
                                            {/foreach}
                                        </ul>
                                    </div>
                                {/if}
                                <div class="blog_description">
                                        {if $category.description}
                                            {$category.description|truncate:500:'...'|escape:'html':'UTF-8' nofilter}
                                        {/if}                                
                                </div><div class="clearfix"></div>
                                {if ( $category.count_posts > 0 )}
                                    {if ( $category.count_posts == 1 )}
                                        <a class="view_detail_link blog_view_all" href="{$category.link|escape:'html':'UTF-8'}">
                                            {l s='View %d post' sprintf=[$category.count_posts] mod='ets_blog'}
                                        </a>  
                                     {else}
                                        <a class="view_detail_link blog_view_all" href="{$category.link|escape:'html':'UTF-8'}">
                                            {l s='View %d posts' sprintf=[$category.count_posts] mod='ets_blog'}
                                        </a> 
                                     {/if}  
                                {else}
                                    <a class="view_detail_link blog_view_all" href="{$category.link|escape:'html':'UTF-8'}">
                                        {l s='View detail' mod='ets_blog'}
                                    </a>
                                {/if}
                             </div>
                        </div>
                    </div>
                </li>
            {/foreach}
        </ul>
        {if $blog_paggination}
            <div class="blog-paggination">
                {$blog_paggination nofilter}
            </div>
        {/if}
    {else}
        <p>{l s='No category found' mod='ets_blog'}</p>
    {/if}
    </div> 