/**
 * STREFA SUPLEMENTÓW - JS (Fix: jQuery Parsing + Full Lazy Load + Daily Refresh + User Context)
 */

document.addEventListener('DOMContentLoaded', function () {
    
    var $contentArea = $('#suple-content-area');
    var langId = (typeof prestashop !== 'undefined') ? 
                 prestashop.language.id : $('html').attr('lang');
    
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

    // Klucz pamięci z datą I statusem (np. suple_data_1_20251201_u1)
    var storageKey = 'suple_data_' + langId + '_' + dateStr + '_u' + userStatus;

    if ($contentArea.length) {
        $contentArea.css('min-height', '600px'); 
    }

    // --- Logika UI ---
    function initSupleLogic() {
        const responsiveConfig = {
            0: { items: 1 }, 320: { items: 2 }, 576: { items: 2 },
            768: { items: 3 }, 992: { items: 3 }, 1200: { items: 4 }, 1600: { items: 5 }
        };

        function updateLayout() {
            const viewportWidth = window.innerWidth;
            let currentItems = 2;
            const breakpoints = Object.keys(responsiveConfig).map(Number).sort((a, b) => a - b);
            
            for (let bp of breakpoints) {
                if (viewportWidth >= bp) currentItems = responsiveConfig[bp].items;
            }

            $('.suple-grid').each(function() {
                $(this).css('--suple-cols', currentItems);
                const limit = currentItems * 2;
                $(this).find('.suple-item').each(function(index) {
                    if (index < limit) $(this).css('display', 'block');
                    else $(this).css('display', 'none');
                });
            });
        }

        updateLayout();
        window.addEventListener('resize', updateLayout);

        // --- KLIKNIĘCIA ---
        $(document).off('click', '.suple-menu-item')
            .on('click', '.suple-menu-item', function() {
            
            var targetId = $(this).data('target'); 
            var tabKey = targetId.replace('suple-tab-', '');

            // UI
            $('.suple-menu-item').removeClass('active');
            $(this).addClass('active');

            $('.suple-pane').removeClass('active');
            var $targetPane = $('#' + targetId);
            $targetPane.addClass('active');

            if ($(window).width() < 992) {
                var $header = $('.suple-header');
                if ($header.length) {
                    $('html, body').animate({ scrollTop: $header.offset().top - 20 }, 400);
                }
            }

            // POBIERANIE (Lazy Load)
            if ($targetPane.find('.suple-lazy-skeleton').length > 0) {
                loadSpecificTab(tabKey, targetId);
            } else {
                setTimeout(updateLayout, 10);
                saveCurrentState();
            }
        });
    }

    // --- AJAX (Z POPRAWKĄ PARSOWANIA) ---
    function loadSpecificTab(tabKey, targetDomId) {
        if (typeof suple_ajax_url === 'undefined') return;

        $.ajax({
            type: 'POST',
            url: suple_ajax_url,
            data: { ajax: true, suple_tab: tabKey },
            success: function(data) {
                if (data && data.length > 50) {
                    
                    // --- FIX: Pakujemy dane w DIV ---
                    var $response = $('<div>' + data + '</div>');
                    // ---------------------------------

                    var newGridHtml = $response.find('#' + targetDomId + ' .suple-grid').html();
                    
                    if (newGridHtml) {
                        var $pane = $('#' + targetDomId);
                        $pane.removeClass('suple-lazy-skeleton');
                        $pane.html('<div class="suple-grid products">' + newGridHtml + '</div>');
                    }

                    initSupleLogic();
                    saveCurrentState();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                $('#' + targetDomId + ' .suple-loading-text').text('Błąd ładowania. Odśwież stronę.');
            }
        });
    }

    function saveCurrentState() {
        if ($contentArea.length) {
            localStorage.setItem(storageKey, $contentArea.html());
        }
    }

    // --- START (SCROLL TRIGGER) ---
    function loadSuple() {
        if (!$contentArea.length) return;

        // Szukamy klucza (Teraz klucz zawiera datę I STATUS logowania)
        var cachedData = localStorage.getItem(storageKey);

        if (cachedData && cachedData.length > 50) {
            // MAMY CACHE (pasujący do daty i statusu usera)
            $contentArea.html(cachedData);
            initSupleLogic();
        } else {
            // BRAK CACHE (nowy dzień lub zmiana statusu logowania) -> POBIERZ
            var $firstBtn = $('.suple-menu-item.active').first();
            if ($firstBtn.length) {
                var targetId = $firstBtn.data('target');
                var tabKey = targetId.replace('suple-tab-', '');
                
                loadSpecificTab(tabKey, targetId);
            }
        }
    }

    // Observer
    if ($contentArea.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) {
                    loadSuple();
                    observer.disconnect();
                }
            }, { rootMargin: '200px' });
            observer.observe($contentArea[0]);
        } else {
            loadSuple();
        }
    }
});