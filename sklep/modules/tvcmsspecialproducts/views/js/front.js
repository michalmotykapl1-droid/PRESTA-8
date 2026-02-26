/**
 * STREFA OKAZJI - JS (Full Lazy Load + Split TPL + Daily Refresh + User Context)
 */

jQuery(document).ready(function($) {

    var $contentArea = $('#special-content-area');
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

    // Klucz pamięci z datą I statusem (np. special_data_1_20251201_u1)
    var storageKey = 'special_data_' + langId + '_' + dateStr + '_u' + userStatus;

    // Konfiguracja Gridu (Oryginalna)
    const saleGridConfig = { desktop: 5, laptop: 4, tablet: 3, mobile: 2 };
    const shortGridConfig = { desktop: 1, laptop: 1, tablet: 2, mobile: 1 };

    // --- Funkcja Inicjująca Logikę (Po załadowaniu) ---
    function initSpecialLogic() {
        const gridSale = document.querySelector('.special-grid-sale');
        const gridShort = document.querySelector('.special-grid-short');

        function updateSpecialColumns() {
            const width = window.innerWidth;
            
            if (gridSale) {
                let cols = saleGridConfig.desktop;
                if (width < 576) cols = 2;
                else if (width < 768) cols = saleGridConfig.mobile;
                else if (width < 992) cols = saleGridConfig.tablet;
                else if (width < 1400) cols = saleGridConfig.laptop;
                gridSale.style.setProperty('--sale-cols', cols);
            }

            if (gridShort) {
                let cols = shortGridConfig.desktop;
                if (width < 576) cols = 1; 
                else if (width < 992) cols = shortGridConfig.tablet;
                else cols = shortGridConfig.desktop;
                gridShort.style.setProperty('--short-cols', cols);
            }
        }
        
        updateSpecialColumns();
        window.addEventListener('resize', updateSpecialColumns);
        startCountdown();
    }

    function startCountdown() {
        const timerElements = document.querySelectorAll('.daily-countdown');
        if (timerElements.length === 0) return;
        function updateTime() {
            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setHours(24, 0, 0, 0);
            const diff = tomorrow - now;
            const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const m = Math.floor((diff / (1000 * 60)) % 60);
            const s = Math.floor((diff / 1000) % 60);
            timerElements.forEach(el => {
                el.innerText = (h < 10 ? "0" + h : h) + "h " + (m < 10 ? "0" + m : m) + "m " + (s < 10 ? "0" + s : s) + "s";
            });
        }
        updateTime();
        setInterval(updateTime, 1000);
    }

    // --- AJAX ---
    function loadSpecialAjax() {
        if (typeof special_ajax_url === 'undefined') return;

        $.ajax({
            type: 'POST',
            url: special_ajax_url,
            data: { ajax: true },
            success: function(data) {
                // Sprawdzamy czy dane nie są puste
                if (data && data.length > 50) {
                    
                    // FIX PARSOWANIA
                    var $response = $('<div>' + data + '</div>');
                    // Szukamy głównego kontenera w odpowiedzi
                    var newContent = $response.find('#special-content-area').html();
                    
                    // Fallback, jeśli struktura jest inna
                    if (!newContent) {
                         newContent = $response.find('.special-dual-layout').parent().html(); 
                         // Lub po prostu cała odpowiedź jeśli to czysty HTML z TPL data
                         if (!newContent && $response.find('.special-dual-layout').length > 0) {
                             newContent = $response.html();
                         }
                    }
                    
                    if (newContent) {
                        $contentArea.html(newContent);
                        $contentArea.removeClass('special-lazy-skeleton');
                        
                        // Uruchamiamy logikę (Timer, Grid)
                        initSpecialLogic();
                        
                        // Cache (z datą i statusem!)
                        if ($contentArea.length) {
                            localStorage.setItem(storageKey, newContent);
                        }
                    }
                }
            },
            error: function() {
                $('.special-loading-text').text('Błąd ładowania.');
            }
        });
    }

    // --- START (SCROLL) ---
    function loadSpecial() {
        if (!$contentArea.length) return;

        // Sprawdź cache (szukamy klucza z dzisiejszą datą I statusem usera)
        var cachedData = localStorage.getItem(storageKey);

        if (cachedData && cachedData.length > 50) {
            // Cache hit (Mamy dane z dzisiaj pasujące do statusu)
            $contentArea.html(cachedData);
            $contentArea.removeClass('special-lazy-skeleton');
            initSpecialLogic();
        } else {
            // Cache miss (Brak lub stary) -> AJAX
            loadSpecialAjax();
        }
    }

    // Observer
    if ($contentArea.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) {
                    loadSpecial();
                    observer.disconnect();
                }
            }, { rootMargin: '200px' });
            observer.observe($contentArea[0]);
        } else {
            loadSpecial();
        }
    }
});