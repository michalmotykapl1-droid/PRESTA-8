<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\ShipmentRepository;

class ShipmentSyncService
{
    private AllegroApiClient $api;
    private ShipmentRepository $shipments;
    private ShipmentReferenceResolver $resolver;

    /**
     * Kontekst z /order/checkout-forms/{id}/shipments i innych źródeł,
     * indeksowany po "referencji" (shipment id / command id / base64 INPOST:...).
     */
    private array $discoveredShipmentContext = [];

    /**
     * Mapowanie waybill -> shipment reference (np. base64 INPOST:...).
     * Używane do fallbacków, gdy /shipment-management nie zwraca packages[].waybill.
     */
    private array $discoveredWaybillToShipmentRef = [];

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
        $this->discoveredWaybillToShipmentRef = [];
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

        // Lista referencji przesyłek do przetworzenia (shipment_id / commandId / base64 INPOST:...).
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
        foreach (array_keys($shipmentIds) as $shipmentRef) {
            $shipmentRef = trim((string)$shipmentRef);
            if ($shipmentRef === '') {
                continue;
            }

            // resolvedShipmentId = UUID do /shipment-management/shipments/{id} (jeśli da się go wyliczyć)
            $resolvedShipmentId = $this->resolver->resolveShipmentIdCandidate($account, $shipmentRef, $debugLines);

            // Kontekst znaleziony np. w /order/.../shipments
            $context = $this->discoveredShipmentContext[$shipmentRef] ?? ($resolvedShipmentId ? ($this->discoveredShipmentContext[$resolvedShipmentId] ?? null) : null);

            if ($resolvedShipmentId === null) {
                // Nie mamy UUID shipmentu – spróbuj wyciągnąć status z trackingu po numerze nadania.
                $waybill = $this->extractWaybillCandidate($shipmentRef, $context);
                if (is_string($waybill) && $waybill !== '') {
                    $progress = $this->fetchTrackingProgressByWaybill($account, $carrierCandidates, $waybill, $debugLines, $debug);
                    if (is_array($progress) && !empty($progress['status'])) {
                        if (!is_array($context)) {
                            $context = [];
                        }
                        $context['status'] = (string)$progress['status'];
                        if (!empty($progress['at'])) {
                            $context['status_changed_at'] = (string)$progress['at'];
                        }
                    }
                }

                if ($this->upsertFromDiscoveredContext($accountId, $checkoutFormId, $shipmentRef, $context, $debugLines)) {
                    $synced++;
                }
                continue;
            }

            $detail = $this->api->get($account, '/shipment-management/shipments/' . rawurlencode($resolvedShipmentId));
            if (!$detail['ok'] || !is_array($detail['json'])) {
                if ($debug) {
                    $debugLines[] = '[SYNC] brak szczegółów shipment-management dla shipmentId=' . $resolvedShipmentId
                        . ' (HTTP ' . (int)($detail['code'] ?? 0) . ') - fallback do kontekstu zamówienia';
                }

                if ($this->upsertFromDiscoveredContext($accountId, $checkoutFormId, $shipmentRef, $context, $debugLines)) {
                    $synced++;
                }
                continue;
            }

            $status = (string)($detail['json']['status'] ?? 'CREATED');

            // Waybille z /shipment-management
            $tracking = $this->extractTrackingNumber($detail['json']);
            $detailWaybills = $this->extractAllWaybillsFromArray($detail['json']);

            // Fallback: jeśli /shipment-management nie zwrócił waybilli, bierzemy z kontekstu /order/.../shipments
            $ctxWaybills = $this->extractWaybillsFromContext($context);
            if (!empty($ctxWaybills)) {
                foreach ($ctxWaybills as $wb) {
                    $wb = trim((string)$wb);
                    if ($wb !== '') {
                        $detailWaybills[$wb] = true;
                    }
                }
            }

            // Jeżeli tracking nieustalony – ustaw go na pierwszy znany waybill.
            if ((!is_string($tracking) || trim($tracking) === '') && !empty($detailWaybills)) {
                $tracking = array_key_first($detailWaybills);
            }

            // Ostateczny fallback na podstawie referencji (np. base64("INPOST:WAYBILL") lub kontekst)
            if (!is_string($tracking) || trim($tracking) === '') {
                $tracking = $this->extractWaybillCandidate($shipmentRef, $context);
            }
            $tracking = is_string($tracking) ? trim($tracking) : null;

            // Zbiór waybilli jako lista (unikalna)
            $waybillsAll = array_keys($detailWaybills);
            if (is_string($tracking) && $tracking !== '') {
                $detailWaybills[$tracking] = true;
            }
            $waybillsAll = array_keys($detailWaybills);

            $isSmart = $this->extractIsSmart($detail['json']);
            $carrierMode = $this->extractCarrierMode($detail['json']);
            $sizeDetails = $this->extractSizeDetails($detail['json']);

            $createdAt = $this->normalizeDateTime($detail['json']['createdAt'] ?? null);
            $statusChangedAt = $this->normalizeDateTime($detail['json']['statusChangedAt'] ?? ($detail['json']['updatedAt'] ?? null));

            if ($debug) {
                $pkgCnt = (!empty($detail['json']['packages']) && is_array($detail['json']['packages'])) ? count($detail['json']['packages']) : 0;
                $debugLines[] = '[SYNC] shipment detail: shipmentId=' . $resolvedShipmentId
                    . ', packages=' . $pkgCnt
                    . ', waybills=' . (!empty($waybillsAll) ? implode('|', $waybillsAll) : '-');
            }

            // Gdy /shipment-management zwraca status bazowy, preferuj status z /checkout-forms/{id}/shipments.
            $ctxStatus = is_array($context) ? strtoupper(trim((string)($context['status'] ?? ''))) : '';
            if ($this->isBaseShipmentStatus($status) && $ctxStatus !== '' && !$this->isBaseShipmentStatus($ctxStatus)) {
                $status = $ctxStatus;
                $ctxChanged = $this->normalizeDateTime($context['status_changed_at'] ?? ($context['updated_at'] ?? null));
                if ($ctxChanged) {
                    $statusChangedAt = $ctxChanged;
                }
                if ($debug) {
                    $debugLines[] = '[SYNC] status override z checkout-forms/{id}/shipments: ' . $status;
                }
            }

            // Jeśli Allegro zwraca status bazowy (np. CREATED), spróbuj trackingu po numerze nadania.
            // Wybieramy "najlepszy" status po rankingu + czasie.
            if ($this->isBaseShipmentStatus($status) && !empty($waybillsAll)) {
                $best = null;
                foreach ($waybillsAll as $wb) {
                    $wb = trim((string)$wb);
                    if ($wb === '') {
                        continue;
                    }
                    $progress = $this->fetchTrackingProgressByWaybill($account, $carrierCandidates, $wb, $debugLines, $debug);
                    if (!is_array($progress) || empty($progress['status'])) {
                        continue;
                    }
                    $candStatus = (string)$progress['status'];
                    $candAt = !empty($progress['at']) ? (string)$progress['at'] : null;

                    if ($debug) {
                        $debugLines[] = '[SYNC] tracking candidate: waybill=' . $wb . ', status=' . $candStatus . ', at=' . ($candAt ?: '-');
                    }

                    $best = $this->pickBetterProgress($best, ['status' => $candStatus, 'at' => $candAt, 'waybill' => $wb]);
                }

                if (is_array($best) && !empty($best['status'])) {
                    $status = (string)$best['status'];
                    if (!empty($best['at'])) {
                        $statusChangedAt = (string)$best['at'];
                    }
                    if ($debug) {
                        $debugLines[] = '[SYNC] tracking best: waybill=' . ($best['waybill'] ?? '-') . ', status=' . $status . ', at=' . ($statusChangedAt ?: '-');
                    }
                }
            }

            if (!$statusChangedAt) {
                $statusChangedAt = $createdAt ?: date('Y-m-d H:i:s');
            }

            // UPSERT: UWAGA
            // shipment_id w tabeli ma być zawsze "referencją" z Allegro (/order/.../shipments) lub UUID,
            // ale NIGDY samym numerem nadania. Tracking idzie do tracking_number.
            //
            // Przechowujemy wiersz(e) per waybill (tracking_number), żeby "Historia Przesyłek" miała komplet.
            // Repozytorium obsługuje teraz wiele rekordów dla shipment_id (unikalne po tracking_number).
            if (!empty($waybillsAll)) {
                foreach ($waybillsAll as $wb) {
                    $wb = trim((string)$wb);
                    if ($wb === '') {
                        continue;
                    }

                    // Dla tej samej referencji i WAYBILL, nie twórz nowego rekordu gdy już istnieje.
                    if ($this->shipments->trackingExistsForOrder($accountId, $checkoutFormId, $wb)) {
                        // Jeżeli rekord jest, to tylko update przez upsertFromAllegro (match po shipment_id lub fallback po tracking)
                        $this->shipments->upsertFromAllegro(
                            $accountId,
                            $checkoutFormId,
                            $resolvedShipmentId ?: $shipmentRef,
                            $status,
                            $wb,
                            $isSmart,
                            $carrierMode,
                            $sizeDetails,
                            $createdAt,
                            $statusChangedAt
                        );
                        continue;
                    }

                    $this->shipments->upsertFromAllegro(
                        $accountId,
                        $checkoutFormId,
                        $resolvedShipmentId ?: $shipmentRef,
                        $status,
                        $wb,
                        $isSmart,
                        $carrierMode,
                        $sizeDetails,
                        $createdAt,
                        $statusChangedAt
                    );
                }
                $synced++;
            } else {
                // Brak waybilla – zapis pojedynczy
                $this->shipments->upsertFromAllegro(
                    $accountId,
                    $checkoutFormId,
                    $resolvedShipmentId ?: $shipmentRef,
                    $status,
                    $tracking,
                    $isSmart,
                    $carrierMode,
                    $sizeDetails,
                    $createdAt,
                    $statusChangedAt
                );
                $synced++;
            }
        }

