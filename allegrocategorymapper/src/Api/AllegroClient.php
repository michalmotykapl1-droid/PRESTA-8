<?php
// /modules/allegrocategorymapper/src/Api/AllegroClient.php

namespace ACM\Api;
use Exception;
use ACM\Domain\Logger;

class AllegroClient
{
    protected $apiUrl;
    protected $accessToken;
    protected $logger;
    
    public function __construct($apiUrl, $accessToken, Logger $logger = null)
    {
        $this->apiUrl = rtrim($apiUrl ?: 'https://api.allegro.pl', '/');
        $this->accessToken = $accessToken;
        $this->logger = $logger;
    }

    protected function ensureToken()
    {
        $exp = (int)\Configuration::get('ACM_TOKEN_EXPIRES');
        if (!$this->accessToken || ($exp && time() >= $exp - 60)) {
            $oauth = new OAuthClient($this->apiUrl, \Configuration::get('ACM_CLIENT_ID'), \Configuration::get('ACM_CLIENT_SECRET'), $this->logger);
            $data = $oauth->refreshToken(\Configuration::get('ACM_REFRESH_TOKEN'));
            if (isset($data['access_token'])) {
                \Configuration::updateValue('ACM_ACCESS_TOKEN', $data['access_token']);
                \Configuration::updateValue('ACM_REFRESH_TOKEN', $data['refresh_token']);
                \Configuration::updateValue('ACM_TOKEN_EXPIRES', time() + (int)$data['expires_in']);
                $this->accessToken = $data['access_token'];
                if ($this->logger && $this->logger->isEnabled()) $this->logger->add('Token refreshed');
            } else {
                if ($this->logger && $this->logger->isEnabled()) $this->logger->add('Token refresh failed', ['response' => $data]);
                throw new Exception('Token refresh failed');
            }
        }
    }
    
    // --- NOWA METODA ---
    public function fetchAllCategoriesWithChildren()
    {
        $this->ensureToken();
        // Pobierz kategorie główne
        $mainCategoriesResponse = $this->performGetRequest($this->apiUrl . '/sale/categories');
        $allCategories = $mainCategoriesResponse['categories'] ?? [];

        // Pobierz dzieci dla każdej kategorii głównej
        foreach ($allCategories as &$category) {
            if ($category['leaf'] === false) {
                $category['children'] = $this->fetchChildrenForCategory($category['id']);
            }
        }

        return $allCategories;
    }

    // --- NOWA METODA POMOCNICZA ---
    private function fetchChildrenForCategory($parentId)
    {
        $childrenResponse = $this->performGetRequest($this->apiUrl . '/sale/categories?parent.id=' . $parentId);
        $children = $childrenResponse['categories'] ?? [];

        foreach ($children as &$child) {
            if ($child['leaf'] === false) {
                $child['children'] = $this->fetchChildrenForCategory($child['id']);
            }
        }
        
        return $children;
    }


    public function searchByEan($ean)
    {
        $this->ensureToken();
        $url = $this->apiUrl . '/sale/products?phrase=' . rawurlencode($ean) . '&mode=GTIN';
        return $this->performGetRequest($url);
    }

    public function searchByName($productName)
    {
        $this->ensureToken();
        $endpoint = (string)\Configuration::get('ACM_NAME_SEARCH_ENDPOINT', null, null, null, '/sale/products');
        if ($endpoint === '/offers/listing') {
            $url = $this->apiUrl . '/offers/listing?phrase=' . rawurlencode($productName);
            $response = $this->performGetRequest($url);
            
            $normalizedProducts = [];
            $offers = array_merge($response['items']['promoted'] ?? [], $response['items']['regular'] ?? []);
            $uniqueCategories = [];
            foreach ($offers as $offer) {
                if (!empty($offer['category']['id']) && !isset($uniqueCategories[$offer['category']['id']])) {
                    $uniqueCategories[$offer['category']['id']] = true; 
                    $normalizedProducts[] = [
                        'category' => ['id' => $offer['category']['id'], 'path' => [['name' => ($offer['category']['name'] ?? 'Brak nazwy')]], 'name' => ($offer['category']['name'] ?? 'Brak nazwy')],
                        'matching' => ['score' => 1.0], 
                        'offersCount' => 1
                    ];
                }
            }
            return ['products' => $normalizedProducts];
        } else {
            // Domyślne, stare wyszukiwanie w katalogu
            $url = $this->apiUrl . '/sale/products?phrase=' . rawurlencode($productName);
            return $this->performGetRequest($url);
        }
    }

    public function getCategoryById($categoryId)
    {
        $this->ensureToken();
        $url = $this->apiUrl . '/sale/categories/' . rawurlencode($categoryId);
        return $this->performGetRequest($url);
    }

    private function performGetRequest($url)
    {
        if ($this->logger && $this->logger->isEnabled()) {
            $this->logger->add('GET', ['url' => $url]);
        }
        $ch = curl_init();
        
        $acceptHeader = 'Accept: application/vnd.allegro.public.v1+json';
        if (strpos($url, '/sale/categories/') !== false) {
            // Ten endpoint wymaga innego nagłówka - to była pierwotna poprawka
            // Dla pobierania drzewa (?parent.id=) ten nagłówek jest OK
            $acceptHeader = 'Accept: application/json';
        }
        
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            $acceptHeader,
            'Accept-Language: pl-PL'
        ];

        curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60]); // Zwiększony timeout
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($this->logger && $this->logger->isEnabled()) $this->logger->add('cURL error', ['http_code' => $code, 'error' => $err]);
            throw new Exception('cURL error: ' . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code >= 400) {
            if ($this->logger && $this->logger->isEnabled()) $this->logger->add('HTTP error', ['code' => $code, 'body' => $body]);
            throw new Exception('HTTP ' . $code . ' response');
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new Exception('Invalid JSON: ' . substr($body, 0, 1000));
        }
        return $json;
    }
}