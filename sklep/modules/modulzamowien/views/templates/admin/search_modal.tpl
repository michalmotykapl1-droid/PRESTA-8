{* MODAL: WYSZUKIWARKA PRODUKTÓW (SKANER) *}
{* Wersja: FLOW CONTROL + FOCUS FIX + RECEPTION CONTROLLER *}

<div id="search_modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border: 2px solid #17a2b8; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            
            {* NAGŁÓWEK *}
            <div class="modal-header" style="background-color: #17a2b8; color: white; padding: 15px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:white; opacity:0.8; font-size: 30px;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h3 class="modal-title" style="margin:0; font-weight:bold; font-size: 20px;">
                    <i class="icon-search"></i> ZESKANUJ LUB WPISZ KOD EAN
                </h3>
            </div>
            
            <div class="modal-body" style="padding: 30px; background-color: #f8f8f8;">
                <div class="form-group" style="margin-bottom: 30px;">
                    <div class="input-group input-group-lg">
                        <span class="input-group-addon" style="background: white; border-right: 0;"><i class="icon-barcode"></i></span>
                        <input type="text" id="scanner_input" class="form-control" placeholder="Zeskanuj EAN lub wpisz nazwę..." style="height: 60px; font-size: 24px; font-weight: bold; border-left: 0;" autocomplete="off">
                        <span class="input-group-btn">
                            <button class="btn btn-info" type="button" id="btn_search_trigger" style="height: 60px; font-size: 18px; font-weight: bold;">
                                <i class="icon-search"></i> SZUKAJ
                            </button>
                        </span>
                    </div>
                    <p class="help-block" style="text-align: center; margin-top: 10px; font-size: 14px; color: #777;">
                        <i class="icon-info-sign"></i> System przeszukuje listę Wirtualnego Magazynu (SQL).
                    </p>
                </div>
                <div id="search_msg_container" style="display:none; margin-top: 15px; font-size: 16px; text-align: center;"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

    // 1. Zabezpieczenie: Pobranie URL do kontrolera RECEPTION
    function getAjaxUrl() {
        // FIX: Używamy ID inputa wskazującego na ReceptionController
        var url = $('#mz_reception_controller_url').val();
        if (!url) {
            // Fallback (powinien być niepotrzebny, ale dla bezpieczeństwa)
            console.error('Brak URL do ReceptionController!');
            return 'index.php'; 
        }
        return url;
    }

    // 2. Główna funkcja wyszukująca
    function runScannerSearch() {
        var inputObj = $('#search_modal').find('input#scanner_input');
        var ean = inputObj.val();
        if (!ean) ean = ''; ean = ean.trim();

        if (ean.length < 3) {
            alert('Błąd odczytu pola!'); return;
        }

        var btn = $('#search_modal').find('#btn_search_trigger');
        var msgBox = $('#search_msg_container');
        btn.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> SZUKAM...');
        msgBox.hide().removeClass('alert-danger alert-success');

        $.ajax({
            url: getAjaxUrl() + '&action=checkSurplus',
            type: 'POST',
            data: { ean: ean },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="icon-search"></i> SZUKAJ');
                if (res.success) {
                    inputObj.val(''); 
                    $('#search_modal').modal('hide');
                    window.mz_is_scanner_flow = true;
                    openReceptionModalDirect(res.product); 
                } else {
                    msgBox.addClass('alert alert-danger').html('<i class="icon-warning-sign"></i> Nie znaleziono produktu: <b>' + ean + '</b>').slideDown();
                    inputObj.select();
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).html('<i class="icon-search"></i> SZUKAJ');
                msgBox.addClass('alert alert-danger').html('Błąd połączenia: ' + error).slideDown();
            }
        });
    }

    $('#search_modal').on('click', '#btn_search_trigger', function(e) { e.preventDefault(); runScannerSearch(); });
    $('#search_modal').on('keypress', '#scanner_input', function(e) { if(e.which == 13) { e.preventDefault(); runScannerSearch(); } });

    function openReceptionModalDirect(product) {
        $('#rec_prod_name').text(product.name);
        $('#rec_prod_ean').text('EAN: ' + product.ean);
        $('#rec_prod_max').text(product.qty);
        $('#rec_qty').val(product.qty); 

        var nextYear = new Date(); nextYear.setFullYear(nextYear.getFullYear() + 1);
        try { $('#rec_expiry').val(nextYear.toISOString().slice(0,10)); } catch(e){}

        var lastType = localStorage.getItem('mz_last_type') || 'regal';
        if (lastType === 'kosz') {
            $('#btn_type_kosz').click(); 
            $('#fg-regal, #fg-polka').hide(); $('#fg-kosz-nr').show();
            var lk = localStorage.getItem('mz_last_kosz'); if(lk) $('#rec_kosz_nr').val(lk);
        } else {
            $('#btn_type_regal').click();
            $('#fg-kosz-nr').hide(); $('#fg-regal, #fg-polka').show();
            var lr = localStorage.getItem('mz_last_regal'); var lp = localStorage.getItem('mz_last_polka');
            if(lr) $('#rec_regal').val(lr); if(lp) $('#rec_polka').val(lp);
        }

        $('#reception_modal').modal('show');
        
        setTimeout(function() { 
            if (lastType === 'kosz') {
                if ($('#rec_kosz_nr').val()) $('#rec_expiry').focus(); else $('#rec_kosz_nr').focus();
            } else {
                if ($('#rec_regal').val() && $('#rec_polka').val()) $('#rec_expiry').focus(); else $('#rec_regal').focus();
            }
        }, 500);
    }
    
    $('#search_modal').on('shown.bs.modal', function () {
        var input = $(this).find('#scanner_input');
        input.val(''); input.focus();
    });
});
</script>