{*
* 2007-2023 PrestaShop
*
* Szablon dla strony Ustawienia Ceny za Jednostkę
*}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-dollar"></i> {l s='Ustawienia Ceny za Jednostkę' mod='productpro'}
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            {l s='Wybierz, jak ma być prezentowana cena produktu w przeliczeniu na jednostkę wagową. Ta opcja będzie miała zastosowanie do wszystkich produktów posiadających wagę.' mod='productpro'}
        </div>

        <form action="{$form_action|escape:'html':'UTF-8'}" method="post" class="form-horizontal">
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Wyświetlaj cenę za' mod='productpro'}</label>
                <div class="col-lg-9">
                    <select name="PRODUCTPRO_PRICE_UNIT" class="form-control">
                        <option value="kg" {if $current_unit == 'kg'}selected="selected"{/if}>{l s='1 kilogram (kg)' mod='productpro'}</option>
                        <option value="100g" {if $current_unit == '100g'}selected="selected"{/if}>{l s='100 gramów (100g)' mod='productpro'}</option>
                    </select>
                </div>
            </div>
            <div class="panel-footer">
                <button type="submit" name="submitPriceCalcSettings" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Zapisz' mod='productpro'}
                </button>
            </div>
        </form>
    </div>
</div>