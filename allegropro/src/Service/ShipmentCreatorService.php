<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\ShipmentRepository;
use Configuration;
use Db;
use Exception;

class ShipmentCreatorService
{
    private AllegroApiClient $api;
    private LabelConfig $config;
    private OrderRepository $orders;
    private DeliveryServiceRepository $deliveryServices;
    private ShipmentRepository $shipments;

    public function __construct(
        AllegroApiClient $api,
        LabelConfig $config,
        OrderRepository $orders,
        DeliveryServiceRepository $deliveryServices,
        ShipmentRepository $shipments
    )
    {
        $this->api = $api;
        $this->config = $config;
        $this->orders = $orders;
        $this->deliveryServices = $deliveryServices;
        $this->shipments = $shipments;
    }

    public function createShipment(array $account, string $checkoutFormId, array $params): array
    {
        $debug = !empty($params['debug']);
        $debugLines = [];

        $order = $this->orders->getDecodedOrder((int)$account['id_allegropro_account'], $checkoutFormId);
        if (!$order) {
            return ['ok' => false, 'message' => 'Nie znaleziono zamówienia w bazie.', 'debug_lines' => $debug ? ['[CREATE] Brak zamówienia w bazie. Najpierw pobierz zamówienia z Allegro.'] : []];
        }

        $deliveryMethodId = $order['delivery']['method']['id'] ?? null;
        $accountId = (int)($account['id_allegropro_account'] ?? 0);

        if ($debug) {
            $debugLines[] = '[CREATE] start checkoutFormId=' . $checkoutFormId . ', accountId=' . $accountId;
            $debugLines[] = '[CREATE] delivery.method.id=' . (string)$deliveryMethodId;
            $debugLines[] = '[CREATE] delivery.method.name=' . (string)($order['delivery']['method']['name'] ?? '');
        }

        // Dociągnij ustawienia delivery-service (credentialsId / additionalProperties)
        $service = null;
        if (is_string($deliveryMethodId) && $deliveryMethodId !== '') {
            $service = $this->deliveryServices->findByDeliveryMethod($accountId, (string)$deliveryMethodId);
            if (!$service) {
                // Fallback: automatycznie odśwież delivery-services, jeśli nie ma mapowania.
                if ($debug) {
                    $debugLines[] = '[CREATE] Brak delivery-service w bazie dla deliveryMethodId. Próbuję auto-refresh /shipment-management/delivery-services...';
                }
                $before = $this->deliveryServices->countForAccount($accountId);
                $refreshInfo = $this->refreshDeliveryServices($account);
                $after = $this->deliveryServices->countForAccount($accountId);
                if ($debug) {
                    $debugLines[] = '[CREATE] delivery-services refresh: HTTP ' . (int)($refreshInfo['code'] ?? 0)
                        . ', ok=' . (!empty($refreshInfo['ok']) ? '1' : '0')
                        . ', records: ' . $before . ' → ' . $after
                        . (!empty($refreshInfo['shape']) ? (', shape=' . $refreshInfo['shape']) : '');
                }
                $service = $this->deliveryServices->findByDeliveryMethod($accountId, (string)$deliveryMethodId);
            }
        }

        $credentialsId = null;
        $additionalProps = null;
        if (is_array($service)) {
            $credentialsId = !empty($service['credentials_id']) ? (string)$service['credentials_id'] : null;
            if (!empty($service['additional_properties_json'])) {
                $decoded = json_decode((string)$service['additional_properties_json'], true);
                if (is_array($decoded) && !empty($decoded)) {
                    $additionalProps = $decoded;
                }
            }
        }

        if ($debug) {
            $debugLines[] = '[CREATE] delivery-service: ' . ($service ? 'OK' : 'BRAK') . (
                $service ? (' (owner=' . (string)($service['owner'] ?? '-') . ', carrier_id=' . (string)($service['carrier_id'] ?? '-') . ')') : ''
            );
            $debugLines[] = '[CREATE] credentialsId: ' . ($credentialsId ? $credentialsId : '(brak)');
            if ($additionalProps) {
                $debugLines[] = '[CREATE] additionalProperties (z delivery-services): ' . json_encode($additionalProps, JSON_UNESCAPED_UNICODE);
            }
        }

        try {
            $cfResp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId));
            if (!empty($cfResp['ok']) && is_array($cfResp['json'])) {
                $smartData = $this->extractSmartDataFromCheckoutForm($cfResp['json']);
                $packageLimit = $smartData['package_count'] ?? null;

                // Ograniczenie liczby paczek z checkout-form dotyczy wyłącznie przesyłek SMART.
                // Dla zwykłych przesyłek (smart=0) Allegro pozwala tworzyć kolejne etykiety.
                $requestIsSmart = !empty($params['smart']);
                if ($requestIsSmart && is_int($packageLimit) && $packageLimit > 0) {
                    $activeCount = method_exists($this->shipments, 'countActiveShipmentsForOrder')
                        ? (int)$this->shipments->countActiveShipmentsForOrder($accountId, $checkoutFormId)
                        : (int)count($this->shipments->findAllByOrderForAccount($accountId, $checkoutFormId));

                    if ($activeCount >= $packageLimit) {
                        return [
                            'ok' => false,
                            'message' => 'Limit paczek SMART dla tej przesyłki został osiągnięty (' . $activeCount . '/' . $packageLimit . '). Wyłącz SMART albo usuń nadmiarową przesyłkę (czerwony X) i spróbuj ponownie.'
                        ];
                    }
                }
            }
        } catch (Exception $e) {
        }

        $pkgDims = $this->resolvePackageDimensions($params);

        try {
            $payload = $this->buildPayload($deliveryMethodId, $order, $pkgDims);

            // Dla części metod (np. InPost na umowie własnej) Allegro wymaga credentialsId.
            if ($credentialsId) {
                $payload['credentialsId'] = $credentialsId;
            }

            // InPost: wymagane przekazanie metody nadania (inpost#sendingMethod), jeśli zwrócone w delivery-services.
            $payload = $this->applyInpostSendingMethod($payload, $order, $additionalProps, $debug, $debugLines);
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'debug_lines' => $debug ? $debugLines : []];
        }

        if ($debug) {
            $debugLines[] = '[API] POST /shipment-management/shipments/create-commands';
            $debugLines[] = '[API] payload.input=' . json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        $resp = $this->api->postJson($account, '/shipment-management/shipments/create-commands', ['input' => $payload]);

        if (!$resp['ok']) {
            $err = $resp['json']['errors'][0]['message'] ?? ('Kod HTTP: ' . $resp['code']);
            if (isset($resp['json']['errors'][0]['details'])) {
                $err .= ' (' . $resp['json']['errors'][0]['details'] . ')';
            }
            if (isset($resp['json']['errors'][0]['path'])) {
                $err .= ' [Pole: ' . $resp['json']['errors'][0]['path'] . ']';
            }
            if ($debug) {
                $debugLines[] = '[API] HTTP ' . (int)$resp['code'] . ' ok=0';
                $debugLines[] = '[API] response=' . (is_string($resp['raw'] ?? null) ? (string)$resp['raw'] : json_encode($resp['json'], JSON_UNESCAPED_UNICODE));
                $debugLines = array_merge($debugLines, $this->troubleshootHints($err, $service));
            }
            return ['ok' => false, 'message' => 'Błąd Allegro: ' . $err, 'debug_lines' => $debug ? $debugLines : []];
        }

        $cmdId = $resp['json']['id'] ?? ($resp['json']['commandId'] ?? null);
        if (empty($cmdId)) {
            return ['ok' => false, 'message' => 'Allegro nie zwróciło ID komendy tworzenia przesyłki.'];
        }

        $shipmentId = null;
        $finalStatus = 'IN_PROGRESS';

        for ($i = 0; $i < 10; $i++) {
            usleep(1000000);

            $statusResp = $this->api->get($account, '/shipment-management/shipments/create-commands/' . $cmdId);
            if (!$statusResp['ok']) {
                continue;
            }

            $status = $statusResp['json']['status'] ?? 'IN_PROGRESS';
            if ($status === 'SUCCESS' && !empty($statusResp['json']['shipmentId'])) {
                $shipmentId = $statusResp['json']['shipmentId'];
                $finalStatus = 'CREATED';
                break;
            }

            if ($status === 'ERROR') {
                $errMsg = $statusResp['json']['errors'][0]['message'] ?? 'Błąd tworzenia';
                if ($debug) {
                    $debugLines[] = '[API] GET /shipment-management/shipments/create-commands/{commandId}: status=ERROR';
                    $debugLines[] = '[API] error=' . $errMsg;
                    $debugLines[] = '[API] response=' . json_encode($statusResp['json'], JSON_UNESCAPED_UNICODE);
                    $debugLines = array_merge($debugLines, $this->troubleshootHints($errMsg, $service));
                }
                return ['ok' => false, 'message' => 'Błąd Allegro (Async): ' . $errMsg, 'debug_lines' => $debug ? $debugLines : []];
            }
        }

        $dbData = [
            'status' => $finalStatus == 'CREATED' ? 'CREATED' : 'NEW',
            'shipmentId' => $shipmentId,
            'is_smart' => !empty($params['smart']) ? 1 : 0,
            'size_type' => $params['size_code'] ?? 'CUSTOM'
        ];
        $this->shipments->upsert((int)$account['id_allegropro_account'], $checkoutFormId, $cmdId, $dbData);

        if ($shipmentId) {
            $this->orders->markShipment((int)$account['id_allegropro_account'], $checkoutFormId, $shipmentId, $cmdId);

            $tracking2 = null;
            try {
                $detailJson = null;
                for ($i = 0; $i < 6; $i++) {
                    $detail = $this->api->get($account, '/shipment-management/shipments/' . rawurlencode($shipmentId));
                    if (!empty($detail['ok']) && is_array($detail['json'])) {
                        $detailJson = $detail['json'];
                        $tracking2 = $this->extractTrackingNumber($detailJson);
                        if (is_string($tracking2) && trim($tracking2) !== '') {
                            $tracking2 = trim($tracking2);
                            break;
                        }
                    }
                    usleep(400000);
                }

                if (is_array($detailJson)) {
                    $status2 = (string)($detailJson['status'] ?? 'CREATED');
                    $isSmart2 = $this->extractIsSmart($detailJson);
                    $carrierMode2 = $this->extractCarrierMode($detailJson);
                    $sizeDetails2 = $this->extractSizeDetails($detailJson);

                    if (method_exists($this->shipments, 'upsertFromAllegro')) {
                                                $createdAt2 = $this->normalizeDateTime($detailJson['createdAt'] ?? null);
                        $statusChangedAt2 = $this->normalizeDateTime($detailJson['statusChangedAt'] ?? ($detailJson['updatedAt'] ?? null))
                            ?: $createdAt2;

                        $this->shipments->upsertFromAllegro(
                            (int)$account['id_allegropro_account'],
                            $checkoutFormId,
                            $shipmentId,
                            $status2,
                            $tracking2,
                            $isSmart2,
                            $carrierMode2,
                            $sizeDetails2,
                            $createdAt2,
                            $statusChangedAt2
                        );
                    } elseif (is_string($tracking2) && $tracking2 !== '') {
                        Db::getInstance()->update(
                            'allegropro_shipment',
                            [
                                'tracking_number' => pSQL($tracking2),
                                'updated_at' => pSQL(date('Y-m-d H:i:s')),
                            ],
                            'id_allegropro_account='.(int)$account['id_allegropro_account']
                                ." AND checkout_form_id='".pSQL($checkoutFormId)."'"
                                ." AND shipment_id='".pSQL($shipmentId)."'"
                        );
                    }
                }
            } catch (Exception $e) {
            }

            if (is_string($tracking2) && trim($tracking2) !== '' && method_exists($this->shipments, 'backfillWzaForTrackingNumber')) {
                $this->shipments->backfillWzaForTrackingNumber(
                    (int)$account['id_allegropro_account'],
                    $checkoutFormId,
                    trim($tracking2),
                    $cmdId,
                    $shipmentId
                );
            }
            if (method_exists($this->shipments, 'mergeWzaFieldsForOrder')) {
                $this->shipments->mergeWzaFieldsForOrder((int)$account['id_allegropro_account'], $checkoutFormId);
            }

            return ['ok' => true, 'shipmentId' => $shipmentId, 'debug_lines' => $debug ? $debugLines : []];
        }

        return ['ok' => true, 'message' => 'Przesyłka w trakcie przetwarzania (Command ID: '.$cmdId.')', 'debug_lines' => $debug ? $debugLines : []];
    }

    private function refreshDeliveryServices(array $account): array
    {
        try {
            $resp = $this->api->get($account, '/shipment-management/delivery-services', ['limit' => 500]);
            if (empty($resp['ok']) || !is_array($resp['json'])) {
                return $resp;
            }
            // Allegro w dokumentacji pokazuje klucz "services" (nie "deliveryServices").
            $services = null;
            $shape = '';
            if (isset($resp['json']['services'])) {
                $services = $resp['json']['services'];
                $shape = 'services';
            } elseif (isset($resp['json']['deliveryServices'])) {
                $services = $resp['json']['deliveryServices'];
                $shape = 'deliveryServices';
            } else {
                // awaryjnie: jeśli API zwróciło listę bez obudowy
                $services = $resp['json'];
                $shape = 'raw';
            }
            if (!is_array($services)) {
                $resp['shape'] = $shape;
                return $resp;
            }
            foreach ($services as $s) {
                if (is_array($s)) {
                    $this->deliveryServices->upsert((int)($account['id_allegropro_account'] ?? 0), $s);
                }
            }
            $resp['shape'] = $shape;
            return $resp;
        } catch (Exception $e) {
            return ['ok' => false, 'code' => 0, 'raw' => (string)$e->getMessage(), 'json' => null];
        }
    }

    /**
     * InPost od 2024/2025 wymaga jawnego wskazania metody nadania.
     * Do końca lutego 2026 wspierane jest additionalProperties.inpost#sendingMethod.
     */
    private function applyInpostSendingMethod(array $payload, array $order, ?array $additionalProps, bool $debug, array &$debugLines): array
    {
        $hasPickup = !empty($order['delivery']['pickupPoint']['id']);

        // Jeżeli delivery-services zwróciło klucz inpost#sendingMethod (często jako lista), wybierz sensowną wartość.
        if (is_array($additionalProps) && array_key_exists('inpost#sendingMethod', $additionalProps)) {
            $supported = $additionalProps['inpost#sendingMethod'];
            $chosen = null;

            if (is_array($supported)) {
                // Preferencje: paczkomat -> parcel_locker / any_point, kurier -> dispatch_order
                $pref = $hasPickup ? ['parcel_locker', 'any_point', 'pop'] : ['dispatch_order'];
                foreach ($pref as $p) {
                    if (in_array($p, $supported, true)) {
                        $chosen = $p;
                        break;
                    }
                }
                if (!$chosen && !empty($supported)) {
                    $chosen = (string)reset($supported);
                }
            } elseif (is_string($supported) && $supported !== '') {
                $chosen = $supported;
            }

            if ($chosen) {
                $payload['additionalProperties'] = $payload['additionalProperties'] ?? [];
                if (!is_array($payload['additionalProperties'])) {
                    $payload['additionalProperties'] = [];
                }
                $payload['additionalProperties']['inpost#sendingMethod'] = $chosen;

                if ($debug) {
                    $debugLines[] = '[CREATE] InPost: ustawiono additionalProperties.inpost#sendingMethod=' . $chosen;
                }
            }
        }

        return $payload;
    }

    private function troubleshootHints(string $errMsg, ?array $service): array
    {
        $hints = [];
        $msgLower = mb_strtolower($errMsg);

        // Najczęstszy problem: brak credentialsId dla metod z umową własną (często InPost).
        if (strpos($msgLower, 'no inpost credentials') !== false || strpos($msgLower, 'credentials') !== false) {
            $hints[] = '';
            $hints[] = '[HINT] Ten błąd zwykle oznacza brak poprawnie dodanej integracji/poświadczeń do Wysyłam z Allegro dla InPost.';
            $hints[] = '[HINT] 1) Allegro Sales Center → Wysyłam z Allegro → Integracja z InPost → dodaj token ShipX (InPost) i zapisz.';
            $hints[] = '[HINT] 2) W module AllegroPro: wejdź w „Przesyłki” i kliknij „Odśwież delivery services” dla tego konta.';
            $hints[] = '[HINT] 3) Upewnij się, że dla tej metody dostawy w tabeli dxna_allegropro_delivery_service pole credentials_id NIE jest puste (wtedy API wie jaką umowę wybrać).';
            if (is_array($service)) {
                $hints[] = '[HINT] delivery-service owner=' . (string)($service['owner'] ?? '-') . ', carrier_id=' . (string)($service['carrier_id'] ?? '-') . ', credentials_id=' . (string)($service['credentials_id'] ?? '(brak)');
            }
            $hints[] = '[HINT] Jeżeli to Paczkomat/Punkt InPost: upewnij się, że w delivery-services jest zwrócone additionalProperties.inpost#sendingMethod i że moduł je przekazuje (parcel_locker/any_point).';
        }

        return $hints;
    }

    private function normalizeDateTime($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function extractSmartDataFromCheckoutForm(array $cf): array
    {
        $delivery = is_array($cf['delivery'] ?? null) ? $cf['delivery'] : [];
        $packageCount = null;
        $candidates = [
            $delivery['calculatedNumberOfPackages'] ?? null,
            $delivery['numberOfPackages'] ?? null,
            $delivery['packagesCount'] ?? null,
            $cf['calculatedNumberOfPackages'] ?? null,
            $cf['numberOfPackages'] ?? null,
        ];
        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }
            if (is_numeric($candidate)) {
                $packageCount = max(0, (int)$candidate);
                break;
            }
        }

        $isSmart = null;
        if (isset($delivery['smart'])) {
            $isSmart = !empty($delivery['smart']) ? 1 : 0;
        } elseif (isset($cf['smart'])) {
            $isSmart = !empty($cf['smart']) ? 1 : 0;
        }

        return ['package_count' => $packageCount, 'is_smart' => $isSmart];
    }

    private function extractTrackingNumber(array $shipment): ?string
    {
        if (!empty($shipment['packages']) && is_array($shipment['packages'])) {
            foreach ($shipment['packages'] as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $wb = $p['waybill'] ?? ($p['trackingNumber'] ?? ($p['waybillNumber'] ?? null));
                if (is_string($wb) && trim($wb) !== '') {
                    return trim($wb);
                }
            }
        }

        $candidates = [
            $shipment['trackingNumber'] ?? null,
            $shipment['waybill'] ?? null,
            $shipment['waybillNumber'] ?? null,
            $shipment['tracking']['number'] ?? null,
            $shipment['label']['trackingNumber'] ?? null,
            $shipment['summary']['trackingNumber'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
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

        $textCandidates = [
            $shipment['service']['name'] ?? null,
            $shipment['service']['id'] ?? null,
            $shipment['deliveryMethod']['name'] ?? null,
            $shipment['deliveryMethod']['id'] ?? null,
            $shipment['summary']['name'] ?? null,
        ];
        foreach ($textCandidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && mb_stripos($candidate, 'smart') !== false) {
                return 1;
            }
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

    private function resolvePackageDimensions(array $params): array
    {
        if (!empty($params['size_code'])) {
            switch ($params['size_code']) {
                // Allegro API akceptuje tylko: DOX|PACKAGE|PALLET|OTHER.
                // Dla gabarytów A/B/C (np. paczkomaty) przekazujemy PACKAGE + wymiary.
                case 'A': return ['height' => 8, 'width' => 38, 'length' => 64, 'weight' => 25, 'type' => 'PACKAGE'];
                case 'B': return ['height' => 19, 'width' => 38, 'length' => 64, 'weight' => 25, 'type' => 'PACKAGE'];
                case 'C': return ['height' => 41, 'width' => 38, 'length' => 64, 'weight' => 25, 'type' => 'PACKAGE'];
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
        $senderPhone = Configuration::get('PS_SHOP_PHONE');
        $senderPhone = preg_replace('/[^0-9+]/', '', (string)$senderPhone);
        if (preg_match('/^[0-9]{9}$/', $senderPhone)) {
            $senderPhone = '+48' . $senderPhone;
        }

        if (empty($senderPhone)) {
            $senderPhone = '+48000000000';
        }

        $sender = [
            'name' => $this->limitText(Configuration::get('PS_SHOP_NAME'), 30),
            'street' => Configuration::get('PS_SHOP_ADDR1'),
            'city' => Configuration::get('PS_SHOP_CITY'),
            'postalCode' => Configuration::get('PS_SHOP_CODE'),
            'countryCode' => 'PL',
            'email' => Configuration::get('PS_SHOP_EMAIL'),
            'phone' => $senderPhone
        ];

        $addr = $order['delivery']['address'];
        $receiver = [
            'name' => trim(($addr['firstName'] ?? '') . ' ' . ($addr['lastName'] ?? '')),
            'street' => $addr['street'],
            'city' => $addr['city'],
            'postalCode' => $addr['zipCode'],
            'countryCode' => $addr['countryCode'],
            'email' => $order['buyer']['email'],
            'phone' => $addr['phoneNumber'] ?? $order['buyer']['phoneNumber']
        ];

        $receiverPhone = (string)($receiver['phone'] ?? '');
        $receiverPhone = preg_replace('/[^0-9+]/', '', $receiverPhone);
        if (strlen($receiverPhone) == 9) {
            $receiverPhone = '+48' . $receiverPhone;
        }
        $receiver['phone'] = $receiverPhone;

        if (!empty($addr['companyName'])) {
            $receiver['company'] = $addr['companyName'];
        }

        if (!empty($order['delivery']['pickupPoint']['id'])) {
            $rawPoint = trim($order['delivery']['pickupPoint']['id']);
            $parts = preg_split('/\s+/', $rawPoint);
            $lastPart = end($parts);
            $cleanPoint = preg_replace('/[^A-Z0-9-]/', '', strtoupper($lastPart));

            if (!empty($cleanPoint)) {
                $receiver['point'] = $cleanPoint;
            }
        }

        $wgtVal = number_format((float)($dims['weight'] ?? 1), 3, '.', '');
        $finalType = $dims['type'] ?? 'PACKAGE';

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
                'length' => [
                    'value' => (string)(int)($dims['length'] ?? 10),
                    'unit' => 'CENTIMETER'
                ],
                'width' => [
                    'value' => (string)(int)($dims['width'] ?? 10),
                    'unit' => 'CENTIMETER'
                ],
                'height' => [
                    'value' => (string)(int)($dims['height'] ?? 10),
                    'unit' => 'CENTIMETER'
                ],
                'content' => 'Towary handlowe'
            ]]
        ];
    }
}
