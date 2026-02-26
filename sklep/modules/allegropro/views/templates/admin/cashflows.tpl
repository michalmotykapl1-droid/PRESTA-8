{* AllegroPro - Przepływy środków (payments/payment-operations) *}

<div class="alpro-page">
  <div class="card mb-3">
    <div class="card-header">
      <div class="alpro-head" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <div class="title">
          <i class="material-icons">swap_horiz</i>
          <strong>Przepływy środków</strong>
          <span class="alpro-badge" title="Źródło danych: Allegro API GET /payments/payment-operations. Daty dotyczą occurredAt (czas wystąpienia operacji).">Dziennik (RAW)</span>
        </div>

        <div class="btn-group" role="group">
          <a class="btn btn-default" href="{$view_tx_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">payments</i> Wpłaty transakcji</a>
          <a class="btn btn-default" href="{$view_recon_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">receipt_long</i> Rozliczenie</a>
          <a class="btn btn-default" href="{$view_billing_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">attach_money</i> Opłaty (BILLING)</a>
          <a class="btn btn-default active" href="{$view_raw_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">list</i> Dziennik (RAW)</a>
        </div>
      </div>
    </div>

    <div class="card-body">
      <form method="get" action="{$base_url|escape:'htmlall':'UTF-8'}" class="form-inline" style="gap:10px; align-items:flex-end;">
        <input type="hidden" name="controller" value="AdminAllegroProCashflows">
        <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="view" value="raw">

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
          <label>Od</label><br>
          <input class="form-control" type="date" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}">
        </div>

        <div class="form-group">
          <label>Do</label><br>
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
          <label>Grupa</label><br>
          <select class="form-control" name="group">
            <option value="" {if $group==''}selected{/if}>Wszystkie</option>
            <option value="INCOME" {if $group=='INCOME'}selected{/if}>INCOME</option>
            <option value="OUTCOME" {if $group=='OUTCOME'}selected{/if}>OUTCOME</option>
            <option value="REFUND" {if $group=='REFUND'}selected{/if}>REFUND</option>
            <option value="BLOCKADES" {if $group=='BLOCKADES'}selected{/if}>BLOCKADES</option>
          </select>
        </div>

        <div class="form-group">
          <label>participant.login</label><br>
          <input class="form-control" type="text" name="participant_login" value="{$participant_login|escape:'htmlall':'UTF-8'}" placeholder="login...">
        </div>

        <div class="form-group">
          <label>payment.id</label><br>
          <input class="form-control" type="text" name="payment_id" value="{$payment_id|escape:'htmlall':'UTF-8'}" placeholder="uuid...">
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
          <a class="btn btn-default" href="{$export_url|escape:'htmlall':'UTF-8'}" title="Eksport CSV z cache DB wg aktualnych filtrów.">
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

      {if !$api.ok}
        <div class="alert alert-warning">
          Nie udało się odczytać danych z cache DB.
          {if $api.error}<br><small>{$api.error|escape:'htmlall':'UTF-8'}</small>{/if}
        </div>
      {elseif $api.totalCount|intval == 0}
        <div class="alert alert-info">
          Brak danych w cache dla wskazanego okresu. Kliknij <strong>Synchronizuj</strong>.
        </div>
      {/if}

      <div class="row" style="margin-bottom:10px;">
        <div class="col-md-4">
          <div class="well">
            <div><strong>Operacji (okres):</strong> {$kpi.count|intval}</div>
            <div><strong>Suma (okres):</strong> {$kpi.total|string_format:"%.2f"} PLN</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="well">
            <div><strong>Wpływy (okres):</strong> {$kpi.pos|string_format:"%.2f"} PLN</div>
            <div><strong>Wypływy (okres):</strong> {$kpi.neg|string_format:"%.2f"} PLN</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="well">
            <div><strong>Rekordy (okres):</strong> {$api.totalCount|intval}</div>
            <div><strong>Ostatnia synchronizacja:</strong> {if $last_sync_at}{$last_sync_at|escape:'htmlall':'UTF-8'}{else}<span class="text-muted">—</span>{/if}</div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>occurredAt</th>
              <th>group</th>
              <th>type</th>
              <th>wallet</th>
              <th>participant</th>
              <th style="text-align:right;">amount</th>
              <th>payment.id</th>
            </tr>
          </thead>
          <tbody>
            {if empty($rows)}
              <tr><td colspan="7" class="text-muted">Brak danych dla filtrów.</td></tr>
            {else}
              {foreach from=$rows item=r}
                <tr>
                  <td>{$r.occurredAt|escape:'htmlall':'UTF-8'}</td>
                  <td>{$r.group|escape:'htmlall':'UTF-8'}</td>
                  <td>{$r.type|escape:'htmlall':'UTF-8'}</td>
                  <td>{$r.wallet_type|escape:'htmlall':'UTF-8'} / {$r.wallet_operator|escape:'htmlall':'UTF-8'}</td>
                  <td>{$r.participant_login|escape:'htmlall':'UTF-8'}</td>
                  <td style="text-align:right;">{$r.amount|escape:'htmlall':'UTF-8'} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                  <td style="font-family:monospace;">{$r.payment_id|escape:'htmlall':'UTF-8'}</td>
                </tr>
              {/foreach}
            {/if}
          </tbody>
        </table>
      </div>

      <div class="help-block" style="margin-top:10px;">
        <small class="text-muted">Przycisk <strong>Synchronizuj</strong> pobiera dane partiami (bez limitu) i pokazuje postęp w oknie modalnym.</small>
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
  window.AllegroProCashflows.view = 'raw';
</script>
