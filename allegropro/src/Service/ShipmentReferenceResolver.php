<?php
namespace AllegroPro\Service;

class ShipmentReferenceResolver
{
    private AllegroApiClient $api;

    public function __construct(AllegroApiClient $api)
    {
        $this->api = $api;
    }

    public function looksLikeShipmentReference(string $value): bool
    {
        return $this->looksLikeShipmentId($value) || $this->looksLikeCreateCommandId($value);
    }

    public function looksLikeShipmentId(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if ($this->looksLikeCreateCommandId($value)) {
            return false;
        }

        if ((bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            return true;
        }

        return (bool)preg_match('/^(?=.*-)[a-zA-Z0-9-]{12,80}$/', $value);
    }

    public function looksLikeCreateCommandId(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (strpos($value, ':') !== false) {
            return true;
        }

        return (bool)preg_match('/^[A-Za-z0-9+\/]+=*$/', $value) && strlen($value) >= 16 && (strlen($value) % 4 === 0);
    }

    public function resolveShipmentIdCandidate(array $account, string $candidateId, array &$debugLines = []): ?string
    {
        $candidateId = trim($candidateId);
        if ($candidateId === '') {
            return null;
        }

        if ($this->looksLikeShipmentId($candidateId)) {
            return $candidateId;
        }

        if (!$this->looksLikeCreateCommandId($candidateId)) {
            return null;
        }

        $resp = $this->api->get($account, '/shipment-management/shipments/create-commands/' . rawurlencode($candidateId));
        if (!empty($debugLines)) {
            $debugLines[] = '[API] GET /shipment-management/shipments/create-commands/{id}: HTTP ' . (int)($resp['code'] ?? 0) . ', ok=' . (!empty($resp['ok']) ? '1' : '0');
        }
        if (!$resp['ok'] || !is_array($resp['json'])) {
            return null;
        }

        $shipmentId = (string)($resp['json']['shipmentId'] ?? '');
        if ($shipmentId !== '' && $this->looksLikeShipmentId($shipmentId)) {
            if (!empty($debugLines)) {
                $debugLines[] = '[SYNC] resolve commandId -> shipmentId: ' . $candidateId . ' => ' . $shipmentId;
            }
            return $shipmentId;
        }

        return null;
    }

    public function extractShipmentRows(array $json): array
    {
        $keys = ['shipments', 'items', 'shipmentList'];
        foreach ($keys as $k) {
            if (!empty($json[$k]) && is_array($json[$k])) {
                return $json[$k];
            }
        }

        if (isset($json[0]) && is_array($json[0])) {
            return $json;
        }

        return [];
    }

    public function extractWaybillFromRow(array $row): ?string
    {
        $candidates = [
            $row['waybill'] ?? null,
            $row['trackingNumber'] ?? null,
            $row['tracking']['number'] ?? null,
            $row['label']['trackingNumber'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate)) {
                $candidate = trim($candidate);
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return null;
    }

    public function resolveShipmentIdByWaybill(array $account, string $waybill, array &$debugLines = []): ?string
    {
        $waybill = trim($waybill);
        if ($waybill === '') {
            return null;
        }

        $queries = [
            ['waybill' => $waybill],
            ['trackingNumber' => $waybill],
            ['phrase' => $waybill],
            ['query' => $waybill],
        ];

        foreach ($queries as $query) {
            $resp = $this->api->get($account, '/shipment-management/shipments', $query);
            if (!empty($debugLines)) {
                $debugLines[] = '[API] GET /shipment-management/shipments ' . json_encode($query, JSON_UNESCAPED_UNICODE) . ': HTTP ' . (int)($resp['code'] ?? 0) . ', ok=' . (!empty($resp['ok']) ? '1' : '0');
            }

            if (empty($resp['ok']) || !is_array($resp['json'])) {
                continue;
            }

            $rows = $this->extractShipmentRows($resp['json']);
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $rowWaybill = $this->extractWaybillFromRow($row);
                $rowId = isset($row['id']) ? trim((string)$row['id']) : '';

                if ($rowId !== '' && $this->looksLikeShipmentId($rowId)) {
                    if ($rowWaybill === null || strcasecmp($rowWaybill, $waybill) === 0) {
                        if (!empty($debugLines)) {
                            $debugLines[] = '[SYNC] resolve waybill -> shipmentId: ' . $waybill . ' => ' . $rowId;
                        }
                        return $rowId;
                    }
                }
            }
        }

        return null;
    }

    public function extractCandidateReferencesFromArray(array $data): array
    {
        $refs = [];

        $walk = function ($value, array $path = []) use (&$walk, &$refs) {
            if (!is_array($value)) {
                return;
            }

            foreach ($value as $k => $v) {
                $childPath = $path;
                if (is_string($k)) {
                    $childPath[] = strtolower($k);
                }

                if (is_string($v) && is_string($k) && $this->isLikelyShipmentReferenceKey($k, $path)) {
                    $candidate = trim($v);
                    if ($candidate !== '' && $this->looksLikeShipmentReference($candidate)) {
                        $refs[$candidate] = true;
                    }
                }

                $walk($v, $childPath);
            }
        };

        $walk($data, []);
        return array_keys($refs);
    }

    public function extractShipmentIdFromRow(array $row): ?string
    {
        $refs = $this->extractCandidateReferencesFromArray($row);

        foreach ($refs as $candidate) {
            if ($this->looksLikeCreateCommandId($candidate)) {
                return $candidate;
            }
        }

        foreach ($refs as $candidate) {
            if ($this->looksLikeShipmentId($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isLikelyShipmentReferenceKey(string $key, array $parentPath): bool
    {
        $key = strtolower($key);

        if (strpos($key, 'shipment') !== false || strpos($key, 'command') !== false) {
            return true;
        }

        if ($key !== 'id') {
            return false;
        }

        if (empty($parentPath)) {
            return true;
        }

        $parent = implode('.', $parentPath);
        return strpos($parent, 'shipment') !== false || strpos($parent, 'command') !== false;
    }

    public function decodeShipmentReference(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (strpos($value, 'ALLEGRO:') === 0) {
            return trim(substr($value, 8));
        }

        if (preg_match('/^[A-Za-z0-9+\/]+=*$/', $value) && strlen($value) % 4 === 0) {
            $decoded = base64_decode($value, true);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $value;
    }
}
