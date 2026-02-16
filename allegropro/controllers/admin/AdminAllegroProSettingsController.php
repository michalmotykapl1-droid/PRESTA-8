<?php
/**
 * ALLEGRO PRO - Back Office controller
 */

use AllegroPro\Service\Config;
use AllegroPro\Service\LabelConfig;
use AllegroPro\Repository\AccountRepository;

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

        if (isset($this->module) && method_exists($this->module, 'ensureDbSchema')) {
            $this->module->ensureDbSchema();
        }

        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }
        
        $this->handlePost();

        $redirectUri = $this->context->link->getModuleLink($this->module->name, 'oauthcallback', [], true);
        
        // Pobieramy instancję configu etykiet, by znać aktualne ustawienia do widoku
        $labelConfig = new LabelConfig();

        $accRepo = new AccountRepository();
        $accounts = $accRepo->all();
        foreach ($accounts as &$a) {
            $a['shipx_token_set'] = !empty($a['shipx_token']);
        }
        unset($a);

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

            // InPost ShipX token (pomocniczo w module)
            'allegropro_shipx_accounts' => $accounts,
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


        // Obsługa InPost ShipX token (informacyjnie / do szybkiego wklejenia)
        if (Tools::isSubmit('submitAllegroProShipX')) {
            $token = trim((string)Tools::getValue('ALLEGROPRO_SHIPX_TOKEN'));
            $accountIds = Tools::getValue('ALLEGROPRO_SHIPX_ACCOUNTS');
            $action = Tools::getValue('ALLEGROPRO_SHIPX_ACTION');

            if (!is_array($accountIds)) {
                $accountIds = $accountIds ? [$accountIds] : [];
            }
            $accountIds = array_values(array_filter(array_map('intval', $accountIds)));

            if (empty($accountIds)) {
                $this->errors[] = $this->l('Wybierz co najmniej jedno konto Allegro.');
                return;
            }

            $repo = new AccountRepository();

            if ($action === 'clear') {
                foreach ($accountIds as $idAcc) {
                    $repo->setShipXToken($idAcc, null);
                }
                $this->confirmations[] = $this->l('Token ShipX usunięty z zaznaczonych kont.');
                return;
            }

            if ($token === '') {
                $this->errors[] = $this->l('Wklej token API ShipX (InPost).');
                return;
            }

            foreach ($accountIds as $idAcc) {
                $repo->setShipXToken($idAcc, $token);
            }
            $this->confirmations[] = $this->l('Token ShipX zapisany dla zaznaczonych kont.');
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