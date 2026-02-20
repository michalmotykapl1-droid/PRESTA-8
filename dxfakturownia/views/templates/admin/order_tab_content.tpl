{* PLIK: modules/dxfakturownia/views/templates/admin/order_tab_content.tpl *}

<div id="dx-buffer" style="display:none;">

    <li class="nav-item" id="dx-tab-li">
        <a class="nav-link" id="dxfakturownia-tab-link" href="#" role="tab">
            <i class="material-icons">description</i> 
            Fakturownia DX 
            {if isset($dx_count) && $dx_count > 0}
                <span class="badge badge-info">{$dx_count}</span>
            {/if}
        </a>
    </li>

    <div class="tab-pane fade" id="dxfakturownia-content" role="tabpanel">
        <div class="card" style="margin-top: 15px; border: 1px solid #dfdfdf;">
            <div class="card-header">
                <i class="icon-file-text"></i> Dokumenty Fakturownia (DX)
            </div>
            <div class="card-body">
                {if isset($dx_invoices) && count($dx_invoices) > 0}
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Typ</th>
                                    <th>Numer</th>
                                    <th>Kwota</th>
                                    <th>Status</th>
                                    <th class="text-right">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$dx_invoices item=inv}
                                    <tr>
                                        <td>{dateFormat date=$inv.sell_date full=0}</td>
                                        <td>
                                            <strong>
                                                {if $inv.kind == 'vat'}Faktura VAT
                                                {elseif $inv.kind == 'receipt'}Paragon
                                                {elseif $inv.kind == 'correction'}Korekta
                                                {elseif $inv.kind == 'proforma'}Proforma
                                                {else}{$inv.kind|upper}{/if}
                                            </strong>
                                        </td>
                                        <td>{$inv.number}</td>
                                        <td>{displayPrice price=$inv.price_gross}</td>
                                        <td>
                                            {if $inv.status == 'paid'}
                                                <span class="badge badge-success" style="background-color:#28a745; color:white;">Opłacona</span>
                                            {else}
                                                <span class="badge badge-danger" style="background-color:#dc3545; color:white;">Wystawiona</span>
                                            {/if}
                                        </td>
                                        <td class="text-right">
                                            <div class="btn-group">
                                                <a href="{$inv.view_url}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                                    <i class="icon-search"></i> Podgląd
                                                </a>
                                                <a href="{$inv.view_url}.pdf" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="icon-file-pdf"></i> PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {else}
                    <div class="alert alert-info">
                        Brak dokumentów wystawionych przez Fakturownia (DX).
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>

<script>
    // Używamy funkcji samowywołującej się, żeby nie śmiecić w globalnym scope
    (function() {
        var attempts = 0;
        
        var interval = setInterval(function() {
            attempts++;
            // Szukamy kontenera zakładek (na Twoim screenie widać #order-view-page)
            var $page = $('#order-view-page');
            var $navTabs = $page.find('ul.nav.nav-tabs').first();
            var $tabContent = $page.find('.tab-content').first();
            var $buffer = $('#dx-buffer');

            // Jeśli nie znaleziono po ID, szukamy ogólnie (fallback)
            if (!$navTabs.length) $navTabs = $('ul.nav.nav-tabs').first();
            if (!$tabContent.length) $tabContent = $('.tab-content').first();

            // Jeśli znaleźliśmy miejsce docelowe I nasz bufor
            if ($navTabs.length && $tabContent.length && $buffer.length) {
                clearInterval(interval);

                // --- 1. PRZENOSZENIE ELEMENTÓW ---
                
                // Przenieś przycisk (LI) do paska zakładek
                $navTabs.append($('#dx-tab-li'));

                // Przenieś treść (DIV) do kontenera treści
                $tabContent.append($('#dxfakturownia-content'));

                // Usuń stary kontener (to naprawi problem "podwójnego wyświetlania")
                $buffer.remove();


                // --- 2. NAPRAWA KLIKANIA (Manualna obsługa klas) ---
                
                var $myLink = $('#dxfakturownia-tab-link');
                var $myContent = $('#dxfakturownia-content');

                $myLink.on('click', function(e) {
                    e.preventDefault();
                    
                    // A. Wyłącz wszystkie inne aktywne zakładki
                    $navTabs.find('.nav-link.active').removeClass('active');
                    $tabContent.find('.tab-pane.active').removeClass('active show');

                    // B. Włącz naszą zakładkę
                    $(this).addClass('active');
                    $myContent.addClass('active show');
                });

                // C. Nasłuchuj kliknięć w INNE zakładki, żeby wyłączyć naszą
                // (Szukamy wszystkich linków w pasku, które NIE są naszym linkiem)
                $navTabs.find('.nav-link').not($myLink).on('click', function() {
                    $myLink.removeClass('active');
                    $myContent.removeClass('active show');
                });

                console.log('DX Fakturownia: Zakładka naprawiona (Manual Mode).');
            }

            // Zabezpieczenie przed nieskończoną pętlą (przestań szukać po 5 sekundach)
            if (attempts > 50) clearInterval(interval);

        }, 100); // Sprawdzaj co 100ms
    })();
</script>