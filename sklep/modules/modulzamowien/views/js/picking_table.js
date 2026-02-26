/**
 * picking_table.js
 * Logika "Pick Stół" (Tab 4) - Skaner stołowy
 */

$(document).ready(function() {
    initTableScanner();

    $(document).on('click', '.btn-confirm-table', function() {
        var $row = $(this).closest('tr');
        var needed = parseInt($row.data('needed'));
        $row.find('.val-collected-table').val(needed);
        confirmTableRow($row, needed);
    });

    $(document).on('click', '.btn-undo-table', function() {
        var $row = $(this).closest('tr');
        if (!confirm("Cofnąć zebranie ze stołu?")) return;
        var sku = $row.data('sku');
        var needed = parseInt($row.data('needed'));
        $.ajax({
            url: table_ajax_url,
            type: 'POST',
            data: {
                action_type: 'revert_pick',
                ean: sku,
                qty_picked: 0
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $row.removeClass('success').css('background-color', '');
                    $row.find('td').css('background-color', '');
                    $row.children('td').eq(5).html('<input type="number" class="form-control val-collected-table" value="0" min="0" max="' + needed + '" style="text-align:center; font-size:20px; font-weight:bold; color:green; height: 40px;">');
                    $row.children('td').eq(6).html('<button type="button" class="btn btn-success btn-confirm-table btn-lg btn-block">OK</button>');
                }
            }
        });
    });

    $(document).on('input', '.val-collected-table', function() {
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
        if (qty === needed) confirmTableRow($row, needed);
        else $.ajax({
            url: table_ajax_url,
            type: 'POST',
            data: {
                action_type: 'update_qty',
                ean: $row.data('sku'),
                qty_picked: qty
            },
            dataType: 'json'
        });
    });
});

function initTableScanner() {
    $('#scanner_input_table').focus().on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            var c = $(this).val().trim();
            if (c.length > 0) processTableScan(c);
            $(this).val('');
        }
    });
}

function processTableScan(ean) {
    var $row = $('#table_pick_table .table-picking-row').filter('[data-ean="' + ean + '"]').first();
    if ($row.length === 0) {
        alert("Nie ma na liście stołowej.");
        return;
    }
    var $in = $row.find('.val-collected-table');
    var needed = parseInt($row.data('needed'));
    var current = parseInt($in.val()) || 0;
    if (!$row.hasClass('success')) {
        if (current < needed) {
            $in.val(current + 1);
            if (current + 1 >= needed) confirmTableRow($row, needed);
            else $in.trigger('input');
        } else {
            confirmTableRow($row, needed);
        }
    } else {
        playSound('success');
    }
}

function confirmTableRow($row, qty) {
    var sku = $row.data('sku');
    $.ajax({
        url: table_ajax_url,
        type: 'POST',
        data: {
            action_type: 'confirm_pick',
            ean: sku,
            qty_picked: qty
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $row.addClass('success').css('background-color', '#dff0d8');
                $row.find('td').css('background-color', '#dff0d8');
                $row.children('td').eq(5).html('<span class="label label-success" style="font-size:14px;">GOTOWE</span>');
                $row.children('td').eq(6).html('<button class="btn btn-warning btn-xs btn-undo-table" style="width:100%; height:35px;" title="Cofnij"><i class="icon-undo"></i> COFNIJ</button>');
                playSound('success');
            }
        }
    });
}