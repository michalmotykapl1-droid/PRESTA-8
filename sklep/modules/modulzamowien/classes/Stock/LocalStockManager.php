<?php
/**
 * Klasa LocalStockManager - Wersja FINAL FIX (PRIORYTET A_MAG)
 * 1. Szuka po wariantach EAN.
 * 2. Priorytetyzuje produkt: A_MAG > Aktywny > Inny.
 * 3. Zapobiega blokowaniu analizy przez stare, nieaktywne ID produktu.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class LocalStockManager
{
    public function checkStock($eanInput, $skuInput, $neededQty, $nameFallback = '')
    {
        // 1. Czyszczenie wejścia
        $eanClean = preg_replace('/[^0-9]/', '', $eanInput);
        
        // 2. GENEROWANIE WSZYSTKICH KOMBINACJI ZER
        $variants = [];
        if (!empty($eanClean)) {
            $core = ltrim($eanClean, '0');
            $variants[] = $eanClean;          
            $variants[] = $core;              
            $variants[] = '0' . $core;        
            $variants[] = '00' . $core;       
            $variants[] = '000' . $core;      
            $variants[] = str_pad($core, 13, '0', STR_PAD_LEFT);
            $variants = array_unique(array_filter($variants));
        }

        $batches = [];
        $foundInDb = false;
        $productName = '';
        $imageId = null;
        $linkRewrite = '';
        $totalAvailable = 0;
        $foundIdProduct = 0;
        
        // 3. DANE Z PRESTY (Z PRIORYTETEM A_MAG)
        if (!empty($variants)) {
            $inClause = '"' . implode('","', array_map('pSQL', $variants)) . '"';
            
            // --- TU JEST KLUCZOWA POPRAWKA ---
            // Sortujemy wyniki tak, aby na górze był produkt A_MAG i produkt AKTYWNY.
            // Dzięki temu system pobierze właściwe ID (40773), a nie stare (12064).
            
            $prodInfo = Db::getInstance()->getRow('
                SELECT p.id_product, pl.name, pl.link_rewrite, i.id_image
                FROM ' . _DB_PREFIX_ . 'product p
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . (int)\Context::getContext()->language->id . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'image i ON (p.id_product = i.id_product AND i.cover = 1)
                WHERE p.ean13 IN (' . $inClause . ')
                ORDER BY 
                    CASE WHEN p.reference LIKE "A_MAG%" THEN 0 ELSE 1 END ASC, -- Najważniejsze: A_MAG na górę
                    p.active DESC, -- Potem Aktywne (1 przed 0)
                    p.id_product DESC -- Na końcu najnowsze ID
            ');
            
            if ($prodInfo) {
                $productName = $prodInfo['name'];
                $imageId = $prodInfo['id_image'];
                $linkRewrite = $prodInfo['link_rewrite'];
                $foundIdProduct = $prodInfo['id_product'];
            }
        }

        // 4. LOGIKA GŁÓWNA: Szukamy w WMS
        if (!empty($variants)) {
            $inClause = '"' . implode('","', array_map('pSQL', $variants)) . '"';
            $sqlMain = "SELECT * FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` 
                        WHERE ean IN (" . $inClause . ") AND quantity_wms > 0";
            $mainRows = Db::getInstance()->executeS($sqlMain);
        } else {
            $mainRows = [];
        }

        if ($mainRows) {
            $foundInDb = true;

            // Uzupełnianie nazwy z CSV jeśli brak w bazie
            if (empty($productName)) {
                if (!empty($nameFallback)) {
                    $productName = $nameFallback;
                } else {
                    $productName = 'Produkt WMS (Brak nazwy)';
                }
            }

            foreach ($mainRows as $mainRow) {
                $foundEan = $mainRow['ean'];
                $expiryDate = $mainRow['expiry_date'];
                
                $sqlDupes = "SELECT * FROM `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` 
                             WHERE ean = '" . pSQL($foundEan) . "' 
                             AND quantity > 0 ";
                
                if (empty($expiryDate) || $expiryDate == '0000-00-00') {
                    $sqlDupes .= " AND (expiry_date IS NULL OR expiry_date = '0000-00-00')";
                } else {
                    $sqlDupes .= " AND expiry_date = '" . pSQL($expiryDate) . "'";
                }
                
                $sqlDupes .= " ORDER BY receipt_date ASC";

                $dupes = Db::getInstance()->executeS($sqlDupes);

                if ($dupes && count($dupes) > 0) {
                    foreach ($dupes as $d) {
                        $datePart = ($d['expiry_date'] && $d['expiry_date'] != '0000-00-00') ? date('dmY', strtotime($d['expiry_date'])) : '000000';
                        $genSku = 'A_MAG_' . $foundEan . '_' . $datePart . '_(' . $d['regal'] . '_' . $d['polka'] . ')';

                        $batches[] = [
                            'sku' => $genSku,
                            'quantity' => (int)$d['quantity'],
                            'regal' => $d['regal'],
                            'polka' => $d['polka'],
                            'expiry' => $d['expiry_date'],
                            'source' => 'dupes'
                        ];
                        $totalAvailable += (int)$d['quantity'];
                    }
                } else {
                    $batches[] = [
                        'sku' => $mainRow['sku'],
                        'quantity' => (int)$mainRow['quantity_wms'],
                        'regal' => $mainRow['regal'],
                        'polka' => $mainRow['polka'],
                        'expiry' => $mainRow['expiry_date'],
                        'source' => 'main'
                    ];
                    $totalAvailable += (int)$mainRow['quantity_wms'];
                }
            }
        }

        // 5. OSTATNIA DESKA RATUNKU DLA NAZWY
        if (empty($productName) && !empty($nameFallback)) {
            $productName = $nameFallback;
        }

        $takenTotal = min($totalAvailable, $neededQty);
        $missingTotal = $neededQty - $takenTotal;
        $hasAmag = (count($batches) > 0);

        return [
            'found_in_db' => $foundInDb,
            'name' => $productName,
            'image_id' => $imageId,
            'link_rewrite' => $linkRewrite,
            'product_id' => $foundIdProduct,
            'total_available' => $totalAvailable,
            'taken' => $takenTotal,
            'missing' => $missingTotal,
            'has_amag' => $hasAmag,
            'batches' => $batches
        ];
    }
    
    // Funkcje pomocnicze
    public function checkStockForEan($ean, $qty) { return $this->checkStock($ean, '', $qty); }
    public function checkStockByName($name, $qty) { return $this->emptyResult($qty, $name); }
    
    private function emptyResult($neededQty, $name = '') {
        return [
            'found_in_db' => false, 'name' => $name, 'taken' => 0, 'missing' => $neededQty, 
            'has_amag' => false, 'batches' => [], 'image_id' => null, 'link_rewrite' => '', 'product_id' => 0
        ];
    }
}