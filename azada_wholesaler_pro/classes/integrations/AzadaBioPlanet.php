<?php

require_once(dirname(__FILE__) . '/../helpers/AzadaFileHelper.php');

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

        $tableName = _DB_PREFIX_ . 'azada_raw_bioplanet';
        
        // Budujemy mapę kolumn (FILTRUJEMY PRZEZ CZARNĄ LISTĘ)
        $columns = [];
        foreach ($headers as $header) {
            // Normalizujemy nazwę do porównania (małe litery, bez spacji)
            $headerNormalized = strtolower(trim($header));

            // SPRAWDZAMY CZY KOLUMNA JEST NA CZARNEJ LIŚCIE
            if (in_array($headerNormalized, self::$ignoredColumns)) {
                continue; // Pomijamy!
            }

            $colName = self::sanitizeColumnName($header);
            if (!empty($colName)) {
                $columns[$colName] = $header; 
            }
        }

        // Dodajemy kolumny techniczne
        // USUNIĘTO: waga_kg_system (na Twoje życzenie)
        $columns['stan_magazynowy_live'] = 'Stan (Live)';
        $columns['cena_netto_live'] = 'Cena (Live)';
        $columns['glebokosc'] = 'Głębokość';
        $columns['szerokosc'] = 'Szerokość';
        $columns['wysokosc'] = 'Wysokość';
        $columns['data_aktualizacji'] = 'Data Pobrania';

        $db = Db::getInstance();
        $tableExists = $db->executeS("SHOW TABLES LIKE '$tableName'");

        if (empty($tableExists)) {
            // TWORZENIE NOWEJ TABELI (Już bez śmieci)
            $sql = "CREATE TABLE `$tableName` (
                `id_raw` int(11) NOT NULL AUTO_INCREMENT, ";
            
            foreach ($columns as $col => $origName) {
                if (in_array($col, ['kod_kreskowy', 'ean', 'kod', 'sku', 'reference', 'produkt_id'])) {
                    $sql .= "`$col` VARCHAR(64) DEFAULT NULL, ";
                }
                elseif (strpos($col, 'cena') !== false || strpos($col, 'waga') !== false || strpos($col, 'vat') !== false || strpos($col, 'koszt') !== false || strpos($col, 'netto') !== false || strpos($col, 'brutto') !== false || in_array($col, ['glebokosc', 'szerokosc', 'wysokosc'])) {
                    $sql .= "`$col` DECIMAL(20,6) DEFAULT 0.000000, ";
                }
                else {
                    $sql .= "`$col` TEXT DEFAULT NULL, ";
                }
            }

            $sql .= "PRIMARY KEY (`id_raw`) ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
            
            if (isset($columns['kod_kreskowy'])) {
                $sql = str_replace('PRIMARY KEY', "INDEX(`kod_kreskowy`), PRIMARY KEY", $sql);
            } elseif (isset($columns['ean'])) {
                $sql = str_replace('PRIMARY KEY', "INDEX(`ean`), PRIMARY KEY", $sql);
            }
            
            $db->execute($sql);

        } else {
            // AKTUALIZACJA (Dodajemy tylko brakujące, nie usuwamy)
            $existingColsQuery = $db->executeS("SHOW COLUMNS FROM `$tableName`");
            $existingCols = array_column($existingColsQuery, 'Field');

            foreach ($columns as $col => $origName) {
                if (!in_array($col, $existingCols)) {
                    if (strpos($col, 'cena') !== false || strpos($col, 'waga') !== false) {
                        $db->execute("ALTER TABLE `$tableName` ADD COLUMN `$col` DECIMAL(20,6) DEFAULT 0.000000");
                    } else {
                        $db->execute("ALTER TABLE `$tableName` ADD COLUMN `$col` TEXT DEFAULT NULL");
                    }
                }
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