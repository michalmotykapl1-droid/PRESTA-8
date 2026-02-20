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
