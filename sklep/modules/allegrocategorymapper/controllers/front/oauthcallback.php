<?php
class AllegrocategorymapperOauthcallbackModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $code  = Tools::getValue('code');
        $error = Tools::getValue('error');
        $state = Tools::getValue('state');

        if (!class_exists('\\ACM\\Domain\\Logger')) {
            require_once _PS_MODULE_DIR_.'allegrocategorymapper/src/Domain/Logger.php';
        }
        $logger = new \ACM\Domain\Logger((bool)Configuration::get('ACM_DEBUG'));

        if ($error) { $this->renderInfo('Błąd OAuth', 'OAuth error: '.pSQL($error)); return; }
        if (!$code) { $this->renderInfo('Błąd OAuth', 'Missing authorization code.'); return; }

        $savedState  = (string)Configuration::get('ACM_OAUTH_STATE');
        $redirectUri = (string)Configuration::get('ACM_OAUTH_REDIRECT');
        if ($logger->isEnabled()) $logger->add('OAuth state check', array('returned'=>$state, 'saved'=>$savedState, 'redirect'=>$redirectUri));

        if ($savedState === '') { // drugie wejście -> statyczny ekran
            $this->renderBackLink('Autoryzacja została już zakończona.', 'Możesz wrócić do konfiguracji modułu w panelu administracyjnym.');
            return;
        }
        if (!$state || !hash_equals($savedState, $state)) { $this->renderInfo('Błąd OAuth', 'OAuth error: invalid state'); return; }

        if (!class_exists('\\ACM\\Api\\OAuthClient')) require_once _PS_MODULE_DIR_.'allegrocategorymapper/src/Api/OAuthClient.php';
        $client = new \ACM\Api\OAuthClient(Configuration::get('ACM_API_URL'), Configuration::get('ACM_CLIENT_ID'), Configuration::get('ACM_CLIENT_SECRET'), $logger);
        $data = $client->exchangeCode($code, $redirectUri);

        if (isset($data['access_token'])) {
            Configuration::updateValue('ACM_ACCESS_TOKEN',  $data['access_token']);
            Configuration::updateValue('ACM_REFRESH_TOKEN', $data['refresh_token']);
            Configuration::updateValue('ACM_TOKEN_EXPIRES', time() + (int)$data['expires_in']);
            Configuration::deleteByName('ACM_OAUTH_STATE');
            Configuration::deleteByName('ACM_OAUTH_REDIRECT');
            $this->renderBackLink('Autoryzacja Allegro zakończona pomyślnie.', 'Tokeny zapisane. Przejdź do konfiguracji modułu.');
        } else {
            $this->renderInfo('Błąd OAuth', 'OAuth exchange failed: '.print_r($data, true));
        }
    }

    protected function renderBackLink($title, $message)
    {
        $adminBase = (string)Configuration::get('ACM_ADMIN_URL');
        if (!$adminBase) { // awaryjnie BO root
            $adminBase = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__;
        }
        $back = $adminBase; // pozwól BO samemu przenieść do logowania/konfiguracji

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'
            .htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</title></head><body style="font-family:sans-serif;padding:24px">';
        echo '<h2>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h2>';
        echo '<p>'.nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')).'</p>';
        echo '<p><a href="'.htmlspecialchars($back, ENT_QUOTES, 'UTF-8')
            .'" target="_top" rel="noopener" style="display:inline-block;padding:8px 12px;background:#2e6da4;color:#fff;text-decoration:none;border-radius:4px">'
            .'Przejdź do konfiguracji modułu</a></p>';
        echo '</body></html>';
        exit;
    }

    protected function renderInfo($title, $message)
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo $title."\n".$message;
        exit;
    }
}
