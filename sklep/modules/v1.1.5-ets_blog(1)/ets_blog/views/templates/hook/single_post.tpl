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
<script type="text/javascript">
    ets_blog_report_url = '{$report_url nofilter}';
    ets_blog_report_warning ="{l s='Do you want to report this comment?' mod='ets_blog'}";
    ets_blog_error = "{l s='There was a problem while submitting your report. Try again later' mod='ets_blog'}";
</script>
<div class="ets_blog_layout_{$blog_layout|escape:'html':'UTF-8'} ets-blog-wrapper-detail" itemscope itemType="http://schema.org/newsarticle">
    <div itemprop="publisher" itemtype="http://schema.org/Organization" itemscope="">
        <meta itemprop="name" content="{Configuration::get('PS_SHOP_NAME')|escape:'html':'UTF-8'}" />
        {if Configuration::get('PS_LOGO')}
            <div itemprop="logo" itemscope itemtype="http://schema.org/ImageObject">
                <meta itemprop="url" content="{$ets_blog_config.ETS_BLOG_SHOP_URI|escape:'html':'UTF-8'}img/{Configuration::get('PS_LOGO')|escape:'html':'UTF-8'}" />
                <meta itemprop="width" content="200px" />
                <meta itemprop="height" content="100px" />
            </div>
        {/if}
    </div> 
    {if $blog_post.image}
        <div class="ets_blog_img_wrapper" itemprop="image" itemscope itemtype="http://schema.org/ImageObject">
            <div class="ets_image-single">                            
                <img title="{$blog_post.title|escape:'html':'UTF-8'}" src="{$blog_post.image|escape:'html':'UTF-8'}" alt="{$blog_post.title|escape:'html':'UTF-8'}" itemprop="url"/>
            </div>
            <meta itemprop="width" content="600px" />
            <meta itemprop="height" content="300px" />
        </div>                        
     {/if}
     <div class="ets-blog-wrapper-content">
    {if $blog_post}
        <h1 class="page-heading product-listing" itemprop="mainEntityOfPage"><span  class="title_cat" itemprop="headline">{$blog_post.title|escape:'html':'UTF-8'}</span></h1>
        <div class="post-details">
            <div class="blog-extra">
                <div class="ets-blog-latest-toolbar">
                    {if $allow_rating && $everage_rating}                      
                        <div class="blog_rating_wrapper">                            
                            {if $total_review}
                                <span title="{l s='Comments' mod='ets_blog'}" class="blog_rating_reviews">
                                     <span class="total_views">{$total_review|intval}</span>
                                     <span>
                                        {if $total_review != 1}
                                            {l s='Comments' mod='ets_blog'}
                                        {else}
                                            {l s='Comment' mod='ets_blog'}
                                        {/if}
                                    </span>
                                </span>
                            {/if}
                            <div title="{l s='Average rating' mod='ets_blog'}" class="ets_blog_review">
                                {for $i = 1 to $everage_rating}
                                    {if $i <= $everage_rating}
                                        <div class="star star_on"></div>
                                    {else}
                                        <div class="star star_on_{($i-$everage_rating)*10|intval}"></div>
                                    {/if}
                                {/for}
                                {if Ceil($everage_rating)<5}
                                    {for $i = ceil($everage_rating)+1 to 5}
                                        <div class="star"></div>
                                    {/for}
                                {/if}
                                <span class="ets-blog-rating-value">({$everage_rating|escape:'html':'UTF-8'})</span>                                               
                            </div>
                        </div>
                    {/if}  
                    {if $show_date}
                        {if !$date_format}{assign var='date_format' value='F jS Y'}{/if}
                        <span class="post-date">
                            <span class="be-label">{l s='Posted on' mod='ets_blog'}: </span>
                            <span>{date($date_format,strtotime($blog_post.date_add))|escape:'html':'UTF-8'}</span>
                            <meta itemprop="datePublished" content="{date('Y-m-d',strtotime($blog_post.date_add))|escape:'html':'UTF-8'}" />
                            <meta itemprop="dateModified" content="{date('Y-m-d',strtotime($blog_post.date_upd))|escape:'html':'UTF-8'}" />
                        </span>
                    {/if}
                    {if $show_author && isset($blog_post.employee) &&  $blog_post.employee}
                        <div class="author-block" itemprop="author" itemscope itemtype="http://schema.org/Person">
                            <span class="post-author-label">{l s='By ' mod='ets_blog'}</span>
                            <a itemprop="url" href="{$blog_post.author_link|escape:'html':'UTF-8'}">
                                <span class="post-author-name" itemprop="name">
                                    {if isset($blog_post.employee.name) && $blog_post.employee.name}
                                        {ucfirst($blog_post.employee.name)|escape:'html':'UTF-8'}
                                    {else}
                                        {ucfirst($blog_post.employee.firstname)|escape:'html':'UTF-8'} {ucfirst($blog_post.employee.lastname)|escape:'html':'UTF-8'}
                                    {/if}
                                </span>
                            </a>
                        </div>
                    {/if}
                    {if isset($blog_post.link_edit) && $blog_post.link_edit}
                        <a class="ets-block-post-edit" href="{$blog_post.link_edit|escape:'html':'UTF-8'}" title="{l s='Edit' mod='ets_blog'}"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;{l s='Edit' mod='ets_blog'}</a>
                    {/if}
                </div>        
            </div>                           
            <div class="blog_description">
                {if $blog_post.description}
                    {$blog_post.description nofilter}
                {else}
                    {$blog_post.short_description nofilter}
                {/if}
            </div>
            {if ($show_tags && $blog_post.tags) || ($show_categories && $blog_post.categories)}
            <div class="extra_tag_cat">
                {if $show_tags && $blog_post.tags}
                    <div class="ets-blog-tags">
                        {assign var='ik' value=0}
                        {assign var='totalTag' value=count($blog_post.tags)}
                        <span class="be-label">
                            {if $totalTag > 1}{l s='Tags' mod='ets_blog'}
                            {else}{l s='Tag' mod='ets_blog'}{/if}: 
                        </span>
                        {foreach from=$blog_post.tags item='tag'}
                            {assign var='ik' value=$ik+1}                                        
                            <a href="{$tag.link|escape:'html':'UTF-8'}">{ucfirst($tag.tag)|escape:'html':'UTF-8'}</a>{if $ik < $totalTag}, {/if}
                        {/foreach}
                    </div>
                {/if}
                {if $show_categories && $blog_post.categories}
                    <div class="ets-blog-categories">
                        {assign var='ik' value=0}
                        {assign var='totalCat' value=count($blog_post.categories)}                        
                        <div class="be-categories">
                            <span class="be-label">{l s='Posted in' mod='ets_blog'}: </span>
                            {foreach from=$blog_post.categories item='cat'}
                                {assign var='ik' value=$ik+1}                                        
                                <a href="{$cat.link|escape:'html':'UTF-8'}">{ucfirst($cat.title)|escape:'html':'UTF-8'}</a>{if $ik < $totalCat}, {/if}
                            {/foreach}
                        </div>
                    </div>
                {/if} 
            </div>
            {/if}
            {if isset($ets_blog_config.ETS_BLOG_AUTHOR_INFORMATION)&& $ets_blog_config.ETS_BLOG_AUTHOR_INFORMATION && isset($blog_post.employee.description)&& $blog_post.employee.description}
                <div class="ets-block-author ets-block-author-avata {if $blog_post.employee.avata} ets-block-author-avata{/if}">
                    {if $blog_post.employee.avata}
                        <div class="avata_img">
                            <img class="avata" src="{$link->getMediaLink("`$smarty.const._PS_ETS_BLOG_IMG_`avata/`$blog_post.employee.avata|escape:'htmlall':'UTF-8'`")}"/>
                        </div>
                        
                    {/if}
                    <div class="ets-des-and-author">
                        <div class="ets-author-name">
                            <a href="{$blog_post.author_link|escape:'html':'UTF-8'}">
                                {l s='Author' mod='ets_blog'}: {$blog_post.employee.name|escape:'html':'UTF-8'}
                            </a>
                        </div>
                        {if isset($blog_post.employee.description)&&$blog_post.employee.description}
                            <div class="ets-author-description">
                                {$blog_post.employee.description nofilter}
                            </div>
                        {/if}
                    </div>
                </div>
            {/if}
            <div class="ets-blog-wrapper-comment"> 
                {if $allowComments}
                    <div class="ets_comment_form_blog">
                        <h4 class="title_blog">{l s='Leave a comment' mod='ets_blog'}</h4>
                        <div class="ets-blog-form-comment" id="ets-blog-form-comment">                   
                            {if $hasLoggedIn || $allowGuestsComments}
                                <form action="#ets-blog-form-comment" method="post">
                                    {if isset($comment_edit->id) && $comment_edit->id && !$justAdded}
                                        <input type="hidden" value="{$comment_edit->id|intval}" name="id_comment" />
                                    {/if}
                                    {if !$hasLoggedIn} 
                                        <div class="blog-comment-row blog-name">
                                            <label for="bc-name">{l s='Name' mod='ets_blog'}</label>
                                            <input class="form-control" name="name_customer" id="bc-name" type="text" value="{if isset($name_customer)}{$name_customer|escape:'html':'UTF-8'}{elseif isset($comment_edit->name) && !$justAdded}{$comment_edit->name|escape:'html':'UTF-8'}{/if}" />
                                        </div>
                                        <div class="blog-comment-row blog-email">
                                            <label for="bc-email">{l s='Email' mod='ets_blog'}</label>
                                            <input class="form-control" name="email_customer" id="bc-email" type="text" value="{if isset($email_customer)}{$email_customer|escape:'html':'UTF-8'}{elseif isset($comment_edit->email)&& !$justAdded}{$comment_edit->email|escape:'html':'UTF-8'}{/if}" />
                                        </div>
                                    {/if}
                                    <div class="blog-comment-row blog-title">
                                        <label for="bc-subject">{l s='Subject ' mod='ets_blog'}</label>
                                        <input class="form-control" name="subject" id="bc-subject" type="text" value="{if isset($subject)}{$subject|escape:'html':'UTF-8'}{elseif isset($comment_edit->subject)&& !$justAdded}{$comment_edit->subject|escape:'html':'UTF-8'}{/if}" />
                                    </div>                                
                                    <div class="blog-comment-row blog-content-comment">
                                        <label for="bc-comment">{l s='Comment ' mod='ets_blog'}</label>
                                        <textarea   class="form-control" name="comment" id="bc-comment">{if isset($comment)}{$comment|escape:'html':'UTF-8'}{elseif isset($comment_edit->comment)&& !$justAdded}{$comment_edit->comment|escape:'html':'UTF-8'}{/if}</textarea>
                                    </div>
                                    <div class="blog-comment-row flex_space_between flex-bottom">
                                        {if $allow_rating}
                                            <div class="blog-rate-capcha">
                                                {if $allow_rating}                            
                                                    <div class="blog-rate-post">
                                                        <label>{l s='Rating: ' mod='ets_blog'}</label>
                                                        <div class="blog_rating_box">
                                                            {if $default_rating > 0 && $default_rating <5}
                                                                <input id="blog_rating" type="hidden" name="rating" value="{$default_rating|intval}" />
                                                                {for $i = 1 to $default_rating}
                                                                    <div rel="{$i|intval}" class="star star_on blog_rating_star blog_rating_star_{$i|intval}"></div>
                                                                {/for}
                                                                {for $i = $default_rating + 1 to 5}
                                                                    <div rel="{$i|intval}" class="star blog_rating_star blog_rating_star_{$i|intval}"></div>
                                                                {/for}
                                                            {else}
                                                                <input id="blog_rating" type="hidden" name="rating" value="5" />
                                                                {for $i = 1 to 5}
                                                                    <div rel="{$i|intval}" class="star star_on blog_rating_star blog_rating_star_{$i|intval}"></div>
                                                                {/for}
                                                            {/if}
                                                        </div>
                                                    </div>
                                                {/if}
                                            </div>
                                        {/if}   
                                        <div class="blog-submit-form">                                                    
                                            {if !Configuration::get('ETS_BLOG_DISPLAY_GDPR_NOTIFICATION')}
                                                <div class="blog-submit">
                                                    <input class="button" type="submit" value="{l s='Submit Comment' mod='ets_blog'}" name="bcsubmit" />
                                                </div>
                                            {/if}   
                                        </div>            
                                        {if isset($blog_errors) && is_array($blog_errors) && $blog_errors && !isset($replyCommentsave)}
                                            <div class="alert alert-danger ets_alert-danger">
                                                <button class="close" type="button" data-dismiss="alert">×</button>
                                                <ul>
                                                    {foreach from=$blog_errors item='error'}
                                                        <li>{$error|escape:'html':'UTF-8'}</li>
                                                    {/foreach}
                                                </ul>
                                            </div>
                                        {/if}                                            
                                    </div> 
                                    <div class="blog-comment-row">                                            
                                        <div class="blog-submit-form">
                                            {if Configuration::get('ETS_BLOG_DISPLAY_GDPR_NOTIFICATION')}
                                                <label for="check_gpdr">
                                                    <input id="check_gpdr" type="checkbox" type="check_gpdr" value="1"/>&nbsp;{$text_gdpr|escape:'html':'UTF-8'}
                                                    <a href="{if Configuration::get('ETS_BLOG_TEXT_GDPR_NOTIFICATION_URL_MORE',$id_lang)}{Configuration::get('ETS_BLOG_TEXT_GDPR_NOTIFICATION_URL_MORE',$id_lang)|escape:'html':'UTF-8'}{else}#{/if}">{if Configuration::get('ETS_BLOG_TEXT_GDPR_NOTIFICATION_TEXT_MORE',$id_lang)}{Configuration::get('ETS_BLOG_TEXT_GDPR_NOTIFICATION_TEXT_MORE',$id_lang)|escape:'html':'UTF-8'}{else}{l s='View more details here' mod='ets_blog'}{/if}</a>
                                                </label>
                                                <div class="blog-submit">
                                                    <input class="button" type="submit" disabled="disabled" value="{l s='Submit Comment' mod='ets_blog'}" name="bcsubmit" />
                                                </div>
                                            {/if}   
                                        </div>  
                                    </div>                                            
                                    {if isset($blog_success) && $blog_success}
                                        <p class="alert alert-success ets_alert-success">
                                        <button class="close" type="button" data-dismiss="alert">×</button>    
                                        {$blog_success|escape:'html':'UTF-8'}
                                        </p>
                                    {/if}
                                </form>
                            {else}
                                <p class="alert alert-warning">{l s='Log in to post comments' mod='ets_blog'}</p>
                            {/if}
                        </div> 
                    </div>
                    {if count($comments)}
                        <div class="ets_blog-comments-list">
                        <h4 class="title_blog">{l s='Comments' mod='ets_blog'}</h4>
                            <ul id="blog-comments-list" class="blog-comments-list">
                                {foreach from=$comments item='comment'}
                                        <li id="blog_comment_line_{$comment.id_comment|intval}" class="blog-comment-line"  {*itemprop="review" itemscope="" itemtype="http://schema.org/Review"*}>
                                        {*<meta itemprop="author" content="{ucfirst($comment.name)|escape:'html':'UTF-8'}"/>*}                                                                
                                        <div class="ets-blog-detail-comment">
                                            <h5 class="comment-subject">{$comment.subject|escape:'html':'UTF-8'}</h5>
                                            {if $comment.name}<span class="comment-by">{l s='By: ' mod='ets_blog'}<b>{ucfirst($comment.name)|escape:'html':'UTF-8'}</b></span>{/if}
                                            <span class="comment-time"><span>{l s='On' mod='ets_blog'} </span>{date($date_format,strtotime($comment.date_add))|escape:'html':'UTF-8'}</span>
                                            {if $allow_rating && $comment.rating > 0}
                                                <div class="comment-rating" >
                                                    <span>{l s='Rating: ' mod='ets_blog'}</span>
                                                    <div class="ets_blog_review">
                                                        {for $i = 1 to $comment.rating}
                                                            <div class="star star_on"></div>
                                                        {/for}
                                                        {if $comment.rating<5}
                                                            {for $i = $comment.rating + 1 to 5}
                                                                <div class="star"></div>
                                                            {/for}
                                                        {/if} 
                                                        <span class="ets-blog-everage-rating"> {number_format((float)$comment.rating, 1, '.', '')|escape:'html':'UTF-8'}</span>                                     
                                                    </div>
                                                </div>
                                            {/if} 
                                            <div class="ets-block-report-reply-edit-delete">
                                                {if isset($comment.reply) && $comment.reply}
                                                    <span class="ets-block-comment-reply comment-reply-{$comment.id_comment|intval}" rel="{$comment.id_comment|intval}"><i class="fa fa-reply" aria-hidden="true" title="{l s='Reply' mod='ets_blog'}"></i></span>
                                                {/if}
                                                {if isset($comment.url_edit)}
                                                    <a class="ets-block-comment-edit comment-edit-{$comment.id_comment|intval}" href="{$comment.url_edit|escape:'html':'UTF-8'}"><i class="fa fa-pencil" aria-hidden="true" title="{l s='Edit this comment' mod='ets_blog'}"></i></a>
                                                {/if}
                                                {if isset($comment.url_delete)}
                                                    <a class="ets-block-comment-delete delete-edit-{$comment.id_comment|intval}" href="{$comment.url_delete|escape:'html':'UTF-8'}"><i class="fa fa-trash" aria-hidden="true" title="{l s='Delete this comment' mod='ets_blog'}"></i></a>
                                                {/if}
                                            </div>
                                            {if $comment.comment}<p class="comment-content">{$comment.comment nofilter}</p>{/if}
                                            {if $comment.replies}
                                                {foreach $comment.replies item='reply'}
                                                    <p class="comment-reply">
                                                        <span class="ets-blog-replied-by">
                                                            {l s='Replied by: ' mod='ets_blog'}
                                                            <span class="ets-blog-replied-by-name">
                                                                {ucfirst($reply.name)|escape:'html':'UTF-8'}
                                                            </span>              
                                                        </span>
                                                        <span class="comment-time"><span>{l s='On' mod='ets_blog'} </span>{date($date_format,strtotime($reply.date_add))|escape:'html':'UTF-8'}</span>
                                                        <span class="ets-blog-reply-content">
                                                            {$reply.reply|nl2br nofilter}
                                                        </span>
                                                    </p>
                                                {/foreach}
                                            {/if}
                                            {if isset($replyCommentsave) && $replyCommentsave==$comment.id_comment}
                                                {if isset($replyCommentsaveok) && $blog_success}
                                                    <p class="alert alert-success ets_alert-success">
                                                    <button class="close" type="button" data-dismiss="alert">×</button>{$blog_success|escape:'html':'UTF-8'}
                                                    </p>
                                                {else}
                                                    {if isset($comment.reply) && $comment.reply}
                                                        <form class="form_reply_comment" action="#blog_comment_line_{$comment.id_comment|intval}" method="post">
                                                            {if $blog_errors && is_array($blog_errors) && isset($replyCommentsave)}
                                                                <div class="alert alert-danger ets_alert-danger">
                                                                    <button class="close" type="button" data-dismiss="alert">×</button>
                                                                    <ul >
                                                                        {foreach from=$blog_errors item='error'}
                                                                            <li>{$error|escape:'html':'UTF-8'}</li>
                                                                        {/foreach}
                                                                    </ul>
                                                                </div>
                                                            {/if}
                                                            <input type="hidden" name="replyCommentsave" value="{$comment.id_comment|intval}" />
                                                            <textarea name="reply_comwent_text" placeholder="{l s='Enter your message...' mod='ets_blog'}">{$reply_comwent_text nofilter}</textarea>
                                                            <input type="submit" value="Send" /> 
                                                        </form>
                                                    {else}
                                                        {if $blog_errors && is_array($blog_errors) && isset($replyCommentsave)}
                                                            <div class="alert alert-danger ets_alert-danger">
                                                                <button class="close" type="button" data-dismiss="alert">×</button>
                                                                <ul >
                                                                    {foreach from=$blog_errors item='error'}
                                                                        <li>{$error|escape:'html':'UTF-8'}</li>
                                                                    {/foreach}
                                                                </ul>
                                                            </div>
                                                        {/if}
                                                    {/if}
                                                {/if}
                                            {/if}
                                        </div>
                                        </li>
                                {/foreach}
                            </ul> 
                            {if isset($link_view_all_comment)}
                                <div class="blog_view_all_button">
                                    <a href="{$link_view_all_comment|escape:'html':'UTF-8'}" class="view_all_link">{l s='View all comments' mod='ets_blog'}</a>
                                </div>
                            {/if}
                        </div>               
                    {/if}
                {/if}
            </div>            
        </div>
        {else}
            <p class="warning">{l s='No posts found' mod='ets_blog'}</p>
        {/if}
        {if $blog_post.related_posts}
            <div class="ets-blog-related-posts ets_blog_related_posts_type_{if $blog_related_posts_type}{$blog_related_posts_type|escape:'html':'UTF-8'}{else}default{/if}">
                <h4 class="title_blog">{l s='Related posts' mod='ets_blog'}</h4>
                <div class="ets-blog-related-posts-wrapper">
                    {assign var='post_row' value=3}
                    <ul class="ets-blog-related-posts-list dt-{$post_row|intval}">
                        {foreach from=$blog_post.related_posts item='rpost'}                                            
                            <li class="ets-blog-related-posts-list-li col-xs-12 col-sm-4 col-lg-{12/$post_row|intval} thumbnail-container">
                                {if $rpost.thumb}
                                    <a class="ets_item_img" href="{$rpost.link|escape:'html':'UTF-8'}">
                                        <img src="{$rpost.thumb|escape:'html':'UTF-8'}" alt="{$rpost.title|escape:'html':'UTF-8'}" />
                                    </a>                                                
                                {/if}
                                <a class="ets_title_block" href="{$rpost.link|escape:'html':'UTF-8'}">{$rpost.title|escape:'html':'UTF-8'}</a>
                                <div class="ets-blog-sidear-post-meta">
                                    {if $rpost.categories}
                                        {assign var='ik' value=0}
                                        {assign var='totalCat' value=count($rpost.categories)}                        
                                        <div class="ets-blog-categories">
                                            <span class="be-label">{l s='Posted in' mod='ets_blog'}: </span>
                                            {foreach from=$rpost.categories item='cat'}
                                                {assign var='ik' value=$ik+1}                                        
                                                <a href="{$cat.link|escape:'html':'UTF-8'}">{ucfirst($cat.title)|escape:'html':'UTF-8'}</a>{if $ik < $totalCat}, {/if}
                                            {/foreach}
                                        </div>
                                    {/if}
                                    <span class="post-date">{date($date_format,strtotime($rpost.date_add))|escape:'html':'UTF-8'}</span>
                                </div>
                                {if $allowComments || $show_views || $allow_like}
                                    <div class="ets-blog-latest-toolbar">                                         
                                        {if $allowComments}
                                            {if $rpost.comments_num > 0 }                                                                
                                                <span class="ets-blog-latest-toolbar-comments">{$rpost.comments_num|intval}
                                                    {if $rpost.comments_num!=1}
                                                        <span>{l s='comments' mod='ets_blog'}</span>
                                                    {else}
                                                        <span>{l s='comment' mod='ets_blog'}</span>
                                                    {/if}
                                                </span> 
                                            {/if}                                                                    
                                        {/if}
                                    </div>
                                {/if} 
                                {if $display_desc}
                                    {if $rpost.short_description}
                                        <div class="blog_description">{$rpost.short_description|strip_tags:'UTF-8'|truncate:120:'...'|escape:'html':'UTF-8'}</div>
                                    {elseif $rpost.description}
                                        <div class="blog_description">{$rpost.description|strip_tags:'UTF-8'|truncate:120:'...'|escape:'html':'UTF-8'}</div>
                                    {/if}
                                {/if}
                                 <a class="read_more" href="{$rpost.link|escape:'html':'UTF-8'}">{if $ets_blog_config.ETS_BLOG_TEXT_READMORE}{$ets_blog_config.ETS_BLOG_TEXT_READMORE|escape:'html':'UTF-8'}{else}{l s='Read More' mod='ets_blog'}{/if}</a>
                            </li>
                        {/foreach}                        
                    </ul>
                </div>
            </div>
        {/if}
    </div>
</div>