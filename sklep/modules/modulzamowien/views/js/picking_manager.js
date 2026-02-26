/**
 * picking_manager.js
 * Główna logika kompletacji (Zakładka 2)
 * Wersja: FIXED INDICES (Naprawa odświeżania po cofnięciu)
 */

var typingTimer;
var doneTypingInterval = 500;

$(document).ready(function() {
    initFilters();
    initScanner();

    // --- LOGIKA ZAKŁADKI 2 (PICKING) ---

    $(document).on('input', '.val-collected', function() {
        var $input = $(this);
        var $row = $input.closest('tr');
        var valStr = $input.val();
        var qty = (valStr === '') ? 0 : parseInt(valStr);
        var needed = parseInt($row.data('needed'));

        if (isNaN(qty) || qty < 0) qty = 0;
        if (qty > needed) {
            qty = needed;
            $input.val(needed);
        }

        updateRowVisuals($row, qty, needed);
        clearTimeout(typingTimer);

        if (qty === needed) {
            $input.prop('disabled', true);
            confirmRow($row, needed);
        } else {
            typingTimer = setTimeout(function() {
                saveQuantity($row, qty);
            }, doneTypingInterval);
        }
    });

    $(document).on('click', '.btn-confirm', function() {
        var $row = $(this).closest('tr');
        var needed = parseInt($row.data('needed'));
        $row.find('.val-collected').val(needed);
        updateRowVisuals($row, needed, needed);
        confirmRow($row, needed);
    });

    $(document).on('click', '.btn-undo', function() {
        undoRow($(this).closest('tr'));
    });

    $(document).on('click', '.val-collected', function() {
        $(this).select();
    });

    $('#btn-collect-all').click(function() {
        bulkCollectAll();
    });
    $('#btn-reset-all').click(function() {
        bulkResetAll();
    });

    $(document).on('click', '#btn-open-clear-modal', function(e) {
        e.preventDefault();
        var finishedCount = $('.picking-row.success').length;
        if (finishedCount === 0) {
            alert("Brak zebranych (zielonych) pozycji do usunięcia.");
            return;
        }
        $('#clear_count_val').text(finishedCount);
        $('#modal_clear_confirmation').modal('show');
    });

    $(document).on('click', '#btn_confirm_clear_action', function() {
        var btn = $(this);
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> CZYSZCZENIE...');

        if (typeof clear_collected_url === 'undefined' || !clear_collected_url) {
            alert("Błąd konfiguracji URL.");
            return;
        }

        $.ajax({
            url: clear_collected_url,
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#modal_clear_confirmation').modal('hide');
                    $('.picking-row.success').fadeOut(500, function() {
                        $(this).remove();
                    });
                    if (typeof $.growl !== 'undefined') $.growl.notice({
                        title: "Sukces",
                        message: "Lista wyczyszczona."
                    });
                    btn.prop('disabled', false).html(originalText);
                } else {
                    alert("Błąd: " + (res.msg || 'Wystąpił problem z bazą.'));
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert("Błąd połączenia z serwerem.");
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $('#btn-correct-stock').click(function() {
        if (!confirm("UWAGA! Czy na pewno skorygować stany dla NIEZEBRANYCH pozycji?")) return;
        var itemsToCorrect = [];
        var $rowsToProcess = $('.picking-row:visible').not('.success');
        $rowsToProcess.each(function() {
            var $row = $(this);
            var needed = parseInt($row.data('needed'));
            var current = parseInt($row.find('.val-collected').val()) || 0;
            var diff = needed - current;
            if (diff > 0) {
                itemsToCorrect.push({
                    ean: $row.data('sku'),
                    qty_to_remove: diff,
                    qty_picked: current
                });
            }
        });
        if (itemsToCorrect.length === 0) {
            alert("Brak różnic do skorygowania.");
            return;
        }
        $.ajax({
            url: smartsupply_ajax_url,
            type: 'POST',
            data: {
                action_type: 'correct_stock_batch',
                items: itemsToCorrect
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $rowsToProcess.each(function() {
                        var $row = $(this);
                        var needed = parseInt($row.data('needed'));
                        var current = parseInt($row.find('.val-collected').val()) || 0;
                        var diff = needed - current;
                        if (diff > 0) {
                            $row.attr('data-completed', '1').addClass('success').css('background-color', '#f2dede').find('td').css('background-color', '#f2dede');
                            
                            // FIX INDEX: 7 (Zebrano)
                            var $inputCell = $row.find('.val-collected').parent();
                            if ($inputCell.length === 0) $inputCell = $row.children('td').eq(7);
                            $inputCell.html('<span class="label label-danger">BRAK: ' + diff + ' (Mam: ' + current + ')</span>');
                            
                            // FIX INDEX: 8 (Akcja)
                            var $btnCell = $row.find('.btn-confirm').parent();
                            if ($btnCell.length === 0) $btnCell = $row.children('td').eq(8);
                            $btnCell.html('<i class="icon-ban-circle" style="color:#a94442; font-size:24px;"></i>');
                            
                            $row.find('.cell-qty-left').html('<span class="label label-success">OK</span>');
                            moveRowToBottom($row);
                        }
                    });
                    playSound('success');
                } else {
                    alert("Błąd korekty.");
                }
            }
        });
    });
});

// --- FUNKCJE GLOBALNE (Dostępne dla mobile_sync.js) ---

function initScanner() {
    $('#scanner_input').focus().on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            var c = $(this).val().trim();
            if (c.length > 0) processScan(c);
            $(this).val('');
        }
    });
}

