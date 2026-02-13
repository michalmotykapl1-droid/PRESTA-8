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

        $allowedBase = $this->context->link->getAdminLink('AdminAllegroProAccounts');
        $allowedParts = parse_url($allowedBase);
        $candidateParts = parse_url($url);
        if (!is_array($allowedParts) || !is_array($candidateParts)) {
            return false;
        }

        $allowedScheme = strtolower((string)($allowedParts['scheme'] ?? ''));
        $allowedHost = strtolower((string)($allowedParts['host'] ?? ''));
        $allowedPort = isset($allowedParts['port']) ? (int)$allowedParts['port'] : null;

        $candidateScheme = strtolower((string)($candidateParts['scheme'] ?? ''));
        $candidateHost = strtolower((string)($candidateParts['host'] ?? ''));
        $candidatePort = isset($candidateParts['port']) ? (int)$candidateParts['port'] : null;

        return $candidateScheme === $allowedScheme
            && $candidateHost === $allowedHost
            && $candidatePort === $allowedPort;
    }

    public function initContent()
    {
        parent::initContent();

        $stateFull = (string)Tools::getValue('state');
        $code = Tools::getValue('code');
        $error = Tools::getValue('error');

        // Rozdzielamy state na ID bazy i URL powrotny (separator to kropka)
        // Format: randomId.encodedUrl
        $parts = explode('.', $stateFull);
        $stateDb = $parts[0] ?? '';
        $returnUrlEncoded = $parts[1] ?? '';
        
        // Dekodujemy URL powrotu (Base64 URL Safe)
        $adminReturnUrl = '';
        if ($returnUrlEncoded) {
            $base64 = str_replace(['-', '_'], ['+', '/'], $returnUrlEncoded);
            $decoded = base64_decode($base64);
            if ($this->isAllowedAdminReturnUrl((string)$decoded)) {
                $adminReturnUrl = $decoded;
            }
        }

        // Helper do przekierowania
        $redirect = function($msg, $isErr) use ($adminReturnUrl) {
            if ($adminReturnUrl) {
                $sep = (strpos($adminReturnUrl, '?') !== false) ? '&' : '?';
                $finalUrl = $adminReturnUrl . $sep . ($isErr ? 'allegropro_err=' : 'allegropro_ok=') . urlencode($msg);
                Tools::redirect($finalUrl);
            } else {
                // Fallback - jeśli nie uda się odzyskać URLa, wyświetl komunikat
                die('<h1>Allegro Pro</h1><p>' . $msg . '</p><p>Możesz zamknąć to okno i wrócić do panelu sklepu.</p>');
            }
        };

        if ($error) {
            $redirect('Błąd autoryzacji Allegro: ' . $error, true);
        }

        if (!$stateDb || !$code) {
            $redirect('Brak danych autoryzacyjnych (state/code)', true);
        }

        $repo = new AccountRepository();
        // Szukamy w bazie TYLKO po krótkim ID
        $acc = $repo->findByOauthState($stateDb);
        
        if (!$acc) {
            $redirect('Sesja autoryzacji wygasła lub jest nieprawidłowa. Spróbuj ponownie.', true);
        }

        $clientId = Config::clientId();
        $clientSecret = Config::clientSecret();
        if (!$clientId || !$clientSecret) {
            $redirect('Client ID/Secret nie są skonfigurowane w module.', true);
        }

        $env = ((int)$acc['sandbox'] === 1) ? 'sandbox' : (Config::env());
        $redirectUri = $this->context->link->getModuleLink($this->module->name, 'oauthcallback', [], true);

        $oauth = new OAuthService(new HttpClient(), $repo);
        // Przekazujemy pełny stateFull, bo Allegro oddaje dokładnie to co dostało (wymagane do weryfikacji przez Allegro czasem)
        $token = $oauth->exchangeCode($env, $clientId, $clientSecret, $redirectUri, (string)$code);
        
        if (!$token['ok'] || !is_array($token['json']) || empty($token['json']['access_token'])) {
            $redirect('Błąd wymiany tokena: ' . (int)$token['code'] . ' ' . Tools::substr((string)$token['raw'], 0, 200), true);
        }

        $accessToken = (string)$token['json']['access_token'];
        $refreshToken = (string)($token['json']['refresh_token'] ?? '');
        $expiresIn = isset($token['json']['expires_in']) ? (int)$token['json']['expires_in'] : null;

        $repo->storeTokens((int)$acc['id_allegropro_account'], $accessToken, $refreshToken, $expiresIn);

        // Pobieramy dane usera (/me)
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

        $repo->update((int)$acc['id_allegropro_account'], [
            'allegro_login' => $login ? pSQL((string)$login) : null,
            'allegro_user_id' => $userId ? pSQL((string)$userId) : null,
            'oauth_state' => null, // czyścimy stan
        ]);

        $redirect('Konto Allegro zostało poprawnie autoryzowane.', false);
    }
}
