<?php
namespace AllegroPro\Repository;

use Db;

/**
 * Widok "Opłaty (BILLING)" – audyt opłat/zwrotów na podstawie cache /billing/billing-entries.
 *
 * 1 wiersz = 1 zamówienie (checkoutFormId) na podstawie billing_entry.order_id.
 */
class BillingFeesAuditRepository
{
    /**
     * Zwraca count (liczbę zamówień) po filtrach.
     */
    public function countOrders(int $accountId, string $dateFrom, string $dateTo, array $filters = []): int
    {
        if ($accountId <= 0) {
            return 0;
        }
        $sub = $this->buildGroupedSql($accountId, $dateFrom, $dateTo, $filters, false);
        $sql = 'SELECT COUNT(*) AS c FROM (' . $sub . ') t';
        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * Strona wyników.
     * @return array<int,array<string,mixed>>
     */
    public function findOrdersPage(int $accountId, string $dateFrom, string $dateTo, array $filters, int $limit, int $offset): array
    {
        if ($accountId <= 0) {
            return [];
        }
        $sql = $this->buildGroupedSql($accountId, $dateFrom, $dateTo, $filters, true)
            . ' ORDER BY last_occurred_at DESC '
            . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * KPI dla całego zakresu (po filtrach).
     * @return array{count:int,sum_charge:float,sum_refund:float,sum_net:float,unpaid_charged:int,missing_refund:int,issues:int}
     */
    public function kpi(int $accountId, string $dateFrom, string $dateTo, array $filters = []): array
    {
        if ($accountId <= 0) {
            return ['count' => 0, 'sum_charge' => 0.0, 'sum_refund' => 0.0, 'sum_net' => 0.0, 'unpaid_charged' => 0, 'missing_refund' => 0, 'issues' => 0];
        }

        $sub = $this->buildGroupedSql($accountId, $dateFrom, $dateTo, $filters, true);
        $sql = "SELECT\n"
            . "  COUNT(*) AS c,\n"
            . "  SUM(charge_amount) AS sum_charge,\n"
            . "  SUM(refund_amount) AS sum_refund,\n"
            . "  SUM(net_amount) AS sum_net,\n"
            . "  SUM(CASE WHEN unpaid_charged=1 THEN 1 ELSE 0 END) AS unpaid_charged,\n"
            . "  SUM(CASE WHEN missing_refund=1 THEN 1 ELSE 0 END) AS missing_refund,\n"
            . "  SUM(CASE WHEN issue=1 THEN 1 ELSE 0 END) AS issues\n"
            . "FROM (" . $sub . ") t";

        $row = Db::getInstance()->getRow($sql) ?: [];
        return [
            'count' => (int)($row['c'] ?? 0),
            'sum_charge' => (float)($row['sum_charge'] ?? 0),
            'sum_refund' => (float)($row['sum_refund'] ?? 0),
            'sum_net' => (float)($row['sum_net'] ?? 0),
            'unpaid_charged' => (int)($row['unpaid_charged'] ?? 0),
            'missing_refund' => (int)($row['missing_refund'] ?? 0),
            'issues' => (int)($row['issues'] ?? 0),
        ];
    }

    /**
     * Szczegóły billing-entries do rozwinięcia pod wierszem.
     * @param array<int,string> $orderIds
     * @return array<string,array<int,array<string,mixed>>> map[checkout_form_id] => list(entries)
     */
    public function getEntriesForOrders(int $accountId, array $orderIds, string $dateFrom, string $dateTo): array
    {
        $orderIds = array_values(array_filter(array_map('strval', $orderIds)));
        if ($accountId <= 0 || empty($orderIds)) {
            return [];
        }

        $in = [];
        foreach ($orderIds as $id) {
            $in[] = "'" . pSQL($id) . "'";
        }
        $inSql = implode(',', $in);

        $start = pSQL($dateFrom . ' 00:00:00');
        $end = pSQL($dateTo . ' 23:59:59');

        $sql = 'SELECT be.order_id AS checkout_form_id, be.occurred_at, be.type_id, be.type_name, be.offer_id, be.offer_name, be.payment_id, '
            . 'be.value_amount, be.value_currency '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_billing_entry` be '
            . 'WHERE be.id_allegropro_account=' . (int)$accountId
            . " AND be.occurred_at >= '{$start}' AND be.occurred_at <= '{$end}'"
            . " AND be.order_id IN ({$inSql})"
            . ' ORDER BY be.occurred_at DESC, be.id_allegropro_billing_entry DESC';

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $cf = (string)($r['checkout_form_id'] ?? '');
            if ($cf === '') {
                continue;
            }
            if (!isset($out[$cf])) {
                $out[$cf] = [];
            }
            $out[$cf][] = $r;
        }
        return $out;
    }

    /**
     * Buduje SQL grupujący billing-entries per order_id.
     * Zwraca SELECT, który jest gotowy do opakowania w subquery.
     */
    private function buildGroupedSql(int $accountId, string $dateFrom, string $dateTo, array $filters, bool $includeFlags): string
    {
        $start = pSQL($dateFrom . ' 00:00:00');
        $end = pSQL($dateTo . ' 23:59:59');

        $buyerLogin = trim((string)($filters['buyer_login'] ?? ''));
        $paymentId = trim((string)($filters['payment_id'] ?? ''));
        $alert = trim((string)($filters['alert'] ?? ''));

        $p = _DB_PREFIX_;

        $paidExpr = 'IFNULL(SUM(op.paid_amount),0)';
        $chargeExpr = '(-SUM(CASE WHEN be.value_amount < 0 THEN be.value_amount ELSE 0 END))';
        $refundExpr = '(SUM(CASE WHEN be.value_amount > 0 THEN be.value_amount ELSE 0 END))';

        $missingRefundCond = "(MAX(o.status)='CANCELLED' AND {$chargeExpr}>0.01 AND ({$refundExpr}+0.01)<{$chargeExpr})";
        $unpaidChargedCond = "({$paidExpr}<=0.01 AND {$chargeExpr}>0.01)";
        $issuesCond = "({$unpaidChargedCond} OR {$missingRefundCond})";

        $selectFlags = '';
        if ($includeFlags) {
            $selectFlags = ",\n  CASE WHEN {$unpaidChargedCond} THEN 1 ELSE 0 END AS unpaid_charged,\n"
                . "  CASE WHEN {$missingRefundCond} THEN 1 ELSE 0 END AS missing_refund,\n"
                . "  CASE WHEN {$issuesCond} THEN 1 ELSE 0 END AS issue";
        }

        $where = "be.id_allegropro_account=" . (int)$accountId
            . " AND be.occurred_at >= '{$start}' AND be.occurred_at <= '{$end}'"
            . " AND be.order_id IS NOT NULL AND be.order_id <> ''";

        if ($paymentId !== '') {
            $where .= " AND be.payment_id='" . pSQL($paymentId) . "'";
        }
        if ($buyerLogin !== '') {
            $where .= " AND o.buyer_login LIKE '%" . pSQL($buyerLogin) . "%'";
        }

        $having = '';
        if ($alert === 'issues') {
            $having = ' HAVING ' . $issuesCond;
        } elseif ($alert === 'unpaid_charged') {
            $having = ' HAVING ' . $unpaidChargedCond;
        } elseif ($alert === 'missing_refund') {
            $having = ' HAVING ' . $missingRefundCond;
        }

        $sql = "SELECT\n"
            . "  be.order_id AS checkout_form_id,\n"
            . "  MAX(o.id_order_prestashop) AS id_order_prestashop,\n"
            . "  MAX(o.buyer_login) AS buyer_login,\n"
            . "  MAX(o.status) AS order_status,\n"
            . "  MAX(op.finished_at) AS finished_at,\n"
            . "  {$paidExpr} AS paid_amount,\n"
            . "  {$chargeExpr} AS charge_amount,\n"
            . "  {$refundExpr} AS refund_amount,\n"
            . "  ({$refundExpr} - {$chargeExpr}) AS net_amount,\n"
            . "  MAX(be.occurred_at) AS last_occurred_at,\n"
            . "  COUNT(*) AS entry_count"
            . $selectFlags
            . "\nFROM `{$p}allegropro_billing_entry` be\n"
            . "LEFT JOIN `{$p}allegropro_order` o ON o.checkout_form_id = be.order_id AND o.id_allegropro_account=" . (int)$accountId . "\n"
            . "LEFT JOIN `{$p}allegropro_order_payment` op ON op.checkout_form_id = be.order_id\n"
            . "WHERE {$where}\n"
            . "GROUP BY be.order_id"
            . $having;

        return $sql;
    }
}
