<?php
/**
 * 2007-2023 PrestaShop
 *
 * Module: Historia Ceny Omnibus
 * PrestaShop 1.7 ↔ 8.x
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Dołączamy wszystkie pliki serwisowe, które są używane przez ten moduł.
require_once __DIR__ . '/services/OmnibusPriceHistoryService.php';

// Klasy PrestaShop są autoloadowane w PrestaShop 1.7+, więc ręczne require_once są zbędne
// i mogą powodować konflikty. Poniższe linie są zakomentowane celowo.
// require_once _PS_CORE_DIR_ . '/classes/Product.php';
// require_once _PS_CORE_DIR_ . '/classes/Image.php';
// require_once _PS_CORE_DIR_ . '/classes/Tab.php';
// require_once _PS_CORE_DIR_ . '/classes/Language.php';
// require_once _PS_CORE_DIR_ . '/classes/Group.php';
// require_once _PS_CORE_DIR_ . '/classes/Validate.php';
// require_once _PS_CORE_DIR_ . '/classes/SpecificPrice.php';
// require_once _PS_CORE_DIR_ . '/classes/Hook.php';


// KLUCZOWA ZMIANA: Nazwa klasy zmieniona na Omnibuspricehistory (małe 'p' w 'price')
// Aby była zgodna z konwencją autoloader'a PrestaShop (ucfirst($moduleName))
class Omnibuspricehistory extends Module
{
    /** @var OmnibusPriceHistoryService */
    public $omnibusPriceHistoryService;
    protected $_logFile;

    public function __construct()
    {
        $this->name            = 'omnibuspricehistory';
        $this->tab             = 'front_office_features';
        $this->version         = '1.0.0';
        $this->author          = 'Twoja Nazwa Firmy';
        $this->need_instance   = 1;
        $this->bootstrap       = true;

        parent::__construct();

        $this->displayName     = $this->l('Historia Ceny Omnibus');
        $this->description     = $this->l('Moduł do zbierania i wyświetlania najniższej ceny produktu z 30 dni przed promocją, zgodnie z dyrektywą Omnibus.');
        $this->confirmUninstall= $this->l('Czy na pewno chcesz odinstalować moduł Historii Ceny Omnibus? Wszystkie zebrane dane zostaną usunięte!');

        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];

        $this->omnibusPriceHistoryService = new OmnibusPriceHistoryService($this);
        $this->_logFile = dirname(__FILE__) . '/omnibus_debug_log.txt';
    }

    /**
     * Metoda instalacji modułu.
     */
    public function install()
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL: start diagnostic'."\n", FILE_APPEND);

        if (!class_exists('SpecificPrice')) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL: brak klasy SpecificPrice - SKIPTING SPECIFIC PRICE HOOKS'."\n", FILE_APPEND);
            $this->_errors[] = $this->l('Błąd: Klasa SpecificPrice jest niedostępna. Upewnij się, że Prestashop jest aktualny i spełnia wymagania.');
            return false;
        }
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL: klasa SpecificPrice sprawdzona'."\n", FILE_APPEND);

        if (!parent::install()
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('actionProductUpdate')
            || !$this->registerHooks()
            || !$this->installTabs()
            || !$this->setInitialConfiguration()
            || !$this->omnibusPriceHistoryService->createPriceHistoryTable()
        ) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL: Błąd podczas instalacji (jeden z kroków zwrócił false).'."\n", FILE_APPEND);
            return false;
        }

        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL: koniec (TRUE)'."\n", FILE_APPEND);
        return true;
    }

    /**
     * Metoda deinstalacji modułu.
     */
    public function uninstall()
    {
        $log_file = $this->_logFile;
        $log_message_prefix = '[' . date('Y-m-d H:i:s').'] ';
        @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL: Rozpoczynam deinstalację modułu.' . "\n", FILE_APPEND);

        $result = true;

        if (!$this->uninstallTabs()) {
            $result = false;
            @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL_ERROR: Błąd podczas odinstalowywania zakładek.' . "\n", FILE_APPEND);
        } else {
            @file_put_contents($log_message_prefix . 'UNINSTALL_SUCCESS: Zakładki odinstalowane pomyślnie.' . "\n", FILE_APPEND);
        }

        if (!$this->deleteConfiguration()) {
            $result = false;
            @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL_ERROR: Błąd podczas usuwania konfiguracji.' . "\n", FILE_APPEND);
        } else {
            @file_put_contents($log_message_prefix . 'UNINSTALL_SUCCESS: Konfiguracja usunięta pomyślnie.' . "\n", FILE_APPEND);
        }

        if (!$this->omnibusPriceHistoryService->dropPriceHistoryTable()) {
            $result = false;
            @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL_ERROR: Błąd podczas usuwania tabeli historii cen.' . "\n", FILE_APPEND);
        } else {
            @file_put_contents($log_message_prefix . 'UNINSTALL_SUCCESS: Tabela historii cen usunięta pomyślnie.' . "\n", FILE_APPEND);
        }

        if (!parent::uninstall()) {
            $result = false;
            @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL_ERROR: Błąd podczas wywoływania parent::uninstall().' . "\n", FILE_APPEND);
        } else {
            @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL_SUCCESS: Parent uninstall zakończony pomyślnie.' . "\n", FILE_APPEND);
        }

        return $result;
    }

    /**
     * Rejestruje hooki używane przez moduł.
     */
    protected function registerHooks()
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] REGISTER_HOOKS: start (wersja niezawodna)'."\n", FILE_APPEND);

        $hooks = [
            'actionAdminControllerSetMedia',
            'actionProductSave',
            'actionUpdateQuantity',
            'actionAttributePostSave',
            'actionAdminProductsControllerSave',
            'displayProductPriceBlock',
            'displayProductAdditionalInfo',
            'displayProductListReviews',
            'displayShoppingCartFooter',
            'displayCustomOmnibusPriceHistory',
            'actionObjectSpecificPriceAddAfter',
            'actionObjectSpecificPriceUpdateAfter',
            'actionObjectSpecificPriceDeleteAfter',
        ];

        foreach ($hooks as $hookName) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] REGISTER_HOOKS: Próba rejestracji hooka: '.$hookName."\n", FILE_APPEND);

            $id_hook_exists = Hook::getIdByName($hookName);
            if (!$id_hook_exists) {
                @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] REGISTER_HOOKS: OSTRZEŻENIE - Hook "'.$hookName.'" nie istnieje w tej wersji PrestaShop lub bazie ps_hook. Pomijam rejestrację.'."\n", FILE_APPEND);
                continue;
            }

            if ($this->isRegisteredInHook($id_hook_exists)) {
                @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] REGISTER_HOOKS: Moduł już zarejestrowany do hooka '.$hookName.'. Pomijam.'."\n", FILE_APPEND);
                continue;
            }

            if (!$this->registerHook($hookName)) {
                PrestaShopLogger::addLog("OMNIBUS_ERROR: Nie udało się zarejestrować modułu do hooka $hookName", 3, null, 'Module', (int)$this->id);
                @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] REGISTER_HOOKS: BŁĄD rejestracji hooka '.$hookName.'. ZATRZYMUJĘ INSTALACJĄ!'."\n", FILE_APPEND);
                return false;
            } else {
                @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] REGISTER_HOOKS: Zarejestrowano hooka '.$hookName.' OK.'."\n", FILE_APPEND);
            }
        }

        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] REGISTER_HOOKS: koniec (TRUE)'."\n", FILE_APPEND);
        return true;
    }

    /**
     * Instaluje zakładki w menu administracyjnym.
     */
    protected function installTabs()
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: start'."\n", FILE_APPEND);
        $languages = Language::getLanguages(true);

        $parentTab = new Tab();
        $parentTab->active = 1;
        $parentTab->class_name = 'AdminOmnibusPriceHistoryParent';
        $parentTab->name = [];
        foreach ($languages as $lang) {
            $parentTab->name[$lang['id_lang']] = $this->l('Omnibus Historia Ceny');
        }
        $parentTab->id_parent = (int) Tab::getIdFromClassName('IMPROVE');
        $parentTab->module = $this->name;
        if (!$parentTab->add()) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: Parent Tab failed'."\n", FILE_APPEND);
            return false;
        }
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: Parent Tab OK'."\n", FILE_APPEND);

        $tab1 = new Tab();
        $tab1->active = 1;
        $tab1->class_name = 'AdminOmnibusPriceHistoryConfig';
        $tab1->name = [];
        foreach ($languages as $lang) {
            $tab1->name[$lang['id_lang']] = $this->l('Ustawienia ogólne');
        }
        $tab1->id_parent = (int)$parentTab->id;
        $tab1->module = $this->name;
        if (!$tab1->add()) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: Config Tab failed'."\n", FILE_APPEND);
            return false;
        }
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: Config Tab OK'."\n", FILE_APPEND);

        $tab2 = new Tab();
        $tab2->active = 1;
        $tab2->class_name = 'AdminOmnibusPriceHistoryDisplay';
        $tab2->name = [];
        foreach ($languages as $lang) {
            $tab2->name[$lang['id_lang']] = $this->l('Wyświetlanie na sklepie');
        }
        $tab2->id_parent = (int)$parentTab->id;
        $tab2->module = $this->name;
        if (!$tab2->add()) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: Display Tab failed'."\n", FILE_APPEND);
            return false;
        }
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: Display Tab OK'."\n", FILE_APPEND);

        $tab3 = new Tab();
        $tab3->active = 1;
        $tab3->class_name = 'AdminOmnibusPriceHistoryDebug';
        $tab3->name = [];
        foreach ($languages as $lang) {
            $tab3->name[$lang['id_lang']] = $this->l('Podgląd cen historycznych');
        }
        $tab3->id_parent = (int)$parentTab->id;
        $tab3->module = $this->name;
        if (!$tab3->add()) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: Debug Tab failed'."\n", FILE_APPEND);
            return false;
        }
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: Debug Tab OK'."\n", FILE_APPEND);

        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] INSTALL_TABS: koniec (TRUE)'."\n", FILE_APPEND);
        return true;
    }

    /**
     * Usuwa zakładki z menu administracyjnego.
     */
    protected function uninstallTabs()
    {
        $log_file = $this->_logFile;
        $log_message_prefix = '[' . date('Y-m-d H:i:s').'] ';
        @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL_TABS: Rozpoczynam usuwanie zakładek.' . "\n", FILE_APPEND);

        $tabs = [
            'AdminOmnibusPriceHistoryParent',
            'AdminOmnibusPriceHistoryConfig',
            'AdminOmnibusPriceHistoryDisplay',
            'AdminOmnibusPriceHistoryDebug',
        ];
        $all_deleted = true;
        foreach ($tabs as $className) {
            $idTab = (int)Tab::getIdFromClassName($className);
            if ($idTab) {
                $tab = new Tab($idTab);
                if (!$tab->delete()) {
                    @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL_TABS_ERROR: Nie udało się usunąć zakładki: ' . $className . "\n", FILE_APPEND);
                    $all_deleted = false;
                } else {
                    @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL_TABS_SUCCESS: Usunięto zakładkę: ' . $className . "\n", FILE_APPEND);
                }
            } else {
                @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL_TABS_INFO: Zakładka nie istnieje (już usunięta lub nigdy nie istniała): ' . $className . "\n", FILE_APPEND);
            }
        }
        return $all_deleted;
    }

    /**
     * Ustawia domyślne wartości konfiguracyjne modułu.
     */
    protected function setInitialConfiguration()
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] SET_INITIAL_CONFIG: start'."\n", FILE_APPEND);

        Configuration::updateValue('OMNIBUS_HISTORY_DAYS', 30);
        Configuration::updateValue('OMNIBUS_ENABLE_CRON', 0);
        Configuration::updateValue('OMNIBUS_CRON_BATCH_SIZE', 100);
        Configuration::updateValue('OMNIBUS_LAST_INDEX_THRESHOLD', 24);

        Configuration::updateValue('OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE', 1);
        Configuration::updateValue('OMNIBUS_PROMO_PRICE_HOOK', 'displayProductPriceBlock_after_price');
        Configuration::updateValue('OMNIBUS_PROMO_PRICE_TEXT', 'Najniższa cena z 30 dni przed promocją: {price}');
        Configuration::updateValue('OMNIBUS_DISPLAY_PROMO_PRICE_LISTING', 1);
        Configuration::updateValue('OMNIBUS_PROMO_PRICE_LISTING_TEXT', 'Najniższa cena: {price}');
        Configuration::updateValue('OMNIBUS_DISPLAY_PROMO_PRICE_CART', 1);
        Configuration::updateValue('OMNIBUS_PROMO_PRICE_FONT_SIZE', 12);
        Configuration::updateValue('OMNIBUS_PROMO_PRICE_FONT_COLOR', '#FF0000');
        Configuration::updateValue('OMNIBUS_PROMO_PRICE_PRICE_COLOR', '#FF0000');

        Configuration::updateValue('OMNIBUS_ENABLE_FULL_HISTORY', 0);
        Configuration::updateValue('OMNIBUS_FULL_HISTORY_SCOPE', 'promotions_only');
        Configuration::updateValue('OMNIBUS_FULL_HISTORY_DISPLAY_TYPE', 'popup_line_chart_modern');
        Configuration::updateValue('OMNIBUS_FULL_HISTORY_FONT_SIZE', 11);
        Configuration::updateValue('OMNIBUS_FULL_HISTORY_BAR_COLOR', '#007bff');
        Configuration::updateValue('OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR', '#28a745');
        Configuration::updateValue('OMNIBUS_FULL_HISTORY_TEXT_COLOR', '#333333');
        Configuration::updateValue('OMNIBUS_FULL_HISTORY_FONT_SIZE', 11);
        Configuration::updateValue('OMNIBUS_FULL_HISTORY_PRICE_COLOR', '#333333');
        Configuration::updateValue('OMNIBUS_DISPLAY_LOWEST_PRICE_INFO', 0);
        Configuration::updateValue('OMNIBUS_LOWEST_PRICE_INFO_TEXT', 'To jest najniższa cena tego produktu w ciągu ostatnich 30 dni ({price} z dnia {date}).');

        Configuration::updateValue('OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP', 0);
        Configuration::updateValue('OMNIBUS_HANDLE_ATTRIBUTES', 1);
        Configuration::updateValue('OMNIBUS_INDEX_CUSTOMER_GROUPS', 0);
        Configuration::updateValue('OMNIBUS_INDEX_COUNTRIES', 0);
        Configuration::updateValue('OMNIBUS_HANDLE_SPECIFIC_PRICES', 1);
        Configuration::updateValue('OMNIBUS_COMPATIBILITY_FONTAWESOME', 'auto');

        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] SET_INITIAL_CONFIG: koniec (TRUE)'."\n", FILE_APPEND);
        return true;
    }

    /**
     * Usuwa zmienne konfiguracyjne modułu.
     */
    protected function deleteConfiguration()
    {
        $log_file = $this->_logFile;
        $log_message_prefix = '[' . date('Y-m-d H:i:s').'] ';
        @file_put_contents($log_file, $log_message_prefix . 'UNINSTALL: Rozpoczynam usuwanie konfiguracji.' . "\n", FILE_APPEND);

        $configsToDelete = [
            'OMNIBUS_HISTORY_DAYS',
            'OMNIBUS_ENABLE_CRON',
            'OMNIBUS_CRON_BATCH_SIZE',
            'OMNIBUS_LAST_INDEX_THRESHOLD',
            'OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE',
            'OMNIBUS_PROMO_PRICE_HOOK',
            'OMNIBUS_PROMO_PRICE_TEXT',
            'OMNIBUS_DISPLAY_PROMO_PRICE_LISTING',
            'OMNIBUS_PROMO_PRICE_LISTING_TEXT',
            'OMNIBUS_DISPLAY_PROMO_PRICE_CART',
            'OMNIBUS_PROMO_PRICE_FONT_SIZE',
            'OMNIBUS_PROMO_PRICE_FONT_COLOR',
            'OMNIBUS_PROMO_PRICE_PRICE_COLOR',
            'OMNIBUS_ENABLE_FULL_HISTORY',
            'OMNIBUS_FULL_HISTORY_SCOPE',
            'OMNIBUS_FULL_HISTORY_DISPLAY_TYPE',
            'OMNIBUS_FULL_HISTORY_BAR_COLOR',
            'OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR',
            'OMNIBUS_FULL_HISTORY_TEXT_COLOR',
            'OMNIBUS_FULL_HISTORY_FONT_SIZE',
            'OMNIBUS_FULL_HISTORY_PRICE_COLOR',
            'OMNIBUS_DISPLAY_LOWEST_PRICE_INFO',
            'OMNIBUS_LOWEST_PRICE_INFO_TEXT',
            'OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP',
            'OMNIBUS_HANDLE_ATTRIBUTES',
            'OMNIBUS_INDEX_CUSTOMER_GROUPS',
            'OMNIBUS_INDEX_COUNTRIES',
            'OMNIBUS_HANDLE_SPECIFIC_PRICES',
            'OMNIBUS_COMPATIBILITY_FONTAWESOME',
        ];

        $all_deleted = true;
        foreach ($configsToDelete as $configName) {
            if (!Configuration::deleteByName($configName)) {
                @file_put_contents($log_file, $log_message_prefix . 'DELETE_CONFIG_ERROR: Nie udało się usunąć konfiguracji: ' . $configName . "\n", FILE_APPEND);
                $all_deleted = false;
            } else {
                @file_put_contents($log_file, $log_message_prefix . 'DELETE_CONFIG_SUCCESS: Usunięto konfigurację: ' . $configName . "\n", FILE_APPEND);
            }
        }
        return $all_deleted;
    }

    /**
     * Hook do dołączania CSS/JS w panelu administracyjnym.
     */
    public function hookActionAdminControllerSetMedia()
    {
        $currentController = Tools::getValue('controller');
        if (in_array($currentController, [
            'AdminOmnibusPriceHistoryConfig',
            'AdminOmnibusPriceHistoryDisplay',
            'AdminOmnibusPriceHistoryDebug'
        ])) {
            // załaduj select2
            $this->context->controller->addJqueryPlugin('select2');
            // potem swój CSS/JS
            $this->context->controller->addCSS($this->_path . 'views/css/admin_omnibuspricehistory.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin_omnibuspricehistory.js');
        }
    }

    /**
     * Hook do wczytywania zasobów na froncie sklepu oraz przekazywania URL-a AJAX do JS.
     */
    public function hookDisplayHeader(array $params)
    {
        // Wygeneruj URL kontrolera AJAX w czystym kontekście PHP
        $ajaxUrl = $this->context->link->getModuleLink('omnibuspricehistory', 'ajax', [], true);

        // Przekaż URL do JS globalnie za pomocą Media::addJsDef()
        Media::addJsDef([
            'omnibusAjaxUrl'                   => $ajaxUrl,
            'omnibusFullHistoryDisplayType'    => Configuration::get('OMNIBUS_FULL_HISTORY_DISPLAY_TYPE'),
            'omnibusFullHistoryFontSize'       => (int) Configuration::get('OMNIBUS_FULL_HISTORY_FONT_SIZE'),
            'omnibusFullHistoryBarColor'       => Configuration::get('OMNIBUS_FULL_HISTORY_BAR_COLOR'),
            'omnibusFullHistoryLowestBarColor' => Configuration::get('OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR'),
            'omnibusFullHistoryTextColor'      => Configuration::get('OMNIBUS_FULL_HISTORY_TEXT_COLOR'),
        ]);

        // Upewnij się, że załadujesz swój skrypt frontowy tędy, a nie ręcznie w tpl
        // Sprawdzamy, czy jesteśmy na stronie produktu, kategorii lub w koszyku,
        // aby nie ładować zasobów niepotrzebnie na każdej stronie.
        if ($this->context->controller->php_self === 'product' || $this->context->controller->php_self === 'category' || $this->context->controller->php_self === 'cart') {
            $this->context->controller->registerStylesheet(
                'module-omnibuspricehistory-css',
                'modules/'.$this->name.'/views/css/front_omnibuspricehistory.css',
                ['media' => 'all', 'priority' => 150]
            );
            $this->context->controller->registerJavascript(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                [
                    'position' => 'head',
                    'priority' => 100,
                ]
            );
            // Dodajemy również front_omnibuspricehistory.js tutaj, aby był ładowany tylko raz
            $this->context->controller->registerJavascript(
                'module-omnibuspricehistory-front-js',
                'modules/'.$this->name.'/views/js/front_omnibuspricehistory.js',
                [
                    'position' => 'bottom',
                    'priority' => 150,
                ]
            );
        }
    }


    /**
     * Hook wywoływany po aktualizacji produktu.
     * @param array $params Zawiera obiekt produktu (product)
     */
    public function hookActionProductUpdate($params)
    {
        $log_file = $this->_logFile;
        // Sprawdzamy, czy skrypt jest uruchamiany w kontekście CRON (bez pracownika i klienta)
        $is_cron_context = (
            !Validate::isLoadedObject(Context::getContext()->employee) &&
            !Validate::isLoadedObject(Context::getContext()->customer)
        );

        // Cron X13 wymaga obecności pracownika w kontekście, inaczej getPriceStatic rzuca wyjątek
        if ($is_cron_context) {
            Context::getContext()->employee = new Employee(1); // 
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Wstrzyknięto sztucznego pracownika (ID 1) dla kontekstu CRON.' . "\n", FILE_APPEND);
        }

        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: (GLOBAL) hookActionProductUpdate wywołany. Kontekst CRON: ' . ($is_cron_context ? 'Tak' : 'Nie') . "\n", FILE_APPEND);

        /** @var Product $product */
        $product = null;
        $id_product = 0;

        // Próba normalizacji obiektu produktu z różnych typów parametrów hooka
        if (isset($params['product'])) {
            if (is_array($params['product'])) {
                // PS 8.x - tablica z danymi
                $id_product = (int) ($params['product']['id_product'] ?? $params['product']['id'] ?? 0);
            } elseif ($params['product'] instanceof \PrestaShop\PrestaShop\Core\Product\ProductListing\Hook\ProductListingLazyArray) {
                // PS 8.x - obiekt lazy array
                $id_product = (int) $params['product']->getId();
            } elseif ($params['product'] instanceof Product) {
                // Stary sposób - obiekt Product
                $product = $params['product'];
                $id_product = (int)$product->id;
            }
        } elseif (isset($params['id_product'])) { // Jeśli przekazano tylko id_product (często w importach/cronach)
            $id_product = (int)$params['id_product'];
        }

        // Jeśli id_product jest dostępne, spróbuj załadować obiekt Product
        if ($id_product > 0) {
            $product = new Product($id_product);
            if (!Validate::isLoadedObject($product)) {
                $product = null; // Resetuj produkt, jeśli nie udało się go załadować
            }
        }

        if (!$product) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Błąd: Brak obiektu produktu lub obiekt nieprawidłowy do załadowania. Pomijam zapis.' . "\n", FILE_APPEND);
            return; // Zakończ funkcję, jeśli produktu nie ma
        }

        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Produkt ID: ' . $product->id . "\n", FILE_APPEND);

        $id_shop = (int)(Context::getContext()->shop->id ?? Configuration::get('PS_SHOP_DEFAULT'));
        $id_currency = (int)(Context::getContext()->currency->id ?? Configuration::get('PS_CURRENCY_DEFAULT'));
        if ($id_currency === 0) {
            $id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        }

        $id_country = (bool)Configuration::get('OMNIBUS_INDEX_COUNTRIES') ? ( (int)(Context::getContext()->country->id ?? Configuration::get('PS_COUNTRY_DEFAULT')) ) : 0;
        if ($id_country === 0 && (bool)Configuration::get('OMNIBUS_INDEX_COUNTRIES')) {
             $id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
        }

        $id_group = 0; // Domyślnie 0 dla CRON
        if (!$is_cron_context) { // Użyj prawdziwej grupy tylko jeśli nie jest to kontekst crona
            $currentGroup = Group::getCurrent();
            $id_group = (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS') ? ( (isset($currentGroup->id_group) && $currentGroup->id_group > 0) ? (int)$currentGroup->id_group : (int)Configuration::get('PS_CUSTOMER_GROUP') ) : 0;
            if ($id_group === 0 && (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS')) {
                 $id_group = (int)Configuration::get('PS_CUSTOMER_GROUP');
            }
        }
        
        $specific_price_output_base = null;
        
        // Obliczamy cenę bazową
        $base_product_price = Product::getPriceStatic(
            $product->id,
            true, // $usetax
            null, // $id_product_attribute (null dla produktu bazowego)
            6,    // $decimals
            null, // $divisor
            false,// $only_reduc
            false, // <--- WAŻNE: pobierz cenę BEZ promocji
            1,    // $quantity
            false,// $force_associated_tax
            null, // $id_customer (może być null, bo mamy employee w kontekście)
            null, // $id_cart (może być null, bo mamy employee w kontekście)
            null, // $id_address
            $specific_price_output_base,
            true, // $with_ecotax
            ($is_cron_context ? false : (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS')) // use_group_reduction: false dla CRON
        );
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Cena dla produktu bazowego (bez promocji) pobrana: ' . $base_product_price . "\n", FILE_APPEND);
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Specific Price dla bazowego (nieużywane dla ceny): ' . json_encode($specific_price_output_base) . "\n", FILE_APPEND);

        // Zapisujemy cenę produktu prostego (bazowego)
        $this->omnibusPriceHistoryService->saveProductPriceToHistory($product->id, $base_product_price, 0, 'manual', $id_shop, $id_currency, $id_country, $id_group);


        // Przetwarzamy kombinacje, jeśli istnieją
        if ((bool)Configuration::get('OMNIBUS_HANDLE_ATTRIBUTES') && $product->hasAttributes()) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Produkt ma kombinacje. Przetwarzam...' . "\n", FILE_APPEND);
            $combinations = $product->getCombinations();
            foreach ($combinations as $combination) {
                $id_product_attribute = (int)$combination['id_product_attribute'];
                $specific_price_output_combo = null;

                $combination_price = Product::getPriceStatic(
                    $product->id,
                    true, // $usetax
                    $id_product_attribute,
                    6,    // $decimals
                    null, // $divisor
                    false,// $only_reduc
                    false, // <--- WAŻNE: pobierz cenę BEZ promocji
                    1,    // $quantity
                    false,// $force_associated_tax
                    null, // $id_customer (może być null, bo mamy employee w kontekście)
                    null, // $id_cart (może być null, bo mamy employee w kontekście)
                    null, // $id_address
                    $specific_price_output_combo,
                    true, // $with_ecotax
                    ($is_cron_context ? false : (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS')) // use_group_reduction: false dla CRON
                );
                @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Kombinacja ID: ' . $id_product_attribute . ', Cena (bez promocji): ' . $combination_price . "\n", FILE_APPEND);
                @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Specific Price dla kombinacji (nieużywane dla ceny): ' . json_encode($specific_price_output_combo) . "\n", FILE_APPEND);
                
                $this->omnibusPriceHistoryService->saveProductPriceToHistory($product->id, $combination_price, $id_product_attribute, 'manual', $id_shop, $id_currency, $id_country, $id_group);
            }
        }
    }

    /**
     * Hook wywoływany po zapisie produktu (również dla kombinacji).
     */
    public function hookActionProductSave($params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: hookActionProductSave wywołany. Produkt ID: ' . (isset($params['product']->id) ? (int)$params['product']->id : 'N/A') . "\n", FILE_APPEND);
        $this->hookActionProductUpdate($params);
    }

    /**
     * Hook wywoływany po aktualizacji ilości.
     * Brak zapisu ceny, bo to zmiana ilości, nie ceny bazowej.
     */
    public function hookActionUpdateQuantity($params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: hookActionUpdateQuantity wywołany. Produkt ID: ' . (isset($params['id_product']) ? (int)$params['id_product'] : 'N/A') . ". (Brak zapisu ceny, bo to zmiana ilości)." . "\n", FILE_APPEND);
    }

    /**
     * Hook wywoływany po zapisie atrybutu (kombinacji).
     * Brak zapisu ceny, bo to zmiana atrybutu, która nie zawsze oznacza zmianę ceny bazowej.
     */
    public function hookActionAttributePostSave($params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: hookActionAttributePostSave wywołany. Atrybut ID: ' . (isset($params['id_product_attribute']) ? (int)$params['id_product_attribute'] : 'N/A') . ". (Brak zapisu ceny, bo to to zmiana atrybutu bez zmiany ceny)." . "\n", FILE_APPEND);
    }

    /**
     * Hook wywoływany po zapisie produktu z poziomu kontrolera AdminProducts.
     * Cena jest obsługiwana przez hookActionProductUpdate, który jest wywoływany po tej akcji.
     */
    public function hookActionAdminProductsControllerSave($params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: hookActionAdminProductsControllerSave wywołany. Produkt ID: ' . (isset($params['id_product']) ? (int)$params['id_product'] : 'N/A') . ". (Brak zapisu ceny bezpośrednio, cena jest obsługiwana przez inne hooki)." . "\n", FILE_APPEND);
    }

    /**
     * Po utworzeniu nowej ceny specyficznej (promocja) - obsługa actionObjectSpecificPriceAddAfter
     * @param array $params Zawiera 'object' (instancja SpecificPrice)
     */
    public function hookActionObjectSpecificPriceAddAfter(array $params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: actionObjectSpecificPriceAddAfter wywołany.' . "\n", FILE_APPEND);

        $params['specific_price'] = $params['object'];
        return $this->hookActionSpecificPriceUpdate($params);
    }

    /**
     * Po aktualizacji ceny specyficznej - obsługa actionObjectSpecificPriceUpdateAfter
     * @param array $params Zawiera 'object' (instancja SpecificPrice)
     */
    public function hookActionObjectSpecificPriceUpdateAfter(array $params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: actionObjectSpecificPriceUpdateAfter wywołany.' . "\n", FILE_APPEND);

        $params['specific_price'] = $params['object'];
        return $this->hookActionSpecificPriceUpdate($params);
    }

    /**
     * Po usunięciu ceny specyficznej - obsługa actionObjectSpecificPriceDeleteAfter
     * @param array $params Zawiera 'object' (instancja SpecificPrice)
     */
    public function hookActionObjectSpecificPriceDeleteAfter(array $params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: actionObjectSpecificPriceDeleteAfter wywołany.' . "\n", FILE_APPEND);

        if (isset($params['object']) && $params['object'] instanceof SpecificPrice && Validate::isLoadedObject($params['object'])) {
             $id_product = (int)$params['object']->id_product;
             $product = new Product($id_product);
             if (Validate::isLoadedObject($product)) {
                 $this->hookActionProductUpdate(['product' => $product]);
                 @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Called hookActionProductUpdate after SpecificPriceDelete (via Object hook).' . "\n", FILE_APPEND);
             } else {
                 @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Błąd: Brak obiektu Product object ' . $id_product . ' for SpecificPriceDelete cleanup (via Object hook).' . "\n", FILE_APPEND);
             }
        } else {
             @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Błąd: Brak obiektu SpecificPrice w hookActionObjectSpecificPriceDeleteAfter.' . "\n", FILE_APPEND);
        }
    }


    /**
     * Wspólny handler dla hookActionSpecificPriceUpdate i hookActionSpecificPriceAdd (przekierowanie z hooków Object)
     * @param array $params Zawiera 'specific_price' lub 'object' (obiekt SpecificPrice)
     */
    public function hookActionSpecificPriceUpdate($params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: (CALL) hookActionSpecificPriceUpdate wywołany.' . "\n", FILE_APPEND);

        /** @var SpecificPrice $specific_price_obj */
        $specific_price_obj = null;
        if (isset($params['specific_price']) && Validate::isLoadedObject($params['specific_price'])) {
            $specific_price_obj = $params['specific_price'];
        } elseif (isset($params['object']) && $params['object'] instanceof SpecificPrice && Validate::isLoadedObject($params['object'])) {
            $specific_price_obj = $params['object'];
        }

        if (!$specific_price_obj) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Błąd: Brak obiektu SpecificPrice lub obiekt nieprawidłowy w parametrach hooka hookActionSpecificPriceUpdate (brak specific_price/object).' . "\n", FILE_APPEND);
            return;
        }

        $id_product = (int)$specific_price_obj->id_product;
        $id_product_attribute = (int)$specific_price_obj->id_product_attribute;

        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Specific Price: id_specific_price=' . (int)$specific_price_obj->id . ', Product ID: ' . $id_product . ', Attribute ID: ' . $id_product_attribute . "\n", FILE_APPEND);
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Specific Price details: price=' . (float)$specific_price_obj->price . ', reduction=' . (float)$specific_price_obj->reduction . ', reduction_type=' . $specific_price_obj->reduction_type . ', from=' . $specific_price_obj->from . ', to=' . $specific_price_obj->to . "\n", FILE_APPEND);

        $product = new Product($id_product);
        if (!Validate::isLoadedObject($product)) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Błąd: Nie można załadować produktu ' . $id_product . ' z hookActionSpecificPriceUpdate.' . "\n", FILE_APPEND);
            return;
        }

        $id_shop = (int)($specific_price_obj->id_shop ?? Context::getContext()->shop->id ?? Configuration::get('PS_SHOP_DEFAULT'));
        $id_currency = (int)$specific_price_obj->id_currency;
        if ($id_currency === 0) {
            $id_currency = (int)Context::getContext()->currency->id;
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Specific Price id_currency był 0, ustawiono na kontekstową: ' . $id_currency . "\n", FILE_APPEND);
        }

        $id_country = (int)$specific_price_obj->id_country;
        if ($id_country === 0 && (bool)Configuration::get('OMNIBUS_INDEX_COUNTRIES')) {
             $id_country = (int)Context::getContext()->country->id;
             @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Specific Price id_country był 0, ustawiono na kontekstowy: ' . $id_country . "\n", FILE_APPEND);
        }

        $id_group = (int)$specific_price_obj->id_group;
        if ($id_group === 0 && (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS')) {
             $id_group = (int)Group::getCurrent()->id;
             if ($id_group === 0) {
                 $id_group = (int)Configuration::get('PS_CUSTOMER_GROUP');
             }
             @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Specific Price id_group był 0, ustawiono na kontekstową/domyślną: ' . $id_group . "\n", FILE_APPEND);
        }

        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Context for saving specific price: shop=' . $id_shop . ', currency=' . $id_currency . ', country=' . $id_country . ', group=' . $id_group . "\n", FILE_APPEND);

        $today = date('Y-m-d');
        $existing_base_price_entry = Db::getInstance()->getRow('
            SELECT `id_omnibus_price_history`, `price`
            FROM `' . _DB_PREFIX_ . 'omnibus_price_history`
            WHERE `id_product` = ' . (int)$id_product . '
            AND `id_product_attribute` = ' . (int)$id_product_attribute . '
            AND `id_shop` = ' . (int)$id_shop . '
            AND `id_currency` = ' . (int)$id_currency . '
            AND `id_country` = ' . (int)$id_country . '
            AND `id_group` = ' . (int)$id_group . '
            AND `change_type` = \'manual\'
            AND DATE(`date_add`) = \'' . pSQL($today) . '\'
        ');

        if (!$existing_base_price_entry) {
            $specific_price_output_dummy = null;
            $base_product_price = Product::getPriceStatic(
                $id_product,
                true,
                $id_product_attribute,
                6,
                null,
                false,
                false, // <--- KLUCZOWE: pobierz cenę BEZ redukcji
                1,
                false,
                null,
                null,
                null,
                $specific_price_output_dummy,
                true,
                (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS')
            );
            $save_base_result = $this->omnibusPriceHistoryService->saveProductPriceToHistory(
                $id_product,
                $base_product_price,
                $id_product_attribute,
                'manual',
                $id_shop,
                $id_currency,
                $id_country,
                $id_group
            );
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Automatyczny zapis ceny bazowej (manual) po wykryciu promocji: ' . ($save_base_result ? 'SUCCESS' : 'FAILURE') . ' Cena: ' . $base_product_price . "\n", FILE_APPEND);
        } else {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Cena bazowa (manual) z dzisiaj już istnieje. Pomijam automatyczny zapis.' . "\n", FILE_APPEND);
        }

        $specific_price_output_dummy = null;
        $current_calculated_price = Product::getPriceStatic(
            $id_product,
            true,
            $id_product_attribute,
            6,
            null,
            false,
            true, // $usereduc (tak, uwzględnij redukcje)
            1,
            false,
            null,
            null,
            null,
            $specific_price_output_dummy,
            true,
            (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS')
        );

        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Aktualna cena (uwzględniająca specyficzną) pobrana: ' . $current_calculated_price . "\n", FILE_APPEND);

        $save_result = $this->omnibusPriceHistoryService->saveProductPriceToHistory(
            $id_product,
            $current_calculated_price,
            $id_product_attribute,
            'promotion',
            $id_shop,
            $id_currency,
            $id_country,
            $id_group
        );
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: saveProductPriceToHistory result (promotion): ' . ($save_result ? 'SUCCESS' : 'FAILURE') . "\n", FILE_APPEND);
    }

    /**
     * Hook do wyświetlania informacji o cenie produktu.
     */
    public function hookDisplayProductPriceBlock($params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_ENTRY: Wywołano hookDisplayProductPriceBlock. Typ: ' . ($params['type'] ?? 'N/A') . "\n", FILE_APPEND);

        $configured_hook = Configuration::get('OMNIBUS_PROMO_PRICE_HOOK');
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: Konfiguracja hooka z bazy: ' . $configured_hook . "\n", FILE_APPEND);

        if (isset($params['type']) && $configured_hook === 'displayProductPriceBlock_' . $params['type']) {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Warunek dopasowania typu hooka spełniony. Typ haka: " . $params['type'] . ", Skonfigurowany hook: " . $configured_hook . "\n", FILE_APPEND);

            $product = null;
            $id_product = 0;

            if (isset($params['product'])) {
                if (is_array($params['product'])) {
                    $id_product = (int) ($params['product']['id_product'] ?? $params['product']['id'] ?? 0);
                    if ($id_product > 0) {
                        $product = new Product($id_product, false, $this->context->language->id);
                        if (!Validate::isLoadedObject($product)) {
                            $product = null;
                            @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Nie udało się załadować obiektu Product z tablicy ID: " . $id_product . "\n", FILE_APPEND);
                        } else {
                            @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Obiekt Product załadowany z tablicy. ID: " . $id_product . "\n", FILE_APPEND);
                        }
                    }
                } elseif ($params['product'] instanceof \PrestaShop\PrestaShop\Core\Product\ProductListing\Hook\ProductListingLazyArray) {
                    $id_product = (int) $params['product']->getId();
                    if ($id_product > 0) {
                        $product = new Product($id_product, false, $this->context->language->id);
                        if (!Validate::isLoadedObject($product)) {
                            $product = null;
                            @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Nie udało się załadować obiektu Product z LazyArray ID: " . $id_product . "\n", FILE_APPEND);
                        } else {
                            @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Obiekt Product załadowany z LazyArray. ID: " . $id_product . "\n", FILE_APPEND);
                        }
                    }
                } elseif ($params['product'] instanceof Product) {
                    $product = $params['product'];
                    $id_product = (int)$product->id;
                    @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Obiekt Product załadowany ze starego sposobu. ID: " . $id_product . "\n", FILE_APPEND);
                }
            }

            if (!$product && Tools::getValue('controller') == 'product' && Tools::getIsset('id_product')) {
                $id_product = (int)Tools::getValue('id_product');
                if ($id_product > 0) {
                    $product = new Product($id_product, false, $this->context->language->id);
                    if (Validate::isLoadedObject($product)) {
                        @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Obiekt Product załadowany z id_product z GET. ID: " . $id_product . "\n", FILE_APPEND);
                    } else {
                        $product = null;
                        @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Nie udało się załadować obiektu Product z id_product z GET: " . $id_product . "\n", FILE_APPEND);
                    }
                }
            }


            $product_id_to_log = ($product instanceof Product) ? $product->id : 'Brak obiektu Product!';
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Wejście do warunku. ID produktu: " . $product_id_to_log . "\n", FILE_APPEND);

            $id_product_attribute = $params['id_product_attribute'] ?? 0;
            if ($id_product_attribute === 0 && $product && $product->hasAttributes()) {
                $default_id_product_attribute = Product::getDefaultAttribute($product->id);
                if ($default_id_product_attribute > 0) {
                    $id_product_attribute = $default_id_product_attribute;
                    @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Ustawiono id_product_attribute na domyślny: " . $id_product_attribute . "\n", FILE_APPEND);
                }
                if (Tools::getIsset('id_product_attribute')) {
                    $id_product_attribute = (int)Tools::getValue('id_product_attribute');
                    @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Ustawiono id_product_attribute z GET: " . $id_product_attribute . "\n", FILE_APPEND);
                }
            }
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Końcowe id_product_attribute: " . $id_product_attribute . "\n", FILE_APPEND);


            $display_data = $this->omnibusPriceHistoryService->getOmnibusPriceDisplayDataForHook(
                $product,
                $id_product_attribute,
                $params['type']
            );

            $data_to_log = json_encode($display_data, JSON_PRETTY_PRINT);
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] LOG_DEBUG: Dane zwrócone przez serwis: " . $data_to_log . "\n", FILE_APPEND);

            if ($display_data && !empty($display_data['omnibus_lowest_price_info'])) {
                @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] HOOK_SUCCESS: Warunek spełniony. Renderowanie szablonu.\n", FILE_APPEND);
                $this->context->smarty->assign($display_data);
                $this->context->smarty->assign([
                    'product' => $product,
                    'id_product_attribute' => $id_product_attribute,
                    'omnibus_enable_full_history' => (bool)Configuration::get('OMNIBUS_ENABLE_FULL_HISTORY'),
                    'omnibus_full_history_scope' => Configuration::get('OMNIBUS_FULL_HISTORY_SCOPE'),
                    'omnibus_full_history_display_type' => Configuration::get('OMNIBUS_FULL_HISTORY_DISPLAY_TYPE'),
                    'omnibus_full_history_font_size' => Configuration::get('OMNIBUS_FULL_HISTORY_FONT_SIZE'),
                    'omnibus_full_history_bar_color' => Configuration::get('OMNIBUS_FULL_HISTORY_BAR_COLOR'),
                    'omnibus_full_history_lowest_bar_color' => Configuration::get('OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR'),
                    'omnibus_full_history_text_color' => Configuration::get('OMNIBUS_FULL_HISTORY_TEXT_COLOR'),
                    'omnibus_full_history_price_color' => Configuration::get('OMNIBUS_FULL_HISTORY_PRICE_COLOR'),
                    'omnibus_display_lowest_price_info' => (bool)Configuration::get('OMNIBUS_DISPLAY_LOWEST_PRICE_INFO'),
                    'omnibus_lowest_price_info_text' => Configuration::get('OMNIBUS_LOWEST_PRICE_INFO_TEXT'),
                    'module_dir' => $this->getPathUri(),
                ]);
                return $this->context->smarty->fetch($this->local_path . 'views/templates/hook/product_price_block.tpl');
            } else {
                @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] HOOK_FAILURE: Warunek niespełniony (brak display_data lub omnibus_lowest_price_info). Zwracam pusty wynik.\n", FILE_APPEND);
            }
        } else {
            @file_put_contents($log_file, '['.date('Y-m-d H:i:s')."] HOOK_DEBUG: Warunek dopasowania typu hooka NIE spełniony. Typ haka: " . ($params['type'] ?? 'N/A') . ", Skonfigurowany hook: " . $configured_hook . "\n", FILE_APPEND);
        }
        return '';
    }

    /**
     * Hook do wyświetlania dodatkowych informacji o produkcie.
     */
    public function hookDisplayProductAdditionalInfo($params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: hookDisplayProductAdditionalInfo wywołany.' . "\n", FILE_APPEND);

        $configured_hook = Configuration::get('OMNIBUS_PROMO_PRICE_HOOK');

        if ($configured_hook == 'displayProductAdditionalInfo') {
            $display_data = $this->omnibusPriceHistoryService->getOmnibusPriceDisplayDataForHook(
                $params['product'] ?? null,
                $params['id_product_attribute'] ?? 0,
                $params['type'] ?? null
            );

            if ($display_data) {
                $this->context->smarty->assign($display_data);
                $this->context->smarty->assign([
                    'product' => $params['product'] ?? null,
                    'id_product_attribute' => $params['id_product_attribute'] ?? 0,
                    'omnibus_enable_full_history' => (bool)Configuration::get('OMNIBUS_ENABLE_FULL_HISTORY'),
                    'omnibus_full_history_scope' => Configuration::get('OMNIBUS_FULL_HISTORY_SCOPE'),
                    'omnibus_full_history_display_type' => Configuration::get('OMNIBUS_FULL_HISTORY_DISPLAY_TYPE'),
                    'omnibus_full_history_font_size' => Configuration::get('OMNIBUS_FULL_HISTORY_FONT_SIZE'),
                    'omnibus_full_history_bar_color' => Configuration::get('OMNIBUS_FULL_HISTORY_BAR_COLOR'),
                    'omnibus_full_history_lowest_bar_color' => Configuration::get('OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR'),
                    'omnibus_full_history_text_color' => Configuration::get('OMNIBUS_FULL_HISTORY_TEXT_COLOR'),
                    'omnibus_full_history_price_color' => Configuration::get('OMNIBUS_FULL_HISTORY_PRICE_COLOR'),
                    'omnibus_display_lowest_price_info' => (bool)Configuration::get('OMNIBUS_DISPLAY_LOWEST_PRICE_INFO'),
                    'omnibus_lowest_price_info_text' => Configuration::get('OMNIBUS_LOWEST_PRICE_INFO_TEXT'),
                    'module_dir' => $this->getPathUri(),
                ]);
                return $this->context->smarty->fetch($this->local_path . 'views/templates/hook/product_price_block.tpl');
            }
        }
        return '';
    }

    /**
     * Hook do wyświetlania na listingu produktów.
     */
    public function hookDisplayProductListReviews($params)
    {
        return '';
    }

    /**
     * Hook do wyświetlania w stopce koszyka.
     */
    public function hookDisplayShoppingCartFooter($params)
    {
        return '';
    }

    /**
     * Własny hook modułu.
     */
    public function hookDisplayCustomOmnibusPriceHistory($params)
    {
        $log_file = $this->_logFile;
        @file_put_contents($log_file, '['.date('Y-m-d H:i:s').'] HOOK_DEBUG: hookDisplayCustomOmnibusPriceHistory wywołany.' . "\n", FILE_APPEND);

        $configured_hook = Configuration::get('OMNIBUS_PROMO_PRICE_HOOK');

        if ($configured_hook == 'displayCustomOmnibusPriceHistory') {
            $display_data = $this->omnibusPriceHistoryService->getOmnibusPriceDisplayDataForHook(
                $params['product'] ?? null,
                $params['id_product_attribute'] ?? 0,
                $params['type'] ?? null
            );

            if ($display_data) {
                $this->context->smarty->assign($display_data);
                $this->context->smarty->assign([
                    'omnibus_enable_full_history' => (bool)Configuration::get('OMNIBUS_ENABLE_FULL_HISTORY'),
                    'omnibus_full_history_scope' => Configuration::get('OMNIBUS_FULL_HISTORY_SCOPE'),
                    'omnibus_full_history_display_type' => Configuration::get('OMNIBUS_FULL_HISTORY_DISPLAY_TYPE'),
                    'omnibus_full_history_font_size' => Configuration::get('OMNIBUS_FULL_HISTORY_FONT_SIZE'),
                    'omnibus_full_history_bar_color' => Configuration::get('OMNIBUS_FULL_HISTORY_BAR_COLOR'),
                    'omnibus_full_history_lowest_bar_color' => Configuration::get('OMNIBUS_FULL_HISTORY_LOWEST_BAR_COLOR'),
                    'omnibus_full_history_text_color' => Configuration::get('OMNIBUS_FULL_HISTORY_TEXT_COLOR'),
                    'omnibus_full_history_price_color' => Configuration::get('OMNIBUS_FULL_HISTORY_PRICE_COLOR'),
                    'omnibus_display_lowest_price_info' => (bool)Configuration::get('OMNIBUS_DISPLAY_LOWEST_PRICE_INFO'),
                    'omnibus_lowest_price_info_text' => Configuration::get('OMNIBUS_LOWEST_PRICE_INFO_TEXT'),
                ]);
                return $this->context->smarty->fetch($this->local_path . 'views/templates/hook/product_price_block.tpl');
            }
        }
        return '';
    }
}