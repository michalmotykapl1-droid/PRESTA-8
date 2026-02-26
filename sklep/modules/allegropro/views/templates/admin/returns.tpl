<div class="panel ap-returns-page">
  <style>
{literal}
    .ap-returns-page { border-radius:14px; }
    .ap-title { margin:0 0 12px; font-size:24px; font-weight:800; color:#1f3044; }

    .ap-block {
      border:1px solid #dbe6f2; border-radius:12px; background:#fff; padding:14px; margin-bottom:14px;
      box-shadow:0 6px 20px rgba(28,56,86,.05);
    }
    .ap-block h4 { margin:0 0 10px; font-size:15px; font-weight:800; color:#1f3044; }

    .ap-grid-top { display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:10px; align-items:end; }
    .ap-grid-cr { display:grid; grid-template-columns:1fr 1fr 1fr 1fr 160px auto; gap:10px; align-items:end; }
    .ap-grid-sr { display:grid; grid-template-columns:2fr 160px auto; gap:10px; align-items:end; }

    .ap-field label { display:block; margin-bottom:4px; font-size:11px; text-transform:uppercase; color:#6f8197; font-weight:800; }
    .ap-field .form-control { border-radius:9px; border:1px solid #cfdceb; box-shadow:none; }

    .ap-actions { display:flex; gap:8px; align-items:flex-end; justify-content:flex-end; }
    .ap-btn {
      border:0; border-radius:10px; padding:9px 14px; font-size:12px; font-weight:800;
      display:inline-flex; gap:6px; align-items:center; text-decoration:none !important;
      transition:all .18s ease;
    }
    .ap-btn:hover { transform:translateY(-1px); }
    .ap-btn-primary { color:#fff; background:linear-gradient(135deg,#2e8bff 0%,#1f6fe0 100%); }
    .ap-btn-secondary { color:#27415b; background:#edf4fb; }

    .ap-table-wrap { border:1px solid #dbe6f2; border-radius:12px; overflow:hidden; }
    .ap-table-top { padding:10px 12px; background:#f7fbff; border-bottom:1px solid #e5edf6; display:flex; justify-content:space-between; align-items:center; gap:12px; }
    .ap-table-top h5 { margin:0; font-size:15px; font-weight:900; color:#1f3044; }
    .ap-pill { font-size:11px; color:#46627f; background:#e8f1fb; padding:4px 8px; border-radius:999px; }
    .ap-table-wrap .table { margin:0; }
    .ap-table-wrap .table td, .ap-table-wrap .table th { vertical-align:middle; }

    .ap-muted { color:#7890a8; }
    .ap-badge {
      display:inline-flex; align-items:center; gap:6px; padding:4px 8px; border-radius:999px;
      font-size:11px; font-weight:800; background:#eef5fc; color:#2e4762;
    }

    /* Status colors (customer returns + shipments tracking) */
    .ap-st { border:1px solid transparent; }
    .ap-st-created { background:#eef5ff; color:#1f5fbf; border-color:#cfe0ff; }
    .ap-st-in-transit { background:#fff4e5; color:#8a4b00; border-color:#ffd8a8; }
    .ap-st-out-for-delivery { background:#e9f5ff; color:#155b8f; border-color:#cfe7ff; }
    .ap-st-delivered { background:#e8fbf2; color:#116a43; border-color:#c7f0dc; }
    .ap-st-finished { background:#e8fbf2; color:#116a43; border-color:#c7f0dc; }
    .ap-st-rejected { background:#ffe9ea; color:#9b1c1c; border-color:#ffc7ca; }
    .ap-st-commission-claimed { background:#f1e9ff; color:#5a2ea6; border-color:#dcc7ff; }
    .ap-st-commission-refunded { background:#e8fbf2; color:#116a43; border-color:#c7f0dc; }
    .ap-st-warehouse-delivered { background:#e8f7ff; color:#0b5d86; border-color:#cbe9fb; }
    .ap-st-warehouse-verification { background:#f1e9ff; color:#5a2ea6; border-color:#dcc7ff; }
    .ap-st-pending { background:#f2f4f7; color:#364152; border-color:#e0e5eb; }
    .ap-st-pickup { background:#f1e9ff; color:#5a2ea6; border-color:#dcc7ff; }
    .ap-st-notice-left { background:#fff4e5; color:#8a4b00; border-color:#ffd8a8; }
    .ap-st-issue { background:#ffe9ea; color:#9b1c1c; border-color:#ffc7ca; }
    .ap-st-returned { background:#ffe9ea; color:#9b1c1c; border-color:#ffc7ca; }
    .ap-st-unknown { background:#f2f4f7; color:#364152; border-color:#e0e5eb; }

    /* Our refund (PrestaShop) */
    .ap-ref-yes { background:#e8fbf2; color:#116a43; border-color:#c7f0dc; }
    .ap-ref-no { background:#f2f4f7; color:#364152; border-color:#e0e5eb; }
    .ap-ref-na { background:#fff7e6; color:#6a4b00; border-color:#ffe2a8; }

    /* Details row */
    .ap-details-row td { background:#fbfdff; }
    .ap-details {
      border:1px solid #dbe6f2; border-radius:12px; background:#fff; padding:14px;
      box-shadow:0 6px 20px rgba(28,56,86,.05);
    }
    .ap-details-head { display:flex; justify-content:space-between; align-items:center; gap:10px; }
    .ap-details-head h4 { margin:0; font-size:15px; font-weight:900; color:#1f3044; }
    .ap-details-meta { margin-top:10px; display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .ap-details-meta .ap-note { margin:0; }
    @media (max-width: 900px) { .ap-details-meta { grid-template-columns:1fr; } }

    .ap-note {
      background:#fff7e6; border:1px solid #ffe2a8; border-radius:12px; padding:10px 12px; color:#6a4b00;
    }

    @media (max-width: 1500px) {
      .ap-grid-top { grid-template-columns:1fr 1fr; }
      .ap-grid-cr { grid-template-columns:1fr 1fr; }
      .ap-grid-sr { grid-template-columns:1fr; }
      .ap-actions { justify-content:flex-start; }
    }
{/literal}
  </style>

  <h3 class="ap-title"><i class="icon icon-undo"></i> Zwroty</h3>

  {* GLOBAL FILTERS *}
  <form method="get" action="{$admin_link|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="controller" value="AdminAllegroProReturns" />
    <input type="hidden" name="token" value="{$smarty.get.token|escape:'htmlall':'UTF-8'}" />

    <div class="ap-block">
      <h4>Zakres danych</h4>
      <div class="ap-grid-top">
        <div class="ap-field">
          <label>Konto Allegro</label>
          <select name="id_allegropro_account" class="form-control">
            {foreach from=$allegropro_accounts item=a}
              <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account|intval == $allegropro_selected_account_id|intval}selected{/if}>
                {$a.label|escape:'htmlall':'UTF-8'}{if $a.allegro_login} ({$a.allegro_login|escape:'htmlall':'UTF-8'}){/if}
              </option>
            {/foreach}
          </select>
          <div class="ap-muted" style="margin-top:6px; font-size:12px;">Zakres dat dotyczy: zwrotów klienckich (createdAt) oraz przesyłek zwróconych (status_changed_at/updated_at).</div>
        </div>

        <div class="ap-field">
          <label>Data od</label>
          <input type="date" name="date_from" value="{$allegropro_date_from|escape:'htmlall':'UTF-8'}" class="form-control" />
        </div>

        <div class="ap-field">
          <label>Data do</label>
          <input type="date" name="date_to" value="{$allegropro_date_to|escape:'htmlall':'UTF-8'}" class="form-control" />
        </div>

        <div class="ap-actions">
          <button type="submit" class="ap-btn ap-btn-primary"><i class="icon icon-search"></i> Zastosuj</button>
          <a href="{$admin_link|escape:'htmlall':'UTF-8'}" class="ap-btn ap-btn-secondary"><i class="icon icon-refresh"></i> Reset</a>
        </div>
      </div>
    </div>

    {* CUSTOMER RETURNS *}
    <div class="ap-block">
      <h4>Zwroty produktów (zwroty klienckie)</h4>

      <div class="ap-grid-cr" style="margin-bottom:10px;">
        <div class="ap-field">
          <label>Status</label>
          <select name="cr_status" class="form-control">
            <option value="" {if !$allegropro_cr_status}selected{/if}>Wszystkie</option>
            <option value="CREATED" {if $allegropro_cr_status=='CREATED'}selected{/if}>Utworzone</option>
            <option value="IN_TRANSIT" {if $allegropro_cr_status=='IN_TRANSIT'}selected{/if}>W drodze</option>
            <option value="DELIVERED" {if $allegropro_cr_status=='DELIVERED'}selected{/if}>Dostarczone</option>
            <option value="FINISHED" {if $allegropro_cr_status=='FINISHED'}selected{/if}>Zakończone</option>
            <option value="REJECTED" {if $allegropro_cr_status=='REJECTED'}selected{/if}>Odrzucone</option>
            <option value="COMMISSION_REFUND_CLAIMED" {if $allegropro_cr_status=='COMMISSION_REFUND_CLAIMED'}selected{/if}>Wniosek o prowizję</option>
            <option value="COMMISSION_REFUNDED" {if $allegropro_cr_status=='COMMISSION_REFUNDED'}selected{/if}>Prowizja zwrócona</option>
            <option value="WAREHOUSE_DELIVERED" {if $allegropro_cr_status=='WAREHOUSE_DELIVERED'}selected{/if}>Magazyn (dostarcz.)</option>
            <option value="WAREHOUSE_VERIFICATION" {if $allegropro_cr_status=='WAREHOUSE_VERIFICATION'}selected{/if}>Magazyn (weryf.)</option>
          </select>
        </div>

        <div class="ap-field">
          <label>Order ID (uuid)</label>
          <input type="text" name="cr_order_id" value="{$allegropro_cr_order_id|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="np. 4a0a6511-dfd7-..." />
        </div>

        <div class="ap-field">
          <label>Kupujący (login)</label>
          <input type="text" name="cr_buyer_login" value="{$allegropro_cr_buyer_login|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="np. klient123" />
        </div>

        <div class="ap-field">
          <label>Numer zwrotu</label>
          <input type="text" name="cr_reference" value="{$allegropro_cr_reference|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="np. XGQX/2026" />
        </div>

        <div class="ap-field">
          <label>Na stronę</label>
          <select name="cr_per_page" class="form-control">
            <option value="25" {if $allegropro_cr_per_page|intval == 25}selected{/if}>25</option>
            <option value="50" {if $allegropro_cr_per_page|intval == 50}selected{/if}>50</option>
            <option value="100" {if $allegropro_cr_per_page|intval == 100}selected{/if}>100</option>
            <option value="200" {if $allegropro_cr_per_page|intval == 200}selected{/if}>200</option>
            <option value="500" {if $allegropro_cr_per_page|intval == 500}selected{/if}>500</option>
            <option value="1000" {if $allegropro_cr_per_page|intval == 1000}selected{/if}>1000</option>
          </select>
        </div>

        <div class="ap-actions">
          <button type="submit" class="ap-btn ap-btn-primary"><i class="icon icon-filter"></i> Filtruj</button>
        </div>
      </div>

      {if $allegropro_customer_returns_error}
        <div class="ap-note">{$allegropro_customer_returns_error|escape:'htmlall':'UTF-8'}</div>
      {/if}

      {assign var=ap_cr_details_id value=$allegropro_customer_return_id}
      {assign var=ap_cr_details_shown value=0}

      <div class="ap-table-wrap">
        <div class="ap-table-top">
          <h5>Lista zwrotów</h5>
          <div class="ap-pill">{$allegropro_cr_total_rows|intval} rekordów • strona {$allegropro_cr_page|intval} / {$allegropro_cr_total_pages|intval}</div>
        </div>

        <table class="table">
          <thead>
            <tr>
              <th>Utworzono</th>
              <th>Status</th>
              <th>Numer</th>
              <th>Zamówienie</th>
              <th>Kupujący</th>
              <th>Pozycje</th>
              <th>Wartość (szac.)</th>
              <th>Zwrot płatności</th>
              <th>Przesyłka zwrotna</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {if empty($allegropro_customer_returns)}
              <tr><td colspan="10" class="ap-muted">Brak wyników dla wybranych filtrów.</td></tr>
            {else}
              {foreach from=$allegropro_customer_returns item=r}
                <tr id="cr-{$r.id|escape:'htmlall':'UTF-8'}">
                  <td>{$r.createdAt|escape:'htmlall':'UTF-8'|replace:'T':' '|replace:'Z':''}</td>
                  <td><span class="ap-badge ap-st ap-st-{$r.status_class|escape:'htmlall':'UTF-8'}">{$r.status_label|escape:'htmlall':'UTF-8'}</span></td>
                  <td>{$r.referenceNumber|escape:'htmlall':'UTF-8'}</td>
                  <td>{$r.orderId|escape:'htmlall':'UTF-8'}</td>
                  <td>
                    <b>{$r.buyer_login|escape:'htmlall':'UTF-8'}</b>
                    {if $r.buyer_email}<div class="ap-muted" style="font-size:12px;">{$r.buyer_email|escape:'htmlall':'UTF-8'}</div>{/if}
                  </td>
                  <td>{$r.items_count|intval}</td>
                  <td>
                    {if isset($r.items_total_fmt)}{$r.items_total_fmt|escape:'htmlall':'UTF-8'}{else}{$r.items_total|escape:'htmlall':'UTF-8'}{/if}
                    {$r.items_currency|escape:'htmlall':'UTF-8'}
                  </td>
                  <td>
                    {if $r.pay_refund_state == 'yes'}
                      <span class="ap-badge ap-st ap-ref-yes">Tak</span>
                      <div class="ap-muted" style="font-size:12px;">{$r.pay_refund_total_fmt|escape:'htmlall':'UTF-8'} {$r.pay_refund_currency|escape:'htmlall':'UTF-8'}</div>
                    {elseif $r.pay_refund_state == 'no'}
                      <span class="ap-badge ap-st ap-ref-no">Nie</span>
                    {else}
                      <span class="ap-badge ap-st ap-ref-na">Brak danych</span>
                    {/if}
                  </td>
                  <td>
                    {if $r.waybill}
                      <div><b>{$r.waybill|escape:'htmlall':'UTF-8'}</b></div>
                      {if $r.carrierId}<div class="ap-muted" style="font-size:12px;">{$r.carrierId|escape:'htmlall':'UTF-8'}</div>{/if}
                    {else}
                      <span class="ap-muted">—</span>
                    {/if}
                  </td>
                  <td style="white-space:nowrap; text-align:right;">
                    {if $ap_cr_details_id && $ap_cr_details_id == $r.id}
                      <a class="ap-btn ap-btn-secondary" style="padding:6px 10px;" data-ap-restore="1" href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&cr_page={$allegropro_cr_page|intval}&sr_page={$allegropro_sr_page|intval}">
                        <i class="icon icon-minus"></i> Zwiń
                      </a>
                    {else}
                      <a class="ap-btn ap-btn-secondary" style="padding:6px 10px;" data-ap-restore="1" href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&cr_page={$allegropro_cr_page|intval}&sr_page={$allegropro_sr_page|intval}&customer_return_id={$r.id|escape:'htmlall':'UTF-8'}">
                        <i class="icon icon-zoom-in"></i> Szczegóły
                      </a>
                    {/if}
                  </td>
                </tr>

                {if $ap_cr_details_id && $ap_cr_details_id == $r.id}
                  {assign var=ap_cr_details_shown value=1}
                  <tr class="ap-details-row">
                    <td colspan="10">
                      {if $allegropro_customer_return_details_error}
                        <div class="ap-note">{$allegropro_customer_return_details_error|escape:'htmlall':'UTF-8'}</div>
                      {elseif $allegropro_customer_return_details}
                        <div class="ap-details">
                          <div class="ap-details-head">
                            <h4>
                              Szczegóły zwrotu
                              {if $allegropro_customer_return_details.referenceNumber}
                                <span class="ap-pill" style="margin-left:8px;">{$allegropro_customer_return_details.referenceNumber|escape:'htmlall':'UTF-8'}</span>
                              {/if}
                              {if $allegropro_customer_return_details._status_label}
                                <span class="ap-badge ap-st ap-st-{$allegropro_customer_return_details._status_class|escape:'htmlall':'UTF-8'}" style="margin-left:8px;">{$allegropro_customer_return_details._status_label|escape:'htmlall':'UTF-8'}</span>
                              {/if}
                            </h4>

                            <a class="ap-btn ap-btn-secondary" style="padding:6px 10px;" data-ap-restore="1" href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&cr_page={$allegropro_cr_page|intval}&sr_page={$allegropro_sr_page|intval}">
                              <i class="icon icon-minus"></i> Zwiń
                            </a>
                          </div>

                          <div style="margin-top:12px;">
                            <h4 style="margin:0 0 10px;">Zwrot płatności dla kupującego (Allegro Finanse)</h4>

                            {if $allegropro_customer_return_details._payment_id}
                              <div class="ap-note" style="background:#eef6ff; border-color:#cfe6ff; color:#1f4b7a;">
                                <b>Payment ID:</b> {$allegropro_customer_return_details._payment_id|escape:'htmlall':'UTF-8'}
                              </div>

                              {if $allegropro_customer_return_details._pay_refund_state == 'yes'}
                                <div class="ap-note" style="margin-top:10px; background:#f3fff8; border-color:#cfeedd; color:#1f6a43;">
                                  <b>Zwrócono:</b> {$allegropro_customer_return_details._pay_refund_total_fmt|escape:'htmlall':'UTF-8'} {$allegropro_customer_return_details._pay_refund_currency|escape:'htmlall':'UTF-8'}
                                  <span class="ap-muted">(operacji: {$allegropro_customer_return_details._pay_refund_count|intval}{if $allegropro_customer_return_details._pay_refund_last_at}, ostatnia: {$allegropro_customer_return_details._pay_refund_last_at|escape:'htmlall':'UTF-8'}{/if})</span>
                                </div>
                              {elseif $allegropro_customer_return_details._pay_refund_state == 'no'}
                                <div class="ap-note" style="margin-top:10px; background:#fff7e6; border-color:#ffe2a8; color:#6a4b00;">
                                  Brak zwrotu płatności w danych finansowych dla tego payment_id.
                                </div>
                              {else}
                                <div class="ap-note" style="margin-top:10px; background:#fff7e6; border-color:#ffe2a8; color:#6a4b00;">
                                  Brak danych o zwrocie płatności (uruchom synchronizację: Finanse → Operacje płatnicze).
                                </div>
                              {/if}

                              {if !empty($allegropro_customer_return_details._pay_refund_ops)}
                                <div class="ap-table-wrap" style="margin-top:10px;">
                                  <div class="ap-table-top">
                                    <h5>Operacje zwrotu płatności</h5>
                                    <div class="ap-pill">{$allegropro_customer_return_details._pay_refund_count|intval} operacji</div>
                                  </div>
                                  <table class="table">
                                    <thead>
                                      <tr>
                                        <th>Data</th>
                                        <th>Kwota</th>
                                        <th>Operacja</th>
                                        <th>Typ</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      {foreach from=$allegropro_customer_return_details._pay_refund_ops item=op}
                                        <tr>
                                          <td>{$op.occurred_at|escape:'htmlall':'UTF-8'}</td>
                                          <td>{$op.amount_fmt|escape:'htmlall':'UTF-8'} {$op.currency|escape:'htmlall':'UTF-8'}</td>
                                          <td>{$op.operation_id|escape:'htmlall':'UTF-8'}</td>
                                          <td title="{$op.group|escape:'htmlall':'UTF-8'}/{$op.type|escape:'htmlall':'UTF-8'}">{$op.label|escape:'htmlall':'UTF-8'}</td>
                                        </tr>
                                      {/foreach}
                                    </tbody>
                                  </table>
                                </div>
                              {/if}
                            {else}
                              <div class="ap-note" style="background:#fff7e6; border-color:#ffe2a8; color:#6a4b00;">
                                Brak payment_id dla tego zamówienia w module – nie można odczytać zwrotu płatności (uruchom synchronizację zamówień/płatności).
                              </div>
                            {/if}
                          </div>

                          <div class="ap-details-meta">
                            <div class="ap-note" style="background:#eef6ff; border-color:#cfe6ff; color:#1f4b7a;">
                              <b>Kupujący:</b>
                              {$allegropro_customer_return_details.buyer.login|escape:'htmlall':'UTF-8'}
                              {if $allegropro_customer_return_details.buyer.email}
                                <span class="ap-muted">({$allegropro_customer_return_details.buyer.email|escape:'htmlall':'UTF-8'})</span>
                              {/if}
                              <br/>
                              <b>Zamówienie:</b> {$allegropro_customer_return_details.orderId|escape:'htmlall':'UTF-8'}<br/>
                              <b>Utworzono:</b> {$allegropro_customer_return_details.createdAt|escape:'htmlall':'UTF-8'|replace:'T':' '|replace:'Z':''}
                            </div>

                            {if isset($allegropro_customer_return_details.refund.bankAccount)}
                              <div class="ap-note" style="background:#f3fff8; border-color:#cfeedd; color:#1f6a43;">
                                <b>Zwrot na konto:</b> {$allegropro_customer_return_details.refund.bankAccount.owner|escape:'htmlall':'UTF-8'}<br/>
                                {if isset($allegropro_customer_return_details.refund.bankAccount.accountNumber_masked)}
                                  <b>Konto:</b> {$allegropro_customer_return_details.refund.bankAccount.accountNumber_masked|escape:'htmlall':'UTF-8'}
                                {/if}
                                {if isset($allegropro_customer_return_details.refund.bankAccount.iban_masked)}
                                  <br/><b>IBAN:</b> {$allegropro_customer_return_details.refund.bankAccount.iban_masked|escape:'htmlall':'UTF-8'}
                                {/if}
                              </div>
                            {else}
                              <div class="ap-note" style="background:#fff7e6; border-color:#ffe2a8; color:#6a4b00;">
                                Brak danych o koncie do zwrotu (w odpowiedzi Allegro).
                              </div>
                            {/if}
                          </div>

                          {if !empty($allegropro_customer_return_details.items)}
                            <div style="margin-top:12px;">
                              <h4 style="margin:0 0 10px;">Pozycje zwrotu</h4>
                              <div class="ap-table-wrap">
                                <table class="table">
                                  <thead>
                                    <tr>
                                      <th>Oferta</th>
                                      <th>Nazwa</th>
                                      <th>Ilość</th>
                                      <th>Cena</th>
                                      <th>Powód</th>
                                      <th>Komentarz</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    {foreach from=$allegropro_customer_return_details.items item=it}
                                      <tr>
                                        <td>{$it.offerId|escape:'htmlall':'UTF-8'}</td>
                                        <td>
                                          {$it.name|escape:'htmlall':'UTF-8'}
                                          {if isset($it.url) && $it.url}
                                            <div class="ap-muted" style="font-size:12px;">
                                              <a href="{$it.url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">Podgląd oferty</a>
                                            </div>
                                          {/if}
                                        </td>
                                        <td>{$it.quantity|intval}</td>
                                        <td>
                                          {if isset($it.price.amount)}{$it.price.amount|escape:'htmlall':'UTF-8'}{/if}
                                          {if isset($it.price.currency)} {$it.price.currency|escape:'htmlall':'UTF-8'}{/if}
                                        </td>
                                        <td>
                                          {if isset($it.reason.type_label)}<span title="Kod: {$it.reason.type|escape:'htmlall':'UTF-8'}">{$it.reason.type_label|escape:'htmlall':'UTF-8'}</span>{elseif isset($it.reason.type)}{$it.reason.type|escape:'htmlall':'UTF-8'}{/if}
                                        </td>
                                        <td>{if isset($it.reason.userComment)}{$it.reason.userComment|escape:'htmlall':'UTF-8'}{/if}</td>
                                      </tr>
                                    {/foreach}
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          {/if}
                        </div>
                      {else}
                        <div class="ap-note">Brak danych szczegółów dla wybranego zwrotu.</div>
                      {/if}
                    </td>
                  </tr>
                {/if}
              {/foreach}
            {/if}
          </tbody>
        </table>

        {if $ap_cr_details_id && !$ap_cr_details_shown}
          <div class="ap-note" style="margin-top:10px;">
            Wybrany zwrot nie znajduje się na tej stronie listy (sprawdź filtry/paginację).
          </div>
        {/if}

        <div style="padding:10px 12px; border-top:1px solid #e5edf6; background:#fcfeff; display:flex; justify-content:space-between; align-items:center;">
          <div class="ap-muted">Paginacja dotyczy tylko listy zwrotów klienckich.</div>
          <div>
            {if $allegropro_cr_page|intval > 1}
              <a class="ap-btn ap-btn-secondary" style="padding:6px 10px;" href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&cr_page={$allegropro_cr_page-1|intval}&sr_page={$allegropro_sr_page|intval}">&larr; Poprzednia</a>
            {/if}
            {if $allegropro_cr_page|intval < $allegropro_cr_total_pages|intval}
              <a class="ap-btn ap-btn-secondary" style="padding:6px 10px;" href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&cr_page={$allegropro_cr_page+1|intval}&sr_page={$allegropro_sr_page|intval}">Następna &rarr;</a>
            {/if}
          </div>
        </div>
      </div>
    </div>

    {* SHIPMENT RETURNS *}
    <div class="ap-block">
      <h4>Zwroty przesyłek nieodebranych / zwróconych do nadawcy</h4>
      <div class="ap-muted" style="margin-bottom:10px;">
        Lista oparta o statusy przesyłek w module (status: RETURNED / RETURNED_TO_SENDER). Jeśli dane są niepełne, uruchom synchronizację przesyłek.
      </div>

      <div class="ap-grid-sr" style="margin-bottom:10px;">
        <div class="ap-field">
          <label>Szukaj</label>
          <input type="text" name="sr_query" value="{$allegropro_sr_query|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="orderId / shipmentId / tracking / kupujący" />
        </div>
        <div class="ap-field">
          <label>Na stronę</label>
          <select name="sr_per_page" class="form-control">
            <option value="10" {if $allegropro_sr_per_page|intval == 10}selected{/if}>10</option>
            <option value="25" {if $allegropro_sr_per_page|intval == 25}selected{/if}>25</option>
            <option value="50" {if $allegropro_sr_per_page|intval == 50}selected{/if}>50</option>
            <option value="100" {if $allegropro_sr_per_page|intval == 100}selected{/if}>100</option>
          </select>
        </div>
        <div class="ap-actions">
          <button type="submit" class="ap-btn ap-btn-primary"><i class="icon icon-filter"></i> Filtruj</button>
        </div>
      </div>

      {if $allegropro_sr_tracking_error}
        <div class="ap-note" style="margin-bottom:10px;">{$allegropro_sr_tracking_error|escape:'htmlall':'UTF-8'}</div>
      {/if}

      {if $allegropro_sr_tracking}
        <div class="ap-block" style="margin:12px 0 12px;">
          <h4>Tracking: {$allegropro_sr_tracking_waybill|escape:'htmlall':'UTF-8'} <span class="ap-pill">{$allegropro_sr_tracking_carrier|escape:'htmlall':'UTF-8'}</span></h4>
          {foreach from=$allegropro_sr_tracking.waybills item=wb}
            <div class="ap-table-wrap" style="margin-top:10px;">
              <div class="ap-table-top">
                <h5>Historia statusów</h5>
                <div class="ap-pill">{$wb.updatedAt|escape:'htmlall':'UTF-8'}</div>
              </div>
              <table class="table">
                <thead>
                  <tr>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Opis</th>
                  </tr>
                </thead>
                <tbody>
                  {foreach from=$wb.statuses item=st}
                    <tr>
                      <td>{$st.occurredAt|escape:'htmlall':'UTF-8'|replace:'T':' '|replace:'Z':''}</td>
                  <td><span class="ap-badge ap-st ap-st-{$st.class|escape:'htmlall':'UTF-8'}">{$st.label|escape:'htmlall':'UTF-8'}</span></td>
                      <td>{$st.description|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                  {/foreach}
                </tbody>
              </table>
            </div>
          {/foreach}
        </div>
      {/if}

      <div class="ap-table-wrap">
        <div class="ap-table-top">
          <h5>Lista przesyłek zwróconych</h5>
          <div class="ap-pill">{$allegropro_sr_total_rows|intval} rekordów • strona {$allegropro_sr_page|intval} / {$allegropro_sr_total_pages|intval}</div>
        </div>

        <table class="table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Status</th>
              <th>Tracking</th>
              <th>Zamówienie</th>
              <th>Kupujący</th>
              <th>Metoda dostawy</th>
              <th>Konto</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {if empty($allegropro_returned_shipments)}
              <tr><td colspan="8" class="ap-muted">Brak wyników dla wybranych filtrów.</td></tr>
            {else}
              {foreach from=$allegropro_returned_shipments item=s}
                <tr>
                  <td>{$s.status_changed_at|escape:'htmlall':'UTF-8'}</td>
                  <td><span class="ap-badge ap-st ap-st-{$s.status_class|escape:'htmlall':'UTF-8'}">{$s.status_label|escape:'htmlall':'UTF-8'}</span></td>
                  <td>
                    {if $s.tracking_number}
                      <b>{$s.tracking_number|escape:'htmlall':'UTF-8'}</b>
                      {if $s.carrier_id}<div class="ap-muted" style="font-size:12px;">{$s.carrier_id|escape:'htmlall':'UTF-8'}</div>{/if}
                    {else}
                      <span class="ap-muted">—</span>
                    {/if}
                  </td>
                  <td>{$s.checkout_form_id|escape:'htmlall':'UTF-8'}</td>
                  <td>{$s.buyer_login|escape:'htmlall':'UTF-8'}</td>
                  <td>{$s.shipping_method_name|escape:'htmlall':'UTF-8'}</td>
                  <td>{$s.account_label|escape:'htmlall':'UTF-8'}</td>
                  <td style="white-space:nowrap; text-align:right;">
                    {if $s.tracking_number && $s.carrier_id}
                      <a class="ap-btn ap-btn-secondary" style="padding:6px 10px;" href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&cr_page={$allegropro_cr_page|intval}&sr_page={$allegropro_sr_page|intval}&sr_waybill={$s.tracking_number|escape:'htmlall':'UTF-8'}&sr_carrier_id={$s.carrier_id|escape:'htmlall':'UTF-8'}">
                        <i class="icon icon-zoom-in"></i> Tracking
                      </a>
                    {else}
                      <span class="ap-muted">Brak danych</span>
                    {/if}
                  </td>
                </tr>
              {/foreach}
            {/if}
          </tbody>
        </table>

        <div style="padding:10px 12px; border-top:1px solid #e5edf6; background:#fcfeff; display:flex; justify-content:space-between; align-items:center;">
          <div class="ap-muted">Paginacja dotyczy listy przesyłek zwróconych.</div>
          <div>
            {if $allegropro_sr_page|intval > 1}
              <a class="ap-btn ap-btn-secondary" style="padding:6px 10px;" href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&cr_page={$allegropro_cr_page|intval}&sr_page={$allegropro_sr_page-1|intval}">&larr; Poprzednia</a>
            {/if}
            {if $allegropro_sr_page|intval < $allegropro_sr_total_pages|intval}
              <a class="ap-btn ap-btn-secondary" style="padding:6px 10px;" href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&cr_page={$allegropro_cr_page|intval}&sr_page={$allegropro_sr_page+1|intval}">Następna &rarr;</a>
            {/if}
          </div>
        </div>
      </div>

    </div>

  </form>
</div>



{literal}
<script>
(function() {
  // Chcemy, żeby po kliknięciu "Szczegóły/Zwiń" strona NIE przeskakiwała do góry.
  // Zapisujemy pozycję wiersza w widoku i po przeładowaniu odtwarzamy scroll tak,
  // aby kliknięty wiersz był w tym samym miejscu na ekranie.
  var KEY_ACTIVE = 'ap_returns_restore_active';
  var KEY_ROW_ID = 'ap_returns_restore_row';
  var KEY_ROW_OFFSET = 'ap_returns_restore_offset';

  function savePosition(anchor) {
    try {
      var tr = anchor.closest('tr');
      if (!tr || !tr.id) {
        return;
      }
      var rect = tr.getBoundingClientRect();
      sessionStorage.setItem(KEY_ACTIVE, '1');
      sessionStorage.setItem(KEY_ROW_ID, tr.id);
      sessionStorage.setItem(KEY_ROW_OFFSET, String(rect.top));
    } catch (e) {}
  }

  function restorePosition() {
    try {
      if (sessionStorage.getItem(KEY_ACTIVE) !== '1') return;

      sessionStorage.removeItem(KEY_ACTIVE);
      var rowId = sessionStorage.getItem(KEY_ROW_ID) || '';
      var offset = parseFloat(sessionStorage.getItem(KEY_ROW_OFFSET) || '0');

      sessionStorage.removeItem(KEY_ROW_ID);
      sessionStorage.removeItem(KEY_ROW_OFFSET);

      if (!rowId) return;

      var tr = document.getElementById(rowId);
      if (!tr) return;

      var y = tr.getBoundingClientRect().top + window.pageYOffset - offset;
      if (y < 0) y = 0;
      window.scrollTo(0, y);
    } catch (e) {}
  }

  document.addEventListener('click', function(e) {
    var a = e.target.closest('a[data-ap-restore="1"]');
    if (!a) return;
    savePosition(a);
  }, true);

  window.addEventListener('load', function() {
    // setTimeout: żeby przeglądarka skończyła własne przewijanie (np. do topu)
    setTimeout(restorePosition, 0);
  });
})();
</script>
{/literal}
