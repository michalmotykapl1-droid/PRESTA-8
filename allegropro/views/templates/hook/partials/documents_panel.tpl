<div class="card bg-light border-0">
    <div class="card-body p-3">
        <h4 class="text-muted mb-3">Dokumenty sprzedaży</h4>

        <div class="card border mb-3">
            <div class="card-body p-3">
                {if $allegro_data.invoice}
                    <div class="row mb-1">
                        <div class="col-md-3 text-muted">Typ dokumentu:</div>
                        <div class="col-md-9"><strong>Faktura</strong></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-md-3 text-muted">Nazwa firmy:</div>
                        <div class="col-md-9">{$allegro_data.invoice.company_name|default:'-'|escape:'htmlall':'UTF-8'}</div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-md-3 text-muted">NIP:</div>
                        <div class="col-md-9">{$allegro_data.invoice.tax_id|default:'-'|escape:'htmlall':'UTF-8'}</div>
                    </div>
                {else}
                    <div class="row mb-0">
                        <div class="col-md-3 text-muted">Typ dokumentu:</div>
                        <div class="col-md-9"><strong>Paragon</strong></div>
                    </div>
                {/if}
            </div>
        </div>

        <div class="card border mb-0">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2" style="gap:8px;">
                    <div class="text-muted" style="font-size:12px;">Dokumenty wysłane do Allegro dla tego zamówienia</div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFetchOrderDocuments">
                        <i class="material-icons" style="font-size:16px; vertical-align:middle;">description</i>
                        Pobierz dokumenty
                    </button>
                </div>

                <div class="mb-2" style="font-size:12px;">
                    <label style="font-weight:normal; margin:0;">
                        <input type="checkbox" id="order_documents_debug"> Tryb debug dokumentów (pokaż szczegóły API)
                    </label>
                </div>

                <div id="order_documents_msg" class="text-muted" style="font-size:12px; margin-bottom:8px;">Kliknij „Pobierz dokumenty”, aby odczytać listę z Allegro.</div>
                <div id="order_documents_list"></div>
                <div id="order_documents_debug_box" class="alert alert-secondary" style="display:none; font-size:11px; white-space:pre-wrap; max-height:260px; overflow:auto; margin-top:8px;"></div>
            </div>
        </div>
    </div>
</div>
