/**
 * Nieskończone Produkty - JS Fixed
 */
document.addEventListener('DOMContentLoaded', function () {
    
    var container = document.getElementById('np-infinite-scroll');
    if (!container) return;

    var grid = document.getElementById('np-grid-container');
    var loader = document.getElementById('np-loader');
    var endMsg = document.getElementById('np-end-message');
    
    // Zmienne stanu
    var currentPage = 1;
    var isLoading = false;
    var hasMore = true;
    
    // Pobieramy ID kategorii (to szerokie, obliczone przez PHP) z atrybutu HTML
    var catId = container.getAttribute('data-cat-id');

    function loadMoreProducts() {
        if (isLoading || !hasMore) return;

        // Sprawdzamy czy URL jest zdefiniowany (powinien być, bo przenieśliśmy go do hookHeader)
        if (typeof np_ajax_url === 'undefined') {
            console.error('np_ajax_url is undefined');
            return;
        }

        isLoading = true;
        loader.style.display = 'flex';

        var nextPage = currentPage + 1;

        $.ajax({
            url: np_ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                page: nextPage,
                id_category: catId,     // Tu wysyłamy ID szerokiej kategorii
                id_product: np_id_product
            },
            success: function (response) {
                if (response.html) {
                    var currentScrollPos = $(window).scrollTop();
                    
                    // Dodaj produkty
                    $(grid).append(response.html);
                    currentPage++;
                    
                    // Odśwież eventy Presty (koszyk, szybki podgląd)
                    if (typeof prestashop !== 'undefined') {
                        prestashop.emit('updateProductList', {});
                        $('html, body').stop(true, true);
                        $(window).scrollTop(currentScrollPos);
                    }
                }

                if (response.has_more === false) {
                    hasMore = false;
                    endMsg.style.display = 'block';
                }
            },
            error: function () {
                hasMore = false; 
            },
            complete: function () {
                isLoading = false;
                loader.style.display = 'none';
            }
        });
    }

    var scrollTimeout;
    window.addEventListener('scroll', function () {
        if (scrollTimeout) clearTimeout(scrollTimeout);
        
        scrollTimeout = setTimeout(function () {
            if (!hasMore || isLoading) return;

            var rect = container.getBoundingClientRect();
            var windowHeight = window.innerHeight;

            // Triggerujemy ładowanie gdy dół kontenera jest blisko
            if (rect.bottom <= windowHeight + 600) {
                loadMoreProducts();
            }
        }, 100);
    });
});