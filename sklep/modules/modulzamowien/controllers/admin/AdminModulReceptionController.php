<?php
/**
 * Kontroler: AdminModulReceptionController
 * Lokalizacja: /modules/modulzamowien/controllers/admin/AdminModulReceptionController.php
 * Rola: Obsługa Skanera Przyjęć (Modal) -> WMS (Wyprzedaż PRO)
 * Wersja: FINALNA (Przeniesiona logika z ZamowienController)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminModulReceptionController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        // 1. Obsługa przycisku "PRZYJMIJ NA STAN" (Modal)
        if (Tools::getValue('action') == 'process_reception') {
            $this->ajaxProcessReception();
        }
        
        // 2. Obsługa usuwania pojedynczej pozycji (Kosz)
        if (Tools::getValue('action') == 'delete_surplus_item') {
            $this->ajaxProcessDeleteSurplusItem();
        }
        
        // 3. Pobieranie danych do modala (Check - Skanowanie wstępne)
        if (Tools::getValue('action') == 'checkSurplus') {
            $this->ajaxProcessCheckSurplus();
        }
    }

    /**
     * GŁÓWNA FUNKCJA INTEGRACJI (PRZYJĘCIE)
     * Łączy Moduł Zamówień (Skaner) z Modułem Wyprzedaż PRO (Logika CSV)
     */
    public function ajaxProcessReception()
    {
        // Czyścimy bufor, aby JSON był poprawny
        @ob_clean();
        header('Content-Type: application/json');

        try {
            // 1. Pobieramy dane z formularza (Modal)
            $ean = trim(Tools::getValue('ean'));
            $qty = (int)Tools::getValue('qty');
            $regal = trim(Tools::getValue('regal'));
            $polka = trim(Tools::getValue('polka'));
            $expiry = trim(Tools::getValue('expiry'));
            $type = Tools::getValue('location_type'); // 'regal' lub 'kosz'

            // Walidacja
            if (empty($ean) || $qty <= 0) {
                throw new Exception('Błąd: Brak EAN lub ilość wynosi 0.');
            }

            if ($type === 'kosz') {
                $regal = 'KOSZ'; 
                if (empty($polka)) throw new Exception('Podaj numer kosza.');
            } else {
                if (empty($regal) || empty($polka)) throw new Exception('Podaj Regał i Półkę.');
            }

            // 2. Sprawdzamy czy moduł Wyprzedaż PRO istnieje
            $servicePath = _PS_MODULE_DIR_ . 'wyprzedazpro/classes/Api/ReceptionService.php';
            if (!file_exists($servicePath)) {
                throw new Exception('BRAK MODUŁU: Nie zainstalowano modułu Wyprzedaż PRO.');
            }

            require_once $servicePath;
            if (!class_exists('ReceptionService')) {
                throw new Exception('BŁĄD: Klasa ReceptionService nie została załadowana.');
            }

            // Inicjalizacja serwisu WMS
            $service = new ReceptionService();
            $id_shop = (int)$this->context->shop->id;
            
            // ----------------------------------------------------------------------
            // KROK A: ZAPIS DO TABELI BUFOROWEJ (STAGING)
            // ----------------------------------------------------------------------
            $insertResult = $service->insertToStaging($ean, $qty, $regal, $polka, $expiry);

            if (!$insertResult['success']) {
                throw new Exception('Błąd zapisu DB: ' . $insertResult['msg']);
            }

            // ----------------------------------------------------------------------
            // KROK B: URUCHOMIENIE LOGIKI PRZETWARZANIA (PROCESS)
            // ----------------------------------------------------------------------
            $processResult = $service->processScannerQueue($id_shop);
            
            if ($processResult['success']) {
                
                // ------------------------------------------------------------------
                // KROK C: CZYSZCZENIE "STOŁU" W MODULE ZAMÓWIEŃ
                // ------------------------------------------------------------------
                require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Stock/SurplusManager.php';
                $surplusManager = new SurplusManager();
                
                // WAŻNE: false na końcu oznacza "NIE dodawaj do kolejki Pick Stół (Tab 4)"
                // Usuwamy z Wirtualnego Magazynu, bo towar trafił na regał fizyczny.
                if (method_exists($surplusManager, 'consumeSurplus')) {
                    $surplusManager->consumeSurplus($ean, $qty, 'Skaner Przyjęć (WMS)', false);
                }

                die(json_encode([
                    'success' => true, 
                    'msg' => 'OK. ' . $processResult['msg']
                ]));
            } else {
                throw new Exception('Błąd przetwarzania WMS: ' . $processResult['msg']);
            }

        } catch (\Throwable $e) {
            die(json_encode(['success' => false, 'msg' => $e->getMessage()]));
        }
    }
    
    /**
     * SPRAWDZANIE PRODUKTU (Podczas wpisywania EAN w wyszukiwarkę)
     */
    public function ajaxProcessCheckSurplus()
    {
        header('Content-Type: application/json');
        $ean = trim(Tools::getValue('ean'));
        
        require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Stock/SurplusManager.php';
        $sm = new SurplusManager();
        
        $found = null;
        if (method_exists($sm, 'getProductByEan')) {
            $found = $sm->getProductByEan($ean);
        } else {
            $list = $sm->getSurplusList();
            foreach ($list as $item) {
                if ($item['ean'] == $ean) {
                    $found = $item;
                    break;
                }
            }
        }
        
        if ($found) {
            die(json_encode(['success' => true, 'product' => $found]));
        } else {
            die(json_encode(['success' => false, 'msg' => 'Brak produktu na Wirtualnym Magazynie.']));
        }
    }

    /**
     * USUWANIE POZYCJI ZE STOŁU (Przycisk Kosz)
     */
    public function ajaxProcessDeleteSurplusItem()
    {
        header('Content-Type: application/json');
        
        $id_surplus = (int)Tools::getValue('id_surplus');
        $ean = trim(Tools::getValue('ean'));
        $name = trim(Tools::getValue('name')); // Bez html_entity_decode, raw input
        
        $success = false;
        
        // Priorytet: ID -> EAN -> Nazwa
        if ($id_surplus > 0) {
            // FIX: Używamy poprawnej nazwy tabeli _v2
            if (Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "modulzamowien_surplus_v2` SET qty = 0 WHERE id_surplus = " . $id_surplus)) {
                $success = true;
            }
        }
        else if (!empty($ean)) {
            if (Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "modulzamowien_surplus_v2` SET qty = 0 WHERE ean = '" . pSQL($ean) . "'")) {
                $success = true;
            }
        } 
        else if (!empty($name)) {
             $sql = "UPDATE `" . _DB_PREFIX_ . "modulzamowien_surplus_v2` 
                    SET qty = 0
                    WHERE `name` LIKE '%" . pSQL($name) . "%' 
                    AND (`ean` IS NULL OR `ean` = '' OR `ean` = '0')";
            if (Db::getInstance()->execute($sql)) {
                $success = true;
            }
        }

        if ($success) {
            die(json_encode(['success' => true]));
        } else {
            die(json_encode(['success' => false, 'msg' => 'Nie udało się wyzerować wpisu.']));
        }
    }
}