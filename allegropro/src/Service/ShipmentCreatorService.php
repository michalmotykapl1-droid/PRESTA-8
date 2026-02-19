<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\ShipmentRepository;
use Configuration;
use Db;
use Exception;

class ShipmentCreatorService
{
    private AllegroApiClient $api;
    private LabelConfig $config;
    private OrderRepository $orders;
    private DeliveryServiceRepository $deliveryServices;
    private ShipmentRepository $shipments;

    public function __construct(
        AllegroApiClient $api,
        LabelConfig $config,
        OrderRepository $orders,
        DeliveryServiceRepository $deliveryServices,
        ShipmentRepository $shipments
    )
    {
        $this->api = $api;
        $this->config = $config;
        $this->orders = $orders;
        $this->deliveryServices = $deliveryServices;
        $this->shipments = $shipments;
    }

    public function createShipment(array $account, string $checkoutFormId, array $params): array
    {
        $debug = !empty($params['debug']);
        $debugLines = [];

        $order = $this->orders->getDecodedOrder((int)$account['id_allegropro_account'], $checkoutFormId);
        if (!$order) {
            return ['ok' => false, 'message' => 'Nie znaleziono zamówienia w bazie.', 'debug_lines' => $debug ? ['[CREATE] Brak zamówienia w bazie. Najpierw pobierz zamówienia z Allegro.'] : []];
        }

        $deliveryMethodId = $order['delivery']['method']['id'] ?? null;
        $hasPickupPoint = !empty($order['delivery']['pickupPoint']['id']);
        $accountId = (int)($account['id_allegropro_account'] ?? 0);

        // Zawsze pobieramy checkout-form (to jest źródło prawdy) – przyda się też do limitów SMART.
        $cfJson = null;
        try {
            $cfResp = $this->api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId));
            if (!empty($cfResp['ok']) && is_array($cfResp['json'])) {
                $cfJson = $cfResp['json'];
                $liveMethodId = $cfJson['delivery']['method']['id'] ?? null;
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

        if ($debug) {
            $debugLines[] = '[CREATE] start checkoutFormId=' . $checkoutFormId . ', accountId=' . $accountId;
            $debugLines[] = '[CREATE] delivery.method.id=' . (string)$deliveryMethodId;
            $debugLines[] = '[CREATE] delivery.method.name=' . (string)($order['delivery']['method']['name'] ?? ($cfJson['delivery']['method']['name'] ?? ''));
        }

        // Dociągnij ustawienia delivery-service (credentialsId / additionalProperties)
        $service = null;
        if (is_string($deliveryMethodId) && $deliveryMethodId !== '') {
            $service = $this->deliveryServices->findByDeliveryMethod($accountId, (string)$deliveryMethodId);
            if (!$service) {
                // Fallback: automatycznie odśwież delivery-services, jeśli nie ma mapowania.
                if ($debug) {
                    $debugLines[] = '[CREATE] Brak delivery-service w bazie dla deliveryMethodId. Próbuję auto-refresh /shipment-management/delivery-services...';
                }
                $before = $this->deliveryServices->countForAccount($accountId);
                $refreshInfo = $this->refreshDeliveryServices($account);
                $after = $this->deliveryServices->countForAccount($accountId);
                if ($debug) {
                    $debugLines[] = '[CREATE] delivery-services refresh: HTTP ' . (int)($refreshInfo['code'] ?? 0)
                        . ', ok=' . (!empty($refreshInfo['ok']) ? '1' : '0')
                        . ', records: ' . $before . ' → ' . $after
                        . (!empty($refreshInfo['shape']) ? (', shape=' . $refreshInfo['shape']) : '');
                }
                $service = $this->deliveryServices->findByDeliveryMethod($accountId, (string)$deliveryMethodId);
            }
        }

        // Jeśli nie znaleźliśmy mapowania lub brakuje credentials_id (szczególnie dla InPost/Paczkomatów),
        // spróbuj pobrać delivery-services na żywo z API i wybrać właściwy wariant dla tej metody.
        // Jeżeli deliveryMethodId nie znajduje się w delivery-services (albo brakuje credentials),
        // spróbuj rozwiązać mapowanie na żywo: po ID, a jeśli nie ma, po nazwie metody / właściwościach InPost.
        $methodName = (string)($order['delivery']['method']['name'] ?? ($cfJson['delivery']['method']['name'] ?? ''));
        if (is_string($deliveryMethodId) && $deliveryMethodId !== '') {
            if (!$service || empty($service['credentials_id'])) {
                $liveService = $this->resolveDeliveryServiceFromApi($account, (string)$deliveryMethodId, $methodName, $hasPickupPoint, $debug, $debugLines);
                if (is_array($liveService)) {
                    $service = $liveService;
                }
            }
        }

        // Jeśli resolveDeliveryServiceFromApi dobrało inną wartość deliveryMethodId (np. dopasowanie po nazwie),
        // przełączamy na resolved ID, bo to jest ID wymagane przez /shipment-management/shipments/create-commands.
        if (is_array($service) && !empty($service['delivery_method_id'])) {
            $resolvedId = (string)$service['delivery_method_id'];
            if ($resolvedId !== '' && $resolvedId !== (string)$deliveryMethodId) {
                if ($debug) {
                    $debugLines[] = '[CREATE] deliveryMethodId override: ' . (string)$deliveryMethodId . ' → ' . $resolvedId;
                }
                $deliveryMethodId = $resolvedId;
            }
        }


        $credentialsId = null;
        $additionalProps = null;
        if (is_array($service)) {
            $credentialsId = !empty($service['credentials_id']) ? (string)$service['credentials_id'] : null;
            if (!empty($service['additional_properties_json'])) {
                $decoded = json_decode((string)$service['additional_properties_json'], true);
                if (is_array($decoded) && !empty($decoded)) {
                    $additionalProps = $decoded;
                }
            }
        }

        if ($debug) {
            $debugLines[] = '[CREATE] delivery-service: ' . ($service ? 'OK' : 'BRAK') . (
                $service ? (' (owner=' . (string)($service['owner'] ?? '-') . ', carrier_id=' . (string)($service['carrier_id'] ?? '-') . ')') : ''
            );
            $debugLines[] = '[CREATE] credentialsId: ' . ($credentialsId ? $credentialsId : '(brak)');
            if ($additionalProps) {
                $debugLines[] = '[CREATE] additionalProperties (z delivery-services): ' . json_encode($additionalProps, JSON_UNESCAPED_UNICODE);
            }
        }

        try {
            if ($debug && is_array($cfJson)) {
                $smartData = $this->extractSmartDataFromCheckoutForm($cfJson);
                $packageLimit = $smartData['package_count'] ?? null;
                if (is_int($packageLimit) && $packageLimit > 0) {
                    $activeCount = method_exists($this->shipments, 'countActiveShipmentsForOrder')
                        ? (int)$this->shipments->countActiveShipmentsForOrder($accountId, $checkoutFormId)
                        : (int)count($this->shipments->findAllByOrderForAccount($accountId, $checkoutFormId));

                    $debugLines[] = '[CREATE] checkout-form package limit=' . $packageLimit . ', active(local)=' . $activeCount . ' (informacyjnie, bez blokady tworzenia)';
                }
            }
        } catch (Exception $e) {
        }

        $pkgDims = $this->resolvePackageDimensions($params);

        // Fallback: jeśli nie znaleźliśmy mapowania dla deliveryMethodId, to dla debug pokaż statystyki INPOST.
        $inpostStats = $debug ? $this->deliveryServices->getCarrierStats($accountId, 'INPOST') : null;
        $inpostCandidate = null;
        if (($debug || $hasPickupPoint) && is_array($inpostStats) && (int)($inpostStats['with_credentials'] ?? 0) > 0) {
            $inpostCandidate = $this->deliveryServices->findFirstWithCredentialsByCarrier($accountId, 'INPOST');
        }
        if ($debug && is_array($inpostStats)) {
            $debugLines[] = '[CREATE] INPOST delivery-services in DB: total=' . (int)$inpostStats['total'] . ', with_credentials=' . (int)$inpostStats['with_credentials'];
            if (is_array($inpostCandidate)) {
                $debugLines[] = '[CREATE] INPOST candidate: delivery_method_id=' . (string)($inpostCandidate['delivery_method_id'] ?? '-') . ', credentials_id=' . (string)($inpostCandidate['credentials_id'] ?? '-') . ', owner=' . (string)($inpostCandidate['owner'] ?? '-');
            }
        }

        try {
            $payloadBase = $this->buildPayload($deliveryMethodId, $order, $pkgDims);
            // Jeśli mamy mapowanie – używamy credentialsId z dopasowanej usługi.
            if ($credentialsId) {
                $payloadBase['credentialsId'] = $credentialsId;
            }
            // Dodatkowe properties z delivery-services (np. inpost#sendingMethod, jeśli Allegro to zwraca dla tej usługi)
            $payloadBase = $this->applyInpostSendingMethod($payloadBase, $order, $additionalProps, $methodName, $debug, $debugLines);
        } catch (Exception $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'debug_lines' => $debug ? $debugLines : []];
        }

