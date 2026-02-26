<?php
/**
 * Packing Controller (Front) - Robust Version 2.2
 */

class Bb_ordermanagerPackingModuleFrontController extends ModuleFrontController
{
    public $display_header = false; 
    public $display_footer = false;
    public $content_only = true;

    public function initContent()
    {
        require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerAuth.php';
        require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerLogger.php';
        require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerPackingSchema.php';

        // Upewnij się, że tabela pakowania ma poprawne indeksy (ważne dla poprawności danych)
        BbOrderManagerPackingSchema::ensureSchema();
        $employeeId = (int) BbOrderManagerAuth::getEmployeeId();

        if (!$employeeId) {
            $managerUrl = $this->context->link->getModuleLink('bb_ordermanager', 'manager');
            echo '<div style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;flex-direction:column;gap:14px;background:#f8d7da;color:#721c24;text-align:center;padding:30px;">'
                . '<h2 style="margin:0;">Brak dostępu</h2>'
                . '<p style="margin:0;max-width:520px;">Musisz być zalogowany jako pracownik. Zaloguj się w BIGBIO Manager i spróbuj ponownie.</p>'
                . '<a href="' . htmlspecialchars($managerUrl) . '" style="display:inline-block;background:#0d6efd;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:700;">Przejdź do logowania</a>'
                . '</div>';
            die();
        }

        if (Tools::getValue('action') === 'update_progress') {
            $this->ajaxProcessUpdateProgress();
            return;
        }

        parent::initContent();

        $id_order = (int)Tools::getValue('id_order');
        if (!$id_order) die('Brak ID zamówienia.');

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) die('Nie znaleziono zamówienia.');

        $db = Db::getInstance();
        $orderListParam = Tools::getValue('order_list');
        
        // --- LOGIKA KOLEJKI ---
        if ($orderListParam) {
            $idsArray = array_map('intval', explode(',', $orderListParam));
            $idsString = implode(',', $idsArray);
            if (empty($idsString)) $idsString = (int)$id_order;

            $sqlQueue = 'SELECT o.id_order, o.reference, CONCAT(c.firstname, " ", c.lastname) as customer 
                         FROM `' . _DB_PREFIX_ . 'orders` o 
                         LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON o.id_customer = c.id_customer
                         WHERE o.id_order IN (' . $idsString . ') 
                         ORDER BY FIELD(o.id_order, ' . $idsString . ')';
        } else {
            $sqlQueue = 'SELECT o.id_order, o.reference, CONCAT(c.firstname, " ", c.lastname) as customer 
                         FROM `' . _DB_PREFIX_ . 'orders` o 
                         LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON o.id_customer = c.id_customer
                         WHERE o.id_order = ' . (int)$id_order;
        }

        $ordersQueue = $db->executeS($sqlQueue);
        $nextOrderId = 0;
        
        if ($ordersQueue) {
            foreach ($ordersQueue as $k => &$qOrder) {
                $qid = (int)$qOrder['id_order'];
                if ($qid === (int)$id_order && isset($ordersQueue[$k + 1])) {
                    $nextOrderId = (int)$ordersQueue[$k + 1]['id_order'];
                }
                // Pobieramy sumy
                $total_items = (int)$db->getValue('SELECT SUM(product_quantity) FROM `' . _DB_PREFIX_ . 'order_detail` WHERE id_order = ' . $qid);
                $packed_items = (int)$db->getValue('SELECT SUM(quantity_packed) FROM `' . _DB_PREFIX_ . 'bb_ordermanager_packing` WHERE id_order = ' . $qid);

                $qOrder['total_items'] = $total_items;
                $qOrder['packed_items'] = $packed_items;
                
                if ($packed_items >= $total_items && $total_items > 0) $qOrder['pack_status'] = 2; 
                elseif ($packed_items > 0) $qOrder['pack_status'] = 1; 
                else $qOrder['pack_status'] = 0; 
            }
        }

        // --- POBIERANIE PRODUKTÓW I POSTĘPU ---
        $products = $order->getProducts();
        
        // Mapa postępu oparta o ID_ORDER_DETAIL (Unikalne dla każdej linii)
        $progress_rows = $db->executeS("SELECT id_order_detail, quantity_packed FROM `" . _DB_PREFIX_ . "bb_ordermanager_packing` WHERE id_order = " . $id_order);
        $progress_map = [];
        if ($progress_rows) {
            foreach ($progress_rows as $row) {
                $progress_map[(int)$row['id_order_detail']] = (int)$row['quantity_packed'];
            }
        }

        $link = new Link();
        foreach ($products as &$p) {
            $detailId = (int)$p['id_order_detail'];
            // Przypisujemy spakowaną ilość z bazy lub 0
            $p['packed_qty'] = isset($progress_map[$detailId]) ? $progress_map[$detailId] : 0;
            $p['is_fully_packed'] = ($p['packed_qty'] >= $p['product_quantity']);
            
            $id_image = 0;
            if ($p['product_attribute_id']) {
                $attrImages = Image::getImages($this->context->language->id, $p['product_id'], $p['product_attribute_id']);
                if (!empty($attrImages)) $id_image = $attrImages[0]['id_image'];
            }
            if (!$id_image) {
                $cover = Image::getCover($p['product_id']);
                if ($cover) $id_image = $cover['id_image'];
            }
            if ($id_image) {
                $prodObj = new Product($p['product_id'], false, $this->context->language->id);
                $p['image_url'] = $this->context->link->getImageLink($prodObj->link_rewrite, $p['product_id'].'-'.$id_image, 'home_default');
            } else {
                $p['image_url'] = null;
            }
        }

