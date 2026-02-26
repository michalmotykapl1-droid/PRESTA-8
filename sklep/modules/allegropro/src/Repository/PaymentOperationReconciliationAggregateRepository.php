<?php
namespace AllegroPro\Repository;

use Db;

/**
 * Agregacje pomocnicze do widoku "Rozliczenie".
 *
 * Uwaga: Allegro w ramach payment-operations potrafi zwracać różne warianty typów opłat.
 * Najczęściej spotykane są DEDUCTION_CHARGE oraz REFUND_CHARGE, ale w praktyce mogą pojawiać się też
 * inne typy kończące się na "CHARGE".
 *
 * Dodatkowo: w zależności od sposobu zapisu w cache (oraz ewentualnych zmian po stronie Allegro)
 * kwota "amount" może występować jako dodatnia lub ujemna. Dlatego w agregacjach per payment.id
 * nie opieramy się wyłącznie na znaku kwoty – priorytetem jest op_type, a kwotę liczymy jako ABS(amount).
 */
class PaymentOperationReconciliationAggregateRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_payment_operation';
    }

    /**
     * @param array<int,string> $paymentIds
     * @return array<string,array{deduction:float,refund_charge:float,first_at:?string,last_at:?string}>
     */
    public function sumChargesByPaymentIds(int $accountId, array $paymentIds): array
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
            SUM(
                CASE
                    WHEN op_type='DEDUCTION_CHARGE' THEN ABS(amount)
                    WHEN op_type LIKE '%CHARGE' AND (op_group='OUTCOME' OR amount < 0) THEN ABS(amount)
                    ELSE 0
                END
            ) AS deduction,
            SUM(
                CASE
                    WHEN op_type='REFUND_CHARGE' THEN ABS(amount)
                    WHEN op_type LIKE '%CHARGE' AND (op_group='REFUND' OR amount > 0) THEN ABS(amount)
                    ELSE 0
                END
            ) AS refund_charge,
            MIN(CASE WHEN op_type LIKE '%CHARGE' THEN occurred_at ELSE NULL END) AS first_at,
            MAX(CASE WHEN op_type LIKE '%CHARGE' THEN occurred_at ELSE NULL END) AS last_at
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
                'deduction' => (float)($r['deduction'] ?? 0),
                'refund_charge' => (float)($r['refund_charge'] ?? 0),
                'first_at' => !empty($r['first_at']) ? (string)$r['first_at'] : null,
                'last_at' => !empty($r['last_at']) ? (string)$r['last_at'] : null,
            ];
        }

        return $out;
    }
}
