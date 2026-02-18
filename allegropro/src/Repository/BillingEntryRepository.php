<?php
namespace AllegroPro\Repository;

use Db;

class BillingEntryRepository
{
    public function ensureSchema(): void
    {
        $p = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;

        // create table if missing
        $sql = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_billing_entry` (
            `id_allegropro_billing_entry` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_allegropro_account` INT UNSIGNED NOT NULL,
            `billing_entry_id` VARCHAR(64) NOT NULL,
            `occurred_at` DATETIME NOT NULL,
            `type_id` VARCHAR(16) NULL,
            `type_name` VARCHAR(255) NULL,
            `offer_id` VARCHAR(64) NULL,
            `offer_name` VARCHAR(512) NULL,
            `order_id` VARCHAR(64) NULL,
            `value_amount` DECIMAL(20,2) NOT NULL DEFAULT 0.00,
            `value_currency` VARCHAR(3) NOT NULL DEFAULT 'PLN',
            `balance_amount` DECIMAL(20,2) NULL,
            `balance_currency` VARCHAR(3) NULL,
            `tax_percentage` DECIMAL(10,2) NULL,
            `tax_annotation` VARCHAR(32) NULL,
            `raw_json` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_allegropro_billing_entry`),
            UNIQUE KEY `uniq_billing_entry` (`billing_entry_id`),
            KEY `idx_acc_date` (`id_allegropro_account`,`occurred_at`),
            KEY `idx_order_id` (`order_id`),
            KEY `idx_offer_id` (`offer_id`)
        ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4;";

        Db::getInstance()->execute($sql);
    }

    /**
     * Upsert billing entries.
     * @return array{inserted:int,updated:int}
     */
    public function upsertEntries(int $accountId, array $entries): array
    {
        $inserted = 0;
        $updated = 0;

        foreach ($entries as $e) {
            if (!is_array($e) || empty($e['id'])) {
                continue;
            }

            $billingId = pSQL((string)$e['id']);
            $occurredAt = $this->toMysqlDatetime((string)($e['occurredAt'] ?? ''));
            if ($occurredAt === null) {
                continue;
            }

            $typeId = pSQL((string)($e['type']['id'] ?? ''));
            $typeName = pSQL((string)($e['type']['name'] ?? ''));
            $offerId = pSQL((string)($e['offer']['id'] ?? ''));
            $offerName = pSQL((string)($e['offer']['name'] ?? ''));
            $orderId = pSQL((string)($e['order']['id'] ?? ''));

            // czasem orderId bywa w additionalInfo
            if ($orderId === '' && !empty($e['additionalInfo']) && is_array($e['additionalInfo'])) {
                foreach ($e['additionalInfo'] as $ai) {
                    if (is_array($ai) && ($ai['type'] ?? '') === 'orderId' && !empty($ai['value'])) {
                        $orderId = pSQL((string)$ai['value']);
                        break;
                    }
                }
            }

            $valAmount = (float)($e['value']['amount'] ?? 0);
            $valCurrency = pSQL((string)($e['value']['currency'] ?? 'PLN'));
            $balAmount = isset($e['balance']['amount']) ? (float)$e['balance']['amount'] : null;
            $balCurrency = pSQL((string)($e['balance']['currency'] ?? ''));
            $taxPerc = isset($e['tax']['percentage']) ? (float)$e['tax']['percentage'] : null;
            $taxAnn = pSQL((string)($e['tax']['annotation'] ?? ''));

            $raw = pSQL(json_encode($e, JSON_UNESCAPED_UNICODE));
            $now = date('Y-m-d H:i:s');

            // czy istnieje?
            $exists = (int)Db::getInstance()->getValue('SELECT id_allegropro_billing_entry FROM `' . _DB_PREFIX_ . 'allegropro_billing_entry` WHERE billing_entry_id = "' . $billingId . '"');

            $row = [
                'id_allegropro_account' => (int)$accountId,
                'billing_entry_id' => $billingId,
                'occurred_at' => $occurredAt,
                'type_id' => $typeId ?: null,
                'type_name' => $typeName ?: null,
                'offer_id' => $offerId ?: null,
                'offer_name' => $offerName ?: null,
                'order_id' => $orderId ?: null,
                'value_amount' => (float)$valAmount,
                'value_currency' => $valCurrency ?: 'PLN',
                'balance_amount' => $balAmount,
                'balance_currency' => $balCurrency ?: null,
                'tax_percentage' => $taxPerc,
                'tax_annotation' => $taxAnn ?: null,
                'raw_json' => $raw ?: null,
                'updated_at' => $now,
            ];

            if ($exists) {
                Db::getInstance()->update('allegropro_billing_entry', $row, 'id_allegropro_billing_entry = ' . (int)$exists);
                $updated++;
            } else {
                $row['created_at'] = $now;
                Db::getInstance()->insert('allegropro_billing_entry', $row);
                $inserted++;
            }
        }

        return ['inserted' => $inserted, 'updated' => $updated];
    }

    public function getCategorySums(int $accountId, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $sql = "SELECT
            SUM(value_amount) AS total,
            SUM(CASE WHEN type_id='SUC' OR type_name LIKE '%Prowizja%' THEN value_amount ELSE 0 END) AS commission,
            SUM(CASE WHEN type_name LIKE '%SMART%' OR type_name LIKE '%Smart%' THEN value_amount ELSE 0 END) AS smart,
            SUM(CASE WHEN type_name LIKE '%Dostaw%' OR type_name LIKE '%przesy%' THEN value_amount ELSE 0 END) AS delivery,
            SUM(CASE WHEN type_name LIKE '%Promow%' OR type_name LIKE '%reklam%' THEN value_amount ELSE 0 END) AS promotion,
            SUM(CASE WHEN value_amount > 0 OR type_name LIKE '%Zwrot%' THEN value_amount ELSE 0 END) AS refunds
        FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
        WHERE id_allegropro_account=" . (int)$accountId . "
          AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'";

        $row = Db::getInstance()->getRow($sql) ?: [];
        return [
            'total' => (float)($row['total'] ?? 0),
            'commission' => (float)($row['commission'] ?? 0),
            'smart' => (float)($row['smart'] ?? 0),
            'delivery' => (float)($row['delivery'] ?? 0),
            'promotion' => (float)($row['promotion'] ?? 0),
            'refunds' => (float)($row['refunds'] ?? 0),
        ];
    }

    public function sumByOrder(int $accountId, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $sql = "SELECT order_id, SUM(value_amount) AS sum_amount
                FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND order_id IS NOT NULL AND order_id <> ''
                  AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                GROUP BY order_id";

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $map[(string)$r['order_id']] = (float)$r['sum_amount'];
        }
        return $map;
    }

    public function listForOrder(int $accountId, string $orderId, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');
        $orderId = pSQL($orderId);

        $sql = "SELECT *
                FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND order_id='" . $orderId . "'
                  AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                ORDER BY occurred_at DESC";

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function countUnassigned(int $accountId, string $dateFrom, string $dateTo): int
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $sql = "SELECT COUNT(*)
                FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND (order_id IS NULL OR order_id='')
                  AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'";

        return (int)Db::getInstance()->getValue($sql);
    }

    private function toMysqlDatetime(string $iso): ?string
    {
        if ($iso === '') {
            return null;
        }
        // 2026-02-16T14:12:10.453Z
        $iso = str_replace('T', ' ', $iso);
        $iso = str_replace('Z', '', $iso);
        // usuń ms
        $iso = preg_replace('/\.[0-9]+$/', '', $iso);
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $iso)) {
            return null;
        }
        return $iso;
    }
}
