<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;
use Db;

/**
 * Raport "Do wyjaśnienia" oparty o billing-entries (gdy enrichment checkout-form nie zadziałał).
 *
 * Źródło problemów:
 * - billing_entry.order_error_* (persistowane przy błędach 404/403/500/timeout itd.)
 * - oraz wykrycie: zamówienie nieopłacone/anulowane + ujemne saldo opłat (brak pełnego zwrotu)
 *
 * Domyślnie raport jest liczony w aktualnym zakresie dat (occurred_at),
 * ale może działać też jako "cała historia" (bez filtra dat).
 */
class IssuesReportService
{
    private BillingEntryRepository $billingRepo;

    public function __construct(BillingEntryRepository $billingRepo)
    {
        $this->billingRepo = $billingRepo;
    }

    /**
     * @param int[] $accountIds
     * @return array{orders_count:int,billing_rows:int,fees_neg:float,refunds_pos:float,balance:float}
     */
    public function getIssuesSummary(array $accountIds, string $dateFrom, string $dateTo, string $q = "", string $feeGroup = "", array $feeTypesSelected = [], bool $allHistory = false, string $refundMode = "any"): array
    {
        $accountIds = $this->normalizeAccountIds($accountIds);
        if (empty($accountIds)) {
            return ['orders_count' => 0, 'billing_rows' => 0, 'fees_neg' => 0.0, 'refunds_pos' => 0.0, 'balance' => 0.0];
        }

        $this->billingRepo->ensureSchema();

        $in = $this->buildIn($accountIds);
        $filter = $this->billingEntryFilterSql('b.value_amount', 'b.type_id', 'b.type_name', $feeGroup, $feeTypesSelected);
        $qWhere = $this->issuesSearchWhereSql($q);
        $dateWhere = $allHistory ? '' : " AND b.occurred_at BETWEEN '" . pSQL($dateFrom . ' 00:00:00') . "' AND '" . pSQL($dateTo . ' 23:59:59') . "'";

        $base = $this->issuesBaseSql($in, $dateWhere, $filter, $qWhere, false, $refundMode);
        $sql = "SELECT\n"
            . "  COUNT(*) AS orders_count,\n"
            . "  SUM(t.billing_rows) AS billing_rows,\n"
            . "  SUM(t.fees_neg) AS fees_neg,\n"
            . "  SUM(t.refunds_pos) AS refunds_pos,\n"
            . "  SUM(t.balance) AS balance\n"
            . "FROM (\n{$base}\n) t";

        $row = Db::getInstance()->getRow($sql) ?: [];

        return [
            'orders_count' => (int)($row['orders_count'] ?? 0),
            'billing_rows' => (int)($row['billing_rows'] ?? 0),
            'fees_neg' => (float)($row['fees_neg'] ?? 0),
            'refunds_pos' => (float)($row['refunds_pos'] ?? 0),
            'balance' => (float)($row['balance'] ?? 0),
        ];
    }

