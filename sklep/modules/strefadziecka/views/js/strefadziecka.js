/**
 * STREFA DZIECKA - JS (Full Lazy Load + Mobile Swipe + Daily Refresh + User Context)
 */
document.addEventListener('DOMContentLoaded', function () {
    
    var $contentArea = $('#dziecko-content-area');
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

    // Klucz pamięci z datą I statusem (np. dziecko_data_1_20251201_u1)
    var storageKey = 'dziecko_data_' + langId + '_' + dateStr + '_u' + userStatus;

    // --- Logika Gridu (RWD) ---
    function initDzieckoGrid() {
        const gridContainers = document.querySelectorAll('.dziecko-grid');
        if(gridContainers.length === 0) return;

        const responsiveConfig = {
            0:    { items: 1 },
            320:  { items: 2 },
            576:  { items: 2 },
            768:  { items: 3 },
            992:  { items: 3 }, 
            1200: { items: 3 }, 
            1400: { items: 4 }, 
            1600: { items: 4 } 
        };

        function updateLayout() {
            const w = window.innerWidth;
            let currentItems = 2; 
            const breakpoints = Object.keys(responsiveConfig).map(Number).sort((a, b) => a - b);
            for (let bp of breakpoints) {
                if (w >= bp) currentItems = responsiveConfig[bp].items;
            }

            gridContainers.forEach(grid => {
                grid.style.setProperty('--dziecko-cols', currentItems);
                const limit = currentItems * 1; // Desktop: 1 rząd
                
                const products = grid.querySelectorAll('.dziecko-item');
                
                // --- LOGIKA SLIDERA ---
                if (w < 992) {
                    // Mobile: Pokaż wszystko (CSS zrobi slider)
                    products.forEach(prod => prod.style.display = 'block');
                } else {
                    // Desktop: Limituj do 1 rzędu
                    products.forEach((prod, index) => {
                        if (index < limit) prod.style.display = 'block';
                        else prod.style.display = 'none';
                    });
                }
            });
        }
        updateLayout();
        window.addEventListener('resize', updateLayout);
    }

    // --- AJAX (Z POPRAWKĄ PARSOWANIA) ---
    function loadDzieckoAjax() {
        if (typeof dziecko_ajax_url === 'undefined') return;

        $.ajax({
            type: 'POST',
            url: dziecko_ajax_url,
            data: { ajax: true },
            success: function(data) {
                if (data && data.length > 50) {
                    
                    // FIX PARSOWANIA
                    var $response = $('<div>' + data + '</div>');
                    var newGridHtml = $response.find('.dziecko-grid').html();
                    
                    if (newGridHtml) {
                        var $grid = $('.dziecko-grid.dziecko-lazy-skeleton');
                        $grid.removeClass('dziecko-lazy-skeleton');
                        $grid.html(newGridHtml);
                        
                        initDzieckoGrid();
                        
                        // Zapisz do cache (z kluczem uwzględniającym status usera)
                        if ($contentArea.length) {
                            localStorage.setItem(storageKey, $contentArea.html());
                        }
                    }
                }
            },
            error: function() {
                $('.dziecko-loading-text').text('Błąd ładowania.');
            }
        });
    }

    // --- START (SCROLL TRIGGER) ---
    function loadDziecko() {
        if (!$contentArea.length) return;

        // Sprawdź cache (z datą i statusem)
        var cachedData = localStorage.getItem(storageKey);

        if (cachedData && cachedData.length > 50) {
            // Mamy dane z DZIŚ pasujące do statusu (Gość/Zalogowany)
            $contentArea.html(cachedData);
            initDzieckoGrid();
        } else {
            // Brak cache (lub stary) -> Pobierz AJAXem
            loadDzieckoAjax();
        }
    }

    // Observer
    if ($contentArea.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) {
                    loadDziecko();
                    observer.disconnect();
                }
            }, { rootMargin: '200px' });
            observer.observe($contentArea[0]);
        } else {
            loadDziecko();
        }
    }
});