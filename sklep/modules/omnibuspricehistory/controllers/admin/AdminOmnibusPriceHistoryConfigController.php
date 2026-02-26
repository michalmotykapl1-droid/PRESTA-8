<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler strony konfiguracyjnej modułu Historia Ceny Omnibus.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Na razie nie dołączamy serwisu, ponieważ skupiamy się na wizualizacji i strukturze zaplecza.
// require_once _PS_MODULE_DIR_ . 'omnibuspricehistory/services/OmnibusPriceHistoryService.php';

class AdminOmnibusPriceHistoryConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true; // Włącza stylizację Bootstrap dla formularza
        parent::__construct();

        $this->toolbar_title = $this->l('Ustawienia ogólne modułu Omnibus'); // Tytuł strony w panelu admina
    }
    
    /**
     * Przetwarzanie akcji POST (zapisywanie ustawień).
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitOmnibusConfig')) {
            // Sekcja 1: Ustawienia zbierania danych o cenach
            Configuration::updateValue('OMNIBUS_HISTORY_DAYS', (int)Tools::getValue('OMNIBUS_HISTORY_DAYS'));
            Configuration::updateValue('OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP', (int)Tools::getValue('OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP'));
            Configuration::updateValue('OMNIBUS_HANDLE_ATTRIBUTES', (int)Tools::getValue('OMNIBUS_HANDLE_ATTRIBUTES'));
            Configuration::updateValue('OMNIBUS_INDEX_CUSTOMER_GROUPS', (int)Tools::getValue('OMNIBUS_INDEX_CUSTOMER_GROUPS'));
            Configuration::updateValue('OMNIBUS_INDEX_COUNTRIES', (int)Tools::getValue('OMNIBUS_INDEX_COUNTRIES'));
            Configuration::updateValue('OMNIBUS_HANDLE_SPECIFIC_PRICES', (int)Tools::getValue('OMNIBUS_HANDLE_SPECIFIC_PRICES'));

            // Sekcja 2: Konfiguracja CRONa (na razie tylko pola konfiguracyjne, bez logiki CRONa)
            Configuration::updateValue('OMNIBUS_ENABLE_CRON', (int)Tools::getValue('OMNIBUS_ENABLE_CRON'));
            Configuration::updateValue('OMNIBUS_CRON_BATCH_SIZE', (int)Tools::getValue('OMNIBUS_CRON_BATCH_SIZE'));
            Configuration::updateValue('OMNIBUS_LAST_INDEX_THRESHOLD', (int)Tools::getValue('OMNIBUS_LAST_INDEX_THRESHOLD'));

            // 1) zapisujemy ustawienia dla CRONa promocji
            Configuration::updateValue('OMNIBUS_ENABLE_PROMO_CRON', (int)Tools::getValue('OMNIBUS_ENABLE_PROMO_CRON'));
            Configuration::updateValue('OMNIBUS_PROMO_CRON_BATCH_SIZE', (int)Tools::getValue('OMNIBUS_PROMO_CRON_BATCH_SIZE'));

            // Sekcja 3: Kompatybilność
            Configuration::updateValue('OMNIBUS_COMPATIBILITY_FONTAWESOME', Tools::getValue('OMNIBUS_COMPATIBILITY_FONTAWESOME'));

            $this->confirmations[] = $this->l('Ustawienia zostały zapisane.');
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOmnibusPriceHistoryConfig')); // Przekierowanie, aby odświeżyć stronę i pokazać potwierdzenie
        }
        parent::postProcess();
    }
    
    /**
     * Inicjalizacja i renderowanie widoku.
     */
    public function initContent()
    {
        parent::initContent();

        // Generujemy token dla CRONa (używamy tego samego mechanizmu co w PrestaShop)
        $cron_token = Tools::substr(Tools::encrypt('omnibuspricehistory/cron'), 0, 10);
        $cron_url = $this->context->link->getModuleLink($this->module->name, 'cron', ['token' => $cron_token], true);

        // token / URL do cron promocji (dodajemy parametr promo=1)
        $promo_token = Tools::substr(Tools::encrypt('omnibuspricehistory/cron_promo'), 0, 10);
        $promo_cron_url = $this->context->link
            ->getModuleLink($this->module->name, 'cron', [
                'token' => $promo_token,
                'promo' => 1,
            ], true);

        // Generujemy token dla formularza administracyjnego
        $admin_token = Tools::getAdminTokenLite('AdminOmnibusPriceHistoryConfig'); // <--- DODAJ TO

        // Przygotowujemy dane dla szablonu Smarty
        $this->context->smarty->assign([
            'module_name'    => $this->module->displayName,
            'current_url'    => $this->context->link->getAdminLink('AdminOmnibusPriceHistoryConfig'),
            'admin_token'    => $admin_token, // <--- DODAJ TO
            
            // Sekcja 1: Ustawienia zbierania danych o cenach
            'omnibus_history_days'          => (int)Configuration::get('OMNIBUS_HISTORY_DAYS'),
            'omnibus_overwrite_same_day_price_up' => (int)Configuration::get('OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP'),
            'omnibus_handle_attributes'     => (int)Configuration::get('OMNIBUS_HANDLE_ATTRIBUTES'),
            'omnibus_index_customer_groups' => (int)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS'),
            'omnibus_index_countries'       => (int)Configuration::get('OMNIBUS_INDEX_COUNTRIES'),
            'omnibus_handle_specific_prices' => (int)Configuration::get('OMNIBUS_HANDLE_SPECIFIC_PRICES'),

            // Sekcja 2: Konfiguracja CRONa
            'omnibus_enable_cron'           => (int)Configuration::get('OMNIBUS_ENABLE_CRON'),
            'omnibus_cron_url'              => $cron_url,
            'omnibus_cron_batch_size'       => (int)Configuration::get('OMNIBUS_CRON_BATCH_SIZE'),
            'omnibus_last_index_threshold'  => (int)Configuration::get('OMNIBUS_LAST_INDEX_THRESHOLD'),

            // Nowe ustawienia dla CRONa promocji
            'omnibus_enable_promo_cron'     => (int)Configuration::get('OMNIBUS_ENABLE_PROMO_CRON'),
            'omnibus_promo_cron_batch_size' => (int)Configuration::get('OMNIBUS_PROMO_CRON_BATCH_SIZE'),
            'omnibus_promo_cron_url'        => $promo_cron_url,

            // Sekcja 3: Kompatybilność
            'omnibus_compatibility_fontawesome' => Configuration::get('OMNIBUS_COMPATIBILITY_FONTAWESOME'),
        ]);

        $this->setTemplate('config.tpl'); // Nazwa pliku szablonu w views/templates/admin/
    }

    /**
     * Zwraca nagłówki do renderowania paska narzędzi.
     */
    public function renderToolbar()
    {
        // Ta metoda jest opcjonalna, jeśli chcesz mieć niestandardowe przyciski na górze strony.
        // Domyślnie PrestaShop dodaje przycisk "Zapisz", jeśli masz formularz.
        return parent::renderToolbar();
    }
}
