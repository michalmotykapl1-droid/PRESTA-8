{* Ujednolicone szerokości kolumn między hurtowniami *}
<style>
    /* Kontener zapobiegający rozjeżdżaniu się układu */
    .wholesaler-wrapper {
        margin-bottom: 40px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
    }

    .wholesaler-header-row {
        padding: 12px 15px;
        background: #f8f8f8;
        border-bottom: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Klucz do równego wyrównania: Fixed Table Layout */
    .table-modern.table-b2b-unified {
        table-layout: fixed !important;
        width: 100% !important;
        border-collapse: collapse;
        margin: 0 !important;
    }

    .table-modern.table-b2b-unified th,
    .table-modern.table-b2b-unified td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 10px 12px !important;
        box-sizing: border-box;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Definicja szerokości kolumn (Suma musi dawać 100% lub stałą szerokość) */
    .col-date    { width: 100px !important; }
    .col-number  { width: 300px !important; }
    .col-net     { width: 130px !important; }
    .col-gross   { width: 130px !important; }
    .col-status  { width: 200px !important; }
    .col-actions { width: 200px !important; }

    /* Wyrównanie tekstów */
    .table-b2b-unified .text-right { text-align: right !important; }
    .table-b2b-unified .text-center { text-align: center !important; }

    /* Stylizacja statusów */
    .status-pill {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        width: 100%;
        text-align: center;
    }

    .fv-verified-badge {
        background: #27ae60;
        color: #fff;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 3px;
        margin-left: 5px;
        vertical-align: middle;
    }


    .csv-on-disk-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #c8e6c9;
        border-radius: 3px;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }
</style>

{if isset($grouped_data)}
    {foreach from=$grouped_data key=wholesaler_name item=docs}
        <div class="wholesaler-wrapper">
            <div class="wholesaler-header-row">
                <h4 class="wholesaler-title" style="margin:0;">
                    <i class="icon-building"></i> <strong>{$wholesaler_name}</strong>
                </h4>
                <span class="badge" style="background:#25b9d7; color:#fff;">{count($docs)} dokumentów</span>
            </div>
            
            <table class="table table-modern table-b2b-unified">
                <colgroup>
                    <col class="col-date">
                    <col class="col-number">
                    <col class="col-net">
                    <col class="col-gross">
                    <col class="col-status">
                    <col class="col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th class="col-date">Data</th>
                        <th class="col-number">Numer Dokumentu</th>
                        <th class="col-net text-right">Netto</th>
                        <th class="col-gross text-right">Brutto</th>
                        <th class="col-status text-center">Status</th>
                        <th class="col-actions text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$docs item=doc}
                        <tr>
                            <td class="col-date">{$doc.date}</td>

                            <td class="col-number">
                                <div id="doc-link-{$doc.clean_number}" style="display:inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis;">
                                    {if $doc.is_downloaded}
                                        <a href="#" class="btn-details-toggle" data-doc="{$doc.number}" style="font-weight:bold; color:#333; text-decoration:none;">
                                            <i class="icon-plus-square" style="color:#25b9d7;"></i> {$doc.number}
                                        </a>
                                    {else}
                                        <span style="font-weight:600; color:#555;">{$doc.number}</span>
                                    {/if}
                                </div>
                                
                                {if $doc.is_verified}
                                    <span class="fv-verified-badge" title="Potwierdzone: Plik zgodny z Fakturą">
                                        <i class="icon-check"></i> FV OK
                                    </span>
                                {/if}
                            </td>

                            <td class="col-net text-right" style="font-family:monospace;">
                                <strong>{$doc.netto}</strong>
                            </td>

                            <td class="col-gross text-right" style="font-family:monospace;">
                                <strong>{$doc.brutto}</strong>
                            </td>

                            <td class="col-status text-center">
                                <span class="status-pill {$doc.pill_class}">{$doc.status}</span>
                            </td>

                            <td class="col-actions text-right">
                                {if $doc.is_downloaded}
                                    <span class="csv-on-disk-badge" title="Plik CSV jest już zapisany na dysku">
                                        <i class="icon-check"></i> CSV NA DYSKU
                                    </span>
                                    <button class="btn btn-default btn-xs btn-delete-csv" data-number="{$doc.number}" title="Usuń" style="margin-left:5px;">
                                        <i class="icon-trash"></i>
                                    </button>
                                {else}
                                    {if $doc.system_csv_url}
                                        <span class="js-auto-sys-download" style="display:none;"
                                              data-id-wholesaler="{$doc.id_wholesaler}"
                                              data-url="{$doc.system_csv_url}"
                                              data-number="{$doc.number}"
                                              data-date="{$doc.date}"
                                              data-netto="{$doc.netto}"
                                              data-status="{$doc.status}"
                                              data-clean-number="{$doc.clean_number}"></span>

                                        <button class="btn btn-primary btn-xs btn-download-system-csv" 
                                                id="status-btn-{$doc.clean_number}"
                                                data-wholesaler="{$doc.id_wholesaler}" 
                                                data-url="{$doc.system_csv_url}"
                                                data-number="{$doc.number}"
                                                data-date="{$doc.date}"
                                                data-netto="{$doc.netto}"
                                                data-status="{$doc.status}">
                                            <i class="icon-cloud-download"></i> POBIERZ
                                        </button>
                                    {else}
                                        <span class="text-muted" style="font-size:10px;">Brak danych</span>
                                    {/if}
                                {/if}

                                <div class="btn-group" style="margin-left:5px;">
                                    <button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                        <i class="icon-caret-down"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        {foreach from=$doc.options item=opt}
                                            <li>
                                                <a href="{$controller_url}&action=streamFile&url={$opt.url|urlencode}&doc_number={$doc.clean_number}&id_wholesaler={$doc.id_wholesaler}&format_name={$opt.name}" target="_blank">
                                                    <i class="icon-file-text"></i> {$opt.name}
                                                </a>
                                            </li>
                                        {/foreach}
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        
                        <tr class="details-row" id="details-{$doc.clean_number}" style="display:none;">
                            <td colspan="6" style="padding:0 !important; border:none;">
                                <div class="details-container" style="background:#fcfcfc; border: 1px solid #eee; margin: 5px 15px 15px 15px; border-radius: 3px;">
                                    <div class="text-center" style="padding:20px;">
                                        <i class="icon-refresh icon-spin icon-2x"></i><br>Pobieranie pozycji dokumentu...
                                    </div>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {/foreach}
{else}
    <div class="alert alert-warning">Nie znaleziono dokumentów w wybranym zakresie dat.</div>
{/if}

