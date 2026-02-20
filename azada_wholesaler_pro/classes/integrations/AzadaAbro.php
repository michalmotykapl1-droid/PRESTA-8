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
        $tableName = _DB_PREFIX_ . 'azada_raw_abro';

        Db::getInstance()->execute("DROP TABLE IF EXISTS `$tableName`");

        if (!AzadaRawSchema::createTable('azada_raw_abro')) {
            return ['status' => 'error', 'msg' => 'Błąd tworzenia tabeli wzorcowej.'];
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

        $insertCols = [
            'kod_kreskowy',
            'eanprzepismatka',
            'produkt_id',
            'kod',
            'nazwa',
            'marka',
            'producentnazwaiadres',
            'opis',
            'kategoria',
            'ilosc',
            'stan_magazynowy_live',
            'cenaporabacienetto',
            'cena_netto_live',
            'cenastandardnetto',
            'vat',
            'ilosc_w_opakowaniu',
            'minimum_logistyczne',
            'zdjecieglownelinkurl',
            'zdjecie1linkurl',
            'zdjecie2linkurl',
            'zdjecie3linkurl',
            'data_aktualizacji',
        ];

        $sqlBase = "INSERT INTO `$tableName` (`" . implode('`,`', $insertCols) . "`) VALUES ";
        $batchValues = [];
        $count = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($offers as $offer) {
            $id = trim((string)$offer['id']);
            $price = self::normalizeDecimal((string)$offer['price']);
            $stock = self::normalizeDecimal((string)$offer['stock']);
            $multiplier = trim((string)$offer['multiplier']);

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
            if ($sku === '') {
                continue;
            }

            $ean = '';
            if (isset($attrs['EAN']) && $attrs['EAN'] !== '') {
                $ean = $attrs['EAN'];
            } elseif ($eanList !== '') {
                $eanCandidates = preg_split('/\s*,\s*/', $eanList);
                $ean = isset($eanCandidates[0]) ? trim((string)$eanCandidates[0]) : '';
            }

            if ($ean === '') {
                continue;
            }

            $vat = self::normalizeDecimal(isset($attrs['VAT']) ? $attrs['VAT'] : '0');
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

            $rowValues = [
                $ean,
                $eanList,
                'ABRO_' . $sku,
                $id,
                $name,
                $producer,
                $producer,
                $desc,
                $category,
                $stock,
                $stock,
                $price,
                $price,
                $price,
                $vat,
                $multiplier,
                $multiplier,
                $mainImage,
                isset($extraImages[0]) ? $extraImages[0] : '',
                isset($extraImages[1]) ? $extraImages[1] : '',
                isset($extraImages[2]) ? $extraImages[2] : '',
                $now,
            ];

            $rowValues = array_map('pSQL', $rowValues);
            $batchValues[] = "('" . implode("','", $rowValues) . "')";
            $count++;

            if (count($batchValues) >= 150) {
                Db::getInstance()->execute($sqlBase . implode(',', $batchValues));
                $batchValues = [];
            }
        }

        if (!empty($batchValues)) {
            Db::getInstance()->execute($sqlBase . implode(',', $batchValues));
        }

        $wholesaler->last_import = date('Y-m-d H:i:s');
        $wholesaler->update();

        return ['status' => 'success', 'msg' => "Tabela zresetowana. Pobrano $count produktów."];
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
