<?php
/*
 * STREFA PRODUKTOWA – Wersja Full Lazy + Menu Hook
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(dirname(__FILE__) . '/classes/StrefaHelper.php')) {
    require_once(dirname(__FILE__) . '/classes/StrefaHelper.php');
}

class Strefaprotuktowa extends Module
{
    public function __construct()
    {
        $this->name = 'strefaprotuktowa';
        $this->tab = 'front_office_features';
        $this->version = '3.7.0'; // Podbijamy wersję
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('STREFA PRODUKTOWA (Full Speed)');
        $this->description = $this->l('Startuje pusto, ładuje produkty po przewinięciu + Hook do Menu.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayHome') &&
            $this->registerHook('header') &&
            // REJESTRACJA NOWEGO HOOKA:
            $this->registerHook('displayMenuSpozywczeDeal'); 
    }

    public function hookHeader($params)
    {
        $this->context->controller->registerStylesheet(
            'module-'.$this->name.'-css',
            'modules/'.$this->name.'/views/css/strefaprotuktowa.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerJavascript(
            'module-'.$this->name.'-js',
            'modules/'.$this->name.'/views/js/strefaprotuktowa.js',
            ['position' => 'bottom', 'priority' => 150]
        );

        Media::addJsDef([
            'strefa_ajax_url' => $this->context->link->getModuleLink(
                'strefaprotuktowa', 
                'ajax'
            )
        ]);
    }

    // --- NOWY HOOK DO MENU ---
    // --- NOWY HOOK DO MENU (POPRAWIONY) ---
    public function hookDisplayMenuSpozywczeDeal($params)
    {
        if (!class_exists('StrefaHelper')) return '';

        // LIMIT 10 = 5 kolumn x 2 rzędy
        $limitMenu = 10; 
        
        $link = $this->context->link;
        $url_cat_508 = $link->getCategoryLink(508);
        $url_new = $link->getPageLink('new-products');

        $tabs = [
            'featured' => ['title' => 'POLECANE', 'url' => $url_cat_508, 'products' => StrefaHelper::getProducts('featured', $limitMenu)],
            'bestsellers' => ['title' => 'ULUBIONE', 'url' => $url_cat_508, 'products' => StrefaHelper::getProducts('bestsellers', $limitMenu)],
            'new' => ['title' => 'NOWOŚCI', 'url' => $url_new, 'products' => StrefaHelper::getProducts('new', $limitMenu)]
        ];

        $this->context->smarty->assign(['strefa_tabs' => $tabs]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/block_menu_deal.tpl');
    }

    // --- 1. START: PUSTE PRODUKTY (HOME) ---
    public function hookDisplayHome($params)
    {
        $link = $this->context->link;
        $url_cat_508 = $link->getCategoryLink(508);
        $url_new = $link->getPageLink('new-products');

        $tabs = [
            'featured' => ['title' => 'POLECANE HITY', 'icon' => 'fa-solid fa-thumbs-up', 'url' => $url_cat_508, 'products' => []],
            'bestsellers' => ['title' => 'ULUBIEŃCY KLIENTÓW', 'icon' => 'fa-solid fa-heart', 'url' => $url_cat_508, 'products' => []],
            'new' => ['title' => 'NOWE PRODUKTY', 'icon' => 'fa-solid fa-bolt', 'url' => $url_new, 'products' => []]
        ];

        $descriptions = [
            'featured' => 'Postaw na sprawdzoną jakość. Ten produkt to nasz absolutny faworyt...',
            'bestsellers' => 'To wybór naszych Klientów! Ten produkt bije rekordy popularności...',
            'new' => 'Odkryj premierę w naszej ofercie! Świeża dostawa i nowa jakość...'
        ];

        $this->context->smarty->assign([
            'strefa_tabs' => $tabs,
            'strefa_deals' => [], 
            'strefa_descriptions' => $descriptions,
            'strefa_accent' => $this->l('TWÓJ DOMOWY NIEZBĘDNIK'),
            'strefa_main_title' => $this->l('STREFA CODZIENNYCH ZAKUPÓW'),
            'strefa_desc' => $this->l('Wszystko, czego potrzebujesz na co dzień. Odkryj nasze hity sprzedażowe i najnowsze produkty.'),
            'is_ajax_loading' => false
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
    }

    // --- 2. AJAX: POBIERANIE (HOME) ---
    public function renderAjaxContent()
    {
        if (!class_exists('StrefaHelper')) return '';

        $requestedTab = Tools::getValue('strefa_tab');
        if (!$requestedTab) $requestedTab = 'featured';

        $id_lang = (int)$this->context->language->id;
        $id_shop = (int)$this->context->shop->id;
        $id_curr = (int)$this->context->currency->id;
        $dateKey = date('YmdH');
        
        $cacheId = 'strefa_' . $id_shop . '_' . $id_lang . '_' . $id_curr . '_' . $requestedTab . '_' . $dateKey;

        $cache = Cache::getInstance();
        if ($cache->exists($cacheId)) {
            return $cache->get($cacheId);
        }

        $max_safe_limit = 16; 
        $link = $this->context->link;
        $url_cat_508 = $link->getCategoryLink(508);
        $url_new = $link->getPageLink('new-products');

        $dealProducts = [];
        $allDeals = StrefaHelper::getDealProducts();

        $tabs = [
            'featured' => ['title' => 'POLECANE HITY', 'icon' => 'fa-solid fa-thumbs-up', 'url' => $url_cat_508, 'products' => []],
            'bestsellers' => ['title' => 'ULUBIEŃCY KLIENTÓW', 'icon' => 'fa-solid fa-heart', 'url' => $url_cat_508, 'products' => []],
            'new' => ['title' => 'NOWE PRODUKTY', 'icon' => 'fa-solid fa-bolt', 'url' => $url_new, 'products' => []]
        ];

        if (array_key_exists($requestedTab, $tabs)) {
            $tabs[$requestedTab]['products'] = StrefaHelper::getProducts($requestedTab, $max_safe_limit);
            if (isset($allDeals[$requestedTab])) {
                $dealProducts[$requestedTab] = $allDeals[$requestedTab];
            }
        }

        $descriptions = [
            'featured' => 'Postaw na sprawdzoną jakość. Ten produkt to nasz absolutny faworyt...',
            'bestsellers' => 'To wybór naszych Klientów! Ten produkt bije rekordy popularności...',
            'new' => 'Odkryj premierę w naszej ofercie! Świeża dostawa i nowa jakość...'
        ];

        $this->context->smarty->assign([
            'strefa_tabs' => $tabs,
            'strefa_deals' => $dealProducts,
            'strefa_descriptions' => $descriptions,
            'is_ajax_loading' => true,
        ]);

        $content = $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
        
        $cache->set($cacheId, $content, 3600);
        return $content;
    }
}