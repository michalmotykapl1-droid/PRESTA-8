<style>
    body { background-color: #f6f6f6; padding-bottom: 50px; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin:0; }
    
    /* SKANER */
    .scanner-box { 
        background: #fff; padding: 15px; border-bottom: 2px solid #00aff0; 
        position: sticky; top: 0; z-index: 900; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    #scanner_input { 
        height: 60px; font-size: 24px; text-align: center; border: 2px solid #ccc; 
        border-radius: 4px; width: 100%; color: #333; font-weight: bold; -webkit-appearance: none;
    }
    #scanner_input:focus { border-color: #00aff0; outline: none; }

    /* LISTA */
    .table-mobile { width: 100%; background: #fff; border: 1px solid #ccc; font-size: 14px; border-collapse: collapse; }
    .table-mobile td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
    .table-mobile tr:nth-child(even) { background-color: #fafafa; }
    .badge-qty { background: #f0ad4e; color: #fff; padding: 5px 10px; border-radius: 10px; font-weight: bold; font-size: 14px; }

    /* MODAL GŁÓWNY (PRZYJĘCIE) */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.8); z-index: 9999; display: none;
        align-items: center; justify-content: center;
    }
    .modal-dialog-custom {
        background: #fff; margin: 0 auto; width: 95%; max-width: 500px; 
        border-radius: 8px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.5);
        position: relative; top: 20px;
    }
    .modal-header-custom {
        background-color: #007aff; color: white; padding: 15px; 
        font-size: 18px; font-weight: bold; text-align: center; text-transform: uppercase;
    }
    .modal-body-custom { padding: 20px; }

    /* ERROR MODAL (NOWOCZESNY BŁĄD DATY) */
    .error-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.98); /* Prawie nieprzezroczyste tło */
        z-index: 20000; /* Zawsze na wierzchu */
        display: none; flex-direction: column; align-items: center; justify-content: center;
        backdrop-filter: blur(10px);
    }
    .error-content { text-align: center; width: 85%; max-width: 400px; }
    .error-icon { font-size: 100px; color: #d9534f; margin-bottom: 20px; animation: shake 0.5s; }
    .error-title { font-size: 28px; font-weight: 900; color: #d9534f; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 1px; }
    .error-desc { font-size: 16px; color: #555; margin-bottom: 5px; }
    .error-format { 
        font-size: 45px; font-weight: 800; color: #333; margin: 20px 0; 
        border: 2px dashed #d9534f; padding: 10px; border-radius: 10px; background: #fff5f5;
    }
    .btn-error-close {
        background: #d9534f; color: white; border: none; padding: 18px; width: 100%;
        font-size: 20px; font-weight: bold; border-radius: 50px;
        box-shadow: 0 10px 25px rgba(217, 83, 79, 0.4); cursor: pointer;
        text-transform: uppercase; transition: transform 0.2s;
    }
    .btn-error-close:active { transform: scale(0.95); }

    @keyframes shake {
        0% { transform: translateX(0); } 20% { transform: translateX(-15px); }
        40% { transform: translateX(15px); } 60% { transform: translateX(-15px); }
        80% { transform: translateX(15px); } 100% { transform: translateX(0); }
    }

    /* TABS */
    .location-tabs { display: flex; margin-bottom: 15px; border: 1px solid #007aff; border-radius: 4px; overflow: hidden; }
    .tab-btn { flex: 1; padding: 12px; text-align: center; font-weight: bold; cursor: pointer; background: #fff; color: #007aff; }
    .tab-btn.active { background: #007aff; color: white; }

    /* INPUTS */
    .form-group-custom { margin-bottom: 15px; }
    .label-custom { display: block; font-size: 12px; font-weight: bold; color: #666; margin-bottom: 5px; text-transform: uppercase; }
    .form-control-lg { width: 100%; height: 50px; font-size: 18px; border: 1px solid #ccc; border-radius: 4px; padding: 5px 10px; background: #fff; }

    /* GRUPA Z MIKROFONEM */
    .input-group-mic { display: flex; align-items: center; }
    .input-group-mic input { border-top-right-radius: 0; border-bottom-right-radius: 0; }
    .btn-mic {
        height: 50px; width: 50px; border: 1px solid #ccc; border-left: 0; 
        background: #eee; color: #333; font-size: 20px;
        border-top-right-radius: 4px; border-bottom-right-radius: 4px;
    }
    .btn-mic.listening { background: #d9534f; color: white; animation: pulse 1s infinite; }

    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

    /* ILOŚĆ */
    input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance:textfield; }
    
    .qty-group { display: flex; height: 50px; }
    .btn-qty { width: 60px; font-size: 24px; font-weight: bold; background: #eee; border: 1px solid #ccc; cursor: pointer; display:flex; align-items:center; justify-content:center; }
    .input-qty { flex: 1; text-align: center; font-size: 24px; font-weight: bold; border: 1px solid #ccc; border-left:0; border-right:0; color: #007aff; }

    .btn-green { width: 100%; padding: 15px; font-size: 18px; font-weight: bold; color: white; background: #5cb85c; border: none; border-radius: 4px; margin-top: 10px; cursor: pointer; }
    .btn-green:disabled { background: #ccc; cursor: not-allowed; }
    
    .btn-red { width: 100%; padding: 12px; font-size: 14px; font-weight: bold; color: white; background: #d9534f; border: none; border-radius: 4px; margin-top: 10px; cursor: pointer; }

    #msg_box { text-align: center; padding: 10px; margin-bottom: 10px; display: none; font-weight: bold; }
</style>

<div class="row">
    <div class="col-xs-12">
        <div class="scanner-box">
            <div style="text-align:center; color:#00aff0; font-weight:bold; margin-bottom:5px;">SKANUJ EAN</div>
            <input type="number" id="scanner_input" autofocus autocomplete="off" placeholder="Kliknij tutaj...">
        </div>
        <div id="msg_box"></div>
        <table class="table-mobile">
            <thead><tr><th>Produkt</th><th style="width:80px; text-align:center;">Ilość</th></tr></thead>
            <tbody id="surplus_list"><tr><td colspan="2" class="text-center">Ładowanie...</td></tr></tbody>
        </table>
    </div>
</div>

{* --- MODAL GŁÓWNY --- *}
<div id="stock_modal" class="modal-overlay">
    <div class="modal-dialog-custom">
        <div class="modal-header-custom">PRZYJĘCIE TOWARU</div>
        <div class="modal-body-custom">
            
            <div style="text-align:center; margin-bottom:15px;">
                <h4 id="modal_product_name" style="margin:0 0 5px 0; font-weight:bold; color:#333;">NAZWA</h4>
                <span style="background:#eee; padding:3px 6px; font-size:12px; border-radius:3px;">EAN: <span id="text_ean"></span></span>
                <input type="hidden" id="modal_ean">
                <div style="color:#777; font-size:12px; margin-top:5px;">Dostępne: <b id="modal_max_qty">0</b></div>
            </div>

            <div class="location-tabs">
                <div class="tab-btn active" id="tab_regal" onclick="switchTab('regal')">REGAŁ</div>
                <div class="tab-btn" id="tab_kosz" onclick="switchTab('kosz')">KOSZ</div>
            </div>
            <input type="hidden" id="modal_loc_type" value="regal">

            <div id="box_regal">
                <div class="row">
                    <div class="col-xs-6">
                        <label class="label-custom">REGAŁ</label>
                        <select id="modal_regal" class="form-control-lg">
                            <option value="">-</option>
                            {foreach from=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','R','S','T','U','W','X','Y','Z'] item=r}
                                <option value="{$r}">{$r}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-xs-6">
                        <label class="label-custom">PÓŁKA</label>
                        <select id="modal_polka" class="form-control-lg">
                            <option value="">-</option>
                            {for $i=1 to 10}<option value="{$i}">{$i}</option>{/for}
                        </select>
                    </div>
                </div>
            </div>

            <div id="box_kosz" style="display:none;">
                <label class="label-custom">WYBIERZ KOSZ</label>
                <select id="modal_kosz" class="form-control-lg">
                    <option value="">- Wybierz -</option>
                    {for $k=1 to 20}<option value="KOSZ {$k}">KOSZ {$k}</option>{/for}
                    <option value="PALETA">PALETA</option>
                </select>
            </div>

            <div class="form-group-custom" style="margin-top:15px;">
                <label class="label-custom">DATA WAŻNOŚCI (DD-MM-RR)</label>
                <div class="input-group-mic">
                    <input type="tel" id="modal_date" class="form-control-lg" placeholder="31-12-26" maxlength="10">
                    <button type="button" class="btn-mic" onclick="startDictation()" title="Wybieranie głosowe">
                        <i class="icon-microphone"></i>
                    </button>
                </div>
            </div>

            <div class="form-group-custom">
                <label class="label-custom">ILOŚĆ</label>
                <div class="qty-group">
                    <button type="button" class="btn-qty" onclick="changeQty(-1)">-</button>
                    <input type="number" id="modal_qty" class="input-qty" value="1" max="999999">
                    <button type="button" class="btn-qty" onclick="changeQty(1)">+</button>
                </div>
            </div>

            <button type="button" id="btn_save_mobile" class="btn-green" onclick="confirmStock()">PRZYJMIJ NA STAN</button>
            <button type="button" class="btn-red" onclick="closeModal()">ANULUJ</button>
        </div>
    </div>
</div>

{* --- NOWOCZESNE OKNO BŁĘDU DATY --- *}
<div id="date_error_modal" class="error-overlay">
    <div class="error-content">
        <i class="icon-calendar error-icon"></i>
        <h2 class="error-title">BŁĘDNA DATA!</h2>
        <p class="error-desc">Wymagany format to:</p>
        <div class="error-format">DD-MM-RR</div>
        <p class="error-desc">Przykład: <b>31-12-25</b></p>
        <button type="button" class="btn-error-close" onclick="closeErrorModal()">POPRAW DATĘ</button>
    </div>
</div>

<script type="text/javascript">
    var url_mobile = "{$ajax_mobile_url nofilter}";
</script>

<script type="text/javascript">
{literal}
$(document).ready(function() {
    loadList();
    $('#scanner_input').focus();
    
    $(document).click(function(e) { 
        if(!$('#stock_modal').is(':visible') && !$('#date_error_modal').is(':visible') && !$(e.target).is('input') && !$(e.target).is('select') && !$(e.target).closest('.btn-mic').length) {
           $('#scanner_input').focus();
        }
    });

    $('#scanner_input').on('keypress', function(e) {
        if(e.which == 13) {
            var code = $(this).val().trim();
            if(code.length > 0) openProductModal(code);
            $(this).val('');
        }
    });

    $('#modal_date').on('input', function() {
        var val = $(this).val().replace(/\D/g, ''); 
        var newVal = '';
        if(val.length > 2) {
            newVal += val.substr(0, 2) + '-';
            if(val.length > 4) {
                newVal += val.substr(2, 2) + '-';
                newVal += val.substr(4, 4);
            } else {
                newVal += val.substr(2);
            }
        } else {
            newVal = val;
        }
        $(this).val(newVal); 
    });
});

function startDictation() {
    if (window.hasOwnProperty('webkitSpeechRecognition')) {
        var recognition = new webkitSpeechRecognition();
        recognition.continuous = false; recognition.interimResults = false; recognition.lang = "pl-PL";
        var $btn = $('.btn-mic'); $btn.addClass('listening');

        recognition.onresult = function(e) {
            var transcript = e.results[0][0].transcript;
            var parsedDate = parseSpokenDate(transcript);
            if(parsedDate) {
                $('#modal_date').val(parsedDate);
                $('#modal_date').css('background', '#dff0d8');
                setTimeout(function(){ $('#modal_date').css('background', '#fff'); }, 500);
            } else {
                alert('Nie rozpoznano daty: "' + transcript + '"');
            }
            $btn.removeClass('listening'); recognition.stop();
        };
        recognition.onerror = function(e) { $btn.removeClass('listening'); recognition.stop(); alert('Błąd mikrofonu.'); };
        recognition.onend = function() { $btn.removeClass('listening'); };
        recognition.start();
    } else {
        alert("Brak obsługi głosowej.");
    }
}

function parseSpokenDate(text) {
    text = text.toLowerCase();
    var months = { 'stycznia':'01','styczeń':'01','pierwszy':'01', 'lutego':'02','luty':'02','drugi':'02', 'marca':'03','marzec':'03','trzeci':'03', 'kwietnia':'04','kwiecień':'04','czwarty':'04', 'maja':'05','maj':'05','piąty':'05', 'czerwca':'06','czerwiec':'06','szósty':'06', 'lipca':'07','lipiec':'07','siódmy':'07', 'sierpnia':'08','sierpień':'08','ósmy':'08', 'września':'09','wrzesień':'09','dziewiąty':'09', 'października':'10','październik':'10','dziesiąty':'10', 'listopada':'11','listopad':'11','jedenasty':'11', 'grudnia':'12','grudzień':'12','dwunasty':'12' };
    
    for (var key in months) { if (text.indexOf(key) !== -1) text = text.replace(key, months[key]); }
    
    var numbers = text.match(/\d+/g);
    if (numbers && numbers.length >= 2) {
        var day = numbers[0].padStart(2, '0');
        var month = numbers[1].padStart(2, '0');
        var year = '';
        if(numbers.length >= 3) year = numbers[2];
        else { var currentYear = new Date().getFullYear(); year = currentYear.toString(); }
        
        if(year.length === 2) year = '20' + year;
        return day + '-' + month + '-' + year.substring(2); 
    }
    return null;
}

function switchTab(type) {
    $('#modal_loc_type').val(type);
    if(type === 'regal') {
        $('#tab_regal').addClass('active');
        $('#tab_kosz').removeClass('active');
        $('#box_regal').show(); $('#box_kosz').hide();
        $('#modal_date').val('');
        
        var lr = localStorage.getItem('stock_last_regal');
        var lp = localStorage.getItem('stock_last_polka');
        
        if (lr && ($('#modal_regal').val() == "" || $('#modal_regal').val() == null)) {
            $('#modal_regal').val(lr);
        }
        if (lp && ($('#modal_polka').val() == "" || $('#modal_polka').val() == null)) {
            $('#modal_polka').val(lp);
        }

    } else {
        $('#tab_kosz').addClass('active');
        $('#tab_regal').removeClass('active');
        $('#box_kosz').show(); $('#box_regal').hide();
        
        var lk = localStorage.getItem('stock_last_kosz');
        if(lk && ($('#modal_kosz').val() == "" || $('#modal_kosz').val() == null)) {
            $('#modal_kosz').val(lk);
        }
        
        var d = new Date();
        d.setFullYear(d.getFullYear() + 1); 
        var day = ('0' + d.getDate()).slice(-2);
        var month = ('0' + (d.getMonth() + 1)).slice(-2);
        var year = d.getFullYear().toString().substr(2);
        var autoDate = day + '-' + month + '-' + year;
        $('#modal_date').val(autoDate);
    }
}

function loadList() {
    $.ajax({
        url: url_mobile + '&action=getSurplusList',
        dataType: 'json',
        success: function(res) { renderList(res.data); }
    });
}

function renderList(items) {
    var $tbody = $('#surplus_list');
    $tbody.empty();
    if (!items || items.length === 0) {
        $tbody.html('<tr><td colspan="2" class="text-center" style="padding:20px;"><b>Wszystko rozłożone!</b></td></tr>');
        return;
    }
    items.sort(function(a, b) { return parseInt(b.qty) - parseInt(a.qty); });
    items.forEach(function(item) {
        var html = '<tr><td><b>'+item.name+'</b><br><small>'+item.ean+'</small></td><td class="text-center"><span class="badge-qty">'+item.qty+'</span></td></tr>';
        $tbody.append(html);
    });
}

function openProductModal(ean) {
    $('#scanner_input').blur();
    $.ajax({
        url: url_mobile + '&action=getProductData',
        type: 'POST', dataType: 'json', data: { ean: ean },
        success: function(res) {
            if(res.success) {
                $('#modal_product_name').text(res.name);
                $('#text_ean').text(res.ean);
                $('#modal_ean').val(res.ean);
                
                if(res.is_adhoc) {
                    $('.modal-header-custom').css('background-color', '#ff9900'); 
                    $('.modal-header-custom').html('<i class="icon-warning-sign"></i> PRODUKT SPOZA LISTY'); 
                    $('.modal-dialog-custom').css('border', '4px solid #ff9900'); 
                    $('#modal_max_qty').html('<span style="color:#d35400; font-weight:bold; font-size:14px; text-transform:uppercase;">BRAK NA ZAMÓWIENIU!</span>');
                    $('#modal_qty').val(1); 
                } else {
                    $('.modal-header-custom').css('background-color', '#007aff');
                    $('.modal-header-custom').html('PRZYJĘCIE TOWARU');
                    $('.modal-dialog-custom').css('border', 'none');
                    $('#modal_qty').val(res.qty_surplus); 
                    $('#modal_max_qty').text(res.qty_surplus + ' szt.');
                }
                
                $('#modal_qty').removeAttr('max').attr('max', 999999); 
                $('#modal_date').val('');
                
                var tabToSwitch = null;
                if(res.current_location && res.current_location.trim() !== "") {
                    if(res.current_location.indexOf('KOSZ') !== -1 || res.current_location.indexOf('PALETA') !== -1) {
                        $('#modal_kosz').val(res.current_location);
                    } else {
                        var parts = res.current_location.split(' ');
                        if(parts.length >= 2) { 
                            tabToSwitch = 'regal';
                            $('#modal_regal').val(parts[0]);
                            $('#modal_polka').val(parts[1]); 
                        }
                    }
                }

                if(!tabToSwitch) {
                    tabToSwitch = localStorage.getItem('stock_last_type') || 'regal';
                }
                
                switchTab(tabToSwitch);
                $('#stock_modal').fadeIn(200);
            } else {
                showMsg(res.msg, 'red');
                $('#scanner_input').focus();
            }
        },
        error: function() { showMsg('Błąd połączenia', 'red'); }
    });
}

function confirmStock() {
    var ean = $('#modal_ean').val();
    var qty = $('#modal_qty').val();
    var dateInput = $('#modal_date').val(); 
    
    var type = 'regal';
    if($('#tab_kosz').hasClass('active')) type = 'kosz';
    
    // --- NOWOCZESNA BLOKADA DATY ---
    if (type === 'regal') {
        var datePattern = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-\d{2}$/;
        if (!dateInput || !datePattern.test(dateInput)) { 
            // Otwórz Full Screen Error Modal
            $('#date_error_modal').css('display', 'flex').hide().fadeIn(200);
            return; 
        }
    }
    
    var regal = $('#modal_regal').val();
    var polka = $('#modal_polka').val();
    var kosz = $('#modal_kosz').val();
    var dateFinal = "";
    if(dateInput.length > 0) {
        var cleanDate = dateInput.replace(/\D/g, '');
        if(cleanDate.length >= 6) {
            var d = cleanDate.substr(0, 2);
            var m = cleanDate.substr(2, 2);
            var y = cleanDate.substr(4);
            if(y.length === 2) y = "20" + y;
            dateFinal = y + '-' + m + '-' + d;
        }
    }

    if(qty <= 0) { alert('Ilość musi być > 0'); return; }

    localStorage.setItem('stock_last_type', type);
    if(type === 'regal') {
        if(regal) localStorage.setItem('stock_last_regal', regal);
        if(polka) localStorage.setItem('stock_last_polka', polka);
    } else {
        if(kosz) localStorage.setItem('stock_last_kosz', kosz);
    }

    var $btn = $('#btn_save_mobile'); 
    var originalText = $btn.html();
    $btn.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> ZAPISYWANIE...');

    $.ajax({
        url: url_mobile + '&action=receive_stock', 
        type: 'POST', dataType: 'json',
        data: { 
            ean: ean, qty: qty, 
            location_type: type, 
            regal: regal, polka: polka, kosz: kosz, 
            expiration_date: dateFinal 
        },
        success: function(res) {
            if (res.success) {
                closeModal(); 
                showMsg('Przyjęto pomyślnie!', 'green');
                loadList(); 
            } else {
                alert(res.msg || 'Błąd zapisu');
            }
        },
        error: function() { 
            closeModal(); showMsg('Zapisano!', 'green'); loadList();
        },
        complete: function() {
            setTimeout(function() {
                $btn.prop('disabled', false).html(originalText);
            }, 500); 
        }
    });
}

function closeErrorModal() {
    $('#date_error_modal').fadeOut(200);
    setTimeout(function(){
        $('#modal_date').focus(); // Automatyczny focus na pole daty
    }, 200);
}

function closeModal() { $('#stock_modal').fadeOut(100); $('#scanner_input').val('').focus(); }

function changeQty(d) { 
    var v = parseInt($('#modal_qty').val())||0; 
    var n = v+d; 
    if(n<1) n=1; 
    $('#modal_qty').val(n); 
}

function showMsg(t, c) {
    var bg=(c==='green')?'#dff0d8':'#f2dede'; var fg=(c==='green')?'#3c763d':'#a94442';
    $('#msg_box').text(t).css({background:bg,color:fg,display:'block'}).delay(2000).slideUp();
}
{/literal}
</script>