<div class="panel allegropro-shipments-panel">
  <style>
    .allegropro-shipments-panel .ap-toolbar {
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      align-items:flex-end;
      margin-bottom:16px;
      padding:12px;
      background:#f8fafc;
      border:1px solid #dde7f0;
      border-radius:8px;
    }
    .allegropro-shipments-panel .ap-toolbar .form-group { margin:0; min-width:160px; }
    .allegropro-shipments-panel .ap-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; margin:14px 0 18px; }
    .allegropro-shipments-panel .ap-card {
      border:1px solid #d9e5f1;
      border-radius:8px;
      padding:12px;
      background:#fff;
      box-shadow:0 2px 8px rgba(15,23,42,.04);
    }
    .allegropro-shipments-panel .ap-card .ap-label { color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:.02em; }
    .allegropro-shipments-panel .ap-card .ap-value { font-size:21px; font-weight:700; margin-top:4px; }
    .allegropro-shipments-panel .ap-table-wrap { border:1px solid #dde7f0; border-radius:8px; overflow:hidden; margin-bottom:20px; }
    .allegropro-shipments-panel .ap-table-wrap h4 { margin:0; padding:12px 14px; background:#f8fafc; border-bottom:1px solid #dde7f0; }
    .allegropro-shipments-panel .table { margin-bottom:0; }
    .allegropro-shipments-panel .label { font-size:11px; }
    .allegropro-shipments-panel .ap-pagination { padding:10px 14px; border-top:1px solid #e5edf5; background:#fcfdff; }
    .allegropro-shipments-panel .ap-pagination .pagination { margin:0; }
    .allegropro-shipments-panel .ap-muted { color:#7c8da1; }
  </style>

  <h3><i class="icon icon-truck"></i> Przesyłki (Wysyłam z Allegro)</h3>

  <form method="get" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="ap-toolbar">
    <input type="hidden" name="controller" value="AdminAllegroProShipments" />
    <input type="hidden" name="token" value="{$smarty.get.token|escape:'htmlall':'UTF-8'}" />

    <div class="form-group">
      <label>Konto</label>
      <select name="id_allegropro_account" class="form-control">
        {foreach from=$allegropro_accounts item=a}
          <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account==$allegropro_selected_account}selected{/if}>
            {$a.label|escape:'htmlall':'UTF-8'} {if $a.allegro_login}({$a.allegro_login|escape:'htmlall':'UTF-8'}){/if}
          </option>
        {/foreach}
      </select>
    </div>

    <div class="form-group">
      <label>Szukaj</label>
      <input type="text" class="form-control" name="filter_query" value="{$allegropro_filters.query|escape:'htmlall':'UTF-8'}" placeholder="checkoutFormId / buyer / shipment" />
    </div>

    <div class="form-group">
      <label>Od</label>
      <input type="date" class="form-control" name="filter_date_from" value="{$allegropro_filters.date_from|escape:'htmlall':'UTF-8'}" />
    </div>

    <div class="form-group">
      <label>Do</label>
      <input type="date" class="form-control" name="filter_date_to" value="{$allegropro_filters.date_to|escape:'htmlall':'UTF-8'}" />
    </div>

    <div class="form-group">
      <label>Status modułu</label>
      <select name="filter_status_codes[]" class="form-control" multiple size="4">
        {foreach from=$allegropro_status_options key=code item=meta}
          <option value="{$code|escape:'htmlall':'UTF-8'}" {if in_array($code, $allegropro_filters.selected_status_codes)}selected{/if}>
            {$meta.label|escape:'htmlall':'UTF-8'}
          </option>
        {/foreach}
      </select>
    </div>

    <div class="form-group">
      <label>Status przesyłki</label>
      <select name="filter_shipment_statuses[]" class="form-control" multiple size="4">
        {foreach from=$allegropro_shipment_status_options item=s}
          <option value="{$s|escape:'htmlall':'UTF-8'}" {if in_array($s, $allegropro_filters.shipment_statuses)}selected{/if}>{$s|escape:'htmlall':'UTF-8'}</option>
        {/foreach}
      </select>
    </div>

    <div class="form-group" style="min-width:100px;">
      <label>Na stronę</label>
      <select name="per_page" class="form-control">
        <option value="10" {if $allegropro_filters.per_page == 10}selected{/if}>10</option>
        <option value="25" {if $allegropro_filters.per_page == 25}selected{/if}>25</option>
        <option value="50" {if $allegropro_filters.per_page == 50}selected{/if}>50</option>
        <option value="100" {if $allegropro_filters.per_page == 100}selected{/if}>100</option>
      </select>
    </div>

    <div class="form-group">
      <button class="btn btn-primary" type="submit"><i class="icon icon-search"></i> Filtruj</button>
      <a class="btn btn-default" href="{$admin_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$allegropro_selected_account|intval}"><i class="icon icon-undo"></i> Wyczyść</a>
    </div>
  </form>

  <div class="ap-cards">
    <div class="ap-card">
      <div class="ap-label">Delivery services w cache</div>
      <div class="ap-value">{$allegropro_delivery_services_count|intval}</div>
    </div>
    <div class="ap-card">
      <div class="ap-label">Bez przesyłki</div>
      <div class="ap-value">{$allegropro_pending_pagination.total_rows|intval}</div>
    </div>
    <div class="ap-card">
      <div class="ap-label">Z przesyłką</div>
      <div class="ap-value">{$allegropro_labeled_pagination.total_rows|intval}</div>
    </div>
  </div>

  <div class="alert alert-info">
    <div class="help-block">To mapowanie jest potrzebne do tworzenia przesyłek. Odśwież, gdy Allegro doda nowe usługi.</div>
    <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}" style="margin-top:8px;">
      <input type="hidden" name="allegropro_refresh_delivery_services" value="1" />
      <input type="hidden" name="id_allegropro_account" value="{$allegropro_selected_account|intval}" />
      <button class="btn btn-primary" type="submit"><i class="icon icon-refresh"></i> Odśwież delivery services</button>
    </form>
  </div>

  <div class="alert alert-warning">
    <div><strong>Naprawa danych historycznych (CUSTOM)</strong></div>
    <div class="help-block" style="margin-top:6px;">
      Uzupełnia <code>wza_shipment_uuid = shipment_id</code> dla rekordów <code>size_details=CUSTOM</code>.
    </div>
    <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}" style="margin-top:8px;">
      <input type="hidden" name="allegropro_fix_custom_wza_uuid" value="1" />
      <input type="hidden" name="id_allegropro_account" value="{$allegropro_selected_account|intval}" />
      <button class="btn btn-default" type="submit" onclick="return confirm('Uzupełnić wza_shipment_uuid = shipment_id dla size_details=CUSTOM?');">
        <i class="icon icon-wrench"></i> Uzupełnij wza_shipment_uuid (CUSTOM)
      </button>
    </form>
  </div>

  <div class="ap-table-wrap">
    <h4>Oczekujące na przesyłkę</h4>
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>checkoutFormId</th>
          <th>Status Allegro</th>
          <th>Status modułu</th>
          <th>Kupujący</th>
          <th>Metoda dostawy</th>
          <th>Zaktualizowano</th>
          <th>Akcje</th>
        </tr>
      </thead>
      <tbody>
        {if empty($allegropro_pending_orders)}
          <tr><td colspan="7" class="text-center ap-muted">Brak zamówień bez przesyłki.</td></tr>
        {else}
          {foreach from=$allegropro_pending_orders item=o}
            <tr>
              <td><code>{$o.checkout_form_id|escape:'htmlall':'UTF-8'}</code></td>
              <td><code>{$o.status|escape:'htmlall':'UTF-8'}</code></td>
              <td><span class="label label-{$o.module_status_class|default:'default'|escape:'htmlall':'UTF-8'}">{$o.module_status_label|escape:'htmlall':'UTF-8'}</span></td>
              <td>{if $o.buyer_login}{$o.buyer_login|escape:'htmlall':'UTF-8'}{else}<span class="ap-muted">—</span>{/if}</td>
              <td>{if $o.shipping_method_name}{$o.shipping_method_name|escape:'htmlall':'UTF-8'}{else}<span class="ap-muted">—</span>{/if}</td>
              <td>{$o.updated_at|date_format:"%d.%m.%Y %H:%M"}</td>
              <td>
                <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}" style="display:inline;">
                  <input type="hidden" name="allegropro_create_shipment" value="1" />
                  <input type="hidden" name="id_allegropro_account" value="{$allegropro_selected_account|intval}" />
                  <input type="hidden" name="checkout_form_id" value="{$o.checkout_form_id|escape:'htmlall':'UTF-8'}" />
                  <button class="btn btn-success btn-xs" type="submit" onclick="return confirm('Utworzyć przesyłkę dla tego zamówienia?');">
                    <i class="icon icon-plus"></i> Utwórz przesyłkę
                  </button>
                </form>
              </td>
            </tr>
          {/foreach}
        {/if}
      </tbody>
    </table>

    <div class="ap-pagination">
      <ul class="pagination">
        {assign var=pp value=$allegropro_pending_pagination}
        {if $pp.page > 1}
          <li><a href="{$admin_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$allegropro_selected_account|intval}&filter_query={$allegropro_filters.query|escape:'url'}&filter_date_from={$allegropro_filters.date_from|escape:'url'}&filter_date_to={$allegropro_filters.date_to|escape:'url'}&per_page={$allegropro_filters.per_page|intval}&pending_page={$pp.page-1}&labeled_page={$allegropro_labeled_pagination.page|intval}">«</a></li>
        {/if}
        <li class="active"><span>Strona {$pp.page|intval} / {$pp.total_pages|intval}</span></li>
        {if $pp.page < $pp.total_pages}
          <li><a href="{$admin_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$allegropro_selected_account|intval}&filter_query={$allegropro_filters.query|escape:'url'}&filter_date_from={$allegropro_filters.date_from|escape:'url'}&filter_date_to={$allegropro_filters.date_to|escape:'url'}&per_page={$allegropro_filters.per_page|intval}&pending_page={$pp.page+1}&labeled_page={$allegropro_labeled_pagination.page|intval}">»</a></li>
        {/if}
      </ul>
    </div>
  </div>

  <div class="ap-table-wrap">
    <h4>Ostatnie zamówienia (z przesyłką)</h4>
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>checkoutFormId</th>
          <th>Status Allegro</th>
          <th>Status modułu</th>
          <th>shipmentId</th>
          <th>Status przesyłki</th>
          <th>Zaktualizowano</th>
          <th>Akcje</th>
        </tr>
      </thead>
      <tbody>
        {if empty($allegropro_labeled_orders)}
          <tr><td colspan="7" class="text-center ap-muted">Brak danych.</td></tr>
        {else}
          {foreach from=$allegropro_labeled_orders item=o}
            <tr>
              <td><code>{$o.checkout_form_id|escape:'htmlall':'UTF-8'}</code></td>
              <td><code>{$o.status|escape:'htmlall':'UTF-8'}</code></td>
              <td><span class="label label-{$o.module_status_class|default:'default'|escape:'htmlall':'UTF-8'}">{$o.module_status_label|escape:'htmlall':'UTF-8'}</span></td>
              <td>{if $o.shipment_id}<code>{$o.shipment_id|escape:'htmlall':'UTF-8'}</code>{else}<span class="ap-muted">—</span>{/if}</td>
              <td>{if $o.shipment_status}<code>{$o.shipment_status|escape:'htmlall':'UTF-8'}</code>{else}<span class="ap-muted">—</span>{/if}</td>
              <td>{$o.updated_at|date_format:"%d.%m.%Y %H:%M"}</td>
              <td>
                {if $o.shipment_id}
                  <a class="btn btn-default btn-xs" href="{$admin_link|escape:'htmlall':'UTF-8'}&action=downloadLabel&id_allegropro_account={$allegropro_selected_account|intval}&checkout_form_id={$o.checkout_form_id|escape:'url'}">
                    <i class="icon icon-file"></i> Pobierz etykietę PDF
                  </a>
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
      <ul class="pagination">
        {assign var=lp value=$allegropro_labeled_pagination}
        {if $lp.page > 1}
          <li><a href="{$admin_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$allegropro_selected_account|intval}&filter_query={$allegropro_filters.query|escape:'url'}&filter_date_from={$allegropro_filters.date_from|escape:'url'}&filter_date_to={$allegropro_filters.date_to|escape:'url'}&per_page={$allegropro_filters.per_page|intval}&pending_page={$allegropro_pending_pagination.page|intval}&labeled_page={$lp.page-1}">«</a></li>
        {/if}
        <li class="active"><span>Strona {$lp.page|intval} / {$lp.total_pages|intval}</span></li>
        {if $lp.page < $lp.total_pages}
          <li><a href="{$admin_link|escape:'htmlall':'UTF-8'}&id_allegropro_account={$allegropro_selected_account|intval}&filter_query={$allegropro_filters.query|escape:'url'}&filter_date_from={$allegropro_filters.date_from|escape:'url'}&filter_date_to={$allegropro_filters.date_to|escape:'url'}&per_page={$allegropro_filters.per_page|intval}&pending_page={$allegropro_pending_pagination.page|intval}&labeled_page={$lp.page+1}">»</a></li>
        {/if}
      </ul>
    </div>
  </div>
</div>