        // Tworzenie przesyłki: wykonujemy maks. 2 próby (np. dołożenie credentialsId dla InPost).
        $attempt = 0;
        $maxAttempts = 2;
        $shipmentId = null;
        $finalStatus = 'IN_PROGRESS';

        while ($attempt < $maxAttempts) {
            $payload = $payloadBase;

            if ($debug) {
                $debugLines[] = '[API] POST /shipment-management/shipments/create-commands (attempt ' . ($attempt + 1) . '/' . $maxAttempts . ')';
                $debugLines[] = '[API] payload.input=' . json_encode($payload, JSON_UNESCAPED_UNICODE);
            }

            $resp = $this->api->postJson($account, '/shipment-management/shipments/create-commands', ['input' => $payload]);
            if (!$resp['ok']) {
                $err = $resp['json']['errors'][0]['message'] ?? ('Kod HTTP: ' . $resp['code']);
                if (isset($resp['json']['errors'][0]['details'])) {
                    $err .= ' (' . $resp['json']['errors'][0]['details'] . ')';
                }
                if (isset($resp['json']['errors'][0]['path'])) {
                    $err .= ' [Pole: ' . $resp['json']['errors'][0]['path'] . ']';
                }
                if ($debug) {
                    $debugLines[] = '[API] HTTP ' . (int)$resp['code'] . ' ok=0';
                    $debugLines[] = '[API] response=' . (is_string($resp['raw'] ?? null) ? (string)$resp['raw'] : json_encode($resp['json'], JSON_UNESCAPED_UNICODE));
                    $debugLines = array_merge($debugLines, $this->troubleshootHints($err, $service));
                }
                return ['ok' => false, 'message' => 'Błąd Allegro: ' . $err, 'debug_lines' => $debug ? $debugLines : []];
            }

            $cmdId = $resp['json']['id'] ?? ($resp['json']['commandId'] ?? null);
            if (empty($cmdId)) {
                return ['ok' => false, 'message' => 'Allegro nie zwróciło ID komendy tworzenia przesyłki.'];
            }

            $shipmentId = null;
            $finalStatus = 'IN_PROGRESS';

            for ($i = 0; $i < 10; $i++) {
                usleep(1000000);

                $statusResp = $this->api->get($account, '/shipment-management/shipments/create-commands/' . $cmdId);
                if (!$statusResp['ok']) {
                    continue;
                }

                $status = $statusResp['json']['status'] ?? 'IN_PROGRESS';
                if ($status === 'SUCCESS' && !empty($statusResp['json']['shipmentId'])) {
                    $shipmentId = $statusResp['json']['shipmentId'];
                    $finalStatus = 'CREATED';
                    break 2; // wyjście z pętli poll i attempt
                }

                if ($status === 'ERROR') {
                    $errObj = $statusResp['json']['errors'][0] ?? [];
                    $errCode = (string)($errObj['code'] ?? '');
                    $errMsg = (string)($errObj['message'] ?? 'Błąd tworzenia');
                    $errDetails = (string)($errObj['details'] ?? '');
                    $errPath = (string)($errObj['path'] ?? '');

                    if ($debug) {
                        $debugLines[] = '[API] GET /shipment-management/shipments/create-commands/{commandId}: status=ERROR';
                        $debugLines[] = '[API] error=' . $errMsg;
                        $debugLines[] = '[API] response=' . json_encode($statusResp['json'], JSON_UNESCAPED_UNICODE);
                    }

                    // AUTOMATYCZNA NAPRAWA: InPost często wymaga credentialsId + inpost#sendingMethod.
                    $isMissingInpostCred = ($errCode === 'MISSING_INPOST_CREDENTIALS')
                        || (stripos($errMsg, 'inpost') !== false && stripos($errMsg, 'credentials') !== false)
                        || (stripos($errDetails, 'shipx') !== false);

                    $isCredentialsNotAllowed = (stripos($errMsg, 'Credentials ID is not allowed') !== false)
                        || (stripos($errMsg, 'credentials id is not allowed') !== false)
                        || (stripos($errDetails, 'credentials id is not allowed') !== false)
                        || ($errPath === 'credentialsId');

                    if ($attempt + 1 < $maxAttempts && $isMissingInpostCred) {
                        $fallbackCred = is_array($inpostCandidate) ? (string)($inpostCandidate['credentials_id'] ?? '') : '';
                        // Ustawiamy sendingMethod „na sztywno” (w Allegro to wymagane dla INPOST).
                        $payloadBase['additionalProperties'] = $payloadBase['additionalProperties'] ?? [];
                        if (!is_array($payloadBase['additionalProperties'])) {
                            $payloadBase['additionalProperties'] = [];
                        }
                        // any_point działa zarówno dla Paczkomatu jak i PaczkoPunktu.
                        $payloadBase['additionalProperties']['inpost#sendingMethod'] = $hasPickupPoint ? 'any_point' : 'dispatch_order';

                        if ($fallbackCred !== '') {
                            $payloadBase['credentialsId'] = $fallbackCred;
                            if ($debug) {
                                $debugLines[] = '[RETRY] Wykryto brak poświadczeń InPost. Dokładam credentialsId=' . $fallbackCred . ' oraz inpost#sendingMethod=' . ($hasPickupPoint ? 'any_point' : 'dispatch_order') . ' i ponawiam.';
                            }
                        } else {
                            unset($payloadBase['credentialsId']);
                            if ($debug) {
                                $debugLines[] = '[RETRY] Wykryto błąd InPost/ShipX. Nie mam credentialsId w DB, ale dokładam inpost#sendingMethod=' . ($hasPickupPoint ? 'any_point' : 'dispatch_order') . ' i ponawiam (jeśli nadal błąd, to brakuje tokenu ShipX w Allegro).';
                        $hasSavedShipx = !empty($account['shipx_token']);
                        if ($debug) {
                            $debugLines[] = '[INFO] ShipX token zapisany w module dla tego konta: ' . ($hasSavedShipx ? 'TAK' : 'NIE');
                            if ($hasSavedShipx) {
                                $debugLines[] = '[INFO] Jeśli Allegro zgłasza brak poświadczeń, skopiuj ten token (Ustawienia modułu → InPost ShipX) i wklej w Allegro Sales Center → Wysyłam z Allegro → Integracja z InPost.';
                            }
                        }

                            }
                        }

                        $attempt++;
                        continue 2; // następna próba
                    }

                    if ($attempt + 1 < $maxAttempts && $isCredentialsNotAllowed) {
                        unset($payloadBase['credentialsId']);
                        if ($debug) {
                            $debugLines[] = '[RETRY] API odrzuciło credentialsId dla tej metody. Usuwam credentialsId i ponawiam.';
                        }
                        $attempt++;
                        continue 2;
                    }

                    if ($debug) {
                        $debugLines = array_merge($debugLines, $this->troubleshootHints($errMsg, $service));
                    }
                    return ['ok' => false, 'message' => 'Błąd Allegro (Async): ' . $errMsg, 'debug_lines' => $debug ? $debugLines : []];
                }
            }

            // Jeśli poll się nie skończył sukcesem ani błędem (timeout), wychodzimy.
            break;
        }

