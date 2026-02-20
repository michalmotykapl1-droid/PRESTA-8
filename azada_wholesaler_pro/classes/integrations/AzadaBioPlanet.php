<?php

require_once(dirname(__FILE__) . '/../helpers/AzadaFileHelper.php');
require_once(dirname(__FILE__) . '/../services/AzadaRawSchema.php');

class AzadaBioPlanet
{
    /**
     * --- CZARNA LISTA KOLUMN (TE NIE ZOSTANĄ UTWORZONE) ---
     */
    public static $ignoredColumns = [
        // Zostawiamy pustą listę: mapujemy pełny feed BioPlanet 1:1 do kolumn RAW.
    ];

    public static function generateLinks($apiKey)
    {
        $key = trim($apiKey);
        return [
            'products' => 'https://bioplanet.pl/xmlapi/2/999/UTF8/' . $key,
            'stocks'   => 'https://bioplanet.pl/xmlapi/99/1/UTF8/' . $key,
            'weights'  => 'https://bioplanet.pl/xmlapi/998/1/UTF8/' . $key
        ];
    }

    /**
     * TWORZENIE STRUKTURY (FILTRUJEMY ŚMIECI)
     */
    public static function syncTableStructure($csvUrl)
    {
        $handle = fopen($csvUrl, "r");
        if (!$handle) return false;
        
        $headers = fgetcsv($handle, 0, ";");
        fclose($handle);

        if (empty($headers) || !is_array($headers)) return false;
        if (!AzadaRawSchema::createTable('azada_raw_bioplanet')) {
            return false;
        }

                $allowedColumns = array_flip(AzadaRawSchema::getColumnNames());
        $columns = [];
        foreach ($headers as $header) {
            $headerNormalized = strtolower(trim($header));
            if (in_array($headerNormalized, self::$ignoredColumns)) {
                continue;
            }

            $colName = self::sanitizeColumnName($header);
            if (!empty($colName) && isset($allowedColumns[$colName])) {
                $columns[$colName] = $header;
            }
        }



        return $columns;
    }

    public static function getBaseUrl()
    {
        return 'https://bioplanet.pl';
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

    public static function sanitizeColumnName($str)
    {
        $str = trim((string)$str);
        $normalized = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $normalized = strtolower((string)$normalized);
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);

        $aliases = [
            'url' => 'LinkDoProduktu',
            'linkdoproduktu' => 'LinkDoProduktu',
            'photo' => 'zdjecieglownelinkurl',
            'nastanie' => 'NaStanie',
            'photo1' => 'zdjecie1linkurl',
            'photo2' => 'zdjecie2linkurl',
            'photo3' => 'zdjecie3linkurl',
            'zdjecie1linkurl' => 'zdjecie1linkurl',
            'zdjecie2linkurl' => 'zdjecie2linkurl',
            'zdjecie3linkurl' => 'zdjecie3linkurl',
        ];

        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }

