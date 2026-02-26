<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\AccountRepository;

class OAuthService
{
    private HttpClient $http;
    private AccountRepository $accounts;

    public function __construct(HttpClient $http, AccountRepository $accounts)
    {
        $this->http = $http;
        $this->accounts = $accounts;
    }

    public function buildAuthorizeUrl(string $env, string $clientId, string $redirectUri, string $state): string
    {
        $base = AllegroEndpoints::authorizeUrl($env);
        
        // --- NAPRAWA: PEŁNA LISTA UPRAWNIEŃ (SCOPES) ---
        // Bez tej listy Allegro nie pozwoli pobrać EAN ani nadać paczki.
        $scopes = [
            'allegro:api:profile:read',       // Login
            'allegro:api:profile:write',
            'allegro:api:sale:offers:read',   // <--- KLUCZOWE DLA EAN
            'allegro:api:sale:offers:write',
            'allegro:api:sale:settings:read',
            'allegro:api:sale:settings:write',
            'allegro:api:orders:read',        // Zamówienia
            'allegro:api:orders:write',
            'allegro:api:ratings',
            'allegro:api:disputes',
            'allegro:api:billing:read',
            'allegro:api:payments:read',
            'allegro:api:payments:write',
            'allegro:api:shipments:read',     // Wysyłki
            'allegro:api:shipments:write',
            'allegro:api:fulfillment:read',
            'allegro:api:fulfillment:write',
            'allegro:api:messaging',
            'allegro:api:bids',
            'allegro:api:ads',
            'allegro:api:campaigns'
        ];

        $params = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => implode(' ', $scopes) // Przekazujemy listę do Allegro
        ];
        return $base . '?' . http_build_query($params);
    }

    public function exchangeCode(string $env, string $clientId, string $clientSecret, string $redirectUri, string $code): array
    {
        $url = AllegroEndpoints::tokenUrl($env);
        $auth = base64_encode($clientId . ':' . $clientSecret);

        $body = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        $res = $this->http->request('POST', $url, [
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ], $body);

        $json = json_decode($res['body'], true);
        return [
            'ok' => $res['ok'],
            'code' => $res['code'],
            'raw' => $res['body'],
            'json' => is_array($json) ? $json : null,
        ];
    }
}