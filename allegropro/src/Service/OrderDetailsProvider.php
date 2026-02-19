<?php
namespace AllegroPro\Service;

use Db;
use DbQuery;
use Order;
use Context;
use Validate;
use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\AccountRepository;
use AllegroPro\Service\ShipmentManager;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\LabelConfig;
use AllegroPro\Service\LabelStorage;
use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\ShipmentRepository;
use AllegroPro\Service\HttpClient;

class OrderDetailsProvider
{
    private ShipmentManager $shipmentManager;
    private AccountRepository $accounts;
    private AllegroApiClient $api;

    public function __construct()
    {
        // Ręczne wstrzykiwanie zależności
        $http = new HttpClient();
        $this->accounts = new AccountRepository();
        $this->api = new AllegroApiClient($http, $this->accounts);

        $this->shipmentManager = new ShipmentManager(
            $this->api,
            new LabelConfig(),
            new LabelStorage(),
            new OrderRepository(),
            new DeliveryServiceRepository(),
            new ShipmentRepository()
        );
    }

    public function getAllegroDataByPsOrderId(int $psOrderId): ?array
    {
        // 1. Znajdź główne zamówienie
        $q = new DbQuery();
        $q->select('o.*, a.label as account_label');
        $q->from('allegropro_order', 'o');
        $q->leftJoin('allegropro_account', 'a', 'a.id_allegropro_account = o.id_allegropro_account');
        $q->where('o.id_order_prestashop = ' . (int)$psOrderId);
        $order = Db::getInstance()->getRow($q);

        if (!$order) {
            return null;
        }

        $cfId = (string)$order['checkout_form_id'];
        $cfIdEsc = pSQL($cfId);

        // 2) Sync shipmentów z Allegro przy wejściu na widok (z TTL)
        $syncMeta = [
            'ok' => false,
            'synced' => 0,
            'skipped' => true,
        ];

        $account = $this->accounts->get((int)$order['id_allegropro_account']);
        if (is_array($account) && !empty($account['access_token'])) {
            $sync = $this->shipmentManager->syncOrderShipments($account, $cfId, 90, false);
            if (is_array($sync)) {
                $syncMeta['ok'] = !empty($sync['ok']);
                $syncMeta['synced'] = (int)($sync['synced'] ?? 0);
                $syncMeta['skipped'] = !empty($sync['skipped']);
            }
        }

        // 3. Pobierz dane (już po sync)
        $buyer = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_buyer WHERE checkout_form_id = '$cfIdEsc'");
        $shipping = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_shipping WHERE checkout_form_id = '$cfIdEsc'");
        $invoice = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_invoice WHERE checkout_form_id = '$cfIdEsc'");
        $items = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."allegropro_order_item WHERE checkout_form_id = '$cfIdEsc'");

        // 4. Tryb i historia przesyłek
        $carrierMode = 'COURIER';
        if (!empty($shipping['method_name'])) {
            $carrierMode = $this->shipmentManager->detectCarrierMode((string)$shipping['method_name']);
        }

        $shipmentsHistory = $this->shipmentManager->getHistory($cfId);
        foreach ($shipmentsHistory as &$sh) {
            $wzaCommand = trim((string)($sh['wza_command_id'] ?? ''));
            $wzaUuid = trim((string)($sh['wza_shipment_uuid'] ?? ''));
            // Za "utworzoną w module" uznajemy przesyłkę gdy:
            // - mamy commandId (WZA create-command) lub
            // - mamy prawdziwy UUID shipmentu (do etykiet WZA). 
            // (Nie traktujemy dowolnego stringa w wza_shipment_uuid jako modułowego, bo czasem trzymamy tam kopię shipment_id.)
            $isUuid = ($wzaUuid !== '' && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $wzaUuid));
            $isModuleShipment = ($wzaCommand !== '' || $isUuid);

            $tracking = trim((string)($sh['tracking_number'] ?? ''));
            $size = strtoupper(trim((string)($sh['size_details'] ?? '')));
            // Drukarka także dla przesyłek pobranych z Allegro, ale tylko CUSTOM + ma tracking_number
            $isInpostExternal = false;
            $ref = trim((string)($sh['shipment_id'] ?? ''));

