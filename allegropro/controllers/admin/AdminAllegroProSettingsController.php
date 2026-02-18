<?php
/**
 * ALLEGRO PRO - Back Office controller
 */

use AllegroPro\Service\Config;
use AllegroPro\Service\LabelConfig;

class AdminAllegroProSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }
        
        $this->handlePost();

        $redirectUri = $this->context->link->getModuleLink($this->module->name, 'oauthcallback', [], true);
        
        // Pobieramy instancję configu etykiet, by znać aktualne ustawienia do widoku
        $labelConfig = new LabelConfig();

        $this->context->smarty->assign([
            'allegropro_redirect_uri' => $redirectUri,
            'allegropro_env' => Configuration::get('ALLEGROPRO_ENV') ?: 'prod',
            'allegropro_client_id' => Configuration::get('ALLEGROPRO_CLIENT_ID') ?: '',
            'allegropro_client_secret_set' => (bool) Configuration::get('ALLEGROPRO_CLIENT_SECRET'),
            
            // Nowe ustawienia etykiet przekazywane do szablonu
            'allegropro_label_format' => $labelConfig->getFileFormat(),
            'allegropro_label_size' => $labelConfig->getPageSize(),
            
            'allegropro_pkg' => Config::pkgDefaults(),
            'shop_defaults' => [
                'name' => Configuration::get('PS_SHOP_NAME'),
                'addr1' => Configuration::get('PS_SHOP_ADDR1'),
                'code' => Configuration::get('PS_SHOP_CODE'),
                'city' => Configuration::get('PS_SHOP_CITY'),
                'email' => Configuration::get('PS_SHOP_EMAIL'),
                'phone' => Configuration::get('PS_SHOP_PHONE'),
            ],
        ]);

        $this->setTemplate('settings.tpl');
    }

    private function handlePost()
    {
        // Obsługa OAuth
        if (Tools::isSubmit('submitAllegroProOauth')) {
            $env = Tools::getValue('ALLEGROPRO_ENV');
            $clientId = trim((string)Tools::getValue('ALLEGROPRO_CLIENT_ID'));
            $clientSecret = (string)Tools::getValue('ALLEGROPRO_CLIENT_SECRET');

            if (!in_array($env, ['prod', 'sandbox'], true)) {
                $env = 'prod';
            }

            if ($clientId === '') {
                $this->errors[] = $this->l('Client ID jest wymagany.');
                return;
            }

            Configuration::updateValue('ALLEGROPRO_ENV', $env);
            Configuration::updateValue('ALLEGROPRO_CLIENT_ID', $clientId);

            if ($clientSecret !== '') {
                Configuration::updateValue('ALLEGROPRO_CLIENT_SECRET', $clientSecret);
            }

            $this->confirmations[] = $this->l('Ustawienia OAuth zapisane.');
        }

        // Obsługa Ustawień Domyślnych i Etykiet
        if (Tools::isSubmit('submitAllegroProDefaults')) {
            // Zapis ustawień etykiet (Nowe pola)
            Configuration::updateValue('ALLEGROPRO_LABEL_FORMAT', Tools::getValue('ALLEGROPRO_LABEL_FORMAT'));
            Configuration::updateValue('ALLEGROPRO_LABEL_SIZE', Tools::getValue('ALLEGROPRO_LABEL_SIZE'));

            // Zapis domyślnych parametrów paczki
            Configuration::updateValue('ALLEGROPRO_PKG_TYPE', Tools::getValue('ALLEGROPRO_PKG_TYPE') ?: 'OTHER');
            Configuration::updateValue('ALLEGROPRO_PKG_LEN', (int)Tools::getValue('ALLEGROPRO_PKG_LEN'));
            Configuration::updateValue('ALLEGROPRO_PKG_WID', (int)Tools::getValue('ALLEGROPRO_PKG_WID'));
            Configuration::updateValue('ALLEGROPRO_PKG_HEI', (int)Tools::getValue('ALLEGROPRO_PKG_HEI'));
            Configuration::updateValue('ALLEGROPRO_PKG_WGT', (float)Tools::getValue('ALLEGROPRO_PKG_WGT'));
            Configuration::updateValue('ALLEGROPRO_PKG_TEXT', (string)Tools::getValue('ALLEGROPRO_PKG_TEXT'));

            $this->confirmations[] = $this->l('Domyślne parametry przesyłek i etykiet zapisane.');
        }
    }
}