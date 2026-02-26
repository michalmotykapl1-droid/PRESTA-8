<?php
require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerFolderStates.php';

trait ApiOrders
{
    /** @var array|null */
    private $statusMap = null;

    /**
     * Zwraca mapę: folder (label) => id_order_state.
     * Mapowanie pochodzi z konfiguracji i jest autouzupełniane (wykrywanie/utworzenie statusów).
     *
     * @return array<string,int>
     */
    private function getStatusMap()
    {
        if (is_array($this->statusMap)) {
            return $this->statusMap;
        }

        $idLang = 0;
        try {
            if (isset($this->context) && isset($this->context->language) && (int) $this->context->language->id > 0) {
                $idLang = (int) $this->context->language->id;
            }
        } catch (Exception $e) {
            $idLang = 0;
        }

        $this->statusMap = BbOrderManagerFolderStates::getMap($idLang);
        return $this->statusMap;
    }

protected function cloneOrderEmpty() {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) throw new Exception("Brak ID zamówienia źródłowego");

        $db = Db::getInstance();
        
        // 1. Generowanie nowego numeru referencyjnego
        $newReference = Order::generateReference();
        
        // 2. Status docelowy: "Nowe (Do zamówienia)" (z mapowania folderów)
        $statusMap = $this->getStatusMap();
        $newStatusId = isset($statusMap['Nowe (Do zamówienia)']) ? (int)$statusMap['Nowe (Do zamówienia)'] : 0;
        if ($newStatusId <= 0) {
            // fallback (Presta core)
            $newStatusId = 2;
        } 

        // 3. Klonowanie rekordu zamówienia (SQL INSERT ... SELECT)
        // Kopiujemy klienta, adresy, walutę, język, ale ZERUJEMY kwoty i produkty
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "orders` 
        (reference, id_shop_group, id_shop, id_carrier, id_lang, id_customer, id_cart, id_currency, id_address_delivery, id_address_invoice, current_state, payment, module, total_paid, total_paid_tax_incl, total_paid_tax_excl, total_products, total_products_wt, total_shipping, total_shipping_tax_incl, total_shipping_tax_excl, conversion_rate, valid, date_add, date_upd, secure_key)
        SELECT 
        '$newReference', id_shop_group, id_shop, id_carrier, id_lang, id_customer, 0, id_currency, id_address_delivery, id_address_invoice, $newStatusId, 'Manager (Ręczne)', 'bb_ordermanager', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, conversion_rate, 1, NOW(), NOW(), secure_key
        FROM `" . _DB_PREFIX_ . "orders` WHERE id_order = $id_order";

