<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Konfiguracja kategorii wagowych' mod='productpro'}
    </div>
    <div class="panel-body">
        <p>{l s='Ustaw przedziały wagowe i wybierz odpowiadające im kategorie PrestaShop. Te ustawienia będą używane do automatycznego przypisywania produktów.' mod='productpro'}</p>

        <form action="{$current_url|escape:'htmlall':'UTF-8'}" method="post" class="form-horizontal">
            {* Konfiguracja dla kategorii 5-10 kg *}
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Kategoria dla 5-10 kg:' mod='productpro'}</label>
                <div class="col-lg-6">
                    <select name="PRODUCTPRO_CATEGORY_5_10KG_ID_SELECTED" class="form-control">
                        <option value="0">{l s='-- Wybierz kategorię --' mod='productpro'}</option>
                        {foreach from=$all_categories item=category}
                            <option value="{$category.id_category|escape:'htmlall':'UTF-8'}"
                                {if $category.id_category == $selected_category_5_10kg_id}selected="selected"{/if}>
                                {$category.spacer|escape:'htmlall':'UTF-8'}{$category.name|escape:'htmlall':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Kategoria "5-10 kg" - Waga minimalna (kg):' mod='productpro'}</label>
                <div class="col-lg-3">
                    <input type="text" name="PRODUCTPRO_WEIGHT_5_10KG_MIN" value="{$weight_5_10kg_min|string_format:"%.2f"|escape:'htmlall':'UTF-8'}" required>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Kategoria "5-10 kg" - Waga maksymalna (kg):' mod='productpro'}</label>
                <div class="col-lg-3">
                    <input type="text" name="PRODUCTPRO_WEIGHT_5_10KG_MAX" value="{$weight_5_10kg_max|string_format:"%.2f"|escape:'htmlall':'UTF-8'}" required>
                </div>
            </div>

            <hr>

            {* Konfiguracja dla kategorii 20-25 kg *}
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Kategoria dla 20-25 kg:' mod='productpro'}</label>
                <div class="col-lg-6">
                    <select name="PRODUCTPRO_CATEGORY_20_25KG_ID_SELECTED" class="form-control">
                        <option value="0">{l s='-- Wybierz kategorię --' mod='productpro'}</option>
                        {foreach from=$all_categories item=category}
                            <option value="{$category.id_category|escape:'htmlall':'UTF-8'}"
                                {if $category.id_category == $selected_category_20_25kg_id}selected="selected"{/if}>
                                {$category.spacer|escape:'htmlall':'UTF-8'}{$category.name|escape:'htmlall':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Kategoria "20-25 kg" - Waga minimalna (kg):' mod='productpro'}</label>
                <div class="col-lg-3">
                    <input type="text" name="PRODUCTPRO_WEIGHT_20_25KG_MIN" value="{$weight_20_25kg_min|string_format:"%.2f"|escape:'htmlall':'UTF-8'}" required>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Kategoria "20-25 kg" - Waga maksymalna (kg):' mod='productpro'}</label>
                <div class="col-lg-3">
                    <input type="text" name="PRODUCTPRO_WEIGHT_20_25KG_MAX" value="{$weight_20_25kg_max|string_format:"%.2f"|escape:'htmlall':'UTF-8'}" required>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" value="1" id="submitCategoryAssignConfig" name="submitCategoryAssignConfig" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Zapisz' mod='productpro'}
                </button>
            </div>
        </form>
    </div>
</div>