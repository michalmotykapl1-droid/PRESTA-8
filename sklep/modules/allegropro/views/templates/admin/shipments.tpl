<div class="panel ap-ship-page">
  <style>
{literal}
    .ap-ship-page { border-radius:14px; }
    .ap-title { margin:0 0 12px; font-size:24px; font-weight:700; color:#1f3044; }

    .ap-card-grid { display:grid; grid-template-columns:repeat(3,minmax(220px,1fr)); gap:12px; margin-bottom:14px; }
    .ap-card {
      border:1px solid #dbe6f2; border-radius:12px; background:#fff; padding:14px;
      box-shadow:0 6px 20px rgba(28,56,86,.06);
    }
    .ap-card .k { color:#6f8197; text-transform:uppercase; font-size:11px; font-weight:700; margin-bottom:6px; }
    .ap-card .v { font-size:30px; line-height:1.1; font-weight:800; color:#22364d; }

    .ap-block {
      border:1px solid #dbe6f2; border-radius:12px; background:#fff; padding:14px; margin-bottom:14px;
      box-shadow:0 6px 20px rgba(28,56,86,.05);
    }
    .ap-block h4 { margin:0 0 10px; font-size:15px; font-weight:700; color:#1f3044; }

    .ap-settings-grid { display:grid; grid-template-columns:2fr 1fr 1fr; gap:10px; }
    .ap-filters-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr; gap:10px; }
    .ap-filters-grid-created { display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr 1fr; gap:10px; }

    .ap-field label { display:block; margin-bottom:4px; font-size:11px; text-transform:uppercase; color:#6f8197; font-weight:700; }
    .ap-field .form-control { border-radius:9px; border:1px solid #cfdceb; box-shadow:none; }

    .ap-account-list {
      border:1px solid #d4e2ef; border-radius:9px; background:#f8fbff; padding:8px; max-height:110px; overflow:auto;
    }
    .ap-account-list label { display:block; margin:0 0 6px; font-size:12px; color:#32475e; text-transform:none; font-weight:600; }

    .ap-actions { display:flex; align-items:flex-end; gap:8px; }
    .ap-btn {
      border:0; border-radius:10px; padding:9px 14px; font-size:12px; font-weight:700;
      display:inline-flex; gap:6px; align-items:center; text-decoration:none !important;
      transition:all .18s ease;
    }
    .ap-btn:hover { transform:translateY(-1px); }
    .ap-btn-primary { color:#fff; background:linear-gradient(135deg,#2e8bff 0%,#1f6fe0 100%); }
    .ap-btn-secondary { color:#27415b; background:#edf4fb; }
    .ap-btn-success { color:#fff; background:linear-gradient(135deg,#28b87a 0%,#1e9e66 100%); }
    .ap-btn-info { color:#fff; background:linear-gradient(135deg,#34b3d8 0%,#2190b0 100%); }
    .ap-btn-warning { color:#fff; background:linear-gradient(135deg,#f2b21d 0%,#d99800 100%); }
    .ap-btn-light { color:#2e4762; background:#eef5fc; }
    .ap-btn[disabled] { opacity:.75; cursor:not-allowed; transform:none !important; }
    .ap-spin {
      width:12px; height:12px; border:2px solid rgba(255,255,255,.45); border-top-color:#fff; border-radius:50%;
      display:inline-block; animation:apspin .7s linear infinite;
    }
    @keyframes apspin { from { transform:rotate(0deg);} to {transform:rotate(360deg);} }

    .ap-table-wrap { border:1px solid #dbe6f2; border-radius:12px; overflow:hidden; }
    .ap-table-top { padding:10px 12px; background:#f7fbff; border-bottom:1px solid #e5edf6; display:flex; justify-content:space-between; }
    .ap-table-top h5 { margin:0; font-size:15px; font-weight:700; color:#1f3044; }
    .ap-pill { font-size:11px; color:#46627f; background:#e8f1fb; padding:4px 8px; border-radius:999px; }
    .ap-table-wrap .table { margin:0; }
    .ap-table-wrap .table td, .ap-table-wrap .table th { vertical-align:middle; }
    .ap-pagination { padding:10px 12px; border-top:1px solid #e5edf6; background:#fcfeff; display:flex; justify-content:space-between; align-items:center; }
    .ap-muted { color:#7890a8; }

    .ap-modal-backdrop {
      position:fixed; inset:0; background:rgba(17,32,50,.45); z-index:10050;
      display:none; align-items:center; justify-content:center; padding:20px;
      backdrop-filter:blur(2px);
    }
    .ap-modal-backdrop.ap-open { display:flex; }
    .ap-modal {
      width:min(560px, 100%); background:#fff; border-radius:16px; overflow:hidden;
      box-shadow:0 28px 60px rgba(12,28,46,.35); border:1px solid #d8e4f2;
      animation:apModalIn .16s ease-out;
    }
    .ap-modal-head { padding:16px 18px; border-bottom:1px solid #e7eef7; display:flex; gap:10px; align-items:center; }
    .ap-modal-icon {
      width:32px; height:32px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
      background:#e9f2ff; color:#1e6fe0; font-size:16px;
    }
    .ap-modal-title { margin:0; font-size:18px; font-weight:800; color:#1f3044; }
    .ap-modal-body { padding:18px; color:#31475e; font-size:14px; line-height:1.55; }
    .ap-modal-actions { padding:0 18px 18px; display:flex; justify-content:flex-end; gap:8px; }

    @keyframes apModalIn {
      from { opacity:0; transform:translateY(6px) scale(.985); }
      to { opacity:1; transform:translateY(0) scale(1); }
    }

    @media (max-width: 1500px) {
      .ap-settings-grid { grid-template-columns:1fr; }
      .ap-filters-grid, .ap-filters-grid-created { grid-template-columns:1fr 1fr; }
      .ap-card-grid { grid-template-columns:1fr; }
    }
{/literal}
  </style>

  <h3 class="ap-title"><i class="icon icon-truck"></i> Przesyłki</h3>

  {* 1) Statystyki *}
  <div class="ap-card-grid">
    <div class="ap-card">
      <div class="k">Delivery services w cache</div>
      <div class="v">{if $allegropro_delivery_services_count > 0}{$allegropro_delivery_services_count|intval}{else}—{/if}</div>
    </div>
    <div class="ap-card">
      <div class="k">Bez przesyłki</div>
      <div class="v">{$allegropro_pending_pagination.total_rows|intval}</div>
    </div>
    <div class="ap-card">
      <div class="k">Z przesyłką</div>
      <div class="v">{$allegropro_labeled_pagination.total_rows|intval}</div>
    </div>
  </div>

  {* 2) Ustawienia *}
  <div class="ap-block">
    <h4>Ustawienia widoku i narzędzia</h4>
    <form method="get" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="ap-settings-grid">
      <input type="hidden" name="controller" value="AdminAllegroProShipments" />
      <input type="hidden" name="token" value="{$smarty.get.token|escape:'htmlall':'UTF-8'}" />
      <div class="ap-field">
        <label>Konta Allegro (wiele naraz)</label>
        <div class="ap-account-list">
          {foreach from=$allegropro_accounts item=a}
            <label>
              <input type="checkbox" name="filter_accounts[]" value="{$a.id_allegropro_account|intval}" {if in_array($a.id_allegropro_account, $allegropro_selected_accounts)}checked{/if} />
              {$a.label|escape:'htmlall':'UTF-8'} {if $a.allegro_login}({$a.allegro_login|escape:'htmlall':'UTF-8'}){/if}
            </label>
          {/foreach}
        </div>
      </div>
      <div class="ap-field">
        <label>Na stronę</label>
        <select name="per_page" class="form-control">
          <option value="10" {if $allegropro_per_page == 10}selected{/if}>10</option>
          <option value="25" {if $allegropro_per_page == 25}selected{/if}>25</option>
          <option value="50" {if $allegropro_per_page == 50}selected{/if}>50</option>
          <option value="100" {if $allegropro_per_page == 100}selected{/if}>100</option>
        </select>
        <div style="margin-top:10px;">
          <button class="ap-btn ap-btn-primary" type="submit"><i class="icon icon-refresh"></i> Zastosuj ustawienia</button>
        </div>
      </div>
      <div class="ap-field">
        <label>Reset</label>
        <a class="ap-btn ap-btn-secondary" href="{$admin_link|escape:'htmlall':'UTF-8'}"><i class="icon icon-undo"></i> Reset widoku</a>
      </div>
    </form>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px;">
      <div>
        <h4 style="margin-bottom:8px;">Odśwież delivery services</h4>
        <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="form-inline">
          <select name="id_allegropro_account" class="form-control">
            {foreach from=$allegropro_accounts item=a}
              <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account == $allegropro_action_account}selected{/if}>{$a.label|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
          </select>
          <input type="hidden" name="allegropro_refresh_delivery_services" value="1" />
          <button class="ap-btn ap-btn-info" type="submit" style="margin-left:8px;"><i class="icon icon-refresh"></i> Odśwież</button>
        </form>
      </div>
      <div>
        <h4 style="margin-bottom:8px;">Synchronizacja starszych danych przesyłek</h4>
        <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="form-inline">
          <select name="id_allegropro_account" class="form-control">
            {foreach from=$allegropro_accounts item=a}
              <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account == $allegropro_action_account}selected{/if}>{$a.label|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
          </select>
          <input type="hidden" name="allegropro_fix_custom_wza_uuid" value="1" />
          <button class="ap-btn ap-btn-warning js-modern-confirm" type="submit" style="margin-left:8px;" data-confirm-title="Potwierdzenie synchronizacji" data-confirm-message="Czy chcesz uzupełnić brakujące identyfikatory przesyłek dla starszych zamówień z niestandardowym gabarytem? Ta operacja jest bezpieczna i pomoże uporządkować historię danych."><i class="icon icon-wrench"></i> Synchronizuj</button>
        </form>
      </div>
    </div>
  </div>

  {* 3) Filtry i lista dla NIEUTWORZONYCH *}
  <div class="ap-block">
    <h4>Filtry: przesyłki nieutworzone</h4>
    <form method="get" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="ap-filters-grid">
      <input type="hidden" name="controller" value="AdminAllegroProShipments" />
      <input type="hidden" name="token" value="{$smarty.get.token|escape:'htmlall':'UTF-8'}" />
      <input type="hidden" name="per_page" value="{$allegropro_per_page|intval}" />
      {foreach from=$allegropro_selected_accounts item=aid}
        <input type="hidden" name="filter_accounts[]" value="{$aid|intval}" />
      {/foreach}

      {* Zachowujemy filtry sekcji utworzonych *}
      <input type="hidden" name="labeled_query" value="{$allegropro_labeled_filters.query|escape:'htmlall':'UTF-8'}" />
      <input type="hidden" name="labeled_date_from" value="{$allegropro_labeled_filters.date_from|escape:'htmlall':'UTF-8'}" />
      <input type="hidden" name="labeled_date_to" value="{$allegropro_labeled_filters.date_to|escape:'htmlall':'UTF-8'}" />
      {foreach from=$allegropro_labeled_filters.selected_status_codes item=sc}
        <input type="hidden" name="labeled_status_codes[]" value="{$sc|escape:'htmlall':'UTF-8'}" />
      {/foreach}
      {foreach from=$allegropro_labeled_filters.shipment_statuses item=ss}
        <input type="hidden" name="labeled_shipment_statuses[]" value="{$ss|escape:'htmlall':'UTF-8'}" />
      {/foreach}

      <div class="ap-field">
        <label>Szukaj</label>
        <input type="text" class="form-control" name="pending_query" value="{$allegropro_pending_filters.query|escape:'htmlall':'UTF-8'}" placeholder="checkoutFormId / kupujący / konto" />
      </div>
      <div class="ap-field">
        <label>Data od</label>
        <input type="date" class="form-control" name="pending_date_from" value="{$allegropro_pending_filters.date_from|escape:'htmlall':'UTF-8'}" />
      </div>
      <div class="ap-field">
        <label>Data do</label>
        <input type="date" class="form-control" name="pending_date_to" value="{$allegropro_pending_filters.date_to|escape:'htmlall':'UTF-8'}" />
      </div>
      <div class="ap-field">
        <label>Status modułu</label>
        <select name="pending_status_codes[]" class="form-control" multiple size="4">
          {foreach from=$allegropro_status_options key=code item=meta}
            <option value="{$code|escape:'htmlall':'UTF-8'}" {if in_array($code, $allegropro_pending_filters.selected_status_codes)}selected{/if}>{$meta.label|escape:'htmlall':'UTF-8'}</option>
          {/foreach}
        </select>
      </div>
      <div class="ap-actions">
        <button class="ap-btn ap-btn-primary" type="submit"><i class="icon icon-search"></i> Filtruj</button>
      </div>
    </form>

    <div class="ap-table-wrap" style="margin-top:12px;">
      <div class="ap-table-top">
        <h5>Oczekujące na przesyłkę</h5>
        <span class="ap-pill">{$allegropro_pending_pagination.total_rows|intval} pozycji</span>
      </div>
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Konto</th>
            <th>checkoutFormId</th>
            <th>Status Allegro</th>
            <th>Status modułu</th>
            <th>Kupujący</th>
            <th>Metoda</th>
            <th>Aktualizacja</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          {if empty($allegropro_pending_orders)}
            <tr><td colspan="8" class="text-center ap-muted">Brak zamówień bez przesyłki.</td></tr>
          {else}
            {foreach from=$allegropro_pending_orders item=o}
              <tr>
                <td>{$o.account_title|escape:'htmlall':'UTF-8'}</td>
                <td><code>{$o.checkout_form_id|escape:'htmlall':'UTF-8'}</code></td>
                <td><code>{$o.status|escape:'htmlall':'UTF-8'}</code></td>
                <td><span class="label label-{$o.module_status_class|default:'default'|escape:'htmlall':'UTF-8'}">{$o.module_status_label|escape:'htmlall':'UTF-8'}</span></td>
                <td>{if $o.buyer_login}{$o.buyer_login|escape:'htmlall':'UTF-8'}{else}<span class="ap-muted">—</span>{/if}</td>
                <td>{if $o.shipping_method_name}{$o.shipping_method_name|escape:'htmlall':'UTF-8'}{else}<span class="ap-muted">—</span>{/if}</td>
                <td>{$o.updated_at|date_format:"%d.%m.%Y %H:%M"}</td>
                <td>
                  <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}" style="display:inline;">
                    <input type="hidden" name="allegropro_create_shipment" value="1" />
                    <input type="hidden" name="id_allegropro_account" value="{$o.id_allegropro_account|intval}" />
                    <input type="hidden" name="checkout_form_id" value="{$o.checkout_form_id|escape:'htmlall':'UTF-8'}" />
                    <button class="ap-btn ap-btn-success js-modern-confirm" type="submit" data-confirm-title="Utworzyć przesyłkę?" data-confirm-message="Za chwilę utworzymy przesyłkę dla tego zamówienia i pobierzemy aktualne dane nadania. Czy chcesz kontynuować?"><i class="icon icon-plus"></i> Utwórz</button>
                  </form>
                </td>
              </tr>
            {/foreach}
          {/if}
        </tbody>
      </table>
      <div class="ap-pagination">
        <div class="ap-muted">Strona {$allegropro_pending_pagination.page|intval} / {$allegropro_pending_pagination.total_pages|intval}</div>
        <ul class="pagination" style="margin:0;">
          {if $allegropro_pending_pagination.page > 1}
            <li><a href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&pending_page={$allegropro_pending_pagination.page-1|intval}&labeled_page={$allegropro_labeled_pagination.page|intval}">«</a></li>
          {/if}
          {if $allegropro_pending_pagination.page < $allegropro_pending_pagination.total_pages}
            <li><a href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&pending_page={$allegropro_pending_pagination.page+1|intval}&labeled_page={$allegropro_labeled_pagination.page|intval}">»</a></li>
          {/if}
        </ul>
      </div>
    </div>
  </div>

  {* 4) Filtry i lista dla UTWORZONYCH *}
  <div class="ap-block">
    <h4>Filtry: przesyłki utworzone</h4>
    <form method="get" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="ap-filters-grid-created">
      <input type="hidden" name="controller" value="AdminAllegroProShipments" />
      <input type="hidden" name="token" value="{$smarty.get.token|escape:'htmlall':'UTF-8'}" />
      <input type="hidden" name="per_page" value="{$allegropro_per_page|intval}" />
      {foreach from=$allegropro_selected_accounts item=aid}
        <input type="hidden" name="filter_accounts[]" value="{$aid|intval}" />
      {/foreach}

      {* Zachowujemy filtry sekcji nieutworzonych *}
      <input type="hidden" name="pending_query" value="{$allegropro_pending_filters.query|escape:'htmlall':'UTF-8'}" />
      <input type="hidden" name="pending_date_from" value="{$allegropro_pending_filters.date_from|escape:'htmlall':'UTF-8'}" />
      <input type="hidden" name="pending_date_to" value="{$allegropro_pending_filters.date_to|escape:'htmlall':'UTF-8'}" />
      {foreach from=$allegropro_pending_filters.selected_status_codes item=sc}
        <input type="hidden" name="pending_status_codes[]" value="{$sc|escape:'htmlall':'UTF-8'}" />
      {/foreach}

      <div class="ap-field">
        <label>Szukaj</label>
        <input type="text" class="form-control" name="labeled_query" value="{$allegropro_labeled_filters.query|escape:'htmlall':'UTF-8'}" placeholder="checkoutFormId / kupujący / shipment / konto" />
      </div>
      <div class="ap-field">
        <label>Data od</label>
        <input type="date" class="form-control" name="labeled_date_from" value="{$allegropro_labeled_filters.date_from|escape:'htmlall':'UTF-8'}" />
      </div>
      <div class="ap-field">
        <label>Data do</label>
        <input type="date" class="form-control" name="labeled_date_to" value="{$allegropro_labeled_filters.date_to|escape:'htmlall':'UTF-8'}" />
      </div>
      <div class="ap-field">
        <label>Status modułu</label>
        <select name="labeled_status_codes[]" class="form-control" multiple size="4">
          {foreach from=$allegropro_status_options key=code item=meta}
            <option value="{$code|escape:'htmlall':'UTF-8'}" {if in_array($code, $allegropro_labeled_filters.selected_status_codes)}selected{/if}>{$meta.label|escape:'htmlall':'UTF-8'}</option>
          {/foreach}
        </select>
      </div>
      <div class="ap-field">
        <label>Status przesyłki</label>
        <select name="labeled_shipment_statuses[]" class="form-control" multiple size="4">
          {foreach from=$allegropro_shipment_status_options item=ss}
            <option value="{$ss.value|escape:'htmlall':'UTF-8'}" {if in_array($ss.value, $allegropro_labeled_filters.shipment_statuses)}selected{/if}>{$ss.label|escape:'htmlall':'UTF-8'}</option>
          {/foreach}
        </select>
      </div>
      <div class="ap-actions">
        <button class="ap-btn ap-btn-primary" type="submit"><i class="icon icon-search"></i> Filtruj</button>
      </div>
    </form>

    <div class="ap-table-wrap" style="margin-top:12px;">
      <div class="ap-table-top">
        <h5>Z przesyłką</h5>
        <span class="ap-pill">{$allegropro_labeled_pagination.total_rows|intval} pozycji</span>
      </div>
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Konto</th>
            <th>checkoutFormId</th>
            <th>Status Allegro</th>
            <th>Status modułu</th>
            <th>shipmentId</th>
            <th>Status przesyłki (moduł)</th>
            <th>Aktualizacja</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          {if empty($allegropro_labeled_orders)}
            <tr><td colspan="8" class="text-center ap-muted">Brak danych.</td></tr>
          {else}
            {foreach from=$allegropro_labeled_orders item=o}
              <tr>
                <td>{$o.account_title|escape:'htmlall':'UTF-8'}</td>
                <td><code>{$o.checkout_form_id|escape:'htmlall':'UTF-8'}</code></td>
                <td><code>{$o.status|escape:'htmlall':'UTF-8'}</code></td>
                <td><span class="label label-{$o.module_status_class|default:'default'|escape:'htmlall':'UTF-8'}">{$o.module_status_label|escape:'htmlall':'UTF-8'}</span></td>
                <td>{if $o.shipment_id}<code>{$o.shipment_id|escape:'htmlall':'UTF-8'}</code>{else}<span class="ap-muted">—</span>{/if}</td>
                <td><span class="label label-{$o.shipment_status_class|default:'default'|escape:'htmlall':'UTF-8'}">{$o.shipment_status_label|escape:'htmlall':'UTF-8'}</span></td>
                <td>{$o.updated_at|date_format:"%d.%m.%Y %H:%M"}</td>
                <td>
                  {if $o.shipment_id}
                    <a class="ap-btn ap-btn-light js-download-label" href="#" data-account="{$o.id_allegropro_account|intval}" data-checkout="{$o.checkout_form_id|escape:'htmlall':'UTF-8'}" data-shipment="{$o.shipment_id|escape:'htmlall':'UTF-8'}"><i class="icon icon-file"></i> Etykieta</a>
                  {else}
                    <span class="ap-muted">—</span>
                  {/if}
                </td>
              </tr>
            {/foreach}
          {/if}
        </tbody>
      </table>
      <div class="ap-pagination">
        <div class="ap-muted">Strona {$allegropro_labeled_pagination.page|intval} / {$allegropro_labeled_pagination.total_pages|intval}</div>
        <ul class="pagination" style="margin:0;">
          {if $allegropro_labeled_pagination.page > 1}
            <li><a href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&pending_page={$allegropro_pending_pagination.page|intval}&labeled_page={$allegropro_labeled_pagination.page-1|intval}">«</a></li>
          {/if}
          {if $allegropro_labeled_pagination.page < $allegropro_labeled_pagination.total_pages}
            <li><a href="{$admin_link|escape:'htmlall':'UTF-8'}&{$allegropro_query_base|escape:'htmlall':'UTF-8'}&pending_page={$allegropro_pending_pagination.page|intval}&labeled_page={$allegropro_labeled_pagination.page+1|intval}">»</a></li>
          {/if}
        </ul>
      </div>
    </div>
  </div>


  <div id="ap-toast-wrap" style="position:fixed; right:24px; top:84px; z-index:9999; display:flex; flex-direction:column; gap:10px;"></div>
  <div id="ap-confirm-backdrop" class="ap-modal-backdrop" aria-hidden="true">
    <div class="ap-modal" role="dialog" aria-modal="true" aria-labelledby="ap-confirm-title">
      <div class="ap-modal-head">
        <span class="ap-modal-icon"><i class="icon icon-question"></i></span>
        <h5 id="ap-confirm-title" class="ap-modal-title">Potwierdzenie</h5>
      </div>
      <div id="ap-confirm-message" class="ap-modal-body">Czy chcesz kontynuować?</div>
      <div class="ap-modal-actions">
        <button type="button" id="ap-confirm-cancel" class="ap-btn ap-btn-secondary">Anuluj</button>
        <button type="button" id="ap-confirm-ok" class="ap-btn ap-btn-primary">Tak, kontynuuj</button>
      </div>
    </div>
  </div>

  <script>
    (function() {
      var confirmState = {
        activeForm: null,
        activeButton: null
      };

      var confirmBackdrop = document.getElementById('ap-confirm-backdrop');
      var confirmTitle = document.getElementById('ap-confirm-title');
      var confirmMessage = document.getElementById('ap-confirm-message');
      var confirmBtnCancel = document.getElementById('ap-confirm-cancel');
      var confirmBtnOk = document.getElementById('ap-confirm-ok');

      function closeConfirmModal() {
        if (!confirmBackdrop) return;
        confirmBackdrop.classList.remove('ap-open');
        confirmBackdrop.setAttribute('aria-hidden', 'true');
        confirmState.activeForm = null;
        confirmState.activeButton = null;
      }

      function openConfirmModal(form, button) {
        if (!confirmBackdrop || !form || !button) {
          if (form) {
            form.submit();
          }
          return;
        }

        confirmState.activeForm = form;
        confirmState.activeButton = button;
        confirmTitle.textContent = button.getAttribute('data-confirm-title') || 'Potwierdzenie akcji';
        confirmMessage.textContent = button.getAttribute('data-confirm-message') || 'Czy na pewno chcesz kontynuować?';
        confirmBackdrop.classList.add('ap-open');
        confirmBackdrop.setAttribute('aria-hidden', 'false');
      }

      function apNotify(type, msg) {
        var wrap = document.getElementById('ap-toast-wrap');
        if (!wrap) return;
        var el = document.createElement('div');
        var bg = '#1f6fe0';
        if (type === 'error') bg = '#dc3545';
        if (type === 'success') bg = '#19a56f';
        el.style.cssText = 'min-width:320px; max-width:520px; padding:12px 14px; color:#fff; border-radius:10px; box-shadow:0 10px 28px rgba(0,0,0,.2); background:' + bg + '; font-size:13px;';
        el.textContent = msg;
        wrap.appendChild(el);
        setTimeout(function() {
          if (el && el.parentNode) el.parentNode.removeChild(el);
        }, 5200);
      }

      {if isset($confirmations) && $confirmations}
        {foreach from=$confirmations item=msg}
          apNotify('success', '{$msg|escape:'javascript':'UTF-8'}');
        {/foreach}
      {/if}
      {if isset($errors) && $errors}
        {foreach from=$errors item=msg}
          apNotify('error', '{$msg|escape:'javascript':'UTF-8'}');
        {/foreach}
      {/if}

      var links = document.querySelectorAll('.js-download-label');
      for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function(e) {
          e.preventDefault();
          var btn = this;
          if (btn.getAttribute('data-loading') === '1') {
            return;
          }

          var accountId = btn.getAttribute('data-account') || '';
          var checkout = btn.getAttribute('data-checkout') || '';
          var shipment = btn.getAttribute('data-shipment') || '';
          var originalHtml = btn.innerHTML;

          btn.setAttribute('data-loading', '1');
          btn.setAttribute('disabled', 'disabled');
          btn.innerHTML = '<span class="ap-spin"></span> Sprawdzam...';

          var url = '{$admin_link|escape:'javascript':'UTF-8'}'
            + '&action=downloadLabelCheck'
            + '&id_allegropro_account=' + encodeURIComponent(accountId)
            + '&checkout_form_id=' + encodeURIComponent(checkout)
            + '&shipment_id=' + encodeURIComponent(shipment);

          fetch(url, { credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
              if (!data || !data.ok || !data.download_url) {
                apNotify('error', (data && data.message) ? data.message : 'Nie udało się pobrać etykiety.');
                return;
              }

              window.open(data.download_url, '_blank');
            })
            .catch(function(){
              apNotify('error', 'Błąd komunikacji podczas przygotowania etykiety.');
            })
            .finally(function(){
              btn.removeAttribute('data-loading');
              btn.removeAttribute('disabled');
              btn.innerHTML = originalHtml;
            });
        });
      }

      var confirmButtons = document.querySelectorAll('.js-modern-confirm');
      for (var j = 0; j < confirmButtons.length; j++) {
        confirmButtons[j].addEventListener('click', function(e) {
          var form = this.form;
          if (!form) {
            return;
          }

          e.preventDefault();
          openConfirmModal(form, this);
        });
      }

      if (confirmBtnCancel) {
        confirmBtnCancel.addEventListener('click', function() {
          closeConfirmModal();
        });
      }

      if (confirmBackdrop) {
        confirmBackdrop.addEventListener('click', function(e) {
          if (e.target === confirmBackdrop) {
            closeConfirmModal();
          }
        });
      }

      if (confirmBtnOk) {
        confirmBtnOk.addEventListener('click', function() {
          if (!confirmState.activeForm) {
            closeConfirmModal();
            return;
          }

          var form = confirmState.activeForm;
          closeConfirmModal();
          form.submit();
        });
      }

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && confirmBackdrop && confirmBackdrop.classList.contains('ap-open')) {
          closeConfirmModal();
        }
      });
    })();
  </script>

</div>
