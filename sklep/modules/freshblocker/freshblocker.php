<?php
/**
 * 2025 FreshBlocker Module for PrestaShop
 * Wersja 3.2.0 - FIX: Zapisywanie przeniesione do głównego hooka (Musi działać)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class FreshBlocker extends Module
{
    public function __construct()
    {
        $this->name = 'freshblocker';
        $this->tab = 'front_office_features';
        $this->version = '3.2.0';
        $this->author = 'TwójNick';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('FreshBlocker + Returns Fixer');
        $this->description = $this->l('Kompleksowa obsługa zwrotów: łączenie, edycja, zdjęcia.');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionFrontControllerSetVariables');
    }

    /**
     * GŁÓWNA FUNKCJA - Obsługuje TERAZ zarówno wyświetlanie, jak i ZAPIS.
     */
    public function hookActionFrontControllerSetVariables($params)
    {
        $context = Context::getContext();
        if (!isset($context->controller->php_self)) return;
        $controller = $context->controller->php_self;

        // ============================================================
        // CZĘŚĆ 1: OBSŁUGA ZAPISU I KASOWANIA (PRIORYTET)
        // ============================================================
        if ($controller === 'order-return' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Sprawdzamy czy kliknięto przycisk usuwania lub zapisu
            if (Tools::isSubmit('submitUpdateReturn') || Tools::getValue('delete_product')) {
                
                $id_order_return = (int)Tools::getValue('id_order_return');
                $returnObj = new OrderReturn($id_order_return);

                // Weryfikacja
                if (Validate::isLoadedObject($returnObj) && 
                    $returnObj->id_customer == $context->customer->id && 
                    in_array($returnObj->state, [1, 2])) {

                    $products_to_update = Tools::getValue('return_qty');
                    $products_to_delete = Tools::getValue('delete_product'); // To może być tablica lub string

                    // A. KASOWANIE
                    if ($products_to_delete) {
                        // Jeśli kliknięto button, value jest stringiem, ale zamieniamy na array dla pętli
                        if (!is_array($products_to_delete)) {
                            $products_to_delete = [$products_to_delete];
                        }

                        foreach ($products_to_delete as $id_order_detail) {
                            Db::getInstance()->execute('
                                DELETE FROM ' . _DB_PREFIX_ . 'order_return_detail 
                                WHERE id_order_return = ' . (int)$id_order_return . ' 
                                AND id_order_detail = ' . (int)$id_order_detail
                            );
                        }
                    }

                    // B. AKTUALIZACJA ILOŚCI
                    // Robimy to tylko jeśli NIE było kasowania (żeby nie robić dwóch akcji naraz)
                    // LUB jeśli kliknięto wyraźnie "Zapisz"
                    if (!$products_to_delete && $products_to_update && is_array($products_to_update)) {
                        foreach ($products_to_update as $id_order_detail => $qty) {
                            $qty = (int)$qty;
                            
                            // Pobierz max ilość
                            $max_qty = (int)Db::getInstance()->getValue('
                                SELECT product_quantity FROM ' . _DB_PREFIX_ . 'order_detail 
                                WHERE id_order_detail = ' . (int)$id_order_detail
                            );
                            if ($qty > $max_qty) $qty = $max_qty;

                            if ($qty > 0) {
                                Db::getInstance()->execute('
                                    UPDATE ' . _DB_PREFIX_ . 'order_return_detail 
                                    SET product_quantity = ' . $qty . '
                                    WHERE id_order_return = ' . (int)$id_order_return . ' 
                                    AND id_order_detail = ' . (int)$id_order_detail
                                );
                            } else {
                                // Ilość 0 = Usuń
                                Db::getInstance()->execute('
                                    DELETE FROM ' . _DB_PREFIX_ . 'order_return_detail 
                                    WHERE id_order_return = ' . (int)$id_order_return . ' 
                                    AND id_order_detail = ' . (int)$id_order_detail
                                );
                            }
                        }
                    }

                    // C. FINALIZACJA
                    $count = (int)Db::getInstance()->getValue('
                        SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'order_return_detail 
                        WHERE id_order_return = ' . (int)$id_order_return
                    );

                    if ($count == 0) {
                        $returnObj->delete();
                        Tools::redirect($context->link->getPageLink('order-follow'));
                    } else {
                        $returnObj->date_upd = date('Y-m-d H:i:s');
                        $returnObj->save();
                        // Odśwież stronę, aby pokazać zmiany (PRG)
                        Tools::redirect($context->link->getPageLink('order-return', true, null, ['id_order_return' => $id_order_return]));
                    }
                }
            }
        }

        // ============================================================
        // CZĘŚĆ 2: MERGOWANIE I DANE DLA WIDOKU
        // ============================================================
        
        // 1. Mergowanie (Łączenie zwrotów)
        if (in_array($controller, ['order-follow', 'order-return', 'order-detail'])) {
            $this->processMergeReturns($context->customer->id);
        }

        // 2. Dane Adresowe
        $shop_address = [
            'name' => Configuration::get('PS_SHOP_NAME'),
            'address1' => Configuration::get('PS_SHOP_ADDR1'),
            'address2' => Configuration::get('PS_SHOP_ADDR2'),
            'postcode' => Configuration::get('PS_SHOP_CODE'),
            'city' => Configuration::get('PS_SHOP_CITY'),
            'phone' => Configuration::get('PS_SHOP_PHONE'),
            'email' => Configuration::get('PS_SHOP_EMAIL'),
        ];

        // 3. Widok Historii
        if ($controller === 'order-detail') {
            $id_order = (int)Tools::getValue('id_order');
            $this->assignOrderDetailVariables($id_order, $shop_address, $context);
        }

        // 4. Widok Zwrotu
        if ($controller === 'order-return') {
            $id_order_return = (int)Tools::getValue('id_order_return');
            $this->assignOrderReturnVariables($id_order_return, $shop_address, $context);
        }
    }

    // --- METODY POMOCNICZE (Bez zmian, ale muszą być) ---
    private function processMergeReturns($id_customer) {
        $sql = 'SELECT id_order, COUNT(*) as c FROM ' . _DB_PREFIX_ . 'order_return WHERE id_customer = ' . (int)$id_customer . ' AND state = 1 GROUP BY id_order HAVING c > 1';
        $ordersToFix = Db::getInstance()->executeS($sql);
        if ($ordersToFix) {
            foreach ($ordersToFix as $row) {
                $id_order = (int)$row['id_order'];
                $returns = Db::getInstance()->executeS('SELECT id_order_return FROM ' . _DB_PREFIX_ . 'order_return WHERE id_order = ' . $id_order . ' AND state = 1 ORDER BY id_order_return ASC');
                if (count($returns) > 1) {
                    $masterId = (int)$returns[0]['id_order_return'];
                    for ($i = 1; $i < count($returns); $i++) { $this->mergeSlaveToMaster((int)$returns[$i]['id_order_return'], $masterId); }
                }
            }
        }
    }
    
    private function mergeSlaveToMaster($slaveId, $masterId) {
        $slaveDetails = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'order_return_detail WHERE id_order_return = ' . (int)$slaveId);
        if ($slaveDetails) {
            foreach ($slaveDetails as $detail) {
                $id_order_detail = (int)$detail['id_order_detail'];
                $quantity = (int)$detail['product_quantity'];
                $existsInMaster = (bool)Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'order_return_detail WHERE id_order_return = ' . (int)$masterId . ' AND id_order_detail = ' . $id_order_detail);
                if ($existsInMaster) {
                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'order_return_detail SET product_quantity = product_quantity + ' . $quantity . ' WHERE id_order_return = ' . (int)$masterId . ' AND id_order_detail = ' . $id_order_detail);
                    Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'order_return_detail WHERE id_order_return = ' . (int)$slaveId . ' AND id_order_detail = ' . $id_order_detail);
                } else {
                    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'order_return_detail SET id_order_return = ' . (int)$masterId . ' WHERE id_order_return = ' . (int)$slaveId . ' AND id_order_detail = ' . $id_order_detail);
                }
            }
        }
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'order_return SET date_upd = NOW() WHERE id_order_return = ' . (int)$masterId);
        $objSlave = new OrderReturn($slaveId); if (Validate::isLoadedObject($objSlave)) { $objSlave->delete(); }
    }

    private function assignOrderDetailVariables($id_order, $shop_address, $context) {
        $order = new Order($id_order);
        if (Validate::isLoadedObject($order) && $order->id_customer == $context->customer->id) {
            $config_days = (int)Configuration::get('PS_ORDER_RETURN_NB_DAYS');
            if ($config_days <= 0) $config_days = 14;
            $date_order = new DateTime($order->date_add);
            $date_now = new DateTime();
            $date_order->setTime(0,0,0); $date_now->setTime(0,0,0);
            $interval = $date_order->diff($date_now);
            $days_passed = (int)$interval->format('%a');
            $days_remaining = $config_days - $days_passed;
            if ($days_remaining < 0) $days_remaining = 0;
            $percent = 0; if ($config_days > 0) { $percent = ($days_remaining / $config_days) * 100; if ($percent > 100) $percent = 100; if ($percent < 0) $percent = 0; }
            $context->smarty->assign(['fresh_return_data' => ['shop_address' => $shop_address,'days_remaining' => $days_remaining,'percent' => $percent,'max_days' => $config_days,'is_returnable' => ($days_remaining > 0)]]);
        }
    }

    private function assignOrderReturnVariables($id_order_return, $shop_address, $context) {
        if (!$id_order_return) return;
        $returnObj = new OrderReturn($id_order_return);
        $days_left_text = '0'; $show_timer = false; $percent = 0; $config_days = 14; $can_edit = false;
        if (Validate::isLoadedObject($returnObj)) {
            if (in_array($returnObj->state, [1, 2])) { $can_edit = true; }
            $order = new Order($returnObj->id_order);
            if (Validate::isLoadedObject($order)) {
                $config_days = (int)Configuration::get('PS_ORDER_RETURN_NB_DAYS');
                if ($config_days <= 0) $config_days = 14; 
                $date_order = new DateTime($order->date_add); $date_now = new DateTime();
                $date_order->setTime(0,0,0); $date_now->setTime(0,0,0);
                $interval = $date_order->diff($date_now); $days_passed = (int)$interval->format('%a');
                $days_remaining = $config_days - $days_passed;
                if ($days_remaining < 0) $days_remaining = 0;
                if ($config_days > 0) { $percent = ($days_remaining / $config_days) * 100; if ($percent > 100) $percent = 100; if ($percent < 0) $percent = 0; }
                if (!in_array($returnObj->state, [5, 6])) { $show_timer = true; $days_left_text = (string)$days_remaining; }
            }
        }
        $sql = 'SELECT ord.id_order_detail, ord.product_quantity as qty_returned FROM ' . _DB_PREFIX_ . 'order_return_detail ord WHERE ord.id_order_return = ' . (int)$id_order_return;
        $returnDetails = Db::getInstance()->executeS($sql);
        $extended_data = []; $grand_total = 0.0;
        if ($returnDetails) {
            foreach ($returnDetails as $detail) {
                $sql_od = 'SELECT product_id, product_attribute_id, unit_price_tax_incl, product_name, product_reference, product_quantity FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order_detail = ' . (int)$detail['id_order_detail'];
                $od = Db::getInstance()->getRow($sql_od);
                if ($od) {
                    $id_product = (int)$od['product_id']; $id_product_attribute = (int)$od['product_attribute_id'];
                    $price = (float)$od['unit_price_tax_incl']; $qty_returned = (int)$detail['qty_returned'];
                    $total_refund_value = $price * $qty_returned; $grand_total += $total_refund_value;
                    $id_image = 0;
                    if ($id_product_attribute > 0) { $id_image = (int)Db::getInstance()->getValue('SELECT id_image FROM ' . _DB_PREFIX_ . 'product_attribute_image WHERE id_product_attribute = ' . $id_product_attribute); }
                    if (!$id_image) { $cover = Product::getCover($id_product); if ($cover) $id_image = (int)$cover['id_image']; }
                    $image_url = ''; $productObj = new Product($id_product, false, $context->language->id);
                    if (Validate::isLoadedObject($productObj) && $id_image) { $image_url = $context->link->getImageLink($productObj->link_rewrite, $id_image, 'small_default'); }
                    else { $image_url = $context->link->getMediaLink(_THEME_PROD_DIR_.'img/no-picture.jpg'); }
                    $key = (int)$detail['id_order_detail'];
                    $extended_data[$key] = [ 'image_url' => $image_url, 'name' => $od['product_name'], 'reference' => $od['product_reference'], 'price_formatted' => Tools::displayPrice($price), 'total_formatted' => Tools::displayPrice($total_refund_value), 'qty_returned' => $qty_returned, 'max_qty' => (int)$od['product_quantity'] ];
                }
            }
        }
        $context->smarty->assign([ 'return_extra_data' => $extended_data, 'shop_return_address' => $shop_address, 'total_return_sum' => Tools::displayPrice($grand_total), 'can_edit_return' => $can_edit, 'return_timer' => [ 'show' => $show_timer, 'days' => $days_left_text, 'percent' => $percent, 'max_days' => $config_days ] ]);
    }
}