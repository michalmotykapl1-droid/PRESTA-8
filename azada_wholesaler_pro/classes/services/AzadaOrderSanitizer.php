<?php

require_once(dirname(__FILE__) . '/AzadaLogger.php');

class AzadaOrderSanitizer
{
    /**
     * Główna metoda naprawcza
     */
    public static function sanitizeRow($ean, $csvQty, $wholesalerId, $docDate)
    {
        $csvQty = (int)$csvQty;
        $ean = trim($ean);
        
        $result = [
            'final_qty' => $csvQty,
            'original_qty' => $csvQty,
            'invoice_qty' => 0, 
            'correction_info' => null,
            'is_corrected' => false
        ];

        if (empty($ean) || $csvQty <= 0) return $result;

        $wholesalerName = Db::getInstance()->getValue("SELECT name FROM "._DB_PREFIX_."azada_wholesaler_pro_integration WHERE id_wholesaler = ".(int)$wholesalerId);
        if (stripos($wholesalerName, 'Bio Planet') === false) return $result;

        // ==========================================================
        // 1. SPRAWDZANIE FAKTURY (OSTATECZNA WYROCZNIA)
        // ==========================================================
        
        $invoiceTable = _DB_PREFIX_ . 'azada_wholesaler_pro_invoice_files';
        $invoiceDetails = _DB_PREFIX_ . 'azada_wholesaler_pro_invoice_details';
        
        $invoiceIds = [];
        try {
            // Pobieramy ID faktur z tego samego dnia
            $sqlCheck = "SELECT id_invoice FROM `$invoiceTable` 
                         WHERE doc_date = '".pSQL($docDate)."' 
                         AND id_wholesaler = ".(int)$wholesalerId;
            $rows = Db::getInstance()->executeS($sqlCheck);
            if ($rows) {
                foreach ($rows as $r) $invoiceIds[] = (int)$r['id_invoice'];
            }
        } catch (Exception $e) {}

        if (!empty($invoiceIds)) {
            $idsString = implode(',', $invoiceIds);
            // Suma sztuk tego produktu na wszystkich fakturach z tego dnia
            $sqlQty = "SELECT SUM(quantity) FROM `$invoiceDetails` 
                       WHERE id_invoice IN ($idsString) AND ean = '".pSQL($ean)."'";
            
            $valInv = Db::getInstance()->getValue($sqlQty);
            $qtyFromInvoice = (int)$valInv; // Jeśli null to 0
            $result['invoice_qty'] = $qtyFromInvoice;

            // --- LOGIKA KOREKTY WG FAKTURY ---

            // A. Brak na fakturze (0), a jest w CSV -> ZERUJEMY
            if ($qtyFromInvoice == 0 && $csvQty > 0) {
                $result['final_qty'] = 0;
                $result['original_qty'] = $csvQty;
                $result['correction_info'] = "Usunięto: Brak na FV (CSV: $csvQty)";
                $result['is_corrected'] = true;
                AzadaLogger::addLog('SANITIZER_FV', "EAN $ean", "Usunięto pozycję. Brak na FV z dnia $docDate.", 2);
                return $result;
            }

            // B. W CSV jest WIĘCEJ niż na fakturze -> KORYGUJEMY W DÓŁ (Błąd nadmiaru)
            if ($csvQty > $qtyFromInvoice) {
                $result['final_qty'] = $qtyFromInvoice;
                $result['original_qty'] = $csvQty;
                $result['correction_info'] = "Korekta w dół wg FV (FV: $qtyFromInvoice, CSV: $csvQty)";
                $result['is_corrected'] = true;
                AzadaLogger::addLog('SANITIZER_FV', "EAN $ean", "Korekta w dół: $csvQty -> $qtyFromInvoice", 2);
                return $result;
            }

            // C. W CSV jest MNIEJ niż na fakturze -> TO JEST OK (Dostawa dzielona)
            // Przykład: FV=12, CSV=6. Nie zmieniamy ilości na 12! Zostawiamy 6.
            if ($csvQty < $qtyFromInvoice) {
                // Nie zmieniamy final_qty
                $result['correction_info'] = "Info: Część Faktury (Ta WZ: $csvQty, Cała FV: $qtyFromInvoice)";
                $result['is_corrected'] = true; // Żeby wyświetlić info (niebieskie)
                return $result;
            }

            // D. Idealna zgodność
            if ($csvQty == $qtyFromInvoice) {
                $result['correction_info'] = "Potwierdzone Fakturą ($qtyFromInvoice szt.)";
                $result['is_corrected'] = true; // Żeby wyświetlić info (zielone)
                return $result;
            }
        }

        // ==========================================================
        // 2. STARA LOGIKA (JEŚLI NIE MA JESZCZE FAKTURY)
        // ==========================================================

        $minLogistic = 1;
        $rawTable = _DB_PREFIX_ . 'azada_raw_bioplanet';
        try {
            $sqlRaw = "SELECT minimum_logistyczne FROM `$rawTable` WHERE kod_kreskowy = '".pSQL($ean)."'";
            $rowRaw = Db::getInstance()->getRow($sqlRaw);
            if ($rowRaw && isset($rowRaw['minimum_logistyczne']) && (int)$rowRaw['minimum_logistyczne'] > 1) {
                $minLogistic = (int)$rowRaw['minimum_logistyczne'];
            }
        } catch (Exception $e) {}

        $historyTable = _DB_PREFIX_ . 'modulzamowien_history';
        $itemsTable = _DB_PREFIX_ . 'modulzamowien_history_items';
        $ordersList = [];
        
        try {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($docDate . ' -10 days'));
            $dateTo = date('Y-m-d 23:59:59', strtotime($docDate . ' +2 days'));

            $sql = "SELECT i.qty 
                    FROM `$historyTable` h
                    JOIN `$itemsTable` i ON h.id_history = i.id_history
                    WHERE h.supplier_name = 'BIO PLANET'
                    AND h.date_add BETWEEN '$dateFrom' AND '$dateTo'
                    AND i.ean = '".pSQL($ean)."'";
            
            $rows = Db::getInstance()->executeS($sql);
            if ($rows) {
                foreach ($rows as $r) $ordersList[] = (int)$r['qty'];
            }
        } catch (Exception $e) { return $result; }

