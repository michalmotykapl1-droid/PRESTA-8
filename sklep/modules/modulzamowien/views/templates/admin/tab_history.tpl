<script>
    var history_delete_url = "{$ajax_history_delete_url}";
</script>

<div style="margin-top:15px;">
    {if isset($history_data) && count($history_data) > 0}
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Data Złożenia</th>
                    <th>Dostawca</th>
                    <th>Pracownik</th>
                    <th style="text-align:center;">Ilość Poz.</th>
                    <th style="text-align:right;">Kwota</th>
                    <th style="width:150px; text-align:center;">Opcje</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$history_data item=h}
                    <tr id="history_row_{$h.id_history}">
                        <td>{$h.id_history}</td>
                        <td>{$h.date_add}</td>
                        <td style="font-weight:bold;">{$h.supplier_name}</td>
                        <td>{$h.employee_name}</td>
                        <td style="text-align:center;">{$h.items_count}</td>
                        <td style="text-align:right; color:green; font-weight:bold;">{$h.total_cost} zł</td>
                        <td style="text-align:center;">
                            <button class="btn btn-default btn-xs" onclick="toggleDetails({$h.id_history})">
                                <i class="icon-eye"></i> Pokaż
                            </button>
                            <button class="btn btn-danger btn-xs" onclick="deleteHistory({$h.id_history})">
                                <i class="icon-trash"></i> Usuń
                            </button>
                        </td>
                    </tr>
                    <tr id="details_row_{$h.id_history}" style="display:none; background:#f9f9f9;">
                        <td colspan="7">
                            <div style="padding:10px;">
                                <strong>Szczegóły:</strong>
                                <ul style="margin-top:5px; list-style:none; padding-left:0;">
                                    {assign var="items" value=$h.order_data|json_decode:true}
                                    {foreach from=$items item=item}
                                        <li style="border-bottom:1px solid #eee; padding:3px 0;">
                                            <span style="display:inline-block; width:130px; font-family:monospace;">{$item.ean}</span>
                                            <span style="font-weight:bold; color:red;">{$item.qty} szt.</span> 
                                            - 
                                            {* WYKRYWANIE EXTRA NA PODSTAWIE NAZWY (Dla starej i nowej metody) *}
                                            {if $item.name|strstr:"[EXTRA]"}
                                                <span class="label" style="background-color:#9c27b0; color:white; font-size:10px; margin-right:5px;">EXTRA</span>
                                                {$item.name|replace:'[EXTRA] ':''}
                                            {else}
                                                {$item.name}
                                            {/if}
                                        </li>
                                    {/foreach}
                                </ul>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <div class="alert alert-warning">Brak historii.</div>
    {/if}
</div>

<script>
function toggleDetails(id) {
    var row = document.getElementById('details_row_' + id);
    if (row.style.display === 'none') row.style.display = 'table-row';
    else row.style.display = 'none';
}
function deleteHistory(id) {
    if (!confirm("Usunąć trwale?")) return;
    $.ajax({
        url: history_delete_url,
        type: 'POST',
        data: { id_history: id },
        dataType: 'json',
        success: function(res) {
            if(res.success) { $('#history_row_' + id).fadeOut(); $('#details_row_' + id).fadeOut(); }
            else alert("Błąd usuwania.");
        }
    });
}
</script>