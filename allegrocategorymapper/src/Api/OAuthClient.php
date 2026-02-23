<?php
namespace ACM\Api;

use ACM\Domain\Logger;

class OAuthClient
{
    protected $clientId, $clientSecret;
    protected $logger;

    public function __construct($apiUrl, $clientId, $clientSecret, Logger $logger = null)
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->logger       = $logger;
    }

    protected function tokenHost()
    {
        $apiUrl = (string)\Configuration::get('ACM_API_URL');
        // Sandbox? podmień na host sandboxowy
        return (stripos($apiUrl, 'sandbox') !== false)
            ? 'https://allegro.pl.allegrosandbox.pl'
            : 'https://allegro.pl';
    }

    public function exchangeCode($code, $redirectUri)
    {
        $url  = $this->tokenHost().'/auth/oauth/token';
        $post = http_build_query(array(
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $redirectUri,
        ), '', '&');
        return $this->call($url, $post);
    }

    public function refreshToken($refreshToken)
    {
        $url  = $this->tokenHost().'/auth/oauth/token';
        $post = http_build_query(array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ), '', '&');
        return $this->call($url, $post);
    }

    protected function call($url, $post)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ));
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($body, true);
        if ($this->logger && $this->logger->isEnabled()) {
            $this->logger->add('OAuth token call', array('url'=>$url, 'http_code'=>$code));
        }
        return is_array($json) ? $json : array('error'=>'invalid_json','body'=>$body);
    }
}
