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
<div class="panel ets_blogcomment_reply">
    <div class="comment-content">
        <span class="panel-heading-action">
            <span>
                {l s='Status' mod='ets_blog'}:&nbsp;{if $comment->approved}{l s='Approved' mod='ets_blog'}{else}{l s='Pending' mod='ets_blog'}{/if}
            </span>
            <a class="del_comment" title="{l s='Delete' mod='ets_blog'}" onclick="return confirm('Do you want to delete this comment?');" href="{$link_delete|escape:'html':'UTF-8'}">
                <i class="icon-trash"></i>
                {l s='Delete' mod='ets_blog'}
            </a>
            {if $comment->approved}
                <a class="field-approved list-action-enable action-disabled" title="{l s='Click to disapprove' mod='ets_blog'}" href="{$curenturl|escape:'html':'UTF-8'}&id_comment={$comment->id|intval}&change_comment_approved=0">
                    <i class="icon-check"></i>
                </a>
            {else}
                <a class="field-approved list-action-enable action-enabled" title="{l s='Click to mark as approved' mod='ets_blog'}" href="{$curenturl|escape:'html':'UTF-8'}&id_comment={$comment->id|intval}&change_comment_approved=1">
                    <i class="icon-remove"></i>
                </a>
            {/if}
        </span>
        <h4 class="subject_comment">{$comment->subject|escape:'html':'UTF-8'}</h4>
        {if $comment->name}
            <h4 class="comment_name">
                {l s='By' mod='ets_blog'}: <span>{$comment->name|escape:'html':'UTF-8'}</span>
            </h4>
        {/if}
        <h4 class="post_title">
            {l s='Post title' mod='ets_blog'}: <span><a target="_blank" href="{$post_link|escape:'html':'UTF-8'}" title="{$post_class->title|escape:'html':'UTF-8'}">{$post_class->title|escape:'html':'UTF-8'}</a></span>
        </h4> 
        <div class="comment-content">
            <p>{$comment->comment|nl2br nofilter}</p>
        </div>
        <form method="post" action="">
            <div class="form_reply">
                <textarea id="reply_comwent_text" placeholder="{l s='Reply ...' mod='ets_blog'}" name="reply_comwent_text">{if isset($reply_comwent_text)}{$reply_comwent_text|escape:'html':'UTF-8'}{/if}</textarea>
                <input class="btn btn-primary btn-default" type="submit" value="{l s='Send' mod='ets_blog'}" name="addReplyComment"/><br />
            </div>
            {if $replies}
                <h4 class="replies_comment">{l s='Replies' mod='ets_blog'}:</h4>
                <div class="table-responsive clearfix">
                    <table class="table configuration">
                        <thead>
                            <tr class="nodrag nodrop">
                                <script type="text/javascript">
                                    var detele_confirm ="{l s='Do you want to delete this item?' mod='ets_blog'}";
                                </script>
                                <td>{l s='Id' mod='ets_blog'}</td>
                                <td>{l s='Name' mod='ets_blog'}</td>
                                <td>{l s='Reply content' mod='ets_blog'}</td>
                                <td class="text-center">{l s='Approved' mod='ets_blog'}</td>
                                <td class="text-center">{l s='Action' mod='ets_blog'}</td>
                            </tr>
                        </thead>
                        <tbody id="list-ets_reply">
                            {foreach from=$replies item='reply'}
                                <tr>
                                    <td>{$reply.id_reply|intval}</td>
                                    <td>{$reply.name|escape:'html':'UTF-8'}</td>
                                    <td>{$reply.reply|nl2br nofilter}</td>
                                    <td class="text-center">
                                        {if $reply.approved}
                                            <a class="list-action field-approved list-action-enable action-enabled list-item-{$reply.id_reply|intval}" data-id="{$reply.id_reply|intval}" title="{l s='Unapproved' mod='ets_blog'}" href="{$curenturl|escape:'html':'UTF-8'}&id_reply={$reply.id_reply|intval}&change_approved=0">
                                                <i class="icon-check"></i>
                                            </a>
                                        {else}
                                            <a class="list-action field-approved list-action-enable action-disabled list-item-{$reply.id_reply|intval}" data-id="{$reply.id_reply|intval}" title="{l s='Approved' mod='ets_blog'}" href="{$curenturl|escape:'html':'UTF-8'}&id_reply={$reply.id_reply|intval}&change_approved=1">
                                                <i class="icon-remove"></i>
                                            </a>
                                        {/if}
                                    </td>
                                    <td class="text-center">
                                        <a class="del_reply" href="{$curenturl|escape:'html':'UTF-8'}&delreply=1&id_reply={$reply.id_reply|intval}" onclick="return confirm('{l s='Do you want to delete this item?' mod='ets_blog'}');" title="{l s='Delete' mod='ets_blog'}"><i class="icon-trash"></i>{l s='Delete' mod='ets_blog'}</a>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                    <select id="bulk_action_reply" name="bulk_action_reply" style="display:none;width: 200px;">
                        <option value="">{l s='Bulk actions' mod='ets_blog'}</option>
                        <option value="mark_as_approved">{l s='Approved' mod='ets_blog'}</option>
                        <option value="mark_as_unapproved">{l s='Unapproved' mod='ets_blog'}</option>
                        <option value="delete_selected">{l s='Delete selected' mod='ets_blog'}</option>
                    </select>
                </div>
            {/if}
            <div class="panel-footer">
                <a class="btn btn-default" href="{$link_back|escape:'html':'UTF-8'}" title="{l s='Back' mod='ets_blog'}">
                <i class="process-icon-cancel"></i>
                {l s='Back' mod='ets_blog'}
            </a>
            </div>
        </form>
    </div>
</div>