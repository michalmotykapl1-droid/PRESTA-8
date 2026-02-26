<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler strony do konfiguracji wyświetlania cen za jednostkę.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Dołączamy plik z nową klasą serwisową
require_once _PS_MODULE_DIR_ . 'productpro/services/ProductProPriceCalcService.php';

class AdminProductProPriceCalcController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /**
     * Przetwarzanie akcji POST (zapisywanie ustawień).
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitPriceCalcSettings')) {
            $unit = Tools::getValue('PRODUCTPRO_PRICE_UNIT');
            if (in_array($unit, ['100g', 'kg'])) {
                Configuration::updateValue('PRODUCTPRO_PRICE_UNIT', $unit);
                $this->confirmations[] = $this->l('Ustawienia zostały zapisane.');
            } else {
                $this->errors[] = $this->l('Wybrano nieprawidłową jednostkę.');
            }
        }
        parent::postProcess();
    }
    
    /**
     * Inicjalizacja i renderowanie widoku.
     */
    public function initContent()
    {
        parent::initContent();

        // Przygotowujemy dane dla szablonu Smarty
        $this->context->smarty->assign([
            'current_unit' => Configuration::get('PRODUCTPRO_PRICE_UNIT'),
            'form_action' => $this->context->link->getAdminLink('AdminProductProPriceCalc'),
        ]);

        // Ustawiamy szablon dla tej strony
        $this->setTemplate('price_calc_settings.tpl');
    }
}