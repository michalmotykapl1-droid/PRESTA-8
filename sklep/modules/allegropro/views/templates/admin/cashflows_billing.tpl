{* AllegroPro - Opłaty (BILLING) (per order_id/checkoutFormId) *}

{assign var=active_alert_label value=''}
{if $alert=='issues'}{assign var=active_alert_label value='Tylko problemy'}{/if}
{if $alert=='unpaid_fees'}{assign var=active_alert_label value='Nieopłacone + opłaty'}{/if}
{if $alert=='no_refund'}{assign var=active_alert_label value='Brak zwrotu opłat'}{/if}
{if $alert=='partial_refund'}{assign var=active_alert_label value='Częściowy zwrot opłat'}{/if}
{if $alert=='api_error'}{assign var=active_alert_label value='Błąd API'}{/if}

{assign var=has_issues value=($billing_kpi.issues|intval > 0)}

<div class="alpro-page alpro-billing" data-details-url="{$ajax_billing_details_url|escape:'htmlall':'UTF-8'}">

  {* Header / nawigacja widoków *}
  <div class="card alpro-header-card mb-3">
    <div class="card-body">
      <div class="alpro-header">
        <div class="alpro-header-left">
          <div class="alpro-header-title">
            <i class="material-icons">swap_horiz</i>
            <span class="alpro-badge" title="Ten widok jest oparty o billing-entries (cache allegropro_billing_entry). Daty dotyczą occurredAt.">
              Opłaty (BILLING)
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
            Źródło: <code>billing-entries</code> (occurredAt). Grupowanie per <code>order_id</code> (checkoutFormId). Moduł wyciąga problemy na górę.
          </div>
        </div>

        <div class="alpro-header-right">
          <div class="btn-group" role="group" aria-label="Widoki">
            <a class="btn btn-default" href="{$view_tx_url|escape:'htmlall':'UTF-8'}"><i class="material-icons">payments</i> Wpłaty</a>
            <a class="btn btn-default" href="{$view_recon_url|escape:'htmlall':'UTF-8'}"><i class="material-icons">receipt_long</i> Rozliczenie</a>
            <a class="btn btn-default active" href="{$view_billing_url|escape:'htmlall':'UTF-8'}"><i class="material-icons">attach_money</i> Opłaty</a>
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
        {if $active_alert_label != ''}
          <div class="alpro-active-filter" title="Aktywny filtr alertów">
            <i class="material-icons">filter_alt</i>
            Filtr: <strong>{$active_alert_label|escape:'htmlall':'UTF-8'}</strong>
            <a class="alpro-clear" href="{$billing_filter_urls.all|escape:'htmlall':'UTF-8'}" title="Wyczyść filtr alertów">×</a>
          </div>
        {/if}
      </div>
    </div>

    <div class="card-body">
      <form method="get" action="{$base_url|escape:'htmlall':'UTF-8'}" class="alpro-filters-form">
        <input type="hidden" name="controller" value="AdminAllegroProCashflows">
        <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="view" value="billing">

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
              <label>Od (occurredAt)</label>
              <input class="form-control" type="date" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}">
            </div>
          </div>

          <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="form-group">
              <label>Do (occurredAt)</label>
              <input class="form-control" type="date" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}">
            </div>
          </div>

          <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="form-group">
              <label>Alerty</label>
              <select class="form-control" name="alert">
                <option value="" {if $alert==''}selected{/if}>Wszystko</option>
                <option value="issues" {if $alert=='issues'}selected{/if}>Tylko problemy</option>
                <option value="unpaid_fees" {if $alert=='unpaid_fees'}selected{/if}>Nieopłacone + opłaty</option>
                <option value="no_refund" {if $alert=='no_refund'}selected{/if}>Brak zwrotu opłat</option>
                <option value="partial_refund" {if $alert=='partial_refund'}selected{/if}>Częściowy zwrot opłat</option>
                <option value="api_error" {if $alert=='api_error'}selected{/if}>Błąd API</option>
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
          <button class="btn btn-primary" type="submit"><i class="material-icons">filter_alt</i> Pokaż</button>
          <button type="button" class="btn btn-default alpro-sync-btn" data-ajax-url="{$ajax_sync_url|escape:'htmlall':'UTF-8'}" data-after-enrich="1" data-enrich-count-url="{$enrich_missing_count_url|escape:'htmlall':'UTF-8'}" data-enrich-step-url="{$enrich_missing_step_url|escape:'htmlall':'UTF-8'}" title="Pobierz billing-entries z Allegro (partiami) i zapisz w cache. Po synchronizacji moduł spróbuje też uzupełnić brakujące dane zamówień (buyer/status/płatność) z Allegro.">
            <i class="material-icons">sync</i> Synchronizuj
          </button>
          {if $enrich_missing_step_url != ''}
            <button type="button"
                    class="btn btn-default alpro-enrich-btn"
                    data-enrich-count-url="{$enrich_missing_count_url|escape:'htmlall':'UTF-8'}"
                    data-enrich-step-url="{$enrich_missing_step_url|escape:'htmlall':'UTF-8'}"
                    title="Uzupełnij brakujące dane zamówień z Allegro (buyer/status/płatność) dla pozycji, które mają status „Nie pobrano / Brak danych”.">
              <i class="material-icons">auto_fix_high</i> Uzupełnij dane zamówień
            </button>
          {/if}
          <a class="btn btn-default" href="{$export_billing_url|escape:'htmlall':'UTF-8'}" title="Eksport CSV: audyt opłat/zwrotów (wg filtrów).">
            <i class="material-icons">download</i> CSV
          </a>
          {if $alert!=''}
            <a class="btn btn-default" href="{$billing_filter_urls.all|escape:'htmlall':'UTF-8'}" title="Wyczyść filtr alertów i pokaż całą listę">
              <i class="material-icons">list</i> Pokaż wszystko
            </a>
          {/if}
        </div>

        {if $enrich_missing_step_url != ''}
          <div class="text-muted" style="margin-top:6px;">
            Widzisz status <strong>„Nie pobrano”</strong> lub <strong>„Brak danych”</strong>? Kliknij <strong>Uzupełnij dane zamówień</strong>, aby pobrać brakujące szczegóły z Allegro.
          </div>
        {/if}

      </form>

      <details class="alpro-details" style="margin-top:10px;">
        <summary><strong>Co oznaczają alerty?</strong></summary>
        <div class="alpro-help">
          <ul>
            <li><strong>Nieopłacone + opłaty</strong> – zamówienie jest nieopłacone, a Allegro naliczyło opłaty (billing-entries ujemne).</li>
            <li><strong>Brak zwrotu opłat</strong> – są naliczone opłaty (np. prowizja), ale brak zwrotu opłat. Dotyczy zamówień anulowanych oraz przypadków, gdy Allegro API nie zwraca już szczegółów zamówienia (np. 404 / brak zamówienia w API).</li>
            <li><strong>Częściowy zwrot opłat</strong> – zwrot opłat jest mniejszy niż naliczone opłaty (również gdy Allegro API nie zwraca szczegółów zamówienia).</li>
            <li><strong>Błąd API</strong> – błąd techniczny po stronie Allegro API podczas pobierania danych (np. limit 429, błąd 500, brak autoryzacji). Najedź na etykietę w tabeli, żeby zobaczyć szczegóły.</li>
          </ul>
        </div>
      </details>

      {if $billing_api.error && !$billing_api.ok}
        <div class="alert alert-warning" style="margin-top:10px;">
          {$billing_api.error|escape:'htmlall':'UTF-8'}
        </div>
      {/if}

      {if $billing_cache_count|intval > 0 && $billing_kpi.orders_count|intval == 0}
        <div class="alert alert-info" style="margin-top:10px;">
          W cache są wpisy billing-entries w tym zakresie (<strong>{$billing_cache_count|intval}</strong>),
          ale nie mają przypisanego <code>order_id</code> (checkoutFormId), więc nie da się ich zgrupować.
          Kliknij <strong>Synchronizuj</strong> (naprawia mapowanie przez <code>payment_id</code>) albo uruchom synchronizację zamówień.
        </div>
      {/if}

    </div>
  </div>

  {* KPI / podsumowanie *}
  <div class="row alpro-kpi-row">
    <div class="col-lg-4 col-md-6">
      <div class="card alpro-kpi-card">
        <div class="alpro-kpi-top">
          <div class="alpro-kpi-number">{$billing_kpi.orders_count|intval}</div>
          <div class="alpro-kpi-label">Zamówień w okresie</div>
        </div>
        <div class="text-muted alpro-kpi-sub">Źródło: billing-entries (order_id)</div>
      </div>
    </div>

    <div class="col-lg-4 col-md-6">
      <div class="card alpro-kpi-card">
        <div class="alpro-kpi-top">
          <div class="alpro-kpi-number">{$billing_kpi.fees_abs|string_format:"%.2f"} PLN</div>
          <div class="alpro-kpi-label">Opłaty (ujemne)</div>
        </div>
        <div class="alpro-kpi-sub"><strong title="Zwroty opłat = kwoty dodatnie w billing-entries (Allegro oddało opłaty/prowizje na Twoje saldo). To nie są zwroty dla klienta.">Zwroty opłat (od Allegro):</strong> {$billing_kpi.refunds|string_format:"%.2f"} PLN</div>
      </div>
    </div>

    <div class="col-lg-4 col-md-12">
      <div class="card alpro-kpi-card {if $has_issues}alpro-kpi-issues{else}alpro-kpi-ok{/if}">
        <div class="alpro-kpi-top">
          <div class="alpro-kpi-number">
            {if $billing_filter_urls.issues}
              <a href="{$billing_filter_urls.issues|escape:'htmlall':'UTF-8'}" title="Pokaż tylko problemy">{$billing_kpi.issues|intval}</a>
            {else}
              {$billing_kpi.issues|intval}
            {/if}
          </div>
          <div class="alpro-kpi-label">Problemów</div>
        </div>
        <div class="alpro-kpi-sub">
          <span class="alpro-kpi-mini"><strong>Nieopłacone + opłaty:</strong>
            {if $billing_filter_urls.unpaid_fees}
              <a href="{$billing_filter_urls.unpaid_fees|escape:'htmlall':'UTF-8'}">{$billing_kpi.issues_unpaid|intval}</a>
            {else}
              {$billing_kpi.issues_unpaid|intval}
            {/if}
          </span>
          <span class="alpro-kpi-mini"><strong>Brak zwrotu:</strong>
            {if $billing_filter_urls.no_refund}
              <a href="{$billing_filter_urls.no_refund|escape:'htmlall':'UTF-8'}">{$billing_kpi.issues_no_refund|intval}</a>
            {else}
              {$billing_kpi.issues_no_refund|intval}
            {/if}
          </span>
          {if $billing_kpi.issues_api_error|intval > 0}
            <span class="alpro-kpi-mini"><strong>Błąd API:</strong>
              {if $billing_filter_urls.api_error}
                <a href="{$billing_filter_urls.api_error|escape:'htmlall':'UTF-8'}">{$billing_kpi.issues_api_error|intval}</a>
              {else}
                {$billing_kpi.issues_api_error|intval}
              {/if}
            </span>
          {/if}
        </div>
      </div>
    </div>
  </div>

  {* Sekcja problemów (szybki podgląd) *}
  {if $billing_api.ok && $billing_kpi.orders_count|intval > 0}
    {if $billing_kpi.issues|intval > 0}
      <div class="card alpro-issues-card mb-3">
        <div class="card-body">
          <div class="alpro-issues-head">
            <div>
              <div class="alpro-issues-title">
                <i class="material-icons">warning</i>
                Wykryto <strong>{$billing_kpi.issues|intval}</strong> problemów w wybranym zakresie
              </div>
              <div class="text-muted">Kliknij w kategorię, żeby od razu zawęzić listę do tego problemu.</div>
            </div>
            <div class="alpro-issues-actions">
              {if $alert!=''}
                <a class="alpro-chip" href="{$billing_filter_urls.all|escape:'htmlall':'UTF-8'}" title="Wyczyść filtr alertów i pokaż całą listę">
                  <i class="material-icons">list</i> Pokaż wszystko
                </a>
              {/if}
              {if $export_billing_issues_url}
                <a class="alpro-chip" href="{$export_billing_issues_url|escape:'htmlall':'UTF-8'}" title="Eksport CSV tylko problemów">
                  <i class="material-icons">download</i> CSV problemy
                </a>
              {/if}
            </div>
          </div>

          <div class="alpro-chip-row">
            {if $billing_filter_urls.issues}
              <a class="alpro-chip alpro-chip-danger" href="{$billing_filter_urls.issues|escape:'htmlall':'UTF-8'}"><i class="material-icons">report</i> Tylko problemy</a>
            {/if}
            {if $billing_filter_urls.unpaid_fees && $billing_kpi.issues_unpaid|intval > 0}
              <a class="alpro-chip" href="{$billing_filter_urls.unpaid_fees|escape:'htmlall':'UTF-8'}">Nieopłacone + opłaty <span class="alpro-chip-count">{$billing_kpi.issues_unpaid|intval}</span></a>
            {/if}
            {if $billing_filter_urls.no_refund && $billing_kpi.issues_no_refund|intval > 0}
              <a class="alpro-chip" href="{$billing_filter_urls.no_refund|escape:'htmlall':'UTF-8'}">Brak zwrotu <span class="alpro-chip-count">{$billing_kpi.issues_no_refund|intval}</span></a>
            {/if}
            {if $billing_filter_urls.partial_refund && $billing_kpi.issues_partial_refund|intval > 0}
              <a class="alpro-chip" href="{$billing_filter_urls.partial_refund|escape:'htmlall':'UTF-8'}">Częściowy zwrot <span class="alpro-chip-count">{$billing_kpi.issues_partial_refund|intval}</span></a>
            {/if}
            {if $billing_filter_urls.api_error && $billing_kpi.issues_api_error|intval > 0}
              <a class="alpro-chip" href="{$billing_filter_urls.api_error|escape:'htmlall':'UTF-8'}">Błąd API <span class="alpro-chip-count">{$billing_kpi.issues_api_error|intval}</span></a>
            {/if}
          </div>
        </div>
      </div>
    {else}
      <div class="card mb-3">
        <div class="card-body">
          <div class="alert alert-success" style="margin:0;">Brak wykrytych problemów w wybranym zakresie.</div>
        </div>
      </div>
    {/if}

    {if !empty($billing_issue_rows) && $billing_kpi.orders_count|intval > 0}
      <div class="card alpro-issues-list mb-3">
        <div class="card-header">
          <div class="alpro-issues-preview-head">
            <div>
              <strong>Podgląd: najnowsze problemy</strong>
              <span class="text-muted">(pokazuję do {$billing_issue_limit|intval}; teraz: {$billing_issue_rows|@count} z {$billing_kpi.issues|intval})</span>
            </div>
            <div>
              {if $billing_filter_urls.issues}
                <a class="btn btn-default btn-xs" href="{$billing_filter_urls.issues|escape:'htmlall':'UTF-8'}" title="Pokaż pełną listę problemów">
                  <i class="material-icons">report</i> Pokaż wszystkie
                </a>
              {/if}
            </div>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive alpro-table-wrap">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Data</th>
                  <th>checkoutFormId</th>
                  <th>Presta</th>
                  <th>Klient</th>
                  <th>Status (Allegro)</th>
                  <th style="text-align:right;">Opłaty</th>
                  <th style="text-align:right;" title="Zwroty opłat = kwoty dodatnie w billing-entries (Allegro oddało opłaty/prowizje na Twoje saldo). To nie są zwroty dla klienta.">Zwroty opłat</th>
                  <th style="text-align:right;" title="Saldo opłat = Zwroty opłat – Opłaty (wg billing-entries).">Saldo</th>
                  <th class="alpro-col-alert">Problem</th>
                </tr>
              </thead>
              <tbody>
                {foreach from=$billing_issue_rows item=ir}
                  <tr class="alpro-row-issue {if $ir.alert_code}alpro-issue-{$ir.alert_code|escape:'htmlall':'UTF-8'}{/if}">
                    <td>{$ir.last_occurred_at|escape:'htmlall':'UTF-8'}</td>
                    <td class="alpro-col-id" title="{$ir.checkout_form_id|escape:'htmlall':'UTF-8'}">{$ir.checkout_form_id|escape:'htmlall':'UTF-8'}</td>
                    <td>
                      {if $ir.id_order_prestashop|intval > 0}
                        <a class="btn btn-default btn-xs" href="{$admin_orders_link|escape:'htmlall':'UTF-8'}&id_order={$ir.id_order_prestashop|intval}&vieworder=1" target="_blank" rel="noopener">#{$ir.id_order_prestashop|intval}</a>
                      {else}
                        <span class="text-muted">-</span>
                      {/if}
                    </td>
                    <td>{$ir.buyer_login|escape:'htmlall':'UTF-8'}</td>
                    <td><span class="alpro-status {$ir.order_status_class|default:'alpro-neutral'|escape:'htmlall':'UTF-8'}" title="{$ir.order_status_hint|escape:'htmlall':'UTF-8'}">{$ir.order_status_label|default:'Brak danych'|escape:'htmlall':'UTF-8'}</span></td>
                    <td style="text-align:right;">{$ir.fees_abs|string_format:"%.2f"} PLN</td>
                    <td style="text-align:right;">{$ir.refunds_pos|string_format:"%.2f"} PLN</td>
                    <td style="text-align:right;">{$ir.net|string_format:"%.2f"} PLN</td>
                    <td class="alpro-col-alert">
                      {assign var=iclass value='alpro-diff'}
                      {if $ir.alert_code=='unpaid_fees' || $ir.alert_code=='no_refund'}{assign var=iclass value='alpro-missing'}{/if}
                      {if $ir.alert_code=='api_error'}{assign var=iclass value='alpro-missing'}{/if}
                      <span class="alpro-status {$iclass}" {if $ir.err_msg || $ir.alert_hint}title="{if $ir.err_msg}{$ir.err_msg|escape:'htmlall':'UTF-8'}{else}{$ir.alert_hint|escape:'htmlall':'UTF-8'}{/if}"{/if}>{$ir.alert_label|escape:'htmlall':'UTF-8'}</span>
                      {if $ir.checkout_form_id}
                        <div class="alpro-details-action">
                          <button type="button"
                                  class="btn btn-link btn-xs alpro-details-link alpro-billing-details-toggle"
                                  data-order-id="{$ir.checkout_form_id|escape:'htmlall':'UTF-8'}"
                                  title="Pokaż szczegóły naliczonych opłat i zwrotów opłat z Allegro">
                            <i class="material-icons">expand_more</i> Szczegóły opłat
                          </button>
                        </div>
                      {/if}
                    </td>
                  </tr>
                {/foreach}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    {/if}
  {/if}

  {* Główna tabela *}
  <div class="card alpro-table-card">
    <div class="card-header">
      <div class="alpro-table-head">
        <strong>Lista zamówień</strong>
        <span class="text-muted">(sortowanie: problemy na górze, potem data)</span>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive alpro-table-wrap">
        <table class="table table-hover table-striped mb-0">
          <thead>
            <tr>
              <th>Data</th>
              <th>checkoutFormId</th>
              <th>Presta</th>
              <th>Klient</th>
              <th>Status (Allegro)</th>
              <th style="text-align:right;">Zapłacono</th>
              <th style="text-align:right;">Opłaty</th>
              <th style="text-align:right;" title="Zwroty opłat = kwoty dodatnie w billing-entries (Allegro oddało opłaty/prowizje na Twoje saldo). To nie są zwroty dla klienta.">Zwroty opłat</th>
              <th style="text-align:right;" title="Saldo opłat = Zwroty opłat – Opłaty (wg billing-entries).">Saldo</th>
              <th class="alpro-col-alert">Problem</th>
              <th style="text-align:right;">Wpisów</th>
            </tr>
          </thead>
          <tbody>
            {if empty($billing_rows)}
              <tr><td colspan="11" class="text-muted" style="padding:18px 12px;">Brak danych dla filtrów.</td></tr>
            {else}
              {foreach from=$billing_rows item=r}
                <tr class="{if $r.alert_code}alpro-row-issue alpro-issue-{$r.alert_code|escape:'htmlall':'UTF-8'}{/if}">
                  <td>{$r.last_occurred_at|escape:'htmlall':'UTF-8'}</td>
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
                  <td><span class="alpro-status {$r.order_status_class|default:'alpro-neutral'|escape:'htmlall':'UTF-8'}" title="{$r.order_status_hint|escape:'htmlall':'UTF-8'}">{$r.order_status_label|default:'Brak danych'|escape:'htmlall':'UTF-8'}</span></td>
                  <td style="text-align:right;">{$r.paid_amount|string_format:"%.2f"} PLN</td>
                  <td style="text-align:right;">{$r.fees_abs|string_format:"%.2f"} PLN</td>
                  <td style="text-align:right;">{$r.refunds_pos|string_format:"%.2f"} PLN</td>
                  <td style="text-align:right;">{$r.net|string_format:"%.2f"} PLN</td>
                  <td class="alpro-col-alert">
                    {if $r.alert_label}
                      {assign var=aclass value='alpro-diff'}
                      {if $r.alert_code=='unpaid_fees' || $r.alert_code=='no_refund'}{assign var=aclass value='alpro-missing'}{/if}
                      {if $r.alert_code=='api_error'}{assign var=aclass value='alpro-missing'}{/if}
                      <span class="alpro-status {$aclass}" {if $r.err_msg || $r.alert_hint}title="{if $r.err_msg}{$r.err_msg|escape:'htmlall':'UTF-8'}{else}{$r.alert_hint|escape:'htmlall':'UTF-8'}{/if}"{/if}>{$r.alert_label|escape:'htmlall':'UTF-8'}</span>
                      {if $r.checkout_form_id}
                        <div class="alpro-details-action">
                          <button type="button"
                                  class="btn btn-link btn-xs alpro-details-link alpro-billing-details-toggle"
                                  data-order-id="{$r.checkout_form_id|escape:'htmlall':'UTF-8'}"
                                  title="Pokaż szczegóły naliczonych opłat i zwrotów opłat z Allegro">
                            <i class="material-icons">expand_more</i> Szczegóły opłat
                          </button>
                        </div>
                      {/if}
                    {else}
                      <span class="text-muted">-</span>
                    {/if}
                  </td>
                  <td style="text-align:right;">{$r.billing_rows|intval}</td>
                </tr>
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
            <li class="{if $page<=1}disabled{/if}">
              <a href="{$prev_url|escape:'htmlall':'UTF-8'}">&laquo;</a>
            </li>
            <li class="active"><span>{$page|intval} / {$total_pages|intval}</span></li>
            <li class="{if $page>=$total_pages}disabled{/if}">
              <a href="{$next_url|escape:'htmlall':'UTF-8'}">&raquo;</a>
            </li>
          </ul>
        </nav>
      {else}
        <span class="text-muted">Strona 1 / 1</span>
      {/if}

      <div class="text-muted" style="margin-top:8px; font-size:12px;">
        Opłaty / zwroty opłat liczone są po znaku kwoty (ujemne = opłaty pobrane przez Allegro, dodatnie = zwroty opłat – Allegro oddało). Wpisy grupowane per <code>order_id</code>.
      </div>
    </div>
  </div>

</div>

{* Modal postępu synchronizacji (wspólny dla cashflows.js) *}
<div class="modal fade" id="alproSyncModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><i class="material-icons" style="vertical-align:middle;">sync</i> Synchronizacja billing-entries</h4>
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
