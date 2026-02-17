<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\ShipmentRepository;

class ShipmentLabelService
{
    private AllegroApiClient $api;
    private LabelConfig $config;
    private LabelStorage $storage;
    private ShipmentRepository $shipments;
    private ShipmentReferenceResolver $resolver;

    public function __construct(
        AllegroApiClient $api,
        LabelConfig $config,
        LabelStorage $storage,
        ShipmentRepository $shipments,
        ShipmentReferenceResolver $resolver
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->storage = $storage;
        $this->shipments = $shipments;
        $this->resolver = $resolver;
    }

    public function cancelShipment(array $account, string $shipmentId): array
    {
        $endpoint = '/shipment-management/shipments/' . $shipmentId . '/cancel';
        $resp = $this->api->postJson($account, $endpoint, []);

        if (!$resp['ok'] && $resp['code'] != 204) {
            $msg = $resp['json']['errors'][0]['message'] ?? $resp['code'];
            return ['ok' => false, 'message' => 'Nie udało się anulować: ' . $msg];
        }

        $this->shipments->updateStatus($shipmentId, 'CANCELLED');
        return ['ok' => true];
    }

    public function downloadLabel(array $account, string $checkoutFormId, string $shipmentId): array
    {
        $debug = [];
        $debug[] = '[LABEL] input shipmentId=' . $shipmentId . ', checkoutFormId=' . $checkoutFormId;

        $removedDuplicates = $this->shipments->removeDuplicatesForOrder((int)$account['id_allegropro_account'], $checkoutFormId);
        if ($removedDuplicates > 0) {
            $debug[] = '[LABEL] usunięto duplikaty lokalnych przesyłek: ' . $removedDuplicates;
        }

        $primaryCandidate = trim($shipmentId);

        if (method_exists($this->shipments, 'getWzaShipmentUuidForShipmentRow')) {
            $wzaUuidForRow = $this->shipments->getWzaShipmentUuidForShipmentRow((int)$account['id_allegropro_account'], $checkoutFormId, $primaryCandidate);
            if (is_string($wzaUuidForRow)) {
                $wzaUuidForRow = trim($wzaUuidForRow);
            }

            // UWAGA: wza_shipment_uuid bywa u nas wykorzystywany jako referencja (np. base64 "INPOST:..."),
            // więc podmieniamy kandydata tylko, jeśli wygląda jak prawdziwy UUID shipmentId.
            if (!empty($wzaUuidForRow) && $wzaUuidForRow !== $primaryCandidate && $this->resolver->looksLikeShipmentId($wzaUuidForRow)) {
                $debug[] = '[LABEL] row has wza_shipment_uuid (UUID), override candidate: ' . $primaryCandidate . ' -> ' . $wzaUuidForRow;
                $primaryCandidate = $wzaUuidForRow;
            }
        }

        // Cache: jeśli wcześniej udało się rozwiązać WZA shipmentId (UUID) do etykiety, użyj tego.
        if (method_exists($this->shipments, 'getWzaLabelShipmentIdForShipmentRow')) {
            $cachedLabelId = $this->shipments->getWzaLabelShipmentIdForShipmentRow((int)$account['id_allegropro_account'], $checkoutFormId, $shipmentId);
            if (is_string($cachedLabelId)) {
                $cachedLabelId = trim($cachedLabelId);
            }
            if (!empty($cachedLabelId) && $this->resolver->looksLikeShipmentId($cachedLabelId)) {
                $debug[] = '[LABEL] cached wza_label_shipment_id found: ' . $cachedLabelId;
                $primaryCandidate = $cachedLabelId;
            }
        }


        if ($primaryCandidate === '') {
            return ['ok' => false, 'message' => 'Brak shipmentId w żądaniu.', 'debug_lines' => $debug, 'http_code' => 400];
        }

        $candidateIds = [];
        $candidatePriority = [];

        if ($this->resolver->looksLikeShipmentId($primaryCandidate)) {
            // UUID może być zarówno shipmentId jak i commandId – resolver rozstrzyga to testowym GET
            $resolvedUuid = $this->resolver->resolveShipmentIdCandidate($account, $primaryCandidate, $debug);
            if (is_string($resolvedUuid) && $resolvedUuid !== '' && $resolvedUuid !== $primaryCandidate) {
                $debug[] = '[LABEL] uuid candidate resolved: ' . $primaryCandidate . ' => ' . $resolvedUuid;
                $primaryCandidate = $resolvedUuid;
            }

            $candidateIds = [$primaryCandidate];
            $candidatePriority[$primaryCandidate] = 0;
        } elseif ($this->resolver->looksLikeCreateCommandId($primaryCandidate)) {
            $resolved = $this->resolver->resolveShipmentIdCandidate($account, $primaryCandidate, $debug);
            if (is_string($resolved) && $resolved !== '' && $this->resolver->looksLikeShipmentId($resolved)) {
                $candidateIds = [$resolved];
                $candidatePriority[$resolved] = 0;
            } else {
                return [
                    'ok' => false,
                    'message' => 'Nie udało się rozwiązać create-command do shipmentId(UUID).',
                    'debug_lines' => $debug,
                    'http_code' => 404,
                ];
            }
        } else {
            // 1) Mamy referencję (np. base64) zamiast UUID/commandId.
            //    Dla przesyłek InPost potrafimy pobrać etykietę z ShipX po numerze nadania.
            $decoded = $this->resolver->decodeShipmentReference($primaryCandidate);
            if (is_string($decoded) && $decoded !== '') {
                $debug[] = '[LABEL] decoded shipment reference=' . $decoded;
            }

            $trackingFromRow = null;
            if (method_exists($this->shipments, 'getTrackingNumberForShipmentRow')) {
                $trackingFromRow = $this->shipments->getTrackingNumberForShipmentRow((int)$account['id_allegropro_account'], $checkoutFormId, $shipmentId);
                if (is_string($trackingFromRow) && trim($trackingFromRow) !== '') {
                    $trackingFromRow = trim($trackingFromRow);
                    $debug[] = '[LABEL] tracking_number from DB row=' . $trackingFromRow;
                } else {
                    $trackingFromRow = null;
                }
            }

            // Carrier + waybill zdekodowane z referencji typu "INPOST:620..." (albo z tracking_number).
            $carrier = null;
            $waybill = null;

            if (is_string($decoded) && strpos($decoded, ':') !== false) {
                $parts = explode(':', $decoded, 2);
                if (count($parts) === 2) {
                    $carrier = strtoupper(trim($parts[0]));
                    $wb = trim($parts[1]);
                    if ($wb !== '') {
                        $waybill = $wb;
                    }
                }
            }

            // Fallback: jeśli nie ma waybill z decoded, spróbuj z tracking_number albo samego shipmentId (po ewentualnym dekodowaniu)
            foreach ([$trackingFromRow, $waybill, $primaryCandidate] as $wbCandidate) {
                if (!is_string($wbCandidate)) {
                    continue;
                }
                $wbCandidate = trim($wbCandidate);
                if ($wbCandidate === '') {
                    continue;
                }

                // Usuń prefiksy typu "ALLEGRO:" jeśli występują
                if (strpos($wbCandidate, 'ALLEGRO:') === 0) {
                    $wbCandidate = trim(substr($wbCandidate, 8));
                }

                // Jeśli nadal zawiera ":" (np. "INPOST:..."), weź część po dwukropku
                if (strpos($wbCandidate, ':') !== false) {
                    $tmp = explode(':', $wbCandidate, 2);
                    if (count($tmp) === 2) {
                        if ($carrier === null) {
                            $carrier = strtoupper(trim($tmp[0]));
                        }
                        $wbCandidate = trim($tmp[1]);
                    }
                }

                if ($wbCandidate !== '') {
                    $waybill = $wbCandidate;
                    break;
                }
            }

            if ($carrier === 'INPOST') {
                if (empty($account['shipx_token'])) {
                    return [
                        'ok' => false,
                        'message' => 'Brak tokenu InPost ShipX w module (Ustawienia → InPost ShipX).',
                        'debug_lines' => $debug,
                        'http_code' => 400,
                    ];
                }

                if (!is_string($waybill) || !preg_match('/^\d{10,}$/', $waybill)) {
                    return [
                        'ok' => false,
                        'message' => 'Nie wykryto poprawnego numeru nadania InPost (tracking_number).',
                        'debug_lines' => $debug,
                        'http_code' => 400,
                    ];
                }

                $debug[] = '[SHIPX] try label by tracking_number=' . $waybill;

                $shipxResp = $this->downloadShipxLabelByTracking($account, $waybill, $debug);
                if (!empty($shipxResp['ok'])) {
                    return $shipxResp;
                }

                return [
                    'ok' => false,
                    'message' => $shipxResp['message'] ?? 'Nie udało się pobrać etykiety z ShipX.',
                    'debug_lines' => $debug,
                    'http_code' => (int)($shipxResp['http_code'] ?? 0),
                ];
            }

            // Dla innych przewoźników bez UUID/commandId nie da się pobrać etykiety przez publiczne API Allegro.
            return [
                'ok' => false,
                'message' => 'Ta przesyłka nie ma UUID/commandId WZA w module. Dla przewoźników innych niż InPost etykietę pobierz w Sales Center / integratorze.',
                'debug_lines' => $debug,
                'http_code' => 404,
            ];
        }


        $labelFormat = $this->config->getFileFormat();
        $isA4Pdf = ($this->config->getPageSize() === 'A4' && $labelFormat === 'PDF');

        $acceptCandidates = $labelFormat === 'ZPL'
            ? ['application/zpl', 'text/plain', 'application/octet-stream', '*/*']
            : ['application/pdf', 'application/octet-stream', '*/*'];

        $lastResp = ['ok' => false, 'code' => 0, 'raw' => ''];
        $usedShipmentId = null;

        foreach ($candidateIds as $candidateShipmentId) {
            $payload = [
                'shipmentIds' => [$candidateShipmentId],
                'pageSize' => $this->config->getPageSize(),
                'labelFormat' => $labelFormat,
                'cutLine' => $isA4Pdf
            ];

            $resp = ['ok' => false, 'code' => 0, 'raw' => ''];
            foreach ($acceptCandidates as $accept) {
                $resp = $this->api->postBinary($account, '/shipment-management/label', $payload, $accept);
                if (!empty($resp['ok'])) {
                    $usedShipmentId = $candidateShipmentId;
                    $lastResp = $resp;
                    break;
                }

                $code = (int)($resp['code'] ?? 0);
                $lastResp = $resp;
                if ($code === 406) {
                    continue;
                }
                break;
            }

            if ($usedShipmentId !== null) {
                break;
            }
        }

        if ($usedShipmentId === null || empty($lastResp['ok'])) {
            return [
                'ok' => false,
                'message' => 'Błąd pobierania etykiety',
                'debug_lines' => $debug,
                'http_code' => (int)($lastResp['code'] ?? 0),
            ];
        }

        $uniqueName = $checkoutFormId . '_' . substr($usedShipmentId, 0, 8);
        $path = $this->storage->save($uniqueName, $lastResp['raw'], $labelFormat);

        return [
            'ok' => true,
            'path' => $path,
            'format' => $labelFormat,
            'name' => $uniqueName,
            'debug_lines' => $debug,
        ];
    }

