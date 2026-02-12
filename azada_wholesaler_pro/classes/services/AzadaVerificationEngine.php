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

    private static function normalizeText($text)
    {
        $txt = trim((string)$text);
        if ($txt === '') return '';

        $txt = mb_strtolower($txt, 'UTF-8');
        $txt = preg_replace('/\s+/u', ' ', $txt);
        $txt = preg_replace('/[^\p{L}\p{N}\s\-_\.]/u', '', $txt);
        return trim($txt);
    }

    private static function normalizeDigits($text)
    {
        return preg_replace('/\D+/', '', (string)$text);
    }

    private static function normalizeCode($text)
    {
        return self::normalizeText($text);
    }

    private static function addCodeTokenVariants(&$tokens, $prefix, $value)
    {
        $norm = self::normalizeCode($value);
        if ($norm === '') return;

        $tokens[$prefix . $norm] = true;

        // Wariant bez prefiksu hurtowni, np. NAT_12345 -> 12345
        $parts = explode('_', $norm);
        if (count($parts) > 1) {
            $last = trim(end($parts));
            if ($last !== '') {
                $tokens[$prefix . $last] = true;
            }
        }
    }

    private static function buildItemTokens($ean, $productId, $skuWholesaler, $name)
    {
        $tokens = [];

        $eanNorm = self::normalizeDigits($ean);
        if ($eanNorm !== '') {
            $tokens['ean:' . $eanNorm] = true;
        }

        self::addCodeTokenVariants($tokens, 'pid:', $productId);
        self::addCodeTokenVariants($tokens, 'sku:', $skuWholesaler);

        $nameNorm = self::normalizeText($name);
        if ($nameNorm !== '') {
            $tokens['name:' . $nameNorm] = true;
        }

        return array_keys($tokens);
    }

    private static function getCanonicalKeyFromTokens($tokens)
    {
        if (empty($tokens)) return 'name:brak';

        // Preferencja klucza: EAN -> PID -> SKU -> NAME
        foreach (['ean:', 'pid:', 'sku:', 'name:'] as $pref) {
            foreach ($tokens as $t) {
                if (strpos($t, $pref) === 0) return $t;
            }
        }

        return $tokens[0];
    }

    private static function parseMoneyToFloat($amount)
    {
        $clean = str_replace([',', ' ', 'PLN', '&nbsp;', "\xc2\xa0"], ['.', '', '', '', ''], (string)$amount);
        return (float)$clean;
    }

    private static function isCancelledStatus($status)
    {
        $statusLower = mb_strtolower((string)$status, 'UTF-8');
        return (strpos($statusLower, 'anulowane') !== false || strpos($statusLower, 'brak') !== false);
    }

    private static function mergeTokenSets($a, $b)
    {
        $tmp = [];
        foreach ($a as $t) $tmp[$t] = true;
        foreach ($b as $t) $tmp[$t] = true;
        return array_keys($tmp);
    }

    private static function buildInvoiceMap($idInvoice)
    {
        $rows = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_invoice_details WHERE id_invoice = ".(int)$idInvoice);
        $map = [];

        foreach ($rows as $row) {
            $tokens = self::buildItemTokens(
                isset($row['ean']) ? $row['ean'] : '',
                isset($row['product_id']) ? $row['product_id'] : '',
                '',
                isset($row['name']) ? $row['name'] : ''
            );
            $key = self::getCanonicalKeyFromTokens($tokens);

            if (!isset($map[$key])) {
                $map[$key] = [
                    'name' => isset($row['name']) ? $row['name'] : '',
                    'ean' => isset($row['ean']) ? $row['ean'] : '',
                    'product_id' => isset($row['product_id']) ? $row['product_id'] : '',
                    'quantity' => 0,
                    'price_net' => (float)$row['price_net'],
                    'tokens' => $tokens,
                ];
            } else {
                $map[$key]['tokens'] = self::mergeTokenSets($map[$key]['tokens'], $tokens);
            }

            $map[$key]['quantity'] += (int)$row['quantity'];
        }

        return $map;
    }

    private static function buildOrderMapForFile($idFile, $status)
    {
        $rows = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_order_details WHERE id_file = ".(int)$idFile);
        $isCancelled = self::isCancelledStatus($status);
        $map = [];

        foreach ($rows as $row) {
            $sku = !empty($row['sku_wholesaler']) ? $row['sku_wholesaler'] : (isset($row['product_id']) ? $row['product_id'] : '');
            $tokens = self::buildItemTokens(
                isset($row['ean']) ? $row['ean'] : '',
                isset($row['product_id']) ? $row['product_id'] : '',
                $sku,
                isset($row['name']) ? $row['name'] : ''
            );
            $key = self::getCanonicalKeyFromTokens($tokens);

            if (!isset($map[$key])) {
                $map[$key] = [
                    'name' => isset($row['name']) ? $row['name'] : '',
                    'ean' => isset($row['ean']) ? $row['ean'] : '',
                    'sku' => $sku,
                    'qty_valid' => 0,
                    'qty_cancelled' => 0,
                    'price_net' => (float)$row['price_net'],
                    'processed' => false,
                    'source_docs' => [],
                    'tokens' => $tokens,
                ];
            } else {
                $map[$key]['tokens'] = self::mergeTokenSets($map[$key]['tokens'], $tokens);
            }

            $qty = (int)$row['quantity'];
            if ($isCancelled) $map[$key]['qty_cancelled'] += $qty;
            else $map[$key]['qty_valid'] += $qty;
        }

        return $map;
    }

    private static function findBestOrderKeyForInvoiceItem($invItem, $orderMap)
    {
        $bestKey = null;
        $bestScore = -1;

        foreach ($orderMap as $ordKey => $ordItem) {
            $score = 0;

            $invTokens = isset($invItem['tokens']) ? $invItem['tokens'] : [];
            $ordTokens = isset($ordItem['tokens']) ? $ordItem['tokens'] : [];
            $ordTokenSet = [];
            foreach ($ordTokens as $ot) $ordTokenSet[$ot] = true;

            $hasEan = false;
            $hasPidOrSku = false;
            $hasName = false;

            foreach ($invTokens as $it) {
                if (!isset($ordTokenSet[$it])) continue;
                if (strpos($it, 'ean:') === 0) $hasEan = true;
                elseif (strpos($it, 'pid:') === 0 || strpos($it, 'sku:') === 0) $hasPidOrSku = true;
                elseif (strpos($it, 'name:') === 0) $hasName = true;
            }

            if ($hasEan) $score += 100;
            if ($hasPidOrSku) $score += 60;
            if ($hasName) $score += 8;

            // Dodatkowy boost jeśli cena jednostkowa podobna
            $pInv = (float)$invItem['price_net'];
            $pOrd = (float)$ordItem['price_net'];
            if (abs($pInv - $pOrd) <= 0.02) $score += 5;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey = $ordKey;
            }
        }

        // Minimalny próg sensownego dopasowania (name-only ma być za słabe)
        if ($bestScore < 30) return null;
        return $bestKey;
    }

    private static function mergeOrderMaps($headers)
    {
        $merged = [];

        foreach ($headers as $h) {
            $docNum = isset($h['external_doc_number']) ? $h['external_doc_number'] : '';
            $fileMap = self::buildOrderMapForFile((int)$h['id_file'], isset($h['status']) ? $h['status'] : '');

            foreach ($fileMap as $key => $item) {
                if (!isset($merged[$key])) {
                    $merged[$key] = $item;
                } else {
                    $merged[$key]['qty_valid'] += $item['qty_valid'];
                    $merged[$key]['qty_cancelled'] += $item['qty_cancelled'];
                    $merged[$key]['tokens'] = self::mergeTokenSets($merged[$key]['tokens'], $item['tokens']);
                }

                if ($docNum !== '' && !in_array($docNum, $merged[$key]['source_docs'])) {
                    $merged[$key]['source_docs'][] = $docNum;
                }
            }
        }

        return $merged;
    }

    private static function scoreSubset($invoiceMap, $subsetHeaders, $invoiceAmount)
    {
        if (empty($subsetHeaders)) {
            return -9999;
        }

        $ordMap = self::mergeOrderMaps($subsetHeaders);

        $totalInvQty = 0;
        $matchedInvQty = 0;
        $lineHits = 0;
        $matchedOrderKeys = [];

        foreach ($invoiceMap as $invItem) {
            $qInv = (int)$invItem['quantity'];
            if ($qInv <= 0) continue;
            $totalInvQty += $qInv;

            $okey = self::findBestOrderKeyForInvoiceItem($invItem, $ordMap);
            if ($okey === null || !isset($ordMap[$okey])) continue;

            $ord = $ordMap[$okey];
            $qOrd = (int)$ord['qty_valid'];
            if ($qOrd > 0) {
                $lineHits++;
                $matchedInvQty += min($qInv, $qOrd);
                $matchedOrderKeys[$okey] = true;
            }
        }

        $invLines = count($invoiceMap);
        $qtyCoverage = ($totalInvQty > 0) ? ($matchedInvQty / $totalInvQty) : 0;
        $lineCoverage = ($invLines > 0) ? ($lineHits / $invLines) : 0;

        $subsetAmount = 0.0;
        foreach ($subsetHeaders as $h) {
            if (!self::isCancelledStatus(isset($h['status']) ? $h['status'] : '')) {
                $subsetAmount += self::parseMoneyToFloat(isset($h['amount_netto']) ? $h['amount_netto'] : 0);
            }
        }

        $den = max(1.0, abs($invoiceAmount));
        $amountScore = 1.0 - (abs($invoiceAmount - $subsetAmount) / $den);
        if ($amountScore < 0) $amountScore = 0;

        $extraValue = 0.0;
        foreach ($ordMap as $k => $ordItem) {
            if (!isset($matchedOrderKeys[$k]) && (int)$ordItem['qty_valid'] > 0) {
                $extraValue += ((int)$ordItem['qty_valid'] * (float)$ordItem['price_net']);
            }
        }
        $extraPenalty = min(1.0, $extraValue / $den);

        // Priorytet: pokrycie pozycji. Kwota jako pomocnicze.
        return ($qtyCoverage * 65.0) + ($lineCoverage * 20.0) + ($amountScore * 10.0) - ($extraPenalty * 15.0);
    }

    private static function optimizeOrderSubset($invoiceMap, $candidateHeaders, $invoiceAmount)
    {
        if (empty($candidateHeaders)) return [];

        // Wstępne sortowanie jakości pojedynczych kandydatów
        foreach ($candidateHeaders as $idx => $h) {
            $candidateHeaders[$idx]['_solo_score'] = self::scoreSubset($invoiceMap, [$h], $invoiceAmount);
        }
        usort($candidateHeaders, function($a, $b) {
            if ($a['_solo_score'] == $b['_solo_score']) return (int)$b['id_file'] - (int)$a['id_file'];
            return ($a['_solo_score'] < $b['_solo_score']) ? 1 : -1;
        });

        // Mniejsza pula do pełnego przeszukania kombinacji
        $pool = array_slice($candidateHeaders, 0, 10);
        $n = count($pool);
        if ($n === 0) return [];

        $bestStrict = null;
        $bestStrictScore = -999999;

        $bestAny = null;
        $bestAnyScore = -999999;

        // Pełne przeszukanie wszystkich kombinacji (2^n - 1)
        for ($mask = 1; $mask < (1 << $n); $mask++) {
            $subset = [];
            $subsetAmount = 0.0;

            for ($i = 0; $i < $n; $i++) {
                if (($mask & (1 << $i)) === 0) continue;
                $subset[] = $pool[$i];
                if (!self::isCancelledStatus(isset($pool[$i]['status']) ? $pool[$i]['status'] : '')) {
                    $subsetAmount += self::parseMoneyToFloat(isset($pool[$i]['amount_netto']) ? $pool[$i]['amount_netto'] : 0);
                }
            }

            $baseScore = self::scoreSubset($invoiceMap, $subset, $invoiceAmount);
            $amountDen = max(1.0, abs($invoiceAmount));
            $amountDelta = abs($invoiceAmount - $subsetAmount);
            $amountRatio = $amountDelta / $amountDen;

            // Kara za liczbę dokumentów (wolimy mniejszy, precyzyjny zestaw)
            $docPenalty = (count($subset) - 1) * 1.5;
            $adjustedScore = $baseScore - ($amountRatio * 40.0) - $docPenalty;

            // najlepszy bez twardych filtrów (fallback)
            if ($adjustedScore > $bestAnyScore) {
                $bestAnyScore = $adjustedScore;
                $bestAny = $subset;
            }

            // Twardy filtr: suma zamówień ma być zbliżona do FV
            // Domyślnie max 35% odchylenia (lub 50 PLN dla małych faktur)
            $amountAbsOk = ($amountDelta <= 50.0);
            $amountRelOk = ($amountRatio <= 0.35);
            if (!$amountAbsOk && !$amountRelOk) {
                continue;
            }

            if ($adjustedScore > $bestStrictScore) {
                $bestStrictScore = $adjustedScore;
                $bestStrict = $subset;
            }
        }

        if (!empty($bestStrict)) {
            return $bestStrict;
        }
        if (!empty($bestAny)) {
            return $bestAny;
        }

        return [$pool[0]];
    }

    private static function getCandidateOrderHeaders($idWholesaler, $invoiceDate)
    {
        $db = Db::getInstance();
        $dateTo = $invoiceDate;

        // KROK 1: preferowane okno D-1..D0 (szybkie i najtrafniejsze)
        $dateFrom = date('Y-m-d', strtotime($invoiceDate . ' -1 day'));
        $rows = $db->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_order_files
            WHERE id_wholesaler = ".(int)$idWholesaler."
            AND doc_date BETWEEN '".pSQL($dateFrom)."' AND '".pSQL($dateTo)."'
            ORDER BY doc_date DESC, id_file DESC");

        // KROK 2 (fallback): jeśli brak dokumentów, rozszerzamy do D-5..D0
        if (empty($rows)) {
            $dateFrom = date('Y-m-d', strtotime($invoiceDate . ' -5 days'));
            $rows = $db->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_order_files
                WHERE id_wholesaler = ".(int)$idWholesaler."
                AND doc_date BETWEEN '".pSQL($dateFrom)."' AND '".pSQL($dateTo)."'
                ORDER BY doc_date DESC, id_file DESC");
        }

        if (empty($rows)) return [];

        // Unikalność po external_doc_number (najnowszy id_file zostaje)
        $out = [];
        $seen = [];
        foreach ($rows as $r) {
            $doc = (string)$r['external_doc_number'];
            if ($doc === '' || isset($seen[$doc])) continue;
            $seen[$doc] = true;
            $out[] = $r;
        }

        return $out;
    }

    public static function getInvoiceCandidateOrders($idWholesaler, $invoiceDate)
    {
        return self::getCandidateOrderHeaders((int)$idWholesaler, (string)$invoiceDate);
    }

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

        $cleanInvAmount = self::parseMoneyToFloat($invoiceHeader['amount_netto']);

        if ($cleanInvAmount < 0) {
             $db->execute("DELETE FROM "._DB_PREFIX_."azada_wholesaler_pro_analysis WHERE id_invoice_file = ".(int)$idInvoice);
             return ['status' => 'success', 'msg' => 'Korekta - pominięto.', 'result' => self::STATUS_SKIPPED];
        }

        $idWholesaler = (int)$invoiceHeader['id_wholesaler'];
        $invoiceDate = $invoiceHeader['doc_date'];
        $invoiceNum = $invoiceHeader['doc_number'];

        $invMap = self::buildInvoiceMap((int)$idInvoice);
        $candidateHeaders = self::getCandidateOrderHeaders($idWholesaler, $invoiceDate);
        $orderHeaders = self::optimizeOrderSubset($invMap, $candidateHeaders, $cleanInvAmount);

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

        $uniqueOrderNums = [];
        foreach ($orderHeaders as $oh) {
            if (!in_array($oh['external_doc_number'], $uniqueOrderNums)) {
                $uniqueOrderNums[] = $oh['external_doc_number'];
            }
        }
        $analysis->doc_number_order = implode(', ', $uniqueOrderNums);
        $analysis->id_order_file = (int)$orderHeaders[0]['id_file'];

        $ordMap = self::mergeOrderMaps($orderHeaders);

        $diffs = [];
        $totalDiffNet = 0.0;
        $matchCount = 0;
        $errCount = 0;

        foreach ($invMap as $invKey => $iItem) {
            $itemHasError = false;
            $matchedOrderKey = self::findBestOrderKeyForInvoiceItem($iItem, $ordMap);

            $srcOrders = '-';
            $sku = '';
            if ($matchedOrderKey !== null && isset($ordMap[$matchedOrderKey])) {
                $srcOrders = implode(', ', $ordMap[$matchedOrderKey]['source_docs']);
                $sku = $ordMap[$matchedOrderKey]['sku'];
                $ordMap[$matchedOrderKey]['processed'] = true;
            }

            if ($matchedOrderKey === null || !isset($ordMap[$matchedOrderKey])) {
                $invVal = (float)$iItem['price_net'] * (int)$iItem['quantity'];
                $diffs[] = [
                    'ean' => $iItem['ean'], 'name' => $iItem['name'], 'type' => self::ERR_MISSING_IN_ORDER,
                    'val_inv' => $iItem['quantity'] . ' szt × ' . number_format((float)$iItem['price_net'], 2) . ' = ' . number_format($invVal, 2) . ' PLN',
                    'val_ord' => 'BRAK',
                    'diff' => 'Brak w ZAM | Kwota: +' . number_format($invVal, 2) . ' PLN',
                    'inv_num' => $invoiceNum, 'src_ord' => 'BRAK', 'sku' => ''
                ];
                $totalDiffNet += $invVal;
                $errCount++;
                continue;
            }

            $oItem = $ordMap[$matchedOrderKey];
            $pInv = (float)$iItem['price_net'];
            $pOrd = (float)$oItem['price_net'];

            if (abs($pInv - $pOrd) > 0.019) {
                $invValPrice = $pInv * (int)$iItem['quantity'];
                $ordValPrice = $pOrd * (int)$oItem['qty_valid'];
                $priceDelta = $invValPrice - $ordValPrice;
                $diffs[] = [
                    'ean' => $iItem['ean'], 'name' => $iItem['name'], 'type' => self::ERR_PRICE,
                    'val_inv' => (int)$iItem['quantity'] . ' szt × ' . number_format($pInv, 2) . ' = ' . number_format($invValPrice, 2) . ' PLN',
                    'val_ord' => (int)$oItem['qty_valid'] . ' szt × ' . number_format($pOrd, 2) . ' = ' . number_format($ordValPrice, 2) . ' PLN',
                    'diff' => 'Cena: ' . number_format($pInv - $pOrd, 2) . ' | Kwota: ' . (($priceDelta >= 0) ? '+' : '') . number_format($priceDelta, 2) . ' PLN',
                    'inv_num' => $invoiceNum, 'src_ord' => $srcOrders, 'sku' => $sku
                ];
                $totalDiffNet += (($pInv - $pOrd) * $iItem['quantity']);
                $errCount++;
                $itemHasError = true;
            }

            $qInv = (int)$iItem['quantity'];
            $qOrdValid = (int)$oItem['qty_valid'];
            $qOrdCancelled = (int)$oItem['qty_cancelled'];

            if ($qInv != $qOrdValid) {
                if ($qInv > $qOrdValid && $qOrdCancelled > 0) {
                    $invValQty = $qInv * $pInv;
                    $ordValQty = $qOrdValid * $pOrd;
                    $qtyDeltaVal = $invValQty - $ordValQty;
                    $diffs[] = [
                        'ean' => $iItem['ean'], 'name' => $iItem['name'], 'type' => self::ERR_FOUND_IN_CANCELLED,
                        'val_inv' => $qInv . ' szt × ' . number_format($pInv, 2) . ' = ' . number_format($invValQty, 2) . ' PLN',
                        'val_ord' => $qOrdValid . ' szt (+ ' . $qOrdCancelled . ' anul.) × ' . number_format($pOrd, 2) . ' = ' . number_format($ordValQty, 2) . ' PLN',
                        'diff' => 'Ilość: ' . ($qInv - $qOrdValid) . ' szt | Kwota: ' . (($qtyDeltaVal >= 0) ? '+' : '') . number_format($qtyDeltaVal, 2) . ' PLN',
                        'inv_num' => $invoiceNum, 'src_ord' => $srcOrders, 'sku' => $sku
                    ];
                    $excess = $qInv - $qOrdValid;
                    $totalDiffNet += ($excess * $pInv);
                    $errCount++;
                    $itemHasError = true;
                } else {
                    $invValQty = $qInv * $pInv;
                    $ordValQty = $qOrdValid * $pOrd;
                    $qtyDeltaVal = $invValQty - $ordValQty;
                    $diffs[] = [
                        'ean' => $iItem['ean'], 'name' => $iItem['name'], 'type' => self::ERR_QTY,
                        'val_inv' => $qInv . ' szt × ' . number_format($pInv, 2) . ' = ' . number_format($invValQty, 2) . ' PLN',
                        'val_ord' => $qOrdValid . ' szt × ' . number_format($pOrd, 2) . ' = ' . number_format($ordValQty, 2) . ' PLN',
                        'diff' => 'Ilość: ' . ($qInv - $qOrdValid) . ' szt | Kwota: ' . (($qtyDeltaVal >= 0) ? '+' : '') . number_format($qtyDeltaVal, 2) . ' PLN',
                        'inv_num' => $invoiceNum, 'src_ord' => $srcOrders, 'sku' => $sku
                    ];
                    $totalDiffNet += (($qInv - $qOrdValid) * $pInv);
                    $errCount++;
                    $itemHasError = true;
                }
            }

            if (!$itemHasError) $matchCount++;
        }

        foreach ($ordMap as $ordKey => $oItem) {
            if (!$oItem['processed'] && (int)$oItem['qty_valid'] > 0) {
                $missingVal = (int)$oItem['qty_valid'] * (float)$oItem['price_net'];
                $srcOrders = implode(', ', $oItem['source_docs']);

                $diffs[] = [
                    'ean' => $oItem['ean'], 'name' => $oItem['name'], 'type' => self::ERR_MISSING_IN_INVOICE,
                    'val_inv' => 'BRAK',
                    'val_ord' => $oItem['qty_valid'] . ' szt × ' . number_format((float)$oItem['price_net'], 2) . ' = ' . number_format($missingVal, 2) . ' PLN',
                    'diff' => 'Brak na FV | Kwota: -' . number_format($missingVal, 2) . ' PLN',
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
