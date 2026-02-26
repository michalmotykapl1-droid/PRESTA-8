/**
 * admin_extra_partial.js
 * Wersja: 17.0 (Fix: Tabela, Widoczność Inputa, Sortowanie Szukanego Producenta)
 */

$(document).ready(function() {

    // KLIKNIĘCIE "DOBIERZ"
    $(document).on('click', '.btn-fill-gap', function() {
        var btn = $(this);
        var fillEan = btn.data('ean');
        var fillName = btn.data('name');
        var fillSku = btn.data('sku');
        var fillQty = $('#fill_qty_' + btn.data('id')).val();
        
        var origEan = $('#partialStockModal').data('orig-ean');
        var origName = $('#partialStockModal').data('orig-name');
        var origSku = $('#partialStockModal').data('orig-sku');
        var availQty = $('#partialStockModal').data('available-qty');
        
        btn.prop('disabled', true).html('<i class="icon-spin icon-spinner"></i>');

        // 1. Dodaj oryginał
        addSingleItem(origEan, origName, origSku, availQty, 0);

        // 2. Dodaj dopełniacz
        setTimeout(function() {
            addSingleItem(fillEan, fillName, fillSku, fillQty, 0);
            $('#partialStockModal').modal('hide');
            $('#alternativesModal').modal('hide');
        }, 300);
    });

    $(document).on('click', '.btn-mix-force', function() {
        var qty = $(this).data('qty');
        var origEan = $('#partialStockModal').data('orig-ean');
        var origName = $('#partialStockModal').data('orig-name');
        var origSku = $('#partialStockModal').data('orig-sku');
        
        $('#partialStockModal').modal('hide');
        addSingleItem(origEan, origName, origSku, qty, 1);
        $('#alternativesModal').modal('hide'); 
    });

});

