<?php
/**
 * STREFA OKAZJI - Controller (Full Lazy Load with Split TPL)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(dirname(__FILE__) . '/classes/SpecialHelper.php')) {
    require_once(dirname(__FILE__) . '/classes/SpecialHelper.php');
}

class TvcmsSpecialProducts extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmsspecialproducts';
        $this->tab = 'front_office_features';
        $this->version = '5.1.0'; // Split TPL Lazy Load
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Special Product (Lazy Load)');
        $this->description = $this->l('Wyświetla Wyprzedaż i Krótką Datę.');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() && $this->registerHook('displayHome') && $this->registerHook('header');
    }

    public function hookHeader()
    {
        $this->context->controller->registerStylesheet(
            'module-'.$this->name.'-css',
            'modules/'.$this->name.'/views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );
        
        $this->context->controller->addJS($this->_path . 'views/js/front.js');

        // Definiujemy URL do naszego kontrolera AJAX
        Media::addJsDef([
            'special_ajax_url' => $this->context->link->getModuleLink('tvcmsspecialproducts', 'ajax')
        ]);
    }

    // --- 1. START: SZKIELET (display_home.tpl) ---
    public function hookdisplayHome()
    {
        // Tutaj nie pobieramy produktów. Tylko statyczne teksty nagłówka.
        return $this->display(__FILE__, 'views/templates/front/display_home.tpl');
    }

    // --- 2. AJAX: TREŚĆ (display_home-data.tpl) ---
    public function renderAjaxContent()
    {
        $id_sale = 45;   
        $id_short = 180; 

        // Cache Key
        $id_lang = (int)$this->context->language->id;
        $id_shop = (int)$this->context->shop->id;
        $id_curr = (int)$this->context->currency->id;
        $dateKey = date('YmdH');
        
        $cacheId = 'special_' . $id_shop . '_' . $id_lang . '_' . $id_curr . '_' . $dateKey;

        $cache = Cache::getInstance();
        if ($cache->exists($cacheId)) {
            return $cache->get($cacheId);
        }

        $products_sale = [];
        $products_short = [];
        $link_sale = '#';
        $link_short = '#';

        if (class_exists('SpecialHelper')) {
            // Pobieramy 10 produktów na lewo
            $products_sale = SpecialHelper::getProductsForCategory($id_sale, 10);
            // Pobieramy 2 produkty na prawo
            $products_short = SpecialHelper::getProductsForCategory($id_short, 2);
            
            $link_sale = SpecialHelper::getCategoryLink($id_sale);
            $link_short = SpecialHelper::getCategoryLink($id_short);
        }

        $this->context->smarty->assign([
            'special_sale_products' => $products_sale,
            'special_short_products' => $products_short,
            'link_sale' => $link_sale,
            'link_short' => $link_short,
        ]);

        // Renderujemy TYLKO plik z danymi
        $content = $this->display(__FILE__, 'views/templates/front/display_home-data.tpl');
        
        $cache->set($cacheId, $content, 3600);
        return $content;
    }
}