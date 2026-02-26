{* AllegroPro - Rozliczenie (wpłaty + opłaty/zwroty opłat per checkoutFormId) *}

{assign var=has_issues value=($recon_kpi.issues|intval > 0)}

{* URL do szybkiego resetu filtrów (zostawia konto + zakres dat + limit + tryb sync) *}
{assign var=recon_reset_url value=$base_url|cat:'&view=recon'|cat:'&id_allegropro_account='|cat:$selected_account_id|cat:'&date_from='|cat:$date_from|cat:'&date_to='|cat:$date_to|cat:'&limit='|cat:$limit|cat:'&sync_mode='|cat:$sync_mode}

<div class="alpro-page alpro-recon">

  {* Header / nawigacja widoków *}
  <div class="card alpro-header-card mb-3">
    <div class="card-body">
      <div class="alpro-header">
        <div class="alpro-header-left">
          <div class="alpro-header-title">
            <i class="material-icons">swap_horiz</i>
            <span class="alpro-badge" title="Widok kontrolny: wpłaty kupującego + opłaty/zwroty opłat (payment-operations) powiązane po payment_id, agregacja do checkoutFormId.">
              Rozliczenie
            </span>
            <span class="alpro-last-sync">
              Ostatnia synchronizacja:
              {if $last_sync_at}
                <strong>{$last_sync_at|escape:'htmlall':'UTF-8'}</strong>
              {else}
                <strong>brak</strong>
              {/if}
            </span>
          </div>
          <div class="alpro-header-sub text-muted">
            Ten widok pokazuje: <strong>zapłacono</strong> (z płatności), <strong>cashflow</strong> (WAITING+AVAILABLE) oraz <strong>opłaty/zwroty opłat</strong> naliczone przez Allegro.
            <span class="text-muted">To jest kontrola przepływu środków w portfelach, a nie pełny rejestr księgowy.</span>
          </div>
        </div>

        <div class="alpro-header-right">
          <div class="btn-group" role="group" aria-label="Widoki">
            <a class="btn btn-default" href="{$view_tx_url|escape:'htmlall':'UTF-8'}"><i class="material-icons">payments</i> Wpłaty</a>
            <a class="btn btn-default active" href="{$view_recon_url|escape:'htmlall':'UTF-8'}"><i class="material-icons">receipt_long</i> Rozliczenie</a>
            {if isset($view_billing_url)}
              <a class="btn btn-default" href="{$view_billing_url|escape:'htmlall':'UTF-8'}"><i class="material-icons">attach_money</i> Opłaty</a>
            {/if}
            <a class="btn btn-default" href="{$view_raw_url|escape:'htmlall':'UTF-8'}"><i class="material-icons">list</i> Dziennik RAW</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  {* Filtry *}
  <div class="card alpro-filters-card mb-3">
    <div class="card-header">
      <div class="alpro-filters-head">
        <div>
          <strong>Filtry</strong>
          <span class="text-muted">(okres i zawężenia listy)</span>
        </div>
        {if $participant_login!='' || $payment_id!='' || $order_status!=''}
          <div class="alpro-active-filter" title="Masz ustawione filtry dodatkowe – kliknij, aby wrócić do pełnej listy w tym okresie.">
            <i class="material-icons">filter_alt</i>
            Filtr aktywny
            <a class="alpro-clear" href="{$recon_reset_url|escape:'htmlall':'UTF-8'}" title="Wyczyść filtry">×</a>
          </div>
        {/if}
      </div>
    </div>

    <div class="card-body">
      <form method="get" action="{$base_url|escape:'htmlall':'UTF-8'}" class="alpro-filters-form">
        <input type="hidden" name="controller" value="AdminAllegroProCashflows">
        <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="view" value="recon">

        <div class="row">
          <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="form-group">
              <label for="id_allegropro_account">Konto</label>
              <select class="form-control" name="id_allegropro_account" id="id_allegropro_account">
                {foreach from=$accounts item=a}
                  <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account|intval == $selected_account_id}selected{/if}>
                    {$a.label|default:('#'|cat:$a.id_allegropro_account)|escape:'htmlall':'UTF-8'}
                  </option>
                {/foreach}
              </select>
            </div>
          </div>

          <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="form-group">
              <label>Od (data płatności)</label>
              <input class="form-control" type="date" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}">
            </div>
          </div>

          <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="form-group">
              <label>Do (data płatności)</label>
              <input class="form-control" type="date" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}">
            </div>
          </div>

          <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="form-group">
              <label>Status zamówienia (Allegro)</label>
              <select class="form-control" name="order_status" title="Status z cache zamówień Allegro (allegropro_order.status).">
                <option value="" {if $order_status==''}selected{/if}>Wszystkie</option>
                <option value="BOUGHT" {if $order_status=='BOUGHT'}selected{/if}>BOUGHT</option>
                <option value="FILLED_IN" {if $order_status=='FILLED_IN'}selected{/if}>FILLED_IN</option>
                <option value="READY_FOR_PROCESSING" {if $order_status=='READY_FOR_PROCESSING'}selected{/if}>READY_FOR_PROCESSING</option>
                <option value="PROCESSING" {if $order_status=='PROCESSING'}selected{/if}>PROCESSING</option>
                <option value="SENT" {if $order_status=='SENT'}selected{/if}>SENT</option>
                <option value="CANCELLED" {if $order_status=='CANCELLED'}selected{/if}>CANCELLED</option>
              </select>
            </div>
          </div>

          <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="form-group">
              <label>Tryb synchronizacji</label>
              <select class="form-control" name="sync_mode" title="Fill = nowe + uzupełnij brakujące dni. Full = pełne pobranie zakresu (wolniej).">
                <option value="fill" {if $sync_mode=='fill'}selected{/if}>NOWE + uzupełnij braki</option>
                <option value="full" {if $sync_mode=='full'}selected{/if}>PEŁNA synchronizacja</option>
              </select>
            </div>
          </div>
        </div>

        <details class="alpro-advanced" {if $participant_login!='' || $payment_id!=''}open{/if}>
          <summary>
            <i class="material-icons">tune</i>
            Filtry zaawansowane
            <span class="text-muted">(login / payment.id / paginacja)</span>
          </summary>
          <div class="row" style="margin-top:10px;">
            <div class="col-lg-3 col-md-6 col-sm-12">
              <div class="form-group">
                <label>buyer.login</label>
                <input class="form-control" type="text" name="participant_login" value="{$participant_login|escape:'htmlall':'UTF-8'}" placeholder="login...">
              </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-12">
              <div class="form-group">
                <label>payment.id</label>
                <input class="form-control" type="text" name="payment_id" value="{$payment_id|escape:'htmlall':'UTF-8'}" placeholder="uuid...">
              </div>
            </div>

            <div class="col-lg-2 col-md-4 col-sm-6">
              <div class="form-group">
                <label title="Ilość wierszy na stronie (nie wpływa na synchronizację).">Na stronę</label>
                <select class="form-control" name="limit" title="Ilość wierszy na stronie (nie wpływa na synchronizację).">
                  <option value="25" {if $limit==25}selected{/if}>25</option>
                  <option value="50" {if $limit==50}selected{/if}>50</option>
                  <option value="100" {if $limit==100}selected{/if}>100</option>
                  <option value="200" {if $limit==200}selected{/if}>200</option>
                </select>
              </div>
            </div>
          </div>
        </details>

        <div class="alpro-actions">
          <button type="submit" class="btn btn-primary"><i class="material-icons">filter_alt</i> Pokaż</button>
          <button type="button"
                  class="btn btn-default alpro-sync-btn"
                  data-ajax-url="{$ajax_sync_url|escape:'htmlall':'UTF-8'}"
                  data-after-enrich="1"
                  data-enrich-count-url="{$enrich_missing_count_url|escape:'htmlall':'UTF-8'}"
                  data-enrich-step-url="{$enrich_missing_step_url|escape:'htmlall':'UTF-8'}"
                  title="Synchronizuj dane do cache DB (pobieranie partiami). Po zakończeniu odświeży statusy zamówień z Allegro.">
            <i class="material-icons">sync</i> Synchronizuj
          </button>
          <a class="btn btn-default" href="{$export_recon_url|escape:'htmlall':'UTF-8'}" title="Eksport CSV: rozliczenie per checkoutFormId (wg aktualnych filtrów).">
            <i class="material-icons">download</i> CSV
          </a>
          {if $participant_login!='' || $payment_id!='' || $order_status!=''}
            <a class="btn btn-default" href="{$recon_reset_url|escape:'htmlall':'UTF-8'}" title="Wyczyść filtry i pokaż pełną listę">
              <i class="material-icons">list</i> Pokaż wszystko
            </a>
          {/if}
        </div>

      </form>

      {if !$recon_api.ok}
        <div class="alert alert-warning" style="margin-top:10px;">
          Nie udało się zbudować widoku rozliczenia (brak cache). Kliknij <strong>Synchronizuj</strong>.
          {if $recon_api.error}<br><small>{$recon_api.error|escape:'htmlall':'UTF-8'}</small>{/if}
        </div>
      {elseif $recon_api.totalCount|intval == 0}
        <div class="alert alert-info" style="margin-top:10px;">
          Brak danych rozliczenia dla tego okresu.
        </div>
      {/if}

      <div class="text-muted" style="margin-top:10px; font-size:12px;">
        <strong>Opłaty</strong> / <strong>Zwroty opłat</strong> dotyczą rozliczeń <strong>Allegro ↔ Twoje konto</strong> (nie zwrotów dla klienta).
      </div>

      <div class="alert alert-info" style="margin-top:12px;">
        <strong>Czym różni się „Rozliczenie” od zakładki „Opłaty (BILLING)”?</strong><br>
        <ul style="margin:6px 0 0 18px;">
          <li><strong>Rozliczenie</strong> = to, co realnie weszło/zeszło z Twoich portfeli Allegro (<em>payment-operations</em>): wpływy do <code>WAITING</code>/<code>AVAILABLE</code> + potrącone opłaty i ich zwroty.</li>
          <li><strong>Opłaty (BILLING)</strong> = rejestr rozliczeń/faktur Allegro (<em>billing-entries</em>): typy opłat, VAT, korekty, pozycje które mogą nie dotyczyć konkretnego zamówienia.</li>
        </ul>
        <div class="text-muted" style="margin-top:6px; font-size:12px;">Wartości w kafelkach liczone są dla <strong>całego</strong> wybranego okresu. Pole <em>„Na stronie”</em> wpływa tylko na to, ile wierszy widzisz w tabeli.</div>
        <div style="margin-top:10px;"><strong>Jak czytać kolumny w tabeli?</strong></div>
        <ul style="margin:6px 0 0 18px;">
          <li><strong>Zapłacono</strong> – kwota zapłacona przez kupującego.</li>
          <li><strong>Cashflow (W/A)</strong> – realny wpływ do portfeli Allegro z <em>payment-operations</em>: <strong>W</strong>=WAITING (oczekujące), <strong>A</strong>=AVAILABLE (dostępne).</li>
          <li><strong>Wypłaty na konto</strong> – przelewy z portfela Allegro na Twoje konto bankowe (payment-operations: <code>OUTCOME / PAYOUT</code>). To <u>nie</u> są zwroty dla klienta.</li>
          <li><strong>Opłaty</strong>/<strong>Zwroty opłat</strong> – potrącenia Allegro z portfeli i ich zwroty (korekty, rabaty, anulowania). To <u>nie</u> są zwroty dla klienta.</li>
          <li><strong>Saldo</strong> = Cashflow − Opłaty + Zwroty opłat.</li>
        </ul>
      </div>

    </div>
  </div>

  {* Podsumowanie rozliczeń (spójne z tabelą „Rozliczenie wypłat”) *}
  {assign var=pc value=$recon_payouts.checks_meta|default:false}

  <div class="card mb-3">
    <div class="card-header">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
        <div>
          <strong><i class="material-icons" style="font-size:18px; vertical-align:middle;">insights</i> Rozliczenia w okresie</strong>
          <div class="text-muted" style="font-size:12px; margin-top:4px;">
            Kwoty liczone z <code>payment-operations</code> dla portfela <strong>AVAILABLE</strong> w oknach pomiędzy wypłatami (<code>OUTCOME / PAYOUT</code>).
            To jest jedna, spójna logika – takie same wartości widzisz w Allegro.
          </div>
        </div>

        <div class="text-muted" style="font-size:12px; margin-top:2px;">
          Okres: <strong>{$date_from|escape:'htmlall':'UTF-8'}</strong> – <strong>{$date_to|escape:'htmlall':'UTF-8'}</strong>
          {if $pc}
            <span class="alpro-note-text">· Wypłat: <strong>{$pc.count|intval}</strong></span>
            {if $pc.warn|intval > 0}
              <span class="alpro-note-text">· Ostrzeżeń: <strong>{$pc.warn|intval}</strong></span>
            {/if}
          {/if}
        </div>
      </div>
    </div>

    <div class="card-body">
      {if !$pc || $pc.count|intval == 0}
        <div class="alert alert-info" style="margin-bottom:0;">
          Brak wypłat (<code>OUTCOME / PAYOUT</code>) w tym okresie – nie można zbudować „okien między wypłatami”.
          Zmień zakres dat lub wykonaj synchronizację.
        </div>
      {else}
        <div class="alpro-payoutcheck-kpis">
          <div class="alpro-mini-kpi">
            <div class="alpro-mini-kpi-title">Zapłacono przez kupujących</div>
            <div class="alpro-mini-kpi-value text-success">{$pc.sum_payments|string_format:"%.2f"} PLN</div>
            <div class="alpro-mini-kpi-sub">Wpłaty klientów → portfel AVAILABLE</div>
          </div>

          <div class="alpro-mini-kpi">
            <div class="alpro-mini-kpi-title">Opłaty Allegro potrącone</div>
            <div class="alpro-mini-kpi-value text-danger">{$pc.sum_fee_deduction|string_format:"%.2f"} PLN</div>
            <div class="alpro-mini-kpi-sub">Opłaty (CHARGE) w AVAILABLE</div>
          </div>

          <div class="alpro-mini-kpi">
            <div class="alpro-mini-kpi-title">Zwroty opłat Allegro</div>
            <div class="alpro-mini-kpi-value text-success">{$pc.sum_fee_refund|string_format:"%.2f"} PLN</div>
            <div class="alpro-mini-kpi-sub">Zwroty/korekty opłat do AVAILABLE</div>
          </div>

          <div class="alpro-mini-kpi">
            <div class="alpro-mini-kpi-title">Inne operacje</div>
            <div class="alpro-mini-kpi-value {if $pc.sum_other_net|floatval < 0}text-danger{elseif $pc.sum_other_net|floatval > 0}text-success{else}text-muted{/if}">
              {if $pc.sum_other_net|floatval > 0}+{/if}{$pc.sum_other_net|string_format:"%.2f"} PLN
            </div>
            <div class="alpro-mini-kpi-sub">Pozostałe ruchy w AVAILABLE (np. zwroty kupującym)</div>
          </div>

          <div class="alpro-mini-kpi">
            <div class="alpro-mini-kpi-title">Bilans do wypłaty</div>
            <div class="alpro-mini-kpi-value">{$pc.sum_expected_total|string_format:"%.2f"} PLN</div>
            <div class="alpro-mini-kpi-sub">Wpłaty − Opłaty + Zwroty + Inne</div>
          </div>

          <div class="alpro-mini-kpi">
            <div class="alpro-mini-kpi-title">Wypłaty na konto</div>
            <div class="alpro-mini-kpi-value">{$pc.sum_payout|string_format:"%.2f"} PLN</div>
            <div class="alpro-mini-kpi-sub"><code>OUTCOME / PAYOUT</code> w okresie</div>
          </div>

          <div class="alpro-mini-kpi">
            <div class="alpro-mini-kpi-title">Zmiana salda AVAILABLE</div>
            <div class="alpro-mini-kpi-value {if $pc.sum_balance_change|floatval < 0}text-danger{elseif $pc.sum_balance_change|floatval > 0}text-success{else}text-muted{/if}">
              {if $pc.sum_balance_change|floatval > 0}+{/if}{$pc.sum_balance_change|string_format:"%.2f"} PLN
            </div>
            <div class="alpro-mini-kpi-sub">Saldo start − saldo koniec (dla wypłat w okresie)</div>
          </div>
        </div>
      {/if}
    </div>
  </div>

