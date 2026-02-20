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
    }

    public function install()
    {
        return parent::install() &&
            AzadaInstaller::installDatabase() &&
            AzadaTabInstaller::installTabs($this->name); // <-- Używamy nowej klasy
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

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminAzadaWholesaler'));
    }
}