{* AllegroPro - Rozliczenia *}

<div class="panel">
  <h3><i class="material-icons">paid</i> Rozliczenia (opłaty Allegro)</h3>

  <form method="get" action="{$settlements_link|escape:'htmlall':'UTF-8'}" class="form-inline" style="margin-bottom: 15px;">

    <div class="form-group" style="margin-right:10px;">
      <label style="margin-right:6px;">Konto</label>
      <select name="id_allegropro_account" class="form-control">
        {foreach from=$accounts item=a}
          <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account == $selected_account_id}selected{/if}>
            {$a.label|escape:'htmlall':'UTF-8'} ({$a.allegro_login|escape:'htmlall':'UTF-8'})
          </option>
        {/foreach}
      </select>
    </div>

    <div class="form-group" style="margin-right:10px;">
      <label style="margin-right:6px;">Od</label>
      <input type="date" class="form-control" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}" />
    </div>

    <div class="form-group" style="margin-right:10px;">
      <label style="margin-right:6px;">Do</label>
      <input type="date" class="form-control" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}" />
    </div>

    <button type="submit" class="btn btn-default">
      <i class="material-icons">refresh</i> Odśwież widok
    </button>

    <button type="submit" name="submitAllegroProBillingSync" value="1" class="btn btn-primary" style="margin-left:10px;">
      <i class="material-icons">cloud_download</i> Synchronizuj opłaty (billing)
    </button>
  </form>

  {if isset($summary.sales_total)}
    <div class="row" style="margin-bottom: 10px;">
      <div class="col-md-2">
        <div class="panel" style="padding:10px;">
          <div><strong>Sprzedaż brutto</strong></div>
          <div style="font-size:18px;">{$summary.sales_total|number_format:2:',':' ' } zł</div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="panel" style="padding:10px;">
          <div><strong>Opłaty (netto)</strong></div>
          <div style="font-size:18px;">{$summary.fees_total|number_format:2:',':' ' } zł</div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="panel" style="padding:10px;">
          <div><strong>Prowizje</strong></div>
          <div style="font-size:18px;">{$summary.fees_commission|number_format:2:',':' ' } zł</div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="panel" style="padding:10px;">
          <div><strong>Smart</strong></div>
          <div style="font-size:18px;">{$summary.fees_smart|number_format:2:',':' ' } zł</div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="panel" style="padding:10px;">
          <div><strong>Dostawa</strong></div>
          <div style="font-size:18px;">{$summary.fees_delivery|number_format:2:',':' ' } zł</div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="panel" style="padding:10px;">
          <div><strong>Saldo po opłatach</strong></div>
          <div style="font-size:18px;">{$summary.net_after_fees|number_format:2:',':' ' } zł</div>
          {if $summary.unassigned_count > 0}
            <div class="text-warning" style="margin-top:6px; font-size:12px;">
              Nieprzypisane opłaty: {$summary.unassigned_count|intval}
            </div>
          {/if}
        </div>
      </div>
    </div>
  {/if}

  {if $order_details}
    <div class="panel">
      <h4>Szczegóły zamówienia: <code>{$view_order_id|escape:'htmlall':'UTF-8'}</code></h4>
      <p>
        Kupujący: <strong>{$order_details.order.buyer_login|escape:'htmlall':'UTF-8'}</strong> |
        Suma: <strong>{$order_details.order.total_amount|number_format:2:',':' '} zł</strong> |
        Saldo po opłatach: <strong>{$order_details.net_after_fees|number_format:2:',':' '} zł</strong>
        <a class="btn btn-default btn-sm" style="margin-left:10px;" href="{$settlements_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$selected_account_id|intval}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}">Wróć</a>
      </p>

      <div class="row">
        <div class="col-md-3"><strong>Prowizje:</strong> {$order_details.cats.commission|number_format:2:',':' '} zł</div>
        <div class="col-md-3"><strong>Smart:</strong> {$order_details.cats.smart|number_format:2:',':' '} zł</div>
        <div class="col-md-3"><strong>Dostawa:</strong> {$order_details.cats.delivery|number_format:2:',':' '} zł</div>
        <div class="col-md-3"><strong>Zwroty:</strong> {$order_details.cats.refunds|number_format:2:',':' '} zł</div>
      </div>

      <hr />
      <table class="table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Kategoria</th>
            <th>Typ</th>
            <th>Kwota</th>
            <th>Oferta</th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$order_details.items item=it}
            <tr>
              <td>{$it.occurred_at|escape:'htmlall':'UTF-8'}</td>
              <td><span class="badge">{$it.category|escape:'htmlall':'UTF-8'}</span></td>
              <td>{$it.type_name|escape:'htmlall':'UTF-8'} ({$it.type_id|escape:'htmlall':'UTF-8'})</td>
              <td>{$it.value_amount|number_format:2:',':' '} {$it.value_currency|escape:'htmlall':'UTF-8'}</td>
              <td>{if $it.offer_id}{$it.offer_name|escape:'htmlall':'UTF-8'}{/if}</td>
            </tr>
          {/foreach}
          {if !$order_details.items}
            <tr><td colspan="5" class="text-muted">Brak wpisów billingowych dla tego zamówienia w wybranym okresie.</td></tr>
          {/if}
        </tbody>
      </table>
    </div>
  {/if}

  <div class="panel">
    <h4>Lista zamówień (saldo po opłatach)</h4>
    <table class="table">
      <thead>
        <tr>
          <th>Data</th>
          <th>CheckoutFormId</th>
          <th>Kupujący</th>
          <th>Suma</th>
          <th>Opłaty</th>
          <th>Saldo</th>
          <th>Akcje</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$orders_rows item=o}
          <tr>
            <td>{$o.created_at_allegro|escape:'htmlall':'UTF-8'}</td>
            <td><code>{$o.checkout_form_id|escape:'htmlall':'UTF-8'}</code></td>
            <td>{$o.buyer_login|escape:'htmlall':'UTF-8'}</td>
            <td>{$o.total_amount|number_format:2:',':' '} {$o.currency|escape:'htmlall':'UTF-8'}</td>
            <td>{$o.fees_total|number_format:2:',':' '} zł</td>
            <td>
              {assign var=saldo value=$o.net_after_fees}
              <span class="{if $saldo < 0}text-danger{elseif $saldo < 5}text-warning{else}text-success{/if}">
                {$saldo|number_format:2:',':' '} zł
              </span>
            </td>
            <td>
              <a class="btn btn-default btn-sm" href="{$settlements_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$selected_account_id|intval}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}&view_order_id={$o.checkout_form_id|escape:'url'}">
                Szczegóły
              </a>
            </td>
          </tr>
        {/foreach}
        {if !$orders_rows}
          <tr><td colspan="7" class="text-muted">Brak zamówień w wybranym okresie.</td></tr>
        {/if}
      </tbody>
    </table>
  </div>

</div>
