/**
 * admin_extra.js
 * Wersja: 15.0 (Fix: Szerokość pola ilości + Full Code)
 */

var extraSearchTimer;
var currentAlternativesData = []; 
var globalSourceInfo = {}; 

// FUNKCJA WYSZUKIWANIA
function performExtraSearch() {
    var query = $('#extra_search_input').val().trim();
    if (query.length < 3) { 
        $('#extra_search_results').slideUp(); 
        $('#extra_results_body').empty();
        return; 
    }

    var btn = $('#btn-extra-search');
    var originalText = '<i class="icon-search"></i> SZUKAJ';
    
    btn.prop('disabled', true).html('<i class="icon-spin icon-spinner"></i> Szukam...');
    $('#extra_search_results').hide(); 
    $('#extra_results_body').empty();
    
    var searchUrl = 'index.php?controller=AdminModulExtra&token=' + token_admin_extra + '&action=searchProduct&ajax=1';
    if (typeof ajax_add_extra_url !== 'undefined') searchUrl = ajax_add_extra_url + '&action=searchProduct';
    
    $.ajax({
        url: searchUrl, type: 'POST', dataType: 'json', data: { q: query },
        success: function(data) {
            btn.prop('disabled', false).html(originalText);
            
            if (data.alternatives_found === true) {
                $('#alt_original_query').text(data.original_query);
                
                var combined = [];
                if(data.direct_data) combined = combined.concat(data.direct_data);
                if(data.smaller_data) combined = combined.concat(data.smaller_data);
                currentAlternativesData = combined;
                
                globalSourceInfo = data.source_info || {};

                if (data.source_info && data.source_info.name) {
                    var s = data.source_info;
                    var headerHtml = '<div style="font-size:1.4em; color:#333; margin-bottom:10px; font-weight:bold;">' + s.name + '</div>';
                    headerHtml += '<div style="font-size:1.0em; color:#555;">';
                    if(s.ean) headerHtml += '<span class="label label-default" style="margin-right:10px; font-family:monospace; font-size:11px;">EAN: ' + s.ean + '</span>';
                    if(s.weight > 0) headerHtml += '<span class="label label-info" style="margin-right:10px; font-size:11px;">WAGA: ' + s.weight + ' kg</span>';
                    if(s.manufacturer) headerHtml += '<span class="label label-primary" style="margin-right:10px; font-size:11px;">PRODUCENT: ' + s.manufacturer + '</span>';
                    if(s.price_gross > 0) headerHtml += '<span class="label label-success" style="margin-right:10px; font-size:11px;">CENA ZAKUPU: ' + s.price_gross + ' zł (brutto)</span>';
                    headerHtml += '</div>';
                    $('#alt_source_name_box').html(headerHtml).show();
                } else {
                    $('#alt_source_name_box').hide();
                }

                renderAlternativesTable(data.direct_data, '#alternatives_list_body');

                if (data.smaller_data && data.smaller_data.length > 0) {
                    $('#smaller_weights_section').show();
                    renderAlternativesTable(data.smaller_data, '#smaller_weights_body');
                } else {
                    $('#smaller_weights_section').hide();
                    $('#smaller_weights_body').empty();
                }

                $('#alternativesModal').modal('show');
                return; 
            }

            if (data && data.length > 0) {
                renderMainResultsTable(data);
                $('#extra_search_results').slideDown();
            } else {
                $('#extra_results_body').html('<tr><td colspan="8" class="text-center text-muted" style="padding:20px;">Brak wyników.</td></tr>');
                $('#extra_search_results').slideDown();
            }
        },
        error: function() { 
            btn.prop('disabled', false).html(originalText); 
            alert('Błąd połączenia.'); 
        }
    });
}

