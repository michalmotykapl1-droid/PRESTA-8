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
<form id="form_comment" class="defaultForm form-horizontal" novalidate="" enctype="multipart/form-data" method="post" action="">
    <div class="panel ets-blog-panel">
        <div class="panel-heading">
            {l s='Edit customer comment' mod='ets_blog'}
        </div>
        <section class="form-fields">
            <div class="form-group row ">
                <label class="col-md-3 form-control-label" for="subject">{l s='Subject' mod='ets_blog'}<span class="required">*</span></label>
                <div class="col-md-9">
                    <input id="subject" class="form-control" type="text" value="{if isset($smarty.post.subject)}{$smarty.post.subject|escape:'html':'UTF-8'}{else}{if $ets_comment->id}{$ets_comment->subject|escape:'html':'UTF-8'}{/if}{/if}" name="subject" title="{if $ets_comment->id}{$ets_comment->subject|escape:'html':'UTF-8'}{/if}" />
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-3 form-control-label" for="rating">{l s='Rating' mod='ets_blog'}</label>
                <div class="col-md-9">
                    <select id="rating" class="form-control fixed-width-xl" name="rating">
                        <option value="1" {if $ets_comment->rating==1}selected="selected"{/if}>{l s='1 rating' mod='ets_blog'}</option>
                        <option value="2" {if $ets_comment->rating==2}selected="selected"{/if}>{l s='2 ratings' mod='ets_blog'}</option>
                        <option value="3" {if $ets_comment->rating==3}selected="selected"{/if}>{l s='3 ratings' mod='ets_blog'}</option>
                        <option value="4" {if $ets_comment->rating==4}selected="selected"{/if}>{l s='4 ratings' mod='ets_blog'}</option>
                        <option value="5" {if $ets_comment->rating==5}selected="selected"{/if}>{l s='5 ratings' mod='ets_blog'}</option>
                    </select>
                </div>
            </div>
            <div class="form-group row ">
                <label class="col-md-3 form-control-label" for="comment">{l s='Comment' mod='ets_blog'}<span class="required">*</span></label>
                <div class="col-md-9">
                    <textarea rows="5" class="form-control"  name="comment" id="comment">{if isset($smarty.post.comment)}{$smarty.post.comment|escape:'html':'UTF-8'}{else}{if $ets_comment->id}{$ets_comment->comment nofilter}{/if}{/if}</textarea>
                </div>
            </div>
            <input name="id_comment" value="{if $ets_comment->id}{$ets_comment->id|intval}{/if}" type="hidden" />
        </section>
    </div>
    <a class="btn btn-primary float-xs-left" href="{$link_back_list|escape:'html':'UTF-8'}">
        {l s='Back to list' mod='ets_blog'}
    </a>
    <button class="btn btn-primary float-xs-right" name="submitComment" type="submit">{l s='Save' mod='ets_blog'}</button>
    <button class="btn btn-primary float-xs-right" name="submitCommentStay" type="submit">{l s='Save and stay' mod='ets_blog'}</button>
</form>