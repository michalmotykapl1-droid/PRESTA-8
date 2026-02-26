<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\OrderRepository;
use Exception;

class ShipmentCreateContextResolver
{
    private AllegroApiClient $api;
    private OrderRepository $orders;

    public function __construct(AllegroApiClient $api, OrderRepository $orders)
    {
        $this->api = $api;
        $this->orders = $orders;
    }

    public function resolve(array $account, string $checkoutFormId, bool $debug = false, array &$debugLines = []): array
    {
        $accountId = (int)($account['id_allegropro_account'] ?? 0);
        $order = $this->orders->getDecodedOrder($accountId, $checkoutFormId);
        if (!$order) {
            return [
                'ok' => false,
                'message' => 'Nie znaleziono zamówienia w bazie.',
                'debug_lines' => $debug ? ['[CREATE] Brak zamówienia w bazie. Najpierw pobierz zamówienia z Allegro.'] : [],
            ];
        }

        $deliveryMethodId = $order['delivery']['method']['id'] ?? null;
        $hasPickupPoint = !empty($order['delivery']['pickupPoint']['id']);

        // Zawsze pobieramy checkout-form (to jest źródło prawdy) – przyda się też do limitów SMART.
        $checkoutFormJson = null;
        try {
            $cfResp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId));
            if (!empty($cfResp['ok']) && is_array($cfResp['json'])) {
                $checkoutFormJson = $cfResp['json'];
                $liveMethodId = $checkoutFormJson['delivery']['method']['id'] ?? null;
                if (is_string($liveMethodId) && $liveMethodId !== '' && $liveMethodId !== $deliveryMethodId) {
                    if ($debug) {
                        $debugLines[] = '[CREATE] delivery.method.id (DB)=' . (string)$deliveryMethodId;
                        $debugLines[] = '[CREATE] delivery.method.id (checkout-form)=' . (string)$liveMethodId . ' → używam wartości z checkout-form';
                    }
                    $deliveryMethodId = $liveMethodId;
                }
            }
        } catch (Exception $e) {
            // silent
        }

        $methodName = (string)($order['delivery']['method']['name'] ?? ($checkoutFormJson['delivery']['method']['name'] ?? ''));

        if ($debug) {
            $debugLines[] = '[CREATE] start checkoutFormId=' . $checkoutFormId . ', accountId=' . $accountId;
            $debugLines[] = '[CREATE] delivery.method.id=' . (string)$deliveryMethodId;
            $debugLines[] = '[CREATE] delivery.method.name=' . $methodName;
        }

        return [
            'ok' => true,
            'account_id' => $accountId,
            'order' => $order,
            'delivery_method_id' => $deliveryMethodId,
            'has_pickup_point' => $hasPickupPoint,
            'checkout_form_json' => $checkoutFormJson,
            'method_name' => $methodName,
            'debug_lines' => $debugLines,
        ];
    }
}
