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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/****************** Start Testimonial list Slider Js *******************************************/
jQuery(document).ready(function($){
  $('.tvcmstestimonial .tvtestimonial-content-box').owlCarousel({
    loop: false,
    dots: true,
    nav: false,
    autoplay:true,
    autoplayTimeout:5000,
    autoplayHoverPause:false,
    smartSpeed: tvMainSmartSpeed,
    responsive: {
      0: { items: 1},
      320:{ items: 1, slideBy: 1},
      400:{ items: 1, slideBy: 1},
      768:{ items: 1, slideBy: 1},
      992:{ items: 1, slideBy: 1},
      1200:{ items: 1, slideBy: 1},
      1600:{ items: 1, slideBy: 1},
      1800:{ items: 1, slideBy: 1}
    },
  });
  $('.tvcmstestimonial .tvtestimonial-prev').on('click',function(){
    $('.tvcmstestimonial .owl-nav .owl-prev').trigger('click');
  });

  $('.tvcmstestimonial .tvtestimonial-next').on('click',function(){
    $('.tvcmstestimonial .owl-nav .owl-next').trigger('click');
  });
  $('.tvcmstestimonial .tvcms-testimonial-pagination-wrapper').insertAfter('.tvcmstestimonial .tvtestimonial-slider-inner');
  /***************** End Home page Slider *************************************************/  
  function sidePanelTestimonialSlider(){
    $('.tvcms-left-testimonial .tvtestimonial-content-box').owlCarousel({
      loop: false,
      dots: false,
      nav: false,
      smartSpeed: tvMainSmartSpeed,
      responsive: {
        0: { items: 1},
        320:{ items: 1, slideBy: 1},
        400:{ items: 1, slideBy: 1},
        768:{ items: 1, slideBy: 1},
        992:{ items: 1, slideBy: 1},
        1200:{ items: 1, slideBy: 1},
        1600:{ items: 1, slideBy: 1},
        1800:{ items: 1, slideBy: 1}
      },
    });
    $('.tvcms-left-testimonial .tvtestimonial-prev').on('click',function(){
      $('.tvcms-left-testimonial .owl-nav .owl-prev').trigger('click');
    });

    $('.tvcms-left-testimonial .tvtestimonial-next').on('click',function(){
      $('.tvcms-left-testimonial .owl-nav .owl-next').trigger('click');
    });
  }
  sidePanelTestimonialSlider();
  // leftRightTestimonialTitleToggle();
  $(window).resize(function(){
    sidePanelTestimonialSlider();
    // leftRightTestimonialTitleToggle();
  });
  // function leftRightTestimonialTitleToggle()
  // {
  //   $('.tvcms-left-testimonial .tvleft-right-title-toggle').on('click',function(e){
  //     e.preventDefault();
  //     if(document.body.clientWidth <= 1199){
  //       if($(this).hasClass('open')) {
  //         $(this).removeClass('open');
  //         $(this).parent().parent().find('.tvtestimonial-slider-button-wrapper').removeClass('open').stop(false).slideUp(500, "swing");
  //       } else {
  //         $(this).addClass('open');
  //         $(this).parent().parent().find('.tvtestimonial-slider-button-wrapper').addClass('open').stop(false).slideDown(500, "swing");
  //       }
  //     }
  //     e.stopPropagation();
  //   });
  // }
});