// --- GŁÓWNA FUNKCJA OTWIERAJĄCA MODAL ---
function openPartialStockModal(originalBtn, available, needed) {
    var missingQty = needed - available; 
    var origId = originalBtn.data('id-product');
    var origSku = originalBtn.data('sku');
    
    var unitWeight = parseFloat(originalBtn.data('weight')) || 0;
    var missingWeightTotal = missingQty * unitWeight;

    // USTALENIE SZUKANEGO PRODUCENTA
    // Priorytet: To co wpisał klient w wyszukiwarkę (Global Variable z admin_extra.js)
    var targetManufacturer = '';
    if (typeof globalSourceInfo !== 'undefined' && globalSourceInfo.manufacturer) {
        targetManufacturer = globalSourceInfo.manufacturer.toUpperCase().trim();
    } else {
        // Fallback: Producent produktu klikniętego
        targetManufacturer = (originalBtn.data('man-name') || '').toUpperCase().trim();
    }

    $('#partialStockModal').data('orig-ean', originalBtn.data('ean'));
    $('#partialStockModal').data('orig-name', originalBtn.data('name'));
    $('#partialStockModal').data('orig-sku', origSku);
    $('#partialStockModal').data('available-qty', available);

    $('#psm_available').text(available);
    $('#psm_needed').text(needed);
    $('#psm_missing').text(missingQty);
    $('#psm_product_name').text(originalBtn.data('name'));
    
    // --- FILTROWANIE ---
    var fillCandidates = [];
    if (typeof currentAlternativesData !== 'undefined' && currentAlternativesData.length > 0) {
        fillCandidates = currentAlternativesData.filter(function(item) {
            // Ukryj ten sam produkt
            if (item.reference === origSku) return false;
            
            // Smart Weight
            var itemWeight = parseFloat(item.weight) || 0;
            if (missingWeightTotal > 0 && itemWeight > 0) {
                if (itemWeight > (missingWeightTotal * 1.1)) return false;
            }
            return true;
        });
        
        // --- SORTOWANIE (Szukany producent na górę) ---
        fillCandidates.sort(function(a, b) {
            var aMan = (a.manufacturer_name || '').toUpperCase().trim();
            var bMan = (b.manufacturer_name || '').toUpperCase().trim();
            
            var aSame = (aMan === targetManufacturer) ? 1 : 0;
            var bSame = (bMan === targetManufacturer) ? 1 : 0;
            
            if (aSame !== bSame) return bSame - aSame; // 1 (Szukany) wyżej niż 0
            
            // Dalej po wadze
            var aDiff = Math.abs((parseFloat(a.weight)||0) - missingWeightTotal);
            var bDiff = Math.abs((parseFloat(b.weight)||0) - missingWeightTotal);
            return aDiff - bDiff;
        });
    }

    // --- BUDOWANIE TABELI ---
    var listHtml = '';
    if (fillCandidates.length > 0) {
        var weightInfo = (missingWeightTotal > 0) ? ' (ok. ' + missingWeightTotal + ' kg)' : '';
        
        // Wyświetlamy jakiego producenta preferujemy
        var manInfo = (targetManufacturer) ? '<span class="label label-primary" style="margin-left:10px;">Preferowany: '+targetManufacturer+'</span>' : '';

        listHtml += '<h5 style="margin-top:15px; font-weight:bold; color:#007aff;">DOBIERZ BRAKUJĄCE ' + missingQty + ' SZT.' + weightInfo + manInfo + ':</h5>';
        
        // Fix styli tabeli: usunięto table-layout: fixed, dodano min-width dla inputa
        listHtml += '<div class="table-responsive" style="max-height:400px; overflow-y:auto; border:1px solid #eee;">';
        listHtml += '<table class="table table-striped table-hover" style="font-size:13px; margin-bottom:0;">';
        listHtml += '<thead><tr style="background:#f0f0f0;">';
        listHtml += '<th style="width:55%;">Produkt</th>';
        listHtml += '<th style="width:10%;">Waga</th>';
        listHtml += '<th style="width:15%; text-align:center;">Ilość</th>';
        listHtml += '<th style="width:20%; text-align:center;">Akcja</th>';
        listHtml += '</tr></thead><tbody>';
        
        $.each(fillCandidates, function(i, item) {
            var itemMan = (item.manufacturer_name || '').toUpperCase().trim();
            var isSameMan = (itemMan === targetManufacturer);
            
            // Wyraźniejsze oznaczenie pasującego producenta
            var rowStyle = isSameMan ? 'style="background-color:#dff0d8;"' : ''; 
            var badge = isSameMan ? '<div style="color:green; font-weight:bold; font-size:10px; margin-bottom:2px;"><i class="icon-check"></i> SZUKANY PRODUCENT</div>' : '';
            
            var suggestQty = 1;
            var iWeight = parseFloat(item.weight) || 0;
            if (missingWeightTotal > 0 && iWeight > 0) {
                suggestQty = Math.round(missingWeightTotal / iWeight);
                if(suggestQty < 1) suggestQty = 1;
            } else {
                suggestQty = missingQty;
            }

            var cleanName = stripHtml(item.name);
            if(item.name.indexOf('[MAG]') !== -1) cleanName = '★ [MAG] ' + cleanName;

            listHtml += '<tr '+rowStyle+'>';
            
            // KOLUMNA PRODUKT
            listHtml += '<td style="vertical-align:middle;">';
            listHtml += badge;
            listHtml += '<strong>' + item.name + '</strong><br>'; 
            listHtml += '<small class="text-muted">' + (item.manufacturer_name || '') + '</small>';
            listHtml += '</td>';
            
            // KOLUMNA WAGA
            listHtml += '<td style="vertical-align:middle;">'+(iWeight > 0 ? iWeight + ' kg' : '-')+'</td>';
            
            // KOLUMNA ILOŚĆ - POPRAWIONY INPUT
            listHtml += '<td class="text-center" style="vertical-align:middle;">';
            listHtml += '<input type="number" class="form-control" id="fill_qty_'+item.id_product+'" value="'+suggestQty+'" min="1" ';
            listHtml += 'style="width: 70px; margin: 0 auto; text-align: center; font-weight: bold; height: 30px; display:inline-block;">';
            listHtml += '</td>';
            
            // KOLUMNA AKCJA
            listHtml += '<td class="text-center" style="vertical-align:middle;">';
            var dataNameClean = stripHtml(item.name); 
            listHtml += '<button class="btn btn-info btn-sm btn-fill-gap" data-id="'+item.id_product+'" data-ean="'+item.ean13+'" data-name="'+dataNameClean.replace(/"/g, '&quot;')+'" data-sku="'+item.reference+'"><i class="icon-plus"></i> DOBIERZ</button>';
            listHtml += '</td>';
            
            listHtml += '</tr>';
        });
        
        listHtml += '</tbody></table></div>';
    } else {
        listHtml += '<div class="alert alert-warning" style="margin-top:15px;">Brak produktów pasujących wagą.</div>';
    }

    $('#psm_complementary_list_container').html(listHtml);

    var forceBtn = '<hr><button class="btn btn-default btn-block btn-mix-force" data-qty="'+needed+'" style="margin-top:10px;">';
    forceBtn += 'Ignoruj stan magazynowy (Dodaj pełne '+needed+' szt. wybranego produktu)';
    forceBtn += '</button>';
    $('#psm_actions').html(forceBtn); 

    $('#partialStockModal').modal('show');
}

function stripHtml(html) {
   var tmp = document.createElement("DIV");
   tmp.innerHTML = html;
   return tmp.textContent || tmp.innerText || "";
}