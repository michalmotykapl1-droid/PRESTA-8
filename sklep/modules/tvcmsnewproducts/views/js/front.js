/**
 * 2007-2025 PrestaShop
 * ThemeVolty - New Product (Logic cloned 1:1 from Bestsellers)
 */
var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentNewModule = tvthemename + "_new_" + langId; // Zmieniono klucz na _new_

jQuery(document).ready(function($) {
    storage = $.localStorage;

    function storageGet(key) {
        return "" + storage.get(currentNewModule + key);
    }

    function storageSet(key, value) {
        storage.set(currentNewModule + key, value);
    }
    
    function storageClear(key) {
        storage.remove(currentNewModule + key);
    }

    var gettvcmsnewproductsajax = function() {
        // Sprawdzamy klasę główną (tvcmsnew-product)
        if ($('.tvcmsnew-product').length) {
            /*****Load Cache*****/
            var data = storageGet("");
            if (data != "null") {
                $('.tvcmsnew-product').html(data);
                makeNewProductSlider();
                // Funkcja odliczania czasu (jeśli występuje w Bestsellerach)
                if (typeof productTime === "function") {
                    productTime(); 
                }
            }
            /*****Load Cache*****/
            
            $.ajax({
                type: 'POST',
                url: gettvcmsnewproductslink, // Zmienna z linkiem do New Products
                success: function(data) {
                    var dataCache = storageGet("");
                    storageSet("", data);
                    if (dataCache === 'null') {
                        $('.tvcmsnew-product').html(data);
                        makeNewProductSlider();
                        // Lazy Load obrazków
                        if (typeof customImgLazyLoad === "function") {
                            customImgLazyLoad('.tvcmsnew-product');
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        }
    }

    themevoltyCallEventsPush(gettvcmsnewproductsajax, null);
    
    function makeNewProductSlider() {
        // Definicja klas: [Wrapper, NextBtn, PrevBtn, ParentContainer]
        var newSwiperClass = [
            ['.tvcmsnew-product .tvnew-product-wrapper', '.tvcmsnew-next', '.tvcmsnew-prev', '.tvcmsnew-product'],
        ];

        for (var i = 0; i < newSwiperClass.length; i++) {
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

            if ($(newSwiperClass[i][0]).attr('data-has-image') == 'true') {
                $(newSwiperClass[i][0]).owlCarousel(owlConfig);
            } else {
                $(newSwiperClass[i][0]).owlCarousel(owlConfig);
            }

            // Obsługa kliknięć w strzałki
            $(newSwiperClass[i][1]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-next').trigger('click');
            });
            
            $(newSwiperClass[i][2]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-prev').trigger('click');
            });

            // Przenoszenie paginacji (dokładnie jak w Bestsellerach)
            // Uwaga: Upewnij się, że w TPL masz div o klasie 'tvcmsmain-title-wrapper' lub 'tvtab-main-title-wrapper'
            // Ten kod szuka '.tvcmsmain-title-wrapper' tak jak oryginał, 
            // ale jeśli w TPL masz 'tvtab...', JS może tego nie znaleźć. 
            // W oryginale jest:
            $(newSwiperClass[i][3] + ' .tv-pagination-wrapper').insertAfter(newSwiperClass[i][3] + ' .tvtab-main-title-wrapper');
        }
    }
});