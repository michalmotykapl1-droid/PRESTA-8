<?php
/**
 * Kontroler kont Allegro Pro.
 */

use AllegroPro\Repository\AccountRepository;

class AdminAllegroProAccountsController extends ModuleAdminController
{
    private AccountRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->repo = new AccountRepository();
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('action') === 'authorize') {
            $this->handleAuthorizeRedirect();
            return;
        }

        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }

        $this->handleActions();
        $this->renderAccountsList();
    }

    private function renderAccountsList()
    {
        $accounts = $this->repo->all();
        $redirectUri = $this->context->link->getModuleLink($this->module->name, 'oauthcallback', [], true);

        $this->context->smarty->assign([
            'allegropro_accounts' => $accounts,
            'allegropro_redirect_uri' => $redirectUri,
            'allegropro_client_id_set' => (bool) Configuration::get('ALLEGROPRO_CLIENT_ID'),
            'allegropro_client_secret_set' => (bool) Configuration::get('ALLEGROPRO_CLIENT_SECRET'),
            'allegropro_env' => Configuration::get('ALLEGROPRO_ENV') ?: 'prod',
            'admin_link' => $this->context->link->getAdminLink('AdminAllegroProAccounts'),
        ]);

        $this->setTemplate('accounts.tpl');
    }

    private function handleAuthorizeRedirect()
    {
        $id = (int) Tools::getValue('id_allegropro_account');
        if ($id <= 0) {
            $this->errors[] = $this->l('Nieprawidłowe ID konta Allegro.');
            $this->renderAccountsList();
            return;
        }

        $acc = $this->repo->get($id);
        if (!$acc) {
            $this->errors[] = $this->l('Wybrane konto Allegro nie istnieje.');
            $this->renderAccountsList();
            return;
        }

        $clientId = trim((string) Configuration::get('ALLEGROPRO_CLIENT_ID'));
        $clientSecret = trim((string) Configuration::get('ALLEGROPRO_CLIENT_SECRET'));
        if ($clientId === '' || $clientSecret === '') {
            $this->errors[] = $this->l('Najpierw uzupełnij Client ID i Client Secret w Ustawieniach modułu.');
            $this->renderAccountsList();
            return;
        }

        $redirectUri = $this->context->link->getModuleLink('allegropro', 'oauthcallback', [], true);

        $randomId = bin2hex(random_bytes(16));
        $this->repo->setOauthState($id, $randomId);
        $adminReturnUrl = $this->context->link->getAdminLink('AdminAllegroProAccounts');
        $encodedUrl = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($adminReturnUrl));
        $statePayload = $randomId . '.' . $encodedUrl;

        $scopes = [
            'allegro:api:profile:read', 'allegro:api:profile:write',
            'allegro:api:sale:offers:read', 'allegro:api:sale:offers:write',
            'allegro:api:sale:settings:read', 'allegro:api:sale:settings:write',
            'allegro:api:orders:read', 'allegro:api:orders:write',
            'allegro:api:ratings', 'allegro:api:disputes',
            'allegro:api:billing:read', 'allegro:api:payments:read',
            'allegro:api:payments:write', 'allegro:api:shipments:read',
            'allegro:api:shipments:write', 'allegro:api:fulfillment:read',
            'allegro:api:fulfillment:write', 'allegro:api:messaging',
            'allegro:api:bids', 'allegro:api:ads', 'allegro:api:campaigns',
        ];

        $env = ((int) $acc['sandbox'] === 1) ? 'sandbox' : (Configuration::get('ALLEGROPRO_ENV') ?: 'prod');
        $baseUrl = ($env === 'sandbox')
            ? 'https://allegro.pl.allegrosandbox.pl/auth/oauth/authorize'
            : 'https://allegro.pl/auth/oauth/authorize';

        $finalUrl = $baseUrl
            . '?response_type=code&client_id=' . urlencode($clientId)
            . '&redirect_uri=' . urlencode($redirectUri)
            . '&state=' . urlencode($statePayload)
            . '&scope=' . implode('%20', $scopes);

        Tools::redirect($finalUrl);
    }

    private function handleActions()
    {
        if (Tools::isSubmit('allegropro_add_account')) {
            $label = trim((string) Tools::getValue('label'));
            $sandbox = (int) Tools::getValue('sandbox') === 1;
            $active = (int) Tools::getValue('active') === 1;
            $isDefault = (int) Tools::getValue('is_default') === 1;
            if ($label !== '') {
                $this->repo->create($label, $sandbox, $active, $isDefault);
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=3');
            }
        }

        if (Tools::isSubmit('allegropro_delete_account')) {
            $id = (int) Tools::getValue('id_allegropro_account');
            if ($id) {
                $this->repo->delete($id);
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=1');
            }
        }

        if (Tools::isSubmit('allegropro_toggle_active')) {
            $id = (int) Tools::getValue('id_allegropro_account');
            $acc = $this->repo->get($id);
            if ($acc) {
                $this->repo->update($id, ['active' => ((int) $acc['active'] === 1 ? 0 : 1)]);
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=4');
            }
        }

        if (Tools::isSubmit('allegropro_set_default')) {
            $id = (int) Tools::getValue('id_allegropro_account');
            if ($id) {
                $this->repo->update($id, ['is_default' => 1]);
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=4');
            }
        }
    }
}
