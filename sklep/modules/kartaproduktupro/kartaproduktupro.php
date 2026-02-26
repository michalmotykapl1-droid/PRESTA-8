<?php
/*
 * kartaproduktupro – moduł "KARTA PRODUKTU PRO"
 * PrestaShop 1.7 / 8.x
 *
 * Prosty pasek na karcie produktu, wyświetlany przez custom hook:
 *   {hook h='displayBBProductPro'}
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class KartaProduktuPro extends Module
{
    public function __construct()
    {
        $this->name = 'kartaproduktupro';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Custom';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('KARTA PRODUKTU PRO');
        $this->description = $this->l('Prosty pasek "KARTA PRODUKTU PRO" na karcie produktu.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    /**
     * Instalacja:
     * - header (CSS/JS),
     * - displayBBProductPro – custom hook na karcie produktu.
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        return
            $this->registerHook('header') &&
            $this->registerHook('displayBBProductPro');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Ładowanie zasobów CSS/JS
     */
    public function hookHeader($params)
    {
        if (method_exists($this->context->controller, 'registerStylesheet')) {
            $this->context->controller->registerStylesheet(
                'module-'.$this->name.'-css',
                'modules/'.$this->name.'/views/css/kartaproduktupro.css',
                [
                    'media' => 'all',
                    'priority' => 150,
                ]
            );
        }

        if (method_exists($this->context->controller, 'registerJavascript')) {
            $this->context->controller->registerJavascript(
                'module-'.$this->name.'-js',
                'modules/'.$this->name.'/views/js/kartaproduktupro.js',
                [
                    'position' => 'bottom',
                    'priority' => 150,
                ]
            );
        }
    }

    /**
     * Karta produktu – custom hook: displayBBProductPro
     * TPL: bb_product_pro.tpl
     */
    public function hookDisplayBBProductPro($params)
    {
        $this->context->smarty->assign([
            'kpp_title' => $this->l('KARTA PRODUKTU PRO'),
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/bb_product_pro.tpl');
    }
}
