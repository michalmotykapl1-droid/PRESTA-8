{*
 * 2007-2023 PrestaShop
 *
 * Szablon do wyświetlania najniższej ceny z 30 dni przed promocją oraz przycisku/linku do pełnej historii cen w pop-upie.
 * Wersja zwalidowana dla PrestaShop 8.x.
 *}

{* --- Sekcja 1: Najniższa cena z 30 dni przed promocją (Wymóg Omnibus) --- *}
{if isset($omnibus_lowest_price_info) && $omnibus_lowest_price_info && (isset($omnibus_display_promo_price_product_page) && $omnibus_display_promo_price_product_page)}
    <div class="omnibus-promo-price-info">
        {* Wyświetlamy tekst z ceną, który jest już sformatowany w serwisie. *}
        <span>{$omnibus_lowest_price_info.text nofilter}</span>
        {* Dodajemy datę najniższej ceny, jeśli jest dostępna *}
        {if isset($omnibus_lowest_price_info.date)}
            {* Możesz dostosować format daty tutaj, np. date_format:"%d.%m.%Y, %H:%M:%S" *}
            <small>({$omnibus_lowest_price_info.date|date_format:"%d.%m.%Y, %H:%M"})</small>
        {/if}

        {* --- Przycisk/Link do pełnej historii cen w pop-upie (Zintegrowany) --- *}
        {if isset($omnibus_enable_full_history) && $omnibus_enable_full_history && isset($product) && is_object($product)}
            {assign var="full_history_scope" value=$omnibus_full_history_scope|default:'promotions_only'}

            {assign var="should_display_full_history_button" value=false}
            {if $full_history_scope == 'all_products'}
                {assign var="should_display_full_history_button" value=true}
            {elseif $full_history_scope == 'promotions_only' && (isset($omnibus_lowest_price_info) && $omnibus_lowest_price_info)}
                {assign var="should_display_full_history_button" value=true}
            {/if}

            {if $should_display_full_history_button}
                <button
                    type="button"
                    class="omnibus-show-history-popup"
                    data-id-product="{$product->id|intval}"
                    data-id-product-attribute="{if isset($id_product_attribute)}{$id_product_attribute|intval}{else}0{/if}"
                    title="Zobacz historię cen"
                >
                    {* Ikona symbolizująca historię ceny (np. zegar lub wykres) *}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                    </svg>
                    <span>Historia cen</span>
                </button>
            {/if}
        {/if}
    </div>
{/if}

{* Dołączamy szablon pop-upu, przekazując mu zmienną 'history' *}
{* Zmienna 'history' jest teraz przygotowywana w OmnibusPriceHistoryService.php i przypisywana w głównym module. *}
{* Ważne: upewnij się, że ten include jest wywoływany tylko raz na stronie, np. na końcu pliku product.tpl *}
{include file='module:omnibuspricehistory/views/templates/hook/price_history_popup.tpl' history=$history}
