<?php
/**
 * Kontroler Modułu Zamówień (ZARZĄDCA MVC)
 * Wersja: 2.1 (FIX: DB Permanent Statuses - Syntax Fix)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Managers/OrderSessionManager.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Handlers/OrderAjaxHandler.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Processors/OrderAnalysisProcessor.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/Stock/SurplusManager.php';

class AdminModulZamowienController extends ModuleAdminController
{
    private $sessionManager;
    private $ajaxHandler;
    private $processor;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Moduł Zamówień');
        
        $this->sessionManager = new OrderSessionManager();
        $this->ajaxHandler = new OrderAjaxHandler($this->sessionManager);
        $this->processor = new OrderAnalysisProcessor($this->sessionManager);
        
        $this->checkDb();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        
        // --- NOWY PODZIAŁ PLIKÓW JS (CLEAN CODE) ---
        
        // 1. Narzędzia wspólne (np. playSound)
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/wms_utils.js?v=' . time());
        
        // 2. Logika Zakładki 2 (Główna kompletacja)
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/picking_manager.js?v=' . time());
        
        // 3. Logika Zakładki 4 (Pick Stół)
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/picking_table.js?v=' . time());
        
        // 4. Synchronizacja Mobile
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/mobile_sync.js?v=' . time());
        
        // 5. Kopiowanie zamówień (Zakładka 3)
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/orders_clipboard.js?v=' . time());

        // --- POZOSTAŁE PLIKI (BEZ ZMIAN) ---
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/admin_extra.js?v=' . time());
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/admin_extra_partial.js?v=' . time());
        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/admin_reception.js?v=' . time());
    }

    private function checkDb()
    {
        // 1. Tabela Historii (istniejąca)
        Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "modulzamowien_history` (
            `id_history` INT(11) NOT NULL AUTO_INCREMENT,
            `id_employee` INT(11) NOT NULL,
            `employee_name` VARCHAR(64) NOT NULL,
            `supplier_name` VARCHAR(128) NOT NULL,
            `total_cost` DECIMAL(20,2) NOT NULL DEFAULT '0.00',
            `items_count` INT(11) NOT NULL DEFAULT '0',
            `order_data` TEXT NOT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_history`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;");

        // 2. Statusy naprawionych produktów (WYMIENIONO) - per pracownik/per dzień
        $this->ensureReplacedTable();

new SurplusManager(); 

        $tabId = (int)Tab::getIdFromClassName('AdminModulSurplus');
        if (!$tabId) {
            $tab = new Tab(); 
            $tab->active = 1; 
            $tab->class_name = 'AdminModulSurplus'; 
            $tab->name = []; 
            foreach (Language::getLanguages(true) as $lang) $tab->name[$lang['id_lang']] = 'Moduł Surplus';
            $tab->id_parent = -1; 
            $tab->module = $this->module->name; 
            $tab->add();
        }
    }


/**
 * Zapewnia istnienie tabeli statusów "WYMIENIONO" w poprawnym schemacie:
 * - statusy są per pracownik (id_employee)
 * - jeden rekord na (id_employee, ean)
 * - date_add pozwala filtrować tylko dzisiejsze statusy
 */
