/*
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 *  @version  Release: $Revision$
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
//console.log("Testing -> 3.6");
var t;
var customImgLazyLoad;
var removeDefaultDropdown;
var mobileViewSize = 991;
var themevoltyCallMasterEvents = [];
var themevoltyCallEventsPushKey = 1;
var themevoltyCallEventsPushParam = [];
var themevoltyCallEventsPushParamCalled = [];
var themevoltyCallEventsPushStatus = true;
var themevoltyCallEventsPush = function($obj, $param) {
    themevoltyCallMasterEvents.push($obj);
    themevoltyCallEventsPushParam[themevoltyCallEventsPushKey] = $param;
    themevoltyCallEventsPushParamCalled[themevoltyCallEventsPushKey] = false;
    themevoltyCallEventsPushKey++;
}
var themevoltyCallEvents = function($ForceCall) {
    if (themevoltyCallEventsPushStatus) {
        var height = $(window).scrollTop();
        if (height > 49 || $ForceCall) {
            $i = 1;
            //themevoltyCallEventsPushStatus = false;
            $.each(themevoltyCallMasterEvents, function(index, func) {
                if (!themevoltyCallEventsPushParamCalled[$i]) {
                    themevoltyCallEventsPushParamCalled[$i] = true;
                    if (themevoltyCallEventsPushParam[$i] != null) {
                        func(themevoltyCallEventsPushParam[$i]);
                    } else {
                        func();
                    }
                }
                $i++;
            });
        }
    }
}
/******* Start Master Classes *********/
// var ProductPageVerSlider6;
/************ Start To Change Left Column Position in Mobile Size *****************/
function changePositionLeftColumnMobileView() {
    const leftColumn = document.getElementById('left-column');
    const contentWrapper = document.getElementById('content-wrapper');

    if (!leftColumn || !contentWrapper || !contentWrapper.parentNode) {
        return;
    }

    const parent = contentWrapper.parentNode;
    const isDesktopView = document.body.clientWidth > 1199;
    
    // Check if leftColumn is currently after contentWrapper.
    const isMobileLayout = (leftColumn.compareDocumentPosition(contentWrapper) & Node.DOCUMENT_POSITION_FOLLOWING) !== 0;

    if (isDesktopView && isMobileLayout) {
        // Desktop view requires left column first.
        parent.insertBefore(leftColumn, contentWrapper);
    } else if (!isDesktopView && !isMobileLayout) {
        // Mobile view requires left column after.
        parent.insertBefore(leftColumn, contentWrapper.nextSibling);
    }

    // Handle visibility on smaller screens
    if (document.body.clientWidth <= 991) {
        leftColumn.style.display = 'block';
    } else {
        // Revert to default display style on larger screens.
        leftColumn.style.display = ''; 
    }
}

function changePositionProduct3Slider() {
    if (document.body.clientWidth < 1025) {
        $('.product-3 .tv-product-page-image').insertBefore('.product-3 .tv-product-details');
        $('.product-3 #block-reassurance').insertAfter('.product-3 .product-information');
    } else {
        $('.product-3 .tv-product-page-image').insertAfter('.product-3 .tv-product-details');
        $('.product-3 #block-reassurance').insertAfter('.product-3 .tvproduct-page-decs');
    }
}
changePositionProduct3Slider();

function moveDataInMobileView(desktopClass, mobileClass) {
    if ($(desktopClass).html() != undefined && $(mobileClass).html() != undefined) {
        var html = '' + $(desktopClass).html();
        if (html != '') {
            $(mobileClass).html(html);
            $(desktopClass).html('');
        }
    }
}

function moveDataInDesktopView(desktopClass, mobileClass) {
    if ($(desktopClass).html() != undefined && $(mobileClass).html() != undefined) {
        var html = '' + $(mobileClass).html();
        if (html != '') {
            $(desktopClass).html(html);
            $(mobileClass).html('');
        }
    }
}

function showView() {
    if (document.body.clientWidth <= mobileViewSize) { //for mobile view
        moveDataInMobileView('#tvdesktop-megamenu', '#tvmobile-megamenu');
        moveDataInMobileView('#tvcmsdesktop-logo', '#tvcmsmobile-header-logo');
        moveDataInMobileView('#_desktop_search', '#tvcmsmobile-search');
        moveDataInMobileView('#_desktop_cart_manage', '#tvmobile-cart');
        moveDataInMobileView('.tvheader-language', '#tvmobile-lang');
        moveDataInMobileView('.tvheader-currency', '#tvmobile-curr');
        moveDataInMobileView('#tvcmsdesktop-account-button', '#tvcmsmobile-account-button');
        moveDataInMobileView('.tvcmsdesktop-contact', '.tvcmsmobile-contact');

        // moveDataInMobileView('#tvcmsdesktop-vertical-menu', '#tvcmsmobile-vertical-menu');
        moveDataInMobileView('.tvheader-compare', '.tvmobile-compare');
        moveDataInMobileView('.ttvcms-wishlist-icon', '.tvmobile-wishlist');
        // moveDataInMobileView('.tvsearch-header-display-wrappper', '#tvcmsmobile-vertical-menu');
    } else { //for desktop view
        moveDataInDesktopView('#tvcmsdesktop-logo', '#tvcmsmobile-header-logo');
        moveDataInDesktopView('#_desktop_cart_manage', '#tvmobile-cart');
        moveDataInDesktopView('#tvcmsdesktop-account-button', '#tvcmsmobile-account-button');
        moveDataInDesktopView('#tvdesktop-megamenu', '#tvmobile-megamenu');
        moveDataInDesktopView('#_desktop_search', '#tvcmsmobile-search');
        moveDataInDesktopView('.tvheader-language', '#tvmobile-lang');
        moveDataInDesktopView('.tvheader-currency', '#tvmobile-curr');
        moveDataInDesktopView('.tvcmsdesktop-contact', '.tvcmsmobile-contact');
        // moveDataInDesktopView('#tvcmsdesktop-vertical-menu', '#tvcmsmobile-vertical-menu');
        moveDataInDesktopView('.tvheader-compare', '.tvmobile-compare');
        moveDataInDesktopView('.ttvcms-wishlist-icon', '.tvmobile-wishlist');
    }
} //showView
function setSimmner() {
    if (document.body.clientWidth > 991) {
        $('.shimmercard-container').each(function(index, value) {
            $shimmerThis = $(this);
            $data_repeat = $shimmerThis.attr('data-repeat');
            $shimmerThis.removeAttr('data-repeat');
            $shimmerHTML = ($shimmerThis.parent().html());
            for (var i = 0; i < $data_repeat - 1; i++) {
                $shimmerThis.parent().append($shimmerHTML);
            }
        });
    } else {
        $('.shimmercard-container').remove();
    }
}

function productTime() {
    $('.tvproduct-timer').each(function() {
        var $this = $(this);
        var time = $(this).attr('data-end-time');
        if (!$this.hasClass("timeLoaded")) {
            $this.countdown(time, function(event) {
                $this.find('.tvproduct-timer-box .days').html(event.strftime('%D'));
                $this.find('.tvproduct-timer-box .hours').html(event.strftime('%H'));
                $this.find('.tvproduct-timer-box .minutes').html(event.strftime('%M'));
                $this.find('.tvproduct-timer-box .seconds').html(event.strftime('%S'));
            }).addClass("timeLoaded");
        }
    })
}
// "use strict";
// check function is defined or not
function isFunction(fn) {
    return typeof fn === 'function';
}
//if (document.body.clientWidth > 768) {
function ZoomProduct() {
    if (document.body.clientWidth > 1024 && $('body#product').length) {
        // && isFunction('elevateZoom')
        $(".product-cover img").elevateZoom({
            responsive: true,
            //zoomType : "lens",// for lens
            //lensShape : "round",// for lens
            //lensSize    : 150,// for lens
            zoomType: "inner",
            easing: true
        }); 
        $('body').on('mouseenter', '.product-cover .js-qv-product-cover', function() {
            // Remove old instance od EZ
            $('.zoomContainer').remove();
            $(this).removeData('elevateZoom');
            // Update source for images
            $(this).attr('src', $(this).attr('data-image-large-src'));
            $(this).data('zoom-image', $(this).data('zoom-image'));
        });
    }
}
$('body').on('mouseenter', '.tvvertical-slider .js-thumb', function(e) {
    e.preventDefault();
    $('.tvvertical-slider .js-thumb').removeClass('active');
    $(this).addClass('active');
    var img_val = $(this).attr('data-image-large-src');
    $('.product-cover img').attr('src', img_val);
    $('.zoomContainer img').attr('src', img_val);
    $('.zoomWindowContainer div').css("background-image", "url(" + $(this).attr('data-image-large-src') + ")");
});
// *****************END ZOOM_PRODUCT**************//
function RemoveExZoom() {
    if ($(".zoomContainer").length) {
        $('.zoomContainer').remove();
    }
}
/******* End Master Classes *********/


$(".tvproduct-play-icon .fancybox").fancybox({
    minHeight: 500,
});

