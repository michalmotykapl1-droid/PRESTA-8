<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> Mapowanie Kolumn: {$wholesaler->name}
    </div>

    {if isset($error_message)}
        <div class="alert alert-danger">{$error_message}</div>
    {else}
        <form action="{$current_url}&action=saveMapping" method="post" class="form-horizontal">
            <input type="hidden" name="id_wholesaler" value="{$wholesaler->id}">
            
            <div class="alert alert-info">
                Pobrano nagłówki z pliku CSV. Przypisz odpowiednie kolumny hurtowni do pól sklepu.
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th width="30%">Pole w PrestaShop</th>
                        <th width="30%">Kolumna w CSV (Hurtownia)</th>
                        <th width="40%">Opcje / Logika</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$ps_fields key=field_key item=field_label}
                    <tr>
                        <td>
                            <strong>{$field_label}</strong><br>
                            <small class="text-muted">Kod pola: {$field_key}</small>
                        </td>
                        <td>
                            <select name="mapping[{$field_key}][csv]" class="fixed-width-xl">
                                <option value="">-- Ignoruj to pole --</option>
                                {foreach from=$csv_headers item=header}
                                    <option value="{$header}" {if isset($saved_mapping[$field_key]) && $saved_mapping[$field_key] == $header}selected{/if}>
                                        {$header}
                                    </option>
                                {/foreach}
                            </select>
                        </td>
                        <td>
                            {if $field_key == 'reference' || $field_key == 'ean13'}
                                <span class="badge badge-warning">Identyfikator</span>
                            {/if}
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>

            <div class="panel-footer">
                <button type="submit" name="submitMapping" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> Zapisz Mapowanie
                </button>
                <a href="{$back_link}" class="btn btn-default">
                    <i class="process-icon-cancel"></i> Anuluj
                </a>
            </div>
        </form>
    {/if}
</div>