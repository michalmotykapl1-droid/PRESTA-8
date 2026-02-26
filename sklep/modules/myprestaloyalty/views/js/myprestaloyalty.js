/**
 * PrestaShop module created by VEKIA
 * Refactored for BigBio: Live Cart Refresh & Auto-Voucher Logic
 * Fixed: Loader conflict & Added Visual Feedback
 */

document.addEventListener("DOMContentLoaded", function() {
    var $ = jQuery; // Bezpieczne przypisanie jQuery, naprawia konflikty z $

    // 1. MECHANIZM ODŚWIEŻANIA BLOKU LOJALNOŚCIOWEGO
    if (typeof prestashop !== 'undefined') {
        prestashop.on('updatedCart', function (event) {
            // Sprawdzamy, czy zdefiniowano URL
            if (typeof myprestaloyaltyurl !== 'undefined') {
                
                var $box = $('#loyalty_cart_box');
                
                // WIZUALNY EFEKT ŁADOWANIA:
                // Przyciemniamy box na moment aktualizacji, żeby klient wiedział, że coś się dzieje
                if ($box.length > 0) {
                    $box.css({
                        'opacity': '0.5',
                        'transition': 'opacity 0.2s ease'
                    });
                }

                $.ajax({
                    type: "POST",
                    url: myprestaloyaltyurl,
                    success: function (data) {
                        // Jeśli serwer zwrócił dane, podmieniamy box
                        if (data && data.length > 0) {
                            if ($box.length) {
                                $box.replaceWith(data);
                            } else {
                                // Fallback: jeśli boxa nie było, próbujemy go znaleźć w rodzicu (rzadki przypadek)
                                $('#loyalty_cart_box').replaceWith(data);
                            }
                        }
                    },
                    error: function() {
                        // W razie błędu serwera przywracamy pełną widoczność
                        if ($box.length > 0) {
                            $box.css('opacity', '1');
                        }
                    },
                    complete: function() {
                        // Opcjonalnie: upewniamy się, że po wszystkim box jest widoczny (dla pewności)
                        // W przypadku sukcesu replaceWith podmienia element na nowy (z opacity 1), więc to dotyczy tylko błędu
                    }
                });
            }
        });
    }

    // 2. MECHANIZM "UŻYJ KODU" (Działa zawsze, nawet po odświeżeniu AJAX)
    $('body').on('click', '.js-auto-use-code', function(e) {
        e.preventDefault(); 
        var code = $(this).attr('data-code');
        
        // Szukamy pola na kod w koszyku
        var $promoInput = $('input[name="discount_name"]');
        var $collapseBtn = $('.promo-code-button'); // Przycisk "Masz kod promocyjny?"

        // Funkcja wpisująca i zatwierdzająca
        function applyCode() {
            var $input = $('input[name="discount_name"]');
            var $form = $input.closest('form');
            var $btn = $form.find('button[type="submit"]'); 
            
            if ($input.length > 0) {
                $input.val(code); 
                // Efekt wizualny wpisania
                $input.css('background-color', '#e8fbf0').animate({ backgroundColor: '#ffffff' }, 500);
                
                if ($btn.length > 0) {
                    $btn.click();
                } else {
                    $form.submit();
                }
            } else {
                console.warn('MyPrestaLoyalty: Nie znaleziono pola input[name="discount_name"]');
            }
        }

        // Logika otwierania sekcji kodów, jeśli jest zwinięta
        if ($promoInput.length === 0 || !$promoInput.is(':visible')) {
            if ($collapseBtn.length > 0) {
                // Jeśli sekcja jest zwinięta, rozwiń ją
                // Sprawdzamy, czy atrybut aria-expanded nie jest już true (żeby nie klikać 2 razy)
                if ($collapseBtn.attr('aria-expanded') !== 'true') {
                    $collapseBtn.click();
                }
                
                setTimeout(applyCode, 400); // Poczekaj na animację i wpisz
            } else {
                applyCode();
            }
        } else {
            applyCode();
        }
    });
});