<div class="ap-box p-3">
    <div class="d-flex align-items-center justify-content-between" style="gap:10px; flex-wrap:wrap;">
        <div class="ap-box-title" style="margin-bottom:0;">
            <i class="material-icons">description</i> Dokumenty
        </div>

        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFetchOrderDocuments">
            <i class="material-icons" style="font-size:16px; vertical-align:middle;">refresh</i>
            Pobierz dokumenty
        </button>
    </div>

    <div class="ap-divider"></div>

    {* Dane dokumentu sprzedaży (faktura/paragon) *}
    <div class="row" style="margin-bottom:6px;">
        <div class="col-md-4 mb-2 mb-md-0">
            <div class="ap-k">Typ dokumentu</div>
            <div class="ap-v">{if $allegro_data.invoice}Faktura{else}Paragon{/if}</div>
        </div>
        <div class="col-md-4 mb-2 mb-md-0">
            <div class="ap-k">Nazwa firmy</div>
            <div class="ap-v">
                {if $allegro_data.invoice}
                    {$allegro_data.invoice.company_name|default:'-'|escape:'htmlall':'UTF-8'}
                {else}
                    -
                {/if}
            </div>
        </div>
        <div class="col-md-4">
            <div class="ap-k">NIP</div>
            <div class="ap-v">
                {if $allegro_data.invoice}
                    {$allegro_data.invoice.tax_id|default:'-'|escape:'htmlall':'UTF-8'}
                {else}
                    -
                {/if}
            </div>
        </div>
    </div>

    <div class="ap-divider"></div>

    <div class="ap-help" style="margin-bottom:8px;">
        Dokumenty wysłane do Allegro dla tego zamówienia
    </div>

    <div class="d-flex align-items-center justify-content-between" style="gap:10px; flex-wrap:wrap; margin-bottom:8px;">
        <div class="form-check" style="margin:0;">
            <input type="checkbox" id="order_documents_debug" class="form-check-input">
            <label class="form-check-label ap-help" for="order_documents_debug" style="margin:0;">Tryb debug (szczegóły API)</label>
        </div>
        <small class="text-muted">Lista odświeża się automatycznie po wejściu w zamówienie.</small>
    </div>

    <div id="order_documents_msg" class="ap-help" style="margin-bottom:10px;">Kliknij „Pobierz”, aby odczytać listę z Allegro.</div>
    <div id="order_documents_list"></div>

    <div id="order_documents_debug_box" class="alert alert-secondary" style="display:none; font-size:11px; white-space:pre-wrap; max-height:260px; overflow:auto; margin-top:10px;"></div>
</div>
