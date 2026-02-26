<?php
/**
 * Klasa CsvImporter - Wersja INTELIGENTNA (Synonimy Nagłówków)
 * Rozpoznaje różne nazwy kolumn (np. z Baselinkera i z Hurtowni)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Utils/DataStandardizer.php';

class CsvImporter
{
    public function processFile($filePath)
    {
        if (!file_exists($filePath)) throw new Exception("Brak pliku na serwerze.");

        $handle = fopen($filePath, 'r');
        if (!$handle) throw new Exception("Nie można otworzyć pliku CSV.");

        $data = [];
        $rowNumber = 0;
        
        // Domyślne mapowanie (-1 oznacza brak kolumny)
        $colMap = ['ean' => -1, 'sku' => -1, 'qty' => -1, 'name' => -1];

        // Separator - próbujemy wykryć ; lub ,
        $delimiter = ';'; 
        $firstLine = fgets($handle);
        if (substr_count($firstLine, ',') > substr_count($firstLine, ';')) {
            $delimiter = ',';
        }
        rewind($handle); // Przewiń na początek

        while (($row = fgetcsv($handle, 0, $delimiter, '"')) !== false) {
            $rowNumber++;

            // Pomijamy puste linie
            if (!$row || $row[0] === null) continue;

            // KROK 1: Wykrywanie nagłówków (zwykle wiersz 1 lub 2)
            if ($rowNumber <= 3 && $colMap['ean'] == -1 && $colMap['name'] == -1) {
                foreach ($row as $index => $headerName) {
                    $clean = $this->cleanHeader($headerName);
                    
                    // -- INTELIGENTNE DOPASOWANIE KOLUMN --
                    
                    // 1. EAN
                    if ($this->matches($clean, ['ean', 'kod kreskowy', 'barcode', 'pkwiu'])) { 
                        $colMap['ean'] = $index;
                    }
                    // 2. SKU (Kod produktu)
                    elseif ($this->matches($clean, ['sku', 'kod', 'indeks', 'symbol', 'numer katalogowy'])) {
                        $colMap['sku'] = $index;
                    }
                    // 3. ILOŚĆ (Quantity)
                    elseif ($this->matches($clean, ['ilosc', 'qty', 'quantity', 'liczba', 'potwierdzone', 'zrealizowano', 'fakturowane'])) {
                        $colMap['qty'] = $index;
                    }
                    // 4. NAZWA (Name)
                    elseif ($this->matches($clean, ['nazwa', 'produkt', 'towar', 'opis', 'name', 'title'])) {
                        $colMap['name'] = $index;
                    }
                }
                
                // Jeśli w tym wierszu znaleźliśmy kluczowe kolumny, to uznajemy go za nagłówek i idziemy dalej
                if ($colMap['qty'] > -1 && ($colMap['ean'] > -1 || $colMap['name'] > -1)) {
                    continue;
                }
                // Jeśli to nie nagłówek, ale mamy już mapę, to traktujmy to jako dane (rzadki przypadek braku nagłówka)
            }

            // KROK 2: Pobieranie danych
            // Musimy mieć przynajmniej ilość i (EAN lub Nazwę)
            if ($colMap['qty'] == -1) continue; 

            $ean = ($colMap['ean'] > -1 && isset($row[$colMap['ean']])) ? trim($row[$colMap['ean']]) : '';
            $sku = ($colMap['sku'] > -1 && isset($row[$colMap['sku']])) ? trim($row[$colMap['sku']]) : '';
            $name = ($colMap['name'] > -1 && isset($row[$colMap['name']])) ? trim($row[$colMap['name']]) : '';
            
            $qtyRaw = isset($row[$colMap['qty']]) ? str_replace(',', '.', $row[$colMap['qty']]) : 0;
            $qty = (float)$qtyRaw;

            // Dodajemy tylko poprawne wiersze
            if ((!empty($ean) || !empty($name)) && $qty > 0) {
                // Jeśli EAN jest pusty, spróbuj użyć SKU jako EAN (częste w hurtowniach)
                if (empty($ean) && !empty($sku) && is_numeric($sku) && strlen($sku) > 7) {
                    $ean = $sku;
                }

                $data[] = [
                    'ean' => $ean,
                    'sku' => $sku,
                    'quantity' => $qty,
                    'csv_name' => $name, 
                    'source' => "CSV Wiersz $rowNumber"
                ];
            }
        }

        fclose($handle);
        
        if (empty($data)) {
            // Jeśli pusta tablica, rzuć błąd żeby user wiedział
            throw new Exception("Nie znaleziono danych w pliku. Sprawdź czy plik ma nagłówki (EAN/Kod, Ilość, Nazwa).");
        }

        return $data;
    }

    private function matches($haystack, $needles) {
        foreach ($needles as $n) {
            if (strpos($haystack, $n) !== false) return true;
        }
        return false;
    }

    private function cleanHeader($string)
    {
        $string = mb_strtolower($string, 'UTF-8');
        $pl = ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'];
        $en = ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'];
        return str_replace($pl, $en, $string);
    }
}