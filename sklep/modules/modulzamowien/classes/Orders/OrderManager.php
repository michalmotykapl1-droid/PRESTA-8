<?php
/**
 * Klasa OrderManager - Wersja Pełna
 * Odpowiada za grupowanie kafelków głównych i sortowanie priorytetowe
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderManager
{
    public function prepareOrderList(array $products)
    {
        $ordersGrouped = [];

        // 1. Grupowanie po dostawcach (Główne kafelki)
        foreach ($products as $product) {
            if (!isset($product['qty_buy']) || $product['qty_buy'] <= 0) continue;

            $supplierNameRaw = isset($product['supplier']) ? $product['supplier'] : '';
            
            // Używamy metody extractSupplierName, aby usunąć dopiski HTML przy grupowaniu
            $supplierKey = $this->extractSupplierName($supplierNameRaw);

            if (!isset($ordersGrouped[$supplierKey])) {
                $ordersGrouped[$supplierKey] = [
                    'supplier_name' => $supplierKey,
                    'total_cost' => 0,
                    'items' => []
                ];
            }

            $price = isset($product['price']) ? (float)$product['price'] : 0;
            $cost = $product['qty_buy'] * $price;

            $ordersGrouped[$supplierKey]['items'][] = $product;
            $ordersGrouped[$supplierKey]['total_cost'] += $cost;
        }

        // 2. Sortowanie Kafelków (Klucze tablicy)
        uksort($ordersGrouped, function($a, $b) {
            $priorityWords = ['BRAK', 'BŁĄD', 'WERYFIKACJI', 'ERROR', 'RĘCZNE'];
            $isPA = false; $isPB = false;
            foreach ($priorityWords as $w) if (strpos(strtoupper($a), $w) !== false) $isPA = true;
            foreach ($priorityWords as $w) if (strpos(strtoupper($b), $w) !== false) $isPB = true;
            
            if ($isPA && !$isPB) return -1;
            if (!$isPA && $isPB) return 1;
            return strcasecmp($a, $b);
        });

        // 3. SORTOWANIE WEWNĘTRZNE PRODUKTÓW
        foreach ($ordersGrouped as &$group) {
            usort($group['items'], function($a, $b) {
                
                $getScore = function($item) {
                    $html = isset($item['supplier']) ? strtoupper($item['supplier']) : '';
                    
                    // POZIOM 0: BRAKI CZĘŚCIOWE (Najważniejsze)
                    if ((isset($item['status']) && $item['status'] === 'PARTIAL') || strpos($html, 'CZĘŚCIOWO') !== false) {
                        return 0;
                    }
                    
                    // POZIOM 1: ZAMIANA NA TAŃSZY (Zielone flagi)
                    if (
                        (isset($item['was_switched']) && $item['was_switched'] == true) || 
                        strpos($html, 'TAŃSZY') !== false || 
                        strpos($html, 'ZAMIAST') !== false
                    ) {
                        return 1;
                    }
                    
                    // POZIOM 2: STANDARD (Reszta)
                    return 2;
                };

                $scoreA = $getScore($a);
                $scoreB = $getScore($b);

                // Porównanie priorytetów
                if ($scoreA != $scoreB) {
                    return $scoreA - $scoreB;
                }

                // Jeśli priorytety są takie same -> Sortowanie Alfabetyczne
                return strcasecmp($a['name'], $b['name']);
            });
        }
        unset($group);

        return $ordersGrouped;
    }

    /**
     * Czyści nazwę dostawcy z tagów HTML i dopisków
     */
    private function extractSupplierName($html)
    {
        // 1. Jeśli jest hidden-supplier-name (dodawane przez niektóre wersje comparatora)
        if (preg_match('/<span class=[\"\']hidden-supplier-name[\"\'][^>]*>(.*?)<\/span>/', $html, $matches)) {
            return trim($matches[1]);
        }
        
        // 2. Czyszczenie ręczne
        $clean = strip_tags($html); 
        $pollution = ['TAŃSZY', 'Tańszy', 'CZĘŚCIOWO', 'Częściowo', 'Zamiast:', 'ZAMIAST:', 'Oszczędzasz:'];
        
        foreach($pollution as $word) {
            $clean = str_ireplace($word, '', $clean);
        }

        // Usuwanie nawiasów z licznikami
        $clean = preg_replace('/\s*\(\d+\/\d+\)/', '', $clean);
        
        return trim(preg_replace('/\s+/', ' ', $clean));
    }
}
?>