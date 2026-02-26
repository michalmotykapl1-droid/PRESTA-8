<div class="panel">
    <div class="panel-heading">
        <i class="icon-eye"></i> {l s='Konfiguracja wyświetlania na sklepie' mod='omnibuspricehistory'}
    </div>

    <form action="{$current_url|escape:'htmlall':'UTF-8'}" method="post" class="form-horizontal">
        <input type="hidden" name="token" value="{$admin_token|escape:'htmlall':'UTF-8'}">

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-tag"></i> {l s='Najniższa cena z 30 dni przed promocją (Wymóg Omnibus)' mod='omnibuspricehistory'}
            </div>
            <div class="alert alert-info">
                {l s='Te ustawienia kontrolują wyświetlanie informacji o najniższej cenie produktu z 30 dni przed obecną promocją. Jest to kluczowy wymóg dyrektywy Omnibus.' mod='omnibuspricehistory'}
            </div>
            <div class="form-wrapper">
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Włącz, aby informacja o najniższej cenie z 30 dni pojawiała się na karcie produktu.' mod='omnibuspricehistory'}">
                            {l s='Wyświetlaj na stronie produktu' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE" id="OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE_on" value="1" {if $omnibus_display_promo_price_product_page == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE" id="OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE_off" value="0" {if $omnibus_display_promo_price_product_page == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Wybierz hook, w którym informacja ma być wyświetlana. Możesz również wpisać własną nazwę hooka.' mod='omnibuspricehistory'}">
                            {l s='Pozycja wyświetlania na stronie produktu (hook)' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <select name="OMNIBUS_PROMO_PRICE_HOOK">
                            <option value="displayProductPriceBlock_after_price" {if $omnibus_promo_price_hook == 'displayProductPriceBlock_after_price'}selected="selected"{/if}>{l s='Po cenie produktu (displayProductPriceBlock_after_price)' mod='omnibuspricehistory'}</option>
                            <option value="displayProductPriceBlock_before_price" {if $omnibus_promo_price_hook == 'displayProductPriceBlock_before_price'}selected="selected"{/if}>{l s='Przed ceną produktu (displayProductPriceBlock_before_price)' mod='omnibuspricehistory'}</option>
                            <option value="displayProductButtons" {if $omnibus_promo_price_hook == 'displayProductButtons'}selected="selected"{/if}>{l s='W sekcji przycisków (displayProductButtons)' mod='omnibuspricehistory'}</option>
                            <option value="displayProductAdditionalInfo" {if $omnibus_promo_price_hook == 'displayProductAdditionalInfo'}selected="selected"{/if}>{l s='Dodatkowe informacje o produkcie (displayProductAdditionalInfo)' mod='omnibuspricehistory'}</option>
                            <option value="displayFooterProduct" {if $omnibus_promo_price_hook == 'displayFooterProduct'}selected="selected"{/if}>{l s='Stopka strony produktu (displayFooterProduct)' mod='omnibuspricehistory'}</option>
                            <option value="displayCustomOmnibusPriceHook" {if $omnibus_promo_price_hook == 'displayCustomOmnibusPriceHook'}selected="selected"{/if}>{l s='Własny hook modułu (displayCustomOmnibusPriceHook)' mod='omnibuspricehistory'}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Użyj {price} jako zmiennej dla ceny. Możesz również dodać {old_price} (cena przed zniżką) i {reduction_percent} (procent obniżki).' mod='omnibuspricehistory'}">
                            {l s='Tekst wyświetlany na stronie produktu' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <textarea name="OMNIBUS_PROMO_PRICE_TEXT" class="form-control">{$omnibus_promo_price_text|escape:'htmlall':'UTF-8'}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Włącz, aby informacja pojawiała się na listach produktów w kategoriach, wynikach wyszukiwania itp.' mod='omnibuspricehistory'}">
                            {l s='Wyświetlaj na liście produktów (kategorie, wyniki wyszukiwania)' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_DISPLAY_PROMO_PRICE_LISTING" id="OMNIBUS_DISPLAY_PROMO_PRICE_LISTING_on" value="1" {if $omnibus_display_promo_price_listing == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_DISPLAY_PROMO_PRICE_LISTING_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_DISPLAY_PROMO_PRICE_LISTING" id="OMNIBUS_DISPLAY_PROMO_PRICE_LISTING_off" value="0" {if $omnibus_display_promo_price_listing == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_DISPLAY_PROMO_PRICE_LISTING_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Użyj {price} jako zmiennej dla ceny. Zaleca się krótszy tekst.' mod='omnibuspricehistory'}">
                            {l s='Tekst wyświetlany na liście produktów' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <textarea name="OMNIBUS_PROMO_PRICE_LISTING_TEXT" class="form-control">{$omnibus_promo_price_listing_text|escape:'htmlall':'UTF-8'}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Włącz, aby informacja pojawiała się obok produktu w podsumowaniu koszyka.' mod='omnibuspricehistory'}">
                            {l s='Wyświetlaj w koszyku' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_DISPLAY_PROMO_PRICE_CART" id="OMNIBUS_DISPLAY_PROMO_PRICE_CART_on" value="1" {if $omnibus_display_promo_price_cart == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_DISPLAY_PROMO_PRICE_CART_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_DISPLAY_PROMO_PRICE_CART" id="OMNIBUS_DISPLAY_PROMO_PRICE_CART_off" value="0" {if $omnibus_display_promo_price_cart == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_DISPLAY_PROMO_PRICE_CART_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Wybierz kolor tekstu dla informacji o najniższej cenie.' mod='omnibuspricehistory'}">
                            {l s='Kolor czcionki' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="color" name="OMNIBUS_PROMO_PRICE_FONT_COLOR" value="{$omnibus_promo_price_font_color|escape:'htmlall':'UTF-8'}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Określ rozmiar czcionki w pikselach.' mod='omnibuspricehistory'}">
                            {l s='Rozmiar czcionki (w pikselach)' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="number" name="OMNIBUS_PROMO_PRICE_FONT_SIZE" value="{$omnibus_promo_price_font_size|intval}" min="8" max="30">
                            <span class="input-group-addon">px</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Wybierz kolor dla samej wartości ceny w tekście.' mod='omnibuspricehistory'}">
                            {l s='Kolor ceny w tekście' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="color" name="OMNIBUS_PROMO_PRICE_PRICE_COLOR" value="{$omnibus_promo_price_price_color|escape:'htmlall':'UTF-8'}" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-area-chart"></i> {l s='Prezentacja historii cen (Opcjonalnie)' mod='omnibuspricehistory'}
            </div>
            <div class="alert alert-info">
                {l s='Te ustawienia pozwalają na wyświetlenie szczegółowej historii cen produktu (np. w formie wykresu lub tabeli). Nie jest to wymóg Omnibus.' mod='omnibuspricehistory'}
            </div>
            <div class="form-wrapper">
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Włącz, aby klienci mogli zobaczyć pełną historię zmian cen produktu.' mod='omnibuspricehistory'}">
                            {l s='Włącz wyświetlanie pełnej historii cen' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_ENABLE_FULL_HISTORY" id="OMNIBUS_ENABLE_FULL_HISTORY_on" value="1" {if $omnibus_enable_full_history == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_ENABLE_FULL_HISTORY_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_ENABLE_FULL_HISTORY" id="OMNIBUS_ENABLE_FULL_HISTORY_off" value="0" {if $omnibus_enable_full_history == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_ENABLE_FULL_HISTORY_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Zdecyduj, czy historia cen ma być dostępna dla wszystkich produktów, czy tylko dla tych, które są aktualnie w promocji.' mod='omnibuspricehistory'}">
                            {l s='Dla jakich produktów wyświetlać historię' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="radio">
                            <label for="OMNIBUS_FULL_HISTORY_SCOPE_promotions_only">
                                <input type="radio" name="OMNIBUS_FULL_HISTORY_SCOPE" id="OMNIBUS_FULL_HISTORY_SCOPE_promotions_only" value="promotions_only" {if $omnibus_full_history_scope == 'promotions_only'}checked="checked"{/if}>
                                {l s='Tylko dla produktów w promocji' mod='omnibuspricehistory'}
                            </label>
                        </div>
                        <div class="radio">
                            <label for="OMNIBUS_FULL_HISTORY_SCOPE_all_products">
                                <input type="radio" name="OMNIBUS_FULL_HISTORY_SCOPE" id="OMNIBUS_FULL_HISTORY_SCOPE_all_products" value="all_products" {if $omnibus_full_history_scope == 'all_products'}checked="checked"{/if}>
                                {l s='Dla wszystkich produktów' mod='omnibuspricehistory'}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Wybierz format prezentacji historii cen. Opcje z "popupem" wymagają kliknięcia.' mod='omnibuspricehistory'}">
                            {l s='Typ wyświetlania historii cenowej' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <select name="OMNIBUS_FULL_HISTORY_DISPLAY_TYPE">
                            <option value="popup_bar_chart" {if $omnibus_full_history_display_type == 'popup_bar_chart'}selected="selected"{/if}>{l s='Wykres słupkowy (w popupie)' mod='omnibuspricehistory'}</option>
                            <option value="popup_line_chart_modern" {if $omnibus_full_history_display_type == 'popup_line_chart_modern'}selected="selected"{/if}>{l s='Wykres liniowy (nowoczesny styl, w popupie)' mod='omnibuspricehistory'}</option>
                            <option value="popup_line_chart" {if $omnibus_full_history_display_type == 'popup_line_chart'}selected="selected"{/if}>{l s='Wykres liniowy (w popupie)' mod='omnibuspricehistory'}</option>
                            <option value="popup_table" {if $omnibus_full_history_display_type == 'popup_table'}selected="selected"{/if}>{l s='Tabela z historią cen (w popupie)' mod='omnibuspricehistory'}</option>
                            <option value="text_info" {if $omnibus_full_history_display_type == 'text_info'}selected="selected"{/if}>{l s='Tekst informacyjny (najniższa cena + data, bez popupu)' mod='omnibuspricehistory'}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Kolor standardowych słupków/linii na wykresie historii cen.' mod='omnibuspricehistory'}">
                            {l s='Kolor słupka/linii wykresu (standardowy)' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="color" name="OMNIBUS_FULL_HISTORY_BAR_COLOR" value="{$omnibus_full_history_bar_color|escape:'htmlall':'UTF-8'}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Kolor słupka/punktu na wykresie, który reprezentuje najniższą cenę w danym okresie.' mod='omnibuspricehistory'}">
                            {l s='Kolor słupka/punktu z najniższą ceną' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="color" name="OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR" value="{$omnibus_full_history_lowest_bar_color|escape:'htmlall':'UTF-8'}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Kolor tekstu w tabeli lub informacji tekstowej.' mod='omnibuspricehistory'}">
                            {l s='Kolor czcionki w historii (dla tabel/tekstu)' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="color" name="OMNIBUS_FULL_HISTORY_TEXT_COLOR" value="{$omnibus_full_history_text_color|escape:'htmlall':'UTF-8'}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Określ rozmiar czcionki w pikselach dla wyświetlanej historii.' mod='omnibuspricehistory'}">
                            {l s='Rozmiar czcionki w historii (w pikselach)' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="number" name="OMNIBUS_FULL_HISTORY_FONT_SIZE" value="{$omnibus_full_history_font_size|intval}" min="8" max="30">
                            <span class="input-group-addon">px</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Wybierz kolor dla wartości ceny w historii (dla tabel/tekstu).' mod='omnibuspricehistory'}">
                            {l s='Kolor ceny w historii' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="color" name="OMNIBUS_FULL_HISTORY_PRICE_COLOR" value="{$omnibus_full_history_price_color|escape:'htmlall':'UTF-8'}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Włącz, aby wyświetlić informację, jeśli bieżąca cena produktu jest najniższą w historii z ostatnich 30 dni.' mod='omnibuspricehistory'}">
                            {l s='Opcja pokazania dodatkowego tekstu, jeśli obecnie produkt ma najniższą cenę z przeciągu 30 dni' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_DISPLAY_LOWEST_PRICE_INFO" id="OMNIBUS_DISPLAY_LOWEST_PRICE_INFO_on" value="1" {if $omnibus_display_lowest_price_info == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_DISPLAY_LOWEST_PRICE_INFO_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_DISPLAY_LOWEST_PRICE_INFO" id="OMNIBUS_DISPLAY_LOWEST_PRICE_INFO_off" value="0" {if $omnibus_display_lowest_price_info == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_DISPLAY_LOWEST_PRICE_INFO_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Użyj {price} dla ceny i {date} dla daty.' mod='omnibuspricehistory'}">
                            {l s='Tekst dla produktu z najniższą ceną w historii' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <textarea name="OMNIBUS_LOWEST_PRICE_INFO_TEXT" class="form-control">{$omnibus_lowest_price_info_text|escape:'htmlall':'UTF-8'}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" value="1" id="submitOmnibusDisplayConfig" name="submitOmnibusDisplayConfig" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Zapisz' mod='omnibuspricehistory'}
            </button>
        </div>
    </form>
</div>

{* Inicjalizacja tooltipów, potrzebne dla label-tooltip *}
{literal}
<script type="text/javascript">
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
{/literal}