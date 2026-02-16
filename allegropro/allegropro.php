<?php
/**
 * ALLEGRO PRO - PrestaShop 8.x
 * (c) BigBio
 * Wersja 2.1.1 - Added "No Payment" Status
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use AllegroPro\Service\OrderDetailsProvider;

class AllegroPro extends Module
{
    public function __construct()
    {
        $this->name = 'allegropro';
        $this->tab = 'administration';
        $this->version = '2.1.4';
        $this->author = 'BigBio';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ALLEGRO PRO');
        $this->description = $this->l('Integracja Allegro: Zamówienia, Płatności, Zaawansowana Wysyłka (Pro).');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        $this->registerAutoloader();
    }

    private function registerAutoloader()
    {
        $baseDir = __DIR__ . '/src/';
        spl_autoload_register(function ($class) use ($baseDir) {
            $prefix = 'AllegroPro\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }
            $rel = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $rel) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });
    }

    /**
     * Zapewnia kompatybilność schematu DB po aktualizacjach modułu.
     * Dodaje brakujące kolumny bez konieczności reinstalacji.
     */
    public function ensureDbSchema(): void
    {
        try {
            $db = \Db::getInstance();
            $p = _DB_PREFIX_;
            $table = $p . 'allegropro_account';

            $cols = $db->executeS('SHOW COLUMNS FROM `' . pSQL($table) . '`');
            $fields = [];
            if (is_array($cols)) {
                foreach ($cols as $c) {
                    if (isset($c['Field'])) $fields[] = $c['Field'];
                }
            }

            if (!in_array('shipx_token', $fields, true)) {
                $db->execute('ALTER TABLE `' . pSQL($table) . '` ADD `shipx_token` TEXT NULL AFTER `oauth_state`');
            }
            if (!in_array('shipx_token_updated_at', $fields, true)) {
                $db->execute('ALTER TABLE `' . pSQL($table) . '` ADD `shipx_token_updated_at` DATETIME NULL AFTER `shipx_token`');
            }
        } catch (\Throwable $e) {
            // Nie przerywamy działania modułu - schemat będzie można poprawić ręcznie.
        }
    }


    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->installDb()) {
            return false;
        }
        $this->ensureDbSchema();

        if (!$this->installTabs()) {
            return false;
        }
        
        if (!$this->installOrderStates()) {
            return false;
        }

        if (!$this->installCarrier()) {
            return false;
        }

        if (!$this->registerHook('displayAdminOrderMain')) {
            return false;
        }

        \Configuration::updateValue('ALLEGROPRO_ENV', 'prod');
        return true;
    }

    public function uninstall()
    {
        $this->uninstallTabs();
        $this->uninstallDb();

        \Configuration::deleteByName('ALLEGROPRO_ENV');
        \Configuration::deleteByName('ALLEGROPRO_CLIENT_ID');
        \Configuration::deleteByName('ALLEGROPRO_CLIENT_SECRET');
        \Configuration::deleteByName('ALLEGROPRO_CARRIER_ID'); 
        
        // Statusy
        \Configuration::deleteByName('ALLEGROPRO_OS_PAID');
        \Configuration::deleteByName('ALLEGROPRO_OS_PROCESSING');
        \Configuration::deleteByName('ALLEGROPRO_OS_CANCELLED');
        \Configuration::deleteByName('ALLEGROPRO_OS_NO_PAYMENT');
        
        return parent::uninstall();
    }

    public function hookDisplayAdminOrderMain($params)
    {
        $id_order = (int)($params['id_order'] ?? 0);
        if (!$id_order) {
            return '';
        }

        $provider = new OrderDetailsProvider();
        $allegroData = $provider->getAllegroDataByPsOrderId($id_order);

        if (!$allegroData || empty($allegroData['order'])) {
            return '';
        }

        $this->context->smarty->assign([
            'allegro_data' => $allegroData,
        ]);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/hook/admin_order_details.tpl');
    }

    public function installCarrier()
    {
        $id = (int)\Configuration::get('ALLEGROPRO_CARRIER_ID');
        if ($id) {
            $carrier = new \Carrier($id);
            if (\Validate::isLoadedObject($carrier) && !$carrier->deleted) {
                return true;
            }
        }

        $carrier = new \Carrier();
        $carrier->name = 'Wysyłka Allegro';
        $carrier->is_module = true;
        $carrier->active = 0; 
        $carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = 2;

        foreach (\Language::getLanguages(true) as $lang) {
            $carrier->delay[$lang['id_lang']] = 'Dostawa Allegro';
        }

        if ($carrier->add()) {
            $groups = \Group::getGroups(true);
            foreach ($groups as $group) {
                \Db::getInstance()->insert('carrier_group', ['id_carrier' => (int)$carrier->id, 'id_group' => (int)$group['id_group']]);
            }
            $rangePrice = new \RangePrice();
            $rangePrice->id_carrier = $carrier->id;
            $rangePrice->delimiter1 = '0';
            $rangePrice->delimiter2 = '1000000';
            $rangePrice->add();
            $rangeWeight = new \RangeWeight();
            $rangeWeight->id_carrier = $carrier->id;
            $rangeWeight->delimiter1 = '0';
            $rangeWeight->delimiter2 = '1000000';
            $rangeWeight->add();
            $zones = \Zone::getZones(true);
            foreach ($zones as $zone) {
                \Db::getInstance()->insert('carrier_zone', ['id_carrier' => (int)$carrier->id, 'id_zone' => (int)$zone['id_zone']]);
                \Db::getInstance()->insert('delivery', ['id_carrier' => (int)$carrier->id, 'id_range_price' => (int)$rangePrice->id, 'id_range_weight' => NULL, 'id_zone' => (int)$zone['id_zone'], 'price' => '0']);
                \Db::getInstance()->insert('delivery', ['id_carrier' => (int)$carrier->id, 'id_range_price' => NULL, 'id_range_weight' => (int)$rangeWeight->id, 'id_zone' => (int)$zone['id_zone'], 'price' => '0']);
            }
            \Configuration::updateValue('ALLEGROPRO_CARRIER_ID', (int)$carrier->id);
            return true;
        }
        return false;
    }

    private function installDb()
    {
        $db = \Db::getInstance();
        $p = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;
        $sql = [];

        // 1. KONTA
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_account` (`id_allegropro_account` INT UNSIGNED NOT NULL AUTO_INCREMENT, `label` VARCHAR(128) NOT NULL, `allegro_user_id` VARCHAR(64) NULL, `allegro_login` VARCHAR(128) NULL, `sandbox` TINYINT(1) DEFAULT 0, `active` TINYINT(1) DEFAULT 1, `is_default` TINYINT(1) DEFAULT 0, `access_token` TEXT NULL, `refresh_token` TEXT NULL, `token_expires_at` DATETIME NULL, `oauth_state` VARCHAR(80) NULL, `shipx_token` TEXT NULL, `shipx_token_updated_at` DATETIME NULL, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, PRIMARY KEY (`id_allegropro_account`)) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";
        
        // 2. ZAMÓWIENIA
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_order` (
            `id_allegropro_order` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
            `id_allegropro_account` INT UNSIGNED NOT NULL, 
            `checkout_form_id` VARCHAR(64) NOT NULL, 
            `status` VARCHAR(64) NULL, 
            `buyer_login` VARCHAR(128) NULL, 
            `buyer_email` VARCHAR(255) NULL, 
            `buyer_guest` TINYINT(1) DEFAULT 0, 
            `message_to_seller` TEXT NULL, 
            `total_amount` DECIMAL(20,2) DEFAULT 0.00, 
            `currency` VARCHAR(3) DEFAULT 'PLN', 
            `created_at_allegro` DATETIME NULL, 
            `updated_at_allegro` DATETIME NULL, 
            `id_order_prestashop` INT UNSIGNED DEFAULT 0, 
            `is_finished` TINYINT(1) DEFAULT 0, 
            `date_add` DATETIME NOT NULL, 
            `date_upd` DATETIME NOT NULL, 
            PRIMARY KEY (`id_allegropro_order`), 
            UNIQUE KEY `uniq_cf` (`checkout_form_id`),
            KEY `idx_finished` (`is_finished`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";
        
        // 3. PRODUKTY
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_order_item` (`id_allegropro_item` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `checkout_form_id` VARCHAR(64) NOT NULL, `offer_id` VARCHAR(64) NOT NULL, `name` VARCHAR(512) NULL, `quantity` INT UNSIGNED DEFAULT 1, `price` DECIMAL(20,2) DEFAULT 0.00, `ean` VARCHAR(32) NULL, `reference_number` VARCHAR(64) NULL, `id_product` INT UNSIGNED DEFAULT 0, `id_product_attribute` INT UNSIGNED DEFAULT 0, `tax_rate` DECIMAL(10,2) DEFAULT 0.00, PRIMARY KEY (`id_allegropro_item`)) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";
        
        // 4. WYSYŁKA
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_order_shipping` (
            `id_allegropro_shipping` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
            `checkout_form_id` VARCHAR(64) NOT NULL, 
            `method_id` VARCHAR(64) NULL, 
            `method_name` VARCHAR(255) NULL, 
            `cost_amount` DECIMAL(20,2) DEFAULT 0.00, 
            `is_smart` TINYINT(1) DEFAULT 0, 
            `package_count` INT UNSIGNED DEFAULT 1, 
            `addr_name` VARCHAR(255) NULL, 
            `addr_street` VARCHAR(255) NULL, 
            `addr_city` VARCHAR(128) NULL, 
            `addr_zip` VARCHAR(20) NULL, 
            `addr_country` VARCHAR(3) DEFAULT 'PL', 
            `addr_phone` VARCHAR(32) NULL, 
            `pickup_point_id` VARCHAR(64) NULL, 
            `pickup_point_name` VARCHAR(255) NULL, 
            PRIMARY KEY (`id_allegropro_shipping`)) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";
        
        // 5. KUPUJĄCY
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_order_buyer` (`id_allegropro_buyer` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `checkout_form_id` VARCHAR(64) NOT NULL, `email` VARCHAR(255) NOT NULL, `login` VARCHAR(128) NOT NULL, `firstname` VARCHAR(128) NULL, `lastname` VARCHAR(128) NULL, `company_name` VARCHAR(255) NULL, `street` VARCHAR(255) NULL, `city` VARCHAR(128) NULL, `zip_code` VARCHAR(20) NULL, `country_code` VARCHAR(3) DEFAULT 'PL', `phone_number` VARCHAR(32) NULL, `tax_id` VARCHAR(32) NULL, PRIMARY KEY (`id_allegropro_buyer`), UNIQUE KEY `uniq_cf` (`checkout_form_id`)) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";
        
        // 6. PŁATNOŚCI
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_order_payment` (`id_allegropro_payment` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `checkout_form_id` VARCHAR(64) NOT NULL, `payment_id` VARCHAR(64) NULL, `paid_amount` DECIMAL(20,2) DEFAULT 0.00, `status` VARCHAR(32) NULL, `provider` VARCHAR(32) NULL, `finished_at` DATETIME NULL, PRIMARY KEY (`id_allegropro_payment`)) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";
        
        // 7. FAKTURY
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_order_invoice` (`id_allegropro_invoice` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `checkout_form_id` VARCHAR(64) NOT NULL, `company_name` VARCHAR(255) NULL, `tax_id` VARCHAR(32) NULL, `street` VARCHAR(255) NULL, `city` VARCHAR(128) NULL, `zip_code` VARCHAR(20) NULL, `country_code` VARCHAR(3) DEFAULT 'PL', `natural_person` TINYINT(1) DEFAULT 0, PRIMARY KEY (`id_allegropro_invoice`)) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";
        
        // 8. PRZESYŁKI
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_shipment` (
            `id_allegropro_shipment` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_allegropro_account` INT UNSIGNED NOT NULL,
            `checkout_form_id` VARCHAR(64) NOT NULL,
            `shipment_id` VARCHAR(64) NOT NULL,
            `tracking_number` VARCHAR(64) NULL,
            `wza_command_id` VARCHAR(36) NULL,
            `wza_shipment_uuid` VARCHAR(36) NULL,
            `carrier_mode` VARCHAR(32) DEFAULT 'BOX',
            `size_details` VARCHAR(32) NULL,
            `is_smart` TINYINT(1) DEFAULT 0,
            `status` VARCHAR(32) DEFAULT 'CREATED',
            `label_path` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            `status_changed_at` DATETIME NULL,
            PRIMARY KEY (`id_allegropro_shipment`),
            KEY `idx_cf` (`checkout_form_id`),
            KEY `idx_wza_cmd` (`wza_command_id`),
            KEY `idx_wza_uuid` (`wza_shipment_uuid`),
            KEY `idx_status_changed_at` (`status_changed_at`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";
        
        // 9. METODY DOSTAWY
        $sql[] = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_delivery_service` (`id_allegropro_delivery_service` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `id_allegropro_account` INT UNSIGNED NOT NULL, `delivery_method_id` VARCHAR(64) NOT NULL, `delivery_service_id` VARCHAR(64) NOT NULL, `credentials_id` VARCHAR(64) NULL, `name` VARCHAR(255) NULL, `carrier_id` VARCHAR(64) NULL, `owner` VARCHAR(16) NULL, `additional_properties_json` TEXT NULL, `updated_at` DATETIME NOT NULL, PRIMARY KEY (`id_allegropro_delivery_service`)) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";

        foreach ($sql as $q) {
            if (!$db->execute($q)) return false;
        }
        return true;
    }

    private function uninstallDb()
    {
        $db = \Db::getInstance();
        $p = _DB_PREFIX_;
        $tables = [
            'allegropro_order_item', 'allegropro_order_shipping', 'allegropro_order_buyer',
            'allegropro_order_payment', 'allegropro_order_invoice', 'allegropro_order',
            'allegropro_shipment', 'allegropro_delivery_service', 'allegropro_account'
        ];
        
        foreach ($tables as $t) {
            $db->execute("DROP TABLE IF EXISTS `{$p}{$t}`");
        }
        return true;
    }

    private function installOrderStates()
    {
        $states = [
            // --- ZMIANA: Dodano status BRAK WPŁATY ---
            ['config' => 'ALLEGROPRO_OS_NO_PAYMENT', 'name' => 'ALLEGRO PRO - BRAK WPŁATY', 'color' => '#8f8f8f', 'logable' => false, 'invoice' => false, 'shipped' => false, 'paid' => false, 'template' => ''],
            
            ['config' => 'ALLEGROPRO_OS_PAID', 'name' => 'ALLEGRO PRO - OPŁACONE', 'color' => '#108510', 'logable' => true, 'invoice' => true, 'shipped' => false, 'paid' => true, 'template' => 'payment'],
            ['config' => 'ALLEGROPRO_OS_PROCESSING', 'name' => 'ALLEGRO PRO - PRZETWARZANIE', 'color' => '#FF8C00', 'logable' => false, 'invoice' => false, 'shipped' => false, 'paid' => false, 'template' => ''],
            ['config' => 'ALLEGROPRO_OS_CANCELLED', 'name' => 'ALLEGRO PRO - ANULOWANE', 'color' => '#DC143C', 'logable' => false, 'invoice' => false, 'shipped' => false, 'paid' => false, 'template' => 'order_canceled']
        ];
        foreach ($states as $state) {
            $idState = (int)\Configuration::get($state['config']);
            if (!\Validate::isLoadedObject(new \OrderState($idState))) {
                $orderState = new \OrderState();
                $orderState->name = array_fill_keys(\Language::getIDs(false), $state['name']);
                $orderState->color = $state['color'];
                $orderState->logable = $state['logable'];
                $orderState->invoice = $state['invoice'];
                $orderState->shipped = $state['shipped'];
                $orderState->paid = $state['paid'];
                $orderState->template = array_fill_keys(\Language::getIDs(false), $state['template']);
                $orderState->send_email = !empty($state['template']);
                $orderState->module_name = $this->name;
                $orderState->unremovable = true;
                if ($orderState->add()) {
                    @copy(_PS_ROOT_DIR_.'/img/os/'.($state['paid'] ? '2.gif' : '6.gif'), _PS_ROOT_DIR_.'/img/os/'.$orderState->id.'.gif');
                    \Configuration::updateValue($state['config'], (int)$orderState->id);
                }
            }
        }
        return true;
    }

    private function installTabs($force = false)
    {
        $idParent = (int)\Tab::getIdFromClassName('AdminAllegroPro');
        if (!$idParent) {
            $tab = new \Tab(); $tab->active = 1; $tab->class_name = 'AdminAllegroPro'; $tab->name = array_fill_keys(\Language::getIDs(false), 'Allegro Pro'); $tab->id_parent = 0; $tab->module = $this->name; $tab->add();
            $idParent = (int)$tab->id;
        }
        $tabs = [['AdminAllegroProAccounts', 'Konta', 'people'], ['AdminAllegroProOrders', 'Zamówienia', 'receipt_long'], ['AdminAllegroProShipments', 'Przesyłki', 'local_shipping'], ['AdminAllegroProSettings', 'Ustawienia', 'settings']];
        foreach ($tabs as $t) {
            if (!\Tab::getIdFromClassName($t[0])) {
                $tab = new \Tab(); $tab->active = 1; $tab->class_name = $t[0]; $tab->name = array_fill_keys(\Language::getIDs(false), $t[1]); $tab->id_parent = $idParent; $tab->module = $this->name; $tab->icon = $t[2]; $tab->add();
            }
        }
        return true;
    }

    private function uninstallTabs()
    {
        $tabs = ['AdminAllegroPro','AdminAllegroProAccounts','AdminAllegroProOrders','AdminAllegroProShipments','AdminAllegroProSettings'];
        foreach ($tabs as $c) {
            $id = (int)\Tab::getIdFromClassName($c);
            if ($id) (new \Tab($id))->delete();
        }
        return true;
    }

    public function ensureTabs() {
        $this->installTabs();
    }
}