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

    public static function importProducts($wholesaler)
    {
        $links = self::generateLinks($wholesaler->api_key);
        $tableName = _DB_PREFIX_ . 'azada_raw_naturamed';

        Db::getInstance()->execute("DROP TABLE IF EXISTS `$tableName`");

        if (!AzadaRawSchema::createTable('azada_raw_naturamed')) {
            return ['status' => 'error', 'msg' => 'Błąd tworzenia tabeli wzorcowej.'];
        }

        $dbColumnsMap = self::syncTableStructure($links['products']);
        if (!$dbColumnsMap) return ['status' => 'error', 'msg' => 'Błąd nagłówków pliku.'];

        $count = 0;

        $h = @fopen($links['products'], 'r');
        if (!$h) {
            return ['status' => 'error', 'msg' => 'Nie można otworzyć pliku.'];
        }

        $headerLine = self::readFirstNonEmptyLine($h);
        if ($headerLine === false) {
            fclose($h);
            return ['status' => 'error', 'msg' => 'Pusty plik.'];
        }

        $delimiter = self::detectDelimiter($headerLine);
        $csvHeaders = str_getcsv(trim($headerLine), $delimiter);

        $allowedColumns = array_flip(AzadaRawSchema::getColumnNames());
        $colIndexMap = [];

        foreach ($csvHeaders as $index => $rawHeader) {
            $normalized = self::normalizeHeader($rawHeader);
            if (isset(self::$columnMap[$normalized])) {
                $colName = self::$columnMap[$normalized];
                if (isset($allowedColumns[$colName])) {
                    $colIndexMap[(int)$index] = $colName;
                }
            }
        }

        $insertCols = array_values($colIndexMap);
        $insertCols[] = 'data_aktualizacji';

        $sqlBase = "INSERT INTO `$tableName` (`" . implode('`,`', $insertCols) . "`) VALUES ";
        $batchValues = [];

        while (($row = fgetcsv($h, 0, $delimiter)) !== false) {
            if (!is_array($row) || count($row) < 3) continue;

            $ean = '';
            foreach ($colIndexMap as $idx => $colName) {
                if ($colName === 'kod_kreskowy' && isset($row[$idx])) {
                    $ean = trim((string)$row[$idx]);
                    break;
                }
            }
            if ($ean === '') continue;

            $rowValues = [];
            foreach ($colIndexMap as $idx => $colName) {
                $val = isset($row[$idx]) ? trim((string)$row[$idx]) : '';

                if ($colName === 'produkt_id' && $val !== '') {
                    $val = 'NAT_' . $val;
                }

                if (in_array($colName, ['LinkDoProduktu','zdjecieglownelinkurl','zdjecie1linkurl','zdjecie2linkurl','zdjecie3linkurl'], true) && $val !== '') {
                    $val = self::normalizeToAbsoluteUrl($val);
                }

                if (in_array($colName, ['waga','ilosc','cenaporabacienetto','cenadetalicznabrutto','vat'], true)) {
                    $val = str_replace(',', '.', $val);
                    $val = preg_replace('/[^0-9.]/', '', $val);
                    if ($val === '') {
                        $val = '0';
                    }
                }

                $rowValues[] = pSQL($val);
            }

            $rowValues[] = date('Y-m-d H:i:s');

            $batchValues[] = "('" . implode("','", $rowValues) . "')";
            $count++;

            if (count($batchValues) >= 150) {
                Db::getInstance()->execute($sqlBase . implode(',', $batchValues));
                $batchValues = [];
            }
        }

        fclose($h);

        if (!empty($batchValues)) {
            Db::getInstance()->execute($sqlBase . implode(',', $batchValues));
        }

        $wholesaler->last_import = date('Y-m-d H:i:s');
        $wholesaler->update();

        return ['status' => 'success', 'msg' => "Tabela zresetowana. Pobrano $count produktów."];
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