{* Problemy – szybki podgląd (bez przewijania całej tabeli) *}
  {if $recon_kpi.issues|default:0 > 0}
    <div class="alpro-issues-banner" id="alproReconIssuesBanner">
      <div class="alpro-issues-banner-left">
        <div class="alpro-issues-title">
          <i class="material-icons" style="font-size:18px; vertical-align:middle;">warning</i>
          Wykryto {$recon_kpi.issues|intval} problemów w wybranym zakresie
        </div>
        <div class="alpro-issues-sub">Kliknij w kategorię, aby zawęzić podgląd problemów.</div>
      </div>
      <div class="alpro-issues-banner-right">
        <a href="#alproReconIssuesPreview" class="btn btn-default btn-sm">Podgląd problemów</a>
      </div>
    </div>

    <div class="alpro-issues-filters" style="margin-bottom:12px;">
      <button type="button" class="btn btn-default btn-sm alpro-recon-filter" data-filter="issues">Tylko problemy</button>
      <button type="button" class="btn btn-default btn-sm alpro-recon-filter" data-filter="missing_cashflow">Brak cashflow ({$recon_kpi.issues_missing_cashflow|intval})</button>
      <button type="button" class="btn btn-default btn-sm alpro-recon-filter" data-filter="cashflow_diff">Różnica wpłaty ({$recon_kpi.issues_cashflow_diff|intval})</button>
      <button type="button" class="btn btn-default btn-sm alpro-recon-filter" data-filter="missing_refund">Brak zwrotu opłat ({$recon_kpi.issues_missing_refund|intval})</button>
      <button type="button" class="btn btn-link btn-sm alpro-recon-filter" data-filter="all" style="text-decoration:none;">Pokaż wszystko</button>
    </div>

    <div class="card alpro-table-card alpro-issues-preview" id="alproReconIssuesPreview" style="margin-bottom:16px;">
      <div class="card-header">
        <strong>Podgląd: najnowsze problemy</strong>
        <span class="text-muted" style="font-weight:400;">
          (pokazuje do {$recon_meta.issueLimit|default:20|intval}, teraz: {$recon_meta.issueCount|default:0|intval} z {$recon_kpi.issues|intval})
        </span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table alpro-table alpro-issues-table">
            <thead>
              <tr>
                <th>Data</th>
                <th>checkoutFormId</th>
                <th>Presta</th>
                <th>Klient</th>
                <th>Status zamówienia</th>
                <th style="text-align:right;">Zapłacono</th>
                <th style="text-align:right;">Cashflow (W/A)</th>
                <th style="text-align:right;">Opłaty</th>
                <th style="text-align:right;">Zwroty opłat</th>
                <th style="text-align:right;">Saldo</th>
                <th>Problem</th>
              </tr>
            </thead>
            <tbody>
              {if !isset($recon_issue_rows) || $recon_issue_rows|@count == 0}
                <tr><td colspan="11" class="text-muted">Brak danych do podglądu.</td></tr>
              {else}
                {foreach from=$recon_issue_rows item=ri}
                  {assign var=os value=$ri.order_status|default:''}
                  {assign var=os_label value='-'}
                  {assign var=os_class value='badge-secondary'}
                  {if $os=='READY_FOR_PROCESSING'}{assign var=os_label value='Gotowe do realizacji'}{assign var=os_class value='badge-success'}{/if}
                  {if $os=='PROCESSING'}{assign var=os_label value='W realizacji'}{assign var=os_class value='badge-info'}{/if}
                  {if $os=='SENT'}{assign var=os_label value='Wysłane'}{assign var=os_class value='badge-info'}{/if}
                  {if $os=='CANCELLED'}{assign var=os_label value='Anulowane'}{assign var=os_class value='badge-secondary'}{/if}

                  {assign var=rc_label value='Problem'}
                  {if $ri.issue_type=='missing_cashflow'}{assign var=rc_label value='Brak cashflow'}{/if}
                  {if $ri.issue_type=='cashflow_diff'}{assign var=rc_label value='Różnica wpłaty'}{/if}
                  {if $ri.issue_type=='missing_refund'}{assign var=rc_label value='Brak zwrotu opłat'}{/if}

                  <tr class="alpro-row-issue" data-issue="1" data-issue-type="{$ri.issue_type|escape:'htmlall':'UTF-8'}">
                    <td>{$ri.finished_at|escape:'htmlall':'UTF-8'}</td>
                    <td class="alpro-col-id" title="{$ri.checkout_form_id|escape:'htmlall':'UTF-8'}">{$ri.checkout_form_id|escape:'htmlall':'UTF-8'}</td>
                    <td>
                      {if $ri.id_order_prestashop|intval > 0}
                        <a class="btn btn-default btn-xs" href="{$admin_orders_link|escape:'htmlall':'UTF-8'}&id_order={$ri.id_order_prestashop|intval}&vieworder=1" target="_blank" rel="noopener">#{$ri.id_order_prestashop|intval}</a>
                      {else}
                        <span class="text-muted">-</span>
                      {/if}
                    </td>
                    <td>{$ri.buyer_login|escape:'htmlall':'UTF-8'}</td>
                    <td><span class="badge {$os_class}">{$os_label|escape:'htmlall':'UTF-8'}</span></td>
                    <td style="text-align:right;">{$ri.paid|string_format:"%.2f"} {$ri.currency|escape:'htmlall':'UTF-8'}</td>
                    <td style="text-align:right;">
                      <div>{$ri.cashflow_total|string_format:"%.2f"} {$ri.currency|escape:'htmlall':'UTF-8'}</div>
                      <div class="text-muted" style="font-size:11px; line-height:1.15;" title="W=WAITING (oczekujące), A=AVAILABLE (dostępne)">
                        W: {$ri.cashflow_waiting|default:0|string_format:"%.2f"} · A: {$ri.cashflow_available|default:0|string_format:"%.2f"}
                      </div>
                    </td>
                    <td style="text-align:right;"><span class="text-danger">{$ri.fee_deduction|string_format:"%.2f"}</span> {$ri.currency|escape:'htmlall':'UTF-8'}</td>
                    <td style="text-align:right;"><span class="text-success">{$ri.fee_refund|string_format:"%.2f"}</span> {$ri.currency|escape:'htmlall':'UTF-8'}</td>
                    <td style="text-align:right;"><strong>{$ri.net|string_format:"%.2f"}</strong> {$ri.currency|escape:'htmlall':'UTF-8'}</td>
                    <td><span class="badge badge-danger">{$rc_label|escape:'htmlall':'UTF-8'}</span></td>
                  </tr>
                {/foreach}
              {/if}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  {/if}


  {* Tabela *}
  <div class="card alpro-table-card" id="alproReconPayouts">
  <div class="card-header">
    <div class="alpro-table-head">
      <strong>Wypłaty na konto (poza Allegro)</strong>
      <span class="text-muted">ostatnie {$recon_payouts.shown|intval} z {$recon_payouts.total_count|intval} w tym zakresie</span>
    </div>
  </div>
  <div class="card-body">
    <p class="help-block" style="margin:0 0 12px 0;">
      To są przelewy z portfela Allegro na Twoje konto bankowe (payment-operations: <code>OUTCOME / PAYOUT</code>).
      Kwoty powinny odpowiadać przelewom widocznym w banku / w Sales Center. (To <strong>nie</strong> są zwroty dla klienta.)
      <span class="text-muted">Liczone wg daty operacji <code>occurredAt</code>.</span>
    </p>

    {if $recon_payouts.total_count|intval == 0}
      <div class="alert alert-info">Brak wypłat na konto w wybranym zakresie dat.</div>
    {else}
      <details class="alpro-details alpro-details--payouts">
        <summary class="alpro-details__summary">
          <span class="alpro-details__summary-title">Pokaż / ukryj tabelę wypłat</span>
          <span class="alpro-details__summary-meta text-muted">ostatnie {$recon_payouts.shown|intval} z {$recon_payouts.total_count|intval}</span>
        </summary>
        <div class="alpro-table-wrap">
        <table class="table alpro-table">
          <thead>
            <tr>
              <th>Data operacji</th>
              <th>Typ</th>
              <th class="text-right">Kwota</th>
              <th>Portfel</th>
              <th>Operator</th>
              <th>payout_id</th>
              <th class="text-right">Saldo portfela po</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$recon_payouts.rows item=po}
              <tr>
                <td>{$po.occurred_at|escape:'html'}</td>
                <td>{$po.type_label|escape:'html'} <span class="text-muted">({$po.type|escape:'html'})</span></td>
                <td class="text-right">
                  {if $po.amount < 0}
                    <span class="text-danger">-{$po.amount_abs|floatval|string_format:"%.2f"} {$po.currency|escape:'html'}</span>
                  {else}
                    <span class="text-success">+{$po.amount_abs|floatval|string_format:"%.2f"} {$po.currency|escape:'html'}</span>
                  {/if}
                </td>
                <td>{$po.wallet_type|escape:'html'}</td>
                <td>{$po.wallet_operator|escape:'html'}</td>
                <td>{if $po.payout_id}{$po.payout_id|escape:'html'}{else}<span class="text-muted">-</span>{/if}</td>
                <td class="text-right">
                  {if $po.wallet_balance_amount !== null}
                    {$po.wallet_balance_amount|floatval|string_format:"%.2f"} {$po.wallet_balance_currency|escape:'html'}
                  {else}
                    <span class="text-muted">-</span>
                  {/if}
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>
        </div>
      </details>
    {/if}
  
