<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;
use Db;

/**
 * Raport: operacje billing nieprzypisane do zamówienia (order_id NULL/empty).
 *
 * Cel: diagnostyka "Nieprzypisane" w panelu Rozliczeń.
 * - respektuje ten sam filtr opłat co SettlementsReportService (feeWhere)
 * - wspiera fee_group oraz fee_type[] (dokładne typy po type_name)
 * - wspiera wyszukiwanie i filtr znaku kwoty
 */
class UnassignedBillingReportService
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
    public function getUnassignedPage(array $accountIds, string $dateFrom, string $dateTo, string $q = '', string $sign = 'any', int $page = 1, int $perPage = 50, string $feeGroup = '', array $feeTypesSelected = []): array
    {
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
        $where[] = "(b.order_id IS NULL OR b.order_id='')";
        $where[] = "b.occurred_at BETWEEN '{$from}' AND '{$to}'";

        // fee filters (jak w SettlementsReportService)
        $where[] = $this->billingEntryFilterSql('b.value_amount', 'b.type_id', 'b.type_name', $feeGroup, $feeTypesSelected);

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
            if (mb_strlen($q) > 120) {
                $q = mb_substr($q, 0, 120);
            }
            $qLike = '%' . pSQL(mb_strtolower($q)) . '%';
            $where[] = "(LOWER(IFNULL(b.type_name,'')) LIKE '{$qLike}' OR LOWER(IFNULL(b.offer_name,'')) LIKE '{$qLike}' OR LOWER(IFNULL(b.billing_entry_id,'')) LIKE '{$qLike}')";
        }

        $whereSql = implode(' AND ', $where);

        // count + sums
        $sqlAgg = "SELECT COUNT(*) AS cnt,
  SUM(b.value_amount) AS sum_total,
  SUM(CASE WHEN b.value_amount < 0 THEN b.value_amount ELSE 0 END) AS sum_neg,
  SUM(CASE WHEN b.value_amount > 0 THEN b.value_amount ELSE 0 END) AS sum_pos
FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
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

        $sql = "SELECT b.id_allegropro_account, b.billing_entry_id, b.occurred_at, b.type_id, b.type_name, b.offer_id, b.offer_name, b.value_amount, b.value_currency
FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b
WHERE {$whereSql}
ORDER BY b.occurred_at DESC, b.id_allegropro_billing_entry DESC
LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

        $items = Db::getInstance()->executeS($sql) ?: [];

        // Normalizacja pól
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
                'value_amount' => (float)($r['value_amount'] ?? 0),
                'value_currency' => (string)($r['value_currency'] ?? 'PLN'),
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

    /**
     * Filtr "opłat" zgodny z SettlementsReportService::feeWhere().
     */
    private function feeWhere(string $valueExpr, string $typeNameExpr): string
    {
        $include = "({$valueExpr} < 0 OR {$typeNameExpr} LIKE '%zwrot%' OR {$typeNameExpr} LIKE '%rabat%' OR {$typeNameExpr} LIKE '%korekt%' OR {$typeNameExpr} LIKE '%rekompens%')";
        $exclude = "({$typeNameExpr} LIKE '%wypł%' OR {$typeNameExpr} LIKE '%wypl%' OR {$typeNameExpr} LIKE '%wpł%' OR {$typeNameExpr} LIKE '%wpl%' OR {$typeNameExpr} LIKE '%przelew%' OR {$typeNameExpr} LIKE '%środk%' OR {$typeNameExpr} LIKE '%srodk%')";
        return "({$include} AND NOT {$exclude})";
    }

    /**
     * Buduje filtr wpisów billing jak w SettlementsReportService::billingEntryFilterSql().
     *
     * - domyślnie: feeWhere (opłaty + korekty, bez przepływów środków)
     * - jeśli wskazano feeTypes: filtrujemy dokładnie po type_name (1:1 jak w Allegro) i NIE stosujemy feeWhere
     * - opcjonalnie: feeGroup (kategorie)
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
}
