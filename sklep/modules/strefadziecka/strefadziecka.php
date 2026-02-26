<?php
/*
 * STREFA DZIECKA – Moduł Hybrydowy (Full Lazy Load)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/classes/StrefaDzieckaHelper.php');

class Strefadziecka extends Module
{
    public function __construct()
    {
        $this->name = 'strefadziecka';
        $this->tab = 'front_office_features';
        $this->version = '2.0.0'; // Lazy Load
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('STREFA DZIECKA (Lazy Load)');
        $this->description = $this->l('Moduł: Info-Box + Kategorie + Produkty (AJAX).');
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
            'modules/'.$this->name.'/views/css/strefadziecka.css',
            ['media' => 'all', 'priority' => 150]
        );
        
        $this->context->controller->registerJavascript(
            'module-'.$this->name.'-js',
            'modules/'.$this->name.'/views/js/strefadziecka.js',
            ['position' => 'bottom', 'priority' => 150]
        );

        Media::addJsDef([
            'dziecko_ajax_url' => $this->context->link->getModuleLink('strefadziecka', 'ajax')
        ]);
    }

    // --- 1. START (PUSTY) ---
    public function hookDisplayHome($params)
    {
        $linkFood = $this->context->link->getCategoryLink(519);
        $linkCare = $this->context->link->getCategoryLink(749);
        $linkAll = $this->context->link->getCategoryLink(16);

        $this->context->smarty->assign([
            'dziecko_products' => [], // PUSTE NA START
            'link_food' => $linkFood,
            'link_care' => $linkCare,
            'link_all'  => $linkAll,
            
            'dziecko_main_title' => $this->l('NATURALNA TROSKA O NAJMŁODSZYCH'),
            'dziecko_main_desc'  => $this->l('Wybieraj mądrze. Certyfikowana żywność i bezpieczna pielęgnacja dla Twojego malucha.'),
            'box_accent' => $this->l('BEZPIECZNY ROZWÓJ'),
            'box_title'  => $this->l('Tylko to, co najlepsze dla Twojego dziecka'),
            'box_desc'   => $this->l('Skóra niemowlęcia jest 5 razy cieńsza niż dorosłego, a brzuszek delikatniejszy. Dlatego w naszej ofercie znajdziesz wyłącznie produkty wolne od szkodliwej chemii, pestycydów i sztucznych barwników. Zadbaj o zdrowy start swojego dziecka z naturą.'),
            'box_btn'    => $this->l('ZOBACZ PEŁNĄ OFERTĘ'),
            'is_child_ajax' => false
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
    }

    // --- 2. AJAX (POBIERANIE) ---
    public function renderAjaxContent()
    {
        // Cache Key
        $id_lang = (int)$this->context->language->id;
        $id_shop = (int)$this->context->shop->id;
        $id_curr = (int)$this->context->currency->id;
        $dateKey = date('YmdH');
        
        $cacheId = 'dziecko_' . $id_shop . '_' . $id_lang . '_' . $id_curr . '_' . $dateKey;

        $cache = Cache::getInstance();
        if ($cache->exists($cacheId)) {
            return $cache->get($cacheId);
        }

        // Pobieramy 8 produktów (zapas na mobile swipe)
        $products = StrefaDzieckaHelper::getRandomProductsFromCategory(16, 8);

        $this->context->smarty->assign([
            'dziecko_products' => $products,
            'is_child_ajax' => true
        ]);

        $content = $this->fetch('module:'.$this->name.'/views/templates/hook/block_home.tpl');
        
        $cache->set($cacheId, $content, 3600);
        return $content;
    }
}