            // CarrierId dla trackingu (/order/carriers/{carrierId}/tracking?waybill=...)
            $carrierId = '';
            if ($ref !== '') {
                $decCarrier = '';
                $dec = base64_decode($ref, true);
                if (is_string($dec) && $dec !== '' && strpos($dec, ':') !== false) {
                    $decCarrier = (string)strtok($dec, ':');
                } elseif (strpos($ref, ':') !== false) {
                    $decCarrier = (string)strtok($ref, ':');
                }
                $decCarrier = strtoupper(trim($decCarrier));
                if ($decCarrier !== '' && preg_match('/^[A-Z0-9_]{2,20}$/', $decCarrier)) {
                    $carrierId = $decCarrier;
                }
            }
            if ($carrierId === '') {
                $mn = strtolower((string)($shipping['method_name'] ?? ''));
                if (strpos($mn, 'inpost') !== false) { $carrierId = 'INPOST'; }
                elseif (strpos($mn, 'dpd') !== false) { $carrierId = 'DPD'; }
                elseif (strpos($mn, 'dhl') !== false) { $carrierId = 'DHL'; }
                elseif (strpos($mn, 'orlen') !== false) { $carrierId = 'ORLEN'; }
                elseif (strpos($mn, 'gls') !== false) { $carrierId = 'GLS'; }
                elseif (strpos($mn, 'ups') !== false) { $carrierId = 'UPS'; }
                elseif (strpos($mn, 'allegro one') !== false || strpos($mn, 'one box') !== false) { $carrierId = 'ALLEGRO'; }
            }
            $sh['carrier_id'] = $carrierId;
            if ($ref !== '') {
                if (strpos($ref, 'SU5QT1NU') === 0) {
                    $isInpostExternal = true; // base64 zaczyna się od "INPOST"
                } else {
                    $dec = base64_decode($ref, true);
                    if (is_string($dec) && strpos($dec, 'INPOST:') === 0) {
                        $isInpostExternal = true;
                    }
                }
            }

            $hasShipx = (!empty($account['shipx_token']));
            // Drukarka dla przesyłek pobranych z Allegro: tylko CUSTOM + tracking + INPOST + skonfigurowany ShipX token
            $isExternalEligible = (!$isModuleShipment && $tracking !== '' && $size === 'CUSTOM' && $isInpostExternal && $hasShipx);

