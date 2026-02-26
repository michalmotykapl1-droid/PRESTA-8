<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

// Ścieżka do pliku serwisu pozostaje bez zmian
require_once __DIR__.'/services/ProductCategorizer.php';

class Dietamamyto extends Module
{
    public function __construct()
    {
        $this->name = 'dietamamyto';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'BigBio';
        $this->need_instance = 0;
        
        // Ustawienia zgodności i bootstrap na wzór modułu "wyprzedaz"
        $this->ps_versions_compliancy = [
            'min' => '8.1.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DIETA? MAMY TO – Auto-kategorie');
        $this->description = $this->l('Automatyczne przypisywanie produktów do kategorii dietetycznych na podstawie cech.');

        // Dodano komunikat potwierdzający deinstalację dla spójności
        $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować ten moduł?');
    }

    /**
     * Instalacja modułu: tworzy zakładkę w menu i rejestruje hooki.
     */
    public function install()
    {
        // Sprawdzenie warunków i rejestracja hooków
        if (!parent::install() ||
            !$this->registerHook('actionAdminControllerSetMedia')) {
            return false;
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminDietamamyto'; // Nazwa kontrolera modułu
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'DIETA? MAMY TO';
        }
        
        // Lokalizacja zakładki w menu: "Ulepszenia" (IMPROVE), tak jak w module "wyprzedaz"
        $tab->id_parent = (int) Tab::getIdFromClassName('IMPROVE');
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * Deinstalacja modułu: usuwa zakładkę z menu.
     */
    public function uninstall()
    {
        // Usunięcie zakładki na podstawie nazwy jej kontrolera
        $tabId = (int) Tab::getIdFromClassName('AdminDietamamyto');
        if ($tabId) {
            $tab = new Tab($tabId);
            $tab->delete();
        }

        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * Po kliknięciu "Konfiguruj" przekierowuje do dedykowanego kontrolera.
     * Logika identyczna jak w module "wyprzedaz".
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminDietamamyto')
        );
    }

    /**
     * Hook wywoływany przy zapisie produktu.
     * Ta funkcja pozostaje bez zmian, ponieważ jest to główna logika Twojego modułu.
     */
    public function hookActionProductSave($params)
    {
        if (!isset($params['id_product'])) {
            return;
        }
        $productId = (int)$params['id_product'];
        ProductCategorizer::assignCategoriesToProduct($productId);
    }
    
    /**
     * Hook do dodawania zasobów CSS/JS w panelu admina.
     * Dodany dla spójności i przyszłych zastosowań.
     */
    public function hookActionAdminControllerSetMedia()
    {
        // W tym miejscu możesz w przyszłości ładować pliki CSS lub JS
    }
}