<?php
/**
 * Klasa AlternativeComparator - Wersja 8.0 (SMART: Ignore Min if Qty Met)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AlternativeComparator
{
    public function findSuppliersForQty($eanInput, $productName, $qtyNeeded, $originalSku = '', $excludeSupplierIds = [])
    {
        $qtyNeeded = (int)$qtyNeeded;
        if ($qtyNeeded <= 0) return $this->emptyResult();

        // 1. Pobieranie kandydatów
        $candidates = $this->getAllOffersByEan($eanInput);
        if (empty($candidates) && !empty($productName)) {
            $candidates = $this->getAllOffersByName($productName);
        }

        if (empty($candidates)) {
            return $this->result('BRAK W BAZIE', 'BRAK W BAZIE', 0, 0, 0, 0, false, 'NOT_FOUND', 0, 0);
        }

        // 2. Filtracja
        $availableCandidates = array_filter($candidates, function($c) use ($excludeSupplierIds) {
            if ($c['qty'] <= 0) return false;
            if (in_array($c['id_supplier'], $excludeSupplierIds)) return false;
            return true;
        });
        
        if (empty($availableCandidates)) {
            $firstName = !empty($candidates) ? $this->mapSkuToName($candidates[0]['sku'], $candidates[0]['id_supplier']) : 'BRAK';
            return $this->result($firstName, $firstName, 0, 0, 0, 0, false, 'NO_STOCK', 0, 0);
        }

        // --- STRATEGIA PORTFELA ---
        $eanSafe = preg_replace('/[^0-9]/', '', $eanInput);
        $bpMinimum = 1; 
        $realBpMinimum = 1; // Zmienna pomocnicza do zapamiętania faktycznego minimum z bazy
        
        // Pobieramy minimum z bazy
        $tableName = _DB_PREFIX_ . 'azada_raw_bioplanet';
        $sqlMin = "SELECT minimum_logistyczne FROM `$tableName` WHERE kod_kreskowy = '" . pSQL($eanSafe) . "'";
        $dbMin = Db::getInstance()->getValue($sqlMin);
        
        if ($dbMin && (int)$dbMin > 1) {
            $bpMinimum = (int)$dbMin;
            $realBpMinimum = (int)$dbMin;
        }

        // --- NOWOŚĆ: WARUNEK "SUPER SMART" ---
        // Jeśli potrzebujemy tyle samo lub więcej niż wymaga hurtownia,
        // to minimum przestaje być problemem (nie jest kosztem, a po prostu cechą zamówienia).
        // Wtedy traktujemy minimum jakby wynosiło 1 (brak kary), co pozwoli wygrać tańszemu (Bio Planet).
        if ($qtyNeeded >= $bpMinimum) {
            $bpMinimum = 1; 
        }

        // --- SMART SPLIT PREVENTION ---
        // (Logika unikania głupiego podziału działa tylko jeśli minimum nadal > 1)
        $bpCandidate = null;
        foreach ($availableCandidates as $cand) {
            if (strpos($this->mapSkuToName($cand['sku'], $cand['id_supplier']), 'BIO PLANET') !== false) {
                $bpCandidate = $cand;
                break;
            }
        }

        if ($bpCandidate && $bpMinimum > 1 && count($availableCandidates) > 1) {
            foreach ($availableCandidates as $key => $cand) {
                $candName = $this->mapSkuToName($cand['sku'], $cand['id_supplier']);
                if (strpos($candName, 'BIO PLANET') !== false) continue;
                if ($cand['qty'] >= $qtyNeeded) continue; 

                $remainder = $qtyNeeded - $cand['qty'];
                $costCheapPart = $cand['qty'] * $cand['price'];
                $costBpPart = $bpCandidate['price'] * max($remainder, $bpMinimum);
                $totalCostSplit = $costCheapPart + $costBpPart;
                $totalCostFullBP = $bpCandidate['price'] * max($qtyNeeded, $bpMinimum);

                if ($totalCostFullBP <= $totalCostSplit) {
                    unset($availableCandidates[$key]);
                }
            }
            $availableCandidates = array_values($availableCandidates);
        }

        // --- SORTOWANIE ---
        usort($availableCandidates, function($a, $b) use ($qtyNeeded, $bpMinimum) {
            $costA = $this->calculateTotalCashOutlay($a, $qtyNeeded, $bpMinimum);
            $costB = $this->calculateTotalCashOutlay($b, $qtyNeeded, $bpMinimum);
            if (abs($costA - $costB) < 0.01) return ($a['price'] < $b['price']) ? -1 : 1;
            return ($costA < $costB) ? -1 : 1;
        });

        if (empty($availableCandidates)) return $this->result('BRAK OPCJI', 'BRAK OPCJI', 0, 0, 0, 0, false, 'NO_STOCK', 0, 0);

        $winner = $availableCandidates[0];
        
        // --- DETEKCJA ZMIANY DOSTAWCY ---
        
        $bpAvailable = false;
        foreach ($candidates as $c) {
            if (strpos($this->mapSkuToName($c['sku'], $c['id_supplier']), 'BIO PLANET') !== false) {
                if ($c['qty'] > 0) $bpAvailable = true;
                break; 
            }
        }

        $cheapestUnit = $availableCandidates[0];
        foreach ($availableCandidates as $cand) {
            if ($cand['price'] < $cheapestUnit['price']) {
                $cheapestUnit = $cand;
            }
        }

        $wasSwitched = false;
        $isLogisticSwitch = false; 
        $savings = 0;

        if ($winner['sku'] !== $cheapestUnit['sku']) {
            $wasSwitched = true;
            
            $cheapestName = $this->mapSkuToName($cheapestUnit['sku'], $cheapestUnit['id_supplier']);
            $winnerName = $this->mapSkuToName($winner['sku'], $winner['id_supplier']);
            
            // Do flagi używamy $bpMinimum (które mogło zostać wyzerowane jeśli spełniamy wymóg)
            // Dzięki temu, jeśli spełniamy wymóg i bierzemy BP, nie będzie flagi.
            // Ale jeśli nadal omijamy BP (bo np. minimum 6, a my chcemy 1), flaga się pojawi.
            
            if ($bpAvailable && strpos($winnerName, 'BIO PLANET') === false) {
                // Tutaj sprawdzamy $realBpMinimum (z bazy), żeby wiedzieć czy to produkt "trudny logistycznie"
                // ORAZ sprawdzamy czy $bpMinimum (zmienna obliczeniowa) nadal jest > 1 (czyli czy problem nadal istnieje)
                if ($bpMinimum > 1 || strpos($cheapestName, 'BIO PLANET') !== false) {
                    $isLogisticSwitch = true;
                }
            }
            
            $costWinner = $this->calculateTotalCashOutlay($winner, $qtyNeeded, $bpMinimum);
            $costCheapestUnit = $this->calculateTotalCashOutlay($cheapestUnit, $qtyNeeded, $bpMinimum);
            $savings = max(0, $costCheapestUnit - $costWinner);
        }

        $take = min($qtyNeeded, $winner['qty']);
        $mainSupplierName = $this->mapSkuToName($winner['sku'], $winner['id_supplier']);
        $statusCode = ($take < $qtyNeeded) ? 'PARTIAL' : 'OK';

        return $this->result($mainSupplierName, $mainSupplierName, $winner['price'], $take, $savings, $winner['tax_rate'], $wasSwitched, $statusCode, $qtyNeeded, $winner['id_supplier'], $isLogisticSwitch);
    }

    private function calculateTotalCashOutlay($candidate, $needed, $bpMinimum)
    {
        $name = $this->mapSkuToName($candidate['sku'], $candidate['id_supplier']);
        if (strpos($name, 'BIO PLANET') !== false) {
            $qtyToPayFor = max($needed, $bpMinimum);
            return $candidate['price'] * $qtyToPayFor;
        } else {
            return $candidate['price'] * $needed;
        }
    }

    // --- (METODY POMOCNICZE - BEZ ZMIAN) ---
    private function getAllOffersByEan($eanInput) {
        $eanClean = preg_replace('/[^0-9]/', '', $eanInput); if (empty($eanClean)) return []; $core = ltrim($eanClean, '0'); $variants = array_unique(array_filter([$core, str_pad($core, 12, '0', STR_PAD_LEFT), str_pad($core, 13, '0', STR_PAD_LEFT), $eanClean])); if (empty($variants)) return []; $inClause = '"' . implode('","', array_map('pSQL', $variants)) . '"';
        $sql = 'SELECT p.id_product, 0 as id_product_attribute, p.reference as sku, p.wholesale_price, p.id_supplier, sa.quantity, p.id_tax_rules_group, p.id_supplier as id_supplier_default, p.id_product as id_product_default FROM ' . _DB_PREFIX_ . 'product p JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (p.id_product = sa.id_product AND sa.id_product_attribute = 0) WHERE p.ean13 IN (' . $inClause . ') UNION SELECT pa.id_product, pa.id_product_attribute, pa.reference as sku, pa.wholesale_price, p.id_supplier, sa.quantity, p.id_tax_rules_group, p.id_supplier as id_supplier_default, p.id_product as id_product_default FROM ' . _DB_PREFIX_ . 'product_attribute pa JOIN ' . _DB_PREFIX_ . 'product p ON pa.id_product = p.id_product JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (pa.id_product = sa.id_product AND sa.id_product_attribute = pa.id_product_attribute) WHERE pa.ean13 IN (' . $inClause . ')';
        return $this->processRawCandidates(Db::getInstance()->executeS($sql));
    }
    private function getAllOffersByName($nameInput) {
        $nameClean = trim($nameInput); if (empty($nameClean)) return [];
        $sql = 'SELECT p.id_product, 0 as id_product_attribute, p.reference as sku, p.wholesale_price, p.id_supplier, sa.quantity, p.id_tax_rules_group, p.id_supplier as id_supplier_default, p.id_product as id_product_default FROM ' . _DB_PREFIX_ . 'product p LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . (int)\Context::getContext()->language->id . ') JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (p.id_product = sa.id_product AND sa.id_product_attribute = 0) WHERE pl.name LIKE "%' . pSQL($nameClean) . '%" LIMIT 5';
        return $this->processRawCandidates(Db::getInstance()->executeS($sql));
    }
    private function processRawCandidates($rows) {
        if (!$rows || !is_array($rows)) return []; $candidates = []; $address = new Address(); $address->id_country = (int)\Context::getContext()->country->id;
        foreach ($rows as $row) {
            $sku = strtoupper($row['sku']); if (strpos($sku, 'A_MAG') === 0 || strpos($sku, '0_MAG') === 0) continue;
            $taxRate = 0; if (!empty($row['id_tax_rules_group'])) { try { $taxManager = TaxManagerFactory::getManager($address, (int)$row['id_tax_rules_group']); $taxCalculator = $taxManager->getTaxCalculator(); $taxRate = $taxCalculator->getTotalRate(); } catch (Exception $e) { $taxRate = 0; } }
            $candidates[] = ['sku' => $sku, 'price' => (float)$row['wholesale_price'], 'qty' => (int)$row['quantity'], 'id_supplier' => (int)$row['id_supplier'], 'id_supplier_default' => (int)$row['id_supplier_default'], 'id_product_default' => (int)$row['id_product_default'], 'tax_rate' => $taxRate];
        } return $candidates;
    }
    private function mapSkuToName($sku, $id_supplier) {
        if (strpos($sku, 'BP_') === 0) return 'BIO PLANET'; if (strpos($sku, 'EKOWIT_') === 0) return 'EKOWITAL'; if (strpos($sku, 'NAT_') === 0) return 'NATURA'; if (strpos($sku, 'STEW_') === 0) return 'STEWIARNIA';
        if ($id_supplier > 0) { $dbName = \Db::getInstance()->getValue('SELECT name FROM ' . _DB_PREFIX_ . 'supplier WHERE id_supplier = ' . (int)$id_supplier); if ($dbName) return strtoupper(trim($dbName)); }
        if (!empty($sku) && strlen($sku) > 3) return $sku; return 'INNY DOSTAWCA';
    }

    private function result($nameHtml, $supplierRaw, $price, $foundQty, $savings, $taxRate, $wasSwitched, $statusCode = 'OK', $qtyNeeded = 0, $idSupplier = 0, $isLogistic = false)
    {
        return [
            'supplier_name' => $nameHtml,
            'supplier_raw' => $supplierRaw,
            'price' => $price,
            'found_qty' => $foundQty,
            'savings' => $savings,
            'tax_rate' => $taxRate,
            'was_switched' => $wasSwitched,
            'status_code' => $statusCode,
            'qty_needed' => $qtyNeeded,
            'id_supplier' => $idSupplier,
            'is_logistic_switch' => $isLogistic
        ];
    }
    
    private function emptyResult() {
        return ['supplier_name' => '-', 'supplier_raw' => '-', 'price' => 0, 'found_qty' => 0, 'savings' => 0, 'tax_rate' => 0, 'was_switched' => false, 'status_code' => 'ERROR', 'qty_needed' => 0, 'id_supplier' => 0, 'is_logistic_switch' => false];
    }
}
?>