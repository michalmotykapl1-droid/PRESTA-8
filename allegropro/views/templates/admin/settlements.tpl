{* AllegroPro - Rozliczenia (Billing Entries) *}

<div class="alpro-page">

  <div class="card mb-3">
    <div class="card-header">
      <div class="alpro-head">
        <div class="title">
          <i class="material-icons">paid</i>
          <strong>Rozliczenia</strong>

          {if isset($selected_account_label) && $selected_account_label}
            {assign var=__multi value=($selected_account_ids|@count > 1)}
            <span class="alpro-badge" title="{if $__multi}Wybrane konta: {foreach from=$selected_account_labels item=lbl name=lab}{$lbl|escape:'htmlall':'UTF-8'}{if !$smarty.foreach.lab.last}, {/if}{/foreach}{else}Wybrane konto{/if}">
              {if $__multi}Konta:{else}Konto:{/if} <strong>{$selected_account_label|escape:'htmlall':'UTF-8'}</strong>
            </span>
          {/if}

          <span class="alpro-badge" title="{if $mode=='billing'}Daty dotyczą księgowania opłat (Sales Center).{else}Daty dotyczą złożenia zamówień; koszty liczone są dla tych zamówień.{/if}">
            Tryb: <strong>{if $mode=='billing'}Księgowanie opłat{else}Zamówienia z okresu{/if}</strong>
          </span>

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
          {* Uwaga: action musi zawierać poprawny token, inaczej Presta przekieruje do Pulpitu.*}
          <form method="get" action="index.php" class="m-0">
            <input type="hidden" name="controller" value="AdminAllegroProSettlements" />
            <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="page" value="1" />

            
            <div class="alpro-accountbar">
              <div class="form-group alpro-account-group">
                <label class="form-control-label">Konto</label>

                <div class="alpro-account-pick">
                  <div class="alpro-ms" id="alproAccountsMs">
                    <button type="button" class="alpro-ms__btn" aria-haspopup="true" aria-expanded="false">
                      <span class="alpro-ms__btnText">
                        {assign var=__selCount value=$selected_account_ids|@count}
                        {if $__selCount==1}
                          {foreach from=$accounts item=a}
                            {if in_array($a.id_allegropro_account, $selected_account_ids)}
                              {$a.label|escape:'htmlall':'UTF-8'}{if $a.allegro_login} ({$a.allegro_login|escape:'htmlall':'UTF-8'}){/if}
                            {/if}
                          {/foreach}
                        {elseif $__selCount>1}
                          Wybrane: {$__selCount}
                        {else}
                          Wybierz konto
                        {/if}
                      </span>
                      <span class="alpro-ms__chev">▾</span>
                    </button>

                    <div class="alpro-ms__menu" role="menu">
                      <div class="alpro-ms__menuHead">
                        <span class="alpro-ms__hint">Wybierz konto</span>
                      <input type="text" class="alpro-ms__search" placeholder="Szukaj konta…" />
                        <div class="alpro-ms__menuActions">
                          <a href="#" data-act="all">Wszystkie</a>
                          <a href="#" data-act="none">Wyczyść</a>
                        </div>
                      </div>

                      <div class="alpro-ms__list">
                        {foreach from=$accounts item=a}
                          <label class="alpro-ms__item">
                            <input type="checkbox" value="{$a.id_allegropro_account|intval}" {if in_array($a.id_allegropro_account, $selected_account_ids)}checked{/if}>
                            <span class="alpro-ms__label">{$a.label|escape:'htmlall':'UTF-8'}{if $a.allegro_login} ({$a.allegro_login|escape:'htmlall':'UTF-8'}){/if}</span>
                          </label>
                        {/foreach}
                      </div>
                    </div>

                    <div class="alpro-ms__hidden">
                      {foreach from=$selected_account_ids item=aid}
                        <input type="hidden" name="id_allegropro_account[]" value="{$aid|intval}" />
                      {/foreach}
                    </div>
                  </div>

                  <button type="submit" class="btn btn-outline-secondary alpro-btn-pick" title="Pokaż dane dla wybranych kont">
                    <i class="material-icons" style="font-size:18px; vertical-align:middle;">check</i>
                    <span style="vertical-align:middle;">Wybierz</span>
                  </button>
                </div>

                <div class="help-block alpro-help">Możesz wybrać kilka kont i kliknąć „Wybierz”.</div>
                <div class="help-block alpro-muted">Dostępne konta: {$accounts|@count}</div>
                <a href="#" class="alpro-select-all" id="alproSelectAll">Zaznacz wszystkie</a>
              </div>
            </div>

            <div class="alpro-filter-grid">
              <div class="form-group">
                <label class="form-control-label">Zakres dotyczy</label>
                <select name="mode" class="form-control">
                  <option value="billing" {if $mode=='billing'}selected{/if}>Księgowanie opłat (Sales Center)</option>
                  <option value="orders" {if $mode=='orders'}selected{/if}>Zamówienia z okresu (koszt zamówień)</option>
                </select>
                <div class="help-block alpro-muted" style="margin-top:4px;">
                  {if $mode=='billing'}Daty odnoszą się do <strong>daty księgowania opłat</strong>.{else}Daty odnoszą się do <strong>daty złożenia zamówienia</strong>.{/if}
                </div>
              </div>

              <div class="form-group">
                <label class="form-control-label">Od</label>
                <input type="date" class="form-control" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}" />
              </div>

              <div class="form-group">
                <label class="form-control-label">Do</label>
                <input type="date" class="form-control" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}" />
              </div>

              <div class="form-group">
                <label class="form-control-label">Szukaj (ID / login)</label>
                <input type="text" class="form-control" name="q" value="{$q|escape:'htmlall':'UTF-8'}" placeholder="np. 23187951... lub login" />
              </div>

              <div class="form-group">
                <label class="form-control-label">Status zamówienia</label>
                <select name="order_state" class="form-control">
                  <option value="all" {if $order_state=='all'}selected{/if}>Wszystkie</option>
                  <option value="paid" {if $order_state=='paid'}selected{/if}>Opłacone</option>
                  <option value="unpaid" {if $order_state=='unpaid'}selected{/if}>Nieopłacone</option>
                  <option value="cancelled" {if $order_state=='cancelled'}selected{/if}>Anulowane</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-control-label">&nbsp;</label>
                <div class="form-check" style="margin-top:8px;">
                  <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="cancelled_no_refund" value="1" {if $cancelled_no_refund}checked{/if}>
                    Anulowane bez zwrotu opłat
                  </label>
                </div>
              </div>

              <div class="form-group">
                <label class="form-control-label">Grupa operacji</label>
                <select name="fee_group" class="form-control">
                  <option value="" {if $fee_group==''}selected{/if}>Wszystkie</option>
                  <option value="commission" {if $fee_group=='commission'}selected{/if}>Prowizje / sprzedaż</option>
                  <option value="delivery" {if $fee_group=='delivery'}selected{/if}>Dostawa / etykiety</option>
                  <option value="smart" {if $fee_group=='smart'}selected{/if}>SMART</option>
                  <option value="promotion" {if $fee_group=='promotion'}selected{/if}>Promocja / wyróżnienia</option>
                  <option value="refunds" {if $fee_group=='refunds'}selected{/if}>Rabaty / zwroty</option>
                  <option value="other" {if $fee_group=='other'}selected{/if}>Pozostałe</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-control-label">Typy operacji</label>
                <div class="alpro-ms" id="alproFeeTypesMs">
                  <button type="button" class="alpro-ms__btn" aria-haspopup="true" aria-expanded="false">
                    <span class="alpro-ms__btnText">Wybierz typy</span>
                    <span class="alpro-ms__chev">▾</span>
                  </button>

                  <div class="alpro-ms__menu" role="menu">
                    <div class="alpro-ms__menuHead">
                      <span class="alpro-ms__hint">Typy z wybranego okresu</span>
                      <input type="text" class="alpro-ms__search" placeholder="Szukaj typu…" />
                      <div class="alpro-ms__menuActions">
                        <a href="#" data-act="all">Wszystkie</a>
                        <a href="#" data-act="none">Wyczyść</a>
                      </div>
                    </div>
                    <div class="alpro-ms__list">
                      {if isset($fee_types_available) && $fee_types_available|@count > 0}
                        {foreach from=$fee_types_available item=ft}
                          <label class="alpro-ms__item">
                            <input type="checkbox" value="{$ft.type_name|escape:'htmlall':'UTF-8'}" {if isset($fee_types_selected) && in_array($ft.type_name, $fee_types_selected)}checked{/if}>
                            <span class="alpro-ms__label">{$ft.type_name|escape:'htmlall':'UTF-8'}</span>
                            <span style="margin-left:auto;color:#8a93a0;font-size:12px;">{$ft.cnt|intval}</span>
                          </label>
                        {/foreach}
                      {else}
                        <div class="alpro-ms__empty">Brak typów operacji w wybranym okresie.</div>
                      {/if}
                    </div>
                    <div class="alpro-ms__hidden"></div>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-control-label">Na stronę</label>
                <select name="per_page" class="form-control">
                  <option value="25" {if $per_page==25}selected{/if}>25</option>
                  <option value="50" {if $per_page==50}selected{/if}>50</option>
                  <option value="100" {if $per_page==100}selected{/if}>100</option>
                  <option value="200" {if $per_page==200}selected{/if}>200</option>
                </select>
              </div>

              <div class="form-group alpro-filter-actions">
                <button type="submit" class="btn btn-outline-secondary">
                  <i class="material-icons" style="font-size:18px; vertical-align:middle;">tune</i>
                  <span style="vertical-align:middle;">Filtruj</span>
                </button>
              </div>
            </div>
