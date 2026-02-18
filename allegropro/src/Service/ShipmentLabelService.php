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
            if (!empty($wzaUuidForRow) && $wzaUuidForRow !== $primaryCandidate) {
                $debug[] = '[LABEL] row has wza_shipment_uuid, override candidate: ' . $primaryCandidate . ' -> ' . $wzaUuidForRow;
                $primaryCandidate = $wzaUuidForRow;
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
            $decoded = $this->resolver->decodeShipmentReference($primaryCandidate);
            if (is_string($decoded) && $decoded !== '') {
                $debug[] = '[LABEL] decoded shipment reference=' . $decoded;
            }

            $waybill = null;
            $waybillCandidates = [$decoded, $primaryCandidate];
            foreach ($waybillCandidates as $wbCandidate) {
                if (!is_string($wbCandidate)) {
                    continue;
                }
                $wbCandidate = trim($wbCandidate);
                if ($wbCandidate === '') {
                    continue;
                }
                if (strpos($wbCandidate, 'ALLEGRO:') === 0) {
                    $wbCandidate = trim(substr($wbCandidate, 8));
                }
                if (preg_match('/^[A-Z0-9]{10,30}$/i', $wbCandidate)) {
                    $waybill = strtoupper($wbCandidate);
                    break;
                }
            }

            if ($waybill !== null) {
                $resolvedFromWaybill = $this->resolver->resolveShipmentIdByWaybill($account, $waybill, $debug);
                if (is_string($resolvedFromWaybill) && $resolvedFromWaybill !== '' && $this->resolver->looksLikeShipmentId($resolvedFromWaybill)) {
                    $candidateIds = [$resolvedFromWaybill];
                    $candidatePriority[$resolvedFromWaybill] = 0;
                }
            }

            if (empty($candidateIds)) {
                return [
                    'ok' => false,
                    'message' => 'Nie udało się odzyskać shipmentId(UUID). Podaj commandId albo shipmentUuid.',
                    'debug_lines' => $debug,
                    'http_code' => 404,
                ];
            }
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
}
