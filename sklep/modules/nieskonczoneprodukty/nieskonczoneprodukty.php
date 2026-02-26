<?php
/*
 * NieskonczoneProdukty - Fixed JS Variables
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Ładujemy Helpera (upewnij się, że plik classes/NieskonczoneHelper.php istnieje z poprzedniego kroku)
require_once(dirname(__FILE__) . '/classes/NieskonczoneHelper.php');

class NieskonczoneProdukty extends Module
{
    public function __construct()
    {
        $this->name = 'nieskonczoneprodukty';
        $this->tab = 'front_office_features';
        $this->version = '1.2.1'; // Fix błędu JS
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('NIESKOŃCZONE PRODUKTY');
        $this->description = $this->l('Infinite Scroll - Mix produktów z całej gałęzi.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBBUnfinishedProducts');
    }

    public function hookHeader()
    {
        // Ładujemy tylko na karcie produktu
        if ($this->context->controller->php_self === 'product') {
            
            // Rejestracja CSS
            $this->context->controller->registerStylesheet(
                'module-'.$this->name.'-css',
                'modules/'.$this->name.'/views/css/nieskonczoneprodukty.css',
                ['media' => 'all', 'priority' => 150]
            );

            // Rejestracja JS
            $this->context->controller->registerJavascript(
                'module-'.$this->name.'-js',
                'modules/'.$this->name.'/views/js/nieskonczoneprodukty.js',
                ['position' => 'bottom', 'priority' => 150]
            );

            // --- NAPRAWA: Definicja zmiennych musi być TUTAJ, w nagłówku ---
            Media::addJsDef([
                'np_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax'),
                'np_id_product' => (int)Tools::getValue('id_product')
            ]);
        }
    }

    public function hookDisplayBBUnfinishedProducts($params)
    {
        if (!isset($params['product']['id_product'])) {
            return '';
        }

        $id_product = (int)$params['product']['id_product'];
        $id_category_default = (int)$params['product']['id_category_default'];

        // 1. Obliczamy ID "Szerokiej Kategorii" (Drzewo - poziom 5)
        $target_category_id = NieskonczoneHelper::findBroadCategory($id_category_default, 4);

        // 2. Pobieramy produkty (Limit 12)
        $products = NieskonczoneHelper::getTreeProducts($target_category_id, $id_product, 1, 12);

        if (empty($products)) {
            return '';
        }

        // Przekazujemy zmienne do Smarty (TPL)
        $this->context->smarty->assign([
            'np_title' => $this->l('MOGĄ CIĘ ZAINTERESOWAĆ'),
            'np_products' => $products,
            // WAŻNE: Przekazujemy tu ID szerokiej kategorii, 
            // dzięki temu TPL wstawi je w data-cat-id, a JS sobie je odczyta.
            'np_id_category' => $target_category_id 
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/bb_unfinished_products.tpl');
    }
}