<?php
namespace AllegroPro\Repository;

use Db;

/**
 * Widok "Opłaty (BILLING)" (per checkoutFormId / order_id) – dane z cache billing-entries.
 *
 * Źródło: {prefix}_allegropro_billing_entry (occurred_at, value_amount, order_id, payment_id)
 * + join do: {prefix}_allegropro_order (status, buyer_login, id_order_prestashop)
 * + join do: {prefix}_allegropro_order_payment (paid_amount)
 */
class CashflowsBillingRepository
{
    /**
     * @param array{buyer_login?:string,payment_id?:string,alert?:string} $filters
     */
    public function countOrders(int $accountId, string $dateFrom, string $dateTo, array $filters = []): int
    {
        if ($accountId <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS c\n"
            . "FROM (" . $this->baseAggSql($accountId, $dateFrom, $dateTo, $filters) . ") t\n"
            . $this->joinsSql($accountId)
            . $this->whereSql($filters);

        $v = Db::getInstance()->getValue($sql);
        return (int)$v;
    }

    /**
     * @param array{buyer_login?:string,payment_id?:string,alert?:string} $filters
     * @return array<int,array<string,mixed>>
     */
    public function findOrdersPage(int $accountId, string $dateFrom, string $dateTo, array $filters, int $limit, int $offset): array
    {
        if ($accountId <= 0) {
            return [];
        }

        $limit = max(1, min(500, (int)$limit));
        $offset = max(0, (int)$offset);

        $sql = "SELECT\n"
            . "  t.checkout_form_id,\n"
            . "  t.last_occurred_at,\n"
            . "  t.fees_neg,\n"
            . "  t.refunds_pos,\n"
            . "  t.net,\n"
            . "  t.billing_rows,\n"
            . "  t.err_code,\n"
            . "  t.err_msg,\n"
            . "  o.id_order_prestashop,\n"
            . "  o.buyer_login,\n"
            . "  o.status AS order_status,\n"
            . "  IFNULL(op.paid_amount, 0) AS paid_amount,\n"
            . "  op.pay_status AS pay_status\n"
            . "FROM (" . $this->baseAggSql($accountId, $dateFrom, $dateTo, $filters) . ") t\n"
            . $this->joinsSql($accountId)
            . $this->whereSql($filters)
            . "ORDER BY t.last_occurred_at DESC\n"
            . "LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * KPI dla widoku billing.
     *
     * @param array{buyer_login?:string,payment_id?:string,alert?:string} $filters
     * @return array{orders_count:int,fees_abs:float,refunds:float,net:float,issues:int,issues_unpaid:int,issues_no_refund:int,issues_partial_refund:int,issues_api_error:int}
     */
    public function kpi(int $accountId, string $dateFrom, string $dateTo, array $filters = []): array
    {
        $out = [
            'orders_count' => 0,
            'fees_abs' => 0.0,
            'refunds' => 0.0,
            'net' => 0.0,
            'issues' => 0,
            'issues_unpaid' => 0,
            'issues_no_refund' => 0,
            'issues_partial_refund' => 0,
            'issues_api_error' => 0,
        ];

        if ($accountId <= 0) {
            return $out;
        }

        $conds = $this->alertConditionsSql();

        $sql = "SELECT\n"
            . "  COUNT(*) AS orders_count,\n"
            . "  SUM(ABS(IFNULL(t.fees_neg,0))) AS fees_abs,\n"
            . "  SUM(IFNULL(t.refunds_pos,0)) AS refunds,\n"
            . "  SUM(IFNULL(t.net,0)) AS net,\n"
            . "  SUM(CASE WHEN {$conds['issues']} THEN 1 ELSE 0 END) AS issues,\n"
            . "  SUM(CASE WHEN {$conds['unpaid_fees']} THEN 1 ELSE 0 END) AS issues_unpaid,\n"
            . "  SUM(CASE WHEN {$conds['no_refund']} THEN 1 ELSE 0 END) AS issues_no_refund,\n"
            . "  SUM(CASE WHEN {$conds['partial_refund']} THEN 1 ELSE 0 END) AS issues_partial_refund,\n"
            . "  SUM(CASE WHEN {$conds['api_error']} THEN 1 ELSE 0 END) AS issues_api_error\n"
            . "FROM (" . $this->baseAggSql($accountId, $dateFrom, $dateTo, $filters) . ") t\n"
            . $this->joinsSql($accountId)
            . $this->whereSql($filters);

        $row = Db::getInstance()->getRow($sql);
        if (is_array($row)) {
            $out['orders_count'] = (int)($row['orders_count'] ?? 0);
            $out['fees_abs'] = (float)($row['fees_abs'] ?? 0);
            $out['refunds'] = (float)($row['refunds'] ?? 0);
            $out['net'] = (float)($row['net'] ?? 0);
            $out['issues'] = (int)($row['issues'] ?? 0);
            $out['issues_unpaid'] = (int)($row['issues_unpaid'] ?? 0);
            $out['issues_no_refund'] = (int)($row['issues_no_refund'] ?? 0);
            $out['issues_partial_refund'] = (int)($row['issues_partial_refund'] ?? 0);
            $out['issues_api_error'] = (int)($row['issues_api_error'] ?? 0);
        }

        return $out;
    }

    /**
     * Subquery agregujący billing-entries per order_id (checkoutFormId).
     *
     * @param array{payment_id?:string} $filters
     */
    private function baseAggSql(int $accountId, string $dateFrom, string $dateTo, array $filters = []): string
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');
        $paymentId = trim((string)($filters['payment_id'] ?? ''));

        $where = "b.id_allegropro_account=" . (int)$accountId
            . " AND b.occurred_at BETWEEN '{$from}' AND '{$to}'"
            . " AND b.order_id IS NOT NULL AND b.order_id <> ''";

        if ($paymentId !== '') {
            $where .= " AND b.payment_id='" . pSQL($paymentId) . "'";
        }

        // NOTE: fees/zwroty liczone po znaku kwoty (ujemne=opłaty, dodatnie=zwroty)
        return "SELECT\n"
            . "  b.order_id AS checkout_form_id,\n"
            . "  MAX(b.occurred_at) AS last_occurred_at,\n"
            . "  SUM(CASE WHEN b.value_amount < 0 THEN b.value_amount ELSE 0 END) AS fees_neg,\n"
            . "  SUM(CASE WHEN b.value_amount > 0 THEN b.value_amount ELSE 0 END) AS refunds_pos,\n"
            . "  SUM(b.value_amount) AS net,\n"
            . "  COUNT(*) AS billing_rows,\n"
            . "  MAX(b.order_error_code) AS err_code,\n"
            . "  MAX(b.order_error) AS err_msg\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b\n"
            . "WHERE {$where}\n"
            . "GROUP BY b.order_id";
    }

    private function joinsSql(int $accountId): string
    {
        $p = _DB_PREFIX_;

        // Łączymy po znormalizowanym checkoutFormId (różne formaty: z myślnikami / bez / z podkreśleniami).
        // Dzięki temu nie dostaniesz fałszywego "Brak danych", gdy ID jest to samo, ale zapisane inaczej.
        $normT = $this->normSql('t.checkout_form_id');
        $normO = $this->normSql('o.checkout_form_id');
        $normOp = $this->normSql('checkout_form_id');

        return "LEFT JOIN `{$p}allegropro_order` o\n"
            . "  ON o.id_allegropro_account=" . (int)$accountId . " AND {$normO} = {$normT}\n"
            . "LEFT JOIN (\n"
            . "  SELECT {$normOp} AS norm_id, SUM(paid_amount) AS paid_amount, MAX(status) AS pay_status\n"
            . "  FROM `{$p}allegropro_order_payment`\n"
            . "  GROUP BY norm_id\n"
            . ") op ON op.norm_id = {$normT}\n";
    }

    /**
     * @param array{buyer_login?:string,alert?:string} $filters
     */
    private function whereSql(array $filters = []): string
    {
        $buyerLogin = trim((string)($filters['buyer_login'] ?? ''));
        $alert = trim((string)($filters['alert'] ?? ''));

        $w = "WHERE 1\n";
        if ($buyerLogin !== '') {
            $w .= " AND o.buyer_login LIKE '%" . pSQL($buyerLogin) . "%'\n";
        }

        $conds = $this->alertConditionsSql();
        if ($alert === 'issues') {
            $w .= " AND {$conds['issues']}\n";
        } elseif ($alert === 'unpaid_fees') {
            $w .= " AND {$conds['unpaid_fees']}\n";
        } elseif ($alert === 'no_refund') {
            $w .= " AND {$conds['no_refund']}\n";
        } elseif ($alert === 'partial_refund') {
            $w .= " AND {$conds['partial_refund']}\n";
        } elseif ($alert === 'api_error') {
            $w .= " AND {$conds['api_error']}\n";
        }

        return $w;
    }

    /**
     * @return array{issues:string,unpaid_fees:string,no_refund:string,partial_refund:string,api_error:string}
     */
    private function alertConditionsSql(): array
    {
        $tol = 0.01;

        // 404 / not found z Allegro dla checkout-form (zamówienie niedostępne w API)
        $notFound = "(t.err_code = 404 OR (LOWER(IFNULL(t.err_msg,'')) LIKE '%checkout form%' AND LOWER(IFNULL(t.err_msg,'')) LIKE '%not found%'))";

        // API error liczymy jako błąd techniczny (wykluczamy 404/not found, bo to najczęściej brak zamówienia w API).
        $apiErr = "(t.err_code IS NOT NULL AND t.err_code > 0 AND NOT {$notFound})";

        $unpaid = "((UPPER(IFNULL(o.status,''))='FILLED_IN' OR (IFNULL(o.status,'')='' AND IFNULL(op.pay_status,'')<>'' AND UPPER(IFNULL(op.pay_status,''))<>'PAID')) AND IFNULL(op.paid_amount,0)<=0 AND t.fees_neg < -{$tol})";

        // Brak/niepełny zwrot liczymy również wtedy, gdy zamówienie jest niedostępne w API (404), ale są opłaty.
        $noRefund = "((UPPER(IFNULL(o.status,''))='CANCELLED' OR {$notFound}) AND t.fees_neg < -{$tol} AND t.refunds_pos <= {$tol})";
        $partialRefund = "((UPPER(IFNULL(o.status,''))='CANCELLED' OR {$notFound}) AND t.fees_neg < -{$tol} AND t.refunds_pos > {$tol} AND (t.refunds_pos + {$tol}) < ABS(t.fees_neg))";

        $issues = "({$apiErr} OR {$unpaid} OR {$noRefund} OR {$partialRefund})";

        return [
            'issues' => $issues,
            'unpaid_fees' => $unpaid,
            'no_refund' => $noRefund,
            'partial_refund' => $partialRefund,
            'api_error' => $apiErr,
        ];
    }


    private function normSql(string $expr): string
    {
        // usuń "-" i "_" oraz ignoruj wielkość liter
        return "LOWER(REPLACE(REPLACE(IFNULL({$expr},''),'-',''),'_',''))";
    }

}
