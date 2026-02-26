document.addEventListener('DOMContentLoaded', function () {
    var $contentArea = $('#pcslider-content-area');
    if ($contentArea.length === 0) return;

    var pid = $contentArea.attr('data-pid');
    var langId = (typeof prestashop !== 'undefined') ? prestashop.language.id : $('html').attr('lang');
    
    // Cache Key
    var now = new Date();
    var dateStr = now.getFullYear() + ('0' + (now.getMonth() + 1)).slice(-2) + ('0' + now.getDate()).slice(-2);
    var storageKey = 'pcslider_' + pid + '_' + langId + '_' + dateStr;

    function initPCSlider() {
        const slider = document.getElementById('pcSlider');
        // Szukamy strzałek wewnątrz kontenera modułu
        const btnPrev = document.querySelector('.pcslider-container .pc-prev');
        const btnNext = document.querySelector('.pcslider-container .pc-next');

        if (slider && btnPrev && btnNext) {
            const getScrollStep = () => {
                const item = slider.querySelector('.pcslider-item');
                return item ? item.offsetWidth : 300;
            };

            btnNext.addEventListener('click', () => {
                slider.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
            });
            btnPrev.addEventListener('click', () => {
                slider.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
            });
        }
    }

    function loadPCSlider() {
        if (typeof pcslider_ajax_url === 'undefined') return;
        var cid = $contentArea.attr('data-cid');

        $.ajax({
            type: 'POST',
            url: pcslider_ajax_url,
            data: { ajax: true, id_product: pid, id_category: cid },
            success: function(data) {
                if (data && data.length > 50) {
                    var $response = $('<div>' + data + '</div>');
                    var newContent = $response.find('.pcslider-container').parent().html();
                    if (!newContent) newContent = $response.html(); 

                    if (newContent) {
                        $contentArea.html(newContent);
                        $contentArea.removeClass('pcslider-lazy-skeleton');
                        $contentArea.removeAttr('id');
                        initPCSlider();
                        try { localStorage.setItem(storageKey, newContent); } catch(e) {}
                    }
                } else {
                    $('.pcslider-section').hide();
                }
            },
            error: function() { $('.pcslider-section').hide(); }
        });
    }

    function startModule() {
        var cachedData = localStorage.getItem(storageKey);
        if (cachedData && cachedData.length > 50) {
            $contentArea.html(cachedData);
            $contentArea.removeClass('pcslider-lazy-skeleton');
            $contentArea.removeAttr('id');
            initPCSlider();
        } else {
            loadPCSlider();
        }
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                startModule();
                observer.disconnect();
            }
        }, { rootMargin: '200px' });
        observer.observe($contentArea[0]);
    } else {
        startModule();
    }
});