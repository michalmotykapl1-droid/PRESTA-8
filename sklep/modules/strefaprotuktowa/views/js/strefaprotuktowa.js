/**
 * STREFA PROTUKTOWA - JS (Original Stable Logic + Daily Auto-Refresh + User Context)
 */

jQuery(document).ready(function($) {

    var $contentArea = $('#strefa-content-area');
    var langId = (typeof prestashop !== 'undefined') ? 
                 prestashop.language.id : $('html').attr('lang');
    
    // --- 1. WYKRYWANIE STATUSU ZALOGOWANIA ---
    // Sprawdzamy, czy klient jest zalogowany. 
    // Dzięki temu stworzymy inny klucz dla gościa (0), a inny dla klienta (1).
    var userStatus = 0;
    if (typeof prestashop !== 'undefined' && prestashop.customer && prestashop.customer.is_logged) {
        userStatus = 1;
    }

    // --- 2. GENEROWANIE KLUCZA Z DATĄ I STATUSEM ---
    var now = new Date();
    var dateStr = now.getFullYear() + 
                  ('0' + (now.getMonth() + 1)).slice(-2) + 
                  ('0' + now.getDate()).slice(-2);

    // KLUCZOWA ZMIANA: Dodajemy "_u" + userStatus do nazwy klucza.
    // Przykład: strefa_init_1_20251201_u0 (dla gościa)
    // Przykład: strefa_init_1_20251201_u1 (dla zalogowanego)
    var storageKey = 'strefa_init_' + langId + '_' + dateStr + '_u' + userStatus;

    if ($contentArea.length) {
        $contentArea.css('min-height', '550px');
    }

    // --- Logika UI (Grid, Zakładki, Mobile) ---
    function initStrefaLogic() {
        const responsiveConfig = {
            0: { items: 1 }, 320: { items: 2 }, 400: { items: 2 },
            650: { items: 2 }, 768: { items: 3 }, 992: { items: 3 },
            1200: { items: 4 }, 1400: { items: 4 }, 1600: { items: 5 }
        };

        function updateLayout() {
            const viewportWidth = $(window).width();
            let currentItems = 2;
            const breakpoints = Object.keys(responsiveConfig)
                .map(Number).sort((a, b) => a - b);
            
            for (let bp of breakpoints) {
                if (viewportWidth >= bp) currentItems = responsiveConfig[bp].items;
            }

            $('.strefa-grid').each(function() {
                $(this).css('--strefa-cols', currentItems);
                const limit = currentItems * 2;
                $(this).find('.strefa-item').each(function(index) {
                    if (index < limit) $(this).css('display', 'block');
                    else $(this).css('display', 'none');
                });
            });
        }

        updateLayout();
        $(window).resize(updateLayout);

        // KLIKNIĘCIE W ZAKŁADKĘ
        $(document).off('click', '.strefa-tab-btn')
            .on('click', '.strefa-tab-btn', function() {
            
            var targetId = $(this).data('target'); 
            var tabKey = targetId.replace('strefa-tab-', '');

            // Zmiana klas aktywnych (UI)
            $('.strefa-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.strefa-pane').removeClass('active');
            var $targetPane = $('#' + targetId);
            $targetPane.addClass('active');

            $('.strefa-deal-container').removeClass('active');
            $('.strefa-deal-container[data-deal-target="' + 
                targetId + '"]').addClass('active');

            $('.strefa-more-link').removeClass('active');
            $('.strefa-more-link[data-link-target="' + 
                targetId + '"]').addClass('active');

            // Mobile Scroll Fix
            if ($(window).width() < 992) {
                var $header = $('.strefa-tabs-header');
                if ($header.length) {
                    $('html, body').animate({
                        scrollTop: $header.offset().top - 80 
                    }, 400);
                }
            }

            // LAZY LOAD LOGIC:
            if ($targetPane.find('.strefa-lazy-skeleton').length > 0) {
                loadSpecificTab(tabKey, targetId);
            } else {
                setTimeout(function() { updateLayout(); }, 50);
                saveCurrentState();
            }
        });
    }

    // --- Dociąganie zakładki (Lazy Load + SAVE TO CACHE) ---
    function loadSpecificTab(tabKey, targetDomId) {
        var dealSelector = '.strefa-deal-container[data-deal-target="' + 
                            targetDomId + '"] .strefa-deal-content';
        $(dealSelector).html('<div class="strefa-spinner"></div>');

        $.ajax({
            type: 'POST',
            url: strefa_ajax_url,
            data: { ajax: true, strefa_tab: tabKey },
            success: function(data) {
                if (data && data.length > 50) {
                    var $response = $(data);
                    
                    var newGridHtml = $response.find('#' + targetDomId + ' .strefa-grid').html();
                    if (newGridHtml) {
                        var $pane = $('#' + targetDomId);
                        $pane.removeClass('strefa-lazy-skeleton');
                        $pane.html('<div class="strefa-grid products">' + 
                                   newGridHtml + '</div>');
                    }

                    var dealTargetAttr = 'strefa-tab-' + tabKey;
                    var newDealHtml = $response.find('.strefa-deal-container[data-deal-target="' + 
                                                      dealTargetAttr + '"] .strefa-deal-content').html();
                    if (newDealHtml) {
                        $('.strefa-deal-container[data-deal-target="' + 
                          dealTargetAttr + '"] .strefa-deal-content').html(newDealHtml);
                    }

                    initStrefaLogic();
                    saveCurrentState();
                }
            }
        });
    }

    // Pomocnicza funkcja zapisu
    function saveCurrentState() {
        if ($contentArea.length) {
            var fullHtml = $contentArea.html();
            localStorage.setItem(storageKey, fullHtml);
        }
    }

    // --- Główne ładowanie (Init) ---
    function loadStrefa() {
        if (!$contentArea.length) return;

        // Szukamy klucza, który zawiera TEŻ status zalogowania
        var cachedData = localStorage.getItem(storageKey);

        if (cachedData && cachedData.length > 50) {
            // Mamy dane pasujące do dzisiejszej daty I statusu zalogowania
            $contentArea.html(cachedData);
            initStrefaLogic();
            
            // Cicha aktualizacja w tle
            fetchData(true);
        } else {
            // Nie ma cache dla tego stanu (np. klient właśnie się zalogował,
            // więc klucz ..._u1 jest pusty, mimo że ..._u0 może istnieć)
            // Pobieramy świeże dane z serwera.
            fetchData(false);
        }
    }

    function fetchData(silentMode) {
        $.ajax({
            type: 'POST',
            url: strefa_ajax_url,
            data: { ajax: true },
            success: function(data) {
                if (data && data.length > 50) {
                    
                    if (!silentMode) {
                        var currentH = $contentArea.height();
                        $contentArea.css('height', currentH + 'px');

                        $contentArea.html(data);
                        initStrefaLogic();

                        setTimeout(function(){ $contentArea.css('height', 'auto'); }, 200);
                        
                        saveCurrentState();
                    } else {
                        // silent update
                    }
                }
            },
            error: function() { console.error("Strefa Connection failed"); }
        });
    }

    // Observer
    if ($contentArea.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) {
                    loadStrefa();
                    observer.disconnect();
                }
            }, { rootMargin: '200px' });
            observer.observe($contentArea[0]);
        } else {
            loadStrefa();
        }
    }
});