<?php
if (!defined('_PS_VERSION_')) { exit; }

class Gpsrcompliance extends Module
{
    public function __construct()
    {
        $this->name = 'gpsrcompliance';
        $this->version = '1.3.4';
        $this->author = 'Custom';
        $this->tab = 'administration';
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('GPSR Compliance (Full)');
        $this->description = $this->l('Producenci/Osoby odpowiedzialne, mapowanie marek, nadpisy per produkt, automatyczne parametry GPSR do Allegro (x13allegro).');
    }

    public function install()
    {
        if (!parent::install()) { return false; }

        $engine = _MYSQL_ENGINE_;
        $ok = true;

        $ok &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'gpsr_producer` (
                `id_gpsr_producer` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `alias` VARCHAR(255) NULL,
                `country` VARCHAR(64) NULL,
                `address` VARCHAR(255) NULL,
                `postcode` VARCHAR(32) NULL,
                `city` VARCHAR(128) NULL,
                `email` VARCHAR(255) NULL,
                `phone` VARCHAR(64) NULL,
                `info` TEXT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id_gpsr_producer`)
            ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
        $ok &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'gpsr_person` (
                `id_gpsr_person` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `alias` VARCHAR(255) NULL,
                `country` VARCHAR(64) NULL,
                `address` VARCHAR(255) NULL,
                `postcode` VARCHAR(32) NULL,
                `city` VARCHAR(128) NULL,
                `email` VARCHAR(255) NULL,
                `phone` VARCHAR(64) NULL,
                `info` TEXT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id_gpsr_person`)
            ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
        $ok &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'gpsr_brand_map` (
                `id_manufacturer` INT UNSIGNED NOT NULL,
                `id_gpsr_producer` INT UNSIGNED NULL,
                `id_gpsr_person` INT UNSIGNED NULL,
                PRIMARY KEY (`id_manufacturer`)
            ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
        $ok &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'gpsr_product` (
                `id_product` INT UNSIGNED NOT NULL,
                `id_gpsr_producer` INT UNSIGNED NULL,
                `id_gpsr_person` INT UNSIGNED NULL,
                `extra_info` TEXT NULL,
                PRIMARY KEY (`id_product`)
            ) ENGINE='.$engine.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
        if (!$ok) { return false; }

        Configuration::updateValue('GPSR_RESP_NAME', Configuration::get('PS_SHOP_NAME'));
        Configuration::updateValue('GPSR_RESP_ADDRESS', trim(Configuration::get('PS_SHOP_ADDR1').' '.Configuration::get('PS_SHOP_CODE').' '.Configuration::get('PS_SHOP_CITY')));
        Configuration::updateValue('GPSR_RESP_EMAIL', Configuration::get('PS_SHOP_EMAIL'));
        Configuration::updateValue('GPSR_RESP_PHONE', Configuration::get('PS_SHOP_PHONE'));
        Configuration::updateValue('GPSR_TEMPLATE', "Podmiot odpowiedzialny: {RESP_NAME}\nAdres: {RESP_ADDRESS}\nKontakt: {RESP_EMAIL}, {RESP_PHONE}\nInformacje: Produkt spełnia wymagania rozporządzenia (UE) 2023/988 (GPSR).\n");
        Configuration::updateValue('GPSR_SAFETY_SELECT_PARAM_ID', '');
        Configuration::updateValue('GPSR_SAFETY_SELECT_VALUE_YES_ID', '');
        Configuration::updateValue('GPSR_SAFETY_TEXT_PARAM_ID', '');
        Configuration::updateValue('GPSR_LOOKUP_PROVIDER', '');
        Configuration::updateValue('GPSR_SERPAPI_KEY', '');

        $this->safeRegisterHook('displayAdminProductsExtra');
        $this->safeRegisterHook('displayAdminProductsMainStepLeftColumnMiddle');
        $this->safeRegisterHook('displayAdminProductsMainStepLeftColumnBottom');

        $this->installTabs();

        return true;
    }

    public function uninstall()
    {
        $this->uninstallTabs();
        foreach (['GPSR_RESP_NAME','GPSR_RESP_ADDRESS','GPSR_RESP_EMAIL','GPSR_RESP_PHONE','GPSR_TEMPLATE',
                  'GPSR_SAFETY_SELECT_PARAM_ID','GPSR_SAFETY_SELECT_VALUE_YES_ID','GPSR_SAFETY_TEXT_PARAM_ID',
                  'GPSR_LOOKUP_PROVIDER','GPSR_SERPAPI_KEY'] as $k) { Configuration::deleteByName($k); }
        return parent::uninstall();
    }

    private function safeRegisterHook($hook)
    { if (Hook::getIdByName($hook)) { $this->registerHook($hook); } }

    private function findPreferredParent()
    {
        foreach (['AdminWyprzedazManager','AdminWyprzedazRoot','AdminWyprzedaz'] as $wy) {
            $id = (int)Tab::getIdFromClassName($wy);
            if ($id) { $tab = new Tab($id); return (int)$tab->id_parent ?: 0; }
        }
        foreach (['AdminAdvancedParameters','AdminParentModulesSf','AdminParentPreferences'] as $cn) {
            $id = (int)Tab::getIdFromClassName($cn);
            if ($id) return $id;
        }
        return 0;
    }

    public function installTabs()
    {
        $parentId = $this->findPreferredParent();

        $rootId = (int)Tab::getIdFromClassName('AdminGpsrRoot');
        if (!$rootId) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = 'AdminGpsrRoot';
            foreach (Language::getLanguages() as $l) { $tab->name[$l['id_lang']] = 'GPSR'; }
            $tab->id_parent = $parentId;
            $tab->module = $this->name;
            $tab->add();
            $rootId = (int)$tab->id;
        }
        $children = [
            ['AdminGpsrProducers', 'Producenci odpowiedzialni'],
            ['AdminGpsrPersons',   'Osoby odpowiedzialne'],
            ['AdminGpsrBrandMap',  'Mapowanie marek'],
            ['AdminGpsrConfig',    'Ustawienia GPSR'],
        ];
        foreach ($children as $c) {
            if (Tab::getIdFromClassName($c[0])) continue;
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $c[0];
            foreach (Language::getLanguages() as $l) { $tab->name[$l['id_lang']] = $c[1]; }
            $tab->id_parent = $rootId;
            $tab->module = $this->name;
            $tab->add();
        }
    }

