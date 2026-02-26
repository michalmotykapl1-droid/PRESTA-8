<style>
    #azada-overlay { 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(255, 255, 255, 0.95); z-index: 9999; 
        display: none; flex-direction: column; justify-content: center; align-items: center; text-align: center; 
    }
    .azada-progress-container { 
        width: 50%; max-width: 600px; background: #e9ecef; border-radius: 20px; 
        height: 24px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px; 
    }
    .azada-progress-bar { 
        height: 100%; width: 0%; background: #28a745; 
        transition: width 0.3s ease; 
        background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent); 
        background-size: 1rem 1rem; animation: progress-bar-stripes 1s linear infinite; 
    }
    @keyframes progress-bar-stripes { from { background-position: 1rem 0; } to { background-position: 0 0; } }
    .azada-title { font-size: 24px; color: #333; margin-bottom: 10px; font-weight: 300; }
    .azada-subtitle { font-size: 16px; color: #666; }
    .azada-count { font-weight: bold; color: #28a745; }
</style>

<div id="azada-overlay">
    <div class="azada-title">Trwa automatyczna analiza faktur...</div>
    <div class="azada-subtitle">Sprawdzanie pozycji: <span id="azada-counter" class="azada-count">0/0</span></div>
    <div class="azada-progress-container">
        <div id="azada-bar" class="azada-progress-bar"></div>
    </div>
    <div style="margin-top:10px; font-size:12px; color:#999;">Proszę nie zamykać okna.</div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-check-square-o"></i> Weryfikacja Faktur (Analiza Rozbieżności)
    </div>
    
    <div class="alert alert-info">
        Moduł automatycznie paruje Faktury z Zamówieniami na podstawie <strong>zawartości pozycji</strong> (EAN/SKU/ID),
        analizując dokumenty z dnia faktury oraz z dnia poprzedniego (<strong>D-1</strong>).<br>
        System wybiera najlepsze dopasowanie po pokryciu pozycji i ilości, a następnie liczy różnice.
    </div>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>Data</th>
                <th>Hurtownia</th>
                <th>Numer Faktury (FV)</th>
                <th>Powiązane Zamówienia (Kandydaci)</th>
                <th>Status Weryfikacji</th>
                <th class="text-right">Akcje</th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$invoices item=inv}
            <tr id="row-inv-{$inv.id_invoice}">
                <td>{$inv.doc_date}</td>
                <td class="text-muted">{$inv.wholesaler_name}</td>
                
                <td>
                    <div style="font-weight:bold; font-size:13px; color:#333;">{$inv.doc_number}</div>
                    <div style="font-size:11px; color:#666; margin-top:3px;">
                        Kwota FV: <strong style="color:#000;">{$inv.amount_netto}</strong>
                    </div>
                </td>
                
                <td style="vertical-align:top; background-color:#fbfbfb; border-left:1px solid #eee; border-right:1px solid #eee;">
                    {if isset($inv.candidate_orders) && $inv.candidate_orders|@count > 0}
                        <div style="font-size:11px;">
                        {foreach from=$inv.candidate_orders item=ord}
                            <div style="margin-bottom:3px; padding:3px; border-bottom:1px solid #f0f0f0;">
                                <span class="label label-{$ord.badge_class}" style="margin-right:5px; font-size:9px;">{$ord.status}</span>
                                <strong>{$ord.external_doc_number}</strong> 
                                <span class="text-muted pull-right">{$ord.amount_netto}</span>
                            </div>
                        {/foreach}
                        
                        <div style="margin-top:5px; padding-top:5px; border-top:2px solid #ddd; text-align:right;">
                            <small class="text-muted">SUMA ZAMÓWIEŃ:</small><br>
                            <strong style="color:#25b9d7; font-size:12px;">{$inv.candidate_sum_formatted}</strong>
                        </div>
                        </div>
                    {else}
                        <span class="text-muted" style="font-style:italic;">Brak zamówień z tą datą</span>
                    {/if}
                </td>

                <td id="cell-status-{$inv.id_invoice}">
                    <span class="label label-{$inv.status_color}" style="font-size:11px; padding:4px 8px;">{$inv.status_label}</span>
                    {if $inv.total_diff_net != 0}
                         <span style="color:#e74c3c; font-weight:bold; margin-left:10px;">({$inv.total_diff_net} PLN)</span>
                    {/if}
                </td>

                <td class="text-right">
                    {if $inv.can_expand}
                        <button class="btn btn-default btn-sm js-show-diffs" data-id="{$inv.id_invoice}" data-analysis="{$inv.id_analysis}" title="Pokaż błędy">
                            <i class="icon-eye"></i> Pokaż Różnice
                        </button>
                    {/if}

                    {if isset($inv.is_correction) && !$inv.is_correction}
                        <button class="btn btn-primary btn-sm js-run-analysis" data-id="{$inv.id_invoice}" title="Uruchom sprawdzanie">
                            <i class="icon-refresh"></i> Analizuj
                        </button>
                    {/if}
                </td>
            </tr>
            <tr class="details-row" id="details-{$inv.id_invoice}" style="display:none;">
                <td colspan="6" style="background-color:#fdfdfd; padding:20px; border-left:3px solid #d9534f;">
                    <div class="diff-container-content"><i class="icon-spinner icon-spin"></i> Ładowanie...</div>
                </td>
            </tr>
        {foreachelse}
            <tr>
                <td colspan="6" class="text-center text-muted" style="padding:30px;">
                    Brak pobranych faktur w bazie. Przejdź do zakładki "Faktury Zakupu" i pobierz pliki CSV.
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>

<script>
    var controllerUrl = "{$controller_url|escape:'javascript':'UTF-8'}";
    
    // Pobieramy kolejkę z kontrolera (jeśli pusta, to [])
    var autoQueue = {$auto_queue}; 
    var totalItems = autoQueue.length;
    var processedItems = 0;

    $(document).ready(function() {
        
        // --- AUTO START ANALIZY ---
        if (totalItems > 0) {
            $("#azada-overlay").css("display", "flex");
            updateProgressUI();
            processNextItem();
        }

        function updateProgressUI() {
            $("#azada-counter").text(processedItems + "/" + totalItems);
            var pct = (totalItems > 0) ? Math.round((processedItems / totalItems) * 100) : 0;
            $("#azada-bar").css("width", pct + "%");
        }

        function processNextItem() {
            if (processedItems >= totalItems) {
                setTimeout(function(){ 
                    // Jednorazowy reload po auto-analizie (bez ponownego auto-startu).
                    var target = controllerUrl + '&analyzed=1';
                    window.location.href = target;
                }, 1000);
                return;
            }

            var idToProcess = autoQueue[processedItems];

            $.ajax({
                type: 'POST',
                url: controllerUrl,
                data: { ajax: 1, action: 'runAnalysis', id_invoice: idToProcess },
                dataType: 'json',
                success: function(res) {
                    // Sukces lub błąd - i tak idziemy dalej
                    processedItems++;
                    updateProgressUI();
                    processNextItem();
                },
                error: function() {
                    // Błąd połączenia - też idziemy dalej
                    processedItems++;
                    updateProgressUI();
                    processNextItem();
                }
            });
        }
        // ---------------------------

        $('.js-run-analysis').on('click', function(e) {
            e.preventDefault();
            var btn = $(this);
            var idInv = btn.data('id');
            var originalIcon = btn.html();

            btn.html('<i class="icon-spin icon-spinner"></i>').prop('disabled', true);

            $.ajax({
                type: 'POST',
                url: controllerUrl,
                data: { ajax: 1, action: 'runAnalysis', id_invoice: idInv },
                dataType: 'json',
                success: function(res) {
                    if (res.status == 'success') {
                        location.reload(); 
                    } else {
                        alert('Błąd: ' + res.msg);
                        btn.html(originalIcon).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Błąd połączenia.');
                    btn.html(originalIcon).prop('disabled', false);
                }
            });
        });

        $('.js-show-diffs').on('click', function(e) {
            e.preventDefault();
            var idInv = $(this).data('id');
            var idAnalysis = $(this).data('analysis');
            var row = $('#details-' + idInv);

            if (row.is(':visible')) {
                row.hide();
            } else {
                row.show();
                $.ajax({
                    url: controllerUrl,
                    data: { ajax: 1, action: 'getAnalysisDetails', id_analysis: idAnalysis },
                    success: function(html) {
                        row.find('.diff-container-content').html(html);
                    }
                });
            }
        });
    });
</script>
