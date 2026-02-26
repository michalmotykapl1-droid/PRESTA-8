<?php
/**
 * Product Category Slider - Full Lazy Load
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/ProductCategorySliderHelper.php');

class ProductCategorySlider extends Module
{
    public function __construct()
    {
        $this->name = 'productcategoryslider';
        $this->tab = 'front_office_features';
        $this->version = '1.3.0';
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Product Category Slider');
        $this->description = $this->l('Wyświetla produkty z tej samej kategorii (AJAX) - Layout: Tytuł center, strzałki prawo.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() && $this->registerHook('header') && $this->registerHook('displayBBProductCategorySlider');
    }

    public function hookHeader()
    {
        if ($this->context->controller->php_self === 'product') {
            $this->context->controller->registerStylesheet(
                'module-'.$this->name.'-css',
                'modules/'.$this->name.'/views/css/productcategoryslider.css',
                ['media' => 'all', 'priority' => 150]
            );
            $this->context->controller->registerJavascript(
                'module-'.$this->name.'-js',
                'modules/'.$this->name.'/views/js/productcategoryslider.js',
                ['position' => 'bottom', 'priority' => 150]
            );
            
            Media::addJsDef([
                'pcslider_ajax_url' => $this->context->link->getModuleLink('productcategoryslider', 'ajax')
            ]);
        }
    }

    // --- HOOK STARTOWY ---
    public function hookDisplayBBProductCategorySlider($params)
    {
        if (!isset($params['product'])) return '';

        $product = $params['product'];
        $id_product = is_object($product) ? (int)$product->id : (int)$product['id_product'];
        $id_category = is_object($product) ? (int)$product->id_category_default : (int)$product['id_category_default'];

        $this->context->smarty->assign([
            'is_pcslider_ajax' => false,
            'current_pid' => $id_product,
            'current_cid' => $id_category,
            'pcslider_products' => [],
            'pcslider_title' => $this->l('Inne produkty z tej kategorii')
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/bb_product_category_slider.tpl');
    }

    // --- AJAX ---
    public function renderAjaxContent()
    {
        $id_product = (int)Tools::getValue('id_product');
        $id_category = (int)Tools::getValue('id_category');

        if (!$id_product || !$id_category) return '';

        // Cache Key (Daily Refresh)
        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;
        $dateKey = date('Ymd');
        $cacheId = 'pcslider_' . $id_shop . '_' . $id_lang . '_' . $id_product . '_' . $dateKey;

        $cache = Cache::getInstance();
        if ($cache->exists($cacheId)) {
            return $cache->get($cacheId);
        }

        // Pobieramy produkty
        $products = ProductCategorySliderHelper::getProducts($id_category, $id_product, 16);

        $this->context->smarty->assign([
            'is_pcslider_ajax' => true,
            'pcslider_products' => $products,
            'pcslider_title' => $this->l('Inne produkty z tej kategorii')
        ]);

        $content = $this->fetch('module:'.$this->name.'/views/templates/hook/bb_product_category_slider.tpl');
        
        $cache->set($cacheId, $content, 3600);
        return $content;
    }
}