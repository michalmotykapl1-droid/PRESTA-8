/**
 * 2007-2025 PrestaShop
 * CLEAN VERSION with LOADER logic + AJAX ABORT (Fixes Race Condition)
 */
$(document).ready(function() {
    // === ZMIENNE ===
    let currentSearchTerm = '';
    let currentCategoryId = 0;
    let searchTimeout;
    
    // NOWA ZMIENNA: Przechowuje aktualne żądanie AJAX
    let currentAjaxRequest = null; 

    // === SELEKTORY ===
    const $input = $('.tvcmssearch-words');
    const $resultsContainer = $('.tvsearch-result');
    const $searchForm = $('.premium-search-form'); 

    // ================================================================
    // 1. OVERLAY
    // ================================================================
    let $overlay = $('<div class="tvsearch-page-overlay"></div>');
    $('body').append($overlay);
    var MOBILE_MAX = 768; 

    function showOverlay() {
        if (window.innerWidth > MOBILE_MAX) {
            $overlay.addClass('is-visible');
            $('body').addClass('tvsearch-scroll-lock');
        }
    }

    function hideOverlay() {
        if (window.innerWidth > MOBILE_MAX) {
            $overlay.removeClass('is-visible');
            $('body').removeClass('tvsearch-scroll-lock');
        }
    }
    
    function startLoading() {
        $searchForm.addClass('is-loading');
    }
    
    function stopLoading() {
        $searchForm.removeClass('is-loading');
    }

    // ================================================================
    // 2. SZUKANIE
    // ================================================================
    function performSearch(searchTerm, categoryId) {
        const term = searchTerm || '';
        currentSearchTerm = term.trim();
        currentCategoryId = categoryId || 0;

        if (currentSearchTerm.length < 3) {
            // Jeśli anulujemy wyszukiwanie (np. skasowano tekst), ubijamy też stare zapytanie
            if (currentAjaxRequest) {
                currentAjaxRequest.abort();
                currentAjaxRequest = null;
            }
            $resultsContainer.hide().empty();
            hideOverlay();
            stopLoading(); 
            return;
        }

        // Start Loadera
        startLoading();
        showOverlay();

        // === KLUCZOWA POPRAWKA: ABORT PREVIOUS REQUEST ===
        // Jeśli istnieje poprzednie zapytanie, które jeszcze nie wróciło - ZABIJA JE.
        // Dzięki temu wyniki dla "CZE" nie nadpiszą wyników dla "CZEKOLADA".
        if (currentAjaxRequest) {
            currentAjaxRequest.abort();
        }

        currentAjaxRequest = $.ajax({
            type: 'POST',
            url: tvcmssearch_ajax_url,
            data: {
                search_words: currentSearchTerm,
                category_id: currentCategoryId,
            },
            success: function(responseHtml) {
                $resultsContainer.html(responseHtml);
                $resultsContainer.show();
                updateVisualsAfterSearch();
                stopLoading(); 
                currentAjaxRequest = null; // Czyścimy zmienną po sukcesie
            },
            error: function(jqXHR) {
                // Ignorujemy błędy wynikające z celowego anulowania (abort)
                if (jqXHR.statusText !== 'abort') {
                    stopLoading(); 
                    hideOverlay();
                }
                // Jeśli to był abort, NIE wyłączamy loadera, bo leci już nowe zapytanie
            }
        });
    }

    // === POZOSTAŁE FUNKCJE (BEZ ZMIAN) ===
    function filterProductsByFeatures() {
        const $products = $resultsContainer.find('.tvsearch-dropdown-wrapper');
        const $checkedFilters = $resultsContainer.find('.tvsearch-feature-filter:checked');
        const selectedFeatureIds = $checkedFilters.map(function() { return $(this).data('id-feature').toString(); }).get();
        
        if (selectedFeatureIds.length > 0) {
            $resultsContainer.find('.tvsearch-reset-filters').show();
        } else if (currentCategoryId == 0) {
            $resultsContainer.find('.tvsearch-reset-filters').hide();
        }

        if (selectedFeatureIds.length === 0) {
            $products.show(); return;
        }

        $products.each(function() {
            const $product = $(this);
            const productFeatureIds = ($product.data('feature-values') || '').toString().split(',');
            const hasAllFeatures = selectedFeatureIds.every(id => productFeatureIds.includes(id));
            if (hasAllFeatures) $product.show(); else $product.hide();
        });
    }

    function updateVisualsAfterSearch() {
        $resultsContainer.find('.tvsearch-category-link').removeClass('active');
        if (currentCategoryId != 0) {
            $resultsContainer.find(`.tvsearch-category-link[data-id-category="${currentCategoryId}"]`).addClass('active');
            $resultsContainer.find('.tvsearch-show-filtered-wrapper').show();
            $resultsContainer.find('.tvsearch-reset-filters').show();
        } else {
            $resultsContainer.find('.tvsearch-show-filtered-wrapper').hide();
            if ($resultsContainer.find('.tvsearch-feature-filter:checked').length === 0) {
                $resultsContainer.find('.tvsearch-reset-filters').hide();
            }
        }
        const $termDisplayWrapper = $resultsContainer.find('.tvsearch-show-all-wrapper');
        const $termDisplay = $resultsContainer.find('.tvsearch-term-display');
        if (currentSearchTerm.length > 0) {
            $termDisplay.text(` "${currentSearchTerm}"`);
            $termDisplayWrapper.show();
        } else {
            $termDisplayWrapper.hide();
        }
    }

    // === LISTENERS ===
    $input.on('input', function() {
        clearTimeout(searchTimeout);
        const val = $(this).val();
        
        // Jeśli wpisujemy, od razu pokaż loader
        if(val.length >= 3) startLoading();
        else stopLoading();

        searchTimeout = setTimeout(() => { performSearch(val, 0); }, 300);
    });

    $(document).on('click', '.tvsearch-category-link', function(e) {
        e.preventDefault();
        const categoryId = $(this).data('id-category');
        if (tvcmssearch_click_mode === 'ajax') {
            performSearch(currentSearchTerm, categoryId);
        } else {
            const categoryUrl = $(this).data('category-url');
            window.location.href = `${categoryUrl}?s=${encodeURIComponent(currentSearchTerm)}`;
        }
    });
    
    $(document).on('change', '.tvsearch-feature-filter', filterProductsByFeatures);
    $(document).on('click', '.tvsearch-reset-filters', function(e) { e.preventDefault(); performSearch(currentSearchTerm, 0); });
    $(document).on('click', '.tvsearch-show-filtered-btn', function() {
        const activeLink = $resultsContainer.find('.tvsearch-category-link.active');
        if (activeLink.length) window.location.href = `${activeLink.data('category-url')}?s=${encodeURIComponent(currentSearchTerm)}`;
    });
    $(document).on('click', '.tvsearch-show-all-results-btn, .tvsearch-show-all-for-term-btn', function() { $('.tvsearch-header-display-wrappper form').submit(); });
    
    $(document).on('click', function(event) {
        const $target = $(event.target);
        if ($target.hasClass('tvsearch-page-overlay') || (!$target.closest('.search-widget').length && !$target.closest('.tvsearch-result').length)) {
            $resultsContainer.hide().empty();
            hideOverlay();
            stopLoading();
        }
    });
});

