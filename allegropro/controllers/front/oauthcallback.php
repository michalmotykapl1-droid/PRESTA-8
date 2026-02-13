<?php
use AllegroPro\Repository\AccountRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\OAuthService;
use AllegroPro\Service\AllegroEndpoints;
use AllegroPro\Service\Config;

class AllegroProOauthcallbackModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    private function isAllowedAdminReturnUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $candidateHost = strtolower((string) ($parts['host'] ?? ''));
        $candidateScheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($candidateHost === '' || ($candidateScheme !== 'https' && $candidateScheme !== 'http')) {
            return false;
        }

        $shopBase = $this->context->shop->getBaseURL(true);
        $shopHost = strtolower((string) parse_url($shopBase, PHP_URL_HOST));
        if ($shopHost === '' || $candidateHost !== $shopHost) {
            return false;
        }

        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        $controller = (string) ($query['controller'] ?? '');
        $legacyController = (string) ($query['controllerUri'] ?? '');

        if ($controller === 'AdminAllegroProAccounts' || $legacyController === 'AdminAllegroProAccounts') {
            return true;
        }

        $path = strtolower((string) ($parts['path'] ?? ''));

        return strpos($path, 'adminallegroproaccounts') !== false;
    }

    private function renderPopupReturnPage(string $adminReturnUrl, string $msg, bool $isErr): void
    {
        $sep = (strpos($adminReturnUrl, '?') !== false) ? '&' : '?';
        $finalUrl = $adminReturnUrl . $sep . ($isErr ? 'allegropro_err=' : 'allegropro_ok=') . urlencode($msg);
        $safeFinalUrlJs = json_encode($finalUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $safeMsg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="pl"><head><meta charset="utf-8"><title>Allegro Pro</title></head><body>';
        echo '<p>' . $safeMsg . '</p>';
        echo '<p>Trwa powrót do listy kont...</p>';
        echo '<script>';
        echo 'var u=' . $safeFinalUrlJs . ';';
        echo 'if(window.opener && !window.opener.closed){window.opener.location.href=u;window.close();}';
        echo 'else{window.location.href=u;}';
        echo '</script>';
        echo '</body></html>';
        exit;
    }

    public function initContent()
    {
        parent::initContent();

        $stateFull = (string) Tools::getValue('state');
        $code = Tools::getValue('code');
        $error = Tools::getValue('error');

        $adminReturnUrl = $this->context->link->getAdminLink('AdminAllegroProAccounts');

        $parts = explode('.', $stateFull);
        $stateDb = $parts[0] ?? '';
        $returnUrlEncoded = $parts[1] ?? '';

        if ($returnUrlEncoded) {
            $base64 = str_replace(['-', '_'], ['+', '/'], $returnUrlEncoded);
            $decoded = base64_decode($base64);
            if ($this->isAllowedAdminReturnUrl((string) $decoded)) {
                $adminReturnUrl = (string) $decoded;
            }
        }

        $redirect = function (string $msg, bool $isErr) use ($adminReturnUrl) {
            $this->renderPopupReturnPage($adminReturnUrl, $msg, $isErr);
        };

        if ($error) {
            $redirect('Błąd autoryzacji Allegro: ' . $error, true);
        }

        if (!$stateDb || !$code) {
            $redirect('Brak danych autoryzacyjnych (state/code).', true);
        }

        $repo = new AccountRepository();
        $acc = $repo->findByOauthState($stateDb);

        if (!$acc) {
            $redirect('Sesja autoryzacji wygasła lub jest nieprawidłowa. Spróbuj ponownie.', true);
        }

        $clientId = Config::clientId();
        $clientSecret = Config::clientSecret();
        if (!$clientId || !$clientSecret) {
            $redirect('Client ID/Secret nie są skonfigurowane w module.', true);
        }

        $env = ((int) $acc['sandbox'] === 1) ? 'sandbox' : (Config::env());
        $redirectUri = $this->context->link->getModuleLink($this->module->name, 'oauthcallback', [], true);

        $oauth = new OAuthService(new HttpClient(), $repo);
        $token = $oauth->exchangeCode($env, $clientId, $clientSecret, $redirectUri, (string) $code);

        if (!$token['ok'] || !is_array($token['json']) || empty($token['json']['access_token'])) {
            $redirect('Błąd wymiany tokena: ' . (int) $token['code'] . ' ' . Tools::substr((string) $token['raw'], 0, 200), true);
        }

        $accessToken = (string) $token['json']['access_token'];
        $refreshToken = (string) ($token['json']['refresh_token'] ?? '');
        $expiresIn = isset($token['json']['expires_in']) ? (int) $token['json']['expires_in'] : null;

        $repo->storeTokens((int) $acc['id_allegropro_account'], $accessToken, $refreshToken, $expiresIn);

        $apiBase = AllegroEndpoints::apiBase($env);
        $http = new HttpClient();
        $me = $http->request('GET', $apiBase . '/me', [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/vnd.allegro.public.v1+json',
        ]);

        $login = null;
        $userId = null;
        if ($me['ok']) {
            $json = json_decode($me['body'], true);
            if (is_array($json)) {
                $login = $json['login'] ?? null;
                $userId = $json['id'] ?? null;
            }
        }

        $repo->update((int) $acc['id_allegropro_account'], [
            'allegro_login' => $login ? pSQL((string) $login) : null,
            'allegro_user_id' => $userId ? pSQL((string) $userId) : null,
            'oauth_state' => null,
        ]);

        $redirect('Konto Allegro zostało poprawnie autoryzowane.', false);
    }
}
