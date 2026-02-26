<?php
/**
 * Kontroler Pomocniczy - Obsługa Nadwyżek (Multiupload + MD5 + Multi-History Subtract)
 * POPRAWKA: Produkty z zakładki EXTRA nie znikają, ale są oznaczane w nazwie jako [EXTRA].
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminModulSurplusController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        // 1. OBSŁUGA PLIKÓW HURTOWNI (MULTIUPLOAD)
        if (Tools::isSubmit('submitUploadConfirmation')) {
            
            // ZMIANA: Pobieramy tablicę ID (dzięki name="compare_history_id[]" w TPL)
            $historyIds = Tools::getValue('compare_history_id');
            
            // Zabezpieczenie: jeśli nic nie zaznaczono, to pusta tablica
            if (!is_array($historyIds)) {
                $historyIds = [];
            }
            
            // Sprawdzamy czy przesłano pliki
            if (isset($_FILES['confirmation_file']) && !empty($_FILES['confirmation_file']['name'][0])) {
                
                // Uruchamiamy logikę zbiorczą z TABLICĄ historii
                $this->processMultiFilesLogic($historyIds);

            } else {
                $this->errors[] = "Nie wybrano żadnych plików CSV.";
            }
        }
        
        // 2. CZYSZCZENIE STOŁU I PRZENOSZENIE DO WMS (INTEGRACJA)
        if (Tools::isSubmit('submitClearSurplus')) {
             require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Stock/SurplusManager.php';
             $sm = new SurplusManager();
             
             // A. Przeniesienie danych z tabeli Surplus do tabeli Staging (Wyprzedaż PRO)
             $transferredCount = $sm->transferToWmsStaging(); 

             if ($transferredCount > 0) {
                 // B. URUCHOMIENIE LOGIKI WYPRZEDAŻY
                 $servicePath = _PS_MODULE_DIR_ . 'wyprzedazpro/classes/Api/ReceptionService.php';
                 
                 if (file_exists($servicePath)) {
                     require_once $servicePath;
                     if (class_exists('ReceptionService')) {
                         $wmsService = new ReceptionService();
                         $id_shop = (int)$this->context->shop->id;
                         
                         // Przetwarza wiersze 'SCANNER_WAITING' na produkty
                         $result = $wmsService->processScannerQueue($id_shop);
                         
                         if ($result['success']) {
                             $this->confirmations[] = "WMS: " . $result['msg'];
                         } else {
                             $this->errors[] = "Błąd WMS: " . $result['msg'];
                         }
                     }
                 } else {
                     $this->errors[] = "Nie znaleziono modułu Wyprzedaż PRO.";
                 }
             }

             // C. Wyczyszczenie stołu w module zamówień
             $sm->moveToWarehouseAndClear();
             $this->confirmations[] = "Stół wyczyszczony. Nadwyżki przeniesione do WMS.";
        }

        // Przekierowanie
        if (empty($this->errors)) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModulZamowien') . '&conf=4#surplus');
        } else {
            $this->context->cookie->mz_errors = json_encode($this->errors);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModulZamowien') . '#surplus');
        }
    }

    // ZMIANA: Argument teraz przyjmuje tablicę $historyIds
    private function processMultiFilesLogic($historyIds)
    {
        require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Input/CsvImporter.php';
        require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Stock/SurplusManager.php';
        
        $importer = new CsvImporter();
        $surplusManager = new SurplusManager();
        
        $files = $_FILES['confirmation_file'];
        $count = count($files['name']);
        
        $grandTotalItems = []; 
        $processedCount = 0;
        $skippedCount = 0;
        
        // KROK 1: Agregacja plików (Sumujemy wszystkie wgrane CSV z dostawą)
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] == 0) {
                $path = $files['tmp_name'][$i];
                $name = $files['name'][$i];
                
                // MD5 Check (Pomijanie duplikatów plików)
                $hash = md5_file($path);
                if ($surplusManager->isFileProcessed($hash)) {
                    $skippedCount++;
                    continue;
                }
                
                // Parsowanie
                try {
                    $items = $importer->processFile($path);
                    foreach ($items as $item) {
                        $ean = trim($item['ean']);
                        $qty = (int)$item['quantity'];
                        $prodName = isset($item['csv_name']) ? $item['csv_name'] : 'Produkt';
                        
                        // Używamy EAN jako klucza, lub Nazwy jeśli brak EAN
                        $key = !empty($ean) ? $ean : 'NAME_' . md5($prodName);

                        if (!isset($grandTotalItems[$key])) {
                            $grandTotalItems[$key] = ['qty' => 0, 'name' => $prodName, 'ean' => $ean];
                        }
                        $grandTotalItems[$key]['qty'] += $qty;
                        
                        if ($grandTotalItems[$key]['name'] == 'Produkt' && $prodName != 'Produkt') {
                            $grandTotalItems[$key]['name'] = $prodName;
                        }
                    }
                    
                    // Oznaczamy plik jako przetworzony
                    $surplusManager->logProcessedFile($hash, $name);
                    $processedCount++;
                    
                } catch (Exception $e) {
                    $this->errors[] = "Błąd w pliku $name: " . $e->getMessage();
                }
            }
        }
        
        if ($processedCount == 0 && $skippedCount > 0) {
            $this->errors[] = "Wszystkie wybrane pliki były już wcześniej wgrane.";
            return;
        }
        if ($processedCount == 0) return;

        // KROK 2: Pobranie Historii (Z wielu zamówień naraz)
        $historyItems = []; // Mapa: Klucz -> Ilość już zamówiona
        
        if (!empty($historyIds)) {
            // Konwersja na bezpieczny ciąg ID do SQL (np. "15,16,20")
            $idsSafe = implode(',', array_map('intval', $historyIds));
            
            $sql = "SELECT order_data FROM `" . _DB_PREFIX_ . "modulzamowien_history` WHERE id_history IN ($idsSafe)";
            $results = Db::getInstance()->executeS($sql);
            
            if ($results) {
                foreach ($results as $row) {
                    $decoded = json_decode($row['order_data'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $hItem) {
                            $hEan = isset($hItem['ean']) ? trim($hItem['ean']) : '';
                            $hName = isset($hItem['name']) ? trim($hItem['name']) : '';
                            
                            // Klucz musi być taki sam jak przy agregacji dostawy
                            $hKey = !empty($hEan) ? $hEan : 'NAME_' . md5($hName);
                            
                            // Próba wyciągnięcia ilości z różnych możliwych pól w historii
                            $hQty = 0;
                            if (isset($hItem['qty_buy'])) $hQty = (int)$hItem['qty_buy'];
                            elseif (isset($hItem['qty_total'])) $hQty = (int)$hItem['qty_total'];
                            elseif (isset($hItem['qty'])) $hQty = (int)$hItem['qty'];
                            
                            if (!isset($historyItems[$hKey])) $historyItems[$hKey] = 0;
                            $historyItems[$hKey] += $hQty;
                        }
                    }
                }
            }
        }

        // --- PRZYGOTOWANIE LISTY EXTRA (DO OZNACZANIA) ---
        // POPRAWKA: lista EXTRA powinna pochodzić z bazy (modulzamowien_extra_items),
        // a nie tylko z sesji (która często nie jest ustawiona).
        $extraEans = [];

        // 1) Pobierz z DB
        $dbExtra = Db::getInstance()->executeS("SELECT DISTINCT ean FROM `" . _DB_PREFIX_ . "modulzamowien_extra_items` WHERE ean IS NOT NULL AND ean != ''");
        if ($dbExtra) {
            foreach ($dbExtra as $row) {
                if (!empty($row['ean'])) {
                    $extraEans[] = trim($row['ean']);
                }
            }
        }

        // 2) (opcjonalnie) domieszaj z sesji – dla zgodności wstecz
        if (session_status() == PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['mz_extra_items']) && is_array($_SESSION['mz_extra_items'])) {
            foreach ($_SESSION['mz_extra_items'] as $ex) {
                if (!empty($ex['ean'])) {
                    $extraEans[] = trim($ex['ean']);
                }
            }
        }

        $extraEans = array_values(array_unique(array_filter($extraEans)));

        // KROK 3: Wyliczenie i Zapis Nadwyżek (NETOWANIE)
        // Wzór: Dostawa - SumaHistorii = WirtualnyMagazyn
        
        $addedPositions = 0;
        
        foreach ($grandTotalItems as $key => $data) {
            $qtyTotal = $data['qty']; // Tyle przyszło w dostawie
            $qtyHistory = isset($historyItems[$key]) ? $historyItems[$key] : 0; // Tyle było potrzebne na stare zamówienia
            
            // Jeśli zaznaczono historię, odejmujemy. Jeśli nie, to cała dostawa jest nadwyżką.
            $surplus = (!empty($historyIds)) ? ($qtyTotal - $qtyHistory) : $qtyTotal;
            
            if ($surplus > 0) {
                
                // SPRAWDZAMY CZY TO PRODUKT EXTRA
                $finalName = $data['name'];
                if (in_array($data['ean'], $extraEans)) {
                    // Jeśli EAN jest na liście Extra, dopisujemy to do nazwy
                    // Dzięki temu wiesz, że ten produkt jest "zarezerwowany" na braki
                    $finalName = '[EXTRA] ' . $finalName;
                }

                // Dodajemy tylko dodatnią różnicę do Wirtualnego Magazynu
                $surplusManager->addSurplus($data['ean'], $finalName, $surplus);
                $addedPositions++;
            }
        }
        
        $msg = "Przetworzono łącznie $processedCount plików CSV.";
        if ($skippedCount > 0) $msg .= " (Pominięto $skippedCount duplikatów).";
        
        if (!empty($historyIds)) {
            $msg .= " Odjęto towary z " . count($historyIds) . " zamówień historycznych.";
        }
        
        $msg .= " Zaktualizowano stan (nadwyżkę) dla $addedPositions pozycji.";
        $this->context->cookie->mz_conf_msg = $msg;
    }
}