/**
 * picking_alternatives.js
 * Obsługa Smart Swap - Wersja FINAL (Full Live Update + Smart Correction + Modern Modals)
 */

$(document).ready(function() {
    
    // Zmienne do przechowywania danych między kliknięciami
    var pendingSwapData = null;
    var pendingResetSku = null;

    // 1. OTWIERANIE OKNA (ZMIEŃ)
    $(document).on('click', '.btn-open-swap', function(e) {
        e.preventDefault();
        var $row = $(this).closest('tr');
        var currentSku = $row.attr('data-sku');
        
        var $list = $('#swap_list_container');
        $list.html('<div class="text-center" style="padding:20px; color:#555;"><i class="icon-refresh icon-spin icon-2x"></i><br>Sprawdzam aktualne stany WMS...</div>');
        
        $('#swap_target_sku').val(currentSku);
        $('#modal_picking_swap').modal('show');

        // Pobierz dane na żywo
        $.ajax({
            url: smartsupply_ajax_url, 
            type: 'POST',
            data: {
                action_type: 'get_alternatives',
                ean: currentSku 
            },
            dataType: 'json',
            success: function(res) {
                if (!res.success || !res.data || res.data.length === 0) {
                    $list.html('<div class="alert alert-danger" style="margin:10px;">Błąd: Nie znaleziono towaru w WMS (Stan 0).</div>');
                    return;
                }

                var alternatives = res.data;
                var html = '<table class="table table-hover" style="margin:0;">';
                html += '<thead><tr><th>Lokalizacja</th><th class="text-center">Dostępne</th><th class="text-center">Akcja</th></tr></thead>';
                html += '<tbody>';
                
                var foundCount = 0;
                
                alternatives.forEach(function(alt) {
                    if (alt.sku === currentSku) return; 
                    foundCount++;
                    var locDisplay = alt.regal + ' / ' + alt.polka;
                    var btn = '<button class="btn btn-success btn-xs btn-perform-swap" ' +
                              'data-real-sku="'+alt.sku+'" ' +
                              'data-regal="'+alt.regal+'" ' +
                              'data-polka="'+alt.polka+'">' +
                              'WYBIERZ <i class="icon-arrow-right"></i></button>';
                    
                    html += '<tr>';
                    html += '<td style="font-weight:bold; font-size:16px;">'+locDisplay+'</td>';
                    html += '<td class="text-center"><span class="badge" style="background:#2e7d32;">'+alt.quantity+'</span></td>';
                    html += '<td class="text-center">'+btn+'</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                
                if (foundCount === 0) {
                    $list.html('<div class="alert alert-warning" style="margin:10px;">Brak innych lokalizacji.</div>');
                } else {
                    $list.html(html);
                }
            },
            error: function() {
                $list.html('<div class="alert alert-danger">Błąd połączenia.</div>');
            }
        });
    });

    // 2. WYKONANIE ZMIANY (SWAP) + DETEKCJA KOREKTY
    $(document).on('click', '.btn-perform-swap', function() {
        var newRegal = $(this).data('regal');
        var newPolka = $(this).data('polka');
        var newRealSku = $(this).attr('data-real-sku'); 
        var originalSku = $('#swap_target_sku').val();
        
        var $row = $('.picking-row[data-sku="'+originalSku+'"]');
        var needed = parseInt($row.attr('data-needed'));
        var pickedInput = parseInt($row.find('.val-collected').val());
        if (isNaN(pickedInput)) pickedInput = 0;

        // Przygotowujemy dane do wysyłki i zapisujemy w zmiennej globalnej
        pendingSwapData = {
            originalSku: originalSku,
            newSku: newRealSku,
            newRegal: newRegal,
            newPolka: newPolka,
            partialQty: pickedInput,
            neededQty: needed,
            $row: $row
        };

        // --- SPRAWDZANIE WARUNKU KOREKTY (Czy zebrano mniej niż potrzeba?) ---
        if (pickedInput < needed) {
            // Ukrywamy stare okno
            $('#modal_picking_swap').modal('hide');
            
            var msg = "";
            var currentLoc = $row.attr('data-regal') + " / " + $row.attr('data-polka');

            if (pickedInput === 0) {
                msg = "Nie zebrałeś towaru z lokalizacji: <br><span style='font-size:1.4em; color:#d9534f;'>" + currentLoc + "</span>";
            } else {
                msg = "Zebrałeś tylko <b>" + pickedInput + "</b> z <b>" + needed + "</b> sztuk.<br>Lokalizacja: " + currentLoc;
            }
            
            // Wypełniamy i otwieramy NOWY modal (Smart Correction)
            $('#smart_correction_msg').html(msg);
            setTimeout(function(){
                $('#modal_smart_correction').modal('show');
            }, 300); 
            
        } else {
            // Jeśli ilości się zgadzają, robimy zwykły swap bez pytań
            performSwapAjax(pendingSwapData, 0);
        }
    });

    // 3. KLIKNIĘCIE "TAK, WYZERUJ" W MODALU KOREKTY
    $(document).on('click', '#btn_confirm_correction', function() {
        if (pendingSwapData) {
            performSwapAjax(pendingSwapData, 1); // 1 = Zrób korektę
            $('#modal_smart_correction').modal('hide');
        }
    });

    // 4. KLIKNIĘCIE "NIE" (Tylko zmień)
    $(document).on('click', '#btn_deny_correction', function() {
        if (pendingSwapData) {
            performSwapAjax(pendingSwapData, 0); // 0 = Bez korekty
            $('#modal_smart_correction').modal('hide');
        }
    });

    // --- GŁÓWNA FUNKCJA WYKONAWCZA AJAX ---
    function performSwapAjax(data, doCorrection) {
        $.ajax({
            url: smartsupply_ajax_url,
            type: 'POST',
            data: {
                action_type: 'swap_location',
                ean: data.originalSku,
                new_sku: data.newSku,
                new_regal: data.newRegal,
                new_polka: data.newPolka,
                do_correction: doCorrection,
                partial_qty: data.partialQty
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    var $row = data.$row;
                    
                    // --- LIVE UPDATE DOM (BEZ PRZEŁADOWANIA) ---
                    
                    // 1. NIE zmieniamy data-sku w DOM!
                    // data-sku jest kluczem rekordu w sesji picking (DB).
                    // Smart Swap zapisuje alternatywę w kolumnach ALT po stronie backendu.
                    
                    // 2. Jeśli była korekta (zerowanie), przeliczamy "POTRZEBA"
                    if (doCorrection === 1) {
                        var remaining = data.neededQty - data.partialQty;
                        
                        // Aktualizujemy atrybut danych
                        $row.attr('data-needed', remaining);
                        
                        // Aktualizujemy widok w tabeli (Kolumna "POTRZEBA")
                        // Szukamy komórki z niebieskim tekstem (najbezpieczniejsza metoda)
                        $row.find('td').filter(function() {
                            var color = $(this).css('color');
                            // Sprawdzamy czy kolor jest niebieski (w różnych formatach)
                            return color === 'rgb(0, 0, 255)' || color === 'blue' || color === '#0000ff';
                        }).text(remaining);
                        
                        // Czyścimy input "Zebrano" (bo przenosimy się na nową półkę, startujemy od zera)
                        $row.find('.val-collected').val('');
                    }

                    // 3. Aktualizacja komórki REGAŁ (Kolumna 1)
                    var $regalCell = $row.find('.loc-cell');
                    $regalCell.css({'background': '#fff3e0', 'border-left': '5px solid #ff9800'});
                    
                    var newRegalHtml = '<div style="font-size: 32px; font-weight:bold; color: #e65100;">' +
                                       '<span class="display-regal">' + data.newRegal + '</span></div>';
                    
                    var origRegal = $row.attr('data-regal');
                    var origPolka = $row.attr('data-polka');
                    newRegalHtml += '<div style="font-size:10px; color:#999; text-decoration:line-through; margin-bottom:2px;">' + 
                                    origRegal + ' / ' + origPolka + '</div>';
                                    
                    newRegalHtml += '<button type="button" class="btn btn-default btn-xs btn-reset-swap" style="width:100%; font-size:10px; padding:2px;">' +
                                    '<i class="icon-undo"></i> COFNIJ</button>';
                    
                    $regalCell.html(newRegalHtml);
                    
                    // 4. Aktualizacja komórki PÓŁKA (Kolumna 2)
                    var $polkaCell = $row.find('.loc-polka-cell');
                    $polkaCell.css('background', '#fff3e0');
                    $polkaCell.find('.display-polka').text(data.newPolka).css('color', '#e65100');
                    
                    // 5. Dodaj label "ZMIANA LOK" jeśli nie ma
                    if($row.find('.label-warning').length === 0) {
                        $row.find('td').eq(3).append('<br><span class="label label-warning" style="font-size:9px;">ZMIANA LOK.</span>');
                    }
                    
                    // Zamykamy modale
                    $('#modal_picking_swap').modal('hide');
                    $('#modal_smart_correction').modal('hide');

                } else {
                    alert('Błąd: ' + (res.msg || 'Nie udało się zapisać zmiany.'));
                }
            },
            error: function() { alert('Krytyczny błąd połączenia.'); }
        });
    }

    // 5. COFNIĘCIE ZMIANY (RESET) - OTWIERANIE MODALA
    $(document).on('click', '.btn-reset-swap', function() {
        var $row = $(this).closest('tr');
        pendingResetSku = $row.attr('data-sku'); // Zapisz kogo resetujemy
        $('#modal_confirm_reset').modal('show');
    });

    // 6. POTWIERDZENIE RESETU (W MODALU)
    $(document).on('click', '#btn_confirm_reset_action', function() {
        if (pendingResetSku) {
            performResetAjax(pendingResetSku);
            $('#modal_confirm_reset').modal('hide');
        }
    });

    // --- FUNKCJA WYKONAWCZA RESETU ---
    function performResetAjax(sku) {
        var $row = $('.picking-row[data-sku="'+sku+'"]');
        var originalRegal = $row.attr('data-regal');
        var originalPolka = $row.attr('data-polka');

        $.ajax({
            url: smartsupply_ajax_url,
            type: 'POST',
            data: { action_type: 'reset_swap', ean: sku },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    // Sprawdzamy czy są opcje alternatyw (żeby przywrócić przycisk ZMIEŃ)
                    var alternativesData = $row.attr('data-alternatives');
                    var hasOptions = false;
                    if (alternativesData && alternativesData.length > 5) {
                        try {
                            var alts = JSON.parse(alternativesData);
                            if(alts.length > 1) hasOptions = true;
                        } catch(e) {}
                    }

                    // 1. Reset komórki REGAŁ
                    var $regalCell = $row.find('.loc-cell');
                    var bgColor = hasOptions ? '#e8f5e9' : '#fff';
                    $regalCell.css({'background': bgColor, 'border-left': 'none'});
                    
                    var origHtml = '<div style="font-size: 32px; font-weight:bold; color: #333;">' +
                                   '<span class="display-regal">' + originalRegal + '</span></div>';
                    
                    if(hasOptions) {
                        origHtml += '<button type="button" class="btn btn-default btn-xs btn-open-swap" style="margin-top:5px; width:100%; font-weight:bold; color:#2e7d32; border:1px solid #2e7d32; font-size:10px;">' +
                                    '<i class="icon-refresh"></i> ZMIEŃ</button>';
                    }
                    $regalCell.html(origHtml);
                    
                    // 2. Reset komórki PÓŁKA
                    var $polkaCell = $row.find('.loc-polka-cell');
                    $polkaCell.css('background', '#f9f9f9');
                    $polkaCell.find('.display-polka').text(originalPolka).css('color', '#555');
                    
                    // 3. Usuwamy label
                    $row.find('.label-warning').remove();
                    
                } else {
                    alert('Błąd resetowania.');
                }
            }
        });
    }
});