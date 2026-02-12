<?php
/**
 * KONTROLER KONT - WERSJA DIAGNOSTYCZNA
 * Zamiast przekierowywać, pokazuje link i instrukcję.
 */

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\OAuthService;

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
        
        // Jeśli mamy akcję autoryzacji, wyświetlamy widok diagnostyczny
        if (Tools::getValue('action') === 'authorize') {
            $this->renderAuthorizeView();
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
        // Generujemy poprawny link wg PrestaShop
        $redirectUri = $this->context->link->getModuleLink($this->module->name, 'oauthcallback', [], true);

        $this->context->smarty->assign([
            'allegropro_accounts' => $accounts,
            'allegropro_redirect_uri' => $redirectUri,
            'allegropro_client_id_set' => (bool)Configuration::get('ALLEGROPRO_CLIENT_ID'),
            'allegropro_client_secret_set' => (bool)Configuration::get('ALLEGROPRO_CLIENT_SECRET'),
            'allegropro_env' => Configuration::get('ALLEGROPRO_ENV') ?: 'prod',
            'admin_link' => $this->context->link->getAdminLink('AdminAllegroProAccounts'),
        ]);

        $this->setTemplate('accounts.tpl');
    }

    private function renderAuthorizeView()
    {
        $id = (int)Tools::getValue('id_allegropro_account');
        $acc = $this->repo->get($id);
        
        $clientId = trim((string)Configuration::get('ALLEGROPRO_CLIENT_ID'));
        $clientSecret = trim((string)Configuration::get('ALLEGROPRO_CLIENT_SECRET'));
        
        // Generujemy URI dynamicznie - to jest adres, który PrestaShop uważa za poprawny
        $redirectUri = $this->context->link->getModuleLink('allegropro', 'oauthcallback', [], true);

        // Generujemy STATE
        $randomId = bin2hex(random_bytes(16));
        $this->repo->setOauthState($id, $randomId);
        $adminReturnUrl = $this->context->link->getAdminLink('AdminAllegroProAccounts');
        $encodedUrl = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($adminReturnUrl));
        $statePayload = $randomId . '.' . $encodedUrl;

        // Scopes
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
            'allegro:api:bids', 'allegro:api:ads', 'allegro:api:campaigns'
        ];

        $env = ((int)$acc['sandbox'] === 1) ? 'sandbox' : (Configuration::get('ALLEGROPRO_ENV') ?: 'prod');
        $baseUrl = ($env === 'sandbox') ? 'https://allegro.pl.allegrosandbox.pl/auth/oauth/authorize' : 'https://allegro.pl/auth/oauth/authorize';
        
        $finalUrl = $baseUrl . '?response_type=code&client_id=' . $clientId . '&redirect_uri=' . urlencode($redirectUri) . '&state=' . $statePayload . '&scope=' . implode('%20', $scopes);

        // Wyświetlamy prosty HTML debugujący
        echo '
        <div style="padding:40px; background:#f4f4f4; font-family:sans-serif; text-align:center;">
            <div style="background:#fff; padding:30px; max-width:800px; margin:0 auto; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.1);">
                <h1 style="color:#ff5a00;">Naprawa Autoryzacji</h1>
                <p>Zanim klikniesz przycisk, <strong>SKOPIUJ</strong> poniższy adres i wklej go w ustawieniach swojej aplikacji na Allegro w polu "Redirect URI":</p>
                
                <div style="background:#ffffd0; padding:15px; border:1px solid #e0e0e0; font-family:monospace; font-size:16px; word-break:break-all; margin:20px 0;">
                    ' . $redirectUri . '
                </div>

                <p style="color:#666; font-size:13px;">(Musi być identyczny co do znaku! Sprawdź czy jest https i www)</p>
                
                <hr style="margin:30px 0; border:0; border-top:1px solid #eee;">
                
                <a href="' . $finalUrl . '" style="background:#ff5a00; color:#fff; padding:15px 30px; text-decoration:none; font-size:20px; border-radius:5px; font-weight:bold; display:inline-block;">
                    PRZEJDŹ DO ALLEGRO (AUTORYZUJ)
                </a>
            </div>
        </div>';
    }

    private function handleActions()
    {
        if (Tools::isSubmit('allegropro_add_account')) {
            $label = trim((string)Tools::getValue('label'));
            $sandbox = (int)Tools::getValue('sandbox') === 1;
            $active = (int)Tools::getValue('active') === 1;
            $isDefault = (int)Tools::getValue('is_default') === 1;
            if ($label !== '') {
                $this->repo->create($label, $sandbox, $active, $isDefault);
                Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token.'&conf=3');
            }
        }
        if (Tools::isSubmit('allegropro_delete_account')) {
            $id = (int)Tools::getValue('id_allegropro_account');
            if ($id) {
                $this->repo->delete($id);
                Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token.'&conf=1');
            }
        }
        if (Tools::isSubmit('allegropro_toggle_active')) {
            $id = (int)Tools::getValue('id_allegropro_account');
            $acc = $this->repo->get($id);
            if ($acc) {
                $this->repo->update($id, ['active' => ((int)$acc['active'] === 1 ? 0 : 1)]);
                Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token.'&conf=4');
            }
        }
        if (Tools::isSubmit('allegropro_set_default')) {
            $id = (int)Tools::getValue('id_allegropro_account');
            if ($id) {
                $this->repo->update($id, ['is_default' => 1]);
                Tools::redirectAdmin(self::$currentIndex.'&token='.$this->token.'&conf=4');
            }
        }
    }
}