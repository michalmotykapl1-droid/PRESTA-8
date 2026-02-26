/**
 * STREFA FRESH - JS (AJAX Only, No Slider)
 */
var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentCatProdModule = tvthemename + "_catprod_" + langId;
var url = gettvcmscategoryproductlink;

jQuery(document).ready(function($) {
    storage = $.localStorage;

    function storageGet(key) {
        return "" + storage.get(currentCatProdModule + key);
    }

    function storageSet(key, value) {
        storage.set(currentCatProdModule + key, value);
    }

    // --- INICJALIZACJA ---
    // Klikamy w pierwszą zakładkę na start
    $('.fresh-nav').children('li:first-child').addClass('active');
    
    // Pobieramy dane dla pierwszej zakładki
    var firstLink = $('.fresh-nav li.active a');
    if(firstLink.length > 0){
        var category_id = firstLink.attr('data-category-id');
        var num_of_prod = firstLink.attr('data-num-prod');
        
        // Wywołanie AJAX
        var param = {
            "url": url,
            "category_id": category_id,
            "num_of_prod": num_of_prod
        };
        getDataUsingAjax(param);
    }

    // --- OBSŁUGA KLIKNIĘCIA W ZAKŁADKĘ ---
    $('.fresh-nav li').on('click', function(e) {
        e.preventDefault();
        
        // Zmiana klasy active
        $('.fresh-nav li').removeClass('active');
        $(this).addClass('active');
        
        var category_id = $(this).find('a').attr('data-category-id');
        var num_of_prod = $(this).find('a').attr('data-num-prod');

        // Loader
        $('.fresh-products-grid').html('<div class="fresh-loader">Ładowanie...</div>');

        // AJAX
        var param = {
            "url": url,
            "category_id": category_id,
            "num_of_prod": num_of_prod
        };
        getDataUsingAjax(param);
    });

    // --- FUNKCJA POBIERANIA DANYCH ---
    function getDataUsingAjax($param) {
        $url = $param.url;
        $category_id = $param.category_id;
        $num_of_prod = $param.num_of_prod;

        $.ajax({
            type: 'POST',
            url: $url,
            data: 'category_id=' + $category_id + '&num_of_prod=' + $num_of_prod + '&token=' + static_token,
            success: function(data) {
                // Wrzucamy HTML do kontenera siatki
                $('.fresh-products-grid').html(data);
                
                // Ważne: NIE uruchamiamy slidera!
                // Dzięki temu CSS (grid) zadziała poprawnie.
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    }
});