<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\BillingEntryRepository;
use Db;

/**
 * Raport rozliczeń Allegro (billing-entries) + mapowanie do zamówień (checkout-form).
 *
 * Dwa tryby:
 * - billing: zakres dat dotyczy księgowania operacji (occurred_at) — zgodnie z Sales Center.
 * - orders: zakres dat dotyczy daty złożenia zamówień (created_at_allegro), a opłaty liczymy dla tych zamówień (bez filtra daty opłat).
 */
class SettlementsReportService
{
    private BillingEntryRepository $billing;

    public function __construct(BillingEntryRepository $billing)
    {
        $this->billing = $billing;
    }

    /**
     * @param int|int[] $accountIds
     * @return int[]
     */
    private function normalizeAccountIds($accountIds): array
    {
        $ids = is_array($accountIds) ? $accountIds : [$accountIds];
        $out = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $out[$id] = $id;
            }
        }
        return array_values($out);
    }

    /**
     * Buduje bezpieczny fragment "IN (...)".
     * @param int[] $ids
     */
    private function buildIn(array $ids): string
    {
        $ids = $this->normalizeAccountIds($ids);
        if (empty($ids)) {
            return '(0)';
        }
        return '(' . implode(',', array_map('intval', $ids)) . ')';
    }

    /**
     * Normalizacja UUID/ID: usuń "-" i "_" oraz ignoruj wielkość liter.
     */
    private function normalizeKeySql(string $expr): string
    {
        return "LOWER(REPLACE(REPLACE(IFNULL({$expr},''),'-',''),'_',''))";
    }

    /**
     * Filtr "opłat" zgodny z BillingEntryRepository::buildFeeWhereSql().
     */
    private function feeWhere(string $valueExpr, string $typeNameExpr): string
    {
        $include = "({$valueExpr} < 0 OR {$typeNameExpr} LIKE '%zwrot%' OR {$typeNameExpr} LIKE '%rabat%' OR {$typeNameExpr} LIKE '%korekt%' OR {$typeNameExpr} LIKE '%rekompens%')";
        $exclude = "({$typeNameExpr} LIKE '%wypł%' OR {$typeNameExpr} LIKE '%wypl%' OR {$typeNameExpr} LIKE '%wpł%' OR {$typeNameExpr} LIKE '%wpl%' OR {$typeNameExpr} LIKE '%przelew%' OR {$typeNameExpr} LIKE '%środk%' OR {$typeNameExpr} LIKE '%srodk%')";
        return "({$include} AND NOT {$exclude})";
    }

    /**
     * Filtr po stanie zamówienia (na podstawie o.status z checkout-forms).
     *
     * Mapowanie jak w AdminAllegroProOrdersController::mapModuleStatusLabel():
     * - paid: READY_FOR_PROCESSING, BOUGHT
     * - unpaid: FILLED_IN
     * - cancelled: CANCELLED
     */
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

    /**
     * Agregacja pozycji (sprzedaż bez dostawy): SUM(quantity*price) per checkout_form_id.
     */
    private function itemsAggSql(): string
    {
        return "SELECT checkout_form_id, SUM(quantity * price) AS items_total FROM `" . _DB_PREFIX_ . "allegropro_order_item` GROUP BY checkout_form_id";
    }

    /**
     * Agregacja dostawy: MAX(cost_amount) per checkout_form_id.
     */
    private function shippingAggSql(): string
    {
        return "SELECT checkout_form_id, MAX(cost_amount) AS shipping_amount FROM `" . _DB_PREFIX_ . "allegropro_order_shipping` GROUP BY checkout_form_id";
    }

    /**
     * Agregacja billing (bez filtra daty) per order_key (znormalizowany order_id).
     * Zwraca neg_sum (<0) i pos_sum (>0) dla opłat/zwrotów.
     */
    private function feesAggNoDateSql(array $accountIds): string
    {
        $in = $this->buildIn($accountIds);
        $tn = "LOWER(IFNULL(b2.type_name,''))";
        $feeWhere = $this->feeWhere('b2.value_amount', $tn);

        return "SELECT b2.id_allegropro_account, " . $this->normalizeKeySql('b2.order_id') . " AS order_key,\n"
            . "       SUM(CASE WHEN b2.value_amount < 0 THEN b2.value_amount ELSE 0 END) AS neg_sum,\n"
            . "       SUM(CASE WHEN b2.value_amount > 0 THEN b2.value_amount ELSE 0 END) AS pos_sum\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b2\n"
            . "WHERE b2.id_allegropro_account IN {$in}\n"
            . "  AND b2.order_id IS NOT NULL AND b2.order_id <> ''\n"
            . "  AND {$feeWhere}\n"
            . "GROUP BY b2.id_allegropro_account, order_key";
    }

    /**
     * TRYB A: Księgowanie opłat (billing entries w okresie).
     */
    public function getPeriodSummaryBilling($accountIds, string $dateFrom, string $dateTo, string $orderState = 'all', bool $cancelledNoRefund = false): array
    {
        $ids = $this->normalizeAccountIds($accountIds);

        // Kategorie opłat w okresie. Jeśli filtrujemy po statusie zamówień / "bez zwrotu",
        // musimy związać billing-entries z zamówieniami i ograniczyć wynik.
        $cats = ($orderState !== 'all' || $cancelledNoRefund)
            ? $this->getCategorySumsBillingFilteredMulti($ids, $dateFrom, $dateTo, $orderState, $cancelledNoRefund)
            : $this->billing->getCategorySumsMulti($ids, $dateFrom, $dateTo);

        $unassigned = $this->billing->countUnassignedMulti($ids, $dateFrom, $dateTo);

        // Sprzedaż (bez dostawy): suma zamówień, które mają naliczone opłaty w wybranym okresie księgowania.
        $sales = $this->sumOrdersSalesForBillingMulti($ids, $dateFrom, $dateTo, $orderState, $cancelledNoRefund);

        $netAfterFees = (float)$sales + (float)($cats['total'] ?? 0);

        return [
            'sales_total' => (float)$sales,
            'fees_total' => (float)($cats['total'] ?? 0),
            'fees_commission' => (float)($cats['commission'] ?? 0),
            'fees_smart' => (float)($cats['smart'] ?? 0),
            'fees_delivery' => (float)($cats['delivery'] ?? 0),
            'fees_promotion' => (float)($cats['promotion'] ?? 0),
            'fees_refunds' => (float)($cats['refunds'] ?? 0),
            'unassigned_count' => (int)$unassigned,
            'net_after_fees' => (float)$netAfterFees,
        ];
    }

    /**
     * TRYB B: Koszt zamówień złożonych w okresie (opłaty liczone dla tych zamówień).
     */
    public function getPeriodSummaryOrders($accountIds, string $dateFrom, string $dateTo, string $orderState = 'all', bool $cancelledNoRefund = false): array
    {
        $ids = $this->normalizeAccountIds($accountIds);

        // Sprzedaż (bez dostawy): zamówienia złożone w okresie.
        $sales = $this->sumOrdersSalesMulti($ids, $dateFrom, $dateTo, $orderState, $cancelledNoRefund);

        // Opłaty: wszystkie billing-entries przypięte do tych zamówień (bez filtra daty).
        $orderIds = $this->listOrderIdsInRangeMulti($ids, $dateFrom, $dateTo, $orderState, $cancelledNoRefund);
        $candidates = $this->buildOrderIdCandidates($orderIds);

        $cats = $this->billing->getCategorySumsForOrderIdsMultiNoDate($ids, $candidates);

        $netAfterFees = (float)$sales + (float)($cats['total'] ?? 0);

        return [
            'sales_total' => (float)$sales,
            'fees_total' => (float)($cats['total'] ?? 0),
            'fees_commission' => (float)($cats['commission'] ?? 0),
            'fees_smart' => (float)($cats['smart'] ?? 0),
            'fees_delivery' => (float)($cats['delivery'] ?? 0),
            'fees_promotion' => (float)($cats['promotion'] ?? 0),
            'fees_refunds' => (float)($cats['refunds'] ?? 0),
            'unassigned_count' => 0,
            'net_after_fees' => (float)$netAfterFees,
        ];
    }

    /**
     * Backward compatible alias (domyślnie: billing).
     */
    public function getPeriodSummary($accountIds, string $dateFrom, string $dateTo): array
    {
        return $this->getPeriodSummaryBilling($accountIds, $dateFrom, $dateTo);
    }

    /**
     * TRYB A: liczba zamówień (unikalne order_id) mających opłaty zaksięgowane w okresie.
     */
    public function countOrdersBilling($accountIds, string $dateFrom, string $dateTo, string $q = '', string $orderState = 'all', bool $cancelledNoRefund = false): int
    {
        $ids = $this->normalizeAccountIds($accountIds);
        $in = $this->buildIn($ids);

        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(b.type_name,''))";
        $feeWhere = $this->feeWhere('b.value_amount', $tn);

        $whereQ = '';
        if ($q !== '') {
            $qEsc = pSQL('%' . $q . '%');
            $whereQ = " AND (b.order_id LIKE '" . $qEsc . "' OR o.buyer_login LIKE '" . $qEsc . "')";
        }

        $statusWhere = $this->orderStateWhere('o.status', $orderState);

        $normO = $this->normalizeKeySql('o.checkout_form_id');
        $normB = $this->normalizeKeySql('b.order_id');

        $bfJoin = '';
        $bfWhere = '';
        if ($cancelledNoRefund) {
            $bfJoin = " LEFT JOIN (" . $this->feesAggNoDateSql($ids) . ") bf\n"
                . "   ON (bf.id_allegropro_account=b.id_allegropro_account AND bf.order_key={$normB})";
            // pending > 0.01 AND charged exists
            $bfWhere = " AND (ABS(IFNULL(bf.neg_sum,0)) - IFNULL(bf.pos_sum,0)) > 0.01 AND IFNULL(bf.neg_sum,0) < 0";
        }

        $sql = "SELECT COUNT(*) FROM (\n"
            . "  SELECT b.id_allegropro_account, b.order_id\n"
            . "  FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b\n"
            . "  LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o\n"
            . "    ON (o.id_allegropro_account=b.id_allegropro_account AND {$normO} = {$normB})\n"
            . $bfJoin . "\n"
            . "  WHERE b.id_allegropro_account IN {$in}\n"
            . "    AND b.order_id IS NOT NULL AND b.order_id <> ''\n"
            . "    AND b.occurred_at BETWEEN '" . $from . "' AND '" . $to . "'\n"
            . "    AND {$feeWhere}\n"
            . "    {$whereQ}\n"
            . "    {$statusWhere}\n"
            . "    {$bfWhere}\n"
            . "  GROUP BY b.id_allegropro_account, b.order_id\n"
            . ") t";

        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * TRYB B: liczba zamówień złożonych w okresie (filtr po created_at_allegro).
     */
    public function countOrders($accountIds, string $dateFrom, string $dateTo, string $q = '', string $orderState = 'all', bool $cancelledNoRefund = false): int
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $whereQ = '';
        if ($q !== '') {
            $qEsc = pSQL('%' . $q . '%');
            $whereQ = " AND (o.checkout_form_id LIKE '" . $qEsc . "' OR o.buyer_login LIKE '" . $qEsc . "')";
        }

        $ids = $this->normalizeAccountIds($accountIds);
        $in = $this->buildIn($ids);

        $statusWhere = $this->orderStateWhere('o.status', $orderState);

        $bfJoin = '';
        $bfWhere = '';
        if ($cancelledNoRefund) {
            $normO = $this->normalizeKeySql('o.checkout_form_id');
            $bfJoin = " LEFT JOIN (" . $this->feesAggNoDateSql($ids) . ") bf\n"
                . "   ON (bf.id_allegropro_account=o.id_allegropro_account AND bf.order_key={$normO})";
            $bfWhere = " AND (ABS(IFNULL(bf.neg_sum,0)) - IFNULL(bf.pos_sum,0)) > 0.01 AND IFNULL(bf.neg_sum,0) < 0";
        }

        $sql = "SELECT COUNT(*)\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_order` o\n"
            . $bfJoin . "\n"
            . "WHERE o.id_allegropro_account IN {$in}\n"
            . "  AND o.created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'\n"
            . "  {$statusWhere}\n"
            . "  {$bfWhere}\n"
            . "  {$whereQ}";

        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * TRYB A: lista zamówień (order_id) które mają opłaty zaksięgowane w okresie.
     */
    public function getOrdersWithFeesBilling($accountIds, string $dateFrom, string $dateTo, string $q = '', int $limit = 50, int $offset = 0, string $orderState = 'all', bool $cancelledNoRefund = false): array
    {
        $ids = $this->normalizeAccountIds($accountIds);
        $in = $this->buildIn($ids);

        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $limit = max(1, min(500, (int)$limit));
        $offset = max(0, (int)$offset);

        $tn = "LOWER(IFNULL(b.type_name,''))";
        $feeWhere = $this->feeWhere('b.value_amount', $tn);

        $whereQ = '';
        if ($q !== '') {
            $qEsc = pSQL('%' . $q . '%');
            $whereQ = " AND (b.order_id LIKE '" . $qEsc . "' OR o.buyer_login LIKE '" . $qEsc . "')";
        }

        $statusWhere = $this->orderStateWhere('o.status', $orderState);

        $normO = $this->normalizeKeySql('o.checkout_form_id');
        $normB = $this->normalizeKeySql('b.order_id');

        // agregacje do sprzedaży bez dostawy
        $itemsAgg = $this->itemsAggSql();
        $shipAgg = $this->shippingAggSql();

        // agregacja opłat (bez filtra daty) do wykrycia "do zwrotu"
        $bfJoin = " LEFT JOIN (" . $this->feesAggNoDateSql($ids) . ") bf\n"
            . "   ON (bf.id_allegropro_account=b.id_allegropro_account AND bf.order_key={$normB})";
        $bfWhere = '';
        if ($cancelledNoRefund) {
            $bfWhere = " AND (ABS(IFNULL(bf.neg_sum,0)) - IFNULL(bf.pos_sum,0)) > 0.01 AND IFNULL(bf.neg_sum,0) < 0";
        }

        $sql = "SELECT\n"
            . "    b.id_allegropro_account,\n"
            . "    b.order_id AS checkout_form_id,\n"
            . "    MAX(b.occurred_at) AS occurred_at_max,\n"
            . "    SUM(b.value_amount) AS fees_total,\n"
            . "    SUM(CASE WHEN b.value_amount < 0 THEN b.value_amount ELSE 0 END) AS fees_neg_period,\n"
            . "    SUM(CASE WHEN b.value_amount > 0 THEN b.value_amount ELSE 0 END) AS fees_pos_period,\n"
            . "    o.status AS order_status,\n"
            . "    o.buyer_login,\n"
            . "    o.total_amount AS order_total_amount,\n"
            . "    o.currency,\n"
            . "    o.created_at_allegro,\n"
            . "    a.label AS account_label,\n"
            . "    CASE\n"
            . "      WHEN o.checkout_form_id IS NULL THEN NULL\n"
            . "      WHEN oi.items_total IS NOT NULL AND oi.items_total > 0 THEN oi.items_total\n"
            . "      ELSE GREATEST(IFNULL(o.total_amount,0) - IFNULL(os.shipping_amount,0), 0)\n"
            . "    END AS sales_amount,\n"
            . "    IFNULL(os.shipping_amount,0) AS shipping_amount,\n"
            . "    IFNULL(bf.neg_sum,0) AS fees_neg_all,\n"
            . "    IFNULL(bf.pos_sum,0) AS fees_pos_all\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b\n"
            . "LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o\n"
            . "  ON (o.id_allegropro_account=b.id_allegropro_account AND {$normO} = {$normB})\n"
            . "LEFT JOIN (" . $itemsAgg . ") oi ON (oi.checkout_form_id = o.checkout_form_id)\n"
            . "LEFT JOIN (" . $shipAgg . ") os ON (os.checkout_form_id = o.checkout_form_id)\n"
            . "LEFT JOIN `" . _DB_PREFIX_ . "allegropro_account` a\n"
            . "  ON (a.id_allegropro_account=b.id_allegropro_account)\n"
            . $bfJoin . "\n"
            . "WHERE b.id_allegropro_account IN {$in}\n"
            . "  AND b.order_id IS NOT NULL AND b.order_id <> ''\n"
            . "  AND b.occurred_at BETWEEN '" . $from . "' AND '" . $to . "'\n"
            . "  AND {$feeWhere}\n"
            . "  {$whereQ}\n"
            . "  {$statusWhere}\n"
            . "  {$bfWhere}\n"
            . "GROUP BY b.id_allegropro_account, b.order_id\n"
            . "ORDER BY occurred_at_max DESC\n"
            . "LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $rows = Db::getInstance()->executeS($sql) ?: [];
        foreach ($rows as &$r) {
            $r['id_allegropro_account'] = (int)($r['id_allegropro_account'] ?? 0);

            $sales = isset($r['sales_amount']) ? (float)$r['sales_amount'] : 0.0;
            $r['sales_amount'] = ($sales > 0.0) ? $sales : null;

            // zgodność z istniejącym TPL: total_amount = sprzedaż (bez dostawy)
            $r['total_amount'] = $r['sales_amount'];

            $ship = isset($r['shipping_amount']) ? (float)$r['shipping_amount'] : 0.0;
            $r['shipping_amount'] = $ship;

            $r['fees_total'] = (float)($r['fees_total'] ?? 0);

            $negAll = (float)($r['fees_neg_all'] ?? 0);
            $posAll = (float)($r['fees_pos_all'] ?? 0);
            $feesCharged = ($negAll < 0) ? abs($negAll) : 0.0;
            $feesRefunded = ($posAll > 0) ? $posAll : 0.0;
            $feesBalance = max(0.0, $feesCharged - $feesRefunded);

            // "Do zwrotu" ma sens tylko dla anulowanych/nieopłaconych.
            $status = strtoupper((string)($r['order_status'] ?? ''));
            $refundExpected = in_array($status, ['CANCELLED', 'FILLED_IN'], true);
            $feesPending = $refundExpected ? $feesBalance : 0.0;

            $r['fees_charged'] = $feesCharged;
            $r['fees_refunded'] = $feesRefunded;
            $r['fees_balance'] = $feesBalance;
            $r['refund_expected'] = $refundExpected ? 1 : 0;
            $r['fees_pending'] = $feesPending;

            // date_display = data operacji billing
            $r['date_display'] = (string)($r['occurred_at_max'] ?? $r['created_at_allegro'] ?? '');
            $r['net_after_fees'] = ($r['sales_amount'] !== null) ? ((float)$r['sales_amount'] + (float)$r['fees_total']) : null;
        }
        unset($r);

        return $rows;
    }

    /**
     * TRYB B: lista zamówień złożonych w okresie + opłaty policzone dla tych zamówień (bez filtra daty opłat).
     */
    public function getOrdersWithFeesOrders($accountIds, string $dateFrom, string $dateTo, string $q = '', int $limit = 50, int $offset = 0, string $orderState = 'all', bool $cancelledNoRefund = false): array
    {
        $ids = $this->normalizeAccountIds($accountIds);
        $orders = $this->listOrdersMulti($ids, $dateFrom, $dateTo, $q, $limit, $offset, $orderState, $cancelledNoRefund);

        // Zbuduj listę kandydatów order_id dla widocznych zamówień (dokładny + bez myślników + lower/upper).
        $candidates = [];
        foreach ($orders as $o) {
            $cf = (string)($o['checkout_form_id'] ?? '');
            if ($cf === '') continue;
            $candidates[] = $cf;
            $dash = str_replace('_', '-', $cf);
            if ($dash !== '' && $dash !== $cf) $candidates[] = $dash;
            $under = str_replace('-', '_', $cf);
            if ($under !== '' && $under !== $cf) $candidates[] = $under;
            $noSep = str_replace(['-','_'], '', $cf);
            if ($noSep !== '' && $noSep !== $cf) $candidates[] = $noSep;
            $lower = strtolower($cf);
            if ($lower !== $cf) $candidates[] = $lower;
            $upper = strtoupper($cf);
            if ($upper !== $cf) $candidates[] = $upper;
        }
        $candidates = array_values(array_unique($candidates));

        $feeMapRaw = $this->billing->sumByOrderIdsMultiNoDate($ids, $candidates);
        $breakdownRaw = $this->sumChargesRefundsByOrderIdsMultiNoDate($ids, $candidates);

        // Normalizacja pomocnicza (dla wariantów id bez myślników)
        $feeMapNorm = [];
        $breakdownNorm = [];
        foreach ($feeMapRaw as $aid => $ordersMap) {
            if (!is_array($ordersMap)) continue;
            foreach ($ordersMap as $oid => $sum) {
                $k = $this->normalizeId((string)$oid);
                if ($k === '') continue;
                if (!isset($feeMapNorm[$aid])) $feeMapNorm[$aid] = [];
                if (!isset($feeMapNorm[$aid][$k])) $feeMapNorm[$aid][$k] = 0.0;
                $feeMapNorm[$aid][$k] += (float)$sum;
            }
        }
        foreach ($breakdownRaw as $aid => $ordersMap) {
            if (!is_array($ordersMap)) continue;
            foreach ($ordersMap as $oid => $bd) {
                $k = $this->normalizeId((string)$oid);
                if ($k === '') continue;
                if (!isset($breakdownNorm[$aid])) $breakdownNorm[$aid] = [];
                if (!isset($breakdownNorm[$aid][$k])) {
                    $breakdownNorm[$aid][$k] = ['neg_sum' => 0.0, 'pos_sum' => 0.0];
                }
                $breakdownNorm[$aid][$k]['neg_sum'] += (float)($bd['neg_sum'] ?? 0);
                $breakdownNorm[$aid][$k]['pos_sum'] += (float)($bd['pos_sum'] ?? 0);
            }
        }

        foreach ($orders as &$o) {
            $cf = (string)$o['checkout_form_id'];
            $aid = (int)($o['id_allegropro_account'] ?? 0);
            $fees = 0.0;
            $negAll = 0.0;
            $posAll = 0.0;

            if ($aid > 0 && isset($feeMapRaw[$aid]) && is_array($feeMapRaw[$aid]) && isset($feeMapRaw[$aid][$cf])) {
                $fees = (float)$feeMapRaw[$aid][$cf];
            } else {
                $k = $this->normalizeId($cf);
                if ($aid > 0 && $k !== '' && isset($feeMapNorm[$aid]) && isset($feeMapNorm[$aid][$k])) {
                    $fees = (float)$feeMapNorm[$aid][$k];
                }
            }

            if ($aid > 0 && isset($breakdownRaw[$aid]) && is_array($breakdownRaw[$aid]) && isset($breakdownRaw[$aid][$cf])) {
                $negAll = (float)($breakdownRaw[$aid][$cf]['neg_sum'] ?? 0);
                $posAll = (float)($breakdownRaw[$aid][$cf]['pos_sum'] ?? 0);
            } else {
                $k = $this->normalizeId($cf);
                if ($aid > 0 && $k !== '' && isset($breakdownNorm[$aid]) && isset($breakdownNorm[$aid][$k])) {
                    $negAll = (float)($breakdownNorm[$aid][$k]['neg_sum'] ?? 0);
                    $posAll = (float)($breakdownNorm[$aid][$k]['pos_sum'] ?? 0);
                }
            }

            $feesCharged = ($negAll < 0) ? abs($negAll) : 0.0;
            $feesRefunded = ($posAll > 0) ? $posAll : 0.0;
            $feesBalance = max(0.0, $feesCharged - $feesRefunded);

            // "Do zwrotu" ma sens tylko dla anulowanych/nieopłaconych.
            $status = strtoupper((string)($o['order_status'] ?? ''));
            $refundExpected = in_array($status, ['CANCELLED', 'FILLED_IN'], true);
            $feesPending = $refundExpected ? $feesBalance : 0.0;

            $o['fees_total'] = $fees;
            $o['fees_charged'] = $feesCharged;
            $o['fees_refunded'] = $feesRefunded;
            $o['fees_balance'] = $feesBalance;
            $o['refund_expected'] = $refundExpected ? 1 : 0;
            $o['fees_pending'] = $feesPending;

            $sales = isset($o['sales_amount']) ? (float)$o['sales_amount'] : 0.0;
            $o['sales_amount'] = ($sales > 0.0) ? $sales : null;

            $o['net_after_fees'] = ($o['sales_amount'] !== null) ? ((float)$o['sales_amount'] + $fees) : null;
            $o['date_display'] = (string)($o['created_at_allegro'] ?? '');
        }
        unset($o);

        return $orders;
    }

    /**
     * Szczegóły zamówienia do modala.
     * @param bool $ignoreBillingDate Jeśli true: opłaty bez filtra daty (wszystkie dla zamówienia)
     */
    public function getOrderDetails(int $accountId, string $checkoutFormId, string $dateFrom, string $dateTo, bool $ignoreBillingDate = false): array
    {
        $order = $this->getOrderRow($accountId, $checkoutFormId);

        // Dopnij sprzedaż (bez dostawy) + koszt dostawy na podstawie tabel order_item / order_shipping.
        if ($order && !empty($order['checkout_form_id'])) {
            $salesInfo = $this->getSalesAndShippingForCheckoutFormId((string)$order['checkout_form_id']);
            $orderTotalFull = (float)($order['total_amount'] ?? 0);
            $itemsTotal = (float)($salesInfo['items_total'] ?? 0);
            $shippingAmount = (float)($salesInfo['shipping_amount'] ?? 0);

            $salesAmount = ($itemsTotal > 0)
                ? $itemsTotal
                : max(0.0, $orderTotalFull - $shippingAmount);

            $order['order_total_amount'] = $orderTotalFull;
            $order['sales_amount'] = $salesAmount;
            $order['shipping_amount'] = $shippingAmount;
        }

        $cf = $this->sanitizeCheckoutFormId($checkoutFormId);
        $candidates = [];
        if ($cf !== '') {
            $candidates[] = $cf;
            $dash = str_replace('_', '-', $cf);
            if ($dash !== '' && $dash !== $cf) $candidates[] = $dash;
            $under = str_replace('-', '_', $cf);
            if ($under !== '' && $under !== $cf) $candidates[] = $under;
            $noSep = str_replace(['-','_'], '', $cf);
            if ($noSep !== '' && $noSep !== $cf) $candidates[] = $noSep;
            $lower = strtolower($cf);
            if ($lower !== $cf) $candidates[] = $lower;
            $upper = strtoupper($cf);
            if ($upper !== $cf) $candidates[] = $upper;
        }

        // Lista pozycji billing (zależnie od trybu: billing=zakres dat, orders=bez dat).
        if ($ignoreBillingDate) {
            $items = $this->billing->listForOrderCandidatesNoDate($accountId, $candidates ?: [$checkoutFormId]);
        } else {
            $items = $this->billing->listForOrderCandidates($accountId, $candidates ?: [$checkoutFormId], $dateFrom, $dateTo);
        }

        // Dodatkowe: globalne (bez filtra daty) pobrane/zwroty opłat — do weryfikacji prowizji dla anulowanych.
        $itemsAll = $this->billing->listForOrderCandidatesNoDate($accountId, $candidates ?: [$checkoutFormId]);
        $feesNegAll = 0.0;
        $feesPosAll = 0.0;
        foreach ($itemsAll as $itAll) {
            $amt = (float)($itAll['value_amount'] ?? 0);
            if ($amt < 0) $feesNegAll += $amt;
            if ($amt > 0) $feesPosAll += $amt;
        }
        $feesCharged = ($feesNegAll < 0) ? abs($feesNegAll) : 0.0;
        $feesRefunded = ($feesPosAll > 0) ? $feesPosAll : 0.0;
        $feesBalance = max(0.0, $feesCharged - $feesRefunded);

        // "Do zwrotu" ma sens tylko dla anulowanych/nieopłaconych.
        $status = strtoupper((string)($order['status'] ?? ''));
        $refundExpected = in_array($status, ['CANCELLED', 'FILLED_IN'], true);
        $feesPending = $refundExpected ? $feesBalance : 0.0;

        $cats = [
            'commission' => 0.0,
            'smart' => 0.0,
            'delivery' => 0.0,
            'promotion' => 0.0,
            'refunds' => 0.0,
            'other' => 0.0,
            'total' => 0.0,
        ];

        foreach ($items as &$it) {
            $amount = (float)$it['value_amount'];
            $cats['total'] += $amount;
            $cat = $this->classify((string)($it['type_id'] ?? ''), (string)($it['type_name'] ?? ''), $amount);
            $it['category'] = $cat;
            if (isset($cats[$cat])) {
                $cats[$cat] += $amount;
            } else {
                $cats['other'] += $amount;
            }
        }
        unset($it);

        $salesBase = 0.0;
        if ($order) {
            // preferuj sales_amount (bez dostawy); fallback do total_amount
            $salesBase = (float)($order['sales_amount'] ?? $order['total_amount'] ?? 0);
        }
        $netAfterFees = $order ? ((float)$salesBase + (float)$cats['total']) : (float)$cats['total'];

        return [
            'order' => $order,
            'items' => $items,
            'cats' => $cats,
            'net_after_fees' => (float)$netAfterFees,
            'fees_charged' => (float)$feesCharged,
            'fees_refunded' => (float)$feesRefunded,
            'fees_balance' => (float)$feesBalance,
            'refund_expected' => $refundExpected ? 1 : 0,
            'fees_pending' => (float)$feesPending,
        ];
    }

    private function contains(string $haystack, string $needle): bool
    {
        return mb_strpos($haystack, $needle) !== false;
    }

    private function classify(string $typeId, string $typeName, float $amount): string
    {
        $n = mb_strtolower($typeName);

        if ($typeId === 'SUC' || $this->contains($n, 'prowiz')) return 'commission';
        if ($this->contains($n, 'smart')) return 'smart';
        if ($this->contains($n, 'dostaw') || $this->contains($n, 'przesy')) return 'delivery';
        if ($this->contains($n, 'promow') || $this->contains($n, 'reklam')) return 'promotion';
        if ($this->contains($n, 'zwrot') || $this->contains($n, 'rabat') || $this->contains($n, 'korekt') || $this->contains($n, 'rekompens')) return 'refunds';
        return 'other';
    }

    /**
     * SUMA sprzedaży (bez dostawy) zamówień złożonych w okresie (created_at_allegro).
     */
    private function sumOrdersSalesMulti(array $accountIds, string $dateFrom, string $dateTo, string $orderState = 'all', bool $cancelledNoRefund = false): float
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $in = $this->buildIn($accountIds);

        $itemsAgg = $this->itemsAggSql();
        $shipAgg = $this->shippingAggSql();

        $statusWhere = $this->orderStateWhere('o.status', $orderState);

        $bfJoin = '';
        $bfWhere = '';
        if ($cancelledNoRefund) {
            $normO = $this->normalizeKeySql('o.checkout_form_id');
            $bfJoin = " LEFT JOIN (" . $this->feesAggNoDateSql($accountIds) . ") bf\n"
                . "   ON (bf.id_allegropro_account=o.id_allegropro_account AND bf.order_key={$normO})";
            $bfWhere = " AND (ABS(IFNULL(bf.neg_sum,0)) - IFNULL(bf.pos_sum,0)) > 0.01 AND IFNULL(bf.neg_sum,0) < 0";
        }

        $sql = "SELECT SUM(\n"
            . "  CASE\n"
            . "    WHEN oi.items_total IS NOT NULL AND oi.items_total > 0 THEN oi.items_total\n"
            . "    ELSE GREATEST(IFNULL(o.total_amount,0) - IFNULL(os.shipping_amount,0), 0)\n"
            . "  END\n"
            . ") AS sales_total\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_order` o\n"
            . "LEFT JOIN (" . $itemsAgg . ") oi ON (oi.checkout_form_id = o.checkout_form_id)\n"
            . "LEFT JOIN (" . $shipAgg . ") os ON (os.checkout_form_id = o.checkout_form_id)\n"
            . $bfJoin . "\n"
            . "WHERE o.id_allegropro_account IN {$in}\n"
            . "  AND o.created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'\n"
            . "  {$statusWhere}\n"
            . "  {$bfWhere}";

        return (float)Db::getInstance()->getValue($sql);
    }

    /**
     * Pobiera checkout_form_id zamówień złożonych w okresie (do obliczeń sum opłat w trybie orders).
     *
     * @return string[]
     */
    private function listOrderIdsInRangeMulti(array $accountIds, string $dateFrom, string $dateTo, string $orderState = 'all', bool $cancelledNoRefund = false): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $in = $this->buildIn($accountIds);
        $statusWhere = $this->orderStateWhere('o.status', $orderState);

        $bfJoin = '';
        $bfWhere = '';
        if ($cancelledNoRefund) {
            $normO = $this->normalizeKeySql('o.checkout_form_id');
            $bfJoin = " LEFT JOIN (" . $this->feesAggNoDateSql($accountIds) . ") bf\n"
                . "   ON (bf.id_allegropro_account=o.id_allegropro_account AND bf.order_key={$normO})";
            $bfWhere = " AND (ABS(IFNULL(bf.neg_sum,0)) - IFNULL(bf.pos_sum,0)) > 0.01 AND IFNULL(bf.neg_sum,0) < 0";
        }

        $sql = "SELECT o.checkout_form_id\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_order` o\n"
            . $bfJoin . "\n"
            . "WHERE o.id_allegropro_account IN {$in}\n"
            . "  AND o.created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'\n"
            . "  {$statusWhere}\n"
            . "  {$bfWhere}";

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $id = trim((string)($r['checkout_form_id'] ?? ''));
            if ($id !== '') $out[] = $id;
        }
        return array_values(array_unique($out));
    }

    /**
     * Buduje listę kandydatów order_id (dokładny + bez myślników + lower/upper).
     * @param string[] $orderIds
     * @return string[]
     */
    private function buildOrderIdCandidates(array $orderIds): array
    {
        $c = [];
        foreach ($orderIds as $id) {
            $id = trim((string)$id);
            if ($id === '') continue;
            $c[] = $id;
            $dash = str_replace('_', '-', $id);
            if ($dash !== '' && $dash !== $id) $c[] = $dash;
            $under = str_replace('-', '_', $id);
            if ($under !== '' && $under !== $id) $c[] = $under;
            $noSep = str_replace(['-','_'], '', $id);
            if ($noSep !== '' && $noSep !== $id) $c[] = $noSep;
            $lower = strtolower($id);
            if ($lower !== $id) $c[] = $lower;
            $upper = strtoupper($id);
            if ($upper !== $id) $c[] = $upper;
        }
        return array_values(array_unique($c));
    }

    /**
     * Sprzedaż (bez dostawy) dla zamówień, do których naliczono opłaty w okresie (billing).
     */
    private function sumOrdersSalesForBillingMulti(array $accountIds, string $dateFrom, string $dateTo, string $orderState = 'all', bool $cancelledNoRefund = false): float
    {
        $in = $this->buildIn($accountIds);
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(b.type_name,''))";
        $feeWhere = $this->feeWhere('b.value_amount', $tn);

        $itemsAgg = $this->itemsAggSql();
        $shipAgg = $this->shippingAggSql();

        $statusWhere = $this->orderStateWhere('o.status', $orderState);

        $bfJoin = '';
        $bfWhere = '';
        if ($cancelledNoRefund) {
            $normO = $this->normalizeKeySql('o.checkout_form_id');
            $bfJoin = " LEFT JOIN (" . $this->feesAggNoDateSql($accountIds) . ") bf\n"
                . "   ON (bf.id_allegropro_account=o.id_allegropro_account AND bf.order_key={$normO})";
            $bfWhere = " AND (ABS(IFNULL(bf.neg_sum,0)) - IFNULL(bf.pos_sum,0)) > 0.01 AND IFNULL(bf.neg_sum,0) < 0";
        }

        // Suma sprzedaży dla zamówień, do których naliczono opłaty w okresie (billing) — dopasowanie po znormalizowanym UUID.
        $sql = "SELECT SUM(\n"
            . "  CASE\n"
            . "    WHEN oi.items_total IS NOT NULL AND oi.items_total > 0 THEN oi.items_total\n"
            . "    ELSE GREATEST(IFNULL(o.total_amount,0) - IFNULL(os.shipping_amount,0), 0)\n"
            . "  END\n"
            . ") AS sales_total\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_order` o\n"
            . "INNER JOIN (\n"
            . "  SELECT DISTINCT b.id_allegropro_account, " . $this->normalizeKeySql('b.order_id') . " AS order_key\n"
            . "  FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b\n"
            . "  WHERE b.id_allegropro_account IN {$in}\n"
            . "    AND b.order_id IS NOT NULL AND b.order_id <> ''\n"
            . "    AND b.occurred_at BETWEEN '" . $from . "' AND '" . $to . "'\n"
            . "    AND {$feeWhere}\n"
            . ") x ON (x.id_allegropro_account=o.id_allegropro_account AND x.order_key = " . $this->normalizeKeySql('o.checkout_form_id') . ")\n"
            . "LEFT JOIN (" . $itemsAgg . ") oi ON (oi.checkout_form_id=o.checkout_form_id)\n"
            . "LEFT JOIN (" . $shipAgg . ") os ON (os.checkout_form_id=o.checkout_form_id)\n"
            . $bfJoin . "\n"
            . "WHERE 1=1\n"
            . "  {$statusWhere}\n"
            . "  {$bfWhere}";

        return (float)Db::getInstance()->getValue($sql);
    }

    /**
     * Lista zamówień (orders) w okresie (created_at_allegro) — baza dla trybu orders.
     */
    private function listOrdersMulti(array $accountIds, string $dateFrom, string $dateTo, string $q = '', int $limit = 50, int $offset = 0, string $orderState = 'all', bool $cancelledNoRefund = false): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $in = $this->buildIn($accountIds);

        $limit = max(1, min(500, (int)$limit));
        $offset = max(0, (int)$offset);

        $whereQ = '';
        if ($q !== '') {
            $qEsc = pSQL('%' . $q . '%');
            $whereQ = " AND (o.checkout_form_id LIKE '" . $qEsc . "' OR o.buyer_login LIKE '" . $qEsc . "')";
        }

        $statusWhere = $this->orderStateWhere('o.status', $orderState);

        $itemsAgg = $this->itemsAggSql();
        $shipAgg = $this->shippingAggSql();

        $bfJoin = '';
        $bfWhere = '';
        if ($cancelledNoRefund) {
            $normO = $this->normalizeKeySql('o.checkout_form_id');
            $bfJoin = " LEFT JOIN (" . $this->feesAggNoDateSql($accountIds) . ") bf\n"
                . "   ON (bf.id_allegropro_account=o.id_allegropro_account AND bf.order_key={$normO})";
            $bfWhere = " AND (ABS(IFNULL(bf.neg_sum,0)) - IFNULL(bf.pos_sum,0)) > 0.01 AND IFNULL(bf.neg_sum,0) < 0";
        }

        $sql = "SELECT\n"
            . "  o.id_allegropro_account, o.checkout_form_id, o.status AS order_status, o.buyer_login, o.total_amount AS order_total_amount, o.currency, o.created_at_allegro,\n"
            . "  a.label AS account_label,\n"
            . "  CASE\n"
            . "    WHEN oi.items_total IS NOT NULL AND oi.items_total > 0 THEN oi.items_total\n"
            . "    ELSE GREATEST(IFNULL(o.total_amount,0) - IFNULL(os.shipping_amount,0), 0)\n"
            . "  END AS sales_amount,\n"
            . "  IFNULL(os.shipping_amount,0) AS shipping_amount\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_order` o\n"
            . "LEFT JOIN `" . _DB_PREFIX_ . "allegropro_account` a ON (a.id_allegropro_account = o.id_allegropro_account)\n"
            . "LEFT JOIN (" . $itemsAgg . ") oi ON (oi.checkout_form_id = o.checkout_form_id)\n"
            . "LEFT JOIN (" . $shipAgg . ") os ON (os.checkout_form_id = o.checkout_form_id)\n"
            . $bfJoin . "\n"
            . "WHERE o.id_allegropro_account IN {$in}\n"
            . "  AND o.created_at_allegro BETWEEN '" . $from . "' AND '" . $to . "'\n"
            . "  {$statusWhere}\n"
            . "  {$bfWhere}\n"
            . "  {$whereQ}\n"
            . "ORDER BY o.created_at_allegro DESC\n"
            . "LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $rows = Db::getInstance()->executeS($sql) ?: [];
        foreach ($rows as &$r) {
            $r['id_allegropro_account'] = (int)($r['id_allegropro_account'] ?? 0);

            $sales = isset($r['sales_amount']) ? (float)$r['sales_amount'] : 0.0;
            $r['sales_amount'] = ($sales > 0.0) ? $sales : null;

            $ship = isset($r['shipping_amount']) ? (float)$r['shipping_amount'] : 0.0;
            $r['shipping_amount'] = $ship;

            // zachowaj zgodność: total_amount w UI = sprzedaż (bez dostawy)
            $r['total_amount'] = $r['sales_amount'];
        }
        unset($r);

        return $rows;
    }

    /**
     * Pobiera w jednym strzale SUM(negatives) i SUM(positives) dla listy orderIds (bez filtra daty).
     *
     * @param int[] $accountIds
     * @param string[] $orderIds
     * @return array<int, array<string, array{neg_sum:float,pos_sum:float}>>
     */
    private function sumChargesRefundsByOrderIdsMultiNoDate(array $accountIds, array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('trim', array_map('strval', $orderIds)))));
        if (empty($orderIds)) {
            return [];
        }

        $accIn = $this->buildIn($accountIds);

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->feeWhere('value_amount', $tn);

        $out = [];
        $chunkSize = 500;
        for ($i = 0; $i < count($orderIds); $i += $chunkSize) {
            $chunk = array_slice($orderIds, $i, $chunkSize);
            $vals = [];
            foreach ($chunk as $id) {
                if ($id === '') continue;
                $vals[] = "'" . pSQL($id) . "'";
            }
            if (empty($vals)) continue;

            $sql = "SELECT id_allegropro_account, order_id,\n"
                . "       SUM(CASE WHEN value_amount < 0 THEN value_amount ELSE 0 END) AS neg_sum,\n"
                . "       SUM(CASE WHEN value_amount > 0 THEN value_amount ELSE 0 END) AS pos_sum\n"
                . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`\n"
                . "WHERE id_allegropro_account IN {$accIn}\n"
                . "  AND order_id IS NOT NULL AND order_id <> ''\n"
                . "  AND order_id IN (" . implode(',', $vals) . ")\n"
                . "  AND {$feeWhere}\n"
                . "GROUP BY id_allegropro_account, order_id";

            $rows = Db::getInstance()->executeS($sql) ?: [];
            foreach ($rows as $r) {
                $aid = (int)($r['id_allegropro_account'] ?? 0);
                if ($aid <= 0) continue;
                $oid = (string)($r['order_id'] ?? '');
                if ($oid === '') continue;

                if (!isset($out[$aid])) $out[$aid] = [];
                $out[$aid][$oid] = [
                    'neg_sum' => (float)($r['neg_sum'] ?? 0),
                    'pos_sum' => (float)($r['pos_sum'] ?? 0),
                ];
            }
        }

        return $out;
    }

    /**
     * Kategorie opłat (billing mode) z filtrem po statusie zamówienia / "bez zwrotu".
     *
     * @return array{total:float,commission:float,smart:float,delivery:float,promotion:float,refunds:float}
     */
    private function getCategorySumsBillingFilteredMulti(array $accountIds, string $dateFrom, string $dateTo, string $orderState, bool $cancelledNoRefund): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');
        $in = $this->buildIn($accountIds);

        $tn = "LOWER(IFNULL(b.type_name,''))";
        $feeWhere = $this->feeWhere('b.value_amount', $tn);

        $statusWhere = $this->orderStateWhere('o.status', $orderState);

        $normO = $this->normalizeKeySql('o.checkout_form_id');
        $normB = $this->normalizeKeySql('b.order_id');

        $bfJoin = '';
        $bfWhere = '';
        if ($cancelledNoRefund) {
            $bfJoin = " LEFT JOIN (" . $this->feesAggNoDateSql($accountIds) . ") bf\n"
                . "   ON (bf.id_allegropro_account=b.id_allegropro_account AND bf.order_key={$normB})";
            $bfWhere = " AND (ABS(IFNULL(bf.neg_sum,0)) - IFNULL(bf.pos_sum,0)) > 0.01 AND IFNULL(bf.neg_sum,0) < 0";
        }

        $sql = "SELECT\n"
            . "  SUM(b.value_amount) AS total,\n"
            . "  SUM(CASE WHEN (b.type_id='SUC' OR {$tn} LIKE '%prowiz%') THEN b.value_amount ELSE 0 END) AS commission,\n"
            . "  SUM(CASE WHEN ({$tn} LIKE '%smart%') THEN b.value_amount ELSE 0 END) AS smart,\n"
            . "  SUM(CASE WHEN ({$tn} LIKE '%dostaw%' OR {$tn} LIKE '%przesy%') THEN b.value_amount ELSE 0 END) AS delivery,\n"
            . "  SUM(CASE WHEN ({$tn} LIKE '%promow%' OR {$tn} LIKE '%reklam%') THEN b.value_amount ELSE 0 END) AS promotion,\n"
            . "  SUM(CASE WHEN ({$tn} LIKE '%zwrot%' OR {$tn} LIKE '%rabat%' OR {$tn} LIKE '%korekt%' OR {$tn} LIKE '%rekompens%') THEN b.value_amount ELSE 0 END) AS refunds\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry` b\n"
            . "LEFT JOIN `" . _DB_PREFIX_ . "allegropro_order` o\n"
            . "  ON (o.id_allegropro_account=b.id_allegropro_account AND {$normO} = {$normB})\n"
            . $bfJoin . "\n"
            . "WHERE b.id_allegropro_account IN {$in}\n"
            . "  AND b.occurred_at BETWEEN '" . $from . "' AND '" . $to . "'\n"
            . "  AND b.order_id IS NOT NULL AND b.order_id <> ''\n"
            . "  AND {$feeWhere}\n"
            . "  {$statusWhere}\n"
            . "  {$bfWhere}";

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

    private function getOrderRow(int $accountId, string $checkoutFormId): ?array
    {
        $cf = $this->sanitizeCheckoutFormId($checkoutFormId);
        if ($cf === '') {
            return null;
        }

        $key = pSQL($this->normalizeId($cf));

        $sql = 'SELECT checkout_form_id, status, buyer_login, total_amount, currency, created_at_allegro '
            . 'FROM `' . _DB_PREFIX_ . 'allegropro_order` '
            . 'WHERE id_allegropro_account=' . (int)$accountId . ' '
            . "AND (checkout_form_id='" . pSQL($cf) . "' OR LOWER(REPLACE(REPLACE(checkout_form_id,'-',''),'_',''))='" . $key . "') "
            . 'ORDER BY created_at_allegro DESC LIMIT 1';

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $row = !empty($rows[0]) ? $rows[0] : null;
        if (!$row) {
            return null;
        }
        $row['total_amount'] = (float)$row['total_amount'];
        return $row;
    }

    /**
     * Zwraca sumę pozycji (items_total) i koszt dostawy (shipping_amount) dla checkout_form_id.
     *
     * @return array{items_total:float,shipping_amount:float}
     */
    private function getSalesAndShippingForCheckoutFormId(string $checkoutFormId): array
    {
        $cf = pSQL(trim($checkoutFormId));
        if ($cf === '') {
            return ['items_total' => 0.0, 'shipping_amount' => 0.0];
        }

        $itemsSql = "SELECT SUM(quantity * price) FROM `" . _DB_PREFIX_ . "allegropro_order_item` WHERE checkout_form_id='" . $cf . "'";
        $shipSql = "SELECT MAX(cost_amount) FROM `" . _DB_PREFIX_ . "allegropro_order_shipping` WHERE checkout_form_id='" . $cf . "'";

        $items = (float)Db::getInstance()->getValue($itemsSql);
        $ship = (float)Db::getInstance()->getValue($shipSql);

        return [
            'items_total' => $items,
            'shipping_amount' => $ship,
        ];
    }

    private function normalizeId(string $id): string
    {
        $id = trim($id);
        if ($id === '') {
            return '';
        }
        $id = strtolower($id);
        $id = preg_replace('/[^a-z0-9]/', '', $id);
        return (string)$id;
    }

    private function sanitizeCheckoutFormId(string $id): string
    {
        $id = trim($id);
        $id = preg_replace('/[^A-Za-z0-9\-_=:\.]/', '', $id);
        return (string)$id;
    }
}
