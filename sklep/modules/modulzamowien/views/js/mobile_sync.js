/**
 * mobile_sync.js
 * Synchronizacja na żywo (Live Sync) i obsługa Mobile
 */

var syncInterval = null;

$(document).ready(function() {
    // --- LIVE SYNC MOBILE ---
    $('#btn-push-to-mobile').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalText = btn.html();
        btn.addClass('disabled').html('<i class="icon-refresh icon-spin"></i> WYSYŁANIE...');

        $.ajax({
            url: ajax_init_mobile_url,
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                btn.removeClass('disabled').html(originalText);
                if (res.success) {
                    if (typeof $.growl !== 'undefined') $.growl.notice({
                        title: "Sukces",
                        message: "Dane wysłane na skanery!"
                    });
                    else alert("Dane wysłane na skanery!");

                    if (res.data) {
                        renderMonitorTable(res.data);
                        updateTab2Inputs(res.data);
                    }
                } else {
                    alert("Błąd: " + res.msg);
                }
            },
            error: function() {
                btn.removeClass('disabled').html(originalText);
                alert("Błąd połączenia z serwerem.");
            }
        });
    });

    startLiveSync();
});

function startLiveSync() {
    if (syncInterval) clearInterval(syncInterval);
    syncInterval = setInterval(function() {
        if (!$('#picking').is(':visible') && !$('#mobile_monitor').is(':visible')) return;
        $.ajax({
            url: ajax_check_state_url,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data) {
                    if ($('#mobile_monitor').is(':visible')) renderMonitorTable(data);
                    updateTab2Inputs(data);
                }
            }
        });
    }, 3000);
}

function renderMonitorTable(items) {
    var $tbody = $('#mobile-monitor-table tbody');
    $tbody.empty();
    if (!items || items.length === 0) {
        $tbody.html('<tr><td colspan="5" class="text-center text-muted" style="padding:20px;">Brak aktywnych danych.</td></tr>');
        return;
    }

    var activeItems = items.filter(function(i) {
        return parseInt(i.qty_stock) > 0;
    });
    if (activeItems.length === 0) {
        $tbody.html('<tr><td colspan="5" class="text-center text-success" style="padding:20px; font-weight:bold; font-size:16px;"><i class="icon-check-circle"></i> WSZYSTKO ZEBRANE!</td></tr>');
        return;
    }

    activeItems.sort(function(a, b) {
        var aDone = (parseInt(a.user_picked_qty) || 0) >= parseInt(a.qty_stock);
        var bDone = (parseInt(b.user_picked_qty) || 0) >= parseInt(b.qty_stock);
        if (aDone && !bDone) return 1;
        if (!aDone && bDone) return -1;
        return 0;
    });

    activeItems.forEach(function(item) {
        var picked = parseInt(item.user_picked_qty) || 0;
        var total = parseInt(item.qty_stock);
        var isDone = (picked >= total);
        var rowClass = isDone ? 'success' : (picked > 0 ? 'warning' : '');
        var icon = isDone ? '<i class="icon-check" style="color:green; font-size:18px;"></i>' : (picked > 0 ? '<i class="icon-refresh icon-spin" style="color:orange;"></i>' : '<i class="icon-time text-muted"></i>');
        $tbody.append('<tr class="' + rowClass + '"><td style="font-weight:bold;">' + (item.location || '--') + '</td><td>' + item.name + '</td><td><small>' + item.ean + '<br>' + item.sku + '</small></td><td class="text-center"><span style="font-weight:bold; color:' + (isDone ? 'green' : '#555') + '">' + picked + ' / ' + total + '</span></td><td class="text-center">' + icon + '</td></tr>');
    });
}

function updateTab2Inputs(items) {
    if (!items || items.length === 0) return;
    var serverMap = {};
    items.forEach(function(i) {
        serverMap[i.sku] = i;
    });

    $('.val-collected').each(function() {
        var $input = $(this);
        var $row = $input.closest('tr');
        var rowSku = $row.data('sku');

        if (rowSku && serverMap.hasOwnProperty(rowSku)) {
            var serverItem = serverMap[rowSku];
            var serverQty = parseInt(serverItem.user_picked_qty) || 0;
            var currentVal = parseInt($input.val()) || 0;
            var needed = parseInt($row.data('needed'));
            var isDone = serverItem.is_collected || (serverQty >= needed);

            if (currentVal !== serverQty && !$input.is(":focus")) {
                $input.val(serverQty).css('background-color', '#dff0d8');
                setTimeout(function() {
                    $input.css('background-color', '');
                }, 1000);
                // Funkcje te muszą być dostępne globalnie (zdefiniowane w picking_manager.js)
                if (typeof updateRowVisuals === 'function') updateRowVisuals($row, serverQty, needed);
            }
            if (isDone && !$row.hasClass('success')) {
                if (typeof markRowAsSuccess === 'function') markRowAsSuccess($row, serverQty);
            } else if (!isDone && $row.hasClass('success')) {
                if (typeof markRowAsIncomplete === 'function') markRowAsIncomplete($row, needed);
            }
        }
    });
}