<div class="panel">
    <div class="panel-heading">
        <i class="icon-mobile-phone"></i> CENTRUM ZBIERANIA (MOBILE)
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="alert alert-info">
                <strong>INSTRUKCJA:</strong><br>
                1. Przygotuj zamówienie w Zakładce 2.<br>
                2. Kliknij <b>WYŚLIJ NA SKANER</b>, aby utworzyć listę dla magazyniera.<br>
                3. Obserwuj postęp w tabeli obok.
            </div>
            
            <button id="btn-push-to-mobile" class="btn btn-success btn-lg btn-block" style="padding:15px; font-weight:bold; font-size:16px; margin-bottom:20px;">
                <i class="icon-cloud-upload"></i> WYŚLIJ NA SKANER
            </button>
            
            <div class="panel" style="text-align:center; border:1px solid #ccc;">
                <div class="panel-heading">QR KOD DO SKANERA</div>
                <div class="panel-body">
                    {assign var="mobile_url" value=$link->getBaseLink()|cat:"modules/"|cat:$module_name|cat:"/mobile.php"}
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={$mobile_url}" style="margin-bottom:10px;">
                    <br>
                    <a href="../modules/{$module_name}/mobile.php" target="_blank" class="btn btn-default btn-sm">
                        OTWÓRZ W NOWYM OKNIE <i class="icon-external-link"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <h3><i class="icon-eye"></i> PODGLĄD NA ŻYWO</h3>
            <table class="table table-bordered table-striped" id="mobile-monitor-table">
                <thead>
                    <tr>
                        <th width="100">Lok.</th>
                        <th>Nazwa Produktu</th>
                        <th>EAN</th>
                        <th class="text-center" width="100">Stan</th>
                        <th class="text-center" width="50"><i class="icon-check"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="text-center text-muted" style="padding:30px;">Kliknij "WYŚLIJ NA SKANER", aby załadować dane.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>