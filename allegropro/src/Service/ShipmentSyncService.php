<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\ShipmentRepository;

class ShipmentSyncService
{
    private AllegroApiClient $api;
    private ShipmentRepository $shipments;
    private ShipmentReferenceResolver $resolver;
    private array $discoveredShipmentContext = [];
    private ?int $currentOrderIsSmart = null;
    private ?int $currentOrderSmartPackageLimit = null;

    public function __construct(AllegroApiClient $api, ShipmentRepository $shipments, ShipmentReferenceResolver $resolver)
    {
        $this->api = $api;
        $this->shipments = $shipments;
        $this->resolver = $resolver;
    }

    public function syncOrderShipments(array $account, string $checkoutFormId, int $ttlSeconds = 90, bool $force = false, bool $debug = false): array
    {
        $debugLines = [];
        $this->discoveredShipmentContext = [];
        $this->currentOrderIsSmart = null;
        $this->currentOrderSmartPackageLimit = null;
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

        $orderResp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId));
        if ($debug) {
            $debugLines[] = '[API] GET /order/checkout-forms/{id}: HTTP ' . (int)($orderResp['code'] ?? 0) . ', ok=' . (!empty($orderResp['ok']) ? '1' : '0');
        }
        if ($orderResp['ok'] && is_array($orderResp['json'])) {
            $smart = $this->extractSmartDataFromCheckoutForm($orderResp['json']);
            $this->currentOrderIsSmart = $smart['is_smart'];
            $this->currentOrderSmartPackageLimit = $smart['package_count'];
            $this->updateShippingSmartData($checkoutFormId, $smart['package_count'], $smart['is_smart']);

            if ($debug) {
                $debugLines[] = '[SYNC] Smart z checkout-form: package_count=' . var_export($smart['package_count'], true) . ', is_smart=' . var_export($smart['is_smart'], true);
            }

            $checkoutFormShipmentIds = $this->extractShipmentIdsFromCheckoutForm($orderResp['json']);
            foreach ($checkoutFormShipmentIds as $sid) {
                $shipmentIds[$sid] = true;
            }
            if ($debug) {
                $debugLines[] = '[SYNC] shipment_id z checkout-form: ' . implode(', ', $checkoutFormShipmentIds);
            }
        } elseif ($debug) {
            $debugLines[] = '[SYNC] checkout-form nie zwrócił danych JSON. raw=' . $this->shortRaw($orderResp['raw'] ?? '');
        }

        foreach ($this->discoverShipmentIdsFromApi($account, $checkoutFormId, $debugLines) as $sid) {
            $shipmentIds[$sid] = true;
        }

        if ($debug) {
            $debugLines[] = '[SYNC] finalna lista shipment_id: ' . implode(', ', array_keys($shipmentIds));
        }

        $synced = 0;
        foreach (array_keys($shipmentIds) as $shipmentId) {
            if ($shipmentId === '') {
                continue;
            }

            $resolvedShipmentId = $this->resolver->resolveShipmentIdCandidate($account, $shipmentId, $debugLines);
            $context = $this->discoveredShipmentContext[$shipmentId] ?? ($this->discoveredShipmentContext[$resolvedShipmentId] ?? null);
            if ($resolvedShipmentId === null) {
                if ($this->upsertFromDiscoveredContext($accountId, $checkoutFormId, $shipmentId, $context, $debugLines)) {
                    $synced++;
                }
                continue;
            }

            $detail = $this->api->get($account, '/shipment-management/shipments/' . rawurlencode($resolvedShipmentId));
            if (!$detail['ok'] || !is_array($detail['json'])) {
                if ($this->upsertFromDiscoveredContext($accountId, $checkoutFormId, $resolvedShipmentId, $context, $debugLines)) {
                    $synced++;
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
                $resolvedShipmentId,
                $status,
                $tracking,
                $isSmart,
                $carrierMode,
                $sizeDetails,
                $this->normalizeDateTime($detail['json']['createdAt'] ?? null)
            );

            if (is_string($tracking) && trim($tracking) !== '' && method_exists($this->shipments, 'backfillWzaForTrackingNumber')) {
                $this->shipments->backfillWzaForTrackingNumber(
                    $accountId,
                    $checkoutFormId,
                    trim($tracking),
                    $this->resolver->looksLikeCreateCommandId($shipmentId) ? $shipmentId : null,
                    $resolvedShipmentId
                );
            }

            $synced++;
        }

        $removedDuplicates = $this->shipments->removeDuplicatesForOrder($accountId, $checkoutFormId);

        if ($debug) {
            $debugLines[] = '[SYNC] koniec, synced=' . $synced . ', time=' . round(microtime(true) - $startedAt, 3) . 's';
            $this->persistSyncDebug($checkoutFormId, $debugLines);
        }

        if (method_exists($this->shipments, 'mergeWzaFieldsForOrder')) {
            $this->shipments->mergeWzaFieldsForOrder($accountId, $checkoutFormId);
        }

        if (method_exists($this->shipments, 'rebalanceSmartFlagsForOrder')) {
            $this->shipments->rebalanceSmartFlagsForOrder(
                $accountId,
                $checkoutFormId,
                $this->currentOrderSmartPackageLimit,
                $this->currentOrderIsSmart
            );
        }

        return ['ok' => true, 'synced' => $synced, 'skipped' => false, 'duplicates_removed' => $removedDuplicates, 'debug_lines' => $debugLines];
    }

    private function discoverShipmentIdsFromApi(array $account, string $checkoutFormId, array &$debugLines = []): array
    {
        $found = [];

        $checkoutShipmentsResp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId) . '/shipments');
        if ($checkoutShipmentsResp['ok'] && is_array($checkoutShipmentsResp['json'])) {
            $rows = $this->resolver->extractShipmentRows($checkoutShipmentsResp['json']);
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $refs = $this->resolver->extractCandidateReferencesFromArray($row);
                foreach ($refs as $ref) {
                    $this->captureDiscoveredRowContext($ref, $row);
                }

                $sid = $this->resolver->extractShipmentIdFromRow($row);
                $waybill = $this->resolver->extractWaybillFromRow($row);

                if ($sid !== null) {
                    if ($this->resolver->looksLikeCreateCommandId($sid)) {
                        $resolvedFromCommand = $this->resolver->resolveShipmentIdCandidate($account, $sid, $debugLines);
                        if (is_string($resolvedFromCommand) && $resolvedFromCommand !== '') {
                            $found[$resolvedFromCommand] = true;
                        } elseif ($waybill !== null) {
                            $resolvedFromWaybill = $this->resolver->resolveShipmentIdByWaybill($account, $waybill, $debugLines);
                            if (is_string($resolvedFromWaybill) && $resolvedFromWaybill !== '') {
                                $found[$resolvedFromWaybill] = true;
                            } else {
                                $found[$sid] = true;
                            }
                        } else {
                            $found[$sid] = true;
                        }
                    } else {
                        $found[$sid] = true;
                    }
                }
            }
        }

        return array_keys($found);
    }

    private function captureDiscoveredRowContext(string $referenceId, array $row): void
    {
        $referenceId = trim($referenceId);
        if ($referenceId === '') {
            return;
        }

        $status = (string)($row['status'] ?? 'CREATED');
        if ($status === '') {
            $status = 'CREATED';
        }

        $tracking = $this->resolver->extractWaybillFromRow($row);

        $isSmart = null;
        if (isset($row['smart'])) {
            $isSmart = !empty($row['smart']) ? 1 : 0;
        }

        $this->discoveredShipmentContext[$referenceId] = [
            'status' => $status,
            'tracking' => $tracking,
            'is_smart' => $isSmart,
            'created_at' => $this->normalizeDateTime($row['createdAt'] ?? null),
        ];
    }

    private function upsertFromDiscoveredContext(int $accountId, string $checkoutFormId, string $shipmentId, ?array $context, array &$debugLines = []): bool
    {
        if (!is_array($context)) {
            return false;
        }

        $tracking = isset($context['tracking']) && is_string($context['tracking']) ? trim($context['tracking']) : '';
        $status = isset($context['status']) && is_string($context['status']) && trim($context['status']) !== ''
            ? trim($context['status'])
            : ($tracking !== '' ? 'CREATED' : 'NEW');
        $isSmart = isset($context['is_smart']) ? (int)$context['is_smart'] : $this->currentOrderIsSmart;

        if ($tracking === '' && $status === 'NEW') {
            return false;
        }

        $this->shipments->upsertFromAllegro(
            $accountId,
            $checkoutFormId,
            $shipmentId,
            $status,
            $tracking,
            $isSmart,
            null,
            null,
            $this->normalizeDateTime($context['created_at'] ?? null)
        );

        return true;
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
                    if (!empty($row[$k]) && $this->resolver->looksLikeShipmentReference((string)$row[$k])) {
                        $ids[(string)$row[$k]] = true;
                    }
                }
            }
        }

        if (!empty($delivery['shipment']) && is_array($delivery['shipment'])) {
            foreach (['shipmentId', 'id'] as $k) {
                if (!empty($delivery['shipment'][$k]) && $this->resolver->looksLikeShipmentReference((string)$delivery['shipment'][$k])) {
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
                    if (!empty($row[$k]) && $this->resolver->looksLikeShipmentReference((string)$row[$k])) {
                        $ids[(string)$row[$k]] = true;
                    }
                }
            }
        }

        return array_keys($ids);
    }

    private function extractSmartDataFromCheckoutForm(array $cf): array
    {
        $delivery = is_array($cf['delivery'] ?? null) ? $cf['delivery'] : [];

        $packageCount = null;
        $packageCountCandidates = [
            $delivery['calculatedNumberOfPackages'] ?? null,
            $delivery['numberOfPackages'] ?? null,
            $delivery['packagesCount'] ?? null,
            $cf['calculatedNumberOfPackages'] ?? null,
            $cf['numberOfPackages'] ?? null,
        ];
        foreach ($packageCountCandidates as $candidate) {
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

        return [
            'package_count' => $packageCount,
            'is_smart' => $isSmart,
        ];
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

        \Db::getInstance()->update('allegropro_order_shipping', $data, "checkout_form_id = '" . pSQL($checkoutFormId) . "'");
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
}
