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
  // Inicjalizacja Owl Carousel z włączoną pętlą
  $('.tvcmsbrandlist-slider .tvbrandlist-slider-content-box').owlCarousel({
    loop: true, // ZMIANA: Włączenie nieskończonej pętli
    dots: false,
    smartSpeed: tvMainSmartSpeed, // Upewnij się, że ta zmienna jest zdefiniowana w Twoim motywie
    nav: false, // Nadal ukrywamy domyślne strzałki Owl Carousel
    responsive: {
      0: { items: 1},
      320:{ items: 2, slideBy: 2},
      400:{ items: 2, slideBy: 1},
      768:{ items: 3, slideBy: 1},
      992:{ items: 4, slideBy: 1},
      1200:{ items: 5, slideBy: 1},
      1600:{ items: 6, slideBy: 1},
      1800:{ items: 6, slideBy: 1}
    },
  });
  
  // Obsługa przycisków nawigacyjnych (bez zmian)
  $('.tvbrandlist-slider-prev').click(function(e){
    e.preventDefault();
    // Używamy wewnętrznych przycisków Owl Carousel, nawet jeśli są ukryte
    $('.tvcmsbrandlist-slider .tvbrandlist-slider-content-box').trigger('prev.owl.carousel'); 
  });
  $('.tvbrandlist-slider-next').click(function(e){
    e.preventDefault();
    // Używamy wewnętrznych przycisków Owl Carousel, nawet jeśli są ukryte
    $('.tvcmsbrandlist-slider .tvbrandlist-slider-content-box').trigger('next.owl.carousel');
  });
  
  // Przeniesienie kontenera paginacji (bez zmian)
  $('.tvcmsbrandlist-slider .tvcms-brandlist-pagination-wrapper').insertAfter('.tvcmsbrandlist-slider .tvbrandlist-slider-content-box');
});