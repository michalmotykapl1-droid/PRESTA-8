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

var mobileViewSize = 1199;
var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentVerMenuModule = currentThemeName + "_ver_menu_" + langId;
var dropDownParentClass = '.tvcmsvertical-menu .tvallcategories-wrapper';
var dropDownClass = '.tvcmsvertical-menu .tvverticalmenu-dropdown';
jQuery(document).ready(function($) {
    storage = $.localStorage;

    function storageGet(key) {
        return "" + storage.get(currentVerMenuModule + key);
    }

    function storageSet(key, value) {
        storage.set(currentVerMenuModule + key, value);
    }
    var getVerticalMenuAjax = function() {
        /*****Load Cache*****/
        var data = storageGet("");
        if (data != "null") {
            $('.tvcmsvertical-menu-wrapper-data').html(data);
            verticalMenuMobileView();
            verticalMenuLengthCategory();
        }
        /*****Load Cache*****/
        $.ajax({
            type: 'POST',
            url: gettvcmsverticalmenulink,
            success: function(data) {
                var dataCache = storageGet("");
                storageSet("", data);
                if (dataCache === 'null') {
                    $('.tvcmsvertical-menu-wrapper-data').html(data);
                    verticalMenuMobileView();
                    verticalMenuLengthCategory();
                    // customImgLazyLoad('.tvcmsvertical-menu-wrapper-data');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    }
    verticalMenuMobileView();
    verticalMenuLengthCategory();
    //getVerticalMenuAjax();
    //themevoltyCallEventsPush(getVerticalMenuAjax, null);    
    /******************** Start Vertical Menu Js ******************************/
    $(document).on('click', '.modal-backdrop.fade', function() {
        $('.tvcmsvertical-menu .tvallcategories-wrapper, .tvcmsvertical-menu .tvverticalmenu-dropdown').removeClass('open');
        $(this).remove();
    });

    //hide vertical menu
    function hideTvcmsVerticalCategory(vertical_menu_length) {
        var count_cat = 0;
        var total = vertical_menu_length + 2;
        $('.tvverticalmenu-dropdown li.level-1').each(function() {
            count_cat++;
            if (count_cat >= total) {
                $(this).hide();
            }
        });
        if ('.tvvertical-menu-show-hide-category')
            $('.tvvertical-menu-show-hide-category').removeClass('open');
        $('.tvvertical-menu-show-hide-category .tvvertical-hide-category').hide();
        $('.tvvertical-menu-show-hide-category .tvvertical-show-category').show();
    }

    //show vertical menu
    function showTvcmsVerticalCategory() {
        $('.tvverticalmenu-dropdown li.level-1').each(function() {
            $(this).show();
        });

        $('.tvvertical-menu-show-hide-category').addClass('open');
        $('.tvvertical-menu-show-hide-category .tvvertical-hide-category').show();
        $('.tvvertical-menu-show-hide-category .tvvertical-show-category').hide();
    }

    // $(".tvallcategories-wrapper .tvleft-right-title-toggle").on('click',function(e){
    //   // $('.tv-dropdown').removeClass('open').stop(false).slideUp(500, "swing");
    //   var obj = $(this).parent().parent().parent().parent();
    //   if (obj.find('.tvverticalmenu-dropdown').hasClass('open')) {
    //     obj.find('.tvverticalmenu-dropdown').removeClass('open').stop(false).slideUp(500, "swing");
    //   } else {
    //     obj.find('.tvverticalmenu-dropdown').addClass('open').stop(false).slideDown(500, "swing");
    //   }
    //   e.stopPropagation();
    // });

    $('.tvverticalmenu-dropdown .tvvertical-menu-dropdown-icon').on('click', function(e) {
        mobileViewDropdownSubMenu(this);
    });


    function mobileViewDropdownSubMenu(obj) {
        if (document.body.clientWidth > mobileViewSize) {
            $(obj).parent().find('.tvvertical-menu-dropdown-wrapper').first().slideToggle(500, function() {
                if ($(obj).is(":hidden")) {
                    $(obj).parent().find('.tvvertical-toggle-up').first().show();
                    $(obj).parent().find('.tvvertical-toggle-down').first().hide();
                } else {
                    $(obj).parent().find('.tvvertical-toggle-up').first().hide();
                    $(obj).parent().find('.tvvertical-toggle-down').first().show();
                }
            });
            customImgLazyLoad('.tvcmsvertical-menu-wrapper-data');
        }
    }
    // $(window).resize(function(){
    //   verticalMenuLengthCategory();
    // });
    function verticalMenuLengthCategory() {
        var vertical_menu_length = 6;
        /*if(document.body.clientWidth >= 1400){ 
          vertical_menu_length = 6;
        } else if (document.body.clientWidth <= 1399 && document.body.clientWidth >= 1200) {
          vertical_menu_length = 4;
        } else if (document.body.clientWidth <= 1199 && document.body.clientWidth >= 992) {
          vertical_menu_length = 5;
        } else if (document.body.clientWidth <= 991 && document.body.clientWidth >= 768) {
          vertical_menu_length = 4;
        } else {
          vertical_menu_length = 4;
        }*/

        if ($('.tvverticalmenu-dropdown li.level-1').length > vertical_menu_length) {
            hideTvcmsVerticalCategory(vertical_menu_length);
            if ($('.tvcmsverticalmenu .tvverticalmenu-dropdown').find('.tvvertical-menu-show-hide-category').html() === undefined) {
                var more_cat = $('.more_title').text();
                var less_cat = $('.less_title').text();
                $('.tvcmsverticalmenu .tvverticalmenu-dropdown').append('<li class="tvvertical-menu-show-hide-category"><a href="#"><div class=\'tvvertical-show-category\'><span>' + more_cat + '</span><span class="tvvertical-menu-dropdown-icon1"><i class=\'material-icons tvvertical-menu-dropdown-icon tvvertical-menu-more-hide\'>&#xe313;</i></span></div><div class=\'tvvertical-hide-category\'><span>' + less_cat + '</span><span class="tvvertical-menu-dropdown-icon1"><i class=\'material-icons tvvertical-menu-dropdown-icon tvvertical-menu-more-hide\'>&#xe316;</i></span></div></a></li>');
                $('.tvverticalmenu-dropdown .tvvertical-hide-category').hide();
            }
            $(document).on('click', '.tvvertical-menu-show-hide-category', function(e) {
                e.preventDefault();
                if ($(this).hasClass('open')) {
                    hideTvcmsVerticalCategory(vertical_menu_length);
                } else {
                    showTvcmsVerticalCategory();
                }
            })
        }
    }
    verticalMenuMobileView();
    // $(window).on('load resize',function(){
    //   verticalMenuProductSlider();
    // });
    function verticalMenuMobileView() {
        $('.tv-vertical-menu-slider .tv-verticalmenu-slider-wrapper').owlCarousel({
            loop: false,
            dots: false,
            nav: false,
            items: 1, // THIS IS IMPORTANT
            singleItem: true,
            smartSpeed: tvMainSmartSpeed,
            responsive: {
                0: { items: 1, slideBy: 1 },
                320: { items: 1, slideBy: 1 },
                400: { items: 1, slideBy: 1 },
                480: { items: 2, slideBy: 1 },
                768: { items: 2, slideBy: 1 },
                992: { items: 3, slideBy: 1 },
                1200: { items: 2, slideBy: 1 },
                1400: { items: 2, slideBy: 1 },
                1600: { items: 3, slideBy: 1 },
                1800: { items: 3, slideBy: 1 }
            },
        });
        $('.tvvertical-menu-slider-prev').click(function(e) {
            e.preventDefault();
            $('.tv-verticalmenu-slider-wrapper .owl-nav .owl-prev').trigger('click');
        });

        $('.tvvertical-menu-slider-next').click(function(e) {
            e.preventDefault();
            $('.tv-verticalmenu-slider-wrapper .owl-nav .owl-next').trigger('click');
        });
    }
    $(document).on('click', '.tvcmsvertical-menu .tvverticalmenu-dropdown .parent span i', function() {
        // [BB_VERTICAL_MENU] Klikalne przyciski rozwijania także na desktopie
        var $icon = $(this);
        var $row = $icon.parent().parent();
        var $submenu = $row.next('.menu-dropdown');

        if ($icon.hasClass('open')) {
            // Zamykamy bieżącą kategorię
            $icon.removeClass('open');
            $submenu.removeClass('open').stop(false).slideUp(500, "swing");
        } else {
            // Najpierw zamknij wszystkie inne otwarte kategorie w tym samym menu (akordeon)
            var $menuRoot = $icon.closest('.tvcmsvertical-menu');
            if ($menuRoot.length) {
                $menuRoot.find('.tvverticalmenu-dropdown .parent span i.open').not($icon).each(function () {
                    var $otherIcon = $(this);
                    var $otherRow = $otherIcon.parent().parent();
                    var $otherSub = $otherRow.next('.menu-dropdown');
                    $otherIcon.removeClass('open');
                    $otherSub.removeClass('open').stop(false).slideUp(500, "swing");
                });
            }

            // Otwórz bieżącą kategorię
            $icon.addClass('open');
            $submenu.addClass('open').stop(false).slideDown(500, "swing");
        }
    });
});