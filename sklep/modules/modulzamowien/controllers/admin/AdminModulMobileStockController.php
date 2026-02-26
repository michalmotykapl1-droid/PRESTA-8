<?php
/**
 * Kontroler: AdminModulMobileStockController
 * Rola: Mobilne ZATOWAROWANIE + FIX: BEZPOŚREDNI SQL (Naprawa wyświetlania ilości)
 * Wersja: 4.0 (Direct SQL Source + Unlimited Qty + WyprzedażPro)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminModulMobileStockController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();
        $mobileUrl = $this->context->link->getAdminLink('AdminModulMobileStock') . '&ajax=1';
        
        $this->context->smarty->assign([
            'ajax_mobile_url' => $mobileUrl,
            'shop_name' => Configuration::get('PS_SHOP_NAME')
        ]);
        
        $this->setTemplate('mobile_stock.tpl');
    }

    public function postProcess()
    {
        if (Tools::getValue('action') == 'getSurplusList') $this->ajaxProcessGetSurplusList();
        if (Tools::getValue('action') == 'getProductData') $this->ajaxProcessGetProductData();
        if (Tools::getValue('action') == 'receive_stock') $this->ajaxProcessReceiveStock();
    }

    // --- 1. POBIERANIE LISTY (NAPRAWIONE: BEZPOŚREDNI SQL) ---
    public function ajaxProcessGetSurplusList()
    {
        // Używamy bezpośredniego SQL, aby uniknąć pomyłki z inną tabelą (np. Queue)
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "modulzamowien_surplus_v2` WHERE qty > 0 ORDER BY id_surplus DESC";
        $list = Db::getInstance()->executeS($sql);

        die(json_encode(['success' => true, 'data' => $list ? $list : []]));
    }

    // --- 2. POBIERANIE DANYCH PRODUKTU (NAPRAWIONE: BEZPOŚREDNI SQL) ---
    public function ajaxProcessGetProductData()
    {
        $ean = trim(Tools::getValue('ean'));
        if (empty($ean)) die(json_encode(['success' => false, 'msg' => 'Brak EAN']));

        // A. Szukamy w Wirtualnym Magazynie (Surplus) bezpośrednio w bazie
        $sqlSurplus = "SELECT name, qty FROM `" . _DB_PREFIX_ . "modulzamowien_surplus_v2` WHERE ean = '".pSQL($ean)."'";
        $surplusRow = Db::getInstance()->getRow($sqlSurplus);

        $productName = '';
        $surplusQty = 0;
        $isAdHoc = false; 

        if ($surplusRow) {
            // JEST NA LIŚCIE
            $productName = $surplusRow['name'];
            $surplusQty = (int)$surplusRow['qty']; // Tu na pewno wejdzie poprawna ilość
        } else {
            // B. NIE MA w Surplus -> Szukamy w bazie PrestaShop (AD-HOC)
            $id_product = (int)Db::getInstance()->getValue("SELECT id_product FROM " . _DB_PREFIX_ . "product WHERE ean13 = '" . pSQL($ean) . "'");
            if (!$id_product) {
                 $id_product = (int)Db::getInstance()->getValue("SELECT id_product FROM " . _DB_PREFIX_ . "product_attribute WHERE ean13 = '" . pSQL($ean) . "'");
            }

            if ($id_product) {
                $product = new Product($id_product, false, $this->context->language->id);
                $productName = $product->name;
                $surplusQty = 0; 
                $isAdHoc = true; 
            } else {
                die(json_encode(['success' => false, 'msg' => 'Nie znaleziono produktu o takim EAN w bazie sklepu.']));
            }
        }
        
        // Pobieranie obecnej lokalizacji (opcjonalne, ale przydatne)
        $location = '';
        if (isset($id_product) && $id_product) {
             $location = Db::getInstance()->getValue("SELECT location FROM " . _DB_PREFIX_ . "stock_available WHERE id_product = " . (int)$id_product . " AND id_product_attribute = 0");
        }

        die(json_encode([
            'success' => true,
            'name' => $productName,
            'ean' => $ean,
            'qty_surplus' => $surplusQty, // To pole jest kluczowe dla JS
            'current_location' => $location ? $location : '',
            'is_adhoc' => $isAdHoc 
        ]));
    }

    // --- 3. ZAPISYWANIE (ZACHOWANA POPRAWKA: BRAK LIMITU + INTEGRACJA) ---
    public function ajaxProcessReceiveStock()
    {
        $ean = trim(Tools::getValue('ean'));
        $qty = (int)Tools::getValue('qty');
        $date = trim(Tools::getValue('expiration_date')); 
        $type = trim(Tools::getValue('location_type')); 

        $regal = '';
        $polka = '';

        if ($type == 'kosz') {
            $regal = 'KOSZ';
            $koszVal = trim(Tools::getValue('kosz')); 
            $polka = str_replace('KOSZ ', '', $koszVal); 
        } else {
            $regal = trim(Tools::getValue('regal'));
            $polka = trim(Tools::getValue('polka'));
        }

        if ($qty <= 0) $qty = 1;

        // 1. Sprawdzenie czy jest w Surplus (tylko informacyjnie)
        $existsInSurplus = Db::getInstance()->getValue("SELECT id_surplus FROM `"._DB_PREFIX_."modulzamowien_surplus_v2` WHERE ean = '".pSQL($ean)."' AND qty > 0");

        // UWAGA: Usunięto blokadę "if ($qty > $surplusQty) die()". Pozwalamy przyjąć więcej.

        // 2. INTEGRACJA: WYPRZEDAŻ PRO (ReceptionService)
        $servicePath = _PS_MODULE_DIR_ . 'wyprzedazpro/classes/Api/ReceptionService.php';
        
        if (file_exists($servicePath)) {
            require_once $servicePath;
            if (class_exists('ReceptionService')) {
                $service = new ReceptionService();
                $insertResult = $service->insertToStaging($ean, $qty, $regal, $polka, $date);
                
                if (isset($insertResult['success']) && $insertResult['success']) {
                    $service->processScannerQueue($this->context->shop->id);
                }
            }
        }

        // 3. AKTUALIZACJA PRESTA (Lokalizacja + Stan)
        $id_product = (int)Db::getInstance()->getValue("SELECT id_product FROM " . _DB_PREFIX_ . "product WHERE ean13 = '" . pSQL($ean) . "'");
        if (!$id_product) {
            $row = Db::getInstance()->getRow("SELECT id_product, id_product_attribute FROM " . _DB_PREFIX_ . "product_attribute WHERE ean13 = '" . pSQL($ean) . "'");
            if ($row) $id_product = (int)$row['id_product'];
        }

        if ($id_product) {
            $fullLocation = ($type == 'kosz') ? ('KOSZ ' . $polka) : ($regal . ' ' . $polka);
            Db::getInstance()->update('stock_available', ['location' => pSQL($fullLocation)], "id_product = $id_product AND id_product_attribute = 0");
            StockAvailable::updateQuantity($id_product, 0, $qty);
        }

        // 4. ZDJĘCIE Z WIRTUALNEGO (BEZPIECZNE ODEJMOWANIE)
        $msg = 'Przyjęto towar!';
        
        if ($existsInSurplus) {
            // Używamy GREATEST(0, qty - X) aby nie wejść na minus w bazie danych
            // Nawet jak przyjmiesz 100, a było 5, to zrobi się 0.
            $sql = "UPDATE `" . _DB_PREFIX_ . "modulzamowien_surplus_v2` 
                    SET qty = GREATEST(0, qty - " . (int)$qty . "), 
                        date_upd = NOW() 
                    WHERE ean = '" . pSQL($ean) . "'";
            Db::getInstance()->execute($sql);
            
            $msg = 'Przyjęto z zamówienia (Nadwyżka zaktualizowana)!';
        } else {
            $msg = 'Dodano produkt spoza listy (Ad-hoc)!';
        }

        die(json_encode(['success' => true, 'msg' => $msg]));
    }
}