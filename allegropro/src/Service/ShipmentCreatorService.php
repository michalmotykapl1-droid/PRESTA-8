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
            if (is_array($cfJson)) {
                $smartData = $this->extractSmartDataFromCheckoutForm($cfJson);
                $packageLimit = $smartData['package_count'] ?? null;

                // Ograniczenie liczby paczek z checkout-form dotyczy wyłącznie przesyłek SMART.
                // Dla zwykłych przesyłek (smart=0) Allegro pozwala tworzyć kolejne etykiety.
                $requestIsSmart = !empty($params['smart']);
                if ($requestIsSmart && is_int($packageLimit) && $packageLimit > 0) {
                    $activeCount = method_exists($this->shipments, 'countActiveShipmentsForOrder')
                        ? (int)$this->shipments->countActiveShipmentsForOrder($accountId, $checkoutFormId)
                        : (int)count($this->shipments->findAllByOrderForAccount($accountId, $checkoutFormId));

                    if ($activeCount >= $packageLimit) {
                        return [
                            'ok' => false,
                            'message' => 'Limit paczek SMART dla tej przesyłki został osiągnięty (' . $activeCount . '/' . $packageLimit . '). Wyłącz SMART albo usuń nadmiarową przesyłkę (czerwony X) i spróbuj ponownie.'
                        ];
                    }
                }
            }
        } catch (Exception $e) {
        }

        $pkgDims = $this->resolvePackageDimensions($params, $checkoutFormId);

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

            if (isset($resp['json']['services']) && is_array($resp['json']['services'])) {
                $services = $resp['json']['services'];
                $shape = 'services';
            } elseif (isset($resp['json']['deliveryServices']) && is_array($resp['json']['deliveryServices'])) {
                $services = $resp['json']['deliveryServices'];
                $shape = 'deliveryServices';
            } elseif (array_values($resp['json']) === $resp['json']) {
                // fallback: jeżeli API zwróci tablicę bez klucza
                $services = $resp['json'];
                $shape = 'root-array';
            }

            if (!is_array($services)) {
                $resp['shape'] = 'unknown';
                return $resp;
            }

            $this->deliveryServices->replaceAllForAccount((int)$account['id_allegropro_account'], $services);
            $resp['shape'] = $shape;
            return $resp;
        } catch (Exception $e) {
            return ['ok' => false, 'code' => 0, 'json' => ['message' => $e->getMessage()]];
        }
    }

    private function resolveDeliveryServiceFromApi(
        array $account,
        string $deliveryMethodId,
        string $methodName,
        bool $hasPickupPoint,
        bool $debug,
        array &$debugLines
    ): ?array {
        try {
            $resp = $this->api->get($account, '/shipment-management/delivery-services', ['limit' => 500]);
            if (empty($resp['ok']) || !is_array($resp['json'])) {
                if ($debug) {
                    $debugLines[] = '[CREATE] resolveDeliveryServiceFromApi: API error code=' . (int)($resp['code'] ?? 0);
                }
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

            if ($debug) {
                $debugLines[] = '[CREATE] resolveDeliveryServiceFromApi: services count=' . count($services);
            }

            // 1) próba strict po deliveryMethodId
            $strict = [];
            foreach ($services as $s) {
                if (!is_array($s)) {
                    continue;
                }
                $dmId = '';
                if (isset($s['deliveryMethodId'])) {
                    $dmId = (string)$s['deliveryMethodId'];
                } elseif (is_array($s['id'] ?? null) && isset($s['id']['deliveryMethodId'])) {
                    $dmId = (string)$s['id']['deliveryMethodId'];
                } elseif (is_array($s['deliveryMethod'] ?? null) && isset($s['deliveryMethod']['id'])) {
                    $dmId = (string)$s['deliveryMethod']['id'];
                }

                if ($dmId === $deliveryMethodId) {
                    $strict[] = $s;
                }
            }

            if (!empty($strict)) {
                if ($debug) {
                    $debugLines[] = '[CREATE] resolveDeliveryServiceFromApi: strict matches=' . count($strict);
                }

                // preferuj te z credentialsId
                usort($strict, function ($a, $b) {
                    $ac = $this->extractCredentialsIdFromService($a) ? 1 : 0;
                    $bc = $this->extractCredentialsIdFromService($b) ? 1 : 0;
                    if ($ac !== $bc) return $bc <=> $ac;

                    $ao = strtoupper((string)($a['owner'] ?? ''));
                    $bo = strtoupper((string)($b['owner'] ?? ''));
                    if ($ao !== $bo) {
                        if ($ao === 'CLIENT') return -1;
                        if ($bo === 'CLIENT') return 1;
                    }
                    return 0;
                });

                $pick = $strict[0];
                return $this->normalizeServiceForPayload($pick, $deliveryMethodId);
            }

            // 2) fallback po nazwie metody / inpost i trybie pickup/courier
            $nameNeedle = mb_strtolower(trim($methodName));
            $candidates = [];
            foreach ($services as $s) {
                if (!is_array($s)) continue;

                $serviceName = mb_strtolower(trim((string)($s['name'] ?? '')));
                $carrier = strtoupper(trim((string)($s['carrierId'] ?? ($s['carrier_id'] ?? ''))));

                $score = 0;
                if ($serviceName !== '' && $nameNeedle !== '' && mb_strpos($serviceName, $nameNeedle) !== false) {
                    $score += 40;
                }
                if (mb_strpos($nameNeedle, 'inpost') !== false && $carrier === 'INPOST') {
                    $score += 30;
                }
                if ($this->extractCredentialsIdFromService($s)) {
                    $score += 10;
                }
                if (strtoupper((string)($s['owner'] ?? '')) === 'CLIENT') {
                    $score += 5;
                }

                $sending = $this->extractInpostSendingMethodFromService($s);
                if ($hasPickupPoint) {
                    $smOk = (!empty($sending) && in_array($sending, ['any_point', 'parcel_locker', 'pop'], true));
                    if ($smOk) $score += 20;
                } else {
                    $smOk = (!empty($sending) && $sending === 'dispatch_order');
                    if ($smOk) $score += 20;
                }

                if ($score > 0) {
                    $s['_score'] = $score;
                    $candidates[] = $s;
                }
            }

            if (empty($candidates)) {
                if ($debug) {
                    $debugLines[] = '[CREATE] resolveDeliveryServiceFromApi: brak kandydatów po fallback.';
                }
                return null;
            }

            usort($candidates, function ($a, $b) {
                return ((int)($b['_score'] ?? 0)) <=> ((int)($a['_score'] ?? 0));
            });

            $pick = $candidates[0];
            $resolvedDmId = $this->extractDeliveryMethodIdFromService($pick);
            if ($resolvedDmId === '') $resolvedDmId = $deliveryMethodId;

            if ($debug) {
                $debugLines[] = '[CREATE] resolveDeliveryServiceFromApi: fallback pick score=' . (int)($pick['_score'] ?? 0)
                    . ', dmId=' . $resolvedDmId
                    . ', name=' . (string)($pick['name'] ?? '-')
                    . ', carrier=' . (string)($pick['carrierId'] ?? '-');
            }

            return $this->normalizeServiceForPayload($pick, $resolvedDmId);
        } catch (Exception $e) {
            if ($debug) {
                $debugLines[] = '[CREATE] resolveDeliveryServiceFromApi exception: ' . $e->getMessage();
            }
            return null;
        }
    }

    private function normalizeServiceForPayload(array $service, string $resolvedDeliveryMethodId): array
    {
        $credentialsId = $this->extractCredentialsIdFromService($service);
        $additional = $this->extractAdditionalPropertiesFromService($service);

        return [
            'delivery_method_id' => $resolvedDeliveryMethodId,
            'credentials_id' => $credentialsId ?: null,
            'owner' => (string)($service['owner'] ?? ''),
            'carrier_id' => (string)($service['carrierId'] ?? ($service['carrier_id'] ?? '')),
            'name' => (string)($service['name'] ?? ''),
            'additional_properties_json' => !empty($additional) ? json_encode($additional, JSON_UNESCAPED_UNICODE) : null,
        ];
    }

    private function extractDeliveryMethodIdFromService(array $service): string
    {
        if (isset($service['deliveryMethodId'])) return (string)$service['deliveryMethodId'];
        if (is_array($service['id'] ?? null) && isset($service['id']['deliveryMethodId'])) return (string)$service['id']['deliveryMethodId'];
        if (is_array($service['deliveryMethod'] ?? null) && isset($service['deliveryMethod']['id'])) return (string)$service['deliveryMethod']['id'];
        return '';
    }

    private function extractCredentialsIdFromService(array $service): string
    {
        if (!empty($service['credentialsId'])) return (string)$service['credentialsId'];
        if (is_array($service['id'] ?? null) && !empty($service['id']['credentialsId'])) return (string)$service['id']['credentialsId'];
        return '';
    }

    private function extractAdditionalPropertiesFromService(array $service): array
    {
        $keys = ['additionalProperties', 'additional_properties', 'properties', 'settings'];
        foreach ($keys as $k) {
            if (isset($service[$k]) && is_array($service[$k])) {
                return $service[$k];
            }
        }
        return [];
    }

    private function extractInpostSendingMethodFromService(array $service): string
    {
        $props = $this->extractAdditionalPropertiesFromService($service);
        if (isset($props['inpost#sendingMethod'])) return (string)$props['inpost#sendingMethod'];
        if (isset($props['sendingMethod'])) return (string)$props['sendingMethod'];
        if (isset($props['sending_method'])) return (string)$props['sending_method'];
        return '';
    }

    private function applyInpostSendingMethod(array $payload, array $order, ?array $additionalProps, string $methodName, bool $debug, array &$debugLines): array
    {
        $hasPickup = !empty($order['delivery']['pickupPoint']['id']);
        $methodLower = mb_strtolower(trim($methodName));

        // 1) Jeśli API delivery-services zwróciło inpost#sendingMethod – użyj.
        $sm = '';
        if (is_array($additionalProps)) {
            if (!empty($additionalProps['inpost#sendingMethod'])) {
                $sm = (string)$additionalProps['inpost#sendingMethod'];
            } elseif (!empty($additionalProps['sendingMethod'])) {
                $sm = (string)$additionalProps['sendingMethod'];
            } elseif (!empty($additionalProps['sending_method'])) {
                $sm = (string)$additionalProps['sending_method'];
            }
        }

        // 2) Fallback heurystyczny.
        if ($sm === '') {
            $isInpost = (mb_strpos($methodLower, 'inpost') !== false);
            if ($isInpost) {
                $sm = $hasPickup ? 'any_point' : 'dispatch_order';
            }
        }

        if ($sm !== '') {
            if (!isset($payload['additionalProperties']) || !is_array($payload['additionalProperties'])) {
                $payload['additionalProperties'] = [];
            }
            $payload['additionalProperties']['inpost#sendingMethod'] = $sm;

            if ($debug) {
                $debugLines[] = '[CREATE] additionalProperties.inpost#sendingMethod=' . $sm
                    . ($hasPickup ? ' (pickupPoint=yes)' : ' (pickupPoint=no)');
            }
        }

        return $payload;
    }

    /**
     * Smart data (package_count) from checkout-form JSON.
     */
    private function extractSmartDataFromCheckoutForm(array $cfJson): array
    {
        $res = [
            'package_count' => null,
        ];

        // Spotykane ścieżki:
        // delivery.smart.packageCount
        // delivery.smart.package_count
        // delivery.smart.deliveryLimit
        // summary.smart.packageCount
        $paths = [
            ['delivery', 'smart', 'packageCount'],
            ['delivery', 'smart', 'package_count'],
            ['delivery', 'smart', 'deliveryLimit'],
            ['summary', 'smart', 'packageCount'],
            ['summary', 'smart', 'package_count'],
        ];

        foreach ($paths as $path) {
            $v = $cfJson;
            $ok = true;
            foreach ($path as $k) {
                if (!is_array($v) || !array_key_exists($k, $v)) {
                    $ok = false;
                    break;
                }
                $v = $v[$k];
            }
            if ($ok && is_numeric($v)) {
                $res['package_count'] = (int)$v;
                break;
            }
        }

        // Ostateczny fallback: jeśli metoda ma strict limit na 1 paczkę (np. część One Box),
        // Allegro zwykle i tak odrzuci 2. etykietę – ale tu nic nie wymuszamy.
        return $res;
    }

    private function troubleshootHints(string $errMsg, ?array $service): array
    {
        $m = mb_strtolower($errMsg);
        $hints = [];

        if (strpos($m, 'credentials') !== false || strpos($m, 'shipx') !== false || strpos($m, 'inpost') !== false) {
            $hints[] = '[HINT] Dla metod InPost (szczególnie Paczkomat) wymagane jest credentialsId (ShipX token) powiązane z delivery-service.';
            $hints[] = '[HINT] Upewnij się, że: (1) masz token ShipX w module, (2) delivery-services są zsynchronizowane, (3) wybrany delivery-service ma credentials_id.';
        }

        if (strpos($m, 'credentials id is not allowed') !== false) {
            $hints[] = '[HINT] Ta metoda nie akceptuje credentialsId. Spróbuj bez credentialsId (moduł robi to automatycznie w retry).';
        }

        if (strpos($m, 'point') !== false || strpos($m, 'pickup') !== false) {
            $hints[] = '[HINT] Zamówienie ma pickupPoint — sprawdź, czy receiver.point jest poprawnie ustawiony.';
        }

        if (strpos($m, 'length') !== false || strpos($m, 'width') !== false || strpos($m, 'height') !== false) {
            $hints[] = '[HINT] Allegro wymaga pól packages[].length/width/height bezpośrednio w paczce.';
        }

        if (strpos($m, 'additionalproperties') !== false || strpos($m, 'sendingmethod') !== false) {
            $hints[] = '[HINT] Dla InPost pickup często wymagane additionalProperties.inpost#sendingMethod=any_point.';
            $hints[] = '[HINT] Dla InPost courier często wymagane additionalProperties.inpost#sendingMethod=dispatch_order.';
        }

        if (is_array($service)) {
            $hints[] = '[HINT] Aktualnie dobrany delivery-service: owner=' . (string)($service['owner'] ?? '-') . ', carrier_id=' . (string)($service['carrier_id'] ?? '-') . ', credentials_id=' . (string)($service['credentials_id'] ?? '(brak)') . ', delivery_method_id=' . (string)($service['delivery_method_id'] ?? '-');
        }

        return $hints;
    }

    private function normalizeDateTime($value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }

    private function extractTrackingNumber(array $shipment): ?string
    {
        $candidates = [];

        // Najczęstsze miejsca
        if (isset($shipment['trackingNumber'])) {
            $candidates[] = $shipment['trackingNumber'];
        }
        if (isset($shipment['tracking']['number'])) {
            $candidates[] = $shipment['tracking']['number'];
        }
        if (isset($shipment['tracking']['waybill'])) {
            $candidates[] = $shipment['tracking']['waybill'];
        }
        if (isset($shipment['waybill'])) {
            $candidates[] = $shipment['waybill'];
        }

        // Bywa w tablicy package'ów
        if (isset($shipment['packages']) && is_array($shipment['packages'])) {
            foreach ($shipment['packages'] as $p) {
                if (isset($p['trackingNumber'])) {
                    $candidates[] = $p['trackingNumber'];
                }
                if (isset($p['tracking']['number'])) {
                    $candidates[] = $p['tracking']['number'];
                }
                // Coraz częściej Allegro/InPost zwraca numer nadania jako "waybill" (a nie trackingNumber)
                if (isset($p['waybill'])) {
                    $candidates[] = $p['waybill'];
                }
                if (isset($p['tracking']['waybill'])) {
                    $candidates[] = $p['tracking']['waybill'];
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }
            $v = trim($candidate);
            if ($v !== '') {
                return $v;
            }
        }

        return null;
    }

    private function extractIsSmart(array $shipment): int
    {
        $paths = [
            ['smart'],
            ['service', 'smart'],
            ['delivery', 'smart'],
            ['additionalServices', 'smart'],
        ];

        foreach ($paths as $path) {
            $v = $shipment;
            $ok = true;
            foreach ($path as $k) {
                if (!is_array($v) || !array_key_exists($k, $v)) {
                    $ok = false;
                    break;
                }
                $v = $v[$k];
            }
            if (!$ok) {
                continue;
            }

            if (is_bool($v)) {
                return $v ? 1 : 0;
            }
            if (is_numeric($v)) {
                return ((int)$v) > 0 ? 1 : 0;
            }
            if (is_string($v)) {
                $u = strtolower(trim($v));
                if (in_array($u, ['1', 'true', 'yes', 'on'], true)) {
                    return 1;
                }
                if (in_array($u, ['0', 'false', 'no', 'off'], true)) {
                    return 0;
                }
            }
        }

        return 0;
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

    private function resolvePackageDimensions(array $params, string $checkoutFormId): array
    {
        $pkgDefaults = Config::pkgDefaults();

        $inputWeight = null;
        if (isset($params['weight']) && $params['weight'] !== '' && is_numeric($params['weight'])) {
            $inputWeight = (float)$params['weight'];
            if ($inputWeight <= 0) {
                $inputWeight = null;
            }
        }

        $weightSource = strtoupper(trim((string)($params['weight_source'] ?? 'MANUAL')));
        if (!in_array($weightSource, ['MANUAL', 'CONFIG', 'PRODUCTS'], true)) {
            $weightSource = 'MANUAL';
        }

        $resolvedWeight = $inputWeight;
        if ($weightSource === 'CONFIG') {
            $cfgWeight = isset($pkgDefaults['weight']) ? (float)$pkgDefaults['weight'] : 1.0;
            $resolvedWeight = $cfgWeight > 0 ? $cfgWeight : 1.0;
        } elseif ($weightSource === 'PRODUCTS') {
            $productsWeight = $this->calculateProductsWeight($checkoutFormId);
            if ($productsWeight !== null && $productsWeight > 0) {
                $resolvedWeight = $productsWeight;
            }
        }

        if (!empty($params['size_code'])) {
            switch ($params['size_code']) {
                // Allegro API akceptuje tylko: DOX|PACKAGE|PALLET|OTHER.
                // Dla gabarytów A/B/C (np. paczkomaty) przekazujemy PACKAGE + wymiary.
                case 'A': return ['height' => 8,  'width' => 38, 'length' => 64, 'weight' => $resolvedWeight ?? 1.0, 'type' => 'PACKAGE'];
                case 'B': return ['height' => 19, 'width' => 38, 'length' => 64, 'weight' => $resolvedWeight ?? 1.0, 'type' => 'PACKAGE'];
                case 'C': return ['height' => 41, 'width' => 38, 'length' => 64, 'weight' => $resolvedWeight ?? 1.0, 'type' => 'PACKAGE'];
            }
        }

        $dimensionSource = strtoupper(trim((string)($params['dimension_source'] ?? 'MANUAL')));
        if (!in_array($dimensionSource, ['MANUAL', 'CONFIG'], true)) {
            $dimensionSource = 'MANUAL';
        }

        $manualLength = isset($params['length']) && is_numeric($params['length']) ? (int)$params['length'] : 0;
        $manualWidth = isset($params['width']) && is_numeric($params['width']) ? (int)$params['width'] : 0;
        $manualHeight = isset($params['height']) && is_numeric($params['height']) ? (int)$params['height'] : 0;

        if ($dimensionSource === 'CONFIG') {
            $resolvedLength = (int)($pkgDefaults['length'] ?? 10);
            $resolvedWidth = (int)($pkgDefaults['width'] ?? 10);
            $resolvedHeight = (int)($pkgDefaults['height'] ?? 10);
        } else {
            $resolvedLength = $manualLength > 0 ? $manualLength : (int)($pkgDefaults['length'] ?? 10);
            $resolvedWidth = $manualWidth > 0 ? $manualWidth : (int)($pkgDefaults['width'] ?? 10);
            $resolvedHeight = $manualHeight > 0 ? $manualHeight : (int)($pkgDefaults['height'] ?? 10);
        }

        if ($resolvedLength <= 0) {
            $resolvedLength = 10;
        }
        if ($resolvedWidth <= 0) {
            $resolvedWidth = 10;
        }
        if ($resolvedHeight <= 0) {
            $resolvedHeight = 10;
        }

        return [
            'height' => $resolvedHeight,
            'width' => $resolvedWidth,
            'length' => $resolvedLength,
            'weight' => $resolvedWeight ?? ((isset($pkgDefaults['weight']) && (float)$pkgDefaults['weight'] > 0) ? (float)$pkgDefaults['weight'] : 1.0),
            'type' => 'PACKAGE'
        ];
    }

    private function calculateProductsWeight(string $checkoutFormId): ?float
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
            return null;
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
            return null;
        }

        return round($sum, 3);
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
