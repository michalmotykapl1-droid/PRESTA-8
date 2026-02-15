<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\ShipmentRepository;

class ShipmentManager
{
    private ShipmentRepository $shipments;
    private ShipmentCreatorService $creatorService;
    private ShipmentSyncService $syncService;
    private ShipmentLabelService $labelService;

    public function __construct(
        AllegroApiClient $api,
        LabelConfig $config,
        LabelStorage $storage,
        OrderRepository $orders,
        DeliveryServiceRepository $deliveryServices,
        ShipmentRepository $shipments
    ) {
        $this->shipments = $shipments;

        $resolver = new ShipmentReferenceResolver($api);

        $this->creatorService = new ShipmentCreatorService(
            $api,
            $config,
            $orders,
            $shipments
        );

        $this->syncService = new ShipmentSyncService(
            $api,
            $shipments,
            $resolver
        );

        $this->labelService = new ShipmentLabelService(
            $api,
            $config,
            $storage,
            $shipments,
            $resolver
        );
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
        return $this->creatorService->createShipment($account, $checkoutFormId, $params);
    }

    public function syncOrderShipments(array $account, string $checkoutFormId, int $ttlSeconds = 90, bool $force = false, bool $debug = false): array
    {
        return $this->syncService->syncOrderShipments($account, $checkoutFormId, $ttlSeconds, $force, $debug);
    }

    public function cancelShipment(array $account, string $shipmentId): array
    {
        return $this->labelService->cancelShipment($account, $shipmentId);
    }

    public function downloadLabel(array $account, string $checkoutFormId, string $shipmentId): array
    {
        return $this->labelService->downloadLabel($account, $checkoutFormId, $shipmentId);
    }
}
