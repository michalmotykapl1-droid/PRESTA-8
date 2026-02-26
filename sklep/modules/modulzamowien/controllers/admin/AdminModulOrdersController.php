<?php
/**
 * Kontroler: AdminModulOrdersController - Wersja 24.0 (STICKY STRATEGY: Modal only once)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/OrdersSessionRepository.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/AlternativeOrdersRepository.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/PickingSessionRepository.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Suppliers/SupplierComparator.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/History/HistoryManager.php';

class AdminModulOrdersController extends ModuleAdminController
{
    private $ordersRepo;
    private $altRepo;
    private $historyManager;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->ordersRepo = new OrdersSessionRepository();
        $this->altRepo = new AlternativeOrdersRepository();
        $this->historyManager = new HistoryManager();
    }

    public function postProcess()
    {
        if (Tools::getValue('action') == 'refreshOrders') $this->ajaxProcessRefreshOrders();
        if (Tools::getValue('action') == 'saveHistory') $this->ajaxProcessSaveHistory();
        if (Tools::getValue('action') == 'deleteHistory') $this->ajaxProcessDeleteHistory();
    }


/**
 * Zapewnia istnienie tabeli statusów "WYMIENIONO" w poprawnym schemacie (per pracownik).
 */
private function ensureReplacedTable()
{
    $table = _DB_PREFIX_ . 'modulzamowien_replaced';

    try {
        Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id_employee` INT(11) NOT NULL DEFAULT 0,
                `ean` VARCHAR(32) NOT NULL,
                `date_add` DATETIME NOT NULL,
                PRIMARY KEY (`id_employee`, `ean`),
                KEY `ean_idx` (`ean`),
                KEY `date_add_idx` (`date_add`)
            ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;
        ");
    } catch (Exception $e) {
        return;
    }

    try {
        $hasCol = Db::getInstance()->executeS("SHOW COLUMNS FROM `{$table}` LIKE 'id_employee'");
        if (empty($hasCol)) {
            Db::getInstance()->execute("ALTER TABLE `{$table}` ADD `id_employee` INT(11) NOT NULL DEFAULT 0 FIRST");
        }
    } catch (Exception $e) {}

    try {
        $pkRows = Db::getInstance()->executeS("SHOW INDEX FROM `{$table}` WHERE Key_name='PRIMARY'");
        $pkCols = [];
        if ($pkRows) {
            foreach ($pkRows as $r) {
                $seq = isset($r['Seq_in_index']) ? (int)$r['Seq_in_index'] : 0;
                $col = isset($r['Column_name']) ? $r['Column_name'] : '';
                if ($seq > 0 && $col) $pkCols[$seq] = $col;
            }
            ksort($pkCols);
            $pkCols = array_values($pkCols);
        }
        if ($pkCols !== ['id_employee', 'ean']) {
            Db::getInstance()->execute("ALTER TABLE `{$table}` DROP PRIMARY KEY, ADD PRIMARY KEY (`id_employee`, `ean`)");
        }
    } catch (Exception $e) {}
}

    public function ajaxProcessRefreshOrders()
    {
        if (session_status() == PHP_SESSION_NONE) session_start();

// --- FIX: statusy "WYMIENIONO" (per pracownik, tylko dzisiaj) ---
$idEmployee = (int)$this->context->employee->id;
$replacedMap = [];
try {
    $this->ensureReplacedTable();
    if ($idEmployee > 0) {
        $replacedRows = Db::getInstance()->executeS("
            SELECT `ean`
            FROM `" . _DB_PREFIX_ . "modulzamowien_replaced`
            WHERE id_employee = " . (int)$idEmployee . " AND date_add >= CURDATE()
        ");
        if ($replacedRows) {
            foreach ($replacedRows as $rr) {
                if (!empty($rr['ean'])) {
                    $replacedMap[$rr['ean']] = 1;
                }
            }
        }
    }
} catch (Exception $e) {
    $replacedMap = [];
}

// 1. Sprawdź czy użytkownik RĘCZNIE kliknął w przycisk (wymuszenie zmiany)
        $requestedStrategy = Tools::getValue('strategy');
        
        // 2. Sprawdź czy mamy ZAPAMIĘTANĄ strategię z poprzednich kroków (np. dodawania extra)
        $storedStrategy = isset($this->context->cookie->mz_active_strategy) ? $this->context->cookie->mz_active_strategy : null;

        // Domyślna inicjalizacja (zostanie nadpisana)
        $strategy = ($storedStrategy) ? $storedStrategy : 'std';

        // --- POBIERANIE DANYCH (STANDARD) ---
        $stdItems = $this->ordersRepo->getAllItems();
        $altItems = $this->altRepo->getAllItems();
        // Employee scope (separacja sesji per pracownik)
        try { new PickingSessionRepository(); } catch (Exception $e) { }


        // --- MAPA: ZEBRANE Z WMS (qty_picked) ---
        $wmsMap = [];
        try {
            $sql = "SELECT 
                        IF(ean IS NULL OR ean = '', CONCAT('NAME_', name), CONCAT('EAN_', ean)) AS ident,
                        SUM(qty_picked) AS qty_wms
                    FROM `" . _DB_PREFIX_ . "modulzamowien_picking_session`
                    WHERE id_employee = $idEmployee
                    GROUP BY ident";
            $pickingData = Db::getInstance()->executeS($sql);
            if ($pickingData) {
                foreach ($pickingData as $p) {
                    $ident = isset($p['ident']) ? $p['ident'] : '';
                    if ($ident === '') continue;
                    $wmsMap[$ident] = (int)$p['qty_wms'];
                }
            }
        } catch (Exception $e) {
            $wmsMap = [];
        }

        // --- MAPA: ZAREZERWOWANE ZE STOŁU (Pick Stół) ---
        // Używamy qty_to_pick (rezerwacja po analizie), filtrujemy do dzisiejszych zadań (spójne z clearQueue()).
        $tableMap = [];
        try {
            $sql = "SELECT CONCAT('EAN_', ean) AS ident, SUM(qty_to_pick) AS qty_table
                    FROM `" . _DB_PREFIX_ . "modulzamowien_picking_queue`
                    WHERE ean IS NOT NULL AND ean <> '' AND DATE(date_add) = CURDATE()
                    GROUP BY ean";
            $queueData = Db::getInstance()->executeS($sql);
            if ($queueData) {
                foreach ($queueData as $q) {
                    $ident = isset($q['ident']) ? $q['ident'] : '';
                    if ($ident === '') continue;
                    $tableMap[$ident] = (int)$q['qty_table'];
                }
            }
        } catch (Exception $e) {
            $tableMap = [];
        }

        // Pokrycie "z magazynu" dla statystyk/braków = WMS + stół
        $coverageMap = $wmsMap;
        if (!empty($tableMap)) {
            foreach ($tableMap as $k => $v) {
                if (!isset($coverageMap[$k])) $coverageMap[$k] = 0;
                $coverageMap[$k] += (int)$v;
            }
        }

        // --- KOREKTA: Zmniejszamy zakupy o to co REALNIE zebrano w WMS (tylko WMS, bez stołu) ---
        $this->applyWmsCorrection($stdItems, $wmsMap);
        $this->applyWmsCorrection($altItems, $wmsMap);


        // --- OBLICZENIA FINANSOWE ---
        $totalValStd = $this->calculateTotalValue($stdItems);
        $totalValAlt = $this->calculateTotalValue($altItems);

        $bpMap = [];
        try {
            $bpRaw = Db::getInstance()->executeS("SELECT kod_kreskowy, minimum_logistyczne FROM `" . _DB_PREFIX_ . "azada_raw_bioplanet`");
            if ($bpRaw) {
                foreach ($bpRaw as $r) $bpMap[$r['kod_kreskowy']] = (int)$r['minimum_logistyczne'];
            }
        } catch (Exception $e) {}

        $realStatsStd = $this->calculateRealCashOutlay($stdItems, $bpMap);
        $realStatsAlt = $this->calculateRealCashOutlay($altItems, $bpMap);

        // --- INTELIGENTNY WYBÓR I MODAL (Z POPRAWKĄ "STICKY") ---
        
        $showModal = false;
        $autoSavings = 0;
        $betterStrategyName = '';

        if (!empty($requestedStrategy) && ($requestedStrategy === 'std' || $requestedStrategy === 'alt')) {
            // SCENARIUSZ A: Użytkownik klika w przycisk
            $strategy = $requestedStrategy;
            // Zapamiętujemy wybór, żeby nie pytać ponownie przy dodawaniu Extra
            $this->context->cookie->mz_active_strategy = $strategy;
            $this->context->cookie->write();
            
        } elseif (!empty($storedStrategy)) {
            // SCENARIUSZ B: Użytkownik już coś robił (np. dodaje Extra) -> NIE POKAZUJ MODALA
            $strategy = $storedStrategy;
            // Tutaj $showModal zostaje false, więc okno nie wyskoczy
            
        } else {
            // SCENARIUSZ C: Pierwsze wejście (brak ciasteczka) -> AUTO-WYBÓR + MODAL
            $showModal = true; 
            
            $costStd = $realStatsStd['total_cost'];
            $costAlt = $realStatsAlt['total_cost'];

            if ($costAlt < $costStd) {
                $strategy = 'alt'; // Fioletowa tańsza
                $autoSavings = $costStd - $costAlt;
                $betterStrategyName = 'STRATEGIA ALTERNATYWNA';
            } else {
                $strategy = 'std'; // Niebieska tańsza
                $autoSavings = $costAlt - $costStd;
                $betterStrategyName = 'NAJNIŻSZA CENA';
            }
            
            // Jeśli oszczędność znikoma, nie pokazuj modala
            if ($autoSavings < 1.00) {
                $showModal = false;
            }

            // Zapamiętujemy ten automatyczny wybór
            $this->context->cookie->mz_active_strategy = $strategy;
            $this->context->cookie->write();
        }

        // --- PRZYGOTOWANIE DANYCH DO WIDOKU ---
        $rawData = ($strategy === 'alt') ? $altItems : $stdItems;

        $rawData = array_filter($rawData, function($r) {
            $buy = (int)$r['qty_buy'];
            $missing = isset($r['missing_qty']) ? (int)$r['missing_qty'] : 0;
            if ($buy <= 0 && $missing <= 0) return false;
            return true;
        });
        $rawData = array_values($rawData);

        // --- EXTRA ITEMS (PEŁNY KOD) ---
        $extraItems = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "modulzamowien_extra_items`");
        $internalPicks = []; 
        $supplierComp = new SupplierComparator();

        if ($extraItems) {
            foreach ($extraItems as $extra) {
                $currentQty = 0; $foundWms = false;
                $sku = isset($extra['sku']) ? $extra['sku'] : '';
                $ean = isset($extra['ean']) ? $extra['ean'] : '';
                $skuSafe = pSQL($sku);
                
                if (strpos($sku, 'DUPL_') === 0) {
                    $duplId = (int)str_replace('DUPL_', '', $sku);
                    $val = Db::getInstance()->getValue("SELECT quantity FROM `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` WHERE id = $duplId");
                    if ($val !== false) { $currentQty = (int)$val; $foundWms = true; }
                } elseif (strpos($sku, 'A_MAG') === 0 || (!empty($sku) && strpos($sku, '_MAG') !== false)) {
                    $val = Db::getInstance()->getValue("SELECT quantity_wms FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE sku = '$skuSafe'");
                    if ($val !== false) { $currentQty = (int)$val; $foundWms = true; }
                }
                
                if (!$foundWms && !empty($ean)) {
                    $eanSafe = pSQL($ean);
                    if ((int)$extra['is_mag'] == 1) {
                        $sumVal = Db::getInstance()->getValue("SELECT SUM(quantity_wms) FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE ean = '$eanSafe'");
                        $currentQty = ($sumVal) ? (int)$sumVal : 0;
                    } else {
                        $sqlSumPresta = "SELECT SUM(sa.quantity) FROM `" . _DB_PREFIX_ . "product` p 
                                         JOIN `" . _DB_PREFIX_ . "stock_available` sa ON (p.id_product = sa.id_product AND sa.id_product_attribute = 0) 
                                         WHERE p.ean13 = '$eanSafe'";
                        $currentQty = (int)Db::getInstance()->getValue($sqlSumPresta);
                    }
                }

                $displayQty = $currentQty;
                $isMag = (isset($extra['is_mag']) && (int)$extra['is_mag'] == 1);
                
                if ($isMag && $foundWms) {
                    $displayQty = $currentQty + (int)$extra['qty'];
                }

                if ($isMag) {
                    $extra['qty_stock_current'] = $displayQty;
                    $internalPicks[] = $extra;
                } else {
                    $namePrefix = (strpos($extra['name'], '[EXTRA]') === false) ? '[EXTRA] ' : '';
                    $qtyNeeded = (int)$extra['qty'];
                    $res = $supplierComp->findSuppliersForQty($ean, $extra['name'], $qtyNeeded, $sku);
                    $qtyFound = (isset($res['found_qty']) && $res['found_qty'] > 0) ? $res['found_qty'] : $qtyNeeded;
                    $missing = $qtyNeeded - ((isset($res['found_qty']) && $res['found_qty'] > 0) ? $res['found_qty'] : 0);

                    $rawData[] = [
                        'ean' => $ean,
                        'name' => $namePrefix . $extra['name'],
                        'qty_buy' => $qtyFound,
                        'qty_total' => $qtyNeeded,
                        'supplier' => isset($res['supplier_raw']) ? $res['supplier_raw'] : $res['supplier_name'],
                        'price' => $res['price'],
                        'tax_rate' => $res['tax_rate'],
                        'status' => isset($res['status_code']) ? $res['status_code'] : 'OK',
                        'was_switched' => $res['was_switched'],
                        'missing_qty' => $missing,
                        'sku' => $sku,
                        'savings' => isset($res['savings']) ? $res['savings'] : 0,
                        'is_logistic_switch' => isset($res['is_logistic_switch']) ? $res['is_logistic_switch'] : false
                    ];
                }
            }
        }

        if (empty($rawData) && empty($internalPicks)) die('<div class="alert alert-warning">Brak danych.</div>');
        
        // ETYKIETY
        foreach ($rawData as &$item) {
            $item['price_gross'] = $item['price'] * (1 + ($item['tax_rate'] / 100));
            if (strpos($item['supplier'], '##LOGISTIC##') !== false) $item['supplier'] = str_replace(' ##LOGISTIC##', '', $item['supplier']);
            $item['clean_supplier_key'] = $this->cleanSupplierNameForGrouping($item['supplier']); 
            
            $badgesHtml = '';
            if (isset($item['is_logistic_switch']) && $item['is_logistic_switch']) $badgesHtml .= ' <span class="label label-danger" style="font-size:10px; margin-left:5px; background-color:#9c27b0;">OMIJAM MINIMUM</span>';
            elseif (isset($item['was_switched']) && $item['was_switched']) $badgesHtml .= ' <span class="label label-success" style="font-size:10px; margin-left:5px;">TAŃSZY</span>';
            
            if ($item['qty_buy'] > 0) {
                if ($item['status'] === 'PARTIAL' || (isset($item['missing_qty']) && $item['missing_qty'] > 0)) $badgesHtml .= ' <span class="label label-info" style="font-size:10px; margin-left:5px;">CZĘŚCIOWO (' . $item['qty_buy'] . '/' . $item['qty_total'] . ')</span>';
            }
            if (!empty($badgesHtml)) $item['name'] .= $badgesHtml;
        }
        unset($item);

        // GRUPOWANIE
        $groupedData = $this->groupDataForView($rawData, $coverageMap);
        require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Orders/OrderManager.php';
        $orderManager = new OrderManager();
        $ordersGrouped = $orderManager->prepareOrderList($groupedData['data_orders']);
        
        $noStockGrouped = [];
        foreach ($groupedData['data_no_stock'] as $item) {
            $supplierName = $item['clean_supplier_key']; 
            if (!isset($noStockGrouped[$supplierName])) $noStockGrouped[$supplierName] = ['supplier_name' => $supplierName, 'items' => []];
            $noStockGrouped[$supplierName]['items'][] = $item;
        }
        ksort($noStockGrouped); 
        foreach ($noStockGrouped as &$group) {
            usort($group['items'], function($a, $b) { return strcasecmp($a['name'], $b['name']); });
        }

        // ASSIGN
        $this->context->smarty->assign([
            'current_strategy' => $strategy,
            'total_val_std' => $totalValStd,
            'total_val_alt' => $totalValAlt,
            
            'real_cash_std' => $realStatsStd['total_cost'],
            'surplus_items_std' => $realStatsStd['total_surplus'],
            'real_cash_alt' => $realStatsAlt['total_cost'],
            'surplus_items_alt' => $realStatsAlt['total_surplus'],

            'show_auto_modal' => $showModal,
            'auto_savings' => $autoSavings,
            'better_strategy_name' => $betterStrategyName,

            'orders_grouped' => $ordersGrouped,
            'internal_picks' => $internalPicks,
            'list_not_found' => $groupedData['data_not_found'],
            'list_partial_orders' => $groupedData['data_partial'],
            'list_no_stock_grouped' => $noStockGrouped,
            'global_stats' => $groupedData['stats'],
            'ajax_history_save_url' => $this->context->link->getAdminLink('AdminModulOrders') . '&ajax=1&action=saveHistory',

            // FIX: mapy i URL-e dla "WYMIENIONO"
            'replaced_map' => $replacedMap,
            'ajax_save_fix_url' => $this->context->link->getAdminLink('AdminModulZamowien') . '&ajax=1&action=saveFixStatus',
            'ajax_get_fix_url' => $this->context->link->getAdminLink('AdminModulZamowien') . '&ajax=1&action=getFixStatuses'
        ]);
        
        die($this->createTemplate('tab_orders.tpl')->fetch());
    }

    private function calculateRealCashOutlay($items, $bpMap) {
        $totalRealCost = 0; $totalSurplus = 0;
        foreach ($items as $item) {
            if ($item['qty_buy'] <= 0) continue;
            $needed = (int)$item['qty_buy']; $price = (float)$item['price'];
            $supplier = strtoupper($item['supplier']);
            $eanSafe = preg_replace('/[^0-9]/', '', $item['ean']);
            $payQty = $needed;
            if (strpos($supplier, 'BIO PLANET') !== false) {
                $min = 1; if (isset($bpMap[$eanSafe])) $min = (int)$bpMap[$eanSafe];
                if ($min > 1 && $needed < $min) { $payQty = $min; $totalSurplus += ($min - $needed); }
            }
            $totalRealCost += round($payQty * $price, 2);
        }
        return ['total_cost' => $totalRealCost, 'total_surplus' => $totalSurplus];
    }


    private function applyWmsCorrection(&$items, $wmsMap)
    {
        if (empty($items) || empty($wmsMap)) return;

        // Grupujemy indeksy wierszy po identyfikatorze produktu (EAN_... / NAME_...)
        $groups = [];
        foreach ($items as $idx => $row) {
            $ident = (!empty($row['ean'])) ? 'EAN_' . $row['ean'] : 'NAME_' . $row['name'];
            if (isset($wmsMap[$ident]) && (int)$wmsMap[$ident] > 0) {
                if (!isset($groups[$ident])) $groups[$ident] = [];
                $groups[$ident][] = $idx;
            }
        }

        foreach ($groups as $ident => $indices) {
            $stock = (int)$wmsMap[$ident];
            if ($stock <= 0) continue;

            // 1) Najpierw pokrywamy "braki" (missing_qty) — bo to były realne niedobory w hurtowniach
            foreach ($indices as $idx) {
                if ($stock <= 0) break;

                if (!isset($items[$idx]['missing_qty'])) continue;
                $missing = (int)$items[$idx]['missing_qty'];
                if ($missing <= 0) continue;

                $deduct = min($missing, $stock);
                $items[$idx]['missing_qty'] = $missing - $deduct;

                // Jeśli to placeholder (NO_STOCK / NOT_FOUND), qty_buy zwykle odzwierciedla brakującą ilość — trzymaj spójnie
                $status = isset($items[$idx]['status']) ? $items[$idx]['status'] : '';
                if (($status === 'NO_STOCK' || $status === 'NOT_FOUND') && isset($items[$idx]['qty_buy'])) {
                    $qb = (int)$items[$idx]['qty_buy'];
                    $items[$idx]['qty_buy'] = max(0, $qb - $deduct);
                }

                $stock -= $deduct;
            }

            // 2) Dopiero potem zmniejszamy realne zakupy (qty_buy) w wierszach OK/PARTIAL
            foreach ($indices as $idx) {
                if ($stock <= 0) break;

                $status = isset($items[$idx]['status']) ? $items[$idx]['status'] : '';
                if ($status === 'NO_STOCK' || $status === 'NOT_FOUND') continue;

                $qtyBuy = (int)$items[$idx]['qty_buy'];
                if ($qtyBuy <= 0) continue;

                $deduct = min($qtyBuy, $stock);
                $items[$idx]['qty_buy'] = $qtyBuy - $deduct;
                $stock -= $deduct;
            }
        }
    }


    private function calculateTotalValue($items) { 
        $sum = 0; foreach ($items as $item) { if ($item['qty_buy'] > 0) $sum += round($item['qty_buy'] * $item['price'], 2); } return $sum; 
    }
    
    private function groupDataForView($rawData, $stockMap = []) { 
        $ordersList = []; $notFoundList = []; $noStockList = []; $partialList = []; $totalOptimized = 0; $totalSavings = 0; 
        $aggregated = []; foreach ($rawData as $item) { $key = $item['ean'] ? 'EAN_'.$item['ean'] : 'NAME_'.$item['name']; if (!isset($aggregated[$key])) { $aggregated[$key] = [ 'qty_total' => (int)$item['qty_total'], 'qty_bought' => 0, 'items' => [] ]; } if ($item['status'] != 'NO_STOCK' && $item['status'] != 'NOT_FOUND') { $aggregated[$key]['qty_bought'] += (int)$item['qty_buy']; } $aggregated[$key]['items'][] = $item; } 
        foreach ($aggregated as $key => $data) { 
            $wmsQty = isset($stockMap[$key]) ? $stockMap[$key] : 0; $missingGlobal = $data['qty_total'] - $data['qty_bought'] - $wmsQty; if ($missingGlobal < 0) $missingGlobal = 0; 
            foreach ($data['items'] as $item) { 
                $qtyBuy = (int)$item['qty_buy']; $status = isset($item['status']) ? $item['status'] : 'OK'; 
                if (isset($item['was_switched']) && $item['was_switched']) { $totalOptimized++; if (isset($item['savings'])) $totalSavings += (float)$item['savings']; } 
                if ($status == 'NOT_FOUND') { $notFoundList[] = $item; } elseif ($status == 'NO_STOCK') { if ($data['qty_bought'] == 0) { if ($missingGlobal > 0) { $item['qty_buy'] = $missingGlobal; $noStockList[] = $item; } } } else { if ($missingGlobal > 0) { $item['missing_qty'] = $missingGlobal; $partialList[] = $item; } if ($qtyBuy > 0) $ordersList[] = $item; } 
            } 
        } 
        return [ 'data_orders' => $ordersList, 'data_not_found' => $notFoundList, 'data_no_stock' => $noStockList, 'data_partial' => $partialList, 'stats' => ['total_savings' => $totalSavings, 'optimized_count' => $totalOptimized] ]; 
    }
    
    private function cleanSupplierNameForGrouping($html) { $clean = strip_tags($html); $replace = ['TAŃSZY', 'Tańszy', 'CZĘŚCIOWO', 'Częściowo', 'ZAMIAST:', 'Zamiast:', 'Oszczędzasz:', 'OMIJAM MINIMUM']; $clean = str_ireplace($replace, '', $clean); $clean = preg_replace('/\s*\(\d+\/\d+\)/', '', $clean); return trim(preg_replace('/\s+/', ' ', $clean)); }

    public function ajaxProcessSaveHistory() { $supplier = Tools::getValue('supplier'); $cost = (float)Tools::getValue('cost'); $itemsJson = Tools::getValue('items'); if (empty($supplier) || empty($itemsJson)) die(json_encode(['success' => false])); $employee = $this->context->employee; $employeeName = $employee->firstname . ' ' . $employee->lastname; $res = $this->historyManager->saveHistory($employee->id, $employeeName, $supplier, $cost, $itemsJson); die(json_encode(['success' => $res])); }
    public function ajaxProcessDeleteHistory() { $id = (int)Tools::getValue('id_history'); if ($id) { $res = $this->historyManager->deleteHistory($id); die(json_encode(['success' => $res])); } die(json_encode(['success' => false])); }
}
?>