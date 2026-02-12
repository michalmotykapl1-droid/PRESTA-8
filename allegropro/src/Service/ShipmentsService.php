<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\ShipmentRepository;
use Configuration;

class ShipmentsService
{
    private AllegroApiClient $api;
    private DeliveryServiceRepository $deliveryRepo;
    private OrderRepository $orders;
    private ShipmentRepository $shipments;

    public function __construct(
        AllegroApiClient $api,
        DeliveryServiceRepository $deliveryRepo,
        OrderRepository $orders,
        ShipmentRepository $shipments
    ) {
        $this->api = $api;
        $this->deliveryRepo = $deliveryRepo;
        $this->orders = $orders;
        $this->shipments = $shipments;
    }

    public function refreshDeliveryServices(array $account): array
    {
        $resp = $this->api->get($account, '/shipment-management/delivery-services', [
            'limit' => 500,
        ]);
        if (!$resp['ok'] || !is_array($resp['json'])) {
            return $resp;
        }
        $services = $resp['json']['deliveryServices'] ?? [];
        if (is_array($services)) {
            foreach ($services as $s) {
                if (is_array($s)) {
                    $this->deliveryRepo->upsert((int)$account['id_allegropro_account'], $s);
                }
            }
        }
        return $resp;
    }

    public function createShipmentCommand(array $account, string $checkoutFormId): array
    {
        $order = $this->orders->getDecodedOrder((int)$account['id_allegropro_account'], $checkoutFormId);
        if (!$order) {
            return ['ok' => false, 'code' => 0, 'raw' => 'Order not found. Fetch orders first.', 'json' => null];
        }

        $deliveryMethodId = $order['delivery']['method']['id'] ?? null;
        if (!$deliveryMethodId) {
            return ['ok' => false, 'code' => 0, 'raw' => 'Missing delivery.method.id in order.', 'json' => null];
        }

        $service = $this->deliveryRepo->findByDeliveryMethod((int)$account['id_allegropro_account'], (string)$deliveryMethodId);
        if (!$service) {
            return ['ok' => false, 'code' => 0, 'raw' => 'Delivery service not found. Click "Odśwież delivery services".', 'json' => null];
        }

        $sender = $this->buildSender();
        $receiver = $this->buildReceiverFromOrder($order);
        if (!$receiver) {
            return ['ok' => false, 'code' => 0, 'raw' => 'Missing receiver address in order.', 'json' => null];
        }

        $pkg = Config::pkgDefaults();

        $additionalProps = null;
        if (!empty($service['additional_properties_json'])) {
            $decoded = json_decode($service['additional_properties_json'], true);
            if (is_array($decoded) && !empty($decoded)) {
                $additionalProps = $decoded;
            }
        }

        $input = [
            'deliveryMethodId' => (string)$service['delivery_method_id'],
            'labelFormat' => Config::labelFormat(),
            'sender' => $sender,
            'receiver' => $receiver,
            'packages' => [[
                'type' => (string)$pkg['type'],
                'weight' => ['value' => (float)$pkg['weight'], 'unit' => 'KILOGRAMS'],
                'dimensions' => [
                    'length' => ['value' => (int)$pkg['length'], 'unit' => 'CENTIMETERS'],
                    'width'  => ['value' => (int)$pkg['width'],  'unit' => 'CENTIMETERS'],
                    'height' => ['value' => (int)$pkg['height'], 'unit' => 'CENTIMETERS'],
                ],
                'content' => (string)$pkg['text'],
            ]],
        ];

        if (!empty($service['credentials_id'])) {
            $input['credentialsId'] = (string)$service['credentials_id'];
        }
        if ($additionalProps) {
            $input['additionalProperties'] = $additionalProps;
        }

        // Pickup point (e.g., parcel locker) - if present in order
        $pickupPointId = $order['delivery']['pickupPoint']['id'] ?? null;
        if ($pickupPointId) {
            $input['receiver']['point'] = [
                'id' => (string)$pickupPointId,
            ];
        }

        $payload = ['input' => $input];

        $resp = $this->api->postJson($account, '/shipment-management/shipments/create-commands', $payload, [
            'Idempotency-Key' => $this->idempotencyKey($account, $checkoutFormId),
        ]);

        if ($resp['ok'] && is_array($resp['json']) && !empty($resp['json']['id'])) {
            $cmd = (string)$resp['json']['id'];

            // fetch command status immediately
            $status = $this->api->get($account, '/shipment-management/shipments/create-commands/' . rawurlencode($cmd));
            if ($status['ok'] && is_array($status['json'])) {
                $this->shipments->upsert((int)$account['id_allegropro_account'], $checkoutFormId, $cmd, $status['json']);
                $shipmentId = $status['json']['shipmentId'] ?? null;
                $this->orders->markShipment((int)$account['id_allegropro_account'], $checkoutFormId, $shipmentId ? (string)$shipmentId : null, $cmd);
            } else {
                $this->shipments->upsert((int)$account['id_allegropro_account'], $checkoutFormId, $cmd, ['status' => 'UNKNOWN', 'raw' => $status['raw']]);
                $this->orders->markShipment((int)$account['id_allegropro_account'], $checkoutFormId, null, $cmd);
            }
        }

        return $resp;
    }

