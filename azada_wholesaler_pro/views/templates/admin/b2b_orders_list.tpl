{if isset($grouped_data)}
    {foreach from=$grouped_data key=wholesaler_name item=docs}
        <div class="wholesaler-wrapper">
            <div class="wholesaler-header-row">
                <h4 class="wholesaler-title"><i class="icon-building"></i> {$wholesaler_name}</h4>
                <span class="badge" style="background:#eee; color:#666;">{count($docs)}</span>
            </div>
            
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Numer Dokumentu</th>
                        <th>Kwota Netto</th>
                        <th>Status</th>
                        <th class="text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$docs item=doc}
                        <tr>
                            <td style="width:100px;">{$doc.date}</td>

                            <td>
                                <div id="doc-link-{$doc.clean_number}" style="display:inline-block;">
                                    {if $doc.is_downloaded}
                                        <a href="#" class="btn-details-toggle" data-doc="{$doc.number}" style="font-weight:bold; color:#333; text-decoration:none;">
                                            <i class="icon-plus-square" style="color:#25b9d7;"></i> {$doc.number}
                                        </a>
                                    {else}
                                        <span style="font-weight:600; color:#555;">{$doc.number}</span>
                                    {/if}
                                </div>
                                
                                {* BADGE WERYFIKACJI Z FAKTURĄ (ZIELONY) *}
                                {if $doc.is_verified}
                                    <span class="fv-verified-badge" title="Potwierdzone: Plik zgodny z Fakturą">
                                        <i class="icon-check"></i> FV OK
                                    </span>
                                {/if}
                            </td>

                            <td style="font-family:monospace; font-size:13px;">
                                {$doc.netto}
                            </td>

                            <td>
                                <span class="status-pill {$doc.pill_class}">{$doc.status}</span>
                            </td>

                            <td class="text-right" style="white-space:nowrap;">
                                {if $doc.is_downloaded}
                                    <button class="btn btn-outline-success btn-xs" disabled title="Plik znajduje się w bazie">
                                        <i class="icon-check"></i> NA DYSKU CSV
                                    </button>
                                    <button class="btn btn-default btn-xs btn-delete-csv" data-number="{$doc.number}" title="Usuń, aby pobrać ponownie">
                                        <i class="icon-trash"></i>
                                    </button>
                                {else}
                                    {if $doc.system_csv_url}
                                        <button class="btn btn-outline-primary btn-xs btn-download-system-csv" 
                                                id="status-btn-{$doc.clean_number}"
                                                data-wholesaler="{$doc.id_wholesaler}" 
                                                data-url="{$doc.system_csv_url}"
                                                data-number="{$doc.number}"
                                                data-date="{$doc.date}"
                                                data-netto="{$doc.netto}"
                                                data-status="{$doc.status}">
                                            <i class="icon-cloud-download"></i> POBIERZ
                                        </button>
                                        
                                        {* ZNACZNIK DLA AUTOMATU JS *}
                                        {if $auto_download_active}
                                            <span class="js-auto-sys-download" style="display:none;"
                                                data-id-wholesaler="{$doc.id_wholesaler}"
                                                data-url="{$doc.system_csv_url}"
                                                data-number="{$doc.number}"
                                                data-date="{$doc.date}"
                                                data-netto="{$doc.netto}"
                                                data-status="{$doc.status}"
                                                data-clean-number="{$doc.clean_number}">
                                            </span>
                                        {/if}
                                    {else}
                                        <span class="text-muted" style="font-size:10px;">Brak CSV</span>
                                    {/if}
                                {/if}

                                <div class="btn-group" style="display:inline-block; margin-left:5px;">
                                    <button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" style="border:1px solid #ccc; background:#fff;">
                                        <i class="icon-caret-down"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        {foreach from=$doc.options item=opt}
                                            <li>
                                                <a href="{$controller_url}&action=streamFile&url={$opt.url|urlencode}&doc_number={$doc.clean_number}&id_wholesaler={$doc.id_wholesaler}&format_name={$opt.name}" target="_blank">
                                                    <i class="icon-download"></i> Zapisz jako {$opt.name}
                                                </a>
                                            </li>
                                        {/foreach}
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        
                        {* WIERSZ SZCZEGÓŁÓW *}
                        <tr class="details-row" id="details-{$doc.clean_number}" style="display:none;">
                            <td colspan="5" style="padding:0; border:none;">
                                <div class="details-container" style="background:#f9f9f9; border-bottom:2px solid #ddd;">
                                    <div class="text-center" style="padding:20px;">
                                        <i class="icon-refresh icon-spin icon-2x"></i><br>Ładowanie pozycji...
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
    <div class="alert alert-warning">Brak dokumentów do wyświetlenia.</div>
{/if}

<script type="text/javascript">
$(function() {
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
                    error: function() { container.html('<div class="alert alert-danger">Błąd ładowania danych.</div>'); }
                });
            }
        }
    });

    $(document).off('click', '.btn-download-system-csv').on('click', '.btn-download-system-csv', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> ...');

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
                    setTimeout(function(){ location.reload(); }, 500);
                } else {
                    alert('Błąd: ' + res.msg);
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Błąd połączenia.');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>