function processScan(code) {
    var $row = findRow(code);
    if (!$row) {
        alert("Nieznany kod: " + code);
        return;
    }
    var $in = $row.find('.val-collected');
    var needed = parseInt($row.data('needed'));
    var current = parseInt($in.val()) || 0;

    if (current < needed) {
        var newVal = current + 1;
        $in.val(newVal);
        updateRowVisuals($row, newVal, needed);
        if (newVal === needed) confirmRow($row, needed);
        else saveQuantity($row, newVal);
    } else confirmRow($row, needed);
}

function findRow(ean) {
    var $v = $('#table_picking .picking-row:visible');
    var $e = $v.filter('[data-ean="' + ean + '"]').not('.success');
    if ($e.length === 0) $e = $('#table_picking .picking-row[data-ean="' + ean + '"]').not('.success');
    if ($e.length > 0) return $e.first();
    return null;
}

function updateRowVisuals($row, currentQty, maxQty) {
    // FIX: Używamy szukania po klasie lub eq(6) dla 'POTRZEBA'
    var leftCell = $row.children('td').eq(6); 
    
    var remaining = maxQty - currentQty;
    if (remaining <= 0) {
        leftCell.html('<span class="label label-success">OK</span>');
        $row.css('background-color', '#e6ffe6');
    } else {
        leftCell.html('<span class="label label-warning" style="font-size:1.1em;">' + remaining + '</span>');
        if (currentQty > 0) $row.find('.val-collected').css({
            'border-color': '#ff9900',
            'color': '#ff9900',
            'font-weight': 'bold'
        });
        else $row.find('.val-collected').css({
            'border-color': '#ccc',
            'color': 'black',
            'font-weight': 'normal'
        });
    }
}

function saveQuantity($row, qty) {
    var sku = $row.data('sku');
    var $input = $row.find('.val-collected');
    $.ajax({
        url: smartsupply_ajax_url,
        type: 'POST',
        data: {
            action_type: 'update_qty',
            ean: sku,
            qty_picked: qty
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $input.css('border-color', 'green');
                setTimeout(function() {
                    if (!$row.hasClass('success')) $input.css('border-color', '#ff9900');
                }, 500);
            }
        }
    });
}

function confirmRow($row, qtyPicked) {
    var sku = $row.data('sku');
    $.ajax({
        url: smartsupply_ajax_url,
        type: 'POST',
        data: {
            action_type: 'confirm_pick',
            ean: sku,
            qty_picked: qtyPicked
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                markRowAsSuccess($row, qtyPicked);
                playSound('success');
            }
        }
    });
}

function undoRow($row) {
    if (!confirm("Cofnąć zebranie tej pozycji?")) return;
    var sku = $row.data('sku');
    var needed = parseInt($row.data('needed'));
    $.ajax({
        url: smartsupply_ajax_url,
        type: 'POST',
        data: {
            action_type: 'revert_pick',
            ean: sku,
            qty_picked: 0
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                // Najpierw przywróć wygląd
                markRowAsIncomplete($row, needed);
                
                // Potem animacja przeniesienia
                var $tbody = $row.closest('tbody');
                $row.fadeOut(300, function() {
                    $tbody.prepend($row); // Przenieś na górę listy
                    $row.fadeIn(300);
                });
            } else alert("Błąd cofania: " + (res.msg || 'Nieznany błąd'));
        }
    });
}

