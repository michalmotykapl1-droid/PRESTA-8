/**
 * STREFA CZYSTOŚCI - JS (Mobile Swipe + Full Lazy Load + Daily Refresh + User Context)
 */
document.addEventListener('DOMContentLoaded', function () {
    
    var $contentArea = $('#czystosc-content-area');
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

    // Klucz pamięci z datą ORAZ statusem (np. czystosc_data_1_20251201_u1)
    var storageKey = 'czystosc_data_' + langId + '_' + dateStr + '_u' + userStatus;

    // --- Logika Gridu (RWD + Mobile Slider Check) ---
    function initCzystoscGrid() {
        const gridContainers = document.querySelectorAll('.czystosc-grid');
        if(gridContainers.length === 0) return;

        // Konfiguracja dla Desktopu (na mobile decyduje CSS flex)
        const responsiveConfig = {
            0: { items: 1 }, 320: { items: 2 }, 576: { items: 2 },
            768: { items: 3 }, 992: { items: 3 }, 1200: { items: 3 }, 
            1400: { items: 5 }, 1800: { items: 5 }
        };

        function updateLayout() {
            const w = window.innerWidth;
            let currentItems = 2; 
            const breakpoints = Object.keys(responsiveConfig).map(Number).sort((a, b) => a - b);
            for (let bp of breakpoints) {
                if (w >= bp) currentItems = responsiveConfig[bp].items;
            }

            gridContainers.forEach(grid => {
                // Ustawiamy zmienną CSS (dla desktopu)
                grid.style.setProperty('--czystosc-cols', currentItems);
                
                const products = grid.querySelectorAll('.czystosc-item');
                
                // === LOGIKA WYŚWIETLANIA ===
                if (w < 992) {
                    // MOBILE / TABLET: Pokazujemy WSZYSTKO (slider CSS to obsłuży)
                    products.forEach(prod => {
                        prod.style.display = 'block';
                    });
                } else {
                    // DESKTOP: Limitujemy do 1 rzędu
                    const limit = currentItems * 1; 
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

    // --- Funkcja Ładująca AJAX (Z POPRAWKĄ PARSOWANIA) ---
    function loadCzystoscAjax() {
        if (typeof czystosc_ajax_url === 'undefined') return;

        $.ajax({
            type: 'POST',
            url: czystosc_ajax_url,
            data: { ajax: true },
            success: function(data) {
                if (data && data.length > 50) {
                    
                    // FIX PARSOWANIA
                    var $response = $('<div>' + data + '</div>');
                    var newGridHtml = $response.find('.czystosc-grid').html();
                    
                    if (newGridHtml) {
                        // Podmieniamy zawartość loadera na produkty
                        var $grid = $('.czystosc-grid.czystosc-lazy-skeleton');
                        $grid.removeClass('czystosc-lazy-skeleton');
                        $grid.html(newGridHtml);
                        
                        // Inicjujemy RWD
                        initCzystoscGrid();
                        
                        // Zapisz do cache (z nowym kluczem daty i statusu)
                        if ($contentArea.length) {
                            localStorage.setItem(storageKey, $contentArea.html());
                        }
                    }
                }
            },
            error: function() {
                $('.czystosc-loading-text').text('Błąd ładowania.');
            }
        });
    }

    // --- START (Scroll Trigger) ---
    function loadCzystosc() {
        if (!$contentArea.length) return;

        // Sprawdź cache (szukamy klucza z dzisiejszą datą I statusem usera)
        var cachedData = localStorage.getItem(storageKey);

        if (cachedData && cachedData.length > 50) {
            // Mamy dane z DZIŚ pasujące do statusu (Gość/Zalogowany)
            $contentArea.html(cachedData);
            initCzystoscGrid();
        } else {
            // Brak cache (nowy dzień lub zmiana statusu logowania) -> Pobierz AJAXem
            loadCzystoscAjax();
        }
    }

    // Observer
    if ($contentArea.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) {
                    loadCzystosc();
                    observer.disconnect();
                }
            }, { rootMargin: '200px' });
            observer.observe($contentArea[0]);
        } else {
            loadCzystosc();
        }
    }
});