$(document).ready(function() {
    refreshExtraTable();

    $(document).on('click', '#btn-extra-search', function() { performExtraSearch(); });
    $(document).on('keyup keypress', '#extra_search_input', function(e) {
        if (e.which == 13) { e.preventDefault(); clearTimeout(extraSearchTimer); performExtraSearch(); return false; }
        if (e.type === 'keyup') { clearTimeout(extraSearchTimer); extraSearchTimer = setTimeout(function() { var val = $('#extra_search_input').val().trim(); if(val.length >= 3) performExtraSearch(); }, 800); }
    });

    $(document).on('click', '.btn-extra-direct-add', function() {
        var btn = $(this);
        var qtyInput = btn.closest('tr').find('.extra-add-qty');
        var qty = parseInt(qtyInput.val());
        
        var isMag = (btn.data('ismag') == 1);
        var needed = parseInt(btn.data('multiplier')) || 1;
        var stock = parseInt(btn.data('stock')) || 0;
        
        if (isMag && qty === needed && stock > 0 && stock < needed) {
            if (typeof openPartialStockModal === 'function') {
                openPartialStockModal(btn, stock, needed);
            } else {
                alert('Błąd: Nie załadowano modułu partial JS.');
            }
            return;
        }

        if (qty <= 0 || isNaN(qty)) { alert('Ilość > 0'); return; }
        
        // Pobieramy dane
        var ean = btn.data('ean');
        var name = btn.data('name');
        var sku = btn.data('sku');
        var manName = btn.data('man-name'); // Pobieramy producenta

        var dataPayload = { ean: ean, name: name, sku: sku, qty: qty, force: 0 };
        
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="icon-spin icon-spinner"></i>');
        
        var isFromModal = $('#alternativesModal').hasClass('in');
        
        sendAddRequest(dataPayload, btn, originalText, isFromModal);
    });

    $(document).on('click', '.btn-remove-extra', function() {
        var btn = $(this);
        var id = btn.data('db-id');
        var originalIcon = btn.html();
        btn.prop('disabled', true).html('<i class="icon-spin icon-spinner"></i>');
        var removeUrl = 'index.php?controller=AdminModulExtra&token=' + token_admin_extra + '&action=removeExtraItem&ajax=1';
        if (typeof ajax_add_extra_url !== 'undefined') removeUrl = ajax_add_extra_url + '&action=removeExtraItem';

        $.ajax({
            url: removeUrl, type: 'POST', data: { id_extra: id }, dataType: 'json',
            success: function(res) { 
                if (res.success) { refreshExtraTable(); } 
                else { btn.prop('disabled', false).html(originalIcon); alert('Błąd usuwania.'); }
            },
            error: function() { btn.prop('disabled', false).html(originalIcon); }
        });
    });
    
    $(document).on('click', '#btn-clear-extra', function() {
        if (!confirm('Wyczyścić listę?')) return;
        var clearUrl = 'index.php?controller=AdminModulExtra&token=' + token_admin_extra + '&action=clearExtraItems&ajax=1';
        if (typeof ajax_add_extra_url !== 'undefined') clearUrl = ajax_add_extra_url + '&action=clearExtraItems';
        $.ajax({ url: clearUrl, type: 'POST', dataType: 'json', success: function(res) { if (res.success) refreshExtraTable(); } });
    });
});

// --- FUNKCJE POMOCNICZE ---

function renderMainResultsTable(data) {
    var html = '';
    $.each(data, function(i, item) { html += buildRowHtml(item, false); });
    $('#extra_results_body').html(html);
}

function renderAlternativesTable(items, targetBodyId) {
    var html = '';
    if (items && items.length > 0) {
        $.each(items, function(i, item) { html += buildRowHtml(item, true); });
    } else {
        html = '<tr><td colspan="7" class="text-center text-muted">Brak propozycji.</td></tr>';
    }
    $(targetBodyId).html(html);
}