private function ensureReplacedTable()
{
    $table = _DB_PREFIX_ . 'modulzamowien_replaced';

    // 1) Utwórz tabelę w docelowym schemacie (dla świeżych instalacji)
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

    // 2) Migracja tabeli ze starego schematu (PRIMARY KEY(ean)) -> (id_employee, ean)
    try {
        $hasCol = Db::getInstance()->executeS("SHOW COLUMNS FROM `{$table}` LIKE 'id_employee'");
        if (empty($hasCol)) {
            Db::getInstance()->execute("ALTER TABLE `{$table}` ADD `id_employee` INT(11) NOT NULL DEFAULT 0 FIRST");
        }
    } catch (Exception $e) {
        // ignore
    }

    try {
        $hasDate = Db::getInstance()->executeS("SHOW COLUMNS FROM `{$table}` LIKE 'date_add'");
        if (empty($hasDate)) {
            Db::getInstance()->execute("ALTER TABLE `{$table}` ADD `date_add` DATETIME NOT NULL AFTER `ean`");
        }
    } catch (Exception $e) {
        // ignore
    }

    // 3) Upewnij się, że PRIMARY KEY ma poprawny kształt
    try {
        $pkRows = Db::getInstance()->executeS("SHOW INDEX FROM `{$table}` WHERE Key_name='PRIMARY'");
        $pkCols = [];
        if ($pkRows) {
            foreach ($pkRows as $r) {
                $seq = isset($r['Seq_in_index']) ? (int)$r['Seq_in_index'] : 0;
                $col = isset($r['Column_name']) ? $r['Column_name'] : '';
                if ($seq > 0 && $col) {
                    $pkCols[$seq] = $col;
                }
            }
            ksort($pkCols);
            $pkCols = array_values($pkCols);
        }

        if ($pkCols !== ['id_employee', 'ean']) {
            Db::getInstance()->execute("ALTER TABLE `{$table}` DROP PRIMARY KEY, ADD PRIMARY KEY (`id_employee`, `ean`)");
        }
    } catch (Exception $e) {
        // ignore
    }
}

    public function postProcess()
    {
        if (Tools::getValue('action') == 'initMobileSession') {
            die(json_encode($this->sessionManager->initMobileSession()));
        }
        if (Tools::getValue('action') == 'decreaseStock') {
            die(json_encode($this->ajaxHandler->handleDecreaseStock()));
        }
        if (Tools::getValue('action') == 'clearCollected') {
            die(json_encode($this->ajaxHandler->handleClearCollected()));
        }
        if (Tools::getValue('action') == 'updateTablePick') {
            die(json_encode($this->ajaxHandler->handleUpdateTablePick()));
        }
        if (Tools::getValue('action') == 'checkPickingState') {
            $fileData = $this->sessionManager->loadPickingFromFile();
            if (session_status() == PHP_SESSION_NONE) session_start();
            if (!empty($fileData)) $_SESSION['mz_picking_data'] = $fileData;
            die(json_encode($fileData));
        }
        

        // RESET (TEST): szybkie czyszczenie danych analizy bez przeinstalowywania modułu
        if (Tools::isSubmit('submitResetAnalysis')) {
            $this->sessionManager->clearAnalysisSessions();

            if (session_status() == PHP_SESSION_NONE) session_start();
            unset($_SESSION['mz_report_data'], $_SESSION['mz_picking_data'], $_SESSION['mz_extra_items']);

            // Czyścimy także statusy "WYMIENIONO" dla tego pracownika (żeby nie wpływały na kolejną analizę)
            try {
                $this->ensureReplacedTable();
                $idEmployee = (int)$this->context->employee->id;
                if ($idEmployee > 0) {
                    Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . "modulzamowien_replaced` WHERE id_employee = " . (int)$idEmployee);
                }
            } catch (Exception $e) {
                // ignore
            }

            $this->confirmations[] = 'Wyczyszczono dane analizy (Zakładki 1-3).';
        }

        if (Tools::isSubmit('submitUploadCsv') || Tools::isSubmit('submitFetchPresta')) { 
            // Przełącznik: czy analiza ma automatycznie ściągać ilości z Pick Stołu (Wirtualnego Magazynu)
            // Domyślnie: TAK
            // Uwaga: to ustawienie trzymamy per-sesja (nie globalnie), żeby testy nie wpływały na innych pracowników.
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $consumePickTableDefault = isset($_SESSION['mz_consume_pick_table']) ? (int)$_SESSION['mz_consume_pick_table'] : 1;
            $consumePickTable = (int)Tools::getValue('consume_pick_table', $consumePickTableDefault);
            $consumePickTable = $consumePickTable ? 1 : 0;
            $_SESSION['mz_consume_pick_table'] = $consumePickTable;

            if (isset($_FILES['order_file']) && !empty($_FILES['order_file']['tmp_name'])) {
                $msg = $this->processor->processCsvAnalysis($_FILES['order_file']['tmp_name'], (bool)$consumePickTable);
                if (strpos($msg, '[DUPLIKAT]') !== false) {
                    $cleanMsg = str_replace('[DUPLIKAT]', '', $msg);
                    $this->context->smarty->assign('duplicate_file_error', $cleanMsg);
                } else {
                    $this->confirmations[] = $msg;
                }
            } else {
                $this->errors[] = $this->l('Nie wybrano pliku CSV.');
            }
        }
        
        if (Tools::isSubmit('submitClearQueue')) {
            $sm = new SurplusManager(); 
            $sm->clearQueue();
            $this->confirmations[] = "Lista zadań Pick Stół została oczyszczona.";
        }

        // Ważne: nie blokuj standardowego mechanizmu ajaxProcessX PrestaShop.
        // Bez tego metody typu ajaxProcessSaveFixStatus/ajaxProcessGetFixStatuses nie będą wywoływane.
        parent::postProcess();
    }

    public function initContent() 
    { 
        if (!Tools::isSubmit('submitUploadCsv') && !Tools::getValue('ajax')) {
            if (session_status() == PHP_SESSION_NONE) session_start();
        }
        
        $mainToken = Tools::getAdminTokenLite('AdminModulZamowien');
        
        // Przełącznik domyślny dla zużywania Pick Stołu podczas analizy CSV
        // Uwaga: trzymamy to per-sesja (nie globalnie), żeby testy nie wpływały na innych pracowników.
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $consumePickTableDefault = 1; // domyślnie ON
        if (isset($_SESSION['mz_consume_pick_table'])) {
            $consumePickTableDefault = (int)$_SESSION['mz_consume_pick_table'] ? 1 : 0;
        }

$this->context->smarty->assign([
            'module_name' => $this->module->name, 
            'modules_dir' => _MODULE_DIR_,
            'statuses' => OrderState::getOrderStates($this->context->language->id),
            'current_url' => self::$currentIndex, 
            'token' => $this->token,
            'consume_pick_table_default' => (int)$consumePickTableDefault,
            'surplus_form_action' => $this->context->link->getAdminLink('AdminModulSurplus'), 
            'main_controller_url' => 'index.php?controller=AdminModulZamowien&token=' . $mainToken, 
            'ajax_picking_url' => $this->context->link->getAdminLink('AdminModulZamowien') . '&ajax=1&action=decreaseStock',
            'ajax_clear_collected_url' => $this->context->link->getAdminLink('AdminModulZamowien') . '&ajax=1&action=clearCollected',
            'ajax_table_pick_url' => $this->context->link->getAdminLink('AdminModulZamowien') . '&ajax=1&action=updateTablePick',
            'ajax_refresh_url' => $this->context->link->getAdminLink('AdminModulOrders') . '&ajax=1&action=refreshOrders',
            'ajax_history_delete_url' => $this->context->link->getAdminLink('AdminModulOrders') . '&ajax=1&action=deleteHistory',
            'ajax_extra_url' => $this->context->link->getAdminLink('AdminModulOrders') . '&ajax=1',
            'ajax_history_save_url' => $this->context->link->getAdminLink('AdminModulOrders') . '&ajax=1&action=saveHistory',
            
            // FIX: Inicjalizacja zmiennych strategii
            'current_strategy' => 'std',
            'total_val_std' => 0,
            'total_val_alt' => 0
        ]);
        
        if (session_status() == PHP_SESSION_NONE) session_start();
        
        $reportData = isset($_SESSION['mz_report_data']) ? $_SESSION['mz_report_data'] : [];
        $pickingData = $this->sessionManager->loadPickingFromFile();
        $_SESSION['mz_picking_data'] = $pickingData;
        
        $this->context->smarty->assign('report_data', $reportData);
        $this->context->smarty->assign('picking_data', $pickingData);
        
        $this->context->smarty->assign('orders_grouped', null); 
        
        $historyList = Db::getInstance()->executeS("SELECT id_history, date_add, supplier_name, items_count FROM `" . _DB_PREFIX_ . "modulzamowien_history` ORDER BY CAST(date_add AS DATE) DESC, CASE WHEN supplier_name LIKE '%Bio%Planet%' THEN 0 WHEN supplier_name LIKE '%[EXTRA]%' THEN 1 ELSE 2 END ASC, date_add DESC LIMIT 20");
        $this->context->smarty->assign('history_list', $historyList);
        
        $historyData = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "modulzamowien_history` ORDER BY date_add DESC");
        $this->context->smarty->assign('history_data', $historyData);
        
        $fixedExtraItems = $this->processor->getExtraItemsForPicking();
        $this->context->smarty->assign('extra_items_picking', $fixedExtraItems); 
        $this->context->smarty->assign('extra_items', isset($_SESSION['mz_extra_items']) ? $_SESSION['mz_extra_items'] : []);

        $sm = new SurplusManager();
        $surplusData = $sm->getSurplusList();
        foreach ($surplusData as &$sItem) {
            $price = 0;
            if (!empty($sItem['ean'])) {
                $price = Db::getInstance()->getValue("SELECT wholesale_price FROM `" . _DB_PREFIX_ . "product` WHERE ean13 = '" . pSQL($sItem['ean']) . "'");
            }
            $sItem['price_net'] = $price ? (float)$price : 0;
            $sItem['val_net'] = $sItem['price_net'] * (int)$sItem['qty'];
        }
        $this->context->smarty->assign('surplus_data', $surplusData);
        
        $queueData = $sm->getQueueList();
        foreach ($queueData as &$qItem) {
            $pid = Db::getInstance()->getValue("SELECT id_product FROM `" . _DB_PREFIX_ . "product` WHERE ean13 = '" . pSQL($qItem['ean']) . "'");
            if ($pid) {
                $img = Product::getCover($pid);
                $qItem['image_id'] = $img['id_image'];
                $qItem['link_rewrite'] = Db::getInstance()->getValue("SELECT link_rewrite FROM `" . _DB_PREFIX_ . "product_lang` WHERE id_product = $pid AND id_lang = " . (int)$this->context->language->id);
            }
            $qItem['is_table_collected'] = ($qItem['qty_picked'] >= $qItem['qty_to_pick']);
        }
        $this->context->smarty->assign('picktable_data', $queueData);
        
        $this->content = $this->createTemplate('main_view.tpl')->fetch();
        parent::initContent();
    }

    /* --- NOWE FUNKCJE: OBSŁUGA STATUSÓW 'WYMIENIONO' (DB) --- */

    
