<style>
    /* NOWOCZESNY DESIGN (STYL FAKTURY) */
    .wholesaler-wrapper { 
        margin-bottom: 30px; border: 1px solid #e6e6e6; background: #fff; border-radius: 4px; 
        box-shadow: 0 1px 3px rgba(0,0,0,0.03); 
    }
    .wholesaler-header-row { 
        background: #fff; border-bottom: 2px solid #25b9d7; padding: 15px; 
        margin-bottom: 0; display:flex; justify-content: space-between; align-items: center; 
    }
    .wholesaler-title { 
        margin: 0; font-size: 15px; color: #444; 
        font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; 
    }
    
    /* TABELA MODERN */
    .table-modern { margin-bottom: 0; }
    .table-modern th { 
        background: #fbfbfb; border-bottom: 1px solid #eee; 
        font-size: 11px; text-transform: uppercase; color: #888; font-weight: 600; padding: 12px 10px;
    }
    .table-modern td { 
        vertical-align: middle !important; font-size: 12px; color: #555; padding: 12px 10px; border-bottom: 1px solid #f5f5f5;
    }
    .table-modern tr:hover td { background-color: #fcfcfc; }

    /* PIGUŁKI STATUSÓW */
    .status-pill { 
        display: inline-block; padding: 4px 10px; border-radius: 12px; 
        font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1; 
    }
    .pill-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .pill-warning { background-color: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
    .pill-danger  { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    .pill-default { background-color: #f5f5f5; color: #777; border: 1px solid #e0e0e0; }
    
    /* BADGE FV OK */
    .fv-verified-badge {
        display: inline-flex; align-items: center; gap: 4px;
        background: #e0f2f1; color: #00695c; 
        border: 1px solid #80cbc4;
        border-radius: 4px; padding: 2px 6px; 
        font-size: 10px; font-weight: 700; margin-left: 8px;
        text-transform: uppercase;
    }

    /* PRZYCISKI OUTLINE */
    .btn-outline { background: #fff; border: 1px solid #ccc; color: #555; transition: all 0.2s; font-size: 11px; font-weight: 600; }
    .btn-outline:hover { background: #f5f5f5; border-color: #aaa; color: #333; }
    
    .btn-outline-success { background: #fff; border: 1px solid #2ecc71; color: #2ecc71; cursor: default; }
    .btn-outline-success:hover { background: #f0fff4; }

    .btn-outline-primary { background: #fff; border: 1px solid #25b9d7; color: #25b9d7; }
    .btn-outline-primary:hover { background: #25b9d7; color: #fff; }

    /* LOADING OVERLAY */
    #azada-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); z-index: 9999; display: none; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
    .azada-progress-container { width: 50%; max-width: 600px; background: #e9ecef; border-radius: 20px; height: 24px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px; }
    .azada-progress-bar { height: 100%; width: 0%; background: #28a745; transition: width 0.3s ease; background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent); background-size: 1rem 1rem; animation: progress-bar-stripes 1s linear infinite; }
    @keyframes progress-bar-stripes { from { background-position: 1rem 0; } to { background-position: 0 0; } }
    .azada-title { font-size: 24px; color: #333; margin-bottom: 10px; font-weight: 300; }
    .azada-subtitle { font-size: 16px; color: #666; }
    .azada-count { font-weight: bold; color: #28a745; }
    
    /* MODAL RAPORTU */
    #import-summary-modal { display:none; position:fixed; z-index:10000; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
    .report-card { background:#fff; width:90%; max-width:500px; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.2); overflow:hidden; animation: slideIn 0.3s ease; }
    .report-header { background:#f8f9fa; padding:15px 20px; border-bottom:1px solid #eee; font-size:16px; font-weight:bold; color:#333; display:flex; justify-content:space-between; align-items:center; }
    .report-body { padding:20px; }
    .report-footer { padding:15px 20px; background:#f8f9fa; border-top:1px solid #eee; text-align:right; }
    .stat-box { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f5f5f5; }
    .stat-val { font-weight:bold; font-size:14px; }
    @keyframes slideIn { from { transform:translateY(-20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
</style>

<div id="azada-overlay">
    <div class="azada-title">Aktualizacja listy zamówień...</div>
    <div class="azada-subtitle">Przetwarzanie: <span id="azada-counter" class="azada-count">0/0</span></div>
    <div class="azada-progress-container"><div id="azada-bar" class="azada-progress-bar"></div></div>
</div>

<div id="import-summary-modal">
    <div class="report-card">
        <div class="report-header">
            <span><i class="icon-clipboard"></i> Raport z pobierania</span>
        </div>
        <div class="report-body">
            <div class="stat-box">
                <span class="text-muted">Przetworzone pliki:</span>
                <span class="stat-val" id="rep-total">0</span>
            </div>
            <div class="stat-box">
                <span style="color:#27ae60;"><i class="icon-check"></i> Pobrano poprawnie:</span>
                <span class="stat-val" style="color:#27ae60;" id="rep-success">0</span>
            </div>
            <div class="stat-box">
                <span style="color:#c0392b;"><i class="icon-warning"></i> Błędy:</span>
                <span class="stat-val" style="color:#c0392b;" id="rep-errors">0</span>
            </div>
            
            <div id="rep-error-list" style="margin-top:15px; display:none; max-height:100px; overflow-y:auto; background:#fff5f5; border:1px solid #ffcccc; padding:10px; font-size:11px; color:#c0392b;"></div>
            
            <div class="alert alert-info" style="margin-top:20px; margin-bottom:0; font-size:12px;">
                Proces zakończony. Kliknij poniżej, aby odświeżyć listę.
            </div>
        </div>
        <div class="report-footer">
            <button class="btn btn-primary" onclick="location.reload();">
                <i class="icon-refresh"></i> Zamknij i Odśwież
            </button>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-heading"><i class="icon-file-text"></i> Dokumenty CSV (B2B)</div>
    <div id="loader-screen" style="padding: 60px; text-align: center;">
        <div style="margin-bottom: 20px;"><i class="icon-spinner icon-spin" style="font-size: 64px; color: #25b9d7;"></i></div>
        <h3 style="color: #555; font-weight: 300;">Łączenie z hurtowniami...</h3>
    </div>
    <div id="orders-list-container" style="display:none;"></div>
    <div id="refresh-footer" style="text-align:right; margin-top:20px; display:none;">
        <button class="btn btn-default" onclick="location.reload();"><i class="icon-refresh"></i> Odśwież widok</button>
    </div>
</div>

<script>
    var autoDownloadActive = {$auto_download_active|intval}; 
    var downloadQueue = [];
    var totalItems = 0; var processedItems = 0;
    var controllerUrl = "{$controller_url|escape:'javascript':'UTF-8'}";
    
    // Zmienna na statystyki
    var stats = {
        success: 0,
        errors: 0,
        errorMsgs: []
    };

    {literal}
    $(document).ready(function(){ 
        loadOrdersList(); 
    });

    function loadOrdersList() {
        $.ajax({
            url: controllerUrl, 
            data: { ajax: 1, action: "fetchList" }, 
            dataType: "json",
            success: function(res) {
                $("#loader-screen").fadeOut(200, function(){ $("#orders-list-container").fadeIn(200); $("#refresh-footer").fadeIn(200); });
                
                if(res.status == "success") { 
                    $("#orders-list-container").html(res.html); 
                    if(autoDownloadActive) { 
                        initBulkDownload(); 
                    } 
                } else { 
                    $("#orders-list-container").html("<div class='alert alert-danger'>Błąd: " + (res.msg || "Nieznany") + "</div>"); 
                }
            },
            error: function() { 
                $("#loader-screen").hide(); 
                $("#orders-list-container").show().html("<div class='alert alert-danger'>Błąd krytyczny połączenia.</div>"); 
            }
        });
    }

    function initBulkDownload() {
        downloadQueue = [];
        $(".js-auto-sys-download").each(function(){ downloadQueue.push($(this)); });
        totalItems = downloadQueue.length; 
        processedItems = 0;
        
        // Reset statystyk
        stats = { success: 0, errors: 0, errorMsgs: [] };
        
        if (totalItems > 0) { 
            $("#azada-overlay").css("display", "flex"); 
            updateProgressUI(); 
            processNextItem();
        }
    }

    function updateProgressUI() {
        $("#azada-counter").text(processedItems + "/" + totalItems);
        var pct = (totalItems > 0) ? Math.round((processedItems / totalItems) * 100) : 0;
        $("#azada-bar").css("width", pct + "%");
    }

    function processNextItem() {
        if (processedItems >= totalItems) { 
            // KONIEC POBIERANIA - POKAŻ RAPORT
            $("#azada-overlay").fadeOut(200, function() {
                showSummaryReport();
            });
            return; 
        }
        
        var $row = downloadQueue[processedItems];
        
        $.ajax({
            url: controllerUrl,
            data: { 
                ajax: 1, action: "downloadSystemCsv", 
                id_wholesaler: $row.data("id-wholesaler"),
                url: $row.data("url"), 
                doc_number: $row.data("number"), 
                doc_date: $row.data("date"), 
                doc_netto: $row.data("netto"), 
                doc_status: $row.data("status")
            },
            dataType: "json",
            success: function(res) {
                if(res.status == "success") {
                    stats.success++;
                    
                    // Aktualizujemy przycisk na liście w tle
                    var cleanNum = $row.data("clean-number");
                    var $statusBtn = $("#status-btn-" + cleanNum);
                    if ($statusBtn.length > 0) {
                        $statusBtn.replaceWith('<button class="btn btn-outline-success btn-xs" disabled><i class="icon-check"></i> NA DYSKU CSV</button>');
                    }
                } else {
                    stats.errors++;
                    stats.errorMsgs.push($row.data("number") + ": " + res.msg);
                }
                
                processedItems++; 
                updateProgressUI(); 
                processNextItem();
            },
            error: function() { 
                stats.errors++;
                stats.errorMsgs.push($row.data("number") + ": Błąd połączenia AJAX");
                processedItems++; 
                updateProgressUI(); 
                processNextItem();
            }
        });
    }

    function showSummaryReport() {
        $("#rep-total").text(totalItems);
        $("#rep-success").text(stats.success);
        $("#rep-errors").text(stats.errors);

        if (stats.errors > 0) {
            var html = "<strong>Szczegóły błędów:</strong><br>";
            $.each(stats.errorMsgs, function(i, msg) {
                html += "- " + msg + "<br>";
            });
            $("#rep-error-list").html(html).show();
        } else {
            $("#rep-error-list").hide();
        }

        $("#import-summary-modal").css("display", "flex");
    }
    {/literal}
</script>