        $str = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = strtolower((string)$str);
        $str = preg_replace('/[^a-z0-9_]/', '_', $str);
        return trim($str, '_');
    }

    public static function importProducts($wholesaler)
    {
        $links = self::generateLinks($wholesaler->api_key);
        $tableName = _DB_PREFIX_ . 'azada_raw_bioplanet';

        Db::getInstance()->execute("DROP TABLE IF EXISTS `$tableName`");

        if (!AzadaRawSchema::createTable('azada_raw_bioplanet')) {
            return ['status' => 'error', 'msg' => 'Błąd tworzenia tabeli wzorcowej.'];
        }

        $dbColumnsMap = self::syncTableStructure($links['products']);
        if (!$dbColumnsMap) return ['status' => 'error', 'msg' => 'Błąd nagłówków CSV.'];

        $dims = [];
        if (($h = fopen($links['weights'], "r")) !== false) {
            fgetcsv($h, 0, ";");
            while (($row = fgetcsv($h, 1000, ";")) !== false) {
                if (!empty($row[0])) {
                    $dims[$row[0]] = [
                        'd' => (float)str_replace(',', '.', $row[1]),
                        'w' => (float)str_replace(',', '.', $row[2]),
                        'h' => (float)str_replace(',', '.', $row[3]),
                    ];
                }
            }
            fclose($h);
        }

        $stocks = [];
        if (($h = fopen($links['stocks'], "r")) !== false) {
            fgetcsv($h, 0, ";");
            while (($row = fgetcsv($h, 1000, ";")) !== false) {
                if (!empty($row[0])) {
                    $stocks[$row[0]] = [
                        'qty' => (int)$row[4],
                        'price' => (float)str_replace(',', '.', $row[6]),
                    ];
                }
            }
            fclose($h);
        }

        $count = 0;
        if (($h = fopen($links['products'], "r")) !== false) {
            $csvHeaders = fgetcsv($h, 0, ";");
            $allowedColumns = array_flip(array_keys($dbColumnsMap));
            $colIndexMap = [];

            foreach ($csvHeaders as $index => $rawHeader) {
                $rawHeaderNormalized = strtolower(trim($rawHeader));
                if (in_array($rawHeaderNormalized, self::$ignoredColumns)) {
                    continue;
                }
                $sanitized = self::sanitizeColumnName($rawHeader);
                if (!empty($sanitized) && isset($allowedColumns[$sanitized])) {
                    $colIndexMap[$index] = $sanitized;
                }
            }

            $insertCols = array_values($colIndexMap);
            $insertCols[] = 'stan_magazynowy_live';
            $insertCols[] = 'cena_netto_live';
            $insertCols[] = 'glebokosc';
            $insertCols[] = 'szerokosc';
            $insertCols[] = 'wysokosc';
            $insertCols[] = 'data_aktualizacji';

            $sqlBase = "INSERT INTO `$tableName` (`" . implode('`,`', $insertCols) . "`) VALUES ";
            $batchValues = [];

            while (($row = fgetcsv($h, 8192, ";")) !== false) {
                $eanIndex = array_search('kod_kreskowy', $colIndexMap);
                $ean = ($eanIndex !== false && isset($row[$eanIndex])) ? $row[$eanIndex] : '';
                if (empty($ean)) {
                    $codeIndex = array_search('kod', $colIndexMap);
                    $ean = ($codeIndex !== false && isset($row[$codeIndex])) ? $row[$codeIndex] : '';
                }
                if (empty($ean)) continue;

                $rowValues = [];
                foreach ($colIndexMap as $index => $colName) {
                    $val = isset($row[$index]) ? $row[$index] : '';
                    if ($colName === 'produkt_id') {
                        $val = 'BP_' . trim($val);
                    }
                    if (in_array($colName, ['LinkDoProduktu','zdjecieglownelinkurl','zdjecie1linkurl','zdjecie2linkurl','zdjecie3linkurl'], true) && trim((string)$val) !== '') {
                        $val = self::normalizeToAbsoluteUrl($val);
                    }
                    if (strpos($colName, 'cena') !== false || strpos($colName, 'waga') !== false || strpos($colName, 'vat') !== false || strpos($colName, 'koszt') !== false || strpos($colName, 'netto') !== false || strpos($colName, 'brutto') !== false) {
                        $val = str_replace(',', '.', $val);
                        $val = preg_replace('/[^0-9.]/', '', $val);
                        if ($val === '') $val = '0';
                    }
                    $rowValues[] = pSQL(trim($val));
                }

                $myStock = isset($stocks[$ean]) ? $stocks[$ean] : ['qty' => 0, 'price' => 0];
                $myDim = isset($dims[$ean]) ? $dims[$ean] : ['d' => 0, 'w' => 0, 'h' => 0];

                $rowValues[] = (int)$myStock['qty'];
                $rowValues[] = (float)$myStock['price'];
                $rowValues[] = (float)$myDim['d'];
                $rowValues[] = (float)$myDim['w'];
                $rowValues[] = (float)$myDim['h'];
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
        }

        $wholesaler->last_import = date('Y-m-d H:i:s');
        $wholesaler->update();

        return ['status' => 'success', 'msg' => "Tabela zresetowana. Pobrano $count produktów."];
    }

    public static function runDiagnostics($apiKey)
    {
        if (empty($apiKey)) return ['success' => false, 'details' => []];
        $links = self::generateLinks($apiKey);
        $details = [];
        $allOk = true;
        
        $check1 = AzadaFileHelper::checkUrl($links['products']);
        $details['products'] = ['status' => $check1];
        if (!$check1) $allOk = false;

        $check2 = AzadaFileHelper::checkUrl($links['stocks']);
        $details['stocks'] = ['status' => $check2];
        if (!$check2) $allOk = false;

        $check3 = AzadaFileHelper::checkUrl($links['weights']);
        $details['weights'] = ['status' => $check3];
        if (!$check3) $allOk = false;

        $details['api'] = ['status' => $allOk];

        return ['success' => $allOk, 'details' => $details];
    }

    public static function getSettings() {
        return ['file_format' => 'csv', 'delimiter' => ';', 'skip_header' => 1, 'encoding' => 'UTF-8'];
    }
}
