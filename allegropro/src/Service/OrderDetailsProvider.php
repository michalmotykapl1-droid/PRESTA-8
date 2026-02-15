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

    public function __construct()
    {
        // Ręczne wstrzykiwanie zależności
        $http = new HttpClient();
        $this->accounts = new AccountRepository();
        $api = new AllegroApiClient($http, $this->accounts);

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
            $sid = trim((string)($sh['shipment_id'] ?? ''));
            $isUuid = (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $sid);
            $isCreateCommand = (bool)preg_match('/^[A-Za-z0-9+\/]+=*$/', $sid) && strlen($sid) >= 16 && (strlen($sid) % 4 === 0);
            if (!$isCreateCommand && strpos($sid, ':') !== false) {
                $isCreateCommand = true;
            }
            $sh['can_download_label'] = $isUuid || $isCreateCommand;
        }
        unset($sh);

        // Smart: liczymy po zsynchronizowanej historii
        $smartLimit = (int)($shipping['package_count'] ?? 0);
        $smartUsed = 0;
        foreach ($shipmentsHistory as $sh) {
            if ((string)($sh['status'] ?? '') === 'CANCELLED') {
                continue;
            }

            if ((int)($sh['is_smart'] ?? 0) === 1) {
                $smartUsed++;
            }
        }
        $smartLeft = max(0, $smartLimit - $smartUsed);

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
        ];
    }
}