<script type="text/javascript">
$(function() {
    // Obsługa rozwijania szczegółów
    $(document).off('click', '.btn-details-toggle').on('click', '.btn-details-toggle', function(e) {
        e.preventDefault();
        var btn = $(this);
        var docNumRaw = btn.data('doc');
        var docNumClean = docNumRaw.replace(/[^a-z0-9]/gi, '');
        var targetRow = $('#details-' + docNumClean);
        var icon = btn.find('i');
        
        if (targetRow.is(':visible')) {
            targetRow.hide();
            icon.removeClass('icon-minus-square').addClass('icon-plus-square');
        } else {
            targetRow.show();
            icon.removeClass('icon-plus-square').addClass('icon-minus-square');
            var container = targetRow.find('.details-container');
            
            if (container.find('.icon-refresh').length > 0) {
                $.ajax({
                    url: '{$controller_url|escape:'javascript':'UTF-8'}&action=getOrderDetails&ajax=1',
                    type: 'POST',
                    data: { doc_number: docNumRaw },
                    success: function(data) { container.html(data); },
                    error: function() { container.html('<div class="alert alert-danger">Wystąpił błąd podczas ładowania pozycji.</div>'); }
                });
            }
        }
    });

    // Obsługa pobierania pliku systemowego CSV
    $(document).off('click', '.btn-download-system-csv').on('click', '.btn-download-system-csv', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i>');

        $.ajax({
            url: '{$controller_url|escape:'javascript':'UTF-8'}&action=downloadSystemCsv&ajax=1',
            type: 'POST',
            dataType: 'json',
            data: {
                id_wholesaler: btn.data('wholesaler'),
                url: btn.data('url'),
                doc_number: btn.data('number'),
                doc_date: btn.data('date'),
                doc_netto: btn.data('netto'),
                doc_status: btn.data('status')
            },
            success: function(res) {
                if (res.status === 'success') {
                    showSuccessMessage("Pobrano plik " + btn.data('number'));
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    alert('Błąd pobierania: ' + res.msg);
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Błąd połączenia z serwerem.');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
