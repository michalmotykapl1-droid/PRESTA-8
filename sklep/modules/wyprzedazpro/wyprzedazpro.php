<?php
/**
 * Plik: /modules/wyprzedazpro/wyprzedazpro.php
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class WyprzedazPro extends Module
{
    public function __construct()
    {
        $this->name = 'wyprzedazpro';
        $this->tab = 'administration';
        $this->version = '1.2.0';
        $this->author = 'Twój Zespół Dev';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Wyprzedaż PRO (WMS)');
        $this->description = $this->l('System WMS: 0_MAG, podwójne stany magazynowe, import CSV.');

        $this->confirmUninstall = $this->l('Czy usunąć moduł i jego dane?');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->registerHook('actionAdminControllerSetMedia');

        // Instalacja Bazy Danych i Zakładki (na wzór ModulZamowien)
        return $this->installDb() && $this->installTab();
    }

    public function uninstall()
    {
        // Usuwanie zakładki i bazy
        return $this->uninstallTab() && $this->uninstallDb() && parent::uninstall();
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminWyprzedazPro'));
    }
    
    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('controller') === 'AdminWyprzedazPro') {
            $this->context->controller->addCSS($this->getPathUri().'views/css/back.css');
            $this->context->controller->addJS($this->getPathUri().'views/js/back.js');
        }
    }

    // --- ZMIANA: Logika zakładki skopiowana i dostosowana z modulzamowien.php ---
    private function installTab()
    {
        $tabId = (int) Tab::getIdFromClassName('AdminWyprzedazPro');
        if (!$tabId) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = 'AdminWyprzedazPro';
            $tab->name = [];
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = 'Wyprzedaż PRO (WMS)';
            }
            
            // Używamy 'SELL' jako rodzica, dokładnie jak w modulzamowien.php
            $tab->id_parent = (int) Tab::getIdFromClassName('SELL');
            
            $tab->module = $this->name;
            $tab->icon = 'storage'; // Dodaję ikonę, żeby wyglądało profesjonalnie
            
            return $tab->add();
        }
        return true;
    }

    private function uninstallTab()
    {
        $tabId = (int) Tab::getIdFromClassName('AdminWyprzedazPro');
        if ($tabId) {
            $tab = new Tab($tabId);
            return $tab->delete();
        }
        return true;
    }
    // --------------------------------------------------------------------------

    private function installDb()
    {
        $prefix = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        $sql = [];

        // Konfiguracja domyślna
        Configuration::updateValue('WYPRZEDAZPRO_IMPORT_BATCH', 500);
        Configuration::updateValue('WYPRZEDAZPRO_FINALIZE_BATCH', 100);
        Configuration::updateValue('WYPRZEDAZPRO_SHORT_DATE_DAYS', 14);

        // Tabela Staging
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedazpro_csv_staging` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `session_id` VARCHAR(64) NOT NULL,
            `ean` VARCHAR(32) NOT NULL,
            `sku` VARCHAR(128) NULL, 
            `quantity` INT NOT NULL,
            `receipt_date` DATE NULL,
            `expiry_date` DATE NULL,
            `regal` VARCHAR(64) NULL,
            `polka` VARCHAR(64) NULL,
            PRIMARY KEY (`id`),
            KEY `session_idx` (`session_id`),
            KEY `ean_idx` (`ean`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';

        // Tabela Tasks
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedazpro_finalize_tasks` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `session_id` VARCHAR(64) NOT NULL,
            `ean` VARCHAR(32) NOT NULL,
            `sku` VARCHAR(128) NULL,
            `quantity` INT NOT NULL,
            `receipt_date` DATE NULL,
            `expiry_date` DATE NULL,
            `regal` VARCHAR(64) NULL,
            `polka` VARCHAR(64) NULL,
            `status` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `session_idx` (`session_id`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';

        // Tabela Duplikaty
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedazpro_csv_duplikaty` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ean` VARCHAR(32) NOT NULL,
            `quantity` INT NOT NULL,
            `receipt_date` DATE NULL,
            `expiry_date` DATE NULL,
            `regal` VARCHAR(64) NULL,
            `polka` VARCHAR(64) NULL,
            PRIMARY KEY (`id`),
            KEY `ean_idx` (`ean`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';

        // Tabela Product Details (WMS) - ZAKTUALIZOWANA O is_manual
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedazpro_product_details` (
            `id_product` INT UNSIGNED NOT NULL,
            `sku` VARCHAR(128) NULL,
            `ean` VARCHAR(32) NULL,
            `quantity_wms` INT NOT NULL DEFAULT 0,
            `is_manual` TINYINT(1) NOT NULL DEFAULT 0, 
            `expiry_date` DATE NULL,
            `receipt_date` DATE NULL,
            `regal` VARCHAR(64) NULL,
            `polka` VARCHAR(64) NULL,
            PRIMARY KEY (`id_product`),
            KEY `ean_idx` (`ean`),
            KEY `sku_idx` (`sku`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';

        // Tabela Not Found
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedazpro_not_found_products` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ean` VARCHAR(32) NOT NULL,
            `quantity` INT NOT NULL,
            `receipt_date` DATE NULL,
            `expiry_date` DATE NULL,
            `regal` VARCHAR(64) NULL,
            `polka` VARCHAR(64) NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `ean_idx` (`ean`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';

        // Tabela History
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . $prefix . 'wyprzedazpro_import_history` (
            `id_history` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `date_add` DATETIME NOT NULL,
            `filename` VARCHAR(255) NOT NULL,
            `rows_total` INT UNSIGNED NOT NULL DEFAULT 0,
            `rows_in_db` INT UNSIGNED NOT NULL DEFAULT 0,
            `rows_not_found` INT UNSIGNED NOT NULL DEFAULT 0,
            `id_shop` INT UNSIGNED NOT NULL DEFAULT 0,
            `id_employee` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id_history`),
            INDEX (`date_add`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) return false;
        }
        
        // --- AKTUALIZACJA DLA ISTNIEJĄCEJ INSTALACJI ---
        // Jeśli tabela już istnieje, dodajemy kolumnę bezpiecznie
        $cols = Db::getInstance()->executeS('SHOW COLUMNS FROM `'.$prefix.'wyprzedazpro_product_details` LIKE "is_manual"');
        if (empty($cols)) {
            Db::getInstance()->execute('ALTER TABLE `'.$prefix.'wyprzedazpro_product_details` ADD COLUMN `is_manual` TINYINT(1) NOT NULL DEFAULT 0 AFTER `quantity_wms`');
        }

        return true;
    }

    private function uninstallDb()
    {
        $tables = [
            'wyprzedazpro_product_details', 'wyprzedazpro_csv_duplikaty',
            'wyprzedazpro_not_found_products', 'wyprzedazpro_csv_staging',
            'wyprzedazpro_finalize_tasks', 'wyprzedazpro_import_history'
        ];
        foreach ($tables as $table) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`');
        }
        
        // Czyszczenie konfiguracji
        Configuration::deleteByName('WYPRZEDAZPRO_IMPORT_BATCH');
        Configuration::deleteByName('WYPRZEDAZPRO_FINALIZE_BATCH');
        Configuration::deleteByName('WYPRZEDAZPRO_SHORT_DATE_DAYS');
        
        return true;
    }
}