<?php

require_once(dirname(__FILE__) . '/../helpers/AzadaFileHelper.php');
require_once(dirname(__FILE__) . '/../services/AzadaRawSchema.php');
require_once(dirname(__FILE__) . '/../services/AzadaLogger.php');

class AzadaEkoWital
{
    public static $columnMap = [
        'ean' => 'kod_kreskowy',
        'id' => 'produkt_id',
        'sku' => 'kod',
        'name' => 'nazwa',
        'brand' => 'marka',
        'desc' => 'opis',
        'url' => 'linkdoproduktu',
        'photo' => 'zdjecieglownelinkurl',
        'kategoria' => 'kategoria',
        'categories' => 'kategoria',
        'unit' => 'jednostkapodstawowa',
        'weight' => 'waga',
        'quantityperbox' => 'ilosc_w_opakowaniu',
        'requiredbox' => 'wymagane_oz',
        'instock' => 'nastanie',
        'qty' => 'ilosc',
        'priceafterdiscountnet' => 'cenaprzedrabatemnetto',
        'vat' => 'vat',
        'retailpricegross' => 'cenadetalicznabrutto',
        'availability' => 'dostepnyod'
    ];

    public static function generateLinks($apiKey)
    {
        $key = trim($apiKey);
        return [
            'products' => 'https://eko-wital.pl/xmlapi/2/2/UTF8/' . $key
        ];
    }

    public static function getSettings()
    {
        return ['file_format' => 'csv', 'delimiter' => "\t", 'skip_header' => 1, 'encoding' => 'UTF-8'];
    }

    public static function detectDelimiter($line)
    {
        if (strpos($line, "\t") !== false) {
            return "\t";
        }
        if (strpos($line, ';') !== false) {
            return ';';
        }
        if (strpos($line, ',') !== false) {
            return ',';
        }
        return "\t";
    }

    public static function syncTableStructure($csvUrl, $debug = false, &$debugData = [])
    {
        $handle = fopen($csvUrl, "r");
        if (!$handle) {
            if ($debug) {
                AzadaLogger::addLog('EKOWITAL', 'Nie można otworzyć pliku', $csvUrl, AzadaLogger::SEVERITY_ERROR);
            }
            $debugData['error'] = 'open_failed';
            $debugData['url'] = $csvUrl;
            return false;
        }

        $headerLine = fgets($handle);
        fclose($handle);
        if ($headerLine === false) {
            if ($debug) {
                AzadaLogger::addLog('EKOWITAL', 'Brak nagłówka', 'Pusty plik lub brak pierwszej linii', AzadaLogger::SEVERITY_ERROR);
            }
            $debugData['error'] = 'missing_header';
            return false;
        }
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);

        $delimiter = self::detectDelimiter($headerLine);
        $headers = str_getcsv(trim($headerLine), $delimiter);
        if (empty($headers) || !is_array($headers)) {
            if ($debug) {
                AzadaLogger::addLog(
                    'EKOWITAL',
                    'Nieprawidłowe nagłówki',
                    json_encode(['headerLine' => $headerLine, 'delimiter' => $delimiter], JSON_UNESCAPED_UNICODE),
                    AzadaLogger::SEVERITY_ERROR
                );
            }
            $debugData['error'] = 'invalid_headers';
            $debugData['headerLine'] = $headerLine;
            $debugData['delimiter'] = $delimiter;
            return false;
        }
        $debugData['headerLine'] = $headerLine;
        $debugData['delimiter'] = $delimiter;
        $debugData['headers'] = $headers;

        if (!AzadaRawSchema::createTable('azada_raw_ekowital')) {
            $debugData['error'] = 'create_table_failed';
            return false;
        }

        $mapping = self::mapHeadersToColumns($headers);
        if (empty($mapping['columns'])) {
            if ($debug) {
                AzadaLogger::addLog(
                    'EKOWITAL',
                    'Nie znaleziono mapowania kolumn',
                    json_encode(['headers' => $headers], JSON_UNESCAPED_UNICODE),
                    AzadaLogger::SEVERITY_ERROR
                );
            }
            $debugData['error'] = 'mapping_empty';
            $debugData['mapping'] = $mapping;
            return false;
        }
        $debugData['mapping'] = $mapping;
        return $mapping['columns'];
    }

    public static function normalizeHeader($header)
    {
        $header = iconv('UTF-8', 'ASCII//TRANSLIT', $header);
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]/', '', $header);
        return $header;
    }

    public static function sanitizeColumnName($header)
    {
        $header = iconv('UTF-8', 'ASCII//TRANSLIT', $header);
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = trim($header, '_');
        return $header;
    }

    public static function mapHeadersToColumns(array $headers)
    {
        $allowedColumns = array_flip(AzadaRawSchema::getColumnNames());
        $columns = [];
        $colIndexMap = [];

        foreach ($headers as $index => $header) {
            $normalized = self::normalizeHeader($header);
            $colName = isset(self::$columnMap[$normalized]) ? self::$columnMap[$normalized] : null;

            if (!$colName) {
                $sanitized = self::sanitizeColumnName($header);
                if (isset($allowedColumns[$sanitized])) {
                    $colName = $sanitized;
                }
            }

            if ($colName && isset($allowedColumns[$colName])) {
                $columns[$colName] = $header;
                $colIndexMap[$index] = $colName;
            }
        }

        return [
            'columns' => $columns,
            'colIndexMap' => $colIndexMap,
        ];
    }

    public static function runDiagnostics($apiKey)
    {
        if (empty($apiKey)) return ['success' => false, 'details' => []];
        $links = self::generateLinks($apiKey);
        $check = AzadaFileHelper::checkUrl($links['products']);
        $details = [
            'products' => ['status' => $check],
            'api' => ['status' => $check]
        ];

        return ['success' => $check, 'details' => $details];
    }
}
