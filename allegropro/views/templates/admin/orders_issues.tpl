<div class="panel">
  <h3 style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
    <span><i class="icon icon-warning-sign"></i> Zamówienia — Do wyjaśnienia</span>
    <span class="text-muted" style="font-size:12px;">Lista pozycji z błędami pobierania danych z Allegro (np. 404/403/500).</span>
  </h3>

  {* NAV TABS *}
  <ul class="nav nav-tabs" style="margin:10px 0 15px 0;">
    <li class="{if $allegropro_view == 'orders'}active{/if}"><a href="{$admin_link|escape:'htmlall':'UTF-8'}">Zamówienia</a></li>
    <li class="{if $allegropro_view == 'issues'}active{/if}">
      <a href="{$admin_link|escape:'htmlall':'UTF-8'}&view=issues">
        Do wyjaśnienia
        {if $allegropro_issues_badge_count|intval > 0}
          <span class="badge" style="background:#d9534f;">{$allegropro_issues_badge_count|intval}</span>
        {/if}
      </a>
    </li>
  </ul>

  {* SUMMARY BOXES *}
  <div class="row" style="margin-bottom:14px;">
    <div class="col-md-3">
      <div class="panel" style="border-radius:14px;border:1px solid #e6eaf0;box-shadow:0 6px 18px rgba(0,0,0,.04);">
        <div class="text-muted" style="font-weight:700;">Pozycje do wyjaśnienia</div>
        <div style="font-size:26px;font-weight:900;">{$allegropro_issues_summary.count|default:0|intval}</div>
        <div class="text-muted" style="font-size:12px;">Wpisów billing: <strong>{$allegropro_issues_summary.billing_rows|default:0|intval}</strong></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="panel" style="border-radius:14px;border:1px solid #e6eaf0;box-shadow:0 6px 18px rgba(0,0,0,.04);">
        <div class="text-muted" style="font-weight:700;">Opłaty pobrane</div>
        <div style="font-size:26px;font-weight:900;color:#c9302c;">{$allegropro_issues_summary.fees_taken|default:0|string_format:"%.2f"} zł</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="panel" style="border-radius:14px;border:1px solid #e6eaf0;box-shadow:0 6px 18px rgba(0,0,0,.04);">
        <div class="text-muted" style="font-weight:700;">Zwroty / korekty</div>
        <div style="font-size:26px;font-weight:900;color:#2e7d32;">{$allegropro_issues_summary.corrections|default:0|string_format:"%.2f"} zł</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="panel" style="border-radius:14px;border:1px solid #e6eaf0;box-shadow:0 6px 18px rgba(0,0,0,.04);">
        <div class="text-muted" style="font-weight:700;">Saldo opłat</div>
        {assign var=net value=$allegropro_issues_summary.net|default:0}
        <div style="font-size:26px;font-weight:900;{if $net < 0}color:#c9302c;{else}color:#2e7d32;{/if}">{$net|string_format:"%.2f"} zł</div>
      </div>
    </div>
  </div>

  {* FILTERS *}
  <form method="get" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="panel" style="padding:14px;border-radius:14px;margin-bottom:14px;border:1px solid #d9e2ec;background:#fff;">
    <input type="hidden" name="controller" value="AdminAllegroProOrders" />
    <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}" />
    <input type="hidden" name="view" value="issues" />

    <div class="row">
      <div class="col-md-4">
        <label style="font-weight:700;margin-bottom:6px;">Konto</label>
        <select name="filter_account" class="form-control" style="border-radius:10px;">
          <option value="0">Wszystkie konta</option>
          {foreach from=$allegropro_accounts item=a}
            <option value="{$a.id_allegropro_account|intval}" {if $allegropro_issues_filters.account_id|intval == $a.id_allegropro_account|intval}selected{/if}>
              {$a.label|escape:'htmlall':'UTF-8'}
            </option>
          {/foreach}
        </select>
      </div>
      <div class="col-md-5">
        <label style="font-weight:700;margin-bottom:6px;">Szukaj (order_id / opis błędu)</label>
        <input type="text" name="filter_query" value="{$allegropro_issues_filters.query|default:''|escape:'htmlall':'UTF-8'}" class="form-control" style="border-radius:10px;" placeholder="np. 9705a290... albo 404 / not found" />
      </div>
      <div class="col-md-3" style="display:flex;align-items:flex-end;justify-content:flex-end;gap:8px;">
        <button class="btn btn-primary" type="submit" style="border-radius:10px;min-width:160px;"><i class="icon icon-filter"></i> Filtruj</button>
        <a class="btn btn-default" href="{$admin_link|escape:'htmlall':'UTF-8'}&view=issues" style="border-radius:10px;min-width:120px;"><i class="icon icon-eraser"></i> Wyczyść</a>
      </div>
    </div>
  </form>

  <table class="table table-bordered table-striped" style="background:#fff;">
    <thead>
      <tr>
        <th style="width:120px;">Konto</th>
        <th>Order ID (Allegro)</th>
        <th style="width:80px;">Kod</th>
        <th>Opis błędu</th>
        <th style="width:140px;">Ostatnia próba</th>
        <th style="width:80px;">Próby</th>
        <th style="width:120px;" class="text-right">Opłaty</th>
        <th style="width:120px;" class="text-right">Zwroty</th>
        <th style="width:120px;" class="text-right">Saldo</th>
        <th style="width:110px;" class="text-center">Akcje</th>
      </tr>
    </thead>
    <tbody>
      {if empty($allegropro_issues)}
        <tr><td colspan="10" class="text-center text-muted" style="padding:20px;">Brak pozycji do wyjaśnienia dla wybranych filtrów.</td></tr>
      {else}
        {foreach from=$allegropro_issues item=it}
          <tr>
            <td><span class="label label-default">{$it.account_label|escape:'htmlall':'UTF-8'}</span></td>
            <td>
              <code style="font-size:11px;">{$it.order_id|escape:'htmlall':'UTF-8'}</code>
              <button type="button" class="btn btn-default btn-xs" style="margin-left:6px;" onclick="navigator.clipboard && navigator.clipboard.writeText('{$it.order_id|escape:'javascript':'UTF-8'}');"><i class="icon icon-copy"></i></button>
            </td>
            <td><span class="label label-danger">{$it.last_code|default:'-'|escape:'htmlall':'UTF-8'}</span></td>
            <td style="font-size:12px;">{$it.last_error|default:'-'|escape:'htmlall':'UTF-8'}</td>
            <td style="font-size:12px;">{$it.last_attempt_at|escape:'htmlall':'UTF-8'}</td>
            <td class="text-center"><strong>{$it.attempts|intval}</strong></td>
            <td class="text-right" style="color:#c9302c;"><strong>{$it.fees_taken|string_format:"%.2f"} zł</strong></td>
            <td class="text-right" style="color:#2e7d32;"><strong>{$it.corrections|string_format:"%.2f"} zł</strong></td>
            {assign var=netrow value=$it.net}
            <td class="text-right" style="{if $netrow < 0}color:#c9302c;{else}color:#2e7d32;{/if}"><strong>{$netrow|string_format:"%.2f"} zł</strong></td>
            <td class="text-center">
              <button class="btn btn-default btn-sm btn-issue-details" data-order="{$it.order_id|escape:'htmlall':'UTF-8'}" data-account="{$it.id_allegropro_account|intval}">
                <i class="icon icon-list"></i> Szczegóły
              </button>
            </td>
          </tr>
          <tr id="issue-details-{$it.id_allegropro_account|intval}-{$it.order_id|escape:'htmlall':'UTF-8'}" style="display:none;background:#f9fafb;">
            <td colspan="10">
              <div class="issue-details-content" style="padding:10px 20px;">
                <i class="icon icon-spinner icon-spin"></i> Ładowanie pozycji billing...
              </div>
            </td>
          </tr>
        {/foreach}
      {/if}
    </tbody>
  </table>