changePositionLeftColumnMobileView();
showView(); //default landing call for mobile view
setSimmner();
ZoomProduct();
/************/
jQuery(document).ready(function($) {
    $(document).on('click', '.tvproduct-cart-btn', function() {
            $(this).find('.tvproduct-add-to-cart').addClass("loading-wake");
            $(this).find('.tvproduct-add-to-cart').find('.add-cart').addClass('tvcms-cart-loading');
            $(this).find('.tvproduct-add-to-cart').find('.add-cart').html('&#xe863;');
        });


    /******** Sticky Left Right panel *******/
    $('#left-column, #content-wrapper, #right-column').theiaStickySidebar({
        additionalMarginTop: 70
    });

    /******** Sticky Product Panel *******/
    $('.product-1 .tv-product-page-image,.product-1 .tv-product-page-content,.product-2 .tv-product-page-image,.product-2 .tv-product-page-content,.product-3 .tv-product-page-image,.product-3 .tv-product-page-content,.product-4 .tv-product-page-image,.product-4 .tv-product-page-content,.product-5 .tv-product-page-image,.product-5 .tv-product-page-content, .tvquickview-prod-img,.tvquickview-prod-details').theiaStickySidebar({
        additionalMarginTop: 70
    });

    /*********************** Start Product Category page View ******************************/
    $('.tvcmsproduct-grid-list .tvproduct-grid').addClass('active');

    function removeClassesOfView() {
        $('#products').removeClass('grid grid-2 list list-2 catelog');
    }
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-grid', function() {
        removeClassesOfView();
        $('#products').addClass('grid');
        // $('.tvgrid-list-view-product .tvproduct-wrapper.grid').balance();
        $('.tvcmsproduct-grid-list .tvproduct-grid-2').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list-2').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-catelog').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-grid').addClass('active');
    });
    // End Grid View
    // Start Grid-2 View
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-grid-2', function() {
        removeClassesOfView();
        $('#products').addClass('grid-2');
        // $('.tvgrid-list-view-product .tvproduct-wrapper.grid-2').balance();
        $('.tvcmsproduct-grid-list .tvproduct-grid').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list-2').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-catelog').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-grid-2').addClass('active');
    });
    // End Grid-2 View
    // Start List View
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-list', function() {
        removeClassesOfView();
        $('#products').addClass('list');
        $('.tvcmsproduct-grid-list .tvproduct-grid-2').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list-2').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-catelog').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-grid').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list').addClass('active');
    });
    // End List View
    // Start List-2 View
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-list-2', function() {
        removeClassesOfView();
        $('#products').addClass('list-2');
        $('.tvcmsproduct-grid-list .tvproduct-grid-2').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-catelog').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-grid').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list-2').addClass('active');
    });
    // End List-2 View
    // Start Catelog View
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-catelog', function() {
        removeClassesOfView();
        $('#products').addClass('catelog');
        $('.tvcmsproduct-grid-list .tvproduct-grid').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-grid-2').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-list-2').removeClass('active');
        $('.tvcmsproduct-grid-list .tvproduct-catelog').addClass('active');
    });
    /*********************** End Product Category page View ******************************/
    customImgLazyLoad = function($class) {
        //t = setTimeout(function() {
        /*if ($class === undefined && !$class) {
            $class = 'img.lazy';
        } else {
            $class += ' img.lazy';
        }
            $($class).Lazy({
               scrollDirection: 'vertical',
                visibleOnly: false,
                onError: function(element) {
                    console.log('error loading ' + element.data('src'))
                },
                afterLoad: function(element) {
                    element.addClass('loaded');
                },
            });*/
        //}, 500);
    }
    customImgLazyLoad();
    /****************** End Default Left Right Panel Hide ************************/
    // if (TVCMSCUSTOMSETTING_HOVER_IMG !== undefined && TVCMSCUSTOMSETTING_HOVER_IMG == '0') {
    //     $('.tvproduct-hover-img').hide();
    // }
    // *****************STRAT ZOOM_PRODUCT**************    

    function ProductPageVerSlider1() {
        if ($('body').find(".product-1 .tvvertical-slider").length) {
            var SlickSliderVertical = $('body').find(".product-1 .tvvertical-slider .product-images").not('.slick-initialized').slick({
                arrows: true,
                dots: false,
                infinite: true,
                speed: 300,
                prevArrow: $('.tvvertical-slider-pre'),
                nextArrow: $('.tvvertical-slider-next'),
                slidesToShow: 6,
                slidesToScroll: 1,
                variableWidth: false,
                height: true,
                centerMode: false,
                focusOnSelect: true,
                autoplay: true,
                adaptiveHeight: true,
                vertical: true,
                responsive: [{
                        breakpoint: 1280,
                        settings: {
                            arrows: true,
                            slidesToShow: 5,
                            slidesToScroll: 1,
                        }
                    },
                    {
                        breakpoint: 769,
                        settings: {
                            arrows: false,
                            slidesToShow: 1,
                            slidesToScroll: 1,
                            vertical: false,
                            adaptiveHeight: false,
                            dots: true,
                            prevArrow: '',
                            nextArrow: '',
                        }
                    }
                ]
            });
            SlickSliderVertical.on('afterChange', function(event, slick, currentSlide) {
                var $current = $(slick.$slides.get(currentSlide)).find('img');
                var img_val = $current.attr('data-image-large-src') || $current.attr('src');
                if (!img_val) {
                    return;
                }
                $('.product-cover img').attr('src', img_val);
                $('.zoomContainer img').attr('src', img_val);
                $('.zoomWindowContainer div').css("background-image", "url(" + img_val + ")");
            });
            RemoveExZoom();
            ZoomProduct();
        }
    }

    function ProductPageVerSlider2() {
        if ($(".product-2 .tvvertical-slider").length) {
            var SlickSliderVertical = $(".product-2 .tvvertical-slider .product-images").not('.slick-initialized').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                vertical: false,
                adaptiveHeight: false,
                dots: true,
                prevArrow: '',
                nextArrow: '',
                infinite: true,
                speed: 300,
                variableWidth: false,
                height: true,
                centerMode: false,
                focusOnSelect: true,
                autoplay: false,
            });
            SlickSliderVertical.on('afterChange', function(event, slick, currentSlide) {
                var $current = $(slick.$slides.get(currentSlide)).find('img');
                var img_val = $current.attr('data-image-large-src') || $current.attr('src');
                if (!img_val) {
                    return;
                }
                $('.product-cover img').attr('src', img_val);
                $('.zoomContainer img').attr('src', img_val);
                $('.zoomWindowContainer div').css("background-image", "url(" + img_val + ")");
            });
            RemoveExZoom();
            ZoomProduct();
        }
        if (document.body.clientWidth > 768) {
            $('.product-2 .tvvertical-slider .product-images').slick('unslick');
        } else {
            if (!$(".product-2 .tvvertical-slider .product-images").hasClass('slick-initialized')) {
                $('.product-2 .tvvertical-slider .product-images').slick(SlickSliderVertical);
            }
        }
    }

    function ProductPageVerSlider3() {
        if ($('body').find(".product-3 .tvvertical-slider").length) {
            var SlickSliderVertical = $('body').find(".product-3 .tvvertical-slider .product-images").not('.slick-initialized').slick({
                dots: false,
                infinite: true,
                speed: 300,
                prevArrow: $('.tvvertical-slider-pre'),
                nextArrow: $('.tvvertical-slider-next'),
                slidesToShow: 5,
                slidesToScroll: 1,
                variableWidth: false,
                height: true,
                centerMode: false,
                focusOnSelect: true,
                autoplay: false,
                adaptiveHeight: false,
                vertical: false,
                responsive: [{
                    breakpoint: 1280,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1,
                    }
                }, {
                    breakpoint: 1025,
                    settings: {
                        slidesToShow: 5,
                        slidesToScroll: 1,
                    }
                }, {
                    breakpoint: 769,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        vertical: false,
                        adaptiveHeight: false,
                        dots: true,
                        prevArrow: '',
                        nextArrow: '',
                    }
                }]
            });
            SlickSliderVertical.on('afterChange', function(event, slick, currentSlide) {
                var $current = $(slick.$slides.get(currentSlide)).find('img');
                var img_val = $current.attr('data-image-large-src') || $current.attr('src');
                if (!img_val) {
                    return;
                }
                $('.product-cover img').attr('src', img_val);
                $('.zoomContainer img').attr('src', img_val);
                $('.zoomWindowContainer div').css("background-image", "url(" + img_val + ")");
            });
            RemoveExZoom();
            ZoomProduct();
        }
    }

    function ProductPageVerSlider4() {
        if ($('body').find(".product-4 .tvvertical-slider").length) {
            var SlickSliderVertical = $('body').find(".product-4 .tvvertical-slider .product-images").not('.slick-initialized').slick({
                dots: false,
                infinite: true,
                speed: 300,
                prevArrow: $('.tvvertical-slider-pre'),
                nextArrow: $('.tvvertical-slider-next'),
                slidesToShow: 1,
                slidesToScroll: 1,
                variableWidth: false,
                height: true,
                centerMode: false,
                focusOnSelect: true,
                autoplay: false,
                adaptiveHeight: false,
                vertical: false,
                responsive: [{
                    breakpoint: 769,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        vertical: false,
                        adaptiveHeight: false,
                        dots: true,
                        prevArrow: '',
                        nextArrow: '',
                    }
                }]
            });
            SlickSliderVertical.on('afterChange', function(event, slick, currentSlide) {
                var $current = $(slick.$slides.get(currentSlide)).find('img');
                var img_val = $current.attr('data-image-large-src') || $current.attr('src');
                if (!img_val) {
                    return;
                }
                $('.product-cover img').attr('src', img_val);
                $('.zoomContainer img').attr('src', img_val);
                $('.zoomWindowContainer div').css("background-image", "url(" + img_val + ")");
            });
            RemoveExZoom();
            ZoomProduct();
        }
    }

    function ProductPageVerSlider5() {
        if ($('body').find(".product-5 .tvvertical-slider").length) {
            var SlickSliderVertical = $('body').find(".product-5 .tvvertical-slider .product-images").not('.slick-initialized').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                vertical: false,
                adaptiveHeight: false,
                dots: true,
                prevArrow: '',
                nextArrow: '',
                infinite: true,
                speed: 300,
                variableWidth: false,
                height: true,
                centerMode: false,
                focusOnSelect: true,
                autoplay: false,
            });
            SlickSliderVertical.on('afterChange', function(event, slick, currentSlide) {
                var $current = $(slick.$slides.get(currentSlide)).find('img');
                var img_val = $current.attr('data-image-large-src') || $current.attr('src');
                if (!img_val) {
                    return;
                }
                $('.product-cover img').attr('src', img_val);
                $('.zoomContainer img').attr('src', img_val);
                $('.zoomWindowContainer div').css("background-image", "url(" + img_val + ")");
            });
            RemoveExZoom();
            ZoomProduct();
        }
        if (document.body.clientWidth > 768) {
            $('.product-5 .tvvertical-slider .product-images').slick('unslick');
        } else {
            if (!$(".product-5 .tvvertical-slider .product-images").hasClass('slick-initialized')) {
                $('.product-5 .tvvertical-slider .product-images').slick(SlickSliderVertical);
            }
        }
    }

    function ProductPageVerSlider6() {
        if ($('body').find(".product-6 .tvvertical-slider").length) {
            var SlickSliderVertical = $('body').find(".product-6 .tvvertical-slider .product-images").not('.slick-initialized').slick({
                dots: false,
                infinite: true,
                speed: 300,
                prevArrow: $('.tvvertical-slider-pre'),
                nextArrow: $('.tvvertical-slider-next'),
                slidesToShow: 3,
                slidesToScroll: 1,
                variableWidth: false,
                centerMode: true,
                height: true,
                focusOnSelect: true,
                autoplay: false,
                adaptiveHeight: false,
                vertical: false,
                responsive: [{
                    breakpoint: 991,
                    settings: {
                        slidesToShow: 2,
                    }
                }, {
                    breakpoint: 769,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        dots: true,
                        centerMode: false,
                        prevArrow: '',
                        nextArrow: '',
                    }
                }]
            });
            SlickSliderVertical.on('afterChange', function(event, slick, currentSlide) {
                var $current = $(slick.$slides.get(currentSlide)).find('img');
                var img_val = $current.attr('data-image-large-src') || $current.attr('src');
                if (!img_val) {
                    return;
                }
                $('.product-cover img').attr('src', img_val);
                $('.zoomContainer img').attr('src', img_val);
                $('.zoomWindowContainer div').css("background-image", "url(" + img_val + ")");
            });
            RemoveExZoom();
            ZoomProduct();
        }
    }
    $(document).ajaxComplete(function() {
        setTimeout(function() {
            if ($(".quickview .product-6").length > 0) {
                $(".quickview .product-6").parent().parent().addClass('quickprod-6');
                $(".quickview .quickprod-6").parent().parent().parent().css('width', '55rem');
            }
            ProductPageVerSlider1();
            ProductPageVerSlider2();
            ProductPageVerSlider3();
            ProductPageVerSlider4();
            ProductPageVerSlider5();
            ProductPageVerSlider6();
        }, 300);
    });
    /******************END loading*************/

    /************ Start close dropdown When open other dropdown in mobile view **************/
    removeDefaultDropdown = function() {
        // Header My Account Dropdown
        $('#header .tv-account-dropdown').removeClass('open');
        $('#header').find('.tvcms-header-myaccount .tv-myaccount-btn').removeClass('open');
        $('#header').find('.tvcms-header-myaccount .tv-account-dropdown').removeClass('open').hide();
        // Header Search Dropdown
        $('#header .tvcmsheader-search .tvsearch-open').show();
        $('#header .tvcmsheader-search .tvsearch-close').hide();
        $('#header .tvcmsheader-search .tvsearch-header-display-wrappper').removeClass('open');
        $('body').removeClass('tvactive-search');
        $('#header .tvmobile-search-icon .tvsearch-open').show();
        $('#header .tvmobile-search-icon .tvsearch-close').hide();
        // Header My Account Dropdown
        $('#header .tv-account-dropdown').removeClass('open');
        $('#header').find('.tvcms-header-myaccount .tv-myaccount-btn').removeClass('open');
        $('#header').find('.tvcms-header-myaccount .tv-account-dropdown').removeClass('open').hide();
        // language Dropdown
        $('.tvcms-header-language .tv-language-btn').removeClass('open');
        $('.tv-language-dropdown').removeClass('open').hide();
        // Currency Dropdown
        $('.tvcms-header-currency .tv-currency-btn').removeClass('open');
        $('.tv-currency-dropdown').removeClass('open').hide();
        if (document.body.clientWidth <= mobileViewSize) {
            // horizontal menu
            $('#tvcms-mobile-view-header .tvmenu-button').removeClass('open');
            // Cart Dropdown
            $('.hexcms-header-cart .tvcmscart-show-dropdown').removeClass('open');
            // Vertical Menu DropDown
            $('.tvcmsvertical-menu .tvallcategories-wrapper tvleft-right-title-toggle, .tvcmsvertical-menu .tvverticalmenu-dropdown').removeClass('open');
        } else {
            // Vertical Menu DropDown
            $('.tvcmsvertical-menu .tvallcategories-wrapper').removeClass('open');
            $('.tvcmsvertical-menu .tvverticalmenu-dropdown').removeClass('open').removeAttr('style');
        }
    }
    /************ End close dropdown When open other dropdown in mobile view **************/
     $(".tv-menu-horizontal li.level-1.parent").hover(function() {
        $('body').addClass('menu-open');
    }, function() {
        $('body').removeClass('menu-open');
    });
    /*********************** Start Product Category page View ******************************/
    // Start Grid View
    function removeClassesOfView() {
        $('#products').removeClass('grid grid-2 list list-2 catelog');
    }
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-grid', function() {
        removeClassesOfView();
        $('#products').addClass('grid');
    });
    // Start Grid-2 View
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-grid-2', function() {
        removeClassesOfView();
        $('#products').addClass('grid-2');
    });
    // Start List View
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-list', function() {
        removeClassesOfView();
        $('#products').addClass('list');
    });
    // Start List-2 View
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-list-2', function() {
        removeClassesOfView();
        $('#products').addClass('list-2');
    });
    // Start Catelog View
    $(document).on('click', '.tvcmsproduct-grid-list .tvproduct-catelog', function() {
        removeClassesOfView();
        $('#products').addClass('catelog');
    });
    /******* End Product Category page View *******/

    $(document).on('click', '.tvmobile-sliderbar-btn a', function() {
        if (document.body.clientWidth <= 991) {
            removeDefaultDropdown();
            $('.tvmobile-slidebar').addClass('open');
            $('body').addClass('mobile-menu-open');
        }
    });

    $(document).on('click', '.tvmobile-dropdown-close a , .full-wrapper-backdrop', function() {
        $('.tvmobile-slidebar').removeClass('open');
        $('body').removeClass('mobile-menu-open');
    });
    $('body').keydown(function(e) {
        if (e.which == 27) {
            $('.tvmobile-slidebar').removeClass('open');
            $('body').removeClass('mobile-menu-open');
            $('.ttvcmscart-show-dropdown-right').removeClass('open');
            $('body').removeClass('classicCartOpen');
            $('body').removeClass('footerCartOpen');
        }
    });

    /******* Sub category scroll Drag *******/
    if ($('.tvcategory-name-image').length > 0) {
        var slider = document.querySelector('.tvcategory-name-image');
        var mouseDown = false;
        var startX, scrollLeft;

        var startDragging = function(e) {
            mouseDown = true;
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        };
        var stopDragging = function(event) {
            mouseDown = false;
        };

        slider.addEventListener('mousemove', function(e) {
            e.preventDefault();
            if (!mouseDown) { return; }
            var x = e.pageX - slider.offsetLeft;
            var scroll = x - startX;
            slider.scrollLeft = scrollLeft - scroll;
        });

        slider.addEventListener('mousedown', startDragging, false);
        slider.addEventListener('mouseup', stopDragging, false);
        slider.addEventListener('mouseleave', stopDragging, false);
    }

    /******* Start Common Drop-Down Functions *******/
    var dropDownParentClass = '';
    var dropDownClass = '';
    var dropDownAllClose = true;

    function tvDropDownOpen(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown) {
        if (checkInsideDropDown && $('ul.tv-account-dropdown:visible').find(dropdownClass).length > 0) {
            closeOtherDropdown = false;
        }
        if (closeOtherDropdown == true) {
            removeDefaultDropdown();
        }
        if (!$(dropdownClass).hasClass('open')) {
            $('.dropdown-menu').css('height', '');
            $(dropdownClass).addClass('open').stop(false).slideDown(200, "swing");
            $(parentClass).addClass('open');
        }
    }

    function tvDropDownClose(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown) {
        $('.dropdown-menu').css('height', '');
        $(dropdownClass).removeClass('open').stop(false).slideUp(200, "swing");
        $(parentClass).removeClass('open');
    }

    function tvDropDownCommon(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown) {
        tvDropDown(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown);
        tvDropDownHover(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown);
    }
    // this function is use Toggle dropdown
    function tvDropDown(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown) {
        $(document).on('click', parentClass, function(e) {
            if ($(dropdownClass).hasClass('open')) {
                tvDropDownClose(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown);
            } else {
                tvDropDownOpen(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown);
            }
            if ($(parentClass).hasClass('open')) {
                $('body').addClass('dropdown-open');
            } else if (!$(parentClass).hasClass('open')) {
                $('body').removeClass('dropdown-open');
            }
            e.stopPropagation();
        });
        $(document).on('click', '.half-wrapper-backdrop', function(e) {
            tvDropDownClose(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown);
            $('body').removeClass('dropdown-open');
        });
    }

    function tvDropDownHover(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown) {
        $(document).on('mouseenter', parentClass, function(e) {
            if (document.body.clientWidth > mobileViewSize) {
                if (!checkInsideDropDown) {
                    tvDropDownOpen(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown);
                } else if ($('ul.tv-account-dropdown:visible').find(dropdownClass).length == 0) {
                    tvDropDownOpen(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown);
                }
            }
            if ($(parentClass).hasClass('open')) {
                $('body').addClass('dropdown-open');
            }
            e.stopPropagation();
        });
        $(document).on('mouseleave', parentClass, function(e) {
            if (document.body.clientWidth > mobileViewSize) {
                if (!checkInsideDropDown) {
                    tvDropDownClose(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown);
                } else if ($('ul.tv-account-dropdown:visible').find(dropdownClass).length == 0) {
                    tvDropDownClose(parentClass, dropdownClass, closeOtherDropdown, checkInsideDropDown);
                }
            }
            if (!$(parentClass).hasClass('open')) {
                $('body').removeClass('dropdown-open');
            }
            e.stopPropagation();
        });
    }
    /******* Start Account DropDown js *******/
    dropDownParentClass = '.tv-account-wrapper';
    dropDownClass = '.tv-account-dropdown';
    $('.tv-account-dropdown').hide();
    tvDropDownCommon(dropDownParentClass, dropDownClass, true, false);
    /******* Start Language DropDown js *******/
    dropDownParentClass = '.tvcms-header-language .tvheader-language-btn-wrapper';
    dropDownClass = '.tv-language-dropdown';
    $(dropDownClass).hide();
    /*if ($('.tvcmsdesktop-top-header-wrapper').hasClass('header-2') ||
        $('.tvcmsdesktop-top-header-wrapper').hasClass('header-3') || 
        $('.tvcmsdesktop-top-header-wrapper').hasClass('header-4') ||
        $('.tvcmsdesktop-top-header-wrapper').hasClass('header-6')) {
        dropDownAllClose = false;
        tvDropDown(dropDownParentClass, dropDownClass, dropDownAllClose);
    } else {
        dropDownAllClose = true;
        tvDropDownCommon(dropDownParentClass, dropDownClass, dropDownAllClose);
    }*/
    dropDownAllClose = true;
    tvDropDownCommon(dropDownParentClass, dropDownClass, dropDownAllClose, true);
    /********************* End Language DropDown js *****************************************/
    /********************* Start Currency DropDown js *****************************************/
    dropDownParentClass = '.tvheader-currency-wrapper';
    dropDownClass = '.tv-currency-dropdown';
    $(dropDownClass).hide();
    /*if ($('.tvcmsdesktop-top-header-wrapper').hasClass('header-2') ||
        $('.tvcmsdesktop-top-header-wrapper').hasClass('header-3') || 
        $('.tvcmsdesktop-top-header-wrapper').hasClass('header-4') ||
        $('.tvcmsdesktop-top-header-wrapper').hasClass('header-6')) {
        dropDownAllClose = false;
        tvDropDown(dropDownParentClass, dropDownClass, dropDownAllClose);
    } else {
        dropDownAllClose = true;
        tvDropDownCommon(dropDownParentClass, dropDownClass, dropDownAllClose);
    }*/
    dropDownAllClose = true;
    tvDropDownCommon(dropDownParentClass, dropDownClass, dropDownAllClose, true);
    /********************* End Currency DropDown js *****************************************/
    /****************** Start Cart Js *******************************************/
    function cartDropDownJs() {
        $(document).on('click', '.tv-header-cart .tvheader-cart-wrapper a', function() {
            if (document.body.clientWidth <= mobileViewSize) {
                if ($('.tv-header-cart .tvcmscart-show-dropdown').hasClass('open')) {
                    $('.tv-header-cart .tvcmscart-show-dropdown').removeClass('open');
                } else {
                    removeDefaultDropdown();
                    $('.tv-header-cart .tvcmscart-show-dropdown').addClass('open');
                }
            }
        });
        $(document).on('mouseenter', '#_desktop_cart .tvheader-cart-wrapper-popup', function() {
            if (document.body.clientWidth > mobileViewSize) {
                removeDefaultDropdown();
                $('#_desktop_cart .tvcmscart-show-dropdown').addClass('open');
            }
        });

        $(document).on('mouseleave', '#_desktop_cart .tvheader-cart-wrapper-popup', function() {
            if (document.body.clientWidth > mobileViewSize) {
                $('#_desktop_cart .tvcmscart-show-dropdown').removeClass('open');
            }
        });
    } //cartDropDownJs
    cartDropDownJs();
    /****************** End Cart Js *******************************************/
    /************************************ Start Product Details page slider ***************************************************/
    var swiperClass = [
        //['slider className','navigation nextClass','navigation prevClass','paging className']
        ['.tvcmslike-product .tvlike-product-wrapper', '.tvcmslike-next', '.tvcmslike-prev', '.tvcmslike-product'],
        ['.tvcmscross-selling-product .tvcross-selling-product-wrapper', '.tvcmscross-selling-next', '.tvcmscross-selling-prev', '.tvcmscross-selling-product'],
        ['.tvcmssame-category-product .tvsame-category-product-wrapper', '.tvcmssame-category-next', '.tvcmssame-category-prev', '.tvcmssame-category-product'],
    ];
    for (var i = 0; i < swiperClass.length; i++) {
        if ((swiperClass[i][0]).length) {
            $(swiperClass[i][0]).owlCarousel({
                loop: false,
                dots: false,
                nav: false,
                smartSpeed: tvMainSmartSpeed,
                responsive: {
                    0: { items: 1 },
                    320: { items: 1, slideBy: 1 },
                    330: { items: 2, slideBy: 1 },
                    400: { items: 2, slideBy: 1 },
                    480: { items: 2, slideBy: 1 },
                    650: { items: 3, slideBy: 1 },
                    767: { items: 3, slideBy: 1 },
                    768: { items: 3, slideBy: 1 },
                    992: { items: 4, slideBy: 1 },
                    1023: { items: 4, slideBy: 1 },
                    1024: { items: 4, slideBy: 1 },
                    1200: { items: 5, slideBy: 1 },
                    1350: { items: 5, slideBy: 1 },
                    1660: { items: 6, slideBy: 1 },
                    1800: { items: 6, slideBy: 1 }
                }
            });
            $(swiperClass[i][1]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-next').trigger('click');
            });
            $(swiperClass[i][2]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-prev').trigger('click');
            });
            $(swiperClass[i][3] + ' .tv-pagination-wrapper').insertAfter(swiperClass[i][3] + ' .tvcmsmain-title-wrapper');
        }
    }
    //************************************ End Product Details page slider *****************************************************/
    /**************** Start Catelog Quentity Increment Decrement *************************/
    $(document).on('click', '.tvproduct-wrapper.catelog .tvproduct-catalog-btn-wrapper .tvproduct-cart-quentity-increment', function() {
        var obj = $(this).parent().parent().parent().parent();
        var qty = parseInt(obj.find('.tvproduct-cart-quentity').val()) + 1;
        obj.find('.tvproduct-cart-quentity').val(qty);
        $('.tvproduct-cart-btn form input[name=qty]').val(qty);
    });
    $(document).on('click', '.tvproduct-wrapper.catelog .tvproduct-catalog-btn-wrapper .tvproduct-cart-quentity-decrement', function() {
        var obj = $(this).parent().parent().parent().parent();
        var qty = parseInt(obj.find('.tvproduct-cart-quentity').val()) - 1;
        if (qty >= 1) {
            obj.find('.tvproduct-cart-quentity').val(qty);
            obj.parent().find('.tvproduct-cart-btn form input[name=qty]').val(qty);
        }
    });
    $(document).on('blur', '.tvproduct-wrapper.catelog .tvproduct-catalog-btn-wrapper .tvproduct-cart-quentity', function() {
        var obj = $(this).parent().parent().parent().parent();
        var qty = parseInt(obj.find('.tvproduct-cart-quentity').val());
        if (qty > 1 && qty != NaN) {
            obj.find('.tvproduct-cart-quentity').val(qty);
            obj.parent().find('.tvproduct-cart-btn form input[name=qty]').val(qty);
        } else {
            qty = 1;
            obj.find('.tvproduct-cart-quentity').val(qty);
            obj.parent().find('.tvproduct-cart-btn form input[name=qty]').val(qty);
        }
    });
    /**************** End Catelog Quentity Increment Decrement *************************/
    /******************* Start Footer Toggle ***********************************************/
    $('.footer-container .tvfooter-title-wrapper').on('click', function(e) {
        if (document.body.clientWidth > mobileViewSize) {
            e.stopPropagation();
        }
    });
    /******************* End Footer Toggle ***********************************************/
    /******************* Start Menu Sticky Js ********************************************/
    function bottomTotop() {
        var startMenuStickyHeight = 250;
        var scrollHeight = $(document).scrollTop();
        if (scrollHeight > startMenuStickyHeight) {
            $('.tvbottom-to-top').fadeIn('slow');
        } else {
            $('.tvbottom-to-top').fadeOut('slow');
        }
    }
    $(window).on('scroll', function() {
        var menu_sticky = localStorage.getItem('menu-sticky') || 'true';
        if (menu_sticky == 'true') {
            menuStickyJs();
        } else {
            $('.tvcmsheader-sticky').removeClass('sticky');
            $('#wrapper').css('margin-top', '0px');
        }
        bottomTotop();
        bottomSticky();
    });

    function menuStickyJs() {
        var checkMenuSticky = $('body').attr('data-menu-sticky');
        if (checkMenuSticky == '1') {
            var startMenuStickyHeight = 250;
            var scrollHeight = $(document).scrollTop();
            if (document.body.clientWidth > mobileViewSize) {
                if (scrollHeight > startMenuStickyHeight) {
                    $('.tvcmsheader-sticky').addClass('sticky');
                    $('#wrapper').css('margin-top', $('.tvcmsheader-sticky').height() + 'px');
                } else {
                    $('.tvcmsheader-sticky').removeClass('sticky');
                    $('#wrapper').css('margin-top', '0px');
                }
            } else {
                if (scrollHeight > startMenuStickyHeight) {
                    $('.tvcmsmobile-header-search-logo-wrapper.tvcmsheader-sticky').addClass('sticky');
                    $('.tvcmsmobile-header-menu-offer-text.tvcmsheader-sticky').addClass('sticky');
                    $('.tvcmsheader-sticky').addClass('sticky');
                    $('#wrapper').css('margin-top', $('.tvcmsmobile-header-search-logo-wrapper.tvcmsheader-sticky').height() + 'px');
                    $('#wrapper').css('margin-top', $('.tvcmsmobile-header-menu-offer-text.tvcmsheader-sticky').height() + 'px');
                    $('#wrapper').css('margin-top', $('.tvcmsheader-sticky').height() + 'px');
                } else {
                    $('.tvcmsmobile-header-search-logo-wrapper.tvcmsheader-sticky').removeClass('sticky');
                    $('.tvcmsmobile-header-menu-offer-text.tvcmsheader-sticky').removeClass('sticky');
                    $('.tvcmsheader-sticky').removeClass('sticky');
                    $('#wrapper').css('margin-top', '0px');
                }
            }
        }
    }
    menuStickyJs();

    /******************* End Menu Sticky Js **********************/
    /************** Start Filter Search ************************************/
    $(document).on('click', '.tv_search_filter_wrapper .tvleft-right-title-wrapper', function() {
        if ($('#search_filters_wrapper #search_filters').hasClass('open')) {
            $('#search_filters_wrapper #search_filters').removeClass('open').stop(false).slideUp(100, "swing");
        } else {
            $('#search_filters_wrapper #search_filters').addClass('open').stop(false).slideDown(100, "swing");
        }
    });
    /************** End Filter Search ************************************/
    /************* Start Filter Search Category Js ***********************************/
    $(document).on('click', '#search_filters .tvfilter-search-types-title', function() {
        if (document.body.clientWidth <= mobileViewSize) {
            var search_type_id = $(this).attr('data-target');
            if ($(search_type_id).hasClass('open')) {
                $(this).removeClass('open');
                $(search_type_id).removeClass('open').stop(false).slideUp(200, "swing");
            } else {
                $(this).addClass('open');
                $(search_type_id).addClass('open').stop(false).slideDown(200, "swing");
            }
        }
    });
    /************* End Filter Search Category Js ***********************************/
    /*************** Start Left Right Column Js *************************************************/
    // Left panel hide show.
    $(document).on('click', '.tvcms-left-column-wrapper .tv-left-pannal-btn-wrapper', function(e) {
        e.preventDefault();
        if ($('#left-column').hasClass('open')) {
            $('#left-column').removeClass('open');
            $('.modal-backdrop.fade.in').remove();
        } else {
            $('#left-column').addClass('open');
            $('body').append('<div class="modal-backdrop fade in"></div>');
            e.stopPropagation();
        }
    });
    // Right Panel Hide show
    $(document).on('click', '.tvcms-right-column-wrapper .tv-right-pannal-btn-wrapper', function(e) {
        e.preventDefault();
        if ($('#right-column').hasClass('open')) {
            $('#right-column').removeClass('open');
            $('.modal-backdrop.fade.in').remove();
        } else {
            $('#right-column').addClass('open');
            $('body').append('<div class="modal-backdrop fade in"></div>');
            e.stopPropagation();
        }
    });
    $(document).on('click', '#left-column .tvleft-column-close-btn, #right-column .tvright-column-close-btn', function() {
        if ($(this).parent().parent().hasClass('open')) {
            $('.tv-left-right-panel-hide').removeClass('open');
            $('.modal-backdrop.fade.in').remove();
        }
    });
    // Left - right Panel Close. 
    $(document).on('click', '.modal-backdrop.fade.in', function() {
        if ($('#left-column.tv-left-right-panel-hide, #right-column.tv-left-right-panel-hide').hasClass('open')) {
            $('#left-column.tv-left-right-panel-hide, #right-column.tv-left-right-panel-hide').removeClass('open');
            $('.modal-backdrop.fade.in').remove();
        }
    });
    /*************** Start Left Right Column Js *************************************************/
    /************** Start Left Column brand list and supplier toggle ***************************/
    // leftRightBrandSupplierTitleToggle();
    // $(window).resize(function(){
    //     $('.tvfilter-brand-list-wrapper .tvfilter-brand-list, .tvfilter-supplier-list-wrapper .tvfilter-supplier-list').removeClass('open');
    //      });
    //      function leftRightBrandSupplierTitleToggle()
    // {
    //     $('.tvfilter-brand-list-wrapper .tvleft-right-title-toggle, .tvfilter-supplier-list-wrapper .tvleft-right-title-toggle, .block-categories .tvleft-right-title-toggle').on('click', function(){
    //          if(document.body.clientWidth <= 1199){
    //          if($(this).hasClass('open')) {
    //              $(this).removeClass('open');
    //              $(this).parent().parent().find('.tvside-panel-dropdown').removeClass('open').stop(false).slideUp(500, "swing");
    //          } else {
    //              $(this).addClass('open');
    //              $(this).parent().parent().find('.tvside-panel-dropdown').addClass('open').stop(false).slideDown(500, "swing");
    //          }
    //      }
    //      });
    // }
    /************** End Left Column brand list and supplier toggle ***************************/
    /******** Start Scroll to Top js ***************************/
    function scrollToTop() {
        $('body,html').animate({
            scrollTop: 0 // Scroll to top of body
        }, 500);
    }
    $(document).on('click', '.tvbottom-to-top-icon', function() { // When arrow is clicked
        scrollToTop();
    });
    /******** End Scroll to Top js ***************************/
    /****************** Start Tooltip Js **************************/
    $(function() {
        'use strict';
        var popoverConfig = {
            trigger: 'hover',
            template: [
                '<div class="popover tvtooltip" role="tooltip">',
                '<div class="popover-arrow"></div>',
                '<h3 class="popover-title"></h3>',
                '<div class="popover-content"></div>',
                '</div>'
            ].join(''),
            placement: 'top',
            container: 'body',
        };
        initPopovers();
        function initPopovers() {
            $('[data-toggle="tvtooltip"]').popover(popoverConfig);
            $('[data-toggle="tvtooltip"]').on('click',function(){
                $('[data-toggle="tvtooltip"]').popover('hide');
            });
        }
        $(document).ajaxComplete(function() {
            initPopovers();

        });
    });
    /****************** End Tooltip Js **************************/

    $(document).on('click', 'a.tvcart-product-list-checkout-link, .tvcart-product-list-checkout', function(e) {
        location.href = prestashop.urls.pages.order;
    });
    /********************* tab title js ***************************/
    $(document).on('click', '.tvcms-header-menu .tvmain-menu-open', function() {
        if (document.body.clientWidth >= 768) {
            $(this).addClass('open');
            $('.tvcms-header-menu').find('.tvcmsheader-main-menu-wrapper').addClass('open');
            $('body').addClass('tvactive-search');
        }
    });
    $(document).on('click', '.tvcms-header-menu .tvmain-menu-close', function() {
        if (document.body.clientWidth >= 768) {
            $(this).removeClass('open');
            $('.tvcms-header-menu').find('.tvcmsheader-main-menu-wrapper').removeClass('open');
            $('body').removeClass('tvactive-search');
        }
    });
    /*************** Start Left Right Column Js *************************************************/
    $(document).ajaxComplete(function() {
        //var height = $(window).scrollTop();
        //if(height > 49 || !$('body#index').length){
        //}
        productTime();
    });
    /*************** Product Page Js *************************************************/
    $(document).on('click', '#product .product-variants li.input-container label', function(e) {
        $(this).find('span').css('border-color', '#000')
            .css('box-shadow', 'none')
            .css('color', '#000');
        $(this).find('i').css('opacity', '1')
            .css('-webkit-transform', 'scale(1)')
            .css('-moz-transform', 'scale(1)')
            .css('-ms-transform', 'scale(1)')
            .css('-o-transform', 'scale(1)');
    });

    function bottomSticky() {
        var startMenuStickyHeight = $('.tvprduct-image-info-wrapper').height();
        var scrollHeight = $(document).scrollTop();

        if (document.body.clientWidth > mobileViewSize) {
            if (scrollHeight > startMenuStickyHeight) {
                event.stopPropagation();
                $('#product .tvfooter-product-sticky-bottom').addClass('sticky');
                var getHtml = $('.tvproduct-page-wrapper .product-actions').html();
                var GetQty = $('#quantity_wanted').val();
                //$('.tvproduct-page-wrapper .product-actions').html('');  
                var getHtmlsBottom = $('.tvfooter-product-sticky-bottom #bottom_sticky_data').html();
                if (getHtmlsBottom == '') {
                    $('.tvfooter-product-sticky-bottom #bottom_sticky_data').append(getHtml);
                    $('#bottom_sticky_data #new_comment_form_ok').css('display', 'none');
                    $('#bottom_sticky_data #quantity_wanted').val(GetQty);
                    $('#bottom_sticky_data #quantity_wanted').attr('value', GetQty);
                    $('body').css('margin-bottom', $('.tvfooter-product-sticky-bottom.sticky').height() + 'px');
                }
            } else if (scrollHeight < startMenuStickyHeight) {
                $('#product .tvfooter-product-sticky-bottom').removeClass('sticky');
                var GetQty = $('#bottom_sticky_data #quantity_wanted').val();
                event.stopPropagation();
                var getHtmls = $('.tvfooter-product-sticky-bottom #bottom_sticky_data').html();
                if (getHtmls != '') {
                    //var return_back_html = $('.tvfooter-product-sticky-bottom #bottom_sticky_data').html();
                    $('#quantity_wanted').val(GetQty);
                    $('#quantity_wanted').attr('value', GetQty);
                    $('.tvfooter-product-sticky-bottom #bottom_sticky_data').html('');
                    //$('.tvproduct-page-wrapper .product-actions').html(return_back_html);
                }
            }
        } else { //remove mobile
            event.stopPropagation();
            $('#product .tvfooter-product-sticky-bottom').removeClass('sticky');
            var getHtmls = $('.tvfooter-product-sticky-bottom #bottom_sticky_data').html();
            if (getHtmls != '') {
                //var return_back_html = $('.tvfooter-product-sticky-bottom #bottom_sticky_data').html();
                $('.tvfooter-product-sticky-bottom #bottom_sticky_data').html('');
                //$('.tvproduct-page-wrapper .product-actions').html(return_back_html);
            }
        }
    }

    $(document).on('click', '#bottom_sticky_data .btn.btn-touchspin.js-touchspin.bootstrap-touchspin-up', function() {
        event.stopPropagation();
        var obj = $(this).parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent();
        var qty = parseInt(obj.find('#quantity_wanted').val()) + 1;
        $('.input-group.form-control').attr('value', qty);
        $('.input-group.form-control').val(qty);
    });

    $(document).on('click', '#bottom_sticky_data .btn.btn-touchspin.js-touchspin.bootstrap-touchspin-down', function() {
        event.stopPropagation();
        var obj = $(this).parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().parent();
        var qty = parseInt(obj.find('#quantity_wanted').val()) - 1;
        if (qty >= 1) {
            $('.input-group.form-control').attr('value', qty);
            $('.input-group.form-control').val(qty);
        }
    });

    $(document).on('click', '.tvsticky-up-arrow', function(e) {
        e.preventDefault();
        $('body,html').animate({
            scrollTop: 0 // Scroll to top of body
        });
    });
    $(document).on('click', '.ttvcmscart-show-dropdown-right .ttvclose-cart , .full-wrapper-backdrop', function() {
        $('.ttvcmscart-show-dropdown-right').removeClass('open');
        $('body').removeClass('classicCartOpen');
        $('body').removeClass('footerCartOpen');
    });
    $(document).on('click', '.tvheader-cart-btn-wrapper', function() {
        removeDefaultDropdown();
        $('.ttvcmscart-show-dropdown-right').addClass('open');
        $('body').removeClass('footerCartOpen');
        $('body').addClass('classicCartOpen');
    });
    $('#product_comparison .product-desc').balance();
    $('#product_comparison .tvproduct-name').balance();
    $('#product_comparison .product-price-and-shipping').balance();
    $('#product_comparison .thumbnail-container').balance();
    $(window).on('resize', function() {
        changePositionLeftColumnMobileView();
        ProductPageVerSlider1();
        ProductPageVerSlider2();
        ProductPageVerSlider3();
        ProductPageVerSlider4();
        ProductPageVerSlider5();
        ProductPageVerSlider6();
        // HomePageVerSlider();
        // removeDefaultDropdown();
        showView();
        changePositionProduct3Slider();
        cartDropDownJs();
        bottomSticky();
        $('#tvcms-mobile-view-header .tvcmsmobile-contact').show();
        $('.tvmain-menu-dropdown').removeClass('open');
    });
});
var loadBgImgStatus = true;
var loadBgImg = function(){
    if(loadBgImgStatus){
        loadBgImgStatus = false;
        $('body').addClass('bg-enable');
        $("div[data-bg-url]").each(function(){
            var url = $(this).attr('data-bg-url');
            $(this).css("background-image",'url(' + url + ')');
        });
    }
}
$(window).on('scroll', function() {
    // $('#left-column').show();
    themevoltyCallEvents(true);
    loadBgImg();
    // var attrilist = document.querySelectorAll("[loading='lazy']").length;
    // var imglist = document.getElementsByTagName("img").length;

    // console.log('Img=' + imglist, 'attribute=' + attrilist);

});
$(window).on('load', function() {
    $('#tvcms-mobile-view-header .tvcmsmobile-contact').show();
    $('.tvcms-loading-overlay').fadeOut();
    if (document.body.clientWidth > mobileViewSize) {
        themevoltyCallEvents(false);
    }
});
$(document).on('click', '.tvproduct-add-to-cart', function() {
    $(this).addClass("loading-wake");
    $(this).find('.add-cart').addClass('tvcms-cart-loading');
    $(this).find('.add-cart').html('&#xe863;');
});