        if ($db->execute($sql)) {
            $newId = $db->Insert_ID();
            
            // 4. Dodanie wpisu do historii statusów
            $employeeId = $this->getAdminIdFromCookie();
            $db->insert('order_history', [
                'id_employee' => $employeeId,
                'id_order' => $newId,
                'id_order_state' => $newStatusId,
                'date_add' => date('Y-m-d H:i:s')
            ]);

            // 5. Log systemowy
            $this->addSystemLog(
                $newId,
                "UTWORZONO NOWE ZAMÓWIENIE (Sklonowano dane klienta z zam. #$id_order)",
                'ORDER_CREATE',
                [
                    'source_id_order' => (int) $id_order,
                    'new_reference' => (string) $newReference,
                    'new_state' => (int) $newStatusId,
                ]
            );

            echo json_encode(['success' => true, 'new_id' => $newId, 'reference' => $newReference]);
        } else {
            throw new Exception("Błąd bazy danych podczas tworzenia zamówienia.");
        }
        die();
    }

    protected function deleteOrder() {
        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) throw new Exception("Brak ID");

        $db = Db::getInstance();
        $order = new Order($id_order);
        
        if (Validate::isLoadedObject($order)) {
            $db->delete('bb_ordermanager_packing', 'id_order = ' . $id_order);
            $db->delete('bb_ordermanager_logs', 'id_order = ' . $id_order);
            
            if ($order->delete()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Nie udało się usunąć zamówienia z bazy PS.");
            }
        } else {
            throw new Exception("Zamówienie nie istnieje.");
        }
        die();
    }

    protected function archiveOrder() {
        $id_order = (int)Tools::getValue('id_order');
        $reason = trim(Tools::getValue('reason'));

        if (!$id_order) throw new Exception("Brak ID");
        if (empty($reason)) throw new Exception("Musisz podać powód przeniesienia do archiwum.");

        $statusMap = $this->getStatusMap();
        $archiveId = isset($statusMap['Archiwum']) ? (int)$statusMap['Archiwum'] : 0;
        if ($archiveId <= 0) {
            $archiveId = (int) BbOrderManagerFolderStates::getStateId('Archiwum');
        }
        if ($archiveId <= 0) {
            throw new Exception("Nie można ustalić statusu 'Archiwum'. Skonfiguruj mapowanie statusów w konfiguracji modułu.");
        }

        $this->changeStatusInDb($id_order, $archiveId);

        // Tylko treść, pracownika doda addSystemLog
        $logMessage = "PRZENIESIONO DO ARCHIWUM. Powód: " . $reason;
        $this->addSystemLog(
            $id_order,
            $logMessage,
            'STATUS_ARCHIVE',
            [
                'new_state' => (int) $archiveId,
                'reason' => (string) $reason,
            ]
        );

        echo json_encode(['success' => true]);
        die();
    }

    protected function getOrders() { 
        $db = Db::getInstance(); 
        $shopUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__; 
        $sql = 'SELECT o.id_order, o.reference, o.date_add, o.total_paid, o.total_paid_real, o.invoice_number, oc.tracking_number as shipping_number, CONCAT(c.firstname, " ", c.lastname) as customer, c.email, osl.name as status_name, o.current_state as status_id, o.payment as payment_method, ca.name as carrier_name, a.city, a.postcode, a.address1, a.address2, a.company, a.other, cl.iso_code as country_iso FROM `' . _DB_PREFIX_ . 'orders` o LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON o.id_customer = c.id_customer LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON (o.current_state = osl.id_order_state AND osl.id_lang = ' . (int)$this->context->language->id . ') LEFT JOIN `' . _DB_PREFIX_ . 'carrier` ca ON o.id_carrier = ca.id_carrier LEFT JOIN `' . _DB_PREFIX_ . 'address` a ON o.id_address_delivery = a.id_address LEFT JOIN `' . _DB_PREFIX_ . 'country` cl ON a.id_country = cl.id_country LEFT JOIN `' . _DB_PREFIX_ . 'order_carrier` oc ON o.id_order = oc.id_order WHERE o.date_add > DATE_SUB(NOW(), INTERVAL 365 DAY) ORDER BY o.date_add DESC LIMIT 500'; 
        $orders = $db->executeS($sql); 
        if($orders) { 
            foreach($orders as &$order) { 
                $id_order = (int)$order['id_order']; 
                $order['packing_link'] = $this->context->link->getModuleLink('bb_ordermanager', 'packing', ['id_order' => $id_order]); 
                $shipDetails = $this->getShippingDetails($id_order, $order['carrier_name'], $db); 
                $order['carrier_name'] = $shipDetails['name']; $order['pickup_point_id'] = $shipDetails['point_id']; $order['pickup_point_name'] = $shipDetails['point_name']; $order['pickup_point_addr'] = $shipDetails['point_addr']; 
                $products = $db->executeS('SELECT product_id, product_name, product_quantity, product_reference FROM `' . _DB_PREFIX_ . 'order_detail` WHERE id_order = ' . $id_order); 
                if ($products) { foreach ($products as &$p) { $imgId = $db->getValue('SELECT id_image FROM `' . _DB_PREFIX_ . 'image` WHERE id_product = ' . (int)$p['product_id'] . ' AND cover = 1'); $p['image_url'] = $imgId ? $shopUrl . 'img/p/' . implode('/', str_split((string)$imgId)) . '/' . $imgId . '-small_default.jpg' : null; } } 
                $order['products'] = $products; 
                
                $total_ordered = (int)$db->getValue('SELECT SUM(product_quantity) FROM `' . _DB_PREFIX_ . 'order_detail` WHERE id_order = ' . $id_order);
                $total_packed = (int)$db->getValue('SELECT SUM(quantity_packed) FROM `' . _DB_PREFIX_ . 'bb_ordermanager_packing` WHERE id_order = ' . $id_order);
                if ($total_packed >= $total_ordered && $total_ordered > 0) { $order['pack_status'] = 'done'; } elseif ($total_packed > 0) { $order['pack_status'] = 'partial'; } else { $order['pack_status'] = 'none'; }
                $hasDxInvoice = (int)$db->getValue('SELECT count(*) FROM `' . _DB_PREFIX_ . 'dxfakturownia_invoices` WHERE id_order = ' . $id_order);
                $order['has_invoice'] = ($order['invoice_number'] > 0 || $hasDxInvoice > 0);

                $virtualFolder = $this->determineFolder($order['status_id'], $order); 
                $newStatusId = $this->checkAndAutoUpdateStatus($order, $virtualFolder); 
                if ($newStatusId != $order['status_id']) { $order['virtual_folder'] = $virtualFolder; } else { $order['virtual_folder'] = $virtualFolder; } 
                try { $order['formatted_date'] = (new DateTime($order['date_add']))->format('Y-m-d H:i'); } catch (Exception $e) { $order['formatted_date'] = $order['date_add']; } 
            } 
        } else { $orders = []; } 
        echo json_encode(['success' => true, 'orders' => $orders]); die(); 
    }

    protected function getOrderDetails() { 
        $id_order = (int)Tools::getValue('id_order'); 
        if (!$id_order) throw new Exception("Brak ID"); 
        
        $db = Db::getInstance(); 
        $shopUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__; 
        
        $sql = 'SELECT o.*, osl.name as status_name, c.firstname, c.lastname, c.email, a_d.phone, a_d.phone_mobile as mobile, ca.name as carrier_name FROM `' . _DB_PREFIX_ . 'orders` o LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON o.id_customer = c.id_customer LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON (o.current_state = osl.id_order_state AND osl.id_lang = ' . (int)$this->context->language->id . ') LEFT JOIN `' . _DB_PREFIX_ . 'address` a_d ON o.id_address_delivery = a_d.id_address LEFT JOIN `' . _DB_PREFIX_ . 'carrier` ca ON o.id_carrier = ca.id_carrier WHERE o.id_order = ' . $id_order; 
        $order = $db->getRow($sql); 
        if (!$order) throw new Exception("Nie znaleziono"); 

        $order['packing_link'] = $this->context->link->getModuleLink('bb_ordermanager', 'packing', ['id_order' => $id_order]); 
        $order['tracking_number'] = $db->getValue('SELECT tracking_number FROM `' . _DB_PREFIX_ . 'order_carrier` WHERE id_order = ' . $id_order); 
        
        $shipDetails = $this->getShippingDetails($id_order, $order['carrier_name'], $db); 
        $order['carrier_name'] = $shipDetails['name']; 
        $order['pickup_point_id'] = $shipDetails['point_id']; 
        $order['pickup_point_name'] = $shipDetails['point_name']; 
        $order['pickup_point_addr'] = $shipDetails['point_addr']; 
        
        $order['is_allegro_smart'] = $shipDetails['is_allegro_smart'] ?? false;
        $order['smart_left'] = $shipDetails['smart_left'] ?? 0;
        $order['smart_limit'] = $shipDetails['smart_limit'] ?? 0;
        
        $sqlAddrD = 'SELECT a.*, cl.name as country, s.name as state FROM `' . _DB_PREFIX_ . 'address` a LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (a.id_country = cl.id_country AND cl.id_lang = ' . (int)$this->context->language->id . ') LEFT JOIN `' . _DB_PREFIX_ . 'state` s ON a.id_state = s.id_state WHERE a.id_address = ' . (int)$order['id_address_delivery']; 
        $order['address_delivery'] = $db->getRow($sqlAddrD); 
        $sqlAddrI = 'SELECT a.*, cl.name as country, s.name as state FROM `' . _DB_PREFIX_ . 'address` a LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (a.id_country = cl.id_country AND cl.id_lang = ' . (int)$this->context->language->id . ') LEFT JOIN `' . _DB_PREFIX_ . 'state` s ON a.id_state = s.id_state WHERE a.id_address = ' . (int)$order['id_address_invoice']; 
        $order['address_invoice'] = $db->getRow($sqlAddrI); 
        
        // Produkty + OBLICZANIE WAGI (Z Narzutem na pakowanie)
        $products = $db->executeS('SELECT id_order_detail, product_id, product_name, product_quantity, product_price, product_reference, product_ean13, product_weight, tax_rate, unit_price_tax_excl, unit_price_tax_incl, total_price_tax_incl, date_upd FROM `' . _DB_PREFIX_ . 'order_detail` WHERE id_order = ' . $id_order); 
        
        $totalWeightReal = 0.0;
        $totalItemsCount = 0; // Licznik sztuk

        if ($products) { 
            foreach ($products as &$p) { 
                $imgId = $db->getValue('SELECT id_image FROM `' . _DB_PREFIX_ . 'image` WHERE id_product = ' . (int)$p['product_id'] . ' AND cover = 1'); 
                $p['image_url'] = $imgId ? $shopUrl . 'img/p/' . implode('/', str_split((string)$imgId)) . '/' . $imgId . '-small_default.jpg' : null; 
                $p['total_price_formatted'] = number_format($p['total_price_tax_incl'], 2); 
                $p['product_price_formatted'] = number_format($p['product_price'], 2); 
                $p['tax_rate_formatted'] = (int)$p['tax_rate'] . '%'; 
                $p['weight_formatted'] = number_format($p['product_weight'], 3); 
                $realDate = !empty($p['date_upd']) ? $p['date_upd'] : $order['date_add']; 
                $p['date_add'] = $realDate; 
                
                // Sumowanie wagi produktów
                $totalWeightReal += ((float)$p['product_weight'] * (int)$p['product_quantity']);
                // Sumowanie liczby sztuk
                $totalItemsCount += (int)$p['product_quantity'];
            } 
        } 
        $order['products'] = $products; 
        
        // --- ZMIANA: ALGORYTM PAKOWANIA ---
        // 1. Waga produktów
        // 2. Narzut na sztukę: 100g (0.1kg)
        // 3. Stała baza kartonu: 200g (0.2kg)
        
        $packagingWeight = 0.2 + ($totalItemsCount * 0.1); 
        $finalWeight = $totalWeightReal + $packagingWeight;

        // Jeśli waga produktów w bazie = 0 (zdarza się), to chociaż mamy wagę opakowania
        $order['total_weight_real'] = number_format($finalWeight, 3, '.', '');
        
        $order['messages'] = $db->executeS('SELECT message, date_add FROM `' . _DB_PREFIX_ . 'message` WHERE id_order = ' . $id_order . ' ORDER BY date_add DESC'); 
        $rawLogs = []; try { $rawLogs = $db->executeS('SELECT message, date_add FROM `' . _DB_PREFIX_ . 'bb_ordermanager_logs` WHERE id_order = ' . $id_order . ' ORDER BY date_add DESC'); } catch (Exception $e) {}
        $historyChanges = []; $historyPack = [];
        if ($rawLogs) { foreach ($rawLogs as $log) { if (strpos($log['message'], 'PAKOWANIE:') !== false) { $log['message'] = str_replace('PAKOWANIE: ', '', $log['message']); $historyPack[] = $log; } else { $historyChanges[] = $log; } } }
        $order['history_changes'] = $historyChanges; $order['history_pack'] = $historyPack;

        $shipmentsRaw = $db->executeS('SELECT oc.tracking_number, oc.date_add, c.name as carrier_name, c.url FROM `' . _DB_PREFIX_ . 'order_carrier` oc LEFT JOIN `' . _DB_PREFIX_ . 'carrier` c ON oc.id_carrier = c.id_carrier WHERE oc.id_order = ' . $id_order . ' ORDER BY oc.date_add DESC');
        $shipments = []; if ($shipmentsRaw) { foreach ($shipmentsRaw as $s) { $trackUrl = '#'; if (!empty($s['url'])) { $trackUrl = str_replace('@', $s['tracking_number'], $s['url']); } elseif (preg_match('/^\d{24}$/', $s['tracking_number'])) {  $trackUrl = 'https://inpost.pl/sledzenie-przesylek?number=' . $s['tracking_number']; } $s['track_url'] = $trackUrl; $shipments[] = $s; } }
        $order['shipments'] = $shipments;

        $historyRaw = $db->executeS('SELECT oh.date_add, oh.id_order_state, oh.id_employee, osl.name as status_name, e.firstname, e.lastname FROM `' . _DB_PREFIX_ . 'order_history` oh LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON (oh.id_order_state = osl.id_order_state AND osl.id_lang = ' . (int)$this->context->language->id . ') LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON oh.id_employee = e.id_employee WHERE oh.id_order = ' . $id_order . ' ORDER BY oh.date_add DESC'); 
        $historyMapped = []; if ($historyRaw) { foreach ($historyRaw as $h) { $h['folder_name'] = $this->determineFolder($h['id_order_state']); $empId = (int)$h['id_employee']; if ($empId === 0) { $h['employee_display'] = 'Zmiana statusu (AUTOMAT)'; $h['is_system'] = true; } else { $fullName = trim($h['firstname'] . ' ' . $h['lastname']); if (!empty($fullName)) { $h['employee_display'] = $fullName; } else { $h['employee_display'] = 'Użytkownik (ID: ' . $empId . ')'; } $h['is_system'] = false; } $historyMapped[] = $h; } } 
        $order['history_status'] = $historyMapped; 
        
        $order['history_payment'] = $db->executeS('SELECT date_add, amount, payment_method, transaction_id FROM `' . _DB_PREFIX_ . 'order_payment` WHERE order_reference = "' . pSQL($order['reference']) . '" ORDER BY date_add DESC'); 
        if ($order['history_payment']) { foreach ($order['history_payment'] as &$pay) { $pay['amount'] = number_format((float)$pay['amount'], 2, '.', ''); } }

        $order['virtual_folder'] = $this->determineFolder($order['current_state'], $order); 
        
        if (ob_get_length()) ob_clean(); 
        echo json_encode(['success' => true, 'order' => $order]); 
        die(); 
    }

    protected function updateOrderFolder()
    {
        $id_order = (int)Tools::getValue('id_order');
        $folderName = trim((string)Tools::getValue('folder'));
        if (!$id_order || !$folderName) {
            throw new Exception("Brak danych");
        }

        $statusMap = $this->getStatusMap();

        $newStatusId = isset($statusMap[$folderName]) ? (int)$statusMap[$folderName] : 0;
        if ($newStatusId === 0) {
            if ($folderName == 'Nowe (Do zamówienia)') {
                $newStatusId = 2;
            } elseif ($folderName == 'Nieopłacone') {
                $newStatusId = 10;
            } elseif ($folderName == 'Wysłane (Historia)') {
                $newStatusId = 4;
            } else {
                throw new Exception("Nie znaleziono ID folderu");
            }
        }

        $db = Db::getInstance();

        // poprzedni status (do audytu)
        $oldStatusId = (int)$db->getValue('SELECT current_state FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order = ' . (int)$id_order);
        $oldStatusName = '';
        $newStatusName = '';
        try {
            $oldStatusName = (string)$db->getValue('SELECT name FROM `' . _DB_PREFIX_ . 'order_state_lang` WHERE id_order_state = ' . (int)$oldStatusId . ' AND id_lang = ' . (int)$this->context->language->id);
            $newStatusName = (string)$db->getValue('SELECT name FROM `' . _DB_PREFIX_ . 'order_state_lang` WHERE id_order_state = ' . (int)$newStatusId . ' AND id_lang = ' . (int)$this->context->language->id);
        } catch (Exception $e) {
            // ignore
        }

        $employeeId = $this->getAdminIdFromCookie();
        if ($employeeId === 0) {
            // fallback: ostatni pracownik z historii
            $lastEmp = (int)$db->getValue('SELECT id_employee FROM `' . _DB_PREFIX_ . 'order_history` WHERE id_order = ' . (int)$id_order . ' AND id_employee > 0 ORDER BY date_add DESC');
            if ($lastEmp) {
                $employeeId = $lastEmp;
            }
        }

        $db->update('orders', ['current_state' => $newStatusId, 'date_upd' => date('Y-m-d H:i:s')], 'id_order = ' . (int)$id_order);
        $db->insert('order_history', [
            'id_employee' => (int)$employeeId,
            'id_order' => (int)$id_order,
            'id_order_state' => (int)$newStatusId,
            'date_add' => date('Y-m-d H:i:s')
        ]);

        // Log audytowy
        $msg = 'ZMIANA STATUSU: ' . ($oldStatusName ? $oldStatusName : ('#' . $oldStatusId)) . ' → ' . ($newStatusName ? $newStatusName : ('#' . $newStatusId));
        $msg .= ' (folder: ' . $folderName . ')';

        $this->addSystemLog(
            $id_order,
            $msg,
            'STATUS_CHANGE',
            [
                'old_state' => (int) $oldStatusId,
                'new_state' => (int) $newStatusId,
                'old_state_name' => (string) $oldStatusName,
                'new_state_name' => (string) $newStatusName,
                'folder' => (string) $folderName,
            ]
        );

        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode(['success' => true, 'message' => 'Status: ' . $newStatusId]);
        die();
    }
    
    private function changeStatusInDb($id_order, $newStatusId) { $db = Db::getInstance(); $current = (int)$db->getValue('SELECT current_state FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order = ' . (int)$id_order); if ($current === (int)$newStatusId) return; $db->update('orders', ['current_state' => (int)$newStatusId, 'date_upd' => date('Y-m-d H:i:s')], 'id_order = ' . (int)$id_order); $db->insert('order_history', ['id_employee' => 0, 'id_order' => (int)$id_order, 'id_order_state' => (int)$newStatusId, 'date_add' => date('Y-m-d H:i:s')]); }
    
    private function checkAndAutoUpdateStatus($order, $virtualFolder) { 
        $currentStatusId = (int)$order['status_id']; 
        $id_order = (int)$order['id_order']; 

        $apPaid = (int)Configuration::get('ALLEGROPRO_OS_PAID');
        $apNoPay = (int)Configuration::get('ALLEGROPRO_OS_NO_PAYMENT');
        $apCancel = (int)Configuration::get('ALLEGROPRO_OS_CANCELLED');
        $apProcess = (int)Configuration::get('ALLEGROPRO_OS_PROCESSING');

        if ($currentStatusId && ($currentStatusId === $apPaid || $currentStatusId === $apNoPay || $currentStatusId === $apCancel || $currentStatusId === $apProcess)) {
            return $currentStatusId; 
        }

        // Auto-korekta statusu wg folderu w Managerze (bez sztywnych ID)
        $statusMap = $this->getStatusMap();
        $idNowe = isset($statusMap['Nowe (Do zamówienia)']) ? (int)$statusMap['Nowe (Do zamówienia)'] : 0;
        $idNoPayLocal = isset($statusMap['Nieopłacone']) ? (int)$statusMap['Nieopłacone'] : 0;

        if ($virtualFolder === 'Nowe (Do zamówienia)' && $idNowe > 0 && $currentStatusId != $idNowe) { 
            $this->changeStatusInDb($id_order, $idNowe); 
            return $idNowe; 
        } 
        if ($virtualFolder === 'Nieopłacone' && $idNoPayLocal > 0 && $currentStatusId != $idNoPayLocal) { 
            $this->changeStatusInDb($id_order, $idNoPayLocal); 
            return $idNoPayLocal; 
        } 
        return $currentStatusId; 
    }

    private function determineFolder($statusId, $orderData = null) { 
        $sid = (int)$statusId; 
        $apPaid = (int)Configuration::get('ALLEGROPRO_OS_PAID');
        $apNoPay = (int)Configuration::get('ALLEGROPRO_OS_NO_PAYMENT');
        $apCancel = (int)Configuration::get('ALLEGROPRO_OS_CANCELLED');
        $apProcess = (int)Configuration::get('ALLEGROPRO_OS_PROCESSING');

        if ($apPaid && $sid === $apPaid) return 'Nowe (Do zamówienia)';
        if ($apNoPay && $sid === $apNoPay) return 'Nieopłacone';
        if ($apCancel && $sid === $apCancel) return 'Anulowane (Klient)';
        if ($apProcess && $sid === $apProcess) return 'Do wyjaśnienia';

        if ($orderData && isset($orderData['total_paid']) && isset($orderData['total_paid_real'])) { 
            $to_pay = (float)$orderData['total_paid']; 
            $paid = (float)$orderData['total_paid_real']; 
            if ($paid > 0.01 && ($to_pay - $paid) > 0.10) { 
                return 'Do wyjaśnienia'; 
            } 
        } 
        $statusMap = $this->getStatusMap(); 
        $folderName = array_search($sid, $statusMap, true); 
        if ($folderName) return $folderName; 
        if (in_array($sid, [2, 9, 11, 13, 15, 18, 20, 21, 24])) return 'Nowe (Do zamówienia)'; 
        if (in_array($sid, [1, 8, 10, 12, 14, 16, 17, 19, 23])) return 'Nieopłacone'; 
        if (in_array($sid, [4, 5])) return 'Wysłane (Historia)'; 
        if (in_array($sid, [6, 8, 22])) return 'Anulowane (Klient)'; 
        return 'Do wyjaśnienia'; 
    }
}