<div class="panel">
  <h3><i class="icon icon-shopping-cart"></i> Zamówienia (Baza danych modułu)</h3>

  <div class="row" style="margin-bottom:20px;">
    <div class="col-md-12">
      <div class="alert alert-info" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;border-radius:12px;border:1px solid #bfe3f6;background:#eef8fe;">
        <div>
          <strong>Konsola Importu:</strong> Pobieraj zamówienia z jednego lub wielu kont, ustaw limit i zakres dat.
        </div>
        <button class="btn btn-primary" id="btnOpenImportModal" style="min-width:320px;border-radius:10px;">
          <i class="icon icon-cloud-download"></i> Otwórz konsolę importu / aktualizacji
        </button>
      </div>
    </div>
  </div>

  <form method="get" action="{$admin_link|escape:'htmlall':'UTF-8'}" class="panel" style="padding:12px; border-radius:10px; margin-bottom:15px;">
    <input type="hidden" name="controller" value="AdminAllegroProOrders" />
    <input type="hidden" name="token" value="{$token|escape:'htmlall':'UTF-8'}" />

    <div class="row">
      <div class="col-md-3">
        <label>Konto</label>
        <select name="filter_account" class="form-control">
          <option value="0">Wszystkie konta</option>
          {foreach from=$allegropro_accounts item=a}
            <option value="{$a.id_allegropro_account|intval}" {if $allegropro_filters.id_allegropro_account == $a.id_allegropro_account}selected{/if}>
              {$a.label|escape:'htmlall':'UTF-8'}
            </option>
          {/foreach}
        </select>
      </div>
      <div class="col-md-2">
        <label>Data od</label>
        <input type="date" name="filter_date_from" value="{$allegropro_filters.date_from|escape:'htmlall':'UTF-8'}" class="form-control" />
      </div>
      <div class="col-md-2">
        <label>Data do</label>
        <input type="date" name="filter_date_to" value="{$allegropro_filters.date_to|escape:'htmlall':'UTF-8'}" class="form-control" />
      </div>
      <div class="col-md-2">
        <label>Dostawa</label>
        <input type="text" name="filter_delivery_method" value="{$allegropro_filters.delivery_method|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="np. InPost" />
      </div>
      <div class="col-md-1">
        <label>Status</label>
        <input type="text" name="filter_status" value="{$allegropro_filters.status|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="READY" />
      </div>
      <div class="col-md-2">
        <label>ID Allegro</label>
        <input type="text" name="filter_checkout_form_id" value="{$allegropro_filters.checkout_form_id|escape:'htmlall':'UTF-8'}" class="form-control" placeholder="checkout_form_id" />
      </div>
    </div>

    <div class="row" style="margin-top:10px;">
      <div class="col-md-2">
        <label>Na stronę</label>
        <select name="per_page" class="form-control">
          {foreach from=$allegropro_pagination.allowed_per_page item=pp}
            <option value="{$pp|intval}" {if $allegropro_pagination.per_page == $pp}selected{/if}>{$pp|intval}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-md-10" style="display:flex; align-items:flex-end; gap:8px;">
        <button type="submit" class="btn btn-primary"><i class="icon icon-search"></i> Filtruj</button>
        <a class="btn btn-default" href="{$admin_link|escape:'htmlall':'UTF-8'}"><i class="icon icon-eraser"></i> Wyczyść filtry</a>
      </div>
    </div>
  </form>

  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Konto</th>
        <th>Data Allegro</th>
        <th>Status</th>
        <th>Dostawa</th>
        <th>Kupujący (login/email)</th>
        <th>Imię i nazwisko</th>
        <th>Telefon</th>
        <th>Kwota</th>
        <th>ID Zamówienia</th>
        <th class="text-center">Akcje</th>
      </tr>
    </thead>
    <tbody>
      {if empty($allegropro_orders)}
        <tr><td colspan="10" class="text-center text-muted" style="padding:20px;">Brak danych dla wybranych filtrów.</td></tr>
      {else}
        {foreach from=$allegropro_orders item=o}
          <tr>
            <td><span class="label label-default">{$o.account_label|default:'-'|escape:'htmlall':'UTF-8'}</span></td>
            <td>{$o.updated_at_allegro|escape:'htmlall':'UTF-8'}</td>
            <td><span class="label label-info">{$o.status|escape:'htmlall':'UTF-8'}</span></td>
            <td style="font-size:11px;">
              {if isset($o.shipping_method_name) && $o.shipping_method_name}<strong>{$o.shipping_method_name|truncate:40:'...'}</strong>{else}<span class="text-muted">-</span>{/if}
            </td>
            <td>
              <strong>{$o.buyer_login|escape:'htmlall':'UTF-8'}</strong><br>
              <small>{$o.buyer_email|escape:'htmlall':'UTF-8'}</small>
            </td>
            <td>
              {$o.buyer_firstname|default:''|escape:'htmlall':'UTF-8'} {$o.buyer_lastname|default:''|escape:'htmlall':'UTF-8'}
            </td>
            <td>{$o.buyer_phone|default:'-'|escape:'htmlall':'UTF-8'}</td>
            <td><strong>{$o.total_amount|string_format:"%.2f"} {$o.currency}</strong></td>
            <td><code style="font-size:10px;">{$o.checkout_form_id}</code></td>
            <td class="text-center">
              <button class="btn btn-default btn-sm btn-details" data-id="{$o.checkout_form_id}">
                <i class="icon icon-list"></i> Szczegóły
              </button>
            </td>
          </tr>
          <tr id="details-{$o.checkout_form_id}" style="display:none; background-color:#f9f9f9;">
            <td colspan="10">
              <div class="details-content" style="padding:10px 20px;">
                <i class="icon icon-spinner icon-spin"></i> Ładowanie produktów...
              </div>
            </td>
          </tr>
        {/foreach}
      {/if}
    </tbody>
  </table>

  {assign var=currentPage value=$allegropro_pagination.page|intval}
  {assign var=totalPages value=$allegropro_pagination.total_pages|intval}
  {assign var=totalRows value=$allegropro_pagination.total_rows|intval}

  <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px; flex-wrap:wrap; gap:10px;">
    <div class="text-muted">
      Łącznie zamówień: <strong>{$totalRows}</strong>
    </div>

    <div>
      {if $currentPage > 1}
        <a class="btn btn-default btn-sm" href="{$admin_link|escape:'htmlall':'UTF-8'}&page={$currentPage-1|intval}&per_page={$allegropro_pagination.per_page|intval}&filter_account={$allegropro_filters.id_allegropro_account|intval}&filter_date_from={$allegropro_filters.date_from|escape:'url'}&filter_date_to={$allegropro_filters.date_to|escape:'url'}&filter_delivery_method={$allegropro_filters.delivery_method|escape:'url'}&filter_status={$allegropro_filters.status|escape:'url'}&filter_checkout_form_id={$allegropro_filters.checkout_form_id|escape:'url'}">
          &laquo; Poprzednia
        </a>
      {/if}

      <span class="btn btn-default btn-sm disabled">Strona {$currentPage} / {$totalPages}</span>

      {if $currentPage < $totalPages}
        <a class="btn btn-default btn-sm" href="{$admin_link|escape:'htmlall':'UTF-8'}&page={$currentPage+1|intval}&per_page={$allegropro_pagination.per_page|intval}&filter_account={$allegropro_filters.id_allegropro_account|intval}&filter_date_from={$allegropro_filters.date_from|escape:'url'}&filter_date_to={$allegropro_filters.date_to|escape:'url'}&filter_delivery_method={$allegropro_filters.delivery_method|escape:'url'}&filter_status={$allegropro_filters.status|escape:'url'}&filter_checkout_form_id={$allegropro_filters.checkout_form_id|escape:'url'}">
          Następna &raquo;
        </a>
      {/if}
    </div>
  </div>
