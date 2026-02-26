<?php
/**
 * 2007-2025 PrestaShop.
 *
 * NOTICE OF LICENSE
 * ... (nagłówek licencyjny bez zmian) ...
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

include_once 'classes/tvcmsbrandlist_status.class.php';
include_once 'classes/tvcmsbrandlist_image_upload.class.php';

class TvcmsBrandList extends Module
{
    public $id_shop_group = '';

    public $id_shop = '';

    public function __construct()
    {
        $this->name = 'tvcmsbrandlist';
        $this->tab = 'front_office_features';
        $this->version = '4.0.2';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Brand List');
        $this->description = $this->l('Its Show Brand List on Front Side');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');

        $this->id_shop_group = (int) Shop::getContextShopGroupID();
        $this->id_shop = (int) Context::getContext()->shop->id;
    }

    public function install()
    {
        $this->installTab();
        // $this->createTable(); 

        return parent::install()
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayHome') 
            && $this->registerHook('displayWrapperBottom')
            && $this->registerHook('displayContentWrapperTop')
            && $this->registerHook('displayWrapperTop');
    }

    public function installTab()
    {
        $response = true;

        // First check for parent tab
        $parentTabID = Tab::getIdFromClassName('AdminThemeVolty');

        if ($parentTabID) {
            $parentTab = new Tab($parentTabID);
        } else {
            $parentTab = new Tab();
            $parentTab->active = 1;
            $parentTab->name = [];
            $parentTab->class_name = 'AdminThemeVolty';
            foreach (Language::getLanguages() as $lang) {
                $parentTab->name[$lang['id_lang']] = 'ThemeVolty Extension';
            }
            $parentTab->id_parent = 0;
            $parentTab->module = $this->name;
            $response &= $parentTab->add();
        }

        // Check for parent tab2
        $parentTab_2ID = Tab::getIdFromClassName('AdminThemeVoltyModules');
        if ($parentTab_2ID) {
            $parentTab_2 = new Tab($parentTab_2ID);
        } else {
            $parentTab_2 = new Tab();
            $parentTab_2->active = 1;
            $parentTab_2->name = [];
            $parentTab_2->class_name = 'AdminThemeVoltyModules';
            foreach (Language::getLanguages() as $lang) {
                $parentTab_2->name[$lang['id_lang']] = 'ThemeVolty Configure';
            }
            $parentTab_2->id_parent = $parentTab->id;
            $parentTab_2->module = $this->name;
            $response &= $parentTab_2->add();
        }
        // Created tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'Admin' . $this->name;
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Brand List';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function createDefaultData()
    {
        $this->reset();
        $this->createVariable();
    }

    public function createVariable()
    {
        $languages = Language::getLanguages();
        $result = [];
        foreach ($languages as $lang) {
            $result['TVCMSBRANDLIST_TITLE'][$lang['id_lang']] = 'PRODUCENCI'; 
            $result['TVCMSBRANDLIST_SUB_DESCRIPTION'][$lang['id_lang']] = 'TOP'; 
            $result['TVCMSBRANDLIST_DESCRIPTION'][$lang['id_lang']] = 'Główny tytuł';
            $result['TVCMSBRANDLIST_IMG'][$lang['id_lang']] = 'demo_main_img.jpg';
        }

        Configuration::updateValue('TVCMSBRANDLIST_TITLE', $result['TVCMSBRANDLIST_TITLE']);
        Configuration::updateValue('TVCMSBRANDLIST_SUB_DESCRIPTION', $result['TVCMSBRANDLIST_SUB_DESCRIPTION']);
        Configuration::updateValue('TVCMSBRANDLIST_DESCRIPTION', $result['TVCMSBRANDLIST_DESCRIPTION']);
        Configuration::updateValue('TVCMSBRANDLIST_IMG', $result['TVCMSBRANDLIST_IMG']);
    }
    
    public function showAdminData()
    {
        // W Adminie wyświetlamy teraz oficjalnych producentów.
        $manufacturers = Manufacturer::getManufacturers(false, $this->context->language->id, true);
        $return_data = [];

        foreach ($manufacturers as $key => $manufacturer) {
            $return_data[$key]['id'] = $manufacturer['id_manufacturer'];
            $return_data[$key]['title'] = $manufacturer['name'];
            $return_data[$key]['image'] = $manufacturer['id_manufacturer'] . '.jpg';
            $return_data[$key]['link'] = $this->context->link->getManufacturerLink($manufacturer['id_manufacturer'], $manufacturer['link_rewrite']);
            $return_data[$key]['status'] = $manufacturer['active'];
            $return_data[$key]['link_edit'] = $this->context->link->getAdminLink('AdminManufacturers', true, ['id_manufacturer' => $manufacturer['id_manufacturer'], 'updatemanufacturer' => '']);
        }

        return $return_data;
    }
    
    // KLUCZOWA FUNKCJA: Pobieranie 12 producentów, sortowanych po ilości produktów, Z WYKLUCZENIEM.
    public function showFrontData()
    {
        $id_lang = (int)Context::getContext()->language->id;
        $id_shop = (int)Context::getContext()->shop->id;
        
        // ZMIANA: Lista nazw producentów do wykluczenia (dokładne nazwy!)
        $excluded_manufacturers = [
            'ŚWIEŻE',
            // Możesz dodać więcej nazw tutaj, np. 'Inny Producent'
        ];
        
        // Krok 1: Pobieramy ID wszystkich aktywnych producentów (bezpiecznie)
        $sql = new DbQuery();
        $sql->select('m.`id_manufacturer`');
        $sql->from('manufacturer', 'm');
        $sql->leftJoin('manufacturer_shop', 'ms', 'm.`id_manufacturer` = ms.`id_manufacturer` AND ms.`id_shop` = ' . $id_shop);
        $sql->where('ms.`id_shop` IS NOT NULL');
        $sql->where('m.`active` = 1');
        
        $manufacturers_ids = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        
        // Krok 2: Pobieramy ilość produktów dla każdego producenta
        $manufacturer_counts = [];
        if ($manufacturers_ids) {
            $counts = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                SELECT p.`id_manufacturer`, COUNT(p.`id_product`) AS product_count
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                WHERE p.`id_manufacturer` IN ('.implode(',', array_column($manufacturers_ids, 'id_manufacturer')).')
                GROUP BY p.`id_manufacturer`
            ');
            foreach ($counts as $count) {
                $manufacturer_counts[$count['id_manufacturer']] = (int)$count['product_count'];
            }
        }
        
        // Krok 3: Pobieramy obiekty producentów i filtrujemy po nazwie
        $manufacturers = [];
        foreach ($manufacturers_ids as $item) {
            $id_manufacturer = (int)$item['id_manufacturer'];
            $manufacturer = new Manufacturer($id_manufacturer, $id_lang);
            
            // ZMIANA: Sprawdzamy, czy nazwa producenta jest na liście wykluczonych
            if (!in_array($manufacturer->name, $excluded_manufacturers)) {
                $manufacturer->product_count = $manufacturer_counts[$id_manufacturer] ?? 0;
                $manufacturers[] = $manufacturer;
            }
        }

        // Krok 4: Sortowanie po ilości produktów (DESC)
        usort($manufacturers, function($a, $b) {
            return $b->product_count <=> $a->product_count;
        });

        // Krok 5: Ograniczenie do 12 najlepszych
        $manufacturers = array_slice($manufacturers, 0, 12);
        
        // Krok 6: Tworzenie finalnej tablicy wynikowej
        $result_data = [];
        foreach ($manufacturers as $manufacturer) {
            $link = Context::getContext()->link->getManufacturerLink($manufacturer, $manufacturer->link_rewrite);
            
            $result_data[] = [
                'code'  => $manufacturer->id,
                'image' => '', 
                'title' => $manufacturer->name,
                'link'  => $link,
                'product_count' => $manufacturer->product_count, // Opcjonalnie do debugowania
            ];
        }

        return $result_data;
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $this->deleteVariable();
        // $this->deleteTable(); 

        return parent::uninstall();
    }
    
    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSBRANDLIST_TITLE');
        Configuration::deleteByName('TVCMSBRANDLIST_SUB_DESCRIPTION');
        Configuration::deleteByName('TVCMSBRANDLIST_DESCRIPTION');
        Configuration::deleteByName('TVCMSBRANDLIST_IMG');
    }

    public function hookdisplayHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addJqueryUI('ui.sortable');
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }
    
    public function showFrontSideResult()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $tvcms_obj = new TvcmsBrandListStatus();
        $main_heading = $tvcms_obj->fieldStatusInformation();

        // Ustawienie tytułu i akcentu
        $main_heading['main_status'] = true;
        if ($main_heading['main_status']) {
            $main_heading_data = [];
            $main_heading_data['title'] = Configuration::get('TVCMSBRANDLIST_TITLE', $id_lang); 
            $main_heading_data['short_desc'] = Configuration::get('TVCMSBRANDLIST_SUB_DESCRIPTION', $id_lang); 
            $main_heading_data['desc'] = Configuration::get('TVCMSBRANDLIST_DESCRIPTION', $id_lang);
            $main_heading_data['image'] = Configuration::get('TVCMSBRANDLIST_IMG', $id_lang);
            
            $main_heading['data'] = $main_heading_data;
        }

        $disArrResult = [];
        $disArrResult['data'] = $this->showFrontData();
        $disArrResult['status'] = empty($disArrResult['data']) ? false : true;
        $disArrResult['path'] = ''; 
        $disArrResult['id_lang'] = $id_lang;

        $this->context->smarty->assign('main_heading', $main_heading);
        $this->context->smarty->assign('dis_arr_result', $disArrResult);

        return $disArrResult['status'] ? true : false;
    }
    
    public function hookDisplayHome()
    {
        $result = $this->showFrontSideResult();
        $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
        
        return $output;
    }
    
    public function hookdisplayWrapperBottom()
    {
        return $this->hookDisplayHome();
    }

    public function hookdisplayContentWrapperTop()
    {
        return $this->hookDisplayHome();
    }

    public function hookdisplayWrapperTop()
    {
        return $this->hookDisplayHome();
    }
}