<?php
/**
 * Główny plik modułu - Wersja 1.9.1 (Obsługa Alternatywnej Strategii)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ModulZamowien extends Module
{
    public function __construct()
    {
        $this->name = 'modulzamowien';
        $this->tab = 'administration';
        $this->version = '1.9.1'; 
        $this->author = 'TwojaFirma';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MODUŁ ZAMÓWIEŃ');
        $this->description = $this->l('System zamawiania: Magazyn + Hurtownie + Historia + Extra (Dual Strategy).');
    }

    public function install()
    {
        return parent::install() 
            && $this->installTab() 
            && $this->installDb() 
            && $this->registerHook('actionAdminControllerSetMedia');
    }

    public function uninstall()
    {
        return $this->uninstallTab() 
            && $this->uninstallDb() 
            && parent::uninstall();
    }

    public function getContent()
    {
        $this->installTab(); 
        $this->installDb(); 
        
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModulZamowien'));
    }

    public function hookActionAdminControllerSetMedia()
    {
        // Ważne: pliki JS są ładowane w kontrolerze AdminModulZamowienController::setMedia().
        // Wcześniej ten hook dokładał admin_reception.js drugi raz, co powodowało podwójne bindy
        // zdarzeń (np. click/submit) i potencjalne podwójne wywołania AJAX.
        // Zostawiamy hook zarejestrowany (bez reinstalacji modułu), ale nie ładujemy już pliku.
        return;
    }

    // --- INSTALACJA BAZY DANYCH ---
    public function installDb()
    {
        $sql = [];

        // 1. Historia (Nagłówki)
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_history` (
            `id_history` INT(11) NOT NULL AUTO_INCREMENT,
            `id_employee` INT(11) NOT NULL,
            `employee_name` VARCHAR(64) NOT NULL,
            `supplier_name` VARCHAR(128) NOT NULL,
            `total_cost` DECIMAL(20,2) NOT NULL DEFAULT "0.00",
            `items_count` INT(11) NOT NULL DEFAULT 0,
            `order_data` TEXT NOT NULL, 
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_history`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 2. Historia Szczegóły
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_history_items` (
            `id_history_item` INT(11) NOT NULL AUTO_INCREMENT,
            `id_history` INT(11) NOT NULL,
            `ean` VARCHAR(32) DEFAULT NULL,
            `sku` VARCHAR(64) DEFAULT NULL, 
            `name` VARCHAR(255) NOT NULL,
            `qty` INT(11) NOT NULL DEFAULT 0,
            `price` DECIMAL(20,2) NOT NULL DEFAULT "0.00",
            `is_extra` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id_history_item`),
            KEY `idx_history` (`id_history`),
            KEY `idx_ean` (`ean`),
            KEY `idx_sku` (`sku`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 3. Surplus
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_surplus_v2` (
            `id_surplus` INT(11) NOT NULL AUTO_INCREMENT,
            `ean` VARCHAR(32) NOT NULL,
            `name` VARCHAR(128) NOT NULL,
            `qty` INT(11) NOT NULL DEFAULT 0,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_surplus`),
            KEY `ean_idx` (`ean`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 4. Queue
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_picking_queue` (
            `id_task` INT(11) NOT NULL AUTO_INCREMENT,
            `ean` VARCHAR(32) NOT NULL,
            `name` VARCHAR(128) NOT NULL,
            `qty_to_pick` INT(11) NOT NULL DEFAULT 0,
            `qty_picked` INT(11) NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_task`),
            KEY `ean_idx` (`ean`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 5. Upload Log
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_upload_log_v2` (
            `id_log` INT(11) NOT NULL AUTO_INCREMENT,
            `file_hash` VARCHAR(32) NOT NULL,
            `file_name` VARCHAR(128) NOT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_log`),
            UNIQUE KEY `hash_idx` (`file_hash`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 6. Extra Items
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_extra_items` (
            `id_extra` INT(11) NOT NULL AUTO_INCREMENT,
            `ean` VARCHAR(32) DEFAULT NULL,
            `name` VARCHAR(255) NOT NULL,
            `qty` INT(11) NOT NULL DEFAULT 0,
            `sku` VARCHAR(64) DEFAULT NULL,
            `is_mag` TINYINT(1) DEFAULT 0,
            `id_product` INT(11) DEFAULT 0,
            `mag_sku` VARCHAR(64) DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_extra`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        // 7. Picking Session
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_picking_session` (
            `id_item` INT(11) NOT NULL AUTO_INCREMENT,
            `id_employee` INT(11) NOT NULL DEFAULT 0,
            `ean` VARCHAR(32) NOT NULL,
            `sku` VARCHAR(64) NOT NULL,
            `alt_sku` VARCHAR(64) DEFAULT NULL,
            `name` VARCHAR(255) NOT NULL,
            `location` VARCHAR(64) DEFAULT NULL,
            `alternatives_json` TEXT DEFAULT NULL,
            `regal` VARCHAR(16) DEFAULT NULL,
            `alt_regal` VARCHAR(16) DEFAULT NULL,
            `polka` VARCHAR(16) DEFAULT NULL,
            `alt_polka` VARCHAR(16) DEFAULT NULL,
            `qty_to_pick` INT(11) NOT NULL DEFAULT 0,
            `qty_picked` INT(11) NOT NULL DEFAULT 0,
            `qty_original` INT(11) NOT NULL DEFAULT 0,
            `image_id` VARCHAR(32) DEFAULT NULL,
            `link_rewrite` VARCHAR(128) DEFAULT NULL,
            `id_product` INT(11) DEFAULT 0,
            `is_collected` TINYINT(1) DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_item`),
            KEY `id_employee_idx` (`id_employee`),
            UNIQUE KEY `emp_sku_unique` (`id_employee`, `sku`),
            KEY `ean_idx` (`ean`),
            KEY `sku_idx` (`sku`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        // 8. Picking Files Log
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_picking_files` (
            `id_file` INT(11) NOT NULL AUTO_INCREMENT,
            `id_employee` INT(11) NOT NULL DEFAULT 0,
            `file_hash` VARCHAR(32) NOT NULL,
            `file_name` VARCHAR(128) NOT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_file`),
            KEY `id_employee_idx` (`id_employee`),
            UNIQUE KEY `emp_hash_idx` (`id_employee`, `file_hash`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        // 9. Orders Session (Strategia A - Standard)
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_orders_session` (
            `id_order_item` INT(11) NOT NULL AUTO_INCREMENT,
            `id_employee` INT(11) NOT NULL DEFAULT 0,
            `ean` VARCHAR(32) NOT NULL,
            `sku` VARCHAR(64) DEFAULT NULL,
            `name` VARCHAR(255) NOT NULL,
            `qty_buy` INT(11) NOT NULL DEFAULT 0,
            `qty_total` INT(11) NOT NULL DEFAULT 0,
            `supplier_name` VARCHAR(128) DEFAULT NULL,
            `price_net` DECIMAL(20,2) NOT NULL DEFAULT "0.00",
            `savings` DECIMAL(20,2) NOT NULL DEFAULT "0.00",
            `tax_rate` DECIMAL(10,2) NOT NULL DEFAULT "0.00",
            `status` VARCHAR(32) NOT NULL DEFAULT "OK",
            `was_switched` TINYINT(1) NOT NULL DEFAULT 0,
            `missing_qty` INT(11) NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_order_item`),
            KEY `id_employee_idx` (`id_employee`),
            KEY `ean_idx` (`ean`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        // 10. Orders Session ALT (Strategia B - Alternatywna)
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'modulzamowien_orders_session_alt` (
            `id_order_item` INT(11) NOT NULL AUTO_INCREMENT,
            `id_employee` INT(11) NOT NULL DEFAULT 0,
            `ean` VARCHAR(32) NOT NULL,
            `sku` VARCHAR(64) DEFAULT NULL,
            `name` VARCHAR(255) NOT NULL,
            `qty_buy` INT(11) NOT NULL DEFAULT 0,
            `qty_total` INT(11) NOT NULL DEFAULT 0,
            `supplier_name` VARCHAR(128) DEFAULT NULL,
            `price_net` DECIMAL(20,2) NOT NULL DEFAULT "0.00",
            `savings` DECIMAL(20,2) NOT NULL DEFAULT "0.00",
            `tax_rate` DECIMAL(10,2) NOT NULL DEFAULT "0.00",
            `status` VARCHAR(32) NOT NULL DEFAULT "OK",
            `was_switched` TINYINT(1) NOT NULL DEFAULT 0,
            `missing_qty` INT(11) NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_order_item`),
            KEY `id_employee_idx` (`id_employee`),
            KEY `ean_idx` (`ean`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return true;
    }

    public function uninstallDb()
    {
        $tables = [
            'modulzamowien_history',
            'modulzamowien_history_items',
            'modulzamowien_surplus_v2',
            'modulzamowien_picking_queue',
            'modulzamowien_upload_log_v2',
            'modulzamowien_extra_items',
            'modulzamowien_picking_session',
            'modulzamowien_picking_files',
            'modulzamowien_orders_session',
            'modulzamowien_orders_session_alt' // Usunięcie nowej tabeli przy odinstalowaniu
        ];

        foreach ($tables as $table) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`');
        }

        return true;
    }

    private function installTab()
    {
        $tabs = [
            'AdminModulZamowien' => ['name' => 'MODUŁ ZAMÓWIEŃ', 'parent' => (int)Tab::getIdFromClassName('SELL')],
            'AdminModulReception' => ['name' => 'Moduł Reception (Ajax)', 'parent' => -1],
            'AdminModulSurplus' => ['name' => 'Moduł Surplus (Ajax)', 'parent' => -1],
            'AdminModulOrders' => ['name' => 'Moduł Orders (Ajax)', 'parent' => -1],
            'AdminModulExtra' => ['name' => 'Moduł Extra (Ajax)', 'parent' => -1],
            'AdminModulMobileStock' => ['name' => 'Zatowarowanie Mobile', 'parent' => -1]
        ];

        foreach ($tabs as $className => $data) {
            $tabId = (int) Tab::getIdFromClassName($className);
            if (!$tabId) {
                $tab = new Tab();
                $tab->active = 1;
                $tab->class_name = $className;
                $tab->name = [];
                foreach (Language::getLanguages(true) as $lang) {
                    $tab->name[$lang['id_lang']] = $data['name'];
                }
                $tab->id_parent = $data['parent'];
                $tab->module = $this->name;
                $tab->add();
            }
        }
        return true;
    }

    private function uninstallTab()
    {
        $classes = [
            'AdminModulZamowien', 
            'AdminModulReception', 
            'AdminModulSurplus', 
            'AdminModulOrders', 
            'AdminModulExtra',
            'AdminModulMobileStock'
        ];
        foreach ($classes as $className) {
            $tabId = (int) Tab::getIdFromClassName($className);
            if ($tabId) {
                $tab = new Tab($tabId);
                $tab->delete();
            }
        }
        return true;
    }
}