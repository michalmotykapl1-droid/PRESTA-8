<div class="panel">
    <div class="row" style="display: flex; align-items: center; justify-content: space-between;">
        
        <div class="col-md-8">
            <h3>
                <i class="icon-cloud-download"></i> Zarządzanie Integracjami
            </h3>
            <p class="text-muted">
               Tutaj zarządzasz połączeniami z hurtowniami. Dodaj plik XML/CSV, skonfiguruj mapowanie i importuj produkty.
            </p>
            
            <div class="row" style="margin-top: 15px;">
                <div class="col-xs-4">
                    <div class="alert alert-info" style="margin-bottom: 0;">
                        <strong>{$total_wholesalers}</strong> Zdefiniowanych hurtowni
                    </div>
                </div>
                <div class="col-xs-4">
                    <div class="alert alert-success" style="margin-bottom: 0;">
                        <strong>{$active_wholesalers}</strong> Aktywnych połączeń
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 text-right">
            <a href="{$add_url}" class="btn btn-primary btn-lg" style="text-transform: uppercase; font-weight: bold; padding: 15px 30px; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                 <i class="icon-plus-circle"></i> Dodaj Nową Hurtownię
            </a>
        </div>

    </div>
</div>

<div class="clearfix" style="margin-bottom: 20px;"></div>

<div class="modal fade" id="b2bModal" tabindex="-1" role="dialog" aria-labelledby="b2bModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="b2bModalLabel"><i class="icon-lock"></i> Konfiguracja Dostępu B2B (Panel Zamówień)</h4>
      </div>
      <div class="modal-body">
        <form class="form-horizontal">
            <input type="hidden" id="b2b_id_wholesaler" value="">
            
            <div class="alert alert-info">
                Podaj login i hasło do strony internetowej hurtowni (tam gdzie zamawiasz towar).<br>
                Moduł użyje ich do pobrania listy faktur/zamówień w CSV.
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Login / E-mail</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" id="b2b_login_input" placeholder="np. zamowienia@twojsklep.pl">
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Hasło</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="b2b_pass_input" placeholder="Wpisz hasło B2B">
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuluj</button>
        <button type="button" class="btn btn-primary" id="btn-save-b2b">Zapisz Dane</button>
      </div>
    </div>
  </div>
</div>