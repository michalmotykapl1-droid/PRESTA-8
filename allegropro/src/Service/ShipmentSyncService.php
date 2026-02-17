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
            $isSmart = $this->extractIsSmart($detail['json']);
            $carrierMode = $this->extractCarrierMode($detail['json']);
            $sizeDetails = $this->extractSizeDetails($detail['json']);

            $createdAt = $this->normalizeDateTime($detail['json']['createdAt'] ?? null);
            $statusChangedAt = $this->normalizeDateTime($detail['json']['statusChangedAt'] ?? ($detail['json']['updatedAt'] ?? null));

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

        $debug = !empty($debugLines);

        $checkoutShipmentsResp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId) . '/shipments');
        if ($debug) {
            $debugLines[] = '[API] GET /order/checkout-forms/{id}/shipments: HTTP ' . (int)($checkoutShipmentsResp['code'] ?? 0) . ', ok=' . (!empty($checkoutShipmentsResp['ok']) ? '1' : '0');
        }
        if ($checkoutShipmentsResp['ok'] && is_array($checkoutShipmentsResp['json'])) {
            $rows = $this->resolver->extractShipmentRows($checkoutShipmentsResp['json']);

            if ($debug) {
                $debugLines[] = '[SYNC] /checkout-forms/{id}/shipments rows=' . count($rows);
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $refs = $this->resolver->extractCandidateReferencesFromArray($row);
                foreach ($refs as $ref) {
                    $this->captureDiscoveredRowContext($ref, $row);
                    // ważne: w /order/checkout-forms/{id}/shipments "id" bywa base64("CARRIER:WAYBILL")
                    // i nie przechodzi przez extractShipmentIdFromRow(). Dodajemy więc wszystkie referencje jako kandydaty.
                    $found[$ref] = true;
                }

                $sid = $this->resolver->extractShipmentIdFromRow($row);
                $waybill = $this->resolver->extractWaybillFromRow($row);
                $carrierId = $this->resolver->extractCarrierIdFromRow($row);

                // Jeżeli mamy waybill, to twórzmy też kanoniczną referencję (base64("CARRIER:WAYBILL")),
                // żeby moduł mógł rozpoznać np. INPOST po prefiksie oraz trzymać spójne ID.
                if (is_string($waybill) && trim($waybill) !== '') {
                    $carrierId = is_string($carrierId) && trim($carrierId) !== '' ? strtoupper(trim($carrierId)) : 'ALLEGRO';
                    $canonicalRef = base64_encode($carrierId . ':' . trim($waybill));
                    $this->captureDiscoveredRowContext($canonicalRef, $row);
                    $found[$canonicalRef] = true;
                }

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
                } elseif ($waybill !== null) {
                    // Jeśli API zwróciło tylko waybill, też mamy pełną informację do historii i trackingu.
                    // Zapiszemy jako referencję kanoniczną powyżej (base64("CARRIER:WAYBILL")).
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
        // W praktyce endpoint /shipment-management/shipments/{id} potrafi zwracać status "SENT" przez dłuższy czas,
        // podczas gdy /order/carriers/{carrierId}/tracking?waybill=... ma już bardziej szczegółowy status.
        // Żeby w BO statusy były "żywe" (np. W DRODZE / DO ODBIORU), próbujemy trackingu dla wszystkich
        // statusów poza finalnymi.
        $status = strtoupper(trim($status));
        if ($status === '') {
            return true;
        }
        return !in_array($status, ['DELIVERED', 'CANCELLED'], true);
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
                // W /order/checkout-forms/{id}/shipments identyfikator bywa w formacie "CARRIER:WAYBILL".
                // Do trackingu potrzebujemy SAMEGO waybilla.
                if (strpos($decoded, ':') !== false) {
                    $parts = explode(':', $decoded, 2);
                    if (count($parts) === 2) {
                        $wb = trim((string)$parts[1]);
                        if ($wb !== '') {
                            return $wb;
                        }
                    }
                }
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
            if (is_array($event) && !empty($event['status'])) {
                return [
                    'status' => $this->normalizeTrackingStatus((string)$event['status']),
                    'at' => $event['at'] ?? null,
                ];
            }
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
        if ($s === 'PENDING') {
            return 'CREATED';
        }
        if ($s === 'PROCESSING') {
            return 'IN_PROGRESS';
        }
        if ($s === 'DELIVERED' || $s === 'PICKED_UP') {
            return 'DELIVERED';
        }

        // Statusy "w drodze" trzymamy rozdzielnie, żeby UI mogło pokazać progres (NADANA -> W DRODZE -> DO ODBIORU).
        if (in_array($s, ['IN_TRANSIT', 'ON_THE_WAY'], true)) {
            return 'IN_TRANSIT';
        }

        if ($s === 'OUT_FOR_DELIVERY') {
            return 'OUT_FOR_DELIVERY';
        }

        if ($s === 'READY_FOR_PICKUP') {
            return 'READY_FOR_PICKUP';
        }

        // Część integracji zwraca "SHIPPED" zamiast "SENT".
        if ($s === 'SHIPPED') {
            return 'SENT';
        }

        return $s !== '' ? $s : 'CREATED';
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
