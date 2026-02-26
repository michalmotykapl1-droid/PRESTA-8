/**
 * FRESH PRODUKTY – JS (Full Lazy Load + LocalStorage + Daily Refresh + User Context)
 */
document.addEventListener('DOMContentLoaded', function () {
    
    var $contentArea = $('#fresh-content-area'); // ID w TPL
    var langId = (typeof prestashop !== 'undefined') ? prestashop.language.id : $('html').attr('lang');
    
    // --- 1. WYKRYWANIE STATUSU ZALOGOWANIA ---
    // 0 = Gość, 1 = Zalogowany
    var userStatus = 0;
    if (typeof prestashop !== 'undefined' && prestashop.customer && prestashop.customer.is_logged) {
        userStatus = 1;
    }

    // --- 2. GENEROWANIE KLUCZA Z DATĄ I STATUSEM ---
    var now = new Date();
    var dateStr = now.getFullYear() + 
                  ('0' + (now.getMonth() + 1)).slice(-2) + 
                  ('0' + now.getDate()).slice(-2);

    // Klucz pamięci z datą I statusem (np. fresh_data_1_20251201_u1)
    var storageKey = 'fresh_data_' + langId + '_' + dateStr + '_u' + userStatus;

    // --- Logika Gridu (RWD) ---
    function initFreshGrid() {
        const gridContainer = document.querySelector('.fresh-dense-grid');
        if (!gridContainer) return;

        const freshGridConfig = {
            desktop: 5, laptop: 4, tablet: 3, mobile: 2
        };

        function updateFreshColumns() {
            const width = window.innerWidth;
            let columns = freshGridConfig.desktop;

            if (width < 576) columns = freshGridConfig.mobile;
            else if (width < 992) columns = freshGridConfig.tablet;
            else if (width < 1400) columns = freshGridConfig.laptop;

            gridContainer.style.setProperty('--fresh-cols', columns);
        }

        updateFreshColumns();
        window.addEventListener('resize', updateFreshColumns);
    }

    // --- AJAX ---
    function loadFreshAjax() {
        if (typeof fresh_ajax_url === 'undefined') return;

        $.ajax({
            type: 'POST',
            url: fresh_ajax_url,
            data: { ajax: true },
            success: function(data) {
                if (data && data.length > 50) {
                    
                    // FIX Parsowania
                    var $response = $('<div>' + data + '</div>');
                    // Pobieramy zawartość layoutu (Hero + Grid)
                    var newContent = $response.find('.fresh-hero-layout').parent().html(); 
                    
                    // Jeśli struktura jest inna, szukamy po prostu .fresh-hero-layout
                    if(!newContent && $response.find('.fresh-hero-layout').length > 0) {
                        newContent = $response.find('.fresh-hero-layout')[0].outerHTML;
                    }

                    if (newContent) {
                        $contentArea.html(newContent);
                        $contentArea.removeClass('fresh-lazy-skeleton');
                        
                        initFreshGrid();
                        
                        // Zapisz do cache (z nowym kluczem daty i statusu)
                        if ($contentArea.length) {
                            localStorage.setItem(storageKey, $contentArea.html());
                        }
                    }
                }
            },
            error: function() {
                $('.fresh-loading-text').text('Błąd ładowania.');
            }
        });
    }

    // --- START ---
    function loadFresh() {
        if (!$contentArea.length) return;

        // Sprawdź cache (szukamy klucza z dzisiejszą datą I statusem usera)
        var cachedData = localStorage.getItem(storageKey);

        if (cachedData && cachedData.length > 50) {
            // Mamy dane z DZIŚ pasujące do statusu
            $contentArea.html(cachedData);
            initFreshGrid();
        } else {
            // Brak cache (lub stary) -> Pobierz AJAXem
            loadFreshAjax();
        }
    }

    // Observer
    if ($contentArea.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) {
                    loadFresh();
                    observer.disconnect();
                }
            }, { rootMargin: '200px' });
            observer.observe($contentArea[0]);
        } else {
            loadFresh();
        }
    }
});