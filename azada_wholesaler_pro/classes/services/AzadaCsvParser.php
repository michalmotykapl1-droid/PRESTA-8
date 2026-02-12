<?php

class AzadaCsvParser
{
    /**
     * Parsuje plik CSV i zwraca tablicę gotową do zapisu w bazie
     */
    public static function parseCsv($filePath)
    {
        if (!file_exists($filePath)) return [];

        $content = file_get_contents($filePath);
        if (empty($content)) return [];

        // Ujednolicenie znaków nowej linii i usunięcie BOM
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace('/^[\xef\xbb\xbf]+/', '', $content);
        $lines = explode("\n", $content);

        if (empty($lines)) return [];

        $separator = ';';
        $headerRowIndex = -1;
        $headerLine = '';

        // SKANER: Przeszukujemy pierwsze 30 wierszy, aby ominąć metryczkę i znaleźć prawdziwe nagłówki tabeli
        for ($i = 0; $i < min(30, count($lines)); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $sep = (substr_count($line, ';') >= substr_count($line, ',')) ? ';' : ',';
            $cols = str_getcsv($line, $sep);
            
            $lineLower = mb_strtolower($line, 'UTF-8');
            
            // Jeśli wiersz ma minimum 4 kolumny i zawiera słowa kluczowe tabeli produktów:
            if (count($cols) >= 4 && (strpos($lineLower, 'nazwa') !== false || strpos($lineLower, 'cena') !== false || strpos($lineLower, 'towar') !== false || strpos($lineLower, 'kod') !== false || strpos($lineLower, 'ean') !== false)) {
                $headerRowIndex = $i;
                $separator = $sep;
                $headerLine = $line;
                break;
            }
        }

        // Fallback dla BioPlanet
        if ($headerRowIndex === -1) {
            $headerRowIndex = 0;
            $headerLine = $lines[0];
            $separator = (substr_count($headerLine, ';') >= substr_count($headerLine, ',')) ? ';' : ',';
        }

        $header = str_getcsv($headerLine, $separator);

        // Domyślne mapowanie dla Bio Planet (twarde indeksy)
        $map = [
            'product_id'  => 1,
            'ean'         => 2,
            'name'        => 3,
            'quantity'    => 4,
            'unit'        => 5,
            'price_net'   => 6,
            'value_net'   => 7,
            'vat_rate'    => 8,
            'price_gross' => 9,
            'value_gross' => 10
        ];

        // DYNAMICZNE MAPOWANIE (Dla EkoWitala i innych formatek)
        $detected = false;
        foreach ($header as $i => $col) {
            $colClean = mb_strtolower(preg_replace('/[^a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/u', '', $col), 'UTF-8');
            if (empty($colClean)) continue;

            if (in_array($colClean, ['kodkreskowy', 'kod', 'symbol']) || strpos($colClean, 'ean') !== false) { $map['ean'] = $i; $detected = true; }
            // ZMIANA TUTAJ: Wyszukujemy 'produktnazwa' lub po prostu części słowa 'nazwa'
            elseif (in_array($colClean, ['nazwa', 'nazwatowaru', 'towar', 'produkt', 'produktnazwa']) || strpos($colClean, 'nazwa') !== false) { $map['name'] = $i; $detected = true; }
            elseif (in_array($colClean, ['ilosc', 'ilość', 'sztuk', 'il'])) { $map['quantity'] = $i; $detected = true; }
            elseif (in_array($colClean, ['jm', 'jednostka', 'miara'])) { $map['unit'] = $i; $detected = true; }
            elseif (strpos($colClean, 'cenanetto') !== false) { $map['price_net'] = $i; $detected = true; }
            elseif (strpos($colClean, 'wartoscnetto') !== false || strpos($colClean, 'wartośćnetto') !== false) { $map['value_net'] = $i; $detected = true; }
            elseif (strpos($colClean, 'vat') !== false || strpos($colClean, 'stawkavat') !== false || $colClean === 'stawka') { $map['vat_rate'] = $i; $detected = true; }
            elseif (strpos($colClean, 'cenabrutto') !== false) { $map['price_gross'] = $i; $detected = true; }
            elseif (strpos($colClean, 'wartoscbrutto') !== false || strpos($colClean, 'wartośćbrutto') !== false) { $map['value_gross'] = $i; $detected = true; }
            elseif (in_array($colClean, ['id', 'indeks', 'index'])) { $map['product_id'] = $i; $detected = true; }
        }

        // Dopasowanie awaryjne dla "Ceny", jeśli nie było wprost napisane "Cena netto"
        if ($detected && $map['price_net'] === 6) { 
            foreach ($header as $i => $col) {
                $colClean = mb_strtolower(preg_replace('/[^a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/u', '', $col), 'UTF-8');
                if (strpos($colClean, 'cena') !== false && strpos($colClean, 'brutto') === false) {
                    $map['price_net'] = $i;
                    break;
                }
            }
        }

        $parsedRows = [];

        // Przetwarzanie od wiersza poniżej nagłówków!
        for ($i = $headerRowIndex + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $row = str_getcsv($line, $separator);
            if (count($row) < 4) continue;
            
            $name = isset($row[$map['name']]) ? trim($row[$map['name']]) : '';
            $ean = isset($row[$map['ean']]) ? trim($row[$map['ean']]) : '';

            // Pomijamy puste linie, śmieci i wiersze z podsumowaniem stopki EkoWitala
            if (empty($name) || stripos($name, 'podsumowanie') !== false || stripos($name, 'razem') !== false || stripos($name, 'do zapłaty') !== false) {
                continue;
            }

            $parsedRows[] = [
                'product_id'  => isset($row[$map['product_id']]) ? trim($row[$map['product_id']]) : '',
                'ean'         => $ean,
                'name'        => $name,
                'quantity'    => isset($row[$map['quantity']]) ? self::sanitizeFloat($row[$map['quantity']]) : 0,
                'unit'        => isset($row[$map['unit']]) ? trim($row[$map['unit']]) : '',
                'price_net'   => isset($row[$map['price_net']]) ? self::sanitizeFloat($row[$map['price_net']]) : 0.0,
                'value_net'   => isset($row[$map['value_net']]) ? self::sanitizeFloat($row[$map['value_net']]) : 0.0,
                'vat_rate'    => isset($row[$map['vat_rate']]) ? self::sanitizeFloat($row[$map['vat_rate']]) : 0,
                'price_gross' => isset($row[$map['price_gross']]) ? self::sanitizeFloat($row[$map['price_gross']]) : 0.0,
                'value_gross' => isset($row[$map['value_gross']]) ? self::sanitizeFloat($row[$map['value_gross']]) : 0.0,
            ];
        }

        return $parsedRows;
    }

    /**
     * Czyści kwotę (usuwa PLN, spacje, zamienia przecinek na kropkę)
     */
    public static function sanitizePrice($priceString)
    {
        if (empty($priceString)) return '0.00';
        $clean = str_replace(['PLN', 'zł', ' ', '&nbsp;', "\xc2\xa0", "\xa0"], '', $priceString);
        
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $clean)) {
            $clean = str_replace('.', '', $clean);
        }
        
        $clean = str_replace(',', '.', $clean);
        return number_format((float)$clean, 2, '.', '');
    }

    /**
     * Pomocnicza do parsowania liczb z CSV
     */
    public static function sanitizeFloat($string)
    {
        $clean = str_replace(['PLN', 'zł', ' ', '&nbsp;', "\xc2\xa0", "\xa0", '%'], '', $string);
        
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $clean)) {
            $clean = str_replace('.', '', $clean);
        }
        
        $clean = str_replace(',', '.', $clean);
        return (float)$clean;
    }

    /**
     * Konwertuje datę PL na SQL
     */
    public static function sanitizeDate($dateString)
    {
        if (empty($dateString)) return date('Y-m-d');
        $clean = str_replace('.', '-', $dateString);
        $ts = strtotime($clean);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}
