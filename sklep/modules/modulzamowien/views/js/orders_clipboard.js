/**
 * orders_clipboard.js
 * Obsługa kopiowania zamówień do schowka (Tab 3)
 */

$(document).ready(function() {
    // --- POPRAWIONA FUNKCJA KOPIOWANIA: SUMOWANIE ILOŚCI ---
    $(document).on('click', '.btn-copy-archive', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var targetId = $btn.data('target-id'); // ID ukrytego textarea
        var supplier = $btn.data('supplier');
        var cost = $btn.data('cost');
        var itemsRaw = $btn.data('items'); // JSON z produktami
        var panelId = $btn.data('panel-id');

        // 1. Logika sumowania EAN-ów
        var grouped = {};

        // --- DETEKCJA DOSTAWCY ---
        var supUpper = supplier ? supplier.toUpperCase() : '';
        var shouldStripZeros = (supUpper.indexOf('EKOWIT') !== -1 || supUpper.indexOf('NATURA') !== -1);

        if (itemsRaw && itemsRaw.length > 0) {
            itemsRaw.forEach(function(item) {
                var ean = String(item.ean).trim();
                if (!ean) return;

                // --- NOWY WARUNEK: Tylko dla 07... ---
                // Jeśli dostawca to Ekowital/Natura I kod zaczyna się od "07" -> utnij zero
                if (shouldStripZeros && ean.indexOf('07') === 0) {
                    ean = ean.substring(1); // Usuwa pierwszy znak (0), zostaje 7...
                }

                var qty = parseInt(item.qty);
                if (isNaN(qty)) qty = 0;

                if (!grouped[ean]) {
                    grouped[ean] = 0;
                }
                grouped[ean] += qty;
            });
        }

        // 2. Budowanie stringa do schowka
        var finalString = "";
        for (var key in grouped) {
            if (grouped.hasOwnProperty(key)) {
                // Format: EAN [TAB] ILOŚĆ
                finalString += key + "\t" + grouped[key] + "\n";
            }
        }

        // 3. Podmiana treści w textarea i kopiowanie
        var copyTextArea = document.getElementById(targetId);
        if (copyTextArea) {
            copyTextArea.value = finalString;
            copyTextArea.select();
            try {
                document.execCommand('copy');
            } catch (e) {
                alert('Błąd kopiowania do schowka. Spróbuj ręcznie.');
            }
        }

        // 4. Zapis do historii (Ajax)
        $btn.html('<i class="icon-spin icon-spinner"></i> ZAPISYWANIE...').prop('disabled', true).removeClass('btn-primary').addClass('btn-warning');

        $.ajax({
            url: history_save_url,
            type: 'POST',
            data: {
                supplier: supplier,
                cost: cost,
                items: JSON.stringify(itemsRaw)
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $btn.removeClass('btn-warning').addClass('btn-success').html('<i class="icon-check"></i> SKOPIOWANO I ZAPISANO');
                    var $panel = $('#' + panelId);
                    $panel.css({
                        'opacity': '0.6',
                        'border-color': '#5cb85c'
                    });
                    if ($panel.find('.label-archive').length === 0) $panel.find('.panel-heading').append(' <span class="label label-success pull-right label-archive">ARCHIWUM</span>');
                } else alert("Błąd zapisu historii!");
            }
        });
    });

    // Odświeżanie zakładki orders
    $('a[data-toggle="tab"][href="#orders"]').on('click', function() {
        var $targetDiv = $('#orders');
        $targetDiv.html('<div style="text-align:center; padding:50px;"><i class="icon-refresh icon-spin icon-4x"></i><br><h3>Przeliczanie...</h3></div>');
        $.ajax({
            url: ajax_refresh_url,
            type: 'GET',
            success: function(h) {
                $targetDiv.html(h);
            }
        });
    });
});