$(function(){
  // 1) Gdzie wkleić:
  var $priceBlock = $('.product-prices').first();
  if (!$priceBlock.length) return;

  // 2) Definicja ikonki info (weź tę ścieżkę z istniejącej historii cen)
  var iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">'+
                   '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 '+
                             '10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>'+
                 '</svg>';

  // 3) Pobieramy dane (mogą być puste)
  var deliveryText = $('.delivery-information').first().text().trim();       // np. "Dostawa w ciągu 24 - 48 h"
  var stockQty    = $('.tv-indicator .tv-items').first().text().trim();      // np. "1588sztuk"
  var unitText    = $('.product-price-per-unit').first().text().trim();       // np. "0,96 zł / 100g"

  // 4) Jeżeli nie ma **żadnego** z trzech — nie wstawiamy wrappera
  if (!deliveryText && !stockQty && !unitText) return;

  // 5) Przygotuj czyste wartości
  var timePart = deliveryText.replace(/^.*?(\d.*$)/, '$1') || '';
  var qtyPart  = stockQty.replace(/^(\d+)\D*$/, '$1') + '\u00A0szt.'; // nawet jak stockQty=="" → " szt."
  var up       = unitText.split(' / ');
  var pricePart = up[0]||'';
  var unitPart  = up[1]|| '';

  // 6) Zbuduj wrapper i kafelki
  var $wrapper = $('<div class="custom-info-list"></div>');

  if (deliveryText) {
    $wrapper.append(
      '<div class="custom-delivery-bar">'+
        '<span class="custom-delivery-label">Wysyłka w ciągu</span>'+
        '<div class="custom-delivery-value-wrapper">'+ iconHtml +
          '<span class="custom-delivery-value">'+ timePart +'</span>'+
        '</div>'+
      '</div>'
    );
  }
  if (stockQty) {
    $wrapper.append(
      '<div class="custom-stock-bar">'+
        '<span class="custom-stock-bar-status">W magazynie</span>'+
        '<div class="custom-stock-bar-quantity-wrapper">'+ iconHtml +
          '<strong class="custom-stock-bar-quantity">'+ qtyPart +'</strong>'+
        '</div>'+
      '</div>'
    );
  }
  if (unitText) {
    $wrapper.append(
      '<div class="custom-unit-price-bar">'+
        '<span class="custom-unit-price-label">Cena za '+ unitPart +'</span>'+
        '<div class="custom-unit-price-value-wrapper">'+ iconHtml +
          '<span class="custom-unit-price-value">'+ pricePart +'</span>'+
        '</div>'+
      '</div>'
    );
  }

  // 7) Wstaw pod cenami
  $priceBlock.after($wrapper);

  // 8) Schowaj oryginały
  $('.delivery-information, .tv-indicator, .product-price-per-unit').hide();
});
$(document).ready(function() {
  // Sprawdź, czy pole wyszukiwania producentów istnieje na bieżącej stronie
  if ($('#manufacturer-search-input').length > 0 && $('#manufacturer-list').length > 0) {
    
    // Funkcja filtrująca listę producentów
    function filterManufacturers() {
      var searchTerm = $('#manufacturer-search-input').val().toLowerCase().trim(); // Pobierz wpisany tekst, zamień na małe litery i usuń białe znaki z początku/końca
      
      // Przejdź przez każdy element listy producentów (kafelek)
      $('#manufacturer-list li.brand').each(function() {
        var manufacturerNameElement = $(this).find('h6'); // Znajdź element z nazwą producenta (zakładamy, że to h6)
        
        // Upewnij się, że element z nazwą istnieje
        if (manufacturerNameElement.length > 0) {
            var manufacturerName = manufacturerNameElement.text().toLowerCase(); // Pobierz nazwę producenta i zamień na małe litery
            
            // Sprawdź, czy nazwa producenta zawiera wpisany tekst
            if (manufacturerName.includes(searchTerm)) {
              $(this).show(); // Pokaż element, jeśli pasuje
            } else {
              $(this).hide(); // Ukryj element, jeśli nie pasuje
            }
        } else {
            // Jeśli element h6 nie został znaleziony, ukryj kafelek, aby uniknąć błędów
            $(this).hide(); 
        }
      });
    }

    // Nasłuchuj na zdarzenie 'input', które reaguje na każdą zmianę w polu (w tym wklejanie i usuwanie)
    $('#manufacturer-search-input').on('input', filterManufacturers);
    
    // Dodatkowo, jeśli chcesz filtrować od razu po załadowaniu strony (np. jeśli pole miało już wartość)
    // filterManufacturers(); 
  }
});