    public function fetchLabelPdf(array $account, array $shipmentIds): array
    {
        $payload = [
            'shipmentIds' => array_values($shipmentIds),
            'pageSize' => 'A4',
            'cutLine' => false,
        ];

        // Endpoint returns PDF bytes
        return $this->api->postBinary($account, '/shipment-management/label', $payload, 'application/pdf');
    }

    private function buildSender(): array
    {
        $countryId = (int) Configuration::get('PS_SHOP_COUNTRY_ID');
        $countryIso = 'PL';
        if ($countryId) {
            $countryIso = \Country::getIsoById($countryId) ?: 'PL';
        }

        $name = (string) (Configuration::get('PS_SHOP_NAME') ?: 'Sklep');
        $addr1 = (string) (Configuration::get('PS_SHOP_ADDR1') ?: '');
        $postcode = (string) (Configuration::get('PS_SHOP_CODE') ?: '');
        $city = (string) (Configuration::get('PS_SHOP_CITY') ?: '');
        $email = (string) (Configuration::get('PS_SHOP_EMAIL') ?: '');
        $phone = (string) (Configuration::get('PS_SHOP_PHONE') ?: '');

        return [
            'name' => $name ?: 'Sklep',
            'company' => $name ?: null,
            'street' => $addr1 ?: 'Brak',
            'postalCode' => $postcode ?: '00-000',
            'city' => $city ?: 'Miasto',
            'countryCode' => $countryIso ?: 'PL',
            'email' => $email ?: 'no-reply@example.com',
            'phone' => $phone ?: '000000000',
        ];
    }

    private function buildReceiverFromOrder(array $order): ?array
    {
        $addr = $order['delivery']['address'] ?? null;
        if (!is_array($addr)) return null;

        $name = trim((string)($addr['firstName'] ?? '') . ' ' . (string)($addr['lastName'] ?? ''));
        $company = $addr['companyName'] ?? ($addr['company'] ?? null);

        $street = $addr['street'] ?? null;
        $postalCode = $addr['zipCode'] ?? ($addr['postalCode'] ?? null);
        $city = $addr['city'] ?? null;
        $countryCode = $addr['countryCode'] ?? 'PL';

        $email = $addr['email'] ?? ($order['buyer']['email'] ?? null);
        $phone = $addr['phoneNumber'] ?? ($addr['phone'] ?? ($order['buyer']['phoneNumber'] ?? null));

        if (!$street || !$postalCode || !$city) return null;

        return [
            'name' => $name ?: 'Kupujący',
            'company' => $company ? (string)$company : null,
            'street' => (string)$street,
            'postalCode' => (string)$postalCode,
            'city' => (string)$city,
            'countryCode' => (string)$countryCode,
            'email' => $email ? (string)$email : 'no-reply@example.com',
            'phone' => $phone ? (string)$phone : '000000000',
        ];
    }

    private function idempotencyKey(array $account, string $checkoutFormId): string
    {
        return sha1((string)$account['id_allegropro_account'] . ':' . $checkoutFormId);
    }
}
