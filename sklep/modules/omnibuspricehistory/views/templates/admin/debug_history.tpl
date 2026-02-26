{*
 * 2007-2023 PrestaShop
 *
 * Szablon dla strony podglądu historii cen w panelu administracyjnym modułu OmnibusPriceHistory.
 *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-search"></i> {l s='Wybierz produkt' mod='omnibuspricehistory'}
    </div>
    <div class="form-horizontal">
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Wybierz produkt z listy' mod='omnibuspricehistory'}</label>
            <div class="col-lg-9">
                {* Uproszczona lista rozwijana produktów *}
                <select id="simple-product-select" class="form-control">
                    <option value="">{l s='-- Wybierz produkt --' mod='omnibuspricehistory'}</option>
                    {foreach from=$products_for_select item=product_option}
                        <option value="{$product_option.id_product|intval}" {if $product_option.id_product == $current_selected_product_id}selected="selected"{/if}>
                            {$product_option.name|escape:'html':'UTF-8'} {if $product_option.reference} ({$product_option.reference|escape:'html':'UTF-8'}){/if}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
</div>

{* Ten blok wyświetli się tylko wtedy, gdy produkt zostanie wybrany *}
{if isset($product_selected) && $product_selected}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-info-circle"></i> {l s='Informacje o wybranym produkcie' mod='omnibuspricehistory'}
        </div>
        <div class="form-wrapper">
            <div class="row">
                <div class="col-lg-2 text-center">
                    {if isset($product_info.image_url) && $product_info.image_url}
                        <img src="{$product_info.image_url|escape:'html':'UTF-8'}" alt="{$product_info.name|escape:'html':'UTF-8'}" class="img-thumbnail" />
                    {else}
                        <img src="../img/p/default-small.jpg" alt="{l s='Brak obrazka' mod='omnibuspricehistory'}" class="img-thumbnail" />
                    {/if}
                </div>
                <div class="col-lg-10">
                    <h3>{$product_info.name|escape:'html':'UTF-8'}</h3>
                    <p><strong>{l s='ID:' mod='omnibuspricehistory'}</strong> {$product_info.id_product|intval}</p>
                    <p><strong>{l s='Referencja:' mod='omnibuspricehistory'}</strong> {if $product_info.reference}{$product_info.reference|escape:'html':'UTF-8'}{else}-{/if}</p>
                    <p><strong>{l s='Aktualna cena:' mod='omnibuspricehistory'}</strong> {$product_info.current_price|escape:'html':'UTF-8'}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <i class="icon-bar-chart"></i> {l s='Historia cen produktu' mod='omnibuspricehistory'}
        </div>
        {if !empty($price_history)}
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{l s='ID Wpisu' mod='omnibuspricehistory'}</th>
                        <th>{l s='Cena' mod='omnibuspricehistory'}</th>
                        <th>{l s='Waluta' mod='omnibuspricehistory'}</th>
                        <th>{l s='ID Kombinacji' mod='omnibuspricehistory'}</th>
                        <th>{l s='Kraj' mod='omnibuspricehistory'}</th>
                        <th>{l s='Grupa klienta' mod='omnibuspricehistory'}</th>
                        <th>{l s='Typ zmiany' mod='omnibuspricehistory'}</th>
                        <th>{l s='Data zmiany' mod='omnibuspricehistory'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$price_history item=entry}
                        <tr>
                            <td>{$entry.id_omnibus_price_history|intval}</td>
                            <td>{displayPrice price=$entry.price currency=$entry.id_currency}</td>
                            <td>{$entry.currency_iso|escape:'html':'UTF-8'}</td>
                            <td>{if $entry.id_product_attribute > 0}{$entry.id_product_attribute|intval}{else}{l s='Produkt prosty' mod='omnibuspricehistory'}{/if}</td>
                            <td>{if $entry.id_country > 0}{$entry.country_name|escape:'html':'UTF-8'}{else}{l s='Wszystkie kraje' mod='omnibuspricehistory'}{/if}</td>
                            <td>{if $entry.id_group > 0}{$entry.group_name|escape:'html':'UTF-8'}{else}{l s='Wszystkie grupy' mod='omnibuspricehistory'}{/if}</td>
                            <td>{$entry.change_type|escape:'html':'UTF-8'}</td>
                            <td>{dateFormat date=$entry.date_add full=1}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <div class="alert alert-warning" role="alert">
                <p class="alert-text">
                    {l s='Brak zapisanej historii cen dla tego produktu.' mod='omnibuspricehistory'}
                </p>
            </div>
        {/if}
    </div>
{else}
    <div class="panel">
        <div class="panel-body">
            <div class="alert alert-info" role="alert">
                <p class="alert-text">
                    {l s='Wybierz produkt z listy, aby wyświetlić jego szczegółową historię cen.' mod='omnibuspricehistory'}
                </p>
            </div>
        </div>
    </div>
{/if}

<script type="text/javascript">
    $(document).ready(function() {
        // Przypisz token i bazowy URL do zmiennych JavaScript
        var adminToken = '{$admin_token|escape:'htmlall':'UTF-8'}';
        var baseUrl = '{$base_admin_url|escape:'htmlall':'UTF-8'}'; // base_admin_url już nie zawiera tokena
        
        $('#simple-product-select').on('change', function() {
            var selectedProductId = $(this).val();
            var redirectUrl = baseUrl; // Rozpoczynamy od bazowego URL

            if (selectedProductId) {
                // Dodajemy id_product i token do URL
                redirectUrl += '&id_product=' + selectedProductId + '&token=' + adminToken;
            } else {
                // Jeśli wybrano "-- Wybierz produkt --", dodajemy tylko token
                redirectUrl += '&token=' + adminToken;
            }
            window.location.href = redirectUrl;
        });
    });
</script>