function buildRowHtml(item, simpleMode) {
    var rowStyle = '';
    var isMag = (item.reference && item.reference.indexOf('A_MAG') === 0);
    var isSameMan = (item.is_same_manufacturer === true);

    if (isMag) rowStyle = 'style="background-color: #fff8e1; border-left: 4px solid #ff9800;"';
    else if (isSameMan) rowStyle = 'style="background-color: #e3f2fd; border-left: 4px solid #2196f3;"';
    
    var uniqueId = item.unique_js_id ? item.unique_js_id : item.id_product;
    var stockId = 'stock_val_' + uniqueId;
    var showEan = item.ean13 ? item.ean13 : '---';
    var showRef = item.reference ? item.reference : ''; 
    var showName = item.name; 
    
    var showPrice = parseFloat(item.price_gross).toFixed(2);
    var showQty = parseInt(item.quantity);
    var formattedRef = showRef.replace(/_\(/g, '<br>(');

    var imgHtml = '';
    if (item.image_url) imgHtml = '<div class="extra-img-wrapper"><img src="' + item.image_url + '" class="extra-product-thumb"></div>';
    else imgHtml = '<div class="extra-img-wrapper" style="background:#f9f9f9; color:#ccc; font-size:10px; display:flex; align-items:center; justify-content:center; border:1px solid #eee;">BRAK</div>';

    var cleanName = $('<div>').html(showName).text(); 
    var nameDisplay = showName;
    var badges = '';
    if (isMag) badges += '<span class="label label-warning" style="background-color:#ff9800; font-size:10px; margin-right:5px;">MAGAZYN LOKALNY</span>';
    if (isSameMan) badges += '<span class="label label-primary" style="background-color:#2196f3; font-size:10px; margin-right:5px;">TEN SAM PRODUCENT</span>';
    if (badges !== '') nameDisplay = badges + ' <strong>' + showName + '</strong>';
    
    if (item.pack_info) {
        var alertStyle = (item.is_shortage) ? 'background:#f2dede; color:#a94442; border:1px solid #ebccd1;' : 'background:#f9f9f9; border:1px solid #eee;';
        nameDisplay += '<br><div style="margin-top:4px; font-size:0.9em; padding:2px 5px; display:inline-block; border-radius:3px; '+alertStyle+'">' + item.pack_info + '</div>';
    }

    var btnHtml = '<button type="button" class="btn btn-success btn-sm btn-extra-direct-add" ' +
                    'data-ean="' + showEan + '" ' +
                    'data-name="' + cleanName.replace(/"/g, '&quot;') + '" ' +
                    'data-sku="' + showRef + '" ' +
                    'data-stock-id="' + stockId + '" ' +
                    'data-multiplier="' + (item.multiplier || 1) + '" ' +
                    'data-stock="' + showQty + '" ' +
                    'data-ismag="' + (isMag ? 1 : 0) + '" ' +
                    'data-weight="' + (item.weight || 0) + '" ' +
                    'data-man-name="' + (item.manufacturer_name || '') + '" ' + 
                    'data-id-product="' + item.id_product + '">' +
                    '<i class="icon-plus"></i> DODAJ</button>';

    var preFillQty = (item.multiplier && item.multiplier > 1) ? item.multiplier : 1;
    
    // --- TU POPRAWKA: ZWIĘKSZONO WIDTH DO 100px I FONT ---
    var qtyInputHtml = '<input type="number" class="form-control input-sm extra-add-qty" value="'+preFillQty+'" min="1" style="width: 100px; margin: 0 auto; text-align: center; font-weight:bold; font-size: 16px;">';
    // -----------------------------------------------------

    var tr = '<tr ' + rowStyle + '>';
    tr += '<td class="text-center">' + imgHtml + '</td>';
    
    if (simpleMode) {
        tr += '<td>' + nameDisplay + '<br><small class="text-muted">' + formattedRef + '</small></td>';
        tr += '<td class="text-center">' + showEan + '</td>';
        var colorStyle = showQty > 0 ? 'color:#2e7d32;' : 'color:#c62828;';
        tr += '<td class="text-center" style="' + colorStyle + ' font-weight:bold;">' + showQty + '</td>';
        
        var priceHtml = '<span style="font-weight:bold;">' + showPrice + ' zł</span>';
        if (item.price_diff_html) priceHtml += '<br><small>' + item.price_diff_html + '</small>';
        tr += '<td class="text-center">' + priceHtml + '</td>';
        
        tr += '<td class="text-center">' + qtyInputHtml + '</td>';
        tr += '<td class="text-center">' + btnHtml + '</td>';
    } else {
        tr += '<td style="word-wrap: break-word;">' + nameDisplay + '</td>';
        tr += '<td class="text-center" style="word-break: break-all;">' + showEan + '</td>';
        tr += '<td style="line-height:1.2; vertical-align:middle;"><small class="text-muted" style="font-family:monospace; font-size:0.85em;">' + formattedRef + '</small></td>';
        tr += '<td class="text-center" style="font-weight:bold;">' + showPrice + ' zł</td>';
        var colorStyle = showQty > 0 ? 'color:#2e7d32;' : 'color:#c62828;';
        tr += '<td class="text-center" id="' + stockId + '" style="font-weight:bold; font-size:1.2em; ' + colorStyle + '">' + showQty + '</td>';
        tr += '<td class="text-center">' + qtyInputHtml + '</td>';
        tr += '<td class="text-center">' + btnHtml + '</td>';
    }
    tr += '</tr>';
    return tr;
}

function addSingleItem(ean, name, sku, qty, force) {
    sendAddRequest({ ean: ean, name: name, sku: sku, qty: qty, force: force }, null, null, false);
}

// FUNKCJA WYSYŁAJĄCA - Z KLUCZOWĄ POPRAWKĄ NAZWY
function sendAddRequest(dataPayload, btn, originalText, closeAltModal) {
    var addUrl = 'index.php?controller=AdminModulExtra&token=' + token_admin_extra + '&action=addExtraItem&ajax=1';
    if (typeof ajax_add_extra_url !== 'undefined') addUrl = ajax_add_extra_url + '&action=addExtraItem';

    // --- POPRAWKA: AUTO-FORMATOWANIE NAZWY DLA MAGAZYNU ---
    if (dataPayload.sku && (dataPayload.sku.indexOf('A_MAG') === 0 || dataPayload.sku.indexOf('DUPL') === 0)) {
        if (dataPayload.name.indexOf('[MAG]') === -1) {
            dataPayload.name = '★ [MAG] ' + dataPayload.name;
        }
    }

    $.ajax({
        url: addUrl, type: 'POST', dataType: 'json', data: dataPayload,
        success: function(res) {
            if (res.confirmation_needed === true) {
                if(btn) btn.prop('disabled', false).html(originalText);
                var listHtml = '<ul class="wms-location-list">';
                if (res.locations && res.locations.length > 0) {
                    $.each(res.locations, function(i, loc) {
                        listHtml += '<li class="wms-location-item"><span class="wms-loc-name"><i class="icon-map-marker"></i> ' + loc.loc + '</span><span class="wms-loc-qty">' + loc.qty + ' szt.</span></li>';
                    });
                }
                listHtml += '</ul>';
                $('#warehouseGuardList').html(listHtml);
                $('#warehouseGuardModal').modal('show');
                
                $('#warehouseGuardConfirmBtn').off('click').on('click', function() {
                    $('#warehouseGuardModal').modal('hide');
                    dataPayload.force = 1;
                    if(btn) btn.prop('disabled', true).html('<i class="icon-spin icon-spinner"></i>');
                    sendAddRequest(dataPayload, btn, originalText, closeAltModal);
                });
                return; 
            }

            if (res.success) {
                refreshExtraTable();
                if(btn) {
                    btn.removeClass('btn-success').addClass('btn-default').html('<i class="icon-check" style="color:green"></i>');
                    setTimeout(function() { btn.removeClass('btn-default').addClass('btn-success').html(originalText).prop('disabled', false); }, 1000);
                    
                    var stockId = btn.data('stock-id');
                    if (res.is_mag && stockId && $('#' + stockId).length > 0) {
                        var currentStock = parseInt($('#' + stockId).text());
                        var newStock = currentStock - res.deducted_qty;
                        $('#' + stockId).text(newStock);
                        if (newStock <= 0) $('#' + stockId).css('color', 'red');
                        $('#' + stockId).fadeOut(100).fadeIn(100);
                    }
                }
                if (closeAltModal) { $('#alternativesModal').modal('hide'); }
            } else { if(btn) btn.prop('disabled', false).html(originalText); alert('Błąd dodawania.'); }
        },
        error: function() { if(btn) btn.prop('disabled', false).html(originalText); alert('Błąd połączenia.'); }
    });
}

function refreshExtraTable() {
    var url = 'index.php?controller=AdminModulExtra&token=' + token_admin_extra + '&action=getExtraTable&ajax=1';
    if (typeof ajax_add_extra_url !== 'undefined') url = ajax_add_extra_url + '&action=getExtraTable';
    $.ajax({ url: url, type: 'GET', dataType: 'json', success: function(res) { if (res.html) { $('#extra_items_table tbody').html(res.html); } } });
}