            $sh['can_download_label'] = ($isModuleShipment || $isExternalEligible);
            $sh['origin_is_module'] = $isModuleShipment ? 1 : 0;
            $sh['origin_label'] = $isModuleShipment ? 'UTWORZONA W MODULE' : 'POBRANA Z ALLEGRO';
        }
        unset($sh);

        // Smart: liczymy po zsynchronizowanej historii
        $isSmartOrder = ((int)($shipping['is_smart'] ?? 0) === 1);
        $smartLimit = $isSmartOrder ? (int)($shipping['package_count'] ?? 0) : 0;
        $smartUsed = 0;
        if ($isSmartOrder) {
            foreach ($shipmentsHistory as $sh) {
                if ((string)($sh['status'] ?? '') === 'CANCELLED') {
                    continue;
                }
                if ((int)($sh['is_smart'] ?? 0) === 1) {
                    $smartUsed++;
                }
            }
        }
        $smartLeft = $isSmartOrder ? max(0, $smartLimit - $smartUsed) : 0;

        // 5. Statusy
        $psStatusName = 'Nieznany';
        $psOrder = new Order($psOrderId);
        if (Validate::isLoadedObject($psOrder)) {
            $state = $psOrder->getCurrentOrderState();
            if (Validate::isLoadedObject($state)) {
                $psStatusName = $state->name[Context::getContext()->language->id] ?? $state->name;
            }
        }

        $allegroStatuses = [
            'READY_FOR_PROCESSING' => 'Do realizacji',
            'PROCESSING' => 'W realizacji',
            'SENT' => 'Wysłane',
            'CANCELLED' => 'Anulowane'
        ];

        $shipmentSizeOptions = $this->buildShipmentSizeOptions(is_array($account) ? $account : [], is_array($shipping) ? $shipping : []);
        $weightDefaults = $this->buildShipmentWeightDefaults($cfId, $psOrderId);

        return [
            'order' => $order,
            'buyer' => $buyer,
            'shipping' => $shipping,
            'invoice' => $invoice,
            'items' => $items,
            'ps_status_name' => $psStatusName,
            'allegro_statuses' => $allegroStatuses,

            // --- DANE DLA WIDOKU ---
            'carrier_mode' => $carrierMode,
            'shipments' => $shipmentsHistory,
            'smart_limit' => $smartLimit,
            'smart_left' => $smartLeft,
            'shipments_sync' => $syncMeta,
            'shipment_size_options' => $shipmentSizeOptions,
            'shipment_weight_defaults' => $weightDefaults,
        ];
    }

    private function buildShipmentWeightDefaults(string $checkoutFormId, int $psOrderId): array
    {
        $pkgDefaults = Config::pkgDefaults();
        $configWeight = isset($pkgDefaults['weight']) ? (float)$pkgDefaults['weight'] : 1.0;
        if ($configWeight <= 0) {
            $configWeight = 1.0;
        }

        $productsWeight = $this->calculateProductsWeight($checkoutFormId, $psOrderId);

        return [
            'manual_default' => 1.0,
            'config_weight' => round($configWeight, 3),
            'products_weight' => $productsWeight,
        ];
    }

    private function calculateProductsWeight(string $checkoutFormId, int $psOrderId): ?float
    {
        $cf = pSQL($checkoutFormId);
        if ($cf === '') {
            return null;
        }

        $sql = 'SELECT oi.quantity, oi.id_product, oi.id_product_attribute, p.weight AS product_weight, pa.weight AS attr_weight '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order_item` oi '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = oi.id_product '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.id_product_attribute = oi.id_product_attribute '
            . "WHERE oi.checkout_form_id='" . $cf . "'";

        $rows = Db::getInstance()->executeS($sql) ?: [];
        if (empty($rows)) {
            $fallback = $this->calculateProductsWeightFromPrestashopOrder($psOrderId);
            return $fallback > 0 ? round($fallback, 3) : null;
        }

        $sum = 0.0;
        foreach ($rows as $row) {
            $qty = max(0.0, (float)($row['quantity'] ?? 0));
            if ($qty <= 0) {
                continue;
            }

            $baseWeight = (float)($row['product_weight'] ?? 0);
            $attrImpact = (float)($row['attr_weight'] ?? 0);
            $itemWeight = $baseWeight + $attrImpact;
            if ($itemWeight <= 0) {
                continue;
            }

            $sum += ($itemWeight * $qty);
        }

        if ($sum <= 0) {
            $sum = $this->calculateProductsWeightFromPrestashopOrder($psOrderId);
        }

        if ($sum <= 0) {
            return null;
        }

        return round($sum, 3);
    }

    private function calculateProductsWeightFromPrestashopOrder(int $psOrderId): float
    {
        if ($psOrderId <= 0) {
            return 0.0;
        }

        $sql = 'SELECT od.product_quantity, od.product_weight, od.product_id, od.product_attribute_id, p.weight AS base_weight, pa.weight AS attr_impact '
            . 'FROM `' . _DB_PREFIX_ . 'order_detail` od '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = od.product_id '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.id_product_attribute = od.product_attribute_id '
            . 'WHERE od.id_order=' . (int)$psOrderId;

        $rows = Db::getInstance()->executeS($sql) ?: [];
        if (empty($rows)) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($rows as $row) {
            $qty = max(0.0, (float)($row['product_quantity'] ?? 0));
            if ($qty <= 0) {
                continue;
            }

            $itemWeight = 0.0;
            $orderDetailWeight = (float)($row['product_weight'] ?? 0);
            if ($orderDetailWeight > 0) {
                $itemWeight = $orderDetailWeight;
            } else {
                $baseWeight = (float)($row['base_weight'] ?? 0);
                $attrImpact = (float)($row['attr_impact'] ?? 0);
                $itemWeight = $baseWeight + $attrImpact;
            }

            if ($itemWeight <= 0) {
                continue;
            }

            $sum += ($itemWeight * $qty);
        }

        return $sum;
    }


    private function buildShipmentSizeOptions(array $account, array $shipping): array
    {
        $fallback = $this->buildShipmentSizeOptionsFallback($shipping);

        $methodId = trim((string)($shipping['method_id'] ?? ''));
        if ($methodId === '' || empty($account['access_token'])) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $service = $this->fetchDeliveryServiceByMethodId($account, $methodId);
        if (!is_array($service)) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $apiOptions = $this->buildSizeOptionsFromDeliveryService($service);
        if (!empty($apiOptions['supports_presets'])) {
            $apiOptions['source'] = 'api';
            return $apiOptions;
        }

        $fallback['source'] = 'fallback';
        return $fallback;
    }

    private function buildShipmentSizeOptionsFallback(array $shipping): array
    {
        $methodName = mb_strtolower(trim((string)($shipping['method_name'] ?? '')));
        $methodId = mb_strtolower(trim((string)($shipping['method_id'] ?? '')));
        $haystack = trim($methodName . ' ' . $methodId);

        $profile = 'DEFAULT';
        if ($this->containsAnyKeyword($haystack, ['inpost', 'paczkomat'])) {
            $profile = 'INPOST';
        } elseif ($this->containsAnyKeyword($haystack, ['allegro one box', 'one box', 'allegro one punkt', 'one punkt'])) {
            $profile = 'ALLEGRO_ONE';
        } elseif ($this->containsAnyKeyword($haystack, ['dpd pickup', 'dpd odbiór', 'dpd punkt'])) {
            $profile = 'DPD_PICKUP';
        } elseif ($this->containsAnyKeyword($haystack, ['orlen paczka', 'orlen'])) {
            $profile = 'ORLEN';
        } elseif ($this->containsAnyKeyword($haystack, ['odbiór w punkcie', 'punkt odbioru', 'automat paczkowy'])) {
            $profile = 'PICKUP_GENERIC';
        }

        $options = [];

        $helpText = 'Dla tej metody dostawy dostępny jest tylko "Własny gabaryt" (waga).';
        $supportsPresetSizes = false;

        switch ($profile) {
            case 'INPOST':
                $supportsPresetSizes = true;
                $options[] = ['value' => 'A', 'label' => 'Gabaryt A (Allegro/InPost)'];
                $options[] = ['value' => 'B', 'label' => 'Gabaryt B (Allegro/InPost)'];
                $options[] = ['value' => 'C', 'label' => 'Gabaryt C (Allegro/InPost)'];
                $helpText = 'Paczkomaty InPost: możesz użyć A/B/C lub "Własny gabaryt". Dla A/B/C moduł używa zdefiniowanych wymiarów.';
                break;

            case 'ALLEGRO_ONE':
                $helpText = 'Allegro One: brak jednoznacznych gabarytów A/B/C w fallbacku. Wybierz "Własny gabaryt" albo zsynchronizuj metody, aby pobrać ograniczenia z API Allegro.';
                break;

            case 'DPD_PICKUP':
                $helpText = 'DPD Pickup: brak jednoznacznych gabarytów A/B/C w fallbacku. Wybierz "Własny gabaryt" albo zsynchronizuj metody, aby pobrać ograniczenia z API Allegro.';
                break;

            case 'ORLEN':
                $helpText = 'ORLEN Paczka: brak jednoznacznych gabarytów A/B/C w fallbacku. Wybierz "Własny gabaryt" albo zsynchronizuj metody, aby pobrać ograniczenia z API Allegro.';
                break;

            case 'PICKUP_GENERIC':
                $helpText = 'Punkt odbioru/automat: fallback nie zakłada gabarytów A/B/C. Wybierz "Własny gabaryt" albo zsynchronizuj metody, aby pobrać ograniczenia z API Allegro.';
                break;
        }

        if (!$supportsPresetSizes) {
            $options[] = ['value' => 'CUSTOM', 'label' => 'Własny gabaryt (waga)'];
        }

        return [
            'options' => $options,
            'supports_presets' => $supportsPresetSizes ? 1 : 0,
            'profile' => $profile,
            'help_text' => $helpText,
            'method_id' => (string)($shipping['method_id'] ?? ''),
            'method_name' => (string)($shipping['method_name'] ?? ''),
        ];
    }

    private function fetchDeliveryServiceByMethodId(array $account, string $deliveryMethodId): ?array
    {
        try {
            $resp = $this->api->get($account, '/shipment-management/delivery-services', ['limit' => 500]);
            if (empty($resp['ok']) || !is_array($resp['json'])) {
                return null;
            }

            $services = [];
            if (isset($resp['json']['services']) && is_array($resp['json']['services'])) {
                $services = $resp['json']['services'];
            } elseif (isset($resp['json']['deliveryServices']) && is_array($resp['json']['deliveryServices'])) {
                $services = $resp['json']['deliveryServices'];
            } elseif (array_values($resp['json']) === $resp['json']) {
                $services = $resp['json'];
            }

            $matches = [];
            foreach ($services as $service) {
                if (!is_array($service)) {
                    continue;
                }
                $serviceMethodId = $this->extractDeliveryMethodId($service);
                if ($serviceMethodId !== $deliveryMethodId) {
                    continue;
                }
                $matches[] = $service;
            }

            if (empty($matches)) {
                return null;
            }

            usort($matches, function (array $a, array $b): int {
                $scoreA = $this->scoreDeliveryServiceCandidate($a);
                $scoreB = $this->scoreDeliveryServiceCandidate($b);
                return $scoreB <=> $scoreA;
            });

            return $matches[0];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function scoreDeliveryServiceCandidate(array $service): int
    {
        $score = 0;
        $owner = strtoupper(trim((string)($service['owner'] ?? '')));
        $carrierId = strtoupper(trim((string)($service['carrierId'] ?? '')));

        if ($owner === 'CLIENT') {
            $score += 20;
        }
        if ($carrierId !== '') {
            $score += 10;
        }

        $idObj = $service['id'] ?? null;
        if (isset($service['credentialsId']) && trim((string)$service['credentialsId']) !== '') {
            $score += 5;
        } elseif (is_array($idObj) && isset($idObj['credentialsId']) && trim((string)$idObj['credentialsId']) !== '') {
            $score += 5;
        }

        return $score;
    }

    private function extractDeliveryMethodId(array $service): string
    {
        $idObj = $service['id'] ?? null;

        if (isset($service['deliveryMethodId'])) {
            return trim((string)$service['deliveryMethodId']);
        }
        if (is_array($idObj) && isset($idObj['deliveryMethodId'])) {
            return trim((string)$idObj['deliveryMethodId']);
        }
        if (is_array($service['deliveryMethod'] ?? null) && isset($service['deliveryMethod']['id'])) {
            return trim((string)$service['deliveryMethod']['id']);
        }

        return '';
    }

    private function buildSizeOptionsFromDeliveryService(array $service): array
    {
        $tokens = $this->extractSizeTokensFromService($service);

        $hasA = in_array('A', $tokens, true);
        $hasB = in_array('B', $tokens, true);
        $hasC = in_array('C', $tokens, true);

        $profile = strtoupper(trim((string)($service['carrierId'] ?? '')));
        if ($profile === '') {
            $profile = 'API';
        }

        $options = [];

        if ($hasA || $hasB || $hasC) {
            if ($hasA) {
                $options[] = ['value' => 'A', 'label' => 'Gabaryt A (z API Allegro)'];
            }
            if ($hasB) {
                $options[] = ['value' => 'B', 'label' => 'Gabaryt B (z API Allegro)'];
            }
            if ($hasC) {
                $options[] = ['value' => 'C', 'label' => 'Gabaryt C (z API Allegro)'];
            }

            return [
                'options' => $options,
                'supports_presets' => 1,
                'profile' => $profile,
                'help_text' => 'Opcje gabarytów pobrane bezpośrednio z API Allegro dla tej metody dostawy.',
                'method_id' => (string)$this->extractDeliveryMethodId($service),
                'method_name' => (string)($service['name'] ?? ''),
            ];
        }

        $options[] = ['value' => 'CUSTOM', 'label' => 'Własny gabaryt (waga)'];

        return [
            'options' => $options,
            'supports_presets' => 0,
            'profile' => $profile,
            'help_text' => 'API Allegro dla tej metody nie zwróciło jawnych presetów A/B/C — dostępny jest "Własny gabaryt".',
            'method_id' => (string)$this->extractDeliveryMethodId($service),
            'method_name' => (string)($service['name'] ?? ''),
        ];
    }

    private function extractSizeTokensFromService(array $service): array
    {
        $tokens = [];

        $walk = function ($value) use (&$walk, &$tokens): void {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (is_string($k)) {
                        $uk = strtoupper(trim($k));
                        if (in_array($uk, ['A', 'B', 'C'], true)) {
                            $tokens[$uk] = true;
                        }
                    }
                    $walk($v);
                }
                return;
            }

            if (!is_string($value)) {
                return;
            }

            $u = strtoupper(trim($value));
            if (in_array($u, ['A', 'B', 'C'], true)) {
                $tokens[$u] = true;
                return;
            }

            if (preg_match('/\bSIZE[_\- ]?A\b/', $u) || preg_match('/\bGABARYT[_\- ]?A\b/', $u)) {
                $tokens['A'] = true;
            }
            if (preg_match('/\bSIZE[_\- ]?B\b/', $u) || preg_match('/\bGABARYT[_\- ]?B\b/', $u)) {
                $tokens['B'] = true;
            }
            if (preg_match('/\bSIZE[_\- ]?C\b/', $u) || preg_match('/\bGABARYT[_\- ]?C\b/', $u)) {
                $tokens['C'] = true;
            }
        };

        $walk($service);

        return array_keys($tokens);
    }

    private function containsAnyKeyword(string $haystack, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            $needle = trim((string)$keyword);
            if ($needle === '') {
                continue;
            }
            if (mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

}
