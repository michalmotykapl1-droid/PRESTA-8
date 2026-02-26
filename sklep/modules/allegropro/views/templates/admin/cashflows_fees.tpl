{* AllegroPro - Opłaty (BILLING) – audyt billing-entries *}

{* Ujednolicenie filtra alert, aby nie było warningów Smarty/PHP *}
{assign var=_alert value=''}
{if isset($alert)}
  {assign var=_alert value=$alert}
{elseif isset($filters) && isset($filters.alert)}
  {assign var=_alert value=$filters.alert}
{/if}


<div class="alpro-page">
  <div class="card mb-3">
    <div class="card-header">
      <div class="alpro-head" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <div class="title">
          <i class="material-icons">swap_horiz</i>
          <strong>Przepływy środków</strong>
          <span class="alpro-badge" title="Widok audytowy: opłaty i zwroty z billing-entries (cache DB). Daty dotyczą occurred_at.">Opłaty (BILLING)</span>
        </div>

        <div class="btn-group" role="group">
          <a class="btn btn-default" href="{$view_tx_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">payments</i> Wpłaty transakcji</a>
          <a class="btn btn-default" href="{$view_recon_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">receipt_long</i> Rozliczenie</a>
          <a class="btn btn-default active" href="{$view_fees_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">paid</i> Opłaty (BILLING)</a>
          <a class="btn btn-default" href="{$view_raw_url|escape:'htmlall':'UTF-8'}"><i class="material-icons" style="font-size:18px; vertical-align:middle;">list</i> Dziennik (RAW)</a>
        </div>
      </div>
    </div>

    <div class="card-body">
      <form method="get" action="{$base_url|escape:'htmlall':'UTF-8'}" class="form-inline" style="gap:10px; align-items:flex-end;">
        <input type="hidden" name="controller" value="AdminAllegroProCashflows">
        <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="view" value="fees">

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
          <label>Od (occurredAt)</label><br>
          <input class="form-control" type="date" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}">
        </div>

        <div class="form-group">
          <label>Do (occurredAt)</label><br>
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
          <label>Alerty</label><br>
          <select class="form-control" name="alert" title="Filtr alertów (audyt).">
            <option value="" {if $_alert==''}selected{/if}>Wszystko</option>
            <option value="issues" {if $_alert=='issues'}selected{/if}>Tylko problemy</option>
            <option value="unpaid_charged" {if $_alert=='unpaid_charged'}selected{/if}>UNPAID + opłaty</option>
            <option value="missing_refund" {if $_alert=='missing_refund'}selected{/if}>CANCELLED + brak zwrotu</option>
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
          <select class="form-control" name="sync_mode" title="Fill = nowe + uzupełnij brakujące dni. Full = pełne pobranie + aktualizacja wszystkiego (wolniej).">
            <option value="fill" {if $sync_mode=='fill'}selected{/if}>NOWE + uzupełnij braki</option>
            <option value="full" {if $sync_mode=='full'}selected{/if}>PEŁNA synchronizacja (wolniej)</option>
          </select>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary"><i class="material-icons">filter_alt</i> Pokaż</button>
          <input type="hidden" id="alproAjaxBillingSyncUrl" value="{$ajax_billing_sync_url|escape:'htmlall':'UTF-8'}" />
          <button type="button" class="btn btn-default alpro-sync-btn" data-sync-kind="billing" data-ajax-url="{$ajax_billing_sync_url|escape:'htmlall':'UTF-8'}" title="Synchronizuj billing-entries do cache DB (pobieranie partiami, bez limitu).">
            <i class="material-icons">sync</i> Synchronizuj
          </button>
          <a class="btn btn-default" href="{$export_fees_url|escape:'htmlall':'UTF-8'}" title="Eksport CSV: audyt opłat/zwrotów (wg filtrów).">
            <i class="material-icons">download</i> CSV
          </a>
        </div>
      </form>

      <hr>

      {if !$fees_api.ok}
        <div class="alert alert-warning">
          Brak danych billing-entries w cache. Kliknij <strong>Synchronizuj</strong>.
          {if $fees_api.error}<br><small>{$fees_api.error|escape:'htmlall':'UTF-8'}</small>{/if}
        </div>
      {elseif $fees_api.totalCount|intval == 0}
        <div class="alert alert-info">
          Brak wpisów billing-entries powiązanych z zamówieniami w tym okresie.
        </div>
      {/if}

      <div class="row" style="margin-bottom:10px;">
        <div class="col-md-4">
          <div class="well">
            <div><strong>Zamówień (okres):</strong> {$fees_kpi.count|intval}</div>
            <div class="text-muted"><small>Źródło: billing-entries (order_id).</small></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="well">
            <div><strong>Opłaty (okres):</strong> {$fees_kpi.sum_charge|string_format:"%.2f"} PLN</div>
            <div><strong>Zwroty (okres):</strong> {$fees_kpi.sum_refund|string_format:"%.2f"} PLN</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="well">
            <div><strong>Problemy:</strong> {$fees_kpi.issues|intval}</div>
            <div><strong>UNPAID+opłaty:</strong> {$fees_kpi.unpaid_charged|intval} &nbsp; <strong>Brak zwrotu:</strong> {$fees_kpi.missing_refund|intval}</div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>last_occurred_at</th>
              <th>checkoutFormId</th>
              <th>Presta</th>
              <th>buyer</th>
              <th>status</th>
              <th style="text-align:right;">zapłacono</th>
              <th style="text-align:right;">opłaty</th>
              <th style="text-align:right;">zwroty</th>
              <th style="text-align:right;">netto</th>
              <th>Alert</th>
              <th>billing-entries</th>
            </tr>
          </thead>
          <tbody>
            {if empty($fees_rows)}
              <tr><td colspan="11" class="text-muted">Brak danych dla filtrów.</td></tr>
            {else}
              {foreach from=$fees_rows item=r}
                {assign var=rowId value='alpro_fee_'|cat:$r.checkout_form_id}
                <tr>
                  <td>{$r.last_occurred_at|escape:'htmlall':'UTF-8'}</td>
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
                  <td>{$r.buyer_login|default:'—'|escape:'htmlall':'UTF-8'}</td>
                  <td>{$r.order_status|default:'—'|escape:'htmlall':'UTF-8'}</td>
                  <td style="text-align:right;">{$r.paid_amount|string_format:"%.2f"} PLN</td>
                  <td style="text-align:right;">{$r.charge_amount|string_format:"%.2f"} PLN</td>
                  <td style="text-align:right;">{$r.refund_amount|string_format:"%.2f"} PLN</td>
                  <td style="text-align:right;">{$r.net_amount|string_format:"%.2f"} PLN</td>
                  <td>
                    {if $r.missing_refund|default:0|intval == 1}
                      <span class="alpro-status alpro-diff">BRAK ZWROTU</span>
                    {elseif $r.unpaid_charged|default:0|intval == 1}
                      <span class="alpro-status alpro-missing">UNPAID + OPŁATY</span>
                    {elseif $r.charge_amount|floatval > 0}
                      <span class="alpro-status alpro-waiting">OPŁATY</span>
                    {else}
                      <span class="alpro-status alpro-ok">OK</span>
                    {/if}
                  </td>
                  <td>
                    {if isset($r.entries) && $r.entries|@count > 0}
                      <button type="button" class="btn btn-link btn-xs alpro-toggle-payments" data-target="#{$rowId|escape:'htmlall':'UTF-8'}" style="padding:0;">
                        {$r.entries|@count} × wpisów <i class="material-icons" style="font-size:16px; vertical-align:middle;">expand_more</i>
                      </button>
                    {else}
                      <span class="text-muted">—</span>
                    {/if}
                  </td>
                </tr>

                {if isset($r.entries) && $r.entries|@count > 0}
                  <tr id="{$rowId|escape:'htmlall':'UTF-8'}" class="alpro-payments-row" style="display:none;">
                    <td colspan="11" style="background:#fafbfc; border-top:0;">
                      <div class="table-responsive" style="margin:0;">
                        <table class="table table-condensed" style="margin:0; background:#fff;">
                          <thead>
                            <tr>
                              <th>occurred_at</th>
                              <th>type</th>
                              <th>offer</th>
                              <th>payment_id</th>
                              <th style="text-align:right;">amount</th>
                            </tr>
                          </thead>
                          <tbody>
                            {foreach from=$r.entries item=e}
                              <tr>
                                <td>{$e.occurred_at|escape:'htmlall':'UTF-8'}</td>
                                <td>{$e.type_name|default:$e.type_id|escape:'htmlall':'UTF-8'}</td>
                                <td>{$e.offer_name|default:'—'|escape:'htmlall':'UTF-8'}</td>
                                <td style="font-family:monospace;">{$e.payment_id|default:'—'|escape:'htmlall':'UTF-8'}</td>
                                <td style="text-align:right;">{$e.value_amount|string_format:"%.2f"} {$e.value_currency|default:'PLN'|escape:'htmlall':'UTF-8'}</td>
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
          Ten widok jest oparty o <strong>billing-entries</strong> (cache <code>allegropro_billing_entry</code>). Wykrywa m.in.:
          <strong>UNPAID + opłaty</strong> oraz <strong>CANCELLED + brak zwrotu</strong>.
          <br>
          <span class="text-muted">Uwaga: opłaty/zwroty liczone są po znaku kwoty (ujemne = opłaty, dodatnie = zwroty) i grupowane per <code>order_id</code>.</span>
        </small>
      </div>
    </div>
  </div>
</div>

{* Modal postępu synchronizacji (wspólny) *}
<div class="modal fade" id="alproSyncModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><i class="material-icons" style="vertical-align:middle;">sync</i> Synchronizacja opłat (billing)</h4>
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
  window.AllegroProCashflows.view = 'fees';
</script>