    /**
     * Pobiera etykietę InPost z ShipX po numerze nadania (tracking_number).
     */
    private function downloadShipxLabelByTracking(array $account, string $trackingNumber, array &$debug): array
    {
        $token = (string)($account['shipx_token'] ?? '');
        $token = trim($token);
        if ($token === '') {
            return ['ok' => false, 'message' => 'Brak tokenu ShipX.', 'http_code' => 400];
        }

        $base = $this->getShipxBaseUrl($account);

        // 1) Organizacje
        $orgResp = $this->shipxRequest('GET', $base . '/v1/organizations', $token, null, false, $debug);
        if (empty($orgResp['ok'])) {
            return [
                'ok' => false,
                'message' => 'ShipX: nie udało się pobrać organizacji (HTTP ' . (int)($orgResp['code'] ?? 0) . ')',
                'http_code' => (int)($orgResp['code'] ?? 0),
            ];
        }

        $orgId = $this->extractShipxOrganizationId($orgResp['json'] ?? null);
        if (!$orgId) {
            return ['ok' => false, 'message' => 'ShipX: brak organization_id dla tokenu.', 'http_code' => 404];
        }
        $debug[] = '[SHIPX] organization_id=' . $orgId;

        // 2) Szukamy shipmentu po tracking_number (dokładne dopasowanie)
        $searchUrl = $base . '/v1/organizations/' . urlencode((string)$orgId) . '/shipments?tracking_number=' . urlencode($trackingNumber);
        $searchResp = $this->shipxRequest('GET', $searchUrl, $token, null, false, $debug);
        if (empty($searchResp['ok'])) {
            return [
                'ok' => false,
                'message' => 'ShipX: nie udało się wyszukać przesyłki po tracking_number (HTTP ' . (int)($searchResp['code'] ?? 0) . ')',
                'http_code' => (int)($searchResp['code'] ?? 0),
            ];
        }

        $shipxShipmentId = $this->extractShipxShipmentIdByTracking($searchResp['json'] ?? null, $trackingNumber);
        if (!$shipxShipmentId) {
            return ['ok' => false, 'message' => 'ShipX: nie znaleziono przesyłki dla tracking_number=' . $trackingNumber, 'http_code' => 404];
        }
        $debug[] = '[SHIPX] shipment_id=' . $shipxShipmentId;

        // 3) Pobierz etykietę (PDF). Typ A6/normal wg konfiguracji.
        $type = ($this->config->getPageSize() === 'A6') ? 'A6' : 'normal';
        $labelUrl = $base . '/v1/shipments/' . urlencode((string)$shipxShipmentId) . '/label?format=pdf&type=' . urlencode($type);

        $labelResp = $this->shipxRequest('GET', $labelUrl, $token, null, true, $debug);
        if (empty($labelResp['ok'])) {
            return [
                'ok' => false,
                'message' => 'ShipX: nie udało się pobrać etykiety (HTTP ' . (int)($labelResp['code'] ?? 0) . ')',
                'http_code' => (int)($labelResp['code'] ?? 0),
            ];
        }

        $uniqueName = $this->sanitizeFileKey(($account['id_allegropro_account'] ?? 'acc') . '_shipx_' . substr($trackingNumber, -8));
        $path = $this->storage->save($uniqueName, (string)$labelResp['raw'], LabelConfig::FORMAT_PDF);

        return [
            'ok' => true,
            'path' => $path,
            'format' => LabelConfig::FORMAT_PDF,
            'name' => $uniqueName,
            'debug_lines' => $debug,
        ];
    }

