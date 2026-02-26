<?php
/*
 * FRESH PRODUKTY – Moduł główny (Full Lazy Load)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/FreshHelper.php');

class FreshProdukty extends Module
{
    public function __construct()
    {
        $this->name = 'freshprodukty';
        $this->tab = 'front_office_features';
        $this->version = '2.0.0'; // Full Speed
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('FRESH PRODUKTY (Lazy Load)');
        $this->description = $this->l('Układ Hero + Grid z ładowaniem AJAX.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayHome') &&
            $this->registerHook('header');
    }

    public function hookHeader($params)
    {
        $this->context->controller->registerStylesheet(
            'module-'.$this->name.'-css',
            'modules/'.$this->name.'/views/css/freshprodukty.css',
            ['media' => 'all', 'priority' => 150]
        );
        
        $this->context->controller->registerJavascript(
            'module-'.$this->name.'-js',
            'modules/'.$this->name.'/views/js/freshprodukty.js',
            ['position' => 'bottom', 'priority' => 150]
        );

        Media::addJsDef([
            'fresh_ajax_url' => $this->context->link->getModuleLink('freshprodukty', 'ajax')
        ]);
    }

    // --- 1. START (PUSTO) ---
    public function hookDisplayHome($params)
    {
        $this->context->smarty->assign([
            'fresh_products' => [], // PUSTE
            'fresh_title' => $this->l('ODKRYJ PRAWDZIWĄ ŚWIEŻOŚĆ'),
            'is_fresh_ajax' => false
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
    }

    // --- 2. AJAX (POBIERANIE) ---
    public function renderAjaxContent()
    {
        // Cache
        $id_lang = (int)$this->context->language->id;
        $id_shop = (int)$this->context->shop->id;
        $id_curr = (int)$this->context->currency->id;
        $dateKey = date('YmdH'); // 1h
        
        $cacheId = 'fresh_' . $id_shop . '_' . $id_lang . '_' . $id_curr . '_' . $dateKey;

        $cache = Cache::getInstance();
        if ($cache->exists($cacheId)) {
            return $cache->get($cacheId);
        }

        $id_category_fresh = 264210; // ID Kategorii Fresh
        $products = FreshHelper::getProductsForCategory($id_category_fresh, 10);

        $this->context->smarty->assign([
            'fresh_products' => $products,
            'is_fresh_ajax' => true
        ]);

        $content = $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
        
        $cache->set($cacheId, $content, 3600);
        return $content;
    }
}