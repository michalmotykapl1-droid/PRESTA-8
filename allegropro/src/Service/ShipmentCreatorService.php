<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\ShipmentRepository;
use Configuration;
use Db;
use Exception;

class ShipmentCreatorService
{
    private AllegroApiClient $api;
    private LabelConfig $config;
    private OrderRepository $orders;
    private ShipmentRepository $shipments;

    public function __construct(AllegroApiClient $api, LabelConfig $config, OrderRepository $orders, ShipmentRepository $shipments)
    {
        $this->api = $api;
        $this->config = $config;
        $this->orders = $orders;
        $this->shipments = $shipments;
    }

    public function createShipment(array $account, string $checkoutFormId, array $params): array
    {
        $order = $this->orders->getDecodedOrder((int)$account['id_allegropro_account'], $checkoutFormId);
        if (!$order) {
            return ['ok' => false, 'message' => 'Nie znaleziono zamówienia w bazie.'];
        }

        $deliveryMethodId = $order['delivery']['method']['id'] ?? null;
        $accountId = (int)($account['id_allegropro_account'] ?? 0);

        try {
            $cfResp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId));
            if (!empty($cfResp['ok']) && is_array($cfResp['json'])) {
                $smartData = $this->extractSmartDataFromCheckoutForm($cfResp['json']);
                $packageLimit = $smartData['package_count'] ?? null;

                if (is_int($packageLimit) && $packageLimit > 0) {
                    $activeCount = method_exists($this->shipments, 'countActiveShipmentsForOrder')
                        ? (int)$this->shipments->countActiveShipmentsForOrder($accountId, $checkoutFormId)
                        : (int)count($this->shipments->findAllByOrderForAccount($accountId, $checkoutFormId));

                    if ($activeCount >= $packageLimit) {
                        return [
                            'ok' => false,
                            'message' => 'Limit paczek dla tej przesyłki został osiągnięty (' . $activeCount . '/' . $packageLimit . '). Usuń nadmiarową przesyłkę (czerwony X) i spróbuj ponownie.'
                        ];
                    }
                }
            }
        } catch (Exception $e) {
        }

        $pkgDims = $this->resolvePackageDimensions($params);

        try {
            $payload = $this->buildPayload($deliveryMethodId, $order, $pkgDims);
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
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
            return ['ok' => false, 'message' => 'Błąd Allegro: ' . $err];
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
                return ['ok' => false, 'message' => 'Błąd Allegro (Async): ' . $errMsg];
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

            return ['ok' => true, 'shipmentId' => $shipmentId];
        }

        return ['ok' => true, 'message' => 'Przesyłka w trakcie przetwarzania (Command ID: '.$cmdId.')'];
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

        $receiver['phone'] = preg_replace('/[^0-9+]/', '', $receiver['phone']);
        if (strlen($receiver['phone']) == 9) {
            $receiver['phone'] = '+48' . $receiver['phone'];
        }

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
