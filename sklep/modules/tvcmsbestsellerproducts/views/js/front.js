/**
 * 2007-2025 PrestaShop
 */
var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentBestModule = tvthemename + "_best_" + langId;
jQuery(document).ready(function($) {
    storage = $.localStorage;

    function storageGet(key) {
        return "" + storage.get(currentBestModule + key);
    }

    function storageSet(key, value) {
        storage.set(currentBestModule + key, value);
    }
    function storageClear(key) {
        storage.remove(currentBestModule + key);
    }
    var gettvcmsbestsellerproductsajax = function() {
        if ($('.tvcmsbest-seller-product').length) {
            /*****Load Cache*****/
            var data = storageGet("");
            if (data != "null") {
                $('.tvcmsbest-seller-product').html(data);
                makeBestProductSlider();
                productTime(); //custom.js
            }
            /*****Load Cache*****/
            $.ajax({
                type: 'POST',
                url: gettvcmsbestsellerproductslink,
                success: function(data) {
                    var dataCache = storageGet("");
                    storageSet("", data);
                    if (dataCache === 'null') {
                        $('.tvcmsbest-seller-product').html(data);
                        makeBestProductSlider();
                        customImgLazyLoad('.tvcmsbest-seller-product');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        }
    }
    themevoltyCallEventsPush(gettvcmsbestsellerproductsajax, null);
    
    function makeBestProductSlider() {
        var bestSellerSwiperClass = [
            ['.tvcmsbest-seller-product .tvbest-seller-product-wrapper', '.tvcmsbest-seller-next', '.tvcmsbest-seller-prev', '.tvcmsbest-seller-product'],
        ];

        for (var i = 0; i < bestSellerSwiperClass.length; i++) {
            var owlConfig = {
                loop: true,
                dots: false,
                nav: false,
                smartSpeed: tvMainSmartSpeed,
                responsive: {
                    0: { items: 1 },
                    320: { items: 2, slideBy: 1 },
                    400: { items: 2, slideBy: 1 },
                    650: { items: 2, slideBy: 1 },
                    768: { items: 3, slideBy: 1 },
                    992: { items: 3, slideBy: 1 },
                    1200: { items: 4, slideBy: 1 },
                    1400: { items: 5, slideBy: 1 },
                    1600: { items: 7, slideBy: 1 },
                    1750: { items: 7, slideBy: 1 } 
                }
            };

            if ($(bestSellerSwiperClass[i][0]).attr('data-has-image') == 'true') {
                $(bestSellerSwiperClass[i][0]).owlCarousel(owlConfig);
            } else {
                $(bestSellerSwiperClass[i][0]).owlCarousel(owlConfig);
            }
            $(bestSellerSwiperClass[i][1]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-next').trigger('click');
            });
            $(bestSellerSwiperClass[i][2]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-prev').trigger('click');
            });
            $(bestSellerSwiperClass[i][3] + ' .tv-pagination-wrapper').insertAfter(bestSellerSwiperClass[i][3] + ' .tvcmsmain-title-wrapper');
        }
    }
});