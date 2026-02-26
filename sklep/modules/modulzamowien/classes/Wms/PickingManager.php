<?php
/**
 * Klasa PickingManager
 * Wersja: FINAL FIX 3.0 - Agresywne zerowanie (Force Zero WMS)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PickingManager
{
    /**
     * Potwierdzenie pobrania (Confirm Pick)
     */
    public function confirmPick($skuInput, $qtyToPick)
    {
        if ($qtyToPick <= 0) return ['success' => true];
        $skuInput = trim($skuInput);

        // 1. Aktualizacja SUMY (Tabela główna)
        $this->updateMainTableDecrease($skuInput, $qtyToPick);

        // 2. Aktualizacja SZCZEGÓŁÓW (Tabela duplikatów)
        $this->syncDuplicatesDecreaseStrict($skuInput, $qtyToPick);

        return ['success' => true];
    }

    /**
     * KOREKTA STANÓW (Dla przycisku ZERUJ BRAKI)
     * Wymusza ustawienie stanu na 0 w WMS i zdejmuje różnicę z Presty.
     */
    public function correctStock($skuInput, $qtyIgnored)
    {
        $skuInput = trim($skuInput);
        $qtyFoundOnStock = 0;
        
        // --- KROK 1: ZEROWANIE SZCZEGÓŁÓW (Lokalizacje / Duplikaty) ---
        // Jeśli SKU zawiera lokalizację, próbujemy namierzyć konkretny wiersz w duplikatach.
        $parsed = $this->parseSkuDetails($skuInput);
        
        if ($parsed['ean'] && $parsed['regal'] !== false && $parsed['polka'] !== false) {
            $dupRow = Db::getInstance()->getRow("SELECT id, quantity FROM `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` 
                                              WHERE `ean` = '" . pSQL($parsed['ean']) . "' 
                                              AND TRIM(`regal`) = '" . pSQL($parsed['regal']) . "' 
                                              AND TRIM(`polka`) = '" . pSQL($parsed['polka']) . "'");
            
            if ($dupRow && (int)$dupRow['quantity'] > 0) {
                $qtyFoundOnStock = (int)$dupRow['quantity'];
                // WYMUSZENIE ZERA NA LOKALIZACJI
                Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` SET `quantity` = 0 WHERE `id` = " . (int)$dupRow['id']);
            }
        }

        // --- KROK 2: ZEROWANIE TABELI GŁÓWNEJ (WMS Details) ---
        // Musimy znaleźć rekord główny i ustawić mu quantity_wms = 0.
        
        $mainId = null;
        
        // A. Szukamy po dokładnym SKU
        $mainRow = Db::getInstance()->getRow("SELECT id_product, quantity_wms FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `sku` = '" . pSQL($skuInput) . "'");
        
        // B. Jeśli nie znaleziono i mamy EAN, szukamy po EAN (jako fallback)
        if (!$mainRow && $parsed['ean']) {
             $mainRow = Db::getInstance()->getRow("SELECT id_product, quantity_wms FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `ean` = '" . pSQL($parsed['ean']) . "'");
        }

        if ($mainRow) {
            $mainId = (int)$mainRow['id_product'];
            $qtyInMain = (int)$mainRow['quantity_wms'];

            // Jeśli nie znaleźliśmy ilości w duplikatach (Krok 1), bierzemy ją stąd.
            // Dzięki temu jeśli w bazie jest 16, to $qtyFoundOnStock = 16.
            if ($qtyFoundOnStock == 0) {
                $qtyFoundOnStock = $qtyInMain;
            }

            // WYMUSZENIE ZERA W TABELI GŁÓWNEJ
            if ($qtyInMain > 0) {
                Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_product_details` SET `quantity_wms` = 0 WHERE `id_product` = " . $mainId);
            }
        }

        // --- KROK 3: AKTUALIZACJA SKLEPU (PrestaShop) ---
        // Odejmujemy tyle, ile faktycznie znaleźliśmy w WMS przed wyzerowaniem.
        // Np. jeśli było 16, odejmujemy 16 ze stanu sklepu.
        if ($qtyFoundOnStock > 0) {
            $this->updatePrestaStock($skuInput, $qtyFoundOnStock);
        }

        return ['success' => true];
    }

    /**
     * Pomocnicza funkcja do aktualizacji PrestaShop (StockAvailable)
     */
    private function updatePrestaStock($skuInput, $qtyToRemove)
    {
        $targetId = null;

        // 1. Szukamy po SKU bezpośrednio w WMS (żeby pobrać ID produktu)
        $targetId = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `sku` = '" . pSQL($skuInput) . "'");
        
        // 2. Jeśli brak, szukamy "Matki" (Base SKU)
        if (!$targetId) {
            $baseSku = $this->stripLocationFromSku($skuInput);
            if ($baseSku && $baseSku !== $skuInput) {
                 $targetId = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `sku` = '" . pSQL($baseSku) . "'");
            }
        }

        // 3. Jeśli brak, szukamy po EAN w tabeli produktów PrestaShop
        if (!$targetId) {
            $parsed = $this->parseSkuDetails($skuInput);
            if ($parsed['ean']) {
                 $targetId = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "product` WHERE `ean13` = '" . pSQL($parsed['ean']) . "'");
            }
        }

        if ($targetId) {
            StockAvailable::updateQuantity((int)$targetId, 0, -1 * (int)$qtyToRemove);
        }
    }

    public function revertPick($skuInput, $qtyToReturn)
    {
        if ($qtyToReturn <= 0) return ['success' => true];
        $skuInput = trim($skuInput);
        $this->updateMainTableIncrease($skuInput, $qtyToReturn);
        $this->syncDuplicatesIncreaseStrict($skuInput, $qtyToReturn);
        return ['success' => true];
    }

    // --- FUNKCJE WEWNĘTRZNE (BEZ ZMIAN - DZIAŁAJĄ DOBRZE DLA ZBIERANIA) ---

    private function updateMainTableDecrease($skuInput, $qty) {
        $qty = (int)$qty;
        // 1. SKU dokładne
        $id = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `sku` = '" . pSQL($skuInput) . "'");
        
        if ($id) {
            Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_product_details` SET `quantity_wms` = GREATEST(0, `quantity_wms` - $qty) WHERE `id_product` = " . (int)$id);
            return;
        }

        // 2. SKU BAZOWE (Matka)
        $baseSku = $this->stripLocationFromSku($skuInput);
        if ($baseSku && $baseSku !== $skuInput) {
             $id = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `sku` = '" . pSQL($baseSku) . "'");
             if ($id) {
                Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_product_details` SET `quantity_wms` = GREATEST(0, `quantity_wms` - $qty) WHERE `id_product` = " . (int)$id);
                return;
             }
        }

        // 3. EAN
        $parsed = $this->parseSkuDetails($skuInput);
        if ($parsed['ean']) {
             $id = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `ean` = '" . pSQL($parsed['ean']) . "'");
             if ($id) {
                Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_product_details` SET `quantity_wms` = GREATEST(0, `quantity_wms` - $qty) WHERE `id_product` = " . (int)$id);
             }
        }
    }

    private function updateMainTableIncrease($skuInput, $qty) {
        $qty = (int)$qty;
        $targetId = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `sku` = '" . pSQL($skuInput) . "'");
        if (!$targetId) {
            $baseSku = $this->stripLocationFromSku($skuInput);
            if ($baseSku && $baseSku !== $skuInput) $targetId = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `sku` = '" . pSQL($baseSku) . "'");
        }
        if (!$targetId) {
            $parsed = $this->parseSkuDetails($skuInput);
            if ($parsed['ean']) $targetId = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE `ean` = '" . pSQL($parsed['ean']) . "'");
        }
        if ($targetId) {
            Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_product_details` SET `quantity_wms` = `quantity_wms` + $qty WHERE `id_product` = " . (int)$targetId);
        }
    }

    private function syncDuplicatesDecreaseStrict($skuInput, $qtyToRemove)
    {
        $parsed = $this->parseSkuDetails($skuInput);
        if (empty($parsed['ean']) || $parsed['regal'] === false || $parsed['polka'] === false) return;

        $sql = "UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` 
                SET `quantity` = GREATEST(0, `quantity` - " . (int)$qtyToRemove . ") 
                WHERE `ean` = '" . pSQL($parsed['ean']) . "' 
                AND TRIM(`regal`) = '" . pSQL($parsed['regal']) . "' 
                AND TRIM(`polka`) = '" . pSQL($parsed['polka']) . "'
                AND `quantity` > 0 
                LIMIT 1";

        Db::getInstance()->execute($sql);
    }

    private function syncDuplicatesIncreaseStrict($skuInput, $qtyToAdd)
    {
        $parsed = $this->parseSkuDetails($skuInput);
        if (empty($parsed['ean']) || $parsed['regal'] === false || $parsed['polka'] === false) return;
        $sql = "UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` 
                SET `quantity` = `quantity` + " . (int)$qtyToAdd . " 
                WHERE `ean` = '" . pSQL($parsed['ean']) . "' 
                AND TRIM(`regal`) = '" . pSQL($parsed['regal']) . "' 
                AND TRIM(`polka`) = '" . pSQL($parsed['polka']) . "'
                LIMIT 1";
        Db::getInstance()->execute($sql);
    }

    private function stripLocationFromSku($sku) {
        return preg_replace('/_\([^\)]+\)\s*$/', '', trim($sku));
    }

    private function parseSkuDetails($sku)
    {
        $result = ['ean' => false, 'regal' => false, 'polka' => false];
        $sku = trim($sku);
        if (empty($sku)) return $result;

        if (preg_match('/_(\d{12,14})_/', $sku, $matches)) {
            $result['ean'] = $matches[1];
        } elseif (preg_match('/^(\d{12,14})$/', $sku, $matches)) {
             $result['ean'] = $matches[1];
        }

        if (preg_match('/_\(([^_]+)_([^\)]+)\)\s*$/', $sku, $matches)) {
            $result['regal'] = trim($matches[1]);
            $result['polka'] = trim($matches[2]);
        }
        return $result;
    }
    
    public function sortForReport(array &$reportData) { usort($reportData, function($a, $b) { return strcasecmp(trim($a['name']), trim($b['name'])); }); }
    public function sortForPicking(array &$pickingData) { usort($pickingData, function($a, $b) { return strnatcasecmp($a['regal'], $b['regal']); }); }
}