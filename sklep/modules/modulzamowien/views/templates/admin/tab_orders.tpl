{* Główny Widok Zakładki 3: DO ZAMÓWIENIA *}

<style>
    /* STYLE OGÓLNE */
    .mz-modern-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #eef0f2; transition: transform 0.2s, box-shadow 0.2s; margin-bottom: 20px; overflow: hidden; }
    .mz-modern-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
    .mz-card-header { padding: 15px; font-weight: 700; font-size: 15px; text-transform: uppercase; border-bottom: 1px solid #f0f0f0; letter-spacing: 0.5px; display: flex; justify-content: space-between; align-items: center; }
    .mz-card-body { padding: 15px; }
    .mz-price-tag { font-size: 22px; font-weight: 800; color: #2c3e50; }
    .mz-btn-copy { background: linear-gradient(135deg, #007aff 0%, #005ecb 100%); color: white; border: none; border-radius: 6px; padding: 12px; font-weight: 600; width: 100%; text-transform: uppercase; letter-spacing: 1px; transition: opacity 0.2s; margin-top: 10px; }
    .mz-btn-copy:hover { opacity: 0.9; color: white; }
    
    .mz-table-clean th { background: #f8f9fa; color: #6c757d; text-transform: uppercase; font-size: 11px; border-bottom: 2px solid #e9ecef !important; }
    .mz-table-clean td { vertical-align: middle !important; padding: 12px 8px !important; border-color: #f1f3f5 !important; }
    .mz-badge-wms { background: #ebfbee; color: #2b8a3e; border: 1px solid #c3e6cb; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
</style>

{if isset($orders_grouped)}
    
    <script>
        var history_save_url = "{$ajax_history_save_url}";
        var fix_save_url = "{$ajax_save_fix_url}";
        var fix_get_url = "{$ajax_get_fix_url}";
    </script>

    {* 1. Panel: Do zebrania z magazynu (A_MAG) *}
    {include file='./tab_orders/internal_picks.tpl'}

    {* --- LISTY PROBLEMÓW / BRAKÓW --- *}
    {include file='./tab_orders/partial_list.tpl'}
    {include file='./tab_orders/not_found_panel.tpl'}
    {include file='./tab_orders/no_stock_panel.tpl'}

    {* --- STATYSTYKI I WYBÓR STRATEGII --- *}
    <div style="margin-top: 30px;">
        {include file='./tab_orders/stats.tpl'}
    </div>

    <div style="margin-top: 10px; margin-bottom: 20px;">
        {include file='./tab_orders/strategy_selector.tpl'}
    </div>

    <h3 style="margin: 10px 0 20px 0; font-weight: 300; border-bottom: 1px solid #eee; padding-bottom: 10px;">
        <i class="icon-truck"></i> Wybierz Dostawcę i Skopiuj Zamówienie
    </h3>

    {include file='./tab_orders/supplier_tiles.tpl'}

    <hr style="border-top: 2px solid #eee; margin: 40px 0;">

    <h3 style="margin-bottom: 20px; font-weight: 300;">
        <i class="icon-list-alt"></i> Szczegóły Zamówień (Podgląd)
    </h3>

    {include file='./tab_orders/orders_list.tpl'}

{else}
    <div class="alert alert-info" style="margin-top:20px; text-align:center; padding: 40px;">
        <i class="icon-refresh" style="font-size: 40px; margin-bottom: 20px; color:#aaa;"></i><br>
        Kliknij przycisk <b>"ANALIZUJ ZAMÓWIENIA"</b> (Krok 2), aby załadować listę.
    </div>
{/if}

{* --- MODAL DO WYSZUKIWANIA EXTRA --- *}
{* ZMIANA: Usunięto z-index, dodano overflow-y *}
<div class="modal fade" id="extraSearchModal" role="dialog" aria-hidden="true" style="overflow-y: auto;">
  <div class="modal-dialog modal-lg" style="width: 90%; max-width: 1200px;">
    <div class="modal-content" style="border-radius: 8px; box-shadow: 0 5px 25px rgba(0,0,0,0.3);">
      
      {* Nagłówek Modala *}
      <div class="modal-header" style="background: #6c757d; color: white; border-top-left-radius: 8px; border-top-right-radius: 8px;">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="color:white; opacity:0.8;">&times;</button>
        <h4 class="modal-title" style="font-weight: bold;">
            <i class="icon-search"></i> ZNAJDŹ ZAMIENNIK (EXTRA)
        </h4>
      </div>

      {* PASEK INFORMACYJNY *}
      <div id="searchTargetInfo" style="background: #fff3cd; color: #856404; padding: 15px 20px; border-bottom: 1px solid #ffeeba; display: none;">
          <div style="display: flex; align-items: center; justify-content: space-between;">
              <div style="flex: 1; margin-right: 20px;">
                  <span style="font-size: 11px; text-transform: uppercase; color: #997404; font-weight: bold; display: block; margin-bottom: 4px;">Szukasz zamiennika dla:</span>
                  <span id="targetName" style="font-size: 15px; font-weight: bold; display:block; line-height: 1.2;">-</span>
                  <span id="targetEan" style="font-size: 12px; font-family: monospace; background: rgba(255,255,255,0.5); padding: 2px 5px; border-radius: 4px; color: #666; margin-top: 4px; display:inline-block;">-</span>
              </div>
              <div style="text-align: right; min-width: 120px;">
                  <span style="font-size: 11px; text-transform: uppercase; color: #997404; font-weight: bold; display: block;">BRAKUJĄCA ILOŚĆ:</span>
                  <span id="targetQty" style="font-size: 24px; font-weight: 800; color: #d9534f;">0</span> <span style="font-size: 12px; font-weight:bold;">szt.</span>
              </div>
          </div>
      </div>

      {* Ciało Modala *}
      <div class="modal-body" id="extraModalBody" style="background: #f4f6f9; min-height: 400px; padding: 20px;">
          <div class="text-center text-muted" style="padding-top: 50px;">
              <i class="icon-refresh icon-spin icon-3x"></i><br>Ładowanie wyszukiwarki...
          </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">ZAMKNIJ OKNO (ESC)</button>
      </div>
    </div>
  </div>
</div>

<script>
var $originalExtraContainer = null;
var $currentFixingEan = null;

function applyFixStatus(ean) {
    if (!ean) return;
    // Obsłuż wszystkie wystąpienia tego EAN (różne sekcje mogą mieć ten sam produkt)
    $('tr[data-fix-ean="' + ean + '"]').each(function() {
        var $row = $(this);
        $row.css('background-color', '#dff0d8');
        $row.find('.fix-status-icon[data-fix-ean="' + ean + '"]').show();
        $row.find('.btn-search-fix[data-fix-ean="' + ean + '"]').hide();
        var $actionCell = $row.find('.action-cell');
        // Jeśli w akcji nie ma ikony, dołóż etykietę (fallback)
        if (!$actionCell.find('.fix-status-icon').length && !$actionCell.find('.label-success').length) {
            $actionCell.html('<span class="label label-success" style="font-size:11px; padding:4px 6px;"><i class="icon-ok"></i> WYMIENIONO</span>');
        }
    });
}

function saveFixStatus(ean) {
    if (!ean || typeof fix_save_url === 'undefined' || !fix_save_url) {
        applyFixStatus(ean);
        return;
    }
    $.ajax({
        url: fix_save_url,
        type: 'POST',
        dataType: 'json',
        data: { ean: ean },
        success: function(res) {
            if (res && res.success) {
                applyFixStatus(ean);
            } else {
                // nawet jeśli backend się wywali, UX ma zostać
                applyFixStatus(ean);
            }
        },
        error: function() {
            applyFixStatus(ean);
        }
    });
}

function loadFixStatuses() {
    if (typeof fix_get_url === 'undefined' || !fix_get_url) return;
    $.ajax({
        url: fix_get_url,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res && res.success && res.data) {
                $.each(res.data, function(ean, val) {
                    if (val) applyFixStatus(ean);
                });
            }
        }
    });
}

function searchInExtra(ean, name, missingQty) {
    var query = ean;
    if (!query || query.length < 3) { query = name; }
    var qtyDisplay = missingQty;
    if (!qtyDisplay || qtyDisplay == 0) { qtyDisplay = "?"; }

    $currentFixingEan = ean;

    $('#targetName').text(name);
    $('#targetEan').text(ean ? ean : 'Brak EAN');
    $('#targetQty').text(qtyDisplay);
    $('#searchTargetInfo').show();

    var $tabLink = $('.nav-tabs a').filter(function() {
        var text = $(this).text().toUpperCase();
        return text.indexOf("EXTRA") > -1 || text.indexOf("DODATKI") > -1;
    }).first();

    if ($tabLink.length) {
        var targetId = $tabLink.attr('href');
        $originalExtraContainer = $(targetId);

        if ($originalExtraContainer.length) {
            var $content = $originalExtraContainer.children().detach();
            $('#extraModalBody').empty().append($content);

            $('#extraSearchModal').modal({
                backdrop: 'static',
                keyboard: true
            });

            setTimeout(function(){
                var $input = $('#extraModalBody input[type="text"]').first();
                var $specInput = $('#extraModalBody input[name="extra_search_query"]');
                if($specInput.length) $input = $specInput;

                if ($input.length) {
                    $input.val(query);
                    $input.focus();
                    var $btn = $input.next().find('button');
                    if (!$btn.length) $btn = $input.parent().find('button');
                    if (!$btn.length) $btn = $input.closest('form').find('button');

                    if ($btn.length) { $btn.click(); } 
                    else { 
                        var e = jQuery.Event("keypress");
                        e.which = 13; 
                        $input.trigger(e); 
                    }
                }
            }, 300);
        } else {
            alert('Błąd: Nie znaleziono kontenera treści zakładki Extra.');
        }
    } else {
        alert('Błąd: Nie znaleziono zakładki Extra w menu.');
    }
}

$(document).ready(function() {
    
    // --- ROZWIĄZANIE OSTATECZNE (THE ULTIMATE FIX) ---
    // Ten kod naprawia problem "szarego ekranu" i braku scrolla po zamknięciu zagnieżdżonego okna.
    
    $(document).on('hidden.bs.modal', function () {
        // Sprawdź, czy są jeszcze jakieś otwarte okna
        if ($('.modal:visible').length) {
            // Jeśli tak, to znaczy że zamknęliśmy tylko jedno z nich.
            // MUSIMY przywrócić klasę 'modal-open' do body, bo Bootstrap ją usunął.
            $('body').addClass('modal-open');
            
            // Opcjonalnie: popraw focus na ostatnim otwartym oknie
            $('.modal:visible').last().focus();
        }
    });

    // --- ZARZĄDZANIE TREŚCIĄ ZAKŁADKI ---
    $('#extraSearchModal').on('hidden.bs.modal', function (e) {
        // Oddaj treść tylko wtedy, gdy zamykamy TO KONKRETNE okno
        // i nie ma już innych otwartych okien (dla pewności)
        if (e.target.id === 'extraSearchModal') {
            if ($originalExtraContainer !== null) {
                var $content = $('#extraModalBody').children().detach();
                $originalExtraContainer.append($content);
            }
            $currentFixingEan = null;
        }
    });

    // --- LOGIKA "WYMIENIONO" (ZIELONY PTASZEK) ---
    // Ładowanie statusów z bazy (na wypadek odświeżenia widoku / przełączania zakładek)
    loadFixStatuses();

    $('#extraModalBody').on('click', 'button, a', function() {
        var txt = $(this).text().toUpperCase();
        if (txt.indexOf("DODAJ") > -1 || $(this).find('.icon-plus').length > 0 || $(this).hasClass('add-extra-btn')) {
            if ($currentFixingEan) {
                saveFixStatus($currentFixingEan);
            }
        }
    });

});
</script>