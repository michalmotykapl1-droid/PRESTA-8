<?php
$sql = array();
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dxfakturownia_accounts`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dxfakturownia_invoices`';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}