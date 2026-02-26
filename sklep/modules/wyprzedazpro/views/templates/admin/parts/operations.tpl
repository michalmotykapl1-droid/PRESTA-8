<div class="panel">
    <div class="panel-heading"><i class="icon-exchange"></i> OPERACJE NA STANACH</div>
    
    <form action="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" class="form-horizontal">
        <div class="row">
            {* LEWA STRONA: Import CSV *}
            <div class="col-md-6">
                <div class="alert alert-info">
                    <b>Import danych WMS (CSV)</b><br>
                    Kolumny: EAN, STAN, DATA PRZYJĘCIA, DATA WAŻNOŚCI, Regał, Półka.
                </div>
                <div class="form-group">
                    <div class="col-lg-12">
                        <input type="file" name="csv_file" id="csv_file_input" class="form-control" />
                    </div>
                </div>
                
                <div id="wyprzedazpro-progress-wrapper" style="display:none;margin-bottom:10px; border:1px solid #eee; padding:15px; background:#f9f9f9;">
                    <div id="wyprzedazpro-progress-stage" style="margin-bottom:6px; font-weight:bold;">{l s='Etap:' mod='wyprzedazpro'} –</div>
                    <div class="progress">
                        <div id="wyprzedazpro-progress-bar" class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%; min-width:2em;">0%</div>
                    </div>
                    <div id="wyprzedazpro-progress-stats" style="margin-top:6px;color:#444;font-size:12px"></div>
                </div>

                <button type="button" id="wyprzedazpro_start_btn" class="btn btn-primary btn-block" data-url="{$ajax_url}">
                    <i class="process-icon-upload"></i> IMPORTUJ PLIK DO WMS
                </button>
            </div>
            
            {* PRAWA STRONA: Narzędzia Naprawcze *}
            <div class="col-md-6">
                <div class="alert alert-warning">
                    <b>Narzędzia Naprawcze</b><br>
                    Użyj, jeśli stany w Preście (Czerwone) nie zgadzają się z WMS (Niebieskie).
                </div>
                
                {* BUTTON SYNCHRONIZACJI *}
                <button type="submit" name="submitSyncWmsToPresta" class="btn btn-info btn-lg btn-block" onclick="return confirm('Czy na pewno chcesz nadpisać stany w Preście ilościami z WMS?');">
                    <i class="icon-refresh"></i> <b>SYNCHRONIZUJ (WMS -> PRESTA)</b><br>
                    <small>Naprawia różnice w ilościach (Czerwona vs Niebieska kolumna)</small>
                </button>
                
                <br>
                <a href="{$link->getAdminLink('AdminWyprzedazPro')|escape:'html':'UTF-8'}&export_current_wms=1" class="btn btn-default btn-block">
                    <i class="icon-download"></i> Pobierz aktualny stan WMS (CSV)
                </a>
            </div>
        </div>
    </form>
</div>