<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

/**
 * Widok "Rozliczenie" (per checkoutFormId) – łączy płatności (order_payment) z order.status.
 * Zakres dat oparty o finished_at (data płatności kupującego).
 */
class CashflowsReconciliationRepository
{
    /**
     * KPI liczone dla CAŁEGO zakresu (nie tylko dla aktualnej strony listy).
     *
     * Uwaga: cashflow + opłaty/zwroty opłat są wiązane po payment_id i sumowane bez ograniczenia occurred_at,
     * tak jak w tabeli rozliczenia (operation mogła się wydarzyć później niż finished_at).
     *
     * @return array{count:int,sum_paid:float,sum_cashflow:float,sum_cashflow_waiting:float,sum_cashflow_available:float,sum_fee:float,sum_fee_refund:float,sum_net:float,issues:int,issues_missing_cashflow:int,issues_cashflow_diff:int,issues_missing_refund:int}
     */
    public function kpiTotal(int $accountId, string $dateFrom, string $dateTo, array $filters = []): array
    {
        $out = [
            'count' => 0,
            'sum_paid' => 0.0,
            'sum_cashflow' => 0.0,
            'sum_cashflow_waiting' => 0.0,
            'sum_cashflow_available' => 0.0,
            'sum_fee' => 0.0,
            'sum_fee_refund' => 0.0,
            'sum_net' => 0.0,
            'issues' => 0,
            'issues_missing_cashflow' => 0,
            'issues_cashflow_diff' => 0,
            'issues_missing_refund' => 0,
        ];

        if ($accountId <= 0) {
            return $out;
        }

        $start = pSQL($dateFrom . ' 00:00:00');
        $end = pSQL($dateTo . ' 23:59:59');

        $buyerLogin = trim((string)($filters['buyer_login'] ?? ''));
        $paymentId = trim((string)($filters['payment_id'] ?? ''));
        $orderStatus = trim((string)($filters['order_status'] ?? ''));

        $tOrder = _DB_PREFIX_ . 'allegropro_order';
        $tPay = _DB_PREFIX_ . 'allegropro_order_payment';
        $tOps = _DB_PREFIX_ . 'allegropro_payment_operation';

        $whereOrder = 'o.id_allegropro_account=' . (int)$accountId;
        if ($buyerLogin !== '') {
            $whereOrder .= " AND o.buyer_login LIKE '%" . pSQL($buyerLogin) . "%'";
        }
        if ($orderStatus !== '') {
            $whereOrder .= " AND o.status='" . pSQL($orderStatus) . "'";
        }

        $wherePay = "op.finished_at >= '{$start}' AND op.finished_at <= '{$end}'";
        if ($paymentId !== '') {
            $wherePay .= " AND op.payment_id='" . pSQL($paymentId) . "'";
        }

        // 1) Płatności agregowane per checkoutFormId
        $subPaid = "
            SELECT
                op.checkout_form_id,
                SUM(op.paid_amount) AS paid
            FROM `{$tPay}` op
            INNER JOIN `{$tOrder}` o ON o.checkout_form_id = op.checkout_form_id
            WHERE {$whereOrder} AND {$wherePay}
            GROUP BY op.checkout_form_id
        ";

        // 2) Cashflow + opłaty/zwroty opłat agregowane per checkoutFormId (wiązanie przez payment_id)
        // Bez ograniczenia occurred_at: chcemy widzieć wszystkie operacje dla payment_id (tak jak w tabeli).
        //
        // Uwaga dot. opłat/zwrotów opłat:
        // W payment-operations opłaty Allegro potrafią mieć różne kombinacje znaków kwoty.
        // Najpewniejsze są typy: DEDUCTION_CHARGE (potrącenie) i REFUND_CHARGE (zwrot opłaty).
        // Dodatkowo zostawiamy fallback po sufiksie "CHARGE" + op_group/znaku kwoty.
        $subOps = "
            SELECT
                op.checkout_form_id,
                SUM(CASE WHEN po.op_group='INCOME' AND po.op_type='CONTRIBUTION' THEN po.amount ELSE 0 END) AS cashflow_total,
                SUM(CASE WHEN po.op_group='INCOME' AND po.op_type='CONTRIBUTION' AND po.wallet_type='WAITING' THEN po.amount ELSE 0 END) AS cashflow_waiting,
                SUM(CASE WHEN po.op_group='INCOME' AND po.op_type='CONTRIBUTION' AND po.wallet_type='AVAILABLE' THEN po.amount ELSE 0 END) AS cashflow_available,
                SUM(
                    CASE
                        WHEN po.op_type='DEDUCTION_CHARGE' THEN ABS(po.amount)
                        WHEN po.op_type='REFUND_CHARGE' THEN 0
                        WHEN po.op_type LIKE '%CHARGE' AND (po.op_group='OUTCOME' OR po.amount < 0) THEN ABS(po.amount)
                        ELSE 0
                    END
                ) AS fee_deduction,
                SUM(
                    CASE
                        WHEN po.op_type='REFUND_CHARGE' THEN ABS(po.amount)
                        WHEN po.op_type='DEDUCTION_CHARGE' THEN 0
                        WHEN po.op_type LIKE '%CHARGE' AND (po.op_group='REFUND' OR po.amount > 0) THEN ABS(po.amount)
                        ELSE 0
                    END
                ) AS fee_refund
            FROM `{$tPay}` op
            INNER JOIN `{$tOrder}` o ON o.checkout_form_id = op.checkout_form_id
            INNER JOIN `{$tOps}` po ON po.id_allegropro_account={$accountId} AND po.payment_id = op.payment_id
            WHERE {$whereOrder} AND {$wherePay}
            GROUP BY op.checkout_form_id
        ";

        // Priorytet problemów zgodny z logiką kontrolera:
        // 1) missing_cashflow, 2) cashflow_diff, 3) missing_refund
        $sql = "
            SELECT
                COUNT(*) AS cnt,
                SUM(t.paid) AS sum_paid,
                SUM(t.cashflow_total) AS sum_cashflow,
                SUM(t.cashflow_waiting) AS sum_cashflow_waiting,
                SUM(t.cashflow_available) AS sum_cashflow_available,
                SUM(t.fee_deduction) AS sum_fee,
                SUM(t.fee_refund) AS sum_fee_refund,
                SUM(t.net) AS sum_net,
                SUM(CASE WHEN t.issue_type<>'' THEN 1 ELSE 0 END) AS issues,
                SUM(CASE WHEN t.issue_type='missing_cashflow' THEN 1 ELSE 0 END) AS issues_missing_cashflow,
                SUM(CASE WHEN t.issue_type='cashflow_diff' THEN 1 ELSE 0 END) AS issues_cashflow_diff,
                SUM(CASE WHEN t.issue_type='missing_refund' THEN 1 ELSE 0 END) AS issues_missing_refund
            FROM (
                SELECT
                    p.checkout_form_id,
                    p.paid,
                    COALESCE(a.cashflow_total,0) AS cashflow_total,
                    COALESCE(a.cashflow_waiting,0) AS cashflow_waiting,
                    COALESCE(a.cashflow_available,0) AS cashflow_available,
                    COALESCE(a.fee_deduction,0) AS fee_deduction,
                    COALESCE(a.fee_refund,0) AS fee_refund,
                    (COALESCE(a.cashflow_total,0) - COALESCE(a.fee_deduction,0) + COALESCE(a.fee_refund,0)) AS net,
                    CASE
                        WHEN p.paid > 0.01 AND COALESCE(a.cashflow_total,0) <= 0.01 THEN 'missing_cashflow'
                        WHEN p.paid > 0.01 AND COALESCE(a.cashflow_total,0) > 0.01 AND ABS(p.paid - COALESCE(a.cashflow_total,0)) > 0.02 THEN 'cashflow_diff'
                        WHEN o.status='CANCELLED' AND COALESCE(a.fee_deduction,0) > 0.01 AND (COALESCE(a.fee_refund,0) + 0.01) < COALESCE(a.fee_deduction,0) THEN 'missing_refund'
                        ELSE ''
                    END AS issue_type
                FROM ({$subPaid}) p
                INNER JOIN `{$tOrder}` o ON o.checkout_form_id = p.checkout_form_id
                LEFT JOIN ({$subOps}) a ON a.checkout_form_id = p.checkout_form_id
            ) t
        ";

        $row = Db::getInstance()->getRow($sql);
        if (!is_array($row) || empty($row)) {
            return $out;
        }

        $out['count'] = (int)($row['cnt'] ?? 0);
        $out['sum_paid'] = (float)($row['sum_paid'] ?? 0);
        $out['sum_cashflow'] = (float)($row['sum_cashflow'] ?? 0);
        $out['sum_cashflow_waiting'] = (float)($row['sum_cashflow_waiting'] ?? 0);
        $out['sum_cashflow_available'] = (float)($row['sum_cashflow_available'] ?? 0);
        $out['sum_fee'] = (float)($row['sum_fee'] ?? 0);
        $out['sum_fee_refund'] = (float)($row['sum_fee_refund'] ?? 0);
        $out['sum_net'] = (float)($row['sum_net'] ?? 0);
        $out['issues'] = (int)($row['issues'] ?? 0);
        $out['issues_missing_cashflow'] = (int)($row['issues_missing_cashflow'] ?? 0);
        $out['issues_cashflow_diff'] = (int)($row['issues_cashflow_diff'] ?? 0);
        $out['issues_missing_refund'] = (int)($row['issues_missing_refund'] ?? 0);
        return $out;
    }

