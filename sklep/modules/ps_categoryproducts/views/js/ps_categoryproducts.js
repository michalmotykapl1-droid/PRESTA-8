/**
 * PS CATEGORY PRODUCTS - JS (Debug + Lazy Load)
 */

document.addEventListener('DOMContentLoaded', function () {
    
    console.log("[CATPRODS] Skrypt załadowany."); // DEBUG

    var $contentArea = $('#catprods-content-area');

    if ($contentArea.length === 0) {
        console.warn("[CATPRODS] Nie znaleziono kontenera #catprods-content-area");
        return;
    }

    // --- 1. Logika Slidera ---
    function initCatProdsSlider() {
        const slider = document.getElementById('catProdsSlider');
        const btnPrev = document.querySelector('.cp-prev');
        const btnNext = document.querySelector('.cp-next');
        const scrollAmount = 300; 

        if (slider && btnPrev && btnNext) {
            console.log("[CATPRODS] Slider zainicjowany.");
            btnNext.addEventListener('click', () => {
                slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
            
            btnPrev.addEventListener('click', () => {
                slider.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
        }
    }

    // --- 2. AJAX ---
    function loadCatProds() {
        if (typeof catprods_ajax_url === 'undefined') {
            console.error("[CATPRODS] Brak URL Ajax!");
            return;
        }
        
        var pid = $contentArea.attr('data-id-product');
        var cid = $contentArea.attr('data-id-category');
        
        console.log("[CATPRODS] Pobieranie dla ID:", pid, "Kat:", cid);

        $.ajax({
            type: 'POST',
            url: catprods_ajax_url,
            data: { ajax: true, id_product: pid, id_category: cid },
            success: function(data) {
                if (data && data.length > 50) {
                    console.log("[CATPRODS] Otrzymano dane.");
                    
                    var $response = $('<div>' + data + '</div>');
                    var newContent = $response.find('.catprods-container').parent().html();
                    if (!newContent) newContent = $response.html(); 

                    if (newContent) {
                        $contentArea.html(newContent);
                        $contentArea.removeClass('catprods-lazy-skeleton');
                        initCatProdsSlider();
                    }
                } else {
                    console.warn("[CATPRODS] Brak produktów w odpowiedzi.");
                    $('.catprods-section').hide();
                }
            },
            error: function(xhr) {
                console.error('[CATPRODS] Błąd ładowania:', xhr.status);
            }
        });
    }

    // --- 3. START (Scroll) ---
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                console.log("[CATPRODS] Sekcja widoczna. Start.");
                loadCatProds();
                observer.disconnect();
            }
        }, { rootMargin: '200px' });
        observer.observe($contentArea[0]);
    } else {
        loadCatProds();
    }
});