/* === AJAX PREFILTER (Mobile/Desktop compatibility) === */
if (!window.__tvsearch_prefilter_v10){
window.__tvsearch_prefilter_v10 = 1;
(function(w, $){
  if (!w || !w.jQuery || !$ || !$.ajaxPrefilter) return;
  function isMobile(){
    try{ if (w.matchMedia && w.matchMedia('(max-width: 991.98px)').matches) return true; if (document.querySelector('#tvcmssearch-mobile')) return true; }catch(_){} return false;
  }
  $.ajaxPrefilter(function(options, original){
    try{
      var url = options.url || '';
      var target = (typeof w.tvcmssearch_ajax_url === 'string' && w.tvcmssearch_ajax_url.length) ? w.tvcmssearch_ajax_url : null;
      if (target && url.indexOf(target)!==-1) {
          var dataStr = (typeof options.data === 'string') ? options.data : $.param(original.data);
          if (isMobile() && !/(^|&)mobile=1(&|$)/.test(dataStr)) dataStr += '&mobile=1';
          else if (!isMobile()) dataStr = dataStr.replace(/(^|&)mobile=1(&|$)/, '').replace(/^&+|&+$/g,'');
          options.data = dataStr;
      }
    }catch(e){}
  });
})(window, window.jQuery);
}