function bulkCollectAll() {
    if (!confirm("Zatwierdzić WSZYSTKO?")) return;
    var items = [];
    var $rowsToUpdate = $('.picking-row:visible').not('.success');
    $rowsToUpdate.each(function() {
        items.push({
            ean: $(this).data('sku'),
            qty: $(this).data('needed')
        });
    });
    if (items.length === 0) return;
    $.ajax({
        url: smartsupply_ajax_url,
        type: 'POST',
        data: {
            action_type: 'confirm_all',
            items: items
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $rowsToUpdate.each(function() {
                    markRowAsSuccess($(this), parseInt($(this).data('needed')));
                });
                playSound('success');
            } else alert("Błąd.");
        }
    });
}

function bulkResetAll() {
    if (!confirm("Cofnąć WSZYSTKIE?")) return;
    var items = [];
    var $rowsToReset = $('.picking-row.success');
    $rowsToReset.each(function() {
        items.push({
            ean: $(this).data('sku'),
            qty: $(this).data('needed')
        });
    });
    if (items.length === 0) return;
    $.ajax({
        url: smartsupply_ajax_url,
        type: 'POST',
        data: {
            action_type: 'revert_all',
            items: items
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $rowsToReset.each(function() {
                    markRowAsIncomplete($(this), parseInt($(this).data('needed')));
                });
            } else alert("Błąd.");
        }
    });
}

function initFilters() {
    var regals = [];
    $('.picking-row').each(function() {
        var r = $(this).data('regal');
        if (r && regals.indexOf(r) === -1) regals.push(r);
    });
    regals.sort();
    $.each(regals, function(i, val) {
        $('#filter_regal').append('<option value="' + val + '">' + val + '</option>');
    });
    $('#filter_regal').change(function() {
        var r = $(this).val();
        $('.picking-row').each(function() {
            if (r === "" || $(this).data('regal') == r) $(this).show();
            else $(this).hide();
        });
    });
}

function markRowAsSuccess($row, qty) {
    // 1. Oznacz jako zakończony
    $row.attr('data-completed', '1').addClass('success')
        .css('background-color', '#dff0d8')
        .find('td').css('background-color', '#dff0d8');
    
    // 2. Podmień input na "ZEBRANO" (FIX INDEX: 7)
    var $inputCell = $row.find('.val-collected').parent();
    if ($inputCell.length === 0) $inputCell = $row.children('td').eq(7);
    
    $inputCell.html('<span class="label label-success" style="font-size:14px; margin-right:5px;">ZEBRANO: ' + qty + '</span><button class="btn btn-warning btn-xs btn-undo" style="margin-left:5px;"><i class="icon-undo"></i></button>');
    
    // 3. Podmień przycisk OK na "PTASZEK" (FIX INDEX: 8)
    var $btnCell = $row.find('.btn-confirm').parent();
    if ($btnCell.length === 0) $btnCell = $row.children('td').eq(8);
    
    $btnCell.html('<i class="icon-check" style="color:green; font-size:24px;"></i>');
    
    // 4. Lewa kolumna "POTRZEBA" - OK (FIX INDEX: 6)
    $row.children('td').eq(6).html('<span class="label label-success">OK</span>');
    
    moveRowToBottom($row);
}

function moveRowToBottom($row) {
    var $tbody = $row.closest('tbody');
    $row.fadeOut(300, function() {
        $tbody.append($row);
        $row.fadeIn(300);
    });
}

function markRowAsIncomplete($row, needed) {
    // 1. Zdejmij status sukcesu i kolory
    $row.removeAttr('data-completed').removeClass('success')
        .css('background-color', '')
        .find('td').css('background-color', '');
    
    // Specyficzne kolory dla regału/półki (opcjonalne czyszczenie)
    $row.find('td').eq(0).css('background-color', '#fff');
    $row.find('td').eq(1).css('background-color', '#f9f9f9');
    
    // 2. Przywróć INPUT (FIX INDEX: 7)
    // Szukamy komórki, która ma label-success (bo tam był tekst "ZEBRANO")
    var $inputCell = $row.children('td').eq(7);
    
    $inputCell.html('<input type="number" class="form-control val-collected" value="0" min="0" max="' + needed + '" style="text-align:center; font-size:20px; font-weight:bold; color:green; height: 40px;">');
    
    // 3. Przywróć BUTTON OK (FIX INDEX: 8)
    var $btnCell = $row.children('td').eq(8);
    
    $btnCell.html('<button type="button" class="btn btn-success btn-confirm btn-lg btn-block">OK</button>');
    
    // 4. Przywróć licznik "POTRZEBA" (FIX INDEX: 6)
    $row.children('td').eq(6).html('<span class="label label-danger">' + needed + '</span>');
}