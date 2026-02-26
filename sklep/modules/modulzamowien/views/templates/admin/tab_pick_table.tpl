<script type="text/javascript">
    var table_ajax_url = "{$ajax_table_pick_url}";
</script>

<div class="row">
    <div class="col-md-9">
        <div style="padding: 20px; background: #ff9900; color: white; margin-top:5px; text-align:center; border-radius: 5px;">
            <label style="font-size: 18px;">SKANUJ ZBIERANIE ZE STOŁU:</label><br>
            <input type="text" id="scanner_input_table" class="form-control" style="width: 50%; margin: 10px auto; font-size: 24px; text-align: center;" placeholder="Skanuj produkt ze stołu..." autofocus>
        </div>
    </div>
    <div class="col-md-3">
        <form action="{$current_url}&token={$token}" method="post" onsubmit="return confirm('Czy na pewno wyczyścić całą listę zadań? (Tylko po zakończeniu dnia!)');">
            <button type="submit" name="submitClearQueue" class="btn btn-default btn-block" style="height:100px; font-weight:bold;">
                <i class="icon-trash"></i> WYCZYŚĆ LISTĘ ZADAŃ<br>(Koniec Dnia)
            </button>
        </form>
    </div>
</div>

<table class="table table-bordered" style="margin-top:15px;" id="table_pick_table">
    <thead>
        <tr style="background: #fcf8e3;">
            <th style="width: 60px;">FOTO</th>
            <th>PRODUKT ZE STOŁU</th>
            <th style="width: 130px;">EAN</th>
            {* ZMIANA NAGŁÓWKA: Było Dostępne -> ZADANIE (Całość) *}
            <th style="width: 90px; text-align:center; background:#eee;">ZADANIE</th>
            {* ZMIANA NAGŁÓWKA: Było Do Wzięcia -> POZOSTAŁO *}
            <th style="width: 100px; text-align:center;">POZOSTAŁO</th>
            <th style="width: 120px; text-align:center;">ZEBRANO</th>
            <th style="width: 100px; text-align:center;">STATUS</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$picktable_data item=row}
            {* Obliczamy ile zostało do zebrania *}
            {assign var="qty_remaining" value=$row.qty_to_pick - $row.qty_picked}
            
            <tr class="table-picking-row {if $row.is_table_collected}success{/if}" 
                data-ean="{$row.ean}" 
                data-sku="{$row.ean}"  
                data-needed="{$row.qty_to_pick}"
                {if $row.is_table_collected}style="background-color:#dff0d8;"{/if}
            >
                <td>
                    {if isset($row.image_id)}
                        <img src="{$link->getImageLink($row.link_rewrite, $row.image_id, 'small_default')}" width="50" style="border:1px solid #ccc;">
                    {/if}
                </td>
                <td style="vertical-align:middle;">
                    <span style="font-size: 14px; font-weight:bold;">{$row.name}</span>
                    <br><span style="font-size:10px; color:#888;">Dodano: {$row.date_add}</span>
                </td>
                <td style="font-size: 12px; font-family: monospace; vertical-align:middle;">{$row.ean}</td>
                
                {* KOLUMNA 1: CAŁKOWITA ILOŚĆ ZADANIA (Pobrane ze stołu) *}
                <td style="font-size: 16px; font-weight:bold; text-align:center; background:#eee; vertical-align:middle; color:#555;">
                    {$row.qty_to_pick}
                </td>
                
                {* KOLUMNA 2: ILE JESZCZE ZOSTAŁO DO SKANOWANIA *}
                <td style="font-size: 24px; font-weight:bold; text-align:center; color:#ff9900; vertical-align:middle;">
                    {if $qty_remaining > 0}{$qty_remaining}{else}<span style="color:green;">0</span>{/if}
                </td>
                
                <td style="vertical-align:middle; text-align:center;">
                    {if $row.is_table_collected}
                        <span class="label label-success" style="font-size:14px;">GOTOWE</span>
                    {else}
                        <input type="number" class="form-control val-collected-table" value="{$row.qty_picked}" min="0" max="{$row.qty_to_pick}" 
                                style="text-align:center; font-size:20px; font-weight:bold; color:green; height: 40px;">
                    {/if}
                </td>
                
                <td style="vertical-align:middle; text-align:center;">
                    {if $row.is_table_collected}
                        <button class="btn btn-warning btn-xs btn-undo-table" style="width:100%; height:35px;" title="Cofnij"><i class="icon-undo"></i> COFNIJ</button>
                    {else}
                        <button type="button" class="btn btn-success btn-confirm-table btn-lg btn-block">OK</button>
                    {/if}
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>