// BB: „Szukaj w tej kategorii” – używa modułu tvcmssearch i zwraca ten sam panel co header
document.addEventListener('DOMContentLoaded', function () {
  var box = document.getElementById('bb-cat-search');
  if (!box) return;

  var $ = window.jQuery; // moduł tvcmssearch używa jQuery
  var input   = document.getElementById('bb-cat-search-input');
  var clearBt = document.getElementById('bb-cat-search-clear');
  var submit  = document.getElementById('bb-cat-search-submit');
  var results = document.getElementById('bb-cat-search-results');

  var catId   = box.getAttribute('data-category-id');
  var ajaxUrl = (typeof window.tvcmssearch_ajax_url === 'string' && window.tvcmssearch_ajax_url.length)
                  ? window.tvcmssearch_ajax_url
                  : box.getAttribute('data-ajax-url');

  // prosta „szukajka”
  function doSearch(categoryOverride) {
    if (!input || !results || !ajaxUrl) return;
    var q = (input.value || '').trim();
    if (q.length < 3) { results.style.display = 'none'; results.innerHTML = ''; return; }

    var cid = (typeof categoryOverride !== 'undefined' ? categoryOverride : catId);

    results.style.display = 'block';
    results.innerHTML = '<div class="tvsearch-loading-spinner"></div>';

    $.ajax({
      type: 'POST',
      url: ajaxUrl,
      data: { search_words: q, category_id: cid },
      success: function (html) {
        results.innerHTML = html;
        results.style.display = 'block';
      },
      error: function () {
        results.innerHTML = '<p class="no-results">Wystąpił błąd wyszukiwania.</p>';
      }
    });
  }

  // Enter / przycisk / wyczyść
  if (input) {
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
    });
  }
  if (submit) submit.addEventListener('click', function(){ doSearch(); });
  if (clearBt) clearBt.addEventListener('click', function(){
    input.value = ''; input.focus(); results.style.display = 'none'; results.innerHTML = '';
  });

  // klik poza panelem – schowaj
  document.addEventListener('click', function (e) {
    if (!box.contains(e.target)) {
      results.style.display = 'none';
    }
  });

  // PRZECHWYĆ klik w listę kategorii wewnątrz wyników (żeby filtrować AJAX-em dla tej kategorii)
  // Nadpisujemy handler z modułu, żeby NIE używał jego wewnętrznego currentSearchTerm.
  $('#bb-cat-search-results').on('click', '.tvsearch-category-link', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    var cid = this.getAttribute('data-id-category') || 0;
    doSearch(cid);
  });

  // Zamknięcie panelu (krzyżyk z markupu modułu)
  $('#bb-cat-search-results').on('click', '.tvsearch-dropdown-close', function (e) {
    e.preventDefault();
    results.style.display = 'none';
  });
});




