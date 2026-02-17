<div class="panel" style="border:1px solid #d9e2ec; border-radius:14px; margin-bottom:15px; overflow:hidden;">
  <div style="padding:14px 16px; background:linear-gradient(135deg,#f8fafc,#eef2f7); border-bottom:1px solid #d9e2ec;">
    <h3 style="margin:0; font-weight:700; display:flex; align-items:center; gap:8px;">
      <i class="icon icon-refresh" style="color:#0ea5e9;"></i>
      Aktualizacja już pobranych zamówień
    </h3>
    <div style="color:#64748b; margin-top:4px;">Nowoczesny widok procesu: postęp partii, postęp aktualizacji i raport końcowy.</div>
  </div>

  <div style="padding:16px;">
    <div class="row" style="margin-bottom:12px;">
      <div class="col-md-4">
        <label style="font-weight:700; margin-bottom:6px;">Konto Allegro</label>
        <select id="refresh_account_id" class="form-control" style="border-radius:8px;">
          {if isset($allegropro_accounts) && $allegropro_accounts}
            {foreach from=$allegropro_accounts item=a}
              <option value="{$a.id_allegropro_account|intval}" {if isset($allegropro_selected_account) && $allegropro_selected_account == $a.id_allegropro_account}selected{/if}>
                {$a.label|escape:'htmlall':'UTF-8'}{if $a.allegro_login} ({$a.allegro_login|escape:'htmlall':'UTF-8'}){/if}
              </option>
            {/foreach}
          {else}
            <option value="0">Brak kont Allegro</option>
          {/if}
        </select>
        <small id="refresh_account_lock_hint" class="text-warning" style="display:none;">W trybie "Tylko reasocjacja legacy" wybór konta jest wyłączony — moduł sprawdza wszystkie aktywne konta.</small>
      </div>

      <div class="col-md-3">
        <label style="font-weight:700; margin-bottom:6px;">Partia (batch)</label>
        <input type="number" id="refresh_batch_size" min="1" max="200" value="25" class="form-control" style="border-radius:8px;" />
        <small class="text-muted">Mniejsze partie = mniejsze obciążenie serwera.</small>
      </div>

      <div class="col-md-5" style="display:flex; align-items:flex-end; gap:8px;">
        <button type="button" class="btn btn-primary" id="btnStartRefreshStoredOrders" style="border-radius:8px; font-weight:700;">
          <i class="icon icon-play"></i> Aktualizuj pobrane zamówienia
        </button>
      </div>
    </div>



    <div class="row" style="margin-bottom:12px;">
      <div class="col-md-12">
        <label style="display:flex; align-items:center; gap:8px; font-weight:600; margin:0;">
          <input type="checkbox" id="refresh_only_legacy_reassign" value="1" />
          Tylko reasocjacja rekordów legacy (pomiń ETAP 1 i ETAP 2 dla aktywnego konta)
        </label>
      </div>
    </div>

    <div id="refresh_runtime_section" style="display:none;">
    <div class="row" style="margin-bottom:10px;">
      <div class="col-md-4"><strong>Postęp partii</strong></div>
      <div class="col-md-8"><span id="refresh_batch_status" class="label label-default">Gotowe do startu</span></div>
    </div>
    <div class="progress" style="height:18px; margin-bottom:14px; border-radius:999px; background:#e2e8f0;">
      <div id="refresh_batch_bar" class="progress-bar progress-bar-info progress-bar-striped active" style="width:0%; border-radius:999px;">0%</div>
    </div>

    <div class="row" style="margin-bottom:10px;">
      <div class="col-md-4"><strong>Postęp aktualizacji</strong></div>
      <div class="col-md-8"><span id="refresh_update_status" class="label label-default">Gotowe do startu</span></div>
    </div>
    <div class="progress" style="height:18px; margin-bottom:12px; border-radius:999px; background:#e2e8f0;">
      <div id="refresh_update_bar" class="progress-bar progress-bar-success progress-bar-striped active" style="width:0%; border-radius:999px;">0%</div>
    </div>

    <div id="refresh_orders_report" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; margin-bottom:12px; color:#334155;">
      <strong>Raport:</strong> oczekuje na uruchomienie.
    </div>

    <div id="refresh_orders_logs" style="background:#0f172a; color:#cbd5e1; font-family:monospace; height:180px; overflow:auto; border-radius:10px; padding:10px;">
      <div class="text-muted">Gotowe do uruchomienia aktualizacji.</div>
    </div>
    </div>
  </div>
</div>
