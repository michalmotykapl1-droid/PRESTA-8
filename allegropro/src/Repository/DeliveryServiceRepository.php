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
        $dmId = (string)($service['deliveryMethodId'] ?? '');
        $dsId = (string)($service['id'] ?? '');
        if (!$dmId || !$dsId) return;

        $existing = Db::getInstance()->getValue(
            'SELECT id_allegropro_delivery_service FROM `'.$this->table.'` WHERE id_allegropro_account='.(int)$accountId." AND delivery_method_id='".pSQL($dmId)."'"
        );

        $row = [
            'id_allegropro_account' => (int)$accountId,
            'delivery_method_id' => pSQL($dmId),
            'delivery_service_id' => pSQL($dsId),
            'credentials_id' => isset($service['credentials']['id']) ? pSQL((string)$service['credentials']['id']) : null,
            'name' => isset($service['name']) ? pSQL((string)$service['name']) : null,
            'carrier_id' => isset($service['carrierId']) ? pSQL((string)$service['carrierId']) : null,
            'owner' => isset($service['owner']) ? pSQL((string)$service['owner']) : null,
            'additional_properties_json' => isset($service['additionalProperties']) ? pSQL(json_encode($service['additionalProperties'], JSON_UNESCAPED_UNICODE), true) : null,
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
}
