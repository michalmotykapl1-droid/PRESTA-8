<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> Zarządzanie Wariantami Produktów PRO
    </div>
    <div class="panel-body">
        <p>
            <strong>Znaleziono produktów bez wagi:</strong>
            <span class="badge badge-info">{$products_count}</span>
        </p>
        <form method="post">
            <button type="submit" name="save_weights" class="btn btn-success">
                <i class="icon-save"></i> Zapisz zaproponowane wagi
            </button>
            <a href="{$scan_url}" class="btn btn-primary">
                <i class="icon-refresh"></i> Skanuj ponownie
            </a>
        </form>
        <br>
        
        <div class="table-scroll-container">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID produktu</th>
                        <th>Nazwa</th>
                        <th>EAN</th>
                        <th>SKU</th>
                        <th>Propozycja wagi (kg)</th>
                        <th>Akcje</th> {* Nowa kolumna na przyciski zapisu *}
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$products item=p}
                        <tr>
                            <td>{$p.id_product}</td>
                            <td>{$p.name}</td>
                            <td>{$p.ean}</td>
                            <td>{$p.sku}</td>
                            <td>
                                {* Sama propozycja wagi (input) bez przycisku w tej kolumnie *}
                                <input type="number" step="0.001" name="display_weight_{$p.id_product}" value="{$p.suggested|string_format:'%.3f'}" class="form-control" style="width: 100px;" disabled> {* Zmiana name i dodanie disabled *}
                                {if $p.suggested === null}
                                    <span class="text-danger">Brak propozycji</span>
                                {/if}
                            </td>
                            <td>
                                {* Cały formularz z inputem i przyciskiem w kolumnie "Akcje" *}
                                <form method="post" class="form-inline form-inline-action-edit"> {* Nowa klasa dla stylizacji *}
                                    <input type="hidden" name="id_product_to_save" value="{$p.id_product}">
                                    {* Input do edycji wagi w formularzu Akcji *}
                                    {if $p.suggested !== null}
                                        <input type="number" step="0.001" name="single_weight" value="{$p.suggested|string_format:'%.3f'}" class="form-control input-action-weight"> {* Dodana klasa *}
                                    {else}
                                        <input type="number" step="0.001" name="single_weight" value="" placeholder="Wprowadź wagę" class="form-control input-action-weight"> {* Dodana klasa *}
                                    {/if}
                                    <button type="submit" name="save_single_weight" class="btn btn-sm btn-info">
                                        <i class="icon-pencil"></i> Zapisz
                                    </button>
                                </form>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
</div>