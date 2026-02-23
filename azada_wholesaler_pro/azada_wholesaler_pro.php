<?php
/**
 * Moduł: INTEGRACJA HURTOWNI PRO
 * Vendor: AZADA
 * Wersja: 2.6.0 (Update: Tab Installer)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Ładowanie instalatorów
require_once(dirname(__FILE__) . '/classes/services/AzadaInstaller.php');
require_once(dirname(__FILE__) . '/classes/services/AzadaTabInstaller.php'); // <-- Nowy instalator zakładek

// Ładowanie klas podstawowych (dla pewności, choć instalatory robią większość roboty)
if (file_exists(dirname(__FILE__) . '/classes/AzadaWholesaler.php')) require_once(dirname(__FILE__) . '/classes/AzadaWholesaler.php');

class Azada_Wholesaler_Pro extends Module
{
    public function __construct()
    {
        $this->name = 'azada_wholesaler_pro';
        $this->tab = 'quick_bulk_update';
        $this->version = '2.6.0';
        $this->author = 'AZADA';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('INTEGRACJA HURTOWNI PRO');
        $this->description = $this->l('Zaawansowany system integracji B2B + Weryfikacja Faktur.');
        $this->confirmUninstall = $this->l('Czy na pewno chcesz usunąć moduł?');

        if ($this->id && !$this->isRegisteredInHook('actionObjectProductAddAfter')) {
            $this->registerHook('actionObjectProductAddAfter');
        }
    }

    public function install()
    {
        return parent::install() &&
            AzadaInstaller::installDatabase() &&
            AzadaTabInstaller::installTabs($this->name) &&
            $this->registerHook('actionObjectProductAddAfter'); // <-- Używamy nowej klasy
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            AzadaTabInstaller::uninstallTabs() && // <-- Używamy nowej klasy
            AzadaInstaller::uninstallDatabase();
    }

    // Metody installTabs() i uninstallTabs() zostały usunięte z tej klasy
    // i przeniesione do AzadaTabInstaller.php

    // Funkcja naprawcza (pozostawiona dla kompatybilności wstecznej przy update, ale korzysta z nowej klasy)
    public static function ensureVerificationTab()
    {
        return AzadaTabInstaller::installTabs('azada_wholesaler_pro');
    }


    public function hookActionObjectProductAddAfter($params)
    {
        AzadaInstaller::ensureProductOriginTable();

        if (!Tools::getValue('azada_manual_create')) {
            return;
        }

        if (empty($params['object']) || !($params['object'] instanceof Product)) {
            return;
        }

        $product = $params['object'];
        $idProduct = (int)$product->id;
        if ($idProduct <= 0) {
            return;
        }

        $sourceTable = trim((string)Tools::getValue('azada_source_table', ''));
        $ean = trim((string)Tools::getValue('azada_ean', ''));
        $sku = trim((string)Tools::getValue('azada_sku', ''));

        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'azada_wholesaler_pro_product_origin';

        $db->execute('DELETE FROM `' . bqSQL($table) . '` WHERE `id_product` = ' . (int)$idProduct);
        $db->insert('azada_wholesaler_pro_product_origin', [
            'id_product' => (int)$idProduct,
            'source_table' => pSQL($sourceTable),
            'ean13' => pSQL($ean),
            'reference' => pSQL($sku),
            'created_by_module' => 1,
            'date_add' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminAzadaWholesaler'));
    }
}
