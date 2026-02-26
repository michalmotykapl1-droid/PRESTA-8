<?php
/**
 * 2007-2025 PrestaShop.
 * ThemeVolty - Category Tiles Module
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

include_once dirname(__FILE__) . '/classes/TvcmsCategoryService.php';

class TvcmsTwoOfferBanner extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'tvcmstwoofferbanner';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Category Tiles (Allegro Style)');
        $this->description = $this->l('Displays category tiles with icons.');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('header')
            && $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
    }

    public function hookdisplayHome()
    {
        return $this->showResult();
    }

    public function hookdisplayWrapperTop() { return $this->showResult(); }
    public function hookdisplayContentWrapperTop() { return $this->showResult(); }

    public function showResult()
    {
        // UWAGA: Usunąłem sprawdzanie Cache::isStored, aby wymusić pobranie nowej listy
        // Zamiast wczytywać stary HTML, zawsze pobieramy świeże dane z TvcmsCategoryService
        
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;
        $id_shop = Context::getContext()->shop->id;

        $categoryService = new TvcmsCategoryService();
        
        // Pobieramy nową listę (Witaminy, Makarony itp.)
        $custom_categories = $categoryService->getCategoriesData($id_lang, $id_shop);

        $this->context->smarty->assign('custom_categories', $custom_categories);

        return $this->display(__FILE__, 'views/templates/front/display_home.tpl');
    }
}