</div>
</div>

{* Kontrola wypłat pomiędzy przelewami *}
<div class="card alpro-table-card alpro-payoutcheck-card" id="alproReconPayoutChecks" style="margin-bottom:16px;">
  <div class="card-header">
    <div class="alpro-table-head">
      <strong>Rozliczenie wypłat (okresy między przelewami)</strong>
      {if isset($recon_payouts.checks_meta)}
        <span class="text-muted">
          (zakres: {$recon_payouts.checks_meta.date_from|escape:'html'} → {$recon_payouts.checks_meta.date_to|escape:'html'}
          • wypłat: {$recon_payouts.checks_meta.count|intval}
          • bilans OK: {$recon_payouts.checks_meta.ok|intval}
          • zmiana salda: {$recon_payouts.checks_meta.changed|intval})
        </span>
      {/if}
    </div>
  </div>
  <div class="card-body">

    <div class="alert alert-info alpro-payoutcheck-explain">
      <strong>Jak to czytać?</strong>
      <div style="margin-top:6px;">
        Ten blok służy do szybkiej kontroli wypłat Allegro na Twoje konto bankowe.
        Liczymy operacje portfela <code>AVAILABLE</code> (wg <code>occurredAt</code>) w oknach <strong>pomiędzy kolejnymi wypłatami (PAYOUT)</strong>.
        <div class="small" style="margin-top:6px;">
          <strong>Bilans do wypłaty</strong> = wpłaty klientów (AVAILABLE) − opłaty Allegro (CHARGE) + zwroty opłat + inne operacje (np. zwroty dla kupujących).
          <br>
          <strong>Zmiana salda AVAILABLE</strong> = wypłata − bilans okna.
          Dodatnia oznacza, że wypłata objęła też środki zgromadzone wcześniej (saldo z poprzednich okresów).
          Ujemna oznacza, że część środków została w portfelu po wypłacie.
        </div>
      </div>
    </div>

    {if isset($recon_payouts.checks_meta)}
      <div class="alpro-payoutcheck-kpis">
        <div class="alpro-mini-kpi">
          <div class="alpro-mini-kpi-title">Wypłaty na konto (suma)</div>
          <div class="alpro-mini-kpi-value">{$recon_payouts.checks_meta.sum_payout|floatval|string_format:"%.2f"} PLN</div>
          <div class="alpro-mini-kpi-sub">Operacji PAYOUT w zakresie: {$recon_payouts.checks_meta.count|intval}</div>
        </div>

        <div class="alpro-mini-kpi">
          <div class="alpro-mini-kpi-title">Bilans operacji AVAILABLE</div>
          <div class="alpro-mini-kpi-value">{$recon_payouts.checks_meta.sum_expected_total|floatval|string_format:"%.2f"} PLN</div>
          <div class="alpro-mini-kpi-sub">
            wpłaty−opłaty+zwroty: {$recon_payouts.checks_meta.sum_expected_orders|floatval|string_format:"%.2f"} PLN • inne: {$recon_payouts.checks_meta.sum_other_net|floatval|string_format:"%.2f"} PLN
          </div>
        </div>

        {assign var=sumBal value=$recon_payouts.checks_meta.sum_balance_change|floatval}
        <div class="alpro-mini-kpi">
          <div class="alpro-mini-kpi-title">Zmiana salda AVAILABLE</div>
          <div class="alpro-mini-kpi-value">
            {if $sumBal > 0.005}<span class="text-success">+{$sumBal|string_format:"%.2f"}</span>{elseif $sumBal < -0.005}<span class="text-danger">{$sumBal|string_format:"%.2f"}</span>{else}<span class="text-muted">0.00</span>{/if} PLN
          </div>
          <div class="alpro-mini-kpi-sub">saldo_start − saldo_koniec (dla całego wybranego zakresu)</div>
        </div>
      </div>

      {assign var=_pcCtl value=$recon_payouts.checks_meta.sum_payout|floatval - ($recon_payouts.checks_meta.sum_expected_total|floatval + $recon_payouts.checks_meta.sum_balance_change|floatval)}
      <div class="text-muted" style="font-size:12px; margin-top:6px;">
        Kontrola: <strong>wypłaty</strong> = <strong>bilans</strong> + <strong>zmiana salda</strong> →
        {$recon_payouts.checks_meta.sum_payout|floatval|string_format:"%.2f"} = {$recon_payouts.checks_meta.sum_expected_total|floatval|string_format:"%.2f"} + {$recon_payouts.checks_meta.sum_balance_change|floatval|string_format:"%.2f"}
        (różnica: {$_pcCtl|floatval|string_format:"%.2f"} PLN)
      </div>
    {/if}

    {if !isset($recon_payouts.checks) || $recon_payouts.checks|@count == 0}
      <div class="alert alert-warning" style="margin-top:10px;">
        Brak wypłat PAYOUT do rozliczenia w tym zakresie (albo brak danych w cache <code>payment-operations</code>).
      </div>
    {else}
      <div class="alpro-table-wrap" style="margin-top:10px;">
        <table class="table alpro-table alpro-payoutcheck-table">
          <thead>
            <tr>
              <th>Okno (między wypłatami)</th>
              <th class="text-right">Wpłaty klientów</th>
              <th class="text-right">Opłaty Allegro</th>
              <th class="text-right">Zwroty opłat</th>
              <th class="text-right">Inne operacje</th>
              <th class="text-right">Bilans do wypłaty</th>
              <th class="text-right">Wypłata</th>
              <th class="text-right">Zmiana salda</th>
              <th>Status</th>
              <th class="text-right">Szczegóły</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$recon_payouts.checks item=pc name=pcloop}
              {assign var=pcIdx value=$smarty.foreach.pcloop.index}
              {assign var=otherNet value=$pc.other_net|floatval}
              {assign var=balChange value=$pc.balance_change|floatval}
              <tr>
                <td>
                  <div class="alpro-payoutcheck-period">
                    <div><strong>{$pc.payout_at_local|default:$pc.payout_at|escape:'html'}</strong></div>
                    <div class="text-muted" style="font-size:11px;">od: {$pc.from_local|default:$pc.from|escape:'html'} → do: {$pc.to_local|default:$pc.to|escape:'html'}</div>
                    {if $pc.note|trim!=''}
                      <div class="text-muted" style="font-size:11px; margin-top:4px;">
                        <span class="alpro-note-dot" title="{$pc.note|escape:'htmlall':'UTF-8'}">ⓘ</span>
                        <span class="alpro-note-text">uwaga</span>
                      </div>
                    {/if}
                  </div>
                </td>

                <td class="text-right"><span class="text-success">{$pc.payments_available|floatval|string_format:"%.2f"}</span> {$pc.currency|escape:'html'}</td>
                <td class="text-right"><span class="text-danger">{$pc.fee_deduction|floatval|string_format:"%.2f"}</span> {$pc.currency|escape:'html'}</td>
                <td class="text-right"><span class="text-success">{$pc.fee_refund|floatval|string_format:"%.2f"}</span> {$pc.currency|escape:'html'}</td>

                <td class="text-right">
                  {if $otherNet > 0.005}
                    <span class="text-success">+{$otherNet|string_format:"%.2f"}</span>
                  {elseif $otherNet < -0.005}
                    <span class="text-danger">{$otherNet|string_format:"%.2f"}</span>
                  {else}
                    <span class="text-muted">0.00</span>
                  {/if}
                  {$pc.currency|escape:'html'}
                </td>

                <td class="text-right">
                  <strong>{$pc.expected_total|floatval|string_format:"%.2f"}</strong> {$pc.currency|escape:'html'}
                  <div class="text-muted" style="font-size:11px;">(wpłaty−opłaty+zwroty: {$pc.expected_orders|floatval|string_format:"%.2f"})</div>
                </td>

                <td class="text-right"><strong>{$pc.payout|floatval|string_format:"%.2f"}</strong> {$pc.currency|escape:'html'}</td>

                <td class="text-right">
                  {if $balChange < 0.05 && $balChange > -0.05}
                    <span class="text-muted">0.00</span>
                  {elseif $balChange > 0}
                    <span class="text-success">+{$balChange|string_format:"%.2f"}</span>
                  {else}
                    <span class="text-danger">{$balChange|string_format:"%.2f"}</span>
                  {/if}
                  {$pc.currency|escape:'html'}
                  <div class="text-muted" style="font-size:11px;">saldo_start − saldo_koniec</div>
                </td>

                <td>
                  {if $pc.status_kind=='ok'}
                    <span class="alpro-badge alpro-badge--ok">{$pc.status_label|escape:'html'}</span>
                  {elseif $pc.status_kind=='warn'}
                    <span class="alpro-badge alpro-badge--warn">{$pc.status_label|escape:'html'}</span>
                  {elseif $pc.status_kind=='carry'}
                    <span class="alpro-badge alpro-badge--carry">{$pc.status_label|escape:'html'}</span>
                  {elseif $pc.status_kind=='left'}
                    <span class="alpro-badge alpro-badge--left">{$pc.status_label|escape:'html'}</span>
                  {else}
                    <span class="alpro-badge alpro-badge--neutral">{$pc.status_label|escape:'html'}</span>
                  {/if}
                </td>

                <td class="text-right">
                  <button type="button" class="btn btn-default btn-xs alpro-pc-toggle" data-target="alproPcDetails{$pcIdx}">
                    <i class="material-icons" style="font-size:16px; vertical-align:middle;">unfold_more</i>
                    <span>Rozwiń</span>
                  </button>
                </td>
              </tr>

              {* Details row (collapsed by default) *}
              <tr id="alproPcDetails{$pcIdx}" class="alpro-pc-details-row" style="display:none;">
                <td colspan="10">
                  <div class="alpro-pc-details">
                    <div class="alpro-pc-details-col">
                      <div class="alpro-pc-details-title">Opłaty Allegro</div>
                      <div class="alpro-pc-details-meta">
                        <span class="text-danger"><strong>{$pc.details.fee.total|default:0|floatval|string_format:"%.2f"}</strong> {$pc.currency|escape:'html'}</span>
                        <span class="text-muted">· {$pc.details.fee.count|default:0|intval} operacji</span>
                      </div>

                      {if ($pc.details.fee.by_type|@count) > 0}
                        <div class="alpro-pc-details-subtitle">Struktura (typy)</div>
                        <table class="table table-condensed table-sm alpro-pc-details-mini">
                          <thead>
                            <tr>
                              <th>Typ</th>
                              <th class="text-right">Ilość</th>
                              <th class="text-right">Suma</th>
                            </tr>
                          </thead>
                          <tbody>
                            {foreach from=$pc.details.fee.by_type item=bt}
                              <tr>
                                <td>
                                  {if $bt.label|default:''|trim!=''}
                                    <div><strong>{$bt.label|escape:'html'}</strong></div>
                                    <div class="text-muted" style="font-size:11px; line-height:1.15;"><code>{$bt.key|escape:'html'}</code></div>
                                  {else}
                                    <code>{$bt.key|escape:'html'}</code>
                                  {/if}
                                </td>
                                <td class="text-right">{$bt.count|intval}</td>
                                <td class="text-right">{$bt.sum|floatval|string_format:"%.2f"} {$pc.currency|escape:'html'}</td>
                              </tr>
                            {/foreach}
                          </tbody>
                        </table>
                      {/if}

                      <div class="alpro-pc-details-subtitle">Operacje</div>
                      {if ($pc.details.fee.rows|@count) > 0}
                        <div class="table-responsive">
                          <table class="table table-condensed table-sm alpro-pc-details-table">
                            <thead>
                              <tr>
                                <th>occurredAt</th>
                                <th>Typ</th>
                                <th>payment_id / zamówienie</th>
                                <th>Uczestnik</th>
                                <th class="text-right">Kwota</th>
                              </tr>
                            </thead>
                            <tbody>
                              {foreach from=$pc.details.fee.rows item=o}
                                <tr>
                                  <td>
                                    <code>{$o.occurred_at_local|default:$o.occurred_at|escape:'html'}</code>
                                    {if $o.operation_id|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">id: <code>{$o.operation_id|escape:'html'}</code></div>
                                    {/if}
                                  </td>
                                  <td>
                                    {if $o.type_label|default:''|trim!=''}
                                      <div><strong>{$o.type_label|escape:'html'}</strong></div>
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;"><code>{$o.group|escape:'html'}/{$o.type|escape:'html'}</code></div>
                                    {else}
                                      <code>{$o.group|escape:'html'}/{$o.type|escape:'html'}</code>
                                    {/if}

                                    {if $o.wallet_operator_label|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">
                                        {$o.wallet_operator_label|escape:'html'}
                                        {if $o.wallet_operator|default:''|trim!='' && $o.wallet_operator_label != $o.wallet_operator}
                                          <span class="text-muted">({$o.wallet_operator|escape:'html'})</span>
                                        {/if}
                                      </div>
                                    {elseif $o.wallet_operator|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">{$o.wallet_operator|escape:'html'}</div>
                                    {/if}
                                  </td>
                                  <td>
                                    {if $o.payment_id|default:''|trim!=''}
                                      <code>{$o.payment_id|escape:'html'}</code>
                                    {else}
                                      <span class="text-muted">-</span>
                                    {/if}

                                    {if $o.checkout_form_id|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">CF: <code>{$o.checkout_form_id|escape:'html'}</code></div>
                                    {/if}

                                    {if $o.id_order_prestashop|default:0|intval > 0}
                                      <div style="margin-top:4px;">
                                        <a class="btn btn-default btn-xs" href="{$admin_orders_link|escape:'htmlall':'UTF-8'}&id_order={$o.id_order_prestashop|intval}&vieworder=1" target="_blank" rel="noopener" title="Otwórz zamówienie w PrestaShop">
                                          #{$o.id_order_prestashop|intval}
                                        </a>
                                      </div>
                                    {/if}

                                    {if $o.order_items_preview|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; margin-top:4px; line-height:1.15;">{$o.order_items_preview|escape:'html'}</div>
                                    {/if}
                                  </td>
                                  <td>
                                    {if $o.participant_login|default:''|trim!=''}
                                      <code>{$o.participant_login|escape:'html'}</code>
                                    {else}
                                      <span class="text-muted">-</span>
                                    {/if}
                                  </td>
                                  <td class="text-right text-danger"><strong>{$o.amount|floatval|string_format:"%.2f"}</strong> {$o.currency|escape:'html'}</td>
                                </tr>
                              {/foreach}
                            </tbody>
                          </table>
                        </div>
                      {else}
                        <div class="text-muted">Brak opłat w tym oknie.</div>
                      {/if}

                      <hr class="alpro-pc-details-sep" />

                      <div class="alpro-pc-details-title">Zwroty opłat Allegro</div>
                      <div class="alpro-pc-details-meta">
                        <span class="text-success"><strong>{$pc.details.fee_refund.total|default:0|floatval|string_format:"%.2f"}</strong> {$pc.currency|escape:'html'}</span>
                        <span class="text-muted">· {$pc.details.fee_refund.count|default:0|intval} operacji</span>
                      </div>

                      {if ($pc.details.fee_refund.rows|@count) > 0}
                        <div class="table-responsive">
                          <table class="table table-condensed table-sm alpro-pc-details-table">
                            <thead>
                              <tr>
                                <th>occurredAt</th>
                                <th>Typ</th>
                                <th>payment_id / zamówienie</th>
                                <th>Uczestnik</th>
                                <th class="text-right">Kwota</th>
                              </tr>
                            </thead>
                            <tbody>
                              {foreach from=$pc.details.fee_refund.rows item=o}
                                <tr>
                                  <td>
                                    <code>{$o.occurred_at_local|default:$o.occurred_at|escape:'html'}</code>
                                    {if $o.operation_id|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">id: <code>{$o.operation_id|escape:'html'}</code></div>
                                    {/if}
                                  </td>
                                  <td>
                                    {if $o.type_label|default:''|trim!=''}
                                      <div><strong>{$o.type_label|escape:'html'}</strong></div>
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;"><code>{$o.group|escape:'html'}/{$o.type|escape:'html'}</code></div>
                                    {else}
                                      <code>{$o.group|escape:'html'}/{$o.type|escape:'html'}</code>
                                    {/if}

                                    {if $o.wallet_operator_label|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">
                                        {$o.wallet_operator_label|escape:'html'}
                                        {if $o.wallet_operator|default:''|trim!='' && $o.wallet_operator_label != $o.wallet_operator}
                                          <span class="text-muted">({$o.wallet_operator|escape:'html'})</span>
                                        {/if}
                                      </div>
                                    {elseif $o.wallet_operator|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">{$o.wallet_operator|escape:'html'}</div>
                                    {/if}
                                  </td>
                                  <td>
                                    {if $o.payment_id|default:''|trim!=''}
                                      <code>{$o.payment_id|escape:'html'}</code>
                                    {else}
                                      <span class="text-muted">-</span>
                                    {/if}

                                    {if $o.checkout_form_id|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">CF: <code>{$o.checkout_form_id|escape:'html'}</code></div>
                                    {/if}

                                    {if $o.id_order_prestashop|default:0|intval > 0}
                                      <div style="margin-top:4px;">
                                        <a class="btn btn-default btn-xs" href="{$admin_orders_link|escape:'htmlall':'UTF-8'}&id_order={$o.id_order_prestashop|intval}&vieworder=1" target="_blank" rel="noopener" title="Otwórz zamówienie w PrestaShop">
                                          #{$o.id_order_prestashop|intval}
                                        </a>
                                      </div>
                                    {/if}

                                    {if $o.order_items_preview|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; margin-top:4px; line-height:1.15;">{$o.order_items_preview|escape:'html'}</div>
                                    {/if}
                                  </td>
                                  <td>
                                    {if $o.participant_login|default:''|trim!=''}
                                      <code>{$o.participant_login|escape:'html'}</code>
                                    {else}
                                      <span class="text-muted">-</span>
                                    {/if}
                                  </td>
                                  <td class="text-right text-success"><strong>{$o.amount|floatval|string_format:"%.2f"}</strong> {$o.currency|escape:'html'}</td>
                                </tr>
                              {/foreach}
                            </tbody>
                          </table>
                        </div>
                      {else}
                        <div class="text-muted">Brak zwrotów opłat w tym oknie.</div>
                      {/if}
                    </div>

                    <div class="alpro-pc-details-col">
                      <div class="alpro-pc-details-title">Inne operacje</div>
                      <div class="alpro-pc-details-meta">
                        {assign var=otherTotal value=$pc.details.other.total|default:0|floatval}
                        {if $otherTotal > 0.005}
                          <span class="text-success"><strong>+{$otherTotal|string_format:"%.2f"}</strong> {$pc.currency|escape:'html'}</span>
                        {elseif $otherTotal < -0.005}
                          <span class="text-danger"><strong>{$otherTotal|string_format:"%.2f"}</strong> {$pc.currency|escape:'html'}</span>
                        {else}
                          <span class="text-muted"><strong>0.00</strong> {$pc.currency|escape:'html'}</span>
                        {/if}
                        <span class="text-muted">· {$pc.details.other.count|default:0|intval} operacji</span>
                      </div>

                      {if ($pc.details.other.by_type|@count) > 0}
                        <div class="alpro-pc-details-subtitle">Struktura (typy)</div>
                        <table class="table table-condensed table-sm alpro-pc-details-mini">
                          <thead>
                            <tr>
                              <th>Typ</th>
                              <th class="text-right">Ilość</th>
                              <th class="text-right">Suma (net)</th>
                            </tr>
                          </thead>
                          <tbody>
                            {foreach from=$pc.details.other.by_type item=bt}
                              <tr>
                                <td>
                                  {if $bt.label|default:''|trim!=''}
                                    <div><strong>{$bt.label|escape:'html'}</strong></div>
                                    <div class="text-muted" style="font-size:11px; line-height:1.15;"><code>{$bt.key|escape:'html'}</code></div>
                                  {else}
                                    <code>{$bt.key|escape:'html'}</code>
                                  {/if}
                                </td>
                                <td class="text-right">{$bt.count|intval}</td>
                                <td class="text-right">{$bt.sum|floatval|string_format:"%.2f"} {$pc.currency|escape:'html'}</td>
                              </tr>
                            {/foreach}
                          </tbody>
                        </table>
                      {/if}

                      <div class="alpro-pc-details-subtitle">Operacje</div>
                      {if ($pc.details.other.rows|@count) > 0}
                        <div class="table-responsive">
                          <table class="table table-condensed table-sm alpro-pc-details-table">
                            <thead>
                              <tr>
                                <th>occurredAt</th>
                                <th>Typ</th>
                                <th>payment_id / zamówienie</th>
                                <th>Uczestnik</th>
                                <th class="text-right">Kwota</th>
                              </tr>
                            </thead>
                            <tbody>
                              {foreach from=$pc.details.other.rows item=o}
                                {assign var=a value=$o.amount|default:0|floatval}
                                <tr>
                                  <td>
                                    <code>{$o.occurred_at_local|default:$o.occurred_at|escape:'html'}</code>
                                    {if $o.operation_id|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">id: <code>{$o.operation_id|escape:'html'}</code></div>
                                    {/if}
                                  </td>
                                  <td>
                                    {if $o.type_label|default:''|trim!=''}
                                      <div><strong>{$o.type_label|escape:'html'}</strong></div>
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;"><code>{$o.group|escape:'html'}/{$o.type|escape:'html'}</code></div>
                                    {else}
                                      <code>{$o.group|escape:'html'}/{$o.type|escape:'html'}</code>
                                    {/if}

                                    {if $o.wallet_operator_label|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">
                                        {$o.wallet_operator_label|escape:'html'}
                                        {if $o.wallet_operator|default:''|trim!='' && $o.wallet_operator_label != $o.wallet_operator}
                                          <span class="text-muted">({$o.wallet_operator|escape:'html'})</span>
                                        {/if}
                                      </div>
                                    {elseif $o.wallet_operator|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">{$o.wallet_operator|escape:'html'}</div>
                                    {/if}
                                  </td>
                                  <td>
                                    {if $o.payment_id|default:''|trim!=''}
                                      <code>{$o.payment_id|escape:'html'}</code>
                                    {else}
                                      <span class="text-muted">-</span>
                                    {/if}

                                    {if $o.checkout_form_id|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; line-height:1.15;">CF: <code>{$o.checkout_form_id|escape:'html'}</code></div>
                                    {/if}

                                    {if $o.id_order_prestashop|default:0|intval > 0}
                                      <div style="margin-top:4px;">
                                        <a class="btn btn-default btn-xs" href="{$admin_orders_link|escape:'htmlall':'UTF-8'}&id_order={$o.id_order_prestashop|intval}&vieworder=1" target="_blank" rel="noopener" title="Otwórz zamówienie w PrestaShop">
                                          #{$o.id_order_prestashop|intval}
                                        </a>
                                      </div>
                                    {/if}

                                    {if $o.order_items_preview|default:''|trim!=''}
                                      <div class="text-muted" style="font-size:11px; margin-top:4px; line-height:1.15;">{$o.order_items_preview|escape:'html'}</div>
                                    {/if}
                                  </td>
                                  <td>
                                    {if $o.participant_login|default:''|trim!=''}
                                      <code>{$o.participant_login|escape:'html'}</code>
                                    {else}
                                      <span class="text-muted">-</span>
                                    {/if}
                                  </td>
                                  <td class="text-right {if $a < -0.005}text-danger{elseif $a > 0.005}text-success{else}text-muted{/if}"><strong>{$a|string_format:"%.2f"}</strong> {$o.currency|escape:'html'}</td>
                                </tr>
                              {/foreach}
                            </tbody>
                          </table>
                        </div>
                      {else}
                        <div class="text-muted">Brak innych operacji w tym oknie.</div>
                      {/if}
                    </div>
                  </div>
                </td>
              </tr>
            {/foreach}
          </tbody>
          {if isset($recon_payouts.checks_meta)}
            <tfoot>
              <tr>
                <th>SUMA</th>
                <th class="text-right"><strong>{$recon_payouts.checks_meta.sum_payments|floatval|string_format:"%.2f"}</strong> PLN</th>
                <th class="text-right"><strong>{$recon_payouts.checks_meta.sum_fee_deduction|floatval|string_format:"%.2f"}</strong> PLN</th>
                <th class="text-right"><strong>{$recon_payouts.checks_meta.sum_fee_refund|floatval|string_format:"%.2f"}</strong> PLN</th>
                <th class="text-right"><strong>{$recon_payouts.checks_meta.sum_other_net|floatval|string_format:"%.2f"}</strong> PLN</th>
                <th class="text-right"><strong>{$recon_payouts.checks_meta.sum_expected_total|floatval|string_format:"%.2f"}</strong> PLN</th>
                <th class="text-right"><strong>{$recon_payouts.checks_meta.sum_payout|floatval|string_format:"%.2f"}</strong> PLN</th>
                <th class="text-right">
                  {assign var=sumBal2 value=$recon_payouts.checks_meta.sum_balance_change|floatval}
                  <strong>{if $sumBal2 > 0.005}+{/if}{$sumBal2|string_format:"%.2f"}</strong> PLN
                </th>
                <th></th>
                <th></th>
              </tr>
            </tfoot>
          {/if}
        </table>
      </div>
    {/if}
  </div>
</div>

<details class="alpro-details" id="alproReconOrdersDetails">
  <summary>
    <span><i class="material-icons" style="font-size:18px; vertical-align:middle;">list</i> Szczegóły transakcji (zaawansowane)</span>
    <span class="alpro-note-text">{$recon_api.totalCount|intval} zamówień · {$recon_kpi.issues|default:0|intval} problemów</span>
  </summary>
  <div class="alpro-details-body">

<div class="card alpro-table-card" id="alproReconMainTable">

    <div class="card-header">
      <div class="alpro-table-head">
        <strong>Lista transakcji</strong>
        <span class="text-muted">(sortowanie: data płatności malejąco; na stronie: {$limit|intval})</span>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive alpro-table-wrap">
        <table class="table table-hover table-striped mb-0">
          <thead>
            <tr>
              <th>Data płatności</th>
              <th>checkoutFormId</th>
              <th>Presta</th>
              <th>Klient</th>
              <th>Status zamówienia</th>
              <th style="text-align:right;">Zapłacono</th>
              <th style="text-align:right;" title="Cashflow = W (WAITING) + A (AVAILABLE)">Cashflow</th>
              <th style="text-align:right;" title="Opłaty/pobrane prowizje naliczone przez Allegro">Opłaty</th>
              <th style="text-align:right;" title="Zwroty opłat naliczone przez Allegro">Zwroty opłat</th>
              <th style="text-align:right;">Saldo</th>
              <th class="alpro-col-alert">Status rozliczenia</th>
              <th>payment_id</th>
            </tr>
          </thead>
          <tbody>
            {if empty($recon_rows)}
              <tr><td colspan="12" class="text-muted" style="padding:18px 12px;">Brak danych dla filtrów.</td></tr>
            {else}
              {foreach from=$recon_rows item=r}
                {assign var=cfRowId value='alpro_recon_'|cat:$r.checkout_form_id}

                {assign var=os value=$r.order_status|default:''}
                {assign var=os_label value='Nie pobrano'}
                {assign var=os_class value='alpro-neutral'}
                {if $os=='BOUGHT'}{assign var=os_label value='Zakupione'}{assign var=os_class value='alpro-neutral'}{/if}
                {if $os=='FILLED_IN'}{assign var=os_label value='Uzupełnione'}{assign var=os_class value='alpro-diff'}{/if}
                {if $os=='READY_FOR_PROCESSING'}{assign var=os_label value='Gotowe do realizacji'}{assign var=os_class value='alpro-ok'}{/if}
                {if $os=='PROCESSING'}{assign var=os_label value='W realizacji'}{assign var=os_class value='alpro-waiting'}{/if}
                {if $os=='SENT'}{assign var=os_label value='Wysłane'}{assign var=os_class value='alpro-waiting'}{/if}
                {if $os=='CANCELLED'}{assign var=os_label value='Anulowane'}{assign var=os_class value='alpro-neutral'}{/if}

                {assign var=rc_label value='OK'}
                {assign var=rc_class value='alpro-ok'}
                {assign var=rc_hint value='Rozliczenie wygląda poprawnie.'}
                {if $r.status=='missing_cashflow'}
                  {assign var=rc_label value='Brak cashflow'}
                  {assign var=rc_class value='alpro-missing'}
                  {assign var=rc_hint value='Zapłacono, ale w payment-operations nie widać wpływu do portfeli (WAITING/AVAILABLE) dla tej płatności. Najczęściej oznacza to brak/niepełną synchronizację payment-operations.'}
                {/if}
                {if $r.status=='cashflow_diff'}
                  {assign var=rc_label value='Różnica wpłaty'}
                  {assign var=rc_class value='alpro-diff'}
                  {assign var=rc_hint value='Cashflow (WAITING+AVAILABLE) różni się od kwoty zapłaconej. Sprawdź szczegóły payment_id i operacje w Allegro.'}
                {/if}
                {if $r.status=='missing_refund'}
                  {assign var=rc_label value='Brak zwrotu opłat'}
                  {assign var=rc_class value='alpro-missing'}
                  {assign var=rc_hint value='Zamówienie anulowane: Allegro pobrało opłaty, ale nie widać ich zwrotu w payment-operations.'}
                {/if}

                <tr class="{if $r.issue}alpro-row-issue{if $r.issue_type} alpro-issue-{$r.issue_type|escape:'htmlall':'UTF-8'}{/if}{/if}" data-issue="{if $r.issue}1{else}0{/if}" data-issue-type="{$r.issue_type|default:''|escape:'htmlall':'UTF-8'}">
                  <td>{$r.finished_at|escape:'htmlall':'UTF-8'}</td>
                  <td class="alpro-col-id" title="{$r.checkout_form_id|escape:'htmlall':'UTF-8'}">{$r.checkout_form_id|escape:'htmlall':'UTF-8'}</td>
                  <td>
                    {if $r.id_order_prestashop|intval > 0}
                      <a class="btn btn-default btn-xs" href="{$admin_orders_link|escape:'htmlall':'UTF-8'}&id_order={$r.id_order_prestashop|intval}&vieworder=1" target="_blank" rel="noopener" title="Otwórz zamówienie w PrestaShop">
                        #{$r.id_order_prestashop|intval}
                      </a>
                    {else}
                      <span class="text-muted">-</span>
                    {/if}
                  </td>
                  <td>{$r.buyer_login|escape:'htmlall':'UTF-8'}</td>
                  <td>
                    <span class="alpro-status {$os_class}" title="{$os|escape:'htmlall':'UTF-8'}">{$os_label|escape:'htmlall':'UTF-8'}</span>
                  </td>
                  <td style="text-align:right;">{$r.paid|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                  <td style="text-align:right;">
                    <div>{$r.cashflow_total|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</div>
                    <div class="text-muted" style="font-size:11px; line-height:1.15;" title="W=WAITING (oczekujące), A=AVAILABLE (dostępne)">
                      <span class="alpro-wa">W</span>: {$r.cashflow_waiting|default:0|string_format:"%.2f"}
                      <span class="alpro-sep">·</span>
                      <span class="alpro-wa">A</span>: {$r.cashflow_available|default:0|string_format:"%.2f"}
                      {if ($r.diff_paid_cashflow|default:0) > 0.02 || ($r.diff_paid_cashflow|default:0) < -0.02}
                        <span class="alpro-sep">·</span>
                        <span title="Różnica: Cashflow − Zapłacono">Δ: {$r.diff_paid_cashflow|string_format:"%.2f"}</span>
                      {/if}
                    </div>
                  </td>
                  <td style="text-align:right;"><span class="text-danger">{$r.fee_deduction|string_format:"%.2f"}</span> {$r.currency|escape:'htmlall':'UTF-8'}</td>
                  <td style="text-align:right;"><span class="text-success">{$r.fee_refund|string_format:"%.2f"}</span> {$r.currency|escape:'htmlall':'UTF-8'}</td>
                  <td style="text-align:right;"><strong>{$r.net|string_format:"%.2f"}</strong> {$r.currency|escape:'htmlall':'UTF-8'}</td>
                  <td>
                    <span class="alpro-status {$rc_class}" title="{$rc_hint|escape:'htmlall':'UTF-8'}">{$rc_label|escape:'htmlall':'UTF-8'}</span>
                  </td>
                  <td>
                    {if isset($r.payments) && $r.payments|@count > 0}
                      <button type="button" class="btn btn-link btn-xs alpro-toggle-payments" data-target="#{$cfRowId|escape:'htmlall':'UTF-8'}" style="padding:0;">
                        {$r.payments|@count} × payment_id <i class="material-icons" style="font-size:16px; vertical-align:middle;">expand_more</i>
                      </button>
                    {else}
                      <span class="text-muted">-</span>
                    {/if}
                  </td>
                </tr>

                {if isset($r.payments) && $r.payments|@count > 0}
                  <tr id="{$cfRowId|escape:'htmlall':'UTF-8'}" class="alpro-payments-row" style="display:none;">
                    <td colspan="12" style="background:#fafbfc; border-top:0;">
                      <div class="table-responsive" style="margin:0;">
                        <table class="table table-condensed" style="margin:0; background:#fff;">
                          <thead>
                            <tr>
                              <th>payment_id</th>
                              <th style="text-align:right;">zapłacono</th>
                              <th style="text-align:right;">WAITING</th>
                              <th style="text-align:right;">AVAILABLE</th>
                              <th style="text-align:right;">Opłaty</th>
                              <th style="text-align:right;">Zwroty opłat</th>
                              <th>finished_at</th>
                              <th style="width:180px;">Szczegóły</th>
                            </tr>
                          </thead>
                          <tbody>
                            {foreach from=$r.payments item=p}
                              <tr>
                                {assign var=pidSafe value=$p.payment_id|regex_replace:'/[^A-Za-z0-9]/':'_'}
                                {assign var=opsRowId value="alpro_payops_`$cfRowId`_`$pidSafe`"}
                                <td style="font-family:monospace;">{$p.payment_id|escape:'htmlall':'UTF-8'}</td>
                                <td style="text-align:right;">{$p.expected|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                                <td style="text-align:right;">{$p.contrib_waiting|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                                <td style="text-align:right;">{$p.contrib_available|string_format:"%.2f"} {$r.currency|escape:'htmlall':'UTF-8'}</td>
                                <td style="text-align:right;"><span class="text-danger">{$p.deduction|string_format:"%.2f"}</span> {$r.currency|escape:'htmlall':'UTF-8'}</td>
                                <td style="text-align:right;"><span class="text-success">{$p.refund_charge|string_format:"%.2f"}</span> {$r.currency|escape:'htmlall':'UTF-8'}</td>
                                <td>{$p.finished_at|escape:'htmlall':'UTF-8'}</td>
                                <td>
                                  <a href="#" class="alpro-toggle-payops" data-payment-id="{$p.payment_id|escape:'htmlall':'UTF-8'}" data-target="#{$opsRowId|escape:'htmlall':'UTF-8'}" data-ajax-url="{$ajax_recon_ops_url|escape:'htmlall':'UTF-8'}">Szczegóły operacji</a>
                                </td>
                              </tr>
                              <tr id="{$opsRowId|escape:'htmlall':'UTF-8'}" class="alpro-payops-row" style="display:none;">
                                <td colspan="8" style="background:#fff;">
                                  <div class="alpro-payops-box">
                                    <div class="text-muted" style="font-size:12px;">Ładuję operacje…</div>
                                  </div>
                                </td>
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
    </div>

    <div class="card-footer alpro-table-footer">
      {if $total_pages > 1}
        <nav class="alpro-paging">
          <ul class="pagination" style="margin:0;">
            <li class="{if $page<=1}disabled{/if}"><a href="{$prev_url|escape:'htmlall':'UTF-8'}">&laquo;</a></li>
            <li class="active"><span>{$page|intval} / {$total_pages|intval}</span></li>
            <li class="{if $page>=$total_pages}disabled{/if}"><a href="{$next_url|escape:'htmlall':'UTF-8'}">&raquo;</a></li>
          </ul>
        </nav>
      {else}
        <span class="text-muted">Strona 1 / 1</span>
      {/if}

      <div class="text-muted" style="margin-top:8px; font-size:12px;">
        Cashflow = suma wpływów do portfeli Allegro: W=WAITING (oczekujące) + A=AVAILABLE (dostępne). Opłaty/Zwroty opłat to potrącenia Allegro i ich zwroty (korekty, rabaty, anulowania). Saldo = Cashflow − Opłaty + Zwroty opłat.
      </div>
    </div>
  </div>

  </div>
</details>

</div>

{* Modal postępu synchronizacji (wspólny dla cashflows.js) *}
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