    private function getShipxBaseUrl(array $account): string
    {
        $isSandbox = !empty($account['sandbox']);
        return $isSandbox ? 'https://sandbox-api-shipx-pl.easypack24.net' : 'https://api-shipx-pl.easypack24.net';
    }

    private function shipxRequest(string $method, string $url, string $token, ?string $bodyJson, bool $binary, array &$debug): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: ' . ($binary ? 'application/pdf' : 'application/json'),
        ];

        if ($bodyJson !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $debug[] = '[SHIPX] ' . $method . ' ' . $url . ': HTTP ' . $code;

        if ($raw === false) {
            return ['ok' => false, 'code' => $code, 'error' => $err ?: 'curl_error', 'raw' => ''];
        }

        $ok = ($code >= 200 && $code < 300);

        if ($binary) {
            return ['ok' => $ok, 'code' => $code, 'raw' => (string)$raw];
        }

        $json = json_decode((string)$raw, true);
        return ['ok' => $ok, 'code' => $code, 'raw' => (string)$raw, 'json' => $json];
    }

    private function extractShipxOrganizationId($json)
    {
        if (is_array($json)) {
            if (isset($json['items']) && is_array($json['items'])) {
                foreach ($json['items'] as $row) {
                    if (is_array($row) && isset($row['id'])) {
                        return $row['id'];
                    }
                }
            }
            foreach ($json as $row) {
                if (is_array($row) && isset($row['id'])) {
                    return $row['id'];
                }
            }
        }
        return null;
    }

    private function extractShipxShipmentIdByTracking($json, string $trackingNumber)
    {
        $trackingNumber = trim($trackingNumber);

        $items = null;
        if (is_array($json)) {
            if (isset($json['items']) && is_array($json['items'])) {
                $items = $json['items'];
            } else {
                $items = $json;
            }
        }

        if (!is_array($items)) {
            return null;
        }

        foreach ($items as $row) {
            if (!is_array($row)) continue;
            $tn = $row['tracking_number'] ?? ($row['trackingNumber'] ?? null);
            if (is_string($tn) && trim($tn) === $trackingNumber) {
                return $row['id'] ?? null;
            }
            if (isset($row['parcels']) && is_array($row['parcels'])) {
                foreach ($row['parcels'] as $p) {
                    if (!is_array($p)) continue;
                    $ptn = $p['tracking_number'] ?? null;
                    if (is_string($ptn) && trim($ptn) === $trackingNumber) {
                        return $row['id'] ?? null;
                    }
                }
            }
        }
        if (count($items) === 1 && is_array($items[0]) && isset($items[0]['id'])) {
            return $items[0]['id'];
        }
        return null;
    }

    private function sanitizeFileKey(string $key): string
    {
        $key = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $key);
        return substr($key, 0, 80);
    }

}
