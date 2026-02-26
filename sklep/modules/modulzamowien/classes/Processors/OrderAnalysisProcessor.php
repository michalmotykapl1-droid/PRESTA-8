<?php
/**
 * Klasa OrderAnalysisProcessor
 * Wersja: 10.0 (Smart Swap - Generowanie Alternatyw - FULL RESTORED CODE)
 * ZACHOWANO: Pełną logikę Extra Items oraz Cascade.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Stock/LocalStockManager.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Wms/PickingManager.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Stock/SurplusManager.php'; 
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Input/CsvImporter.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/PickingSessionRepository.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/OrdersSessionRepository.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/AlternativeOrdersRepository.php'; 
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Suppliers/SupplierComparator.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Suppliers/AlternativeComparator.php'; 

class OrderAnalysisProcessor
{
    private $sessionManager;
    private $pickingRepo;
    private $ordersRepo;
    private $altRepo;

    public function __construct($sessionManager)
    {
        $this->sessionManager = $sessionManager;
        $this->pickingRepo = new PickingSessionRepository();
        $this->ordersRepo = new OrdersSessionRepository();
        $this->altRepo = new AlternativeOrdersRepository(); 
    }

    /**
     * GŁÓWNA METODA (Odświeżanie zakładek)
     */
    public function processAnalysis($strategy = 'std')
    {
        $this->pickingRepo->clearSession();
        $this->ordersRepo->clearSession();
        $this->altRepo->clearSession();

        // Uruchamiamy logikę Extra
        $this->processExtraItems($strategy);
        
        return true;
    }

    /**
     * LOGIKA EXTRA ITEMS (Z obsługą alternatyw Smart Swap)
     */
    private function processExtraItems($strategy)
    {
        $extraItems = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "modulzamowien_extra_items` ORDER BY id_extra ASC");
        
        if (!$extraItems) return;

        $stockManager = new LocalStockManager();
        $supplierComp = new SupplierComparator();
        $altComp = new AlternativeComparator(); 

        $pickingBatch = [];
        $ordersBatch = [];

        foreach ($extraItems as $item) {
            $ean = trim($item['ean']);
            $qty = (int)$item['qty'];
            $name = $item['name'];
            $origSku = $item['sku'];
            $isMag = (int)$item['is_mag'];

            if ($qty <= 0) continue;

            // --- PRZYPADEK 1: MAGAZYN (Zbiórka) ---
            if ($isMag === 1) {
                $stockInfo = $stockManager->checkStock($ean, $origSku, $qty, $name);
                
                // Active Guard (bezpieczny dzięki LocalStockManager)
                if (isset($stockInfo['product_id']) && $stockInfo['product_id'] > 0) {
                    $isActive = (int)Db::getInstance()->getValue("SELECT active FROM " . _DB_PREFIX_ . "product WHERE id_product = " . (int)$stockInfo['product_id']);
                    
                    if ($isActive === 0) {
                        $stockInfo['batches'] = [];
                        $stockInfo['taken'] = 0; 
                    }
                }

                if (!empty($stockInfo['batches'])) {
                    // --- ZBIERANIE ALTERNATYW ---
                    // Kopiujemy wszystkie partie jako potencjalne alternatywy
                    $allBatches = $stockInfo['batches'];
                    $alternativesJson = json_encode($allBatches);

                    foreach ($stockInfo['batches'] as $batch) {
                        if ($qty <= 0) break;
                        $qtyAvailable = (int)$batch['quantity'];
                        $toTake = min($qty, $qtyAvailable);
                        
                        $pickingBatch[] = [
                            'ean' => $ean,
                            'sku' => $origSku,
                            'name' => $name,
                            'location' => $batch['location_code'], // Upewnij się, że LocalStockManager zwraca to pole lub użyj 'regal' . ' ' . 'polka'
                            'regal' => $batch['regal'],
                            'polka' => $batch['polka'],
                            'qty_to_pick' => $toTake,
                            'qty_original' => $qtyAvailable,
                            'image_id' => $stockInfo['image_id'],
                            'link_rewrite' => $stockInfo['link_rewrite'],
                            'alternatives_json' => $alternativesJson // ZAPISUJEMY ALTERNATYWY
                        ];
                        $qty -= $toTake; 
                    }
                } else {
                    // Brak w WMS
                    $pickingBatch[] = [
                        'ean' => $ean,
                        'sku' => $origSku,
                        'name' => (strpos($stockInfo['name'], '[WYŁĄCZONY]') !== false) ? $stockInfo['name'] : ('[BRAK WMS] ' . $name),
                        'location' => 'BRAK',
                        'regal' => '',
                        'polka' => '',
                        'qty_to_pick' => $qty,
                        'qty_original' => 0,
                        'image_id' => 0,
                        'link_rewrite' => '',
                        'alternatives_json' => NULL
                    ];
                }
            } 
            // --- PRZYPADEK 2: HURTOWNIA (Zakup) ---
            else {
                $bestOffer = null;
                $candidatesStd = $supplierComp->getAllOffersByEan($ean);
                $candidatesAlt = $altComp->getAllOffersByEan($ean);
                $allCandidates = array_merge($candidatesStd, $candidatesAlt);
                $availableCandidates = [];
                foreach ($allCandidates as $cand) {
                    if ($cand['qty'] > 0) $availableCandidates[] = $cand;
                }
                if (!empty($availableCandidates)) {
                    usort($availableCandidates, function($a, $b) { return $a['price'] <=> $b['price']; });
                    $winner = $availableCandidates[0];
                    $bestOffer = ['supplier_name' => strtoupper($winner['supplier']), 'price' => $winner['price'], 'savings' => 0, 'tax_rate' => $winner['tax_rate'], 'status_code' => 'OK'];
                } else {
                    $bestOffer = $supplierComp->findSuppliersForQty($ean, $name, $qty);
                }
                $ordersBatch[] = [
                    'ean' => $ean, 'sku' => $origSku, 'name' => $name, 'qty_buy' => $qty, 'qty_total' => $qty,
                    'supplier_name' => isset($bestOffer['supplier_name']) ? $bestOffer['supplier_name'] : 'BRAK W BAZIE',
                    'price_net' => isset($bestOffer['price']) ? (float)$bestOffer['price'] : 0,
                    'savings' => isset($bestOffer['savings']) ? (float)$bestOffer['savings'] : 0,
                    'tax_rate' => isset($bestOffer['tax_rate']) ? (float)$bestOffer['tax_rate'] : 0,
                    'status' => isset($bestOffer['status_code']) ? $bestOffer['status_code'] : 'OK'
                ];
            }
        }

        if (!empty($pickingBatch)) $this->pickingRepo->addItemsToSession($pickingBatch);
        if (!empty($ordersBatch)) $this->ordersRepo->addItemsToSession($ordersBatch);
    }

    /**
     * IMPORT CSV (Z obsługą alternatyw Smart Swap)
     */
    /**
 * IMPORT CSV (Z obsługą alternatyw Smart Swap)
 * + GENEROWANIE RAPORTU (Zakładka 1)
 */
