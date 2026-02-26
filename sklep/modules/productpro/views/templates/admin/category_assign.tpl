<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Przypisywanie produktów do kategorii wagowych' mod='productpro'}
    </div>
    <div class="panel-body">
        <p>{l s='Poniżej znajduje się lista produktów, których waga mieści się w określonych przedziałach. Możesz zaznaczyć wybrane produkty i przypisać je do odpowiednich kategorii.' mod='productpro'}</p>

        {* Sekcja dla produktów 5-10 kg *}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-truck"></i> {l s='Produkty o wadze od 5 do 10 kg (%s produktów)' sprintf=[$products_5_10kg_count] mod='productpro'}
                <button type="button" class="btn btn-default pull-right" onclick="window.location.reload();">
                    <i class="process-icon-refresh"></i> {l s='Odśwież produkty' mod='productpro'}
                </button>
            </div>
            <div class="panel-body">
                {if $products_5_10kg|@count > 0}
                    <form action="{$current_url|escape:'htmlall':'UTF-8'}" method="post" id="form-assign-5-10kg">
                        <input type="hidden" name="category_type" value="5_10kg">
                        <div class="table-scroll-container">
                            <table class="table productpro-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="check_all_products"></th>
                                        <th>{l s='ID' mod='productpro'}</th>
                                        <th>{l s='Nazwa produktu' mod='productpro'}</th>
                                        <th>{l s='Waga (kg)' mod='productpro'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$products_5_10kg item=product}
                                        <tr>
                                            <td><input type="checkbox" name="product_box[]" value="{$product.id_product|escape:'htmlall':'UTF-8'}"></td>
                                            <td>{$product.id_product|escape:'htmlall':'UTF-8'}</td>
                                            <td>{$product.name|escape:'htmlall':'UTF-8'}</td>
                                            <td>{$product.weight|string_format:"%.3f"|escape:'htmlall':'UTF-8'} kg</td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" name="submitAssignProductsToCategory" class="btn btn-primary pull-right">
                            <i class="process-icon-save"></i> {l s='Przypisz zaznaczone do kategorii: %s' sprintf=[$category_name_5_10kg] mod='productpro'}
                        </button>
                    </form>
                {else}
                    <div class="alert alert-info">
                        <p>{l s='Brak produktów o wadze od 5 do 10 kg.' mod='productpro'}</p>
                    </div>
                {/if}
            </div>
        </div>

        {* Sekcja dla produktów 20-25 kg *}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-truck"></i> {l s='Produkty o wadze od 20 do 25 kg (%s produktów)' sprintf=[$products_20_25kg_count] mod='productpro'}
                <button type="button" class="btn btn-default pull-right" onclick="window.location.reload();">
                    <i class="process-icon-refresh"></i> {l s='Odśwież produkty' mod='productpro'}
                </button>
            </div>
            <div class="panel-body">
                {if $products_20_25kg|@count > 0}
                    <form action="{$current_url|escape:'htmlall':'UTF-8'}" method="post" id="form-assign-20-25kg">
                        <input type="hidden" name="category_type" value="20_25kg">
                        <div class="table-scroll-container">
                            <table class="table productpro-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="check_all_products"></th>
                                        <th>{l s='ID' mod='productpro'}</th>
                                        <th>{l s='Nazwa produktu' mod='productpro'}</th>
                                        <th>{l s='Waga (kg)' mod='productpro'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$products_20_25kg item=product}
                                        <tr>
                                            <td><input type="checkbox" name="product_box[]" value="{$product.id_product|escape:'htmlall':'UTF-8'}"></td>
                                            <td>{$product.id_product|escape:'htmlall':'UTF-8'}</td>
                                            <td>{$product.name|escape:'htmlall':'UTF-8'}</td>
                                            <td>{$product.weight|string_format:"%.3f"|escape:'htmlall':'UTF-8'} kg</td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" name="submitAssignProductsToCategory" class="btn btn-primary pull-right">
                            <i class="process-icon-save"></i> {l s='Przypisz zaznaczone do kategorii: %s' sprintf=[$category_name_20_25kg] mod='productpro'}
                        </button>
                    </form>
                {else}
                    <div class="alert alert-info">
                        <p>{l s='Brak produktów o wadze od 20 do 25 kg.' mod='productpro'}</p>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>

{* Dodajemy jQuery z CDN, aby upewnić się, że jest dostępne przed naszym skryptem *}
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        // Funkcja do zaznaczania/odznaczania wszystkich checkboxów
        $('.check_all_products').on('change', function() {
            var isChecked = $(this).prop('checked');
            $(this).closest('table').find('input[type="checkbox"][name="product_box[]"]').prop('checked', isChecked);
        });
    });
</script>
