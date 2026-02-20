<?php

require_once(dirname(__FILE__) . '/../helpers/AzadaFileHelper.php');
require_once(dirname(__FILE__) . '/../services/AzadaRawSchema.php');

class AzadaNaturaMed
{
    /**
     * Mapowanie nagłówków CSV NaturaMed -> kolumny w AzadaRawSchema.
     */
    public static $columnMap = [
        'ean' => 'kod_kreskowy',
        'id' => 'kod',
        'sku' => 'produkt_id',
        'name' => 'nazwa',
        'brand' => 'marka',
        'desc' => 'opis',
        'url' => 'LinkDoProduktu',
        'photo' => 'zdjecieglownelinkurl',
        'photo1' => 'zdjecie1linkurl',
        'photo2' => 'zdjecie2linkurl',
        'photo3' => 'zdjecie3linkurl',
        'kategoria' => 'kategoria',
        'unit' => 'jednostkapodstawowa',
        'weight' => 'waga',
        'pkwiu' => 'pkwiu',
        'instock' => 'NaStanie',
        'qty' => 'ilosc',
        'availability' => 'dostepnyod',
        'requiredbox' => 'wymagane_oz',
        'quantityperbox' => 'ilosc_w_opakowaniu',
        'priceafterdiscountnet' => 'cenaporabacienetto',
        'vat' => 'vat',
        'retailpricegross' => 'cenadetalicznabrutto',
    ];

    /**
     * NaturaMed w różnych wdrożeniach wystawia feed pod różnymi wariantami endpointów.
     */
    public static function buildCandidateLinks($apiKey)
    {
        $key = trim((string)$apiKey);
        $candidates = [
            // Priorytet: poprawna domena NaturaMed przekazana przez użytkownika
            'https://naturamed.com.pl/xmlapi/2/2/UTF8/' . $key,

            // Dodatkowe warianty kompatybilności
            'https://naturamed.com.pl/xmlapi/2/2/UTF-8/' . $key,
            'http://naturamed.com.pl/xmlapi/2/2/UTF8/' . $key,
            'http://naturamed.com.pl/xmlapi/2/2/UTF-8/' . $key,
            'https://naturamed.com.pl/xmlapi/2/1/UTF8/' . $key,
            'https://naturamed.com.pl/xmlapi/2/1/UTF-8/' . $key,
            'https://naturamed.com.pl/xmlapi/2/999/UTF8/' . $key,
            'https://naturamed.com.pl/xmlapi/2/999/UTF-8/' . $key,

            // Legacy fallback (stara domena)
            'https://naturamed.pl/xmlapi/2/2/UTF8/' . $key,
            'https://naturamed.pl/xmlapi/2/2/UTF-8/' . $key,
            'https://naturamed.pl/xmlapi/2/1/UTF8/' . $key,
            'https://naturamed.pl/xmlapi/2/1/UTF-8/' . $key,
            'https://naturamed.pl/xmlapi/2/999/UTF8/' . $key,
            'https://naturamed.pl/xmlapi/2/999/UTF-8/' . $key,
        ];

        return array_values(array_unique($candidates));
    }

    /**
     * Zwraca pierwszy działający endpoint produktów (albo pierwszy kandydat jako fallback).
     */
    public static function resolveProductsUrl($apiKey)
    {
        $candidates = self::buildCandidateLinks($apiKey);
        if (empty($candidates)) {
            return '';
        }

        foreach ($candidates as $url) {
            $check = AzadaFileHelper::checkUrlDetailed($url);
            if (!empty($check['status'])) {
                return $url;
            }
        }

        return $candidates[0];
    }

    public static function generateLinks($apiKey)
    {
        return [
            'products' => self::resolveProductsUrl($apiKey),
        ];
    }

    public static function getSettings()
    {
        return [
            'file_format' => 'csv',
            'delimiter' => ';',
            'skip_header' => 1,
            'encoding' => 'UTF-8',
        ];
    }

    public static function getBaseUrl()
    {
        return 'https://naturamed.com.pl';
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

    public static function detectDelimiter($line)
    {
        if (strpos($line, ';') !== false) return ';';
        if (strpos($line, "\t") !== false) return "\t";
        if (strpos($line, ',') !== false) return ',';
        return ';';
    }

    public static function normalizeHeader($header)
    {
        $header = trim((string)$header);
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
        $header = strtolower(trim((string)$header));
        $header = preg_replace('/[^a-z0-9]/', '', $header);
        return $header;
    }

    public static function readFirstNonEmptyLine($handle, $maxLines = 50)
    {
        $i = 0;
        while (!feof($handle) && $i < $maxLines) {
            $line = fgets($handle);
            if ($line === false) return false;
            $i++;

            $clean = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            if (trim($clean) === '') continue;
            return $clean;
        }
        return false;
    }

    public static function syncTableStructure($csvUrl)
    {
        $handle = @fopen($csvUrl, 'r');
        if (!$handle) return false;

        $headerLine = self::readFirstNonEmptyLine($handle);
        fclose($handle);

        if ($headerLine === false) return false;

        $delimiter = self::detectDelimiter($headerLine);
        $headers = str_getcsv(trim($headerLine), $delimiter);
        if (empty($headers) || !is_array($headers)) return false;

        if (!AzadaRawSchema::createTable('azada_raw_naturamed')) {
            return false;
        }

        $allowedColumns = array_flip(AzadaRawSchema::getColumnNames());
        $columns = [];
        foreach ($headers as $header) {
            $normalized = self::normalizeHeader($header);
            if (isset(self::$columnMap[$normalized])) {
                $colName = self::$columnMap[$normalized];
                if (isset($allowedColumns[$colName])) {
                    $columns[$colName] = $header;
                }
            }
        }

        if (!isset($columns['kod_kreskowy']) || !isset($columns['produkt_id']) || !isset($columns['kod']) || !isset($columns['nazwa'])) {
            return false;
        }

        return $columns;
    }

    public static function runDiagnostics($apiKey)
    {
        if (empty($apiKey)) {
            return [
                'success' => false,
                'details' => [
                    'api' => ['status' => false, 'msg' => 'Brak klucza API.'],
                    'products' => ['status' => false, 'msg' => 'Brak klucza API.'],
                ],
            ];
        }

        $candidates = self::buildCandidateLinks($apiKey);
        $messages = [];
        $best = ['status' => false, 'http_code' => 0, 'msg' => 'Brak połączenia z API.'];

        foreach ($candidates as $url) {
            $check = AzadaFileHelper::checkUrlDetailed($url);
            $messages[] = $url . ' => ' . (isset($check['msg']) ? $check['msg'] : 'brak odpowiedzi');

            if (!empty($check['status'])) {
                $best = $check;
                $best['msg'] = 'Połączenie OK: ' . $url;
                break;
            }

            $best = $check; // zapamiętujemy ostatni błąd jako najbardziej aktualny
        }

        if (empty($best['status'])) {
            $best['msg'] = 'Sprawdzono endpointy NaturaMed: ' . implode(' | ', $messages);
        }

        $details = [
            'products' => [
                'status' => !empty($best['status']),
                'msg' => isset($best['msg']) ? $best['msg'] : '',
                'http_code' => isset($best['http_code']) ? (int)$best['http_code'] : 0,
            ],
            'api' => [
                'status' => !empty($best['status']),
                'msg' => isset($best['msg']) ? $best['msg'] : '',
                'http_code' => isset($best['http_code']) ? (int)$best['http_code'] : 0,
            ],
        ];

        return ['success' => !empty($best['status']), 'details' => $details];
    }
}
