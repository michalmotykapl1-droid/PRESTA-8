<?php

require_once(dirname(__FILE__) . '/../helpers/AzadaFileHelper.php');
require_once(dirname(__FILE__) . '/../services/AzadaRawSchema.php');

class AzadaEkoWital
{
    /**
     * Map normalized CSV headers -> DB column names (AzadaRawSchema)
     * normalizeHeader() lowercases and strips non-alnum.
     */
    public static $columnMap = [
        // źródło
        'ean' => 'kod_kreskowy',
        'id' => 'kod',
        'sku' => 'produkt_id',
        'name' => 'nazwa',
        'brand' => 'marka',
        'desc' => 'opis',
        'url' => 'LinkDoProduktu',
        'photo' => 'zdjecieglownelinkurl',
        'kategoria' => 'kategoria',
        'categories' => 'kategoria',
        'unit' => 'jednostkapodstawowa',
        'weight' => 'waga',
        'instock' => 'NaStanie',
        'qty' => 'ilosc',
        'availability' => 'dostepnyod',
        'requiredbox' => 'wymagane_oz',
        'quantityperbox' => 'ilosc_w_opakowaniu',
        'priceafterdiscountnet' => 'cenaporabacienetto',
        'vat' => 'vat',
        // retailPriceGross - nie używamy
    ];

    public static function generateLinks($apiKey)
    {
        $key = trim($apiKey);
        return [
            'products' => 'https://eko-wital.pl/xmlapi/2/2/UTF8/' . $key,
        ];
    }

    public static function getSettings()
    {
        // W praktyce EkoWital wystawia CSV (często ; i BOM)
        return [
            'file_format' => 'csv',
            'delimiter' => ';',
            'skip_header' => 1,
            'encoding' => 'UTF-8',
        ];
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
        // usuń BOM
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
        $header = strtolower(trim((string)$header));
        $header = preg_replace('/[^a-z0-9]/', '', $header);
        return $header;
    }

    /**
     * Czyta pierwszą niepustą linię (obsługa plików z pustą linią na początku).
     */
    public static function readFirstNonEmptyLine($handle, $maxLines = 50)
    {
        $i = 0;
        while (!feof($handle) && $i < $maxLines) {
            $line = fgets($handle);
            if ($line === false) return false;
            $i++;
            // usuń BOM i białe znaki
            $clean = preg_replace('/^\xEF\xBB\xBF/', '', $line);
            if (trim($clean) === '') continue;
            return $clean;
        }
        return false;
    }

    /**
     * Sprawdza nagłówki i zwraca mapę dozwolonych kolumn (dbCol => originalHeader)
     * — wykorzystywane tylko do walidacji.
     */
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

        // upewnij się, że tabela istnieje (wzorzec)
        if (!AzadaRawSchema::createTable('azada_raw_ekowital')) {
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

        // wymagamy minimum: EAN + ID + SKU + NAME
        if (!isset($columns['kod_kreskowy']) || !isset($columns['produkt_id']) || !isset($columns['kod']) || !isset($columns['nazwa'])) {
            return false;
        }

        return $columns;
    }

    public static function runDiagnostics($apiKey)
    {
        if (empty($apiKey)) return ['success' => false, 'details' => []];
        $links = self::generateLinks($apiKey);
        $check = AzadaFileHelper::checkUrl($links['products']);

        $details = [
            'products' => ['status' => $check],
            'api' => ['status' => $check],
        ];

        return ['success' => $check, 'details' => $details];
    }
}
