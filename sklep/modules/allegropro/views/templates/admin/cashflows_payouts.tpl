{* AllegroPro - Wypłaty (PAYOUT) *}

<div class="alpro-page">
  <div class="card mb-3">
    <div class="card-header">
      <div class="alpro-head" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <div class="title">
          <i class="material-icons">swap_horiz</i>
          <strong>Przepływy środków</strong>
          <span class="alpro-badge" title="Widok biznesowy: wypłaty Allegro (OUTCOME/PAYOUT) z cache DB. Daty dotyczą occurredAt.">Wypłaty (PAYOUT)</span>
        </div>

        <div class="btn-group" role="group">
          <a class="btn btn-default" href="{$view_tx_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">payments</i> Wpłaty transakcji</a>
          <a class="btn btn-default active" href="{$view_payout_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">account_balance_wallet</i> Wypłaty (PAYOUT)</a>
          <a class="btn btn-default" href="{$view_raw_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">list</i> Dziennik (RAW)</a>
        </div>
      </div>
    </div>

    <div class="card-body">
      <form method="get" action="{$base_url|escape:'htmlall':'UTF-8'}" class="form-inline" style="gap:10px; align-items:flex-end;">
        <input type="hidden" name="controller" value="AdminAllegroProCashflows">
        <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="view" value="payout">

        <div class="form-group">
          <label for="id_allegropro_account">Konto</label><br>
          <select class="form-control" name="id_allegropro_account" id="id_allegropro_account">
            {foreach from=$accounts item=a}
              <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account|intval == $selected_account_id}selected{/if}>
                {$a.label|default:('#'|cat:$a.id_allegropro_account)|escape:'htmlall':'UTF-8'}
              </option>
            {/foreach}
          </select>
        </div>

        <div class="form-group">
          <label>Od (data operacji)</label><br>
          <input class="form-control" type="date" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}">
        </div>

        <div class="form-group">
          <label>Do (data operacji)</label><br>
          <input class="form-control" type="date" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}">
        </div>

        <div class="form-group">
          <label>Portfel</label><br>
          <select class="form-control" name="wallet_type">
            <option value="" {if $wallet_type==''}selected{/if}>Wszystkie</option>
            <option value="AVAILABLE" {if $wallet_type=='AVAILABLE'}selected{/if}>AVAILABLE</option>
            <option value="WAITING" {if $wallet_type=='WAITING'}selected{/if}>WAITING</option>
          </select>
        </div>

        <div class="form-group">
          <label>Operator</label><br>
          <select class="form-control" name="wallet_payment_operator">
            <option value="" {if $wallet_payment_operator==''}selected{/if}>Wszyscy</option>
            <option value="PAYU" {if $wallet_payment_operator=='PAYU'}selected{/if}>PAYU</option>
            <option value="P24" {if $wallet_payment_operator=='P24'}selected{/if}>P24</option>
            <option value="AF" {if $wallet_payment_operator=='AF'}selected{/if}>AF</option>
            <option value="AF_PAYU" {if $wallet_payment_operator=='AF_PAYU'}selected{/if}>AF_PAYU</option>
            <option value="AF_P24" {if $wallet_payment_operator=='AF_P24'}selected{/if}>AF_P24</option>
          </select>
        </div>

        <div class="form-group">
          <label title="Ilość wierszy na stronie (nie wpływa na synchronizację).">Na stronę</label><br>
          <select class="form-control" name="limit" title="Ilość wierszy na stronie (nie wpływa na synchronizację).">
            <option value="25" {if $limit==25}selected{/if}>25</option>
            <option value="50" {if $limit==50}selected{/if}>50</option>
            <option value="100" {if $limit==100}selected{/if}>100</option>
            <option value="200" {if $limit==200}selected{/if}>200</option>
          </select>
        </div>

        <div class="form-group">
          <label>Tryb synchronizacji</label><br>
          <select class="form-control" name="sync_mode" title="Fill = nowe + uzupełnij brakujące dni. Full = pełne pobranie zakresu (wolniej).">
            <option value="fill" {if $sync_mode=='fill'}selected{/if}>NOWE + uzupełnij braki</option>
            <option value="full" {if $sync_mode=='full'}selected{/if}>PEŁNA synchronizacja (wolniej)</option>
          </select>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary"><i class="material-icons">filter_alt</i> Pokaż</button>
          <button type="button" class="btn btn-default alpro-sync-btn" data-ajax-url="{$ajax_sync_url|escape:'htmlall':'UTF-8'}" title="Synchronizuj dane do cache DB (pobieranie partiami, bez limitu).">
            <i class="material-icons">sync</i> Synchronizuj
          </button>
          <a class="btn btn-default" href="{$export_payout_url|escape:'htmlall':'UTF-8'}" title="Eksport CSV: wypłaty OUTCOME/PAYOUT wg filtrów.">
            <i class="material-icons">download</i> CSV
          </a>
        </div>
      </form>

      <hr>

      {if $sync_flash}
        {if $sync_flash.ok}
          <div class="alert alert-success" style="margin-bottom:10px;">
            <strong>Synchronizacja zakończona.</strong>
            Tryb: <strong>{$sync_flash.mode|escape:'htmlall':'UTF-8'}</strong>,
            pobrano: <strong>{$sync_flash.fetched|intval}</strong>,
            zapisano/upsert: <strong>{$sync_flash.stored|intval}</strong>,
            strony API: <strong>{$sync_flash.pages|intval}</strong>,
            czas: <strong>{$sync_flash.duration_ms|default:0|intval} ms</strong>
            {if $sync_flash.filled_days|default:0}
              , uzupełnione dni: <strong>{$sync_flash.filled_days|intval}</strong>
            {/if}
          </div>
        {else}
          <div class="alert alert-danger" style="margin-bottom:10px;">
            <strong>Synchronizacja nieudana:</strong>
            {$sync_flash.error|default:'(brak szczegółów)'|escape:'htmlall':'UTF-8'}
            {if $sync_flash.http|default:0}
              (HTTP {$sync_flash.http|intval})
            {/if}
          </div>
        {/if}
      {/if}

      {if !$payout_api.ok}
        <div class="alert alert-warning">
          Nie udało się odczytać wypłat z cache DB.
          {if $payout_api.error}<br><small>{$payout_api.error|escape:'htmlall':'UTF-8'}</small>{/if}
        </div>
      {elseif $payout_api.totalCount|intval == 0}
        <div class="alert alert-info">
          Brak wypłat (OUTCOME/PAYOUT) w tym okresie. Kliknij <strong>Synchronizuj</strong>, jeśli to pierwszy raz.
        </div>
      {/if}

      <div class="row" style="margin-bottom:10px;">
        <div class="col-md-4">
          <div class="well">
            <div><strong>Wypłat (okres):</strong> {$payout_kpi.count|intval}</div>
            <div><strong>Suma wypłat (okres):</strong> {$payout_kpi.sum_abs|string_format:"%.2f"} PLN</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="well">
            <div><strong>Suma (z znakiem):</strong> {$payout_kpi.sum|string_format:"%.2f"} PLN</div>
            <div class="text-muted"><small>W ledgerze PAYOUT zwykle jest ujemny (OUTCOME).</small></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="well">
            <div><strong>Rekordy (okres):</strong> {$payout_api.totalCount|intval}</div>
            <div><strong>Ostatnia synchronizacja:</strong> {if $last_sync_at}{$last_sync_at|escape:'htmlall':'UTF-8'}{else}<span class="text-muted">—</span>{/if}</div>
          </div>
        </div>
      </div>

      {if !empty($payout_daily)}
        <div class="table-responsive" style="margin-bottom:10px;">
          <table class="table table-condensed">
            <thead>
              <tr>
                <th colspan="3">Podsumowanie dzienne (ostatnie dni w zakresie)</th>
              </tr>
              <tr>
                <th>dzień</th>
                <th style="text-align:right;">wypłat</th>
                <th style="text-align:right;">suma</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$payout_daily item=d name=dd}
                {if $smarty.foreach.dd.iteration <= 14}
                  <tr>
                    <td>{$d.day|escape:'htmlall':'UTF-8'}</td>
                    <td style="text-align:right;">{$d.count|intval}</td>
                    <td style="text-align:right;">{$d.sum_abs|string_format:"%.2f"} PLN</td>
                  </tr>
                {/if}
              {/foreach}
            </tbody>
          </table>
        </div>
      {/if}

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>occurredAt</th>
              <th>wallet</th>
              <th style="text-align:right;">amount</th>
              <th class="text-muted">(group/type)</th>
            </tr>
          </thead>
          <tbody>
            {if empty($payout_rows)}
              <tr><td colspan="4" class="text-muted">Brak danych dla filtrów.</td></tr>
            {else}
              {foreach from=$payout_rows item=r}
                <tr>
                  <td>{$r.occurredAt|escape:'htmlall':'UTF-8'}</td>
                  <td>{$r.wallet_type|escape:'htmlall':'UTF-8'}{if $r.wallet_operator} / {$r.wallet_operator|escape:'htmlall':'UTF-8'}{/if}</td>
                  <td style="text-align:right;">{$r.amount|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                  <td class="text-muted" style="font-family:monospace;">{$r.group|escape:'htmlall':'UTF-8'} / {$r.type|escape:'htmlall':'UTF-8'}</td>
                </tr>
              {/foreach}
            {/if}
          </tbody>
        </table>
      </div>

      {if $total_pages > 1}
        <nav>
          <ul class="pagination">
            <li class="{if $page<=1}disabled{/if}">
              <a href="{$prev_url|escape:'htmlall':'UTF-8'}">&laquo;</a>
            </li>
            <li class="active"><span>{$page|intval} / {$total_pages|intval}</span></li>
            <li class="{if $page>=$total_pages}disabled{/if}">
              <a href="{$next_url|escape:'htmlall':'UTF-8'}">&raquo;</a>
            </li>
          </ul>
        </nav>
      {/if}
    </div>
  </div>
</div>

{* Modal postępu synchronizacji *}
<div class="modal fade" id="alproSyncModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><i class="material-icons" style="vertical-align:middle;">sync</i> Synchronizacja przepływów</h4>
      </div>
      <div class="modal-body">
        <div class="text-center" style="padding:18px 0;">
          <div class="spinner-border" role="status" style="width:4rem; height:4rem;"><span class="sr-only">Loading...</span></div>
          <div id="alproSyncText" style="margin-top:12px; font-weight:600;">Start…</div>
          <div id="alproSyncSub" class="text-muted" style="margin-top:6px; font-size:12px;"></div>
        </div>
        <div class="progress" style="height:18px;">
          <div id="alproSyncProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%;">0%</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" id="alproSyncClose" data-dismiss="modal" disabled>Zamknij</button>
      </div>
    </div>
  </div>
</div>

<script>
  window.AllegroProCashflows = window.AllegroProCashflows || {};
  window.AllegroProCashflows.view = 'payout';
</script>
