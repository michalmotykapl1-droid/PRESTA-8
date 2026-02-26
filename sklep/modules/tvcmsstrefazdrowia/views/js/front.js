/**
 * 2007-2025 PrestaShop
 * ThemeVolty - Strefa Zdrowia & Logic
 */
var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentNewModule = tvthemename + "_strefa_" + langId;

jQuery(document).ready(function($) {
    storage = $.localStorage;

   /* --- LOGIKA ZMIANY CEN (UNIWERSALNA: FIZJO, URODA, NATURO) --- */
    $('#visitTypeToggle').on('change', function() {
        var isMobile = $(this).is(':checked');
        var fee = (typeof strefaTravelFee !== 'undefined') ? strefaTravelFee : 200;
        
        // Wykrywanie podstrony
        var isBeautyPage = $('.strefa-beauty-page').length > 0;
        var isNaturoPage = $('.strefa-naturo-page').length > 0;
        
        // Ustalanie koloru i selektora
        var activeColor = '#008b8b'; // Domyślny (Fizjo - Turkus)
        var btnSelector = '.physio-cart-btn';
        
        if (isBeautyPage) {
            activeColor = '#d81b60'; // Uroda - Róż
            btnSelector = '.beauty-cart-btn';
        } else if (isNaturoPage) {
            activeColor = '#2e7d32'; // Naturo - Zielony
            btnSelector = '.naturo-cart-btn';
        }

        // Zmiana kolorów etykiet
        if(isMobile) {
            $('.toggle-label.label-stationary').removeClass('active');
            $('.toggle-label.label-mobile').addClass('active').css('color', activeColor); 
        } else {
            $('.toggle-label.label-stationary').addClass('active');
            $('.toggle-label.label-mobile').removeClass('active').css('color', '#888');
        }

        // Przeliczanie cen
        $('.dynamic-price').each(function() {
            var basePrice = parseInt($(this).attr('data-base'));
            
            if (!isNaN(basePrice)) {
                var newPrice = basePrice;
                if (isMobile) {
                    newPrice = basePrice + fee;
                }
                var formattedPrice = newPrice.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
                
                $(this).fadeOut(100, function() {
                    $(this).text(formattedPrice + ' zł').fadeIn(100);
                });
            }
        });
        
        // Zmiana tekstu na przyciskach
        if (isMobile) {
            $(btnSelector).html('ZAMAWIAM Z DOJAZDEM <i class="material-icons">local_shipping</i>');
        } else {
            $(btnSelector).html('DO KOSZYKA <i class="material-icons">shopping_cart</i>');
        }
    });
    /* ----------------------------------------------------- */

    function storageGet(key) {
        return "" + storage.get(currentNewModule + key);
    }

    function storageSet(key, value) {
        storage.set(currentNewModule + key, value);
    }
    
    function storageClear(key) {
        storage.remove(currentNewModule + key);
    }

    var gettvcmsstrefazdrowiaajax = function() {
        if ($('.tvcmsstrefa-product').length) {
            /*****Load Cache*****/
            var data = storageGet("");
            if (data != "null") {
                $('.tvcmsstrefa-product').html(data);
                makeNewProductSlider();
                if (typeof productTime === "function") {
                    productTime(); 
                }
            }
            /*****Load Cache*****/
            
            $.ajax({
                type: 'POST',
                url: gettvcmsstrefazdrowialink,
                success: function(data) {
                    var dataCache = storageGet("");
                    storageSet("", data);
                    if (dataCache === 'null') {
                        $('.tvcmsstrefa-product').html(data);
                        makeNewProductSlider();
                        if (typeof customImgLazyLoad === "function") {
                            customImgLazyLoad('.tvcmsstrefa-product');
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        }
    }

    themevoltyCallEventsPush(gettvcmsstrefazdrowiaajax, null);
    
    function makeNewProductSlider() {
        var newSwiperClass = [
            ['.tvcmsstrefa-product .tvnew-product-wrapper', '.tvcmsnew-next', '.tvcmsnew-prev', '.tvcmsstrefa-product'],
        ];

        for (var i = 0; i < newSwiperClass.length; i++) {
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

            if ($(newSwiperClass[i][0]).attr('data-has-image') == 'true') {
                $(newSwiperClass[i][0]).owlCarousel(owlConfig);
            } else {
                $(newSwiperClass[i][0]).owlCarousel(owlConfig);
            }

            $(newSwiperClass[i][1]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-next').trigger('click');
            });
            
            $(newSwiperClass[i][2]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-prev').trigger('click');
            });

            $(newSwiperClass[i][3] + ' .tv-pagination-wrapper').insertAfter(newSwiperClass[i][3] + ' .tvtab-main-title-wrapper');
        }
    }
});