</div>

<div id="importModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:9999; overflow:auto; padding:20px;">
  <div style="background:#ffffff; width:min(1080px,96vw); margin:18px auto; padding:0; border-radius:16px; box-shadow:0 24px 80px rgba(0,0,0,0.35); overflow:hidden; border:1px solid #dbe7f3;">

    <div style="padding:18px 24px; background:linear-gradient(120deg,#0ea5e9,#2563eb); color:#fff; display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0; font-size:26px; font-weight:700; color:#ffffff !important; background:transparent !important; border:none !important; text-shadow:0 1px 2px rgba(0,0,0,.25); display:flex; align-items:center; gap:10px;">
        <i class="icon icon-refresh" style="color:#fff;"></i>
        <span style="color:#ffffff;">Konsola Importu Allegro</span>
      </h3>
      <button type="button" class="close" style="color:#fff; opacity:.95; text-shadow:none; font-size:28px;" onclick="$('#importModal').hide();">&times;</button>
    </div>

    <div style="padding:20px 22px; background:#f8fafc;">
      <div id="step_config">
        <div style="border:1px solid #dce6f2; border-radius:14px; padding:16px; background:#fff; margin-bottom:14px; box-shadow:0 8px 20px rgba(30,64,175,0.05);">
          <div class="row" style="display:flex; flex-wrap:wrap; gap:14px 0;">
            <div class="col-md-6">
              <label style="font-weight:700; margin-bottom:8px; display:block;">Konta do pobierania</label>

              <div style="display:flex; gap:8px; margin-bottom:8px;">
                <button type="button" class="btn btn-default btn-xs" id="btnSelectAllAccounts">Zaznacz wszystkie</button>
                <button type="button" class="btn btn-default btn-xs" id="btnClearAllAccounts">Wyczyść</button>
              </div>

              <div id="account_checkbox_list" style="max-height:190px; overflow:auto; border:1px solid #d8e3ef; border-radius:10px; padding:10px; background:#f8fbff;">
                {foreach from=$allegropro_accounts item=a}
                  <label style="display:flex; align-items:flex-start; gap:10px; margin:0 0 8px 0; padding:8px 10px; border:1px solid #e5edf7; border-radius:8px; background:#fff; cursor:pointer;">
                    <input
                      type="checkbox"
                      name="import_account_ids[]"
                      class="import-account-checkbox"
                      value="{$a.id_allegropro_account|intval}"
                      {if $a.id_allegropro_account == $allegropro_selected_account}checked{/if}
                      style="margin-top:2px; width:16px; height:16px;"
                    />
                    <span>
                      <strong>{$a.label|escape:'htmlall':'UTF-8'}</strong>
                      {if $a.allegro_login}<span class="text-muted">({$a.allegro_login|escape:'htmlall':'UTF-8'})</span>{/if}
                    </span>
                  </label>
                {/foreach}
              </div>

              <small class="text-muted">Wybierz checkboxy kont, które chcesz przetworzyć.</small>
            </div>

            <div class="col-md-3">
              <label style="font-weight:700; margin-bottom:8px; display:block;">Tryb pobierania</label>
              <select id="import_scope" class="form-control" style="height:44px; border-radius:10px; border:1px solid #cfe0f1;">
                <option value="recent">Ostatnie (incremental)</option>
                <option value="history">Historia (zakres dat)</option>
              </select>
            </div>

            <div class="col-md-3">
              <label style="font-weight:700; margin-bottom:8px; display:block;">Ile zamówień na konto</label>
              <input type="number" min="1" max="1000" step="1" id="fetch_limit" class="form-control" value="100" style="height:44px; border-radius:10px; border:1px solid #cfe0f1;" />
              <small class="text-muted">Zakres 1-1000 (dla każdego konta osobno).</small>
            </div>
          </div>

          <div class="row" id="history_dates" style="display:none; margin-top:12px;">
            <div class="col-md-4">
              <label style="font-weight:700; margin-bottom:8px; display:block;">Data od</label>
              <input type="date" id="history_date_from" class="form-control" value="2024-11-01" style="height:44px; border-radius:10px; border:1px solid #cfe0f1;" />
            </div>
            <div class="col-md-4">
              <label style="font-weight:700; margin-bottom:8px; display:block;">Data do</label>
              <input type="date" id="history_date_to" class="form-control" value="2024-12-01" style="height:44px; border-radius:10px; border:1px solid #cfe0f1;" />
            </div>
            <div class="col-md-4" style="display:flex; align-items:flex-end; gap:8px; flex-wrap:wrap;">
              <button class="btn btn-default btn-sm" type="button" id="preset_last30">Ostatnie 30 dni</button>
              <button class="btn btn-default btn-sm" type="button" id="preset_this_month">Bieżący miesiąc</button>
            </div>
          </div>
        </div>

        <button class="btn btn-success btn-lg" id="btnStartProcess" style="border-radius:12px; padding:10px 26px; box-shadow:0 6px 14px rgba(34,197,94,.25);">
          <i class="icon icon-play"></i> Rozpocznij proces
        </button>
      </div>

      <div id="step_progress" style="display:none;">
        <div style="margin-bottom:10px;">
          <span class="label label-primary" id="run_summary">Przygotowanie...</span>
        </div>

        <div class="row" style="margin-bottom:10px;">
          <div class="col-md-4"><strong>Faza 1: Pobieranie z Allegro</strong></div>
          <div class="col-md-8"><span id="status_fetch" class="label label-default">Oczekuje...</span></div>
        </div>
        <div class="progress" style="height:16px; margin-bottom:15px; border-radius:99px;"><div id="bar_fetch" class="progress-bar progress-bar-info" style="width:0%;">0%</div></div>

        <div class="row" style="margin-bottom:10px;">
          <div class="col-md-4"><strong>Faza 2: Tworzenie Zamówień</strong></div>
          <div class="col-md-8"><span id="status_create" class="label label-default">Oczekuje...</span></div>
        </div>
        <div class="progress" style="height:16px; margin-bottom:15px; border-radius:99px;"><div id="bar_create" class="progress-bar progress-bar-warning" style="width:0%;">0%</div></div>

        <div class="row" style="margin-bottom:10px;">
          <div class="col-md-4"><strong>Faza 3: Aktualizacja Cen/Wysyłki</strong></div>
          <div class="col-md-8"><span id="status_fix" class="label label-default">Oczekuje...</span></div>
        </div>
        <div class="progress" style="height:16px; margin-bottom:15px; border-radius:99px;"><div id="bar_fix" class="progress-bar progress-bar-success" style="width:0%;">0%</div></div>

        <div style="background:#0f172a; color:#e2e8f0; font-family:monospace; height:280px; overflow-y:scroll; padding:10px; border:1px solid #1e293b; font-size:11px; border-radius:10px;" id="console_logs">
          <div class="text-muted">Gotowy do pracy...</div>
        </div>

        <div style="margin-top:15px; text-align:right; display:flex; justify-content:flex-end; gap:8px;">
          <button class="btn btn-default" id="btnCloseComplete" style="display:none;" onclick="location.reload()">ZAMKNIJ I ODŚWIEŻ</button>
        </div>
      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
  var IMPORT_CFG = {
    token: '{$token|escape:'javascript':'UTF-8'}',
    adminLink: '{$admin_link|escape:'javascript':'UTF-8'}'
  };