        $dbData = [
            'status' => $finalStatus == 'CREATED' ? 'CREATED' : 'NEW',
            'shipmentId' => $shipmentId,
            'is_smart' => !empty($params['smart']) ? 1 : 0,
            'size_type' => $params['size_code'] ?? 'CUSTOM'
        ];
        $this->shipments->upsert((int)$account['id_allegropro_account'], $checkoutFormId, $cmdId, $dbData);

        if ($shipmentId) {
            $this->orders->markShipment((int)$account['id_allegropro_account'], $checkoutFormId, $shipmentId, $cmdId);

            $tracking2 = null;
            try {
                $detailJson = null;
                for ($i = 0; $i < 6; $i++) {
                    $detail = $this->api->get($account, '/shipment-management/shipments/' . rawurlencode($shipmentId));
                    if (!empty($detail['ok']) && is_array($detail['json'])) {
                        $detailJson = $detail['json'];
                        $tracking2 = $this->extractTrackingNumber($detailJson);
                        if (is_string($tracking2) && trim($tracking2) !== '') {
                            $tracking2 = trim($tracking2);
                            break;
                        }
                    }
                    usleep(400000);
                }

                if (is_array($detailJson)) {
                    $status2 = (string)($detailJson['status'] ?? 'CREATED');
                    $isSmart2 = $this->extractIsSmart($detailJson);
                    $carrierMode2 = $this->extractCarrierMode($detailJson);
                    $sizeDetails2 = $this->extractSizeDetails($detailJson);

                    if (method_exists($this->shipments, 'upsertFromAllegro')) {
                                                $createdAt2 = $this->normalizeDateTime($detailJson['createdAt'] ?? null);
                        $statusChangedAt2 = $this->normalizeDateTime($detailJson['statusChangedAt'] ?? ($detailJson['updatedAt'] ?? null))
                            ?: $createdAt2;

                        $this->shipments->upsertFromAllegro(
                            (int)$account['id_allegropro_account'],
                            $checkoutFormId,
                            $shipmentId,
                            $status2,
                            $tracking2,
                            $isSmart2,
                            $carrierMode2,
                            $sizeDetails2,
                            $createdAt2,
                            $statusChangedAt2
                        );
                    } elseif (is_string($tracking2) && $tracking2 !== '') {
                        Db::getInstance()->update(
                            'allegropro_shipment',
                            [
                                'tracking_number' => pSQL($tracking2),
                                'updated_at' => pSQL(date('Y-m-d H:i:s')),
                            ],
                            'id_allegropro_account='.(int)$account['id_allegropro_account']
                                ." AND checkout_form_id='".pSQL($checkoutFormId)."'"
                                ." AND shipment_id='".pSQL($shipmentId)."'"
                        );
                    }
                }
            } catch (Exception $e) {
            }

            if (is_string($tracking2) && trim($tracking2) !== '' && method_exists($this->shipments, 'backfillWzaForTrackingNumber')) {
                $this->shipments->backfillWzaForTrackingNumber(
                    (int)$account['id_allegropro_account'],
                    $checkoutFormId,
                    trim($tracking2),
                    $cmdId,
                    $shipmentId
                );
            }
            if (method_exists($this->shipments, 'mergeWzaFieldsForOrder')) {
                $this->shipments->mergeWzaFieldsForOrder((int)$account['id_allegropro_account'], $checkoutFormId);
            }

            return ['ok' => true, 'shipmentId' => $shipmentId, 'debug_lines' => $debug ? $debugLines : []];
        }

