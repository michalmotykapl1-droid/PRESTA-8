<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Ustawienia ogólne modułu Omnibus' mod='omnibuspricehistory'}
    </div>

    <form action="{$current_url|escape:'htmlall':'UTF-8'}" method="post" class="form-horizontal">
        {* Zmieniona linia: używamy zmiennej admin_token przekazanej z kontrolera *}
        <input type="hidden" name="token" value="{$admin_token|escape:'htmlall':'UTF-8'}">

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-database"></i> {l s='Konfiguracja zbierania danych' mod='omnibuspricehistory'}
            </div>
            <div class="form-wrapper">
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Określ, przez ile dni wstecz ma być przechowywana historia cen produktów. Wymóg dyrektywy Omnibus to 30 dni.' mod='omnibuspricehistory'}">
                            {l s='Liczba dni przechowywania historii cen' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <input type="number" name="OMNIBUS_HISTORY_DAYS" value="{$omnibus_history_days|intval}" required="required" min="1">
                        <p class="help-block">{l s='Domyślna wartość: 30 dni.' mod='omnibuspricehistory'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Jeśli aktywne, każda zmiana ceny produktu w górę w tym samym dniu nadpisze poprzednią cenę z tego dnia. Zalecane wyłączenie dla bardziej szczegółowej historii.' mod='omnibuspricehistory'}">
                            {l s='Nadpisywanie zmiany ceny (w górę) z tego samego dnia' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP" id="OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP_on" value="1" {if $omnibus_overwrite_same_day_price_up == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP" id="OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP_off" value="0" {if $omnibus_overwrite_same_day_price_up == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Włącz, aby moduł zbierał i analizował ceny dla poszczególnych kombinacji produktów (np. rozmiar, kolor).' mod='omnibuspricehistory'}">
                            {l s='Obsługa produktów z atrybutami (kombinacje)' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_HANDLE_ATTRIBUTES" id="OMNIBUS_HANDLE_ATTRIBUTES_on" value="1" {if $omnibus_handle_attributes == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_HANDLE_ATTRIBUTES_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_HANDLE_ATTRIBUTES" id="OMNIBUS_HANDLE_ATTRIBUTES_off" value="0" {if $omnibus_handle_attributes == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_HANDLE_ATTRIBUTES_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Jeśli korzystasz z cen specyficznych dla różnych grup klientów, włącz tę opcję, aby uwzględniać te ceny w historii.' mod='omnibuspricehistory'}">
                            {l s='Indeksacja cen dla niestandardowych grup klientów' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_INDEX_CUSTOMER_GROUPS" id="OMNIBUS_INDEX_CUSTOMER_GROUPS_on" value="1" {if $omnibus_index_customer_groups == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_INDEX_CUSTOMER_GROUPS_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_INDEX_CUSTOMER_GROUPS" id="OMNIBUS_INDEX_CUSTOMER_GROUPS_off" value="0" {if $omnibus_index_customer_groups == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_INDEX_CUSTOMER_GROUPS_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Aktywuj, jeśli ceny produktów różnią się w zależności od kraju, a chcesz zbierać historię dla każdego z nich.' mod='omnibuspricehistory'}">
                            {l s='Pobieranie cen dla poszczególnych krajów' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_INDEX_COUNTRIES" id="OMNIBUS_INDEX_COUNTRIES_on" value="1" {if $omnibus_index_countries == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_INDEX_COUNTRIES_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_INDEX_COUNTRIES" id="OMNIBUS_INDEX_COUNTRIES_off" value="0" {if $omnibus_index_countries == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_INDEX_COUNTRIES_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Zapewnia poprawne zbieranie danych dla cen zdefiniowanych jako "ceny specyficzne" w PrestaShop (np. rabaty ilościowe, ceny dla konkretnych dat).' mod='omnibuspricehistory'}">
                            {l s='Pełna obsługa cen specyficznych' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_HANDLE_SPECIFIC_PRICES" id="OMNIBUS_HANDLE_SPECIFIC_PRICES_on" value="1" {if $omnibus_handle_specific_prices == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_HANDLE_SPECIFIC_PRICES_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_HANDLE_SPECIFIC_PRICES" id="OMNIBUS_HANDLE_SPECIFIC_PRICES_off" value="0" {if $omnibus_handle_specific_prices == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_HANDLE_SPECIFIC_PRICES_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-time"></i> {l s='Ustawienia CRONa' mod='omnibuspricehistory'}
            </div>
            <div class="alert alert-info">
                {l s='Moduł może cyklicznie indeksować ceny, aby zapewnić aktualność danych, szczególnie dla zmian wprowadzanych poza panelem administracyjnym (np. przez importy).' mod='omnibuspricehistory'}
                {l s='Należy ustawić zadanie CRON na swoim serwerze, używając poniższego URL.' mod='omnibuspricehistory'}
            </div>
            <div class="form-wrapper">
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Włącz tę opcję, aby aktywować mechanizm cyklicznej indeksacji cen.' mod='omnibuspricehistory'}">
                            {l s='Włącz CRONa do zbierania cen' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_ENABLE_CRON" id="OMNIBUS_ENABLE_CRON_on" value="1" {if $omnibus_enable_cron == 1}checked="checked"{/if}>
                            <label for="OMNIBUS_ENABLE_CRON_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_ENABLE_CRON" id="OMNIBUS_ENABLE_CRON_off" value="0" {if $omnibus_enable_cron == 0}checked="checked"{/if}>
                            <label for="OMNIBUS_ENABLE_CRON_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Skopiuj ten URL i dodaj go do zadań CRON na swoim serwerze. Zalecana częstotliwość: raz na godzinę.' mod='omnibuspricehistory'}">
                            {l s='URL do zadania CRON' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <input type="text" name="OMNIBUS_CRON_URL_DISPLAY" value="{$omnibus_cron_url|escape:'htmlall':'UTF-8'}" readonly="readonly" class="form-control">
                        <p class="help-block">{l s='Użyj tego adresu w konfiguracji CRONa na swoim serwerze.' mod='omnibuspricehistory'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Określ, ile produktów ma być przetwarzanych w jednym przebiegu zadania CRON. Wpływa na wydajność.' mod='omnibuspricehistory'}">
                            {l s='Liczba produktów do indeksacji w jednej paczce CRON' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="number" name="OMNIBUS_CRON_BATCH_SIZE" value="{$omnibus_cron_batch_size|intval}" required="required" min="1">
                            <span class="input-group-addon">{l s='produktów' mod='omnibuspricehistory'}</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Po jakim czasie od ostatniej indeksacji moduł ma wyświetlać ostrzeżenie w panelu administracyjnym, że zadanie CRON nie działa poprawnie.' mod='omnibuspricehistory'}">
                            {l s='Pokaż ostrzeżenie o braku indeksacji po' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="number" name="OMNIBUS_LAST_INDEX_THRESHOLD" value="{$omnibus_last_index_threshold|intval}" required="required" min="1">
                            <span class="input-group-addon">{l s='godzinach' mod='omnibuspricehistory'}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {* Sekcja CRON promocji *}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-gift"></i> {l s='Ustawienia CRONa promocji' mod='omnibuspricehistory'}
            </div>
            <div class="alert alert-info">
                {l s='Ta sekcja pozwala na konfigurację niezależnego zadania CRON, przeznaczonego wyłącznie do indeksowania produktów w promocji.' mod='omnibuspricehistory'}
                {l s='Możesz użyć tego, aby częściej aktualizować ceny promocyjne bez obciążania serwera pełną indeksacją.' mod='omnibuspricehistory'}
            </div>
            <div class="form-wrapper">
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <i class="icon-gift"></i> {l s='Włącz CRON promocji' mod='omnibuspricehistory'}
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="OMNIBUS_ENABLE_PROMO_CRON" id="OMNIBUS_ENABLE_PROMO_CRON_on" value="1"
                                {if $omnibus_enable_promo_cron}checked="checked"{/if}>
                            <label for="OMNIBUS_ENABLE_PROMO_CRON_on">{l s='Tak' mod='omnibuspricehistory'}</label>
                            <input type="radio" name="OMNIBUS_ENABLE_PROMO_CRON" id="OMNIBUS_ENABLE_PROMO_CRON_off" value="0"
                                {if !$omnibus_enable_promo_cron}checked="checked"{/if}>
                            <label for="OMNIBUS_ENABLE_PROMO_CRON_off">{l s='Nie' mod='omnibuspricehistory'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='URL do CRONa promocji' mod='omnibuspricehistory'}
                    </label>
                    <div class="col-lg-9">
                        <input type="text" readonly="readonly" class="form-control"
                            value="{$omnibus_promo_cron_url|escape:'htmlall':'UTF-8'}">
                        <p class="help-block">{l s='Użyj tego adresu w konfiguracji CRONa na swoim serwerze.' mod='omnibuspricehistory'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Liczba produktów w paczce (promo)' mod='omnibuspricehistory'}
                    </label>
                    <div class="col-lg-9">
                        <input type="number" name="OMNIBUS_PROMO_CRON_BATCH_SIZE" min="1" required="required"
                            value="{$omnibus_promo_cron_batch_size|intval}">
                        <p class="help-block">{l s='Ile rekordów przetworzyć na jedno wywołanie.' mod='omnibuspricehistory'}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <i class="icon-code"></i> {l s='Kompatybilność' mod='omnibuspricehistory'}
            </div>
            <div class="form-wrapper">
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Wybierz wersję biblioteki FontAwesome używanej w Twoim szablonie, aby poprawnie wyświetlać ikony modułu.' mod='omnibuspricehistory'}">
                            {l s='Kompatybilność z FontAwesome' mod='omnibuspricehistory'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <select name="OMNIBUS_COMPATIBILITY_FONTAWESOME">
                            <option value="auto" {if $omnibus_compatibility_fontawesome == 'auto'}selected="selected"{/if}>{l s='Automatyczna detekcja (zalecane)' mod='omnibuspricehistory'}</option>
                            <option value="4" {if $omnibus_compatibility_fontawesome == '4'}selected="selected"{/if}>{l s='Wersja 4.x' mod='omnibuspricehistory'}</option>
                            <option value="5" {if $omnibus_compatibility_fontawesome == '5'}selected="selected"{/if}>{l s='Wersja 5.x' mod='omnibuspricehistory'}</option>
                            <option value="6" {if $omnibus_compatibility_fontawesome == '6'}selected="selected"{/if}>{l s='Wersja 6.x' mod='omnibuspricehistory'}</option>
                            <option value="none" {if $omnibus_compatibility_fontawesome == 'none'}selected="selected"{/if}>{l s='Brak (nie używaj ikon FontAwesome)' mod='omnibuspricehistory'}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" value="1" id="submitOmnibusConfig" name="submitOmnibusConfig" class="btn btn-default pull-right">
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
