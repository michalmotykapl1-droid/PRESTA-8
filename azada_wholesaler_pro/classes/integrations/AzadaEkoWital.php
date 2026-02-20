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
        'photo1' => 'zdjecie1linkurl',
        'photo2' => 'zdjecie2linkurl',
        'photo3' => 'zdjecie3linkurl',
        'zdjecie1linkurl' => 'zdjecie1linkurl',
        'zdjecie2linkurl' => 'zdjecie2linkurl',
        'zdjecie3linkurl' => 'zdjecie3linkurl',
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

    public static function getBaseUrl()
    {
        return 'https://eko-wital.pl';
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

    public static function importProducts($wholesaler)
    {
        $links = self::generateLinks($wholesaler->api_key);
        $tableName = _DB_PREFIX_ . 'azada_raw_ekowital';

        Db::getInstance()->execute("DROP TABLE IF EXISTS `$tableName`");

        if (!AzadaRawSchema::createTable('azada_raw_ekowital')) {
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
                if ($colName === 'kod_kreskowy' && isset($row[$idx])) { $ean = trim((string)$row[$idx]); break; }
            }
            if ($ean === '') continue;

            $rowValues = [];
            foreach ($colIndexMap as $idx => $colName) {
                $val = isset($row[$idx]) ? trim((string)$row[$idx]) : '';

                if ($colName === 'produkt_id' && $val !== '') {
                    $val = 'EKOWIT_' . $val;
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
