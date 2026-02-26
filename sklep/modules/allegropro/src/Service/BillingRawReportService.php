<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;
use Db;

/**
 * Raport: pełna lista operacji billing (RAW) z filtrowaniem i eksportem.
 *
 * Cel: diagnostyka na poziomie pojedynczych wpisów billing-entry (jak w Sales Center).
 * - respektuje filtr opłat jak w SettlementsReportService (feeWhere)
 * - wspiera fee_group oraz fee_type[] (dokładne typy po type_name)
 * - wspiera filtr znaku kwoty, przypisania do zamówienia, statusu zamówienia
 */
class BillingRawReportService
{
    private BillingEntryRepository $billing;

    public function __construct(BillingEntryRepository $billing)
    {
        $this->billing = $billing;
    }

    /**
     * @param int[] $accountIds
     * @param string[] $feeTypesSelected
     * @return array{total:int,page:int,pages:int,per_page:int,sum_total:float,sum_neg:float,sum_pos:float,items:array<int,array<string,mixed>>}
     */
    public function getPage(
        array $accountIds,
        string $dateFrom,
        string $dateTo,
        string $q = '',
        string $sign = 'any',
        string $assigned = 'any',
        string $orderState = 'all',
        int $page = 1,
        int $perPage = 50,
        string $sortBy = 'date',
        string $sortDir = 'desc',
        string $feeGroup = '',
        array $feeTypesSelected = []
    ): array {
        $accountIds = $this->normalizeAccountIds($accountIds);
        if (empty($accountIds)) {
            return ['total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage, 'sum_total' => 0.0, 'sum_neg' => 0.0, 'sum_pos' => 0.0, 'items' => []];
        }

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = max(0, ($page - 1) * $perPage);

        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $in = '(' . implode(',', array_map('intval', $accountIds)) . ')';
        $where = [];
        $where[] = "b.id_allegropro_account IN {$in}";
        $where[] = "b.occurred_at BETWEEN '{$from}' AND '{$to}'";

        // fee filters (jak w SettlementsReportService)
        $where[] = $this->billingEntryFilterSql('b.value_amount', 'b.type_id', 'b.type_name', $feeGroup, $feeTypesSelected);

        // assigned filter
        $assigned = strtolower(trim((string)$assigned));
        if ($assigned === 'unassigned') {
            $where[] = "(b.order_id IS NULL OR b.order_id='')";
        } elseif ($assigned === 'assigned') {
            $where[] = "(b.order_id IS NOT NULL AND b.order_id<>'')";
        }

        // sign filter
        $sign = strtolower(trim((string)$sign));
        if ($sign === 'neg') {
            $where[] = 'b.value_amount < 0';
        } elseif ($sign === 'pos') {
            $where[] = 'b.value_amount > 0';
        }

        // search
        $q = trim((string)$q);
        if ($q !== '') {
            if (mb_strlen($q) > 140) {
                $q = mb_substr($q, 0, 140);
            }
            $qLike = '%' . pSQL(mb_strtolower($q)) . '%';
            $where[] = "(LOWER(IFNULL(b.type_name,'')) LIKE '{$qLike}'"
                . " OR LOWER(IFNULL(b.offer_name,'')) LIKE '{$qLike}'"
                . " OR LOWER(IFNULL(b.offer_id,'')) LIKE '{$qLike}'"
                . " OR LOWER(IFNULL(b.order_id,'')) LIKE '{$qLike}'"
                . " OR LOWER(IFNULL(b.billing_entry_id,'')) LIKE '{$qLike}')";
        }

        // order state filter (requires join + assigned)
        $orderState = strtolower(trim((string)$orderState));
        $orderStateSql = $this->orderStateWhere('o.status', $orderState);
        if ($orderStateSql !== '') {
            // jeśli użytkownik filtruje po statusie, sens ma tylko assigned
            $where[] = "(b.order_id IS NOT NULL AND b.order_id<>'')";
            $where[] = '1=1 ' . $orderStateSql; // orderStateWhere zaczyna od AND
        }

        $whereSql = implode(' AND ', $where);

        // sort
        $sortBy = strtolower(trim((string)$sortBy));
        $sortDir = (strtolower((string)$sortDir) === 'asc') ? 'ASC' : 'DESC';
        $orderBy = 'b.occurred_at ' . $sortDir . ', b.id_allegropro_billing_entry ' . $sortDir;
        if ($sortBy === 'amount') {
            $orderBy = 'b.value_amount ' . $sortDir . ', b.occurred_at DESC';
        }

        // count + sums
        $sqlAgg = "SELECT COUNT(*) AS cnt,
  SUM(b.value_amount) AS sum_total,
  SUM(CASE WHEN b.value_amount < 0 THEN b.value_amount ELSE 0 END) AS sum_neg,
  SUM(CASE WHEN b.value_amount > 0 THEN b.value_amount ELSE 0 END) AS sum_pos
FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o
  ON o.checkout_form_id = b.order_id AND o.id_allegropro_account = b.id_allegropro_account
WHERE {$whereSql}";

        $agg = Db::getInstance()->getRow($sqlAgg) ?: [];
        $total = (int)($agg['cnt'] ?? 0);
        $sumTotal = (float)($agg['sum_total'] ?? 0);
        $sumNeg = (float)($agg['sum_neg'] ?? 0);
        $sumPos = (float)($agg['sum_pos'] ?? 0);
        $pages = (int)max(1, (int)ceil(($total ?: 0) / $perPage));
        if ($page > $pages) {
            $page = $pages;
            $offset = max(0, ($page - 1) * $perPage);
        }

        $sql = "SELECT
  b.id_allegropro_account,
  b.billing_entry_id,
  b.occurred_at,
  b.type_id,
  b.type_name,
  b.offer_id,
  b.offer_name,
  b.order_id,
  b.value_amount,
  b.value_currency,
  o.status AS order_status,
  o.id_order_prestashop
FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o
  ON o.checkout_form_id = b.order_id AND o.id_allegropro_account = b.id_allegropro_account
WHERE {$whereSql}
ORDER BY {$orderBy}
LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

        $items = Db::getInstance()->executeS($sql) ?: [];

        $out = [];
        foreach ($items as $r) {
            $out[] = [
                'id_allegropro_account' => (int)($r['id_allegropro_account'] ?? 0),
                'billing_entry_id' => (string)($r['billing_entry_id'] ?? ''),
                'occurred_at' => (string)($r['occurred_at'] ?? ''),
                'type_id' => (string)($r['type_id'] ?? ''),
                'type_name' => (string)($r['type_name'] ?? ''),
                'offer_id' => (string)($r['offer_id'] ?? ''),
                'offer_name' => (string)($r['offer_name'] ?? ''),
                'order_id' => (string)($r['order_id'] ?? ''),
                'value_amount' => (float)($r['value_amount'] ?? 0),
                'value_currency' => (string)($r['value_currency'] ?? 'PLN'),
                'order_status' => (string)($r['order_status'] ?? ''),
                'id_order_prestashop' => (int)($r['id_order_prestashop'] ?? 0),
            ];
        }

        return [
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'sum_total' => $sumTotal,
            'sum_neg' => $sumNeg,
            'sum_pos' => $sumPos,
            'items' => $out,
        ];
    }

    /**
     * Podsumowanie RAW: grupowanie per dzień / konto / typ.
     *
     * @param int[] $accountIds
     * @param string[] $feeTypesSelected
     * @return array{items:array<int,array<string,mixed>>}
     */
    public function getSummary(
        array $accountIds,
        string $dateFrom,
        string $dateTo,
        string $q = '',
        string $sign = 'any',
        string $assigned = 'any',
        string $orderState = 'all',
        string $feeGroup = '',
        array $feeTypesSelected = []
    ): array {
        $accountIds = $this->normalizeAccountIds($accountIds);
        if (empty($accountIds)) {
            return ['items' => []];
        }

        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $in = '(' . implode(',', array_map('intval', $accountIds)) . ')';
        $where = [];
        $where[] = "b.id_allegropro_account IN {$in}";
        $where[] = "b.occurred_at BETWEEN '{$from}' AND '{$to}'";

        // fee filters
        $where[] = $this->billingEntryFilterSql('b.value_amount', 'b.type_id', 'b.type_name', $feeGroup, $feeTypesSelected);

        // assigned filter
        $assigned = strtolower(trim((string)$assigned));
        if ($assigned === 'unassigned') {
            $where[] = "(b.order_id IS NULL OR b.order_id='')";
        } elseif ($assigned === 'assigned') {
            $where[] = "(b.order_id IS NOT NULL AND b.order_id<>'')";
        }

        // sign filter
        $sign = strtolower(trim((string)$sign));
        if ($sign === 'neg') {
            $where[] = 'b.value_amount < 0';
        } elseif ($sign === 'pos') {
            $where[] = 'b.value_amount > 0';
        }

        // search
        $q = trim((string)$q);
        if ($q !== '') {
            if (mb_strlen($q) > 140) {
                $q = mb_substr($q, 0, 140);
            }
            $qLike = '%' . pSQL(mb_strtolower($q)) . '%';
            $where[] = "(LOWER(IFNULL(b.type_name,'')) LIKE '{$qLike}'"
                . " OR LOWER(IFNULL(b.offer_name,'')) LIKE '{$qLike}'"
                . " OR LOWER(IFNULL(b.offer_id,'')) LIKE '{$qLike}'"
                . " OR LOWER(IFNULL(b.order_id,'')) LIKE '{$qLike}'"
                . " OR LOWER(IFNULL(b.billing_entry_id,'')) LIKE '{$qLike}')";
        }

        // order state filter
        $orderState = strtolower(trim((string)$orderState));
        $orderStateSql = $this->orderStateWhere('o.status', $orderState);
        if ($orderStateSql !== '') {
            $where[] = "(b.order_id IS NOT NULL AND b.order_id<>'')";
            $where[] = '1=1 ' . $orderStateSql;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
  DATE(b.occurred_at) AS day,
  b.id_allegropro_account,
  b.type_name,
  COUNT(*) AS cnt,
  SUM(b.value_amount) AS sum_total,
  SUM(CASE WHEN b.value_amount < 0 THEN b.value_amount ELSE 0 END) AS sum_neg,
  SUM(CASE WHEN b.value_amount > 0 THEN b.value_amount ELSE 0 END) AS sum_pos,
  MAX(b.value_currency) AS currency
FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o
  ON o.checkout_form_id = b.order_id AND o.id_allegropro_account = b.id_allegropro_account
WHERE {$whereSql}
GROUP BY day, b.id_allegropro_account, b.type_name
ORDER BY day ASC, b.id_allegropro_account ASC, b.type_name ASC";

        $rows = Db::getInstance()->executeS($sql) ?: [];

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'day' => (string)($r['day'] ?? ''),
                'id_allegropro_account' => (int)($r['id_allegropro_account'] ?? 0),
                'type_name' => (string)($r['type_name'] ?? ''),
                'cnt' => (int)($r['cnt'] ?? 0),
                'sum_total' => (float)($r['sum_total'] ?? 0),
                'sum_neg' => (float)($r['sum_neg'] ?? 0),
                'sum_pos' => (float)($r['sum_pos'] ?? 0),
                'currency' => (string)($r['currency'] ?? 'PLN'),
            ];
        }

        return ['items' => $items];
    }