/*
 * BB – kod pocztowy: Metoda API (kodpocztowy.intami.pl)
 * Wersja 4.2-clean (Produkcyjna): Budowanie własnej listy <ul>
 * Usunięto logi z konsoli.
 */
(function () {
  
  let isCurrentlyUpdating = false;

  function injectCustomListCss() {
    if (document.getElementById('bb-postcode-css-fix')) return;
    var css = `
      .bb-postcode-wrapper {
        position: relative;
      }
      .bb-city-suggestions {
        position: absolute;
        width: 100%;
        background-color: #ffffff;
        border: 1px solid #ccc;
        border-top: none;
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        list-style-type: none;
        z-index: 100;
        max-height: 200px;
        overflow-y: auto;
      }
      .bb-city-suggestions:empty {
        display: none;
      }
      .bb-city-suggestions li {
        padding: 8px 12px;
        cursor: pointer;
        margin: 0 !important;
        font-size: 14px;
        color: #333;
      }
      .bb-city-suggestions li:hover {
        background-color: #f0f0f0;
      }
    `;
    var styleTag = document.createElement('style');
    styleTag.id = 'bb-postcode-css-fix';
    styleTag.innerHTML = css;
    document.head.appendChild(styleTag);
  }

  async function getCityDataFromApi(postcode) {
    if (postcode.length !== 6 || postcode.charAt(2) !== '-') {
      return [];
    }
    var cacheKey = 'bb_postcode_' + postcode;
    try {
      var cachedData = sessionStorage.getItem(cacheKey);
      if (cachedData) {
        return JSON.parse(cachedData);
      }
    } catch (e) {}
    try {
      var apiUrl = 'https://kodpocztowy.intami.pl/api/' + postcode;
      const response = await fetch(apiUrl, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
      });
      if (!response.ok) {
        throw new Error('API zwróciło błąd: ' + response.status);
      }
      const data = await response.json();
      if (Array.isArray(data) && data.length > 0) {
        const cityNames = data.map(item => item.miejscowosc);
        const uniqueCityNames = [...new Set(cityNames)];
        try {
          sessionStorage.setItem(cacheKey, JSON.stringify(uniqueCityNames));
        } catch (e) {}
        return uniqueCityNames;
      }
      return [];
    } catch (error) {
      console.error('[BB Postcode] BŁĄD API:', error); // Zostawiłem tylko logowanie błędów
      return [];
    }
  }

  function formatPlPostcode(raw) {
    var digits = (raw || '').replace(/\D/g, '').slice(0, 5);
    if (digits.length <= 2) {
      return digits;
    }
    return digits.substr(0, 2) + (digits.length > 2 ? '-' : '') + digits.substr(2);
  }

  function getInputs() {
    var postcodeInput = document.querySelector('#field-postcode, input[name="postcode"], input[name*="[postcode]"]');
    var cityInput = document.querySelector('#field-city, input[name="city"], input[name*="[city]"]');
    var listElement = document.querySelector('#bb-city-suggestions-list');
    return { postcode: postcodeInput, city: cityInput, list: listElement };
  }

  async function updateCityField() {
    if (isCurrentlyUpdating) {
      return;
    }
    isCurrentlyUpdating = true;
    
    var inputs = getInputs();
    if (!inputs.postcode || !inputs.city || !inputs.list) {
      isCurrentlyUpdating = false; 
      return;
    }

    var formatted = formatPlPostcode(inputs.postcode.value);
    if (formatted !== inputs.postcode.value) {
        inputs.postcode.value = formatted;
    }

    inputs.list.innerHTML = ""; 

    if (formatted.length !== 6) {
      if (inputs.city.value !== "") {
          inputs.city.value = ""; 
          inputs.city.dispatchEvent(new Event('change', { bubbles: true }));
      }
      isCurrentlyUpdating = false; 
      return;
    }
    
    var cityNames = await getCityDataFromApi(formatted); 
    
    cityNames.forEach(city => {
        var li = document.createElement('li');
        li.textContent = city;
        
        li.addEventListener('mousedown', function() { 
            inputs.city.value = city;
            inputs.list.innerHTML = "";
            inputs.city.dispatchEvent(new Event('change', { bubbles: true }));
            isCurrentlyUpdating = true; 
            setTimeout(() => { isCurrentlyUpdating = false; }, 200);
        });
        inputs.list.appendChild(li);
    });

    if (cityNames.length === 1) {
      inputs.city.value = cityNames[0];
      inputs.list.innerHTML = "";
    } else if (cityNames.length > 1) {
      if (!cityNames.includes(inputs.city.value)) {
           inputs.city.value = "";
      }
    } else {
      inputs.city.value = "";
    }
    
    inputs.city.dispatchEvent(new Event('change', { bubbles: true }));
    
    isCurrentlyUpdating = false;
  }
  
  var debounceTimeout = null;
  
  function handlePostcodeChange() {
    clearTimeout(debounceTimeout);
    var inputs = getInputs();
    if(inputs.postcode) {
        var formatted = formatPlPostcode(inputs.postcode.value);
        if (formatted !== inputs.postcode.value) {
            inputs.postcode.value = formatted;
        }
    }
    debounceTimeout = setTimeout(updateCityField, 300);
  }
  
  function handlePostcodeBlur() {
    clearTimeout(debounceTimeout); 
    setTimeout(updateCityField, 150); 
  }

  function init() {
    document.addEventListener('DOMContentLoaded', function() {
      var inputs = getInputs();

      if (!inputs.postcode || !inputs.city) {
        return;
      }
      
      var wrapper = document.createElement('div');
      wrapper.className = 'bb-postcode-wrapper';
      
      inputs.city.parentElement.replaceChild(wrapper, inputs.city);
      wrapper.appendChild(inputs.city);

      if (!inputs.list) {
        var listElement = document.createElement('ul');
        listElement.id = 'bb-city-suggestions-list';
        listElement.className = 'bb-city-suggestions';
        wrapper.appendChild(listElement);
      }
      
      inputs.city.setAttribute('autocomplete', 'nope');
      injectCustomListCss();
      
      inputs.postcode.addEventListener('input', handlePostcodeChange);
      inputs.postcode.addEventListener('blur', handlePostcodeBlur);
      
      inputs.city.addEventListener('blur', function() {
           setTimeout(function() {
                var list = getInputs().list;
                if(list && !isCurrentlyUpdating) {
                    list.innerHTML = "";
                }
           }, 250);
      });
      
      if(inputs.postcode.value && inputs.postcode.value.length === 6) {
        updateCityField();
      }
    });
  }

  init();

})();

