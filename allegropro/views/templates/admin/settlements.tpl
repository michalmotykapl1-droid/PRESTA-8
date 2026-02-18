{* AllegroPro - Rozliczenia (Billing Entries) *}

<div class="alpro-page">

  <div class="card mb-3">
    <div class="card-header">
      <div class="alpro-head">
        <div class="title">
          <i class="material-icons">paid</i>
          <strong>Rozliczenia</strong>

          {if isset($selected_account_label) && $selected_account_label}
            <span class="alpro-badge" title="Wybrane konto">Konto: <strong>{$selected_account_label|escape:'htmlall':'UTF-8'}</strong></span>
          {/if}

          {if isset($summary.unassigned_count) && $summary.unassigned_count > 0}
            <span class="badge badge-warning">Nieprzypisane: {$summary.unassigned_count|intval}</span>
          {/if}
        </div>

        <div class="meta">
          <span>Operacje billing: <strong>{$billing_count|intval}</strong></span>
          {if isset($orders_total)}
            <span>• Zamówienia: <strong>{$orders_total|intval}</strong></span>
          {/if}
        </div>
      </div>
    </div>

    <div class="card-body">

      <div class="alpro-filters">
        <div class="filters-left">
          <form method="get" action="{$settlements_link|escape:'htmlall':'UTF-8'}" class="m-0">
            <input type="hidden" name="page" value="1" />

            <div class="alpro-filter-row">
              <div class="form-group" style="min-width:260px;">
                <label class="form-control-label">Konto</label>
                <select name="id_allegropro_account" class="form-control">
                  {foreach from=$accounts item=a}
                    <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account == $selected_account_id}selected{/if}>
                      {$a.label|escape:'htmlall':'UTF-8'}{if $a.allegro_login} ({$a.allegro_login|escape:'htmlall':'UTF-8'}){/if}
                    </option>
                  {/foreach}
                </select>
              </div>

              <div class="form-group" style="min-width:160px;">
                <label class="form-control-label">Od</label>
                <input type="date" class="form-control" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}" />
              </div>

              <div class="form-group" style="min-width:160px;">
                <label class="form-control-label">Do</label>
                <input type="date" class="form-control" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}" />
              </div>

              <div class="form-group" style="min-width:260px;">
                <label class="form-control-label">Szukaj (ID / login)</label>
                <input type="text" class="form-control" name="q" value="{$q|escape:'htmlall':'UTF-8'}" placeholder="np. 23187951... lub login" />
              </div>

              <div class="form-group" style="min-width:150px;">
                <label class="form-control-label">Na stronę</label>
                <select name="per_page" class="form-control">
                  <option value="25" {if $per_page==25}selected{/if}>25</option>
                  <option value="50" {if $per_page==50}selected{/if}>50</option>
                  <option value="100" {if $per_page==100}selected{/if}>100</option>
                  <option value="200" {if $per_page==200}selected{/if}>200</option>
                </select>
              </div>

              <div class="form-group">
                <button type="submit" class="btn btn-outline-secondary">
                  <i class="material-icons" style="font-size:18px; vertical-align:middle;">tune</i>
                  <span style="vertical-align:middle;">Filtruj</span>
                </button>
              </div>
            </div>
          </form>
        </div>

        <div class="filters-right">
          <a class="btn btn-outline-secondary" href="{$settlements_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$selected_account_id|intval}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}&q={$q|escape:'url'}&page={$page|intval}&per_page={$per_page|intval}">
            <i class="material-icons" style="font-size:18px; vertical-align:middle;">refresh</i>
            <span style="vertical-align:middle;">Odśwież</span>
          </a>

          <form method="post" action="{$current_index|escape:'htmlall':'UTF-8'}&token={$token|escape:'htmlall':'UTF-8'}" class="m-0">
            <input type="hidden" name="id_allegropro_account" value="{$selected_account_id|intval}" />
            <input type="hidden" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="q" value="{$q|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="page" value="{$page|intval}" />
            <input type="hidden" name="per_page" value="{$per_page|intval}" />

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
          <summary style="cursor:pointer; color:#6c757d;">Pokaż log synchronizacji (debug)</summary>
          <pre class="mt-2" style="max-height:240px; overflow:auto; background:#f6f8fa; padding:10px; border-radius:12px; border:1px solid rgba(0,0,0,.06);">{foreach from=$sync_debug item=l}{$l|escape:'htmlall':'UTF-8'}
{/foreach}</pre>
        </details>
      {/if}

      {* KPI *}
      {if isset($summary.sales_total)}
        {assign var=fees_other value=($fee_shares.fees_other_amount|default:0)}

        <div class="mt-4 alpro-kpi-grid">

          <div class="alpro-kpi alpro-kpi--sales">
            <div class="top">
              <div>
                <div class="label">Sprzedaż brutto</div>
                <div class="value">{$summary.sales_total|number_format:2:',':' '} zł</div>
                <div class="sub">Suma zamówień w okresie</div>
              </div>
              <div class="icon" title="Sprzedaż">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M7 7h10v10H7V7Z" stroke="currentColor" stroke-width="2" />
                  <path d="M9 11h6M9 14h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="alpro-kpi alpro-kpi--fees">
            <div class="top">
              <div>
                <div class="label">Opłaty Allegro</div>
                <div class="value {if $summary.fees_total < 0}text-danger{elseif $summary.fees_total > 0}text-success{/if}">
                  {$summary.fees_total|number_format:2:',':' '} zł
                </div>
                <div class="sub">Opłaty + korekty (bez przepływów środków)</div>
              </div>
              <div class="icon" title="Opłaty">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 3v18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M16 7c0-2-2-3-4-3s-4 1-4 3 2 3 4 3 4 1 4 3-2 3-4 3-4-1-4-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="alpro-kpi alpro-kpi--net">
            <div class="top">
              <div>
                <div class="label">Saldo po opłatach</div>
                <div class="value {if $summary.net_after_fees < 0}text-danger{elseif $summary.net_after_fees < 5}text-warning{else}text-success{/if}">
                  {$summary.net_after_fees|number_format:2:',':' '} zł
                </div>
                <div class="sub">Sprzedaż + opłaty (to nie koszt towaru)</div>
              </div>
              <div class="icon" title="Saldo">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M4 8h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z" stroke="currentColor" stroke-width="2" />
                  <path d="M7 8V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" />
                  <path d="M16 13h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="alpro-kpi alpro-kpi--commission">
            <div class="top">
              <div>
                <div class="label">Prowizje</div>
                <div class="value text-danger">{$summary.fees_commission|number_format:2:',':' '} zł</div>
                <div class="sub">Największy składnik opłat</div>
              </div>
              <div class="icon" title="Prowizje">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M7 4h10v16H7V4Z" stroke="currentColor" stroke-width="2" />
                  <path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
            </div>
          </div>

          <div class="alpro-kpi alpro-kpi--delivery">
            <div class="top">
              <div>
                <div class="label">Dostawa</div>
                <div class="value text-danger">{$summary.fees_delivery|number_format:2:',':' '} zł</div>
                <div class="sub">Opłaty dostaw / etykiety</div>
              </div>
              <div class="icon" title="Dostawa">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 7h11v10H3V7Z" stroke="currentColor" stroke-width="2" />
                  <path d="M14 10h4l3 3v4h-7v-7Z" stroke="currentColor" stroke-width="2" />
                  <path d="M7 19a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm12 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" stroke="currentColor" stroke-width="2" />
                </svg>
              </div>
            </div>
          </div>

          <div class="alpro-kpi alpro-kpi--promo">
            <div class="top">
              <div>
                <div class="label">Rabaty / zwroty</div>
                <div class="value {if $summary.fees_refunds >= 0}text-success{else}text-danger{/if}">{$summary.fees_refunds|number_format:2:',':' '} zł</div>
                <div class="sub">Korekty, rabaty, rekompensaty</div>
              </div>
              <div class="icon" title="Rabaty/zwroty">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 3 3 8l9 5 9-5-9-5Z" stroke="currentColor" stroke-width="2" />
                  <path d="M3 8v8l9 5 9-5V8" stroke="currentColor" stroke-width="2" />
                </svg>
              </div>
            </div>
          </div>

        </div>

        {* Breakdown bars *}
        <div class="mt-3 alpro-break">
          <div style="font-weight:700; margin-bottom:8px;">Struktura opłat</div>

          <div class="row">
            <div class="name">Prowizje</div>
            <div class="alpro-bar alpro-bar--purple"><span style="width:{$fee_shares.commission|intval}%"></span></div>
            <div class="amount">{$summary.fees_commission|number_format:2:',':' '} zł</div>
          </div>

          <div class="row">
            <div class="name">Dostawa</div>
            <div class="alpro-bar alpro-bar--info"><span style="width:{$fee_shares.delivery|intval}%"></span></div>
            <div class="amount">{$summary.fees_delivery|number_format:2:',':' '} zł</div>
          </div>

          <div class="row">
            <div class="name">Smart</div>
            <div class="alpro-bar alpro-bar--warning"><span style="width:{$fee_shares.smart|intval}%"></span></div>
            <div class="amount">{$summary.fees_smart|number_format:2:',':' '} zł</div>
          </div>

          <div class="row">
            <div class="name">Promocja</div>
            <div class="alpro-bar alpro-bar--danger"><span style="width:{$fee_shares.promotion|intval}%"></span></div>
            <div class="amount">{$summary.fees_promotion|number_format:2:',':' '} zł</div>
          </div>

          <div class="row">
            <div class="name">Rabaty / zwroty</div>
            <div class="alpro-bar alpro-bar--success"><span style="width:{$fee_shares.refunds|intval}%"></span></div>
            <div class="amount">{$summary.fees_refunds|number_format:2:',':' '} zł</div>
          </div>

          <div class="row">
            <div class="name">Pozostałe</div>
            <div class="alpro-bar"><span style="width:{$fee_shares.other|intval}%"></span></div>
            <div class="amount">{$fees_other|number_format:2:',':' '} zł</div>
          </div>
        </div>

      {/if}

    </div>
  </div>

  {* SZCZEGÓŁY ZAMÓWIENIA *}
  

  {* LISTA ZAMÓWIEŃ *}
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between" style="gap:10px; flex-wrap:wrap;">
      <strong>Zamówienia</strong>
      <span class="alpro-badge">Pokazano <strong>{$orders_from|intval}-{$orders_to|intval}</strong> z <strong>{$orders_total|intval}</strong></span>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-hover table-sm mb-0 alpro-table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Konto</th>
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
              <td><span class="alpro-badge" title="Konto Allegro">{$o.account_label|default:'-'|escape:'htmlall':'UTF-8'}</span></td>
              <td><span class="alpro-id" title="{$o.checkout_form_id|escape:'htmlall':'UTF-8'}">{$idShort|escape:'htmlall':'UTF-8'}</span></td>
              <td>{$o.buyer_login|escape:'htmlall':'UTF-8'}</td>
              <td class="text-right">{$o.total_amount|number_format:2:',':' '} {$o.currency|escape:'htmlall':'UTF-8'}</td>
              <td class="text-right {if $o.fees_total < 0}text-danger{elseif $o.fees_total > 0}text-success{/if}">
                {$o.fees_total|number_format:2:',':' '} zł
              </td>
              <td class="text-right {if $saldo < 0}text-danger{elseif $saldo < 5}text-warning{else}text-success{/if}">
                {$saldo|number_format:2:',':' '} zł
              </td>
              <td>
                <a class="btn btn-outline-secondary btn-sm js-alpro-details" href="#" data-checkout="{$o.checkout_form_id|escape:'htmlall':'UTF-8'}">Szczegóły</a>
              </td>
            </tr>
          {/foreach}
          {if !$orders_rows}
            <tr><td colspan="8" class="p-3" style="color:#6c757d;">Brak zamówień w wybranym okresie.</td></tr>
          {/if}
        </tbody>
      </table>
    </div>

    <div class="alpro-table-footer">
      <div class="muted">Pokazano {$orders_from|intval}-{$orders_to|intval} z {$orders_total|intval}</div>

      {if isset($page_links) && $page_links|@count > 0}
        <nav class="alpro-pagination" aria-label="Paginacja">
          <ul class="pagination pagination-sm">
            {foreach from=$page_links item=pl}
              {if $pl.type=='gap'}
                <li class="page-item disabled"><span class="page-link">{$pl.label|escape:'htmlall':'UTF-8'}</span></li>
              {elseif $pl.type=='nav'}
                <li class="page-item {if !empty($pl.disabled)}disabled{/if}">
                  <a class="page-link" href="{if !empty($pl.disabled)}#{else}{$pl.url|escape:'htmlall':'UTF-8'}{/if}">{$pl.label|escape:'htmlall':'UTF-8'}</a>
                </li>
              {else}
                <li class="page-item {if !empty($pl.active)}active{/if}">
                  <a class="page-link" href="{$pl.url|escape:'htmlall':'UTF-8'}">{$pl.label|escape:'htmlall':'UTF-8'}</a>
                </li>
              {/if}
            {/foreach}
          </ul>
        </nav>
      {/if}
    </div>

  </div>

</div>

{* Konfiguracja JS (bez {literal} — dane jako data-*) *}
<div id="alpro-settlements"
     data-ajax-url="{$ajax_url|escape:'htmlall':'UTF-8'}"
     data-account-id="{$selected_account_id|intval}"
     data-date-from="{$date_from|escape:'htmlall':'UTF-8'}"
     data-date-to="{$date_to|escape:'htmlall':'UTF-8'}"
></div>

{* Modal: szczegóły zamówienia *}
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
