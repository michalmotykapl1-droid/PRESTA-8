{* AllegroPro - Rozliczenia *}

<style>
{literal}
.alpro-wrap{max-width:1400px;}
  .alpro-toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;justify-content:space-between;}
  .alpro-toolbar .left{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;}
  .alpro-toolbar .right{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;}
  .alpro-kpis{display:flex;flex-wrap:wrap;gap:12px;}
  .alpro-kpi{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:12px;padding:14px 16px;min-width:210px;flex:1 1 210px;}
  .alpro-kpi .label{font-size:12px;color:#6c757d;margin-bottom:6px;}
  .alpro-kpi .value{font-size:22px;font-weight:700;line-height:1.15;}
  .alpro-kpi .meta{font-size:12px;color:#6c757d;margin-top:6px;}
  .alpro-chip{display:inline-block;background:#f6f7f9;border:1px solid rgba(0,0,0,.06);border-radius:999px;padding:6px 10px;font-size:12px;margin-right:8px;margin-bottom:8px;}
  .alpro-id{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;font-size:12px;}
  .alpro-table th{white-space:nowrap;}
  .alpro-muted{color:#6c757d;}
{/literal}
</style>

<div class="alpro-wrap">

  <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <i class="material-icons mr-2">paid</i>
        <strong>Rozliczenia z Allegro</strong>
        {if isset($summary.unassigned_count) && $summary.unassigned_count > 0}
          <span class="badge badge-warning ml-2">Nieprzypisane do zamówień: {$summary.unassigned_count|intval}</span>
        {/if}
      </div>
      <div class="alpro-muted" style="font-size:12px;">
        Operacje w okresie: <strong>{$billing_count|intval}</strong>
      </div>
    </div>

    <div class="card-body">

      <div class="alpro-toolbar">

        <div class="left">
          {* FILTRY (GET) *}
          <form method="get" action="{$settlements_link|escape:'htmlall':'UTF-8'}" class="m-0">
            <div class="form-row align-items-end">

              <div class="form-group mb-0" style="min-width:260px;">
                <label class="form-control-label">Konto</label>
                <select name="id_allegropro_account" class="form-control">
                  {foreach from=$accounts item=a}
                    <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account == $selected_account_id}selected{/if}>
                      {$a.label|escape:'htmlall':'UTF-8'} ({$a.allegro_login|escape:'htmlall':'UTF-8'})
                    </option>
                  {/foreach}
                </select>
              </div>

              <div class="form-group mb-0" style="min-width:160px;">
                <label class="form-control-label">Od</label>
                <input type="date" class="form-control" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}" />
              </div>

              <div class="form-group mb-0" style="min-width:160px;">
                <label class="form-control-label">Do</label>
                <input type="date" class="form-control" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}" />
              </div>

              <div class="form-group mb-0" style="min-width:260px;">
                <label class="form-control-label">Szukaj (ID / login)</label>
                <input type="text" class="form-control" name="q" value="{$q|escape:'htmlall':'UTF-8'}" placeholder="np. 23187951... lub login" />
              </div>

              <div class="form-group mb-0">
                <button type="submit" class="btn btn-outline-secondary">
                  <i class="material-icons" style="font-size:18px; vertical-align:middle;">refresh</i>
                  <span style="vertical-align:middle;">Pokaż</span>
                </button>
              </div>

            </div>
          </form>
        </div>

        <div class="right">
          {* SYNC (POST) – osobny formularz, żeby nie „wywalało” z kontrolera i żeby stan filtrów się zachował *}
          <form method="post" action="{$current_index|escape:'htmlall':'UTF-8'}&token={$token|escape:'htmlall':'UTF-8'}" class="m-0">
            <input type="hidden" name="id_allegropro_account" value="{$selected_account_id|intval}" />
            <input type="hidden" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="q" value="{$q|escape:'htmlall':'UTF-8'}" />

            <button type="submit" name="submitAllegroProBillingSync" value="1" class="btn btn-primary">
              <i class="material-icons" style="font-size:18px; vertical-align:middle;">cloud_download</i>
              <span style="vertical-align:middle;">Synchronizuj opłaty</span>
            </button>

            {if $sync_result}
              {if !empty($sync_result.ok)}
                <span class="badge badge-success ml-2">OK</span>
              {else}
                <span class="badge badge-danger ml-2">Błąd (HTTP {$sync_result.code|intval})</span>
              {/if}
            {/if}
          </form>
        </div>

      </div>

      {if $sync_debug && $sync_debug|@count > 0}
        <details class="mt-3">
          <summary class="alpro-muted" style="cursor:pointer;">Pokaż log synchronizacji (debug)</summary>
          <pre class="mt-2" style="max-height:240px; overflow:auto; background:#f6f8fa; padding:10px; border-radius:10px; border:1px solid rgba(0,0,0,.06);">{foreach from=$sync_debug item=l}{$l|escape:'htmlall':'UTF-8'}
{/foreach}</pre>
        </details>
      {/if}

      {* PODSUMOWANIE *}
      {if isset($summary.sales_total)}
        {assign var=fees_other value=($summary.fees_total-$summary.fees_commission-$summary.fees_smart-$summary.fees_delivery-$summary.fees_promotion-$summary.fees_refunds)}

        <div class="mt-4 alpro-kpis">
          <div class="alpro-kpi">
            <div class="label">Sprzedaż brutto</div>
            <div class="value">{$summary.sales_total|number_format:2:',':' '} zł</div>
            <div class="meta">Suma zamówień w okresie</div>
          </div>

          <div class="alpro-kpi">
            <div class="label">Suma opłat</div>
            <div class="value {if $summary.fees_total < 0}text-danger{elseif $summary.fees_total > 0}text-success{/if}">
              {$summary.fees_total|number_format:2:',':' '} zł
            </div>
            <div class="meta">Opłaty + rabaty/zwroty (bez przepływów środków)</div>
          </div>

          <div class="alpro-kpi">
            <div class="label">Saldo po opłatach</div>
            <div class="value {if $summary.net_after_fees < 0}text-danger{elseif $summary.net_after_fees < 5}text-warning{else}text-success{/if}">
              {$summary.net_after_fees|number_format:2:',':' '} zł
            </div>
            <div class="meta">Sprzedaż brutto + suma opłat</div>
          </div>

          <div class="alpro-kpi">
            <div class="label">Struktura opłat</div>
            <div class="meta">
              <span class="alpro-chip">Prowizje: <strong>{$summary.fees_commission|number_format:2:',':' '}</strong> zł</span>
              <span class="alpro-chip">Dostawa: <strong>{$summary.fees_delivery|number_format:2:',':' '}</strong> zł</span>
              <span class="alpro-chip">Smart: <strong>{$summary.fees_smart|number_format:2:',':' '}</strong> zł</span>
              <span class="alpro-chip">Promocja: <strong>{$summary.fees_promotion|number_format:2:',':' '}</strong> zł</span>
              <span class="alpro-chip">Rabaty/zwroty: <strong>{$summary.fees_refunds|number_format:2:',':' '}</strong> zł</span>
              <span class="alpro-chip">Pozostałe: <strong>{$fees_other|number_format:2:',':' '}</strong> zł</span>
            </div>
          </div>
        </div>
      {/if}

    </div>
  </div>

  {* SZCZEGÓŁY ZAMÓWIENIA *}
  {if $order_details}
    {assign var=orderHasRow value=($order_details.order && $order_details.order.checkout_form_id)}
    {assign var=oTotal value=($order_details.order.total_amount|default:0)}
    {assign var=feesOtherOrder value=($order_details.cats.total-$order_details.cats.commission-$order_details.cats.smart-$order_details.cats.delivery-$order_details.cats.promotion-$order_details.cats.refunds)}

    <div class="card mb-3">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <strong>Szczegóły</strong>
          <div class="alpro-muted" style="font-size:12px;">Zamówienie: <span class="alpro-id">{$view_order_id|escape:'htmlall':'UTF-8'}</span></div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="{$settlements_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$selected_account_id|intval}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}&q={$q|escape:'url'}">
          Wróć do listy
        </a>
      </div>

      <div class="card-body">
        <div class="alpro-kpis mb-3">
          <div class="alpro-kpi">
            <div class="label">Kupujący</div>
            <div class="value" style="font-size:18px;">{if $orderHasRow}{$order_details.order.buyer_login|escape:'htmlall':'UTF-8'}{else}<span class="alpro-muted">brak danych w tabeli zamówień</span>{/if}</div>
            <div class="meta">(billing jest liczony niezależnie)</div>
          </div>
          <div class="alpro-kpi">
            <div class="label">Suma zamówienia</div>
            <div class="value">{$oTotal|number_format:2:',':' '} zł</div>
          </div>
          <div class="alpro-kpi">
            <div class="label">Opłaty łącznie</div>
            <div class="value {if $order_details.cats.total < 0}text-danger{elseif $order_details.cats.total > 0}text-success{/if}">
              {$order_details.cats.total|number_format:2:',':' '} zł
            </div>
          </div>
          <div class="alpro-kpi">
            <div class="label">Saldo po opłatach</div>
            <div class="value {if $order_details.net_after_fees < 0}text-danger{elseif $order_details.net_after_fees < 5}text-warning{else}text-success{/if}">
              {$order_details.net_after_fees|number_format:2:',':' '} zł
            </div>
          </div>
        </div>

        <div class="mb-2">
          <span class="alpro-chip">Prowizje: <strong>{$order_details.cats.commission|number_format:2:',':' '}</strong> zł</span>
          <span class="alpro-chip">Dostawa: <strong>{$order_details.cats.delivery|number_format:2:',':' '}</strong> zł</span>
          <span class="alpro-chip">Smart: <strong>{$order_details.cats.smart|number_format:2:',':' '}</strong> zł</span>
          <span class="alpro-chip">Promocja: <strong>{$order_details.cats.promotion|number_format:2:',':' '}</strong> zł</span>
          <span class="alpro-chip">Rabaty/zwroty: <strong>{$order_details.cats.refunds|number_format:2:',':' '}</strong> zł</span>
          <span class="alpro-chip">Pozostałe: <strong>{$feesOtherOrder|number_format:2:',':' '}</strong> zł</span>
        </div>

        <div class="table-responsive">
          <table class="table table-striped table-sm alpro-table">
            <thead>
              <tr>
                <th>Data</th>
                <th>Kategoria</th>
                <th>Typ operacji</th>
                <th class="text-right">Kwota</th>
                <th>Oferta</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$order_details.items item=it}
                {assign var=catLabel value=$it.category}
                {if $it.category=='commission'}{assign var=catLabel value='Prowizja'}{/if}
                {if $it.category=='smart'}{assign var=catLabel value='Smart'}{/if}
                {if $it.category=='delivery'}{assign var=catLabel value='Dostawa'}{/if}
                {if $it.category=='promotion'}{assign var=catLabel value='Promocja'}{/if}
                {if $it.category=='refunds'}{assign var=catLabel value='Rabat / zwrot'}{/if}
                {if $it.category=='other'}{assign var=catLabel value='Pozostałe'}{/if}

                <tr>
                  <td>{$it.occurred_at|escape:'htmlall':'UTF-8'}</td>
                  <td><span class="badge badge-light">{$catLabel|escape:'htmlall':'UTF-8'}</span></td>
                  <td>{$it.type_name|escape:'htmlall':'UTF-8'}{if $it.type_id} <span class="alpro-muted">({$it.type_id|escape:'htmlall':'UTF-8'})</span>{/if}</td>
                  <td class="text-right {if $it.value_amount < 0}text-danger{elseif $it.value_amount > 0}text-success{/if}">
                    {$it.value_amount|number_format:2:',':' '} {$it.value_currency|escape:'htmlall':'UTF-8'}
                  </td>
                  <td>{if $it.offer_id}{$it.offer_name|escape:'htmlall':'UTF-8'}{/if}</td>
                </tr>
              {/foreach}
              {if !$order_details.items}
                <tr><td colspan="5" class="alpro-muted">Brak wpisów opłat dla tego zamówienia w wybranym okresie.</td></tr>
              {/if}
            </tbody>
          </table>
        </div>

      </div>
    </div>
  {/if}

  {* LISTA ZAMÓWIEŃ *}
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <strong>Zamówienia</strong>
      <span class="alpro-muted" style="font-size:12px;">Pokazano maks. 500 ostatnich</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm mb-0 alpro-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Zamówienie</th>
              <th>Kupujący</th>
              <th class="text-right">Sprzedaż</th>
              <th class="text-right">Opłaty</th>
              <th class="text-right">Saldo</th>
              <th style="width:120px;">&nbsp;</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$orders_rows item=o}
              {assign var=saldo value=$o.net_after_fees}
              {assign var=idShort value=$o.checkout_form_id|truncate:18:"…":true}
              <tr>
                <td>{$o.created_at_allegro|escape:'htmlall':'UTF-8'}</td>
                <td>
                  <span class="alpro-id" title="{$o.checkout_form_id|escape:'htmlall':'UTF-8'}">{$idShort|escape:'htmlall':'UTF-8'}</span>
                </td>
                <td>{$o.buyer_login|escape:'htmlall':'UTF-8'}</td>
                <td class="text-right">{$o.total_amount|number_format:2:',':' '} {$o.currency|escape:'htmlall':'UTF-8'}</td>
                <td class="text-right {if $o.fees_total < 0}text-danger{elseif $o.fees_total > 0}text-success{/if}">
                  {$o.fees_total|number_format:2:',':' '} zł
                </td>
                <td class="text-right {if $saldo < 0}text-danger{elseif $saldo < 5}text-warning{else}text-success{/if}">
                  {$saldo|number_format:2:',':' '} zł
                </td>
                <td>
                  <a class="btn btn-outline-secondary btn-sm" href="{$settlements_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$selected_account_id|intval}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}&q={$q|escape:'url'}&view_order_id={$o.checkout_form_id|escape:'url'}">
                    Szczegóły
                  </a>
                </td>
              </tr>
            {/foreach}
            {if !$orders_rows}
              <tr><td colspan="7" class="alpro-muted p-3">Brak zamówień w wybranym okresie.</td></tr>
            {/if}
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