        // Dodatkowe domknięcie: jeśli mamy kontekst z /order/.../shipments,
        // upewnij się, że wszystkie waybille są obecne lokalnie.
        $synced += $this->ensureWaybillsFromContext($accountId, $checkoutFormId, $debugLines);

        // Smart rebalance (nie więcej is_smart=1 niż limit paczek z checkout-form)
        if (method_exists($this->shipments, 'rebalanceSmartFlagsForOrder')) {
            $rebalanced = $this->shipments->rebalanceSmartFlagsForOrder(
                $accountId,
                $checkoutFormId,
                $this->currentOrderSmartPackageLimit,
                $this->currentOrderIsSmart
            );
            if ($debug) {
                $debugLines[] = '[SYNC] smart rebalance: updated=' . (int)$rebalanced
                    . ', order_is_smart=' . var_export($this->currentOrderIsSmart, true)
                    . ', package_limit=' . var_export($this->currentOrderSmartPackageLimit, true);
            }
        }

        // Merge WZA fields by tracking_number
        if (method_exists($this->shipments, 'mergeWzaFieldsForOrder')) {
            $merged = $this->shipments->mergeWzaFieldsForOrder($accountId, $checkoutFormId);
            if ($debug) {
                $debugLines[] = '[SYNC] mergeWzaFieldsForOrder merged=' . (int)$merged;
            }
        }

