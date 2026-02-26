<?php
/**
 * DX Fakturownia - Wersja FINALNA (Czysty PHP + TPL)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once dirname(__FILE__) . '/classes/FakturowniaClient.php';
include_once dirname(__FILE__) . '/classes/FakturowniaAccount.php';
include_once dirname(__FILE__) . '/classes/DxFakturowniaInvoice.php';

class DxFakturownia extends Module
{
    public function __construct()
    {
        $this->name = 'dxfakturownia';
        $this->tab = 'billing_invoicing';
        $this->version = '1.9.0';
        $this->author = 'BigBio Dev';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DX Fakturownia - Integracja');
        $this->description = $this->l('Integracja z Fakturownia.pl (API, Sync, Manager).');
    }

    public function install()
    {
        // SQL install
        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('displayAdminOrderTabContent') &&
            $this->installTab();
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        return parent::uninstall() && $this->uninstallTab();
    }

    public function installTab()
    {
        // 1. Rodzic
        $parentTab = new Tab();
        $parentTab->active = 1;
        $parentTab->class_name = 'AdminDxFakturowniaParent';
        $parentTab->name = array();
        foreach (Language::getLanguages(true) as $lang) $parentTab->name[$lang['id_lang']] = 'Fakturownia DX';
        $parentTab->id_parent = (int)Tab::getIdFromClassName('SELL'); 
        $parentTab->module = $this->name;
        $parentTab->icon = 'description'; 
        $parentTab->add();
        $id_parent = (int)Tab::getIdFromClassName('AdminDxFakturowniaParent');

        // 2. Konta
        $tab1 = new Tab();
        $tab1->active = 1;
        $tab1->class_name = 'AdminDxFakturowniaAccounts';
        $tab1->name = [];
        foreach (Language::getLanguages(true) as $lang) $tab1->name[$lang['id_lang']] = 'Konta i Konfiguracja';
        $tab1->id_parent = $id_parent;
        $tab1->module = $this->name;
        $tab1->add();

        // 3. Faktury
        $tab2 = new Tab();
        $tab2->active = 1;
        $tab2->class_name = 'AdminDxFakturowniaInvoices';
        $tab2->name = [];
        foreach (Language::getLanguages(true) as $lang) $tab2->name[$lang['id_lang']] = 'Lista Faktur';
        $tab2->id_parent = $id_parent;
        $tab2->module = $this->name;
        $tab2->add();

        return true;
    }

    public function uninstallTab()
    {
        $tabs = ['AdminDxFakturowniaInvoices', 'AdminDxFakturowniaAccounts', 'AdminDxFakturowniaParent'];
        foreach ($tabs as $className) {
            $id_tab = (int)Tab::getIdFromClassName($className);
            if ($id_tab) { $tab = new Tab($id_tab); $tab->delete(); }
        }
        return true;
    }

    public function hookActionValidateOrder($params)
    {
        // Logic optional
    }

    /**
     * HOOK: Pobiera dane i renderuje plik TPL
     */
    public function hookDisplayAdminOrderTabContent($params)
    {
        $id_order = 0;
        if (isset($params['id_order'])) $id_order = (int)$params['id_order'];
        elseif (Tools::isSubmit('id_order')) $id_order = (int)Tools::getValue('id_order');
        elseif (isset($params['order']) && is_object($params['order'])) $id_order = (int)$params['order']->id;

        if (!$id_order) return '';

        // 1. Pobierz dane z bazy
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'dxfakturownia_invoices` WHERE `id_order` = ' . $id_order . ' ORDER BY `id_dxfakturownia_invoice` DESC';
        $invoices = Db::getInstance()->executeS($sql);

        // 2. Przekaż dane do Smarty
        $this->context->smarty->assign([
            'dx_invoices' => $invoices,
            'dx_count' => count($invoices)
        ]);

        // 3. Wyświetl szablon (który zawiera JS do przenoszenia)
        return $this->display(__FILE__, 'views/templates/admin/order_tab_content.tpl');
    }
}