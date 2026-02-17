<div class="panel" style="border:1px solid #d9e6f2; border-radius:12px; margin-bottom:15px;">
  <h3 style="margin-top:0;"><i class="icon icon-refresh"></i> Aktualizacja już pobranych zamówień</h3>

  <div class="row" style="margin-bottom:12px;">
    <div class="col-md-4">
      <label style="font-weight:700;">Konto Allegro</label>
      <select id="refresh_account_id" class="form-control">
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
    </div>

    <div class="col-md-3">
      <label style="font-weight:700;">Partia (batch)</label>
      <input type="number" id="refresh_batch_size" min="1" max="200" value="25" class="form-control" />
      <small class="text-muted">Mniejsze partie = mniejsze obciążenie serwera.</small>
    </div>

    <div class="col-md-5" style="display:flex; align-items:flex-end; gap:8px;">
      <button type="button" class="btn btn-primary" id="btnStartRefreshStoredOrders">
        <i class="icon icon-play"></i> Aktualizuj pobrane zamówienia
      </button>
    </div>
  </div>

  <div class="row" style="margin-bottom:10px;">
    <div class="col-md-4"><strong>Postęp partii</strong></div>
    <div class="col-md-8"><span id="refresh_batch_status" class="label label-default">Oczekuje...</span></div>
  </div>
  <div class="progress" style="height:15px; margin-bottom:12px;"><div id="refresh_batch_bar" class="progress-bar progress-bar-info" style="width:0%;">0%</div></div>

  <div class="row" style="margin-bottom:10px;">
    <div class="col-md-4"><strong>Postęp aktualizacji</strong></div>
    <div class="col-md-8"><span id="refresh_update_status" class="label label-default">Oczekuje...</span></div>
  </div>
  <div class="progress" style="height:15px; margin-bottom:12px;"><div id="refresh_update_bar" class="progress-bar progress-bar-success" style="width:0%;">0%</div></div>

  <div id="refresh_orders_logs" style="background:#0f172a; color:#cbd5e1; font-family:monospace; height:160px; overflow:auto; border-radius:8px; padding:8px;">
    <div class="text-muted">Gotowe do uruchomienia aktualizacji.</div>
  </div>
</div>
