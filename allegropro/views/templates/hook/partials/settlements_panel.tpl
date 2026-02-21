{* AllegroPro — Zakładka: Rozliczenia Allegro (w szczegółach zamówienia) *}

{assign var=settle value=$allegro_data.settlements|default:null}
{assign var=canSync value=false}
{if $settle && (int)$settle.account_id > 0 && $settle.checkout_form_id|default:'' != ''}
  {assign var=canSync value=true}
{/if}


<style>
{literal}
#apSettlementsTabWrap .ap-spin{animation:apSpin .9s linear infinite;display:inline-block;}
@keyframes apSpin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}
{/literal}
</style>
<div class="pt-3" id="apSettlementsTabWrap">

  {if !$settle}
    <div class="alert alert-info mb-0">Brak danych rozliczeń dla tego zamówienia.</div>
  {else}

    {* Panel akcji (zawsze jeśli mamy konto + checkoutFormId) *}
    {if $canSync}
      <div class="ap-box p-3 mb-3" id="apSettlementsPanel"
           data-account-id="{$settle.account_id|intval}"
           data-checkout-form-id="{$settle.checkout_form_id|escape:'htmlall':'UTF-8'}"
           data-range-narrow-from="{$settle.ranges.narrow_from|escape:'htmlall':'UTF-8'}"
           data-range-narrow-to="{$settle.ranges.narrow_to|escape:'htmlall':'UTF-8'}"
           data-range-wide-from="{$settle.ranges.wide_from|escape:'htmlall':'UTF-8'}"
           data-range-wide-to="{$settle.ranges.wide_to|escape:'htmlall':'UTF-8'}">

      <div class="d-flex align-items-start justify-content-between" style="gap:12px; flex-wrap:wrap;">
        <div>
          <div class="ap-box-title"><i class="material-icons">paid</i> Rozliczenia Allegro</div>
          <div class="ap-help">
            Źródło: baza modułu (billing-entries) • Ręczne pobranie uruchamia synchronizację zakresu dat z Allegro.
          </div>
        </div>

        <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap; justify-content:flex-end;">
          <div class="text-muted" style="font-size:11px; text-align:right;">
            Ostatni wpis billing:<br>
            <strong>{if $settle.last_billing_at}{$settle.last_billing_at|escape:'htmlall':'UTF-8'}{else}-{/if}</strong>
          </div>

          <div>
            <select class="form-control" id="apBillingRange" style="min-width:220px;">
              <option value="narrow">Wąski zakres (od zamówienia -3 dni)</option>
              <option value="wide">Szeroki zakres (ostatnie 180 dni)</option>
            </select>
            <div class="form-text" id="apBillingRangeHint" style="font-size:11px;"></div>
          </div>

          <label class="m-0" style="font-size:12px; user-select:none;">
            <input type="checkbox" id="apBillingForce" />
            Wymuś aktualizację
          </label>

          <button type="button" class="btn btn-primary" id="apBillingSyncBtn">
            <i class="material-icons ap-sync-icon" style="font-size:18px; vertical-align:middle;">refresh</i>
            <span class="ap-sync-text">Pobierz z Allegro</span>
          </button>

          <button type="button" class="btn btn-outline-secondary js-alpro-details"
                  data-checkout="{$settle.checkout_form_id|escape:'htmlall':'UTF-8'}"
                  data-account-id="{$settle.account_id|intval}">
            <i class="material-icons" style="font-size:18px; vertical-align:middle;">insights</i>
            Szczegóły i wykres
          </button>
        </div>
      </div>

      {* Komunikat o braku danych / błędzie budowy raportu (ale z przyciskiem pobrania) *}
      {if empty($settle.ok)}
        <div class="ap-divider"></div>
        <div class="alert alert-info mb-0">
          Brak danych rozliczeń dla tego zamówienia w bazie modułu.
          <div class="mt-2"><small class="text-muted">Kliknij <strong>Pobierz z Allegro</strong>, aby ręcznie zsynchronizować billing-entries.</small></div>
          {if $settle.error}
            <div class="mt-2"><small class="text-muted">{$settle.error|escape:'htmlall':'UTF-8'}</small></div>
          {/if}
          {if $settle.error_debug}
            <div class="mt-1"><small class="text-muted">{$settle.error_debug|escape:'htmlall':'UTF-8'}</small></div>
          {/if}
        </div>

        <div class="mt-2">
          <small class="form-text text-muted" id="apBillingSyncMsg"></small>
        </div>
      {else}

      {assign var=orderRow value=$settle.details.order|default:[]}
      {assign var=paymentRow value=$settle.payment|default:null}

      <div class="ap-divider"></div>

      {* Statusy / szybka diagnoza *}
      <div class="row">
        <div class="col-lg-6 mb-3 mb-lg-0">
          <div class="ap-mini-box">
            <div class="ap-mini-title"><i class="material-icons">assignment_turned_in</i> Status płatności wg Allegro</div>
            <div class="ap-kv">
              <div class="ap-k">Status płatności</div>
              <div class="ap-v">
                {$settle.pay_status_label|escape:'htmlall':'UTF-8'}
                {if $settle.pay_status_raw}
                  <span class="text-muted" style="font-weight:800; font-size:11px;">({$settle.pay_status_raw|escape:'htmlall':'UTF-8'})</span>
                {/if}
              </div>

              <div class="ap-k">Kupujący zapłacił</div>
              <div class="ap-v">
                {if $paymentRow && isset($paymentRow.paid_amount)}
                  {$paymentRow.paid_amount|floatval|number_format:2:',':' '} zł
                  {if $paymentRow.finished_at}
                    <span class="text-muted" style="font-weight:700; font-size:11px;"> • {$paymentRow.finished_at|escape:'htmlall':'UTF-8'}</span>
                  {/if}
                {else}
                  -
                {/if}
              </div>

              {assign var=charged value=$settle.details.fees_charged|default:0|floatval}
              {assign var=chargedAbs value=$charged}
              {if $charged < 0}{assign var=chargedAbs value=0-$charged}{/if}
              {assign var=chargedDisp value=0-$chargedAbs}

              <div class="ap-k">Opłaty naliczone</div>
              <div class="ap-v text-danger" style="font-weight:800;">{$chargedDisp|floatval|number_format:2:',':' '} zł</div>

              {assign var=ref value=$settle.details.fees_refunded|default:0|floatval}
              <div class="ap-k">Zwroty</div>
              <div class="ap-v {if $ref > 0}text-success{else}text-muted{/if}" style="font-weight:800;">{if $ref > 0}+{/if}{$ref|floatval|number_format:2:',':' '} zł</div>
            </div>

            {if (int)$settle.details.refund_expected === 1 && (float)$settle.details.fees_pending > 0}
              <div class="mt-2">
                <span class="badge badge-warning">Do zwrotu: {$settle.details.fees_pending|floatval|number_format:2:',':' '} zł</span>
              </div>
            {/if}
          </div>
        </div>

        <div class="col-lg-6">
          <div class="ap-mini-box">
            <div class="ap-mini-title"><i class="material-icons">fact_check</i> Podsumowanie zamówienia</div>
            <div class="ap-kv">
              <div class="ap-k">Produkty (bez dostawy)</div>
              <div class="ap-v">{$orderRow.sales_amount|default:0|floatval|number_format:2:',':' '} zł</div>

              <div class="ap-k">Dostawa</div>
              <div class="ap-v">{$orderRow.shipping_amount_display|default:$orderRow.shipping_amount|default:0|floatval|number_format:2:',':' '} zł{if (int)$orderRow.shipping_smart_badge === 1} <span style="color:#f0ad4e; font-weight:800;">SMART</span>{/if}</div>

              <div class="ap-k">Suma zamówienia</div>
              <div class="ap-v">{$orderRow.order_total_amount|default:$orderRow.total_amount|default:0|floatval|number_format:2:',':' '} {$orderRow.currency|default:'zł'|escape:'htmlall':'UTF-8'}</div>

              <div class="ap-k">Net po opłatach (bez dostawy)</div>
              <div class="ap-v">{$settle.details.net_after_fees|floatval|number_format:2:',':' '} zł</div>
            </div>
          </div>
        </div>
      </div>

      <div class="ap-divider"></div>

      {* Rozbicie kosztów *}
      <div class="row">
        <div class="col-12">
          <div class="ap-box p-3" style="background:#f8fafc;">
            <div class="ap-box-title"><i class="material-icons">donut_large</i> Struktura opłat (dla tego zamówienia)</div>
            <div class="row">
              <div class="col-md-4 mb-2">Prowizje: <strong>{$settle.details.cats.commission|floatval|number_format:2:',':' '} zł</strong></div>
              <div class="col-md-4 mb-2">SMART: <strong>{$settle.details.cats.smart|floatval|number_format:2:',':' '} zł</strong></div>
              <div class="col-md-4 mb-2">Dostawa / etykiety: <strong>{$settle.details.cats.delivery|floatval|number_format:2:',':' '} zł</strong></div>
              <div class="col-md-4 mb-2">Promocje: <strong>{$settle.details.cats.promotion|floatval|number_format:2:',':' '} zł</strong></div>
              <div class="col-md-4 mb-2">Zwroty / korekty: <strong>{$settle.details.cats.refunds|floatval|number_format:2:',':' '} zł</strong></div>
              <div class="col-md-4 mb-2">Pozostałe: <strong>{$settle.details.cats.other|floatval|number_format:2:',':' '} zł</strong></div>
            </div>
            <div class="mt-2">Suma opłat (saldo): <strong>{$settle.details.cats.total|floatval|number_format:2:',':' '} zł</strong></div>
          </div>
        </div>
      </div>

      <div class="ap-divider"></div>

      {* Tabela operacji billing *}
      <div class="d-flex align-items-center justify-content-between" style="gap:10px; flex-wrap:wrap;">
        <div class="ap-box-title" style="margin:0;"><i class="material-icons">receipt_long</i> Operacje billing dla tego zamówienia</div>
        <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
          <select class="form-control" id="apBillingView" style="min-width:180px;">
            <option value="fees">Opłaty i zwroty</option>
            <option value="all">Wszystkie</option>
          </select>
          <select class="form-control" id="apBillingCat" style="min-width:180px;">
            <option value="">Wszystkie kategorie</option>
            <option value="commission">Prowizje</option>
            <option value="smart">SMART</option>
            <option value="delivery">Dostawa</option>
            <option value="promotion">Promocje</option>
            <option value="refunds">Zwroty/korekty</option>
            <option value="other">Pozostałe</option>
          </select>
        </div>
      </div>

      <div class="table-responsive mt-2">
        <table class="table mb-0" id="apBillingTable">
          <thead>
            <tr>
              <th style="width:170px;">Data</th>
              <th>Typ operacji</th>
              <th style="width:150px;">Kategoria</th>
              <th style="width:140px; text-align:right;">Kwota</th>
              <th style="width:160px;">VAT / adnotacja</th>
            </tr>
          </thead>
          <tbody>
            {if empty($settle.details.items)}
              <tr><td colspan="5" class="text-muted">Brak operacji billing przypiętych do tego zamówienia w bazie modułu.</td></tr>
            {else}
              {foreach from=$settle.details.items item=it}
                {assign var=amt value=$it.value_amount|floatval}
                <tr data-cat="{$it.category|default:''|escape:'htmlall':'UTF-8'}" data-amt="{$amt}">
                  <td>{$it.occurred_at|default:'-'|escape:'htmlall':'UTF-8'}</td>
                  <td>
                    <strong>{$it.type_name|default:'-'|escape:'htmlall':'UTF-8'}</strong>
                    {if $it.type_id}
                      <div class="text-muted" style="font-size:11px;">{$it.type_id|escape:'htmlall':'UTF-8'}</div>
                    {/if}
                  </td>
                  <td>{$it.category|default:'-'|escape:'htmlall':'UTF-8'}</td>
                  <td style="text-align:right;" class="{if $amt < 0}text-danger{elseif $amt > 0}text-success{/if}">
                    {$amt|number_format:2:',':' '} zł
                  </td>
                  <td>
                    {assign var=tp value=""}
                    {assign var=ta value=""}
                    {if isset($it.tax_percentage) && $it.tax_percentage !== ""}
                      {assign var=tp value=$it.tax_percentage}
                    {/if}
                    {if isset($it.tax_annotation) && $it.tax_annotation}
                      {assign var=ta value=$it.tax_annotation}
                    {/if}

                    {if $tp !== "" || $ta !== ""}
                      {if $tp !== ""}{$tp|escape:'htmlall':'UTF-8'}%{/if}
                      {if $ta !== ""}
                        <div class="text-muted" style="font-size:11px;">{$ta|escape:'htmlall':'UTF-8'}</div>
                      {/if}
                    {else}
                      <span class="text-muted">-</span>
                    {/if}
                  </td>
                </tr>
              {/foreach}
            {/if}
          </tbody>
        </table>
      </div>

      <div class="mt-2">
        <small class="form-text text-muted" id="apBillingSyncMsg"></small>
      </div>
    </div>
      {/if}


      {* Modal szczegółów (wykres) — bez przechodzenia do zakładki Rozliczenia *}
      <div id="alpro-settlements"
           data-ajax-url="index.php?controller=AdminAllegroProSettlements&token={getAdminToken tab='AdminAllegroProSettlements'}"
           data-date-from="{$settle.ranges.wide_from|escape:'htmlall':'UTF-8'}"
           data-date-to="{$settle.ranges.wide_to|escape:'htmlall':'UTF-8'}"
           data-mode="orders"></div>

      <div class="alpro-modal" id="alproModal" aria-hidden="true">
        <div class="alpro-modal__backdrop" data-alpro-close="1"></div>
        <div class="alpro-modal__dialog" role="dialog" aria-modal="true" aria-label="Szczegóły zamówienia">
          <div class="alpro-modal__head">
            <div class="alpro-modal__title">
              <strong>Szczegóły zamówienia</strong>
              <span class="alpro-modal__meta" id="alproModalMeta"></span>
            </div>
            <button type="button" class="alpro-modal__close" title="Zamknij" data-alpro-close="1">×</button>
          </div>

          <div class="alpro-modal__body">
            <div class="alpro-modal__loading" id="alproModalLoading">
              <div class="alpro-spinner"></div>
              <div>Ładowanie danych…</div>
            </div>
            <div class="alpro-modal__content" id="alproModalContent" style="display:none;"></div>
          </div>
        </div>
      </div>


    {else}
      <div class="alert alert-info mb-0">
        Brak powiązania z Allegro (konto lub checkoutFormId) — nie można pobrać rozliczeń.
        {if $settle.error}
          <div class="mt-2"><small class="text-muted">{$settle.error|escape:'htmlall':'UTF-8'}</small></div>
        {/if}
      </div>
    {/if}

  {/if}
</div>