/*
 * [BB CUSTOM] MODUŁY KOSZYKA: LIMIT ILOŚCI, LOADER, DARMOWA DOSTAWA
 * Wersja: Zsynchronizowana (Loader czeka na przeliczenie paska)
 */

// 1. LIMIT ILOŚCI (Max Qty) - Obsługa steppera
(function () {
  function bbGetMaxQty(input) {
    if (!input) return 0;
    var max = parseInt(input.getAttribute('max') || '0', 10);
    if ((!max || isNaN(max)) && input.dataset) {
      var d = input.dataset.max || input.dataset.quantityAvailable;
      if (d) max = parseInt(d, 10);
    }
    if (isNaN(max) || max < 0) max = 0;
    return max;
  }

  function bbShowMaxAlert(max) {
    var cart = document.querySelector('.cart-grid');
    if (!cart) return;
    var body = cart.querySelector('.cart-grid-body') || cart;
    var alert = document.querySelector('#bb-max-qty-alert');

    if (!alert) {
      alert = document.createElement('div');
      alert.id = 'bb-max-qty-alert';
      alert.className = 'alert alert-warning';
      alert.style.marginBottom = '15px';
      body.insertBefore(alert, body.firstChild);
    }
    alert.innerHTML = 'Możesz kupić maksymalnie <strong>' + max + '</strong> sztuk tego produktu. Ilość w koszyku została skorygowana.';
    clearTimeout(alert._bbTimeout);
    alert._bbTimeout = setTimeout(function () {
      if (alert && alert.parentNode) alert.parentNode.removeChild(alert);
    }, 7000);
  }

  function bbClampQty(input, showAlert) {
    if (!input) return;
    var max = bbGetMaxQty(input);
    if (!max) return;
    var val = parseInt(input.value, 10) || 0;
    if (val > max) {
      input.value = max;
      input.dispatchEvent(new Event('change', { bubbles: true }));
      if (showAlert) bbShowMaxAlert(max);
    }
  }

  document.addEventListener('change', function (e) {
    if (e.target && e.target.classList && e.target.classList.contains('js-cart-line-product-quantity')) {
      bbClampQty(e.target, true);
    }
  });

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-increase-product-quantity, .js-decrease-product-quantity, .bootstrap-touchspin-up, .bootstrap-touchspin-down, .btn-touchspin');
    if (btn) {
      setTimeout(function () {
        var line = btn.closest('.js-cart-line, .cart-item, .product-line-grid');
        if (!line) return;
        var input = line.querySelector('.js-cart-line-product-quantity');
        if (!input) return;
        var max = bbGetMaxQty(input);
        var val = parseInt(input.value, 10) || 0;
        var isPlus = btn.classList.contains('js-increase-product-quantity') || btn.classList.contains('touchspin-up');

        if (isPlus && max && val >= max) {
          bbShowMaxAlert(max);
          if(val > max) input.value = max;
        } else {
          bbClampQty(input, true);
        }
      }, 100);
    }
  });
})();


