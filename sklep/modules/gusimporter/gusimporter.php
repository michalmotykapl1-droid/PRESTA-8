<?php
/**
 * Importer Danych GUS (NIP)
 *
 * PrestaShop 8.1+
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class GusImporter extends Module
{
    public const CONFIG_API_KEY    = 'GUS_IMPORTER_API_KEY';
    public const CONFIG_TESTMODE   = 'GUS_IMPORTER_TEST_MODE';
    public const CONFIG_DEBUGMODE  = 'GUS_IMPORTER_DEBUG_MODE';
    public const CONFIG_VENDOR_OK  = 'GUS_IMPORTER_VENDOR_OK';

    public function __construct()
    {
        $this->name = 'gusimporter';
        $this->tab = 'administration';
        $this->version = '1.0.2';
        $this->author = 'ChatGPT';
        $this->need_instance = 0;

        $this->ps_versions_compliancy = [
            'min' => '8.0.0.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->l('Importer Danych GUS (NIP)');
        $this->description = $this->l('Automatycznie pobiera dane firmy z GUS po wpisaniu NIP w kasie.');
        $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować moduł Importer Danych GUS (NIP)?');

        // Wstępne ostrzeżenie w liście modułów, jeśli brakuje vendor/
        if (!$this->isVendorReady()) {
            $this->warning = $this->l('Biblioteka GUS API (rudashi/gusapi) nie jest jeszcze zainstalowana. Moduł spróbuje ją zainstalować automatycznie przy instalacji. Jeśli komunikat nie zniknie, uruchom ręcznie "composer install" w katalogu modułu.');
        }
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (
            !Configuration::updateValue(self::CONFIG_API_KEY, '') ||
            !Configuration::updateValue(self::CONFIG_TESTMODE, 1) ||
            !Configuration::updateValue(self::CONFIG_DEBUGMODE, 0) ||
            !Configuration::updateValue(self::CONFIG_VENDOR_OK, 0)
        ) {
            return false;
        }

        if (!$this->registerHook('actionFrontControllerSetMedia')) {
            return false;
        }

        // Próba automatycznej instalacji vendor/ przy instalacji modułu
        $this->checkAndMaybeInstallVendor();

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CONFIG_API_KEY);
        Configuration::deleteByName(self::CONFIG_TESTMODE);
        Configuration::deleteByName(self::CONFIG_DEBUGMODE);
        Configuration::deleteByName(self::CONFIG_VENDOR_OK);

        return parent::uninstall();
    }

    /**
     * Back Office configuration page
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitGusImporter')) {
            $apiKey   = (string) Tools::getValue(self::CONFIG_API_KEY);
            $testMode = (int) Tools::getValue(self::CONFIG_TESTMODE);
            $debug    = (int) Tools::getValue(self::CONFIG_DEBUGMODE);

            Configuration::updateValue(self::CONFIG_API_KEY, $apiKey);
            Configuration::updateValue(self::CONFIG_TESTMODE, $testMode ? 1 : 0);
            Configuration::updateValue(self::CONFIG_DEBUGMODE, $debug ? 1 : 0);

            // Ponowna próba instalacji vendor, jeśli jeszcze nie ma
            if (!$this->isVendorReady()) {
                $this->checkAndMaybeInstallVendor();
            }

            $output .= $this->displayConfirmation($this->l('Ustawienia zostały zapisane.'));
        }

        // Ostrzeżenie na stronie konfiguracji, jeśli vendor nadal brak
        if (!$this->isVendorReady()) {
            $modulePath = _PS_MODULE_DIR_.$this->name;
            $output .= $this->displayWarning(
                $this->l('Biblioteka GUS API (rudashi/gusapi) nie jest zainstalowana. ')
                .'<br>'.$this->l('Moduł próbował automatycznie uruchomić "composer install", ale wygląda na to, że się nie powiodło.')
                .'<br>'.$this->l('Uruchom ręcznie polecenie w katalogu modułu:').'<br><code>cd '
                .pSQL($modulePath)
                .' && composer install --no-dev --optimize-autoloader</code>'
            );
        }

        return $output.$this->renderForm();
    }

    /**
     * Render configuration form
     */
    protected function renderForm()
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Ustawienia Importera Danych GUS'),
                    'icon'  => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Klucz API GUS (produkcyjny)'),
                        'name' => self::CONFIG_API_KEY,
                        'required' => false,
                        'class' => 'fixed-width-xxl',
                        'desc' => $this->l('Wpisz swój produkcyjny klucz API do usługi GUS BIR. Jeśli pole pozostanie puste lub włączysz tryb testowy, użyty zostanie klucz testowy GUS.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Tryb testowy'),
                        'name' => self::CONFIG_TESTMODE,
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'gus_test_on',
                                'value' => 1,
                                'label' => $this->l('Włączony'),
                            ],
                            [
                                'id' => 'gus_test_off',
                                'value' => 0,
                                'label' => $this->l('Wyłączony'),
                            ],
                        ],
                        'desc' => $this->l('Jeśli włączone, moduł użyje klucza testowego GUS: abcde12345abcde12345.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Tryb debugowania'),
                        'name' => self::CONFIG_DEBUGMODE,
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'gus_debug_on',
                                'value' => 1,
                                'label' => $this->l('Włączony'),
                            ],
                            [
                                'id' => 'gus_debug_off',
                                'value' => 0,
                                'label' => $this->l('Wyłączony'),
                            ],
                        ],
                        'desc' => $this->l('Jeśli włączone, moduł będzie wypisywał dodatkowe informacje w konsoli przeglądarki oraz w odpowiedzi JSON (pole "debug").'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz'),
                    'class' => 'btn btn-primary pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->table = $this->name;
        $helper->show_toolbar = false;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGusImporter';

        $helper->fields_value = [
            self::CONFIG_API_KEY   => Configuration::get(self::CONFIG_API_KEY),
            self::CONFIG_TESTMODE  => (int) Configuration::get(self::CONFIG_TESTMODE),
            self::CONFIG_DEBUGMODE => (int) Configuration::get(self::CONFIG_DEBUGMODE),
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Hook: load JS on order page and expose API URL + debug flag to JS
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        if (!isset($this->context) || !isset($this->context->controller)) {
            return;
        }

        // Only on order/checkout controller
        $controller = $this->context->controller;
        if (!property_exists($controller, 'php_self') || $controller->php_self !== 'order') {
            return;
        }

        $apiUrl = $this->context->link->getModuleLink($this->name, 'api');

        Media::addJsDef([
            'gusImporterApiUrl' => $apiUrl,
            'gusImporterDebug'  => (bool) Configuration::get(self::CONFIG_DEBUGMODE),
        ]);

        $controller->registerJavascript(
            'module-'.$this->name.'-front',
            'modules/'.$this->name.'/views/js/front.js',
            [
                'position' => 'bottom',
                'priority' => 150,
            ]
        );
    }

    /**
     * Sprawdza, czy vendor/autoload.php jest dostępny.
     */
    protected function isVendorReady()
    {
        $moduleDir = _PS_MODULE_DIR_.$this->name.'/';
        return file_exists($moduleDir.'vendor/autoload.php');
    }

    /**
     * Próbuje automatycznie uruchomić composer install w katalogu modułu.
     * Nie blokuje działania modułu, ale zapisuje status w konfiguracji i logu.
     */
    protected function checkAndMaybeInstallVendor()
    {
        $moduleDir = _PS_MODULE_DIR_.$this->name.'/';
        $autoload  = $moduleDir.'vendor/autoload.php';

        if (file_exists($autoload)) {
            Configuration::updateValue(self::CONFIG_VENDOR_OK, 1);
            return true;
        }

        $commands = [
            'cd '.escapeshellarg($moduleDir).' && composer install --no-dev --optimize-autoloader 2>&1',
            'cd '.escapeshellarg($moduleDir).' && php composer.phar install --no-dev --optimize-autoloader 2>&1',
        ];

        $outputAll = '';
        $success = false;

        foreach ($commands as $cmd) {
            $output = '';

            if (function_exists('shell_exec')) {
                $output = (string) shell_exec($cmd);
            } elseif (function_exists('exec')) {
                $out = [];
                $ret = 0;
                exec($cmd, $out, $ret);
                $output = implode("\n", $out);
            } else {
                $output = 'Brak funkcji shell_exec/exec na serwerze.';
            }

            $outputAll .= "CMD: ".$cmd."\n".$output."\n";

            if (file_exists($autoload)) {
                $success = true;
                break;
            }
        }

        if ($success) {
            Configuration::updateValue(self::CONFIG_VENDOR_OK, 1);
            if (class_exists('PrestaShopLogger')) {
                PrestaShopLogger::addLog('[GUS Importer] composer install zakończony powodzeniem.', 1);
            }
        } else {
            Configuration::updateValue(self::CONFIG_VENDOR_OK, 0);
            if (class_exists('PrestaShopLogger')) {
                PrestaShopLogger::addLog('[GUS Importer] Nie udało się automatycznie zainstalować bibliotek (composer install). Szczegóły: '.$outputAll, 2);
            }
        }

        return $success;
    }
}
