{*
* 2007-2023 PrestaShop
*
* Szablon dla strony Korekta Wag PRO
*}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-wrench"></i> {l s='Korekta Wag PRO' mod='productpro'}
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            {l s='Ta sekcja pozwala na znalezienie i poprawienie produktów, których rzeczywista waga w systemie różni się od wagi sugerowanej w nazwie (np. produkt "Sól 1 kg" o wadze 1.005 kg).' mod='productpro'}
        </div>
        <p>
            <strong>{l s='Znaleziono produktów z potencjalną niezgodnością wagi:' mod='productpro'}</strong>
            <span class="badge" style="background-color: #d9534f; color: white;">{$products_count}</span>
        </p>
        <form method="post" action="{$current|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}">
            <button type="submit" name="save_all_corrections" class="btn btn-success" {if $products_count == 0}disabled{/if}>
                <i class="icon-save"></i> {l s='Zapisz wszystkie sugerowane korekty' mod='productpro'}
            </button>
            <a href="{$scan_correction_url|escape:'html':'UTF-8'}" class="btn btn-primary">
                <i class="icon-refresh"></i> {l s='Skanuj ponownie' mod='productpro'}
            </a>
        </form>
        <br>
        
        <div class="table-scroll-container">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th style="width: 80px;">{l s='ID produktu' mod='productpro'}</th>
                        <th style="width: 35%;">{l s='Nazwa' mod='productpro'}</th>
                        <th>{l s='Aktualna waga (kg)' mod='productpro'}</th>
                        <th>{l s='Sugerowana waga (kg)' mod='productpro'}</th>
                        <th>{l s='Różnica (kg)' mod='productpro'}</th>
                        <th style="width: 230px;">{l s='Akcje' mod='productpro'}</th>
                    </tr>
                </thead>
                <tbody>
                    {if isset($products_for_correction) && $products_for_correction|count > 0}
                        {foreach from=$products_for_correction item=p}
                            <tr>
                                <td>{$p.id_product|escape:'html':'UTF-8'}</td>
                                <td>{$p.name|escape:'html':'UTF-8'}</td>
                                <td style="color: #d9534f; font-weight: bold;">{$p.current_weight|string_format:"%.3f"}</td>
                                <td style="color: #5cb85c; font-weight: bold;">
                                    {if $p.suggested_weight !== null}
                                        {$p.suggested_weight|string_format:"%.3f"}
                                    {else}
                                        <span class="text-danger">{l s='Brak propozycji' mod='productpro'}</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $p.difference !== null}
                                        {if $p.difference > 0}+{/if}{$p.difference|string_format:"%.3f"}
                                    {else}
                                        -
                                    {/if}
                                </td>
                                <td>
                                    <form method="post" class="form-inline form-inline-action-edit">
                                        <input type="hidden" name="id_product_to_correct" value="{$p.id_product|escape:'html':'UTF-8'}">
                                        <input type="number" step="0.001" name="corrected_weight" 
                                               value="{if $p.suggested_weight !== null}{$p.suggested_weight|string_format:'%.3f'}{/if}" 
                                               placeholder="{l s='Wprowadź wagę' mod='productpro'}" 
                                               class="form-control input-action-weight"
                                               {if $p.suggested_weight === null}style="border-color: #d9534f;"{/if}>
                                        <button type="submit" name="save_single_correction" class="btn btn-sm btn-info">
                                            <i class="icon-pencil"></i> {l s='Popraw' mod='productpro'}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="6" class="text-center">{l s='Nie znaleziono produktów z niezgodną wagą.' mod='productpro'}</td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>
    </div>
</div>