public function ajaxProcessSaveFixStatus()
{
    $ean = Tools::getValue('ean');
    $ean = trim((string)$ean);

    if ($ean !== '') {
        $this->ensureReplacedTable();
        $idEmployee = (int)$this->context->employee->id;

        if ($idEmployee > 0) {
            Db::getInstance()->execute("
                INSERT INTO `" . _DB_PREFIX_ . "modulzamowien_replaced` (`id_employee`, `ean`, `date_add`)
                VALUES (" . (int)$idEmployee . ", '" . pSQL($ean) . "', NOW())
                ON DUPLICATE KEY UPDATE `date_add` = VALUES(`date_add`)
            ");
        }
    }

    die(json_encode(['success' => true]));
}


    
public function ajaxProcessGetFixStatuses()
{
    $this->ensureReplacedTable();
    $idEmployee = (int)$this->context->employee->id;

    $map = [];
    if ($idEmployee > 0) {
        $results = Db::getInstance()->executeS("
            SELECT `ean`
            FROM `" . _DB_PREFIX_ . "modulzamowien_replaced`
            WHERE id_employee = " . (int)$idEmployee . " AND date_add >= CURDATE()
        ");

        if ($results) {
            foreach ($results as $row) {
                if (!empty($row['ean'])) {
                    $map[$row['ean']] = 1;
                }
            }
        }
    }

    die(json_encode(['success' => true, 'data' => $map]));
}

}