        // Backfill custom UUID from shipment_id
        if (method_exists($this->shipments, 'backfillCustomWzaShipmentUuidFromShipmentIdForOrder')) {
            $filled = $this->shipments->backfillCustomWzaShipmentUuidFromShipmentIdForOrder($accountId, $checkoutFormId);
            if ($debug) {
                $debugLines[] = '[SYNC] backfillCustomWzaShipmentUuidFromShipmentIdForOrder updated=' . (int)$filled;
            }
        } elseif (method_exists($this->shipments, 'backfillCustomWzaShipmentUuidFromShipmentId')) {
            $filled = $this->shipments->backfillCustomWzaShipmentUuidFromShipmentId($accountId);
            if ($debug) {
                $debugLines[] = '[SYNC] backfillCustomWzaShipmentUuidFromShipmentId updated=' . (int)$filled;
            }
        }

        // deduplikacja
        $removed = $this->shipments->removeDuplicatesForOrder($accountId, $checkoutFormId);

        if ($debug) {
            $elapsed = round((microtime(true) - $startedAt) * 1000);
            $debugLines[] = '[SYNC] done synced=' . $synced . ', removed_duplicates=' . $removed . ', elapsed=' . $elapsed . 'ms';
            $this->persistSyncDebug($checkoutFormId, $debugLines);
        }

