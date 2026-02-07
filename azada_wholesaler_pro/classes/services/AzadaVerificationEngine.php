<?php

require_once(dirname(__FILE__) . '/../AzadaAnalysis.php');
require_once(dirname(__FILE__) . '/../services/AzadaDbRepository.php');

class AzadaVerificationEngine
{
    const STATUS_OK = 'OK';
    const STATUS_MISMATCH = 'MISMATCH';
    const STATUS_NO_ORDER = 'NO_ORDER';
    const STATUS_SKIPPED = 'SKIPPED';

    const ERR_PRICE = 'PRICE';
    const ERR_QTY = 'QTY';
    const ERR_MISSING_IN_ORDER = 'MISSING_IN_ORDER'; 
    const ERR_FOUND_IN_CANCELLED = 'FOUND_IN_CANCELLED';
    const ERR_MISSING_IN_INVOICE = 'MISSING_IN_INVOICE';
    
    public static function ensureDatabase()
    {
        $db = Db::getInstance();
        
        $db->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_analysis` (
            `id_analysis` int(11) NOT NULL AUTO_INCREMENT,
            `id_wholesaler` int(11) NOT NULL,
            `id_invoice_file` int(11) NOT NULL,
            `id_order_file` int(11) DEFAULT NULL,
            `doc_number_invoice` varchar(100) NOT NULL,
            `doc_number_order` TEXT DEFAULT NULL,
            `status` varchar(20) DEFAULT "NEW", 
            `total_diff_net` decimal(20,2) DEFAULT 0.00,
            `items_match_count` int(11) DEFAULT 0,
            `items_error_count` int(11) DEFAULT 0,
            `date_analyzed` datetime NOT NULL,
            PRIMARY KEY (`id_analysis`),
            KEY `id_invoice_file` (`id_invoice_file`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;');

        $db->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_analysis_diff` (
            `id_diff` int(11) NOT NULL AUTO_INCREMENT,
            `id_analysis` int(11) NOT NULL,
            `doc_number_invoice` varchar(100) DEFAULT NULL,
            `source_orders` TEXT DEFAULT NULL,
            `wholesaler_sku` varchar(64) DEFAULT NULL,
            `product_identifier` varchar(64) DEFAULT NULL,
            `product_name` varchar(255) DEFAULT NULL,
            `error_type` varchar(50) NOT NULL,
            `val_invoice` varchar(255) DEFAULT NULL,
            `val_order` varchar(255) DEFAULT NULL,
            `diff_val` varchar(50) DEFAULT NULL,
            PRIMARY KEY (`id_diff`),
            KEY `id_analysis` (`id_analysis`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;');

        try { $db->execute("ALTER TABLE `" . _DB_PREFIX_ . "azada_wholesaler_pro_analysis` MODIFY `doc_number_order` TEXT"); } catch (Exception $e) {}
        try {
            $cols = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "azada_wholesaler_pro_analysis_diff` LIKE 'doc_number_invoice'");
            if (empty($cols)) {
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . "azada_wholesaler_pro_analysis_diff` ADD `doc_number_invoice` VARCHAR(100) DEFAULT NULL AFTER `id_analysis`");
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . "azada_wholesaler_pro_analysis_diff` ADD `source_orders` TEXT DEFAULT NULL AFTER `doc_number_invoice`");
            }
        } catch (Exception $e) {}
        try {
            $colsSku = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "azada_wholesaler_pro_analysis_diff` LIKE 'wholesaler_sku'");
            if (empty($colsSku)) {
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . "azada_wholesaler_pro_analysis_diff` ADD `wholesaler_sku` VARCHAR(64) DEFAULT NULL AFTER `source_orders`");
            }
        } catch (Exception $e) {}
    }

    public static function analyzeInvoice($idInvoice)
    {
        $db = Db::getInstance();
        
        $invoiceHeader = $db->getRow("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_invoice_files WHERE id_invoice = ".(int)$idInvoice);
        if (!$invoiceHeader) return ['status' => 'error', 'msg' => 'Faktura nie istnieje w bazie'];

        $cleanInvAmount = (float)str_replace([',', ' ', 'PLN'], ['.', '', ''], $invoiceHeader['amount_netto']);

        if ($cleanInvAmount < 0) {
             $db->execute("DELETE FROM "._DB_PREFIX_."azada_wholesaler_pro_analysis WHERE id_invoice_file = ".(int)$idInvoice);
             return ['status' => 'success', 'msg' => 'Korekta - pominięto.', 'result' => self::STATUS_SKIPPED];
        }

        $idWholesaler = (int)$invoiceHeader['id_wholesaler'];
        $invoiceDate = $invoiceHeader['doc_date']; 
        $invoiceNum = $invoiceHeader['doc_number'];

        // 1. Szukamy po DACIE
        $orderHeaders = $db->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_order_files 
            WHERE id_wholesaler = $idWholesaler 
            AND doc_date = '".pSQL($invoiceDate)."'
            ORDER BY id_file DESC"); // Najnowsze ID najpierw

        // 2. Strategia "Sierot" (Fallback)
        if (empty($orderHeaders)) {
            $allInvoices = $db->executeS("SELECT doc_date, amount_netto FROM "._DB_PREFIX_."azada_wholesaler_pro_invoice_files WHERE id_wholesaler = $idWholesaler");
            $occupiedDates = [];
            foreach ($allInvoices as $inv) {
                $amt = (float)str_replace([',', ' ', 'PLN'], ['.', '', ''], $inv['amount_netto']);
                if ($amt >= 0) $occupiedDates[] = $inv['doc_date'];
            }

            $dateFrom = date('Y-m-d', strtotime($invoiceDate . ' -7 days'));
            $dateTo = $invoiceDate;
            
            $rawCandidates = $db->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_order_files 
                WHERE id_wholesaler = $idWholesaler 
                AND doc_date BETWEEN '$dateFrom' AND '$dateTo'
                ORDER BY id_file DESC");
            
            if ($rawCandidates) {
                $orderHeaders = [];
                foreach ($rawCandidates as $rc) {
                    if (!in_array($rc['doc_date'], $occupiedDates)) {
                        $orderHeaders[] = $rc;
                    }
                }
            }
        }

        $db->execute("DELETE FROM "._DB_PREFIX_."azada_wholesaler_pro_analysis WHERE id_invoice_file = ".(int)$idInvoice);
        
        $analysis = new AzadaAnalysis();
        $analysis->id_wholesaler = $idWholesaler;
        $analysis->id_invoice_file = $idInvoice;
        $analysis->doc_number_invoice = $invoiceNum;
        $analysis->date_analyzed = date('Y-m-d H:i:s');
        
        if (empty($orderHeaders)) {
            $analysis->status = self::STATUS_NO_ORDER;
            $analysis->total_diff_net = 0.00;
            $analysis->save();
            return ['status' => 'success', 'result' => 'NO_ORDER'];
        }

        // Zapisujemy listę UNIKALNYCH numerów zamówień
        $uniqueOrderNums = [];
        foreach ($orderHeaders as $oh) {
            if (!in_array($oh['external_doc_number'], $uniqueOrderNums)) {
                $uniqueOrderNums[] = $oh['external_doc_number'];
            }
        }
        $analysis->doc_number_order = implode(', ', $uniqueOrderNums);
        $analysis->id_order_file = (int)$orderHeaders[0]['id_file'];

        // Agregacja FAKTURY
        $invItemsRaw = $db->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_invoice_details WHERE id_invoice = ".(int)$idInvoice);
        $invMap = [];
        foreach ($invItemsRaw as $row) {
            $key = !empty($row['ean']) ? trim($row['ean']) : trim($row['name']);
            if (!isset($invMap[$key])) {
                $invMap[$key] = ['name' => $row['name'], 'ean' => $row['ean'], 'quantity' => 0, 'price_net' => (float)$row['price_net']];
            }
            $invMap[$key]['quantity'] += (int)$row['quantity'];
        }

        // --- AGREGACJA ZAMÓWIEŃ (Z BLOKADĄ DUPLIKATÓW) ---
        $ordMap = []; 
        $processedDocs = []; // Lista przetworzonych numerów zamówień
        
        foreach ($orderHeaders as $oh) {
            $docNum = $oh['external_doc_number'];
            
            // KLUCZOWA POPRAWKA: Jeśli już analizowaliśmy to zamówienie (np. zduplikowany plik w bazie), pomijamy!
            if (in_array($docNum, $processedDocs)) {
                continue;
            }
            $processedDocs[] = $docNum; // Oznaczamy jako przetworzone

            $statusLower = mb_strtolower($oh['status'], 'UTF-8');
            $isCancelledOrder = (strpos($statusLower, 'anulowane') !== false || strpos($statusLower, 'brak') !== false);

            $itemsRaw = $db->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_order_details WHERE id_file = ".(int)$oh['id_file']);
            
            foreach ($itemsRaw as $item) {
                $key = !empty($item['ean']) ? trim($item['ean']) : trim($item['name']);
                $sku = !empty($item['sku_wholesaler']) ? $item['sku_wholesaler'] : $item['product_id'];

                if (!isset($ordMap[$key])) {
                    $ordMap[$key] = [
                        'name' => $item['name'], 'ean' => $item['ean'], 
                        'sku' => $sku, 
                        'qty_valid' => 0, 'qty_cancelled' => 0, 
                        'price_net' => (float)$item['price_net'],
                        'processed' => false,
                        'source_docs' => []
                    ];
                }
                
                if (!in_array($docNum, $ordMap[$key]['source_docs'])) {
                    $ordMap[$key]['source_docs'][] = $docNum;
                }

                $qty = (int)$item['quantity'];
                if ($isCancelledOrder) {
                    $ordMap[$key]['qty_cancelled'] += $qty;
                } else {
                    $ordMap[$key]['qty_valid'] += $qty;
                }
            }
        }

        $diffs = [];
        $totalDiffNet = 0.0;
        $matchCount = 0;
        $errCount = 0;

        // Porównanie (FV vs ZAM)
        foreach ($invMap as $key => $iItem) {
            $srcOrders = isset($ordMap[$key]) ? implode(', ', $ordMap[$key]['source_docs']) : '-';
            $sku = isset($ordMap[$key]) ? $ordMap[$key]['sku'] : '';

            if (isset($ordMap[$key])) $ordMap[$key]['processed'] = true;

            if (!isset($ordMap[$key])) {
                $diffs[] = [
                    'ean' => $iItem['ean'], 'name' => $iItem['name'], 'type' => self::ERR_MISSING_IN_ORDER,
                    'val_inv' => $iItem['quantity'] . ' szt', 'val_ord' => 'BRAK',
                    'diff' => '+' . number_format($iItem['price_net'] * $iItem['quantity'], 2),
                    'inv_num' => $invoiceNum, 'src_ord' => 'BRAK', 'sku' => ''
                ];
                $totalDiffNet += ($iItem['price_net'] * $iItem['quantity']);
                $errCount++;
                continue;
            }

            $oItem = $ordMap[$key];
            $pInv = (float)$iItem['price_net'];
            $pOrd = (float)$oItem['price_net'];
            
            if (abs($pInv - $pOrd) > 0.019) {
                $diffs[] = [
                    'ean' => $iItem['ean'], 'name' => $iItem['name'], 'type' => self::ERR_PRICE,
                    'val_inv' => number_format($pInv, 2), 'val_ord' => number_format($pOrd, 2),
                    'diff' => number_format($pInv - $pOrd, 2),
                    'inv_num' => $invoiceNum, 'src_ord' => $srcOrders, 'sku' => $sku
                ];
                $totalDiffNet += (($pInv - $pOrd) * $iItem['quantity']);
                $errCount++;
            }

            $qInv = $iItem['quantity'];
            $qOrdValid = $oItem['qty_valid'];
            $qOrdCancelled = $oItem['qty_cancelled'];

            if ($qInv != $qOrdValid) {
                if ($qInv > $qOrdValid && $qOrdCancelled > 0) {
                    $diffs[] = [
                        'ean' => $iItem['ean'], 'name' => $iItem['name'], 'type' => self::ERR_FOUND_IN_CANCELLED,
                        'val_inv' => $qInv, 'val_ord' => $qOrdValid . ' (+'.$qOrdCancelled.' anul)',
                        'diff' => '!',
                        'inv_num' => $invoiceNum, 'src_ord' => $srcOrders, 'sku' => $sku
                    ];
                    $excess = $qInv - $qOrdValid;
                    $totalDiffNet += ($excess * $pInv);
                    $errCount++;
                } else {
                    $diffs[] = [
                        'ean' => $iItem['ean'], 'name' => $iItem['name'], 'type' => self::ERR_QTY,
                        'val_inv' => $qInv, 'val_ord' => $qOrdValid,
                        'diff' => ($qInv - $qOrdValid) . ' szt',
                        'inv_num' => $invoiceNum, 'src_ord' => $srcOrders, 'sku' => $sku
                    ];
                    $totalDiffNet += (($qInv - $qOrdValid) * $pInv);
                    $errCount++;
                }
            }

            if ($errCount == 0) $matchCount++;
        }

        // Porównanie zwrotne
        foreach ($ordMap as $key => $oItem) {
            if (!$oItem['processed'] && $oItem['qty_valid'] > 0) {
                $missingVal = $oItem['qty_valid'] * $oItem['price_net'];
                $srcOrders = implode(', ', $oItem['source_docs']);
                
                $diffs[] = [
                    'ean' => $oItem['ean'], 'name' => $oItem['name'], 'type' => self::ERR_MISSING_IN_INVOICE,
                    'val_inv' => 'BRAK', 'val_ord' => $oItem['qty_valid'] . ' szt',
                    'diff' => '-' . number_format($missingVal, 2),
                    'inv_num' => $invoiceNum, 'src_ord' => $srcOrders, 'sku' => $oItem['sku']
                ];
                $totalDiffNet -= $missingVal;
                $errCount++; 
            }
        }

        $analysis->status = ($errCount > 0) ? self::STATUS_MISMATCH : self::STATUS_OK;
        $analysis->total_diff_net = (float)number_format($totalDiffNet, 2, '.', '');
        $analysis->items_match_count = $matchCount;
        $analysis->items_error_count = $errCount;
        $analysis->save();

        if (!empty($diffs)) {
            foreach ($diffs as $d) {
                $db->insert('azada_wholesaler_pro_analysis_diff', [
                    'id_analysis' => (int)$analysis->id,
                    'doc_number_invoice' => pSQL($d['inv_num']),
                    'source_orders' => pSQL($d['src_ord']),
                    'wholesaler_sku' => pSQL($d['sku']),
                    'product_identifier' => pSQL($d['ean']),
                    'product_name' => pSQL($d['name']),
                    'error_type' => pSQL($d['type']),
                    'val_invoice' => pSQL($d['val_inv']),
                    'val_order' => pSQL($d['val_ord']),
                    'diff_val' => pSQL($d['diff'])
                ]);
            }
        }

        return ['status' => 'success', 'result' => $analysis->status];
    }

    public static function getAnalysisDetails($idAnalysis)
    {
        return Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_analysis_diff WHERE id_analysis = ".(int)$idAnalysis);
    }
}