<?php

require_once(dirname(__FILE__) . '/../services/AzadaConfig.php');

class AzadaSettingsTechnical
{
    /**
     * Sekcja techniczna: linki CRON + token bezpieczeństwa.
     * Kolejność linków jest ustawiona wg rekomendowanej częstotliwości uruchomień.
     *
     * @param Module $module
     * @param string $baseUrl Bazowy URL do katalogu modułu, np. https://domena.pl/modules/azada_wholesaler_pro/
     */
    public static function getForm($module, $baseUrl)
    {
        $js = self::getJavascriptLogic($baseUrl);

        // Pobieramy aktualny token ręcznie, bo użyjemy własnego pola HTML
        AzadaConfig::ensureDefaults();
        $currentToken = AzadaConfig::get('AZADA_CRON_KEY');

        // --- BUDUJEMY WŁASNY WYGLĄD POLA Z TOKENEM (Flexbox) ---
        $customTokenField = '
        <div class="form-group">
            <label class="control-label col-lg-3 required">Twój Token</label>
            <div class="col-lg-9">
                <div style="display: flex; align-items: center; gap: 10px; max-width: 600px;">
                    <input type="text"
                           name="AZADA_CRON_KEY"
                           id="AZADA_CRON_KEY"
                           value="' . htmlspecialchars($currentToken, ENT_QUOTES, 'UTF-8') . '"
                           class="form-control"
                           style="flex: 1;">

                    <button id="btn-generate-token" class="btn btn-default" type="button" style="white-space: nowrap;">
                        <i class="icon-random"></i> Generuj nowy
                    </button>
                </div>
                <p class="help-block" style="margin-top:5px;">
                    Ciąg znaków zabezpieczający wywołanie skryptów. Musi być trudny do zgadnięcia.
                </p>
            </div>
        </div>';

        $cronInfoHtml = '
        <div class="alert alert-info" style="margin-bottom: 15px;">
            <strong>Jak działa CRON w tym module?</strong><br>
            <ul style="margin: 8px 0 0 18px;">
                <li><strong>IMPORT</strong> = pobiera dane z hurtowni i aktualizuje tabele RAW (odpowiednik przycisku <em>Pobierz dane</em>).</li>
                <li><strong>UPDATE</strong> = aktualizuje produkty w PrestaShop na podstawie danych z RAW (nie pobiera nic z hurtowni).</li>
                <li><strong>CREATE</strong> = tworzy nowe produkty w PrestaShop tylko z kategorii <em>Import ON</em> (is_active=1) i zmapowanych.</li>
            </ul>
            <div style="margin-top:8px;">
                <strong>Ważne:</strong> UPDATE/CREATE mają sens tylko, jeśli wcześniej działa IMPORT (RAW musi być świeże).
            </div>
        </div>';

        $sepFast = '<div style="margin: 10px 0 5px; padding: 8px 0 0; border-top: 1px solid #eee;"><strong>CRONY najczęstsze (co kilka minut)</strong></div>';
        $sepRegular = '<div style="margin: 15px 0 5px; padding: 8px 0 0; border-top: 1px solid #eee;"><strong>CRONY regularne (kilkanaście–kilkadziesiąt minut)</strong></div>';
        $sepRare = '<div style="margin: 15px 0 5px; padding: 8px 0 0; border-top: 1px solid #eee;"><strong>CRONY rzadkie (co kilka godzin)</strong></div>';
        $sepTools = '<div style="margin: 15px 0 5px; padding: 8px 0 0; border-top: 1px solid #eee;"><strong>Narzędzia / serwisowe</strong></div>';

        $exampleHtml = '
        <div class="alert alert-warning" style="margin-top: 10px;">
            <strong>Przykładowy harmonogram (do crontab):</strong><br>
            <div style="margin-top:6px; font-family: monospace; white-space: pre;">
*/5 * * * *   (ABRO) Update QTY ABRO (pull+push)
*/15 * * * *  Update QTY (inne hurtownie)
*/30 * * * *  Create Products (batch)
0 */2 * * *   Update PRICE
0 */6 * * *   Import FULL
            </div>
            <div style="margin-top:6px;">
                To tylko przykład. Jeśli używasz <strong>IMPORT LIGHT</strong>, ustaw go zwykle co 15–30 min i wykonuj Update QTY z lekkim przesunięciem.
            </div>
        </div>';

        return [
            'form' => [
                'legend' => ['title' => '1. Adresy skryptów (CRON)', 'icon' => 'icon-cogs'],
                'input' => [
                    ['type' => 'html', 'name' => 'js_logic', 'html_content' => $js],
                    ['type' => 'html', 'name' => 'cron_info', 'html_content' => $cronInfoHtml],

                    // Kolejność wg częstotliwości
                    ['type' => 'html', 'name' => 'sep_fast', 'html_content' => $sepFast],
                    [
                        'type' => 'text',
                        'label' => 'CRON: ABRO – stany co kilka minut (pull + push)',
                        'name' => 'VIEW_CRON_UPDATE_QTY_ABRO',
                        'id' => 'VIEW_CRON_UPDATE_QTY_ABRO',
                        'class' => 'col-lg-9',
                        'readonly' => true,
                        'desc' => 'Dedykowane dla ABRO: lekki import stanów do RAW + aktualizacja stanów w PrestaShop. Zalecane: co 3–5 minut.',
                    ],
                    [
                        'type' => 'text',
                        'label' => 'CRON: Update QTY (PrestaShop – stany)',
                        'name' => 'VIEW_CRON_UPDATE_QTY',
                        'id' => 'VIEW_CRON_UPDATE_QTY',
                        'class' => 'col-lg-9',
                        'readonly' => true,
                        'desc' => 'Aktualizuje stany (i opcjonalnie min. ilość/aktywność) w PrestaShop na podstawie danych RAW. Nie pobiera danych z hurtowni. Zalecane: co 10–15 minut.',
                    ],

                    ['type' => 'html', 'name' => 'sep_regular', 'html_content' => $sepRegular],
                    [
                        'type' => 'text',
                        'label' => 'CRON: Import LIGHT (stany/ceny, jeśli dostępne)',
                        'name' => 'VIEW_CRON_IMPORT_LIGHT',
                        'id' => 'VIEW_CRON_IMPORT_LIGHT',
                        'class' => 'col-lg-9',
                        'readonly' => true,
                        'desc' => 'Tryb lekki – jeśli integracja wspiera. Docelowo do częstszej synchronizacji stanów/cen. Zalecane: co 15–30 minut. Jeśli hurtownia nie wspiera, cron zwróci SKIP.',
                    ],
                    [
                        'type' => 'text',
                        'label' => 'CRON: Create Products (kategorie Import ON)',
                        'name' => 'VIEW_CRON_CREATE_PRODUCTS',
                        'id' => 'VIEW_CRON_CREATE_PRODUCTS',
                        'class' => 'col-lg-9',
                        'readonly' => true,
                        'desc' => 'Tworzy produkty tylko z kategorii Import ON (is_active=1) i zmapowanych. Najlepiej batchami (limit 50–200). Zalecane: co 10–30 minut (lub ręcznie).',
                    ],

                    ['type' => 'html', 'name' => 'sep_rare', 'html_content' => $sepRare],
                    [
                        'type' => 'text',
                        'label' => 'CRON: Update PRICE (PrestaShop – ceny)',
                        'name' => 'VIEW_CRON_UPDATE_PRICE',
                        'id' => 'VIEW_CRON_UPDATE_PRICE',
                        'class' => 'col-lg-9',
                        'readonly' => true,
                        'desc' => 'Aktualizuje ceny sprzedaży/koszt/VAT na podstawie RAW i narzutów (kategoria ma priorytet). Zalecane: co 1–4 godziny lub po imporcie.',
                    ],
                    [
                        'type' => 'text',
                        'label' => 'CRON: Import FULL (pobierz dane z hurtowni)',
                        'name' => 'VIEW_CRON_IMPORT_FULL',
                        'id' => 'VIEW_CRON_IMPORT_FULL',
                        'class' => 'col-lg-9',
                        'readonly' => true,
                        'desc' => 'Pełny import feedów i przebudowa tabel RAW (np. ABRO: source → conversion → final). Zalecane: co 2–6 godzin (zależnie od wielkości plików).',
                    ],

                    ['type' => 'html', 'name' => 'sep_tools', 'html_content' => $sepTools],
                    [
                        'type' => 'text',
                        'label' => 'CRON: Rebuild Index (Poczekalnia)',
                        'name' => 'VIEW_CRON_REBUILD_INDEX',
                        'id' => 'VIEW_CRON_REBUILD_INDEX',
                        'class' => 'col-lg-9',
                        'readonly' => true,
                        'desc' => 'Odświeża azada_raw_search_index na podstawie RAW. Przydatne, gdy Create Products ma działać automatycznie bez wchodzenia w Poczekalnię. Zalecane: po Import FULL (lub serwisowo, jeśli Import FULL robi to automatycznie).',
                    ],

                    ['type' => 'html', 'name' => 'cron_examples', 'html_content' => $exampleHtml],

                    // Przełącznik tokena
                    [
                        'type' => 'switch',
                        'label' => 'Używaj tokenu bezpieczeństwa',
                        'name' => 'AZADA_USE_SECURE_TOKEN',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => 'Tak'],
                            ['id' => 'off', 'value' => 0, 'label' => 'Nie'],
                        ],
                    ],

                    // Token (custom HTML)
                    [
                        'type' => 'html',
                        'name' => 'custom_token_html',
                        'html_content' => $customTokenField,
                    ],
                ],
                'submit' => ['title' => 'Zapisz sekcję Techniczną', 'class' => 'btn btn-primary pull-right'],
            ],
        ];
    }

    private static function getJavascriptLogic($baseUrl)
    {
        return "
        <script>
            $(document).ready(function() {
                var baseUrl = '{$baseUrl}';

                function appendToken(url) {
                    var token = $('#AZADA_CRON_KEY').val();
                    var useToken = $('input[name=\"AZADA_USE_SECURE_TOKEN\"]:checked').val() == 1;

                    if (!(useToken && token && token.length > 0)) {
                        return url;
                    }

                    if (url.indexOf('?') === -1) {
                        return url + '?token=' + encodeURIComponent(token);
                    }
                    return url + '&token=' + encodeURIComponent(token);
                }

                function updateCronLinks() {
                    $('#VIEW_CRON_UPDATE_QTY_ABRO').val(appendToken(baseUrl + 'cron_update_qty_abro.php'));
                    $('#VIEW_CRON_UPDATE_QTY').val(appendToken(baseUrl + 'cron_update_qty.php'));
                    $('#VIEW_CRON_IMPORT_LIGHT').val(appendToken(baseUrl + 'cron_import_light.php'));
                    $('#VIEW_CRON_CREATE_PRODUCTS').val(appendToken(baseUrl + 'cron_create_products.php'));
                    $('#VIEW_CRON_UPDATE_PRICE').val(appendToken(baseUrl + 'cron_update_price.php'));
                    $('#VIEW_CRON_IMPORT_FULL').val(appendToken(baseUrl + 'cron_import_full.php'));
                    $('#VIEW_CRON_REBUILD_INDEX').val(appendToken(baseUrl + 'cron_rebuild_index.php'));
                }

                function generateRandomToken() {
                    var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                    var res = '';
                    for (var i = 32; i > 0; --i) res += chars[Math.floor(Math.random() * chars.length)];
                    return res;
                }

                $(document).on('click', '#btn-generate-token', function(e) {
                    e.preventDefault();
                    $('#AZADA_CRON_KEY').val(generateRandomToken());
                    $('#AZADA_CRON_KEY').fadeOut(100).fadeIn(100);
                    updateCronLinks();
                });

                $(document).on('keyup change', '#AZADA_CRON_KEY', updateCronLinks);

                $(document).on('change', 'input[name=\"AZADA_USE_SECURE_TOKEN\"]', function() {
                    updateCronLinks();
                });

                setTimeout(updateCronLinks, 300);
            });
        </script>";
    }
}
