<?php
/*
 * STREFA CZYSTOŚCI – Moduł Główny (Full Lazy Load)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/StrefaCzystosciHelper.php');

class Strefaczystosci extends Module
{
    public function __construct()
    {
        $this->name = 'strefaczystosci';
        $this->tab = 'front_office_features';
        $this->version = '2.0.0'; // Full Speed
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('STREFA CZYSTOŚCI (Lazy Load)');
        $this->description = $this->l('Moduł: Inspiracja + Produkty (AJAX).');
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
            'modules/'.$this->name.'/views/css/strefaczystosci.css',
            ['media' => 'all', 'priority' => 150]
        );
        
        $this->context->controller->registerJavascript(
            'module-'.$this->name.'-js',
            'modules/'.$this->name.'/views/js/strefaczystosci.js',
            ['position' => 'bottom', 'priority' => 150]
        );

        Media::addJsDef([
            'czystosc_ajax_url' => $this->context->link->getModuleLink('strefaczystosci', 'ajax')
        ]);
    }

    // --- 1. WIDOK STARTOWY (PUSTY) ---
    public function hookDisplayHome($params)
    {
        // Nie pobieramy produktów na start -> Szybkie ładowanie strony
        $linkAll = $this->context->link->getCategoryLink(726);

        $this->context->smarty->assign([
            'czystosc_products' => [], // PUSTE
            'czystosc_all_link' => $linkAll,
            'czystosc_main_title' => $this->l('EKOLOGICZNY DOM BEZ CHEMII'),
            'czystosc_main_desc'  => $this->l('Zadbaj o czystość w zgodzie z naturą. Skuteczne i bezpieczne środki, które odmienią Twój dom.'),
            'box_accent' => $this->l('BEZPIECZNY DOM'),
            'box_title'  => $this->l('Twoja strefa eko sprzątania'),
            'box_desc'   => $this->l('Pozbądź się toksycznej chemii ze swojego otoczenia. Wybierz skuteczne środki oparte na roślinnych składnikach, które są bezpieczne dla Twoich dzieci, zwierząt i alergików. Czystość może pachnieć naturą!'),
            'box_btn'    => $this->l('ZOBACZ CAŁĄ OFERTĘ'),
            'is_czystosc_ajax' => false
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
    }

    // --- 2. WIDOK AJAX (POBIERANIE) ---
    public function renderAjaxContent()
    {
        // Cache Key
        $id_lang = (int)$this->context->language->id;
        $id_shop = (int)$this->context->shop->id;
        $id_curr = (int)$this->context->currency->id;
        $dateKey = date('YmdH'); // Cache 1h
        
        $cacheId = 'czystosc_' . $id_shop . '_' . $id_lang . '_' . $id_curr . '_' . $dateKey;

        $cache = Cache::getInstance();
        if ($cache->exists($cacheId)) {
            return $cache->get($cacheId);
        }

        // Pobieranie 8 produktów (zapas na szeroki ekran)
        $products = StrefaCzystosciHelper::getRandomProductsFromCategory(726, 8);

        $this->context->smarty->assign([
            'czystosc_products' => $products,
            'is_czystosc_ajax' => true
        ]);

        $content = $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
        
        $cache->set($cacheId, $content, 3600);
        return $content;
    }
}