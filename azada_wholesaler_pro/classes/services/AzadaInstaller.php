<?php

class AzadaInstaller
{
    public static function installDatabase()
    {
        $sql = [];

        // 2. Integracja (Konfiguracja)
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_integration` (
            `id_wholesaler` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `active` tinyint(1) DEFAULT 1,
            `raw_table_name` varchar(64) DEFAULT NULL,
            `file_url` text NOT NULL,
            `file_format` varchar(10) DEFAULT "csv",
            `delimiter` varchar(5) DEFAULT ";",
            `encoding` varchar(20) DEFAULT "UTF-8",
            `skip_header` int(2) DEFAULT 1,
            `api_key` varchar(255) DEFAULT NULL,
            `b2b_login` varchar(255) DEFAULT NULL,
            `b2b_password` varchar(255) DEFAULT NULL,
            `connection_status` tinyint(1) DEFAULT 0,
            `diagnostic_result` TEXT DEFAULT NULL,
            `last_import` datetime DEFAULT NULL,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_wholesaler`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 3. Mapowanie
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_mapping` (
            `id_mapping` int(11) NOT NULL AUTO_INCREMENT,
            `id_wholesaler` int(11) NOT NULL,
            `csv_column` varchar(255) NOT NULL,
            `ps_target` varchar(255) NOT NULL,
            `logic_type` varchar(50) DEFAULT "simple",
            `logic_value` text DEFAULT NULL,
            `is_identifier` tinyint(1) DEFAULT 0,
            PRIMARY KEY (`id_mapping`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 4. Cache Produktów
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_cache` (
            `id_product_cache` int(11) NOT NULL AUTO_INCREMENT,
            `id_wholesaler` int(11) NOT NULL,
            `reference` varchar(64) DEFAULT NULL,
            `ean13` varchar(13) DEFAULT NULL,
            `name` varchar(255) DEFAULT NULL,
            `price_tax_excl` decimal(20,6) DEFAULT 0.000000,
            `quantity` int(11) DEFAULT 0,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_product_cache`),
            KEY `id_wholesaler` (`id_wholesaler`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 5. Logi Systemowe
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_logs` (
            `id_log` int(11) NOT NULL AUTO_INCREMENT,
            `severity` tinyint(1) NOT NULL DEFAULT 1,
            `source` varchar(50) DEFAULT NULL,
            `title` varchar(255) DEFAULT NULL,
            `details` longtext,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_log`),
            KEY `date_add` (`date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 6. Pliki Zamówień (Nagłówki)
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_order_files` (
            `id_file` int(11) NOT NULL AUTO_INCREMENT,
            `id_wholesaler` int(11) NOT NULL,
            `external_doc_number` varchar(100) DEFAULT NULL,
            `doc_date` date DEFAULT NULL,
            `amount_netto` decimal(20,2) DEFAULT 0.00,
            `file_name` varchar(255) DEFAULT NULL,
            `download_hash` varchar(255) DEFAULT NULL,
            `status` varchar(50) DEFAULT NULL,
            `is_downloaded` tinyint(1) DEFAULT 0,
            `is_verified_with_invoice` tinyint(1) DEFAULT 0,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_file`),
            KEY `id_wholesaler` (`id_wholesaler`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 7. Detale Zamówień
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_order_details` (
            `id_detail` int(11) NOT NULL AUTO_INCREMENT,
            `id_file` int(11) NOT NULL,
            `doc_number` varchar(100) NOT NULL,
            `sku_wholesaler` varchar(64) DEFAULT NULL,
            `product_id` varchar(50) DEFAULT NULL,
            `ean` varchar(50) DEFAULT NULL,
            `name` varchar(255) DEFAULT NULL,
            `quantity` int(11) DEFAULT 0,
            `original_csv_qty` int(11) DEFAULT NULL,
            `correction_info` varchar(255) DEFAULT NULL,
            `invoice_qty` int(11) DEFAULT 0,
            `unit` varchar(20) DEFAULT NULL,
            `price_net` decimal(20,2) DEFAULT 0.00,
            `value_net` decimal(20,2) DEFAULT 0.00,
            `vat_rate` int(11) DEFAULT 0,
            `price_gross` decimal(20,2) DEFAULT 0.00,
            `value_gross` decimal(20,2) DEFAULT 0.00,
            `fv_price_net` decimal(20,2) DEFAULT 0.00,
            `fv_value_net` decimal(20,2) DEFAULT 0.00,
            `fv_price_gross` decimal(20,2) DEFAULT 0.00,
            `fv_value_gross` decimal(20,2) DEFAULT 0.00,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_detail`),
            KEY `id_file` (`id_file`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 8. Pliki Faktur (Nagłówki)
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_invoice_files` (
            `id_invoice` int(11) NOT NULL AUTO_INCREMENT,
            `id_wholesaler` int(11) NOT NULL,
            `doc_number` varchar(100) DEFAULT NULL,
            `doc_date` varchar(50) DEFAULT NULL,
            `amount_netto` varchar(50) DEFAULT NULL,
            `payment_deadline` varchar(50) DEFAULT NULL,
            `is_paid` tinyint(1) DEFAULT 0,
            `file_name` varchar(255) DEFAULT NULL,
            `is_downloaded` tinyint(1) DEFAULT 0,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_invoice`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 9. Detale Faktur
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_invoice_details` (
            `id_detail` int(11) NOT NULL AUTO_INCREMENT,
            `id_invoice` int(11) NOT NULL,
            `doc_number` varchar(100) DEFAULT NULL,
            `sku_wholesaler` varchar(64) DEFAULT NULL,
            `product_id` varchar(50) DEFAULT NULL,
            `ean` varchar(50) DEFAULT NULL,
            `name` varchar(255) DEFAULT NULL,
            `quantity` varchar(50) DEFAULT NULL,
            `unit` varchar(20) DEFAULT NULL,
            `price_net` varchar(50) DEFAULT NULL,
            `value_net` varchar(50) DEFAULT NULL,
            `vat_rate` varchar(20) DEFAULT NULL,
            `price_gross` varchar(50) DEFAULT NULL,
            `value_gross` varchar(50) DEFAULT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_detail`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 10. Analiza
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_analysis` (
            `id_analysis` int(11) NOT NULL AUTO_INCREMENT,
            `id_wholesaler` int(11) NOT NULL,
            `doc_number_invoice` varchar(100) DEFAULT NULL,
            `source_orders` text DEFAULT NULL,
            `summary` text DEFAULT NULL,
            `is_ok` tinyint(1) DEFAULT 0,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_analysis`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 11. Różnice analizy
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_analysis_diff` (
            `id_diff` int(11) NOT NULL AUTO_INCREMENT,
            `id_analysis` int(11) NOT NULL,
            `doc_number_invoice` varchar(100) DEFAULT NULL,
            `source_orders` text DEFAULT NULL,
            `wholesaler_sku` varchar(64) DEFAULT NULL,
            `product_identifier` varchar(64) DEFAULT NULL,
            `product_name` varchar(255) DEFAULT NULL,
            `error_type` varchar(50) NOT NULL,
            `val_invoice` varchar(255) DEFAULT NULL,
            `val_order` varchar(255) DEFAULT NULL,
            `diff_val` varchar(50) DEFAULT NULL,
            PRIMARY KEY (`id_diff`),
            KEY `id_analysis` (`id_analysis`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


        // 12. Pochodzenie produktów utworzonych przez moduł


        // 13. Mapowanie kategorii hurtownia -> sklep
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_category_map` (
            `id_category_map` int(11) NOT NULL AUTO_INCREMENT,
            `source_table` varchar(64) NOT NULL,
            `source_category` varchar(255) NOT NULL,
            `source_type` varchar(16) NOT NULL DEFAULT \'category\',
            `ps_category_ids` text DEFAULT NULL,
            `id_category_default` int(11) DEFAULT 0,
            `category_markup_percent` decimal(10,2) NOT NULL DEFAULT 0.00,
            `is_active` tinyint(1) NOT NULL DEFAULT 0,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_category_map`),
            UNIQUE KEY `uniq_source_category` (`source_table`,`source_category`),
            KEY `idx_default_category` (`id_category_default`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_product_origin` (
            `id_origin` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `source_table` varchar(64) DEFAULT NULL,
            `ean13` varchar(64) DEFAULT NULL,
            `reference` varchar(64) DEFAULT NULL,
            `created_by_module` tinyint(1) NOT NULL DEFAULT 1,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_origin`),
            UNIQUE KEY `uniq_id_product` (`id_product`),
            KEY `idx_source_table` (`source_table`),
            KEY `idx_ean13` (`ean13`),
            KEY `idx_reference` (`reference`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }


    public static function ensureProductOriginTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_product_origin` (
            `id_origin` int(11) NOT NULL AUTO_INCREMENT,
            `id_product` int(11) NOT NULL,
            `source_table` varchar(64) DEFAULT NULL,
            `ean13` varchar(64) DEFAULT NULL,
            `reference` varchar(64) DEFAULT NULL,
            `created_by_module` tinyint(1) NOT NULL DEFAULT 1,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_origin`),
            UNIQUE KEY `uniq_id_product` (`id_product`),
            KEY `idx_source_table` (`source_table`),
            KEY `idx_ean13` (`ean13`),
            KEY `idx_reference` (`reference`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }



    public static function ensureCategoryMapTables()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_category_map` (
            `id_category_map` int(11) NOT NULL AUTO_INCREMENT,
            `source_table` varchar(64) NOT NULL,
            `source_category` varchar(255) NOT NULL,
            `source_type` varchar(16) NOT NULL DEFAULT \'category\',
            `ps_category_ids` text DEFAULT NULL,
            `id_category_default` int(11) DEFAULT 0,
            `category_markup_percent` decimal(10,2) NOT NULL DEFAULT 0.00,
            `is_active` tinyint(1) NOT NULL DEFAULT 0,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_category_map`),
            UNIQUE KEY `uniq_source_category` (`source_table`,`source_category`),
            KEY `idx_default_category` (`id_category_default`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $table = _DB_PREFIX_ . 'azada_wholesaler_pro_category_map';

        $hasSourceType = (bool)Db::getInstance()->getValue(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='" . pSQL($table) . "' AND COLUMN_NAME='source_type'"
        );
        if (!$hasSourceType) {
            Db::getInstance()->execute("ALTER TABLE `" . bqSQL($table) . "` ADD COLUMN `source_type` varchar(16) NOT NULL DEFAULT 'category' AFTER `source_category`");
        }

        Db::getInstance()->execute("UPDATE `" . bqSQL($table) . "` SET source_type='category' WHERE source_type='' OR source_type IS NULL");

        $hasCategoryMarkupPercent = (bool)Db::getInstance()->getValue(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='" . pSQL($table) . "' AND COLUMN_NAME='category_markup_percent'"
        );
        if (!$hasCategoryMarkupPercent) {
            Db::getInstance()->execute("ALTER TABLE `" . bqSQL($table) . "` ADD COLUMN `category_markup_percent` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `id_category_default`");
        }

        return true;
    }

    public static function uninstallDatabase()
    {
        $tables = [
            'azada_raw_bioplanet',
            'azada_raw_ekowital',
            'azada_raw_naturamed',
            'azada_wholesaler_pro_integration',
            'azada_wholesaler_pro_mapping',
            'azada_wholesaler_pro_cache',
            'azada_wholesaler_pro_logs',
            'azada_wholesaler_pro_order_files',
            'azada_wholesaler_pro_order_details',
            'azada_wholesaler_pro_invoice_files',
            'azada_wholesaler_pro_invoice_details',
            'azada_wholesaler_pro_analysis',
            'azada_wholesaler_pro_analysis_diff',
            'azada_wholesaler_pro_product_origin',
            'azada_wholesaler_pro_category_map'
        ];

        foreach ($tables as $table) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`');
        }
        return true;
    }
}
