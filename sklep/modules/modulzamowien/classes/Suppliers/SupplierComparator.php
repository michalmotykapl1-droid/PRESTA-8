<?php
/**
 * Klasa SupplierComparator - Wersja 3.2 (FIX: Distinguish NoStock vs NotFound)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SupplierComparator
{
    public function findSuppliersForQty($eanInput, $productName, $qtyNeeded, $originalSku = '', $excludeSupplierIds = [])
    {
        $qtyNeeded = (int)$qtyNeeded;
        if ($qtyNeeded <= 0) return $this->emptyResult();

        // 1. Pobieranie ofert (WSZYSTKIE, nawet te ze stanem 0, żeby zidentyfikować dostawcę)
        $candidates = $this->getAllOffersByEan($eanInput);
        
        if (empty($candidates) && !empty($productName)) {
            $candidates = $this->getAllOffersByName($productName);
        }

        // Jeśli SQL nic nie zwrócił -> BRAK W BAZIE
        if (empty($candidates)) {
            return $this->result('BRAK W BAZIE', 'BRAK W BAZIE', 0, 0, 0, 0, false, 'NOT_FOUND', 0, 0);
        }

        // 2. Filtracja: Do kupowania bierzemy tylko te > 0 i niewykluczone
        $availableCandidates = array_filter($candidates, function($c) use ($excludeSupplierIds) {
            if ($c['qty'] <= 0) return false; // Tu odrzucamy zera
            if (in_array($c['id_supplier'], $excludeSupplierIds)) return false;
            return true;
        });
        
        // SCENARIUSZ: Produkt jest w bazie, ale nie ma go na stanie (lub został wykluczony)
        if (empty($availableCandidates)) {
            // Pobieramy nazwę dostawcy z pierwszego kandydata (nawet tego ze stanem 0)
            $firstName = !empty($candidates) ? $this->mapSkuToName($candidates[0]['sku'], $candidates[0]['id_supplier']) : 'BRAK';
            return $this->result($firstName, $firstName, 0, 0, 0, 0, false, 'NO_STOCK', 0, 0);
        }

        // 3. Sortowanie: Cena rosnąco
        usort($availableCandidates, function($a, $b) {
            if (abs($a['price'] - $b['price']) < 0.01) return $b['qty'] <=> $a['qty'];
            return ($a['price'] < $b['price']) ? -1 : 1;
        });

        $winner = $availableCandidates[0];
        $baseCandidate = null;

        if (!empty($originalSku)) {
            foreach ($candidates as $cand) {
                if ($cand['sku'] == $originalSku) {
                    $baseCandidate = $cand;
                    break;
                }
            }
        }
        
        if (!$baseCandidate && isset($candidates[0]['id_product_default'])) {
             $defSupplierId = (int)$candidates[0]['id_supplier_default'];
             foreach ($candidates as $cand) {
                 if ($cand['id_supplier'] == $defSupplierId) {
                     $baseCandidate = $cand;
                     break;
                 }
             }
        }

        if (!$baseCandidate) $baseCandidate = $winner;

        $wasSwitched = false;
        $savingsPerUnit = 0;

        if ($winner['sku'] !== $baseCandidate['sku'] && ($baseCandidate['price'] - $winner['price'] > 0.05)) {
            $wasSwitched = true;
            $savingsPerUnit = $baseCandidate['price'] - $winner['price'];
        }

        $take = min($qtyNeeded, $winner['qty']);
        $mainSupplierName = $this->mapSkuToName($winner['sku'], $winner['id_supplier']);
        
        $savings = $wasSwitched ? ($take * $savingsPerUnit) : 0;
        $statusCode = ($take < $qtyNeeded) ? 'PARTIAL' : 'OK';

        return $this->result($mainSupplierName, $mainSupplierName, $winner['price'], $take, $savings, $winner['tax_rate'], $wasSwitched, $statusCode, $qtyNeeded, $winner['id_supplier']);
    }

    private function getAllOffersByEan($eanInput)
    {
        $eanClean = preg_replace('/[^0-9]/', '', $eanInput);
        if (empty($eanClean)) return [];
        $core = ltrim($eanClean, '0');
        $variants = array_unique(array_filter([$core, str_pad($core, 12, '0', STR_PAD_LEFT), str_pad($core, 13, '0', STR_PAD_LEFT), $eanClean]));
        if (empty($variants)) return [];
        $inClause = '"' . implode('","', array_map('pSQL', $variants)) . '"';

        // FIX: Usunięto 'AND sa.quantity > 0'. Pobieramy wszystko, co jest w bazie.
        $sql = '
        SELECT p.id_product, 0 as id_product_attribute, p.reference as sku, p.wholesale_price, p.id_supplier, sa.quantity, p.id_tax_rules_group,
               p.id_supplier as id_supplier_default, p.id_product as id_product_default
        FROM ' . _DB_PREFIX_ . 'product p
        JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (p.id_product = sa.id_product AND sa.id_product_attribute = 0)
        WHERE p.ean13 IN (' . $inClause . ')
        
        UNION
        
        SELECT pa.id_product, pa.id_product_attribute, pa.reference as sku, pa.wholesale_price, p.id_supplier, sa.quantity, p.id_tax_rules_group,
               p.id_supplier as id_supplier_default, p.id_product as id_product_default
        FROM ' . _DB_PREFIX_ . 'product_attribute pa
        JOIN ' . _DB_PREFIX_ . 'product p ON pa.id_product = p.id_product
        JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (pa.id_product = sa.id_product AND sa.id_product_attribute = pa.id_product_attribute)
        WHERE pa.ean13 IN (' . $inClause . ')
        ';

        return $this->processRawCandidates(Db::getInstance()->executeS($sql));
    }

    private function getAllOffersByName($nameInput)
    {
        $nameClean = trim($nameInput);
        if (empty($nameClean)) return [];
        
        // FIX: Usunięto 'AND sa.quantity > 0'.
        $sql = '
        SELECT p.id_product, 0 as id_product_attribute, p.reference as sku, p.wholesale_price, p.id_supplier, sa.quantity, p.id_tax_rules_group,
               p.id_supplier as id_supplier_default, p.id_product as id_product_default
        FROM ' . _DB_PREFIX_ . 'product p
        LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . (int)\Context::getContext()->language->id . ')
        JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (p.id_product = sa.id_product AND sa.id_product_attribute = 0)
        WHERE pl.name LIKE "%' . pSQL($nameClean) . '%" 
        LIMIT 5
        ';
        
        return $this->processRawCandidates(Db::getInstance()->executeS($sql));
    }

    private function processRawCandidates($rows)
    {
        if (!$rows || !is_array($rows)) return [];
        $candidates = [];
        $address = new Address();
        $address->id_country = (int)\Context::getContext()->country->id;

        foreach ($rows as $row) {
            $sku = strtoupper($row['sku']);
            if (strpos($sku, 'A_MAG') === 0 || strpos($sku, '0_MAG') === 0) continue;

            $taxRate = 0;
            if (!empty($row['id_tax_rules_group'])) {
                try {
                    $taxManager = TaxManagerFactory::getManager($address, (int)$row['id_tax_rules_group']);
                    $taxCalculator = $taxManager->getTaxCalculator();
                    $taxRate = $taxCalculator->getTotalRate();
                } catch (Exception $e) { $taxRate = 0; }
            }

            $candidates[] = [
                'sku' => $sku, 
                'price' => (float)$row['wholesale_price'], 
                'qty' => (int)$row['quantity'], 
                'id_supplier' => (int)$row['id_supplier'],
                'id_supplier_default' => (int)$row['id_supplier_default'],
                'id_product_default' => (int)$row['id_product_default'],
                'tax_rate' => $taxRate
            ];
        }
        return $candidates;
    }

    private function mapSkuToName($sku, $id_supplier)
    {
        if (strpos($sku, 'BP_') === 0) return 'BIO PLANET';
        if (strpos($sku, 'EKOWIT_') === 0) return 'EKOWITAL';
        if (strpos($sku, 'NAT_') === 0) return 'NATURA';
        if (strpos($sku, 'STEW_') === 0) return 'STEWIARNIA';
        
        if ($id_supplier > 0) {
            $dbName = \Db::getInstance()->getValue('SELECT name FROM ' . _DB_PREFIX_ . 'supplier WHERE id_supplier = ' . (int)$id_supplier);
            if ($dbName) return strtoupper(trim($dbName));
        }
        if (!empty($sku) && strlen($sku) > 3) return $sku;
        return 'INNY DOSTAWCA';
    }

    private function result($nameHtml, $supplierRaw, $price, $foundQty, $savings, $taxRate, $wasSwitched, $statusCode = 'OK', $qtyNeeded = 0, $idSupplier = 0)
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
            'id_supplier' => $idSupplier
        ];
    }
    
    private function emptyResult() {
        return ['supplier_name' => '-', 'supplier_raw' => '-', 'price' => 0, 'found_qty' => 0, 'savings' => 0, 'tax_rate' => 0, 'was_switched' => false, 'status_code' => 'ERROR', 'qty_needed' => 0, 'id_supplier' => 0];
    }
}
?>