</div>

<script type="text/javascript">
  var ISSUE_CFG = {
    adminLink: '{$admin_link|escape:'javascript':'UTF-8'}',
    token: '{$token|escape:'javascript':'UTF-8'}'
  };
</script>

{literal}
<script>
$(function(){
  $('.btn-issue-details').on('click', function(e){
    e.preventDefault();
    var orderId = $(this).data('order');
    var accId = $(this).data('account');
    var rowId = 'issue-details-' + accId + '-' + orderId;
    var $row = $('#' + CSS.escape(rowId));
    if ($row.is(':visible')) { $row.hide(); return; }
    $row.show();
    var $box = $row.find('.issue-details-content');

    // jeśli już załadowane
    if ($box.data('loaded') === 1) { return; }

    var url = ISSUE_CFG.adminLink + '&action=get_issue_billing_details&id_allegropro_account=' + encodeURIComponent(accId) + '&order_id=' + encodeURIComponent(orderId);
    fetch(url)
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (!data || !data.success) {
          $box.html('<div class="text-danger"><strong>Błąd:</strong> ' + (data && data.message ? data.message : 'nieznany') + '</div>');
          return;
        }

        var rows = data.rows || [];
        var html = '';
        html += '<div class="row" style="margin-bottom:10px;">';
        html += '  <div class="col-md-12"><strong>Billing entries dla order_id:</strong> <code>' + orderId + '</code></div>';
        html += '</div>';

        html += '<table class="table table-bordered" style="background:#fff;">';
        html += '<thead><tr>';
        html += '<th>Data</th><th>Typ</th><th>Offer ID</th><th>Offer name / opis</th><th class="text-right">Kwota</th>';
        html += '</tr></thead><tbody>';

        if (rows.length === 0) {
          html += '<tr><td colspan="5" class="text-muted">Brak wpisów billing dla tego order_id.</td></tr>';
        } else {
          rows.forEach(function(r){
            var offerId = r.offer_id || '';
            var offerName = r.offer_name || '';
            var typeName = r.type_name || '';
            var label = offerName !== '' ? offerName : (typeName !== '' ? typeName : '-');
            html += '<tr>';
            html += '<td style="white-space:nowrap;">' + (r.occurred_at || '') + '</td>';
            html += '<td>' + (typeName || '-') + '</td>';
            html += '<td><code style="font-size:11px;">' + (offerId || '-') + '</code></td>';
            html += '<td>' + label + '</td>';
            html += '<td class="text-right"><strong>' + (r.value_amount || '0.00') + ' ' + (r.value_currency || 'PLN') + '</strong></td>';
            html += '</tr>';
          });
        }
        html += '</tbody></table>';

        // stopka z sumami
        if (data.totals) {
          html += '<div class="text-muted" style="font-size:12px;">';
          html += 'Opłaty pobrane: <strong style="color:#c9302c;">' + (data.totals.fees_taken || '0.00') + ' zł</strong> · ';
          html += 'Zwroty/korekty: <strong style="color:#2e7d32;">' + (data.totals.corrections || '0.00') + ' zł</strong> · ';
          html += 'Saldo: <strong>' + (data.totals.net || '0.00') + ' zł</strong>'; 
          html += '</div>';
        }

        $box.data('loaded', 1);
        $box.html(html);
      })
      .catch(function(err){
        $box.html('<div class="text-danger"><strong>Błąd:</strong> ' + (err && err.message ? err.message : err) + '</div>');
      });
  });
});
</script>
{/literal}
