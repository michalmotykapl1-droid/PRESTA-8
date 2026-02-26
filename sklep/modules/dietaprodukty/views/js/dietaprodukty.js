/**
 * DIETA PRODUKTY – JS (Fix: Force Load + Daily Refresh + User Context)
 */

document.addEventListener('DOMContentLoaded', function () {
    
    var $contentArea = $('#dieta-content-area');
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

    // Klucz pamięci z datą I statusem (np. dieta_data_1_20251201_u1)
    var storageKey = 'dieta_data_' + langId + '_' + dateStr + '_u' + userStatus;

    if ($contentArea.length) {
        $contentArea.css('min-height', '500px'); 
    }

    // --- Logika Gridu (RWD) ---
    const gridConfig = { desktop: 5, laptop: 4, tablet: 3, mobile: 2 };

    function initGridControl() {
        const gridContainers = document.querySelectorAll('.diet-products-grid');
        
        function updateColumns() {
            const width = window.innerWidth;
            let columns = gridConfig.desktop;

            if (width < 768) columns = gridConfig.mobile;
            else if (width < 992) columns = gridConfig.tablet;
            else if (width < 1400) columns = gridConfig.laptop;

            gridContainers.forEach(grid => {
                grid.style.setProperty('--grid-cols', columns);
            });
        }
        updateColumns();
        window.addEventListener('resize', updateColumns);
    }

    // --- Obsługa Zakładek ---
    function initDietaTabs() {
        $(document).off('click', '.dieta-nav-item')
            .on('click', '.dieta-nav-item', function() {
            
            var $this = $(this);
            var targetId = $this.data('target'); 
            var tabKey = targetId.replace('tab-', '');

            // UI
            $('.dieta-nav-item').removeClass('active');
            $this.addClass('active');

            $('.dieta-tab-pane').removeClass('active');
            var $targetPane = $('#' + targetId);
            $targetPane.addClass('active');

            // === LOGIKA LAZY LOAD (Z POPRAWKĄ FORCE LOAD) ===
            var hasSkeleton = $targetPane.find('.dieta-lazy-skeleton').length > 0;
            var hasProducts = $targetPane.find('.product-miniature-wrapper').length > 0;

            if (hasSkeleton || !hasProducts) {
                loadSpecificTab(tabKey, targetId);
            } else {
                saveCurrentState();
            }
        });
    }

    // --- AJAX ---
    function loadSpecificTab(tabKey, targetDomId) {
        if (typeof dieta_ajax_url === 'undefined') return;

        // Pokaż loader jeśli go nie ma
        var $pane = $('#' + targetDomId);
        if ($pane.find('.dieta-spinner').length === 0 && $pane.find('.product-miniature-wrapper').length === 0) {
             $pane.html('<div class="dieta-loading-overlay"><div class="dieta-spinner"></div><span class="dieta-loading-text">Ładowanie...</span></div>');
        }

        $.ajax({
            type: 'POST',
            url: dieta_ajax_url,
            data: { ajax: true, dieta_tab: tabKey },
            success: function(data) {
                if (data && data.length > 50) {
                    
                    // Parsowanie
                    var $response = $('<div>' + data + '</div>');
                    var newGridHtml = $response.find('#' + targetDomId + ' .diet-scroll-container').html();
                    
                    if (newGridHtml) {
                        // Wstrzyknięcie
                        $('#' + targetDomId).find('.diet-scroll-container').html(newGridHtml);
                        
                        // Czyszczenie klas
                        $('#' + targetDomId).removeClass('dieta-lazy-skeleton'); 
                        
                        initGridControl();
                        saveCurrentState();
                    }
                }
            },
            error: function() {
                $('#' + targetDomId).html('<p class="alert alert-danger">Błąd połączenia.</p>');
            }
        });
    }

    function saveCurrentState() {
        if ($contentArea.length) {
            localStorage.setItem(storageKey, $contentArea.html());
        }
    }

    // --- START ---
    function loadDieta() {
        if (!$contentArea.length) return;

        // Sprawdź cache (z datą i statusem)
        var cachedData = localStorage.getItem(storageKey);

        if (cachedData && cachedData.length > 50) {
            // Mamy dane z DZIŚ pasujące do statusu
            $contentArea.html(cachedData);
            initGridControl();
            initDietaTabs();
        } else {
            // Brak cache (lub stary) -> Pobierz pierwszą zakładkę
            var $firstBtn = $('.dieta-nav-item.active').first();
            if ($firstBtn.length) {
                var targetId = $firstBtn.data('target');
                var tabKey = targetId.replace('tab-', '');
                
                loadSpecificTab(tabKey, targetId);
                initGridControl();
                initDietaTabs();
            }
        }
    }

    // Observer
    if ($contentArea.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) {
                    loadDieta();
                    observer.disconnect();
                }
            }, { rootMargin: '200px' });
            observer.observe($contentArea[0]);
        } else {
            loadDieta();
        }
    }
});