        $ajaxUrl = $this->context->link->getModuleLink('bb_ordermanager', 'packing', ['action' => 'update_progress', 'id_order' => $id_order]);
        $baseLink = $this->context->link->getModuleLink('bb_ordermanager', 'packing');
        if ($orderListParam) $baseLink .= '&order_list=' . $orderListParam;
        $managerUrl = $this->context->link->getModuleLink('bb_ordermanager', 'manager', ['open_order_id' => $id_order]);

        $this->context->smarty->assign([
            'order' => $order,
            'products' => $products,
            'orders_queue' => $ordersQueue,
            'current_order_id' => $id_order,
            'next_order_id' => $nextOrderId,
            'ajax_url' => $ajaxUrl,
            'base_link' => $baseLink,
            'manager_url' => $managerUrl,
            'csrf_token' => BbOrderManagerAuth::getCsrfToken(),
            'base_dir' => __PS_BASE_URI__
        ]);

        echo $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'bb_ordermanager/views/templates/front/packing.tpl');
        exit;
    }

    protected function ajaxProcessUpdateProgress()
    {
        header('Content-Type: application/json');

        require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerAuth.php';
        require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerPackingSchema.php';
        // sesja + CSRF (nagłówek X-BBOM-CSRF)
        BbOrderManagerAuth::enforceApiAuth();

        // Schemat tabeli (migracja indeksów) – zabezpiecza poprawność ON DUPLICATE KEY
        BbOrderManagerPackingSchema::ensureSchema();

        $id_order = (int)Tools::getValue('id_order');
        $id_order_detail = (int)Tools::getValue('id_order_detail'); // To jest nasz klucz
        $qty = (int)Tools::getValue('qty');
        
        // Dane dodatkowe (dla pewności, ale id_order_detail jest najważniejsze)
        $product_id = (int)Tools::getValue('product_id');
        $attr_id = (int)Tools::getValue('product_attribute_id');

        if (!$id_order || !$id_order_detail) { 
            echo json_encode(['success' => false, 'error' => 'Brak ID detalu']); 
            die(); 
        }

        $db = Db::getInstance();
        
        // INSERT ON DUPLICATE KEY UPDATE - teraz zadziała poprawnie, bo mamy UNIQUE na id_order_detail w bazie
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "bb_ordermanager_packing` 
                (id_order, id_order_detail, product_id, product_attribute_id, quantity_packed, date_upd)
                VALUES ($id_order, $id_order_detail, $product_id, $attr_id, $qty, NOW())
                ON DUPLICATE KEY UPDATE quantity_packed = $qty, date_upd = NOW()";

        if ($db->execute($sql)) {
            
            $inv_result = null;
            
            // Sprawdź czy CAŁE zamówienie jest gotowe
            $total_ordered = (int)$db->getValue('SELECT SUM(product_quantity) FROM `' . _DB_PREFIX_ . 'order_detail` WHERE id_order = ' . $id_order);
            // Sumujemy z naszej tabeli pakowania
            $total_packed = (int)$db->getValue('SELECT SUM(quantity_packed) FROM `' . _DB_PREFIX_ . 'bb_ordermanager_packing` WHERE id_order = ' . $id_order);

            if ($total_packed >= $total_ordered && $total_ordered > 0) {
                
                // 1. Zapisz Log Pakowania
                $logExists = $db->getValue("SELECT id_log FROM `" . _DB_PREFIX_ . "bb_ordermanager_logs` WHERE id_order = $id_order AND message LIKE 'PAKOWANIE:%'");
                
                if (!$logExists) {
                    $employeeName = 'Pracownik';
                    $emp = BbOrderManagerAuth::getEmployee();
                    if ($emp && Validate::isLoadedObject($emp)) {
                        $employeeName = $emp->firstname . ' ' . $emp->lastname;
                    }
                    $msg = 'PAKOWANIE: Zamówienie skompletowane przez: ' . $employeeName;
                    BbOrderManagerLogger::log(
                        (int) $id_order,
                        'PACKING_DONE',
                        $msg,
                        [
                            'total_ordered' => (int) $total_ordered,
                            'total_packed' => (int) $total_packed,
                        ],
                        ($emp && Validate::isLoadedObject($emp)) ? $emp : null
                    );
                }

                // 2. Wystaw Fakturę (Fakturownia)
                if (Module::isInstalled('dxfakturownia') && Module::isEnabled('dxfakturownia')) {
                    $servicePath = _PS_MODULE_DIR_ . 'dxfakturownia/classes/AutoInvoiceService.php';
                    if (file_exists($servicePath)) {
                        require_once $servicePath;
                        if (class_exists('AutoInvoiceService')) {
                            $inv_result = AutoInvoiceService::processOrder($id_order);
                        }
                    }
                }
            }

            echo json_encode(['success' => true, 'invoice' => $inv_result]);
        } else {
            echo json_encode(['success' => false, 'error' => $db->getMsgError()]);
        }
        die();
    }
}