<?php
/*
 * STREFA SUPLEMENTÓW – Wersja 100% Lazy Load (SEO & Performance)
 * Start: Puste szkielety. Ładowanie: AJAX po scrollu.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/StrefaSupleHelper.php');

class Strefasuplementow extends Module
{
    public function __construct()
    {
        $this->name = 'strefasuplementow';
        $this->tab = 'front_office_features';
        $this->version = '4.1.0';
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('STREFA SUPLEMENTÓW (Full Lazy)');
        $this->description = $this->l('Startuje pusto, ładuje produkty po przewinięciu.');
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
            'modules/'.$this->name.'/views/css/strefasuplementow.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerJavascript(
            'module-'.$this->name.'-js',
            'modules/'.$this->name.'/views/js/strefasuplementow.js',
            ['position' => 'bottom', 'priority' => 150]
        );

        Media::addJsDef([
            'suple_ajax_url' => $this->context->link->getModuleLink('strefasuplementow', 'ajax')
        ]);
    }

    private function getCategoryConfig()
    {
        return [
            'odpornosc' => [ 'title' => 'Na Odporność', 'icon' => 'fa-solid fa-shield-heart', 'id' => 589 ],
            'witaminy' => [ 'title' => 'Witaminy i Minerały', 'icon' => 'fa-solid fa-capsules', 'id' => 684 ],
            'uroda' => [ 'title' => 'Włosy, Skóra, Paznokcie', 'icon' => 'fa-solid fa-spa', 'id' => 942 ],
            'trawienie' => [ 'title' => 'Układ Pokarmowy', 'icon' => 'fa-solid fa-leaf', 'id' => 680 ],
            'pamiec' => [ 'title' => 'Pamięć i Koncentracja', 'icon' => 'fa-solid fa-brain', 'id' => 935 ],
            'stawy' => [ 'title' => 'Stawy, Kości, Mięśnie', 'icon' => 'fa-solid fa-person-running', 'id' => 681 ],
            'waga' => [ 'title' => 'Kontrola Wagi', 'icon' => 'fa-solid fa-weight-scale', 'id' => 682 ],
            'oczyszczanie' => [ 'title' => 'Oczyszczanie Organizmu', 'icon' => 'fa-solid fa-droplet', 'id' => 891 ],
            'krazenie' => [ 'title' => 'Układ Krążenia', 'icon' => 'fa-solid fa-heart-pulse', 'id' => 937 ],
            'wzrok' => [ 'title' => 'Dobry Wzrok', 'icon' => 'fa-solid fa-eye', 'id' => 941 ],
            'moczowy' => [ 'title' => 'Układ Moczowy', 'icon' => 'fa-solid fa-water', 'id' => 940 ],
            'intymne' => [ 'title' => 'Sprawność Seksualna', 'icon' => 'fa-solid fa-venus-mars', 'id' => 939 ],
            'dzieci' => [ 'title' => 'Dla Dzieci', 'icon' => 'fa-solid fa-baby', 'id' => 613 ]
        ];
    }

    // --- 1. START: PUSTE SZKIELETY (0 SQL Queries) ---
    public function hookDisplayHome($params)
    {
        $categories = $this->getCategoryConfig();
        $finalTabs = [];
        
        // ZAWSZE puste na start
        foreach ($categories as $key => $data) {
            $finalTabs[$key] = [
                'title' => $data['title'],
                'icon'  => $data['icon'],
                'products' => [] 
            ];
        }

        $linkAll = $this->context->link->getCategoryLink(588);

        $this->context->smarty->assign([
            'suple_tabs' => $finalTabs,
            'suple_main_title' => $this->l('SUPLEMENTY DLA TWOJEGO ZDROWIA'),
            'suple_desc' => $this->l('Zapoznaj się z szeroką gamą naturalnych preparatów. Wybierz kategorię z menu, aby znaleźć produkty idealnie dopasowane do potrzeb Twojego organizmu.'),
            'suple_all_link' => $linkAll,
            'is_suple_ajax' => false
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
    }

    // --- 2. AJAX: POBIERANIE DANYCH ---
    public function renderAjaxContent()
    {
        require_once(dirname(__FILE__) . '/classes/StrefaSupleHelper.php');

        $requestedTab = Tools::getValue('suple_tab');
        
        // Zabezpieczenie: domyślna zakładka
        if (!$requestedTab) {
            $categories = $this->getCategoryConfig();
            $requestedTab = array_key_first($categories);
        }

        // Cache
        $id_lang = (int)$this->context->language->id;
        $id_shop = (int)$this->context->shop->id;
        $id_curr = (int)$this->context->currency->id;
        $dateKey = date('YmdH');
        
        $cacheId = 'suple_' . $id_shop . '_' . $id_lang . '_' . $id_curr . '_' . $requestedTab . '_' . $dateKey;

        $cache = Cache::getInstance();
        if ($cache->exists($cacheId)) {
            return $cache->get($cacheId);
        }

        // Generowanie
        $categories = $this->getCategoryConfig();
        $finalTabs = [];

        if (isset($categories[$requestedTab])) {
            foreach ($categories as $key => $data) {
                $products = [];
                // Pobieramy TYLKO dla żądanej
                if ($key === $requestedTab) {
                    $products = StrefaSupleHelper::getProductsByCategory($data['id'], 16);
                }
                
                $finalTabs[$key] = [
                    'title' => $data['title'],
                    'icon'  => $data['icon'],
                    'products' => $products
                ];
            }
        }

        $this->context->smarty->assign([
            'suple_tabs' => $finalTabs,
            'is_suple_ajax' => true
        ]);

        $content = $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
        
        $cache->set($cacheId, $content, 3600);
        return $content;
    }
}