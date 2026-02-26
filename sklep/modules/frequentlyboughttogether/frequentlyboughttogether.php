<?php
/*
 * frequentlyboughttogether – "Często kupowane razem"
 * PrestaShop 1.7 / 8.x
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Dołączamy naszą klasę z logiką
require_once dirname(__FILE__) . '/classes/FbtProductProvider.php';

use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductPresenter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

class FrequentlyBoughtTogether extends Module
{
    public function __construct()
    {
        $this->name = 'frequentlyboughttogether';
        $this->tab = 'front_office_features';
        $this->version = '1.0.4'; // Podbita wersja (Fix Object/Array)
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Często kupowane razem');
        $this->description = $this->l('Wyświetla sugerowane produkty na karcie produktu (Amazon style).');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        return
            $this->registerHook('header') &&
            $this->registerHook('displayBBFrequentlyBoughtTogether') &&
            $this->registerHook('displayCartModalFooter');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookHeader($params)
    {
        // Ładujemy style CSS i JS tylko tam gdzie trzeba
        if ($this->context->controller->php_self === 'product' || $this->context->controller->php_self === 'category' || $this->context->controller->php_self === 'index') {
            $this->context->controller->registerStylesheet(
                'module-'.$this->name.'-css',
                'modules/'.$this->name.'/views/css/frequentlyboughttogether.css',
                ['media' => 'all', 'priority' => 150]
            );
            
            $this->context->controller->registerJavascript(
                'module-'.$this->name.'-js',
                'modules/'.$this->name.'/views/js/frequentlyboughttogether.js',
                ['position' => 'bottom', 'priority' => 150]
            );
        }
    }

    /**
     * Karta produktu – Wyświetlanie 2 losowych produktów
     * FIX: Obsługa sytuacji, gdy $params['product'] jest Obiektem (np. na Home Page)
     */
    public function hookDisplayBBFrequentlyBoughtTogether($params)
    {
        // Bezpieczne pobranie produktu
        if (!isset($params['product'])) {
            return '';
        }

        $productInput = $params['product'];
        $id_product = 0;
        $id_category = 0;
        $productForTemplate = null;

        // 1. Sprawdzamy czy to Tablica (Standard na karcie produktu)
        if (is_array($productInput) && isset($productInput['id_product'])) {
            $id_product = (int)$productInput['id_product'];
            $id_category = (int)($productInput['id_category_default'] ?? 0);
            $productForTemplate = $productInput;
        } 
        // 2. Sprawdzamy czy to Obiekt (Np. moduł wyprzedaży na Home)
        elseif (is_object($productInput) && isset($productInput->id)) {
            $id_product = (int)$productInput->id;
            $id_category = (int)$productInput->id_category_default;

            // Konwersja Obiektu na Tablicę (Presenter), żeby szablon .tpl nie wyrzucał błędu
            $assembler = new ProductAssembler($this->context);
            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = $presenterFactory->getPresenter();
            
            $productForTemplate = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct(['id_product' => $id_product]),
                $this->context->language
            );
        } else {
            return ''; // Nie rozpoznano formatu produktu
        }

        if (!$id_product) {
            return '';
        }

        // Pobieramy produkty powiązane
        $provider = new FbtProductProvider($this->context);
        $products = $provider->getRelatedProducts($id_product, $id_category, 2);

        if (empty($products)) {
            return '';
        }

        // Przekazujemy do szablonu
        $this->context->smarty->assign([
            'fbt_title' => $this->l('Często kupowane razem'),
            'fbt_products' => $products,
            // Nadpisujemy 'product' bezpieczną wersją (tablicą), aby uniknąć błędu "Cannot use object as array"
            'product' => $productForTemplate 
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/bb_fbt_product.tpl');
    }

    /**
     * MODAL KOSZYKA
     * Tutaj też dodajemy zabezpieczenie Object vs Array
     */
    public function hookDisplayCartModalFooter($params)
    {
        if (!isset($params['product'])) {
            return '';
        }

        $productInput = $params['product'];
        $id_product = 0;
        $id_category = 0;

        if (is_array($productInput) && isset($productInput['id_product'])) {
            $id_product = (int)$productInput['id_product'];
            $id_category = isset($productInput['id_category_default']) ? (int)$productInput['id_category_default'] : 0;
        } elseif (is_object($productInput) && isset($productInput->id)) {
            $id_product = (int)$productInput->id;
            $id_category = isset($productInput->id_category_default) ? (int)$productInput->id_category_default : 0;
        } else {
            return '';
        }

        // Jeśli nie mamy kategorii (np. dziwny obiekt), dociągamy ją
        if (!$id_category && $id_product) {
            $prodObj = new Product($id_product);
            $id_category = (int)$prodObj->id_category_default;
        }

        // Pobieramy produkty powiązane
        $provider = new FbtProductProvider($this->context);
        $products = $provider->getRelatedProducts($id_product, $id_category, 2); 

        if (empty($products)) {
            return '';
        }

        $this->context->smarty->assign([
            'fbt_title' => $this->l('Często kupowane razem'),
            'fbt_products' => $products,
            'fbt_context' => 'modal',
            'product' => $params['product']
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/bb_fbt_modal.tpl');
    }
}