    public function countCheckoutForms(int $accountId, string $dateFrom, string $dateTo, array $filters = []): int
    {
        if ($accountId <= 0) {
            return 0;
        }

        $start = pSQL($dateFrom . ' 00:00:00');
        $end = pSQL($dateTo . ' 23:59:59');

        $buyerLogin = trim((string)($filters['buyer_login'] ?? ''));
        $paymentId = trim((string)($filters['payment_id'] ?? ''));
        $orderStatus = trim((string)($filters['order_status'] ?? ''));

        $q = new DbQuery();
        $q->select('COUNT(DISTINCT o.checkout_form_id) AS c')
            ->from('allegropro_order', 'o')
            ->innerJoin('allegropro_order_payment', 'op', 'op.checkout_form_id = o.checkout_form_id')
            ->where('o.id_allegropro_account=' . (int)$accountId)
            ->where("op.finished_at >= '{$start}'")
            ->where("op.finished_at <= '{$end}'");

        if ($buyerLogin !== '') {
            $q->where("o.buyer_login LIKE '%" . pSQL($buyerLogin) . "%' ");
        }
        if ($paymentId !== '') {
            $q->where("op.payment_id='" . pSQL($paymentId) . "'");
        }
        if ($orderStatus !== '') {
            $q->where("o.status='" . pSQL($orderStatus) . "'");
        }

        $row = Db::getInstance()->getRow($q);
        return (int)($row['c'] ?? 0);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findCheckoutFormsPage(int $accountId, string $dateFrom, string $dateTo, array $filters, int $limit, int $offset): array
    {
        if ($accountId <= 0) {
            return [];
        }

        $start = pSQL($dateFrom . ' 00:00:00');
        $end = pSQL($dateTo . ' 23:59:59');

        $buyerLogin = trim((string)($filters['buyer_login'] ?? ''));
        $paymentId = trim((string)($filters['payment_id'] ?? ''));
        $orderStatus = trim((string)($filters['order_status'] ?? ''));

        $q = new DbQuery();
        $q->select('o.checkout_form_id, o.id_order_prestashop, o.buyer_login, o.currency, o.status AS order_status')
            ->select('MAX(op.finished_at) AS finished_at')
            ->select('SUM(op.paid_amount) AS paid_amount')
            ->from('allegropro_order', 'o')
            ->innerJoin('allegropro_order_payment', 'op', 'op.checkout_form_id = o.checkout_form_id')
            ->where('o.id_allegropro_account=' . (int)$accountId)
            ->where("op.finished_at >= '{$start}'")
            ->where("op.finished_at <= '{$end}'");

        if ($buyerLogin !== '') {
            $q->where("o.buyer_login LIKE '%" . pSQL($buyerLogin) . "%' ");
        }
        if ($paymentId !== '') {
            $q->where("op.payment_id='" . pSQL($paymentId) . "'");
        }
        if ($orderStatus !== '') {
            $q->where("o.status='" . pSQL($orderStatus) . "'");
        }

        $q->groupBy('o.checkout_form_id');
        $q->orderBy('finished_at DESC');
        $q->limit((int)$limit, (int)$offset);

        return Db::getInstance()->executeS($q) ?: [];
    }
}