    private function uninstallTabs()
    {
        foreach (['AdminGpsrBrandMap','AdminGpsrPersons','AdminGpsrProducers','AdminGpsrConfig','AdminGpsrRoot'] as $cn) {
            $id = (int)Tab::getIdFromClassName($cn);
            if ($id) { $t = new Tab($id); $t->delete(); }
        }
    }

    public function getContent()
    {
        $out = '';
        if (Tools::isSubmit('submitGpsr')) {
            Configuration::updateValue('GPSR_RESP_NAME', Tools::getValue('GPSR_RESP_NAME'));
            Configuration::updateValue('GPSR_RESP_ADDRESS', Tools::getValue('GPSR_RESP_ADDRESS'));
            Configuration::updateValue('GPSR_RESP_EMAIL', Tools::getValue('GPSR_RESP_EMAIL'));
            Configuration::updateValue('GPSR_RESP_PHONE', Tools::getValue('GPSR_RESP_PHONE'));
            Configuration::updateValue('GPSR_TEMPLATE', Tools::getValue('GPSR_TEMPLATE'));
            Configuration::updateValue('GPSR_SAFETY_SELECT_PARAM_ID', Tools::getValue('GPSR_SAFETY_SELECT_PARAM_ID'));
            Configuration::updateValue('GPSR_SAFETY_SELECT_VALUE_YES_ID', Tools::getValue('GPSR_SAFETY_SELECT_VALUE_YES_ID'));
            Configuration::updateValue('GPSR_SAFETY_TEXT_PARAM_ID', Tools::getValue('GPSR_SAFETY_TEXT_PARAM_ID'));
            Configuration::updateValue('GPSR_LOOKUP_PROVIDER', Tools::getValue('GPSR_LOOKUP_PROVIDER'));
            Configuration::updateValue('GPSR_SERPAPI_KEY', Tools::getValue('GPSR_SERPAPI_KEY'));
            $out .= $this->displayConfirmation($this->l('Zapisano.'));
        }
        $this->context->smarty->assign([
            'GPSR_RESP_NAME' => Configuration::get('GPSR_RESP_NAME'),
            'GPSR_RESP_ADDRESS' => Configuration::get('GPSR_RESP_ADDRESS'),
            'GPSR_RESP_EMAIL' => Configuration::get('GPSR_RESP_EMAIL'),
            'GPSR_RESP_PHONE' => Configuration::get('GPSR_RESP_PHONE'),
            'GPSR_TEMPLATE' => Configuration::get('GPSR_TEMPLATE'),
            'GPSR_SAFETY_SELECT_PARAM_ID' => Configuration::get('GPSR_SAFETY_SELECT_PARAM_ID'),
            'GPSR_SAFETY_SELECT_VALUE_YES_ID' => Configuration::get('GPSR_SAFETY_SELECT_VALUE_YES_ID'),
            'GPSR_SAFETY_TEXT_PARAM_ID' => Configuration::get('GPSR_SAFETY_TEXT_PARAM_ID'),
            'GPSR_LOOKUP_PROVIDER' => Configuration::get('GPSR_LOOKUP_PROVIDER'),
            'GPSR_SERPAPI_KEY' => Configuration::get('GPSR_SERPAPI_KEY'),
        ]);
        return $out.$this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        try {
            $id_product = 0;
            if (isset($params['id_product'])) { $id_product = (int)$params['id_product']; }
            elseif (Tools::getIsset('id_product')) { $id_product = (int)Tools::getValue('id_product'); }
            if ($id_product <= 0) { return ''; }

            require_once _PS_MODULE_DIR_.$this->name.'/classes/GpsrService.php';
            $svc = new GpsrService();
            $current = $svc->getProductRecord($id_product);
            $defaults = $svc->resolve($id_product);
            $this->context->smarty->assign([
                'gpsr_current' => $current ?: ['extra_info'=>''],
                'gpsr_default' => $defaults,
                'gpsr_producers' => $svc->getProducersOptions(),
                'gpsr_persons' => $svc->getPersonsOptions(),
            ]);
            return $this->display(__FILE__, 'views/templates/hook/product_extra.tpl');
        } catch (Exception $e) { return ''; }
    }
    public function hookDisplayAdminProductsMainStepLeftColumnMiddle($params) { return $this->hookDisplayAdminProductsExtra($params); }
    public function hookDisplayAdminProductsMainStepLeftColumnBottom($params) { return $this->hookDisplayAdminProductsExtra($params); }
}
