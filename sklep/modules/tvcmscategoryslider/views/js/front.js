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
* @author    PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*/

jQuery(document).ready(function($){
  // UWAGA: Kod odpowiedzialny za inicjalizację OwlCarousel został usunięty/zakomentowany
  // w celu przejścia na statyczny widok siatki (grid).
  /*
  $('.tvcmscategory-slider .tvcategory-slider-content-box').owlCarousel({
    loop: false,
    dots: false,
    nav: false,
    smartSpeed: tvMainSmartSpeed,
    responsive: {
      0: { items: 1},
      320:{ items: 2, slideBy: 1},
      400:{ items: 3, slideBy: 1},
      768:{ items: 4, slideBy: 1},
      992:{ items: 6, slideBy: 1},
      1200:{ items: 6, slideBy: 1},
      1600:{ items: 6, slideBy: 1},
      1800:{ items: 6, slideBy: 1}
    },
  });
  $('.tvcategory-slider-prev').click(function(e){
    e.preventDefault();
    $('.tvcmscategory-slider .owl-nav .owl-prev').trigger('click');
  });
  $('.tvcategory-slider-next').click(function(e){
    e.preventDefault();
    $('.tvcmscategory-slider .owl-nav .owl-next').trigger('click');
  });
  */
  
  // Ten kod pozostawiamy bez zmian, ponieważ jest związany z umiejscowieniem tytułu/elementu na stronie.
  $('.tvcmscategory-slider .tvcategory-slider-pagination-wrapper').insertAfter('.tvcmscategory-slider .tvcategory-slider-main-title-wrapper .tvcmsmain-title-wrapper');
});