// 2. AKTUALIZACJA PASKA DARMOWEJ DOSTAWY
(function () {
  function bbGetCurrentTotalFromDom() {
    try {
      // 1. Szukamy w głównym podsumowaniu koszyka
      var subtotalEl = document.querySelector('#cart-subtotal-products .value');
      
      // 2. Jeśli nie ma, szukamy w bocznym koszyku (dropdown)
      if (!subtotalEl) {
          subtotalEl = document.querySelector('.ttvcart-product-list-subtotal-prod .tvcart-product-price');
      }
      
      // 3. Fallback ogólny
      if (!subtotalEl) {
          subtotalEl = document.querySelector('.cart-summary-line.cart-summary-subtotals .value');
      }

      if (!subtotalEl) return NaN;

      var txt = subtotalEl.textContent || '';
      txt = txt.replace(/\s/g, '').replace('zł', '').replace('&nbsp;', '');
      var num = txt.replace(',', '.');
      return parseFloat(num);
    } catch (e) {
      return NaN;
    }
  }

  window.bbUpdateFreeShippingBars = function() {
    try {
      var currentTotal = bbGetCurrentTotalFromDom();
      if (isNaN(currentTotal)) return;

      var bars = document.querySelectorAll('.fs-cart-banner, .bb-free-shipping-bar');
      if (!bars.length) return;

      bars.forEach(function (bar) {
        var threshold = parseFloat(bar.getAttribute('data-free-shipping-threshold') || '0');
        if (!threshold) return;

        var remaining = threshold - currentTotal;
        if (remaining < 0) remaining = 0;

        var percent = threshold > 0 ? (currentTotal * 100) / threshold : 0;
        if (percent > 100) percent = 100;
        if (percent < 0) percent = 0;

        var textContentDiv = bar.querySelector('.fs-text-content, .bb-free-shipping-text');
        var fillEl = bar.querySelector('.fs-progress-fill, .bb-free-shipping-fill');

        if (textContentDiv) {
          if (remaining > 0) {
            var remainingText = remaining.toFixed(2).replace('.', ',');
            if(textContentDiv.classList.contains('fs-text-content')) {
                textContentDiv.innerHTML = 'Pozostało Ci tylko&nbsp;<span class="fs-highlight" style="color:#ea7404; font-weight:700;">' + remainingText + ' zł</span>&nbsp;do darmowej dostawy!';
            } else {
                textContentDiv.innerHTML = 'Brakuje Ci jeszcze <strong>' + remainingText + ' zł</strong> aby skorzystać z darmowej dostawy.';
                textContentDiv.classList.remove('bb-free-shipping-done');
            }
          } else {
            if(textContentDiv.classList.contains('fs-text-content')) {
                textContentDiv.innerHTML = '<span class="fs-highlight" style="color:#ea7404; font-weight:700;">Gratulacje! Twoje zamówienie kwalifikuje się do darmowej dostawy.</span>';
            } else {
                textContentDiv.innerHTML = 'Gratulacje! Twoje zamówienie kwalifikuje się do darmowej dostawy.';
                textContentDiv.classList.add('bb-free-shipping-done');
            }
          }
        }

        if (fillEl) {
          fillEl.style.width = percent.toFixed(0) + '%';
        }
      });
    } catch (e) {
      console.error('BB Free Shipping update error', e);
    }
  };

  // --- MECHANIZM AKTUALIZACJI ---
  var retryInterval = null;
  function triggerUpdateSequence() {
      if(retryInterval) clearInterval(retryInterval);
      
      var attempts = 0;
      // ZWIĘKSZONO CZĘSTOTLIWOŚĆ I CZAS TRWANIA
      // Sprawdza co 200ms przez 2 sekundy (10 prób)
      retryInterval = setInterval(function() {
          window.bbUpdateFreeShippingBars();
          attempts++;
          if(attempts > 10) clearInterval(retryInterval);
      }, 200);
  }

  if (typeof prestashop !== 'undefined') {
      prestashop.on('updateCart', function () {
          // Opóźnienie 800ms przed startem sprawdzania (dajemy czas Preście)
          setTimeout(triggerUpdateSequence, 800);
      });
      prestashop.on('updatedCart', function () {
          setTimeout(triggerUpdateSequence, 800);
      });
  }

  document.addEventListener('click', function(e){
      if(e.target.closest('.qty-stepper') || e.target.closest('.remove-from-cart')) {
          triggerUpdateSequence();
      }
  });

  document.addEventListener('DOMContentLoaded', function(){
      triggerUpdateSequence();
  });

})();


