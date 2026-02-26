<?php
/**
 * Główny plik modułu Category Carrier Restriction
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CategoryCarrier extends Module
{
    public function __construct()
    {
        $this->name = 'categorycarrier';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'TwójNick';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Przypisz przewoźników (MVC)');
        $this->description = $this->l('Zarządzanie przewoźnikami dla kategorii z drzewem wyboru.');
    }

    public function install()
    {
        return parent::install() &&
            $this->installTab() &&
            $this->registerHook('header') &&
            $this->registerHook('actionProductAdd') &&
            $this->registerHook('actionProductUpdate');
    }

    public function uninstall()
    {
        return $this->uninstallTab() && parent::uninstall();
    }

    /**
     * Instalacja ukrytej zakładki dla kontrolera
     */
    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminCategoryCarrier'; // Musi pasować do nazwy pliku controllera
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Category Carrier Config';
        }
        $tab->id_parent = -1; // -1 oznacza, że nie widać jej w menu (ukryta)
        $tab->module = $this->name;
        
        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminCategoryCarrier');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    /**
     * Przekierowanie przycisku "Konfiguruj" do naszego Kontrolera
     */
    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminCategoryCarrier')
        );
    }

    /**
     * Dodawanie styli i skryptów do sklepu (Front)
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front/style.css');
        $this->context->controller->addJS($this->_path . 'views/js/front/script.js');
    }

    /**
     * Hooki akcji na produktach
     */
    public function hookActionProductUpdate($params)
    {
        // POPRAWKA: Sprawdzenie czy id_product istnieje przed użyciem (dla Crona/Importu)
        if (isset($params['id_product'])) {
            $this->applyRule($params['id_product']);
        }
    }

    public function hookActionProductAdd($params)
    {
        if (isset($params['id_product'])) {
            $this->applyRule($params['id_product']);
        }
    }

    /**
     * Główna logika aplikowania reguły do pojedynczego produktu
     */
    public function applyRule($id_product)
    {
        $product = new Product(
            (int) $id_product,
            false,
            (int) $this->context->language->id,
            (int) $this->context->shop->id,
            $this->context
        );

        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $catId = (int) $product->id_category_default;

        // Pobierz reguły z konfiguracji
        $rules = json_decode(Configuration::get('CATCARRIER_RULES'), true);

        if (!is_array($rules) || empty($rules[$catId]) || !is_array($rules[$catId])) {
            return;
        }

        // Konwersja na int dla bezpieczeństwa
        $carrierIds = array_map('intval', $rules[$catId]);

        if (!empty($carrierIds)) {
            // Metoda PrestaShop do ustawiania przewoźników (id_reference przewoźników)
            $product->setCarriers($carrierIds);
        }
    }
}