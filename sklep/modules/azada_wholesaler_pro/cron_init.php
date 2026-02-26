<?php

// Bootstrap PrestaShop (Front) + środowisko modułu dla CRON
include_once(dirname(__FILE__) . '/../../config/config.inc.php');
include_once(dirname(__FILE__) . '/../../init.php');

if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/modules/');
}

// Klasy modułu (nie opieramy się na autoloaderze)
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/AzadaWholesaler.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/AzadaImportEngine.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaCronRunner.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaCronLog.php');