// 3. LOADER KOSZYKA (WYDŁUŻONY CZAS)
(function () {
  var cartLoadingActive = false;
  var hideTimer = null;

  function getCartWrapper() {
    var cart = document.querySelector('.cart-grid');
    if (!cart) return null;
    var body = cart.querySelector('.cart-grid-body') || cart;
    if (!body.classList.contains('bb-cart-loading-wrapper')) {
      body.classList.add('bb-cart-loading-wrapper');
    }
    return body;
  }

  function createOverlay() {
    var wrapper = getCartWrapper();
    if (!wrapper) return null;
    var existing = wrapper.querySelector('.bb-cart-loading-overlay');
    if (existing) return existing;
    var overlay = document.createElement('div');
    overlay.className = 'bb-cart-loading-overlay';
    overlay.innerHTML = '<div class="bb-cart-loading-spinner"></div>';
    wrapper.appendChild(overlay);
    return overlay;
  }

  function showCartLoading() {
    var overlay = createOverlay();
    if (!overlay) return;
    cartLoadingActive = true;
    overlay.classList.add('is-visible');
    if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
  }

  function hideCartLoading() {
    var wrapper = getCartWrapper();
    if (!wrapper) return;
    var overlay = wrapper.querySelector('.bb-cart-loading-overlay');
    if (!overlay) return;
    overlay.classList.remove('is-visible');
    cartLoadingActive = false;
    
    // Na wszelki wypadek odśwież pasek jeszcze raz po ukryciu
    if (typeof window.bbUpdateFreeShippingBars === 'function') {
        window.bbUpdateFreeShippingBars();
    }
  }

  document.addEventListener('change', function (e) {
    if (e.target && e.target.classList && e.target.classList.contains('js-cart-line-product-quantity')) {
      showCartLoading();
    }
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest) return;
    var plus  = e.target.closest('.js-increase-product-quantity, .bootstrap-touchspin-up, .touchspin-up, .btn-touchspin');
    var minus = e.target.closest('.js-decrease-product-quantity, .bootstrap-touchspin-down, .touchspin-down, .btn-touchspin');
    var remove = e.target.closest('.remove-from-cart');
    if (plus || minus || remove) {
      showCartLoading();
    }
  });

  if (typeof prestashop !== 'undefined') {
      prestashop.on('updateCart', function () {
          if (hideTimer) clearTimeout(hideTimer);
          // ZMIANA: WYDŁUŻONO CZAS DO 1600ms (1.6 sekundy)
          // To daje pewność, że pasek pod spodem zdąży się zaktualizować
          hideTimer = setTimeout(hideCartLoading, 100); 
      });
      
      prestashop.on('handleError', function () {
          hideCartLoading();
      });
  }

  // Fallback dla jQuery
  if (window.jQuery) {
    jQuery(document).ajaxStop(function () {
      if (!cartLoadingActive) return;
      if (hideTimer) clearTimeout(hideTimer);
      // Również tutaj 1600ms
      hideTimer = setTimeout(hideCartLoading, 100);
    });
  }

  setInterval(function () {
    if (!cartLoadingActive) return;
    hideCartLoading();
  }, 10000);
})();


