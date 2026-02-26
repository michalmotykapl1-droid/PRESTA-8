/**
 * 2007-2025 PrestaShop
 */
var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentFeatureModule = tvthemename + "_feature_" + langId;
jQuery(document).ready(function($) {
    storage = $.localStorage;

    function storageGet(key) {
        return "" + storage.get(currentFeatureModule + key);
    }

    function storageSet(key, value) {
        storage.set(currentFeatureModule + key, value);
    }
    function storageClear(key) {
        storage.remove(currentFeatureModule + key);
    }
    var gettvcmsfeatureproductsajax = function() {
        if ($('.tvcmsfeatured-product').length) {
            /*****Load Cache*****/
            var data = storageGet("");
            if (data != "null") {
                $('.tvcmsfeatured-product').html(data);
                makeFeatureProductSlider();
                productTime(); //custom.js
            }
            /*****Load Cache*****/
            $.ajax({
                type: 'POST',
                url: gettvcmsfeaturedproductslink,
                success: function(data) {
                    var dataCache = storageGet("");
                    storageSet("", data);
                    if (dataCache === 'null') {
                        $('.tvcmsfeatured-product').html(data);
                        makeFeatureProductSlider();
                        customImgLazyLoad('.tvcmsfeatured-product');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        }
    }

    themevoltyCallEventsPush(gettvcmsfeatureproductsajax, null);

    function makeFeatureProductSlider() {
        // ZMIANA NAZWY ZMIENNEJ ABY UNIKNĄĆ KONFLIKTU Z MODUŁEM PROMOCJI
        var featuredSwiperClass = [
            ['.tvcmsfeatured-product .tvfeatured-product-wrapper', '.tvcmsfeatured-next', '.tvcmsfeatured-prev', '.tvcmsfeatured-product'],
        ];
        
        for (var i = 0; i < featuredSwiperClass.length; i++) {
            var owlConfig = {
                loop: true,
                dots: false,
                nav: false,
                smartSpeed: tvMainSmartSpeed,
                responsive: {
                    0: { items: 1 },
                    320: { items: 1, slideBy: 1 },
                    400: { items: 2, slideBy: 1 },
                    650: { items: 2, slideBy: 1 },
                    768: { items: 3, slideBy: 1 },
                    992: { items: 3, slideBy: 1 },
                    1200: { items: 4, slideBy: 1 },
                    1400: { items: 5, slideBy: 1 },
                    1600: { items: 6, slideBy: 1 },
                    1750: { items: 7, slideBy: 1 } 
                }
            };

            if ($(featuredSwiperClass[i][0]).attr('data-has-image') == 'true') {
                $(featuredSwiperClass[i][0]).owlCarousel(owlConfig);
            } else {
                $(featuredSwiperClass[i][0]).owlCarousel(owlConfig);
            }
            
            $(featuredSwiperClass[i][1]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-next').trigger('click');
            });
            $(featuredSwiperClass[i][2]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-prev').trigger('click');
            });
            
            $(featuredSwiperClass[i][3] + ' .tv-pagination-wrapper').insertAfter(featuredSwiperClass[i][3] + ' .tvcmsmain-title-wrapper');
        }
    }
});