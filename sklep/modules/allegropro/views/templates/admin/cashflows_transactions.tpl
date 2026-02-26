{* AllegroPro - Wpłaty transakcji (per checkoutFormId) *}

<div class="alpro-page">
  <div class="card mb-3">
    <div class="card-header">
      <div class="alpro-head" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <div class="title">
          <i class="material-icons">swap_horiz</i>
          <strong>Przepływy środków</strong>
          <span class="alpro-badge" title="Widok biznesowy: 1 wiersz = 1 checkoutFormId. Daty dotyczą finished_at (data płatności kupującego).">Wpłaty transakcji</span>
        </div>

        <div class="btn-group" role="group">
          <a class="btn btn-default active" href="{$view_tx_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">payments</i> Wpłaty transakcji</a>
          <a class="btn btn-default" href="{$view_recon_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">receipt_long</i> Rozliczenie</a>
          <a class="btn btn-default" href="{$view_billing_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">attach_money</i> Opłaty (BILLING)</a>
          <a class="btn btn-default" href="{$view_raw_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">list</i> Dziennik (RAW)</a>
        </div>
      </div>
    </div>

    <div class="card-body">
      <form method="get" action="{$base_url|escape:'htmlall':'UTF-8'}" class="form-inline" style="gap:10px; align-items:flex-end;">
        <input type="hidden" name="controller" value="AdminAllegroProCashflows">
        <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="view" value="tx">

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
          <label>Od (data płatności)</label><br>
          <input class="form-control" type="date" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}">
        </div>

        <div class="form-group">
          <label>Do (data płatności)</label><br>
          <input class="form-control" type="date" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}">
        </div>

        <div class="form-group">
          <label>buyer.login</label><br>
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
          <a class="btn btn-default" href="{$export_tx_url|escape:'htmlall':'UTF-8'}" title="Eksport CSV: 1 wiersz = 1 checkoutFormId (wg aktualnych filtrów).">
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

      {if !$tx_api.ok}
        <div class="alert alert-warning">
          Nie udało się odczytać danych do porównania wpłat (brak cache). Kliknij <strong>Synchronizuj</strong>.
          {if $tx_api.error}<br><small>{$tx_api.error|escape:'htmlall':'UTF-8'}</small>{/if}
        </div>
      {elseif $tx_api.totalCount|intval == 0}
        <div class="alert alert-info">
          Brak transakcji w tym okresie (wg finished_at) lub brak zapisanych danych płatności kupującego.
        </div>
      {/if}

      <div class="row" style="margin-bottom:10px;">
        <div class="col-md-4">
          <div class="well">
            <div><strong>Transakcji (okres):</strong> {$tx_kpi.count|intval}</div>
            <div><strong>Zapłacono (okres):</strong> {$tx_kpi.sum_expected|string_format:"%.2f"} PLN</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="well">
            <div><strong>Cashflow WAITING:</strong> {$tx_kpi.sum_waiting|string_format:"%.2f"} PLN</div>
            <div><strong>Cashflow AVAILABLE:</strong> {$tx_kpi.sum_available|string_format:"%.2f"} PLN</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="well">
            <div><strong>OK:</strong> {$tx_kpi.ok|intval} &nbsp; <strong>Brak:</strong> {$tx_kpi.missing|intval}</div>
            <div><strong>Różnice:</strong> {$tx_kpi.diff|intval} &nbsp; <strong>Tylko WAITING:</strong> {$tx_kpi.waiting_only|intval}</div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>finished_at</th>
              <th>checkoutFormId</th>
              <th>Presta</th>
              <th>buyer</th>
              <th style="text-align:right;">zapłacono</th>
              <th style="text-align:right;">WAITING</th>
              <th style="text-align:right;">AVAILABLE</th>
              <th>Status</th>
              <th>payment_id</th>
            </tr>
          </thead>
          <tbody>
            {if empty($tx_rows)}
              <tr><td colspan="9" class="text-muted">Brak danych dla filtrów.</td></tr>
            {else}
              {foreach from=$tx_rows item=r}
                {assign var=cfRowId value='alpro_pay_'|cat:$r.checkout_form_id}
                <tr>
                  <td>{$r.finished_at|escape:'htmlall':'UTF-8'}</td>
                  <td style="font-family:monospace;">{$r.checkout_form_id|escape:'htmlall':'UTF-8'}</td>
                  <td>
                    {if $r.id_order_prestashop|intval > 0}
                      <a class="btn btn-default btn-xs" href="{$admin_orders_link|escape:'htmlall':'UTF-8'}&id_order={$r.id_order_prestashop|intval}&vieworder=1" target="_blank" rel="noopener" title="Otwórz zamówienie w PrestaShop">
                        #{$r.id_order_prestashop|intval} ↗
                      </a>
                    {else}
                      <span class="text-muted">—</span>
                    {/if}
                  </td>
                  <td>{$r.buyer_login|escape:'htmlall':'UTF-8'}</td>
                  <td style="text-align:right;">{$r.expected|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                  <td style="text-align:right;">{$r.waiting|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                  <td style="text-align:right;">{$r.available|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                  <td>
                    {if $r.status=='ok'}
                      <span class="alpro-status alpro-ok">OK</span>
                    {elseif $r.status=='missing'}
                      <span class="alpro-status alpro-missing">BRAK</span>
                    {elseif $r.status=='waiting_only'}
                      <span class="alpro-status alpro-waiting">WAITING</span>
                    {else}
                      <span class="alpro-status alpro-diff">RÓŻNICA</span>
                    {/if}
                  </td>
                  <td>
                    {if isset($r.payments) && $r.payments|@count > 0}
                      <button type="button" class="btn btn-link btn-xs alpro-toggle-payments" data-target="#{$cfRowId|escape:'htmlall':'UTF-8'}" style="padding:0;">
                        {$r.payments|@count} × payment_id <i class="material-icons" style="font-size:16px; vertical-align:middle;">expand_more</i>
                      </button>
                    {else}
                      <span class="text-muted">—</span>
                    {/if}
                  </td>
                </tr>
                {if isset($r.payments) && $r.payments|@count > 0}
                  <tr id="{$cfRowId|escape:'htmlall':'UTF-8'}" class="alpro-payments-row" style="display:none;">
                    <td colspan="9" style="background:#fafbfc; border-top:0;">
                      <div class="table-responsive" style="margin:0;">
                        <table class="table table-condensed" style="margin:0; background:#fff;">
                          <thead>
                            <tr>
                              <th>payment_id</th>
                              <th style="text-align:right;">expected</th>
                              <th style="text-align:right;">WAITING</th>
                              <th style="text-align:right;">AVAILABLE</th>
                              <th>finished_at</th>
                            </tr>
                          </thead>
                          <tbody>
                            {foreach from=$r.payments item=p}
                              <tr>
                                <td style="font-family:monospace;">{$p.payment_id|escape:'htmlall':'UTF-8'}</td>
                                <td style="text-align:right;">{$p.expected|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                                <td style="text-align:right;">{$p.waiting|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                                <td style="text-align:right;">{$p.available|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                                <td>{$p.finished_at|escape:'htmlall':'UTF-8'}</td>
                              </tr>
                            {/foreach}
                          </tbody>
                        </table>
                      </div>
                    </td>
                  </tr>
                {/if}
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

      <div class="help-block" style="margin-top:10px;">
        <small>
          Porównanie działa tak: <strong>Zapłacono</strong> (z Allegro checkout-form → tabela <code>allegropro_order_payment</code>)
          vs <strong>Cashflow</strong> (INCOME/CONTRIBUTION z cache <code>allegropro_payment_operation</code>) po <code>payment_id</code>.
          <br>
          <span class="text-muted">Uwaga: przycisk <strong>Synchronizuj</strong> pobiera dane partiami (bez limitu) i pokazuje postęp w oknie modalnym.</span>
        </small>
      </div>
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
  window.AllegroProCashflows.view = 'tx';
</script>
