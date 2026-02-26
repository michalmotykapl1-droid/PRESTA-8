<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler strony do konfiguracji wyświetlania modułu Historia Ceny Omnibus na froncie.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminOmnibusPriceHistoryDisplayController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->toolbar_title = $this->l('Konfiguracja wyświetlania na sklepie');
    }
    
    /**
     * Przetwarzanie akcji POST (zapisywanie ustawień wyświetlania).
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitOmnibusDisplayConfig')) {
            // Sekcja 1: Najniższa cena z 30 dni przed promocją
            Configuration::updateValue('OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE', (int)Tools::getValue('OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE'));
            Configuration::updateValue('OMNIBUS_PROMO_PRICE_HOOK', Tools::getValue('OMNIBUS_PROMO_PRICE_HOOK'));
            Configuration::updateValue('OMNIBUS_PROMO_PRICE_TEXT', Tools::getValue('OMNIBUS_PROMO_PRICE_TEXT'));
            Configuration::updateValue('OMNIBUS_DISPLAY_PROMO_PRICE_LISTING', (int)Tools::getValue('OMNIBUS_DISPLAY_PROMO_PRICE_LISTING'));
            Configuration::updateValue('OMNIBUS_PROMO_PRICE_LISTING_TEXT', Tools::getValue('OMNIBUS_PROMO_PRICE_LISTING_TEXT')); // Nowe pole dla tekstu na liście produktów
            Configuration::updateValue('OMNIBUS_DISPLAY_PROMO_PRICE_CART', (int)Tools::getValue('OMNIBUS_DISPLAY_PROMO_PRICE_CART'));
            Configuration::updateValue('OMNIBUS_PROMO_PRICE_FONT_SIZE', Tools::getValue('OMNIBUS_PROMO_PRICE_FONT_SIZE'));
            Configuration::updateValue('OMNIBUS_PROMO_PRICE_FONT_COLOR', Tools::getValue('OMNIBUS_PROMO_PRICE_FONT_COLOR'));
            Configuration::updateValue('OMNIBUS_PROMO_PRICE_PRICE_COLOR', Tools::getValue('OMNIBUS_PROMO_PRICE_PRICE_COLOR')); // Zmieniona nazwa dla jasności

            // Sekcja 2: Prezentacja historii cen (Opcjonalnie)
            Configuration::updateValue('OMNIBUS_ENABLE_FULL_HISTORY', (int)Tools::getValue('OMNIBUS_ENABLE_FULL_HISTORY'));
            Configuration::updateValue('OMNIBUS_FULL_HISTORY_SCOPE', Tools::getValue('OMNIBUS_FULL_HISTORY_SCOPE'));
            Configuration::updateValue('OMNIBUS_FULL_HISTORY_DISPLAY_TYPE', Tools::getValue('OMNIBUS_FULL_HISTORY_DISPLAY_TYPE'));
            Configuration::updateValue('OMNIBUS_FULL_HISTORY_BAR_COLOR', Tools::getValue('OMNIBUS_FULL_HISTORY_BAR_COLOR'));
            Configuration::updateValue('OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR', Tools::getValue('OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR'));
            Configuration::updateValue('OMNIBUS_FULL_HISTORY_TEXT_COLOR', Tools::getValue('OMNIBUS_FULL_HISTORY_TEXT_COLOR'));
            Configuration::updateValue('OMNIBUS_FULL_HISTORY_FONT_SIZE', Tools::getValue('OMNIBUS_FULL_HISTORY_FONT_SIZE'));
            Configuration::updateValue('OMNIBUS_FULL_HISTORY_PRICE_COLOR', Tools::getValue('OMNIBUS_FULL_HISTORY_PRICE_COLOR')); // Nowe pole dla koloru ceny w historii
            Configuration::updateValue('OMNIBUS_DISPLAY_LOWEST_PRICE_INFO', (int)Tools::getValue('OMNIBUS_DISPLAY_LOWEST_PRICE_INFO'));
            Configuration::updateValue('OMNIBUS_LOWEST_PRICE_INFO_TEXT', Tools::getValue('OMNIBUS_LOWEST_PRICE_INFO_TEXT')); // Nowe pole dla tekstu najniższej ceny w historii

            $this->confirmations[] = $this->l('Ustawienia wyświetlania zostały zapisane.');
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOmnibusPriceHistoryDisplay'));
        }
        parent::postProcess();
    }
    
    /**
     * Inicjalizacja i renderowanie widoku.
     */
    public function initContent()
    {
        parent::initContent();

        $admin_token = Tools::getAdminTokenLite('AdminOmnibusPriceHistoryDisplay');

        // Lista dostępnych hooków na stronie produktu, które są faktycznie obsługiwane przez moduł
        $available_product_page_hooks = [
            ['id' => 'displayProductPriceBlock_after_price', 'name' => $this->l('Po cenie produktu (displayProductPriceBlock_after_price)')],
            ['id' => 'displayProductPriceBlock_before_price', 'name' => $this->l('Przed ceną produktu (displayProductPriceBlock_before_price)')],
            ['id' => 'displayProductAdditionalInfo', 'name' => $this->l('Dodatkowe informacje o produkcie (displayProductAdditionalInfo)')],
            ['id' => 'displayCustomOmnibusPriceHistory', 'name' => $this->l('Własny hook modułu (displayCustomOmnibusPriceHistory)')],
            // Możesz dodać inne hooki, jeśli zostaną zaimplementowane i zarejestrowane w omnibuspricehistory.php
            // ['id' => 'displayFooterProduct', 'name' => $this->l('Stopka strony produktu (displayFooterProduct)')],
            // ['id' => 'displayProductButtons', 'name' => $this->l('W sekcji przycisków (displayProductButtons)')],
        ];

        // Przygotowujemy dane dla szablonu Smarty
        $this->context->smarty->assign([
            'module_name'    => $this->module->displayName,
            'current_url'    => $this->context->link->getAdminLink('AdminOmnibusPriceHistoryDisplay'),
            'admin_token'    => $admin_token,
            
            // Sekcja 1: Najniższa cena z 30 dni przed promocją
            'omnibus_display_promo_price_product_page' => (int)Configuration::get('OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE'),
            'omnibus_promo_price_hook'                 => Configuration::get('OMNIBUS_PROMO_PRICE_HOOK'),
            'omnibus_promo_price_text'                 => Configuration::get('OMNIBUS_PROMO_PRICE_TEXT'),
            'omnibus_display_promo_price_listing'      => (int)Configuration::get('OMNIBUS_DISPLAY_PROMO_PRICE_LISTING'),
            'omnibus_promo_price_listing_text'         => Configuration::get('OMNIBUS_PROMO_PRICE_LISTING_TEXT'),
            'omnibus_display_promo_price_cart'         => (int)Configuration::get('OMNIBUS_DISPLAY_PROMO_PRICE_CART'),
            'omnibus_promo_price_font_size'            => Configuration::get('OMNIBUS_PROMO_PRICE_FONT_SIZE'),
            'omnibus_promo_price_font_color'           => Configuration::get('OMNIBUS_PROMO_PRICE_FONT_COLOR'),
            'omnibus_promo_price_price_color'          => Configuration::get('OMNIBUS_PROMO_PRICE_PRICE_COLOR'),
            'omnibus_promo_price_hook_options'         => $available_product_page_hooks, // Przekazujemy dostępne hooki

            // Sekcja 2: Prezentacja historii cen (Opcjonalnie)
            'omnibus_enable_full_history'      => (int)Configuration::get('OMNIBUS_ENABLE_FULL_HISTORY'),
            'omnibus_full_history_scope'       => Configuration::get('OMNIBUS_FULL_HISTORY_SCOPE'),
            'omnibus_full_history_display_type' => Configuration::get('OMNIBUS_FULL_HISTORY_DISPLAY_TYPE'),
            'omnibus_full_history_bar_color'   => Configuration::get('OMNIBUS_FULL_HISTORY_BAR_COLOR'),
            'omnibus_full_history_lowest_bar_color' => Configuration::get('OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR'),
            'omnibus_full_history_text_color'  => Configuration::get('OMNIBUS_FULL_HISTORY_TEXT_COLOR'),
            'omnibus_full_history_font_size'   => Configuration::get('OMNIBUS_FULL_HISTORY_FONT_SIZE'),
            'omnibus_full_history_price_color' => Configuration::get('OMNIBUS_FULL_HISTORY_PRICE_COLOR'),
            'omnibus_display_lowest_price_info' => (int)Configuration::get('OMNIBUS_DISPLAY_LOWEST_PRICE_INFO'),
            'omnibus_lowest_price_info_text'   => Configuration::get('OMNIBUS_LOWEST_PRICE_INFO_TEXT'),
        ]);

        $this->setTemplate('display_config.tpl');
    }
}
