<?php

require_once(dirname(__FILE__) . '/../helpers/AzadaFileHelper.php');
require_once(dirname(__FILE__) . '/../services/AzadaRawSchema.php');

class AzadaBioPlanet
{
    /**
     * --- CZARNA LISTA KOLUMN (TE NIE ZOSTANĄ UTWORZONE) ---
     */
    public static $ignoredColumns = [
        'nastanie',
        'masanetto',
        'terminprzydataktualnypromowyprzeprzece',
        'eanprzepismatka',
        'cenazakg',
        'cenazal',
        'linkdoproduktu',    // Dodatkowo (standardowy śmieć)
        'kodyopzbiorczych',  // Dodatkowo
        'kod_cn',            // Dodatkowo
        'objetoscbrutto',    // Dodatkowo
        'objetoscnetto'      // Dodatkowo
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

    public static function sanitizeColumnName($str)
    {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str); 
        $str = strtolower($str);
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
