<?php
$sql = array();

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dxfakturownia_accounts`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dxfakturownia_invoices`';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dxfakturownia_accounts` (
    `id_dxfakturownia_account` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(64) NOT NULL,
    `api_token` varchar(255) NOT NULL,
    `domain` varchar(255) NOT NULL,
    `connection_status` tinyint(1) DEFAULT 0,
    `last_error` varchar(255) DEFAULT NULL,
    `is_default` tinyint(1) unsigned DEFAULT 0,
    `active` tinyint(1) unsigned DEFAULT 1,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY  (`id_dxfakturownia_account`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

// Dodano buyer_name
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dxfakturownia_invoices` (
    `id_dxfakturownia_invoice` int(11) NOT NULL AUTO_INCREMENT,
    `id_dxfakturownia_account` int(11) NOT NULL,
    `id_order` int(11) DEFAULT 0, 
    `remote_id` int(11) NOT NULL, 
    `parent_remote_id` int(11) DEFAULT 0,
    `kind` varchar(32) NOT NULL DEFAULT "vat", 
    `number` varchar(64) NOT NULL,
    `buyer_name` varchar(255) DEFAULT NULL,
    `sell_date` date NOT NULL,
    `price_gross` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `status` varchar(32) DEFAULT NULL,
    `view_url` varchar(255) DEFAULT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY  (`id_dxfakturownia_invoice`),
    KEY `idx_remote` (`remote_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}