    /**
     * Rozbicie problemów na typy (do szybkiego podsumowania na dashboardzie).
     *
     * @param int[] $accountIds
     * @return array{api:array{orders:int,fees_neg:float,refunds_pos:float,balance:float},unpaid:array{orders:int,fees_neg:float,refunds_pos:float,balance:float},cancelled:array{orders:int,fees_neg:float,refunds_pos:float,balance:float}}
     */
    public function getIssuesBreakdown(array $accountIds, string $dateFrom, string $dateTo, string $q = "", string $feeGroup = "", array $feeTypesSelected = [], bool $allHistory = false, string $refundMode = "any"): array
    {
        $accountIds = $this->normalizeAccountIds($accountIds);
        if (empty($accountIds)) {
            return [
                'api' => ['orders' => 0, 'fees_neg' => 0.0, 'refunds_pos' => 0.0, 'balance' => 0.0],
                'unpaid' => ['orders' => 0, 'fees_neg' => 0.0, 'refunds_pos' => 0.0, 'balance' => 0.0],
                'cancelled' => ['orders' => 0, 'fees_neg' => 0.0, 'refunds_pos' => 0.0, 'balance' => 0.0],
            ];
        }

        $this->billingRepo->ensureSchema();

        $in = $this->buildIn($accountIds);
        $filter = $this->billingEntryFilterSql('b.value_amount', 'b.type_id', 'b.type_name', $feeGroup, $feeTypesSelected);
        $qWhere = $this->issuesSearchWhereSql($q);
        $dateWhere = $allHistory ? '' : " AND b.occurred_at BETWEEN '" . pSQL($dateFrom . ' 00:00:00') . "' AND '" . pSQL($dateTo . ' 23:59:59') . "'";

        $base = $this->issuesBaseSql($in, $dateWhere, $filter, $qWhere, false, $refundMode);

        $sql = "SELECT\n"
            . "  SUM(CASE WHEN t.err_code IS NOT NULL THEN 1 ELSE 0 END) AS api_orders,\n"
            . "  SUM(CASE WHEN t.err_code IS NOT NULL THEN t.fees_neg ELSE 0 END) AS api_fees_neg,\n"
            . "  SUM(CASE WHEN t.err_code IS NOT NULL THEN t.refunds_pos ELSE 0 END) AS api_refunds_pos,\n"
            . "  SUM(CASE WHEN t.err_code IS NOT NULL THEN t.balance ELSE 0 END) AS api_balance,\n"
            . "  SUM(CASE WHEN t.err_code IS NULL AND (UPPER(IFNULL(t.order_status,''))='CANCELLED' OR UPPER(IFNULL(t.pay_status,''))='CANCELLED') THEN 1 ELSE 0 END) AS cancelled_orders,\n"
            . "  SUM(CASE WHEN t.err_code IS NULL AND (UPPER(IFNULL(t.order_status,''))='CANCELLED' OR UPPER(IFNULL(t.pay_status,''))='CANCELLED') THEN t.fees_neg ELSE 0 END) AS cancelled_fees_neg,\n"
            . "  SUM(CASE WHEN t.err_code IS NULL AND (UPPER(IFNULL(t.order_status,''))='CANCELLED' OR UPPER(IFNULL(t.pay_status,''))='CANCELLED') THEN t.refunds_pos ELSE 0 END) AS cancelled_refunds_pos,\n"
            . "  SUM(CASE WHEN t.err_code IS NULL AND (UPPER(IFNULL(t.order_status,''))='CANCELLED' OR UPPER(IFNULL(t.pay_status,''))='CANCELLED') THEN t.balance ELSE 0 END) AS cancelled_balance,\n"
            . "  SUM(CASE WHEN t.err_code IS NULL AND UPPER(IFNULL(t.order_status,''))='FILLED_IN' AND IFNULL(t.paid_amount,0)<=0 THEN 1 ELSE 0 END) AS unpaid_orders,\n"
            . "  SUM(CASE WHEN t.err_code IS NULL AND UPPER(IFNULL(t.order_status,''))='FILLED_IN' AND IFNULL(t.paid_amount,0)<=0 THEN t.fees_neg ELSE 0 END) AS unpaid_fees_neg,\n"
            . "  SUM(CASE WHEN t.err_code IS NULL AND UPPER(IFNULL(t.order_status,''))='FILLED_IN' AND IFNULL(t.paid_amount,0)<=0 THEN t.refunds_pos ELSE 0 END) AS unpaid_refunds_pos,\n"
            . "  SUM(CASE WHEN t.err_code IS NULL AND UPPER(IFNULL(t.order_status,''))='FILLED_IN' AND IFNULL(t.paid_amount,0)<=0 THEN t.balance ELSE 0 END) AS unpaid_balance\n"
            . "FROM (\n{$base}\n) t";

        $row = Db::getInstance()->getRow($sql) ?: [];

        return [
            'api' => [
                'orders' => (int)($row['api_orders'] ?? 0),
                'fees_neg' => (float)($row['api_fees_neg'] ?? 0),
                'refunds_pos' => (float)($row['api_refunds_pos'] ?? 0),
                'balance' => (float)($row['api_balance'] ?? 0),
            ],
            'cancelled' => [
                'orders' => (int)($row['cancelled_orders'] ?? 0),
                'fees_neg' => (float)($row['cancelled_fees_neg'] ?? 0),
                'refunds_pos' => (float)($row['cancelled_refunds_pos'] ?? 0),
                'balance' => (float)($row['cancelled_balance'] ?? 0),
            ],
            'unpaid' => [
                'orders' => (int)($row['unpaid_orders'] ?? 0),
                'fees_neg' => (float)($row['unpaid_fees_neg'] ?? 0),
                'refunds_pos' => (float)($row['unpaid_refunds_pos'] ?? 0),
                'balance' => (float)($row['unpaid_balance'] ?? 0),
            ],
        ];
    }

