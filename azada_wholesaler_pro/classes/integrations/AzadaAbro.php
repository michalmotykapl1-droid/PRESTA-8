<?php

require_once(dirname(__FILE__) . '/../helpers/AzadaFileHelper.php');
require_once(dirname(__FILE__) . '/../services/AzadaRawSchema.php');

class AzadaAbro
{
    const FEED_URL = 'https://api.abro.com.pl/azada/';
    const AUTH_HEADER = 'X-Auth-Key';

    public static function generateLinks($apiKey)
    {
        return [
            'products' => self::FEED_URL,
            'api_key' => trim((string)$apiKey),
        ];
    }

    public static function getSettings()
    {
        return [
            'file_format' => 'xml',
            'delimiter' => '',
            'skip_header' => 0,
            'encoding' => 'UTF-8',
        ];
    }

    public static function getBaseUrl()
    {
        return 'https://hurt.abro.com.pl';
    }

    public static function normalizeToAbsoluteUrl($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }

        $base = rtrim(self::getBaseUrl(), '/');
        if (strpos($url, '/') === 0) {
            return $base . $url;
        }

        return $base . '/' . ltrim($url, '/');
    }

    public static function runDiagnostics($apiKey)
    {
        $apiKey = trim((string)$apiKey);
        if ($apiKey === '') {
            return [
                'success' => false,
                'details' => [
                    'api' => [
                        'status' => false,
                        'msg' => 'Brak API key (X-Auth-Key).',
                    ],
                    'products' => [
                        'status' => false,
                        'msg' => 'Brak API key (X-Auth-Key).',
                    ],
                ],
            ];
        }

        $response = self::requestFeed($apiKey, true);

        $status = !empty($response['status']);
        $msg = isset($response['msg']) ? $response['msg'] : '';

        return [
            'success' => $status,
            'details' => [
                'api' => [
                    'status' => $status,
                    'http_code' => isset($response['http_code']) ? (int)$response['http_code'] : 0,
                    'msg' => $msg,
                ],
                'products' => [
                    'status' => $status,
                    'msg' => $status ? 'Feed ABRO dostępny.' : ($msg !== '' ? $msg : 'Brak dostępu do feedu ABRO.'),
                ],
            ],
        ];
    }

    public static function downloadFeed($apiKey)
    {
        $response = self::requestFeed($apiKey, false);
        if (empty($response['status'])) {
            return [
                'status' => false,
                'msg' => isset($response['msg']) ? $response['msg'] : 'Nie udało się pobrać feedu ABRO.',
                'content' => '',
            ];
        }

        return [
            'status' => true,
            'msg' => 'Feed ABRO pobrany poprawnie.',
            'content' => isset($response['body']) ? (string)$response['body'] : '',
        ];
    }

    public static function importProducts($wholesaler)
    {
        $targetTableName = _DB_PREFIX_ . 'azada_raw_abro';
        $sourceTableName = _DB_PREFIX_ . 'azada_raw_abro_source';
        $conversionTableName = _DB_PREFIX_ . 'azada_raw_abro_conversion';

        Db::getInstance()->execute("DROP TABLE IF EXISTS `$targetTableName`");
        Db::getInstance()->execute("DROP TABLE IF EXISTS `$sourceTableName`");
        Db::getInstance()->execute("DROP TABLE IF EXISTS `$conversionTableName`");

        if (!AzadaRawSchema::createTable('azada_raw_abro')) {
            return ['status' => 'error', 'msg' => 'Błąd tworzenia tabeli docelowej ABRO.'];
        }

        if (!self::ensureTargetExtraColumns($targetTableName)) {
            return ['status' => 'error', 'msg' => 'Błąd dodawania kolumn rozszerzonych ABRO w tabeli docelowej.'];
        }

        if (!self::createSourceTable($sourceTableName)) {
            return ['status' => 'error', 'msg' => 'Błąd tworzenia tabeli surowej ABRO.'];
        }

        if (!self::createConversionTable($conversionTableName)) {
            return ['status' => 'error', 'msg' => 'Błąd tworzenia tabeli konwersji ABRO.'];
        }

        $feed = self::downloadFeed($wholesaler->api_key);
        if (empty($feed['status'])) {
            return ['status' => 'error', 'msg' => isset($feed['msg']) ? $feed['msg'] : 'Nie udało się pobrać XML ABRO.'];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string)$feed['content']);
        if ($xml === false) {
            $msg = 'Błąd parsowania XML ABRO.';
            $errors = libxml_get_errors();
            if (!empty($errors) && isset($errors[0]->message)) {
                $msg .= ' ' . trim((string)$errors[0]->message);
            }
            libxml_clear_errors();
            return ['status' => 'error', 'msg' => $msg];
        }
        libxml_clear_errors();

        $offers = [];
        if (isset($xml->o)) {
            $offers = $xml->o;
        } elseif (isset($xml->offers) && isset($xml->offers->o)) {
            $offers = $xml->offers->o;
        }

        if (empty($offers)) {
            return ['status' => 'error', 'msg' => 'Brak produktów w feedzie ABRO.'];
        }

        $targetInsertCols = [
            'kod_kreskowy',
            'eanprzepismatka',
            'produkt_id',
            'kod',
            'nazwa',
            'marka',
            'producentnazwaiadres',
            'opis',
            'LinkDoProduktu',
            'kategoria',
            'jednostkapodstawowa',
            'ilosc',
            'NaStanie',
            'stan_magazynowy_live',
            'cenaporabacienetto',
            'cena_netto_live',
            'cenastandardnetto',
            'vat',
            'ilosc_w_opakowaniu',
            'minimum_logistyczne',
            'wymagane_oz',
            'source_stock_szt',
            'source_cena_za_sztuke_netto',
            'source_multiplier_w_opakowaniu',
            'source_cena_za_opakowanie_netto',
            'conversion_applied',
            'zdjecieglownelinkurl',
            'zdjecie1linkurl',
            'zdjecie2linkurl',
            'zdjecie3linkurl',
            'data_aktualizacji',
        ];

        $sourceInsertCols = [
            'source_id',
            'source_sku',
            'source_ean',
            'source_ean_list',
            'source_name',
            'source_category',
            'source_desc',
            'source_price_netto',
            'source_stock',
            'source_multiplier',
            'source_vat',
            'source_producer',
            'source_main_image',
            'source_extra_images',
            'imported_at',
        ];

        $conversionInsertCols = [
            'source_id',
            'source_sku',
            'source_ean',
            'source_stock',
            'source_price_netto',
            'source_multiplier',
            'target_stock',
            'target_price_netto',
            'conversion_applied',
            'conversion_note',
            'imported_at',
        ];

        $targetSqlBase = "INSERT INTO `$targetTableName` (`" . implode('`,`', $targetInsertCols) . "`) VALUES ";
        $sourceSqlBase = "INSERT INTO `$sourceTableName` (`" . implode('`,`', $sourceInsertCols) . "`) VALUES ";
        $conversionSqlBase = "INSERT INTO `$conversionTableName` (`" . implode('`,`', $conversionInsertCols) . "`) VALUES ";

        $targetBatchValues = [];
        $sourceBatchValues = [];
        $conversionBatchValues = [];

        $targetCount = 0;
        $sourceCount = 0;
        $conversionCount = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($offers as $offer) {
            $id = trim((string)$offer['id']);
            $rawPrice = (float)self::normalizeDecimal((string)$offer['price']);
            $rawStock = (float)self::normalizeDecimal((string)$offer['stock']);
            $multiplierRaw = trim((string)$offer['multiplier']);
            $packQty = self::normalizePackQty($multiplierRaw);

            $name = trim((string)$offer->name);
            $category = trim((string)$offer->cat);
            $desc = trim((string)$offer->desc);
            $eanList = trim((string)$offer->{'ean-list'});

            $attrs = [];
            if (isset($offer->attrs) && isset($offer->attrs->a)) {
                foreach ($offer->attrs->a as $attr) {
                    $attrName = trim((string)$attr['name']);
                    if ($attrName !== '') {
                        $attrs[$attrName] = trim((string)$attr);
                    }
                }
            }

            $sku = isset($attrs['SKU']) && $attrs['SKU'] !== '' ? $attrs['SKU'] : $id;
            $ean = '';
            if (isset($attrs['EAN']) && $attrs['EAN'] !== '') {
                $ean = $attrs['EAN'];
            } elseif ($eanList !== '') {
                $eanCandidates = preg_split('/\s*,\s*/', $eanList);
                $ean = isset($eanCandidates[0]) ? trim((string)$eanCandidates[0]) : '';
            }

            $vat = (float)self::normalizeDecimal(isset($attrs['VAT']) ? $attrs['VAT'] : '0');
            $producer = isset($attrs['Producent']) ? $attrs['Producent'] : '';

            $mainImage = '';
            $extraImages = [];
            if (isset($offer->imgs)) {
                if (isset($offer->imgs->main)) {
                    $mainImage = self::normalizeToAbsoluteUrl((string)$offer->imgs->main['url']);
                }
                if (isset($offer->imgs->i)) {
                    foreach ($offer->imgs->i as $imgNode) {
                        $url = self::normalizeToAbsoluteUrl((string)$imgNode['url']);
                        if ($url !== '') {
                            $extraImages[] = $url;
                        }
                    }
                }
            }

            if ($mainImage === '' && !empty($extraImages)) {
                $mainImage = $extraImages[0];
            }

            // 1) Zapis surowy 1:1 do osobnej tabeli ABRO
            $sourceRowValues = [
                $id,
                $sku,
                $ean,
                $eanList,
                $name,
                $category,
                $desc,
                self::formatDecimal($rawPrice),
                self::formatDecimal($rawStock),
                self::formatDecimal($packQty),
                self::formatDecimal($vat),
                $producer,
                $mainImage,
                implode(',', $extraImages),
                $now,
            ];

            $sourceRowValues = array_map('pSQL', $sourceRowValues);
            $sourceBatchValues[] = "('" . implode("','", $sourceRowValues) . "')";
            $sourceCount++;

            if (count($sourceBatchValues) >= 150) {
                Db::getInstance()->execute($sourceSqlBase . implode(',', $sourceBatchValues));
                $sourceBatchValues = [];
            }

            // 2) Zapis docelowy do wspólnych kolumn modułu
            if ($sku === '' || $ean === '') {
                continue;
            }

            $targetStock = $rawStock;
            $targetPrice = $rawPrice;
            $minimumLogistic = '';
            $requiredPack = 'False';
            $packQtyValue = '';
            $conversionApplied = 0;
            $conversionNote = 'Brak przeliczenia: multiplier pusty/0.';

            if ($packQty > 0) {
                $targetStock = floor($rawStock / $packQty);
                $targetPrice = $rawPrice * $packQty;
                $minimumLogistic = '1';
                $requiredPack = 'True';
                $packQtyValue = (string)$packQty;
                $conversionApplied = 1;
                $conversionNote = 'Przeliczono po opakowaniu: stan=floor(stock/multiplier), cena=price*multiplier.';
            }

            $targetDescription = self::buildTargetDescription($desc, $packQty);
            $targetUnit = ($packQty > 0) ? 'opak' : 'szt';

            $targetRowValues = [
                $ean,
                $eanList,
                'ABRO_' . $sku,
                $id,
                $name,
                $producer,
                $producer,
                $targetDescription,
                self::buildProductUrl($name, $sku, $ean),
                $category,
                $targetUnit,
                self::formatQuantity($targetStock),
                ((float)$targetStock >= 1.0 ? 'True' : 'False'),
                self::formatQuantity($targetStock),
                self::formatDecimal($targetPrice),
                self::formatDecimal($targetPrice),
                self::formatDecimal($targetPrice),
                self::formatDecimal($vat),
                $packQtyValue,
                $minimumLogistic,
                $requiredPack,
                self::formatQuantity($rawStock),
                self::formatDecimal($rawPrice),
                self::formatDecimal($packQty),
                self::formatDecimal($targetPrice),
                (string)$conversionApplied,
                $mainImage,
                isset($extraImages[0]) ? $extraImages[0] : '',
                isset($extraImages[1]) ? $extraImages[1] : '',
                isset($extraImages[2]) ? $extraImages[2] : '',
                $now,
            ];

            $targetRowValues = array_map('pSQL', $targetRowValues);
            $targetBatchValues[] = "('" . implode("','", $targetRowValues) . "')";
            $targetCount++;

            if (count($targetBatchValues) >= 150) {
                Db::getInstance()->execute($targetSqlBase . implode(',', $targetBatchValues));
                $targetBatchValues = [];
            }

            // 3) Jawna tabela konwersji (audyt co moduł przeliczył)
            $conversionRowValues = [
                $id,
                $sku,
                $ean,
                self::formatQuantity($rawStock),
                self::formatDecimal($rawPrice),
                self::formatDecimal($packQty),
                self::formatQuantity($targetStock),
                self::formatDecimal($targetPrice),
                (string)$conversionApplied,
                $conversionNote,
                $now,
            ];

            $conversionRowValues = array_map('pSQL', $conversionRowValues);
            $conversionBatchValues[] = "('" . implode("','", $conversionRowValues) . "')";
            $conversionCount++;

            if (count($conversionBatchValues) >= 150) {
                Db::getInstance()->execute($conversionSqlBase . implode(',', $conversionBatchValues));
                $conversionBatchValues = [];
            }
        }

        if (!empty($sourceBatchValues)) {
            Db::getInstance()->execute($sourceSqlBase . implode(',', $sourceBatchValues));
        }

        if (!empty($targetBatchValues)) {
            Db::getInstance()->execute($targetSqlBase . implode(',', $targetBatchValues));
        }

        if (!empty($conversionBatchValues)) {
            Db::getInstance()->execute($conversionSqlBase . implode(',', $conversionBatchValues));
        }

        $wholesaler->last_import = date('Y-m-d H:i:s');
        $wholesaler->update();

        return ['status' => 'success', 'msg' => "ABRO OK. Surowe: $sourceCount, docelowe: $targetCount, konwersje: $conversionCount produktów."];
    }

    private static function ensureTargetExtraColumns($fullTableName)
    {
        $db = Db::getInstance();

        $columns = [
            'source_stock_szt' => 'TEXT DEFAULT NULL',
            'source_cena_za_sztuke_netto' => 'DECIMAL(20,6) DEFAULT 0.000000',
            'source_multiplier_w_opakowaniu' => 'DECIMAL(20,6) DEFAULT 0.000000',
            'source_cena_za_opakowanie_netto' => 'DECIMAL(20,6) DEFAULT 0.000000',
            'conversion_applied' => 'TINYINT(1) DEFAULT 0',
        ];

        foreach ($columns as $columnName => $definition) {
            $exists = (bool)$db->getValue(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = '" . pSQL($fullTableName) . "'
                   AND COLUMN_NAME = '" . pSQL($columnName) . "'"
            );

            if ($exists) {
                continue;
            }

            $sql = "ALTER TABLE `" . bqSQL($fullTableName) . "` ADD COLUMN `" . bqSQL($columnName) . "` " . $definition;
            if (!$db->execute($sql)) {
                return false;
            }
        }

        return true;
    }

    private static function createSourceTable($fullTableName)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `$fullTableName` (
            `id_source` int(11) NOT NULL AUTO_INCREMENT,
            `source_id` varchar(128) DEFAULT NULL,
            `source_sku` varchar(128) DEFAULT NULL,
            `source_ean` varchar(64) DEFAULT NULL,
            `source_ean_list` text DEFAULT NULL,
            `source_name` text DEFAULT NULL,
            `source_category` text DEFAULT NULL,
            `source_desc` longtext,
            `source_price_netto` decimal(20,6) DEFAULT 0.000000,
            `source_stock` decimal(20,6) DEFAULT 0.000000,
            `source_multiplier` decimal(20,6) DEFAULT 0.000000,
            `source_vat` decimal(20,6) DEFAULT 0.000000,
            `source_producer` text DEFAULT NULL,
            `source_main_image` text DEFAULT NULL,
            `source_extra_images` longtext,
            `imported_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id_source`),
            KEY `idx_source_id` (`source_id`),
            KEY `idx_source_ean` (`source_ean`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        return (bool)Db::getInstance()->execute($sql);
    }

    private static function createConversionTable($fullTableName)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `$fullTableName` (
            `id_conversion` int(11) NOT NULL AUTO_INCREMENT,
            `source_id` varchar(128) DEFAULT NULL,
            `source_sku` varchar(128) DEFAULT NULL,
            `source_ean` varchar(64) DEFAULT NULL,
            `source_stock` decimal(20,6) DEFAULT 0.000000,
            `source_price_netto` decimal(20,6) DEFAULT 0.000000,
            `source_multiplier` decimal(20,6) DEFAULT 0.000000,
            `target_stock` decimal(20,6) DEFAULT 0.000000,
            `target_price_netto` decimal(20,6) DEFAULT 0.000000,
            `conversion_applied` tinyint(1) DEFAULT 0,
            `conversion_note` text DEFAULT NULL,
            `imported_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id_conversion`),
            KEY `idx_source_id` (`source_id`),
            KEY `idx_source_ean` (`source_ean`),
            KEY `idx_conversion_applied` (`conversion_applied`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        return (bool)Db::getInstance()->execute($sql);
    }



    private static function buildTargetDescription($desc, $packQty)
    {
        $desc = trim((string)$desc);
        $packQty = (int)$packQty;

        if ($packQty <= 1) {
            return $desc;
        }

        $prefix = 'Sprzedaż na opakowania zbiorcze: ' . $packQty . ' szt.';

        if ($desc === '') {
            return $prefix;
        }

        if (stripos($desc, 'Sprzedaż na opakowania zbiorcze:') === 0) {
            return $desc;
        }

        return $prefix . "

" . $desc;
    }

    private static function buildProductUrl($name, $sku, $ean)
    {
        $ean = trim((string)$ean);
        if ($ean !== '') {
            return 'https://b2b.abro.com.pl/index.php?do_search=true&search_query=' . urlencode($ean);
        }

        $sku = trim((string)$sku);
        if ($sku !== '') {
            return 'https://b2b.abro.com.pl/index.php?do_search=true&search_query=' . urlencode($sku);
        }

        $name = trim((string)$name);
        if ($name !== '') {
            return 'https://b2b.abro.com.pl/index.php?do_search=true&search_query=' . urlencode($name);
        }

        return '';
    }

    private static function normalizePackQty($value)
    {
        $normalized = (float)self::normalizeDecimal($value);
        if ($normalized <= 0) {
            return 0;
        }

        return (int)floor($normalized);
    }

    private static function formatQuantity($value)
    {
        $value = (float)$value;
        if ((float)(int)$value === $value) {
            return (string)(int)$value;
        }

        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }

    private static function formatDecimal($value)
    {
        return number_format((float)$value, 6, '.', '');
    }

    private static function normalizeDecimal($value)
    {
        $value = str_replace(',', '.', trim((string)$value));
        $value = preg_replace('/[^0-9.\-]/', '', $value);
        if ($value === '' || $value === '-' || $value === '.' || $value === '-.') {
            return '0';
        }
        return $value;
    }

    private static function requestFeed($apiKey, $rangeOnly = false)
    {
        $apiKey = trim((string)$apiKey);
        if ($apiKey === '') {
            return [
                'status' => false,
                'http_code' => 0,
                'msg' => 'Brak API key (X-Auth-Key).',
            ];
        }

        $ch = curl_init(self::FEED_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [self::AUTH_HEADER . ': ' . $apiKey]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; AzadaWholesalerPro/2.6.0; +ABRO)');

        if ($rangeOnly) {
            curl_setopt($ch, CURLOPT_RANGE, '0-1024');
        }

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo = (int)curl_errno($ch);
        $curlErr = (string)curl_error($ch);
        curl_close($ch);

        if ($curlErrNo !== 0) {
            return [
                'status' => false,
                'http_code' => $httpCode,
                'msg' => 'Błąd CURL: ' . $curlErr,
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'status' => false,
                'http_code' => $httpCode,
                'msg' => 'Endpoint ABRO zwrócił HTTP ' . $httpCode . '.',
            ];
        }

        $body = (string)$body;
        if ($body === '') {
            return [
                'status' => false,
                'http_code' => $httpCode,
                'msg' => 'Pusty response z endpointu ABRO.',
            ];
        }

        $isXml = (strpos(ltrim($body), '<?xml') === 0) || (stripos($body, '<offers') !== false);
        if (!$isXml) {
            return [
                'status' => false,
                'http_code' => $httpCode,
                'msg' => 'Endpoint ABRO odpowiedział, ale payload nie wygląda na XML.',
            ];
        }

        return [
            'status' => true,
            'http_code' => $httpCode,
            'msg' => 'Połączenie OK (ABRO XML + X-Auth-Key).',
            'body' => $body,
        ];
    }
}
