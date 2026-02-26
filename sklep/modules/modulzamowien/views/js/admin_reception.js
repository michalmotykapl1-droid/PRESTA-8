/**
 * admin_reception.js - Logika Przyjęcia (Focus na DATĘ + Zabezpieczenie Double-Click + Brak Limitu Max)
 * WERSJA: FINALNA (Spinner + Disable Button + Allow Over-Quantity FIX)
 */

$(document).ready(function() {
    
    // Zabezpieczenie: Pobieranie URL do kontrolera
    function getControllerUrl() {
        var url = $('#mz_reception_controller_url').val();
        return url ? url : 'index.php'; 
    }

    // ============================================================
    // 1. OTWIERANIE GŁÓWNEGO OKNA SKANERA (RESET)
    // ============================================================
    $(document).on('click', '#btn-open-search-modal', function() {
        $('#scanner_input').val('').prop('disabled', false);
        $('#search_msg_container').hide(); 
        $('#search_modal').modal('show');
        
        // Focus na input po otwarciu
        setTimeout(function() {
            $('#scanner_input').focus();
        }, 500);
    });

    // ============================================================
    // 2. OBSŁUGA UI MODALA PRZYJĘCIA (Regał/Kosz)
    // ============================================================
    $(document).on('change', 'input[name="rec_type"]', function() {
        setReceptionMode($(this).val());
    });
    
    $(document).on('click', '#btn_type_regal, #btn_type_kosz', function() {
        var input = $(this).find('input');
        input.prop('checked', true);
        setReceptionMode(input.val());
    });

    function setReceptionMode(mode) {
        // Zapisz wybór w pamięci przeglądarki dla wygody
        localStorage.setItem('mz_last_type', mode);

        if (mode === 'kosz') {
            $('#fg-regal, #fg-polka').hide();
            $('#fg-kosz-nr').slideDown();
            
            $('#btn_type_kosz').addClass('btn-info active').removeClass('btn-default');
            $('#btn_type_regal').removeClass('btn-info active').addClass('btn-default');
        } else {
            $('#fg-kosz-nr').hide();
            $('#fg-regal, #fg-polka').slideDown();
            
            $('#btn_type_regal').addClass('btn-info active').removeClass('btn-default');
            $('#btn_type_kosz').removeClass('btn-info active').addClass('btn-default');
        }
    }

    // ============================================================
    // 3. LOGIKA OTWIERANIA MODALA PRZYJĘCIA
    // ============================================================
    // Wywoływana np. z tabeli Nadwyżek (przycisk "PRZYJMIJ")
    $(document).on('click', '.btn-reception-open', function(e) {
        e.preventDefault();
        
        var ean = $(this).data('ean');
        var name = $(this).data('name'); // Opcjonalnie, jeśli jest w atrybucie
        
        // Jeśli mamy EAN, sprawdzamy w bazie (aby pobrać pełne dane)
        // Jeśli nie, otwieramy pusty lub z tym co mamy
        
        $.ajax({
            url: getControllerUrl() + '&action=checkSurplus',
            type: 'POST',
            data: { ean: ean },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    openReceptionModal(res.product);
                } else {
                    // Fallback jeśli nie znaleziono w bazie, ale mamy dane z przycisku
                    openReceptionModal({
                        ean: ean,
                        name: name || 'Nieznany produkt',
                        qty: 1
                    });
                }
            },
            error: function() { 
                alert('Błąd połączenia podczas pobierania danych produktu.'); 
            }
        });
    });

    // Funkcja wypełniająca i otwierająca modal
    function openReceptionModal(product) {
        // Wypełnianie danych widocznych
        $('#rec_prod_name').text(product.name);
        $('#rec_prod_ean').text(product.ean); // To jest EAN widoczny
        $('#rec_prod_max').text(product.qty);
        
        // Ustawienie wartości domyślnej (z bazy)
        var $qtyInput = $('#rec_qty');
        $qtyInput.val(product.qty); 
        
        // --- POPRAWKA KLUCZOWA: USUŃ OGRANICZENIE MAX I TOUCHSPIN ---
        // 1. Ustawiamy atrybut HTML na bardzo dużą liczbę
        $qtyInput.attr('max', 999999);
        $qtyInput.prop('max', 999999);
        
        // 2. Jeśli PrestaShop używa pluginu TouchSpin (przyciski +/-),
        // musimy go "zniszczyć" lub zaktualizować, aby puścił blokadę.
        try {
            $qtyInput.trigger("touchspin.destroy"); // To zmieni input w zwykłe pole tekstowe
        } catch(err) {
            // Ignorujemy błąd jeśli pluginu nie ma
        }
        
        // Data ważności (Domyślnie rok do przodu)
        var nextYear = new Date(); 
        nextYear.setFullYear(nextYear.getFullYear() + 1);
        try { 
            $('#rec_expiry').val(nextYear.toISOString().slice(0,10)); 
        } catch(e){}
        
        // Przywracanie ostatnich ustawień (Lokalizacja)
        var lastType = localStorage.getItem('mz_last_type') || 'regal';
        $('input[name="rec_type"][value="'+lastType+'"]').prop('checked', true);
        setReceptionMode(lastType);

        // Przywracanie wartości pól
        if(lastType === 'regal') {
            var lr = localStorage.getItem('mz_last_regal'); 
            var lp = localStorage.getItem('mz_last_polka');
            if(lr) $('#rec_regal').val(lr); 
            if(lp) $('#rec_polka').val(lp);
        } else {
            var lk = localStorage.getItem('mz_last_kosz'); 
            if(lk) $('#rec_kosz_nr').val(lk);
        }
        
        // Pokazanie modala
        $('#reception_modal').modal('show');
        
        // Inteligentny Focus
        setTimeout(function() {
            // Jeśli mamy wybraną lokalizację, idź od razu do daty lub ilości
            // Jeśli nie, idź do pola lokalizacji
            if (lastType === 'kosz') {
                if($('#rec_kosz_nr').val()) $qtyInput.focus().select(); 
                else $('#rec_kosz_nr').focus();
            } else {
                if($('#rec_regal').val() && $('#rec_polka').val()) $qtyInput.focus().select();
                else $('#rec_regal').focus();
            }
        }, 500);
    }

    // ============================================================
    // 4. ZAPISYWANIE (PRZYCISK PRZYJMIJ NA STAN) - FIX DOUBLE CLICK
    // ============================================================
    $(document).on('click', '#btn_save_reception', function() {
        var $btn = $(this);
        
        // Zabezpieczenie logiczne (gdyby UI disable nie zadziałało)
        if ($btn.prop('disabled')) return;

        // Pobieranie danych z formularza
        var eanRaw = $('#rec_prod_ean').text().replace('EAN: ', '').trim();
        var qty = parseInt($('#rec_qty').val());
        var type = $('input[name="rec_type"]:checked').val();
        var regal = $('#rec_regal').val().trim();
        var polka = $('#rec_polka').val().trim();
        var kosz = $('#rec_kosz_nr').val().trim();
        var expiry = $('#rec_expiry').val();

        // Walidacja
        if (isNaN(qty) || qty <= 0) {
            alert("Podaj poprawną ilość!");
            $('#rec_qty').focus();
            return;
        }

        if (type === 'kosz' && kosz === '') {
            alert("Podaj numer kosza!");
            $('#rec_kosz_nr').focus();
            return;
        }
        if (type === 'regal' && (regal === '' || polka === '')) {
            alert("Podaj regał i półkę!");
            $('#rec_regal').focus();
            return;
        }

        // --- EFEKT UI: BLOKADA I SPINNER ---
        var originalText = $btn.html(); // Zapisz stary tekst (z ikoną check)
        $btn.prop('disabled', true);    // Zablokuj przycisk
        $btn.html('<i class="icon-refresh icon-spin"></i> ZAPISYWANIE...'); // Pokaż kółeczko

        // Zapisanie lokalizacji do pamięci na przyszłość
        if (type === 'regal') {
            localStorage.setItem('mz_last_regal', regal);
            localStorage.setItem('mz_last_polka', polka);
        } else {
            localStorage.setItem('mz_last_kosz', kosz);
        }

        // AJAX Save
        $.ajax({
            url: getControllerUrl() + '&action=process_reception',
            type: 'POST',
            dataType: 'json',
            data: {
                ean: eanRaw,
                qty: qty,
                type: type,
                regal: regal,
                polka: polka,
                kosz: kosz,
                expiry: expiry
            },
            success: function(res) {
                if (res.success) {
                    // Sukces
                    $('#reception_modal').modal('hide');
                    
                    if (typeof $.growl !== 'undefined') {
                        $.growl.notice({ title: "Sukces", message: "Przyjęto towar na stan!" });
                    } else {
                        alert("Przyjęto towar na stan!");
                    }

                    // Odświeżenie listy nadwyżek (jeśli jesteśmy w tym widoku)
                    // Próba usunięcia wiersza z tabeli wizualnie (jeśli istnieje)
                    var $row = $('tr[data-ean="'+eanRaw+'"]');
                    if($row.length > 0) {
                        $row.fadeOut(500, function(){ $(this).remove(); });
                    } else {
                        // Jeśli nie ma wiersza (np. skanowaliśmy z ręki), można odświeżyć całą tabelę jeśli funkcja dostępna
                        if(typeof location.reload === 'function') setTimeout(function(){ location.reload(); }, 1000);
                    }

                } else {
                    // Błąd logiczny z serwera
                    alert("Błąd zapisu: " + (res.msg || 'Nieznany błąd'));
                }
            },
            error: function() {
                alert("Błąd połączenia z serwerem.");
            },
            complete: function() {
                // ZAWSZE PRZYWRACAJ PRZYCISK (nawet przy błędzie)
                // Aby użytkownik mógł poprawić dane i spróbować ponownie
                setTimeout(function() {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                }, 500); // Małe opóźnienie dla płynności
            }
        });
    });

});