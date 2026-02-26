<?php
/*
 * DIETA PRODUKTY – Moduł główny (Full Lazy Load)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/DietaHelper.php');

class DietaProdukty extends Module
{
    public function __construct()
    {
        $this->name = 'dietaprodukty';
        $this->tab = 'front_office_features';
        $this->version = '2.2.0'; // Fix Load Logic
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DIETA PRODUKTY (Lazy Load)');
        $this->description = $this->l('Wyświetla diety z ładowaniem AJAX.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() && $this->registerHook('displayHome') && $this->registerHook('header');
    }

    public function hookHeader($params)
    {
        $this->context->controller->registerStylesheet('module-'.$this->name.'-css', 'modules/'.$this->name.'/views/css/dietaprodukty.css', ['media' => 'all', 'priority' => 150]);
        $this->context->controller->registerJavascript('module-'.$this->name.'-js', 'modules/'.$this->name.'/views/js/dietaprodukty.js', ['position' => 'bottom', 'priority' => 150]);
        Media::addJsDef(['dieta_ajax_url' => $this->context->link->getModuleLink('dietaprodukty', 'ajax')]);
    }

    private function getDietsConfig()
    {
        return [
            'gluten' => [ 'name' => $this->l('Bez Glutenu'), 'icon' => 'fa-solid fa-bread-slice', 'id_cat' => 264208 ],
            'vege'   => [ 'name' => $this->l('Wegetariańskie'), 'icon' => 'fa-solid fa-carrot', 'id_cat' => 264210 ],
            'vegan'  => [ 'name' => $this->l('Wegańskie'), 'icon' => 'fa-solid fa-seedling', 'id_cat' => 264209 ],
            'lactose'=> [ 'name' => $this->l('Bez Laktozy'), 'icon' => 'fa-solid fa-glass-water', 'id_cat' => 264207 ],
            'bio'    => [ 'name' => $this->l('Bio / Organic'), 'icon' => 'fa-solid fa-leaf', 'id_cat' => 264205 ],
            'keto'   => [ 'name' => $this->l('Keto / Low-Carb'), 'icon' => 'fa-solid fa-bolt', 'id_cat' => 264211 ],
            'sugar'  => [ 'name' => $this->l('Bez Cukru'), 'icon' => 'fa-solid fa-cube', 'id_cat' => 264206 ],
            'ig'     => [ 'name' => $this->l('Niski IG'), 'icon' => 'fa-solid fa-arrow-trend-down', 'id_cat' => 264212 ],
        ];
    }

    public function hookDisplayHome($params)
    {
        $config = $this->getDietsConfig();
        $diets_data = [];
        foreach ($config as $key => $data) {
            $diets_data[$key] = [ 'info' => array_merge($data, ['id_tab' => 'tab-' . $key]), 'products' => [] ];
        }
        $this->context->smarty->assign([ 'diets_data' => $diets_data, 'is_dieta_ajax' => false ]);
        return $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
    }

    public function renderAjaxContent()
    {
        $requestedTab = Tools::getValue('dieta_tab');
        if (!$requestedTab) $requestedTab = 'gluten';

        $id_lang = (int)$this->context->language->id;
        $id_shop = (int)$this->context->shop->id;
        $id_curr = (int)$this->context->currency->id;
        $dateKey = date('YmdH');
        
        $cacheId = 'dieta_' . $id_shop . '_' . $id_lang . '_' . $id_curr . '_' . $requestedTab . '_' . $dateKey;
        $cache = Cache::getInstance();
        if ($cache->exists($cacheId)) return $cache->get($cacheId);

        $config = $this->getDietsConfig();
        $diets_data = [];

        if (isset($config[$requestedTab])) {
            foreach ($config as $key => $data) {
                $products = ($key === $requestedTab) ? DietaHelper::getProductsForCategory($data['id_cat'], 15) : [];
                $diets_data[$key] = [ 'info' => array_merge($data, ['id_tab' => 'tab-' . $key]), 'products' => $products ];
            }
        }

        $this->context->smarty->assign([ 'diets_data' => $diets_data, 'is_dieta_ajax' => true ]);
        $content = $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
        $cache->set($cacheId, $content, 3600);
        return $content;
    }
}