<?php
/**
 * Ścieżka do pliku: /modules/wyprzedaz/wyprzedaz.php
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Wyprzedaz extends Module
{
    private function guessParentTabId()
    {
        $db = \Db::getInstance();
        $candidates = [
            'Omnibus Historia Ceny',
            'Gemini AI Content',
            'Zarządzanie Wyprzedażą'
        ];
        foreach ($candidates as $label) {
            $id = (int)$db->getValue('SELECT t.id_parent FROM `'._DB_PREFIX_.'tab` t JOIN `'._DB_PREFIX_.'tab_lang` tl ON (tl.id_tab=t.id_tab) WHERE tl.name="'.pSQL($label).'"');
            if ($id > 0) return $id;
        }
        $fallback = (int)Tab::getIdFromClassName('AdminCatalog');
        if ($fallback > 0) return $fallback;
        $mods = (int)Tab::getIdFromClassName('AdminParentModulesSf');
        return $mods > 0 ? $mods : 0;
    }

    public function __construct()
    {
        $this->name = 'wyprzedaz';
        $this->tab = 'administration';
        $this->version = '3.3.1'; // Zwiększona wersja po modyfikacjach
        $this->author = 'Twoje Imię/Nazwa Firmy';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.1.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Zarządzanie Wyprzedażą');
        $this->description = $this->l('Moduł do zarządzania produktami wyprzedażowymi na podstawie importu z pliku CSV.');

        $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować ten moduł? Spowoduje to usunięcie tabel modułu!');
    }

    /**
     * Stworzenie zakładki w menu panelu admina i nowej tabeli w bazie
     */
    public function install()
    {
        if (!parent::install()) { return false; }

        // Rejestracja hooków
        $this->registerHook('actionAdminControllerSetMedia');

        // Zakładka BO
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminWyprzedazManager';
        $tab->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Zarządzanie Wyprzedażą';
        }
        $tab->id_parent = (int)$this->guessParentTabId();
        $tab->module = $this->name;
        $tab->add();

        // Konfiguracja domyślna
        Configuration::updateValue('WYPRZEDAZ_IMPORT_BATCH', 500);
        Configuration::updateValue('WYPRZEDAZ_FINALIZE_BATCH', 100);
        Configuration::updateValue('WYPRZEDAZ_SHORT_DATE_DAYS', 14);

        // Tabele
        $prefix = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        
        // staging
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'.$prefix.'wyprzedaz_csv_staging`');
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedaz_csv_staging` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `session_id` VARCHAR(64) NOT NULL,
            `ean` VARCHAR(32) NOT NULL,
            `quantity` INT NOT NULL,
            `receipt_date` DATE NULL,
            `expiry_date` DATE NULL,
            `regal` VARCHAR(64) NULL,
            `polka` VARCHAR(64) NULL,
            PRIMARY KEY (`id`),
            KEY `session_idx` (`session_id`),
            KEY `ean_idx` (`ean`),
            KEY `ses_ean_date` (`session_id`, `ean`, `expiry_date`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        // finalize tasks
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'.$prefix.'wyprzedaz_finalize_tasks`');
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedaz_finalize_tasks` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `session_id` VARCHAR(64) NOT NULL,
            `ean` VARCHAR(32) NOT NULL,
            `quantity` INT NOT NULL,
            `receipt_date` DATE NULL,
            `expiry_date` DATE NULL,
            `regal` VARCHAR(64) NULL,
            `polka` VARCHAR(64) NULL,
            `status` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `session_idx` (`session_id`),
            KEY `ses_status` (`session_id`,`status`),
            KEY `ses_ean_date` (`session_id`,`ean`,`expiry_date`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        // duplikaty
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedaz_csv_duplikaty` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ean` VARCHAR(32) NOT NULL,
            `quantity` INT NOT NULL,
            `receipt_date` DATE NULL,
            `expiry_date` DATE NULL,
            `regal` VARCHAR(64) NULL,
            `polka` VARCHAR(64) NULL,
            PRIMARY KEY (`id`),
            KEY `ean_idx` (`ean`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        // product_details
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedaz_product_details` (
            `id_product` INT UNSIGNED NOT NULL,
            `expiry_date` DATE NULL,
            `receipt_date` DATE NULL,
            `regal` VARCHAR(64) NULL,
            `polka` VARCHAR(64) NULL,
            `ean` VARCHAR(32) NULL,
            PRIMARY KEY (`id_product`),
            KEY `ean_idx` (`ean`)
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        // not found
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'.$prefix.'wyprzedaz_not_found_products` (
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
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        // POPRAWKA: Dodano tworzenie tabeli `wyprzedaz_import_history` podczas instalacji
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . $prefix . 'wyprzedaz_import_history` (
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
        ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');


        Configuration::updateValue('WYPRZEDAZ_DEBUG', 1);
        return true;
    }

    /**
     * Usunięcie zakładki z menu i naszych tabel z bazy
     */
    public function uninstall()
    {
        // Usunięcie zakładki z menu
        $tabId = (int) Tab::getIdFromClassName('AdminWyprzedazManager');
        if ($tabId) {
            $tab = new Tab($tabId);
            $tab->delete();
        }

        // POPRAWKA: Dodano wszystkie tabely do usunięcia
        $tables_to_drop = [
            'wyprzedaz_product_details',
            'wyprzedaz_csv_duplikaty',
            'wyprzedaz_not_found_products',
            'wyprzedaz_csv_staging',
            'wyprzedaz_finalize_tasks',
            'wyprzedaz_import_history'
        ];

        foreach ($tables_to_drop as $table) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`;');
        }

        // Wywołanie deinstalatora nadrzędnego
        if (!parent::uninstall()) {
            return false;
        }
        
        return true;
    }

    /**
     * Ta metoda jest wywoływana po kliknięciu "Konfiguruj"
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminWyprzedazManager')
        );
    }
    
    /**
     * Hook do dodawania CSS/JS
     */
    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('controller') === 'AdminWyprzedazManager') {
            $this->context->controller->addCSS($this->getPathUri().'views/css/back.css');
            $this->context->controller->addJS($this->getPathUri().'views/js/back.js');
        }
    }
}