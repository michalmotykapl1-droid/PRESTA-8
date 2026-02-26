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
    $(document).on('click','.tvcmsproduct-compare-btn', function(e){
        e.preventDefault();
        var t = $(this);
        var id_product = $(this).attr('data-product-id');
        var comp_val = $(this).attr('data-comp-val');

        // console.log(baseDir + 'modules/tvcmsproductcompare/ajax.php?');
        var parents = $('.tvcompare-wrapper.product_id_' + id_product + ' .tvcmsproduct-compare-btn');
        $.ajax({
            type: 'POST',
            url: baseDir + 'modules/tvcmsproductcompare/ajax.php?',
            cache:false,
            data: 'id_product='+ id_product + '&comp_val='+ comp_val + '&token=' + static_token,
            success: function(data)
            {
                var message;
                var arr_data = data.split('##');
                var notic = arr_data[0];
                var full_notic = arr_data[1];
                var tot_comp_prod = arr_data[2];

                if (notic == 'add_compare_prod') {
                    parents.find('.add').addClass('hide');
                    parents.find('.remove').removeClass('hide');
                    parents.attr('data-comp-val','remove');
                    message = full_notic;
                } else if (notic == 'full_compare_prod') {
                    message = full_notic;
                } else if (notic == 'product_remove') {
                    parents.attr('data-comp-val','add');
                    parents.find('.remove').addClass('hide');
                    parents.find('.add').removeClass('hide');
                    message = full_notic;

                } else {
                    message = 'Connection Error';
                }

                var count = $(document).find('.tvcmscount-compare-product');
                count.each(function(){
                    $(this).find('.count-product').html(tot_comp_prod);
                });
                count.find('.tvsticky-compare').find('.count-product').html('( '+tot_comp_prod+' )');
                
                // Desktop View
                $(document).find('.tvcmsdesktop-view-compare').find('.count-product').html('('+tot_comp_prod+')');
                // Mobile View
                $(document).find('.tvcmsmobile-view-compare').find('.count-product').html('('+tot_comp_prod+')');

            //     if (!!$.prototype.fancybox)
            //         $.fancybox.open([
            //             {
            //                 type: 'inline',
            //                 autoScale: true,
            //                 minHeight: 30,
            //                 content: '<p class="fancybox-error"> ' + full_notic + ' </p>'
            //             }
            //         ], {
            //             padding: 0
            //         });
            //     else 
            //         alert(full_notic);

            // },
               if(notic == "full_compare_prod"){
                    $html = '<div class="tvwishlist-popup warning" style="top:'+$('#header').height()+'px"><i class="material-icons">&#xe002</i><p>' + full_notic + '</p></div>';
                    $('#wrapper').append($html);    
                    $('.tvwishlist-popup').slideDown().delay(3000).slideUp(function(){  $(this).remove(); });                    
                }else{
                    if(comp_val == 'add'){
                        $html = '<div class="tvwishlist-popup success" style="top:'+$('#header').height()+'px"><i class="material-icons">&#xe877</i><p>' + full_notic + '</p></div>';
                        $('#wrapper').append($html);    
                        $('.tvwishlist-popup').slideDown().delay(3000).slideUp(function(){  $(this).remove(); });
                    }else{
                        $html = '<div class="tvwishlist-popup error" style="top:'+$('#header').height()+'px"><i class="material-icons">&#xe15d</i><p>' + full_notic + '</p></div>';
                        $('#wrapper').append($html);    
                        $('.tvwishlist-popup').slideDown().delay(3000).slideUp(function(){  $(this).remove(); });
                    }
                }

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    });

    $('.tvcmsproduct-compare-list').click(function(){
        var t = $(this);
        var id_product = $(this).attr('data-product-id');
        var comp_val = $(this).attr('data-comp-val');
        var parents = $('.tvcms-compare-product-'+id_product);
        $.ajax({
            type: 'POST',
            url: baseDir + 'modules/tvcmsproductcompare/ajax.php?',
            cache:false,
            data: 'id_product='+ id_product + '&comp_val='+ comp_val + '&token=' + static_token,
            success: function(data)
            {
                var message;
                var arr_data = data.split('##');
                var notic = arr_data[0];
                var full_notic = arr_data[1];
                var tot_comp_prod = arr_data[2];

                // console.log(parents);
                if (notic == 'product_remove') {

                    parents.attr('data-comp-val','add');
                    parents.find('.remove').addClass('hide');
                    parents.find('.add').removeClass('hide');
                    message = full_notic;

                } else {
                    message = 'Connection Error';
                }

                if(tot_comp_prod>00) {
                    $('.tvcms-compare-product-'+id_product).hide();
                } else {
                    $('#product_comparison').removeClass('active');
                    $('#no_product_comparison').addClass('active');
                }

                var count = $(document).find('.tvcmscount-compare-product');
                count.each(function(){
                    $(this).find('.count-product').html(tot_comp_prod);
                });
                count.find('.tvsticky-compare').find('.count-product').html('( '+tot_comp_prod+' )');

                // Desktop View
                $(document).find('.tvcmsdesktop-view-compare').find('.count-product').html('('+tot_comp_prod+')');
                // Mobile View
                $(document).find('.tvcmsmobile-view-compare').find('.count-product').html('('+tot_comp_prod+')');

                $html = '<div class="tvwishlist-popup error" style="top:'+$('#header').height()+'px"><i class="material-icons">&#xe15d</i><p>' + full_notic + '</p></div>';
                $('#wrapper').append($html);    
                $('.tvwishlist-popup').slideDown().delay(3000).slideUp(function(){  $(this).remove(); });
                // if (!!$.prototype.fancybox)
                //     $.fancybox.open([
                //         {
                //             type: 'inline',
                //             autoScale: true,
                //             minHeight: 30,
                //             content: '<p class="fancybox-error"> ' + full_notic + ' </p>'
                //         }
                //     ], {
                //         padding: 0
                //     });
                // else 
                //     alert(full_notic);

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    })
});