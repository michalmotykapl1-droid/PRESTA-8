<?php
// /modules/allegrocategorymapper/controllers/admin/AdminAllegroOAuthCallbackController.php

use ACM\Api\OAuthClient;
use ACM\Domain\Logger;

class AdminAllegroOAuthCallbackController extends ModuleAdminController
{
    public function initContent()
    {
        parent::initContent();

        // --- DODANY KOD DIAGNOSTYCZNY ---
        $redirectUriForTokenExchange = $this->context->link->getAdminLink('AdminAllegroOAuthCallback');
        die("KROK 2 - Adres Redirect URI do wymiany tokenu:<br><pre>" . $redirectUriForTokenExchange . "</pre><br>Porównaj ten adres z adresem z KROKU 1. Muszą być identyczne.");
        // --- KONIEC KODU DIAGNOSTYCZNEGO ---

        $debug = (int)Configuration::get('ACM_DEBUG');
        $logger = new Logger($debug);
        $code = Tools::getValue('code');

        if ($code) {
            $cli = new OAuthClient(Configuration::get('ACM_API_URL'), Configuration::get('ACM_CLIENT_ID'), Configuration::get('ACM_CLIENT_SECRET'), $logger);
            $data = $cli->exchangeCode($code, $this->context->link->getAdminLink('AdminAllegroOAuthCallback'));
            if (isset($data['access_token'])) {
                Configuration::updateValue('ACM_ACCESS_TOKEN', $data['access_token']);
                Configuration::updateValue('ACM_REFRESH_TOKEN', $data['refresh_token']);
                Configuration::updateValue('ACM_TOKEN_EXPIRES', time() + (int)$data['expires_in']);
                $this->confirmations[] = $this->l('Autoryzacja Allegro zakończona sukcesem.');
            } else {
                $this->errors[] = 'OAuth exchange failed.';
            }
        } else {
            $this->errors[] = 'Missing code.';
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'allegrocategorymapper']));
    }
}