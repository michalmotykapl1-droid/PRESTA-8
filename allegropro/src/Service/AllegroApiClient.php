<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\AccountRepository;

class AllegroApiClient
{
    private HttpClient $http;
    private AccountRepository $accounts;

    public function __construct(HttpClient $http, AccountRepository $accounts)
    {
        $this->http = $http;
        $this->accounts = $accounts;
    }

    public function apiBaseForAccount(array $account): string
    {
        $env = (int)($account['sandbox'] ?? 0) === 1 ? 'sandbox' : 'prod';
        return AllegroEndpoints::apiBase($env);
    }

    public function ensureAccessToken(array $account): array
    {
        $expiresAt = $account['token_expires_at'] ?? null;
        $accessToken = (string)($account['access_token'] ?? '');
        $refreshToken = (string)($account['refresh_token'] ?? '');

        $needsRefresh = true;
        if ($accessToken && $expiresAt) {
            $needsRefresh = (strtotime($expiresAt) <= time());
        } elseif ($accessToken && !$expiresAt) {
            $needsRefresh = false;
        }

        if (!$needsRefresh) {
            return $account;
        }

        if (!$refreshToken) {
            return $account;
        }

        $env = (int)($account['sandbox'] ?? 0) === 1 ? 'sandbox' : 'prod';
        $tokenUrl = AllegroEndpoints::tokenUrl($env);

        $clientId = Config::clientId();
        $clientSecret = Config::clientSecret();

        if (!$clientId || !$clientSecret) {
            return $account;
        }

        $auth = base64_encode($clientId . ':' . $clientSecret);
        $body = http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        $res = $this->http->request('POST', $tokenUrl, [
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ], $body);

        if (!$res['ok']) {
            return $account;
        }

        $json = json_decode($res['body'], true);
        if (!is_array($json) || empty($json['access_token'])) {
            return $account;
        }

        $newAccess = (string)$json['access_token'];
        $newRefresh = (string)($json['refresh_token'] ?? $refreshToken);
        $expiresIn = isset($json['expires_in']) ? (int)$json['expires_in'] : null;

        $this->accounts->storeTokens((int)$account['id_allegropro_account'], $newAccess, $newRefresh, $expiresIn);
        $account['access_token'] = $newAccess;
        $account['refresh_token'] = $newRefresh;
        $account['token_expires_at'] = $expiresIn ? date('Y-m-d H:i:s', time() + $expiresIn - 30) : $account['token_expires_at'];

        return $account;
    }

    public function get(array $account, string $path, array $query = [], string $accept = 'application/vnd.allegro.public.v1+json'): array
    {
        $account = $this->ensureAccessToken($account);
        $base = $this->apiBaseForAccount($account);
        $url = $base . $path . (empty($query) ? '' : ('?' . http_build_query($query)));

        $headers = [
            'Authorization' => 'Bearer ' . (string)($account['access_token'] ?? ''),
            'Accept' => $accept,
        ];

        $res = $this->http->request('GET', $url, $headers, null);
        $json = json_decode($res['body'], true);
        return [
            'ok' => $res['ok'],
            'code' => $res['code'],
            'raw' => $res['body'],
            'json' => is_array($json) ? $json : null,
        ];
    }

    public function getWithAcceptFallbacks(array $account, string $path, array $query = [], array $acceptCandidates = []): array
    {
        $acceptCandidates = array_values(array_unique(array_filter(array_map('strval', $acceptCandidates))));
        if (empty($acceptCandidates)) {
            $acceptCandidates = ['application/vnd.allegro.public.v1+json', 'application/json', '*/*'];
        }

        $attempts = [];
        $last = ['ok' => false, 'code' => 0, 'raw' => '', 'json' => null];

        foreach ($acceptCandidates as $accept) {
            $resp = $this->get($account, $path, $query, $accept);
            $resp['accept'] = $accept;
            $attempts[] = $resp;
            $last = $resp;

            if (!empty($resp['ok'])) {
                $last['attempts'] = $attempts;
                return $last;
            }

            if ((int)($resp['code'] ?? 0) !== 406) {
                $last['attempts'] = $attempts;
                return $last;
            }
        }

        $last['attempts'] = $attempts;
        return $last;
    }


    public function fetchPublicUrl(string $url, array $headers = []): array
    {
        $res = $this->http->request('GET', $url, $headers, null);

        return [
            'ok' => $res['ok'],
            'code' => $res['code'],
            'error' => (string)($res['error'] ?? ''),
            'body' => (string)($res['body'] ?? ''),
        ];
    }
    public function postJson(array $account, string $path, array $payload, array $headersExtra = []): array
    {
        $account = $this->ensureAccessToken($account);
        $base = $this->apiBaseForAccount($account);
        $url = $base . $path;

        $headers = [
            'Authorization' => 'Bearer ' . (string)($account['access_token'] ?? ''),
            'Accept' => 'application/vnd.allegro.public.v1+json',
            'Content-Type' => 'application/vnd.allegro.public.v1+json',
        ];
        foreach ($headersExtra as $k => $v) {
            $headers[$k] = $v;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $res = $this->http->request('POST', $url, $headers, $body);

        $json = json_decode($res['body'], true);
        return [
            'ok' => $res['ok'],
            'code' => $res['code'],
            'raw' => $res['body'],
            'json' => is_array($json) ? $json : null,
        ];
    }
    public function postBinary(array $account, string $path, array $payload, string $accept = 'application/octet-stream'): array
    {
        $account = $this->ensureAccessToken($account);
        $base = $this->apiBaseForAccount($account);
        $url = $base . $path;

        $headers = [
            'Authorization' => 'Bearer ' . (string)($account['access_token'] ?? ''),
            'Accept' => $accept,
            'Content-Type' => 'application/vnd.allegro.public.v1+json',
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $res = $this->http->request('POST', $url, $headers, $body);

        return [
            'ok' => $res['ok'],
            'code' => $res['code'],
            'raw' => $res['body'],
        ];
    }

}
