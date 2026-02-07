<?php

class AzadaInstaller
{
    public static function installDatabase()
    {
        $sql = [];

        // 1. Tabela Surowa (Bio Planet)
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_raw_bioplanet` (
            `id_raw` int(11) NOT NULL AUTO_INCREMENT,
            `kod_kreskowy` varchar(64) DEFAULT NULL,
            `produkt_id` varchar(64) DEFAULT NULL,
            `kod` varchar(64) DEFAULT NULL,
            `nazwa` text DEFAULT NULL,
            `marka` text DEFAULT NULL,
            `opis` text DEFAULT NULL,
            `zdjecieglownelinkurl` text DEFAULT NULL,
            `kategoria` text DEFAULT NULL,
            `jednostkapodstawowa` text DEFAULT NULL,
            `waga` decimal(20,6) DEFAULT 0.000000,
            `ilosc_w_opakowaniu` text DEFAULT NULL,
            `wymagane_oz` text DEFAULT NULL,
            `ilosc` text DEFAULT NULL,
            `cenaprzedrabatemnetto` decimal(20,6) DEFAULT 0.000000,
            `cenaporabacienetto` decimal(20,6) DEFAULT 0.000000,
            `vat` decimal(20,6) DEFAULT 0.000000,
            `cenadetalicznabrutto` decimal(20,6) DEFAULT 0.000000,
            `dostepnyod` text DEFAULT NULL,
            `gwaranterminprzydatdni` text DEFAULT NULL,
            `producentnazwaiadres` text DEFAULT NULL,
            `nazamowienie` text DEFAULT NULL,
            `krajpochodzeniaskladnikow` text DEFAULT NULL,
            `masabrutto` decimal(20,6) DEFAULT 0.000000,
            `cenastandardnetto` decimal(20,6) DEFAULT 0.000000,
            `orientacyjny_termin_przydatnosci` text DEFAULT NULL,
            `minimum_logistyczne` text DEFAULT NULL,
            `stan_magazynowy_live` text DEFAULT NULL,
            `cena_netto_live` decimal(20,6) DEFAULT 0.000000,
            `glebokosc` decimal(20,6) DEFAULT 0.000000,
            `szerokosc` decimal(20,6) DEFAULT 0.000000,
            `wysokosc` decimal(20,6) DEFAULT 0.000000,
            `data_aktualizacji` text DEFAULT NULL,
            PRIMARY KEY (`id_raw`),
            KEY `kod_kreskowy` (`kod_kreskowy`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

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
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_invoice`),
            KEY `doc_number` (`doc_number`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 9. Detale Faktur
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_invoice_details` (
            `id_detail` int(11) NOT NULL AUTO_INCREMENT,
            `id_invoice` int(11) NOT NULL,
            `doc_number` varchar(100) NOT NULL,
            `ean` varchar(50) DEFAULT NULL,
            `name` varchar(255) DEFAULT NULL,
            `quantity` int(11) DEFAULT 0,
            `price_net` decimal(20,2) DEFAULT 0.00,
            `vat_rate` int(11) DEFAULT 0,
            PRIMARY KEY (`id_detail`),
            KEY `id_invoice` (`id_invoice`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 10. Analiza (Weryfikacja - Nagłówek)
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'azada_wholesaler_pro_analysis` (
            `id_analysis` int(11) NOT NULL AUTO_INCREMENT,
            `id_wholesaler` int(11) NOT NULL,
            `id_invoice_file` int(11) NOT NULL,
            `id_order_file` int(11) DEFAULT NULL,
            `doc_number_invoice` varchar(100) NOT NULL,
            `doc_number_order` text DEFAULT NULL,
            `status` varchar(20) DEFAULT "NEW", 
            `total_diff_net` decimal(20,2) DEFAULT 0.00,
            `items_match_count` int(11) DEFAULT 0,
            `items_error_count` int(11) DEFAULT 0,
            `date_analyzed` datetime NOT NULL,
            PRIMARY KEY (`id_analysis`),
            KEY `id_invoice_file` (`id_invoice_file`),
            KEY `id_wholesaler` (`id_wholesaler`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // 11. Analiza (Różnice - Diff)
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

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    public static function uninstallDatabase()
    {
        $tables = [
            'azada_raw_bioplanet',
            'azada_wholesaler_pro_integration',
            'azada_wholesaler_pro_mapping',
            'azada_wholesaler_pro_cache',
            'azada_wholesaler_pro_logs',
            'azada_wholesaler_pro_order_files',
            'azada_wholesaler_pro_order_details',
            'azada_wholesaler_pro_invoice_files',
            'azada_wholesaler_pro_invoice_details',
            'azada_wholesaler_pro_analysis',
            'azada_wholesaler_pro_analysis_diff'
        ];

        foreach ($tables as $table) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`');
        }
        return true;
    }
}