public function processCsvAnalysis($fileTmpPath, $consumePickTable = true)
{
    // Czyścimy sesje zakupowe (zakładka 3) – kompletacja (zakładka 2) jest sumowana po SKU w DB.
    $this->ordersRepo->clearSession();
    $this->altRepo->clearSession();

    // Start sesji (raport trzymamy w $_SESSION)
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $fileHash = md5_file($fileTmpPath);
    if ($this->pickingRepo->isFileProcessed($fileHash)) {
        return "[DUPLIKAT] Ten plik został już wgrany dzisiaj!";
    }

    // Czyścimy statusy "WYMIENIONO" dla bieżącego pracownika - nowa analiza ma startować na czysto
    try {
        $idEmployee = (int)Context::getContext()->employee->id;
        if ($idEmployee > 0) {
            Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . "modulzamowien_replaced` WHERE id_employee = " . (int)$idEmployee);
        }
    } catch (Exception $e) {
        // ignore
    }


    $importer = new CsvImporter();
    $rawItems = $importer->processFile($fileTmpPath);

    if (empty($rawItems)) {
        return "Brak danych w pliku.";
    }

    // --- Grupowanie pozycji z pliku (sumowanie ilości po EAN / nazwie) ---
    $groupedItems = [];
    foreach ($rawItems as $item) {
        $ean = isset($item['ean']) ? trim($item['ean']) : '';
        $csvName = isset($item['csv_name']) ? trim($item['csv_name']) : '';
        $key = (!empty($ean)) ? 'EAN_' . $ean : 'NAME_' . $csvName;

        if (!isset($groupedItems[$key])) {
            // Normalizacja pól
            if (!isset($item['csv_name'])) {
                $item['csv_name'] = $csvName;
            }
            if (isset($item['sku'])) {
                $item['sku'] = trim($item['sku']);
            }
            $groupedItems[$key] = $item;
        } else {
            $groupedItems[$key]['quantity'] += (int)$item['quantity'];
        }
    }
    $rawItems = array_values($groupedItems);

    $stockManager = new LocalStockManager();
    $surplusManager = new SurplusManager();

    $pickingList = [];
    $ordersListTmp = [];

    // --- RAPORT: wczytujemy poprzedni raport (żeby analiza mogła być sumowana na kilku plikach) ---
    $reportMap = [];
    if (isset($_SESSION['mz_report_data']) && is_array($_SESSION['mz_report_data'])) {
        foreach ($_SESSION['mz_report_data'] as $oldRow) {
            if (!is_array($oldRow)) {
                continue;
            }
            $oldEan = isset($oldRow['ean']) ? trim((string)$oldRow['ean']) : '';
            $oldName = isset($oldRow['name']) ? trim((string)$oldRow['name']) : '';
            $oldKey = (!empty($oldEan)) ? 'EAN_' . $oldEan : 'NAME_' . $oldName;

            $reportMap[$oldKey] = [
                'name' => $oldName,
                'ean' => $oldEan,
                'match_type' => isset($oldRow['match_type']) ? $oldRow['match_type'] : '',
                'qty_total' => isset($oldRow['qty_total']) ? (int)$oldRow['qty_total'] : 0,
                'qty_stock' => isset($oldRow['qty_stock']) ? (int)$oldRow['qty_stock'] : 0,
                'qty_buy' => isset($oldRow['qty_buy']) ? (int)$oldRow['qty_buy'] : 0,
                'locations' => [],
            ];

            // Odtwarzamy listę lokacji z poprzedniego stringa (opcjonalnie)
            if (!empty($oldRow['location'])) {
                $parts = explode(',', (string)$oldRow['location']);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '') {
                        $reportMap[$oldKey]['locations'][$p] = 1;
                    }
                }
            }
        }
    }

    foreach ($rawItems as $item) {
        $ean = isset($item['ean']) ? trim((string)$item['ean']) : '';
        $skuInput = isset($item['sku']) ? trim((string)$item['sku']) : '';
        $qtyTotal = isset($item['quantity']) ? (int)$item['quantity'] : 0;
        $csvName = isset($item['csv_name']) ? trim((string)$item['csv_name']) : '';

        if ($qtyTotal <= 0) {
            continue;
        }

        // Nazwa fallback z CSV (żeby raport nie był pusty jeśli produkt nie istnieje w Preście)
        $stockInfo = $stockManager->checkStock($ean, $skuInput, $qtyTotal, $csvName);

        // 1. ACTIVE GUARD (Bezpieczny dzięki LocalStockManager)
        if (isset($stockInfo['product_id']) && (int)$stockInfo['product_id'] > 0) {
            $isActive = (int)Db::getInstance()->getValue(
                "SELECT active FROM " . _DB_PREFIX_ . "product WHERE id_product = " . (int)$stockInfo['product_id']
            );

            if ($isActive === 0) {
                $stockInfo['batches'] = [];
                $stockInfo['taken'] = 0;
            }
        }

        $productName = !empty($stockInfo['name']) ? $stockInfo['name'] : $csvName;

        // A. Stół (Pick Stół / Wirtualny magazyn) – opcjonalnie
        $qtyToBuy = $qtyTotal;
        $pickedFromTable = 0;
        if ($consumePickTable && $qtyToBuy > 0) {
            $pickedFromTable = (int)$surplusManager->consumeSurplus($ean, $qtyToBuy, $productName);
            $qtyToBuy -= $pickedFromTable;
        }

        // B. Magazyn (WMS)
        // Ilość do zebrania z WMS = zamówienie klienta minus to, co pokryto ze Stołu (jeśli włączone).
        // Dodatkowo: nie przekraczamy realnej dostępności w WMS (stockInfo['taken']).
        $requiredPickQty = max(0, $qtyTotal - $pickedFromTable);
        $qtyRemainingToPick = min((int)$stockInfo['taken'], $requiredPickQty);

        $wmsPickedActual = 0;
        $usedLoc = [];

        if (!empty($stockInfo['batches']) && $qtyRemainingToPick > 0) {
            // Zbieramy WSZYSTKIE partie jako potencjalne alternatywy dla każdego wiersza tego produktu
            $alternativesJson = json_encode($stockInfo['batches']);

            foreach ($stockInfo['batches'] as $batch) {
                if ($qtyRemainingToPick <= 0) {
                    break;
                }

                $availableInBatch = (int)$batch['quantity'];
                $takeFromBatch = min($qtyRemainingToPick, $availableInBatch);

                if ($takeFromBatch > 0) {
                    $pickingList[] = [
                        'ean' => $ean,
                        'sku' => $batch['sku'],
                        'name' => $productName,
                        'qty_stock' => $takeFromBatch,
                        'qty_stock_original' => $takeFromBatch,
                        'regal' => $batch['regal'],
                        'polka' => $batch['polka'],
                        'location' => $batch['regal'] . ' ' . $batch['polka'],
                        'image_id' => $stockInfo['image_id'],
                        'link_rewrite' => $stockInfo['link_rewrite'],
                        'id_product' => $stockInfo['product_id'],
                        'alternatives_json' => $alternativesJson
                    ];

                    $wmsPickedActual += $takeFromBatch;
                    $qtyRemainingToPick -= $takeFromBatch;

                    $locLabel = trim($batch['regal'] . ' ' . $batch['polka']);
                    if ($locLabel !== '') {
                        $usedLoc[$locLabel] = 1;
                    }
                }
            }
        }

        // C. Do listy zakupów (HURTOWNIE)
        // $qtyToBuy = pełne zamówienie minus to, co było na stole (jeśli włączone).
        // Korekta o WMS dzieje się dalej (Zakładka 3), natomiast RAPORT pokazuje realne "do kupienia" (braki).
        if ($qtyToBuy > 0) {
            $ordersListTmp[] = [
                'ean' => $ean,
                'sku' => $skuInput,
                'name' => $productName,
                'qty_buy' => $qtyToBuy,
                'qty_total' => $qtyTotal
            ];
        }

        // --- RAPORT: agregacja po EAN / nazwie ---
        $reportKey = (!empty($ean)) ? 'EAN_' . $ean : 'NAME_' . (!empty($csvName) ? $csvName : $productName);
        if (!isset($reportMap[$reportKey])) {
            $reportMap[$reportKey] = [
                'name' => $productName,
                'ean' => $ean,
                'match_type' => empty($ean) ? 'name' : '',
                'qty_total' => 0,
                'qty_stock' => 0, // Z magazynu (WMS + stół)
                'qty_buy' => 0,   // Realne braki do kupienia (po WMS + stół)
                'locations' => [],
            ];
        }

        // Ile realnie pokryliśmy "z magazynu" (WMS + stół)
        $qtyFromWarehouse = (int)$pickedFromTable + (int)$wmsPickedActual;
        $qtyMissing = max(0, $qtyTotal - $qtyFromWarehouse);

        $reportMap[$reportKey]['qty_total'] += $qtyTotal;
        $reportMap[$reportKey]['qty_stock'] += $qtyFromWarehouse;
        $reportMap[$reportKey]['qty_buy'] += $qtyMissing;

        // Lokacje: WMS + ewentualnie "STÓŁ"
        foreach ($usedLoc as $loc => $v) {
            $reportMap[$reportKey]['locations'][$loc] = 1;
        }
        if ($pickedFromTable > 0) {
            $reportMap[$reportKey]['locations']['STÓŁ'] = 1;
        }

        // Uzupełnienie nazwy jeśli wcześniej była pusta
        if (empty($reportMap[$reportKey]['name']) && !empty($productName)) {
            $reportMap[$reportKey]['name'] = $productName;
        }
    }

    // Zapis kompletacji (Zakładka 2)
    $this->sessionManager->addPickingToSession($pickingList);
    $_SESSION['mz_picking_data'] = $this->sessionManager->loadPickingFromFile();

    // --- Wyliczenie dostawcy dla RAPORTU (na podstawie realnych braków qty_buy) ---
    $reportSupplierComp = new SupplierComparator();
    $reportRows = [];

    foreach ($reportMap as $rKey => $r) {
        $row = $r;

        $row['location'] = '';
        if (!empty($row['locations']) && is_array($row['locations'])) {
            $row['location'] = implode(', ', array_keys($row['locations']));
        }

        $row['supplier'] = '';
        $needBuy = isset($row['qty_buy']) ? (int)$row['qty_buy'] : 0;
        if ($needBuy > 0) {
            $offer = $reportSupplierComp->findSuppliersForQty($row['ean'], $row['name'], $needBuy);
            $status = isset($offer['status_code']) ? $offer['status_code'] : 'OK';

            if ($status === 'NOT_FOUND') {
                $row['supplier'] = 'BRAK W BAZIE';
            } elseif ($status === 'NO_STOCK') {
                $row['supplier'] = 'BRAK NA RYNKU';
            } elseif ($status === 'PARTIAL') {
                $found = isset($offer['found_qty']) ? (int)$offer['found_qty'] : 0;
                $supName = isset($offer['supplier_name']) ? $offer['supplier_name'] : '-';
                $row['supplier'] = $supName . ' <span class="label label-info" style="font-size:10px; margin-left:5px;">CZĘŚCIOWO ' . $found . '/' . $needBuy . '</span>';
            } else {
                $row['supplier'] = isset($offer['supplier_name']) ? $offer['supplier_name'] : '-';
            }
        }

        unset($row['locations']);
        $reportRows[] = $row;
    }

    // Sortowanie: najpierw braki, potem nazwa
    usort($reportRows, function ($a, $b) {
        $qa = isset($a['qty_buy']) ? (int)$a['qty_buy'] : 0;
        $qb = isset($b['qty_buy']) ? (int)$b['qty_buy'] : 0;
        if ($qa === $qb) {
            return strcmp((string)$a['name'], (string)$b['name']);
        }
        return ($qb <=> $qa);
    });

    $_SESSION['mz_report_data'] = $reportRows;

    // --- Strategia zakupów (Zakładka 3): STD + ALT (kaskada) ---
    $supplierCompStd = new SupplierComparator();
    $supplierCompAlt = new AlternativeComparator();

    $finalOrdersListStd = [];
    $finalOrdersListAlt = [];

    $processCascade = function ($ord, $comparator) {
        $results = [];
        $needed = (int)$ord['qty_buy'];
        $excludeIds = [];
        $loopLimit = 20;

        while ($needed > 0 && $loopLimit > 0) {
            $loopLimit--;

            $res = $comparator->findSuppliersForQty($ord['ean'], $ord['name'], $needed, $ord['sku'], $excludeIds);

            if ($res['status_code'] == 'NOT_FOUND' || $res['status_code'] == 'NO_STOCK' || $res['found_qty'] <= 0) {
                $row = $ord;
                $this->mapResultToRow($row, $res, $needed);

                if ($res['status_code'] === 'NOT_FOUND') {
                    $row['status'] = 'NOT_FOUND';
                } else {
                    $row['status'] = 'NO_STOCK';
                }

                $row['missing_qty'] = $needed;
                $results[] = $row;
                $needed = 0;
                break;
            }

            $take = $res['found_qty'];
            $idSup = $res['id_supplier'];

            $row = $ord;
            $row['qty_buy'] = $take;
            $this->mapResultToRow($row, $res, $take);
            $row['missing_qty'] = 0;

            $results[] = $row;
            $needed -= $take;

            if ($idSup > 0) {
                $excludeIds[] = $idSup;
            } else {
                if ($needed > 0) {
                    $rowMissing = $ord;
                    $res['status_code'] = 'NO_STOCK';
                    $this->mapResultToRow($rowMissing, $res, $needed);
                    $rowMissing['status'] = 'NO_STOCK';
                    $rowMissing['missing_qty'] = $needed;
                    $results[] = $rowMissing;
                }
                break;
            }
        }

        return $results;
    };

    foreach ($ordersListTmp as $ord) {
        $stdRows = $processCascade($ord, $supplierCompStd);
        foreach ($stdRows as $r) {
            $finalOrdersListStd[] = $r;
        }

        $altRows = $processCascade($ord, $supplierCompAlt);
        foreach ($altRows as $r) {
            $finalOrdersListAlt[] = $r;
        }
    }

    $this->ordersRepo->addItemsToSession($finalOrdersListStd);
    $this->altRepo->addItemsToSession($finalOrdersListAlt);

    $fileName = isset($_FILES['order_file']['name']) ? $_FILES['order_file']['name'] : 'plik.csv';
    $this->pickingRepo->logProcessedFile($fileHash, $fileName);

    return "Analiza zakończona (Raport + Kompletacja + Zakupy).";
}
    
    private function mapResultToRow(&$row, $res, $qtyOverride) {
        $row['supplier'] = isset($res['supplier_raw']) ? $res['supplier_raw'] : $res['supplier_name'];
        $row['price'] = $res['price'];
        $row['savings'] = isset($res['savings']) ? $res['savings'] : 0;
        $row['tax_rate'] = $res['tax_rate'];
        $row['status'] = isset($res['status_code']) ? $res['status_code'] : 'OK';
        $row['was_switched'] = $res['was_switched'];
        $row['missing_qty'] = isset($res['missing_qty']) ? $res['missing_qty'] : 0;
        $row['is_logistic_switch'] = isset($res['is_logistic_switch']) ? $res['is_logistic_switch'] : false;
        $row['qty_buy'] = $qtyOverride;
    }
    
    // --- PRZYWRÓCONA PEŁNA FUNKCJA (NIE STUB) ---
    public function getExtraItemsForPicking()
    {
        $extraItems = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "modulzamowien_extra_items` WHERE is_mag = 1");
        $resultList = [];
        if (!$extraItems) return [];
        $stockManager = new LocalStockManager();
        foreach ($extraItems as $item) {
            $neededQty = (int)$item['qty'];
            $ean = $item['ean'];
            $sku = $item['sku'];
            $stockInfo = $stockManager->checkStock($ean, $sku, $neededQty);
            if (!empty($stockInfo['batches'])) {
                foreach ($stockInfo['batches'] as $batch) {
                    if ($neededQty <= 0) break;
                    $batchQty = (int)$batch['quantity'];
                    $toTake = min($neededQty, $batchQty);
                    $resultList[] = [
                        'ean' => $ean, 'name' => $item['name'],
                        'location' => $batch['regal'] . ' / ' . $batch['polka'],
                        'expiry' => isset($batch['expiry']) ? $batch['expiry'] : '',
                        'qty_to_pick' => $toTake, 'qty_in_loc' => $batchQty,
                        'qty_after' => $batchQty - $toTake
                    ];
                    $neededQty -= $toTake;
                }
            } else {
                $resultList[] = [
                    'ean' => $ean, 'name' => $item['name'], 'location' => 'BRAK WMS', 'expiry' => '',
                    'qty_to_pick' => $neededQty, 'qty_in_loc' => 0, 'qty_after' => -1 * $neededQty
                ];
            }
        }
        return $resultList;
    }
}