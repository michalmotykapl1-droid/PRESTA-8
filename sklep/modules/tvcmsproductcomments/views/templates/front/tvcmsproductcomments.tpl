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
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2025 PrestaShop SA
    * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    {strip}
    <script>
        var tvcmsproductcomments_controller_url = '{$tvcmsproductcomments_controller_url nofilter}';
    var confirm_report_message = '{l s='Are you sure that you want to report this comment?' mod='tvcmsproductcomments' js=1}';
    var secure_key = '{$secure_key}';
    var tvcmsproductcomments_url_rewrite = '{$tvcmsproductcomments_url_rewriting_activated}';
    var tvcmsproductcomment_added = '{l s='Your comment has been added!' mod='tvcmsproductcomments' js=1}';
    var tvcmsproductcomment_added_moderation = '{l s='Your comment has been submitted and will be available once approved by a moderator.' mod='tvcmsproductcomments' js=1}';
    var tvcmsproductcomment_title = '{l s='New comment' mod='tvcmsproductcomments' js=1}';
    var tvcmsproductcomment_ok = '{l s='OK' mod='tvcmsproductcomments' js=1}';
    var moderation_active = {$moderation_active};
</script>
    <div class="tab-pane fade in" id="tvcmsproductCommentsBlock">
        {* <div class="tvproduct-comment-review-title products-section-title">{l s='Reviews' mod='tvcmsproductcomments'}</div> *}
        <div class="tabs">
            <div class="clearfix pull-right tvReviews">
                {if ($too_early == false AND ($logged OR $allow_guests))}
                <a class="open-comment-form tv-simple-review-link" href="#new_comment_form">
    WYSTAW OPINIĘ