        return ['ok' => true, 'message' => 'Przesyłka w trakcie przetwarzania (Command ID: '.$cmdId.')', 'debug_lines' => $debug ? $debugLines : []];
    }

    private function refreshDeliveryServices(array $account): array
    {
        try {
            $resp = $this->api->get($account, '/shipment-management/delivery-services', ['limit' => 500]);
            if (empty($resp['ok']) || !is_array($resp['json'])) {
                return $resp;
            }
            // Allegro w dokumentacji pokazuje klucz "services" (nie "deliveryServices").
            $services = null;
            $shape = '';
            if (isset($resp['json']['services'])) {
                $services = $resp['json']['services'];
                $shape = 'services';
            } elseif (isset($resp['json']['deliveryServices'])) {
                $services = $resp['json']['deliveryServices'];
                $shape = 'deliveryServices';
            } else {
                // awaryjnie: jeśli API zwróciło listę bez obudowy
                $services = $resp['json'];
                $shape = 'raw';
            }
            if (!is_array($services)) {
                $resp['shape'] = $shape;
                return $resp;
            }
            foreach ($services as $s) {
                if (is_array($s)) {
                    $this->deliveryServices->upsert((int)($account['id_allegropro_account'] ?? 0), $s);
                }
            }
            $resp['shape'] = $shape;
            return $resp;
        } catch (Exception $e) {
            return ['ok' => false, 'code' => 0, 'raw' => (string)$e->getMessage(), 'json' => null];
        }
    }


    /**
     * Pobiera delivery-services bezpośrednio z API i wybiera najlepszy wariant dla podanego deliveryMethodId.
     * To jest krytyczne dla InPost/Paczkomatów, gdzie Allegro często wymaga jawnego credentialsId.
     *
     * Zwraca "db-like" tablicę zgodną z findByDeliveryMethod(): credentials_id, additional_properties_json, carrier_id, owner, name...
     */
    private function resolveDeliveryServiceFromApi(array $account, string $deliveryMethodId, string $methodName, bool $hasPickupPoint, bool $debug, array &$debugLines): ?array
    {
        $accountId = (int)($account['id_allegropro_account'] ?? 0);

        $lc = function (string $s): string {
            return function_exists('mb_strtolower') ? (string)mb_strtolower($s) : strtolower($s);
        };

        try {
            $resp = $this->api->get($account, '/shipment-management/delivery-services', ['limit' => 500]);
            if (empty($resp['ok']) || !is_array($resp['json'])) {
                if ($debug) {
                    $debugLines[] = '[CREATE] delivery-services (live): nie udało się pobrać z API (HTTP ' . (int)($resp['code'] ?? 0) . ').';
                }
                return null;
            }

            $services = null;
            $shape = '';
            if (isset($resp['json']['services'])) {
                $services = $resp['json']['services'];
                $shape = 'services';
            } elseif (isset($resp['json']['deliveryServices'])) {
                $services = $resp['json']['deliveryServices'];
                $shape = 'deliveryServices';
            } else {
                $services = $resp['json'];
                $shape = 'raw';
            }

            if (!is_array($services)) {
                if ($debug) {
                    $debugLines[] = '[CREATE] delivery-services (live): nieoczekiwany kształt odpowiedzi (' . $shape . ').';
                }
                return null;
            }

            $wantName = trim($lc((string)$methodName));
            $wantInpost = ($wantName !== '' && strpos($wantName, 'inpost') !== false);
            $wantPacz = ($wantName !== '' && (strpos($wantName, 'paczkom') !== false || strpos($wantName, 'paczko') !== false));

            $matchesById = [];
            $matchesByName = [];
            $inpostCandidates = [];

            foreach ($services as $s) {
                if (!is_array($s)) {
                    continue;
                }

                $idObj = $s['id'] ?? null;

                $dmId = '';
                if (isset($s['deliveryMethodId'])) {
                    $dmId = (string)$s['deliveryMethodId'];
                } elseif (is_array($idObj) && isset($idObj['deliveryMethodId'])) {
                    $dmId = (string)$idObj['deliveryMethodId'];
                } elseif (is_array($s['deliveryMethod'] ?? null) && isset($s['deliveryMethod']['id'])) {
                    $dmId = (string)$s['deliveryMethod']['id'];
                } elseif (is_array($idObj) && is_array($idObj['deliveryMethod'] ?? null) && isset($idObj['deliveryMethod']['id'])) {
                    $dmId = (string)$idObj['deliveryMethod']['id'];
                }

                // credentials
                $credentialsId = null;
                if (isset($s['credentialsId'])) {
                    $credentialsId = (string)$s['credentialsId'];
                } elseif (is_array($idObj) && isset($idObj['credentialsId'])) {
                    $credentialsId = (string)$idObj['credentialsId'];
                } elseif (is_array($s['credentials'] ?? null) && isset($s['credentials']['id'])) {
                    $credentialsId = (string)$s['credentials']['id'];
                }
                $credentialsId = ($credentialsId && strtolower($credentialsId) !== 'null') ? $credentialsId : null;

                // additionalProperties
                $add = null;
                if (isset($s['additionalProperties']) && is_array($s['additionalProperties'])) {
                    $add = $s['additionalProperties'];
                } elseif (isset($s['additional_properties']) && is_array($s['additional_properties'])) {
                    $add = $s['additional_properties'];
                }

                $sending = null;
                if (is_array($add) && array_key_exists('inpost#sendingMethod', $add)) {
                    $sm = $add['inpost#sendingMethod'];
                    if (is_string($sm) && $sm !== '') {
                        $sending = $sm;
                    } elseif (is_array($sm) && !empty($sm)) {
                        $sending = (string)reset($sm);
                    }
                }

                $name = isset($s['name']) ? (string)$s['name'] : '';
                $nameLc = trim($lc($name));
                $hasInpostProp = is_array($add) && array_key_exists('inpost#sendingMethod', $add);
                $isInpostName = ($nameLc !== '' && strpos($nameLc, 'inpost') !== false);
                $isPaczName = ($nameLc !== '' && (strpos($nameLc, 'paczkom') !== false || strpos($nameLc, 'paczko') !== false));

                $entry = [
                    'raw' => $s,
                    'dmId' => $dmId,
                    'credentialsId' => $credentialsId,
                    'additional' => $add,
                    'sending' => $sending,
                    'carrierId' => isset($s['carrierId']) ? (string)$s['carrierId'] : null,
                    'owner' => isset($s['owner']) ? (string)$s['owner'] : null,
                    'name' => $name,
                    'reason' => 'unknown',
                ];

                if ($dmId !== '' && $dmId === $deliveryMethodId) {
                    $entry['reason'] = 'by-id';
                    $matchesById[] = $entry;
                    continue;
                }

                // Dopasowanie po nazwie (gdy deliveryMethodId z checkout-form nie występuje w delivery-services)
                if ($wantInpost && $isInpostName) {
                    $nameOk = true;
                    if ($wantPacz) {
                        $nameOk = $isPaczName;
                    } elseif ($hasPickupPoint) {
                        $nameOk = ($isPaczName || strpos($nameLc, 'punkt') !== false);
                    }
                    if ($nameOk) {
                        $entry['reason'] = (strpos($nameLc, $wantName) !== false || strpos($wantName, $nameLc) !== false) ? 'by-name-strong' : 'by-name';
                        $matchesByName[] = $entry;
                    }
                }

                // Fallback: InPost rozpoznajemy też po additionalProperties.inpost#sendingMethod
                if ($hasInpostProp || $isInpostName) {
                    $entry['reason'] = $hasInpostProp ? 'by-inpost-prop' : 'by-inpost-name';
                    $inpostCandidates[] = $entry;
                }
            }

            if ($debug) {
                $debugLines[] = '[CREATE] delivery-services (live): shape=' . $shape
                    . ', matches_by_id=' . count($matchesById)
                    . ', matches_by_name=' . count($matchesByName)
                    . ', inpost_candidates=' . count($inpostCandidates)
                    . ' (wanted deliveryMethodId=' . $deliveryMethodId . ', methodName=' . $methodName . ')';
            }

            // wybór kandydatów: najpierw dokładne ID; dla InPost preferuj kandydatów INPOST (props/nazwa) zanim dopasujesz po nazwie
            $candidates = [];
            if (!empty($matchesById)) {
                $candidates = $matchesById;
            } else {
                // Dla Paczkomatów/InPost najpewniejsze są rekordy z carrierId=INPOST lub z additionalProperties.inpost#sendingMethod.
                if (($wantInpost || $hasPickupPoint) && !empty($inpostCandidates)) {
                    $candidates = $inpostCandidates;

                    // Preferuj rekordy, gdzie carrierId=INPOST (najbardziej wiarygodne dla InPost).
                    $onlyInpostCarrier = [];
                    foreach ($candidates as $m) {
                        $cid = strtoupper((string)($m['carrierId'] ?? ''));
                        if ($cid === 'INPOST') {
                            $onlyInpostCarrier[] = $m;
                        }
                    }
                    if (!empty($onlyInpostCarrier)) {
                        $candidates = $onlyInpostCarrier;
                    }

                    // Jeśli to punkt/paczkomat, ogranicz do usług "punktowych" lub takich, które wspierają sendingMethod dla punktu.
                    if ($hasPickupPoint) {
                        $filtered = [];
                        foreach ($candidates as $m) {
                            $n = trim($lc((string)($m['name'] ?? '')));
                            $isPointy = ($n !== '' && (strpos($n, 'paczkom') !== false || strpos($n, 'paczko') !== false || strpos($n, 'punkt') !== false));
                            $smOk = (!empty($m['sending']) && in_array($m['sending'], ['any_point', 'parcel_locker', 'pop'], true));
                            if ($isPointy || $smOk) {
                                $filtered[] = $m;
                            }
                        }
                        if (!empty($filtered)) {
                            $candidates = $filtered;
                        }
                    }
                } elseif (!empty($matchesByName)) {
                    $candidates = $matchesByName;
                }
            }

            if (empty($candidates)) {
                if ($debug) {
                    $sample = [];
                    $i = 0;
                    foreach ($services as $s) {
                        if (!is_array($s)) {
                            continue;
                        }
                        $nm = isset($s['name']) ? (string)$s['name'] : '';
                        if ($nm === '') {
                            continue;
                        }
                        if (stripos($nm, 'inpost') !== false || stripos($nm, 'paczko') !== false || stripos($nm, 'paczkom') !== false) {
                            $idObj = $s['id'] ?? null;
                            $dm = '';
                            if (isset($s['deliveryMethodId'])) {
                                $dm = (string)$s['deliveryMethodId'];
                            } elseif (is_array($idObj) && isset($idObj['deliveryMethodId'])) {
                                $dm = (string)$idObj['deliveryMethodId'];
                            } elseif (is_array($s['deliveryMethod'] ?? null) && isset($s['deliveryMethod']['id'])) {
                                $dm = (string)$s['deliveryMethod']['id'];
                            }
                            $sample[] = '{dmId=' . $dm . ', name=' . $nm . '}';
                            $i++;
                            if ($i >= 5) {
                                break;
                            }
                        }
                    }
                    if (!empty($sample)) {
                        $debugLines[] = '[CREATE] delivery-services sample (InPost-like): ' . implode(' | ', $sample);
                    } else {
                        $debugLines[] = '[CREATE] delivery-services sample: brak pozycji zawierających "InPost/Paczkom" – API może nie zwracać tej metody dla tego konta.';
                    }
                }
                return null;
            }

            // Wybór najlepszego kandydata (score): preferuj INPOST + umowa własna (owner=CLIENT) + sendingMethod zgodny z typem + credentialsId.
            $preferred = $hasPickupPoint ? ['any_point', 'parcel_locker', 'pop'] : ['dispatch_order'];

            $scored = [];
            foreach ($candidates as $m) {
                // Bez deliveryMethodId nie da się utworzyć przesyłki.
                if (empty($m['dmId'])) {
                    continue;
                }
                $score = 0;

                $carrierId = (string)($m['carrierId'] ?? '');
                $owner = (string)($m['owner'] ?? '');
                $nameLc2 = trim($lc((string)($m['name'] ?? '')));

                if ($carrierId === 'INPOST') {
                    $score += 200;
                }
                if (is_array($m['additional']) && array_key_exists('inpost#sendingMethod', $m['additional'])) {
                    $score += 20;
                }
                if (!empty($m['sending']) && in_array($m['sending'], $preferred, true)) {
                    $score += 10;
                }

                // Umowa własna (Separate Agreement) zwykle ma owner=CLIENT.
                if ($owner === 'CLIENT') {
                    $score += 80;
                } elseif ($owner === 'ALLEGRO' && ($wantInpost || $hasPickupPoint)) {
                    // Dla InPost/Paczkomatów owner=ALLEGRO często oznacza Allegro Standard – to niemal pewny konflikt umowy.
                    $score -= 200;
                }

                if (!empty($m['credentialsId'])) {
                    $score += 10;
                }

                if ($wantName !== '' && $nameLc2 !== '') {
                    if (strpos($nameLc2, $wantName) !== false || strpos($wantName, $nameLc2) !== false) {
                        $score += 8;
                    }
                    if ($wantPacz && (strpos($nameLc2, 'paczkom') !== false || strpos($nameLc2, 'paczko') !== false || strpos($nameLc2, 'punkt') !== false)) {
                        $score += 5;
                    }
                }

                $m['score'] = $score;
                $scored[] = $m;
            }

            usort($scored, function ($a, $b) {
                $sa = (int)($a['score'] ?? 0);
                $sb = (int)($b['score'] ?? 0);
                if ($sa === $sb) {
                    $ta = (!empty($a['credentialsId']) ? 1 : 0)
                        + (((string)($a['owner'] ?? '') === 'CLIENT') ? 1 : 0)
                        + (((string)($a['carrierId'] ?? '') === 'INPOST') ? 1 : 0);
                    $tb = (!empty($b['credentialsId']) ? 1 : 0)
                        + (((string)($b['owner'] ?? '') === 'CLIENT') ? 1 : 0)
                        + (((string)($b['carrierId'] ?? '') === 'INPOST') ? 1 : 0);
                    return $tb <=> $ta;
                }
                return $sb <=> $sa;
            });

            $selected = $scored[0];

            if ($debug) {
                $dbg = [];
                $i = 0;
                foreach ($scored as $m) {
                    $dbg[] = '{score=' . (int)($m['score'] ?? 0)
                        . ', dmId=' . (string)($m['dmId'] ?? '-')
                        . ', carrierId=' . (string)($m['carrierId'] ?? '-')
                        . ', owner=' . (string)($m['owner'] ?? '-')
                        . ', cred=' . (!empty($m['credentialsId']) ? (string)$m['credentialsId'] : '(brak)')
                        . ', sending=' . (!empty($m['sending']) ? (string)$m['sending'] : '(brak)')
                        . ', name=' . (string)($m['name'] ?? '') . '}';
                    $i++;
                    if ($i >= 5) {
                        break;
                    }
                }
                $debugLines[] = '[CREATE] delivery-service candidates top5: ' . implode(' | ', $dbg);
                $debugLines[] = '[CREATE] delivery-service (live) picked score=' . (int)($selected['score'] ?? 0);
            }

// Zapisz do DB dla przyszłych wywołań (upsert jest idempotentny).
            if ($accountId > 0 && is_array($selected['raw'])) {
                $this->deliveryServices->upsert($accountId, $selected['raw']);
            }

            $dbRow = [
                'id_allegropro_account' => $accountId,
                'delivery_method_id' => (string)($selected['dmId'] ?: $deliveryMethodId),
                'credentials_id' => $selected['credentialsId'],
                'name' => $selected['name'],
                'carrier_id' => $selected['carrierId'],
                'owner' => $selected['owner'],
                'additional_properties_json' => is_array($selected['additional']) ? json_encode($selected['additional'], JSON_UNESCAPED_UNICODE) : null,
            ];

            if ($debug) {
                $debugLines[] = '[CREATE] delivery-service (live) selected: reason=' . (string)($selected['reason'] ?? '-')
                    . ', dmId=' . (string)($dbRow['delivery_method_id'] ?? '-')
                    . ', carrier_id=' . (string)($dbRow['carrier_id'] ?? '-')
                    . ', credentialsId=' . (string)($dbRow['credentials_id'] ?? '(brak)')
                    . ', owner=' . (string)($dbRow['owner'] ?? '-');
            }

            return $dbRow;
        } catch (Exception $e) {
            if ($debug) {
                $debugLines[] = '[CREATE] delivery-services (live) exception: ' . $e->getMessage();
            }
            return null;
        }
    }

    /**
     * InPost od 2024/2025 wymaga jawnego wskazania metody nadania.
     * Do końca lutego 2026 wspierane jest additionalProperties.inpost#sendingMethod.
     */
    private function applyInpostSendingMethod(array $payload, array $order, ?array $additionalProps, string $methodName, bool $debug, array &$debugLines): array
    {
        $hasPickup = !empty($order['delivery']['pickupPoint']['id']);

        // Jeżeli delivery-services zwróciło klucz inpost#sendingMethod (często jako lista), wybierz sensowną wartość.
        if (is_array($additionalProps) && array_key_exists('inpost#sendingMethod', $additionalProps)) {
            $supported = $additionalProps['inpost#sendingMethod'];
            $chosen = null;

            if (is_array($supported)) {
                // Preferencje: paczkomat -> parcel_locker / any_point, kurier -> dispatch_order
                $pref = $hasPickup ? ['parcel_locker', 'any_point', 'pop'] : ['dispatch_order'];
                foreach ($pref as $p) {
                    if (in_array($p, $supported, true)) {
                        $chosen = $p;
                        break;
                    }
                }
                if (!$chosen && !empty($supported)) {
                    $chosen = (string)reset($supported);
                }
            } elseif (is_string($supported) && $supported !== '') {
                $chosen = $supported;
            }

            if ($chosen) {
                $payload['additionalProperties'] = $payload['additionalProperties'] ?? [];
                if (!is_array($payload['additionalProperties'])) {
                    $payload['additionalProperties'] = [];
                }
                $payload['additionalProperties']['inpost#sendingMethod'] = $chosen;

                if ($debug) {
                    $debugLines[] = '[CREATE] InPost: ustawiono additionalProperties.inpost#sendingMethod=' . $chosen;
                }
            }
        }


        // Jeśli API nie zwróciło listy supported sendingMethod, a to jest InPost (po nazwie metody),
        // ustaw domyślnie, bo bez tego WZA potrafi zwrócić błędy (np. ShipX).
        $mn = function_exists('mb_strtolower') ? (string)mb_strtolower($methodName) : strtolower($methodName);
        $isInpost = ($mn !== '' && strpos($mn, 'inpost') !== false);

        if ($isInpost) {
            $payload['additionalProperties'] = $payload['additionalProperties'] ?? [];
            if (!is_array($payload['additionalProperties'])) {
                $payload['additionalProperties'] = [];
            }
            if (!array_key_exists('inpost#sendingMethod', $payload['additionalProperties'])) {
                $payload['additionalProperties']['inpost#sendingMethod'] = $hasPickup ? 'any_point' : 'dispatch_order';
                if ($debug) {
                    $debugLines[] = '[CREATE] InPost: brak listy supported – ustawiono domyślnie additionalProperties.inpost#sendingMethod=' . $payload['additionalProperties']['inpost#sendingMethod'];
                }
            }
        }

        return $payload;
    }

    private function troubleshootHints(string $errMsg, ?array $service): array
    {
        $hints = [];
        $msgLower = mb_strtolower($errMsg);

        // Najczęstszy problem: brak credentialsId dla metod z umową własną (często InPost).
        if (strpos($msgLower, 'no inpost credentials') !== false || strpos($msgLower, 'credentials') !== false) {
            $hints[] = '';
            $hints[] = '[HINT] Ten błąd zwykle oznacza brak poprawnie dodanej integracji/poświadczeń do Wysyłam z Allegro dla InPost.';
            $hints[] = '[HINT] 1) Allegro Sales Center → Wysyłam z Allegro → Integracja z InPost → dodaj token ShipX (InPost) i zapisz.';
            $hints[] = '[HINT] 2) W module AllegroPro: wejdź w „Przesyłki” i kliknij „Odśwież delivery services” dla tego konta.';
            $hints[] = '[HINT] 3) Upewnij się, że dla tej metody dostawy w tabeli dxna_allegropro_delivery_service pole credentials_id NIE jest puste (wtedy API wie jaką umowę wybrać).';
            if (is_array($service)) {
                $hints[] = '[HINT] delivery-service owner=' . (string)($service['owner'] ?? '-') . ', carrier_id=' . (string)($service['carrier_id'] ?? '-') . ', credentials_id=' . (string)($service['credentials_id'] ?? '(brak)');
            }
            $hints[] = '[HINT] Jeżeli to Paczkomat/Punkt InPost: upewnij się, że w delivery-services jest zwrócone additionalProperties.inpost#sendingMethod i że moduł je przekazuje (parcel_locker/any_point).';
        }

        
        // Błąd umowy: próbujesz nadać metodę przypisaną do umowy własnej używając usługi Allegro Standard (owner=ALLEGRO).
        if (strpos($msgLower, 'allegro standard agreement') !== false || strpos($msgLower, 'separate agreement') !== false || strpos($msgLower, 'delivery_method_not_available') !== false) {
            $hints[] = '';
            $hints[] = '[HINT] Ten błąd oznacza, że metoda dostawy w tym zamówieniu jest przypisana do UMOWY WŁASNEJ (owner=CLIENT), a próbujesz utworzyć przesyłkę usługą Allegro Standard (owner=ALLEGRO).';
            $hints[] = '[HINT] Rozwiązanie: użyj dokładnie takiej usługi dostawy (deliveryMethodId z /shipment-management/delivery-services), która ma owner=CLIENT i odpowiada metodzie z zamówienia.';
            $hints[] = '[HINT] Dla InPost upewnij się, że w Sales Center → Wysyłam z Allegro → Integracja z InPost masz dodany token ShipX oraz wybraną aktywną umowę (po zmianach z 17.09.2025 działa tylko jedna umowa).';
            if (is_array($service)) {
                $hints[] = '[HINT] Aktualnie dobrany delivery-service: owner=' . (string)($service['owner'] ?? '-') . ', carrier_id=' . (string)($service['carrier_id'] ?? '-') . ', credentials_id=' . (string)($service['credentials_id'] ?? '(brak)') . ', delivery_method_id=' . (string)($service['delivery_method_id'] ?? '-');
            }
        }

return $hints;
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

    private function extractSmartDataFromCheckoutForm(array $cf): array
    {
        $delivery = is_array($cf['delivery'] ?? null) ? $cf['delivery'] : [];
        $packageCount = null;
        $candidates = [
            $delivery['calculatedNumberOfPackages'] ?? null,
            $delivery['numberOfPackages'] ?? null,
            $delivery['packagesCount'] ?? null,
            $cf['calculatedNumberOfPackages'] ?? null,
            $cf['numberOfPackages'] ?? null,
        ];
        foreach ($candidates as $candidate) {
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

        return ['package_count' => $packageCount, 'is_smart' => $isSmart];
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

    private function limitText($text, $maxLen)
    {
        $text = (string)$text;
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($maxLen > 0 && function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') > $maxLen) {
                $text = mb_substr($text, 0, $maxLen, 'UTF-8');
            }
        } else {
            if ($maxLen > 0 && strlen($text) > $maxLen) {
                $text = substr($text, 0, $maxLen);
            }
        }
        return $text;
    }

    private function resolvePackageDimensions(array $params): array
    {
        $inputWeight = null;
        if (isset($params['weight']) && $params['weight'] !== '' && is_numeric($params['weight'])) {
            $inputWeight = (float)$params['weight'];
            if ($inputWeight <= 0) {
                $inputWeight = null;
            }
        }

        if (!empty($params['size_code'])) {
            switch ($params['size_code']) {
                // Allegro API akceptuje tylko: DOX|PACKAGE|PALLET|OTHER.
                // Dla gabarytów A/B/C (np. paczkomaty) przekazujemy PACKAGE + wymiary.
                // UWAGA: waga powinna pochodzić z pola formularza (użytkownik ją wpisuje). Wcześniej była na sztywno 25kg.
                case 'A': return ['height' => 8,  'width' => 38, 'length' => 64, 'weight' => $inputWeight ?? 1.0, 'type' => 'PACKAGE'];
                case 'B': return ['height' => 19, 'width' => 38, 'length' => 64, 'weight' => $inputWeight ?? 1.0, 'type' => 'PACKAGE'];
                case 'C': return ['height' => 41, 'width' => 38, 'length' => 64, 'weight' => $inputWeight ?? 1.0, 'type' => 'PACKAGE'];
            }
        }

        if ($inputWeight !== null) {
            $def = Config::pkgDefaults();
            return [
                'height' => $def['height'], 'width' => $def['width'], 'length' => $def['length'],
                'weight' => $inputWeight,
                'type' => 'PACKAGE'
            ];
        }

        return Config::pkgDefaults();
    }

    private function buildPayload($methodId, $order, $dims)
    {
        $senderPhone = Configuration::get('PS_SHOP_PHONE');
        $senderPhone = preg_replace('/[^0-9+]/', '', (string)$senderPhone);
        if (preg_match('/^[0-9]{9}$/', $senderPhone)) {
            $senderPhone = '+48' . $senderPhone;
        }

        if (empty($senderPhone)) {
            $senderPhone = '+48000000000';
        }

        $sender = [
            'name' => $this->limitText(Configuration::get('PS_SHOP_NAME'), 30),
            'street' => Configuration::get('PS_SHOP_ADDR1'),
            'city' => Configuration::get('PS_SHOP_CITY'),
            'postalCode' => Configuration::get('PS_SHOP_CODE'),
            'countryCode' => 'PL',
            'email' => Configuration::get('PS_SHOP_EMAIL'),
            'phone' => $senderPhone
        ];

        $addr = $order['delivery']['address'];
        $receiver = [
            'name' => trim(($addr['firstName'] ?? '') . ' ' . ($addr['lastName'] ?? '')),
            'street' => $addr['street'],
            'city' => $addr['city'],
            'postalCode' => $addr['zipCode'],
            'countryCode' => $addr['countryCode'],
            'email' => $order['buyer']['email'],
            'phone' => $addr['phoneNumber'] ?? $order['buyer']['phoneNumber']
        ];

        $receiverPhone = (string)($receiver['phone'] ?? '');
        $receiverPhone = preg_replace('/[^0-9+]/', '', $receiverPhone);
        if (strlen($receiverPhone) == 9) {
            $receiverPhone = '+48' . $receiverPhone;
        }
        $receiver['phone'] = $receiverPhone;

        if (!empty($addr['companyName'])) {
            $receiver['company'] = $addr['companyName'];
        }

        if (!empty($order['delivery']['pickupPoint']['id'])) {
            $rawPoint = trim($order['delivery']['pickupPoint']['id']);
            $parts = preg_split('/\s+/', $rawPoint);
            $lastPart = end($parts);
            $cleanPoint = preg_replace('/[^A-Z0-9-]/', '', strtoupper($lastPart));

            if (!empty($cleanPoint)) {
                // Allegro WZA oczekuje tutaj stringa (np. "WCL01BAPP"), nie obiektu.
                $receiver['point'] = $cleanPoint;
            }
        }

        $wgtVal = number_format((float)($dims['weight'] ?? 1), 3, '.', '');
        $finalType = $dims['type'] ?? 'PACKAGE';

        return [
            'deliveryMethodId' => $methodId,
            'sender' => $sender,
            'receiver' => $receiver,
            'labelFormat' => $this->config->getFileFormat(),
            'packages' => [[
                'type' => $finalType,
                'weight' => [
                    'value' => (string)$wgtVal,
                    'unit' => 'KILOGRAMS'
                ],
                // Allegro WZA oczekuje pól length/width/height bezpośrednio w paczce (nie w obiekcie "dimensions").
                // W przeciwnym wypadku zwraca błąd: input.packages[0].length (brak wymaganych pól).
                'length' => [
                    'value' => (string)(int)($dims['length'] ?? 10),
                    'unit' => 'CENTIMETER'
                ],
                'width' => [
                    'value' => (string)(int)($dims['width'] ?? 10),
                    'unit' => 'CENTIMETER'
                ],
                'height' => [
                    'value' => (string)(int)($dims['height'] ?? 10),
                    'unit' => 'CENTIMETER'
                ],
                'content' => 'Towary handlowe'
            ]]
        ];
    }
}
