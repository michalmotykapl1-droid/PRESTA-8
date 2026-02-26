{* Widok Zakładki: WIRTUALNY MAGAZYN (DB) *}
{* Wersja: FIX DELETE + HIGHLIGHT OLD/EXTRA + NEW CONTROLLER BRIDGE *}

<input type="hidden" id="mz_main_controller_url_fixed" value="{$main_controller_url}&ajax=1">
{* NOWY INPUT: Wskazuje na osobny kontroler Reception *}
<input type="hidden" id="mz_reception_controller_url" value="{$link->getAdminLink('AdminModulReception')}&ajax=1">

{* 1. Obliczenia sum *}
{assign var="total_surplus_qty" value=0}
{assign var="total_surplus_val" value=0}
{assign var="total_positions" value=0}

{if isset($surplus_data) && $surplus_data}
    {assign var="total_positions" value=$surplus_data|@count}
    {foreach from=$surplus_data item=row}
        {assign var="total_surplus_qty" value=$total_surplus_qty + $row.qty}
        {assign var="total_surplus_val" value=$total_surplus_val + $row.val_net}
    {/foreach}
{/if}

<div class="row" style="margin-bottom: 15px;">
    {* LEWA STRONA: PODSUMOWANIE *}
    <div class="col-md-7">
        <div class="alert alert-success" style="margin-bottom: 0; height: 84px; display: flex; flex-direction: column; justify-content: center;">
            <div style="font-size:1.1em;">
                <i class="icon-inbox" style="margin-right:10px;"></i>
                <strong>WIRTUALNY MAGAZYN (PICK STÓŁ)</strong> - Produkty potwierdzone, oczekujące na przypisanie.
            </div>
            <div style="margin-top: 8px; font-size: 14px; border-top: 1px solid #c9e2b3; padding-top: 5px;">
                <span class="badge" style="font-size: 14px; background-color: #007aff; padding: 3px 8px; margin-right:10px;">POZYCJE: {$total_positions}</span>
                <span class="badge badge-success" style="font-size: 14px; background-color: #3c763d; padding: 3px 8px; margin-right:10px;">SZTUKI: {$total_surplus_qty}</span>
                <span class="badge badge-warning" style="font-size: 14px; background-color: #f0ad4e; padding: 3px 8px;">WARTOŚĆ: {$total_surplus_val|string_format:"%.2f"} zł</span>
            </div>
        </div>
    </div>
    
    {* ŚRODEK: PRZYCISK SKANERA *}
    <div class="col-md-2" style="padding-left: 5px; padding-right: 5px;">
        <button type="button" id="btn-open-search-modal" class="btn btn-info btn-block" style="height: 84px; white-space: normal; font-weight:bold; font-size:15px; background-color: #17a2b8; border-color: #17a2b8; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <i class="icon-barcode" style="font-size:2em; margin-bottom: 5px;"></i>
            <span>SKANER PRZYJĘĆ<br><small>(SZUKAJ / SKANUJ)</small></span>
        </button>
    </div>
    
    {* PRAWA STRONA: CZYSZCZENIE *}
    <div class="col-md-3">
        <form action="{$surplus_form_action}" method="post" onsubmit="return confirm('Czy na pewno usunąć wszystkie pozycje z Wirtualnego Magazynu? (Operacja nieodwracalna)');">
            <button type="submit" name="submitClearSurplus" class="btn btn-danger btn-block" style="height: 84px; white-space: normal; font-weight:bold; font-size:15px; background-color: #d9534f; border-color: #d43f3a; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <i class="icon-trash" style="font-size:2em; margin-bottom: 5px;"></i>
                <span>WYCZYŚĆ LISTĘ<br><small>(USUŃ NADWYŻKI)</small></span>
            </button>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover" id="surplus_table">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="width: 120px;">EAN</th>
                <th>Nazwa Produktu</th>
                <th class="text-center" style="width: 100px;">Cena Netto</th>
                <th class="text-center" style="width: 100px;">ILOŚĆ</th>
                <th class="text-center" style="width: 100px;">Wartość</th>
                <th class="text-center" style="width: 150px;">Aktualizacja</th>
                <th class="text-center" style="width: 120px;">AKCJA</th>
            </tr>
        </thead>
        <tbody>
            {if isset($surplus_data) && count($surplus_data) > 0}
                {assign var="now_date" value=$smarty.now|date_format:"%Y-%m-%d"}
                {foreach from=$surplus_data item=item}
                    {assign var="item_date" value=$item.date_upd|date_format:"%Y-%m-%d"}
                    
                    {assign var="row_style" value=""}
                    {assign var="is_old" value=false}
                    {assign var="is_extra" value=false}

                    {if $item.name|strstr:"[EXTRA]"}
                        {assign var="is_extra" value=true}
                        {assign var="row_style" value="background-color: #e1bee7; color: #4a148c; font-weight:bold;"}
                    {/if}

                    {if $item_date < $now_date}
                        {assign var="is_old" value=true}
                        {assign var="row_style" value="background-color: #f2dede; color: #a94442;"}
                    {/if}

                    <tr data-ean="{$item.ean}" data-name="{$item.name|escape:'html':'UTF-8'|lower}" style="{$row_style}">
                        <td>{$item.ean}</td>
                        <td>
                            {if $is_old}<span class="label label-danger" style="margin-right:5px;">STARE ({$item_date})</span>{/if}
                            {if $is_extra}<span class="label" style="background-color:#9c27b0; color:white; margin-right:5px;">REZERWACJA EXTRA</span>{/if}
                            {$item.name|replace:'[EXTRA] ':''}
                        </td>
                        <td class="text-center text-muted">{if $item.price_net > 0}{$item.price_net|string_format:"%.2f"} zł{else}-{/if}</td>
                        <td class="text-center">
                            <span style="font-size:1.4em; font-weight:bold; color:{if $is_old}#a94442{elseif $is_extra}#4a148c{else}#007aff{/if};">{$item.qty}</span>
                        </td>
                        <td class="text-center">{if $item.val_net > 0}<strong>{$item.val_net|string_format:"%.2f"} zł</strong>{else}-{/if}</td>
                        <td class="text-center text-muted"><small>{$item.date_upd}</small></td>
                        <td class="text-center">
                            <div class="btn-group-vertical" style="width: 100%;">
                                <button type="button" class="btn btn-success btn-sm btn-quick-receive" data-ean="{$item.ean}" style="margin-bottom: 2px;" title="Szybkie przyjęcie na stan"><i class="icon-plus"></i> DODAJ</button>
                                <button type="button" class="btn btn-default btn-sm btn-delete-surplus" data-id="{$item.id_surplus}" data-ean="{$item.ean}" data-name="{$item.name|escape:'html':'UTF-8'}" title="Usuń z listy"><i class="icon-trash" style="color:red;"></i></button>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            {else}
                <tr><td colspan="7" class="text-center" style="padding: 30px; color: #999;"><i class="icon-check-circle" style="font-size: 3em;"></i><br><br>Brak nadwyżek. Stół jest czysty.</td></tr>
            {/if}
        </tbody>
    </table>
