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
        $this->version = '2.1.7';
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
    // 1) Konto: kolumny dodawane po aktualizacjach
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

    // 2) Korespondencja: tabele (tworzymy bez reinstalacji)
    try {
        $db = \Db::getInstance();
        $p = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;

        $db->execute("CREATE TABLE IF NOT EXISTS `{$p}allegropro_msg_thread` (
            `id_allegropro_msg_thread` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_allegropro_account` INT UNSIGNED NOT NULL,
            `thread_id` VARCHAR(64) NOT NULL,
            `interlocutor_login` VARCHAR(128) NULL,
            `read` TINYINT(1) NOT NULL DEFAULT 1,
            `last_message_at` DATETIME NULL,
            `checkout_form_id` VARCHAR(64) NULL,
            `offer_id` VARCHAR(64) NULL,
            `payload_json` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_allegropro_msg_thread`),
            UNIQUE KEY `uniq_acc_thread` (`id_allegropro_account`,`thread_id`),
            KEY `idx_read` (`read`),
            KEY `idx_last_message_at` (`last_message_at`),
            KEY `idx_checkout_form_id` (`checkout_form_id`),
            KEY `idx_offer_id` (`offer_id`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;");

        $db->execute("CREATE TABLE IF NOT EXISTS `{$p}allegropro_msg_message` (
            `id_allegropro_msg_message` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_allegropro_account` INT UNSIGNED NOT NULL,
            `thread_id` VARCHAR(64) NOT NULL,
            `message_id` VARCHAR(64) NOT NULL,
            `created_at_allegro` DATETIME NULL,
            `author_login` VARCHAR(128) NULL,
            `author_is_interlocutor` TINYINT(1) NOT NULL DEFAULT 0,
            `text` LONGTEXT NULL,
            `has_attachments` TINYINT(1) NOT NULL DEFAULT 0,
            `attachments_json` LONGTEXT NULL,
            `payload_json` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_allegropro_msg_message`),
            UNIQUE KEY `uniq_acc_msg` (`id_allegropro_account`,`message_id`),
            KEY `idx_thread` (`thread_id`),
            KEY `idx_created_at_allegro` (`created_at_allegro`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;");

        $db->execute("CREATE TABLE IF NOT EXISTS `{$p}allegropro_issue` (
            `id_allegropro_issue` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_allegropro_account` INT UNSIGNED NOT NULL,
            `issue_id` VARCHAR(64) NOT NULL,
            `type` VARCHAR(16) NOT NULL,
            `status` VARCHAR(32) NOT NULL,
            `checkout_form_id` VARCHAR(64) NULL,
            `buyer_login` VARCHAR(128) NULL,
            `created_at_allegro` DATETIME NULL,
            `updated_at_allegro` DATETIME NULL,
            `last_message_status` VARCHAR(32) NULL,
            `last_message_at` DATETIME NULL,
            `decision_due_date` DATETIME NULL,
            `status_due_date` DATETIME NULL,
            `return_required` TINYINT(1) NOT NULL DEFAULT 0,
            `right_type` VARCHAR(16) NULL,
            `exp_refund` TINYINT(1) NOT NULL DEFAULT 0,
            `exp_partial_refund` TINYINT(1) NOT NULL DEFAULT 0,
            `exp_exchange` TINYINT(1) NOT NULL DEFAULT 0,
            `exp_repair` TINYINT(1) NOT NULL DEFAULT 0,
            `payload_json` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_allegropro_issue`),
            UNIQUE KEY `uniq_acc_issue` (`id_allegropro_account`,`issue_id`),
            KEY `idx_type` (`type`),
            KEY `idx_status` (`status`),
            KEY `idx_checkout_form_id` (`checkout_form_id`),
            KEY `idx_buyer_login` (`buyer_login`),
            KEY `idx_last_message_status` (`last_message_status`),
            KEY `idx_due_dates` (`status_due_date`, `decision_due_date`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;");

        $db->execute("CREATE TABLE IF NOT EXISTS `{$p}allegropro_issue_chat` (
            `id_allegropro_issue_chat` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_allegropro_account` INT UNSIGNED NOT NULL,
            `issue_id` VARCHAR(64) NOT NULL,
            `msg_uid` VARCHAR(80) NOT NULL,
            `created_at_allegro` DATETIME NULL,
            `author_role` VARCHAR(32) NULL,
            `text` LONGTEXT NULL,
            `has_attachments` TINYINT(1) NOT NULL DEFAULT 0,
            `attachments_json` LONGTEXT NULL,
            `payload_json` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_allegropro_issue_chat`),
            UNIQUE KEY `uniq_acc_uid` (`id_allegropro_account`,`msg_uid`),
            KEY `idx_issue_id` (`issue_id`),
            KEY `idx_created_at_allegro` (`created_at_allegro`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;");

        // === Uzupełnienie schematu: flagi synchronizacji (delta sync) ===
        // Używamy tego, aby móc odróżnić rekordy, które już zostały pobrane i obsługiwane przez moduł.
        // (Dodatkowo pozwala to na szybszą synchronizację przy wejściu w Korespondencję.)
        $mt = $p . 'allegropro_msg_thread';
        $cols = $db->executeS('SHOW COLUMNS FROM `' . pSQL($mt) . '`');
        $fields = [];
        if (is_array($cols)) {
            foreach ($cols as $c) {
                if (isset($c['Field'])) $fields[] = $c['Field'];
            }
        }
        if (!in_array('is_synced', $fields, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mt) . '` ADD `is_synced` TINYINT(1) NOT NULL DEFAULT 0 AFTER `offer_id`');
            $db->execute('UPDATE `' . pSQL($mt) . '` SET `is_synced` = 1 WHERE `is_synced` = 0');
        }
        if (!in_array('synced_at', $fields, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mt) . '` ADD `synced_at` DATETIME NULL AFTER `is_synced`');
            $db->execute('UPDATE `' . pSQL($mt) . '` SET `synced_at` = `updated_at` WHERE `synced_at` IS NULL');
        }

        // Status pełnej synchronizacji wiadomości w danym wątku (żeby delta nie "ucinała" historii).
        // messages_sync_complete = 1 oznacza, że dla bieżącego zakresu (miesiące) mamy pełny chat w DB.
        // messages_sync_months przechowuje zakres (ile miesięcy), dla którego oznaczono kompletność.
        if (!in_array('messages_sync_complete', $fields, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mt) . '` ADD `messages_sync_complete` TINYINT(1) NOT NULL DEFAULT 0 AFTER `synced_at`');
        }
        if (!in_array('messages_sync_months', $fields, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mt) . '` ADD `messages_sync_months` INT UNSIGNED NULL AFTER `messages_sync_complete`');
        }

        // Dodatkowe pola do filtrowania i priorytetyzacji wiadomości (UI po lewej stronie).
        // Uzupełniamy je po synchronizacji wiadomości w wątku (delta/prefetch).
        if (!in_array('need_reply', $fields, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mt) . '` ADD `need_reply` TINYINT(1) NOT NULL DEFAULT 0 AFTER `messages_sync_months`');
        }
        if (!in_array('has_attachments', $fields, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mt) . '` ADD `has_attachments` TINYINT(1) NOT NULL DEFAULT 0 AFTER `need_reply`');
        }
        if (!in_array('last_interlocutor_at', $fields, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mt) . '` ADD `last_interlocutor_at` DATETIME NULL AFTER `has_attachments`');
        }
        if (!in_array('last_seller_at', $fields, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mt) . '` ADD `last_seller_at` DATETIME NULL AFTER `last_interlocutor_at`');
        }
        if (!in_array('derived_updated_at', $fields, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mt) . '` ADD `derived_updated_at` DATETIME NULL AFTER `last_seller_at`');
        }

        // Ustawienia: ile wątków "segregować" (uzupełnić pola pochodne) podczas synchronizacji.
        // 0 = wyłączone.
        if (\Configuration::get('ALLEGROPRO_CORR_PREFETCH_THREADS') === false) {
            \Configuration::updateValue('ALLEGROPRO_CORR_PREFETCH_THREADS', 200);
        }

        $it = $p . 'allegropro_issue';
        $cols2 = $db->executeS('SHOW COLUMNS FROM `' . pSQL($it) . '`');
        $fields2 = [];
        if (is_array($cols2)) {
            foreach ($cols2 as $c) {
                if (isset($c['Field'])) $fields2[] = $c['Field'];
            }
        }
        if (!in_array('is_synced', $fields2, true)) {
            $db->execute('ALTER TABLE `' . pSQL($it) . '` ADD `is_synced` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payload_json`');
            $db->execute('UPDATE `' . pSQL($it) . '` SET `is_synced` = 1 WHERE `is_synced` = 0');
        }
        if (!in_array('synced_at', $fields2, true)) {
            $db->execute('ALTER TABLE `' . pSQL($it) . '` ADD `synced_at` DATETIME NULL AFTER `is_synced`');
            $db->execute('UPDATE `' . pSQL($it) . '` SET `synced_at` = `updated_at` WHERE `synced_at` IS NULL');
        }

        // Flagi synchronizacji dla wiadomości w wątku (messages)
        $mm = $p . 'allegropro_msg_message';
        $cols3 = $db->executeS('SHOW COLUMNS FROM `' . pSQL($mm) . '`');
        $fields3 = [];
        if (is_array($cols3)) {
            foreach ($cols3 as $c) {
                if (isset($c['Field'])) $fields3[] = $c['Field'];
            }
        }
        if (!in_array('is_synced', $fields3, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mm) . '` ADD `is_synced` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payload_json`');
            $db->execute('UPDATE `' . pSQL($mm) . '` SET `is_synced` = 1 WHERE `is_synced` = 0');
        }
        if (!in_array('synced_at', $fields3, true)) {
            $db->execute('ALTER TABLE `' . pSQL($mm) . '` ADD `synced_at` DATETIME NULL AFTER `is_synced`');
            $db->execute('UPDATE `' . pSQL($mm) . '` SET `synced_at` = `updated_at` WHERE `synced_at` IS NULL');
        }

        // Flagi synchronizacji dla chatu issues
        $ic = $p . 'allegropro_issue_chat';
        $cols4 = $db->executeS('SHOW COLUMNS FROM `' . pSQL($ic) . '`');
        $fields4 = [];
        if (is_array($cols4)) {
            foreach ($cols4 as $c) {
                if (isset($c['Field'])) $fields4[] = $c['Field'];
            }
        }
        if (!in_array('is_synced', $fields4, true)) {
            $db->execute('ALTER TABLE `' . pSQL($ic) . '` ADD `is_synced` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payload_json`');
            $db->execute('UPDATE `' . pSQL($ic) . '` SET `is_synced` = 1 WHERE `is_synced` = 0');
        }
        if (!in_array('synced_at', $fields4, true)) {
            $db->execute('ALTER TABLE `' . pSQL($ic) . '` ADD `synced_at` DATETIME NULL AFTER `is_synced`');
            $db->execute('UPDATE `' . pSQL($ic) . '` SET `synced_at` = `updated_at` WHERE `synced_at` IS NULL');
        }


    } catch (\Throwable $e) {
        // nie blokujemy działania modułu
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

        // Hooki dla modułu (w tym Korespondencja – otwieranie w nowym oknie)
        if (!$this->registerHook('displayBackOfficeHeader')) {
            return false;
        }
        if (!$this->registerHook('actionAdminControllerSetMedia')) {
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
            `wza_command_id` VARCHAR(64) NULL,
            `wza_shipment_uuid` VARCHAR(64) NULL,
            `wza_label_shipment_id` VARCHAR(64) NULL,
            `carrier_mode` VARCHAR(64) DEFAULT 'BOX',
            `size_details` VARCHAR(64) NULL,
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
            KEY `idx_wza_label_shipment_id` (`wza_label_shipment_id`),
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
        $tabs = [['AdminAllegroProAccounts', 'Konta', 'people'], ['AdminAllegroProOrders', 'Zamówienia', 'receipt_long'], ['AdminAllegroProCorrespondence', 'Korespondencja', 'forum'], ['AdminAllegroProSettlements', 'Rozliczenia', 'paid'], ['AdminAllegroProCashflows', 'Przepływy środków', 'swap_horiz'], ['AdminAllegroProShipments', 'Przesyłki', 'local_shipping'], ['AdminAllegroProReturns', 'Zwroty', 'assignment_return'], ['AdminAllegroProSettings', 'Ustawienia', 'settings']];
        foreach ($tabs as $t) {
            if (!\Tab::getIdFromClassName($t[0])) {
                $tab = new \Tab(); $tab->active = 1; $tab->class_name = $t[0]; $tab->name = array_fill_keys(\Language::getIDs(false), $t[1]); $tab->id_parent = $idParent; $tab->module = $this->name; $tab->icon = $t[2]; $tab->add();
            }
        }
        return true;
    }

    private function uninstallTabs()
    {
        $tabs = ['AdminAllegroPro','AdminAllegroProAccounts','AdminAllegroProOrders','AdminAllegroProCorrespondence','AdminAllegroProSettlements','AdminAllegroProCashflows','AdminAllegroProShipments','AdminAllegroProReturns','AdminAllegroProSettings'];
        foreach ($tabs as $c) {
            $id = (int)\Tab::getIdFromClassName($c);
            if ($id) (new \Tab($id))->delete();
        }
        return true;
    }

    public function ensureTabs() {
        $this->installTabs();
        $this->ensureHooks();
    }


    /**
     * Rejestruje brakujące hooki bez reinstalacji modułu (np. po aktualizacji plików).
     */
    public function ensureHooks(): void
    {
        try {
            // Rejestracja jest idempotentna – jeśli hook już jest podpięty, nic się nie stanie.
            $this->registerHook('displayBackOfficeHeader');
            $this->registerHook('actionAdminControllerSetMedia');
        } catch (\Throwable $e) {
            // nie blokujemy działania modułu
        }
    }

    /**
     * BO: dopinamy JS, który ustawia target=_blank dla zakładki Korespondencja
     * (żeby klik w menu otwierał nową kartę/okno).
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        $src = $this->_path . 'views/js/admin/correspondence_menu.js?v=' . rawurlencode((string)$this->version);
        return '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"></script>';
    }

    /**
     * Legacy BO: alternatywne dołączanie zasobów (na wszelki wypadek).
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        try {
            if (isset($this->context->controller) && method_exists($this->context->controller, 'addJS')) {
                $this->context->controller->addJS($this->_path . 'views/js/admin/correspondence_menu.js');
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Generuje podpisane parametry do bezpiecznego przekierowania z BO -> strona "app" na froncie.
     * To jest szybki "bridge" (krótki TTL), żeby nie udostępniać nic stałego publicznie.
     */
    public function generateBoBridgeParams(?int $employeeId = null, int $ttlSeconds = 300): array
    {
        $eid = $employeeId !== null ? (int)$employeeId : (int)($this->context->employee->id ?? 0);
        $ts = time();
        // podpinamy TTL do sygnatury, żeby łatwo było zmienić po stronie walidacji
        $payload = $eid . '|' . $ts . '|' . (int)$ttlSeconds;
        $sig = hash_hmac('sha256', $payload, _COOKIE_KEY_);
        return [
            'eid' => $eid,
            'ts' => $ts,
            'ttl' => (int)$ttlSeconds,
            'sig' => $sig,
        ];
    }

    /**
     * Waliduje parametry bridge BO->FO.
     */
    public function validateBoBridgeParams($eid, $ts, $ttl, $sig): bool
    {
        $eid = (int)$eid;
        $ts = (int)$ts;
        $ttl = (int)$ttl;
        $sig = (string)$sig;

        if ($eid <= 0 || $ts <= 0 || $ttl <= 0 || $sig === '') {
            return false;
        }

        if (abs(time() - $ts) > $ttl) {
            return false;
        }

        $payload = $eid . '|' . $ts . '|' . $ttl;
        $expected = hash_hmac('sha256', $payload, _COOKIE_KEY_);
        return hash_equals($expected, $sig);
    }

}