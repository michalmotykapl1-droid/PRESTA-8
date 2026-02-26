{* Plik: tab_mobile_stock_qr.tpl *}

<div class="row">
    {* LEWA KOLUMNA: QR KOD I INSTRUKCJE *}
    <div class="col-md-4">
        <div class="panel panel-default" style="border-top: 3px solid #e65100;">
            <div class="panel-heading" style="font-weight:bold; color:#e65100;">
                <i class="icon-mobile-phone icon-large"></i> CENTRUM ZATOWAROWANIA (MOBILE)
            </div>
            <div class="panel-body text-center">
                
                {* Pobieramy link do kontrolera *}
                {assign var="mobile_url" value=$link->getAdminLink('AdminModulMobileStock')}
                
                {* Jeśli link jest pusty (kontroler niezarejestrowany), pokaż błąd *}
                {if !$mobile_url || $mobile_url|strstr:"AdminModulMobileStock" === false}
                    <div class="alert alert-danger">
                        <strong>BŁĄD KONFIGURACJI:</strong><br>
                        Link do kontrolera nie został wygenerowany.<br>
                        Uruchom plik <b>fix_mobile_install.php</b> w głównym katalogu sklepu.
                    </div>
                {else}
                    <div class="alert alert-info" style="text-align:left; margin-bottom:15px;">
                        <ol style="padding-left:15px; margin-bottom:0;">
                            <li>Otwórz aparat w telefonie.</li>
                            <li>Zeskanuj kod QR poniżej.</li>
                            <li>Otworzy się lista przyjęć.</li>
                        </ol>
                    </div>
                    
                    {* GENERATOR QR KODU *}
                    <div style="background: white; padding: 10px; display: inline-block; border: 1px solid #ccc; border-radius: 4px;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={$mobile_url|urlencode}" 
                             alt="QR Kod" 
                             style="width: 200px; height: 200px;">
                    </div>
                    
                    <br><br>
                    
                    <a href="{$mobile_url}" target="_blank" class="btn btn-warning btn-lg btn-block" style="font-weight:bold;">
                        <i class="icon-external-link"></i> OTWÓRZ W NOWYM OKNIE
                    </a>
                    
                    <div style="margin-top:10px;">
                        <small class="text-muted">Link bezpośredni:</small><br>
                        <input type="text" value="{$mobile_url}" class="form-control input-sm" onclick="this.select();" readonly style="background:#fff; cursor:text;">
                    </div>
                {/if}
            </div>
        </div>
    </div>

    {* PRAWA KOLUMNA: PODGLĄD NA ŻYWO *}
    <div class="col-md-8">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-eye-open"></i> PODGLĄD WIRTUALNEGO MAGAZYNU (NA ŻYWO)
            </div>
            <div class="panel-body">
                <div id="live_surplus_monitor">
                    <div class="text-center" style="padding:40px; color:#777;">
                        <i class="icon-refresh icon-spin icon-2x"></i><br><br>
                        Ładowanie podglądu...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var surplus_monitor_interval;

// Uruchom odświeżanie tylko gdy ta zakładka jest aktywna
$('a[data-toggle="tab"][href="#mobile_stock_qr"]').on('shown.bs.tab', function (e) {
    refreshSurplusMonitor();
    if(surplus_monitor_interval) clearInterval(surplus_monitor_interval);
    surplus_monitor_interval = setInterval(refreshSurplusMonitor, 5000); // Odświeżaj co 5s
});

$('a[data-toggle="tab"][href="#mobile_stock_qr"]').on('hidden.bs.tab', function (e) {
    if(surplus_monitor_interval) clearInterval(surplus_monitor_interval);
});

function refreshSurplusMonitor() {
    var controllerLink = "{$link->getAdminLink('AdminModulMobileStock')}";
    
    // Zabezpieczenie przed pustym linkiem
    if (!controllerLink) return;

    var url = controllerLink + "&ajax=1&action=getSurplusList";
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                renderSurplusMonitor(res.data);
            }
        },
        error: function() {
             // Ignoruj błędy sieci w tle
        }
    });
}

function renderSurplusMonitor(items) {
    var $div = $('#live_surplus_monitor');
    
    if (!items || items.length === 0) {
        $div.html('<div class="alert alert-success text-center" style="padding:40px;"><h1><i class="icon-check-circle"></i></h1><br><b>Wszystko rozłożone!</b><br>Wirtualny magazyn jest pusty.</div>');
        return;
    }

    var html = '<table class="table table-striped table-condensed">';
    html += '<thead><tr><th>EAN</th><th>Produkt</th><th class="text-center" style="width:80px;">Ilość</th></tr></thead><tbody>';
    
    items.sort(function(a, b) { return parseInt(b.qty) - parseInt(a.qty); });

    items.forEach(function(item) {
        html += '<tr>';
        html += '<td style="font-family:monospace; color:#777;">'+item.ean+'</td>';
        html += '<td>'+item.name+'</td>';
        html += '<td class="text-center"><span class="badge badge-warning" style="font-size:1.1em; background-color:#f0ad4e;">'+item.qty+'</span></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    $div.html(html);
}
</script>