</script>
{literal}
<script>
var ImportManager = {
  token: IMPORT_CFG.token,
  controller: 'AdminAllegroProOrders',
  selectedAccounts: [],
  totalFetched: 0,
  totalProcessed: 0,

  log: function(msg, type='info') {
    var color = '#94a3b8';
    if(type==='error') color = '#f87171';
    if(type==='success') color = '#4ade80';
    if(type==='warning') color = '#facc15';

    var time = new Date().toLocaleTimeString();
    var html = '<div style="color:'+color+'">['+time+'] '+msg+'</div>';
    var box = $('#console_logs');
    box.append(html);
    box.scrollTop(box[0].scrollHeight);
  },

  getValidatedLimit: function() {
    var raw = parseInt($('#fetch_limit').val(), 10);
    if (isNaN(raw) || raw < 1) raw = 50;
    if (raw > 1000) raw = 1000;
    $('#fetch_limit').val(raw);
    return raw;
  },

  getSelectedAccountIds: function() {
    return $('input[name="import_account_ids[]"]:checked').map(function(){
      return $(this).val();
    }).get();
  },

  start: function() {
    this.selectedAccounts = this.getSelectedAccountIds();
    this.totalFetched = 0;
    this.totalProcessed = 0;

    if (!this.selectedAccounts.length) {
      this.log('Wybierz przynajmniej jedno konto.', 'error');
      return;
    }

    $('#step_config').hide();
    $('#step_progress').show();
    $('#run_summary').text('Konta: ' + this.selectedAccounts.length + ' | Start...');

    this.processAccountAt(0);
  },

  processAccountAt: function(idx) {
    if (idx >= this.selectedAccounts.length) {
      $('#status_fetch').removeClass('label-warning').addClass('label-success').text('Zakończono');
      $('#status_create').removeClass('label-warning').addClass('label-success').text('Zakończono');
      $('#status_fix').removeClass('label-warning').addClass('label-success').text('Zakończono');
      this.log('>>> WSZYSTKIE KONTA ZAKOŃCZONE. Pobrano łącznie: ' + this.totalFetched + ', przetworzono: ' + this.totalProcessed, 'success');
      $('#btnCloseComplete').show();
      $('#run_summary').text('Gotowe | Konta: ' + this.selectedAccounts.length + ' | Pobrane: ' + this.totalFetched + ' | Przetworzone: ' + this.totalProcessed);
      return;
    }

    var accountId = this.selectedAccounts[idx];
    var accountNo = (idx + 1) + '/' + this.selectedAccounts.length;
    $('#run_summary').text('Konto ' + accountNo + ' (ID ' + accountId + ')');
    this.log('=== START KONTO ' + accountNo + ' (ID ' + accountId + ') ===', 'warning');

    this.fetchForSingleAccount(accountId, (res) => {
      if (!res || !res.success) {
        this.log('Pominięto konto ID ' + accountId + ' z powodu błędu.', 'error');
        this.processAccountAt(idx + 1);
        return;
      }

      this.getPendingForSingleAccount(res.account_id, res.limit, res.fetched_ids || [], (pendingRes) => {
        if (!pendingRes || !pendingRes.success) {
          this.log('Błąd listy pending dla konta ID ' + accountId + '.', 'error');
          this.processAccountAt(idx + 1);
          return;
        }

        var list = pendingRes.ids || [];
        this.totalProcessed += list.length;
        this.log('Pending dla konta ID ' + accountId + ': ' + list.length, 'warning');

        this.processQueueCreate(list, 0, () => {
          this.processQueueFix(list, 0, () => {
            this.log('=== KONIEC KONTO ID ' + accountId + ' ===', 'success');
            this.processAccountAt(idx + 1);
          });
        });
      });
    });
  },

  fetchForSingleAccount: function(accountId, done) {
    var scope = $('#import_scope').val();
    var fetchLimit = this.getValidatedLimit();

    var payload = {
      scope: scope,
      id_allegropro_account: accountId,
      fetch_limit: fetchLimit
    };

    if (scope === 'history') {
      payload.date_from = $('#history_date_from').val();
      payload.date_to = $('#history_date_to').val();

      if (!payload.date_from || !payload.date_to) {
        this.log('W trybie historii musisz podać zakres dat.', 'error');
        done({ 'success': false });
        return;
      }
      if (new Date(payload.date_from) > new Date(payload.date_to)) {
        this.log('Data "od" nie może być późniejsza niż data "do".', 'error');
        done({ 'success': false });
        return;
      }
    }

    $('#status_fetch').removeClass('label-default').addClass('label-warning').text('Pobieranie...');
    $('#bar_fetch').css('width', '50%').text('50%');

    $.ajax({
      url: 'index.php?controller='+this.controller+'&token='+this.token+'&action=import_fetch&ajax=1',
      type: 'POST',
      data: payload,
      dataType: 'json',
      success: (res) => {
        if (res.success) {
          this.totalFetched += (res.count || 0);
          $('#bar_fetch').css('width', '100%').text('100%');
          $('#status_fetch').removeClass('label-warning').addClass('label-success').text('OK ('+(res.count||0)+')');
          this.log('Pobrano dla konta ID ' + accountId + ': ' + (res.count||0), 'success');
        } else {
          this.log('Błąd fetch konto ID ' + accountId + ': ' + res.message, 'error');
        }
        done(res);
      },
      error: () => {
        this.log('Błąd połączenia fetch konto ID ' + accountId, 'error');
        done({ 'success': false });
      }
    });
  },

  getPendingForSingleAccount: function(accountId, limit, fetchedIds, done) {
    $.ajax({
      url: 'index.php?controller='+this.controller+'&token='+this.token+'&action=import_get_pending&ajax=1',
      type: 'POST',
      dataType: 'json',
      data: {
        id_allegropro_account: accountId,
        limit: limit,
        fetched_ids: JSON.stringify(fetchedIds || [])
      },
      success: (res) => done(res),
      error: () => done({ 'success': false })
    });
  },

  processQueueCreate: function(list, index, onDone) {
    var total = list.length;
    if (index === 0) {
      $('#status_create').addClass('label-warning').text('Przetwarzanie...');
    }

    var percent = 0;
    if (total > 0) percent = Math.round((index / total) * 100);
    $('#bar_create').css('width', percent + '%').text(percent + '%');

    if (index >= total) {
      $('#bar_create').css('width', '100%').text('100%');
      $('#status_create').removeClass('label-warning').addClass('label-success').text('Zakończono');
      onDone();
      return;
    }

    var cfId = list[index];
    $.ajax({
      url: 'index.php?controller='+this.controller+'&token='+this.token+'&action=import_process_single&ajax=1',
      type: 'POST',
      data: { checkout_form_id: cfId, step: 'create' },
      dataType: 'json',
      success: (res) => {
        if (!res.success) this.log('Błąd create ' + cfId + ': ' + res.message, 'error');
        this.processQueueCreate(list, index + 1, onDone);
      },
      error: () => this.processQueueCreate(list, index + 1, onDone)
    });
  },

  processQueueFix: function(list, index, onDone) {
    var total = list.length;
    if (index === 0) {
      $('#status_fix').addClass('label-warning').text('Aktualizowanie...');
    }

    var percent = 0;
    if (total > 0) percent = Math.round((index / total) * 100);
    $('#bar_fix').css('width', percent + '%').text(percent + '%');

    if (index >= total) {
      $('#bar_fix').css('width', '100%').text('100%');
      $('#status_fix').removeClass('label-warning').addClass('label-success').text('Zakończono');
      onDone();
      return;
    }

    var cfId = list[index];
    $.ajax({
      url: 'index.php?controller='+this.controller+'&token='+this.token+'&action=import_process_single&ajax=1',
      type: 'POST',
      data: { checkout_form_id: cfId, step: 'fix' },
      dataType: 'json',
      success: (res) => {
        if (!res.success) this.log('Błąd fix ' + cfId + ': ' + res.message, 'error');
        this.processQueueFix(list, index + 1, onDone);
      },
      error: () => this.processQueueFix(list, index + 1, onDone)
    });
  }
};