</div>
          </form>
        </div>

        <div class="filters-right">
          <a class="btn btn-outline-secondary" href="{$current_index|escape:'htmlall':'UTF-8'}&token={$token|escape:'htmlall':'UTF-8'}{foreach from=$selected_account_ids item=aid}&id_allegropro_account[]={$aid|intval}{/foreach}&mode={$mode|escape:'url'}&date_from={$date_from|escape:'url'}&date_to={$date_to|escape:'url'}&q={$q|escape:'url'}&order_state={$order_state|escape:'url'}&cancelled_no_refund={$cancelled_no_refund|intval}&fee_group={$fee_group|default:''|escape:'url'}{if isset($fee_types_selected) && $fee_types_selected|@count>0}{foreach from=$fee_types_selected item=ft}&fee_type[]={$ft|escape:'url'}{/foreach}{/if}&page={$page|intval}&per_page={$per_page|intval}">
            <i class="material-icons" style="font-size:18px; vertical-align:middle;">refresh</i>
            <span style="vertical-align:middle;">Odśwież</span>
          </a>

          <form method="post" action="index.php" class="m-0">
            <input type="hidden" name="controller" value="AdminAllegroProSettlements" />
            <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="id_allegropro_account" value="{$selected_account_id|intval}" />
            <input type="hidden" name="mode" value="{$mode|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="date_from" value="{$date_from|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="date_to" value="{$date_to|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="q" value="{$q|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="order_state" value="{$order_state|escape:'htmlall':'UTF-8'}" />
            <input type="hidden" name="cancelled_no_refund" value="{$cancelled_no_refund|intval}" />
            <input type="hidden" name="fee_group" value="{$fee_group|default:''|escape:'htmlall':'UTF-8'}" />
            {if isset($fee_types_selected) && $fee_types_selected|@count>0}
              {foreach from=$fee_types_selected item=ft}
                <input type="hidden" name="fee_type[]" value="{$ft|escape:'htmlall':'UTF-8'}" />
              {/foreach}
            {/if}
            <input type="hidden" name="page" value="{$page|intval}" />
            <input type="hidden" name="per_page" value="{$per_page|intval}" />

            <button type="submit" id="alproSyncBtn" name="submitAllegroProBillingSync" value="1" class="btn btn-primary" {if $selected_account_ids|@count > 1 || $mode!='billing'}disabled title="Synchronizacja działa dla jednego konta naraz i dotyczy księgowania opłat — wybierz jedno konto oraz tryb 'Księgowanie opłat'."{/if}>
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

        <div class="mt-4 alpro-dashboard">

          <div class="alpro-dashboard__left">
            <div class="alpro-kpi-grid alpro-kpi-grid--compact">

          <div class="alpro-kpi alpro-kpi--sales">
            <div class="top">
              <div>
                <div class="label">Sprzedaż brutto</div>
                <div class="value">{$summary.sales_total|number_format:2:',':' '} zł</div>
                <div class="sub">Zamówień: <strong>{$summary.orders_count|intval}</strong></div>
                <div class="sub">{if $mode=='billing'}Zamówienia z opłatami zaksięgowanymi w okresie{else}Zamówienia złożone w okresie{/if}</div>
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
                <div class="label">{if $mode=='billing'}Opłaty Allegro{else}Koszty zamówień{/if}</div>
                <div class="value {if $summary.fees_total < 0}text-danger{elseif $summary.fees_total > 0}text-success{/if}">
                  {$summary.fees_total|number_format:2:',':' '} zł
                </div>
                <div class="sub">{if $mode=='billing'}Opłaty + korekty (bez przepływów środków){else}Opłaty przypisane do zamówień z okresu (mogą być zaksięgowane później){/if}</div>
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

          {if isset($issues_summary.orders_count) && $issues_summary.orders_count > 0}
          <div class="alpro-kpi alpro-kpi--refundpending">
            <div class="top">
              <div>
                <div class="label">Niezgodności opłat (Do wyjaśnienia)</div>
                <div class="value {if $issues_summary.balance < 0}text-danger{else}text-success{/if}">{$issues_summary.balance|number_format:2:',':' '} zł</div>
                <div class="sub">Problemów: <strong>{$issues_summary.orders_count|intval}</strong> • Wpisy billing: <strong>{$issues_summary.billing_rows|intval}</strong></div>
                <div class="sub">
                  Opłaty: <span class="text-danger">{$issues_summary.fees_neg|number_format:2:',':' '} zł</span>
                  • Zwroty/korekty: <span class="text-success">+{$issues_summary.refunds_pos|number_format:2:',':' '} zł</span>
                </div>
                {if isset($issues_breakdown)}
                  <div class="sub" style="margin-top:6px;">
                    {if $issues_breakdown.api.orders|intval > 0}
                      <span class="badge badge-danger" style="margin-right:6px;">ERR</span>
                      <strong>{$issues_breakdown.api.orders|intval}</strong> • saldo: {$issues_breakdown.api.balance|number_format:2:',':' '} zł
                    {/if}
                    {if $issues_breakdown.unpaid.orders|intval > 0}
                      <span class="badge badge-warning" style="margin-left:10px; margin-right:6px;">NIEOPŁ.</span>
                      <strong>{$issues_breakdown.unpaid.orders|intval}</strong> • saldo: {$issues_breakdown.unpaid.balance|number_format:2:',':' '} zł
                    {/if}
                    {if $issues_breakdown.cancelled.orders|intval > 0}
                      <span class="badge badge-warning" style="margin-left:10px; margin-right:6px;">ANUL.</span>
                      <strong>{$issues_breakdown.cancelled.orders|intval}</strong> • saldo: {$issues_breakdown.cancelled.balance|number_format:2:',':' '} zł
                    {/if}
                  </div>
                {/if}
              </div>
              <div class="icon" title="Do wyjaśnienia">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Z" stroke="currentColor" stroke-width="2"/>
                  <path d="M12 7v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M12 17h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
              </div>
            </div>
          </div>
          {/if}


        </div>
          </div>

          <div class="alpro-dashboard__right">

        {* Wykres kołowy: struktura opłat *}
        <div class="alpro-structure" id="alproStructure" data-structure="{$structure_chart_json|escape:'htmlall':'UTF-8'}">
          <div class="alpro-structure__head">
            <div>
              <div class="ttl">Struktura opłat</div>
              <div class="sub">udział kosztów w opłatach oraz w sprzedaży</div>
            </div>
            <div class="alpro-structure__hint">
              <span class="chip">Koszt opłat: <strong id="alproFeesRate">—</strong></span>
              <span class="chip">Opłaty łącznie: <strong id="alproFeesTotal">—</strong></span>
              <span class="chip">Rabaty/zwroty: <strong id="alproRefunds">—</strong></span>
            </div>
          </div>

          <div class="alpro-structure__grid">
            <div class="alpro-structure__chart">
              <div class="alpro-pie" id="alproPie"></div>
              <div class="alpro-pie-center">
                <div class="k">Koszty opłat</div>
                <div class="v" id="alproPieTotal">—</div>
                <div class="sub" id="alproPieSub">—</div>
              </div>
            </div>

            <div class="alpro-structure__legend" id="alproLegend">
              <div class="muted">Ładowanie…</div>
            </div>
          </div>
        </div>
          </div>
        </div>

      {/if}

    </div>
  </div>

  {* SZCZEGÓŁY ZAMÓWIENIA *}
  

  {* LISTA ZAMÓWIEŃ *}
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between" style="gap:10px; flex-wrap:wrap;">
      <ul class="nav nav-tabs" id="alproSubTabs" style="border-bottom:0;">
        <li class="nav-item">
          <a class="nav-link active js-alpro-subtab" href="#alproTabOrders" data-tab="orders">Zamówienia</a>
        </li>
        <li class="nav-item">
          <a class="nav-link js-alpro-subtab" href="#alproTabIssues" data-tab="issues">Do wyjaśnienia{if isset($issues_badge_total) && $issues_badge_total>0} <span class="badge badge-danger ml-1">{$issues_badge_total|intval}</span>{/if}</a>
        </li>
      </ul>
      <span class="alpro-badge" id="alproTabBadgeOrders">Pokazano <strong>{$orders_from|intval}-{$orders_to|intval}</strong> z <strong>{$orders_total|intval}</strong></span>
    </div>

    <div class="tab-content" style="padding-top:10px;">
      <div class="tab-pane is-active" id="alproTabOrders">

    <div class="table-responsive">
      <table class="table table-striped table-hover table-sm mb-0 alpro-table">
        <thead>
          <tr>
            <th>{if $mode=='billing'}Data operacji{else}Data zamówienia{/if}</th>
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
                        <tr>
              <td>{$o.date_display|default:$o.created_at_allegro|escape:'htmlall':'UTF-8'}</td>
              <td><span class="alpro-badge" title="Konto Allegro">{$o.account_label|default:'-'|escape:'htmlall':'UTF-8'}</span></td>
              <td>
                <div class="alpro-idwrap">
                  <span class="alpro-id" title="{$o.checkout_form_id|escape:'htmlall':'UTF-8'}">{$o.checkout_form_id|escape:'htmlall':'UTF-8'}</span>
                  <a href="#" class="alpro-copy js-alpro-copy" data-copy="{$o.checkout_form_id|escape:'htmlall':'UTF-8'}" title="Kopiuj ID">
                    <i class="material-icons" style="font-size:16px;">content_copy</i>
                  </a>
                </div>
                {if $o.order_status}
                  {assign var=_st value=$o.order_status|upper}
                  <div style="margin-top:4px;">
                    {if $_st=='CANCELLED'}
                      <span class="badge badge-danger">Anulowane</span>
                    {elseif $_st=='FILLED_IN'}
                      <span class="badge badge-warning">Nieopłacone</span>
                    {elseif $_st=='READY_FOR_PROCESSING' || $_st=='BOUGHT'}
                      <span class="badge badge-success">Opłacone</span>
                    {else}
                      <span class="badge badge-secondary">{$_st|escape:'htmlall':'UTF-8'}</span>
                    {/if}
                  </div>
                {/if}
              </td>
              <td>{$o.buyer_login|escape:'htmlall':'UTF-8'}</td>
              <td class="text-right">
                {if $o.total_amount && $o.total_amount > 0}
                  {$o.total_amount|number_format:2:',':' '} {$o.currency|escape:'htmlall':'UTF-8'}
                  {if isset($o.shipping_amount) && $o.shipping_amount > 0}
                    <div class="alpro-muted" style="font-size:11px;">Dostawa: {$o.shipping_amount|number_format:2:',':' '} {$o.currency|escape:'htmlall':'UTF-8'}</div>
                  {/if}
                {else}
                  <span class="alpro-muted">—</span>
                {/if}
              </td>
              <td class="text-right {if $o.fees_total < 0}text-danger{elseif $o.fees_total > 0}text-success{/if}">
                {$o.fees_total|number_format:2:',':' '} zł
                {if isset($o.fees_pending) && $o.fees_pending > 0.01}
                  <div style="margin-top:4px;"><span class="badge badge-warning" title="Allegro pobrało opłaty i nie oddało ich w całości">Do zwrotu: {$o.fees_pending|number_format:2:',':' '} zł</span></div>
                {/if}
              </td>
              <td class="text-right {if $saldo < 0}text-danger{elseif $saldo < 5}text-warning{else}text-success{/if}">
                {$saldo|number_format:2:',':' '} zł
              </td>
              <td>
                <a class="btn btn-outline-secondary btn-sm js-alpro-details" href="#" data-checkout="{$o.checkout_form_id|escape:'htmlall':'UTF-8'}" data-account-id="{$o.id_allegropro_account|intval}">Szczegóły</a>
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

      <div class="tab-pane" id="alproTabIssues">
        <div class="d-flex align-items-center justify-content-between" style="gap:10px; flex-wrap:wrap; padding:6px 0 10px;">
          <div class="alpro-muted" style="font-size:12px;">
            Źródło: billing_entry.order_error_* (błędy enrichmentu checkout-form) + wykrycie: nieopłacone/anulowane z ujemnym saldem opłat.
          </div>
          <label class="form-check" style="margin:0; display:flex; align-items:center; gap:8px;">
            <input type="checkbox" id="alproIssuesAll" {if isset($issues_all_history) && $issues_all_history}checked{/if}>
            <span style="font-size:12px; color:#6c757d;">Cała historia (bez filtra dat)</span>
          </label>
          <label class="form-check" id="alproIssuesRefundModeWrap" style="margin:0; display:flex; align-items:center; gap:8px;">
            <span style="font-size:12px; color:#6c757d;">Filtr:</span>
            <select id="alproIssuesRefundMode" class="form-control form-control-sm" style="width:auto; min-width:220px;">
              <option value="any" {if !isset($issues_refund_mode) || $issues_refund_mode=='any'}selected{/if}>Wszystkie</option>
              <option value="balance_neg" {if isset($issues_refund_mode) && $issues_refund_mode=='balance_neg'}selected{/if}>Tylko saldo ujemne</option>
              <option value="no_refund" {if isset($issues_refund_mode) && $issues_refund_mode=='no_refund'}selected{/if}>Tylko brak zwrotu</option>
            </select>
          </label>
        </div>

<ul class="nav nav-pills" id="alproIssuesInnerTabs" style="margin:0 0 10px 0; gap:8px;">
  <li class="nav-item">
    <a class="nav-link active js-alpro-issues-inner" href="#" data-view="orders">Problemy zamówień <span class="badge badge-light ml-1">{if isset($issues_summary.orders_count)}{$issues_summary.orders_count|intval}{else}0{/if}</span></a>
  </li>
  <li class="nav-item">
    <a class="nav-link js-alpro-issues-inner" href="#" data-view="unassigned">Nieprzypisane operacje <span class="badge badge-light ml-1">{if isset($unassigned_summary.entries_count)}{$unassigned_summary.entries_count|intval}{else}0{/if}</span></a>
  </li>
</ul>

<div id="alproIssuesOrdersWrap">

        <div class="alpro-kpi-grid alpro-kpi-grid--compact" style="margin: 0 0 12px 0;">
          <div class="alpro-kpi">
            <div class="top">
              <div>
                <div class="label">Problem order_id</div>
                <div class="value">{if isset($issues_summary.orders_count)}{$issues_summary.orders_count|intval}{else}0{/if}</div>
                <div class="sub">Zamówienia do wyjaśnienia</div>
              </div>
              <div class="icon" title="Problemy"><i class="material-icons" style="font-size:20px;">report_problem</i></div>
            </div>
          </div>

          <div class="alpro-kpi">
            <div class="top">
              <div>
                <div class="label">Wpisy billing</div>
                <div class="value">{if isset($issues_summary.billing_rows)}{$issues_summary.billing_rows|intval}{else}0{/if}</div>
                <div class="sub">Operacje w problemach</div>
              </div>
              <div class="icon" title="Billing"><i class="material-icons" style="font-size:20px;">receipt_long</i></div>
            </div>
          </div>

          <div class="alpro-kpi">
            <div class="top">
              <div>
                <div class="label">Opłaty pobrane</div>
                <div class="value text-danger">{if isset($issues_summary.fees_neg)}{$issues_summary.fees_neg|number_format:2:',':' '}{else}0,00{/if} zł</div>
                <div class="sub">Suma ujemnych (koszty)</div>
              </div>
              <div class="icon" title="Opłaty"><i class="material-icons" style="font-size:20px;">remove_circle</i></div>
            </div>
          </div>

          <div class="alpro-kpi">
            <div class="top">
              <div>
                <div class="label">Zwroty / korekty</div>
                <div class="value {if isset($issues_summary.refunds_pos) && $issues_summary.refunds_pos>0}text-success{/if}">{if isset($issues_summary.refunds_pos) && $issues_summary.refunds_pos>0}+{/if}{if isset($issues_summary.refunds_pos)}{$issues_summary.refunds_pos|number_format:2:',':' '}{else}0,00{/if} zł</div>
                <div class="sub">Suma dodatnich</div>
              </div>
              <div class="icon" title="Zwroty"><i class="material-icons" style="font-size:20px;">add_circle</i></div>
            </div>
          </div>

          <div class="alpro-kpi">
            <div class="top">
              <div>
                <div class="label">Saldo</div>
                <div class="value {if isset($issues_summary.balance) && $issues_summary.balance<0}text-danger{elseif isset($issues_summary.balance) && $issues_summary.balance>0}text-success{/if}">{if isset($issues_summary.balance)}{$issues_summary.balance|number_format:2:',':' '}{else}0,00{/if} zł</div>
                <div class="sub">Suma opłat i zwrotów</div>
              </div>
              <div class="icon" title="Saldo"><i class="material-icons" style="font-size:20px;">account_balance_wallet</i></div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm mb-0 alpro-table">
            <thead>
              <tr>
                <th>Konto</th>
                <th>Order ID</th>
                <th>Błąd</th>
                <th>Opis</th>
                <th class="text-right">Opłaty</th>
                <th class="text-right">Zwroty</th>
                <th class="text-right">Saldo</th>
                <th class="text-right">Próby</th>
                <th>Ostatnia próba</th>
                <th style="width:120px;">&nbsp;</th>
              </tr>
            </thead>
            <tbody>
              {if isset($issues_rows) && $issues_rows|@count>0}
                {foreach from=$issues_rows item=ir}
                  {assign var=_bal value=$ir.balance}
                  <tr>
                    <td><span class="alpro-badge">{$ir.account_label|default:'-'|escape:'htmlall':'UTF-8'}</span></td>
                    <td>
                      <div class="alpro-idwrap">
                        <span class="alpro-id" title="{$ir.order_id|escape:'htmlall':'UTF-8'}">{$ir.order_id|escape:'htmlall':'UTF-8'}</span>
                        <a href="#" class="alpro-copy js-alpro-copy" data-copy="{$ir.order_id|escape:'htmlall':'UTF-8'}" title="Kopiuj ID">
                          <i class="material-icons" style="font-size:16px;">content_copy</i>
                        </a>
                      </div>
                    </td>
                    <td>
                      <span class="badge {$ir.badge_class|default:'badge-danger'|escape:'htmlall':'UTF-8'}">{$ir.badge_text|default:'ERR'|escape:'htmlall':'UTF-8'}</span>
                    </td>
                    <td title="{$ir.desc|default:''|escape:'htmlall':'UTF-8'}">{$ir.desc|default:''|escape:'htmlall':'UTF-8'}</td>
                    <td class="text-right text-danger">{$ir.fees_neg|number_format:2:',':' '} zł</td>
                    <td class="text-right {if $ir.refunds_pos>0}text-success{/if}">{if $ir.refunds_pos>0}+{/if}{$ir.refunds_pos|number_format:2:',':' '} zł</td>
                    <td class="text-right {if $_bal<0}text-danger{elseif $_bal>0}text-success{/if}">{$_bal|number_format:2:',':' '} zł</td>
                    <td class="text-right">{$ir.attempts|intval}</td>
                    <td>{$ir.last_attempt_at|escape:'htmlall':'UTF-8'}</td>
                    <td>
                      <a class="btn btn-outline-secondary btn-sm js-alpro-details" href="#" data-checkout="{$ir.order_id|escape:'htmlall':'UTF-8'}" data-account-id="{$ir.id_allegropro_account|intval}">Szczegóły</a>
                    </td>
                  </tr>
                {/foreach}
              {else}
                <tr><td colspan="10" class="p-3" style="color:#6c757d;">Brak problemów w wybranym zakresie.</td></tr>
              {/if}
            </tbody>
          </table>
        </div>

        {if isset($issues_total) && isset($issues_limit) && $issues_total > $issues_limit}
          <div class="alpro-muted" style="padding:10px 2px; font-size:12px;">Pokazano pierwsze {$issues_limit|intval} z {$issues_total|intval}. (Paginacja w kolejnych etapach)</div>
        {/if}


</div> {* /#alproIssuesOrdersWrap *}

<div id="alproIssuesUnassignedWrap" style="display:none;">
  <div class="alpro-muted" style="font-size:12px; padding:2px 0 10px;">
    Operacje billing bez powiązania z zamówieniem (order_id puste). To są wpisy, których nie da się pokazać w tabeli „Zamówienia”, ale nadal wpływają na saldo.
  </div>

  <div class="alpro-kpi-grid alpro-kpi-grid--compact" style="margin: 0 0 12px 0;">
    <div class="alpro-kpi">
      <div class="top">
        <div>
          <div class="label">Operacje</div>
          <div class="value">{if isset($unassigned_summary.entries_count)}{$unassigned_summary.entries_count|intval}{else}0{/if}</div>
          <div class="sub">Nieprzypisane wpisy billing</div>
        </div>
        <div class="icon" title="Nieprzypisane"><i class="material-icons" style="font-size:20px;">help_outline</i></div>
      </div>
    </div>

    <div class="alpro-kpi">
      <div class="top">
        <div>
          <div class="label">Opłaty pobrane</div>
          <div class="value text-danger">{if isset($unassigned_summary.fees_neg)}{$unassigned_summary.fees_neg|number_format:2:',':' '}{else}0,00{/if} zł</div>
          <div class="sub">Suma ujemnych (koszty)</div>
        </div>
        <div class="icon" title="Opłaty"><i class="material-icons" style="font-size:20px;">remove_circle</i></div>
      </div>
    </div>

    <div class="alpro-kpi">
      <div class="top">
        <div>
          <div class="label">Zwroty / korekty</div>
          <div class="value {if isset($unassigned_summary.refunds_pos) && $unassigned_summary.refunds_pos>0}text-success{/if}">{if isset($unassigned_summary.refunds_pos) && $unassigned_summary.refunds_pos>0}+{/if}{if isset($unassigned_summary.refunds_pos)}{$unassigned_summary.refunds_pos|number_format:2:',':' '}{else}0,00{/if} zł</div>
          <div class="sub">Suma dodatnich</div>
        </div>
        <div class="icon" title="Zwroty"><i class="material-icons" style="font-size:20px;">add_circle</i></div>
      </div>
    </div>

    <div class="alpro-kpi">
      <div class="top">
        <div>
          <div class="label">Saldo</div>
          <div class="value {if isset($unassigned_summary.balance) && $unassigned_summary.balance<0}text-danger{elseif isset($unassigned_summary.balance) && $unassigned_summary.balance>0}text-success{/if}">{if isset($unassigned_summary.balance)}{$unassigned_summary.balance|number_format:2:',':' '}{else}0,00{/if} zł</div>
          <div class="sub">Suma opłat i zwrotów</div>
        </div>
        <div class="icon" title="Saldo"><i class="material-icons" style="font-size:20px;">account_balance_wallet</i></div>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-hover table-sm mb-0 alpro-table">
      <thead>
        <tr>
          <th>Konto</th>
          <th>Data</th>
          <th>Typ</th>
          <th>Oferta</th>
          <th class="text-right">Kwota</th>
          <th>VAT / adnotacja</th>
          <th style="width:130px;">&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        {if isset($unassigned_rows) && $unassigned_rows|@count>0}
          {foreach from=$unassigned_rows item=ur}
            <tr>
              <td><span class="alpro-badge">{$ur.account_label|default:'-'|escape:'htmlall':'UTF-8'}</span></td>
              <td>{$ur.occurred_at|escape:'htmlall':'UTF-8'}</td>
              <td>
                <div style="font-weight:600;">{$ur.type_name|escape:'htmlall':'UTF-8'}</div>
                <div class="alpro-muted" style="font-size:11px;">{$ur.type_id|escape:'htmlall':'UTF-8'}</div>
              </td>
              <td>
                <div style="font-weight:600;">{$ur.offer_name|default:'-'|escape:'htmlall':'UTF-8'}</div>
                {if $ur.offer_id}
                  <div class="alpro-muted" style="font-size:11px;">offer_id: {$ur.offer_id|escape:'htmlall':'UTF-8'}</div>
                {/if}
                {if $ur.billing_entry_id}
                  <div class="alpro-idwrap" style="margin-top:4px;">
                    <span class="alpro-id" title="{$ur.billing_entry_id|escape:'htmlall':'UTF-8'}">{$ur.billing_entry_id|escape:'htmlall':'UTF-8'}</span>
                    <a href="#" class="alpro-copy js-alpro-copy" data-copy="{$ur.billing_entry_id|escape:'htmlall':'UTF-8'}" title="Kopiuj billing_entry_id">
                      <i class="material-icons" style="font-size:16px;">content_copy</i>
                    </a>
                  </div>
                {/if}
              </td>
              <td class="text-right {if $ur.value_amount<0}text-danger{elseif $ur.value_amount>0}text-success{/if}">
                {if $ur.value_amount>0}+{/if}{$ur.value_amount|number_format:2:',':' '} {$ur.value_currency|escape:'htmlall':'UTF-8'}
              </td>
              <td>
                {if isset($ur.tax_percentage) && $ur.tax_percentage!==''}
                  {$ur.tax_percentage|number_format:2:',':' '}%
                {else}
                  -
                {/if}
                {if isset($ur.tax_annotation) && $ur.tax_annotation}
                  <span class="alpro-muted">{$ur.tax_annotation|escape:'htmlall':'UTF-8'}</span>
                {/if}
              </td>
              <td>
                <a class="btn btn-outline-secondary btn-sm js-alpro-billing-details" href="#" data-billing-entry="{$ur.id_allegropro_billing_entry|intval}" data-account-id="{$ur.id_allegropro_account|intval}">Podgląd</a>
              </td>
            </tr>
          {/foreach}
        {else}
          <tr><td colspan="7" class="p-3" style="color:#6c757d;">Brak nieprzypisanych operacji w wybranym zakresie.</td></tr>
        {/if}
      </tbody>
    </table>
  </div>

  {if isset($unassigned_total) && isset($unassigned_limit) && $unassigned_total > $unassigned_limit}
    <div class="alpro-muted" style="padding:10px 2px; font-size:12px;">Pokazano pierwsze {$unassigned_limit|intval} z {$unassigned_total|intval}. (Paginacja w kolejnych etapach)</div>
  {/if}
</div>
      </div>

    </div>

  </div>

</div>

{* Konfiguracja JS (bez {literal} — dane jako data-*) *}
<div id="alpro-settlements"
     data-ajax-url="{$ajax_url|escape:'htmlall':'UTF-8'}"
          data-date-from="{$date_from|escape:'htmlall':'UTF-8'}"
     data-date-to="{$date_to|escape:'htmlall':'UTF-8'}"
     data-mode="{$mode|escape:'htmlall':'UTF-8'}"
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


  

{* Modal synchronizacji rozliczeń (setup -> start -> postęp -> podsumowanie) *}
<div class="modal fade" id="alproSyncModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg alpro-sync-modal" role="document">
    <div class="modal-content alpro-sync-modal__content">
      <div class="modal-header alpro-sync-modal__head">
        <div class="alpro-sync-head">
          <div class="alpro-sync-head__icon"><i class="material-icons">sync</i></div>
          <div class="alpro-sync-head__txt">
            <div class="alpro-sync-head__title">Synchronizacja rozliczeń</div>
            <div class="alpro-sync-head__sub" id="alproSyncHeaderSub">Wybierz tryb i kliknij „Rozpocznij”.</div>
          </div>
        </div>
        <button type="button" class="close" id="alproSyncHeaderClose" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>

      <div class="modal-body alpro-sync-modal__body">

        <div class="alpro-sync-card alpro-sync-overall">
          <div class="alpro-sync-overall__row">
            <div class="alpro-sync-overall__label">Postęp</div>
            <div class="alpro-sync-overall__pct" id="alproSyncOverallPct">0%</div>
          </div>
          <div class="progress alpro-progress">
            <div id="alproSyncOverallBar" class="progress-bar" style="width:0%"></div>
          </div>
          <div class="alpro-sync-now">
            <span class="k">Aktualnie:</span>
            <span class="v" id="alproSyncNowText">Wybierz tryb i kliknij „Rozpocznij”.</span>
          </div>
        </div>

        <div class="alpro-sync-card alpro-sync-mode" id="alproSyncModeBox">
          <div class="alpro-sync-card__title">Tryb synchronizacji</div>

          <label class="alpro-radio">
            <input type="radio" id="alproSyncModeInc" name="alpro_sync_mode" value="inc" checked>
            <span>
              <strong>Szybka</strong> — tylko nowe wpisy + uzupełnij braki w zapisanych operacjach
            </span>
          </label>

          <label class="alpro-radio">
            <input type="radio" id="alproSyncModeFull" name="alpro_sync_mode" value="full">
            <span>
              <strong>Pełna</strong> — pobierz i zaktualizuj wszystkie wpisy w zakresie dat (wolniej)
            </span>
          </label>

          <div class="alpro-sync-hint">Na co dzień wybieraj „Szybka”. „Pełna” tylko gdy podejrzewasz braki lub po zmianach w logice mapowania.</div>
        </div>

        <div id="alproSyncSteps" style="display:none">
          <div class="alpro-sync-card alpro-sync-step">
            <div class="alpro-sync-step__title"><span class="alpro-step-badge">1</span> Pobieranie opłat (billing-entries)</div>
            <div class="progress alpro-progress alpro-progress--sm">
              <div id="alproSyncBillingBar" class="progress-bar" style="width:0%"></div>
            </div>
            <div id="alproSyncBillingText" class="alpro-muted">Oczekiwanie…</div>
          </div>

          <div class="alpro-sync-card alpro-sync-step">
            <div class="alpro-sync-step__title"><span class="alpro-step-badge">2</span> Uzupełnianie brakujących danych zamówień</div>
            <div class="progress alpro-progress alpro-progress--sm">
              <div id="alproSyncEnrichBar" class="progress-bar" style="width:0%"></div>
            </div>
            <div id="alproSyncEnrichText" class="alpro-muted">Oczekiwanie…</div>
          </div>

          <div class="alpro-sync-card alpro-sync-summary" id="alproSyncSummary" style="display:none">
            <div class="alpro-sync-card__title">Podsumowanie</div>
            <div class="alpro-sync-summary__grid">
              <div class="alpro-sync-metric">
                <div class="label">Pobrano wpisów</div>
                <div class="value" id="alproSumFetched">0</div>
              </div>
              <div class="alpro-sync-metric">
                <div class="label">Nowe wpisy</div>
                <div class="value" id="alproSumInserted">0</div>
              </div>
              <div class="alpro-sync-metric">
                <div class="label">Aktualizacje</div>
                <div class="value" id="alproSumUpdated">0</div>
              </div>
              <div class="alpro-sync-metric">
                <div class="label">Uzupełnione zamówienia</div>
                <div class="value" id="alproSumOrdersFilled">0</div>
              </div>
            </div>
            <div class="alpro-sync-summary__note" id="alproSyncSummaryNote"></div>
          </div>

          <div class="alpro-sync-details">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="alproSyncToggleLog">Pokaż szczegóły techniczne</button>
            <div id="alproSyncLogWrap" style="display:none">
              <pre id="alproSyncLog" class="alpro-sync-log"></pre>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer alpro-sync-modal__foot">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" id="alproSyncDismiss">Zamknij</button>
        <button type="button" class="btn btn-outline-secondary" id="alproSyncCancel" style="display:none">Anuluj</button>

        <div class="alpro-sync-foot-right">
          <button type="button" class="btn btn-outline-primary" id="alproSyncRefresh" style="display:none">
            <i class="material-icons">refresh</i> Odśwież widok
          </button>

          <button type="button" class="btn btn-primary" id="alproSyncStart">
            <i class="material-icons">play_arrow</i> Rozpocznij
          </button>

          <button type="button" class="btn btn-primary" data-dismiss="modal" id="alproSyncClose" style="display:none">Zamknij</button>
        </div>
      </div>
    </div>
  </div>
</div>

