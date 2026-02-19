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

        $checkoutFormJson = null;

        $orderResp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId));
        if ($debug) {
            $debugLines[] = '[API] GET /order/checkout-forms/{id}: HTTP ' . (int)($orderResp['code'] ?? 0) . ', ok=' . (!empty($orderResp['ok']) ? '1' : '0');
        }
        if ($orderResp['ok'] && is_array($orderResp['json'])) {
            $checkoutFormJson = $orderResp['json'];
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

        $carrierCandidates = $this->getCarrierCandidatesForOrder($account, $checkoutFormJson, $checkoutFormId, $debugLines, $debug);

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
                // Brak UUID shipmentu – spróbuj wyciągnąć realny status z trackingu po numerze nadania (waybill)
                $waybill = $this->extractWaybillCandidate($shipmentId, $context);
                if (is_string($waybill) && $waybill !== '') {
                    $progress = $this->fetchTrackingProgressByWaybill($account, $carrierCandidates, $waybill, $debugLines, $debug);
                    if (is_array($progress) && !empty($progress['status'])) {
                        if (!is_array($context)) {
                            $context = [];
                        }
                        $context['tracking'] = $waybill;
                        $context['status'] = (string)$progress['status'];
                        if (!empty($progress['at'])) {
                            $context['status_changed_at'] = (string)$progress['at'];
                        }
                    }
                }

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
            $detailWaybills = $this->extractAllWaybillsFromArray($detail['json']);
            $isSmart = $this->extractIsSmart($detail['json']);
            $carrierMode = $this->extractCarrierMode($detail['json']);
            $sizeDetails = $this->extractSizeDetails($detail['json']);

            $createdAt = $this->normalizeDateTime($detail['json']['createdAt'] ?? null);
            $statusChangedAt = $this->normalizeDateTime($detail['json']['statusChangedAt'] ?? ($detail['json']['updatedAt'] ?? null));

            if ($debug) {
                $pkgCnt = (!empty($detail['json']['packages']) && is_array($detail['json']['packages'])) ? count($detail['json']['packages']) : 0;
                $debugLines[] = '[SYNC] shipment detail: shipmentId=' . $resolvedShipmentId
                    . ', packages=' . $pkgCnt
                    . ', waybills=' . (!empty($detailWaybills) ? implode('|', $detailWaybills) : '-');
            }

            // Jeśli Allegro ma więcej waybilli niż posiadamy w DB, dopisz brakujące jako osobne wiersze.
            // Dzięki temu "Historia Przesyłek" może mieć tyle pozycji co Sales Center.
            if (!empty($detailWaybills) && method_exists($this->shipments, 'trackingExistsForOrder')) {
                foreach ($detailWaybills as $wb) {
                    $wb = trim((string)$wb);
                    if ($wb === '') {
                        continue;
                    }
                    if ($this->shipments->trackingExistsForOrder($accountId, $checkoutFormId, $wb)) {
                        continue;
                    }

                    // Wiersz "paczki" (waybill) – bez UUID/commandId, tylko do podglądu/trackingu.
                    $this->shipments->upsertFromAllegro(
                        $accountId,
                        $checkoutFormId,
                        $wb,
                        $status,
                        $wb,
                        $isSmart,
                        $carrierMode,
                        $sizeDetails,
                        $createdAt,
                        $statusChangedAt
                    );
                    $synced++;
                    if ($debug) {
                        $debugLines[] = '[SYNC] dopisano brakujący waybill do DB: ' . $wb . ' (z shipmentId=' . $resolvedShipmentId . ')';
                    }
                }
            }

            // Jeśli Allegro zwraca status bazowy (np. CREATED), spróbuj trackingu po numerze nadania
            if ($this->isBaseShipmentStatus($status)) {
                $waybill2 = is_string($tracking) ? trim($tracking) : '';
                if ($waybill2 !== '') {
                    $progress2 = $this->fetchTrackingProgressByWaybill($account, $carrierCandidates, $waybill2, $debugLines, $debug);
                    if (is_array($progress2) && !empty($progress2['status'])) {
                        $status = (string)$progress2['status'];
                        if (!empty($progress2['at'])) {
                            $statusChangedAt = (string)$progress2['at'];
                        }
                    }
                }
            }

            if (!$statusChangedAt) {
                $statusChangedAt = $createdAt ?: date('Y-m-d H:i:s');
            }

            $this->shipments->upsertFromAllegro(
                $accountId,
                $checkoutFormId,
                $resolvedShipmentId,
                $status,
                $tracking,
                $isSmart,
                $carrierMode,
                $sizeDetails,
                $createdAt,
                $statusChangedAt
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

        // Dodatkowa korekta historycznych rekordów: dla size_details=CUSTOM kopiuj shipment_id -> wza_shipment_uuid,
        // jeśli wza_shipment_uuid jest puste. Dzięki temu dane są spójne dla dalszych operacji w module.
        if (method_exists($this->shipments, 'backfillWzaUuidFromShipmentIdForCustom')) {
            $fixed = $this->shipments->backfillWzaUuidFromShipmentIdForCustom($accountId, $checkoutFormId);
            if ($debug) {
                $debugLines[] = '[SYNC] backfill CUSTOM: wza_shipment_uuid <- shipment_id, updated=' . (int)$fixed;
                // jeżeli debug był włączony, dopiszemy te linie jeszcze raz do cache debug, bo wcześniejszy persist mógł już zajść
                $this->persistSyncDebug($checkoutFormId, $debugLines);
            }
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
        if (!empty($debugLines)) {
            $debugLines[] = '[API] GET /order/checkout-forms/{id}/shipments: HTTP ' . (int)($checkoutShipmentsResp['code'] ?? 0) . ', ok=' . (!empty($checkoutShipmentsResp['ok']) ? '1' : '0');
        }
        if ($checkoutShipmentsResp['ok'] && is_array($checkoutShipmentsResp['json'])) {
            $rows = $this->resolver->extractShipmentRows($checkoutShipmentsResp['json']);
            if (!empty($debugLines)) {
                $debugLines[] = '[SYNC] /order/.../shipments rows=' . count($rows);
            }
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                // DEBUG: pokaż co dokładnie zwraca Allegro w tym wierszu
                if (!empty($debugLines)) {
                    $sidDbg = (string)($row['shipmentId'] ?? ($row['shipment_id'] ?? ($row['id'] ?? '')));
                    $statusDbg = (string)($row['status'] ?? '');
                    $wbsDbg = $this->extractAllWaybillsFromArray($row);
                    $debugLines[] = '[SYNC] row: id=' . ($sidDbg !== '' ? $sidDbg : '-')
                        . ', status=' . ($statusDbg !== '' ? $statusDbg : '-')
                        . ', waybills=' . (!empty($wbsDbg) ? implode('|', $wbsDbg) : '-');
                }

                $refs = $this->resolver->extractCandidateReferencesFromArray($row);
                foreach ($refs as $ref) {
                    $this->captureDiscoveredRowContext($ref, $row);
                }

                $sid = $this->resolver->extractShipmentIdFromRow($row);
                $waybill = $this->resolver->extractWaybillFromRow($row);
                $waybillsAll = $this->extractAllWaybillsFromArray($row);

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

                // Jeśli Allegro zwraca wiele paczek (packages[]), dopisz je jako osobne referencje (waybille)
                // — dzięki temu moduł może odtworzyć pełną listę numerów nadania.
                foreach ($waybillsAll as $wb) {
                    $wb = trim((string)$wb);
                    if ($wb === '') {
                        continue;
                    }
                    $found[$wb] = true;
                    // Kontekst dla upsertFromDiscoveredContext
                    $this->captureDiscoveredRowContext($wb, $row);
                    if (isset($this->discoveredShipmentContext[$wb])) {
                        $this->discoveredShipmentContext[$wb]['tracking'] = $wb;
                    }
                }
            }
        } elseif (!empty($debugLines)) {
            $debugLines[] = '[SYNC] /order/.../shipments brak JSON. raw=' . $this->shortRaw($checkoutShipmentsResp['raw'] ?? '');
        }

        return array_keys($found);
    }

    /**
     * Zwraca wszystkie waybille znalezione w strukturze (row/detail), np. row.waybill + packages[].waybill.
     */
    private function extractAllWaybillsFromArray(array $data): array
    {
        $out = [];

        // 1) pole główne
        foreach (['waybill', 'trackingNumber'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) {
                $v = trim($data[$k]);
                if ($v !== '') {
                    $out[$v] = true;
                }
            }
        }

        // 2) tracking.number
        if (!empty($data['tracking']) && is_array($data['tracking']) && !empty($data['tracking']['number']) && is_string($data['tracking']['number'])) {
            $v = trim($data['tracking']['number']);
            if ($v !== '') {
                $out[$v] = true;
            }
        }

        // 3) packages
        if (!empty($data['packages']) && is_array($data['packages'])) {
            foreach ($data['packages'] as $p) {
                if (!is_array($p)) {
                    continue;
                }
                foreach (['waybill', 'trackingNumber', 'waybillNumber'] as $k) {
                    if (!empty($p[$k]) && is_string($p[$k])) {
                        $v = trim($p[$k]);
                        if ($v !== '') {
                            $out[$v] = true;
                        }
                    }
                }
            }
        }

        return array_keys($out);
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
            'status_changed_at' => $this->normalizeDateTime($row['statusChangedAt'] ?? ($row['updatedAt'] ?? null)),
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

        $createdAt = $this->normalizeDateTime($context['created_at'] ?? null);
        $statusChangedAt = $this->normalizeDateTime($context['status_changed_at'] ?? ($context['updated_at'] ?? null)) ?: $createdAt;

        $this->shipments->upsertFromAllegro(
            $accountId,
            $checkoutFormId,
            $shipmentId,
            $status,
            $tracking,
            $isSmart,
            null,
            null,
            $createdAt,
            $statusChangedAt
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


    private function isBaseShipmentStatus(string $status): bool
    {
        $status = strtoupper(trim($status));
        return $status === '' || in_array($status, ['CREATED', 'NEW', 'IN_PROGRESS'], true);
    }

    /**
     * Spróbuj wyciągnąć numer nadania z:
     * - kontekstu (tracking)
     * - shipment_id (np. base64("ALLEGRO:WAYBILL") / "ALLEGRO:WAYBILL")
     */
    private function extractWaybillCandidate(string $shipmentId, ?array $context = null): ?string
    {
        if (is_array($context)) {
            $t = $context['tracking'] ?? null;
            if (is_string($t) && trim($t) !== '') {
                return trim($t);
            }
        }

        $decoded = $this->resolver->decodeShipmentReference($shipmentId);
        if (is_string($decoded)) {
            $decoded = trim($decoded);
            if ($decoded !== '' && !$this->resolver->looksLikeShipmentId($decoded) && !$this->resolver->looksLikeCreateCommandId($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Pobiera realny status przesyłki po numerze nadania.
     * Wg dokumentacji: GET /order/carriers/{carrierId}/tracking?waybill={waybill}. (Allegro REST API)
     */
    private function fetchTrackingProgressByWaybill(array $account, array $carrierCandidates, string $waybill, array &$debugLines = [], bool $debug = false): ?array
{
    $waybill = trim($waybill);
    if ($waybill === '') {
        return null;
    }

    $accepts = ['application/vnd.allegro.public.v1+json', 'application/json', '*/*'];

    $best = null; // ['carrier'=>..., 'raw'=>..., 'status'=>..., 'at'=>..., 'rank'=>..., 'ts'=>...]
    foreach ($carrierCandidates as $carrierId) {
        $carrierId = strtoupper(trim((string)$carrierId));
        if ($carrierId === '') {
            continue;
        }

        $path = '/order/carriers/' . rawurlencode($carrierId) . '/tracking?waybill=' . rawurlencode($waybill);
        $resp = $this->api->getWithAcceptFallbacks($account, $path, [], $accepts);

        if ($debug) {
            $debugLines[] = '[API] GET /order/carriers/' . $carrierId . '/tracking?waybill=' . $waybill . ': HTTP ' . (int)($resp['code'] ?? 0) . ', ok=' . (!empty($resp['ok']) ? '1' : '0');
        }

        if (empty($resp['ok']) || !is_array($resp['json'])) {
            continue;
        }

        $event = $this->extractLatestTrackingEvent($resp['json'], $waybill);
        if (!is_array($event) || empty($event['status'])) {
            continue;
        }

        $raw = (string)$event['status'];
        $norm = $this->normalizeTrackingStatus($raw);
        $at = isset($event['at']) && is_string($event['at']) ? trim($event['at']) : null;
        $ts = $at ? (int)strtotime($at) : 0;
        $rank = $this->trackingStatusRank($norm);

        if ($debug) {
            $debugLines[] = '[SYNC] tracking candidate: carrier=' . $carrierId . ', raw=' . strtoupper(trim($raw)) . ', norm=' . $norm . ', at=' . ($at ?: '-');
        }

        if ($best === null) {
            $best = [
                'carrier' => $carrierId,
                'raw' => strtoupper(trim($raw)),
                'status' => $norm,
                'at' => $at,
                'rank' => $rank,
                'ts' => $ts,
            ];
            continue;
        }

        // wybór najlepszego: wyższy "rank" wygrywa, przy remisie nowsza data wygrywa
        if ($rank > (int)$best['rank'] || ($rank === (int)$best['rank'] && $ts > (int)$best['ts'])) {
            $best = [
                'carrier' => $carrierId,
                'raw' => strtoupper(trim($raw)),
                'status' => $norm,
                'at' => $at,
                'rank' => $rank,
                'ts' => $ts,
            ];
        }
    }

    if ($best !== null) {
        if ($debug) {
            $debugLines[] = '[SYNC] tracking best: carrier=' . $best['carrier'] . ', status=' . $best['status'] . ', at=' . ($best['at'] ?: '-');
        }
        return [
            'status' => (string)$best['status'],
            'at' => $best['at'] ?? null,
        ];
    }

    return null;
}



    /**
     * Buduje listę carrierId do testowania trackingu.
     * - próbuje wywnioskować z delivery.method.id/name
     * - dociąga listę z GET /order/carriers oraz /shipment-management/delivery-services (jako fallback).
     */
    private function getCarrierCandidatesForOrder(array $account, ?array $checkoutFormJson, string $checkoutFormId, array &$debugLines = [], bool $debug = false): array
    {
        $candidates = [];

        $add = function (?string $id) use (&$candidates) {
            if (!is_string($id)) {
                return;
            }
            $id = strtoupper(trim($id));
            if ($id === '') {
                return;
            }
            $candidates[$id] = true;
        };

        // 1) Heurystyka z checkout-form (delivery.method.*)
        if (is_array($checkoutFormJson)) {
            $method = $checkoutFormJson['delivery']['method'] ?? null;
            $id = null;
            $name = null;
            if (is_array($method)) {
                $id = $method['id'] ?? null;
                $name = $method['name'] ?? ($method['description'] ?? null);
            }
            $hay = strtoupper(trim((string)($id ?: '') . ' ' . (string)($name ?: '')));
            if ($debug && $hay !== '') {
                $debugLines[] = '[SYNC] delivery.method (checkout-form): ' . $hay;
            }

            if (strpos($hay, 'DPD') !== false) { $add('DPD'); }
            if (strpos($hay, 'INPOST') !== false || strpos($hay, 'PACZKOMAT') !== false) { $add('INPOST'); }
            if (strpos($hay, 'DHL') !== false) { $add('DHL'); }
            if (strpos($hay, 'UPS') !== false) { $add('UPS'); }
            if (strpos($hay, 'GLS') !== false) { $add('GLS'); }
            if (strpos($hay, 'ORLEN') !== false) { $add('ORLEN'); }
            if (strpos($hay, 'ALLEGRO') !== false) { $add('ALLEGRO'); }
        }

        // 2) Lista przewoźników trackingu (Order API)
        $carriersResp = $this->api->getWithAcceptFallbacks($account, '/order/carriers', [], ['application/vnd.allegro.public.v1+json', 'application/json', '*/*']);
        if ($debug) {
            $debugLines[] = '[API] GET /order/carriers: HTTP ' . (int)($carriersResp['code'] ?? 0) . ', ok=' . (!empty($carriersResp['ok']) ? '1' : '0');
        }
        if (!empty($carriersResp['ok']) && is_array($carriersResp['json'])) {
            $rows = $carriersResp['json']['carriers'] ?? $carriersResp['json'];
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (is_string($row)) {
                        $add($row);
                    } elseif (is_array($row)) {
                        $add($row['id'] ?? ($row['carrierId'] ?? null));
                    }
                }
            }
        }

        // 3) Fallback: /shipment-management/delivery-services (z niego można też wyciągnąć carrierId).
        $dsResp = $this->api->getWithAcceptFallbacks($account, '/shipment-management/delivery-services', [], ['application/vnd.allegro.public.v1+json', 'application/json', '*/*']);
        if ($debug) {
            $debugLines[] = '[API] GET /shipment-management/delivery-services: HTTP ' . (int)($dsResp['code'] ?? 0) . ', ok=' . (!empty($dsResp['ok']) ? '1' : '0');
        }
        if (!empty($dsResp['ok']) && is_array($dsResp['json'])) {
            $this->walkAndCollectCarrierIds($dsResp['json'], $add);
        }

        // Preferowane na początku (najczęstsze w PL)
        $preferred = ['DPD', 'INPOST', 'DHL', 'UPS', 'GLS', 'ORLEN', 'ALLEGRO', 'OTHER'];
        $ordered = [];
        foreach ($preferred as $p) {
            if (isset($candidates[$p])) {
                $ordered[] = $p;
                unset($candidates[$p]);
            }
        }

        // Nie testuj setek carrierId – to potrafi wydłużyć sync do kilkunastu sekund.
        // Dokładamy maksymalnie kilkanaście dodatkowych kandydatów.
        $cap = 12;
        foreach (array_keys($candidates) as $id) {
            if (count($ordered) >= $cap) {
                break;
            }
            $ordered[] = $id;
        }

        if (empty($ordered)) {
            // awaryjnie: spróbuj najpopularniejszych carrierId
            $ordered = ['DPD', 'INPOST', 'DHL', 'UPS', 'GLS', 'ORLEN', 'ALLEGRO', 'OTHER'];
        }

        if ($debug) {
            $debugLines[] = '[SYNC] carrier candidates: ' . (empty($ordered) ? 'brak' : implode(', ', $ordered));
        }

        return $ordered;
    }

    private function walkAndCollectCarrierIds($value, callable $add): void
    {
        if (!is_array($value)) {
            return;
        }

        foreach ($value as $k => $v) {
            if (is_string($k)) {
                $lk = strtolower($k);
                if ($lk === 'carrierid' || $lk === 'carrier_id') {
                    if (is_string($v)) {
                        $add($v);
                    }
                }
            }

            if (is_array($v)) {
                $this->walkAndCollectCarrierIds($v, $add);
            }
        }
    }

    private function extractLatestTrackingEvent(array $payload, string $waybill): ?array
    {
        $waybill = trim($waybill);
        if ($waybill === '') {
            return null;
        }

        // Najczęstszy kształt odpowiedzi: { waybills: [ { waybill, trackingDetails: { statuses: [...] } } ] }
        $waybills = $payload['waybills'] ?? null;
        if (is_array($waybills)) {
            foreach ($waybills as $wbRow) {
                if (!is_array($wbRow)) {
                    continue;
                }
                $wb = isset($wbRow['waybill']) ? trim((string)$wbRow['waybill']) : '';
                if ($wb === '' || strcasecmp($wb, $waybill) !== 0) {
                    continue;
                }
                $td = $wbRow['trackingDetails'] ?? null;
                if (!is_array($td)) {
                    continue;
                }
                $statuses = $td['statuses'] ?? null;
                if (!is_array($statuses) || empty($statuses)) {
                    continue;
                }

                $best = null;
                $bestTs = 0;
                foreach ($statuses as $st) {
                    if (!is_array($st)) {
                        continue;
                    }
                    $code = $st['code'] ?? ($st['status'] ?? ($st['statusCode'] ?? null));
                    if (!is_string($code) || trim($code) === '') {
                        continue;
                    }

                    $dt = $this->extractDateFromTrackingStatus($st);
                    $ts = $dt ? (int)strtotime($dt) : 0;
                    if ($best === null || $ts >= $bestTs) {
                        $best = ['status' => (string)$code, 'at' => $dt];
                        $bestTs = $ts;
                    }
                }

                if (is_array($best) && !empty($best['status'])) {
                    return $best;
                }
            }
        }

        // Fallback: rekurencyjnie przejdź po payloadzie (czasem różne kształty odpowiedzi)
        $events = [];
        $this->collectTrackingEvents($payload, $events);
        if (empty($events)) {
            return null;
        }
        usort($events, function (array $a, array $b): int {
            $ta = strtotime((string)($a['at'] ?? '')) ?: 0;
            $tb = strtotime((string)($b['at'] ?? '')) ?: 0;
            return $tb <=> $ta;
        });
        return $events[0] ?? null;
    }

    private function extractDateFromTrackingStatus(array $st): ?string
    {
        foreach (['occurredAt','occurred_at','at','eventAt','event_at','eventDate','date','timestamp','time','updatedAt','createdAt'] as $k) {
            if (!array_key_exists($k, $st)) {
                continue;
            }
            $dt = $this->normalizeDateTime($st[$k]);
            if ($dt) {
                return $dt;
            }
        }
        // Czasem jest zagnieżdżone
        if (!empty($st['occurred']) && is_array($st['occurred']) && !empty($st['occurred']['at'])) {
            $dt = $this->normalizeDateTime($st['occurred']['at']);
            if ($dt) {
                return $dt;
            }
        }
        return null;
    }

    private function collectTrackingEvents($node, array &$events): void
    {
        if (!is_array($node)) {
            return;
        }

        // event-like object
        $statusKeys = ['status', 'statusCode', 'code'];
        $timeKeys = ['at', 'occurredAt', 'occurred_at', 'eventDate', 'date', 'timestamp', 'createdAt', 'updatedAt'];

        $status = null;
        foreach ($statusKeys as $k) {
            if (isset($node[$k]) && is_string($node[$k]) && trim($node[$k]) !== '') {
                $status = trim((string)$node[$k]);
                break;
            }
        }

        $at = null;
        foreach ($timeKeys as $k) {
            if (isset($node[$k]) && is_string($node[$k]) && trim($node[$k]) !== '') {
                $at = $this->normalizeDateTime($node[$k]);
                if ($at) {
                    break;
                }
            }
        }

        if ($status !== null && $at !== null) {
            $events[] = ['status' => $status, 'at' => $at];
        }

        foreach ($node as $v) {
            if (is_array($v)) {
                $this->collectTrackingEvents($v, $events);
            }
        }
    }

    private function normalizeTrackingStatus(string $status): string
{
    $s = strtoupper(trim($status));
    if ($s === '') {
        return 'CREATED';
    }

    // Ujednolicenia między różnymi carrierami (żeby frontend BO miał spójne wartości)
    $map = [
        'PENDING' => 'CREATED',
        'PROCESSING' => 'IN_PROGRESS',
        'ON_THE_WAY' => 'IN_TRANSIT',
        'RELEASED_FOR_DELIVERY' => 'OUT_FOR_DELIVERY',
        'AVAILABLE_FOR_PICKUP' => 'READY_FOR_PICKUP',
        'PICKED_UP' => 'DELIVERED',
    ];

    if (isset($map[$s])) {
        return $map[$s];
    }

    // Czasem przychodzą "unifikowane" statusy z innych integracji
    if ($s === 'SENT') {
        return 'IN_TRANSIT';
    }

    return $s;
}

/**
 * Ranking statusów - potrzebny gdy różne carrierId zwracają różne kody dla tego samego waybilla.
 * Np. INPOST potrafi zwrócić PENDING, gdy ALLEGRO ma już IN_TRANSIT — wtedy wybieramy IN_TRANSIT.
 */
private function trackingStatusRank(string $status): int
{
    $s = strtoupper(trim($status));

    // Problemy / wyjątki - powinny "wygrywać" nad zwykłym IN_TRANSIT
    if (in_array($s, ['LOST', 'DELIVERY_FAILED', 'UNDELIVERED'], true)) {
        return 120;
    }

    if ($s === 'DELIVERED') {
        return 110;
    }

    if (in_array($s, ['RETURNED_TO_SENDER', 'RETURNED'], true)) {
        return 105;
    }

    if (in_array($s, ['READY_FOR_PICKUP', 'AVAILABLE_FOR_PICKUP'], true)) {
        return 90;
    }

    if (in_array($s, ['OUT_FOR_DELIVERY', 'RELEASED_FOR_DELIVERY'], true)) {
        return 80;
    }

    if (in_array($s, ['IN_TRANSIT', 'ON_THE_WAY'], true)) {
        return 70;
    }

    if (in_array($s, ['IN_PROGRESS', 'PROCESSING', 'NEW'], true)) {
        return 40;
    }

    if (in_array($s, ['CREATED', 'PENDING'], true)) {
        return 10;
    }

    // Nieznane: ustaw średnio, żeby nie "zbić" do CREATED
    return 50;
}



    private function normalizeDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // numeric timestamp (sekundy lub milisekundy)
        if (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^\d{10,16}$/', trim($value)))) {
            $n = (float)$value;
            if ($n > 20000000000) { // wygląda na ms
                $n = $n / 1000.0;
            }
            if ($n > 0) {
                return date('Y-m-d H:i:s', (int)$n);
            }
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Utnij ułamki sekund w ISO (np. 2026-02-13T11:34:00.123Z)
        $value2 = preg_replace('/(\d{2}:\d{2}:\d{2})\.\d+/', '$1', $value);
        $ts = strtotime($value2);
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