</div>

{include file="./reception_modal.tpl"}
{include file="./search_modal.tpl"}

{* SKRYPT AJAX DO USUWANIA (Teraz korzysta z nowego controllera) *}
<script>
$(document).ready(function() {
    $('.btn-delete-surplus').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var id_surplus = parseInt(btn.attr('data-id')); 
        var ean = btn.attr('data-ean');
        var name = btn.attr('data-name');
        var row = btn.closest('tr');

        var confirmMsg = 'Czy na pewno usunąć tę pozycję z Wirtualnego Magazynu?';
        if (!ean) confirmMsg += '\n(Produkt bez EAN: "' + name + '")';
        
        if (!confirm(confirmMsg)) return;
        btn.prop('disabled', true).html('<i class="icon-spin icon-spinner"></i>');
        
        // FIX: Pobieramy URL z nowego inputa RECEPTION
        var url = $('#mz_reception_controller_url').val() + '&action=delete_surplus_item';
        
        $.ajax({
            url: url,
            type: 'POST',
            data: { id_surplus: id_surplus, ean: ean, name: name },
            dataType: 'json',
            success: function(res) {
                if (res.success) { row.fadeOut(500, function() { $(this).remove(); }); } 
                else { btn.prop('disabled', false).html('<i class="icon-trash" style="color:red;"></i>'); alert('Błąd: ' + res.msg); }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="icon-trash" style="color:red;"></i>');
                alert('Błąd połączenia.');
            }
        });
    });
});
</script>