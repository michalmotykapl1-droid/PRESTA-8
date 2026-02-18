<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

class DeliveryServiceRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_delivery_service';
    }

    public function upsert(int $accountId, array $service): void
    {
        // API /shipment-management/delivery-services
        // Zależnie od wersji dokumentacji/zmian Allegro, pojedynczy rekord może wyglądać np.:
        // 1) {"id": {"deliveryMethodId": "...", "credentialsId": "..."}, "carrierId": "INPOST", ...}
        // 2) starsze warianty: {"deliveryMethodId": "...", "credentials": {"id": "..."}, "additionalProperties": {...}}
        $idObj = $service['id'] ?? null;

        $dmId = '';
        if (isset($service['deliveryMethodId'])) {
            $dmId = (string)$service['deliveryMethodId'];
        } elseif (is_array($idObj) && isset($idObj['deliveryMethodId'])) {
            $dmId = (string)$idObj['deliveryMethodId'];
        } elseif (is_array($service['deliveryMethod'] ?? null) && isset($service['deliveryMethod']['id'])) {
            $dmId = (string)$service['deliveryMethod']['id'];
        }

        $credentialsId = null;
        if (isset($service['credentialsId'])) {
            $credentialsId = (string)$service['credentialsId'];
        } elseif (is_array($idObj) && isset($idObj['credentialsId'])) {
            $credentialsId = (string)$idObj['credentialsId'];
        } elseif (is_array($service['credentials'] ?? null) && isset($service['credentials']['id'])) {
            $credentialsId = (string)$service['credentials']['id'];
        }

        // "delivery_service_id" trzymamy jako stabilny identyfikator techniczny w DB.
        // W nowym kształcie API nie ma jednego stringowego id – jest para (deliveryMethodId, credentialsId).
        $dsId = $dmId;
        // Zachowujemy kompatybilność ze schematem DB (często VARCHAR(64)) – nie doklejamy credentialsId do delivery_service_id.
        // Dla nowych odpowiedzi API identyfikatorem jest para (deliveryMethodId, credentialsId), ale credentialsId trzymamy osobno w kolumnie credentials_id.
        if (isset($service['id']) && is_string($service['id']) && $service['id'] !== '') {
            $dsId = (string)$service['id'];
        }

        if (!$dmId || !$dsId) {
            return;
        }

        $existing = Db::getInstance()->getValue(
            'SELECT id_allegropro_delivery_service FROM `'.$this->table.'` WHERE id_allegropro_account='.(int)$accountId." AND delivery_method_id='".pSQL($dmId)."'"
        );

        $row = [
            'id_allegropro_account' => (int)$accountId,
            'delivery_method_id' => pSQL($dmId),
            'delivery_service_id' => pSQL($dsId),
            'credentials_id' => ($credentialsId && strtolower($credentialsId) !== 'null') ? pSQL((string)$credentialsId) : null,
            'name' => isset($service['name']) ? pSQL((string)$service['name']) : null,
            'carrier_id' => isset($service['carrierId']) ? pSQL((string)$service['carrierId']) : null,
            'owner' => isset($service['owner']) ? pSQL((string)$service['owner']) : null,
            'additional_properties_json' => isset($service['additionalProperties'])
                ? pSQL(json_encode($service['additionalProperties'], JSON_UNESCAPED_UNICODE), true)
                : (isset($service['additional_properties']) ? pSQL(json_encode($service['additional_properties'], JSON_UNESCAPED_UNICODE), true) : null),
            'updated_at' => pSQL(date('Y-m-d H:i:s')),
        ];

        if ($existing) {
            Db::getInstance()->update('allegropro_delivery_service', $row, 'id_allegropro_delivery_service='.(int)$existing);
        } else {
            Db::getInstance()->insert('allegropro_delivery_service', $row);
        }
    }

    public function findByDeliveryMethod(int $accountId, string $deliveryMethodId): ?array
    {
        $q = new DbQuery();
        $q->select('*')->from('allegropro_delivery_service')
            ->where('id_allegropro_account='.(int)$accountId)
            ->where("delivery_method_id='".pSQL($deliveryMethodId)."'");
        $row = Db::getInstance()->getRow($q);
        return $row ?: null;
    }

    public function countForAccount(int $accountId): int
    {
        return (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM `'.$this->table.'` WHERE id_allegropro_account='.(int)$accountId);
    }

    /**
     * Zwraca przykładową usługę dla danego przewoźnika z niepustym credentials_id (jeśli istnieje).
     * Używane jako fallback, gdy nie potrafimy zmapować deliveryMethodId z zamówienia.
     */
    public function findFirstWithCredentialsByCarrier(int $accountId, string $carrierId): ?array
    {
        $carrierId = strtoupper(trim($carrierId));
        if ($carrierId === '') {
            return null;
        }
        $q = new DbQuery();
        $q->select('*')
            ->from('allegropro_delivery_service')
            ->where('id_allegropro_account='.(int)$accountId)
            ->where("carrier_id='".pSQL($carrierId)."'")
            ->where("credentials_id IS NOT NULL AND credentials_id != ''")
            ->orderBy('updated_at DESC');
        $row = Db::getInstance()->getRow($q);
        return $row ?: null;
    }

    /**
     * Szybkie statystyki w debug: ile usług ma dany carrier i ile z nich ma credentials_id.
     */
    public function getCarrierStats(int $accountId, string $carrierId): array
    {
        $carrierId = strtoupper(trim($carrierId));
        if ($carrierId === '') {
            return ['total' => 0, 'with_credentials' => 0];
        }
        $total = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'.$this->table.'` WHERE id_allegropro_account='.(int)$accountId." AND carrier_id='".pSQL($carrierId)."'"
        );
        $withCred = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'.$this->table.'` WHERE id_allegropro_account='.(int)$accountId." AND carrier_id='".pSQL($carrierId)."' AND credentials_id IS NOT NULL AND credentials_id != ''"
        );
        return ['total' => $total, 'with_credentials' => $withCred];
    }

    /**
     * Zwraca unikalną listę carrier_id skonfigurowanych dla konta.
     * Przydatne do fallbacków (np. tracking po waybill, gdy carrier z zamówienia jest błędny).
     *
     * @return string[]
     */
    public function listCarrierIdsForAccount(int $accountId): array
    {
        if ($accountId <= 0) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT DISTINCT carrier_id FROM `' . $this->table . '` '
            . 'WHERE id_allegropro_account=' . (int)$accountId
            . " AND carrier_id IS NOT NULL AND carrier_id != ''"
            . ' ORDER BY carrier_id ASC'
        ) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $cid = strtoupper(trim((string)($r['carrier_id'] ?? '')));
            if ($cid === '') {
                continue;
            }
            $out[$cid] = true;
        }

        return array_keys($out);
    }
}