    /**
     * @param mixed $ids
     * @return int[]
     */
    private function normalizeAccountIds($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $out = [];
        foreach ($ids as $v) {
            $id = (int)$v;
            if ($id > 0) {
                $out[$id] = $id;
            }
        }
        return array_values($out);
    }

    private function feeWhere(string $valueExpr, string $typeNameExpr): string
    {
        $include = "({$valueExpr} < 0 OR {$typeNameExpr} LIKE '%zwrot%' OR {$typeNameExpr} LIKE '%rabat%' OR {$typeNameExpr} LIKE '%korekt%' OR {$typeNameExpr} LIKE '%rekompens%')";
        $exclude = "({$typeNameExpr} LIKE '%wypł%' OR {$typeNameExpr} LIKE '%wypl%' OR {$typeNameExpr} LIKE '%wpł%' OR {$typeNameExpr} LIKE '%wpl%' OR {$typeNameExpr} LIKE '%przelew%' OR {$typeNameExpr} LIKE '%środk%' OR {$typeNameExpr} LIKE '%srodk%')";
        return "({$include} AND NOT {$exclude})";
    }

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
            $base = $this->feeWhere($valueExpr, $tn);
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

    private function orderStateWhere(string $statusExpr, string $orderState): string
    {
        $s = strtolower(trim((string)$orderState));
        if ($s === 'paid') {
            return " AND UPPER(IFNULL({$statusExpr},'')) IN ('READY_FOR_PROCESSING','BOUGHT')";
        }
        if ($s === 'unpaid') {
            return " AND UPPER(IFNULL({$statusExpr},'')) IN ('FILLED_IN')";
        }
        if ($s === 'cancelled') {
            return " AND UPPER(IFNULL({$statusExpr},'')) IN ('CANCELLED')";
        }
        return '';
    }
}
