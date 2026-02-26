<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Wms/PickingManager.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Stock/SurplusManager.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/PickingSessionRepository.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Repositories/OrdersSessionRepository.php';

class OrderAjaxHandler
{
    private $sessionManager;

    public function __construct($sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }
    
    public function handleClearCollected()
    {
        $repo = new PickingSessionRepository();
        if ($repo->deleteCollectedItems()) {
            return ['success' => true];
        }
        return ['error' => true, 'msg' => 'Błąd bazy danych przy usuwaniu.'];
    }

    public function handleDecreaseStock()
    {
        $sku = Tools::getValue('ean'); // Może być EAN lub SKU
        $qtyInput = (int)Tools::getValue('qty_picked'); 
        $actionType = Tools::getValue('action_type');
        $wms = new PickingManager();
        $repo = new PickingSessionRepository(); // Inicjalizacja tutaj dla wygody
        
        // --- 1. POBIERANIE ALTERNATYW (Bez zmian) ---
        if ($actionType == 'get_alternatives') {
            $ean = $this->extractEanFromSku($sku);
            if (empty($ean)) return ['success' => false, 'msg' => 'Brak EAN w SKU'];

            $locations = [];
            $mainSql = "SELECT sku, regal, polka, quantity_wms as qty FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE ean = '" . pSQL($ean) . "' AND quantity_wms > 0";
            $mainRows = Db::getInstance()->executeS($mainSql);
            if ($mainRows) { foreach ($mainRows as $r) { $locations[] = ['sku' => $r['sku'], 'regal' => $r['regal'], 'polka' => $r['polka'], 'quantity' => (int)$r['qty']]; }}

            $dupeSql = "SELECT regal, polka, quantity as qty, expiry_date FROM `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` WHERE ean = '" . pSQL($ean) . "' AND quantity > 0";
            $dupeRows = Db::getInstance()->executeS($dupeSql);
            if ($dupeRows) { foreach ($dupeRows as $d) { $datePart = ($d['expiry_date'] && $d['expiry_date'] != '0000-00-00') ? date('dmY', strtotime($d['expiry_date'])) : '000000'; $generatedSku = 'A_MAG_' . $ean . '_' . $datePart . '_(' . $d['regal'] . '_' . $d['polka'] . ')'; $locations[] = ['sku' => $generatedSku, 'regal' => $d['regal'], 'polka' => $d['polka'], 'quantity' => (int)$d['qty']]; }}

            return ['success' => true, 'data' => $locations];
        }

        // --- 2. ZAMIANA LOKALIZACJI (SMART CORRECTION) ---
        if ($actionType == 'swap_location') {
            $newRegal = Tools::getValue('new_regal');
            $newPolka = Tools::getValue('new_polka');
            $newSku = Tools::getValue('new_sku'); 
            
            // PARAMETRY MAGII
            $doCorrection = (int)Tools::getValue('do_correction');
            $partialQty = (int)Tools::getValue('partial_qty');

            if (empty($newSku)) return ['error' => true, 'msg' => 'Brak SKU docelowego.'];

            // A. JEŚLI ZATWIERDZONO KOREKTĘ (WYZEROWANIE STAREGO)
            if ($doCorrection === 1) {
                // Zerujemy dokładnie ten SKU, z którego wychodzimy ($sku)
                $this->zeroOutWmsStock($sku);
                
                // Zmniejszamy ilość do zebrania w sesji
                if ($partialQty >= 0) {
                    $repo->decreaseQtyToPick($sku, $partialQty);
                }
            }

            // B. ZAPISUJEMY NOWĄ LOKALIZACJĘ (W kolumnach ALT)
            if ($repo->setAlternativeLocation($sku, $newRegal, $newPolka, $newSku)) {
                return ['success' => true, 'new_sku' => $newSku];
            } else {
                return ['error' => true, 'msg' => 'Błąd aktualizacji bazy.'];
            }
        }
        
        // --- 3. RESETOWANIE WYBORU ---
        if ($actionType == 'reset_swap') {
            if ($repo->resetAlternative($sku)) {
                return ['success' => true];
            }
            return ['error' => true];
        }

        // --- KOREKTA STANÓW (FIX: Trwałe usuwanie z bazy) ---
        if ($actionType == 'correct_stock_batch') {
            $items = Tools::getValue('items');
            
            if (is_array($items)) {
                foreach ($items as $item) {
                    $s = $item['ean'];
                    // Tutaj sprawdzamy czy użyć alternatywy dla korekty
                    $activeSku = $s;
                    $altSku = $repo->getAlternativeSku($s);
                    if ($altSku) $activeSku = $altSku; 

                    $qToRemove = (int)$item['qty_to_remove'];

                    // 1. Fizyczna korekta w WMS/Presta (zeruje stany)
                    $wms->correctStock($activeSku, $qToRemove);
                    
                    // 2. TRWAŁE USUNIĘCIE Z LISTY SESJI
                    $repo->deleteItem($s);
                }
            }
            return ['success' => true];
        }

        $qtyOld = $this->sessionManager->getSessionPickedQty($sku, 'user_picked_qty');
        $diff = $qtyInput - $qtyOld;

        if ($actionType == 'update_qty' || $actionType == 'confirm_pick') {
            if ($diff != 0) {
                // --- KLUCZOWY MOMENT: Wybieramy SKU do zdjęcia ze stanu ---
                $targetSku = $sku;
                $altSku = $repo->getAlternativeSku($sku);
                if (!empty($altSku)) {
                    $targetSku = $altSku; // Używamy alternatywnego SKU
                }

                if ($diff > 0) { 
                    $res = $wms->confirmPick($targetSku, $diff); 
                    if (!$res['success']) return $res; 
                } else { 
                    $res = $wms->revertPick($targetSku, abs($diff)); 
                    if (!$res['success']) return $res; 
                }

                $this->sessionManager->updateOrderSessionQty($sku, -1 * $diff);
            }
            
            $isDone = ($actionType == 'confirm_pick');
            $this->sessionManager->updateReportSession($sku, $qtyInput, $isDone, 'user_picked_qty', true);
            return ['success' => true];
        } 
        
        if ($actionType == 'revert_pick') {
            if ($qtyOld > 0) {
                // Tutaj też sprawdzamy alternatywę
                $targetSku = $sku;
                $altSku = $repo->getAlternativeSku($sku);
                if (!empty($altSku)) $targetSku = $altSku;

                $wms->revertPick($targetSku, $qtyOld);
                $this->sessionManager->updateOrderSessionQty($sku, $qtyOld);
            }
            $this->sessionManager->updateReportSession($sku, 0, false, 'user_picked_qty', true);
            return ['success' => true];
        }
        
        if ($actionType == 'confirm_all') {
            $items = Tools::getValue('items');
            if (is_array($items)) { 
                foreach ($items as $item) { 
                    $s = $item['ean']; 
                    $q = (int)$item['qty']; 
                    $old = $this->sessionManager->getSessionPickedQty($s, 'user_picked_qty'); 
                    $d = $q - $old; 
                    if ($d > 0) {
                        // Check alt
                        $targetSku = $s;
                        $altSku = $repo->getAlternativeSku($s);
                        if (!empty($altSku)) $targetSku = $altSku;

                        $wms->confirmPick($targetSku, $d);
                        $this->sessionManager->updateOrderSessionQty($s, -1 * $d);
                    }
                    $this->sessionManager->updateReportSession($s, $q, true, 'user_picked_qty', false); 
                } 
            }
            return ['success' => true];
        }
        
        if ($actionType == 'revert_all') {
            $items = Tools::getValue('items');
            if (is_array($items)) { 
                foreach ($items as $item) { 
                    $s = $item['ean']; 
                    $old = $this->sessionManager->getSessionPickedQty($s, 'user_picked_qty'); 
                    if ($old > 0) {
                        $targetSku = $s;
                        $altSku = $repo->getAlternativeSku($s);
                        if (!empty($altSku)) $targetSku = $altSku;

                        $wms->revertPick($targetSku, $old); 
                        $this->sessionManager->updateOrderSessionQty($s, $old);
                    }
                    $this->sessionManager->updateReportSession($s, 0, false, 'user_picked_qty', false); 
                } 
            }
            return ['success' => true];
        }
        
        return ['error' => true];
    }

