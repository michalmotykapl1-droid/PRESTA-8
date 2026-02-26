<?php
/**
 * Strefa Koszyka - Wersja z przyciskiem "Pobierz nowe"
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/StrefaKoszykaHelper.php');

class StrefaKoszyka extends Module
{
    public function __construct()
    {
        $this->name = 'strefakoszyka';
        $this->tab = 'front_office_features';
        $this->version = '1.6.0'; // Aktualizacja wersji
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('STREFA KOSZYKA (Slider + Refresh)');
        $this->description = $this->l('Wyświetla produkty z kategorii ID 45 w formie slidera z opcją odświeżania.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() && 
               $this->registerHook('header') && 
               $this->registerHook('displayBBCartZone');
    }

    public function hookHeader()
    {
        if ($this->context->controller->php_self === 'cart') {
            $this->context->controller->registerStylesheet(
                'module-'.$this->name.'-css',
                'modules/'.$this->name.'/views/css/strefakoszyka.css',
                ['media' => 'all', 'priority' => 150]
            );
            $this->context->controller->registerJavascript(
                'module-'.$this->name.'-js',
                'modules/'.$this->name.'/views/js/strefakoszyka.js',
                ['position' => 'bottom', 'priority' => 150]
            );
            
            Media::addJsDef([
                'strefa_ajax_url' => $this->context->link->getModuleLink('strefakoszyka', 'ajax')
            ]);
        }
    }

    public function hookDisplayBBCartZone($params)
    {
        $this->context->smarty->assign([
            'is_strefa_ajax' => false,
            'strefa_title' => $this->l('SKORZYSTAJ ZANIM ZAPŁACISZ – OKAZJE DO -50%!') // Twój obecny tytuł
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/bb_cart_zone.tpl');
    }

    // --- AJAX ---
    public function renderAjaxContent()
    {
        $target_category_id = 45;
        
        // Sprawdzamy, czy klient kliknął "Pobierz nowe"
        $force_refresh = (bool)Tools::getValue('refresh');

        // Cache settings
        $id_lang = $this->context->language->id;
        $id_shop = $this->context->shop->id;
        $cacheId = 'strefakoszyka_' . $id_shop . '_' . $id_lang . '_' . $target_category_id . date('YmdH');
        $cache = Cache::getInstance();

        // Jeśli NIE ma wymuszenia odświeżenia, próbujemy pobrać z cache
        if (!$force_refresh && $cache->exists($cacheId)) {
            return $cache->get($cacheId);
        }

        // Pobieramy nowe losowe produkty
        $products = StrefaKoszykaHelper::getProducts($target_category_id, 10);

        if (empty($products)) {
            return '';
        }

        $this->context->smarty->assign([
            'is_strefa_ajax' => true,
            'impulse_products' => $products,
            'strefa_title' => $this->l('SKORZYSTAJ ZANIM ZAPŁACISZ – OKAZJE DO -50%!')
        ]);

        $content = $this->fetch('module:'.$this->name.'/views/templates/hook/bb_cart_zone.tpl');
        
        // Zapisujemy do cache tylko jeśli to nie jest wymuszone odświeżenie (opcjonalnie)
        // lub nadpisujemy cache nowym zestawem
        $cache->set($cacheId, $content, 3600);
        
        return $content;
    }
}