    /**
     * @param int[] $accountIds
     */
    public function countIssuesOrders(array $accountIds, string $dateFrom, string $dateTo, string $q = "", string $feeGroup = "", array $feeTypesSelected = [], bool $allHistory = false, string $refundMode = "any"): int
    {
        $accountIds = $this->normalizeAccountIds($accountIds);
        if (empty($accountIds)) {
            return 0;
        }
        $this->billingRepo->ensureSchema();

        $in = $this->buildIn($accountIds);
        $filter = $this->billingEntryFilterSql('b.value_amount', 'b.type_id', 'b.type_name', $feeGroup, $feeTypesSelected);
        $qWhere = $this->issuesSearchWhereSql($q);
        $dateWhere = $allHistory ? '' : " AND b.occurred_at BETWEEN '" . pSQL($dateFrom . ' 00:00:00') . "' AND '" . pSQL($dateTo . ' 23:59:59') . "'";

        $base = $this->issuesBaseSql($in, $dateWhere, $filter, $qWhere, false, $refundMode);
        $sql = "SELECT COUNT(*) AS cnt FROM (\n{$base}\n) t";
        $row = Db::getInstance()->getRow($sql) ?: [];
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * @param int[] $accountIds
     * @return array<int,array<string,mixed>>
     */
    public function getIssuesRows(array $accountIds, string $dateFrom, string $dateTo, string $q = "", int $limit = 50, int $offset = 0, string $feeGroup = "", array $feeTypesSelected = [], bool $allHistory = false, string $refundMode = "any"): array
    {
        $accountIds = $this->normalizeAccountIds($accountIds);
        if (empty($accountIds)) {
            return [];
        }
        $this->billingRepo->ensureSchema();

        $in = $this->buildIn($accountIds);
        $filter = $this->billingEntryFilterSql('b.value_amount', 'b.type_id', 'b.type_name', $feeGroup, $feeTypesSelected);
        $qWhere = $this->issuesSearchWhereSql($q);
        $dateWhere = $allHistory ? '' : " AND b.occurred_at BETWEEN '" . pSQL($dateFrom . ' 00:00:00') . "' AND '" . pSQL($dateTo . ' 23:59:59') . "'";

        $limit = max(1, min(500, (int)$limit));
        $offset = max(0, (int)$offset);

        $base = $this->issuesBaseSql($in, $dateWhere, $filter, $qWhere, true, $refundMode);
        $sql = $base . "\nLIMIT {$limit} OFFSET {$offset}";
        $rows = Db::getInstance()->executeS($sql) ?: [];

        // Dodaj pola pomocnicze do UI (badge i opis) – bez ruszania DB.
        foreach ($rows as &$r) {
            $errCode = isset($r['err_code']) ? (int)$r['err_code'] : 0;
            $orderStatus = strtoupper((string)($r['order_status'] ?? ''));
            $payStatus = strtoupper((string)($r['pay_status'] ?? ''));
            $paidAmount = (float)($r['paid_amount'] ?? 0);

            $kind = 'API_ERROR';
            if ($errCode <= 0) {
                if ($orderStatus === 'CANCELLED' || $payStatus === 'CANCELLED') {
                    $kind = 'CANCELLED_FEES';
                } elseif ($orderStatus === 'FILLED_IN' && $paidAmount <= 0.0) {
                    $kind = 'UNPAID_FEES';
                } else {
                    $kind = 'UNPAID_FEES';
                }
            }

            $r['issue_kind'] = $kind;
            if ($kind === 'API_ERROR') {
                $r['badge_text'] = 'ERR ' . (string)$errCode;
                $r['badge_class'] = 'badge-danger';
                $r['desc'] = (string)($r['err_msg'] ?? '');
            } elseif ($kind === 'CANCELLED_FEES') {
                $r['badge_text'] = 'ANULOWANE';
                $r['badge_class'] = 'badge-warning';

                $bal = (float)($r['balance'] ?? 0);
                $ref = (float)($r['refunds_pos'] ?? 0);
                if ($ref <= 0.0) {
                    $r['desc'] = 'Zamówienie anulowane: pobrano opłaty i brak zwrotów w billing.';
                } elseif ($bal < 0.0) {
                    $r['desc'] = 'Zamówienie anulowane: zwroty częściowe, saldo opłat nadal ujemne.';
                } else {
                    $r['desc'] = 'Zamówienie anulowane: zwrot widoczny (saldo 0 lub dodatnie) — do weryfikacji.';
                }
            } else {
                $r['badge_text'] = 'NIEOPŁACONE';
                $r['badge_class'] = 'badge-warning';

                $bal = (float)($r['balance'] ?? 0);
                $ref = (float)($r['refunds_pos'] ?? 0);
                if ($ref <= 0.0) {
                    $r['desc'] = 'Zamówienie nieopłacone: pobrano opłaty i brak zwrotów w billing.';
                } elseif ($bal < 0.0) {
                    $r['desc'] = 'Zamówienie nieopłacone: zwroty częściowe, saldo opłat nadal ujemne.';
                } else {
                    $r['desc'] = 'Zamówienie nieopłacone: zwrot widoczny (saldo 0 lub dodatnie) — do weryfikacji.';
                }
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Buduje bazowy SELECT (1 wiersz = 1 order_id) z warunkiem "do wyjaśnienia":
     * - błąd enrichmentu (order_error_code)
     * - lub zamówienie nieopłacone/anulowane + ujemne saldo opłat
     */
    private function issuesBaseSql(string $inAccounts, string $dateWhere, string $filter, string $qWhere, bool $orderBy, string $refundMode = 'any'): string
    {
        // Normalizacja order_id do joinów ze skip (UUID bywa z myślnikami/podkreśleniami).
        $normB = "LOWER(REPLACE(REPLACE(IFNULL(b.order_id,''),'-',''),'_',''))";
        $normS = "LOWER(REPLACE(REPLACE(IFNULL(s.order_id,''),'-',''),'_',''))";

        // Statusy z DB (jeśli order/payment istnieją).
        $orderStatusExpr = "UPPER(IFNULL(o.status,''))";
        $payStatusExpr = "UPPER(IFNULL(op.status,''))";
        $paidAmountExpr = "IFNULL(op.paid_amount,0)";

        // Warunki "issue":
        // A) zapisany błąd pobrania checkout-form
        $condApiErr = "MAX(b.order_error_code IS NOT NULL)=1";
        // B) anulowane + ujemne saldo
        $condCancelled = "(MAX({$orderStatusExpr}='CANCELLED')=1 OR MAX({$payStatusExpr}='CANCELLED')=1)";
        // C) nieopłacone (FILLED_IN) + ujemne saldo
        $condUnpaid = "(MAX({$orderStatusExpr}='FILLED_IN')=1 AND MAX({$paidAmountExpr})<=0)";
        // "Pobrane opłaty" – jeśli są jakiekolwiek ujemne pozycje (koszty)
        $condFeesNeg = "SUM(CASE WHEN b.value_amount < 0 THEN b.value_amount ELSE 0 END) < 0";

        $baseHaving = "({$condApiErr} OR ({$condFeesNeg} AND ({$condCancelled} OR {$condUnpaid})))";

        // Dodatkowy filtr "zwroty" (opcjonalnie) – pozwala wyświetlić np. tylko brak zwrotu lub tylko saldo ujemne.
        $refundMode = strtolower(trim((string)$refundMode));
        $extra = '';
        if ($refundMode === 'no_refund') {
            $extra = " AND SUM(CASE WHEN b.value_amount > 0 THEN b.value_amount ELSE 0 END) = 0";
        } elseif ($refundMode === 'balance_neg') {
            $extra = " AND SUM(b.value_amount) < 0";
        }

        $having = "HAVING {$baseHaving}{$extra}";

        $sql = "SELECT\n"
            . "  b.id_allegropro_account,\n"
            . "  b.order_id,\n"
            . "  MAX(b.order_error_code) AS err_code,\n"
            . "  MAX(b.order_error_at) AS err_at,\n"
            . "  MAX(b.order_error) AS err_msg,\n"
            . "  COUNT(*) AS billing_rows,\n"
            . "  SUM(CASE WHEN b.value_amount < 0 THEN b.value_amount ELSE 0 END) AS fees_neg,\n"
            . "  SUM(CASE WHEN b.value_amount > 0 THEN b.value_amount ELSE 0 END) AS refunds_pos,\n"
            . "  SUM(b.value_amount) AS balance,\n"
            . "  MAX(s.attempts) AS attempts,\n"
            . "  MAX(s.last_attempt_at) AS last_attempt_at,\n"
            . "  MAX(s.skip_until) AS skip_until,\n"
            . "  MAX(o.status) AS order_status,\n"
            . "  MAX(op.status) AS pay_status,\n"
            . "  MAX(op.paid_amount) AS paid_amount\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b\n"
            . "LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order_enrich_skip` s\n"
            . "  ON (s.id_allegropro_account=b.id_allegropro_account AND {$normS} = {$normB})\n"
            . "LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o\n"
            . "  ON (o.id_allegropro_account=b.id_allegropro_account AND o.checkout_form_id=b.order_id)\n"
            . "LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order_payment` op\n"
            . "  ON (op.checkout_form_id=b.order_id)\n"
            . "WHERE b.id_allegropro_account IN {$inAccounts}\n"
            . $dateWhere . "\n"
            . "  AND b.order_id IS NOT NULL AND b.order_id <> ''\n"
            . "  AND {$filter}\n"
            . $qWhere . "\n"
            . "GROUP BY b.id_allegropro_account, b.order_id\n"
            . $having;

        if ($orderBy) {
            $sql .= "\nORDER BY err_at DESC";
        }
        return $sql;
    }

    /**
     * Warunek wyszukiwania dla listy problemów.
     * Szukamy po order_id i po tekście błędu (order_error).
     */
    private function issuesSearchWhereSql(string $q): string
    {
        $q = trim((string)$q);
        if ($q === '') {
            return '';
        }
        $q = \Tools::substr($q, 0, 160);
        $like = '%' . pSQL($q) . '%';
        return " AND (b.order_id LIKE '{$like}' OR b.order_error LIKE '{$like}')";
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private function normalizeAccountIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $v) {
            $i = (int)$v;
            if ($i > 0) {
                $out[$i] = $i;
            }
        }
        return array_values($out);
    }

    /**
     * @param int[] $ids
     */
    private function buildIn(array $ids): string
    {
        $vals = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $vals[] = $id;
            }
        }
        if (empty($vals)) {
            return '(0)';
        }
        return '(' . implode(',', $vals) . ')';
    }

    /**
     * Kopia logiki filtrowania wpisów billing z SettlementsReportService.
     * - jeśli wybrano fee_type[]: filtrujemy dokładnie po type_name (1:1 jak w Allegro)
     * - inaczej: domyślnie opłaty + korekty (bez przepływów środków) + opcjonalnie fee_group
     */
    private function billingEntryFilterSql(string $valueExpr, string $typeIdExpr, string $typeNameExpr, string $feeGroup = '', array $feeTypesSelected = []): string
    {
        $tn = "LOWER(IFNULL({$typeNameExpr},''))";

        $types = [];
        foreach ($feeTypesSelected as $t) {
            $t = trim((string)$t);
            if ($t === '') {
                continue;
            }
            $t = mb_strtolower($t);
            $types[$t] = $t;
        }

        if (!empty($types)) {
            $vals = [];
            foreach ($types as $t) {
                $vals[] = "'" . pSQL($t) . "'";
            }
            $base = $tn . ' IN (' . implode(',', $vals) . ')';
        } else {
            // domyślnie bierzemy opłaty + korekty (bez przepływów środków)
            $base = "({$valueExpr} <> 0 AND ({$typeIdExpr} <> 'PAYOUT' AND {$typeIdExpr} <> 'TOP_UP'))";
        }

        $group = $this->feeGroupCondSql($typeIdExpr, $tn, $feeGroup);
        if ($group !== '') {
            return '(' . $base . ') AND (' . $group . ')';
        }
        return (string)$base;
    }

    private function feeGroupCondSql(string $typeIdExpr, string $typeNameLowerExpr, string $feeGroup): string
    {
        $g = strtolower(trim((string)$feeGroup));
        if ($g === '' || $g === 'all') {
            return '';
        }

        $commission = "({$typeIdExpr}='SUC' OR {$typeNameLowerExpr} LIKE '%prowiz%')";
        $smart = "({$typeNameLowerExpr} LIKE '%smart%')";
        $delivery = "({$typeNameLowerExpr} LIKE '%dostaw%' OR {$typeNameLowerExpr} LIKE '%przesy%')";
        $promotion = "({$typeNameLowerExpr} LIKE '%promow%' OR {$typeNameLowerExpr} LIKE '%reklam%')";
        $refunds = "({$typeNameLowerExpr} LIKE '%zwrot%' OR {$typeNameLowerExpr} LIKE '%rabat%' OR {$typeNameLowerExpr} LIKE '%korekt%' OR {$typeNameLowerExpr} LIKE '%rekompens%')";

        if ($g === 'commission') return $commission;
        if ($g === 'smart') return $smart;
        if ($g === 'delivery') return $delivery;
        if ($g === 'promotion') return $promotion;
        if ($g === 'refunds') return $refunds;
        if ($g === 'other') {
            return 'NOT (' . implode(' OR ', [$commission, $smart, $delivery, $promotion, $refunds]) . ')';
        }

        return '';
    }
}
