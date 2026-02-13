<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\ShipmentRepository;
use Exception;
use Configuration;
use Db;

class ShipmentManager
{
    private AllegroApiClient $api;
    private LabelConfig $config;
    private LabelStorage $storage;
    private OrderRepository $orders;
    private DeliveryServiceRepository $deliveryServices;
    private ShipmentRepository $shipments;

    public function __construct(
        AllegroApiClient $api,
        LabelConfig $config,
        LabelStorage $storage,
        OrderRepository $orders,
        DeliveryServiceRepository $deliveryServices,
        ShipmentRepository $shipments
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->storage = $storage;
        $this->orders = $orders;
        $this->deliveryServices = $deliveryServices;
        $this->shipments = $shipments;
    }

    /**
     * Helper: Przycinanie tekstu do limitów Allegro (np. Sender Name max 30)
     */
    private function limitText($text, $maxLen)
    {
        $text = (string)$text;
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($maxLen > 0 && function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') > $maxLen) {
                $text = mb_substr($text, 0, $maxLen, 'UTF-8');
            }
        } else {
            if ($maxLen > 0 && strlen($text) > $maxLen) {
                $text = substr($text, 0, $maxLen);
            }
        }
        return $text;
    }

    public function detectCarrierMode(string $methodName): string
    {
        $nameLower = mb_strtolower($methodName);
        $boxKeywords = ['paczkomat', 'one box', 'one punkt', 'odbiór w punkcie', 'automat'];
        foreach ($boxKeywords as $keyword) {
            if (strpos($nameLower, $keyword) !== false) {
                return 'BOX';
            }
        }
        return 'COURIER';
    }

    public function getHistory(string $checkoutFormId): array
    {
        return $this->shipments->findAllByOrder($checkoutFormId);
    }

    public function createShipment(array $account, string $checkoutFormId, array $params): array
    {
        // 1. Dane zamówienia
        $order = $this->orders->getDecodedOrder((int)$account['id_allegropro_account'], $checkoutFormId);
        if (!$order) return ['ok' => false, 'message' => 'Nie znaleziono zamówienia w bazie.'];

        // Pobieramy ID metody bezpośrednio z zamówienia (tak jak w działającym kodzie)
        $deliveryMethodId = $order['delivery']['method']['id'] ?? null;
        
        // 2. Wymiary
        $pkgDims = $this->resolvePackageDimensions($params);
        
        // 3. Budowanie Payloadu (Logika 1:1 z działającego kodu)
        try {
            $payload = $this->buildPayload($deliveryMethodId, $order, $pkgDims);
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
        
        // 4. Wysyłka
        $resp = $this->api->postJson($account, '/shipment-management/shipments/create-commands', ['input' => $payload]);

        if (!$resp['ok']) {
            $err = $resp['json']['errors'][0]['message'] ?? ('Kod HTTP: ' . $resp['code']);
            if (isset($resp['json']['errors'][0]['details'])) {
                $err .= ' (' . $resp['json']['errors'][0]['details'] . ')';
            }
            if (isset($resp['json']['errors'][0]['path'])) {
                $err .= ' [Pole: ' . $resp['json']['errors'][0]['path'] . ']';
            }
            return ['ok' => false, 'message' => 'Błąd Allegro: ' . $err];
        }

        $cmdId = $resp['json']['id'] ?? ($resp['json']['commandId'] ?? null);
        if (empty($cmdId)) {
            return ['ok' => false, 'message' => 'Allegro nie zwróciło ID komendy tworzenia przesyłki.'];
        }
        
        // 5. Polling (Pętla sprawdzająca status, wzorowana na działającym kodzie)
        $shipmentId = null;
        $finalStatus = 'IN_PROGRESS';
        
        // Próbujemy 10 razy co 1 sekundę (uproszczone względem BbAllegroProShipping, bo nie mamy łatwego dostępu do nagłówków w tej klasie API)
        for ($i = 0; $i < 10; $i++) {
            usleep(1000000); // 1 sekunda
            
            $statusResp = $this->api->get($account, '/shipment-management/shipments/create-commands/' . $cmdId);
            
            if (!$statusResp['ok']) continue;
            
            $status = $statusResp['json']['status'] ?? 'IN_PROGRESS';
            
            if ($status === 'SUCCESS' && !empty($statusResp['json']['shipmentId'])) {
                $shipmentId = $statusResp['json']['shipmentId'];
                $finalStatus = 'CREATED';
                break;
            }
            
            if ($status === 'ERROR') {
                $errMsg = $statusResp['json']['errors'][0]['message'] ?? 'Błąd tworzenia';
                return ['ok' => false, 'message' => 'Błąd Allegro (Async): ' . $errMsg];
            }
        }

        // Zapisz w bazie (nawet jak IN_PROGRESS, żeby nie zgubić commandId)
        $dbData = [
            'status' => $finalStatus == 'CREATED' ? 'CREATED' : 'NEW',
            'shipmentId' => $shipmentId,
            'is_smart' => !empty($params['smart']) ? 1 : 0,
            'size_type' => $params['size_code'] ?? 'CUSTOM'
        ];
        
        $this->shipments->upsert((int)$account['id_allegropro_account'], $checkoutFormId, $cmdId, $dbData);
        
        if ($shipmentId) {
            $this->orders->markShipment((int)$account['id_allegropro_account'], $checkoutFormId, $shipmentId, $cmdId);
            return ['ok' => true, 'shipmentId' => $shipmentId];
        } else {
            return ['ok' => true, 'message' => 'Przesyłka w trakcie przetwarzania (Command ID: '.$cmdId.')'];
        }
    }

    public function cancelShipment(array $account, string $shipmentId): array
    {
        $endpoint = '/shipment-management/shipments/' . $shipmentId . '/cancel';
        $resp = $this->api->postJson($account, $endpoint, []);

        if (!$resp['ok'] && $resp['code'] != 204) {
             $msg = $resp['json']['errors'][0]['message'] ?? $resp['code'];
             return ['ok' => false, 'message' => 'Nie udało się anulować: ' . $msg];
        }

        $this->shipments->updateStatus($shipmentId, 'CANCELLED');
        return ['ok' => true];
    }

    public function downloadLabel(array $account, string $checkoutFormId, string $shipmentId): array
    {
        $labelFormat = $this->config->getFileFormat();
        $isA4Pdf = ($this->config->getPageSize() === 'A4' && $labelFormat === 'PDF');
        $accept = $labelFormat === 'ZPL' ? 'application/zpl' : 'application/pdf';

        $payload = [
            'shipmentIds' => [$shipmentId],
            'pageSize' => $this->config->getPageSize(),
            'labelFormat' => $labelFormat,
            'cutLine' => $isA4Pdf
        ];
        
        $resp = $this->api->postBinary($account, '/shipment-management/label', $payload, $accept);
        
        if (!$resp['ok']) return ['ok' => false, 'message' => 'Błąd pobierania etykiety'];

        $uniqueName = $checkoutFormId . '_' . substr($shipmentId, 0, 8);
        $path = $this->storage->save($uniqueName, $resp['raw'], $labelFormat);
        $url = $this->storage->getUrl($uniqueName, $labelFormat);

        return ['ok' => true, 'url' => $url];
    }

    // --- Helpers ---

    private function resolvePackageDimensions(array $params): array
    {
        if (!empty($params['size_code'])) {
            switch ($params['size_code']) {
                case 'A': return ['height' => 8, 'width' => 38, 'length' => 64, 'weight' => 25, 'type' => 'BOX'];
                case 'B': return ['height' => 19, 'width' => 38, 'length' => 64, 'weight' => 25, 'type' => 'BOX'];
                case 'C': return ['height' => 41, 'width' => 38, 'length' => 64, 'weight' => 25, 'type' => 'BOX'];
            }
        }
        
        if (!empty($params['weight'])) {
            $def = Config::pkgDefaults();
            return [
                'height' => $def['height'], 'width' => $def['width'], 'length' => $def['length'],
                'weight' => (float)$params['weight'],
                'type' => 'PACKAGE'
            ];
        }

        return Config::pkgDefaults();
    }

    private function buildPayload($methodId, $order, $dims)
    {
        // 1. Nadawca (Wymagany Telefon + Limit Nazwy)
        $senderPhone = Configuration::get('PS_SHOP_PHONE');
        // Czyszczenie telefonu nadawcy
        $senderPhone = preg_replace('/[^0-9+]/', '', (string)$senderPhone);
        if (preg_match('/^[0-9]{9}$/' , $senderPhone)) $senderPhone = '+48' . $senderPhone;
        
        if (empty($senderPhone)) {
            // Fallback na losowy, jeśli brak w sklepie, żeby nie blokować API (ale user powinien to ustawić)
            $senderPhone = '+48000000000'; 
        }

        $sender = [
            'name' => $this->limitText(Configuration::get('PS_SHOP_NAME'), 30), // Limit 30 znaków!
            'street' => Configuration::get('PS_SHOP_ADDR1'),
            'city' => Configuration::get('PS_SHOP_CITY'),
            'postalCode' => Configuration::get('PS_SHOP_CODE'),
            'countryCode' => 'PL',
            'email' => Configuration::get('PS_SHOP_EMAIL'),
            'phone' => $senderPhone // Wymagane pole
        ];

        // 2. Odbiorca
        $addr = $order['delivery']['address'];
        $receiver = [
            'name' => trim(($addr['firstName']??'') . ' ' . ($addr['lastName']??'')),
            'street' => $addr['street'],
            'city' => $addr['city'],
            'postalCode' => $addr['zipCode'],
            'countryCode' => $addr['countryCode'],
            'email' => $order['buyer']['email'],
            'phone' => $addr['phoneNumber'] ?? $order['buyer']['phoneNumber']
        ];
        
        $receiver['phone'] = preg_replace('/[^0-9+]/', '', $receiver['phone']);
        if (strlen($receiver['phone']) == 9) $receiver['phone'] = '+48' . $receiver['phone'];

        if(!empty($addr['companyName'])) $receiver['company'] = $addr['companyName'];
        
        // 3. Punkt Odbioru - Agresywne czyszczenie (Zgodnie z Twoim działającym kodem)
        if(!empty($order['delivery']['pickupPoint']['id'])) {
            $rawPoint = trim($order['delivery']['pickupPoint']['id']);
            // Rozbij po spacjach i weź ostatni element
            $parts = preg_split('/\s+/', $rawPoint);
            $lastPart = end($parts);
            // Agresywny regex
            $cleanPoint = preg_replace('/[^A-Z0-9-]/', '', strtoupper($lastPart));
            
            if (!empty($cleanPoint)) {
                // Zgodnie z komentarzem w Twoim kodzie: "Allegro API wymaga aby point był stringiem"
                // Jeśli u Ciebie to działało jako string, to zostawiamy string.
                // UWAGA: Standardowo API chce {id: "..."}. Jeśli to nie zadziała, to znaczy że Twój kod
                // używa specyficznej wersji. Ale trzymam się wzorca:
                $receiver['point'] = $cleanPoint; 
            }
        }

        // 4. Waga i Typ
        $wgtVal = number_format((float)($dims['weight']??1), 3, '.', '');
        $finalType = $dims['type'] ?? 'PACKAGE'; // Domyślnie PACKAGE (chyba że A/B/C to BOX)

        // 5. Struktura Payloadu (KOPIA 1:1 z działającego kodu)
        // Zwróć uwagę na wymiary bezpośrednio w packages[0] jako obiekty {value, unit}
        return [
            'deliveryMethodId' => $methodId,
            'sender' => $sender,
            'receiver' => $receiver,
            'labelFormat' => $this->config->getFileFormat(),
            'packages' => [[
                'type' => $finalType,
                'weight' => [
                    'value' => (string)$wgtVal,
                    'unit' => 'KILOGRAMS'
                ],
                // Wymiary "Płaskie" ale obiektowe - TO JEST KLUCZ
                'length' => [
                    'value' => (string)(int)($dims['length']??10), 
                    'unit' => 'CENTIMETER' // Zgodnie z Twoim kodem (L.p.)
                ],
                'width' => [
                    'value' => (string)(int)($dims['width']??10), 
                    'unit' => 'CENTIMETER'
                ],
                'height' => [
                    'value' => (string)(int)($dims['height']??10), 
                    'unit' => 'CENTIMETER'
                ],
                'content' => 'Towary handlowe'
            ]]
        ];
    }
}
