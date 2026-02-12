<?php
use AllegroPro\Repository\AccountRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\OAuthService;
use AllegroPro\Service\AllegroEndpoints;
use AllegroPro\Service\Config;

class AllegroProOauthcallbackModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $stateFull = (string)Tools::getValue('state');
        $code = Tools::getValue('code');
        $error = Tools::getValue('error');

        // Rozdzielamy state na ID bazy i URL powrotny (separator to kropka)
        // Format: randomId.encodedUrl
        $parts = explode('.', $stateFull, 2);
        $stateDb = $parts[0] ?? '';
        $returnUrlEncoded = $parts[1] ?? '';

        // Dekodujemy URL powrotu (Base64 URL Safe)
        // Zabezpieczenie: akceptujemy tylko zaufany URL panelu administracyjnego.
        $adminReturnUrl = '';
        if ($returnUrlEncoded) {
            $decodedUrl = $this->decodeBase64Url($returnUrlEncoded);
            if ($decodedUrl !== null && $this->isTrustedAdminReturnUrl($decodedUrl)) {
                $adminReturnUrl = $decodedUrl;
            }
        }

        // Fallback zawsze do bezpiecznego, lokalnego URL-a panelu
        if ($adminReturnUrl === '') {
            $adminReturnUrl = $this->context->link->getAdminLink('AdminAllegroProAccounts');
        }

        // Helper do przekierowania
        $redirect = function ($msg, $isErr) use ($adminReturnUrl) {
            $sep = (strpos($adminReturnUrl, '?') !== false) ? '&' : '?';
            $finalUrl = $adminReturnUrl . $sep . ($isErr ? 'allegropro_err=' : 'allegropro_ok=') . urlencode($msg);
            Tools::redirect($finalUrl);
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

    private function decodeBase64Url(string $value): ?string
    {
        $base64 = str_replace(['-', '_'], ['+', '/'], $value);
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($base64, true);
        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        if (!filter_var($decoded, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $decoded;
    }

    private function isTrustedAdminReturnUrl(string $url): bool
    {
        $expected = $this->context->link->getAdminLink('AdminAllegroProAccounts');

        $parsedExpected = parse_url($expected);
        $parsedActual = parse_url($url);

        if (!is_array($parsedExpected) || !is_array($parsedActual)) {
            return false;
        }

        $expectedScheme = strtolower((string)($parsedExpected['scheme'] ?? ''));
        $actualScheme = strtolower((string)($parsedActual['scheme'] ?? ''));
        if ($expectedScheme === '' || $actualScheme === '' || $expectedScheme !== $actualScheme) {
            return false;
        }

        $expectedHost = strtolower((string)($parsedExpected['host'] ?? ''));
        $actualHost = strtolower((string)($parsedActual['host'] ?? ''));
        if ($expectedHost === '' || $actualHost === '' || $expectedHost !== $actualHost) {
            return false;
        }

        $expectedPort = (int)($parsedExpected['port'] ?? 0);
        $actualPort = (int)($parsedActual['port'] ?? 0);
        if ($expectedPort !== $actualPort) {
            return false;
        }

        $expectedPath = rtrim((string)($parsedExpected['path'] ?? ''), '/');
        $actualPath = rtrim((string)($parsedActual['path'] ?? ''), '/');
        if ($expectedPath === '' || $actualPath === '' || $expectedPath !== $actualPath) {
            return false;
        }

        parse_str((string)($parsedActual['query'] ?? ''), $query);
        if (!isset($query['controller']) || (string)$query['controller'] !== 'AdminAllegroProAccounts') {
            return false;
        }

        return true;
    }
}