$(document).ready(function() {
  function toggleHistoryFields() {
    if ($('#import_scope').val() === 'history') $('#history_dates').show();
    else $('#history_dates').hide();
  }

  function formatDate(dateObj) {
    var y = dateObj.getFullYear();
    var m = String(dateObj.getMonth() + 1).padStart(2, '0');
    var d = String(dateObj.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
  }

  $('#btnOpenImportModal').click(function() {
    toggleHistoryFields();
    $('#importModal').show();
  });

  $('#import_scope').change(toggleHistoryFields);

  $('#btnSelectAllAccounts').click(function() {
    $('input[name="import_account_ids[]"]').prop('checked', true);
  });

  $('#btnClearAllAccounts').click(function() {
    $('input[name="import_account_ids[]"]').prop('checked', false);
  });

  $('#preset_last30').click(function() {
    var now = new Date();
    var from = new Date();
    from.setDate(now.getDate() - 30);
    $('#history_date_from').val(formatDate(from));
    $('#history_date_to').val(formatDate(now));
  });

  $('#preset_this_month').click(function() {
    var now = new Date();
    var first = new Date(now.getFullYear(), now.getMonth(), 1);
    $('#history_date_from').val(formatDate(first));
    $('#history_date_to').val(formatDate(now));
  });

  $('#btnStartProcess').click(function() { ImportManager.start(); });

  $('.btn-details').click(function(e) {
    e.preventDefault();
    var cfId = $(this).data('id');
    var row = $('#details-'+cfId);
    if(row.is(':visible')) row.hide();
    else {
      row.show();
      var url = IMPORT_CFG.adminLink + '&action=get_order_details&checkout_form_id=' + cfId;
      fetch(url).then(r=>r.json()).then(data=>{
        var html = '<table class="table" style="background:#fff;"><thead><tr><th>Nazwa</th><th>SKU</th><th>EAN</th><th>Ilość</th><th>Cena</th><th>Mapowanie</th></tr></thead><tbody>';
        if (data.length === 0) html += '<tr><td colspan="6" class="text-danger">BRAK DANYCH</td></tr>';
        else {
          data.forEach(function(item) {
            var matchInfo = item.id_product > 0 ? '<span class="label label-success">OK (ID: '+item.id_product+')</span>' : '<span class="label label-danger">BRAK</span>';
            html += '<tr><td>' + item.name + '</td><td>' + (item.reference_number || '-') + '</td><td>' + (item.ean || '-') + '</td><td><strong>' + item.quantity + ' szt.</strong></td><td>' + item.price + '</td><td>' + matchInfo + '</td></tr>';
          });
        }
        html += '</tbody></table>';
        row.find('.details-content').html(html);
      });
    }
  });
});
</script>
{/literal}
