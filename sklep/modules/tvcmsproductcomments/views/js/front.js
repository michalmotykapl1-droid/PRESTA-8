/**
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2025 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

jQuery(document).ready(function($){
    $('input.star').rating();
    $('.auto-submit-star').rating();
    $('#product .star_content .star a').attr('href','/formratting');
    $('#product .star_content .cancel a').attr('href','/commentcancel');
    $(document).on('click','#product .star_content .cancel a',function(e){
        e.preventDefault();
    });

    var obj = $(document).find('#submitNewMessage');
    $('.open-comment-form').fancybox({
        'hideOnContentClick': false
    });

    url_options = '?';

    if (typeof(tvcmsproductcomments_url_rewrite) !== 'undefined'){
        if (tvcmsproductcomments_url_rewrite == '0') {
            url_options = '&';
        }
    }

    $('button.usefulness_btn').click(function () {
        var id_tvcmsproduct_comment = $(this).data('id-product-comment');
        var is_usefull = $(this).data('is-usefull');
        var parent = $(this).parent();

        $.ajax({
            url: tvcmsproductcomments_controller_url + url_options + 'rand=' + new Date().getTime(),
            data: {
                id_tvcmsproduct_comment: id_tvcmsproduct_comment,
                action: 'comment_is_usefull',
                value: is_usefull
            },
            type: 'POST',
            headers: {"cache-control": "no-cache"},
            success: function (result) {
                parent.fadeOut('slow', function () {
                    parent.remove();
                });
            }
        });
    });

    $('span.report_btn').click(function () {
        if (confirm(confirm_report_message)) {
            var idtvcmsProductComment = $(this).data('id-product-comment');
            var parent = $(this).parent();

            $.ajax({
                url: tvcmsproductcomments_controller_url + url_options + 'rand=' + new Date().getTime(),
                data: {
                    id_tvcmsproduct_comment: idtvcmsProductComment,
                    action: 'report_abuse'
                },
                type: 'POST',
                headers: {"cache-control": "no-cache"},
                success: function (result) {
                    parent.fadeOut('slow', function () {
                        parent.remove();
                    });
                }
            });
        }
    });

    $('#submitNewMessage').click(function (e) {
        e.preventDefault();
        var obj = $(this);

        $.ajax({
            url: tvcmsproductcomments_controller_url + url_options + 'action=add_comment&secure_key=' + secure_key + '&rand=' + new Date().getTime(),
            data: $('#id_new_comment_form').serialize(),
            type: 'POST',
            headers: {"cache-control": "no-cache"},
            dataType: "json",
            success: function (data) {
                if (data.result) {
                    $.fancybox.close();
                    fancyChooseBox(moderation_active ? tvcmsproductcomment_added_moderation : tvcmsproductcomment_added);
                }
                else {
                    $('#new_comment_form_error ul').html('');
                    $.each(data.errors, function (index, value) {
                        $('#new_comment_form_error ul').append('<li>' + value + '</li>');
                    });
                    $('#new_comment_form_error').slideDown('slow');
                }
            }
        });
        return false;
    });

    $('.tvall-product-review').click(function(e){
        e.preventDefault();
        $('html, body').animate({
            scrollTop: ($('.tvproduct-description-tab').offset().top)-300
        }, 500, 'linear',function(){
            $('a[href="#tvcmsproductCommentsBlock"]').trigger('click'); 
        });
    });


function fancyChooseBox(msg) {
    $('#new_comment_form_ok').html(msg).show();
}

function tvcmsproductcommentRefreshPage() {
    window.location.reload();
}
});