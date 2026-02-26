document.addEventListener('DOMContentLoaded', function () {
    var $contentArea = $('#strefa-content-area');
    if ($contentArea.length === 0) return;

    // Funkcja inicjująca przyciski (slider + refresh)
    function initStrefaEvents() {
        const slider = document.getElementById('strefaSlider');
        const btnPrev = document.querySelector('.strefa-container .strefa-prev');
        const btnNext = document.querySelector('.strefa-container .strefa-next');
        const btnRefresh = document.getElementById('strefaRefreshBtn');

        // Obsługa slidera
        if (slider && btnPrev && btnNext) {
            const getScrollStep = () => {
                const item = slider.querySelector('.strefa-item');
                return item ? item.offsetWidth + 15 : 250; 
            };

            btnNext.addEventListener('click', () => {
                const maxScrollLeft = slider.scrollWidth - slider.clientWidth;
                if (Math.ceil(slider.scrollLeft) >= maxScrollLeft - 5) {
                    slider.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    slider.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
                }
            });

            btnPrev.addEventListener('click', () => {
                if (slider.scrollLeft <= 5) {
                    slider.scrollTo({ left: slider.scrollWidth, behavior: 'smooth' });
                } else {
                    slider.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
                }
            });
        }

        // Obsługa przycisku "Pobierz nowe"
        if (btnRefresh) {
            btnRefresh.addEventListener('click', function(e) {
                e.preventDefault();
                // Dodaj klasę loading (kręcenie ikonką)
                this.classList.add('loading');
                // Załaduj z wymuszeniem odświeżenia
                loadStrefa(true);
            });
        }
    }

    // Funkcja ładująca AJAX
    function loadStrefa(forceRefresh = false) {
        if (typeof strefa_ajax_url === 'undefined') return;

        let ajaxData = { ajax: true };
        if (forceRefresh) {
            ajaxData.refresh = 1; // Parametr dla PHP
        }

        $.ajax({
            type: 'POST',
            url: strefa_ajax_url,
            data: ajaxData,
            success: function(data) {
                if (data && data.length > 50) {
                    var $response = $('<div>' + data + '</div>');
                    var newContent = $response.find('.strefa-container').parent().html();
                    if (!newContent) newContent = $response.html(); 

                    if (newContent) {
                        // Jeśli to refresh, podmieniamy cały kontener .strefa-container wewnątrz wrapper-boxa
                        // LUB podmieniamy contentArea jeśli to pierwsze ładowanie.
                        
                        // Najbezpieczniej podmienić zawartość wrappera, który już mamy w DOM
                        if ($('.strefa-container').length > 0) {
                             $('.strefa-container').parent().html(newContent);
                        } else {
                             $contentArea.replaceWith(newContent);
                        }
                        
                        // Ponownie podpinamy zdarzenia (bo HTML się zmienił)
                        initStrefaEvents();
                    }
                } else {
                    $('.strefa-section').hide();
                }
            },
            error: function() { 
                // W razie błędu zdejmij loading
                const btn = document.getElementById('strefaRefreshBtn');
                if(btn) btn.classList.remove('loading');
            }
        });
    }

    // Intersection Observer
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                loadStrefa(false);
                observer.disconnect();
            }
        }, { rootMargin: '100px' });
        observer.observe($contentArea[0]);
    } else {
        loadStrefa(false);
    }
});