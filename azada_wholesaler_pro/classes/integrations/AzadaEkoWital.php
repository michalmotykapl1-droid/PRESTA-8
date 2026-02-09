<?php

require_once(dirname(__FILE__) . '/../helpers/AzadaFileHelper.php');
require_once(dirname(__FILE__) . '/../services/AzadaRawSchema.php');

class AzadaEkoWital
{
    public static $columnMap = [
        'ean' => 'kod_kreskowy',
        'id' => 'produkt_id',
        'sku' => 'kod',
        'name' => 'nazwa',
        'brand' => 'marka',
        'desc' => 'opis',
        'photo' => 'zdjecieglownelinkurl',
        'kategoria' => 'kategoria',
        'categories' => 'kategoria',
        'unit' => 'jednostkapodstawowa',
        'weight' => 'waga',
        'quantityperbox' => 'ilosc_w_opakowaniu',
        'requiredbox' => 'wymagane_oz',
        'qty' => 'ilosc',
        'priceafterdiscountnet' => 'cenaporabacienetto',
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

    public static function syncTableStructure($csvUrl)
    {
        $handle = fopen($csvUrl, "r");
        if (!$handle) return false;

        $headerLine = fgets($handle);
        fclose($handle);
        if ($headerLine === false) return false;
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);

        $delimiter = self::detectDelimiter($headerLine);
        $headers = str_getcsv(trim($headerLine), $delimiter);
        if (empty($headers) || !is_array($headers)) return false;

        if (!AzadaRawSchema::createTable('azada_raw_ekowital')) {
            return false;
        }

        $mapping = self::mapHeadersToColumns($headers);
        if (empty($mapping['columns'])) {
            return false;
        }
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
