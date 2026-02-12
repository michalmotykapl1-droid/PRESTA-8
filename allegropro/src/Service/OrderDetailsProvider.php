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
    private $shipmentManager;

    public function __construct()
    {
        // Ręczne wstrzykiwanie zależności
        $http = new HttpClient();
        $accRepo = new AccountRepository();
        $api = new AllegroApiClient($http, $accRepo);
        
        $this->shipmentManager = new ShipmentManager(
            $api,
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

        if (!$order) return null;

        $cfId = $order['checkout_form_id'];
        $cfIdEsc = pSQL($cfId);

        // 2. Pobierz dane
        $buyer = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_buyer WHERE checkout_form_id = '$cfIdEsc'");
        $shipping = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_shipping WHERE checkout_form_id = '$cfIdEsc'");
        $invoice = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_invoice WHERE checkout_form_id = '$cfIdEsc'");
        $items = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."allegropro_order_item WHERE checkout_form_id = '$cfIdEsc'");

        // 3. NOWA LOGIKA: Pobierz tryb (BOX/COURIER) i historię przesyłek
        $carrierMode = 'COURIER';
        if (!empty($shipping['method_name'])) {
            $carrierMode = $this->shipmentManager->detectCarrierMode($shipping['method_name']);
        }
        
        $shipmentsHistory = $this->shipmentManager->getHistory($cfId);

        // Oblicz pozostałe darmowe paczki (Smart Limit - Smart Zużyte + Smart Anulowane)
        $smartLimit = (int)($shipping['package_count'] ?? 0);
        $smartUsed = 0;
        foreach ($shipmentsHistory as $sh) {
            // Liczymy tylko te, które są SMART i NIE są anulowane
            if ($sh['status'] !== 'CANCELLED' && isset($sh['is_smart']) && $sh['is_smart'] == 1) {
                $smartUsed++;
            }
        }
        $smartLeft = max(0, $smartLimit - $smartUsed);

        // 4. Statusy
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

        return [
            'order' => $order,
            'buyer' => $buyer,
            'shipping' => $shipping,
            'invoice' => $invoice,
            'items' => $items,
            'ps_status_name' => $psStatusName,
            'allegro_statuses' => $allegroStatuses,
            
            // --- DANE DLA NOWEGO WIDOKU ---
            'carrier_mode' => $carrierMode,
            'shipments' => $shipmentsHistory,
            'smart_limit' => $smartLimit,
            'smart_left' => $smartLeft
        ];
    }
}