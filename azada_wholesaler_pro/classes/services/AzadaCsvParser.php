<?php

class AzadaCsvParser
{
    /**
     * Parsuje plik CSV i zwraca tablicę gotową do zapisu w bazie
     */
    public static function parseCsv($filePath)
    {
        if (!file_exists($filePath)) return [];

        $handle = fopen($filePath, 'r');
        if (!$handle) return [];

        // Wykrywanie separatora
        $separator = ';';
        $line = fgets($handle);
        if (substr_count($line, ',') > substr_count($line, ';')) {
            $separator = ',';
        }
        rewind($handle);

        $header = fgetcsv($handle, 0, $separator);
        // Walidacja nagłówka (czy to w ogóle sensowny plik)
        if (!$header || count($header) < 5) {
            fclose($handle);
            return [];
        }

        $parsedRows = [];

        while (($row = fgetcsv($handle, 0, $separator)) !== false) {
            if (count($row) < 10) continue;

            // MAPOWANIE BIO PLANET (Indeksy kolumn)
            // 1: ID, 2: EAN, 3: Nazwa, 4: Ilość, 5: Jedn, 6: CenaNetto ...
            
            $parsedRows[] = [
                'product_id'  => isset($row[1]) ? trim($row[1]) : '',
                'ean'         => isset($row[2]) ? trim($row[2]) : '',
                'name'        => isset($row[3]) ? trim($row[3]) : '',
                'quantity'    => isset($row[4]) ? self::sanitizeFloat($row[4]) : 0,
                'unit'        => isset($row[5]) ? trim($row[5]) : '',
                'price_net'   => isset($row[6]) ? self::sanitizeFloat($row[6]) : 0.0,
                'value_net'   => isset($row[7]) ? self::sanitizeFloat($row[7]) : 0.0,
                'vat_rate'    => isset($row[8]) ? self::sanitizeFloat($row[8]) : 0,
                'price_gross' => isset($row[9]) ? self::sanitizeFloat($row[9]) : 0.0,
                'value_gross' => isset($row[10]) ? self::sanitizeFloat($row[10]) : 0.0,
            ];
        }
        fclose($handle);
        return $parsedRows;
    }

    /**
     * Czyści kwotę (usuwa PLN, spacje, zamienia przecinek na kropkę)
     */
    public static function sanitizePrice($priceString)
    {
        if (empty($priceString)) return '0.00';
        // Usuń PLN, spacje zwykłe, spacje twarde (UTF-8 i ASCII)
        $clean = str_replace(['PLN', ' ', '&nbsp;', "\xc2\xa0", "\xa0"], '', $priceString);
        // Zamień przecinek na kropkę
        $clean = str_replace(',', '.', $clean);
        return number_format((float)$clean, 2, '.', '');
    }

    /**
     * Pomocnicza do parsowania liczb z CSV (np. ilość)
     */
    public static function sanitizeFloat($string)
    {
        $clean = str_replace([' ', '&nbsp;'], '', $string);
        $clean = str_replace(',', '.', $clean);
        return (float)$clean;
    }

    /**
     * Konwertuje datę PL (22.01.2026) na SQL (2026-01-22)
     */
    public static function sanitizeDate($dateString)
    {
        if (empty($dateString)) return date('Y-m-d');
        $clean = str_replace('.', '-', $dateString);
        $ts = strtotime($clean);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}