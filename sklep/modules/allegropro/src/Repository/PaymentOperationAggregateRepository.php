<?php
namespace AllegroPro\Repository;

use Db;

/**
 * Agregacje na cache {prefix}_allegropro_payment_operation.
 * Na start używane do widoku "Wpłaty transakcji" (sumy INCOME/CONTRIBUTION per payment_id).
 */
class PaymentOperationAggregateRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_payment_operation';
    }

    /**
     * @param array<int,string> $paymentIds
     * @return array<string,array{available:float,waiting:float,total:float,first_at:?string,last_at:?string}>
     */
    public function sumContributionsByPaymentIds(int $accountId, array $paymentIds): array
    {
        $accountId = (int)$accountId;
        $paymentIds = array_values(array_filter(array_map('strval', $paymentIds)));
        if ($accountId <= 0 || empty($paymentIds)) {
            return [];
        }

        $in = [];
        foreach ($paymentIds as $pid) {
            $in[] = "'" . pSQL($pid) . "'";
        }
        $inSql = implode(',', $in);

        $sql = "SELECT
            payment_id,
            SUM(CASE WHEN op_group='INCOME' AND op_type='CONTRIBUTION' AND wallet_type='AVAILABLE' THEN amount ELSE 0 END) AS available,
            SUM(CASE WHEN op_group='INCOME' AND op_type='CONTRIBUTION' AND wallet_type='WAITING' THEN amount ELSE 0 END) AS waiting,
            SUM(CASE WHEN op_group='INCOME' AND op_type='CONTRIBUTION' THEN amount ELSE 0 END) AS total,
            MIN(occurred_at) AS first_at,
            MAX(occurred_at) AS last_at
        FROM `{$this->table}`
        WHERE id_allegropro_account={$accountId}
          AND payment_id IN ({$inSql})
        GROUP BY payment_id";

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $pid = (string)($r['payment_id'] ?? '');
            if ($pid === '') {
                continue;
            }
            $out[$pid] = [
                'available' => (float)($r['available'] ?? 0),
                'waiting' => (float)($r['waiting'] ?? 0),
                'total' => (float)($r['total'] ?? 0),
                'first_at' => !empty($r['first_at']) ? (string)$r['first_at'] : null,
                'last_at' => !empty($r['last_at']) ? (string)$r['last_at'] : null,
            ];
        }

        return $out;
    }
}
