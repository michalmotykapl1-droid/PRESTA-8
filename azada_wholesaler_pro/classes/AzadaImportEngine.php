<?php

require_once(dirname(__FILE__) . '/integrations/AzadaBioPlanet.php');
require_once(dirname(__FILE__) . '/integrations/AzadaEkoWital.php');
require_once(dirname(__FILE__) . '/helpers/AzadaFileHelper.php');
require_once(dirname(__FILE__) . '/services/AzadaRawSchema.php');

class AzadaImportEngine
{
    public function runFullImport($id_wholesaler)
    {
        $wholesaler = new AzadaWholesaler($id_wholesaler);
        
        if (!Validate::isLoadedObject($wholesaler)) return ['status' => 'error', 'msg' => 'Błąd ID'];

        if (stripos($wholesaler->name, 'Bio Planet') !== false) {
            return $this->processDynamicBioPlanet($wholesaler);
        }
        if (stripos($wholesaler->name, 'EkoWital') !== false) {
            return $this->processEkoWital($wholesaler);
        }
        return ['status' => 'error', 'msg' => 'Brak obsługi.'];
    }

    private function processDynamicBioPlanet($wholesaler)
    {
        $links = AzadaBioPlanet::generateLinks($wholesaler->api_key);
        $tableName = _DB_PREFIX_ . 'azada_raw_bioplanet';

        // --- ZMIANA KLUCZOWA: TWARDY RESET ---
        // Usuwamy tabelę całkowicie, żeby pozbyć się niechcianych kolumn
        Db::getInstance()->execute("DROP TABLE IF EXISTS `$tableName`");
        // -------------------------------------

        // 1. TERAZ Budujemy tabelę od zera (wzorzec dla hurtowni)
        if (!AzadaRawSchema::createTable('azada_raw_bioplanet')) {
            return ['status' => 'error', 'msg' => 'Błąd tworzenia tabeli wzorcowej.'];
        }

        $dbColumnsMap = AzadaBioPlanet::syncTableStructure($links['products']);
        if (!$dbColumnsMap) return ['status' => 'error', 'msg' => 'Błąd nagłówków CSV.'];

        // 2. Ładowanie Wymiarów
        $dims = [];
        if (($h = fopen($links['weights'], "r")) !== FALSE) {
            fgetcsv($h, 0, ";"); 
            while (($row = fgetcsv($h, 1000, ";")) !== FALSE) {
                if (!empty($row[0])) {
                    $dims[$row[0]] = [
                        'd' => (float)str_replace(',', '.', $row[1]),
                        'w' => (float)str_replace(',', '.', $row[2]),
                        'h' => (float)str_replace(',', '.', $row[3])
                    ];
                }
            }
            fclose($h);
        }

        // 3. Ładowanie Stanów
        $stocks = [];
        if (($h = fopen($links['stocks'], "r")) !== FALSE) {
            fgetcsv($h, 0, ";");
            while (($row = fgetcsv($h, 1000, ";")) !== FALSE) {
                if (!empty($row[0])) {
                    $stocks[$row[0]] = [
                        'qty' => (int)$row[4],
                        'price' => (float)str_replace(',', '.', $row[6])
                    ];
                }
            }
            fclose($h);
        }

        // 4. Import Główny
        $count = 0;
        if (($h = fopen($links['products'], "r")) !== FALSE) {
            $csvHeaders = fgetcsv($h, 0, ";");
            
            // Mapowanie tylko dozwolonych kolumn
            $allowedColumns = array_flip(array_keys($dbColumnsMap));
            $colIndexMap = [];
            foreach ($csvHeaders as $index => $rawHeader) {
                // Sprawdzamy czarną listę
                $rawHeaderNormalized = strtolower(trim($rawHeader));
                if (in_array($rawHeaderNormalized, AzadaBioPlanet::$ignoredColumns)) {
                    continue; // Skip - nie importujemy tego
                }

                $sanitized = AzadaBioPlanet::sanitizeColumnName($rawHeader);
                if (!empty($sanitized) && isset($allowedColumns[$sanitized])) {
                    $colIndexMap[$index] = $sanitized;
                }
            }

            $insertCols = array_values($colIndexMap);
            // Dodajemy nasze techniczne
            $insertCols[] = 'stan_magazynowy_live';
            $insertCols[] = 'cena_netto_live';
            $insertCols[] = 'glebokosc';
            $insertCols[] = 'szerokosc';
            $insertCols[] = 'wysokosc';
            $insertCols[] = 'data_aktualizacji';
            
            $sqlBase = "INSERT INTO `$tableName` (`" . implode('`,`', $insertCols) . "`) VALUES ";
            $batchValues = [];

            while (($row = fgetcsv($h, 8192, ";")) !== FALSE) {
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
            if (!empty($batchValues)) Db::getInstance()->execute($sqlBase . implode(',', $batchValues));
        }

        $wholesaler->last_import = date('Y-m-d H:i:s');
        $wholesaler->update();

        return ['status' => 'success', 'msg' => "Tabela zresetowana. Pobrano $count produktów."];
    }

    private function processEkoWital($wholesaler)
    {
        $links = AzadaEkoWital::generateLinks($wholesaler->api_key);
        $tableName = _DB_PREFIX_ . 'azada_raw_ekowital';

        Db::getInstance()->execute("DROP TABLE IF EXISTS `$tableName`");

        if (!AzadaRawSchema::createTable('azada_raw_ekowital')) {
            return ['status' => 'error', 'msg' => 'Błąd tworzenia tabeli wzorcowej.'];
        }

        $dbColumnsMap = AzadaEkoWital::syncTableStructure($links['products']);
        if (!$dbColumnsMap) return ['status' => 'error', 'msg' => 'Błąd nagłówków pliku.'];

        $count = 0;
        if (($h = fopen($links['products'], "r")) !== FALSE) {
            $headerLine = fgets($h);
            if ($headerLine === false) {
                fclose($h);
                return ['status' => 'error', 'msg' => 'Pusty plik.'];
            }
            $delimiter = AzadaEkoWital::detectDelimiter($headerLine);
            $csvHeaders = str_getcsv(trim($headerLine), $delimiter);

            $allowedColumns = array_flip(array_keys($dbColumnsMap));
            $colIndexMap = [];
            foreach ($csvHeaders as $index => $rawHeader) {
                $normalized = AzadaEkoWital::normalizeHeader($rawHeader);
                $colName = isset(AzadaEkoWital::$columnMap[$normalized]) ? AzadaEkoWital::$columnMap[$normalized] : null;
                if ($colName && isset($allowedColumns[$colName])) {
                    $colIndexMap[$index] = $colName;
                }
            }

            $insertCols = array_values($colIndexMap);
            $insertCols[] = 'data_aktualizacji';

            $sqlBase = "INSERT INTO `$tableName` (`" . implode('`,`', $insertCols) . "`) VALUES ";
            $batchValues = [];

            while (($row = fgetcsv($h, 8192, $delimiter)) !== FALSE) {
                $eanIndex = array_search('kod_kreskowy', $colIndexMap);
                $ean = ($eanIndex !== false && isset($row[$eanIndex])) ? $row[$eanIndex] : '';
                if (empty($ean)) continue;

                $rowValues = [];
                foreach ($colIndexMap as $index => $colName) {
                    $val = isset($row[$index]) ? $row[$index] : '';

                    if ($colName === 'produkt_id') {
                        $val = 'EW_' . trim($val);
                    }

                    if (in_array($colName, ['waga', 'ilosc', 'cenaporabacienetto', 'cenadetalicznabrutto', 'vat'], true)) {
                        $val = str_replace(',', '.', $val);
                        $val = preg_replace('/[^0-9.]/', '', $val);
                        if ($val === '') $val = '0';
                    }

                    $rowValues[] = pSQL(trim($val));
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
            if (!empty($batchValues)) Db::getInstance()->execute($sqlBase . implode(',', $batchValues));
        }

        $wholesaler->last_import = date('Y-m-d H:i:s');
        $wholesaler->update();

        return ['status' => 'success', 'msg' => "Tabela zresetowana. Pobrano $count produktów."];
    }
}
