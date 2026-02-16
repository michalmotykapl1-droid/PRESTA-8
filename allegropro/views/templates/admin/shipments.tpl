
<div class="panel">
  <h3><i class="icon icon-truck"></i> Przesyłki (Wysyłam z Allegro)</h3>

  <form method="get" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="form-inline" style="margin-bottom:15px;">
    <input type="hidden" name="controller" value="AdminAllegroProShipments" />
    <input type="hidden" name="token" value="{$smarty.get.token|escape:'htmlall':'UTF-8'}" />

    <label style="margin-right:8px;">Konto:</label>
    <select name="id_allegropro_account" class="form-control" style="min-width:260px;">
      {foreach from=$allegropro_accounts item=a}
        <option value="{$a.id_allegropro_account|intval}" {if $a.id_allegropro_account==$allegropro_selected_account}selected{/if}>
          {$a.label|escape:'htmlall':'UTF-8'} {if $a.allegro_login}({$a.allegro_login|escape:'htmlall':'UTF-8'}){/if}
        </option>
      {/foreach}
    </select>

    <button class="btn btn-default" type="submit" style="margin-left:10px;"><i class="icon icon-refresh"></i> Odśwież widok</button>
  </form>

  <div class="alert alert-info">
    <div><strong>Delivery services w cache:</strong> {$allegropro_delivery_services_count|intval}</div>
    <div class="help-block">To mapowanie jest potrzebne do tworzenia przesyłek. Zrób to raz (albo gdy Allegro doda nowe usługi).</div>
    <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}" style="margin-top:8px;">
      <input type="hidden" name="allegropro_refresh_delivery_services" value="1" />
      <input type="hidden" name="id_allegropro_account" value="{$allegropro_selected_account|intval}" />
      <button class="btn btn-primary" type="submit"><i class="icon icon-refresh"></i> Odśwież delivery services</button>
    </form>
  </div>

  <div class="alert alert-warning">
    <div><strong>Naprawa danych historycznych (CUSTOM):</strong></div>
    <div class="help-block" style="margin-top:6px;">
      Jeśli część starszych rekordów ma puste <code>wza_shipment_uuid</code>, a w <code>shipment_id</code> jest identyfikator potrzebny dalej w module,
      możesz uzupełnić <code>wza_shipment_uuid = shipment_id</code> (tylko dla <code>size_details=CUSTOM</code>). 
    </div>
    <form method="post" action="{$admin_link|escape:'htmlall':'UTF-8'}" style="margin-top:8px;">
      <input type="hidden" name="allegropro_fix_custom_wza_uuid" value="1" />
      <input type="hidden" name="id_allegropro_account" value="{$allegropro_selected_account|intval}" />
      <button class="btn btn-default" type="submit" onclick="return confirm('Uzupełnić wza_shipment_uuid = shipment_id dla size_details=CUSTOM (konto)?');">
        <i class="icon icon-wrench"></i> Uzupełnij wza_shipment_uuid (CUSTOM)
      </button>
    </form>
  </div>

  <h4>Oczekujące na przesyłkę</h4>
  <table class="table">
    <thead>
      <tr>
        <th>checkoutFormId</th>
        <th>Status</th>
        <th>Kupujący</th>
        <th>Zaktualizowano</th>
        <th>Akcje</th>
      </tr>
    </thead>
    <tbody>
      {if empty($allegropro_orders_without_shipment)}
        <tr><td colspan="5" class="text-center text-muted">Brak zamówień bez przesyłki (w bazie).</td></tr>
      {else}
        {foreach from=$allegropro_orders_without_shipment item=o}
          <tr>
            <td><code>{$o.checkout_form_id|escape:'htmlall':'UTF-8'}</code></td>
            <td>{$o.status|escape:'htmlall':'UTF-8'}</td>
            <td>{if $o.buyer_login}{$o.buyer_login|escape:'htmlall':'UTF-8'}{else}<span class="text-muted">—</span>{/if}</td>
            <td>{$o.updated_at|date_format:"%d.%m.%Y (%H:%M)"}</td>
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

  <h4>Ostatnie zamówienia (z etykietą)</h4>
  <table class="table">
    <thead>
      <tr>
        <th>checkoutFormId</th>
        <th>Status</th>
        <th>shipmentId</th>
        <th>Akcje</th>
      </tr>
    </thead>
    <tbody>
      {if empty($allegropro_orders)}
        <tr><td colspan="4" class="text-center text-muted">Brak danych.</td></tr>
      {else}
        {foreach from=$allegropro_orders item=o}
          <tr>
            <td><code>{$o.checkout_form_id|escape:'htmlall':'UTF-8'}</code></td>
            <td>{$o.status|escape:'htmlall':'UTF-8'}</td>
            <td>{if $o.shipment_id}<code>{$o.shipment_id|escape:'htmlall':'UTF-8'}</code>{else}<span class="text-muted">—</span>{/if}</td>
            <td>
              {if $o.shipment_id}
                <a class="btn btn-default btn-xs" href="{$admin_link|escape:'htmlall':'UTF-8'}&action=downloadLabel&id_allegropro_account={$allegropro_selected_account|intval}&checkout_form_id={$o.checkout_form_id|escape:'url'}">
                  <i class="icon icon-file"></i> Pobierz etykietę PDF
                </a>
              {else}
                <span class="text-muted">—</span>
              {/if}
            </td>
          </tr>
        {/foreach}
      {/if}
    </tbody>
  </table>
</div>