</a>
                {/if}
            </div>
            <div id="new_comment_form_ok" class="alert alert-success" style="display:none;padding:15px 25px"></div>
            <div id="tvcmsproduct_comments_block_tab" class="row">
                {if $comments}
                {foreach from=$comments item=comment}
                {if $comment.content}
                <div class="col-md-6">
                    <div class="comment clearfix">
                        <div class="comment_author">
                            {* <span>{l s='Grade' mod='tvcmsproductcomments'}&nbsp;</span> *}
                            <div class="comment_author_infos">
                                <strong>{$comment.customer_name|escape:'html':'UTF-8'}</strong>
                                <em>{dateFormat date=$comment.date_add|escape:'html':'UTF-8' full=0}</em>
                            </div>
                            <div class="star_content clearfix">
                                {section name="i" start=0 loop=5 step=1}
                                {if $comment.grade le $smarty.section.i.index}
                                <div class="star"><img src="{$path}star-no.png" loading="lazy"></div>
                                {else}
                                <div class="star star_on"><img src="{$path}star.png" loading="lazy"></div>
                                {/if}
                                {/section}
                            </div>
                        </div>
                        <div class="comment_details">
                            <h6 class="title_block">{$comment.title}</h6>
                            <p>{$comment.content|escape:'html':'UTF-8'}</p>
                            <ul>
                                {if $comment.total_advice > 0}
                                <li>{l s='%1$d out of %2$d people found this review useful.' sprintf=[$comment.total_useful,$comment.total_advice] mod='tvcmsproductcomments'}</li>
                                {/if}
                                {if $logged}
                                {if !$comment.customer_advice}
                                <li>{l s='Was this comment useful to you?' mod='tvcmsproductcomments'}&nbsp;&nbsp;&nbsp;
                                    <button class="usefulness_btn btn btn-unstyle" data-is-usefull="1" data-id-product-comment="{$comment.id_tvcmsproduct_comment}">{l s='Yes' mod='tvcmsproductcomments'}</button>&nbsp;&nbsp;
                                    <button class="usefulness_btn btn btn-unstyle" data-is-usefull="0" data-id-product-comment="{$comment.id_tvcmsproduct_comment}">{l s='No' mod='tvcmsproductcomments'}</button>
                                </li>
                                {/if}
                                {if !$comment.customer_report}
                                <li><span class="report_btn btn" data-id-product-comment="{$comment.id_tvcmsproduct_comment}">{l s='Report abuse' mod='tvcmsproductcomments'}</span></li>
                                {/if}
                                {/if}
                            </ul>
                            {hook::exec('displayProductComment', $comment) nofilter}
                        </div>
                    </div>
                </div>
                {/if}
                {/foreach}
                {else}
                <p class="align_center">{l s='No customer reviews for the moment.' mod='tvcmsproductcomments'}</p>
                {/if}
            </div>
        </div>
        {if isset($product) && $product}
        <!-- Fancybox -->
        <div style="display:none">
            <div id="new_comment_form">
                <form id="id_new_comment_form" action="#">
                    <div class="title">{l s='Write your review' mod='tvcmsproductcomments'}</div>
                    <div class="tvcmsproduct-image-comment col-xs-12 col-sm-6 text-center">
                        <img src="{$product.cover.bySize.medium_default.url}" alt="{$product->name}" width="300" loading="lazy">
                    </div>
                    <div class="tvproduct-review-box new_comment_form_content col-xs-12 col-sm-6">
                        {if isset($product) && $product}
                        <div class="tvproduct-name">
                            <div class="product-title">
                                <a href="#">
                                    <h6>{if isset($product->name)}{$product->name}{elseif isset($product.name)}{$product.name}{/if}</h6>
                                </a>
                            </div>
                        </div>
                        {/if}
                        {if $criterions|@count > 0}
                        <ul id="criterions_list">
                            {foreach from=$criterions item='criterion'}
                            <li>
                                <label>{$criterion.name|escape:'html':'UTF-8'}</label>
                                <div class="star_content">
                                    <input class="star" type="radio" name="criterion[{$criterion.id_tvcmsproduct_comment_criterion|round}]" value="1" />
                                    <input class="star" type="radio" name="criterion[{$criterion.id_tvcmsproduct_comment_criterion|round}]" value="2" />
                                    <input class="star" type="radio" name="criterion[{$criterion.id_tvcmsproduct_comment_criterion|round}]" value="3" />
                                    <input class="star" type="radio" name="criterion[{$criterion.id_tvcmsproduct_comment_criterion|round}]" value="4" />
                                    <input class="star" type="radio" name="criterion[{$criterion.id_tvcmsproduct_comment_criterion|round}]" value="5" checked="checked" />
                                </div>
                                <div class="clearfix"></div>
                            </li>
                            {/foreach}
                        </ul>
                        {/if}
                        <div class="tvcustom-review-box">
                            <label for="comment_title">{l s='Title for your review' mod='tvcmsproductcomments'}<sup class="required">*</sup></label>
                            <input id="comment_title" name="title" type="text" value="" />
                        </div>
                        <div class="tvcustom-review-box">
                            <label for="review_content">{l s='Your review' mod='tvcmsproductcomments'}<sup class="required">*</sup></label>
                            <textarea id="review_content" name="content"></textarea>
                        </div>
                        <div class="tvcustom-review-box">
                            {if $allow_guests == true && !$logged}
                            <label>{l s='Your name' mod='tvcmsproductcomments'}<sup class="required">*</sup></label>
                            <input id="commentCustomerName" name="customer_name" type="text" value="" />
                            {/if}
                        </div>
                        <div id="new_comment_form_footer">
                            <div id="new_comment_form_error" class="error">
                                <ul></ul>
                            </div>
                            <input id="id_tvcmsproduct_comment_send" name="id_product" type="hidden" value='{$id_tvcmsproduct_comment_form}' />
                            <p class="fl required">{l s='Required fields' mod='tvcmsproductcomments'}<sup>*</sup> </p>
                            <div class="fr tvreviews-popup-send-btn">
                                <button class="tvall-inner-btn" id="submitNewMessage" name="submitMessage" type="submit">
                                    <span>{l s='Send' mod='tvcmsproductcomments'}</span>
                                </button>&nbsp;
                                {l s='or' mod='tvcmsproductcomments'}&nbsp;
                                <a href="#" onclick="$.fancybox.close();" class="tvall-inner-btn">
                                    <span>{l s='Cancel' mod='tvcmsproductcomments'}</span>
                                </a>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </form><!-- /end new_comment_form_content -->
            </div>
        </div>
        <!-- End fancybox -->
        {/if}
    </div>
    {/strip}