<style>
    /* 1. Ustawienie przycisków Szukaj/Wyczyść jeden pod drugim */
    .table tr.filter th:last-child .btn, 
    .table tr.filter td:last-child .btn {
        display: block !important;       /* Blokowy element (jeden pod drugim) */
        width: 100% !important;          /* Pełna szerokość w kontenerze */
        margin-bottom: 4px !important;   /* Odstęp między przyciskami */
        margin-left: 0 !important;       /* Usunięcie bocznych marginesów */
        text-align: center !important;
    }

    /* 2. Zwężenie ostatniej kolumny (z przyciskami), żeby nie rozpychała tabeli */
    .table tr.filter th:last-child,
    .table tr.filter td:last-child {
        width: 80px !important;          /* Sztywna, mała szerokość */
        white-space: normal !important;  /* Pozwól na zawijanie w pionie */
        vertical-align: bottom !important;
    }

    /* 3. Rozciągnięcie inputów, żeby wykorzystały odzyskane miejsce */
    .table input[type="text"], .table select {
        min-width: 100% !important;
    }
</style>

<div class="panel" id="fakturownia_sync_panel">
    <div class="panel-heading">
        <i class="icon-cloud-download"></i> Synchronizacja z Fakturownia.pl
    </div>
    
    <div class="row">
        <div class="col-md-12">
            
            <div id="auto_sync_status" class="alert alert-info" style="display:none; margin-bottom: 15px; padding-left: 50px !important;">
                <i class="icon-refresh icon-spin"></i> Pobieram najnowsze faktury i sprawdzam płatności...
            </div>

            <div id="auto_sync_success" class="alert alert-success" style="display:none; margin-bottom: 15px; padding: 15px 15px 15px 60px !important;">
                <strong>Gotowe!</strong> Lista jest aktualna.
            </div>

            <div class="row" style="display:flex; align-items:center;">
                <div class="col-md-7">
                    <p class="help-block">
                        System automatycznie sprawdza statusy faktur przy wejściu.
                        <br>Użyj przycisku <strong>"Pobierz najnowsze"</strong>, aby ręcznie wymusić sprawdzenie teraz.
                    </p>
                </div>
                <div class="col-md-5 text-right">
                    
                    <button type="button" class="btn btn-primary" id="manual_quick_sync_btn" style="margin-right: 10px;">
                        <i class="icon-refresh"></i> Pobierz najnowsze
                    </button>

                    <div class="btn-group">
                         <button type="button" class="btn btn-default" id="start_sync_btn">
                            <i class="icon-time"></i> Historia (Brakujące)
                        </button>
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right">
                            <li>
                                <a href="#" id="start_full_sync_btn">
                                    <i class="icon-exchange"></i> Wymuś pełną aktualizację (Wszystko)
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

            <div id="sync_progress_container" style="display:none; margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                <p><strong>Trwa pobieranie...</strong> Strona: <span id="current_page_span">1</span> <span id="sync_status_text"></span></p>
                <div class="progress active" style="margin-bottom: 5px;">
                    <div class="progress-bar progress-bar-striped" role="progressbar" 
                         id="sync_progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        <span id="sync_percent">0%</span>
                    </div>
                </div>
                <div id="sync_logs" style="max-height: 80px; overflow-y: auto; font-size: 11px; color: #888; border: 1px solid #f0f0f0; padding: 5px; background:#f9f9f9;"></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var syncUrl = '{$sync_url|escape:'javascript':'UTF-8'}';
    var isFullUpdate = 0;
    var syncPeriod = 'all';
    var RELOAD_KEY = 'fv_just_reloaded';

    $(document).ready(function() {
        if (sessionStorage.getItem(RELOAD_KEY)) {
            sessionStorage.removeItem(RELOAD_KEY);
            $('#auto_sync_success').show().delay(4000).fadeOut();
        } else {
            runQuickSync(false); 
        }

        $('#manual_quick_sync_btn').click(function() {
            runQuickSync(true); 
        });

        $('#start_sync_btn').click(function() {
            isFullUpdate = 0; syncPeriod = 'all'; startSyncProcess();
        });

        $('#start_full_sync_btn').click(function(e) {
            e.preventDefault();
            if (confirm('To pobierze WSZYSTKIE faktury z historii. Kontynuować?')) {
                isFullUpdate = 1; syncPeriod = 'all'; startSyncProcess();
            }
        });
    });

    function runQuickSync(isManual) {
        if (isManual) {
            $('#auto_sync_status').show();
            $('#manual_quick_sync_btn').prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> Pobieranie...');
        } else {
            $('#auto_sync_status').fadeIn();
        }
        
        $.ajax({
            url: syncUrl,
            type: 'POST',
            dataType: 'json',
            data: { ajax: 1, action: 'sync_process', page: 1, full_update: 0, period: 'this_month' },
            success: function(response) {
                if (response.success && (response.added > 0 || response.updated > 0)) {
                    sessionStorage.setItem(RELOAD_KEY, '1');
                    location.reload(); 
                } else {
                    $('#auto_sync_status').fadeOut();
                    if (isManual) {
                        $('#manual_quick_sync_btn').prop('disabled', false).html('<i class="icon-check"></i> Aktualne!');
                        setTimeout(function(){ 
                            $('#manual_quick_sync_btn').html('<i class="icon-refresh"></i> Pobierz najnowsze'); 
                        }, 2000);
                    }
                }
            },
            error: function() { 
                $('#auto_sync_status').hide(); 
                if (isManual) $('#manual_quick_sync_btn').prop('disabled', false).text('Błąd połączenia');
            }
        });
    }

    function startSyncProcess() {
        $('#sync_progress_container').slideDown();
        $('#start_sync_btn').prop('disabled', true);
        $('#manual_quick_sync_btn').prop('disabled', true);
        $('#sync_logs').html('');
        processPage(1);
    }

    function processPage(page) {
        $('#current_page_span').text(page);
        $('#sync_status_text').text('(Pobieranie...)');

        $.ajax({
            url: syncUrl,
            type: 'POST',
            dataType: 'json',
            data: { ajax: 1, action: 'sync_process', page: page, full_update: isFullUpdate, period: syncPeriod },
            success: function(response) {
                if (response.success) {
                    var added = response.added;
                    var updated = response.updated;
                    var hasNext = response.has_next;
                    
                    if(added > 0 || updated > 0) log('Strona ' + page + ': + ' + added + ', ^ ' + updated);
                    else log('Strona ' + page + ': Brak zmian.');
                    
                    var currentWidth = parseFloat($('#sync_progressbar')[0].style.width) || 0;
                    var newWidth = currentWidth + 10; 
                    if (!hasNext) newWidth = 100;
                    updateProgress(newWidth);

                    if (hasNext) processPage(page + 1);
                    else finishSync();
                } else {
                    log('Błąd API: ' + response.message);
                    $('#start_sync_btn').prop('disabled', false);
                    $('#manual_quick_sync_btn').prop('disabled', false);
                }
            },
            error: function() {
                log('Błąd połączenia.');
                $('#start_sync_btn').prop('disabled', false);
                $('#manual_quick_sync_btn').prop('disabled', false);
            }
        });
    }

    function updateProgress(val) {
        $('#sync_progressbar').css('width', val + '%');
    }

    function log(msg) {
        $('#sync_logs').append('<div>' + msg + '</div>');
        var d = $('#sync_logs');
        d.scrollTop(d.prop("scrollHeight"));
    }

    function finishSync() {
        $('#sync_status_text').text('(Gotowe!)');
        updateProgress(100);
        setTimeout(function() { 
            sessionStorage.setItem(RELOAD_KEY, '1'); 
            location.reload(); 
        }, 1500);
    }
</script>