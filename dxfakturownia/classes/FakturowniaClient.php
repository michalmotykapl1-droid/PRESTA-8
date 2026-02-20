<?php

class FakturowniaClient
{
    private $api_token;
    private $domain;

    public function __construct($api_token, $domain)
    {
        $this->api_token = $api_token;
        $this->domain = $this->normalizeDomain($domain);
    }

    private function normalizeDomain($domain)
    {
        $domain = trim((string) $domain);
        $domain = rtrim($domain, "/\t\n\r\0\x0B/");

        if ($domain === '') return '';
        if (preg_match('#^https?://#i', $domain)) return rtrim($domain, '/');
        if (strpos($domain, '.') === false) return 'https://' . $domain . '.fakturownia.pl';
        return 'https://' . $domain;
    }

    public function testConnection()
    {
        $endpoint = $this->domain . '/account.json?api_token=' . $this->api_token;
        try {
            $response = $this->sendRequest($endpoint, [], 'GET');
            if (isset($response['code']) && $response['code'] >= 200 && $response['code'] < 300) return ['success' => true];
            // Jeśli sendRequest zwrócił samą tablicę (stara metoda), sprawdzamy klucze
            if (isset($response['login']) || isset($response['name'])) return ['success' => true];
            return ['success' => false, 'message' => 'Błąd połączenia'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * POPRAWIONA METODA: Zwraca strukturę ['code' => ..., 'response' => ...]
     * Wymagana przez AdminDxFakturowniaInvoicesController
     */
    public function getInvoices($page = 1, $period = 'all')
    {
        $endpoint = $this->domain . '/invoices.json?api_token=' . $this->api_token . '&page=' . $page . '&period=' . $period;
        
        try {
            // Pobieramy surowe dane
            $data = $this->sendRequest($endpoint, [], 'GET');
            
            // Pakujemy w strukturę oczekiwaną przez kontroler
            return [
                'code' => 200, 
                'response' => $data
            ];
        } catch (Exception $e) {
            // W razie błędu zwracamy kod błędu, żeby kontroler nie "wybuchł"
            return [
                'code' => 500, 
                'message' => $e->getMessage(), 
                'response' => []
            ];
        }
    }

    public function createInvoice($order, $customer, $kind = 'vat')
    {
        $endpoint = $this->domain . '/invoices.json';
        
        $invoiceAddress = new Address((int)$order->id_address_invoice);
        
        $buyer_name = $invoiceAddress->firstname . ' ' . $invoiceAddress->lastname;
        if (!empty($invoiceAddress->company)) {
            $buyer_name = $invoiceAddress->company;
        }

        $street = $invoiceAddress->address1;
        if (!empty($invoiceAddress->address2)) {
            $street .= ' ' . $invoiceAddress->address2;
        }

        $data = [
            'api_token' => $this->api_token,
            'invoice' => [
                'kind' => $kind,
                'number' => null, 
                'sell_date' => date('Y-m-d', strtotime($order->date_add)),
                'issue_date' => date('Y-m-d'),
                'payment_to' => date('Y-m-d', strtotime($order->date_add . ' + 7 days')),
                'buyer_name' => $buyer_name,
                'buyer_email' => $customer->email,
                'buyer_tax_no' => $invoiceAddress->vat_number,
                'buyer_post_code' => $invoiceAddress->postcode,
                'buyer_city' => $invoiceAddress->city,
                'buyer_street' => $street,
                'oid' => $order->id, 
                'positions' => $this->mapPositions($order)
            ]
        ];

        return $this->sendRequest($endpoint, $data, 'POST');
    }

    private function mapPositions($order)
    {
        $products = $order->getProducts();
        $positions = [];
        
        foreach ($products as $product) {
            // Bezpieczne pobieranie ceny brutto
            $price = isset($product['total_price_tax_incl']) ? $product['total_price_tax_incl'] : $product['total_wt'];

            $positions[] = [
                'name' => $product['product_name'],
                'quantity' => $product['product_quantity'],
                'quantity_unit' => 'szt', // JEDNOSTKA
                'total_price_gross' => $price, 
                'tax' => $product['tax_rate'],
            ];
        }
        
        if ($order->total_shipping > 0) {
             $positions[] = [
                'name' => 'Koszty wysyłki',
                'quantity' => 1,
                'quantity_unit' => 'szt', // JEDNOSTKA
                'total_price_gross' => $order->total_shipping,
                'tax' => $order->carrier_tax_rate, 
            ];
        }

        return $positions;
    }

    private function sendRequest($url, $data = [], $method = 'POST')
    {
        $ch = curl_init($url);
        
        if ($method == 'POST') {
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new Exception("CURL Error: " . $curlError);
        }

        $response = json_decode($result, true);

        // Obsługa błędów API
        if ($httpCode >= 300) {
            $msg = 'Błąd API Fakturowni (' . $httpCode . ')';
            if (is_array($response)) {
                if (isset($response['message'])) {
                    $msg .= ': ' . (is_string($response['message']) ? $response['message'] : json_encode($response['message'], JSON_UNESCAPED_UNICODE));
                } elseif (isset($response['error'])) {
                    $msg .= ': ' . (is_string($response['error']) ? $response['error'] : json_encode($response['error'], JSON_UNESCAPED_UNICODE));
                } else {
                    $msg .= ': ' . json_encode($response, JSON_UNESCAPED_UNICODE);
                }
            } else {
                $msg .= ': ' . $result;
            }
            throw new Exception($msg);
        }

        return $response;
    }
}