<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

class ShipmentRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_shipment';
    }

    /**
     * Tworzy nowy wpis lub aktualizuje istniejący
     * FIX: Dostosowano do istniejącej struktury tabeli (brak kolumny command_id)
     */
    public function upsert(int $accountId, string $checkoutFormId, string $commandId, array $payload): void
    {
        // Decydujemy, co jest naszym identyfikatorem.
        // Jeśli mamy finalne shipmentId z API, używamy go. Jeśli nie, używamy commandId.
        $finalId = !empty($payload['shipmentId']) ? $payload['shipmentId'] : $commandId;

        // Sprawdzamy czy wpis już istnieje (szukamy po shipment_id)
        $sql = 'SELECT id_allegropro_shipment FROM `'.$this->table.'`
                WHERE id_allegropro_account='.(int)$accountId."
                AND shipment_id='".pSQL($finalId)."'";

        $existing = Db::getInstance()->getValue($sql);

        // Mapowanie danych na istniejące kolumny w Twojej bazie
        // Zgodnie ze zrzutem ekranu: tracking_number, carrier_mode, size_details, is_smart, status, created_at, updated_at
        $row = [
            'id_allegropro_account' => (int)$accountId,
            'checkout_form_id' => pSQL($checkoutFormId),

            // Zapisujemy ID (To jest kluczowe pole z Twojej tabeli)
            'shipment_id' => pSQL($finalId),

            'status' => isset($payload['status']) ? pSQL((string)$payload['status']) : 'NEW',
            'is_smart' => isset($payload['is_smart']) ? (int)$payload['is_smart'] : 0,

            // Mapowanie size_type (A/B/C) na odpowiednie kolumny
            'carrier_mode' => isset($payload['size_type']) && in_array($payload['size_type'], ['A','B','C']) ? 'BOX' : 'COURIER',
            'size_details' => isset($payload['size_type']) ? pSQL((string)$payload['size_type']) : 'CUSTOM',

            'updated_at' => pSQL(date('Y-m-d H:i:s')),
        ];

        if ($existing) {
            Db::getInstance()->update('allegropro_shipment', $row, 'id_allegropro_shipment='.(int)$existing);
        } else {
            $row['created_at'] = pSQL(date('Y-m-d H:i:s'));
            // Domyślne wartości dla kolumn, które mogą nie być w payloadzie
            $row['tracking_number'] = '';
            $row['label_path'] = null;

            Db::getInstance()->insert('allegropro_shipment', $row);
        }
    }

    /**
     * Pobiera pełną historię przesyłek dla danego zamówienia
     */
    public function findAllByOrder(string $checkoutFormId): array
    {
        $q = new DbQuery();
        $q->select('*');
        $q->from('allegropro_shipment');
        $q->where("checkout_form_id = '" . pSQL($checkoutFormId) . "'");
        $q->orderBy('created_at DESC');

        $results = Db::getInstance()->executeS($q);
        return $results ?: [];
    }

    /**
     * Aktualizuje status konkretnej przesyłki
     */
    public function updateStatus(string $shipmentId, string $newStatus): void
    {
        Db::getInstance()->update(
            'allegropro_shipment',
            ['status' => pSQL($newStatus), 'updated_at' => date('Y-m-d H:i:s')],
            "shipment_id = '" . pSQL($shipmentId) . "'"
        );
    }

    /**
     * Zwraca unikalne ID przesyłek zapisanych lokalnie dla zamówienia.
     */
    public function getOrderShipmentIds(int $accountId, string $checkoutFormId): array
    {
        $rows = Db::getInstance()->executeS(
            'SELECT shipment_id FROM `' . $this->table . '` '
            . 'WHERE id_allegropro_account=' . (int)$accountId
            . " AND checkout_form_id='" . pSQL($checkoutFormId) . "'"
            . ' AND shipment_id IS NOT NULL AND shipment_id != ""'
        ) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $id = (string)($r['shipment_id'] ?? '');
            if ($id !== '') {
                $out[$id] = true;
            }
        }

        return array_keys($out);
    }

    /**
     * TTL guard - ogranicza zbyt częste synchronizacje.
     */
    public function shouldSyncOrder(int $accountId, string $checkoutFormId, int $ttlSeconds): bool
    {
        $ttlSeconds = max(0, (int)$ttlSeconds);
        if ($ttlSeconds === 0) {
            return true;
        }

        $last = Db::getInstance()->getValue(
            'SELECT MAX(updated_at) FROM `' . $this->table . '` '
            . 'WHERE id_allegropro_account=' . (int)$accountId
            . " AND checkout_form_id='" . pSQL($checkoutFormId) . "'"
        );

        if (!$last) {
            return true;
        }

        $lastTs = strtotime((string)$last);
        if ($lastTs === false) {
            return true;
        }

        return (time() - $lastTs) >= $ttlSeconds;
    }

    /**
     * Upsert danych przesyłki pobranych z Allegro (status/tracking/smart).
     */
    public function upsertFromAllegro(
        int $accountId,
        string $checkoutFormId,
        string $shipmentId,
        ?string $status,
        ?string $trackingNumber,
        ?int $isSmart,
        ?string $carrierMode,
        ?string $sizeDetails,
        ?string $createdAt = null
    ): void {
        $shipmentId = trim($shipmentId);
        if ($shipmentId === '') {
            return;
        }

        $q = new DbQuery();
        $q->select('id_allegropro_shipment');
        $q->from('allegropro_shipment');
        $q->where('id_allegropro_account=' . (int)$accountId);
        $q->where("shipment_id='" . pSQL($shipmentId) . "'");
        $existingId = (int)Db::getInstance()->getValue($q);

        $row = [
            'id_allegropro_account' => (int)$accountId,
            'checkout_form_id' => pSQL($checkoutFormId),
            'shipment_id' => pSQL($shipmentId),
            'status' => pSQL((string)($status ?: 'NEW')),
            'tracking_number' => pSQL((string)($trackingNumber ?: '')),
            'is_smart' => $isSmart === null ? 0 : (int)$isSmart,
            'carrier_mode' => pSQL((string)($carrierMode ?: 'COURIER')),
            'size_details' => pSQL((string)($sizeDetails ?: 'CUSTOM')),
            'updated_at' => pSQL(date('Y-m-d H:i:s')),
        ];

        if ($existingId > 0) {
            Db::getInstance()->update('allegropro_shipment', $row, 'id_allegropro_shipment=' . $existingId);
            return;
        }

        $createdAt = is_string($createdAt) ? trim($createdAt) : '';
        if ($createdAt === '') {
            $createdAt = date('Y-m-d H:i:s');
        }

        $row['created_at'] = pSQL($createdAt);
        $row['label_path'] = null;
        Db::getInstance()->insert('allegropro_shipment', $row);
    }
}
