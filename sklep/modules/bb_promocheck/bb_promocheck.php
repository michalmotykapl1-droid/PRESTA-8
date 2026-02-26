<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/PromoFinder.php';

class Bb_PromoCheck extends Module
{
    public function __construct()
    {
        $this->name = 'bb_promocheck';
        $this->tab = 'front_office_features';
        $this->version = '1.0.4'; // Podbiłem wersję
        $this->author = 'BigBio';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Sprawdzacz Łap Okazje');
        $this->description = $this->l('Wyświetla link do produktu outletowego z obliczeniem różnicy ceny.');
    }

    public function install()
    {
        return parent::install() 
            && $this->registerHook('displayPromoNotification')
            && $this->registerHook('displayHeader');
    }

    public function hookDisplayHeader()
    {
        if ($this->context->controller->php_self === 'product') {
            $this->context->controller->registerStylesheet(
                'modules-bbpromocheck-style',
                'modules/' . $this->name . '/views/css/promocheck.css',
                ['media' => 'all', 'priority' => 150]
            );
            
            $this->context->controller->registerJavascript(
                'modules-bbpromocheck-js',
                'modules/' . $this->name . '/views/js/promocheck.js',
                ['position' => 'bottom', 'priority' => 150]
            );
        }
    }

    public function hookDisplayPromoNotification($params)
    {
        // 1. Pobieramy dane o produkcie promocyjnym z klasy pomocniczej
        $promoData = PromoFinder::findData($params['product'], $this->context);

        if (!$promoData) {
            return false;
        }

        // 2. LOGIKA OBLICZANIA PROCENTU TANIEJ
        // Pobieramy ID obu produktów
        $currentProductId = (int)$params['product']['id_product'];
        $promoProductId   = (int)$promoData['id_product'];

        // Pobieramy aktualne ceny brutto (z podatkiem) dla obu produktów
        // Używamy Product::getPriceStatic, aby mieć pewność co do aktualnej ceny w bazie
        $currentPriceVal = Product::getPriceStatic($currentProductId, true, null, 2);
        $promoPriceVal   = Product::getPriceStatic($promoProductId, true, null, 2);

        $percentage = 0;

        // Wyliczamy różnicę tylko jeśli cena promocyjna jest niższa od aktualnej
        if ($currentPriceVal > 0 && $promoPriceVal < $currentPriceVal) {
            $diff = $currentPriceVal - $promoPriceVal;
            $percentage = round(($diff / $currentPriceVal) * 100);
        }

        // 3. Przekazujemy zmienne do Smarty
        $this->context->smarty->assign([
            'promo_id'         => $promoData['id_product'],
            'promo_url'        => $promoData['url'],
            'promo_price'      => $promoData['price'],      // Sformatowana cena (np. "8,53 zł")
            'promo_percentage' => $percentage,              // Wyliczony procent (int)
            'promo_total'      => $promoData['qty_total'],
            'promo_in_cart'    => $promoData['qty_in_cart'],
            'promo_left'       => $promoData['qty_left'],
            'promo_date'       => $promoData['expiry_date'],
            'static_token'     => Tools::getToken(false),
            'cart_url'         => $this->context->link->getPageLink('cart')
        ]);

        return $this->display(__FILE__, 'views/templates/hook/notification.tpl');
    }
}