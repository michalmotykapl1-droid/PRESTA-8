/**
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
 */
$(document).ready(function(){
    ets_blog.runowl();
    $('#module-ets_blog-blog').addClass('ets-blog');
    $(document).on('click','.ets-category-blog-parent',function(){
        $(this).next().toggleClass('show');
        $(this).toggleClass('active');
    });
    $(document).on('click','.ets_axpand_button',function(){
       if($(this).hasClass('closed'))
       {
            $(this).next('.list-months').removeClass('hidden');
            $(this).removeClass('closed').addClass('opened');
       }
       else
       {
            $(this).next('.list-months').addClass('hidden');
            $(this).removeClass('opened').addClass('closed');
       } 
    });
    $('#ets-blog-capcha-refesh').click(function(){
        originalCapcha = $('#ets-blog-capcha-img').attr('src');
        originalCode = $('#ets-blog-capcha-img').attr('rel');
        newCode = Math.random();
        $('#ets-blog-capcha-img').attr('src', originalCapcha.replace(originalCode,newCode));
        $('#ets-blog-capcha-img').attr('rel', newCode);
    });
    $('.blog_rating_star').click(function(){
        var rating = parseInt($(this).attr('rel'));
        $('.blog_rating_star').removeClass('star_on');
        $('#blog_rating').val(rating);
        for(i = 1; i<= rating; i++)
        {
            $('.blog_rating_star_'+i).addClass('star_on');
        }
    });
});
ets_blog = {
    runowl: function()
    {
        if ( $('.ets_blog_rtl_mode').length > 0 ){
            var rtl_blog = true;
    
        } else {
            var rtl_blog = false;
        }
     if ($('.page_blog.ets_block_slider ul').length > 0)
    	$('.page_blog.ets_block_slider ul').etsowlCarousel({
            items : 1,
            nav : true,  
            navigation : true,
            navigationText : ["",""],
            pagination : false,
            loop: $(".page_blog.ets_block_slider ul li").length > 1,
            rewindNav : false,
            dots : false,        
            navText: ['', ''],  
            callbacks: true,
            rtl: rtl_blog,
        });
     if($('.ets_blog_related_posts_type_carousel ul').length)
     {
        $('.ets_blog_related_posts_type_carousel ul').etsowlCarousel({
            items : 3,
            nav : true,  
            navigation : true,
            navigationText : ["",""],
            pagination : false,
            loop: $(".ets_blog_related_posts_type_carousel ul li").length > 3,
            rewindNav : false,
            dots : false,        
            navText: ['', ''],  
            callbacks: true,
            rtl: rtl_blog,
            responsive: {
                0: {
                    items: 1
                },
                768: {
                    items: 2
                },
                1200: {
                    items: 3
                }
            }
        });
     }
    }
}