        return ['ok' => true, 'synced' => $synced, 'removed_duplicates' => $removed, 'debug_lines' => $debugLines];
    }

    private function discoverShipmentIdsFromApi(array $account, string $checkoutFormId, array &$debugLines): array
    {
        $out = [];

        $resp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId) . '/shipments');
        if ($resp['ok'] && is_array($resp['json'])) {
            $items = $resp['json']['shipments'] ?? ($resp['json']['items'] ?? []);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $shipmentRef = null;
                    foreach ([
                        $item['id'] ?? null,
                        $item['shipmentId'] ?? null,
                        $item['shipment']['id'] ?? null,
                        $item['reference'] ?? null,
                        $item['externalId'] ?? null,
                    ] as $cand) {
                        if (is_string($cand) && trim($cand) !== '') {
                            $shipmentRef = trim($cand);
                            break;
                        }
                    }

                    $ctx = $this->mapShipmentContext($item);
                    if ($shipmentRef !== null) {
                        $out[$shipmentRef] = true;
                        if ($ctx) {
                            $this->discoveredShipmentContext[$shipmentRef] = $ctx;
                        }

                        if (!empty($ctx['waybills']) && is_array($ctx['waybills'])) {
                            foreach ($ctx['waybills'] as $wb) {
                                $wb = trim((string)$wb);
                                if ($wb !== '') {
                                    $this->discoveredWaybillToShipmentRef[$wb] = $shipmentRef;
                                }
                            }
                        }
                    } else {
                        // Awaryjny synthetic ref po waybillu
                        if ($ctx && !empty($ctx['waybills'][0])) {
                            $wb = trim((string)$ctx['waybills'][0]);
                            if ($wb !== '') {
                                $synthetic = 'WB:' . $wb;
                                $out[$synthetic] = true;
                                $this->discoveredShipmentContext[$synthetic] = $ctx;
                                $this->discoveredWaybillToShipmentRef[$wb] = $synthetic;
                            }
                        }
                    }
                }
            }
        }

        return array_keys($out);
    }

    private function mapShipmentContext(array $item): ?array
    {
        $ctx = [];

        $status = $item['status'] ?? ($item['state'] ?? null);
        if (is_string($status) && trim($status) !== '') {
            $ctx['status'] = strtoupper(trim($status));
        }

        $createdAt = $this->normalizeDateTime($item['createdAt'] ?? null);
        $updatedAt = $this->normalizeDateTime($item['updatedAt'] ?? null);
        $statusChangedAt = $this->normalizeDateTime($item['statusChangedAt'] ?? null);

        if ($createdAt) {
            $ctx['created_at'] = $createdAt;
        }
        if ($updatedAt) {
            $ctx['updated_at'] = $updatedAt;
        }
        if ($statusChangedAt) {
            $ctx['status_changed_at'] = $statusChangedAt;
        }

        $waybills = $this->extractAllWaybillsFromArray($item);
        if (!empty($waybills)) {
            $ctx['waybills'] = array_keys($waybills);
            $ctx['tracking'] = $ctx['waybills'][0];
            $ctx['tracking_number'] = $ctx['waybills'][0];
        }

        $isSmart = $this->extractIsSmart($item);
        if ($isSmart !== null) {
            $ctx['is_smart'] = $isSmart;
        }

        $carrierMode = $this->extractCarrierMode($item);
        if ($carrierMode !== null) {
            $ctx['carrier_mode'] = $carrierMode;
        }

        $sizeDetails = $this->extractSizeDetails($item);
        if ($sizeDetails !== null) {
            $ctx['size_details'] = $sizeDetails;
        }

        if (empty($ctx)) {
            return null;
        }

        return $ctx;
    }

    private function upsertFromDiscoveredContext(int $accountId, string $checkoutFormId, string $shipmentId, ?array $context, array &$debugLines): bool
    {
        if (!is_array($context) || empty($context)) {
            return false;
        }

        if (!$this->shouldInsertContextShipment($accountId, $checkoutFormId, $shipmentId, $context, $debugLines)) {
            return false;
        }

        $status = (string)($context['status'] ?? 'CREATED');
        $tracking = isset($context['tracking_number']) ? trim((string)$context['tracking_number']) : null;
        if ($tracking === '') {
            $tracking = null;
        }

        $isSmart = array_key_exists('is_smart', $context) ? (int)$context['is_smart'] : null;
        $carrierMode = isset($context['carrier_mode']) ? (string)$context['carrier_mode'] : null;
        $sizeDetails = isset($context['size_details']) ? (string)$context['size_details'] : null;

        $createdAt = $this->normalizeDateTime($context['created_at'] ?? null);
        $statusChangedAt = $this->normalizeDateTime($context['status_changed_at'] ?? ($context['updated_at'] ?? null));

        if ($this->isBaseShipmentStatus($status) && is_string($tracking) && $tracking !== '') {
            $carrierCandidates = $this->getCarrierCandidatesForOrder([], null, $checkoutFormId, $debugLines, false);
            $progress = $this->fetchTrackingProgressByWaybill([], $carrierCandidates, $tracking, $debugLines, false);
            if (is_array($progress) && !empty($progress['status'])) {
                $status = (string)$progress['status'];
                if (!empty($progress['at'])) {
                    $statusChangedAt = (string)$progress['at'];
                }
            }
        }

        if (empty($statusChangedAt)) {
            $statusChangedAt = $createdAt ?: date('Y-m-d H:i:s');
        }

        $this->shipments->upsertFromAllegro(
            $accountId,
            $checkoutFormId,
            $shipmentId,
            $status,
            $tracking,
            $isSmart,
            $carrierMode,
            $sizeDetails,
            $createdAt,
            $statusChangedAt
        );

        return true;
    }

    private function shouldInsertContextShipment(int $accountId, string $checkoutFormId, string $shipmentId, array $context, array &$debugLines): bool
    {
        $tracking = trim((string)($context['tracking_number'] ?? ''));
        if ($tracking === '' && !empty($context['waybills']) && is_array($context['waybills'])) {
            foreach ($context['waybills'] as $wb) {
                $wb = trim((string)$wb);
                if ($wb !== '') {
                    $tracking = $wb;
                    break;
                }
            }
        }

        if ($tracking !== '') {
            return true;
        }

        if (method_exists($this->shipments, 'shipmentIdExistsForOrder')
            && $this->shipments->shipmentIdExistsForOrder($accountId, $checkoutFormId, $shipmentId)
        ) {
            return true;
        }

        if (method_exists($this->shipments, 'hasAnyTrackingNumberForOrder')
            && !$this->shipments->hasAnyTrackingNumberForOrder($accountId, $checkoutFormId)
        ) {
            return true;
        }

        $debugLines[] = '[SYNC] pomijam kontekst bez trackingu dla shipmentRef=' . $shipmentId
            . ' (zamówienie ma już przesyłki z numerami nadania)';

        return false;
    }

    private function ensureWaybillsFromContext(int $accountId, string $checkoutFormId, array &$debugLines): int
    {
        $added = 0;

        foreach ($this->discoveredShipmentContext as $shipmentRef => $ctx) {
            if (!is_array($ctx) || empty($ctx['waybills']) || !is_array($ctx['waybills'])) {
                continue;
            }

            $status = (string)($ctx['status'] ?? 'CREATED');
            $isSmart = array_key_exists('is_smart', $ctx) ? (int)$ctx['is_smart'] : null;
            $carrierMode = isset($ctx['carrier_mode']) ? (string)$ctx['carrier_mode'] : null;
            $sizeDetails = isset($ctx['size_details']) ? (string)$ctx['size_details'] : null;
            $createdAt = $this->normalizeDateTime($ctx['created_at'] ?? null);
            $statusChangedAt = $this->normalizeDateTime($ctx['status_changed_at'] ?? ($ctx['updated_at'] ?? null));

            foreach ($ctx['waybills'] as $wb) {
                $wb = trim((string)$wb);
                if ($wb === '') {
                    continue;
                }

                if ($this->shipments->trackingExistsForOrder($accountId, $checkoutFormId, $wb)) {
                    continue;
                }

                $finalStatus = $status;
                $finalAt = $statusChangedAt;

                if ($this->isBaseShipmentStatus($status)) {
                    $carrierCandidates = $this->getCarrierCandidatesForOrder([], null, $checkoutFormId, $debugLines, false);
                    $progress = $this->fetchTrackingProgressByWaybill([], $carrierCandidates, $wb, $debugLines, false);
                    if (is_array($progress) && !empty($progress['status'])) {
                        $finalStatus = (string)$progress['status'];
                        if (!empty($progress['at'])) {
                            $finalAt = (string)$progress['at'];
                        }
                    }
                }

                if (!$finalAt) {
                    $finalAt = $createdAt ?: date('Y-m-d H:i:s');
                }

                $this->shipments->upsertFromAllegro(
                    $accountId,
                    $checkoutFormId,
                    (string)$shipmentRef,
                    $finalStatus,
                    $wb,
                    $isSmart,
                    $carrierMode,
                    $sizeDetails,
                    $createdAt,
                    $finalAt
                );
                $added++;
            }
        }

        if ($added > 0) {
            $debugLines[] = '[SYNC] ensureWaybillsFromContext added=' . $added;
        }

        return $added;
    }

    private function extractShipmentIdsFromCheckoutForm(array $json): array
    {
        $ids = [];

        $nodes = [];
        if (isset($json['shipments']) && is_array($json['shipments'])) {
            $nodes[] = $json['shipments'];
        }
        if (isset($json['delivery']['shipments']) && is_array($json['delivery']['shipments'])) {
            $nodes[] = $json['delivery']['shipments'];
        }
        if (isset($json['shipping']['shipments']) && is_array($json['shipping']['shipments'])) {
            $nodes[] = $json['shipping']['shipments'];
        }

        foreach ($nodes as $shipments) {
            foreach ($shipments as $s) {
                if (!is_array($s)) {
                    continue;
                }

                foreach ([
                    $s['id'] ?? null,
                    $s['shipmentId'] ?? null,
                    $s['shipment']['id'] ?? null,
                    $s['reference'] ?? null,
                    $s['externalId'] ?? null,
                ] as $cand) {
                    if (is_string($cand) && trim($cand) !== '') {
                        $ids[trim($cand)] = true;
                    }
                }
            }
        }

        $direct = [
            $json['delivery']['shipmentId'] ?? null,
            $json['shipping']['shipmentId'] ?? null,
            $json['shipmentId'] ?? null,
        ];
        foreach ($direct as $cand) {
            if (is_string($cand) && trim($cand) !== '') {
                $ids[trim($cand)] = true;
            }
        }

        return array_keys($ids);
    }

    private function extractAllWaybillsFromArray(array $shipment): array
    {
        $out = [];

        if (!empty($shipment['packages']) && is_array($shipment['packages'])) {
            foreach ($shipment['packages'] as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $wb = $p['waybill'] ?? ($p['trackingNumber'] ?? ($p['waybillNumber'] ?? null));
                if (is_string($wb) && trim($wb) !== '') {
                    $out[trim($wb)] = true;
                }
            }
        }

        foreach ([
            $shipment['trackingNumber'] ?? null,
            $shipment['waybill'] ?? null,
            $shipment['waybillNumber'] ?? null,
            $shipment['tracking']['number'] ?? null,
            $shipment['label']['trackingNumber'] ?? null,
            $shipment['summary']['trackingNumber'] ?? null,
        ] as $wb) {
            if (is_string($wb) && trim($wb) !== '') {
                $out[trim($wb)] = true;
            }
        }

        return $out;
    }

    private function extractWaybillsFromContext(?array $context): array
    {
        if (!is_array($context) || empty($context)) {
            return [];
        }

        $out = [];
        $t = $context['tracking'] ?? ($context['tracking_number'] ?? null);
        if (is_string($t) && trim($t) !== '') {
            $out[trim($t)] = true;
        }

        if (!empty($context['waybills']) && is_array($context['waybills'])) {
            foreach ($context['waybills'] as $wb) {
                if (is_string($wb) && trim($wb) !== '') {
                    $out[trim($wb)] = true;
                }
            }
        }

        return array_keys($out);
    }

    private function getCarrierCandidatesForOrder(array $account, ?array $checkoutFormJson, string $checkoutFormId, array &$debugLines, bool $debug): array
    {
        $candidates = [];

        if (is_array($checkoutFormJson)) {
            $methodName = trim((string)($checkoutFormJson['delivery']['method']['name'] ?? ''));
            $methodId = trim((string)($checkoutFormJson['delivery']['method']['id'] ?? ''));
            if ($methodName !== '' || $methodId !== '') {
                $candidates[] = [
                    'source' => 'checkout-form',
                    'method_id' => $methodId,
                    'method_name' => $methodName,
                ];
            }
        }

        foreach ($this->discoveredShipmentContext as $ctx) {
            if (!is_array($ctx) || empty($ctx['waybills']) || !is_array($ctx['waybills'])) {
                continue;
            }
            foreach ($ctx['waybills'] as $wb) {
                $w = strtoupper(trim((string)$wb));
                if ($w === '') {
                    continue;
                }

                if (strpos($w, 'INPOST') !== false || strpos($w, 'LP') === 0 || strpos($w, '00') === 0) {
                    $candidates[] = ['source' => 'waybill', 'carrier' => 'INPOST'];
                    break;
                }
            }
        }

        if ($debug) {
            $debugLines[] = '[SYNC] carrier candidates=' . json_encode($candidates, JSON_UNESCAPED_UNICODE);
        }

        return $candidates;
    }

    private function isBaseShipmentStatus(string $status): bool
    {
        $status = strtoupper(trim($status));
        return $status === '' || in_array($status, ['CREATED', 'NEW', 'IN_PROGRESS'], true);
    }

    private function extractWaybillCandidate(string $shipmentId, ?array $context = null): ?string
    {
        if (is_array($context)) {
            $t = $context['tracking'] ?? null;
            if (is_string($t) && trim($t) !== '') {
                return trim($t);
            }

            $wbs = $context['waybills'] ?? null;
            if (is_array($wbs) && !empty($wbs)) {
                $first = trim((string)($wbs[0] ?? ''));
                if ($first !== '') {
                    return $first;
                }
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

    private function fetchTrackingProgressByWaybill(array $account, array $carrierCandidates, string $waybill, array &$debugLines = [], bool $debug = false): ?array
    {
        $waybill = trim($waybill);
        if ($waybill === '') {
            return null;
        }

        foreach ($carrierCandidates as $cand) {
            $carrierId = strtoupper(trim((string)($cand['carrier'] ?? ($cand['carrier_id'] ?? ''))));
            if ($carrierId === '') {
                $carrierId = 'INPOST';
            }

            $resp = $this->api->get($account, '/order/carriers/' . rawurlencode($carrierId) . '/tracking', ['waybill' => $waybill]);
            if ($debug) {
                $debugLines[] = '[TRACK] GET /order/carriers/' . $carrierId . '/tracking?waybill=' . $waybill
                    . ' => HTTP ' . (int)($resp['code'] ?? 0) . ', ok=' . (!empty($resp['ok']) ? '1' : '0');
            }

            if (!$resp['ok'] || !is_array($resp['json'])) {
                continue;
            }

            $progress = $this->extractTrackingProgress($resp['json'], $waybill, $carrierCandidates);
            if (is_array($progress) && !empty($progress['status'])) {
                return $progress;
            }
        }

        // Fallback bez carrierId (niektóre środowiska mają niestandardowy endpoint w API wrapperze)
        $resp = $this->api->get($account, '/order/carriers/tracking', ['waybill' => $waybill]);
        if ($debug) {
            $debugLines[] = '[TRACK] fallback /order/carriers/tracking?waybill=' . $waybill
                . ' => HTTP ' . (int)($resp['code'] ?? 0) . ', ok=' . (!empty($resp['ok']) ? '1' : '0');
        }
        if ($resp['ok'] && is_array($resp['json'])) {
            $progress = $this->extractTrackingProgress($resp['json'], $waybill, $carrierCandidates);
            if (is_array($progress) && !empty($progress['status'])) {
                return $progress;
            }
        }

        return null;
    }

    private function extractTrackingProgress(array $json, string $waybill, array $carrierCandidates): ?array
    {
        $events = [];

        if (isset($json['tracking']['events']) && is_array($json['tracking']['events'])) {
            foreach ($json['tracking']['events'] as $ev) {
                if (is_array($ev)) {
                    $events[] = $ev;
                }
            }
        }

        if (isset($json['events']) && is_array($json['events'])) {
            foreach ($json['events'] as $ev) {
                if (is_array($ev)) {
                    $events[] = $ev;
                }
            }
        }

        if (isset($json['items']) && is_array($json['items'])) {
            foreach ($json['items'] as $it) {
                if (is_array($it)) {
                    $events[] = $it;
                }
            }
        }

        $topStatus = $json['status'] ?? ($json['tracking']['status'] ?? null);
        if (is_string($topStatus) && trim($topStatus) !== '') {
            return [
                'status' => $this->normalizeTrackingStatus((string)$topStatus),
                'at' => $this->normalizeDateTime($json['updatedAt'] ?? ($json['statusChangedAt'] ?? null)),
            ];
        }

        if (empty($events)) {
            return null;
        }

        $best = null;
        foreach ($events as $ev) {
            $s = $ev['status'] ?? ($ev['type'] ?? ($ev['code'] ?? null));
            if (!is_string($s) || trim($s) === '') {
                continue;
            }

            $at = $this->normalizeDateTime($ev['occurredAt'] ?? ($ev['at'] ?? ($ev['date'] ?? ($ev['time'] ?? null))));
            $cand = ['status' => $this->normalizeTrackingStatus($s), 'at' => $at];
            $best = $this->pickBetterProgress($best, $cand);
        }

        return $best;
    }

    private function pickBetterProgress(?array $current, array $candidate): array
    {
        if (!is_array($current) || empty($current)) {
            return $candidate;
        }

        $currRank = $this->trackingStatusRank((string)($current['status'] ?? ''));
        $candRank = $this->trackingStatusRank((string)($candidate['status'] ?? ''));

        if ($candRank > $currRank) {
            return $candidate;
        }
        if ($candRank < $currRank) {
            return $current;
        }

        $currAt = !empty($current['at']) ? strtotime((string)$current['at']) : false;
        $candAt = !empty($candidate['at']) ? strtotime((string)$candidate['at']) : false;

        if ($candAt !== false && $currAt !== false) {
            return $candAt >= $currAt ? $candidate : $current;
        }
        if ($candAt !== false) {
            return $candidate;
        }

        return $current;
    }

    private function trackingStatusRank(string $status): int
    {
        $s = strtoupper(trim($status));

        $rank = [
            'CREATED' => 1,
            'CONFIRMED' => 2,
            'ACCEPTED' => 3,
            'IN_PREPARATION' => 4,
            'READY_TO_SEND' => 5,
            'SENT' => 6,
            'IN_TRANSIT' => 7,
            'OUT_FOR_DELIVERY' => 8,
            'READY_FOR_PICKUP' => 9,
            'DELIVERED' => 10,
            'RETURNED' => 10,
            'CANCELLED' => 10,
            'FAILED' => 10,
        ];

        return $rank[$s] ?? 0;
    }

    private function normalizeTrackingStatus(string $status): string
    {
        $s = strtoupper(trim($status));

        $map = [
            'UNKNOWN' => 'CREATED',
            'CREATED' => 'CREATED',
            'NEW' => 'CREATED',
            'REGISTERED' => 'CONFIRMED',
            'CONFIRMED' => 'CONFIRMED',
            'ACCEPTED' => 'ACCEPTED',
            'PREPARED' => 'IN_PREPARATION',
            'IN_PREPARATION' => 'IN_PREPARATION',
            'READY_TO_SEND' => 'READY_TO_SEND',
            'SENT' => 'SENT',
            'IN_TRANSIT' => 'IN_TRANSIT',
            'OUT_FOR_DELIVERY' => 'OUT_FOR_DELIVERY',
            'AT_PICKUP_POINT' => 'READY_FOR_PICKUP',
            'READY_FOR_PICKUP' => 'READY_FOR_PICKUP',
            'DELIVERED' => 'DELIVERED',
            'RETURNED' => 'RETURNED',
            'CANCELLED' => 'CANCELLED',
            'CANCELED' => 'CANCELLED',
            'ERROR' => 'FAILED',
            'FAILED' => 'FAILED',
        ];

        if (isset($map[$s])) {
            return $map[$s];
        }

        if (strpos($s, 'DELIVER') !== false) {
            return 'DELIVERED';
        }
        if (strpos($s, 'TRANSIT') !== false || strpos($s, 'ROUTE') !== false) {
            return 'IN_TRANSIT';
        }
        if (strpos($s, 'PICKUP') !== false || strpos($s, 'POINT') !== false) {
            return 'READY_FOR_PICKUP';
        }
        if (strpos($s, 'OUT_FOR') !== false || strpos($s, 'DELIVERY') !== false) {
            return 'OUT_FOR_DELIVERY';
        }
        if (strpos($s, 'RETURN') !== false) {
            return 'RETURNED';
        }
        if (strpos($s, 'CANCEL') !== false) {
            return 'CANCELLED';
        }
        if (strpos($s, 'FAIL') !== false || strpos($s, 'ERROR') !== false) {
            return 'FAILED';
        }

        return $s !== '' ? $s : 'CREATED';
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

        foreach ([
            $shipment['trackingNumber'] ?? null,
            $shipment['waybill'] ?? null,
            $shipment['waybillNumber'] ?? null,
            $shipment['tracking']['number'] ?? null,
            $shipment['label']['trackingNumber'] ?? null,
            $shipment['summary']['trackingNumber'] ?? null,
        ] as $candidate) {
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

        foreach ([
            $shipment['service']['name'] ?? null,
            $shipment['service']['id'] ?? null,
            $shipment['deliveryMethod']['name'] ?? null,
            $shipment['deliveryMethod']['id'] ?? null,
            $shipment['summary']['name'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && $candidate !== '' && mb_stripos($candidate, 'smart') !== false) {
                return 1;
            }
        }

        return null;
    }

    private function extractCarrierMode(array $shipment): ?string
    {
        $mode = $shipment['carrierMode'] ?? ($shipment['service']['carrierMode'] ?? null);
        if (is_string($mode) && trim($mode) !== '') {
            $mode = strtoupper(trim($mode));
            if (in_array($mode, ['BOX', 'COURIER'], true)) {
                return $mode;
            }
        }

        $name = (string)($shipment['service']['name'] ?? ($shipment['deliveryMethod']['name'] ?? ''));
        $name = mb_strtolower($name);
        if ($name !== '') {
            if (strpos($name, 'paczkomat') !== false || strpos($name, 'one box') !== false || strpos($name, 'punkt') !== false || strpos($name, 'automat') !== false) {
                return 'BOX';
            }
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

    private function normalizeDateTime($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_numeric($value)) {
            $ts = (int)$value;
            if ($ts > 0) {
                return date('Y-m-d H:i:s', $ts);
            }
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
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

        $targetTable = $this->resolveShippingTableName();
        if ($targetTable === null) {
            return;
        }

        try {
            \Db::getInstance()->update($targetTable, $data, "checkout_form_id = '" . pSQL($checkoutFormId) . "'");
        } catch (\Exception $e) {
            // Nie przerywaj renderu BO, jeżeli struktura tabel nie jest jeszcze gotowa.
        }
    }

    /**
     * Zwraca nazwę logiczną tabeli shipping używaną przez moduł (bez prefixu),
     * albo null, gdy nie istnieje żadna kompatybilna tabela.
     */
    private function resolveShippingTableName(): ?string
    {
        static $resolved = null;

        if ($resolved !== null) {
            return $resolved;
        }

        if ($this->tableExists('allegropro_order_shipping')) {
            $resolved = 'allegropro_order_shipping';
            return $resolved;
        }

        // Fallback dla starszych instalacji.
        if ($this->tableExists('allegropro_shipping')) {
            $resolved = 'allegropro_shipping';
            return $resolved;
        }

        $resolved = null;
        return null;
    }

    /**
     * Bezpieczne sprawdzenie istnienia tabeli (z prefixem PS).
     */
    private function tableExists(string $logicalTable): bool
    {
        try {
            $full = _DB_PREFIX_ . $logicalTable;
            $sql = 'SELECT COUNT(*) FROM information_schema.TABLES '
                . 'WHERE TABLE_SCHEMA = DATABASE() '
                . "AND TABLE_NAME = '" . pSQL($full) . "'";

            return (int) \Db::getInstance()->getValue($sql) > 0;
        } catch (\Exception $e) {
            return false;
        }
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