        if (empty($ordersList)) return $result;

        // Dopasowanie Best Match
        foreach ($ordersList as $singleOrderQty) {
            $calculatedExpected = $singleOrderQty;
            if ($singleOrderQty < $minLogistic) $calculatedExpected = $minLogistic;
            
            $isMultipack = ($minLogistic > 1 && ($csvQty % $minLogistic == 0));
            if ($isMultipack) {
                $previousStep = $csvQty - $minLogistic;
                if ($singleOrderQty > $previousStep && $singleOrderQty <= $csvQty) {
                    $calculatedExpected = $csvQty; 
                }
            }

            if ($calculatedExpected == $csvQty) {
                if ($singleOrderQty != $csvQty) {
                    $result['correction_info'] = "Info: Zamówiono $singleOrderQty, wysłano $csvQty (Min: $minLogistic)";
                    $result['final_qty'] = $csvQty;
                    $result['original_qty'] = $csvQty;
                    $result['is_corrected'] = true; 
                }
                return $result;
            }
        }

        // Fallback Suma
        $totalOrdered = array_sum($ordersList);
        $expectedTotal = $totalOrdered;
        if ($totalOrdered < $minLogistic) $expectedTotal = $minLogistic;

        if ($csvQty != $expectedTotal) {
             $isMulti = ($minLogistic > 1 && ($csvQty % $minLogistic == 0));
             if ($isMulti && $totalOrdered > ($csvQty - $minLogistic) && $totalOrdered <= $csvQty) {
                 $result['correction_info'] = "Info: Zamówiono łącznie $totalOrdered, wysłano $csvQty (Zgrzewka)";
                 $result['is_corrected'] = true;
                 return $result;
             }

             $result['final_qty'] = $expectedTotal;
             $result['original_qty'] = $csvQty;
             $result['correction_info'] = "Korekta z $csvQty na $expectedTotal (Min: $minLogistic)";
             $result['is_corrected'] = true;
        }

        return $result;
    }

    public static function ensureTableStructure()
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'azada_wholesaler_pro_order_details';
        $check = $db->executeS("SHOW TABLES LIKE 'azada_wholesaler_pro_order_details'"); 
        if (empty($check)) return; 

        $col1 = $db->executeS("SHOW COLUMNS FROM `$table` LIKE 'original_csv_qty'");
        if (empty($col1)) $db->execute("ALTER TABLE `$table` ADD COLUMN `original_csv_qty` INT(11) DEFAULT 0 AFTER `quantity`");

        $col2 = $db->executeS("SHOW COLUMNS FROM `$table` LIKE 'correction_info'");
        if (empty($col2)) $db->execute("ALTER TABLE `$table` ADD COLUMN `correction_info` VARCHAR(255) DEFAULT NULL AFTER `original_csv_qty`");
        
        $col3 = $db->executeS("SHOW COLUMNS FROM `$table` LIKE 'invoice_qty'");
        if (empty($col3)) $db->execute("ALTER TABLE `$table` ADD COLUMN `invoice_qty` INT(11) DEFAULT 0 AFTER `correction_info`");
    }
}