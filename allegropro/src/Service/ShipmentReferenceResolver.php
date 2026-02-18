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
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        // Jeśli to jest zakodowany/oznaczony numer nadania (np. base64 z "ALLEGRO:..."),
        // traktujemy to jako referencję, ale NIE jako shipmentId/commandId.
        $decoded = $this->decodeShipmentReference($value);
        if (is_string($decoded) && $decoded !== '' && $decoded !== $value) {
            $value = $decoded;
        }

        return $this->looksLikeShipmentId($value)
            || $this->looksLikeCreateCommandId($value)
            || $this->looksLikeWaybill($value);
    }

    public function looksLikeShipmentId(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        // ShipmentId w /shipment-management to najczęściej UUID
        if ($this->looksLikeUuid($value)) {
            return true;
        }

        // Fallback: inne identyfikatory z myślnikiem (historycznie spotykane)
        return (bool)preg_match('/^(?=.*-)[a-zA-Z0-9-]{12,80}$/', $value);
    }

    /**
     * W praktyce commandId z create-commands bywa UUID albo innym tokenem.
     * Nie da się pewnie odróżnić UUID shipmentId od UUID commandId "po samym formacie",
     * więc rozstrzygamy to w resolveShipmentIdCandidate() poprzez testowe GET.
     */
    public function looksLikeCreateCommandId(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        // Specjalne prefiksy/formaty numerów nadania – NIE są commandId
        if (stripos($value, 'ALLEGRO:') === 0) {
            return false;
        }

        // Bazowe heurystyki dla tokenów (np. base64) – zostawiamy, ale bez łapania "ALLEGRO:..."
        if ((bool)preg_match('/^[A-Za-z0-9+\/]+=*$/', $value) && strlen($value) >= 16 && (strlen($value) % 4 === 0)) {
            return true;
        }

        // UUID może być commandId, ale może też być shipmentId – nie rozstrzygamy tu.
        return false;
    }

    public function resolveShipmentIdCandidate(array $account, string $candidateId, array &$debugLines = []): ?string
    {
        $candidateId = trim($candidateId);
        if ($candidateId === '') {
            return null;
        }

        // Dekoduj referencje typu base64("ALLEGRO:WAYBILL") / "ALLEGRO:WAYBILL"
        $decoded = $this->decodeShipmentReference($candidateId);
        if (is_string($decoded) && $decoded !== '') {
            $candidateId = $decoded;
        }

        // Jeśli po dekodowaniu mamy numer nadania, to nie rozwiążemy go do shipmentId tutaj
        // (robimy to w ShipmentSyncService przez /order/carriers/{carrierId}/tracking).
        if ($this->looksLikeWaybill($candidateId)) {
            return null;
        }

        // Jeśli to UUID – może być shipmentId lub commandId. Spróbuj najpierw jako shipmentId.
        if ($this->looksLikeUuid($candidateId)) {
            $asShipment = $this->api->get($account, '/shipment-management/shipments/' . rawurlencode($candidateId));
            if (!empty($debugLines)) {
                $debugLines[] = '[API] GET /shipment-management/shipments/{id}: HTTP ' . (int)($asShipment['code'] ?? 0) . ', ok=' . (!empty($asShipment['ok']) ? '1' : '0');
            }
            if (!empty($asShipment['ok'])) {
                return $candidateId;
            }

            // Jeśli nie jest shipmentem, spróbuj rozwiązać jako commandId
            $fromCmd = $this->api->get($account, '/shipment-management/shipments/create-commands/' . rawurlencode($candidateId));
            if (!empty($debugLines)) {
                $debugLines[] = '[API] GET /shipment-management/shipments/create-commands/{id}: HTTP ' . (int)($fromCmd['code'] ?? 0) . ', ok=' . (!empty($fromCmd['ok']) ? '1' : '0');
            }
            if ($fromCmd['ok'] && is_array($fromCmd['json'])) {
                $shipmentId = trim((string)($fromCmd['json']['shipmentId'] ?? ''));
                if ($shipmentId !== '' && $this->looksLikeShipmentId($shipmentId)) {
                    if (!empty($debugLines)) {
                        $debugLines[] = '[SYNC] resolve commandId -> shipmentId: ' . $candidateId . ' => ' . $shipmentId;
                    }
                    return $shipmentId;
                }
            }

            return null;
        }

        // Dla tokenów (np. base64 commandId)
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

        $shipmentId = trim((string)($resp['json']['shipmentId'] ?? ''));
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

    public function extractCarrierIdFromRow(array $row): ?string
    {
        $candidates = [
            $row['carrierId'] ?? null,
            $row['carrier_id'] ?? null,
            $row['carrier']['id'] ?? null,
        ];
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return strtoupper(trim($candidate));
            }
        }
        return null;
    }

    public function resolveShipmentIdByWaybill(array $account, string $waybill, array &$debugLines = []): ?string
    {
        // UWAGA: /shipment-management/shipments nie jest publicznie udokumentowane jako wyszukiwarka po waybill.
        // W praktyce często zwraca 406/4xx. Zostawiamy dla kompatybilności (best effort).
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

        // Preferuj UUID (shipment) – bo command UUID rozstrzygamy testowym GET w resolveShipmentIdCandidate()
        foreach ($refs as $candidate) {
            if ($this->looksLikeShipmentId($candidate)) {
                return $candidate;
            }
        }

        // A potem tokeny (command)
        foreach ($refs as $candidate) {
            if ($this->looksLikeCreateCommandId($candidate)) {
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

        // Jawny prefiks
        if (stripos($value, 'ALLEGRO:') === 0) {
            return trim(substr($value, 8));
        }

        // base64("ALLEGRO:WAYBILL") albo base64(UUID/commandId)
        if (preg_match('/^[A-Za-z0-9+\/]+=*$/', $value) && strlen($value) % 4 === 0) {
            $decoded = base64_decode($value, true);
            if (is_string($decoded) && $decoded !== '') {
                $decoded = trim($decoded);
                if (stripos($decoded, 'ALLEGRO:') === 0) {
                    return trim(substr($decoded, 8));
                }
                return $decoded;
            }
        }

        return $value;
    }

    private function looksLikeUuid(string $value): bool
    {
        return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    private function looksLikeWaybill(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        // typowe numery nadania: zaczynają się od liter/cyfr, bez spacji
        // (np. AD0..., A003..., 6209...)
        if (preg_match('/\s/', $value)) {
            return false;
        }

        // Jeśli wygląda jak UUID lub token – to nie waybill
        if ($this->looksLikeUuid($value)) {
            return false;
        }
        if ((bool)preg_match('/^[A-Za-z0-9+\/]+=*$/', $value) && strlen($value) >= 16 && (strlen($value) % 4 === 0)) {
            // base64 – to raczej token, nie numer nadania
            return false;
        }

        // Waybill zwykle ma długość 8-32 znaków
        $len = strlen($value);
        return $len >= 8 && $len <= 64;
    }
}
