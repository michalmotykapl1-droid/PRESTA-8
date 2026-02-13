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

    /**
     * Synchronizuje przesyłki dla checkoutForm z Allegro:
     * - odświeża dane Smart (is_smart / package_count),
     * - aktualizuje statusy i tracking_number lokalnych przesyłek,
     * - próbuje wykryć przesyłki utworzone poza modułem.
     */
    public function syncOrderShipments(array $account, string $checkoutFormId, int $ttlSeconds = 90, bool $force = false, bool $debug = false): array
    {
        $debugLines = [];
        $startedAt = microtime(true);
        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        if ($accountId <= 0 || $checkoutFormId === '') {
            return ['ok' => false, 'message' => 'Brak danych konta lub checkoutFormId.'];
        }

        if ($debug) {
            $debugLines[] = '[SYNC] start checkoutFormId=' . $checkoutFormId . ', accountId=' . $accountId;
        }

        if (!$force && !$this->shipments->shouldSyncOrder($accountId, $checkoutFormId, $ttlSeconds)) {
            if ($debug) {
                $debugLines[] = '[SYNC] pominięto przez TTL=' . (int)$ttlSeconds . 's';
                $this->persistSyncDebug($checkoutFormId, $debugLines);
            }
            return ['ok' => true, 'skipped' => true, 'synced' => 0, 'debug_lines' => $debugLines];
        }

        $shipmentIds = [];
        foreach ($this->shipments->getOrderShipmentIds($accountId, $checkoutFormId) as $id) {
            $shipmentIds[$id] = true;
        }
        if ($debug) {
            $debugLines[] = '[SYNC] lokalne shipment_id: ' . implode(', ', array_keys($shipmentIds));
        }

        // 1) Pobierz świeże dane checkoutForm i zaktualizuj dane Smart
        $orderResp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId));
        if ($debug) {
            $debugLines[] = '[API] GET /order/checkout-forms/{id}: HTTP ' . (int)($orderResp['code'] ?? 0) . ', ok=' . (!empty($orderResp['ok']) ? '1' : '0');
        }
        if ($orderResp['ok'] && is_array($orderResp['json'])) {
            $smart = $this->extractSmartDataFromCheckoutForm($orderResp['json']);
            $this->updateShippingSmartData(
                $checkoutFormId,
                $smart['package_count'],
                $smart['is_smart']
            );
            if ($debug) {
                $debugLines[] = '[SYNC] Smart z checkout-form: package_count=' . var_export($smart['package_count'], true) . ', is_smart=' . var_export($smart['is_smart'], true);
            }

            foreach ($this->extractShipmentIdsFromCheckoutForm($orderResp['json']) as $sid) {
                $shipmentIds[$sid] = true;
            }
            if ($debug) {
                $debugLines[] = '[SYNC] shipment_id z checkout-form: ' . implode(', ', $this->extractShipmentIdsFromCheckoutForm($orderResp['json']));
            }
        } elseif ($debug) {
            $debugLines[] = '[SYNC] checkout-form nie zwrócił danych JSON. raw=' . $this->shortRaw($orderResp['raw'] ?? '');
        }

        // 1b) Dodatkowa próba wykrycia shipmentów utworzonych poza modułem
        foreach ($this->discoverShipmentIdsFromApi($account, $checkoutFormId, $debugLines) as $sid) {
            $shipmentIds[$sid] = true;
        }

        if ($debug) {
            $debugLines[] = '[SYNC] finalna lista shipment_id: ' . implode(', ', array_keys($shipmentIds));
        }

        // 2) Dla każdego ID pobierz szczegóły przesyłki (status + tracking)
        $synced = 0;
        foreach (array_keys($shipmentIds) as $shipmentId) {
            if ($shipmentId === '') {
                continue;
            }

            $detail = $this->api->get($account, '/shipment-management/shipments/' . rawurlencode($shipmentId));
            if (!$detail['ok'] || !is_array($detail['json'])) {
                if ($debug) {
                    $debugLines[] = '[API] GET /shipment-management/shipments/' . $shipmentId . ': HTTP ' . (int)($detail['code'] ?? 0) . ', brak danych; raw=' . $this->shortRaw($detail['raw'] ?? '');
                }
                continue;
            }

            $status = (string)($detail['json']['status'] ?? 'CREATED');
            $tracking = $this->extractTrackingNumber($detail['json']);
            $isSmart = $this->extractIsSmart($detail['json']);
            $carrierMode = $this->extractCarrierMode($detail['json']);
            $sizeDetails = $this->extractSizeDetails($detail['json']);

            $this->shipments->upsertFromAllegro(
                $accountId,
                $checkoutFormId,
                $shipmentId,
                $status,
                $tracking,
                $isSmart,
                $carrierMode,
                $sizeDetails
            );
            $synced++;

            if ($debug) {
                $debugLines[] = '[SYNC] shipment=' . $shipmentId . ', status=' . $status . ', tracking=' . ($tracking ?: '-') . ', smart=' . var_export($isSmart, true);
            }
        }

        if ($debug) {
            $debugLines[] = '[SYNC] koniec, synced=' . $synced . ', time=' . round(microtime(true) - $startedAt, 3) . 's';
            $this->persistSyncDebug($checkoutFormId, $debugLines);
        }

        return ['ok' => true, 'synced' => $synced, 'skipped' => false, 'debug_lines' => $debugLines];
    }


    private function updateShippingSmartData(string $checkoutFormId, ?int $packageCount, ?int $isSmart): void
    {
        $data = [];

        if ($packageCount !== null && $packageCount >= 0) {
            $data['package_count'] = (int)$packageCount;
        }

        if ($isSmart !== null) {
            $data['is_smart'] = (int)$isSmart;
        }

        if (empty($data)) {
            return;
        }

        \Db::getInstance()->update(
            'allegropro_order_shipping',
            $data,
            "checkout_form_id = '" . pSQL($checkoutFormId) . "'"
        );
    }

    private function extractSmartDataFromCheckoutForm(array $cf): array
    {
        $delivery = is_array($cf['delivery'] ?? null) ? $cf['delivery'] : [];

        $packageCount = null;
        if (isset($delivery['calculatedNumberOfPackages'])) {
            $packageCount = max(0, (int)$delivery['calculatedNumberOfPackages']);
        }

        $isSmart = null;
        if (isset($delivery['smart'])) {
            $isSmart = !empty($delivery['smart']) ? 1 : 0;
        }

        return [
            'package_count' => $packageCount,
            'is_smart' => $isSmart,
        ];
    }

    /**
     * Wyciąga shipmentId z możliwych struktur checkoutForm.
     */
    private function extractShipmentIdsFromCheckoutForm(array $cf): array
    {
        $ids = [];

        $delivery = is_array($cf['delivery'] ?? null) ? $cf['delivery'] : [];

        if (!empty($delivery['shipments']) && is_array($delivery['shipments'])) {
            foreach ($delivery['shipments'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                foreach (['shipmentId', 'id'] as $k) {
                    if (!empty($row[$k]) && $this->looksLikeShipmentId((string)$row[$k])) {
                        $ids[(string)$row[$k]] = true;
                    }
                }
            }
        }

        if (!empty($delivery['shipment']) && is_array($delivery['shipment'])) {
            foreach (['shipmentId', 'id'] as $k) {
                if (!empty($delivery['shipment'][$k]) && $this->looksLikeShipmentId((string)$delivery['shipment'][$k])) {
                    $ids[(string)$delivery['shipment'][$k]] = true;
                }
            }
        }

        if (!empty($cf['shipments']) && is_array($cf['shipments'])) {
            foreach ($cf['shipments'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                foreach (['shipmentId', 'id'] as $k) {
                    if (!empty($row[$k]) && $this->looksLikeShipmentId((string)$row[$k])) {
                        $ids[(string)$row[$k]] = true;
                    }
                }
            }
        }

        return array_keys($ids);
    }

    private function looksLikeShipmentId(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        // Allegro shipment ids to zwykle UUID lub podobny identyfikator z myślnikami.
        return (bool)preg_match('/^[a-zA-Z0-9-]{12,}$/', $value);
    }

    private function extractTrackingNumber(array $shipment): ?string
    {
        $candidates = [
            $shipment['trackingNumber'] ?? null,
            $shipment['waybill'] ?? null,
            $shipment['waybillNumber'] ?? null,
            $shipment['tracking']['number'] ?? null,
            $shipment['label']['trackingNumber'] ?? null,
            $shipment['summary']['trackingNumber'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate)) {
                $candidate = trim($candidate);
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function extractIsSmart(array $shipment): ?int
    {
        if (isset($shipment['smart'])) {
            return !empty($shipment['smart']) ? 1 : 0;
        }

        if (isset($shipment['service']['smart'])) {
            return !empty($shipment['service']['smart']) ? 1 : 0;
        }

        return null;
    }

    private function extractCarrierMode(array $shipment): ?string
    {
        $candidate = $shipment['packages'][0]['type'] ?? ($shipment['package']['type'] ?? null);
        if (!is_string($candidate) || $candidate === '') {
            return null;
        }

        $candidate = strtoupper(trim($candidate));
        if (in_array($candidate, ['BOX', 'PACKAGE', 'COURIER'], true)) {
            return $candidate === 'PACKAGE' ? 'COURIER' : $candidate;
        }

        return null;
    }

    private function extractSizeDetails(array $shipment): ?string
    {
        $candidate = $shipment['packages'][0]['size']
            ?? $shipment['packages'][0]['type']
            ?? ($shipment['package']['size'] ?? null);

        if (!is_string($candidate) || trim($candidate) === '') {
            return null;
        }

        return strtoupper(trim($candidate));
    }

    private function discoverShipmentIdsFromApi(array $account, string $checkoutFormId, array &$debugLines = []): array
    {
        $found = [];

        $querySets = [
            ['limit' => 100, 'checkoutForm.id' => $checkoutFormId],
            ['limit' => 100, 'checkoutFormId' => $checkoutFormId],
            ['limit' => 100, 'order.id' => $checkoutFormId],
            ['limit' => 100, 'orderId' => $checkoutFormId],
            ['limit' => 100],
        ];

        foreach ($querySets as $query) {
            $resp = $this->api->get($account, '/shipment-management/shipments', $query);
            if (!empty($debugLines)) {
                $debugLines[] = '[API] GET /shipment-management/shipments?' . http_build_query($query) . ': HTTP ' . (int)($resp['code'] ?? 0) . ', ok=' . (!empty($resp['ok']) ? '1' : '0');
            }
            if (!$resp['ok'] || !is_array($resp['json'])) {
                if (!empty($debugLines)) {
                    $debugLines[] = '[API] list raw=' . $this->shortRaw($resp['raw'] ?? '');
                }
                continue;
            }

            $rows = $this->extractShipmentRows($resp['json']);
            if (!empty($debugLines)) {
                $debugLines[] = '[SYNC] list rows=' . count($rows);
            }
            foreach ($rows as $row) {
                $sid = $this->extractShipmentIdFromRow($row);
                if ($sid === null) {
                    continue;
                }

                if (!$this->rowMatchesCheckoutForm($row, $checkoutFormId) && count($query) === 1) {
                    // Dla zapytania bez filtrów wymagamy jawnego powiązania z checkoutForm
                    continue;
                }

                $found[$sid] = true;
            }

            if (!empty($found)) {
                if (!empty($debugLines)) {
                    $debugLines[] = '[SYNC] discovery znalezione shipment_id: ' . implode(', ', array_keys($found));
                }
                break;
            }
        }

        return array_keys($found);
    }

    private function shortRaw(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '[empty]';
        }

        $raw = preg_replace('/\s+/', ' ', $raw);
        if (strlen($raw) > 350) {
            $raw = substr($raw, 0, 350) . '...';
        }

        return $raw;
    }

    private function persistSyncDebug(string $checkoutFormId, array $lines): void
    {
        if (empty($lines)) {
            return;
        }

        $base = rtrim(_PS_ROOT_DIR_, '/\\') . '/var/logs';
        if (!is_dir($base)) {
            @mkdir($base, 0775, true);
        }

        if (!is_dir($base) || !is_writable($base)) {
            return;
        }

        $logPath = $base . '/allegropro_sync_debug.log';
        $prefix = '[' . date('Y-m-d H:i:s') . '][' . $checkoutFormId . '] ';
        $content = '';
        foreach ($lines as $line) {
            $content .= $prefix . $line . PHP_EOL;
        }

        @file_put_contents($logPath, $content, FILE_APPEND);
    }

    private function extractShipmentRows(array $json): array
    {
        $keys = ['shipments', 'items', 'shipmentList'];
        foreach ($keys as $k) {
            if (!empty($json[$k]) && is_array($json[$k])) {
                return $json[$k];
            }
        }

        if (isset($json[0]) && is_array($json[0])) {
            return $json;
        }

        return [];
    }

    private function extractShipmentIdFromRow(array $row): ?string
    {
        foreach (['id', 'shipmentId'] as $k) {
            if (!empty($row[$k]) && $this->looksLikeShipmentId((string)$row[$k])) {
                return (string)$row[$k];
            }
        }

        return null;
    }

    private function rowMatchesCheckoutForm(array $row, string $checkoutFormId): bool
    {
        $candidates = [
            $row['checkoutForm']['id'] ?? null,
            $row['checkoutFormId'] ?? null,
            $row['order']['id'] ?? null,
            $row['orderId'] ?? null,
            $row['reference'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) === $checkoutFormId) {
                return true;
            }
        }

        return false;
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

        return [
            'ok' => true,
            'path' => $path,
            'format' => $labelFormat,
            'name' => $uniqueName,
        ];
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