    public function handleUpdateTablePick()
    {
        $sm = new SurplusManager();
        $sku = Tools::getValue('ean'); 
        $qtyInput = (int)Tools::getValue('qty_picked'); 
        $actionType = Tools::getValue('action_type');

        if ($actionType == 'update_qty' || $actionType == 'confirm_pick') {
            $sm->updateQueueProgress($sku, $qtyInput); 
            return ['success' => true];
        }
        if ($actionType == 'revert_pick') {
            $sm->updateQueueProgress($sku, 0); 
            return ['success' => true];
        }
        return ['error' => true];
    }

    private function extractEanFromSku($sku) {
        if (preg_match('/_(\d{12,14})_/', $sku, $matches)) {
            return $matches[1];
        }
        if (preg_match('/^\d{12,14}$/', $sku)) {
            return $sku;
        }
        return $sku; 
    }

    // --- FUNKCJA ZERUJĄCA PO SKU (Prosta i skuteczna) ---
    private function zeroOutWmsStock($sku)
    {
        $db = Db::getInstance();
        $skuSafe = pSQL($sku);
        
        // 1. Tabela Główna - tu SKU jest kluczem
        $db->execute("UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_product_details` 
                      SET quantity_wms = 0 
                      WHERE sku = '$skuSafe'");
        
        // 2. Tabela Duplikatów - parsujemy string SKU
        if (preg_match('/A_MAG_(\d+)_.*_\((.*)_(.*)\)/', $sku, $m)) {
            $ean = $m[1];
            $regal = $m[2];
            $polka = $m[3];
            $db->execute("UPDATE `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` 
                          SET quantity = 0 
                          WHERE ean = '$ean' AND regal = '$regal' AND polka = '$polka'");
        }
    }
}
?>